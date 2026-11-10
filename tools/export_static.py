#!/usr/bin/env python3
"""Статична копія локального WordPress-сайту для показу (GitHub Pages).

Обходить усі внутрішні посилання, зберігає сторінки як <шлях>/index.html,
а стилі, скрипти, фото й PDF — поруч. Усі адреси переписує на відносні,
тож копія працює з будь-якої теки на будь-якому хостингу.

Запуск:  python3 tools/export_static.py http://127.0.0.1:9400 docs
"""
from __future__ import annotations
import os
import posixpath
import re
import shutil
import sys
import urllib.parse
import urllib.request
from collections import deque

BASE = sys.argv[1].rstrip('/')
OUT = sys.argv[2]
BASE_ESC = BASE.replace('/', '\\/')

SKIP = re.compile(r'^/(wp-json|wp-admin|wp-login\.php|xmlrpc\.php|wp-cron\.php)|/feed/?$|/embed/?$|/comments/feed|/trackback/?$')
ASSET_EXT = re.compile(r'\.(css|js|mjs|png|jpe?g|gif|webp|svg|ico|pdf|woff2?|ttf|eot|otf|mp4|webm|json|xml|txt|docx?|xlsx?)$', re.I)

# посилання в атрибутах, srcset, url() у стилях, а також адреси в JSON (\/)
ATTR_RX = re.compile(r'''(?P<a>\b(?:href|src|poster|data-src|action)=)(?P<q>["'])(?P<u>[^"'<>]*?)(?P=q)''', re.I)
SRCSET_RX = re.compile(r'''\bsrcset=(["'])([^"']*)\1''', re.I)
CSSURL_RX = re.compile(r'''url\(\s*(["']?)([^)"']+)\1\s*\)''')
ABS_RX = re.compile(re.escape(BASE) + r'''(/[^\s"'<>()\\]*)?''')
ABS_ESC_RX = re.compile(re.escape(BASE_ESC) + r'''((?:\\/[^\s"'<>()\\]*)*)''')

if os.path.isdir(OUT):
    shutil.rmtree(OUT)
os.makedirs(OUT)

saved = {}          # path -> local file
queue = deque()
seen = set()


def norm(url: str, cur: str = '/') -> str | None:
    """Внутрішній шлях без домену/запиту або None для зовнішніх."""
    url = url.strip().replace('&amp;', '&').replace('&#038;', '&')
    if not url or url.startswith(('#', 'mailto:', 'tel:', 'data:', 'javascript:')):
        return None
    if url.startswith('//'):
        return None
    if url.startswith(BASE):
        url = url[len(BASE):] or '/'
    elif re.match(r'^[a-z]+:', url, re.I):
        return None
    elif not url.startswith('/'):
        url = posixpath.normpath(posixpath.join(posixpath.dirname(cur), url))
    p = urllib.parse.urlsplit(url)
    if p.query and not ASSET_EXT.search(p.path):
        # сторінки з ?параметрами (пошук, ?p=) статично не відтворити
        if not re.match(r'^(ver|v)=', p.query):
            return None
    path = urllib.parse.unquote(p.path) or '/'
    if SKIP.search(path):
        return None
    return path


def local_file(path: str) -> str:
    if ASSET_EXT.search(path):
        return path.lstrip('/')
    return posixpath.join(path.strip('/'), 'index.html') if path.strip('/') else 'index.html'


def rel(from_file: str, path: str, frag: str = '') -> str:
    target = local_file(path)
    start = posixpath.dirname(from_file) or '.'
    if target.endswith('index.html'):
        d = posixpath.dirname(target) or '.'
        r = posixpath.relpath(d, start)
        r = './' if r == '.' else r + '/'
    else:
        r = posixpath.relpath(target, start)
    return urllib.parse.quote(r, safe='/.-_~:') + frag


def enqueue(path: str):
    if path not in seen:
        seen.add(path)
        queue.append(path)


def fetch(path: str):
    url = BASE + urllib.parse.quote(path, safe='/%-_.~')
    try:
        with urllib.request.urlopen(urllib.request.Request(url, headers={'User-Agent': 'vitryla-export'}), timeout=120) as r:
            return r.status, r.headers.get('Content-Type', ''), r.read()
    except urllib.error.HTTPError as e:
        return e.code, e.headers.get('Content-Type', ''), e.read()
    except Exception as e:  # noqa
        print('ERR', path, e)
        return 0, '', b''


def rewrite_html(html: str, me: str) -> str:
    def sub_attr(m):
        u = m.group('u')
        frag = ''
        if '#' in u:
            u, frag = u.split('#', 1)
            frag = '#' + frag
            if not u:
                return m.group(0)
        path = norm(u, '/' + me)
        if path is None:
            return m.group(0)
        enqueue(path)
        return f"{m.group('a')}{m.group('q')}{rel(me, path, frag)}{m.group('q')}"

    def sub_srcset(m):
        parts = []
        for item in m.group(2).split(','):
            bits = item.strip().split()
            if not bits:
                continue
            path = norm(bits[0], '/' + me)
            if path:
                enqueue(path)
                bits[0] = rel(me, path)
            parts.append(' '.join(bits))
        return f'srcset={m.group(1)}{", ".join(parts)}{m.group(1)}'

    def sub_css(m):
        path = norm(m.group(2), '/' + me)
        if path is None:
            return m.group(0)
        enqueue(path)
        return f'url({m.group(1)}{rel(me, path)}{m.group(1)})'

    def sub_abs(m):
        path = norm(BASE + (m.group(1) or '/'), '/' + me)
        if path is None:
            return m.group(0)
        enqueue(path)
        return rel(me, path)

    def sub_abs_esc(m):
        raw = (m.group(1) or '').replace('\\/', '/') or '/'
        path = norm(BASE + raw, '/' + me)
        if path is None:
            return m.group(0)
        enqueue(path)
        r = rel(me, path)
        if not r.startswith('.'):
            r = './' + r  # карта модулів (importmap) приймає лише ./ ../ або /
        return r.replace('/', '\\/')

    html = SRCSET_RX.sub(sub_srcset, html)
    html = ATTR_RX.sub(sub_attr, html)
    html = CSSURL_RX.sub(sub_css, html)
    html = ABS_ESC_RX.sub(sub_abs_esc, html)
    html = ABS_RX.sub(sub_abs, html)

    # Демонстраційна копія: не індексувати, пошук прибрати (статично не працює), емодзі-скрипт не потрібен.
    html = re.sub(r'<script[^>]*>[^<]*_wpemojiSettings.*?</script>', '', html, flags=re.S)
    html = re.sub(r'<script[^>]*id="wp-emoji-settings"[^>]*>.*?</script>', '', html, flags=re.S)
    html = html.replace('<head>', '<head>\n<meta name="robots" content="noindex, nofollow">\n'
                        '<style>.vit-utility .vit-search,.wp-block-search{display:none!important}</style>', 1)
    return html


def rewrite_css(css: str, me: str) -> str:
    def sub_css(m):
        u = m.group(2)
        if u.startswith('data:'):
            return m.group(0)
        path = norm(u, '/' + me)
        if path is None:
            return m.group(0)
        enqueue(path)
        return f'url({m.group(1)}{posixpath.relpath(local_file(path), posixpath.dirname(me) or ".")}{m.group(1)})'
    return CSSURL_RX.sub(sub_css, css)


enqueue('/')
enqueue('/karta-sajtu/')
enqueue('/novyny/')
pages = assets = 0
while queue:
    path = queue.popleft()
    status, ctype, body = fetch(path)
    if status != 200:
        if status:
            print(status, path)
        continue
    me = local_file(path)
    if 'text/html' in ctype and not ASSET_EXT.search(path):
        me = local_file(path if path.endswith('/') else path + '/')
        out = rewrite_html(body.decode('utf-8', 'replace'), me).encode('utf-8')
        pages += 1
    elif 'css' in ctype or path.endswith('.css'):
        out = rewrite_css(body.decode('utf-8', 'replace'), me).encode('utf-8')
        assets += 1
    else:
        out = body
        assets += 1
    dest = os.path.join(OUT, me)
    os.makedirs(os.path.dirname(dest) or OUT, exist_ok=True)
    with open(dest, 'wb') as f:
        f.write(out)
    if pages and pages % 50 == 0 and 'text/html' in ctype:
        print('…', pages, 'сторінок,', assets, 'файлів')

# 404 для GitHub Pages
st, ct, body = fetch('/tsiieyi-storinky-nemaie/')
if body:
    with open(os.path.join(OUT, '404.html'), 'w', encoding='utf-8') as f:
        f.write(rewrite_html(body.decode('utf-8', 'replace'), '404.html'))

with open(os.path.join(OUT, 'robots.txt'), 'w') as f:
    f.write('User-agent: *\nDisallow: /\n')
open(os.path.join(OUT, '.nojekyll'), 'w').close()
import subprocess
subprocess.run([sys.executable, '-I', os.path.join(os.path.dirname(os.path.abspath(__file__)), 'clean_static.py'), OUT])
print('Готово:', pages, 'сторінок,', assets, 'файлів')

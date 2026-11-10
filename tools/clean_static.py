#!/usr/bin/env python3
"""Прибирає зі статичної копії службові посилання WordPress на локальну адресу."""
import os, re, sys
ROOT = sys.argv[1]
LINK = re.compile(r"""<link[^>]+(?:dns-prefetch|EditURI|alternate|shortlink|https://api\.w\.org/)[^>]*127\.0\.0\.1[^>]*/?>\s*""", re.I)
IMAP = re.compile(r'(<script[^>]*type="importmap"[^>]*>)(.*?)(</script>)', re.S)
n = 0
for d, _, files in os.walk(ROOT):
    for f in files:
        if not f.endswith('.html'):
            continue
        p = os.path.join(d, f)
        s = open(p, encoding='utf-8').read()
        t = LINK.sub('', s)
        # карта модулів: адреси мають починатися з ./ ../ або /
        t = IMAP.sub(lambda m: m.group(1) + re.sub(r'":"(?![./]|https?:)', '":"./', m.group(2)) + m.group(3), t)
        if t != s:
            open(p, 'w', encoding='utf-8').write(t); n += 1
left = sum(open(os.path.join(d, f), encoding='utf-8').read().count('127.0.0.1') for d, _, fs in os.walk(ROOT) for f in fs if f.endswith('.html'))
print('очищено файлів:', n, '| залишилось згадок 127.0.0.1:', left)

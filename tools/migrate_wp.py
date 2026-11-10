#!/usr/bin/env python3
"""Одноразове перенесення вмісту з WordPress-версії сайту в JSON-файли нового сайту.

Запуск:  python3 tools/migrate_wp.py <шлях до .ht.sqlite> <wp-content/uploads> <корінь нового сайту>

Що переносить: налаштування, 60+ сторінок (блоки WordPress → прості блоки), новини,
документи, кадри, гуртки, файли (PDF, фото). Блоки WordPress розбираються в типи,
які вміє редагувати адмінка: p, h, list, cards, stats, faq, table, callout, buttons,
html, component, gallery.
"""
import html
import json
import os
import re
import shutil
import sqlite3
import sys
from datetime import datetime

DB, UPLOADS, ROOT = sys.argv[1], sys.argv[2], sys.argv[3]
CONTENT = os.path.join(ROOT, 'content')
FILES = os.path.join(ROOT, 'uploads')
os.makedirs(os.path.join(CONTENT, 'pages'), exist_ok=True)
os.makedirs(os.path.join(CONTENT, 'news'), exist_ok=True)

con = sqlite3.connect(f'file:{DB}?mode=ro', uri=True)
con.row_factory = sqlite3.Row
q = lambda sql, *a: con.execute(sql, a).fetchall()


# ── PHP unserialize (рядки, масиви, числа, булеві) ──
def unserialize(s: str):
    b = s.encode('utf-8')
    pos = 0

    def read():
        nonlocal pos
        t = chr(b[pos])
        if t == 'N':
            pos += 2
            return None
        if t in 'ibd':
            end = b.index(b';', pos)
            v = b[pos + 2:end].decode()
            pos = end + 1
            return int(v) if t == 'i' else (v == '1' if t == 'b' else float(v))
        if t == 's':
            colon = b.index(b':', pos + 2)
            n = int(b[pos + 2:colon])
            v = b[colon + 2:colon + 2 + n].decode('utf-8')
            pos = colon + 2 + n + 2
            return v
        if t == 'a':
            colon = b.index(b':', pos + 2)
            n = int(b[pos + 2:colon])
            pos = colon + 2
            out = {}
            for _ in range(n):
                k = read()
                out[k] = read()
            pos += 1
            return out
        raise ValueError('unsupported ' + t)
    return read()


def opt(name, default=None):
    r = q('select option_value from wp_options where option_name=?', name)
    if not r:
        return default
    v = r[0][0]
    try:
        return unserialize(v) if v[:2] in ('a:', 's:', 'i:', 'b:') else v
    except Exception:
        return v


def meta(pid):
    return {r['meta_key']: r['meta_value'] for r in q('select meta_key, meta_value from wp_postmeta where post_id=?', pid)}


def terms(pid, tax):
    return [r[0] for r in q('''select t.slug from wp_term_relationships r join wp_term_taxonomy tt on tt.term_taxonomy_id=r.term_taxonomy_id
        join wp_terms t on t.term_id=tt.term_id where r.object_id=? and tt.taxonomy=?''', pid, tax)]


def attached_file(aid):
    if not aid:
        return ''
    r = q("select meta_value from wp_postmeta where post_id=? and meta_key='_wp_attached_file'", int(aid))
    return r[0][0] if r else ''


def copy_upload(rel):
    """Копіює файл з uploads WordPress у uploads нового сайту, повертає шлях від кореня сайту."""
    if not rel:
        return ''
    src = os.path.join(UPLOADS, rel)
    dst = os.path.join(FILES, rel.replace('vitryla/', '', 1))
    if os.path.exists(src):
        os.makedirs(os.path.dirname(dst), exist_ok=True)
        if not os.path.exists(dst):
            shutil.copy2(src, dst)
    return 'uploads/' + rel.replace('vitryla/', '', 1)


# ── Розбір блоків WordPress ──
TOKEN = re.compile(r'<!--\s+(/)?wp:([a-z][a-z0-9_-]*/)?([a-z][a-z0-9_-]*)\s+(\{.*?\}\s+)?(/)?-->', re.S)


def parse_blocks(src):
    root = {'name': 'root', 'attrs': {}, 'inner': []}
    stack = [root]
    pos = 0
    for m in TOKEN.finditer(src):
        if m.start() > pos:
            stack[-1]['inner'].append(src[pos:m.start()])
        pos = m.end()
        closing, ns, name, attrs, selfclose = m.groups()
        name = (ns or 'core/') + name
        if closing:
            if len(stack) > 1:
                stack.pop()
            continue
        blk = {'name': name, 'attrs': json.loads(attrs) if attrs else {}, 'inner': []}
        stack[-1]['inner'].append(blk)
        if not selfclose:
            stack.append(blk)
    if pos < len(src):
        stack[-1]['inner'].append(src[pos:])
    return root['inner']


def children(b):
    return [x for x in b['inner'] if isinstance(x, dict)]


def raw(b):
    """HTML блоку без вкладених блоків."""
    return ''.join(x if isinstance(x, str) else raw(x) for x in b['inner'])


def inner_of(htm, tag):
    m = re.search(rf'<{tag}\b[^>]*>(.*)</{tag}>', htm, re.S)
    return (m.group(1) if m else htm).strip()


def clean_inline(s):
    s = s.strip()
    s = re.sub(r'\s+', ' ', s)
    return s


def tone_of(cls):
    m = re.search(r'\bis-(blue|sun|green|sail)\b', cls or '')
    return m.group(1) if m else ''


SC = re.compile(r'\[(vit_[a-z_]+)([^\]]*)\]')


def shortcode_block(text):
    m = SC.search(text or '')
    if not m:
        return None
    args = dict(re.findall(r'(\w+)="([^"]*)"', m.group(2)))
    name = m.group(1)[4:]
    return {'type': 'component', 'name': name, 'args': args}


def convert(nodes):
    out = []
    for n in nodes:
        if isinstance(n, str):
            if n.strip():
                sc = shortcode_block(n)
                if sc:
                    out.append(sc)
            continue
        name, a = n['name'], n['attrs']
        cls = a.get('className', '')
        if name == 'core/paragraph':
            out.append({'type': 'p', 'text': clean_inline(inner_of(raw(n), 'p')), **({'style': cls} if cls else {})})
        elif name == 'core/heading':
            h = raw(n)
            lvl = a.get('level', 2)
            text = clean_inline(inner_of(h, f'h{lvl}'))
            blk = {'type': 'h', 'level': lvl, 'text': text}
            idm = re.search(r'\bid="([^"]+)"', h)
            if idm:
                blk['anchor'] = idm.group(1)
            out.append(blk)
        elif name == 'core/list':
            items = [clean_inline(inner_of(raw(li), 'li')) for li in children(n)]
            style = 'steps' if 'vit-steps' in cls else ('timeline' if 'vit-timeline' in cls else 'bullets')
            if style == 'timeline':
                tl = []
                for it in items:
                    mm = re.match(r'<strong>(.*?)</strong>(.*)', it)
                    tl.append({'label': mm.group(1), 'text': mm.group(2).strip()} if mm else {'label': '', 'text': it})
                out.append({'type': 'timeline', 'items': tl})
            else:
                out.append({'type': 'list', 'style': style, 'items': items})
        elif name == 'core/group' and 'vit-cards' in cls:
            cards = []
            for g in children(n):
                hs = [c for c in children(g) if c['name'] == 'core/heading']
                ps = [c for c in children(g) if c['name'] == 'core/paragraph']
                hcls = hs[0]['attrs'].get('className', '') if hs else ''
                icon = re.search(r'vit-i-([a-z]+)', hcls)
                cards.append({
                    'icon': icon.group(1) if icon else '',
                    'title': clean_inline(inner_of(raw(hs[0]), 'h3')) if hs else '',
                    'text': ' '.join(clean_inline(inner_of(raw(p), 'p')) for p in ps),
                })
            out.append({'type': 'cards', 'items': cards})
        elif name == 'core/group' and 'vit-stats' in cls:
            items = []
            for g in children(n):
                ps = {c['attrs'].get('className', ''): clean_inline(inner_of(raw(c), 'p')) for c in children(g)}
                items.append({'n': ps.get('vit-stat-n', ''), 'label': ps.get('vit-stat-l', ''), 'note': ps.get('vit-stat-note', '')})
            out.append({'type': 'stats', 'items': items})
        elif name == 'core/group' and 'vit-faq' in cls:
            items = []
            for d in children(n):
                h = raw(d)
                qm = re.search(r'<summary>(.*?)</summary>', h, re.S)
                ans = ' '.join(clean_inline(inner_of(raw(p), 'p')) for p in children(d) if p['name'] == 'core/paragraph')
                items.append({'q': clean_inline(qm.group(1)) if qm else '', 'a': ans})
            out.append({'type': 'faq', 'items': items})
        elif name == 'core/group' and 'vit-callout' in cls:
            hs = [c for c in children(n) if c['name'] == 'core/heading']
            ps = [c for c in children(n) if c['name'] == 'core/paragraph']
            out.append({
                'type': 'callout', 'tone': tone_of(cls) or 'sun',
                'title': clean_inline(inner_of(raw(hs[0]), 'h3')) if hs else '',
                'text': ' '.join(clean_inline(inner_of(raw(p), 'p')) for p in ps),
            })
        elif name == 'core/table':
            h = raw(n)
            head = [clean_inline(x) for x in re.findall(r'<th[^>]*>(.*?)</th>', h, re.S)]
            body = re.search(r'<tbody>(.*?)</tbody>', h, re.S)
            rows = [[clean_inline(c) for c in re.findall(r'<td[^>]*>(.*?)</td>', r, re.S)]
                    for r in re.findall(r'<tr>(.*?)</tr>', body.group(1) if body else '', re.S)]
            out.append({'type': 'table', 'head': head, 'rows': rows})
        elif name == 'core/buttons':
            items = []
            for bt in children(n):
                mm = re.search(r'href="([^"]*)"[^>]*>(.*?)</a>', raw(bt), re.S)
                if mm:
                    items.append({'label': clean_inline(mm.group(2)), 'url': html.unescape(mm.group(1))})
            out.append({'type': 'buttons', 'items': items})
        elif name in ('core/html', 'core/shortcode'):
            body = raw(n).strip()
            sc = shortcode_block(body)
            if sc and SC.sub('', body).strip() == '':
                out.append(sc)
            else:
                out.append({'type': 'html', 'html': body})
        elif name == 'core/gallery':
            imgs = [m for m in re.findall(r'<img[^>]+src="([^"]+)"', raw(n))]
            out.append({'type': 'gallery', 'images': imgs})
        elif name == 'core/details':
            h = raw(n)
            qm = re.search(r'<summary>(.*?)</summary>', h, re.S)
            ans = ' '.join(clean_inline(inner_of(raw(p), 'p')) for p in children(n) if p['name'] == 'core/paragraph')
            out.append({'type': 'faq', 'items': [{'q': clean_inline(qm.group(1)) if qm else '', 'a': ans}]})
        elif name == 'core/group':
            out.extend(convert(n['inner']))
        else:
            body = raw(n).strip()
            if body:
                out.append({'type': 'html', 'html': body})
    return out


def fix_urls(blocks):
    """Абсолютні адреси локального WordPress → відносні до кореня сайту."""
    s = json.dumps(blocks, ensure_ascii=False)
    s = s.replace('http://127.0.0.1:9400/wp-content/uploads/vitryla/', '/uploads/')
    s = s.replace('http://127.0.0.1:9400', '')
    s = s.replace('/wp-content/uploads/vitryla/', '/uploads/')
    return json.loads(s)


def write(path, data):
    with open(path, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=1)
        f.write('\n')


# ── Налаштування ──
c = opt('vitryla_contacts', {}) or {}
nums = opt('vitryla_numbers', {}) or {}
today = opt('vitryla_today', {}) or {}
settings = {
    'site': {'name': 'Біляївський ліцей №2', 'motto': 'Вітрила юності', 'full_name': 'Біляївський ліцей №2 Біляївської міської ради Одеського району Одеської області', 'founded': nums.get('founded', '1897'), 'demo_bar': True},
    'contacts': {
        'phone': c.get('phone', ''), 'phone_extra': c.get('phone_extra', ''), 'hours': c.get('office_hours', ''),
        'reception': c.get('reception', ''), 'public_info': c.get('public_info', ''), 'email': 'shkola2bel@ukr.net',
        'facebook': 'https://www.facebook.com/groups/165229927190562/',
        'buildings': [
            {'name': 'Головний корпус', 'address': 'І провулок Шевченка, 18, м. Біляївка, 67602'},
            {'name': 'Початкова школа', 'address': 'вулиця Шевченка, 29, м. Біляївка, 67602'},
            {'name': 'Харчоблок', 'address': 'І провулок Шевченка, 11, м. Біляївка, 67602'},
        ],
    },
    'numbers': {k: nums.get(k, '') for k in ('pupils', 'pupils_date', 'classes', 'classes_date', 'staff', 'staff_date', 'shelters', 'shelters_note')},
    'today': {k: today.get(k, '') for k in ('format', 'format_note', 'alarm', 'alarm_note', 'food', 'food_note', 'office', 'office_note')},
}
write(os.path.join(CONTENT, 'settings.json'), settings)

# ── Сторінки ──
pages = q("select * from wp_posts where post_type='page' and post_status='publish'")
by_id = {p['ID']: p for p in pages}
count_pages = 0
for p in pages:
    m = meta(p['ID'])
    parent = by_id.get(p['post_parent'])
    blocks = fix_urls(convert(parse_blocks(p['post_content'])))
    write(os.path.join(CONTENT, 'pages', p['post_name'] + '.json'), {
        'slug': p['post_name'],
        'parent': parent['post_name'] if parent else '',
        'title': p['post_title'],
        'lead': p['post_excerpt'],
        'order': p['menu_order'],
        'template': m.get('vit_template') == '1',
        'updated': p['post_modified'][:10],
        'blocks': blocks,
    })
    count_pages += 1

# ── Рубрики, розділи документів, групи кадрів ──
def tax(taxonomy):
    return [{'slug': r[0], 'name': r[1]} for r in q('''select t.slug, t.name from wp_terms t join wp_term_taxonomy tt on tt.term_id=t.term_id
        where tt.taxonomy=? order by t.term_id''', taxonomy)]

write(os.path.join(CONTENT, 'taxonomies.json'), {
    'news': [t for t in tax('category') if t['slug'] != 'uncategorized'],
    'documents': tax('vit_doc_section'),
    'staff': tax('vit_staff_group'),
})

# ── Новини ──
count_news = 0
for p in q("select * from wp_posts where post_type='post' and post_status='publish'"):
    m = meta(p['ID'])
    blocks = convert(parse_blocks(p['post_content']))
    paras, gallery, source = [], [], m.get('vit_fb_url', '')
    for b in blocks:
        if b['type'] == 'p' and b.get('style') in ('vit-fb-src', 'vit-fb-more'):
            continue
        if b['type'] == 'p':
            paras.append(b['text'])
        elif b['type'] == 'gallery':
            gallery.extend(b['images'])
    cover = attached_file(m.get('_thumbnail_id'))
    cover_url = copy_upload(cover) if cover else ''
    imgs = []
    for u in gallery:
        rel = re.sub(r'^.*?/wp-content/uploads/', '', u)
        imgs.append(copy_upload(rel))
    cats = terms(p['ID'], 'category')
    write(os.path.join(CONTENT, 'news', f"{p['ID']}.json"), {
        'id': p['ID'],
        'date': p['post_date'][:16],
        'title': p['post_title'],
        'excerpt': p['post_excerpt'],
        'categories': [x for x in cats if x != 'uncategorized'] or ['zhyttia'],
        'text': paras,
        'cover': cover_url,
        'images': [i for i in imgs if i],
        'more_photos': '',
        'source': source,
    })
    count_news += 1

# ── Документи ──
docs = []
for p in q("select * from wp_posts where post_type='vit_document' and post_status='publish'"):
    m = meta(p['ID'])
    f = attached_file(m.get('vit_file'))
    secs = terms(p['ID'], 'vit_doc_section')
    docs.append({
        'id': p['ID'], 'title': p['post_title'], 'sections': secs,
        'number': m.get('vit_number', ''), 'approved': m.get('vit_approved', ''), 'published': m.get('vit_published', ''),
        'valid': m.get('vit_valid', 'current'), 'file': copy_upload(f) if f else '', 'demo': m.get('vit_demo') == '1',
    })
docs.sort(key=lambda d: d['approved'] or '', reverse=True)
write(os.path.join(CONTENT, 'documents.json'), docs)

# ── Кадри ──
staff = []
for p in q("select * from wp_posts where post_type='vit_staff' and post_status='publish' order by menu_order, post_title"):
    m = meta(p['ID'])
    photo = attached_file(m.get('_thumbnail_id'))
    grp = terms(p['ID'], 'vit_staff_group')
    staff.append({
        'id': p['ID'], 'name': p['post_title'], 'position': m.get('vit_position', ''), 'subjects': m.get('vit_subjects', ''),
        'group': grp[0] if grp else 'vchyteli', 'order': p['menu_order'], 'photo': copy_upload(photo) if photo else '',
        'source': m.get('vit_source', ''), 'demo': m.get('vit_demo') == '1',
    })
write(os.path.join(CONTENT, 'staff.json'), staff)

# ── Гуртки ──
clubs = []
for p in q("select * from wp_posts where post_type='vit_club' and post_status='publish' order by menu_order, ID"):
    m = meta(p['ID'])
    desc = re.sub(r'<[^>]+>', '', re.sub(r'<!--.*?-->', '', p['post_content'], flags=re.S)).strip()
    clubs.append({
        'id': p['ID'], 'title': p['post_title'], 'leader': m.get('vit_leader', ''), 'classes': m.get('vit_classes', ''),
        'day': m.get('vit_day', ''), 'time': m.get('vit_time', ''), 'room': m.get('vit_room', ''),
        'text': desc or p['post_excerpt'], 'demo': m.get('vit_demo') == '1',
    })
write(os.path.join(CONTENT, 'clubs.json'), clubs)

# ── Емблема ──
copy_upload('vitryla/emblema.png')

print(f'Сторінок: {count_pages}, новин: {count_news}, документів: {len(docs)}, кадрів: {len(staff)}, гуртків: {len(clubs)}')
types = {}
for f in os.listdir(os.path.join(CONTENT, 'pages')):
    for b in json.load(open(os.path.join(CONTENT, 'pages', f)))['blocks']:
        k = b['type'] + ('/' + b['name'] if b['type'] == 'component' else '')
        types[k] = types.get(k, 0) + 1
print('Типи блоків:', dict(sorted(types.items(), key=lambda x: -x[1])))

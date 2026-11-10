#!/usr/bin/env python3
"""Готує дописи з Facebook до імпорту на сайт ліцею.

Вхід:  raw.json  — [{id,url,author,date,text,imgs,more}]
Вихід: <uploads>/vitryla/fb/posts.json + фото <id>-<n>.jpg
"""
import html, json, os, re, subprocess, sys, unicodedata, urllib.request
from concurrent.futures import ThreadPoolExecutor

RAW, OUT = sys.argv[1], sys.argv[2]
MAX_IMG = 6
os.makedirs(OUT, exist_ok=True)

def clean_symbols(s: str) -> str:
    out = []
    for ch in s:
        cat = unicodedata.category(ch)
        if cat in ('So', 'Sk', 'Cs', 'Co') or ch in '‍️︎⃣':
            continue
        out.append(ch)
    return re.sub(r'\s{2,}', ' ', ''.join(out)).strip(' -–—•·*_')

PROPER = ['Біляїв', 'Україн', 'Одес', 'Анни Ярославни', 'Кліменко', 'Шевченк', 'Пласт', 'Нов', 'МОН', 'НУШ', 'НМТ', 'ЗСУ', 'Різдв', 'Покров', 'Захисник', 'Бог', 'Курін']

def sentence_case(s: str) -> str:
    letters = [c for c in s if c.isalpha()]
    if not letters or sum(c.isupper() for c in letters) / len(letters) < 0.7:
        return s
    low = s.lower()
    low = low[:1].upper() + low[1:]
    for w in PROPER:
        low = re.sub(re.escape(w.lower()), w, low)
        low = re.sub(r'(?<=[.!?] )' + re.escape(w.lower()), w, low)
    low = re.sub(r'№\s*(\d)', r'№\1', low)
    low = re.sub(r'(\d+)-([а-яієїґ])\b', lambda m: m.group(1) + '-' + m.group(2).upper(), low)  # 1-а → 1-А
    return low

def cut(s: str, n: int) -> str:
    if len(s) <= n:
        return s
    s = s[:n].rsplit(' ', 1)[0].rstrip(',;:—– ')
    return s + '…'

JUNK = re.compile(r'\s*(Показать меньше|Показати менше|See less|Скрыть|Ещё|Ще)\s*$')

CATS = [
    ('profilna-shkola', r'старш(а|ої|у|ій) профільн|академічн(ий|ого|ому|им) ліце|профільн(ого|е|ому) навчанн|освітн(ій|і|іх) траєкторі|вибір профіл'),
    ('bezpeka-news', r'тривог|евакуац|укритт|вибухонебезп|мінн(а|ої|у) безпек|пожеж|електробезпек|електричн|ураженн[яю] (людини )?струм|техні(ки|ці) безпеки|інструктаж|булінг|кібер|безпека понад|клас(у)? безпеки|торгівл(і|ею) людьми|цивільн(ого|ий) захист|правил(а|ам) дорожнього'),
    ('hordist', r'переможц|призер|призов(е|і|их) місц|перемог(ла|ли|ла в|у в)|нагород|диплом|відзнак|олімпіад|пишаємос'),
    ('ogoloshennia', r'увага|оголош|шановні батьки|запрошуємо|батьківськ(і|их) збор|нагадуємо|до відома'),
    ('navchannia', r'урок|методичн|засідання мо|нмт|семінар|тренінг|вебінар|квест|предметн|тиждень|бібліотек|консиліум|stem'),
]

def categorize(text: str) -> list:
    t = text.lower()
    found = [slug for slug, rx in CATS if re.search(rx, t)]
    if 'profilna-shkola' in found:
        found = ['profilna-shkola'] + [f for f in found if f != 'profilna-shkola'][:1]
    else:
        found = found[:1]
    return found or ['zhyttia']

URL_RX = re.compile(r'(https?://[^\s<]+)')

def para_html(line: str) -> str:
    esc = html.escape(line, quote=False)
    return URL_RX.sub(lambda m: f'<a href="{m.group(1)}" target="_blank" rel="noopener">{m.group(1)}</a>', esc)

def make_title(lines: list) -> tuple:
    """Повертає (заголовок, скільки перших рядків «з'їв» заголовок)."""
    first = clean_symbols(lines[0]) if lines else ''
    used = 1
    if len(first) < 25 and len(lines) > 1 and first[-1:] not in '!?':
        second = clean_symbols(lines[1])
        if second:
            first = ((first + ' ') if first[-1:] in '!?…' else (first.rstrip('.:') + ': ')) + second if first else second
            used = 2
    first = re.sub(r'#\S+', '', first).strip()
    if not first:
        return 'Новини ліцею', 0
    sentence = re.split(r'(?<=[.!?])\s', first, maxsplit=1)[0]
    title = sentence if (30 <= len(sentence) <= 110 or (sentence == first and len(sentence) <= 110)) else cut(first, 100)
    title = sentence_case(title).rstrip(':,;')
    # заголовок забирає рядок з тексту лише тоді, коли повторює його повністю
    consumed = used if title.rstrip('.!…') == sentence_case(first).rstrip('.!…:') else 0
    return title, consumed

def download(job):
    url, dest = job
    if os.path.exists(dest) and os.path.getsize(dest) > 1000:
        return dest
    try:
        req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req, timeout=40) as r, open(dest, 'wb') as f:
            f.write(r.read())
        subprocess.run(['sips', '-Z', '1600', '-s', 'formatOptions', '72', dest, '--out', dest], capture_output=True)
        return dest
    except Exception as e:  # noqa
        print('img fail', dest, e)
        return None

raw = json.load(open(RAW, encoding='utf-8'))
out, jobs = [], []
for p in sorted(raw, key=lambda x: x['date']):
    text = JUNK.sub('', (p.get('text') or '').replace('\r', '')).strip()
    lines = [l.strip() for l in text.split('\n') if l.strip()]
    lines = [l for l in lines if not all(w.startswith('#') for w in l.split())]
    if not lines and not p.get('imgs'):
        continue
    title, used = make_title(lines)
    body_lines = lines[used:] if used else lines
    paras = [para_html(l) for l in body_lines]
    plain = ' '.join(clean_symbols(l) for l in body_lines)
    imgs = []
    for n, u in enumerate((p.get('imgs') or [])[:MAX_IMG]):
        name = f"{p['id']}-{n + 1}.jpg"
        imgs.append(name)
        jobs.append((u, os.path.join(OUT, name)))
    out.append({
        'id': p['id'], 'url': p['url'], 'author': p.get('author', ''), 'date': p['date'],
        'title': title, 'paras': paras, 'excerpt': cut(plain, 170),
        'cats': categorize(text), 'images': imgs, 'more': p.get('more') or '',
        'alt': 'Фото до допису «' + title.rstrip('.') + '»',
    })

from collections import Counter as _C
_dup = _C(o['title'] for o in out)
for o, p in zip(out, sorted([q for q in raw], key=lambda x: x['date'])):
    pass
by_id = {p['id']: p for p in raw}
for o in out:
    if _dup[o['title']] > 1:
        m = re.search(r'\b(\d{1,2})\s?-\s?([АБВГабвг])\b', by_id[o['id']].get('text', ''))
        if m and m.group(1) + '-' not in o['title']:
            o['title'] = o['title'].rstrip('.') + f" — {m.group(1)}-{m.group(2).upper()} клас"

with ThreadPoolExecutor(6) as ex:
    res = list(ex.map(download, jobs))
ok = {os.path.basename(r) for r in res if r}
for o in out:
    o['images'] = [i for i in o['images'] if i in ok]

json.dump(out, open(os.path.join(OUT, 'posts.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
from collections import Counter
print(len(out), 'posts;', len(ok), 'images;', Counter(c for o in out for c in o['cats']))

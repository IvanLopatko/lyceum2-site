#!/usr/bin/env python3
"""Друга частина перенесення: дані з коду WordPress-версії (план профільної школи,
відповідальні) і заміна HTML-вставок на структуровані блоки, які редагуються в адмінці."""
import json, os, re, sys
FACTS, ROOT = sys.argv[1], sys.argv[2]
C = os.path.join(ROOT, 'content')
src = open(FACTS, encoding='utf-8').read()

def php_pairs(fn_name):
    body = src[src.index('function ' + fn_name):]
    body = body[:body.index('\n}\n')]
    return body

# план заходів: 'РРРР-ММ' => [ [що, як] ]
plan_src = php_pairs('vitryla_profile_plan')
months = []
for m in re.finditer(r"'(\d{4}-\d{2})' => array\((.*?)\n\t\t\),", plan_src, re.S):
    items = [{'what': a, 'how': b} for a, b in re.findall(r"array\( '((?:[^'\\]|\\.)*)', '((?:[^'\\]|\\.)*)' \)", m.group(2))]
    months.append({'month': m.group(1), 'items': items})
stages_src = php_pairs('vitryla_profile_stages')
stages = [{'name': a, 'from': b, 'to': c, 'label': d} for a, b, c, d in re.findall(r"array\( '([^']*)', '([^']*)', '([^']*)', '([^']*)' \)", stages_src)]
resp_src = php_pairs('vitryla_responsible')
resp = [{'icon': a, 'area': b, 'who': c, 'position': d} for a, b, c, d in re.findall(r"array\( '([^']*)', '([^']*)', '([^']*)', '([^']*)' \)", resp_src)]
json.dump({'start': '2027-09-01', 'source': 'План комунікаційних заходів щодо впровадження старшої профільної школи на 2026–2027 роки', 'stages': stages, 'months': months},
          open(os.path.join(C, 'profile-plan.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
s = json.load(open(os.path.join(C, 'settings.json'), encoding='utf-8'))
s['responsible'] = resp
json.dump(s, open(os.path.join(C, 'settings.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)

def strip(h): return re.sub(r'\s+', ' ', re.sub(r'<[^>]+>', ' ', h)).strip()

# профільна школа: html → compare / myths / toc
p = json.load(open(os.path.join(C, 'pages', 'profilna-shkola.json'), encoding='utf-8'))
nb = []
for b in p['blocks']:
    if b['type'] == 'html' and 'vit-jump' in b['html']:
        nb.append({'type': 'component', 'name': 'toc', 'args': {}})
    elif b['type'] == 'html' and 'vit-compare' in b['html']:
        cols = re.findall(r'<div class="vit-cmp[^"]*"><p class="vit-cmp-k">(.*?)</p><h3>(.*?)</h3><ul>(.*?)</ul></div>', b['html'], re.S)
        nb.append({'type': 'compare', 'items': [{'label': k, 'title': t, 'points': [strip(x) for x in re.findall(r'<li>(.*?)</li>', ul, re.S)]} for k, t, ul in cols]})
    elif b['type'] == 'html' and 'vit-myths' in b['html']:
        pairs = re.findall(r'<p class="vit-myth-m">(.*?)</p><p class="vit-myth-f">(.*?)</p>', b['html'], re.S)
        nb.append({'type': 'myths', 'items': [{'myth': strip(m).strip('«»'), 'fact': strip(f)} for m, f in pairs]})
    else:
        nb.append(b)
p['blocks'] = nb
json.dump(p, open(os.path.join(C, 'pages', 'profilna-shkola.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)

# контакти: карта → компонент; мова: цитата → quote
k = json.load(open(os.path.join(C, 'pages', 'kontakty.json'), encoding='utf-8'))
k['blocks'] = [{'type': 'component', 'name': 'map', 'args': {}} if (b['type'] == 'html' and 'vit-map' in b['html']) else b for b in k['blocks']]
json.dump(k, open(os.path.join(C, 'pages', 'kontakty.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
mv = json.load(open(os.path.join(C, 'pages', 'mova-osvitnoho-protsesu.json'), encoding='utf-8'))
out = []
for b in mv['blocks']:
    if b['type'] == 'html' and 'blockquote' in b['html']:
        ps = [strip(x) for x in re.findall(r'<p>(.*?)</p>', b['html'], re.S)]
        cite = re.search(r'<cite>(.*?)</cite>', b['html'], re.S)
        out.append({'type': 'quote', 'text': ' '.join(ps), 'cite': strip(cite.group(1)) if cite else ''})
    else:
        out.append(b)
mv['blocks'] = out
json.dump(mv, open(os.path.join(C, 'pages', 'mova-osvitnoho-protsesu.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
print('місяців плану:', len(months), '| етапів:', len(stages), '| відповідальних:', len(resp))

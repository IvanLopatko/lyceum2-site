<?php
/**
 * Живі компоненти, які вставляються в сторінки блоком «component».
 * Кожен бере дані з content/ і рахує стан на сьогодні (строки, відлік, план).
 */

declare(strict_types=1);

const PAGE_ICONS = [
    'istoriia' => 'book', 'struktura-ta-organy' => 'users', 'korpusy-i-baza' => 'home', 'ustanovchi-dokumenty' => 'doc', 'vakansii' => 'hand',
    'osvitni-programy' => 'book', 'pochatkova-shkola' => 'sun', 'bazova-shkola' => 'compass', 'profilna-shkola' => 'award', 'rozklad' => 'clock',
    'struktura-navchalnoho-roku' => 'calendar', 'systema-otsiniuvannia' => 'chart', 'rezultaty-i-monitorynh' => 'chart', 'proforiientatsiia-nmt' => 'flag',
    'vstup' => 'key', 'terytoriia-obsluhovuvannia' => 'pin', 'harchuvannia' => 'food', 'pravyla-povedinky' => 'check', 'pravyla-dostupu' => 'lock',
    'indyvidualna-forma' => 'home', 'spivpratsia-z-batkamy' => 'chat', 'nakazy' => 'doc', 'pidviz' => 'bus',
    'bezpeka-i-ukryttia' => 'shield', 'psyhologichna-sluzhba' => 'heart', 'inkliuzyvne-navchannia' => 'users', 'dostupnist-oop' => 'eye',
    'protydiia-bulinhu' => 'hand', 'ohorona-pratsi' => 'alert', 'kiberbezpeka' => 'laptop', 'medychne-obsluhovuvannia' => 'heart',
    'finansovi-dokumenty' => 'chart', 'blagodijna-dopomoha' => 'gift', 'plany-i-zvity' => 'doc', 'strategiia-rozvytku' => 'compass',
    'samootsiniuvannia' => 'check', 'litsenzovanyj-obsiah' => 'users', 'mova-osvitnoho-protsesu' => 'globe', 'dodatkovi-posluhy' => 'star',
    'zapobihannia-koruptsii' => 'scale', 'shcho-opryliudniuiemo' => 'eye', 'normatyvna-baza' => 'scale', 'pidvyshchennia-kvalifikatsii' => 'award',
    'atestatsiia' => 'check', 'metodychna-robota' => 'book', 'rada-litseiu' => 'users', 'nashi-dosiahnennia' => 'award',
    'nashi-vypusknyky' => 'flag', 'uchnivske-samovriaduvannia' => 'chat', 'natsionalna-identychnist' => 'flag',
];

function page_icon(string $slug): string
{
    return PAGE_ICONS[$slug] ?? 'doc';
}

function component_documents(array $a, array $ctx = []): string
{
    $docs = documents_in((string) ($a['section'] ?? ''), ($a['archive'] ?? '0') === '1');
    if (($a['real'] ?? '0') === '1') {
        $docs = array_values(array_filter($docs, static fn(array $d): bool => empty($d['demo'])));
    }
    $limit = (int) ($a['limit'] ?? 0);
    if ($limit > 0) {
        $docs = array_slice($docs, 0, $limit);
    }
    if (!$docs) {
        return '<p class="empty">У цьому розділі документів поки немає.</p>';
    }
    $out = '<ul class="docs">';
    foreach ($docs as $d) {
        $st = doc_state($d);
        $meta = $d['published'] ? 'оприлюднено ' . dmy($d['published']) : $st['label'];
        $title = e($d['title']) . ($d['number'] ? ' <small>' . e($d['number']) . '</small>' : '');
        $inner = '<span class="doc-ico" aria-hidden="true"></span><span class="doc-t">' . $title . '</span>'
            . '<span class="doc-m is-' . e($st['class']) . '">' . e($meta) . '</span>';
        $out .= $d['file']
            ? '<li><a class="doc" href="' . e(upload_url($d['file'])) . '" target="_blank" rel="noopener">' . $inner . '<span class="sr">(PDF, відкриється в новій вкладці)</span></a></li>'
            : '<li><span class="doc is-nofile">' . $inner . '</span></li>';
    }
    return $out . '</ul>';
}

function person_card(array $p): string
{
    if ($p['photo']) {
        $ph = '<img src="' . e(upload_url($p['photo'])) . '" alt="" loading="lazy" width="240" height="240">';
    } else {
        $parts = preg_split('~\s+~u', trim($p['name'])) ?: [''];
        $ini = mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1] ?? '', 0, 1));
        $ph = '<span class="ava" aria-hidden="true">' . e($ini) . '</span>';
    }
    return '<li class="person">' . $ph . '<span class="person-n">' . e($p['name']) . '</span>'
        . ($p['position'] ? '<span class="person-r">' . e($p['position']) . '</span>' : '')
        . ($p['subjects'] ? '<span class="person-s">' . e($p['subjects']) . '</span>' : '') . '</li>';
}

function component_team(array $a, array $ctx = []): string
{
    $people = staff();
    if (!empty($a['group'])) {
        $groups = array_map('trim', explode(',', (string) $a['group']));
        $people = array_filter($people, static fn(array $p): bool => in_array($p['group'], $groups, true));
    }
    if (!empty($a['subjects'])) {
        $needle = mb_strtolower((string) $a['subjects']);
        $people = array_filter($people, static fn(array $p): bool => str_contains(mb_strtolower($p['subjects'] . ' ' . $p['position']), $needle));
    }
    if (!empty($a['limit'])) {
        $people = array_slice($people, 0, (int) $a['limit']);
    }
    if (!$people) {
        return '<p class="empty">Список ще не заповнено.</p>';
    }
    return '<ul class="team">' . implode('', array_map('person_card', $people)) . '</ul>';
}

function component_contacts(array $a = [], array $ctx = []): string
{
    $c = setting('contacts', []);
    $rows = [
        ['phone', 'Телефон приймальні', e($c['phone'] ?: 'уточнюється') . ($c['phone_extra'] ? '<small>' . e($c['phone_extra']) . '</small>' : '')],
        ['mail', 'Електронна пошта', '<a href="mailto:' . e($c['email']) . '">' . e($c['email']) . '</a>'],
        ['clock', 'Приймальня', e($c['hours'] ?: 'уточнюється')],
        ['users', 'Особистий прийом громадян', e($c['reception'] ?: 'уточнюється')],
    ];
    $out = '<ul class="contact-list">';
    foreach ($rows as [$ic, $label, $val]) {
        $out .= '<li><span class="ico i-' . $ic . '" aria-hidden="true"></span><span class="contact-l">' . $label . '</span><span class="contact-v">' . $val . '</span></li>';
    }
    return $out . '</ul>';
}

function component_responsible(array $a = [], array $ctx = []): string
{
    $out = '<ul class="resp">';
    foreach (setting('responsible', []) as $r) {
        $out .= '<li><span class="ico i-' . e($r['icon']) . '" aria-hidden="true"></span><span class="resp-a">' . e($r['area'])
            . '</span><span class="resp-w">' . e($r['who']) . '</span><span class="resp-p">' . e($r['position']) . '</span></li>';
    }
    return $out . '</ul>';
}

function component_map(array $a = [], array $ctx = []): string
{
    return '<div class="map"><iframe title="Карта: головний корпус ліцею" loading="lazy" src="https://www.openstreetmap.org/export/embed.html?bbox=30.1909%2C46.4829%2C30.2109%2C46.4929&amp;layer=mapnik&amp;marker=46.487948%2C30.200875"></iframe></div>';
}

function component_countdown(array $a = [], array $ctx = []): string
{
    $start = profile_plan()['start'] ?? '2027-09-01';
    $d = days_to($start);
    return '<div class="countdown"><b class="countdown-n" data-count>' . $d . '</b><span class="countdown-l">'
        . plural($d, 'день', 'дні', 'днів') . ' до ' . e(ua_date($start)) . '</span><span class="countdown-s">старт академічного ліцею</span></div>';
}

function month_name(string $ym, bool $year = true): string
{
    [$y, $m] = array_map('intval', explode('-', $ym));
    return UA_MONTHS[$m] . ($year ? ' ' . $y : '');
}

function component_roadmap(array $a = [], array $ctx = []): string
{
    $plan = profile_plan();
    $now = date('Y-m');
    $done = [];
    foreach (news_in('profilna-shkola') as $n) {
        $done[substr($n['date'], 0, 7)][] = $n;
    }

    $out = '<div class="roadmap"><ol class="rm-stages">';
    foreach ($plan['stages'] as $i => $s) {
        $state = $now > $s['to'] ? 'is-past' : ($now >= $s['from'] ? 'is-now' : '');
        $out .= '<li class="' . $state . '"><span class="rm-k">Етап ' . ($i + 1) . '</span><b>' . e($s['name']) . '</b><span class="rm-l">' . e($s['label']) . '</span>'
            . ($state === 'is-now' ? '<span class="badge">ми тут</span>' : '') . '</li>';
    }
    $out .= '</ol><div class="rm-track" tabindex="0" role="region" aria-label="План заходів по місяцях. Гортайте вбік."><ol class="rm-months">';
    foreach ($plan['months'] as $m) {
        $ym = $m['month'];
        $state = $ym < $now ? 'is-past' : ($ym === $now ? 'is-now' : '');
        $out .= '<li class="rm-m ' . $state . '"' . ($state === 'is-now' ? ' aria-current="date"' : '') . '><div class="rm-mh"><b>' . e(month_name($ym)) . '</b>'
            . ($state === 'is-now' ? '<span class="badge">зараз</span>' : '') . '</div><ul>';
        foreach ($m['items'] as $it) {
            $out .= '<li>' . e($it['what']) . '<small>' . e($it['how']) . '</small></li>';
        }
        $out .= '</ul>';
        if (!empty($done[$ym])) {
            $out .= '<div class="rm-done"><span>Відбулося</span>';
            foreach (array_slice($done[$ym], 0, 4) as $n) {
                $out .= '<a href="' . e(url(news_url($n))) . '">' . e(date('d.m', strtotime($n['date'])) . ' · ' . $n['title']) . '</a>';
            }
            $out .= '</div>';
        }
        $out .= '</li>';
    }
    return $out . '</ol></div><p class="rm-note">Джерело: ' . e($plan['source'] ?? '') . '. Поточний місяць підсвічується автоматично.</p></div>';
}

function component_alarm_shifts(array $a = [], array $ctx = []): string
{
    $rows = [
        ['Перша зміна', '07:30', '09:00', 'Якщо тривога триває до 07:30 — учні залишаються вдома й не вирушають до ліцею.', 'Якщо тривога триває до 09:00 — навчання першої зміни переходить у дистанційний формат.'],
        ['Друга зміна', '11:30', '13:00', 'Якщо тривога триває до 11:30 — учні залишаються вдома. Початок занять другої зміни — о 12:00, якщо адміністрація не повідомила інше.', 'Якщо тривога триває до 13:00 — навчання другої зміни переходить у дистанційний формат.'],
    ];
    $out = '<div class="shifts">';
    foreach ($rows as [$name, $home, $ctrl, $t1, $t2]) {
        $out .= '<div class="shift"><h3>' . $name . '</h3><div class="shift-row"><b>' . $home . '</b><p>' . $t1 . '</p></div>'
            . '<div class="shift-row is-ctrl"><b>' . $ctrl . '</b><p><strong>Контрольний час.</strong> ' . $t2 . '</p></div></div>';
    }
    return $out . '</div>';
}

function news_row(array $n): string
{
    return '<li><a href="' . e(url(news_url($n))) . '"><span class="post-d">' . e(ua_date($n['date'])) . '</span><span class="post-t">' . e($n['title']) . '</span></a></li>';
}

function component_posts(array $a, array $ctx = []): string
{
    $list = !empty($a['cat']) ? news_in((string) $a['cat']) : news_all();
    $list = array_slice($list, 0, (int) ($a['limit'] ?? 6));
    if (!$list) {
        return '<p class="empty">' . e($a['empty'] ?? 'Дописів поки немає.') . '</p>';
    }
    return '<ul class="postlist">' . implode('', array_map('news_row', $list)) . '</ul>';
}

function component_children(array $a, array $ctx = []): string
{
    $page = $ctx['page'] ?? null;
    if (!$page) {
        return '';
    }
    $kids = page_children($page['slug']);
    if (!$kids) {
        return '';
    }
    $out = '<ul class="tiles">';
    foreach ($kids as $k) {
        $out .= '<li><a class="tile-link" href="' . e(url(page_path($k['slug']))) . '"><span class="ico i-' . page_icon($k['slug']) . '" aria-hidden="true"></span>'
            . '<b>' . e($k['title']) . '</b>' . ($k['lead'] ? '<span>' . e(excerpt($k['lead'], 110)) . '</span>' : '') . '</a></li>';
    }
    return $out . '</ul>';
}

function component_related(array $page): string
{
    if (($page['parent'] ?? '') === '') {
        return '';
    }
    $sibs = array_filter(page_children($page['parent']), static fn(array $p): bool => $p['slug'] !== $page['slug']);
    if (!$sibs) {
        return '';
    }
    $parent = page($page['parent']);
    $out = '<nav class="related" aria-label="Інші сторінки розділу"><h2>Ще в розділі «' . e($parent['title'] ?? '') . '»</h2><ul>';
    foreach ($sibs as $s) {
        $out .= '<li><a href="' . e(url(page_path($s['slug']))) . '">' . e($s['title']) . '</a></li>';
    }
    return $out . '</ul></nav>';
}

function component_sitemap(array $a = [], array $ctx = []): string
{
    $out = '<div class="sitemap">';
    foreach (nav_sections() as $s) {
        $out .= '<section><h2><a href="' . e(url(page_path($s['slug']))) . '">' . e($s['title']) . '</a></h2><ul class="list">';
        foreach (page_children($s['slug']) as $k) {
            $out .= '<li><a href="' . e(url(page_path($k['slug']))) . '">' . e($k['title']) . '</a></li>';
        }
        $out .= '</ul></section>';
    }
    $out .= '<section><h2>Ще на сайті</h2><ul class="list">'
        . '<li><a href="' . e(url('/novyny/')) . '">Новини</a></li><li><a href="' . e(url('/dokumenty/')) . '">Документи</a></li>'
        . '<li><a href="' . e(url('/kadrovyj-sklad/')) . '">Кадровий склад</a></li><li><a href="' . e(url('/gurtky/')) . '">Гуртки і клуби</a></li>';
    foreach (['kontakty', 'publichna-informacia', 'dostupnist'] as $s) {
        if ($p = page($s)) {
            $out .= '<li><a href="' . e(url(page_path($s))) . '">' . e($p['title']) . '</a></li>';
        }
    }
    return $out . '</ul></section></div>';
}

function component_club_list(array $a = [], array $ctx = []): string
{
    $list = clubs();
    if (!empty($a['limit'])) {
        $list = array_slice($list, 0, (int) $a['limit']);
    }
    if (!$list) {
        return '<p class="empty">Гуртків поки не додано.</p>';
    }
    $out = '<ul class="clubs">';
    foreach ($list as $c) {
        $when = trim(implode(', ', array_filter([$c['day'], $c['time']])));
        $out .= '<li class="club"><b>' . e($c['title']) . '</b>' . ($c['text'] ? '<p>' . e($c['text']) . '</p>' : '')
            . '<dl>' . ($c['classes'] ? '<div><dt>Для кого</dt><dd>' . e($c['classes']) . '</dd></div>' : '')
            . ($when ? '<div><dt>Коли</dt><dd>' . e($when) . '</dd></div>' : '')
            . ($c['room'] ? '<div><dt>Де</dt><dd>' . e($c['room']) . '</dd></div>' : '')
            . ($c['leader'] ? '<div><dt>Керівник</dt><dd>' . e($c['leader']) . '</dd></div>' : '') . '</dl></li>';
    }
    return $out . '</ul>';
}

function component_toc(array $a, array $ctx = []): string
{
    $anchors = page_anchors($ctx['page']['blocks'] ?? []);
    if (!$anchors) {
        return '';
    }
    $out = '<nav class="toc" aria-label="Розділи сторінки">';
    foreach ($anchors as $id => $label) {
        $out .= '<a href="#' . e($id) . '">' . e($label) . '</a>';
    }
    return $out . '</nav>';
}

function component_numbers(array $a = [], array $ctx = []): string
{
    $n = setting('numbers', []);
    $num = static fn($v): int => (int) preg_replace('~\D~', '', (string) $v);
    $items = [
        [setting('site.founded', '1897'), 'рік заснування', 'церковно-приходська школа'],
        [$n['pupils'] ?? '', plural($num($n['pupils'] ?? 0), 'учень', 'учні', 'учнів'), $n['pupils_date'] ?? ''],
        [$n['classes'] ?? '', plural($num($n['classes'] ?? 0), 'клас', 'класи', 'класів'), $n['classes_date'] ?? ''],
        [$n['staff'] ?? '', plural($num($n['staff'] ?? 0), 'працівник', 'працівники', 'працівників'), $n['staff_date'] ?? ''],
        [$n['shelters'] ?? '', plural($num($n['shelters'] ?? 0), 'укриття', 'укриття', 'укриттів'), $n['shelters_note'] ?? ''],
    ];
    $out = '<ul class="numbers">';
    foreach ($items as [$v, $l, $note]) {
        if ($v === '') {
            continue;
        }
        $out .= '<li><b data-count>' . e((string) $v) . '</b><span>' . e($l) . '</span>' . ($note ? '<small>' . e($note) . '</small>' : '') . '</li>';
    }
    return $out . '</ul>';
}

function component_today(array $a = [], array $ctx = []): string
{
    $t = setting('today', []);
    $cells = [
        ['calendar', 'Навчання', $t['format'] ?? '', $t['format_note'] ?? ''],
        ['shield', 'Під час тривоги', $t['alarm'] ?? '', $t['alarm_note'] ?? ''],
        ['food', 'Харчування', $t['food'] ?? '', $t['food_note'] ?? ''],
        ['phone', 'Приймальня', $t['office'] ?? '', $t['office_note'] ?? ''],
    ];
    $out = '<ul class="today" aria-label="Сьогодні в ліцеї">';
    foreach ($cells as [$ic, $label, $v, $note]) {
        if ($v === '') {
            continue;
        }
        $out .= '<li><span class="ico i-' . $ic . '" aria-hidden="true"></span><span class="today-l">' . $label . '</span><b>' . e($v) . '</b>' . ($note ? '<small>' . e($note) . '</small>' : '') . '</li>';
    }
    return $out . '</ul>';
}

<?php
/**
 * Дії адмінки, частина друга: головна сторінка, тексти й посилання, рубрики, план профільної школи,
 * журнал дій, резервні копії, завантаження фото в галереї сторінок.
 * Змінні з actions.php: $section, $isPost, $user, $isAdmin, $isOwner.
 */

declare(strict_types=1);

if (!defined('ROOT')) {
    http_response_code(404);
    exit;
}

/** Рубрики й групи, на які спирається сам сайт: перейменувати можна, видалити — ні. */
const TAX_PROTECTED = ['news' => ['zhyttia', 'profilna-shkola'], 'staff' => ['vchyteli', 'administratsiya'], 'documents' => []];
const TAX_TITLES = ['news' => 'Рубрики новин', 'documents' => 'Розділи реєстру документів', 'staff' => 'Групи кадрового складу'];

/** Рядки «назва + адреса» з двох масивів форми; порожні відкидаються. */
function form_links(string $labelKey, string $urlKey, int $max = 12): array
{
    $out = [];
    foreach ((array) ($_POST[$labelKey] ?? []) as $i => $label) {
        $label = ct($label);
        $url = safe_url(ct($_POST[$urlKey][$i] ?? ''));
        if ($label !== '' && $url !== '' && $url !== '#') {
            $out[] = ['label' => $label, 'url' => $url];
        }
    }
    return array_slice($out, 0, $max);
}

/** Скільки записів користується рубрикою чи розділом. */
function tax_usage(string $tax, string $slug): int
{
    if ($tax === 'news') {
        return count(array_filter(news_all(true), static fn(array $n): bool => in_array($slug, $n['categories'] ?? [], true)));
    }
    if ($tax === 'documents') {
        return count(array_filter(documents(), static fn(array $d): bool => in_array($slug, $d['sections'] ?? [], true)));
    }
    return count(array_filter(store_read('staff.json', []), static fn(array $p): bool => ($p['group'] ?? '') === $slug));
}

switch ($section) {

    /* ═════════ Головна сторінка ═════════ */
    case 'holovna':
        if ($isPost && post('do') === 'reset') {
            store_delete('home.json');
            journal_add('скинуто до початкового', 'головна сторінка');
            flash('Головну сторінку повернуто до початкового вигляду. Попередній варіант є в «Історії змін».');
            back('holovna');
        }
        if ($isPost) {
            $h = [];
            $h['hero'] = [
                'eyebrow' => ct(post('hero_eyebrow')),
                'title' => ct(post('hero_title')),
                'lead' => ct(post('hero_lead')),
                'buttons' => form_links('hero_btn_label', 'hero_btn_url', 2),
                'chips' => array_slice(array_values(array_filter(array_map('ct', preg_split('~\R~u', (string) ($_POST['hero_chips'] ?? '')) ?: []), 'strlen')), 0, 6),
            ];
            if ($h['hero']['title'] === '') {
                flash('Заголовок першого екрана не може бути порожнім. Нічого не збережено.', 'err');
                back('holovna');
            }
            $h['headings'] = [];
            foreach (array_keys(home_defaults()['headings']) as $k) {
                $h['headings'][$k] = ct(post('head_' . $k)) ?: home_defaults()['headings'][$k];
            }
            $tiles = [];
            $arts = tile_arts();
            foreach ((array) ($_POST['tile_title'] ?? []) as $i => $t) {
                $t = ct($t);
                $u = safe_url(ct($_POST['tile_url'][$i] ?? ''));
                if ($t === '' || $u === '' || $u === '#') {
                    continue;
                }
                $art = (string) ($_POST['tile_art'][$i] ?? '');
                $tone = (string) ($_POST['tile_tone'][$i] ?? 'blue');
                $tiles[] = ['art' => in_array($art, $arts, true) ? $art : '', 'url' => $u, 'title' => $t, 'text' => ct($_POST['tile_text'][$i] ?? ''), 'tone' => isset(TILE_TONES[$tone]) ? $tone : 'blue',
                    'order' => (int) ($_POST['tile_order'][$i] ?? 99)];
            }
            usort($tiles, static fn(array $a, array $b): int => $a['order'] <=> $b['order']);
            $h['tiles'] = array_map(static function (array $t): array {
                unset($t['order']);
                return $t;
            }, array_slice($tiles, 0, 12));
            $h['promo'] = ['show' => isset($_POST['promo_show']), 'eyebrow' => ct(post('promo_eyebrow')), 'title' => ct(post('promo_title')), 'text' => ct(post('promo_text')),
                'button' => ct(post('promo_button')), 'url' => safe_url(ct(post('promo_url'))) ?: '/'];
            $h['docs'] = ['title' => ct(post('docs_title')), 'text' => ct(post('docs_text'))];
            $h['cta'] = ['title' => ct(post('cta_title')), 'text' => ct(post('cta_text')), 'buttons' => form_links('cta_btn_label', 'cta_btn_url', 2)];
            store_write('home.json', $h);
            journal_add('змінено', 'головна сторінка', '', 'holovna');
            flash('Головну сторінку збережено.');
            back('holovna');
        }
        admin_view('home-edit');

    /* ═════════ Тексти розділів, посилання, тривога, карта ═════════ */
    case 'teksty':
        if ($isPost) {
            $s = settings();
            $s['texts'] = [];
            foreach (array_keys(TEXT_LABELS) as $k) {
                $v = ct(post('text_' . $k));
                if ($v !== '' && $v !== TEXT_DEFAULTS[$k]) {
                    $s['texts'][$k] = $v;
                }
            }
            $s['links'] = ['utility' => form_links('u_label', 'u_url', 7), 'footer' => form_links('f_label', 'f_url', 8)];
            $alarm = [];
            foreach ((array) ($_POST['al_name'] ?? []) as $i => $name) {
                $row = ['name' => ct($name), 'home' => ct($_POST['al_home'][$i] ?? ''), 'ctrl' => ct($_POST['al_ctrl'][$i] ?? ''),
                    'text_home' => ct($_POST['al_text_home'][$i] ?? ''), 'text_ctrl' => ct($_POST['al_text_ctrl'][$i] ?? '')];
                if ($row['name'] !== '') {
                    $alarm[] = $row;
                }
            }
            $s['alarm'] = array_slice($alarm, 0, 4);
            $s['map'] = ['lat' => (float) str_replace(',', '.', post('map_lat')), 'lon' => (float) str_replace(',', '.', post('map_lon')), 'title' => ct(post('map_title'))];
            store_write('settings.json', $s);
            journal_add('змінено', 'тексти й посилання', '', 'teksty');
            flash('Збережено.');
            back('teksty');
        }
        admin_view('texts');

    /* ═════════ Рубрики новин, розділи документів, групи кадрів ═════════ */
    case 'rubryky':
        if ($isPost) {
            $tx = taxonomies();
            $problems = [];
            foreach (array_keys(TAX_TITLES) as $tax) {
                $del = array_map('strval', (array) ($_POST['t_' . $tax . '_del'] ?? []));
                $rows = [];
                $slugs = [];
                foreach ((array) ($_POST['t_' . $tax . '_name'] ?? []) as $i => $name) {
                    $name = ct($name);
                    $slug = preg_replace('~[^a-z0-9-]~', '', (string) ($_POST['t_' . $tax . '_slug'][$i] ?? '')) ?? '';
                    $known = in_array($slug, array_column($tx[$tax] ?? [], 'slug'), true);
                    if ($slug !== '' && !$known) {
                        continue; // чужий ідентифікатор у формі
                    }
                    if ($slug !== '' && in_array($slug, $del, true)) {
                        $used = tax_usage($tax, $slug);
                        if (in_array($slug, TAX_PROTECTED[$tax], true)) {
                            $problems[] = '«' . tax_name($tax, $slug) . '» потрібна для роботи сайту, її можна лише перейменувати';
                        } elseif ($used) {
                            $problems[] = '«' . tax_name($tax, $slug) . '» використовується в ' . $used . ' ' . plural($used, 'записі', 'записах', 'записах') . ' — спершу перенесіть їх';
                        } else {
                            continue; // видаляємо
                        }
                    }
                    if ($name === '') {
                        if ($slug === '') {
                            continue; // порожній рядок для нової рубрики
                        }
                        $name = tax_name($tax, $slug);
                    }
                    if ($slug === '') {
                        $slug = slugify($name);
                        while (in_array($slug, $slugs, true) || in_array($slug, array_column($tx[$tax] ?? [], 'slug'), true)) {
                            $slug .= '-2';
                        }
                    }
                    $slugs[] = $slug;
                    $rows[] = ['slug' => $slug, 'name' => $name, 'order' => (int) ($_POST['t_' . $tax . '_order'][$i] ?? 99)];
                }
                usort($rows, static fn(array $a, array $b): int => $a['order'] <=> $b['order']);
                if ($rows) {
                    $tx[$tax] = array_map(static fn(array $r): array => ['slug' => $r['slug'], 'name' => $r['name']], $rows);
                }
            }
            store_write('taxonomies.json', $tx);
            journal_add('змінено', 'рубрики й розділи', '', 'rubryky');
            $problems ? flash('Збережено, крім видалення: ' . implode('; ', $problems) . '.', 'err') : flash('Рубрики й розділи збережено.');
            back('rubryky');
        }
        admin_view('taxonomies');

    /* ═════════ План профільної школи ═════════ */
    case 'plan':
        if ($isPost) {
            $plan = profile_plan();
            $ym = static fn(string $v): string => preg_match('~^\d{4}-(0[1-9]|1[0-2])$~', $v) ? $v : '';
            $plan['start'] = clean_date(post('start')) ?: ($plan['start'] ?? '2027-09-01');
            $plan['source'] = ct(post('source'));
            $stages = [];
            foreach ((array) ($_POST['st_name'] ?? []) as $i => $name) {
                $row = ['name' => ct($name), 'from' => $ym((string) ($_POST['st_from'][$i] ?? '')), 'to' => $ym((string) ($_POST['st_to'][$i] ?? '')), 'label' => ct($_POST['st_label'][$i] ?? '')];
                if ($row['name'] !== '' && $row['from'] !== '' && $row['to'] !== '') {
                    $stages[] = $row;
                }
            }
            $months = [];
            foreach ((array) ($_POST['m_month'] ?? []) as $i => $month) {
                $month = $ym((string) $month);
                if ($month === '') {
                    continue;
                }
                $items = [];
                foreach (preg_split('~\R~u', (string) ($_POST['m_items'][$i] ?? '')) ?: [] as $line) {
                    $parts = array_map('ct', explode('|', $line, 2));
                    if (($parts[0] ?? '') !== '') {
                        $items[] = ['what' => $parts[0], 'how' => $parts[1] ?? ''];
                    }
                }
                $months[$month] = ['month' => $month, 'items' => $items];
            }
            ksort($months);
            $plan['stages'] = $stages;
            $plan['months'] = array_values($months);
            store_write('profile-plan.json', $plan);
            journal_add('змінено', 'план профільної школи', '', 'plan');
            flash('План збережено.');
            back('plan');
        }
        admin_view('plan');

    /* ═════════ Журнал дій ═════════ */
    case 'zhurnal':
        admin_view('journal');

    /* ═════════ Резервні копії ═════════ */
    case 'kopii':
        $name = (string) ($_GET['kopiia'] ?? ($_POST['kopiia'] ?? ''));
        if (!$isPost && isset($_GET['zavantazhyty'])) {
            journal_add('завантажено', 'резервна копія', (string) $_GET['zavantazhyty']);
            backup_download((string) $_GET['zavantazhyty']);
        }
        if ($isPost && post('do') === 'create') {
            $made = backup_create('manual', ct(post('note')));
            if ($made) {
                journal_add('створено', 'резервна копія', $made, 'kopii');
            }
            $made ? flash('Копію створено.') : flash('Не вдалося створити копію: перевірте, чи сервер дозволяє запис у теку content.', 'err');
            back('kopii');
        }
        if ($isPost && post('do') === 'restore') {
            if (!$isOwner) {
                flash('Відкотити сайт до резервної копії може лише головний адміністратор.', 'err');
                back('kopii');
            }
            if (!password_verify((string) ($_POST['password'] ?? ''), (string) $user['hash'])) {
                flash('Пароль неправильний. Відкат не виконано.', 'err');
                back('kopii/?kopiia=' . rawurlencode($name));
            }
            if (post('confirm') !== 'yes') {
                flash('Поставте позначку, що розумієте наслідки. Відкат не виконано.', 'err');
                back('kopii/?kopiia=' . rawurlencode($name));
            }
            $r = backup_restore($name);
            if (!$r) {
                flash('Не вдалося прочитати цю копію. Нічого не змінено.', 'err');
                back('kopii');
            }
            journal_add('відкат до копії', 'резервна копія', $name . ' (повернено ' . ($r['changed'] + $r['returned']) . ', прибрано ' . $r['removed'] . ')', 'kopii');
            flash('Сайт повернуто до стану з копії. Змінено записів: ' . $r['changed'] . ', повернено видалених: ' . $r['returned'] . ', прибрано створених пізніше: ' . $r['removed']
                . '. Файлів повернено з кошика: ' . $r['files_back'] . ($r['files_missing'] ? ', не знайдено: ' . $r['files_missing'] : '')
                . '. Стан, який був до відкату, збережено окремою копією — до нього теж можна повернутися.');
            back('kopii');
        }
        if ($name !== '') {
            $data = backup_read($name);
            if (!$data) {
                flash('Такої копії немає.', 'err');
                back('kopii');
            }
            admin_view('backup-restore', ['name' => $name, 'data' => $data, 'diff' => backup_diff($data), 'isOwner' => $isOwner]);
        }
        admin_view('backups', ['isOwner' => $isOwner]);

    /* ═════════ Фото для галерей у сторінках (відповідь — JSON) ═════════ */
    case 'zavantazhennia':
        header('Content-Type: application/json; charset=utf-8');
        if (!$isPost) {
            http_response_code(405);
            exit('{"ok":false}');
        }
        $out = ['ok' => true, 'images' => [], 'errors' => []];
        foreach (uploaded_files('photos') as $f) {
            try {
                $out['images'][] = save_image($f, 'pages', 1600, pathinfo((string) $f['name'], PATHINFO_FILENAME) ?: 'foto');
            } catch (RuntimeException $ex) {
                $out['errors'][] = $ex->getMessage();
            }
        }
        if ($out['images']) {
            journal_add('завантажено фото', 'сторінка', count($out['images']) . ' шт.');
        }
        exit(json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

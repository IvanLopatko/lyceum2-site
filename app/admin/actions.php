<?php
/** Дії адмінки. Змінні з admin.php: $seg, $section, $isPost, $user. */

declare(strict_types=1);

if (!defined('ROOT')) {
    http_response_code(404);
    exit;
}

$isAdmin = is_admin_user($user);
$isOwner = is_owner($user);

/** Адреси, зайняті службовими розділами сайту: сторінку з такою адресою створити не можна. */
const RESERVED_SLUGS = ['admin', 'uploads', 'assets', 'app', 'content', 'config', 'tools', 'docs', 'novyny', 'dokumenty', 'kadrovyj-sklad', 'gurtky', 'poshuk', 'index', 'robots', 'sitemap', 'favicon', 'storinka', 'rubryka'];

/** Розділи, доступні лише адміністраторові. */
const ADMIN_ONLY = ['nalashtuvannia', 'holovna', 'teksty', 'rubryky', 'plan', 'zhurnal', 'kopii'];

function next_id(array $list, int $start = 1): int
{
    $ids = array_map(static fn($x) => (int) ($x['id'] ?? 0), $list);
    return max($ids ?: [$start - 1]) + 1;
}

function find_index(array $list, int $id): ?int
{
    foreach ($list as $i => $x) {
        if ((int) ($x['id'] ?? 0) === $id) {
            return $i;
        }
    }
    return null;
}

if (in_array($section, ADMIN_ONLY, true) && !$isAdmin) {
    flash('Цей розділ доступний лише адміністраторові сайту.', 'err');
    back('');
}

require APP . '/admin/actions-more.php';

switch ($section) {

    /* ═════════ Огляд ═════════ */
    case '':
        if ($isPost && post('do') === 'purge') {
            if (!$isAdmin) {
                flash('Прибирати зразкові записи може лише адміністратор сайту.', 'err');
                back('');
            }
            if (post('confirm') !== 'yes') {
                flash('Поставте позначку, що розумієте наслідки.', 'err');
                back('');
            }
            $n = 0;
            foreach (['documents.json', 'clubs.json', 'staff.json'] as $f) {
                $list = store_read($f, []);
                $keep = array_values(array_filter($list, static fn($x) => empty($x['demo'])));
                $n += count($list) - count($keep);
                if (count($keep) !== count($list)) {
                    store_write($f, $keep);
                }
            }
            journal_add('прибрано зразкові записи', 'сайт', (string) $n);
            flash("Прибрано зразкових записів: $n. Попередній стан можна повернути в розділі «Історія змін».");
            back('');
        }
        admin_view('dashboard', ['isAdmin' => $isAdmin]);

    /* ═════════ Сторінки ═════════ */
    case 'storinky':
        $slug = $seg[1] ?? '';
        if ($slug === '') {
            admin_view('pages');
        }
        $isNew = $slug === 'nova';
        $page = $isNew ? ['slug' => '', 'parent' => '', 'title' => '', 'lead' => '', 'order' => 50, 'template' => false, 'blocks' => []] : page($slug);
        if (!$page) {
            flash('Такої сторінки немає.', 'err');
            back('storinky');
        }
        if ($isPost && post('do') === 'delete' && !$isNew) {
            if (page_children($slug)) {
                flash('Спершу перенесіть або видаліть підсторінки.', 'err');
                back('storinky/' . $slug);
            }
            if (in_array($slug, SPECIAL_PAGES, true)) {
                flash('Цю службову сторінку видаляти не можна.', 'err');
                back('storinky/' . $slug);
            }
            store_delete('pages/' . $slug . '.json');
            foreach (uploads_in($page) as $u) {
                trash_upload($u);
            }
            journal_add('видалено', 'сторінка', (string) $page['title']);
            flash('Сторінку видалено. Повернути її можна в розділі «Історія змін».');
            back('storinky');
        }
        if ($isPost) {
            $title = ct(post('title'));
            $blocks = json_decode((string) ($_POST['blocks'] ?? '[]'), true);
            $blocks = clean_blocks(is_array($blocks) ? $blocks : [], true);
            if (!$isAdmin) {
                // Блок «HTML» може додавати й змінювати лише адміністратор: у ньому дозволено будь-який код.
                // Від редактора приймаємо тільки ті блоки, що вже є на сторінці, без жодних змін.
                $known = [];
                foreach ($page['blocks'] ?? [] as $b0) {
                    if (($b0['type'] ?? '') === 'html') {
                        $known[] = (string) ($b0['html'] ?? '');
                    }
                }
                $blocks = array_values(array_filter($blocks, static fn(array $b): bool => $b['type'] !== 'html' || in_array($b['html'], $known, true)));
            }
            $parent = post('parent');
            // у формі лишаємо те, що людина ввела, — щоб помилка не коштувала їй роботи
            $icon = preg_replace('~[^a-z]~', '', post('icon')) ?? '';
            $draft = array_merge($page, ['title' => $title, 'lead' => ct(post('lead')), 'parent' => $parent, 'order' => (int) post('order', '0'), 'template' => isset($_POST['template']), 'blocks' => $blocks, 'icon' => $icon]);
            if ($title === '') {
                flash('Заголовок не може бути порожнім. Решту введеного збережено у формі — допишіть заголовок і натисніть «Зберегти».', 'err');
                admin_view('page-edit', ['page' => $draft, 'isNew' => $isNew]);
            }
            $newSlug = $isNew ? slugify(post('slug') ?: $title) : $slug;
            if ($isNew && in_array($newSlug, RESERVED_SLUGS, true)) {
                $newSlug .= '-storinka';
            }
            if ($isNew && page($newSlug)) {
                $newSlug .= '-' . substr(bin2hex(random_bytes(2)), 0, 3);
            }
            // розділом може бути лише сторінка верхнього рівня; розділ із підсторінками лишається нагорі
            $parentPage = $parent !== '' ? page($parent) : null;
            if ($parent !== '' && (!$parentPage || $parent === $newSlug || ($parentPage['parent'] ?? '') !== '' || in_array($parent, SPECIAL_PAGES, true))) {
                $parent = '';
            }
            if ($parent !== '' && !$isNew && page_children($slug)) {
                flash('У цієї сторінки є підсторінки, тому перенести її всередину іншого розділу не можна. Спершу перенесіть підсторінки.', 'err');
                admin_view('page-edit', ['page' => array_merge($draft, ['parent' => '']), 'isNew' => $isNew]);
            }
            $data = [
                'slug' => $newSlug,
                'parent' => $parent,
                'title' => $title,
                'lead' => $draft['lead'],
                'order' => $draft['order'],
                'template' => $draft['template'],
                'updated' => today(),
                'blocks' => $blocks,
            ];
            if ($icon !== '') {
                $data['icon'] = $icon;
            }
            store_write('pages/' . $newSlug . '.json', $data);
            // фото, прибрані з галерей сторінки, зникають із сайту (у кошик на 30 днів)
            foreach (array_diff(uploads_in($page), uploads_in($data)) as $gone) {
                trash_upload($gone);
            }
            journal_add($isNew ? 'створено' : 'змінено', 'сторінка', $title, 'storinky/' . $newSlug);
            flash('Збережено. Сторінка на сайті вже оновилась.');
            back('storinky/' . $newSlug);
        }
        admin_view('page-edit', ['page' => $page, 'isNew' => $isNew]);

    /* ═════════ Новини ═════════ */
    case 'novyny':
        $id = $seg[1] ?? '';
        if ($id === '') {
            admin_view('news');
        }
        $isNew = $id === 'nova';
        $item = $isNew ? ['id' => 0, 'date' => date('Y-m-d H:i'), 'title' => '', 'excerpt' => '', 'categories' => ['zhyttia'], 'text' => [], 'cover' => '', 'images' => [], 'source' => ''] : news_item((int) $id);
        if (!$item) {
            flash('Такої новини немає.', 'err');
            back('novyny');
        }
        if ($isPost && post('do') === 'delete' && !$isNew) {
            $photos = news_photos($item);
            store_delete('news/' . (int) $id . '.json');
            foreach ($photos as $ph) {
                trash_upload($ph);
            }
            journal_add('видалено', 'новина', (string) $item['title']);
            flash('Новину видалено. Повернути її разом із фото можна протягом 30 днів у розділі «Історія змін».');
            back('novyny');
        }
        if ($isPost) {
            $errors = [];
            $paras = json_decode((string) ($_POST['text_json'] ?? '[]'), true);
            $item['title'] = ct(post('title'));
            $item['date'] = preg_match('~^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}~', post('date')) && strtotime(post('date')) ? str_replace('T', ' ', substr(post('date'), 0, 16)) : date('Y-m-d H:i');
            $cats = array_map('strval', (array) ($_POST['categories'] ?? []));
            $known = array_column(taxonomies()['news'], 'slug');
            $item['categories'] = array_values(array_intersect($cats, $known)) ?: ['zhyttia'];
            $item['text'] = clean_paragraphs(is_array($paras) ? $paras : []);
            $item['excerpt'] = ct(post('excerpt'));
            $item['source'] = preg_match('~^https?://~i', post('source')) && filter_var(post('source'), FILTER_VALIDATE_URL) ? post('source') : '';
            $item['draft'] = isset($_POST['draft']);
            if ($item['title'] === '') {
                flash('Заголовок не може бути порожнім. Якщо ви додавали фото, виберіть їх ще раз.', 'err');
                admin_view('news-edit', ['item' => $item, 'isNew' => $isNew]);
            }
            // фото: усі в одному списку (обкладинка — одна з них), прибрати позначені, додати нові
            $photos = news_photos($item);
            $remove = array_values(array_intersect(array_map('strval', (array) ($_POST['remove'] ?? [])), $photos));
            $photos = array_values(array_diff($photos, $remove));
            foreach (uploaded_files('photos') as $f) {
                try {
                    $photos[] = save_image($f, 'news', 1600, $item['title'] ?: 'novyna');
                } catch (RuntimeException $ex) {
                    $errors[] = $ex->getMessage();
                }
            }
            $cover = post('cover');
            if (in_array($cover, $photos, true)) {
                $item['cover'] = $cover;
            } elseif (!in_array($item['cover'] ?? '', $photos, true)) {
                $item['cover'] = $photos[0] ?? '';
            }
            $item['images'] = $photos;
            // описи фото для програм читання з екрана
            $alts = [];
            foreach ((array) ($_POST['alt'] ?? []) as $ph => $txt) {
                $txt = mb_substr(ct($txt), 0, 200);
                if ($txt !== '' && in_array((string) $ph, $photos, true)) {
                    $alts[(string) $ph] = $txt;
                }
            }
            $item['alts'] = $alts ?: new stdClass();
            if ($isNew) {
                $item['id'] = news_next_id();
            }
            store_write('news/' . $item['id'] . '.json', $item);
            foreach ($remove as $r) {
                trash_upload($r);
            }
            journal_add($isNew ? 'створено' : 'змінено', $item['draft'] ? 'чернетка новини' : 'новина', $item['title'], 'novyny/' . $item['id']);
            if ($errors) {
                flash('Новину збережено, але не всі фото додались: ' . implode(' ', array_unique($errors)), 'err');
            } else {
                flash($item['draft'] ? 'Чернетку збережено. На сайті її ще не видно.' : 'Новину збережено.');
            }
            back('novyny/' . $item['id']);
        }
        admin_view('news-edit', ['item' => $item, 'isNew' => $isNew]);

    /* ═════════ Документи ═════════ */
    case 'dokumenty':
        $id = $seg[1] ?? '';
        $docs = documents();
        if ($id === '') {
            admin_view('docs');
        }
        $isNew = $id === 'novyi';
        $idx = $isNew ? null : find_index($docs, (int) $id);
        if (!$isNew && $idx === null) {
            flash('Такого документа немає.', 'err');
            back('dokumenty');
        }
        $doc = $isNew ? ['id' => 0, 'title' => '', 'sections' => [], 'number' => '', 'approved' => '', 'published' => '', 'valid' => 'current', 'file' => '', 'demo' => false] : $docs[$idx];
        if ($isPost && post('do') === 'delete' && !$isNew) {
            array_splice($docs, $idx, 1);
            store_write('documents.json', $docs);
            if (!empty($doc['file'])) {
                trash_upload($doc['file']);
            }
            journal_add('видалено', 'документ', (string) $doc['title']);
            flash('Документ прибрано з реєстру, файл за посиланням більше не відкривається. Повернути можна протягом 30 днів у розділі «Історія змін».');
            back('dokumenty');
        }
        if ($isPost) {
            $oldFile = (string) ($doc['file'] ?? '');
            $doc['title'] = ct(post('title'));
            $known = array_column(taxonomies()['documents'], 'slug');
            $doc['sections'] = array_values(array_intersect(array_map('strval', (array) ($_POST['sections'] ?? [])), $known));
            $doc['number'] = ct(post('number'));
            $doc['approved'] = clean_date(post('approved'));
            $doc['published'] = clean_date(post('published'));
            $doc['valid'] = post('valid') === 'archive' ? 'archive' : 'current';
            $doc['demo'] = isset($_POST['demo']);
            if ($doc['title'] === '') {
                flash('Назва документа не може бути порожньою.', 'err');
                admin_view('doc-edit', ['doc' => $doc, 'isNew' => $isNew]);
            }
            if (isset($_POST['remove_file'])) {
                $doc['file'] = '';
            }
            foreach (uploaded_files('file') as $f) {
                try {
                    $doc['file'] = save_pdf($f, $doc['title'] ?: 'dokument');
                    if ($doc['published'] === '') {
                        $doc['published'] = today();
                    }
                } catch (RuntimeException $ex) {
                    flash($ex->getMessage(), 'err');
                    $doc['file'] = $oldFile;
                    admin_view('doc-edit', ['doc' => $doc, 'isNew' => $isNew]);
                }
            }
            if ($isNew) {
                $doc['id'] = next_id($docs, 1);
                $docs[] = $doc;
            } else {
                $docs[$idx] = $doc;
            }
            store_write('documents.json', $docs);
            if ($oldFile !== '' && $oldFile !== $doc['file']) {
                trash_upload($oldFile);
            }
            journal_add($isNew ? 'створено' : 'змінено', 'документ', $doc['title'], 'dokumenty/' . $doc['id']);
            flash('Документ збережено.');
            back('dokumenty/' . $doc['id']);
        }
        admin_view('doc-edit', ['doc' => $doc, 'isNew' => $isNew]);

    /* ═════════ Кадри ═════════ */
    case 'kadry':
        $id = $seg[1] ?? '';
        $list = store_read('staff.json', []);
        if ($id === '') {
            admin_view('staff');
        }
        $isNew = $id === 'novyi';
        $idx = $isNew ? null : find_index($list, (int) $id);
        if (!$isNew && $idx === null) {
            flash('Такого запису немає.', 'err');
            back('kadry');
        }
        $p = $isNew ? ['id' => 0, 'name' => '', 'position' => '', 'subjects' => '', 'group' => 'vchyteli', 'order' => 100, 'photo' => '', 'demo' => false] : $list[$idx];
        if ($isPost && post('do') === 'delete' && !$isNew) {
            array_splice($list, $idx, 1);
            store_write('staff.json', $list);
            if (!empty($p['photo'])) {
                trash_upload($p['photo']);
            }
            journal_add('видалено', 'працівник', (string) $p['name']);
            flash('Запис видалено разом із фото. Повернути можна протягом 30 днів у розділі «Історія змін».');
            back('kadry');
        }
        if ($isPost) {
            $oldPhoto = (string) ($p['photo'] ?? '');
            $p['name'] = ct(post('name'));
            $p['position'] = ct(post('position'));
            $p['subjects'] = ct(post('subjects'));
            $groups = array_column(taxonomies()['staff'], 'slug');
            $p['group'] = in_array(post('group'), $groups, true) ? post('group') : 'vchyteli';
            $p['order'] = (int) post('order', '100');
            $p['demo'] = isset($_POST['demo']);
            if ($p['name'] === '') {
                flash('Вкажіть прізвище, ім’я та по батькові.', 'err');
                admin_view('staff-edit', ['p' => $p, 'isNew' => $isNew]);
            }
            if (isset($_POST['remove_photo'])) {
                $p['photo'] = '';
            }
            $photoError = '';
            foreach (uploaded_files('photo') as $f) {
                try {
                    $p['photo'] = save_image($f, 'staff', 800, $p['name'] ?: 'foto');
                } catch (RuntimeException $ex) {
                    $photoError = $ex->getMessage();
                }
            }
            if ($isNew) {
                $p['id'] = next_id($list, 1);
                $list[] = $p;
            } else {
                $list[$idx] = $p;
            }
            store_write('staff.json', $list);
            if ($oldPhoto !== '' && $oldPhoto !== $p['photo']) {
                trash_upload($oldPhoto);
            }
            journal_add($isNew ? 'створено' : 'змінено', 'працівник', $p['name'], 'kadry/' . $p['id']);
            $photoError !== '' ? flash('Запис збережено, але фото не додалось: ' . $photoError, 'err') : flash('Збережено.');
            back('kadry/' . $p['id']);
        }
        admin_view('staff-edit', ['p' => $p, 'isNew' => $isNew]);

    /* ═════════ Гуртки ═════════ */
    case 'gurtky':
        $id = $seg[1] ?? '';
        $list = clubs();
        if ($id === '') {
            admin_view('clubs');
        }
        $isNew = $id === 'novyi';
        $idx = $isNew ? null : find_index($list, (int) $id);
        if (!$isNew && $idx === null) {
            flash('Такого гуртка немає.', 'err');
            back('gurtky');
        }
        $c = $isNew ? ['id' => 0, 'title' => '', 'leader' => '', 'classes' => '', 'day' => '', 'time' => '', 'room' => '', 'text' => '', 'demo' => false] : $list[$idx];
        if ($isPost && post('do') === 'delete' && !$isNew) {
            array_splice($list, $idx, 1);
            store_write('clubs.json', $list);
            journal_add('видалено', 'гурток', (string) $c['title']);
            flash('Гурток видалено. Повернути можна в розділі «Історія змін».');
            back('gurtky');
        }
        if ($isPost) {
            foreach (['title', 'leader', 'classes', 'day', 'time', 'room', 'text'] as $k) {
                $c[$k] = ct(post($k));
            }
            $c['demo'] = isset($_POST['demo']);
            if ($c['title'] === '') {
                flash('Вкажіть назву гуртка.', 'err');
                admin_view('club-edit', ['c' => $c, 'isNew' => $isNew]);
            }
            if ($isNew) {
                $c['id'] = next_id($list, 1);
                $list[] = $c;
            } else {
                $list[$idx] = $c;
            }
            store_write('clubs.json', $list);
            journal_add($isNew ? 'створено' : 'змінено', 'гурток', $c['title'], 'gurtky/' . $c['id']);
            flash('Збережено.');
            back('gurtky/' . $c['id']);
        }
        admin_view('club-edit', ['c' => $c, 'isNew' => $isNew]);

    /* ═════════ Налаштування (лише адміністратор) ═════════ */
    case 'nalashtuvannia':
        if ($isPost) {
            $s = settings();
            foreach (['name', 'motto', 'full_name', 'founded'] as $k) {
                $s['site'][$k] = ct(post('site_' . $k));
            }
            $siteUrl = rtrim(post('site_url'), '/');
            $s['site']['url'] = preg_match('~^https?://[A-Za-z0-9.\-]+(:\d+)?(/[^\s]*)?$~', $siteUrl) ? $siteUrl : '';
            $s['site']['demo_bar'] = isset($_POST['site_demo_bar']);
            foreach (['phone', 'phone_extra', 'hours', 'reception', 'public_info', 'email', 'facebook'] as $k) {
                $s['contacts'][$k] = ct(post('contacts_' . $k));
            }
            $b = [];
            foreach ((array) ($_POST['b_name'] ?? []) as $i => $n) {
                $n = ct($n);
                $a = ct($_POST['b_address'][$i] ?? '');
                if ($n !== '' || $a !== '') {
                    $b[] = ['name' => $n, 'address' => $a];
                }
            }
            // посилання на Facebook — лише звичайна веб-адреса
            $fb = (string) ($s['contacts']['facebook'] ?? '');
            if ($fb !== '' && !preg_match('~^https?://~i', $fb)) {
                $fb = preg_match('~^[a-z0-9.-]+\.[a-z]{2,}(/|$)~i', $fb) ? 'https://' . $fb : '';
            }
            $s['contacts']['facebook'] = $fb;
            $s['contacts']['buildings'] = $b;
            foreach (['pupils', 'pupils_date', 'classes', 'classes_date', 'staff', 'staff_date', 'shelters', 'shelters_note'] as $k) {
                $s['numbers'][$k] = ct(post('numbers_' . $k));
            }
            foreach (['format', 'format_note', 'alarm', 'alarm_note', 'food', 'food_note', 'office', 'office_note'] as $k) {
                $s['today'][$k] = ct(post('today_' . $k));
            }
            $r = [];
            foreach ((array) ($_POST['r_area'] ?? []) as $i => $area) {
                $row = ['icon' => preg_replace('~[^a-z]~', '', (string) ($_POST['r_icon'][$i] ?? 'doc')) ?: 'doc', 'area' => ct($area), 'who' => ct($_POST['r_who'][$i] ?? ''), 'position' => ct($_POST['r_position'][$i] ?? '')];
                if ($row['area'] !== '' || $row['who'] !== '') {
                    $r[] = $row;
                }
            }
            $s['responsible'] = $r;
            store_write('settings.json', $s);
            journal_add('змінено', 'налаштування', '', 'nalashtuvannia');
            $logoError = '';
            foreach (uploaded_files('logo') as $f) {
                try {
                    save_logo($f);
                    journal_add('замінено', 'емблема');
                } catch (RuntimeException $ex) {
                    $logoError = $ex->getMessage();
                }
            }
            if ($logoError !== '') {
                flash('Налаштування збережено, але емблему не замінено: ' . $logoError, 'err');
                back('nalashtuvannia');
            }
            flash('Налаштування збережено.');
            back('nalashtuvannia');
        }
        admin_view('settings');

    /* ═════════ Історія змін: попередні версії та видалене ═════════ */
    case 'istoriia':
        $rel = (string) ($_GET['f'] ?? ($_POST['f'] ?? ''));
        $okRel = preg_match('~^(pages/[a-z0-9-]+|news/\d+|documents|staff|clubs|settings|home|taxonomies|profile-plan)\.json$~', $rel) === 1;
        if (!$isAdmin && in_array($rel, ['settings.json', 'home.json', 'taxonomies.json', 'profile-plan.json'], true)) {
            $okRel = false;
        }
        if ($isPost && post('do') === 'restore') {
            $data = $okRel ? store_version($rel, post('v')) : null;
            if (!is_array($data)) {
                flash('Не вдалося знайти цю версію.', 'err');
                back('istoriia');
            }
            store_write($rel, $data);
            $missing = 0;
            foreach (uploads_in($data) as $u) {
                if (!restore_upload($u)) {
                    $missing++;
                }
            }
            journal_add('відновлено попередню версію', 'запис', backup_label($rel, (string) json_encode($data, JSON_UNESCAPED_UNICODE)));
            flash('Версію відновлено.' . ($missing ? " Не вдалося повернути файлів: $missing (минуло понад 30 днів або їх прибрали вручну)." : ''));
            if (preg_match('~^pages/([a-z0-9-]+)\.json$~', $rel, $m)) {
                back('storinky/' . $m[1]);
            }
            if (preg_match('~^news/(\d+)\.json$~', $rel, $m)) {
                back('novyny/' . $m[1]);
            }
            back('istoriia/?f=' . rawurlencode($rel));
        }
        admin_view('history', ['rel' => $okRel ? $rel : '', 'isAdmin' => $isAdmin]);

    /* ═════════ Обліковий запис і доступи ═════════ */
    case 'oblikovyi-zapys':
        $do = $isPost ? post('do') : '';
        if ($do === 'password') {
            if (!password_verify((string) ($_POST['old'] ?? ''), $user['hash'])) {
                flash('Поточний пароль неправильний.', 'err');
            } elseif ($p = password_problem((string) ($_POST['new'] ?? ''), (string) $user['login'])) {
                flash($p, 'err');
            } elseif (($_POST['new'] ?? '') !== ($_POST['new2'] ?? '')) {
                flash('Нові паролі не збігаються.', 'err');
            } elseif (password_verify((string) $_POST['new'], $user['hash'])) {
                flash('Новий пароль має відрізнятися від поточного.', 'err');
            } else {
                $pv = (int) ($user['pv'] ?? 0) + 1;
                admin_update_user((string) $user['login'], static function (array $u) use ($pv): array {
                    $u['hash'] = password_hash((string) $_POST['new'], PASSWORD_DEFAULT);
                    $u['pv'] = $pv;
                    unset($u['must_change']);
                    return $u;
                });
                // цей сеанс лишається, усі інші (на інших пристроях) завершуються
                $_SESSION['pv'] = $pv;
                session_regenerate_id(true);
                journal_add('змінено власний пароль', 'обліковий запис', (string) $user['name']);
                flash('Пароль змінено. На інших пристроях доведеться ввійти знову.');
            }
            back('oblikovyi-zapys');
        }
        // усе нижче — лише головний адміністратор
        if ($do !== '' && !$isOwner) {
            flash('Доступами співробітників керує лише головний адміністратор.', 'err');
            back('oblikovyi-zapys');
        }
        $target = null;
        foreach (admin_users() as $u) {
            if ($u['login'] === post('login')) {
                $target = $u;
            }
        }
        if ($do === 'add') {
            $login = strtolower(post('login'));
            if (!preg_match('~^[a-z0-9._-]{3,32}$~', $login)) {
                flash('Логін: 3–32 латинські літери, цифри, крапка, дефіс.', 'err');
            } elseif ($target || in_array($login, array_column(admin_users(), 'login'), true)) {
                flash('Такий логін уже є.', 'err');
            } elseif ($p = password_problem((string) ($_POST['password'] ?? ''), $login)) {
                flash($p, 'err');
            } else {
                $users = admin_users();
                $users[] = ['login' => $login, 'name' => ct(post('name')) ?: $login, 'role' => post('role') === 'admin' ? 'admin' : 'editor',
                    'hash' => password_hash((string) $_POST['password'], PASSWORD_DEFAULT), 'pv' => 0, 'must_change' => true, 'created' => today()];
                admin_save_users($users);
                journal_add('створено доступ', 'обліковий запис', (ct(post('name')) ?: $login) . ' (' . role_label(post('role') === 'admin' ? 'admin' : 'editor') . ')');
                flash('Доступ створено. Передайте логін і тимчасовий пароль людині особисто, не в чаті: під час першого входу сайт попросить її задати власний пароль.');
            }
            back('oblikovyi-zapys');
        }
        if (in_array($do, ['remove', 'role', 'resetpw', 'transfer'], true) && (!$target || $target['login'] === $user['login'])) {
            flash('Такого облікового запису немає.', 'err');
            back('oblikovyi-zapys');
        }
        if ($do === 'remove') {
            admin_save_users(array_values(array_filter(admin_users(), static fn($u) => $u['login'] !== $target['login'])));
            journal_add('забрано доступ', 'обліковий запис', (string) $target['name']);
            flash('Доступ забрано: ' . $target['name'] . ' більше не зможе ввійти.');
            back('oblikovyi-zapys');
        }
        if ($do === 'role') {
            $role = post('role') === 'admin' ? 'admin' : 'editor';
            admin_update_user((string) $target['login'], static function (array $u) use ($role): array {
                $u['role'] = $role;
                return $u;
            });
            journal_add('змінено роль', 'обліковий запис', $target['name'] . ' → ' . role_label($role));
            flash('Роль змінено: ' . $target['name'] . ' — ' . role_label($role) . '.');
            back('oblikovyi-zapys');
        }
        if ($do === 'resetpw') {
            if ($p = password_problem((string) ($_POST['password'] ?? ''), (string) $target['login'])) {
                flash($p, 'err');
                back('oblikovyi-zapys');
            }
            admin_update_user((string) $target['login'], static function (array $u): array {
                $u['hash'] = password_hash((string) $_POST['password'], PASSWORD_DEFAULT);
                $u['pv'] = (int) ($u['pv'] ?? 0) + 1; // усі відкриті сеанси цієї людини завершуються
                $u['must_change'] = true;
                return $u;
            });
            journal_add('видано новий тимчасовий пароль', 'обліковий запис', (string) $target['name']);
            flash('Новий тимчасовий пароль для ' . $target['name'] . ' задано. Передайте його особисто: під час входу сайт попросить замінити його власним.');
            back('oblikovyi-zapys');
        }
        if ($do === 'transfer') {
            if (!password_verify((string) ($_POST['password'] ?? ''), (string) $user['hash'])) {
                flash('Ваш пароль неправильний. Права не передано.', 'err');
                back('oblikovyi-zapys');
            }
            $users = admin_users();
            foreach ($users as $i => $u) {
                if ($u['login'] === $target['login']) {
                    $users[$i]['role'] = 'owner';
                } elseif ($u['login'] === $user['login']) {
                    $users[$i]['role'] = 'admin';
                }
            }
            admin_save_users($users);
            journal_add('передано права головного адміністратора', 'обліковий запис', (string) $target['name']);
            flash('Права головного адміністратора передано: ' . $target['name'] . '. Ви тепер адміністратор.');
            back('oblikovyi-zapys');
        }
        admin_view('account', ['isAdmin' => $isAdmin, 'isOwner' => $isOwner]);
}

http_response_code(404);
admin_view('notfound');

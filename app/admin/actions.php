<?php
/** Дії адмінки. Змінні з admin.php: $seg, $section, $isPost, $user. */

declare(strict_types=1);

$isAdmin = ($user['role'] ?? '') === 'admin';

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

switch ($section) {

    /* ═════════ Огляд ═════════ */
    case '':
        if ($isPost && post('do') === 'purge') {
            if (post('confirm') !== 'yes') {
                flash('Поставте позначку, що розумієте наслідки.', 'err');
                back('');
            }
            $n = 0;
            foreach (['documents.json', 'clubs.json', 'staff.json'] as $f) {
                $list = store_read($f, []);
                $keep = array_values(array_filter($list, static fn($x) => empty($x['demo'])));
                $n += count($list) - count($keep);
                store_write($f, $keep);
            }
            flash("Прибрано зразкових записів: $n. Їхні попередні версії збережено в історії.");
            back('');
        }
        admin_view('dashboard');

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
            flash('Сторінку видалено. Копія лишилася в історії змін.');
            back('storinky');
        }
        if ($isPost) {
            $title = ct(post('title'));
            if ($title === '') {
                flash('Заголовок не може бути порожнім.', 'err');
                admin_view('page-edit', ['page' => array_merge($page, $_POST), 'isNew' => $isNew]);
            }
            $blocks = json_decode((string) ($_POST['blocks'] ?? '[]'), true);
            $newSlug = $isNew ? slugify(post('slug') ?: $title) : $slug;
            if ($isNew && page($newSlug)) {
                $newSlug .= '-' . substr(bin2hex(random_bytes(2)), 0, 3);
            }
            $parent = post('parent');
            if ($parent !== '' && (!page($parent) || $parent === $newSlug)) {
                $parent = '';
            }
            $data = [
                'slug' => $newSlug,
                'parent' => $parent,
                'title' => $title,
                'lead' => ct(post('lead')),
                'order' => (int) post('order', '0'),
                'template' => isset($_POST['template']),
                'updated' => today(),
                'blocks' => clean_blocks(is_array($blocks) ? $blocks : [], $isAdmin),
            ];
            store_write('pages/' . $newSlug . '.json', $data);
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
            store_delete('news/' . (int) $id . '.json');
            flash('Новину видалено. Копія лишилася в історії змін.');
            back('novyny');
        }
        if ($isPost) {
            $errors = [];
            $paras = json_decode((string) ($_POST['text_json'] ?? '[]'), true);
            $item['title'] = ct(post('title'));
            $item['date'] = preg_match('~^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}~', post('date')) ? str_replace('T', ' ', substr(post('date'), 0, 16)) : date('Y-m-d H:i');
            $cats = array_map('strval', (array) ($_POST['categories'] ?? []));
            $known = array_column(taxonomies()['news'], 'slug');
            $item['categories'] = array_values(array_intersect($cats, $known)) ?: ['zhyttia'];
            $item['text'] = clean_paragraphs(is_array($paras) ? $paras : []);
            $item['excerpt'] = ct(post('excerpt'));
            $item['source'] = filter_var(post('source'), FILTER_VALIDATE_URL) ? post('source') : '';
            // фото: прибрати позначені, додати нові
            $remove = array_map('strval', (array) ($_POST['remove'] ?? []));
            $item['images'] = array_values(array_filter($item['images'], static fn($i) => !in_array($i, $remove, true)));
            if (in_array($item['cover'], $remove, true)) {
                $item['cover'] = '';
            }
            foreach (uploaded_files('photos') as $f) {
                try {
                    $item['images'][] = save_image($f, 'news', 1600, $item['title'] ?: 'novyna');
                } catch (RuntimeException $ex) {
                    $errors[] = $ex->getMessage();
                }
            }
            $cover = post('cover');
            if ($cover !== '' && in_array($cover, $item['images'], true)) {
                $item['cover'] = $cover;
            }
            if ($item['cover'] === '' && $item['images']) {
                $item['cover'] = $item['images'][0];
            }
            if ($item['title'] === '') {
                $errors[] = 'Заголовок не може бути порожнім.';
            }
            if ($errors) {
                flash(implode(' ', $errors), 'err');
                if ($item['title'] === '') {
                    admin_view('news-edit', ['item' => $item, 'isNew' => $isNew]);
                }
            }
            if ($isNew) {
                $item['id'] = news_next_id();
            }
            store_write('news/' . $item['id'] . '.json', $item);
            foreach ($remove as $r) {
                delete_upload($r);
            }
            if (!$errors) {
                flash('Новину збережено.');
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
            flash('Документ прибрано з реєстру. Сам файл лишився на сервері й у історії змін.');
            back('dokumenty');
        }
        if ($isPost) {
            $doc['title'] = ct(post('title'));
            $known = array_column(taxonomies()['documents'], 'slug');
            $doc['sections'] = array_values(array_intersect(array_map('strval', (array) ($_POST['sections'] ?? [])), $known));
            $doc['number'] = ct(post('number'));
            $doc['approved'] = clean_date(post('approved'));
            $doc['published'] = clean_date(post('published'));
            $doc['valid'] = post('valid') === 'archive' ? 'archive' : 'current';
            $doc['demo'] = isset($_POST['demo']);
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
                    admin_view('doc-edit', ['doc' => $doc, 'isNew' => $isNew]);
                }
            }
            if ($doc['title'] === '') {
                flash('Назва документа не може бути порожньою.', 'err');
                admin_view('doc-edit', ['doc' => $doc, 'isNew' => $isNew]);
            }
            if ($isNew) {
                $doc['id'] = next_id($docs, 1);
                $docs[] = $doc;
            } else {
                $docs[$idx] = $doc;
            }
            store_write('documents.json', $docs);
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
            back('kadry');
        }
        $p = $isNew ? ['id' => 0, 'name' => '', 'position' => '', 'subjects' => '', 'group' => 'vchyteli', 'order' => 100, 'photo' => '', 'demo' => false] : $list[$idx];
        if ($isPost && post('do') === 'delete' && !$isNew) {
            array_splice($list, $idx, 1);
            store_write('staff.json', $list);
            flash('Запис видалено.');
            back('kadry');
        }
        if ($isPost) {
            $p['name'] = ct(post('name'));
            $p['position'] = ct(post('position'));
            $p['subjects'] = ct(post('subjects'));
            $groups = array_column(taxonomies()['staff'], 'slug');
            $p['group'] = in_array(post('group'), $groups, true) ? post('group') : 'vchyteli';
            $p['order'] = (int) post('order', '100');
            $p['demo'] = isset($_POST['demo']);
            if (isset($_POST['remove_photo'])) {
                $p['photo'] = '';
            }
            foreach (uploaded_files('photo') as $f) {
                try {
                    $p['photo'] = save_image($f, 'staff', 800, $p['name'] ?: 'foto');
                } catch (RuntimeException $ex) {
                    flash($ex->getMessage(), 'err');
                }
            }
            if ($p['name'] === '') {
                flash('Вкажіть прізвище, ім’я та по батькові.', 'err');
                admin_view('staff-edit', ['p' => $p, 'isNew' => $isNew]);
            }
            if ($isNew) {
                $p['id'] = next_id($list, 1);
                $list[] = $p;
            } else {
                $list[$idx] = $p;
            }
            store_write('staff.json', $list);
            flash('Збережено.');
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
            back('gurtky');
        }
        $c = $isNew ? ['id' => 0, 'title' => '', 'leader' => '', 'classes' => '', 'day' => '', 'time' => '', 'room' => '', 'text' => '', 'demo' => false] : $list[$idx];
        if ($isPost && post('do') === 'delete' && !$isNew) {
            array_splice($list, $idx, 1);
            store_write('clubs.json', $list);
            flash('Гурток видалено.');
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
            flash('Збережено.');
            back('gurtky/' . $c['id']);
        }
        admin_view('club-edit', ['c' => $c, 'isNew' => $isNew]);

    /* ═════════ Налаштування ═════════ */
    case 'nalashtuvannia':
        if ($isPost) {
            $s = settings();
            foreach (['name', 'motto', 'full_name', 'founded'] as $k) {
                $s['site'][$k] = ct(post('site_' . $k));
            }
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
            flash('Налаштування збережено.');
            back('nalashtuvannia');
        }
        admin_view('settings');

    /* ═════════ Обліковий запис ═════════ */
    case 'oblikovyi-zapys':
        if ($isPost && post('do') === 'password') {
            if (!password_verify((string) ($_POST['old'] ?? ''), $user['hash'])) {
                flash('Поточний пароль неправильний.', 'err');
            } elseif ($p = password_problem((string) ($_POST['new'] ?? ''))) {
                flash($p, 'err');
            } elseif (($_POST['new'] ?? '') !== ($_POST['new2'] ?? '')) {
                flash('Нові паролі не збігаються.', 'err');
            } else {
                $users = admin_users();
                foreach ($users as &$u) {
                    if ($u['login'] === $user['login']) {
                        $u['hash'] = password_hash((string) $_POST['new'], PASSWORD_DEFAULT);
                    }
                }
                unset($u);
                admin_save_users($users);
                flash('Пароль змінено.');
            }
            back('oblikovyi-zapys');
        }
        if ($isPost && post('do') === 'add' && $isAdmin) {
            $login = post('login');
            $users = admin_users();
            if (!preg_match('~^[a-z0-9._-]{3,32}$~', $login)) {
                flash('Логін: 3–32 латинські літери, цифри, крапка, дефіс.', 'err');
            } elseif (in_array($login, array_column($users, 'login'), true)) {
                flash('Такий логін уже є.', 'err');
            } elseif ($p = password_problem((string) ($_POST['password'] ?? ''))) {
                flash($p, 'err');
            } else {
                $users[] = ['login' => $login, 'name' => ct(post('name')) ?: $login, 'role' => post('role') === 'admin' ? 'admin' : 'editor', 'hash' => password_hash((string) $_POST['password'], PASSWORD_DEFAULT)];
                admin_save_users($users);
                flash('Обліковий запис додано. Передайте пароль людині особисто, не в чаті.');
            }
            back('oblikovyi-zapys');
        }
        if ($isPost && post('do') === 'remove' && $isAdmin) {
            $login = post('login');
            if ($login !== $user['login']) {
                admin_save_users(array_values(array_filter(admin_users(), static fn($u) => $u['login'] !== $login)));
                flash('Обліковий запис видалено.');
            }
            back('oblikovyi-zapys');
        }
        admin_view('account', ['isAdmin' => $isAdmin]);
}

http_response_code(404);
admin_view('notfound');

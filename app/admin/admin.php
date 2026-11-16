<?php
/**
 * Адмінка: /admin/...
 *
 * Розділи: огляд, сторінки, новини, документи, кадри, гуртки, налаштування, обліковий запис.
 * Усі зміни — через POST із перевіркою CSRF; кожна правка зберігає попередню версію файлу.
 */

declare(strict_types=1);

if (!defined('ROOT')) {
    http_response_code(404);
    exit;
}

require APP . '/admin/auth.php';
require APP . '/admin/upload.php';
require APP . '/admin/sanitize.php';

admin_session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; script-src 'self'; frame-src https://www.openstreetmap.org; frame-ancestors 'none'; form-action 'self'");
header('Cache-Control: no-store');

$path = current_path();
$seg = array_values(array_filter(explode('/', trim(substr($path, strlen('/admin')), '/')), 'strlen'));
$section = $seg[0] ?? '';
$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

function admin_view(string $name, array $vars = []): never
{
    $vars['user'] = current_user();
    extract($vars, EXTR_SKIP);
    ob_start();
    include APP . '/admin/views/' . $name . '.php';
    $body = (string) ob_get_clean();
    include APP . '/admin/views/layout.php';
    exit;
}

function flash(string $msg, string $kind = 'ok'): void
{
    $_SESSION['flash'] = [$msg, $kind];
}

function back(string $to): never
{
    redirect('/admin/' . ltrim($to, '/'));
}

function post(string $k, string $d = ''): string
{
    return trim((string) ($_POST[$k] ?? $d));
}

/* ── Перший запуск: створення облікового запису ── */
if (!admin_users()) {
    $token = setup_token();
    $error = '';
    if ($isPost) {
        csrf_check();
        if (!hash_equals($token, post('token'))) {
            $error = 'Код не збігається. Візьміть його з другого рядка файлу config/setup-token.php на сервері.';
        } elseif (!preg_match('~^[a-z0-9._-]{3,32}$~', post('login'))) {
            $error = 'Логін: 3–32 латинські літери, цифри, крапка, дефіс.';
        } elseif ($p = password_problem((string) ($_POST['password'] ?? ''), post('login'))) {
            $error = $p;
        } else {
            admin_save_users([['login' => post('login'), 'name' => ct(post('name')) ?: post('login'), 'role' => 'owner', 'hash' => password_hash((string) $_POST['password'], PASSWORD_DEFAULT), 'pv' => 0]]);
            @unlink(SETUP_TOKEN_FILE);
            flash('Обліковий запис головного адміністратора створено. Увійдіть.');
            back('');
        }
    }
    admin_view('setup', ['error' => $error, 'bare' => true]);
}

/* ── Відновлення доступу головного адміністратора (на сервері створено файл config/recover) ── */
if (recover_requested() && !current_user() && ($_GET['vhid'] ?? '') === '' && !($isPost && isset($_POST['login']))) {
    $token = recover_token();
    $error = '';
    $owner = null;
    foreach (admin_users() as $u) {
        if (is_owner($u)) {
            $owner = $u;
        }
    }
    if ($isPost && isset($_POST['recover']) && $owner) {
        csrf_check();
        if ($left = login_locked()) {
            $error = 'Забагато невдалих спроб. Спробуйте за ' . ceil($left / 60) . ' хв.';
        } elseif (!hash_equals($token, post('token'))) {
            login_fail();
            $error = 'Код не збігається. Візьміть його з другого рядка файлу config/recover-token.php на сервері.';
        } elseif ($p = password_problem((string) ($_POST['password'] ?? ''), (string) $owner['login'])) {
            $error = $p;
        } elseif (($_POST['password'] ?? '') !== ($_POST['password2'] ?? '')) {
            $error = 'Паролі не збігаються.';
        } else {
            admin_update_user((string) $owner['login'], static function (array $u): array {
                $u['hash'] = password_hash((string) $_POST['password'], PASSWORD_DEFAULT);
                $u['pv'] = (int) ($u['pv'] ?? 0) + 1;
                unset($u['must_change']);
                return $u;
            });
            recover_finish();
            $GLOBALS['user'] = $owner;
            journal_add('відновлено доступ', 'обліковий запис', (string) $owner['name']);
            flash('Пароль головного адміністратора змінено. Увійдіть з новим паролем.');
            back('');
        }
    }
    admin_view('recover', ['error' => $error, 'owner' => $owner, 'bare' => true]);
}

/* ── Вхід і вихід ── */
if ($section === 'vyhid') {
    $_SESSION = [];
    session_regenerate_id(true);
    back('');
}

$user = current_user();
if (!$user) {
    $error = '';
    if ($isPost && isset($_POST['login'])) {
        csrf_check();
        if ($left = login_locked()) {
            $error = 'Забагато невдалих спроб. Спробуйте за ' . ceil($left / 60) . ' хв.';
        } elseif (attempt_login(post('login'), (string) ($_POST['password'] ?? ''))) {
            back('');
        } else {
            $error = 'Неправильний логін або пароль.';
        }
    }
    admin_view('login', ['error' => $error, 'bare' => true]);
}

if ($isPost) {
    // Якщо форма з файлами більша, ніж приймає сервер, PHP відкидає її цілком — пояснюємо це по-людськи.
    if (!$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $lim = upload_limits();
        flash('Сервер не прийняв форму: файли разом важать більше, ніж дозволено за один раз (' . round($lim['post'] / 1048576) . ' МБ). Додавайте фото меншими порціями або зменште файли. Нічого не збережено.', 'err');
        redirect(current_path());
    }
    csrf_check();
}

// Тимчасовий пароль, який видав головний адміністратор, треба замінити власним, перш ніж працювати далі.
if (!empty($user['must_change']) && $section !== 'oblikovyi-zapys') {
    flash('Ви ввійшли з тимчасовим паролем. Задайте власний пароль — після цього відкриються всі розділи.', 'err');
    back('oblikovyi-zapys');
}

require APP . '/admin/actions.php';

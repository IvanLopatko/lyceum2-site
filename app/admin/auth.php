<?php
/**
 * Вхід в адмінку.
 *
 * Облікові записи — у config/users.php (поза git, недоступний з вебу через .htaccess).
 * Паролі зберігаються лише як password_hash. Після 5 невдалих спроб з однієї адреси
 * вхід блокується на 15 хвилин.
 */

declare(strict_types=1);

const USERS_FILE = CONFIG . '/users.php';
const ATTEMPTS_FILE = CONFIG . '/login-attempts.json';
const SETUP_TOKEN_FILE = CONFIG . '/setup-token.txt';
const SESSION_IDLE = 4 * 3600;

function admin_users(): array
{
    if (!is_file(USERS_FILE)) {
        return [];
    }
    $u = include USERS_FILE;
    return is_array($u) ? $u : [];
}

function admin_save_users(array $users): void
{
    if (!is_dir(CONFIG)) {
        mkdir(CONFIG, 0770, true);
    }
    $php = "<?php\n// Облікові записи адмінки. Не викладати в git і не надсилати.\nreturn " . var_export(array_values($users), true) . ";\n";
    file_put_contents(USERS_FILE, $php, LOCK_EX);
    if (function_exists('opcache_invalidate')) {
        @opcache_invalidate(USERS_FILE, true);
    }
}

function admin_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    // Сесії — у власній закритій теці (на спільному хостингу так їх не бачать інші сайти).
    $dir = CONFIG . '/sessions';
    if (!is_dir($dir)) {
        @mkdir($dir, 0770, true);
    }
    if (is_dir($dir) && is_writable($dir)) {
        session_save_path($dir);
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '50');
        ini_set('session.gc_maxlifetime', (string) SESSION_IDLE);
    }
    ini_set('session.use_strict_mode', '1');
    session_name('lyceum_admin');
    session_set_cookie_params(['lifetime' => 0, 'path' => (BASE ?: '') . '/', 'httponly' => true, 'samesite' => 'Strict', 'secure' => $https]);
    session_start();
    if (!empty($_SESSION['user']) && (time() - (int) ($_SESSION['seen'] ?? 0)) > SESSION_IDLE) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['seen'] = time();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
}

function current_user(): ?array
{
    $login = $_SESSION['user'] ?? null;
    if (!$login) {
        return null;
    }
    foreach (admin_users() as $u) {
        if ($u['login'] === $login) {
            return $u;
        }
    }
    return null;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e($_SESSION['csrf'] ?? '') . '">';
}

function csrf_check(): void
{
    $sent = (string) ($_POST['_csrf'] ?? '');
    if ($sent === '' || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(400);
        exit('Сторінка застаріла. Поверніться назад, оновіть сторінку й повторіть.');
    }
}

function client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

function login_attempts(): array
{
    $d = is_file(ATTEMPTS_FILE) ? json_decode((string) file_get_contents(ATTEMPTS_FILE), true) : [];
    return is_array($d) ? $d : [];
}

function login_locked(): int
{
    $a = login_attempts()[client_ip()] ?? null;
    if (!$a || $a['n'] < 5) {
        return 0;
    }
    $left = 900 - (time() - $a['t']);
    return max(0, $left);
}

function login_fail(): void
{
    $all = login_attempts();
    $ip = client_ip();
    $a = $all[$ip] ?? ['n' => 0, 't' => time()];
    if (time() - $a['t'] > 900) {
        $a = ['n' => 0, 't' => time()];
    }
    $a['n']++;
    $a['t'] = time();
    $all[$ip] = $a;
    // старі записи прибираємо
    foreach ($all as $k => $v) {
        if (time() - $v['t'] > 86400) {
            unset($all[$k]);
        }
    }
    if (!is_dir(CONFIG)) {
        mkdir(CONFIG, 0770, true);
    }
    file_put_contents(ATTEMPTS_FILE, json_encode($all), LOCK_EX);
}

function login_ok(): void
{
    $all = login_attempts();
    unset($all[client_ip()]);
    if (is_dir(CONFIG)) {
        file_put_contents(ATTEMPTS_FILE, json_encode($all), LOCK_EX);
    }
}

function attempt_login(string $login, string $password): bool
{
    foreach (admin_users() as $u) {
        if (hash_equals($u['login'], $login) && password_verify($password, $u['hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = $u['login'];
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            login_ok();
            return true;
        }
    }
    password_verify($password, '$2y$10$abcdefghijklmnopqrstuuJ8Z1z6x4u2bq7V3E7xg2iK9Yk1mHqW');
    login_fail();
    return false;
}

/** Перший вхід: поки немає облікових записів, потрібен одноразовий код із файлу config/setup-token.txt. */
function setup_token(): string
{
    if (!is_dir(CONFIG)) {
        mkdir(CONFIG, 0770, true);
    }
    if (!is_file(SETUP_TOKEN_FILE)) {
        file_put_contents(SETUP_TOKEN_FILE, bin2hex(random_bytes(12)) . "\n");
    }
    return trim((string) file_get_contents(SETUP_TOKEN_FILE));
}

function password_problem(string $p): string
{
    if (mb_strlen($p) < 10) {
        return 'Пароль має бути щонайменше з 10 символів.';
    }
    if (!preg_match('~\d~', $p) || !preg_match('~\p{L}~u', $p)) {
        return 'Пароль має містити і літери, і цифри.';
    }
    return '';
}

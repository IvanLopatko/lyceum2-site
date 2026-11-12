<?php
/**
 * Вхід в адмінку.
 *
 * Облікові записи — у config/users.php (поза git). Усі службові файли в config/ мають розширення .php
 * і нічого не показують за прямим посиланням, тож захист не залежить від .htaccess.
 * Паролі зберігаються лише як password_hash. Після 5 невдалих спроб з однієї адреси
 * вхід блокується на 15 хвилин.
 */

declare(strict_types=1);

if (!defined('ROOT')) {
    http_response_code(404);
    exit;
}

const USERS_FILE = CONFIG . '/users.php';
// Службові файли мають розширення .php і починаються з «exit», тому навіть якщо сервер
// не зважає на .htaccess (nginx), за прямим посиланням вони нічого не показують.
const ATTEMPTS_FILE = CONFIG . '/login-attempts.php';
const SETUP_TOKEN_FILE = CONFIG . '/setup-token.php';
const LOGIN_LOG_FILE = CONFIG . '/login-log.php';
const GUARD = "<?php http_response_code(404); exit; ?>\n";
const SESSION_IDLE = 4 * 3600;

/** Читає службовий файл із «замком» у першому рядку. */
function guard_read(string $file): string
{
    if (!is_file($file)) {
        return '';
    }
    $raw = (string) file_get_contents($file);
    return str_starts_with($raw, '<?php') ? (string) substr($raw, (int) strpos($raw, "\n") + 1) : $raw;
}

function guard_write(string $file, string $data): void
{
    if (!is_dir(dirname($file))) {
        mkdir(dirname($file), 0770, true);
    }
    file_put_contents($file, GUARD . $data, LOCK_EX);
}

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
    // спершу в тимчасовий файл, потім перейменування: сайт ніколи не побачить наполовину записаний список
    $tmp = USERS_FILE . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (file_put_contents($tmp, $php, LOCK_EX) === false || !@rename($tmp, USERS_FILE)) {
        @unlink($tmp);
        file_put_contents(USERS_FILE, $php, LOCK_EX);
    }
    if (function_exists('opcache_invalidate')) {
        @opcache_invalidate(USERS_FILE, true);
    }
}

function admin_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = is_https();
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
            // після зміни пароля всі інші сеанси цього користувача перестають діяти
            return (int) ($_SESSION['pv'] ?? 0) === (int) ($u['pv'] ?? 0) ? $u : null;
        }
    }
    return null;
}

/** Чи відкрито сеанс адмінки (для попереднього перегляду чернеток на сайті). Сеанс запускається, лише якщо є cookie. */
function admin_logged_in(): bool
{
    if (empty($_COOKIE['lyceum_admin'])) {
        return false;
    }
    admin_session_start();
    return current_user() !== null;
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
    if (is_file(CONFIG . '/login-attempts.json')) {
        @unlink(CONFIG . '/login-attempts.json'); // старий файл без «замка»
    }
    $d = json_decode(guard_read(ATTEMPTS_FILE), true);
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
    guard_write(ATTEMPTS_FILE, (string) json_encode($all));
}

function login_ok(): void
{
    $all = login_attempts();
    unset($all[client_ip()]);
    if (is_dir(CONFIG)) {
        guard_write(ATTEMPTS_FILE, (string) json_encode($all));
    }
}

function attempt_login(string $login, string $password): bool
{
    $login = strtolower($login);
    $users = admin_users();
    $found = null;
    foreach ($users as $u) {
        if (hash_equals((string) $u['login'], $login)) {
            $found = $u;
        }
    }
    // Пароль перевіряємо завжди — і для логіна, якого немає (тоді з хешем першого запису).
    // Так за часом відповіді не можна дізнатися, чи існує такий логін.
    $hash = (string) ($found['hash'] ?? ($users[0]['hash'] ?? ''));
    $ok = password_verify($password, $hash) && $found !== null;
    login_log($found['login'] ?? '', $ok);
    if (!$ok) {
        login_fail();
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['user'] = $found['login'];
    $_SESSION['pv'] = (int) ($found['pv'] ?? 0);
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    login_ok();
    // сучасніший алгоритм зберігання пароля, якщо PHP на сервері оновився
    if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        foreach ($users as &$u) {
            if ($u['login'] === $found['login']) {
                $u['hash'] = password_hash($password, PASSWORD_DEFAULT);
            }
        }
        unset($u);
        admin_save_users($users);
    }
    return true;
}

/**
 * Журнал входів: останні 60 вдалих і 60 невдалих спроб (час, логін, адреса).
 * Для невдалої спроби логін записуємо, лише якщо такий обліковий запис є:
 * буває, що людина помилково вводить у це поле пароль.
 */
function login_log(string $login, bool $ok): void
{
    if (!is_dir(CONFIG)) {
        return;
    }
    $log = login_history();
    $log[] = ['t' => time(), 'login' => $login, 'ip' => client_ip(), 'ok' => $ok];
    $keep = [];
    $n = [0, 0];
    foreach (array_reverse($log) as $row) {
        if ($n[(int) !empty($row['ok'])]++ < 60) {
            $keep[] = $row;
        }
    }
    guard_write(LOGIN_LOG_FILE, (string) json_encode(array_reverse($keep)));
}

/** Журнал входів, старіші першими. */
function login_history(): array
{
    $d = json_decode(guard_read(LOGIN_LOG_FILE), true);
    return is_array($d) ? array_values(array_filter($d, 'is_array')) : [];
}

/** Перший вхід: поки немає облікових записів, потрібен одноразовий код із файлу config/setup-token.php. */
function setup_token(): string
{
    // старі файли без «замка» прибираємо
    foreach ([CONFIG . '/setup-token.txt', CONFIG . '/login-attempts.json'] as $legacy) {
        if (is_file($legacy)) {
            @unlink($legacy);
        }
    }
    $t = trim(guard_read(SETUP_TOKEN_FILE));
    if (!preg_match('~^[0-9a-f]{24}$~', $t)) {
        $t = bin2hex(random_bytes(12));
        guard_write(SETUP_TOKEN_FILE, $t . "\n");
    }
    return $t;
}

function password_problem(string $p, string $login = ''): string
{
    if (mb_strlen($p) < 10) {
        return 'Пароль має бути щонайменше з 10 символів.';
    }
    if (!preg_match('~\d~', $p) || !preg_match('~\p{L}~u', $p)) {
        return 'Пароль має містити і літери, і цифри.';
    }
    // «Слово й цифри» (Liceum2026, Parol12345) вгадують першими — такі не приймаємо.
    $low = mb_strtolower($p);
    $letters = preg_replace('~[^\p{L}]+~u', '', $low) ?? '';
    $easy = ['password', 'parol', 'пароль', 'qwerty', 'qwertyuiop', 'йцукен', 'admin', 'administrator', 'адмін', 'lyceum', 'liceum', 'licey', 'litsei',
        'ліцей', 'лицей', 'школа', 'shkola', 'school', 'біляївка', 'беляевка', 'biliaivka', 'bilyaivka', 'ukraine', 'ukraina', 'україна', 'iloveyou', 'abc', 'abcd', 'qwe', 'qaz', 'qazwsx'];
    $loginLetters = preg_replace('~[^\p{L}]+~u', '', mb_strtolower($login)) ?? '';
    if (in_array($letters, $easy, true) || ($loginLetters !== '' && $letters === $loginLetters) || ($login !== '' && mb_strlen($login) >= 4 && str_contains($low, mb_strtolower($login)))) {
        return 'Пароль надто простий: відоме слово або логін із цифрами легко вгадати. Найкраще — три-чотири не пов’язані між собою слова й число.';
    }
    if (count(array_unique(mb_str_split($low))) < 6) {
        return 'Пароль надто простий: у ньому мало різних символів.';
    }
    return '';
}

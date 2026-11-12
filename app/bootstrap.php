<?php
/**
 * Сайт Біляївського ліцею №2 — запуск.
 *
 * Без фреймворків і бази даних: вміст лежить у JSON-файлах у content/,
 * файли — в uploads/, налаштування доступу — в config/ (не в git).
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');
define('CONTENT', ROOT . '/content');
define('UPLOADS', ROOT . '/uploads');
define('CONFIG', ROOT . '/config');

// Сайт може жити в підкаталозі (наприклад, на GitHub Pages чи тестовому хостингу).
$script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
define('BASE', rtrim(dirname($script), '/'));

// Попередження PHP відвідувачам не показуємо: вони пишуться в журнал сервера.
// Щоб побачити їх під час налагодження, створіть порожній файл config/debug.
if (!is_file(CONFIG . '/debug')) {
    ini_set('display_errors', '0');
}
ini_set('log_errors', '1');
// Версію PHP стороннім не показуємо.
header_remove('X-Powered-By');

date_default_timezone_set('Europe/Kyiv');
mb_internal_encoding('UTF-8');

require APP . '/lib/helpers.php';
require APP . '/lib/store.php';
require APP . '/lib/data.php';
require APP . '/lib/status.php';
require APP . '/lib/render.php';
require APP . '/lib/components.php';

// Якщо сайт відкрито через https, браузер надалі сам відмовлятиметься від незашифрованого з’єднання.
if (is_https() && !is_local_host()) {
    header('Strict-Transport-Security: max-age=15552000');
}

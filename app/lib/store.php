<?php
/**
 * Сховище: JSON-файли в content/.
 * Запис атомарний (тимчасовий файл + перейменування) з блокуванням,
 * попередня версія кожного файлу кладеться в закриту теку content/.history/ (останні 30).
 */

declare(strict_types=1);

if (!defined('ROOT')) {
    http_response_code(404);
    exit;
}

function store_path(string $rel): string
{
    $rel = str_replace(['..', '\\'], '', $rel);
    return CONTENT . '/' . ltrim($rel, '/');
}

function store_read(string $rel, mixed $default = []): mixed
{
    static $cache = [];
    $file = store_path($rel);
    $mtime = is_file($file) ? filemtime($file) : 0;
    $key = $file . ':' . $mtime;
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    if (!$mtime) {
        return $default;
    }
    $data = json_decode((string) file_get_contents($file), true);
    return $cache[$key] = ($data ?? $default);
}

function store_write(string $rel, mixed $data): void
{
    $file = store_path($rel);
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    if (is_file($file)) {
        store_backup($rel, $file);
    }
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
    $tmp = $file . '.tmp' . bin2hex(random_bytes(3));
    // одне спільне блокування на всі записи (окремі .lock біля кожного файлу лише засмічували теку)
    $fp = @fopen(CONTENT . '/.write.lock', 'c');
    if ($fp) {
        flock($fp, LOCK_EX);
    }
    file_put_contents($tmp, $json);
    rename($tmp, $file);
    if ($fp) {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
    clearstatcache(true, $file);
}

function store_delete(string $rel): void
{
    $file = store_path($rel);
    if (is_file($file)) {
        store_backup($rel, $file);
        unlink($file);
    }
}

/**
 * Закрита тека для історії правок («history») і кошика («trash»).
 *
 * Лежить у content/.history/ чи content/.trash/ у підтеці з випадковою назвою («k-…»).
 * На Apache ці теки закриті правилами .htaccess; але на частині хостингів файли віддає
 * nginx, який цих правил не читає. Випадкову назву вгадати неможливо, тому старі версії
 * й видалені файли лишаються недоступними за будь-яких налаштувань сервера.
 */
function store_private(string $kind): string
{
    static $cache = [];
    if (isset($cache[$kind])) {
        return $cache[$kind];
    }
    $base = CONTENT . '/.' . $kind;
    $found = glob($base . '/k-*', GLOB_ONLYDIR) ?: [];
    if ($found) {
        return $cache[$kind] = $found[0];
    }
    $dir = $base . '/k-' . bin2hex(random_bytes(12));
    if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
        return $cache[$kind] = $base; // запис у теку неможливий — нехай далі спрацює звичайна помилка запису
    }
    // те, що лежало тут до появи закритої підтеки, переносимо в неї
    foreach (glob($base . '/*') ?: [] as $old) {
        if ($old !== $dir && !str_starts_with(basename($old), 'k-')) {
            @rename($old, $dir . '/' . basename($old));
        }
    }
    return $cache[$kind] = $dir;
}

/** Копія попередньої версії файлу — щоб будь-яку правку можна було відкотити. */
function store_backup(string $rel, string $file): void
{
    $hist = store_history_dir($rel);
    if (!is_dir($hist)) {
        mkdir($hist, 0775, true);
    }
    // дві правки за одну секунду не повинні затирати одна одну: друга дістає суфікс «-2»
    $v = date('Ymd-His');
    for ($i = 2; is_file($hist . '/' . $v . '.json') && $i < 50; $i++) {
        $v = date('Ymd-His') . '-' . $i;
    }
    copy($file, $hist . '/' . $v . '.json');
    $all = glob($hist . '/*.json') ?: [];
    sort($all);
    foreach (array_slice($all, 0, max(0, count($all) - 30)) as $old) {
        unlink($old);
    }
}

/** Тека з попередніми версіями файлу. */
function store_history_dir(string $rel): string
{
    return store_private('history') . '/' . str_replace('/', '__', $rel);
}

/** Попередні версії файлу: [['v' => '20261111-203000', 'time' => unix, 'size' => байт], …], новіші першими. */
function store_versions(string $rel): array
{
    $out = [];
    foreach (glob(store_history_dir($rel) . '/*.json') ?: [] as $f) {
        $v = basename($f, '.json');
        if (preg_match('~^(\d{4})(\d\d)(\d\d)-(\d\d)(\d\d)(\d\d)(-\d+)?$~', $v, $m)) {
            $out[] = ['v' => $v, 'time' => mktime((int) $m[4], (int) $m[5], (int) $m[6], (int) $m[2], (int) $m[3], (int) $m[1]), 'size' => (int) filesize($f)];
        }
    }
    usort($out, static fn(array $a, array $b): int => strnatcmp($b['v'], $a['v']));
    return $out;
}

/** Вміст однієї попередньої версії або null. */
function store_version(string $rel, string $v): mixed
{
    if (!preg_match('~^\d{8}-\d{6}(-\d+)?$~', $v)) {
        return null;
    }
    $f = store_history_dir($rel) . '/' . $v . '.json';
    return is_file($f) ? json_decode((string) file_get_contents($f), true) : null;
}

/** Файли, у яких є історія: ['pages/kontakty.json' => кількість версій, …]. */
function store_history_index(): array
{
    $out = [];
    foreach (glob(store_private('history') . '/*', GLOB_ONLYDIR) ?: [] as $d) {
        $rel = str_replace('__', '/', basename($d));
        $n = count(glob($d . '/*.json') ?: []);
        if ($n) {
            $out[$rel] = $n;
        }
    }
    ksort($out);
    return $out;
}

/** Усі JSON-файли з теки (для сторінок і новин — по файлу на запис). */
function store_list(string $dir): array
{
    static $cache = [];
    $path = store_path($dir);
    $files = glob($path . '/*.json') ?: [];
    $sig = $dir . ':' . count($files) . ':' . (int) @filemtime($path);
    foreach ($files as $f) {
        $sig .= ':' . filemtime($f);
    }
    $sig = md5($sig);
    if (isset($cache[$sig])) {
        return $cache[$sig];
    }
    $out = [];
    foreach ($files as $f) {
        $d = json_decode((string) file_get_contents($f), true);
        if (is_array($d)) {
            $out[basename($f, '.json')] = $d;
        }
    }
    return $cache[$sig] = $out;
}

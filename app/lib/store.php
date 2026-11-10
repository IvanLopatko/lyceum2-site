<?php
/**
 * Сховище: JSON-файли в content/.
 * Запис атомарний (тимчасовий файл + перейменування) з блокуванням,
 * попередня версія кожного файлу кладеться в content/.history/ (останні 30).
 */

declare(strict_types=1);

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
    $fp = fopen($file . '.lock', 'c');
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

/** Копія попередньої версії файлу — щоб будь-яку правку можна було відкотити. */
function store_backup(string $rel, string $file): void
{
    $hist = CONTENT . '/.history/' . str_replace('/', '__', $rel);
    if (!is_dir($hist)) {
        mkdir($hist, 0775, true);
    }
    copy($file, $hist . '/' . date('Ymd-His') . '.json');
    $all = glob($hist . '/*.json') ?: [];
    sort($all);
    foreach (array_slice($all, 0, max(0, count($all) - 30)) as $old) {
        unlink($old);
    }
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

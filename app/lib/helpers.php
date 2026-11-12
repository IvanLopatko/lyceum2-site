<?php
/** Дрібні помічники: екранування, адреси, дати, відмінки. */

declare(strict_types=1);

if (!defined('ROOT')) {
    http_response_code(404);
    exit;
}

/** Екранування тексту для HTML. */
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Адреса всередині сайту з урахуванням підкаталогу. */
function url(string $path = '/'): string
{
    if (preg_match('~^(https?:|mailto:|tel:|#)~', $path)) {
        return $path;
    }
    return BASE . '/' . ltrim($path, '/');
}

/** Адреса файлу з версією, щоб браузер не тримав старий стиль. */
function asset(string $path): string
{
    $file = ROOT . '/' . ltrim($path, '/');
    $v = is_file($file) ? (string) filemtime($file) : '1';
    return url($path) . '?v=' . $v;
}

/** Адреса завантаженого файлу (у даних шлях зберігається як «uploads/...»). */
function upload_url(string $path): string
{
    return $path === '' ? '' : url($path);
}

/** Де лежить зменшена копія фото: «uploads/fb/a.jpg» → «uploads/thumbs/fb/a.jpg». */
function thumb_path(string $path): string
{
    return str_starts_with($path, 'uploads/') ? 'uploads/thumbs/' . substr($path, 8) : '';
}

/** Адреса зменшеної копії для списків; якщо копії немає — адреса самого фото. */
function thumb_url(string $path): string
{
    $t = thumb_path($path);
    return ($t !== '' && is_file(ROOT . '/' . $t)) ? url($t) : upload_url($path);
}

/** Повна адреса з доменом — для карти сайту, robots.txt і картинок у соцмережах. */
function abs_url(string $path = '/'): string
{
    $u = url($path);
    if (preg_match('~^https?:~', $u)) {
        return $u;
    }
    $site = rtrim((string) setting('site.url', ''), '/');
    if (preg_match('~^https?://[^/]+~', $site, $m)) {
        return $m[0] . $u;
    }
    $https = is_https();
    $host = preg_replace('~[^A-Za-z0-9.:\-\[\]]~', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    return ($https ? 'https' : 'http') . '://' . $host . $u;
}

/**
 * Безпечна адреса для посилань з адмінки: лише звичайні адреси
 * (http, https, пошта, телефон, внутрішні). Усе інше стає «#».
 */
function safe_url(string $u): string
{
    $u = trim(preg_replace('~[\x00-\x1F\x7F]+~', '', $u) ?? '');
    if ($u === '') {
        return '';
    }
    if (preg_match('~^(https?://|mailto:|tel:|/|#|\?)~i', $u)) {
        return $u;
    }
    // відносна адреса без схеми («storinka/», «file.pdf») — можна
    return preg_match('~^[a-z][a-z0-9+.\-]*\s*:~i', $u) ? '#' : $u;
}

/** Множина в українській: 1 учень, 2 учні, 5 учнів. */
function plural(int $n, string $one, string $few, string $many): string
{
    $n = abs($n);
    $m10 = $n % 10;
    $m100 = $n % 100;
    if ($m10 === 1 && $m100 !== 11) {
        return $one;
    }
    if ($m10 >= 2 && $m10 <= 4 && ($m100 < 12 || $m100 > 14)) {
        return $few;
    }
    return $many;
}

const UA_MONTHS_GEN = ['', 'січня', 'лютого', 'березня', 'квітня', 'травня', 'червня', 'липня', 'серпня', 'вересня', 'жовтня', 'листопада', 'грудня'];
const UA_MONTHS = ['', 'Січень', 'Лютий', 'Березень', 'Квітень', 'Травень', 'Червень', 'Липень', 'Серпень', 'Вересень', 'Жовтень', 'Листопад', 'Грудень'];

/** «13 жовтня 2026» */
function ua_date(string $date, bool $year = true): string
{
    $ts = strtotime($date);
    if (!$ts) {
        return $date;
    }
    $s = (int) date('j', $ts) . ' ' . UA_MONTHS_GEN[(int) date('n', $ts)];
    return $year ? $s . ' ' . date('Y', $ts) : $s;
}

/** «13.10.2026» */
function dmy(string $date): string
{
    $ts = strtotime($date);
    return $ts ? date('d.m.Y', $ts) : $date;
}

function today(): string
{
    return date('Y-m-d');
}

/** Латинський «слаг» з українського тексту (для файлів і адрес). */
function slugify(string $s): string
{
    $map = ['а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'h', 'ґ' => 'g', 'д' => 'd', 'е' => 'e', 'є' => 'ie', 'ж' => 'zh', 'з' => 'z', 'и' => 'y', 'і' => 'i', 'ї' => 'i', 'й' => 'i', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ь' => '', 'ю' => 'iu', 'я' => 'ia', "'" => '', 'ʼ' => '', '’' => ''];
    $s = mb_strtolower($s);
    $s = strtr($s, $map);
    $s = preg_replace('~[^a-z0-9]+~', '-', $s) ?? '';
    return trim(substr($s, 0, 60), '-') ?: 'item';
}

/** Обрізати текст до N символів по слову. */
function excerpt(string $html, int $len = 170): string
{
    $t = trim(preg_replace('~\s+~u', ' ', strip_tags($html)) ?? '');
    if (mb_strlen($t) <= $len) {
        return $t;
    }
    $cut = mb_substr($t, 0, $len);
    $cut = preg_replace('~\s+\S*$~u', '', $cut) ?? $cut;
    return rtrim($cut, ',;:—– ') . '…';
}

/** Поточна адреса без підкаталогу й запиту: «/novyny/». */
function current_path(): string
{
    $p = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $p = rawurldecode($p);
    if (BASE !== '' && str_starts_with($p, BASE)) {
        $p = substr($p, strlen(BASE)) ?: '/';
    }
    return $p;
}

/**
 * Перенаправлення — лише всередині сайту. Зворотну риску браузери читають як звичайну,
 * тож без очищення адреса «/\чужий-сайт» вела б на чужий сайт.
 */
function redirect(string $to, int $code = 302): never
{
    $to = str_replace('\\', '/', preg_replace('~[\x00-\x1F\x7F]+~', '', $to) ?? '');
    header('Location: ' . BASE . '/' . ltrim($to, '/'), true, $code);
    exit;
}

/** Чи з’єднання зашифроване (напряму або через проксі хостингу). */
function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

/** Чи сайт відкрито на власному комп’ютері (локальний запуск), а не в інтернеті. */
function is_local_host(): bool
{
    $h = strtolower((string) preg_replace('~:\d+$~', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
    return in_array($h, ['localhost', '127.0.0.1', '[::1]'], true) || str_ends_with($h, '.localhost') || str_ends_with($h, '.test');
}

/**
 * Маленький скрипт у <head>, що вмикає збережені налаштування доступності до першого малювання.
 * Його відбиток вписано в правила безпеки сторінки (див. render_page), тому інші вбудовані скрипти не виконуються.
 */
const A11Y_BOOT = "try{var a=JSON.parse(localStorage.getItem('a11y')||'{}');var r=document.documentElement;if(a.size)r.dataset.size=a.size;if(a.space)r.dataset.space='1';if(a.contrast)r.dataset.contrast='1';}catch(e){}";

/** Дозволені в тексті теги: жирний, курсив, посилання, перенос. Решту прибираємо. */
function clean_inline(string $html): string
{
    $html = strip_tags($html, '<strong><b><em><i><a><br><small><code><mark>');
    // атрибути — лише href у посиланнях
    $html = preg_replace_callback('~<(a)\b([^>]*)>~i', static function (array $m): string {
        if (preg_match('~href\s*=\s*("|\')(.*?)\1~is', $m[2], $h)) {
            $href = safe_url(html_entity_decode($h[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $ext = preg_match('~^https?://~i', $href) ? ' target="_blank" rel="noopener"' : '';
            return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"' . $ext . '>';
        }
        return '<a>';
    }, $html) ?? '';
    $html = preg_replace('~<(strong|b|em|i|br|small|code|mark)\b[^>]*>~i', '<$1>', $html) ?? '';
    return trim($html);
}

<?php
/** Дрібні помічники: екранування, адреси, дати, відмінки. */

declare(strict_types=1);

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

function redirect(string $to, int $code = 302): never
{
    header('Location: ' . url($to), true, $code);
    exit;
}

/** Дозволені в тексті теги: жирний, курсив, посилання, перенос. Решту прибираємо. */
function clean_inline(string $html): string
{
    $html = strip_tags($html, '<strong><b><em><i><a><br><small><code><mark>');
    // атрибути — лише href у посиланнях
    $html = preg_replace_callback('~<(a)\b([^>]*)>~i', static function (array $m): string {
        if (preg_match('~href\s*=\s*("|\')(.*?)\1~i', $m[2], $h)) {
            $href = $h[2];
            if (preg_match('~^\s*javascript:~i', $href)) {
                $href = '#';
            }
            $ext = preg_match('~^https?://~', $href) ? ' target="_blank" rel="noopener"' : '';
            return '<a href="' . htmlspecialchars($href, ENT_QUOTES) . '"' . $ext . '>';
        }
        return '<a>';
    }, $html) ?? '';
    $html = preg_replace('~<(strong|b|em|i|br|small|code|mark)\b[^>]*>~i', '<$1>', $html) ?? '';
    return trim($html);
}

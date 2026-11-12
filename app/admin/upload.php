<?php
/**
 * Завантаження файлів з адмінки.
 * Фото перекодовуються через GD (зменшуються, втрачають приховані дані й будь-який
 * вбудований код); PDF перевіряються за вмістом. Імена файлів — лише латиниця.
 */

declare(strict_types=1);

if (!defined('ROOT')) {
    http_response_code(404);
    exit;
}

const MAX_IMAGE_BYTES = 15 * 1024 * 1024;
const MAX_PDF_BYTES = 30 * 1024 * 1024;
const MAX_IMAGE_PIXELS = 40 * 1000 * 1000;
const THUMB_SIZE = 720;
const TRASH_DAYS = 30;

/** Список завантажених файлів з поля (одного чи кількох). */
function uploaded_files(string $field): array
{
    $f = $_FILES[$field] ?? null;
    if (!$f) {
        return [];
    }
    if (!is_array($f['name'])) {
        return ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE ? [] : [$f];
    }
    $out = [];
    foreach ($f['name'] as $i => $name) {
        if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $out[] = ['name' => $name, 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    }
    return $out;
}

/** Зберігає фото: повертає шлях «uploads/...» або кидає виняток з поясненням. */
function save_image(array $file, string $dir, int $max = 1600, string $hint = 'foto'): string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Файл не завантажився (код ' . $file['error'] . '). Спробуйте ще раз або зменште розмір.');
    }
    if ($file['size'] > MAX_IMAGE_BYTES) {
        throw new RuntimeException('Фото завелике: до 15 МБ.');
    }
    $info = @getimagesize($file['tmp_name']);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)) {
        throw new RuntimeException('Підтримуються фото у форматах JPG, PNG, WebP.');
    }
    if ($info[0] * $info[1] > MAX_IMAGE_PIXELS) {
        throw new RuntimeException('Фото завелике: понад 40 мегапікселів. Зменште його перед завантаженням.');
    }
    // поворот за EXIF (фото з телефона)
    $o = 1;
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file['tmp_name']);
        $o = (int) ($exif['Orientation'] ?? 1);
    }
    // Розпаковане фото займає в пам'яті близько 4 байтів на піксель, а з поворотом — удвічі більше.
    // Якщо серверу цього не вистачить, краще пояснити, ніж показати порожню сторінку.
    $need = (int) ($info[0] * $info[1] * 4.6 * (in_array($o, [3, 6, 8], true) ? 2 : 1)) + (int) $file['size'] + memory_get_usage();
    if ($need > upload_limits()['memory']) {
        @ini_set('memory_limit', '512M');
    }
    if ($need > upload_limits()['memory']) {
        throw new RuntimeException('Фото завелике для цього сервера (' . round($info[0] * $info[1] / 1000000) . ' мегапікселів). Зменште його перед завантаженням.');
    }
    $src = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
    if (!$src) {
        throw new RuntimeException('Не вдалося прочитати фото.');
    }
    if ($o !== 1) {
        $rot = match ($o) { 3 => imagerotate($src, 180, 0), 6 => imagerotate($src, -90, 0), 8 => imagerotate($src, 90, 0), default => $src };
        $src = $rot ?: $src;
    }
    $w = imagesx($src);
    $h = imagesy($src);
    $k = min(1, $max / max($w, $h));
    $nw = max(1, (int) round($w * $k));
    $nh = max(1, (int) round($h * $k));
    $dst = imagecreatetruecolor($nw, $nh);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $rel = 'uploads/' . trim($dir, '/') . '/' . date('Y') . '/' . slugify($hint) . '-' . bin2hex(random_bytes(3)) . '.jpg';
    $abs = ROOT . '/' . $rel;
    if (!is_dir(dirname($abs))) {
        mkdir(dirname($abs), 0775, true);
    }
    imagejpeg($dst, $abs, 82);
    make_thumb($rel, $dir === 'staff' ? 400 : THUMB_SIZE, $dst);
    return $rel;
}

/** Зменшена копія фото для списків: uploads/thumbs/… (див. thumb_path). */
function make_thumb(string $rel, int $max = THUMB_SIZE, mixed $src = null): void
{
    $t = thumb_path($rel);
    if ($t === '') {
        return;
    }
    $src = $src ?: @imagecreatefromstring((string) @file_get_contents(ROOT . '/' . $rel));
    if (!$src) {
        return;
    }
    $w = imagesx($src);
    $h = imagesy($src);
    $k = min(1, $max / max($w, $h));
    $dst = imagecreatetruecolor(max(1, (int) round($w * $k)), max(1, (int) round($h * $k)));
    imagecopyresampled($dst, $src, 0, 0, 0, 0, imagesx($dst), imagesy($dst), $w, $h);
    $abs = ROOT . '/' . $t;
    if (!is_dir(dirname($abs))) {
        @mkdir(dirname($abs), 0775, true);
    }
    // зменшена копія необов'язкова: якщо записати не вдалося, сайт покаже саме фото
    if (!@imagejpeg($dst, $abs, 78)) {
        // друга спроба — через тимчасовий файл (на деяких серверах запис поверх щойно видаленого файлу не вдається)
        $tmp = $abs . '.' . bin2hex(random_bytes(3)) . '.tmp';
        if (@imagejpeg($dst, $tmp, 78) && !@rename($tmp, $abs)) {
            @unlink($tmp);
        }
    }
}

function save_pdf(array $file, string $hint): string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Файл не завантажився (код ' . $file['error'] . ').');
    }
    if ($file['size'] > MAX_PDF_BYTES) {
        throw new RuntimeException('PDF завеликий: до 30 МБ. Стисніть скан або розділіть документ.');
    }
    $head = (string) file_get_contents($file['tmp_name'], false, null, 0, 5);
    $mime = function_exists('finfo_open') ? (string) finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name']) : 'application/pdf';
    if ($head !== '%PDF-' || $mime !== 'application/pdf') {
        throw new RuntimeException('Потрібен файл PDF.');
    }
    $rel = 'uploads/docs/' . slugify($hint) . '-' . bin2hex(random_bytes(3)) . '.pdf';
    $abs = ROOT . '/' . $rel;
    if (!is_dir(dirname($abs))) {
        mkdir(dirname($abs), 0775, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $abs) && !rename($file['tmp_name'], $abs)) {
        throw new RuntimeException('Не вдалося зберегти файл.');
    }
    return $rel;
}

/** Чи посилається ще щось на цей файл (документ, працівник, новина, сторінка). */
function upload_referenced(string $rel): bool
{
    foreach (['documents.json', 'staff.json', 'clubs.json', 'settings.json'] as $f) {
        $file = store_path($f);
        if (is_file($file) && str_contains((string) file_get_contents($file), $rel)) {
            return true;
        }
    }
    foreach (['pages', 'news'] as $dir) {
        foreach (glob(store_path($dir) . '/*.json') ?: [] as $file) {
            if (str_contains((string) file_get_contents($file), $rel)) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Прибирає файл із сайту: переносить у закритий кошик (content/.trash/, підтека з випадковою назвою) на 30 днів.
 * За прямим посиланням він більше не відкривається. Файл, на який ще щось посилається, не чіпаємо.
 * Викликати ПІСЛЯ збереження запису, з якого файл прибрано.
 */
function trash_upload(string $rel): void
{
    if (!str_starts_with($rel, 'uploads/') || str_contains($rel, '..') || upload_referenced($rel)) {
        return;
    }
    $dir = store_private('trash');
    $abs = ROOT . '/' . $rel;
    if (is_file($abs) && !@rename($abs, $dir . '/' . date('Ymd-His') . '__' . str_replace('/', '__', substr($rel, 8)))) {
        @unlink($abs);
    }
    $thumb = thumb_path($rel);
    if ($thumb !== '' && is_file(ROOT . '/' . $thumb)) {
        @unlink(ROOT . '/' . $thumb);
    }
    foreach (glob($dir . '/*') ?: [] as $old) {
        if (filemtime($old) < time() - TRASH_DAYS * 86400) {
            @unlink($old);
        }
    }
}

/** Повертає файл із кошика (під час відновлення попередньої версії запису). */
function restore_upload(string $rel): bool
{
    if (!str_starts_with($rel, 'uploads/') || str_contains($rel, '..')) {
        return false;
    }
    $abs = ROOT . '/' . $rel;
    if (is_file($abs)) {
        return true;
    }
    $found = glob(store_private('trash') . '/*__' . addcslashes(str_replace('/', '__', substr($rel, 8)), '*?[]{}\\')) ?: [];
    if (!$found) {
        return false;
    }
    rsort($found);
    if (!is_dir(dirname($abs))) {
        mkdir(dirname($abs), 0775, true);
    }
    if (!rename($found[0], $abs)) {
        return false;
    }
    if (preg_match('~\.(jpe?g|png|webp)$~i', $rel)) {
        make_thumb($rel, str_contains($rel, '/staff/') ? 400 : THUMB_SIZE);
    }
    return true;
}

/** Усі шляхи «uploads/…», згадані в даних (щоб повернути файли з кошика). */
function uploads_in(mixed $data): array
{
    $out = [];
    array_walk_recursive($data, static function ($v) use (&$out): void {
        if (is_string($v) && str_starts_with($v, 'uploads/')) {
            $out[] = $v;
        }
    });
    return array_values(array_unique($out));
}

/** Найбільший розмір одного запиту й кількість файлів, які приймає сервер. */
function upload_limits(): array
{
    $bytes = static function (string $v): int {
        $n = (int) $v;
        return match (strtolower(substr(trim($v), -1))) { 'g' => $n * 1073741824, 'm' => $n * 1048576, 'k' => $n * 1024, default => $n };
    };
    $post = $bytes((string) ini_get('post_max_size'));
    $file = $bytes((string) ini_get('upload_max_filesize'));
    $mem = $bytes((string) ini_get('memory_limit'));
    return ['files' => max(1, (int) ini_get('max_file_uploads') ?: 20), 'post' => $post > 0 ? $post : PHP_INT_MAX, 'file' => $file > 0 ? $file : PHP_INT_MAX, 'memory' => $mem > 0 ? $mem : PHP_INT_MAX];
}

<?php
/**
 * Завантаження файлів з адмінки.
 * Фото перекодовуються через GD (зменшуються, втрачають приховані дані й будь-який
 * вбудований код); PDF перевіряються за вмістом. Імена файлів — лише латиниця.
 */

declare(strict_types=1);

const MAX_IMAGE_BYTES = 15 * 1024 * 1024;
const MAX_PDF_BYTES = 30 * 1024 * 1024;

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
    $src = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
    if (!$src) {
        throw new RuntimeException('Не вдалося прочитати фото.');
    }
    // поворот за EXIF (фото з телефона)
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file['tmp_name']);
        $o = (int) ($exif['Orientation'] ?? 1);
        $src = match ($o) { 3 => imagerotate($src, 180, 0), 6 => imagerotate($src, -90, 0), 8 => imagerotate($src, 90, 0), default => $src };
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
    return $rel;
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

/** Видаляє файл з uploads, якщо на нього більше ніщо не посилається (перевіряє викликач). */
function delete_upload(string $rel): void
{
    if (!str_starts_with($rel, 'uploads/') || str_contains($rel, '..')) {
        return;
    }
    $abs = ROOT . '/' . $rel;
    if (is_file($abs)) {
        unlink($abs);
    }
}

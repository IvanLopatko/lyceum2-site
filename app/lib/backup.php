<?php
/**
 * Журнал дій і резервні копії вмісту.
 *
 * Журнал: хто, коли і що змінив в адмінці — файл на місяць у закритій теці content/.log/.
 * Копії: раз на день (з першим відвідуванням сайту за добу) увесь вміст із content/
 * складається в один файл у закритій теці content/.backups/. Зберігаються копії за
 * останні 30 днів і перша копія кожного місяця за рік. До будь-якої копії можна відкотитися;
 * перед відкатом поточний стан теж зберігається, тож і сам відкат можна скасувати.
 *
 * Фото й PDF у копію не входять: файли на сайті ніколи не перезаписуються, а видалені
 * 30 днів лежать у кошику — під час відкату вони повертаються звідти.
 */

declare(strict_types=1);

if (!defined('ROOT')) {
    http_response_code(404);
    exit;
}

const BACKUP_KEEP_DAILY = 30;
const BACKUP_KEEP_MONTHLY = 12;
const BACKUP_KEEP_EXTRA = 10;
const JOURNAL_KEEP_MONTHS = 24;

/* ───────── Журнал дій ───────── */

/**
 * Запис у журнал. $action — «створено», «змінено», «видалено», «відновлено» тощо;
 * $kind — що саме («сторінка», «новина»…); $ref — адреса в адмінці, щоб перейти до запису.
 */
function journal_add(string $action, string $kind, string $title = '', string $ref = ''): void
{
    $u = $GLOBALS['user'] ?? null;
    $row = [
        't' => time(),
        'login' => (string) ($u['login'] ?? ''),
        'name' => (string) ($u['name'] ?? ''),
        'action' => $action,
        'kind' => $kind,
        'title' => mb_substr(trim(strip_tags($title)), 0, 160),
        'ref' => $ref,
    ];
    $dir = store_private('log');
    @file_put_contents($dir . '/' . date('Y-m') . '.jsonl', json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);
}

/** Місяці, за які є записи: ['2026-11', '2026-10', …]. Старіші за два роки прибираються. */
function journal_months(): array
{
    $out = [];
    $limit = date('Y-m', strtotime('-' . JOURNAL_KEEP_MONTHS . ' months'));
    foreach (glob(store_private('log') . '/*.jsonl') ?: [] as $f) {
        $m = basename($f, '.jsonl');
        if (!preg_match('~^\d{4}-\d\d$~', $m)) {
            continue;
        }
        if ($m < $limit) {
            @unlink($f);
            continue;
        }
        $out[] = $m;
    }
    rsort($out);
    return $out;
}

/** Записи за місяць, новіші першими. */
function journal_read(string $month): array
{
    if (!preg_match('~^\d{4}-\d\d$~', $month)) {
        return [];
    }
    $file = store_private('log') . '/' . $month . '.jsonl';
    if (!is_file($file)) {
        return [];
    }
    $out = [];
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $r = json_decode($line, true);
        if (is_array($r)) {
            $out[] = $r;
        }
    }
    return array_reverse($out);
}

/* ───────── Резервні копії ───────── */

/** Усі файли вмісту: «pages/kontakty.json» => повний шлях. */
function backup_files(): array
{
    $out = [];
    foreach (glob(CONTENT . '/*.json') ?: [] as $f) {
        $out[basename($f)] = $f;
    }
    foreach (['pages', 'news'] as $d) {
        foreach (glob(CONTENT . '/' . $d . '/*.json') ?: [] as $f) {
            $out[$d . '/' . basename($f)] = $f;
        }
    }
    ksort($out);
    return $out;
}

function backup_dir(): string
{
    return store_private('backups');
}

function backup_valid_name(string $name): bool
{
    return preg_match('~^\d{4}-\d\d-\d\d(-\d{6}-(manual|pre-restore))?\.json(\.gz)?$~', $name) === 1;
}

/** Перелік копій з короткими відомостями (без читання самих копій), новіші першими. */
function backup_list(): array
{
    $dir = backup_dir();
    $index = json_decode((string) @file_get_contents($dir . '/index.json'), true);
    $index = is_array($index) ? $index : [];
    $out = [];
    foreach (glob($dir . '/*.json*') ?: [] as $f) {
        $name = basename($f);
        if (!backup_valid_name($name)) {
            continue;
        }
        $info = $index[$name] ?? [];
        // час копії: із записаних відомостей, а якщо їх немає — з назви файлу
        $fromName = strtotime(substr($name, 0, 10) . (preg_match('~^\d{4}-\d\d-\d\d-(\d\d)(\d\d)(\d\d)-~', $name, $tm) ? " $tm[1]:$tm[2]:$tm[3]" : ' 00:00:00'));
        $out[] = [
            'name' => $name,
            'time' => (int) ($info['time'] ?? ($fromName ?: filemtime($f))),
            'kind' => (string) ($info['kind'] ?? (str_contains($name, 'manual') ? 'manual' : (str_contains($name, 'pre-restore') ? 'pre-restore' : 'daily'))),
            'note' => (string) ($info['note'] ?? ''),
            'by' => (string) ($info['by'] ?? ''),
            'counts' => (array) ($info['counts'] ?? []),
            'size' => (int) filesize($f),
        ];
    }
    usort($out, static fn(array $a, array $b): int => $b['time'] <=> $a['time']);
    return $out;
}

/** Найсвіжіша щоденна копія або null. */
function backup_latest(): ?array
{
    foreach (backup_list() as $b) {
        if ($b['kind'] === 'daily') {
            return $b;
        }
    }
    return null;
}

/** Створює копію. $kind: daily — щоденна, manual — вручну, pre-restore — перед відкатом. Повертає назву файлу. */
function backup_create(string $kind = 'daily', string $note = ''): ?string
{
    $dir = backup_dir();
    $files = [];
    foreach (backup_files() as $rel => $abs) {
        $files[$rel] = (string) file_get_contents($abs);
    }
    if (!$files) {
        return null;
    }
    $counts = [
        'pages' => count(array_filter(array_keys($files), static fn(string $r): bool => str_starts_with($r, 'pages/'))),
        'news' => count(array_filter(array_keys($files), static fn(string $r): bool => str_starts_with($r, 'news/'))),
        'documents' => count((array) json_decode($files['documents.json'] ?? '[]', true)),
        'staff' => count((array) json_decode($files['staff.json'] ?? '[]', true)),
    ];
    $u = $GLOBALS['user'] ?? null;
    $data = ['made' => date('c'), 'kind' => $kind, 'note' => $note, 'by' => (string) ($u['login'] ?? ''), 'counts' => $counts, 'files' => $files];
    $json = (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    $gz = function_exists('gzencode');
    $name = date('Y-m-d') . ($kind === 'daily' ? '' : '-' . date('His') . '-' . $kind) . '.json' . ($gz ? '.gz' : '');
    $tmp = $dir . '/' . $name . '.' . bin2hex(random_bytes(3)) . '.tmp';
    if (file_put_contents($tmp, $gz ? gzencode($json, 6) : $json) === false || !@rename($tmp, $dir . '/' . $name)) {
        @unlink($tmp);
        return null;
    }
    $index = json_decode((string) @file_get_contents($dir . '/index.json'), true);
    $index = is_array($index) ? $index : [];
    $index[$name] = ['time' => time(), 'kind' => $kind, 'note' => $note, 'by' => $data['by'], 'counts' => $counts];
    backup_prune($index);
    return $name;
}

/** Лишає 30 щоденних копій, першу копію кожного з 12 місяців і 10 останніх «ручних» та «перед відкатом». */
function backup_prune(array $index): void
{
    $dir = backup_dir();
    $daily = $extra = [];
    foreach (glob($dir . '/*.json*') ?: [] as $f) {
        $name = basename($f);
        if (!backup_valid_name($name)) {
            continue;
        }
        if (preg_match('~-(manual|pre-restore)\.~', $name)) {
            $extra[] = $name;
        } else {
            $daily[] = $name;
        }
    }
    rsort($daily);
    rsort($extra);
    $keep = array_slice($daily, 0, BACKUP_KEEP_DAILY);
    $months = [];
    foreach (array_reverse($daily) as $name) { // від найстарішої: перша копія місяця
        $m = substr($name, 0, 7);
        if (!isset($months[$m])) {
            $months[$m] = $name;
        }
    }
    krsort($months);
    $keep = array_merge($keep, array_slice(array_values($months), 0, BACKUP_KEEP_MONTHLY), array_slice($extra, 0, BACKUP_KEEP_EXTRA));
    foreach (array_merge($daily, $extra) as $name) {
        if (!in_array($name, $keep, true)) {
            @unlink($dir . '/' . $name);
            unset($index[$name]);
        }
    }
    foreach (array_keys($index) as $name) {
        if (!is_file($dir . '/' . $name)) {
            unset($index[$name]);
        }
    }
    @file_put_contents($dir . '/index.json', json_encode($index, JSON_UNESCAPED_UNICODE), LOCK_EX);
}

/** Вміст копії: ['made' => …, 'files' => ['pages/x.json' => '…текст файлу…']] або null. */
function backup_read(string $name): ?array
{
    if (!backup_valid_name($name)) {
        return null;
    }
    $file = backup_dir() . '/' . $name;
    if (!is_file($file)) {
        return null;
    }
    $raw = (string) file_get_contents($file);
    if (str_ends_with($name, '.gz')) {
        $raw = function_exists('gzdecode') ? (string) @gzdecode($raw) : '';
    }
    $data = json_decode($raw, true);
    return is_array($data) && is_array($data['files'] ?? null) ? $data : null;
}

/** Людська назва файлу вмісту: «Сторінка „Контакти“», «Новина „…“», «Кадровий склад». */
function backup_label(string $rel, string $json): string
{
    static $names = ['documents.json' => 'Реєстр документів', 'staff.json' => 'Кадровий склад', 'clubs.json' => 'Гуртки', 'settings.json' => 'Налаштування сайту',
        'home.json' => 'Головна сторінка', 'taxonomies.json' => 'Рубрики й розділи', 'profile-plan.json' => 'План профільної школи', 'meta.json' => 'Службовий лічильник'];
    if (isset($names[$rel])) {
        return $names[$rel];
    }
    $d = json_decode($json, true);
    $t = is_array($d) ? (string) ($d['title'] ?? '') : '';
    return (str_starts_with($rel, 'news/') ? 'Новина' : 'Сторінка') . ' «' . excerpt($t !== '' ? $t : basename($rel, '.json'), 70) . '»';
}

/**
 * Чим нинішній вміст відрізняється від копії:
 * 'changed' — буде повернено до стану з копії, 'missing' — з'явиться знову (зараз видалено),
 * 'extra' — зникне (створено після копії). Значення — людські назви.
 */
function backup_diff(array $data): array
{
    $now = backup_files();
    // лічильник номерів новин не відкочуємо: інакше номер видаленої новини міг би дістатися іншій
    unset($now['meta.json'], $data['files']['meta.json']);
    $out = ['changed' => [], 'missing' => [], 'extra' => []];
    foreach ($data['files'] as $rel => $json) {
        if (!isset($now[$rel])) {
            $out['missing'][$rel] = backup_label($rel, (string) $json);
        } elseif ((string) file_get_contents($now[$rel]) !== (string) $json) {
            $out['changed'][$rel] = backup_label($rel, (string) $json);
        }
    }
    foreach ($now as $rel => $abs) {
        if (!isset($data['files'][$rel])) {
            $out['extra'][$rel] = backup_label($rel, (string) file_get_contents($abs));
        }
    }
    return $out;
}

/**
 * Відкат вмісту до копії. Перед цим поточний стан зберігається окремою копією «перед відкатом».
 * Повертає ['safe' => назва страхувальної копії, 'changed' => N, 'returned' => N, 'removed' => N, 'files_back' => N, 'files_missing' => N] або null.
 */
function backup_restore(string $name): ?array
{
    $data = backup_read($name);
    if (!$data) {
        return null;
    }
    $safe = backup_create('pre-restore', 'стан перед відкатом до копії ' . $name);
    if (!$safe) {
        return null;
    }
    $diff = backup_diff($data);
    $fp = @fopen(CONTENT . '/.write.lock', 'c');
    if ($fp) {
        flock($fp, LOCK_EX);
    }
    foreach (array_merge(array_keys($diff['changed']), array_keys($diff['missing'])) as $rel) {
        if (!preg_match('~^((pages|news)/[a-z0-9-]+|[a-z-]+)\.json$~', $rel)) {
            continue;
        }
        $file = CONTENT . '/' . $rel;
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0775, true);
        }
        $tmp = $file . '.tmp' . bin2hex(random_bytes(3));
        file_put_contents($tmp, (string) $data['files'][$rel]);
        rename($tmp, $file);
    }
    foreach (array_keys($diff['extra']) as $rel) {
        // сторінки й новини, створені після копії, зникають; головна повертається до початкового вигляду
        if (preg_match('~^(pages|news)/[a-z0-9-]+\.json$~', $rel) || $rel === 'home.json') {
            @unlink(CONTENT . '/' . $rel);
        }
    }
    if ($fp) {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
    clearstatcache();
    // файли, на які посилається відновлений вміст, повертаємо з кошика
    $back = $lost = 0;
    if (function_exists('restore_upload')) {
        $seen = [];
        foreach ($data['files'] as $json) {
            if (preg_match_all('~uploads/[^"\\\\]+\.(?:jpe?g|png|webp|pdf)~i', str_replace('\\/', '/', (string) $json), $m)) {
                foreach ($m[0] as $u) {
                    if (isset($seen[$u])) {
                        continue;
                    }
                    $seen[$u] = true;
                    $was = is_file(ROOT . '/' . $u);
                    if (restore_upload($u)) {
                        $back += $was ? 0 : 1;
                    } else {
                        $lost++;
                    }
                }
            }
        }
    }
    return ['safe' => $safe, 'changed' => count($diff['changed']), 'returned' => count($diff['missing']), 'removed' => count($diff['extra']), 'files_back' => $back, 'files_missing' => $lost];
}

/** Віддає копію для завантаження: ZIP з файлами вмісту (або сам файл копії, якщо сервер не вміє ZIP). */
function backup_download(string $name): void
{
    $data = backup_read($name);
    if (!$data) {
        http_response_code(404);
        exit('Такої копії немає.');
    }
    $base = 'lyceum-vmist-' . substr($name, 0, 10);
    if (class_exists('ZipArchive')) {
        $tmp = tempnam(sys_get_temp_dir(), 'lyc');
        $zip = new ZipArchive();
        if ($tmp !== false && $zip->open($tmp, ZipArchive::OVERWRITE) === true) {
            foreach ($data['files'] as $rel => $json) {
                $zip->addFromString('content/' . $rel, (string) $json);
            }
            $zip->addFromString('ПРОЧИТАЙТЕ.txt', "Резервна копія вмісту сайту ліцею від " . ($data['made'] ?? '') . ".\n\n"
                . "Тут увесь текстовий вміст: сторінки, новини, реєстр документів, кадри, гуртки, налаштування.\n"
                . "Фото й PDF лежать окремо — у теці uploads/ на сервері.\n\n"
                . "Щоб відновити сайт із цієї копії вручну, скопіюйте вміст теки content/ з архіву в теку content/ сайту.\n");
            $zip->close();
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $base . '.zip"');
            header('Content-Length: ' . filesize($tmp));
            readfile($tmp);
            @unlink($tmp);
            exit;
        }
    }
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $base . (str_ends_with($name, '.gz') ? '.json.gz' : '.json') . '"');
    readfile(backup_dir() . '/' . $name);
    exit;
}

/**
 * Щоденна копія: якщо сьогоднішньої ще немає, вона створюється наприкінці першого за добу звернення
 * до сайту — уже після того, як сторінку віддано відвідувачеві.
 */
function backup_maybe(): void
{
    $dir = backup_dir();
    $today = $dir . '/' . date('Y-m-d') . '.json';
    if (is_file($today . '.gz') || is_file($today)) {
        return;
    }
    register_shutdown_function(static function () use ($dir, $today): void {
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }
        $lock = @fopen($dir . '/.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            return;
        }
        clearstatcache();
        if (!is_file($today . '.gz') && !is_file($today)) {
            @set_time_limit(60);
            backup_create('daily');
        }
        flock($lock, LOCK_UN);
        fclose($lock);
    });
}

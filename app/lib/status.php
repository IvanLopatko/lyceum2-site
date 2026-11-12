<?php
/**
 * Строки оприлюднення.
 *
 * Стаття 30 Закону «Про освіту», ч. 4: документ — на сайті не пізніше ніж через
 * 10 робочих днів із дня затвердження чи змін. Вихідні не рахуються;
 * державні свята під час воєнного стану не є вихідними, тому їх теж не виключаємо.
 */

declare(strict_types=1);

if (!defined('ROOT')) {
    http_response_code(404);
    exit;
}

const PUBLISH_DEADLINE_DAYS = 10;
const FRESH_DAYS = 30;

function working_days(string $from, string $to): ?int
{
    try {
        $a = new DateTimeImmutable($from);
        $b = new DateTimeImmutable($to);
    } catch (Exception) {
        return null;
    }
    if ($b < $a) {
        return 0;
    }
    $days = 0;
    for ($cur = $a; $cur < $b;) {
        $cur = $cur->modify('+1 day');
        if ((int) $cur->format('N') < 6) {
            $days++;
        }
    }
    return $days;
}

/** Стан документа: key, label, class (ok | fresh | due | late | none | archive), note. */
function doc_state(array $d): array
{
    if (($d['valid'] ?? '') === 'archive') {
        return ['key' => 'archive', 'label' => 'Архівний', 'class' => 'archive', 'note' => ''];
    }
    $approved = (string) ($d['approved'] ?? '');
    $published = (string) ($d['published'] ?? '');
    if ($approved === '') {
        return ['key' => 'nodate', 'label' => 'Дата не вказана', 'class' => 'late', 'note' => 'Поставте дату затвердження — без неї строк не рахується.'];
    }
    if ($published !== '') {
        $age = working_days($published, today());
        return ($age !== null && $age <= FRESH_DAYS)
            ? ['key' => 'fresh', 'label' => 'Нове', 'class' => 'fresh', 'note' => '']
            : ['key' => 'ok', 'label' => 'Оприлюднено', 'class' => 'ok', 'note' => ''];
    }
    $passed = working_days($approved, today()) ?? 0;
    $left = PUBLISH_DEADLINE_DAYS - $passed;
    if ($left < 0) {
        return ['key' => 'late', 'label' => 'Прострочено', 'class' => 'late', 'note' => "Минуло $passed робочих днів із дня затвердження, строк — " . PUBLISH_DEADLINE_DAYS . '.'];
    }
    return ['key' => 'due', 'label' => "Лишилось $left роб. дн.", 'class' => 'due', 'note' => 'Документ ще не позначено як оприлюднений.'];
}

/** Документи, які потребують уваги: прострочені, без дати, зі строком, що спливає. */
function docs_attention(): array
{
    $out = [];
    foreach (documents() as $d) {
        $s = doc_state($d);
        if (in_array($s['key'], ['late', 'nodate', 'due'], true)) {
            $out[] = ['doc' => $d, 'state' => $s];
        }
    }
    return $out;
}

/** Звіт про бюджетні кошти на порталі spending.gov.ua: щокварталу, до 35 днів після кварталу (ст. 3 Закону №183-VIII). */
function finance_deadline(): array
{
    $now = new DateTimeImmutable(today());
    $year = (int) $now->format('Y');
    $q = (int) ceil((int) $now->format('n') / 3) - 1;
    if ($q === 0) {
        $q = 4;
        $year--;
    }
    $end = (new DateTimeImmutable(sprintf('%d-%02d-01', $year, $q * 3)))->modify('last day of this month');
    $deadline = $end->modify('+35 days');
    return [
        'quarter' => ['', 'I', 'II', 'III', 'IV'][$q] . ' квартал ' . $year,
        'deadline' => $deadline->format('d.m.Y'),
        'days_left' => (int) $now->diff($deadline)->format('%r%a'),
    ];
}

function days_to(string $date): int
{
    $d = (new DateTimeImmutable(today()))->diff(new DateTimeImmutable($date));
    return max(0, (int) $d->format('%r%a'));
}

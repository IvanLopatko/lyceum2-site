<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<?php
$months = journal_months();
$month = (string) ($_GET['m'] ?? ($months[0] ?? date('Y-m')));
if (!in_array($month, $months, true)) {
    $month = $months[0] ?? date('Y-m');
}
$who = (string) ($_GET['khto'] ?? '');
$rows = journal_read($month);
$people = [];
foreach ($rows as $r) {
    $people[(string) $r['login']] = (string) ($r['name'] ?: $r['login']);
}
if ($who !== '') {
    $rows = array_values(array_filter($rows, static fn(array $r): bool => (string) $r['login'] === $who));
}
$total = count($rows);
$rows = array_slice($rows, 0, 500);
$monthName = static function (string $ym): string {
    [$y, $m] = array_map('intval', explode('-', $ym));
    return UA_MONTHS[$m] . ' ' . $y;
};
$when = static fn(int $t): string => (int) date('j', $t) . ' ' . UA_MONTHS_GEN[(int) date('n', $t)] . ', ' . date('H:i', $t);
?>
<header class="head"><h1>Журнал дій</h1></header>
<p class="muted">Хто, коли і що змінив на сайті. Записи зберігаються два роки. Якщо треба повернути попередній стан одного запису — «Історія змін»; якщо всього сайту — «Резервні копії».</p>
<form class="filters" method="get">
  <label>Місяць<select name="m"><?php foreach ($months ?: [$month] as $m): ?><option value="<?= e($m) ?>"<?= $m === $month ? ' selected' : '' ?>><?= e($monthName($m)) ?></option><?php endforeach; ?></select></label>
  <label>Хто<select name="khto"><option value="">усі</option><?php foreach ($people as $login => $name): ?><option value="<?= e($login) ?>"<?= $login === $who ? ' selected' : '' ?>><?= e($name) ?></option><?php endforeach; ?></select></label>
  <noscript><button class="btn btn-ghost" type="submit">Показати</button></noscript>
</form>
<?php if (!$rows): ?><p class="muted">За цей місяць записів немає.</p><?php else: ?>
<ul class="rows">
  <?php foreach ($rows as $r): $label = trim($r['action'] . ': ' . $r['kind'] . (($r['title'] ?? '') !== '' ? ' «' . $r['title'] . '»' : '')); ?>
    <li><?php if (($r['ref'] ?? '') !== ''): ?><a href="<?= e(url('/admin/' . trim((string) $r['ref'], '/') . '/')) ?>"><span><?= e($label) ?></span><small><?= e(($r['name'] ?: $r['login']) . ' · ' . $when((int) $r['t'])) ?></small></a>
        <?php else: ?><div class="row-static"><span><?= e($label) ?></span><small class="muted"><?= e(($r['name'] ?: $r['login']) . ' · ' . $when((int) $r['t'])) ?></small></div><?php endif; ?></li>
  <?php endforeach; ?>
</ul>
<?php if ($total > 500): ?><p class="muted small">Показано останні 500 записів із <?= $total ?>.</p><?php endif; ?>
<?php endif; ?>

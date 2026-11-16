<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<?php
/** @var string $name @var array $data @var array $diff @var bool $isOwner */
$t = strtotime((string) ($data['made'] ?? '')) ?: time();
$when = ua_date(date('Y-m-d', $t)) . ', ' . date('H:i', $t);
$nothing = !$diff['changed'] && !$diff['missing'] && !$diff['extra'];
$show = static function (array $items, int $max = 40): string {
    $out = '';
    foreach (array_slice($items, 0, $max) as $label) {
        $out .= '<li>' . e($label) . '</li>';
    }
    if (count($items) > $max) {
        $out .= '<li class="muted">…і ще ' . (count($items) - $max) . '</li>';
    }
    return $out;
};
?>
<header class="head"><div><p class="crumb"><a href="<?= e(url('/admin/kopii/')) ?>">Резервні копії</a></p><h1>Копія від <?= e($when) ?></h1></div>
  <a class="btn btn-ghost" href="<?= e(url('/admin/kopii/?zavantazhyty=' . rawurlencode($name))) ?>">Завантажити</a></header>

<?php if ($nothing): ?>
  <p class="ok-line">Сайт зараз такий самий, як у цій копії: відкочувати нічого.</p>
<?php else: ?>
<section class="card">
  <h2>Що зміниться, якщо відкотити сайт до цієї копії</h2>
  <?php if ($diff['changed']): ?><h3 class="sub3">Повернуться до попереднього вигляду (<?= count($diff['changed']) ?>)</h3><ul class="plain"><?= $show($diff['changed']) ?></ul><?php endif; ?>
  <?php if ($diff['missing']): ?><h3 class="sub3">З’являться знову — зараз видалені (<?= count($diff['missing']) ?>)</h3><ul class="plain"><?= $show($diff['missing']) ?></ul><?php endif; ?>
  <?php if ($diff['extra']): ?><h3 class="sub3">Зникнуть із сайту — створені після копії (<?= count($diff['extra']) ?>)</h3><ul class="plain"><?= $show($diff['extra']) ?></ul><?php endif; ?>
  <p class="muted">Облікові записи й паролі відкат не зачіпає. Перед відкатом нинішній стан сайту збережеться окремою копією «перед відкатом», тож відкат можна скасувати так само — відкотившись до неї.</p>
</section>

<?php if ($isOwner): ?>
<section class="card is-warn">
  <h2>Відкотити</h2>
  <form method="post" class="fields" data-confirm="Відкотити весь вміст сайту до копії від <?= e($when) ?>?">
    <?= csrf_field() ?><input type="hidden" name="do" value="restore"><input type="hidden" name="kopiia" value="<?= e($name) ?>">
    <label>Ваш пароль<input type="password" name="password" autocomplete="current-password" required></label>
    <label class="check wide"><input type="checkbox" name="confirm" value="yes" required> Розумію: усе, що змінено на сайті після <?= e($when) ?>, буде замінено станом із копії</label>
    <div class="wide"><button class="btn btn-danger" type="submit">Відкотити сайт до цієї копії</button></div>
  </form>
</section>
<?php else: ?>
<p class="muted">Відкотити сайт до копії може лише головний адміністратор.</p>
<?php endif; ?>
<?php endif; ?>

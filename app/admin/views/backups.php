<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<?php
/** @var bool $isOwner */
$list = backup_list();
$latest = backup_latest();
$when = static fn(int $t): string => ua_date(date('Y-m-d', $t)) . ', ' . date('H:i', $t);
$kinds = ['daily' => 'щоденна', 'manual' => 'вручну', 'pre-restore' => 'перед відкатом'];
$size = static fn(int $b): string => $b >= 1048576 ? round($b / 1048576, 1) . ' МБ' : max(1, (int) round($b / 1024)) . ' КБ';
?>
<header class="head"><h1>Резервні копії</h1></header>
<section class="card">
  <h2>Як це працює</h2>
  <ul class="plain">
    <li><b>Щодня</b> сайт сам зберігає копію всього вмісту: сторінок, новин, реєстру документів, кадрів, гуртків і налаштувань. Копія з’являється з першим відвідуванням сайту за добу.</li>
    <li>Зберігаються копії за <b>останні <?= BACKUP_KEEP_DAILY ?> днів</b> і перша копія кожного місяця за рік.</li>
    <li><b>Фото й PDF</b> у копію не входять: файли на сайті ніколи не перезаписуються, а видалені 30 днів лежать у кошику — під час відкату вони повертаються звідти.</li>
    <li>Копії лежать на тому самому сервері, що й сайт. Від збою хостингу рятує лише копія на іншому комп’ютері: раз на місяць завантажуйте свіжу копію кнопкою нижче.</li>
  </ul>
  <?php if ($latest): ?><p class="ok-line">Остання щоденна копія: <?= e($when($latest['time'])) ?>.</p><?php else: ?><p class="flash is-warn">Щоденної копії ще немає. Вона з’явиться протягом хвилини; якщо ні — сервер не дозволяє запис у теку <code>content</code>.</p><?php endif; ?>
  <div class="actions-row">
    <?php if ($latest): ?><a class="btn" href="<?= e(url('/admin/kopii/?kopiia=' . rawurlencode($latest['name']))) ?>">Відкотити до останньої копії…</a><?php endif; ?>
    <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="do" value="create">
      <button class="btn btn-ghost" type="submit">Створити копію зараз</button>
    </form>
  </div>
  <?php if (!$isOwner): ?><p class="muted small">Відкотити сайт до копії може лише головний адміністратор. Переглянути й завантажити копію можете й ви.</p><?php endif; ?>
</section>

<section class="card">
  <h2>Усі копії</h2>
  <?php if (!$list): ?><p class="muted">Копій ще немає.</p><?php else: ?>
  <ul class="rows">
    <?php foreach ($list as $b): $c = $b['counts']; ?>
      <li><div class="row-static">
        <span><?= e($when($b['time'])) ?> <em class="pill<?= $b['kind'] === 'daily' ? ' is-ok' : '' ?>"><?= e($kinds[$b['kind']] ?? $b['kind']) ?></em>
          <small class="muted"><?= e(($c ? 'сторінок ' . ($c['pages'] ?? 0) . ' · новин ' . ($c['news'] ?? 0) . ' · документів ' . ($c['documents'] ?? 0) . ' · ' : '') . $size($b['size']) . ($b['note'] !== '' ? ' · ' . $b['note'] : '')) ?></small></span>
        <span class="row-actions"><a href="<?= e(url('/admin/kopii/?zavantazhyty=' . rawurlencode($b['name']))) ?>">Завантажити</a> · <a href="<?= e(url('/admin/kopii/?kopiia=' . rawurlencode($b['name']))) ?>">Що зміниться й відкат</a></span>
      </div></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</section>

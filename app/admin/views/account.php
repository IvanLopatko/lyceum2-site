<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<?php /** @var bool $isAdmin */ ?>
<header class="head"><h1>Обліковий запис</h1></header>
<section class="card">
  <h2>Змінити пароль</h2>
  <form method="post" class="fields"><?= csrf_field() ?><input type="hidden" name="do" value="password">
    <label>Поточний пароль<input type="password" name="old" autocomplete="current-password" required></label>
    <label>Новий пароль (від 10 символів, літери й цифри)<input type="password" name="new" autocomplete="new-password" minlength="10" required></label>
    <label>Новий пароль ще раз<input type="password" name="new2" autocomplete="new-password" minlength="10" required></label>
    <div class="wide"><button class="btn" type="submit">Змінити пароль</button></div>
  </form>
</section>
<?php if ($isAdmin): ?>
<section class="card">
  <h2>Хто має доступ</h2>
  <ul class="rows">
    <?php foreach (admin_users() as $u): ?>
      <li><div class="row-static"><span><?= e($u['name']) ?> <small class="muted"><?= e($u['login']) ?></small></span><em class="pill"><?= $u['role'] === 'admin' ? 'адміністратор' : 'редактор' ?></em>
        <?php if ($u['login'] !== $user['login']): ?><form method="post" data-confirm="Забрати доступ у <?= e($u['name']) ?>?"><?= csrf_field() ?><input type="hidden" name="do" value="remove"><input type="hidden" name="login" value="<?= e($u['login']) ?>"><button class="link-danger" type="submit">забрати доступ</button></form><?php endif; ?></div></li>
    <?php endforeach; ?>
  </ul>
  <h3 class="sub3">Додати людину</h3>
  <form method="post" class="fields"><?= csrf_field() ?><input type="hidden" name="do" value="add">
    <label>Ім’я<input name="name" required></label>
    <label>Логін (латиницею)<input name="login" pattern="[a-z0-9._-]{3,32}" required></label>
    <label>Тимчасовий пароль<input type="password" name="password" minlength="10" autocomplete="new-password" required></label>
    <label>Роль<select name="role"><option value="editor">Редактор — сторінки, новини, документи, кадри, гуртки</option><option value="admin">Адміністратор — ще й налаштування сайту, доступи, прибирання зразків</option></select></label>
    <div class="wide"><button class="btn" type="submit">Додати</button></div>
  </form>
</section>
<?php endif; ?>
<?php
$log = array_reverse(login_history());
if (!$isAdmin) {
    $log = array_filter($log, static fn($r) => !empty($r['ok']) && ($r['login'] ?? '') === $user['login']);
}
$log = array_slice(array_values($log), 0, 30);
?>
<section class="card">
  <h2>Журнал входів</h2>
  <p class="muted"><?= $isAdmin ? 'Останні входи в адмінку й невдалі спроби, новіші зверху.' : 'Ваші останні входи в адмінку, новіші зверху.' ?> Якщо бачите вхід, якого не робили, — одразу змініть пароль: усі інші сеанси завершаться.</p>
  <?php if (!$log): ?><p class="muted">Записів ще немає.</p><?php else: ?>
  <ul class="rows">
    <?php foreach ($log as $r): $ok = !empty($r['ok']); ?>
      <li><div class="row-static"><span><?= e(ua_date(date('Y-m-d', (int) $r['t'])) . ', ' . date('H:i', (int) $r['t'])) ?> <small class="muted"><?= e((($r['login'] ?? '') !== '' ? $r['login'] : 'логін не розпізнано') . ' · адреса ' . ($r['ip'] ?? '')) ?></small></span><em class="pill <?= $ok ? 'is-ok' : 'is-late' ?>"><?= $ok ? 'вхід' : 'невдала спроба' ?></em></div></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</section>

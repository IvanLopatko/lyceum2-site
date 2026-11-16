<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<?php
/** @var bool $isAdmin @var bool $isOwner */
$users = admin_users();
$hist = login_history();
$lastIn = [];
foreach ($hist as $r) {
    if (!empty($r['ok'])) {
        $lastIn[(string) $r['login']] = (int) $r['t'];
    }
}
$when = static fn(int $t): string => ua_date(date('Y-m-d', $t)) . ', ' . date('H:i', $t);
$ownerName = '';
foreach ($users as $u) {
    if (is_owner($u)) {
        $ownerName = (string) $u['name'];
    }
}
$others = array_values(array_filter($users, static fn($u) => $u['login'] !== $user['login']));
?>
<header class="head"><h1>Обліковий запис</h1></header>

<section class="card">
  <h2><?= e($user['name']) ?></h2>
  <p class="muted">Логін: <b><?= e($user['login']) ?></b> · роль: <b><?= e(role_label((string) $user['role'])) ?></b></p>
  <?php if (!empty($user['must_change'])): ?><p class="flash is-warn">Зараз у вас тимчасовий пароль. Задайте власний — тоді відкриються всі розділи адмінки.</p><?php endif; ?>
  <h3 class="sub3">Змінити пароль</h3>
  <form method="post" class="fields"><?= csrf_field() ?><input type="hidden" name="do" value="password">
    <label><?= !empty($user['must_change']) ? 'Тимчасовий пароль' : 'Поточний пароль' ?><input type="password" name="old" autocomplete="current-password" required></label>
    <label>Новий пароль (від 10 символів, літери й цифри)<input type="password" name="new" autocomplete="new-password" minlength="10" required></label>
    <label>Новий пароль ще раз<input type="password" name="new2" autocomplete="new-password" minlength="10" required></label>
    <div class="wide"><button class="btn" type="submit">Змінити пароль</button></div>
  </form>
</section>

<?php if ($isOwner): ?>
<section class="card" id="dostupy">
  <h2>Доступи співробітників</h2>
  <p class="muted">Ви — головний адміністратор: лише ви створюєте й забираєте доступи. Кожній людині — окремий логін; так у журналі видно, хто що змінив, а забрати доступ можна в одної людини, не зачіпаючи інших.</p>
  <ul class="rows">
    <li><div class="row-static"><span><?= e($user['name']) ?> <small class="muted"><?= e($user['login']) ?> · це ви</small></span><em class="pill is-ok">головний адміністратор</em></div></li>
    <?php foreach ($others as $u): $t = $lastIn[(string) $u['login']] ?? 0; ?>
      <li>
        <details class="manage">
          <summary><span><?= e($u['name']) ?> <small class="muted"><?= e($u['login']) ?> · <?= $t ? 'останній вхід ' . e($when($t)) : 'ще не входив(ла)' ?><?= !empty($u['must_change']) ? ' · тимчасовий пароль ще не замінено' : '' ?></small></span><em class="pill"><?= e(role_label((string) $u['role'])) ?></em></summary>
          <div class="manage-in">
            <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="do" value="role"><input type="hidden" name="login" value="<?= e($u['login']) ?>">
              <label>Роль<select name="role"><option value="editor"<?= $u['role'] === 'editor' ? ' selected' : '' ?>>Редактор</option><option value="admin"<?= $u['role'] === 'admin' ? ' selected' : '' ?>>Адміністратор</option></select></label>
              <button class="btn btn-ghost" type="submit">Змінити роль</button>
            </form>
            <form method="post" class="inline" data-confirm="Задати новий тимчасовий пароль для <?= e($u['name']) ?>? Усі відкриті сеанси цієї людини завершаться."><?= csrf_field() ?><input type="hidden" name="do" value="resetpw"><input type="hidden" name="login" value="<?= e($u['login']) ?>">
              <label>Новий тимчасовий пароль<input type="password" name="password" minlength="10" autocomplete="new-password" required></label>
              <button class="btn btn-ghost" type="submit">Видати пароль</button>
            </form>
            <form method="post" class="inline" data-confirm="Забрати доступ у <?= e($u['name']) ?>? Людина більше не зможе ввійти в адмінку."><?= csrf_field() ?><input type="hidden" name="do" value="remove"><input type="hidden" name="login" value="<?= e($u['login']) ?>">
              <button class="link-danger" type="submit">Забрати доступ</button>
            </form>
          </div>
        </details>
      </li>
    <?php endforeach; ?>
  </ul>

  <h3 class="sub3">Додати людину</h3>
  <form method="post" class="fields"><?= csrf_field() ?><input type="hidden" name="do" value="add">
    <label>Прізвище та ім’я<input name="name" required></label>
    <label>Логін (латиницею)<input name="login" pattern="[a-z0-9._-]{3,32}" required autocomplete="off"></label>
    <label>Тимчасовий пароль<input type="password" name="password" minlength="10" autocomplete="new-password" required><small class="muted">Під час першого входу сайт попросить людину замінити його власним.</small></label>
    <label>Роль<select name="role"><option value="editor">Редактор — сторінки, новини, документи, кадри, гуртки</option><option value="admin">Адміністратор — ще й головна, рубрики, налаштування, журнал, копії</option></select></label>
    <div class="wide"><button class="btn" type="submit">Створити доступ</button></div>
  </form>

  <?php if ($others): ?>
  <details class="manage is-plain">
    <summary><span>Передати права головного адміністратора</span></summary>
    <div class="manage-in">
      <p class="muted">Якщо сайт веде вже інша людина. Вона стане головним адміністратором, а ви — звичайним адміністратором.</p>
      <form method="post" class="inline" data-confirm="Передати права головного адміністратора? Керувати доступами зможе лише нова людина."><?= csrf_field() ?><input type="hidden" name="do" value="transfer">
        <label>Кому<select name="login"><?php foreach ($others as $u): ?><option value="<?= e($u['login']) ?>"><?= e($u['name']) ?></option><?php endforeach; ?></select></label>
        <label>Ваш пароль<input type="password" name="password" autocomplete="current-password" required></label>
        <button class="btn btn-ghost" type="submit">Передати</button>
      </form>
    </div>
  </details>
  <?php endif; ?>
  <p class="muted small">Якщо головний адміністратор забув пароль: створіть на сервері порожній файл <code>config/recover</code> (через файловий менеджер хостингу) і відкрийте сторінку входу — сайт попросить код із файлу <code>config/recover-token.php</code> і новий пароль.</p>
</section>
<?php else: ?>
<p class="muted">Доступами співробітників керує головний адміністратор<?= $ownerName !== '' ? ': ' . e($ownerName) : '' ?>. Якщо забули пароль — зверніться до нього по новий тимчасовий.</p>
<?php endif; ?>

<?php
$log = array_reverse($hist);
if (!$isOwner) {
    $log = array_filter($log, static fn($r) => !empty($r['ok']) && ($r['login'] ?? '') === $user['login']);
}
$log = array_slice(array_values($log), 0, 30);
?>
<section class="card">
  <h2>Журнал входів</h2>
  <p class="muted"><?= $isOwner ? 'Останні входи в адмінку й невдалі спроби, новіші зверху.' : 'Ваші останні входи в адмінку, новіші зверху.' ?> Якщо бачите вхід, якого не робили, — одразу змініть пароль: усі інші сеанси завершаться.</p>
  <?php if (!$log): ?><p class="muted">Записів ще немає.</p><?php else: ?>
  <ul class="rows">
    <?php foreach ($log as $r): $ok = !empty($r['ok']); ?>
      <li><div class="row-static"><span><?= e($when((int) $r['t'])) ?> <small class="muted"><?= e((($r['login'] ?? '') !== '' ? $r['login'] : 'логін не розпізнано') . ' · адреса ' . ($r['ip'] ?? '')) ?></small></span><em class="pill <?= $ok ? 'is-ok' : 'is-late' ?>"><?= $ok ? 'вхід' : 'невдала спроба' ?></em></div></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</section>

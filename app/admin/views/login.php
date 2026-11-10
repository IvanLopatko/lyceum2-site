<form class="auth" method="post" action="<?= e(url('/admin/')) ?>">
  <img src="<?= e(url('/uploads/emblema.png')) ?>" alt="" width="72" height="72">
  <h1>Вхід в адмінку</h1>
  <p class="muted"><?= e(setting('site.name')) ?></p>
  <?php if ($error): ?><p class="flash is-err" role="alert"><?= e($error) ?></p><?php endif; ?>
  <?= csrf_field() ?>
  <label>Логін<input name="login" autocomplete="username" required autofocus></label>
  <label>Пароль<input name="password" type="password" autocomplete="current-password" required></label>
  <button class="btn" type="submit">Увійти</button>
</form>

<form class="auth" method="post" action="<?= e(url('/admin/')) ?>">
  <img src="<?= e(url('/uploads/emblema.png')) ?>" alt="" width="72" height="72">
  <h1>Перший вхід</h1>
  <p class="muted">Створіть головний обліковий запис. Одноразовий код лежить на сервері у файлі <code>config/setup-token.txt</code> — його бачить лише той, хто має доступ до файлів хостингу.</p>
  <?php if ($error): ?><p class="flash is-err" role="alert"><?= e($error) ?></p><?php endif; ?>
  <?= csrf_field() ?>
  <label>Код із файлу<input name="token" required autocomplete="off"></label>
  <label>Ваше ім’я<input name="name" placeholder="Наприклад: Іван Лопатко"></label>
  <label>Логін<input name="login" required pattern="[a-z0-9._-]{3,32}" autocomplete="username"></label>
  <label>Пароль (від 10 символів, літери й цифри)<input name="password" type="password" required minlength="10" autocomplete="new-password"></label>
  <button class="btn" type="submit">Створити</button>
</form>

<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<form class="auth" method="post" action="<?= e(url('/admin/')) ?>">
  <img src="<?= e(asset('/uploads/emblema.png')) ?>" alt="" width="72" height="72">
  <h1>Відновлення доступу</h1>
  <p class="muted">На сервері створено файл <code>config/recover</code>, тому можна задати новий пароль головного адміністратора<?= $owner ? ' (логін <b>' . e($owner['login']) . '</b>)' : '' ?>. Одноразовий код лежить у файлі <code>config/recover-token.php</code> (другий рядок) — його бачить лише той, хто має доступ до файлів хостингу.</p>
  <?php if (!is_https() && !is_local_host()): ?><p class="flash is-err" role="alert">З’єднання не зашифроване: адреса починається з http://, тож пароль дорогою можуть перехопити. Відкрийте сайт через https://.</p><?php endif; ?>
  <?php if ($error): ?><p class="flash is-err" role="alert"><?= e($error) ?></p><?php endif; ?>
  <?= csrf_field() ?><input type="hidden" name="recover" value="1">
  <label>Код із файлу<input name="token" required autocomplete="off"></label>
  <label>Новий пароль (від 10 символів, літери й цифри)<input name="password" type="password" required minlength="10" autocomplete="new-password"></label>
  <label>Новий пароль ще раз<input name="password2" type="password" required minlength="10" autocomplete="new-password"></label>
  <button class="btn" type="submit">Задати пароль</button>
  <p class="muted small"><a href="<?= e(url('/admin/?vhid=1')) ?>">Звичайний вхід</a> · якщо відновлення не потрібне, видаліть файл <code>config/recover</code> із сервера.</p>
</form>

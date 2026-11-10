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
    <label>Роль<select name="role"><option value="editor">Редактор — новини, сторінки, документи</option><option value="admin">Адміністратор — ще й доступи</option></select></label>
    <div class="wide"><button class="btn" type="submit">Додати</button></div>
  </form>
</section>
<?php endif; ?>

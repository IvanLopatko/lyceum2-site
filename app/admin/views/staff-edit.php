<?php /** @var array $p @var bool $isNew */ ?>
<header class="head"><div><p class="crumb"><a href="<?= e(url('/admin/kadry/')) ?>">Кадри</a></p><h1><?= $isNew ? 'Нова людина' : e($p['name']) ?></h1></div></header>
<form method="post" enctype="multipart/form-data" class="edit">
  <?= csrf_field() ?>
  <div class="person-edit">
    <div class="photo-box"><?php if ($p['photo']): ?><img src="<?= e(upload_url($p['photo'])) ?>" alt=""><label class="check"><input type="checkbox" name="remove_photo"> прибрати фото</label><?php else: ?><span class="thumb big"></span><?php endif; ?>
      <label class="upload">Фото (JPG/PNG)<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label></div>
    <div class="fields">
      <label class="wide">Прізвище, ім’я, по батькові<input name="name" value="<?= e($p['name']) ?>" required></label>
      <label class="wide">Посада<input name="position" value="<?= e($p['position']) ?>" placeholder="Вчитель"></label>
      <label class="wide">Предмети / напрям<input name="subjects" value="<?= e($p['subjects']) ?>" placeholder="Математика"></label>
      <label>Група<select name="group"><?php foreach (taxonomies()['staff'] as $g): ?><option value="<?= e($g['slug']) ?>"<?= $p['group'] === $g['slug'] ? ' selected' : '' ?>><?= e($g['name']) ?></option><?php endforeach; ?></select></label>
      <label>Порядок у списку<input type="number" name="order" value="<?= (int) $p['order'] ?>"></label>
      <label class="check wide"><input type="checkbox" name="demo"<?= !empty($p['demo']) ? ' checked' : '' ?>> Зразковий запис</label>
    </div>
  </div>
  <div class="savebar"><button class="btn" type="submit">Зберегти</button></div>
</form>
<?php if (!$isNew): ?><form method="post" class="danger-zone" data-confirm="Видалити запис?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><button class="btn btn-danger-ghost" type="submit">Видалити</button></form><?php endif; ?>

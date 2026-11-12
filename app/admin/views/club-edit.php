<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<?php /** @var array $c @var bool $isNew */ ?>
<header class="head"><div><p class="crumb"><a href="<?= e(url('/admin/gurtky/')) ?>">Гуртки</a></p><h1><?= $isNew ? 'Новий гурток' : e($c['title']) ?></h1></div></header>
<form method="post" class="edit">
  <?= csrf_field() ?>
  <div class="fields">
    <label class="wide">Назва<input name="title" value="<?= e($c['title']) ?>" required></label>
    <label class="wide">Опис<textarea name="text" rows="3"><?= e($c['text']) ?></textarea></label>
    <label>Для кого<input name="classes" value="<?= e($c['classes']) ?>" placeholder="5–9 класи"></label>
    <label>Керівник<input name="leader" value="<?= e($c['leader']) ?>"></label>
    <label>День<input name="day" value="<?= e($c['day']) ?>" placeholder="вівторок"></label>
    <label>Час<input name="time" value="<?= e($c['time']) ?>" placeholder="14:30–15:30"></label>
    <label>Де<input name="room" value="<?= e($c['room']) ?>" placeholder="каб. 24"></label>
    <label class="check wide"><input type="checkbox" name="demo"<?= !empty($c['demo']) ? ' checked' : '' ?>> Зразковий запис</label>
  </div>
  <div class="savebar"><button class="btn" type="submit">Зберегти</button></div>
</form>
<?php if (!$isNew): ?><form method="post" class="danger-zone" data-confirm="Видалити гурток?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><button class="btn btn-danger-ghost" type="submit">Видалити</button></form><?php endif; ?>

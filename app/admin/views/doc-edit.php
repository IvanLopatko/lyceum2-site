<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<?php /** @var array $doc @var bool $isNew */ $st = doc_state($doc); ?>
<header class="head"><div><p class="crumb"><a href="<?= e(url('/admin/dokumenty/')) ?>">Документи</a></p><h1><?= $isNew ? 'Новий документ' : e($doc['title']) ?></h1></div>
<?php if (!$isNew): ?><span class="pill is-<?= e($st['class']) ?>"><?= e($st['label']) ?></span><?php endif; ?></header>
<?php if (!$isNew && $st['note']): ?><p class="flash is-warn"><?= e($st['note']) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="edit">
  <?= csrf_field() ?>
  <div class="fields">
    <label class="wide">Назва документа<input name="title" value="<?= e($doc['title']) ?>" required></label>
    <label>Номер, реквізити (необов’язково)<input name="number" value="<?= e($doc['number']) ?>" placeholder="наказ №192-о"></label>
    <label>Чинність<select name="valid"><option value="current">Чинний</option><option value="archive"<?= $doc['valid'] === 'archive' ? ' selected' : '' ?>>Архівний (не показувати в списках)</option></select></label>
    <label>Дата затвердження<input type="date" name="approved" value="<?= e($doc['approved']) ?>"></label>
    <label>Дата оприлюднення на сайті<input type="date" name="published" value="<?= e($doc['published']) ?>"><small class="muted">Ставиться сама, коли додаєте файл</small></label>
    <fieldset class="wide"><legend>Розділ реєстру</legend><div class="checks">
      <?php foreach (taxonomies()['documents'] as $t): ?><label class="check"><input type="checkbox" name="sections[]" value="<?= e($t['slug']) ?>"<?= in_array($t['slug'], $doc['sections'], true) ? ' checked' : '' ?>> <?= e($t['name']) ?></label><?php endforeach; ?>
    </div></fieldset>
  </div>
  <h2 class="sub">Файл</h2>
  <?php if ($doc['file']): ?><p><a href="<?= e(upload_url($doc['file'])) ?>" target="_blank" rel="noopener">Відкрити поточний файл ↗</a> <label class="check"><input type="checkbox" name="remove_file"> прибрати файл</label></p><?php endif; ?>
  <label class="upload"><?= $doc['file'] ? 'Замінити' : 'Додати' ?> PDF (до 30 МБ)<input type="file" name="file" data-max-bytes="<?= (int) min(upload_limits()['post'], upload_limits()['file']) ?>" accept="application/pdf"></label>
  <label class="check"><input type="checkbox" name="demo"<?= !empty($doc['demo']) ? ' checked' : '' ?>> Це зразковий запис (буде прибраний кнопкою «Прибрати зразки»)</label>
  <div class="savebar"><button class="btn" type="submit">Зберегти</button></div>
</form>
<?php if (!$isNew): ?><form method="post" class="danger-zone" data-confirm="Прибрати документ з реєстру? Файл за посиланням більше не відкриватиметься."><?= csrf_field() ?><input type="hidden" name="do" value="delete"><button class="btn btn-danger-ghost" type="submit">Прибрати з реєстру</button></form><?php endif; ?>

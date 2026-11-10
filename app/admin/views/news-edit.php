<?php /** @var array $item @var bool $isNew */ ?>
<header class="head">
  <div><p class="crumb"><a href="<?= e(url('/admin/novyny/')) ?>">Новини</a></p><h1><?= $isNew ? 'Нова новина' : 'Редагування новини' ?></h1></div>
  <?php if (!$isNew): ?><a class="btn btn-ghost" href="<?= e(url(news_url($item))) ?>" target="_blank" rel="noopener">Переглянути ↗</a><?php endif; ?>
</header>
<form method="post" enctype="multipart/form-data" class="edit" id="news-form">
  <?= csrf_field() ?>
  <div class="fields">
    <label class="wide">Заголовок<input name="title" value="<?= e($item['title']) ?>" required></label>
    <label>Дата й час<input type="datetime-local" name="date" value="<?= e(str_replace(' ', 'T', $item['date'])) ?>"></label>
    <fieldset class="wide"><legend>Рубрики</legend><div class="checks">
      <?php foreach (taxonomies()['news'] as $t): ?><label class="check"><input type="checkbox" name="categories[]" value="<?= e($t['slug']) ?>"<?= in_array($t['slug'], $item['categories'], true) ? ' checked' : '' ?>> <?= e($t['name']) ?></label><?php endforeach; ?>
    </div></fieldset>
  </div>
  <h2 class="sub">Текст</h2>
  <p class="muted small">Кожен абзац — з нового рядка (Enter). Виділіть слово, щоб зробити його жирним або посиланням.</p>
  <div class="rich-wrap">
    <div class="rich-bar" role="toolbar" aria-label="Форматування"><button type="button" data-cmd="bold"><b>Ж</b></button><button type="button" data-cmd="italic"><i>К</i></button><button type="button" data-cmd="link">Посилання</button></div>
    <div class="rich rich-body" id="news-text" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Текст новини"><?php foreach ($item['text'] as $p): ?><p><?= $p ?></p><?php endforeach; ?></div>
  </div>
  <input type="hidden" name="text_json" id="text-json">
  <h2 class="sub">Фото</h2>
  <?php if ($item['images']): ?>
  <div class="photos">
    <?php foreach ($item['images'] as $img): ?>
      <figure><img src="<?= e(upload_url($img)) ?>" alt="" loading="lazy">
        <label class="check"><input type="radio" name="cover" value="<?= e($img) ?>"<?= $item['cover'] === $img ? ' checked' : '' ?>> обкладинка</label>
        <label class="check"><input type="checkbox" name="remove[]" value="<?= e($img) ?>"> прибрати</label></figure>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <label class="upload">Додати фото (можна кілька, JPG/PNG, до 15 МБ кожне)<input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple></label>
  <div class="fields">
    <label class="wide">Короткий опис для списку (необов’язково)<textarea name="excerpt" rows="2"><?= e($item['excerpt']) ?></textarea></label>
    <label class="wide">Посилання на допис у Facebook (необов’язково)<input type="url" name="source" value="<?= e($item['source']) ?>"></label>
  </div>
  <div class="savebar"><button class="btn" type="submit">Зберегти</button></div>
</form>
<?php if (!$isNew): ?>
<form method="post" class="danger-zone" data-confirm="Видалити новину?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><button class="btn btn-danger-ghost" type="submit">Видалити новину</button></form>
<?php endif; ?>

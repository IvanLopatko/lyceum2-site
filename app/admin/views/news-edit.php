<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<?php /** @var array $item @var bool $isNew */
$photos = news_photos($item);
$lim = upload_limits();
$versions = $isNew ? 0 : count(store_versions('news/' . $item['id'] . '.json'));
?>
<header class="head">
  <div><p class="crumb"><a href="<?= e(url('/admin/novyny/')) ?>">Новини</a></p><h1><?= $isNew ? 'Нова новина' : 'Редагування новини' ?></h1></div>
  <?php if (!$isNew): ?><a class="btn btn-ghost" href="<?= e(url(news_url($item))) ?>" target="_blank" rel="noopener"><?= !empty($item['draft']) ? 'Попередній перегляд' : 'Переглянути' ?> ↗</a><?php endif; ?>
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
  <?php if ($photos): ?>
  <div class="photos">
    <?php foreach ($photos as $img): ?>
      <figure><img src="<?= e(thumb_url($img)) ?>" alt="" loading="lazy">
        <label class="check"><input type="radio" name="cover" value="<?= e($img) ?>"<?= news_cover($item) === $img ? ' checked' : '' ?>> обкладинка</label>
        <label class="check"><input type="checkbox" name="remove[]" value="<?= e($img) ?>"> прибрати</label>
        <input class="alt" name="alt[<?= e($img) ?>]" value="<?= e((string) (($item['alts'] ?? [])[$img] ?? '')) ?>" maxlength="200" placeholder="Що на фото (для незрячих)" aria-label="Опис фото для програм читання з екрана"></figure>
    <?php endforeach; ?>
  </div>
  <p class="muted small">Опис фото читають уголос програми для незрячих. Кілька слів: «Учні 5-А саджають дерева біля корпусу». Можна лишити порожнім.</p>
  <?php endif; ?>
  <label class="upload">Додати фото (JPG/PNG, до 15 МБ кожне, за один раз — до <?= (int) $lim['files'] ?> фото)<input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple data-max-files="<?= (int) $lim['files'] ?>" data-max-bytes="<?= (int) min($lim['post'], PHP_INT_MAX) ?>"></label>
  <div class="fields">
    <label class="wide">Короткий опис для списку (необов’язково)<textarea name="excerpt" rows="2"><?= e($item['excerpt']) ?></textarea></label>
    <label class="wide">Посилання на допис у Facebook (необов’язково)<input type="url" name="source" value="<?= e($item['source']) ?>"></label>
  </div>
  <label class="check"><input type="checkbox" name="draft"<?= !empty($item['draft']) ? ' checked' : '' ?>> Чернетка — зберегти, але поки не показувати на сайті</label>
  <div class="savebar"><button class="btn" type="submit">Зберегти</button>
    <?php if (!empty($item['draft'])): ?><span class="pill is-due">чернетка: на сайті не видно</span><?php endif; ?>
    <?php if ($versions): ?><a class="muted small" href="<?= e(url('/admin/istoriia/?f=' . rawurlencode('news/' . $item['id'] . '.json'))) ?>">Попередні версії (<?= $versions ?>)</a><?php endif; ?></div>
</form>
<?php if (!$isNew): ?>
<form method="post" class="danger-zone" data-confirm="Видалити новину?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><button class="btn btn-danger-ghost" type="submit">Видалити новину</button></form>
<?php endif; ?>

<?php
/** @var array $page @var bool $isNew */
$sections = array_filter(pages(), static fn($p) => ($p['parent'] ?? '') === '' && !in_array($p['slug'], SPECIAL_PAGES, true));
$icons = array_values(array_unique(array_merge(array_values(PAGE_ICONS), ['phone', 'mail', 'clock', 'food', 'bus', 'music', 'ball', 'flask', 'gift', 'spark', 'sun', 'pin', 'star'])));
sort($icons);
$cfg = [
    'types' => BLOCK_TYPES, 'components' => COMPONENTS, 'icons' => $icons,
    'docSections' => taxonomies()['documents'], 'staffGroups' => taxonomies()['staff'], 'newsCats' => taxonomies()['news'],
];
?>
<header class="head">
  <div><p class="crumb"><a href="<?= e(url('/admin/storinky/')) ?>">Сторінки</a></p><h1><?= $isNew ? 'Нова сторінка' : e($page['title']) ?></h1></div>
  <?php if (!$isNew): ?><a class="btn btn-ghost" href="<?= e(url(page_path($page['slug']))) ?>" target="_blank" rel="noopener">Переглянути ↗</a><?php endif; ?>
</header>
<form method="post" class="edit" id="page-form">
  <?= csrf_field() ?>
  <div class="fields">
    <label class="wide">Заголовок<input name="title" value="<?= e($page['title']) ?>" required></label>
    <label class="wide">Короткий опис під заголовком<textarea name="lead" rows="2"><?= e($page['lead'] ?? '') ?></textarea></label>
    <label>Розділ
      <select name="parent">
        <option value="">— верхній рівень —</option>
        <?php foreach ($sections as $s): if ($s['slug'] === $page['slug']) continue; ?><option value="<?= e($s['slug']) ?>"<?= ($page['parent'] ?? '') === $s['slug'] ? ' selected' : '' ?>><?= e($s['title']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Порядок у розділі<input name="order" type="number" value="<?= (int) ($page['order'] ?? 0) ?>"></label>
    <?php if ($isNew): ?><label>Адреса (латиницею, можна лишити порожньою)<input name="slug" pattern="[a-z0-9-]*" placeholder="naprykład-tak"></label><?php else: ?><p class="muted">Адреса: <code><?= e(page_path($page['slug'])) ?></code></p><?php endif; ?>
    <label class="check wide"><input type="checkbox" name="template"<?= !empty($page['template']) ? ' checked' : '' ?>> Текст зразковий — на сайті показується позначка «Зразок наповнення»</label>
  </div>

  <h2 class="sub">Вміст сторінки</h2>
  <p class="muted small">Блоки йдуть зверху вниз, як на сайті. Стрілками змінюйте порядок. У текстових полях виділіть слово, щоб зробити його жирним або посиланням.</p>
  <textarea name="blocks" id="blocks-json" hidden><?= e(json_encode($page['blocks'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></textarea>
  <script type="application/json" id="editor-config"><?= json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
  <div id="blocks" class="blocks" data-editor></div>

  <div class="savebar">
    <button class="btn" type="submit">Зберегти</button>
    <?php if (!$isNew): ?><span class="muted small">Оновлено: <?= e(ua_date($page['updated'] ?? today())) ?></span><?php endif; ?>
  </div>
</form>
<?php if (!$isNew): ?>
<form method="post" class="danger-zone" data-confirm="Видалити сторінку «<?= e($page['title']) ?>»?">
  <?= csrf_field() ?><input type="hidden" name="do" value="delete">
  <button class="btn btn-danger-ghost" type="submit">Видалити сторінку</button>
</form>
<?php endif; ?>

<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<?php
$h = home_data();
$arts = tile_arts();
$tiles = array_pad($h['tiles'], count($h['tiles']) + 2, ['art' => '', 'url' => '', 'title' => '', 'text' => '', 'tone' => 'blue']);
$btn = static fn(array $list, int $i, string $k): string => e((string) ($list[$i][$k] ?? ''));
$versions = count(store_versions('home.json'));
$headLabels = ['quick' => 'Над плитками', 'news' => 'Над новинами', 'numbers' => 'Над цифрами', 'team' => 'Над адміністрацією', 'clubs' => 'Над гуртками'];
?>
<header class="head">
  <div><h1>Головна сторінка</h1></div>
  <a class="btn btn-ghost" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">Переглянути ↗</a>
</header>
<p class="muted">Тут редагується все, що видно на головній, крім блоків, які збираються самі: новини, документи, цифри, адміністрація й гуртки беруться з відповідних розділів, а смуга «Сьогодні в ліцеї» — з «Налаштувань».</p>
<form method="post" class="edit">
  <?= csrf_field() ?>
  <h2 class="sub">Перший екран</h2>
  <div class="fields">
    <label class="wide">Рядок над заголовком<input name="hero_eyebrow" value="<?= e($h['hero']['eyebrow']) ?>"></label>
    <label class="wide">Заголовок<input name="hero_title" value="<?= e($h['hero']['title']) ?>" required><small class="muted">Слово в [квадратних дужках] буде виділене кольором.</small></label>
    <label class="wide">Текст під заголовком<textarea name="hero_lead" rows="2"><?= e($h['hero']['lead']) ?></textarea></label>
  </div>
  <h3 class="sub3">Кнопки</h3>
  <div class="table-edit">
    <div class="tr th"><span>Напис</span><span>Куди веде (наприклад, /kontakty/)</span></div>
    <?php for ($i = 0; $i < 2; $i++): ?>
      <div class="tr"><input name="hero_btn_label[]" value="<?= $btn($h['hero']['buttons'], $i, 'label') ?>"><input name="hero_btn_url[]" value="<?= $btn($h['hero']['buttons'], $i, 'url') ?>"></div>
    <?php endfor; ?>
  </div>
  <div class="fields">
    <label class="wide">Короткі позначки під кнопками (кожна з нового рядка)<textarea name="hero_chips" rows="3"><?= e(implode("\n", $h['hero']['chips'])) ?></textarea><small class="muted">Кількість учнів і класів додається сама з «Налаштувань».</small></label>
  </div>

  <h2 class="sub">Плитки «Швидко знайти»</h2>
  <p class="muted small">Щоб прибрати плитку, зітріть її назву. Порядок задає число в першій колонці. Велика синя плитка займає місце чотирьох.</p>
  <div class="table-edit is-tiles">
    <div class="tr th"><span>№</span><span>Назва</span><span>Підпис</span><span>Куди веде</span><span>Малюнок</span><span>Вигляд</span></div>
    <?php foreach ($tiles as $i => $t): ?>
      <div class="tr">
        <input name="tile_order[]" type="number" value="<?= $i + 1 ?>" aria-label="Порядок">
        <input name="tile_title[]" value="<?= e($t['title']) ?>" aria-label="Назва">
        <input name="tile_text[]" value="<?= e($t['text']) ?>" aria-label="Підпис">
        <input name="tile_url[]" value="<?= e($t['url']) ?>" aria-label="Адреса">
        <select name="tile_art[]" aria-label="Малюнок"><option value="">без малюнка</option><?php foreach ($arts as $a): ?><option<?= ($t['art'] ?? '') === $a ? ' selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?></select>
        <select name="tile_tone[]" aria-label="Вигляд"><?php foreach (TILE_TONES as $k => $label): ?><option value="<?= $k ?>"<?= ($t['tone'] ?? '') === $k ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
      </div>
    <?php endforeach; ?>
  </div>

  <h2 class="sub">Заголовки блоків</h2>
  <div class="fields">
    <?php foreach ($headLabels as $k => $label): ?><label><?= e($label) ?><input name="head_<?= $k ?>" value="<?= e($h['headings'][$k]) ?>"></label><?php endforeach; ?>
  </div>

  <h2 class="sub">Блок про профільну школу</h2>
  <div class="fields">
    <label class="check wide"><input type="checkbox" name="promo_show"<?= !empty($h['promo']['show']) ? ' checked' : '' ?>> Показувати цей блок на головній</label>
    <label>Рядок над заголовком<input name="promo_eyebrow" value="<?= e($h['promo']['eyebrow']) ?>"></label>
    <label>Заголовок<input name="promo_title" value="<?= e($h['promo']['title']) ?>"></label>
    <label class="wide">Текст<textarea name="promo_text" rows="2"><?= e($h['promo']['text']) ?></textarea></label>
    <label>Напис на кнопці<input name="promo_button" value="<?= e($h['promo']['button']) ?>"></label>
    <label>Куди веде кнопка<input name="promo_url" value="<?= e($h['promo']['url']) ?>"></label>
  </div>
  <p class="muted small">Відлік днів і «що за планом цього місяця» беруться з розділу <a href="<?= e(url('/admin/plan/')) ?>">«План профільної школи»</a>.</p>

  <h2 class="sub">Блок «Прозорість і документи»</h2>
  <div class="fields">
    <label class="wide">Заголовок<input name="docs_title" value="<?= e($h['docs']['title']) ?>"></label>
    <label class="wide">Текст<textarea name="docs_text" rows="3"><?= e($h['docs']['text']) ?></textarea></label>
  </div>

  <h2 class="sub">Блок унизу сторінки</h2>
  <div class="fields">
    <label class="wide">Заголовок<input name="cta_title" value="<?= e($h['cta']['title']) ?>"></label>
    <label class="wide">Текст<textarea name="cta_text" rows="3"><?= e($h['cta']['text']) ?></textarea></label>
  </div>
  <div class="table-edit">
    <div class="tr th"><span>Напис на кнопці</span><span>Куди веде</span></div>
    <?php for ($i = 0; $i < 2; $i++): ?>
      <div class="tr"><input name="cta_btn_label[]" value="<?= $btn($h['cta']['buttons'], $i, 'label') ?>"><input name="cta_btn_url[]" value="<?= $btn($h['cta']['buttons'], $i, 'url') ?>"></div>
    <?php endfor; ?>
  </div>

  <div class="savebar">
    <button class="btn" type="submit">Зберегти головну</button>
    <?php if ($versions): ?><a class="muted small" href="<?= e(url('/admin/istoriia/?f=home.json')) ?>">Попередні версії (<?= $versions ?>)</a><?php endif; ?>
  </div>
</form>
<?php if (is_file(store_path('home.json'))): ?>
<form method="post" class="danger-zone" data-confirm="Повернути головну сторінку до початкового вигляду? Поточний варіант збережеться в «Історії змін».">
  <?= csrf_field() ?><input type="hidden" name="do" value="reset">
  <button class="btn btn-danger-ghost" type="submit">Повернути початковий вигляд</button>
</form>
<?php endif; ?>

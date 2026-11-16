<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<?php
$tx = taxonomies();
$hints = [
    'news' => 'Рубрики, за якими відвідувач фільтрує новини.',
    'documents' => 'Розділи реєстру на сторінці «Документи». Порожні розділи на сайті не показуються.',
    'staff' => 'Групи на сторінці «Кадровий склад».',
];
?>
<header class="head"><h1>Рубрики й розділи</h1></header>
<p class="muted">Назву можна змінити будь-коли — записи лишаться на місці. Видалити можна лише рубрику, якою ніхто не користується. Щоб додати нову, впишіть назву в порожній рядок унизу списку.</p>
<form method="post" class="edit">
  <?= csrf_field() ?>
  <?php foreach (TAX_TITLES as $tax => $title): $rows = $tx[$tax] ?? []; ?>
    <h2 class="sub"><?= e($title) ?></h2>
    <p class="muted small"><?= e($hints[$tax]) ?></p>
    <div class="table-edit is-tax">
      <div class="tr th"><span>№</span><span>Назва</span><span>Записів</span><span>Видалити</span></div>
      <?php foreach ($rows as $i => $r): $used = tax_usage($tax, $r['slug']); $locked = $used > 0 || in_array($r['slug'], TAX_PROTECTED[$tax], true); ?>
        <div class="tr">
          <input name="t_<?= $tax ?>_order[]" type="number" value="<?= $i + 1 ?>" aria-label="Порядок">
          <input name="t_<?= $tax ?>_name[]" value="<?= e($r['name']) ?>" aria-label="Назва" required>
          <span class="cell"><?= $used ?></span>
          <span class="cell"><?php if ($locked): ?><small class="muted"><?= $used ? 'використовується' : 'потрібна сайту' ?></small><?php else: ?><label class="check"><input type="checkbox" name="t_<?= $tax ?>_del[]" value="<?= e($r['slug']) ?>"> так</label><?php endif; ?></span>
          <input type="hidden" name="t_<?= $tax ?>_slug[]" value="<?= e($r['slug']) ?>">
        </div>
      <?php endforeach; ?>
      <?php for ($k = 0; $k < 2; $k++): ?>
        <div class="tr">
          <input name="t_<?= $tax ?>_order[]" type="number" value="<?= count($rows) + $k + 1 ?>" aria-label="Порядок">
          <input name="t_<?= $tax ?>_name[]" value="" placeholder="Нова назва" aria-label="Нова назва">
          <span class="cell muted">—</span><span class="cell"></span>
          <input type="hidden" name="t_<?= $tax ?>_slug[]" value="">
        </div>
      <?php endfor; ?>
    </div>
  <?php endforeach; ?>
  <div class="savebar">
    <button class="btn" type="submit">Зберегти</button>
    <?php if ($n = count(store_versions('taxonomies.json'))): ?><a class="muted small" href="<?= e(url('/admin/istoriia/?f=taxonomies.json')) ?>">Попередні версії (<?= $n ?>)</a><?php endif; ?>
  </div>
</form>

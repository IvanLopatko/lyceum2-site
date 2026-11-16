<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<?php
$plan = profile_plan();
$stages = array_pad($plan['stages'] ?? [], count($plan['stages'] ?? []) + 1, ['name' => '', 'from' => '', 'to' => '', 'label' => '']);
$months = array_pad($plan['months'] ?? [], count($plan['months'] ?? []) + 2, ['month' => '', 'items' => []]);
$lines = static fn(array $items): string => implode("\n", array_map(static fn(array $i): string => $i['what'] . (($i['how'] ?? '') !== '' ? ' | ' . $i['how'] : ''), $items));
?>
<header class="head">
  <div><h1>План профільної школи</h1></div>
  <a class="btn btn-ghost" href="<?= e(url('/navchannia/profilna-shkola/')) ?>" target="_blank" rel="noopener">Переглянути ↗</a>
</header>
<p class="muted">Звідси беруться відлік днів до старту, етапи й помісячний план на сторінці «Профільна школа» та рядок «за планом цього місяця» на головній. Поточний місяць підсвічується сам.</p>
<form method="post" class="edit">
  <?= csrf_field() ?>
  <div class="fields">
    <label>Дата старту академічного ліцею<input type="date" name="start" value="<?= e((string) ($plan['start'] ?? '2027-09-01')) ?>"></label>
    <label class="wide">Джерело плану (документ, на який він спирається)<input name="source" value="<?= e((string) ($plan['source'] ?? '')) ?>"></label>
  </div>

  <h2 class="sub">Етапи</h2>
  <p class="muted small">Місяці вказуйте як рік-місяць: 2026-09. Щоб прибрати етап, зітріть його назву.</p>
  <div class="table-edit is-4">
    <div class="tr th"><span>Назва етапу</span><span>З місяця</span><span>До місяця</span><span>Підпис</span></div>
    <?php foreach ($stages as $s): ?>
      <div class="tr"><input name="st_name[]" value="<?= e($s['name']) ?>"><input name="st_from[]" value="<?= e($s['from']) ?>" placeholder="2026-09" pattern="\d{4}-\d{2}"><input name="st_to[]" value="<?= e($s['to']) ?>" placeholder="2026-11" pattern="\d{4}-\d{2}"><input name="st_label[]" value="<?= e($s['label']) ?>"></div>
    <?php endforeach; ?>
  </div>

  <h2 class="sub">Заходи по місяцях</h2>
  <p class="muted small">Кожен захід — з нового рядка. Після вертикальної риски можна вказати, як він проводиться: <code>Батьківські збори 9-х класів | актова зала</code>. Щоб прибрати місяць, зітріть його дату.</p>
  <?php foreach ($months as $m): ?>
    <div class="fields is-group">
      <label>Місяць<input name="m_month[]" value="<?= e($m['month']) ?>" placeholder="2027-01" pattern="\d{4}-\d{2}"></label>
      <label class="wide">Заходи<textarea name="m_items[]" rows="<?= max(3, count($m['items']) + 1) ?>"><?= e($lines($m['items'])) ?></textarea></label>
    </div>
  <?php endforeach; ?>
  <div class="savebar">
    <button class="btn" type="submit">Зберегти план</button>
    <?php if ($n = count(store_versions('profile-plan.json'))): ?><a class="muted small" href="<?= e(url('/admin/istoriia/?f=profile-plan.json')) ?>">Попередні версії (<?= $n ?>)</a><?php endif; ?>
  </div>
</form>

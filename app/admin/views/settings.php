<?php $s = settings(); $f = static fn(string $g, string $k): string => e((string) ($s[$g][$k] ?? '')); ?>
<header class="head"><h1>Налаштування</h1></header>
<form method="post" class="edit">
  <?= csrf_field() ?>
  <h2 class="sub">Ліцей</h2>
  <div class="fields">
    <label>Назва<input name="site_name" value="<?= $f('site', 'name') ?>"></label>
    <label>Девіз<input name="site_motto" value="<?= $f('site', 'motto') ?>"></label>
    <label class="wide">Повна назва<input name="site_full_name" value="<?= $f('site', 'full_name') ?>"></label>
    <label>Рік заснування<input name="site_founded" value="<?= $f('site', 'founded') ?>"></label>
    <label class="check"><input type="checkbox" name="site_demo_bar"<?= !empty($s['site']['demo_bar']) ? ' checked' : '' ?>> Показувати смугу «Зразок сайту», поки є зразкові дані</label>
  </div>
  <h2 class="sub">Контакти</h2>
  <div class="fields">
    <label>Телефон приймальні<input name="contacts_phone" value="<?= $f('contacts', 'phone') ?>"></label>
    <label>Інші телефони<input name="contacts_phone_extra" value="<?= $f('contacts', 'phone_extra') ?>"></label>
    <label>Години приймальні<input name="contacts_hours" value="<?= $f('contacts', 'hours') ?>"></label>
    <label>Особистий прийом громадян<input name="contacts_reception" value="<?= $f('contacts', 'reception') ?>"></label>
    <label>Відповідальний за публічну інформацію<input name="contacts_public_info" value="<?= $f('contacts', 'public_info') ?>"></label>
    <label>Електронна пошта<input type="email" name="contacts_email" value="<?= $f('contacts', 'email') ?>"></label>
    <label class="wide">Facebook<input type="url" name="contacts_facebook" value="<?= $f('contacts', 'facebook') ?>"></label>
  </div>
  <h3 class="sub3">Корпуси</h3>
  <div class="table-edit">
    <?php foreach (array_pad($s['contacts']['buildings'] ?? [], 4, ['name' => '', 'address' => '']) as $b): ?>
      <div class="tr"><input name="b_name[]" value="<?= e($b['name']) ?>" placeholder="Назва корпусу"><input name="b_address[]" value="<?= e($b['address']) ?>" placeholder="Адреса"></div>
    <?php endforeach; ?>
  </div>
  <h2 class="sub">Ліцей у цифрах</h2>
  <p class="muted small">Поруч із кожною цифрою — дата, станом на яку вона правдива.</p>
  <div class="fields">
    <?php foreach (['pupils' => 'Учнів', 'pupils_date' => '↳ станом на', 'classes' => 'Класів', 'classes_date' => '↳ станом на', 'staff' => 'Працівників', 'staff_date' => '↳ станом на', 'shelters' => 'Укриттів', 'shelters_note' => '↳ уточнення (місткість)'] as $k => $l): ?>
      <label><?= e($l) ?><input name="numbers_<?= $k ?>" value="<?= $f('numbers', $k) ?>"></label>
    <?php endforeach; ?>
  </div>
  <h2 class="sub">Смуга «Сьогодні в ліцеї» на головній</h2>
  <div class="fields">
    <?php foreach (['format' => 'Навчання', 'format_note' => '↳ уточнення', 'alarm' => 'Під час тривоги', 'alarm_note' => '↳ уточнення', 'food' => 'Харчування', 'food_note' => '↳ уточнення', 'office' => 'Приймальня', 'office_note' => '↳ уточнення'] as $k => $l): ?>
      <label><?= e($l) ?><input name="today_<?= $k ?>" value="<?= $f('today', $k) ?>"></label>
    <?php endforeach; ?>
  </div>
  <h2 class="sub">Хто за що відповідає</h2>
  <div class="table-edit is-4">
    <div class="tr th"><span>Значок</span><span>Напрям</span><span>Хто</span><span>Посада</span></div>
    <?php foreach (array_pad($s['responsible'] ?? [], count($s['responsible'] ?? []) + 2, ['icon' => 'doc', 'area' => '', 'who' => '', 'position' => '']) as $r): ?>
      <div class="tr"><select name="r_icon[]"><?php foreach (['shield', 'key', 'food', 'doc', 'compass', 'laptop', 'heart', 'users', 'phone', 'book', 'bus'] as $ic): ?><option<?= $r['icon'] === $ic ? ' selected' : '' ?>><?= $ic ?></option><?php endforeach; ?></select><input name="r_area[]" value="<?= e($r['area']) ?>"><input name="r_who[]" value="<?= e($r['who']) ?>"><input name="r_position[]" value="<?= e($r['position']) ?>"></div>
    <?php endforeach; ?>
  </div>
  <div class="savebar"><button class="btn" type="submit">Зберегти налаштування</button></div>
</form>

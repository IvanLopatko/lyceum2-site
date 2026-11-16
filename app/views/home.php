<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<?php
$n = setting('numbers', []);
$news = news_all();
$lead = $news[0] ?? null;
$rest = array_slice($news, 1, 4);
$plan = profile_plan();
$nowItems = [];
foreach ($plan['months'] as $m) {
    if ($m['month'] === date('Y-m')) {
        $nowItems = $m['items'];
    }
}
$days = days_to($plan['start'] ?? '2027-09-01');
$pupils = (int) preg_replace('~\D~', '', (string) ($n['pupils'] ?? '0'));
$classes = (int) preg_replace('~\D~', '', (string) ($n['classes'] ?? '0'));
$h = home_data();
$tiles = $h['tiles'];
?>
<section class="hero">
  <div class="wrap hero-in">
    <div class="hero-txt">
      <p class="eyebrow hero-eyebrow"><?= e($h['hero']['eyebrow']) ?></p>
      <h1><?= hl_title($h['hero']['title']) ?></h1>
      <p class="lead"><?= e($h['hero']['lead']) ?></p>
      <div class="actions">
        <?php foreach ($h['hero']['buttons'] as $i => $btn): ?><a class="btn<?= $i ? ' btn-ghost' : '' ?>" href="<?= e(url(safe_url((string) $btn['url']))) ?>"><?= e($btn['label']) ?></a>
        <?php endforeach; ?>
      </div>
      <ul class="chips" aria-label="Коротко про ліцей">
        <?php if ($pupils): ?><li><?= $pupils ?> <?= plural($pupils, 'учень', 'учні', 'учнів') ?></li><?php endif; ?>
        <?php if ($classes): ?><li><?= $classes ?> <?= plural($classes, 'клас', 'класи', 'класів') ?></li><?php endif; ?>
        <?php foreach ($h['hero']['chips'] as $chip): ?><li><?= e($chip) ?></li><?php endforeach; ?>
      </ul>
    </div>
    <div class="hero-art" aria-hidden="true">
      <span class="hero-sun"></span>
      <span class="hero-emb"><img src="<?= e(asset('/uploads/emblema.png')) ?>" alt="" width="240" height="240"><span class="hero-motto">«<?= e(setting('site.motto')) ?>»</span></span>
      <svg class="hero-wave" viewBox="0 0 1200 62" preserveAspectRatio="none">
        <path d="M0 34 C150 6 300 62 450 34 S750 6 900 34 S1100 62 1200 34 V62 H0z" fill="#BFDBFF" opacity=".7"/>
        <path d="M0 46 C200 24 350 66 550 46 S850 24 1000 46 S1150 64 1200 50 V62 H0z" fill="#8FC0FF" opacity=".55"/>
      </svg>
    </div>
  </div>
  <div class="wrap"><?= component_today() ?></div>
</section>

<section class="sec" aria-labelledby="h-quick">
  <div class="wrap">
    <div class="sec-head"><h2 id="h-quick"><?= e($h['headings']['quick']) ?></h2></div>
    <ul class="bento">
      <?php foreach ($tiles as $t): ?>
        <li class="is-<?= e(isset(TILE_TONES[$t['tone'] ?? '']) ? $t['tone'] : 'blue') ?>"><a href="<?= e(url(safe_url((string) $t['url']))) ?>"><?php if (in_array($t['art'] ?? '', tile_arts(), true)): ?><img class="bento-art" src="<?= e(url('/assets/img/tiles/' . $t['art'] . '.svg')) ?>" alt="" width="200" height="150"><?php endif; ?><b><?= e($t['title']) ?></b><span><?= e($t['text']) ?></span></a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="sec sec-soft" aria-labelledby="h-news">
  <div class="wrap">
    <div class="sec-head"><h2 id="h-news"><?= e($h['headings']['news']) ?></h2><a class="more" href="<?= e(url('/novyny/')) ?>">Усі новини</a></div>
    <?php if ($lead): ?>
    <div class="news-home">
      <a class="news-lead" href="<?= e(url(news_url($lead))) ?>">
        <?php if ($cv = news_cover($lead)): ?><img src="<?= e(thumb_url($cv)) ?>" alt="" loading="lazy" width="720" height="480"><?php else: ?><span class="news-ph" aria-hidden="true"></span><?php endif; ?>
        <span class="news-body"><span class="tag is-<?= e($lead['categories'][0] ?? 'zhyttia') ?>"><?= e(tax_name('news', $lead['categories'][0] ?? 'zhyttia')) ?></span><b><?= e($lead['title']) ?></b><span class="news-ex"><?= e(excerpt(implode(' ', $lead['text']), 180)) ?></span><time datetime="<?= e(substr($lead['date'], 0, 10)) ?>"><?= e(ua_date($lead['date'])) ?></time></span>
      </a>
      <ul class="news-list">
        <?php foreach ($rest as $r): ?>
          <li><a href="<?= e(url(news_url($r))) ?>"><span class="tag is-<?= e($r['categories'][0] ?? 'zhyttia') ?>"><?= e(tax_name('news', $r['categories'][0] ?? 'zhyttia')) ?></span><b><?= e($r['title']) ?></b><time datetime="<?= e(substr($r['date'], 0, 10)) ?>"><?= e(ua_date($r['date'])) ?></time></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php if (!empty($h['promo']['show'])): ?>
<section class="sec" aria-labelledby="h-promo">
  <div class="wrap">
    <div class="promo">
      <div class="promo-txt">
        <p class="eyebrow"><?= e($h['promo']['eyebrow']) ?></p>
        <h2 id="h-promo"><?= e($h['promo']['title']) ?></h2>
        <p><?= e($h['promo']['text']) ?></p>
        <?php if ($nowItems): ?><p class="promo-now"><span><?= e(UA_MONTHS[(int) date('n')]) ?> за планом</span><?= e($nowItems[0]['what']) ?></p><?php endif; ?>
        <a class="btn btn-light" href="<?= e(url(safe_url((string) $h['promo']['url']))) ?>"><?= e($h['promo']['button']) ?></a>
      </div>
      <div class="promo-cd" aria-hidden="true"><b data-count><?= $days ?></b><span><?= plural($days, 'день', 'дні', 'днів') ?> до старту</span></div>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="sec" aria-labelledby="h-docs">
  <div class="wrap two-col">
    <div class="sec-head is-col">
      <h2 id="h-docs"><?= e($h['docs']['title']) ?></h2>
      <p><?= e($h['docs']['text']) ?></p>
      <a class="more" href="<?= e(url('/dokumenty/')) ?>">Увесь реєстр документів</a>
    </div>
    <?= component_documents(['limit' => 6, 'real' => '1']) ?>
  </div>
</section>

<section class="sec sec-soft" aria-labelledby="h-about">
  <div class="wrap">
    <div class="sec-head"><h2 id="h-about"><?= e($h['headings']['numbers']) ?></h2></div>
    <?= component_numbers() ?>
    <div class="sec-head sec-head-sub"><h3><?= e($h['headings']['team']) ?></h3><a class="more" href="<?= e(url('/kadrovyj-sklad/')) ?>">Увесь кадровий склад</a></div>
    <?= component_team(['group' => 'administratsiya', 'limit' => 5]) ?>
  </div>
</section>

<section class="sec" aria-labelledby="h-clubs">
  <div class="wrap">
    <div class="sec-head"><h2 id="h-clubs"><?= e($h['headings']['clubs']) ?></h2><a class="more" href="<?= e(url('/gurtky/')) ?>">Усі гуртки</a></div>
    <?= component_club_list(['limit' => 3]) ?>
  </div>
</section>

<section class="sec" aria-labelledby="h-cta">
  <div class="wrap">
    <div class="cta">
      <div>
        <h2 id="h-cta"><?= e($h['cta']['title']) ?></h2>
        <p><?= e($h['cta']['text']) ?></p>
      </div>
      <div class="actions">
        <?php foreach ($h['cta']['buttons'] as $i => $btn): ?><a class="btn <?= $i ? 'btn-outline-light' : 'btn-light' ?>" href="<?= e(url(safe_url((string) $btn['url']))) ?>"><?= e($btn['label']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

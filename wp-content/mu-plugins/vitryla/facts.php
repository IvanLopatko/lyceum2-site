<?php
/**
 * Фактичні дані від директорки (анкета 05.11.2026) і документи ліцею.
 *
 * Що робить один раз (за номером версії):
 *  — записує контакти, цифри і смугу «Сьогодні» в «Дані ліцею»;
 *  — завантажує PDF документів у медіатеку й створює записи реєстру;
 *  — уточнює склад адміністрації і відповідальних;
 *  — переписує сторінки, для яких уже є справжні дані:
 *    «Профільна школа», «Безпека і укриття», «Вакансії», «Освітні програми»;
 *  — додає сторінки «Медичне обслуговування» і «Підвіз учнів».
 *
 * Модуль не створює вигаданих даних: усе, чого немає в документах
 * або відповідях директорки, позначено як «уточнюється».
 *
 * @package vitryla
 */

defined( 'ABSPATH' ) || exit;

const VITRYLA_FACTS_VERSION = 2; // підняти номер = переписати 4 сторінки заново (ручні правки в них зітруться)

/* ═════════ Довідники ═════════ */

/**
 * Хто за що відповідає — з анкети директорки.
 *
 * @return array<int,array{0:string,1:string,2:string,3:string}> іконка, напрям, ПІБ, посада
 */
function vitryla_responsible(): array {
	return array(
		array( 'shield', 'Укриття та безпека', 'Кліменко Наталя Михайлівна', 'директор ліцею' ),
		array( 'key', 'Доступ до публічної інформації', 'Кліменко Наталя Михайлівна', 'директор ліцею' ),
		array( 'food', 'Харчування', 'Ковальова Олена Сергіївна', 'заступник директора з виховної роботи' ),
		array( 'doc', 'Накази та діловодство', 'Новак Світлана Олександрівна', 'секретар-друкарка' ),
		array( 'compass', 'Перехід до профільної школи', 'Кирилюк Юлія Миколаївна', 'заступник директора з навчально-виховної роботи' ),
		array( 'laptop', 'Сайт ліцею', 'Кирилюк Юлія Миколаївна, Лопатко Іван Сергійович', 'заступник директора з НВР; інженер-технік' ),
		array( 'heart', 'Медичне обслуговування', 'Мітіна Галина Олегівна', 'медична сестра, корпус №1, щодня 08:00–16:00' ),
	);
}

/**
 * План комунікаційних заходів щодо старшої профільної школи на 2026–2027 роки
 * (затверджено директором у вересні 2026) + етап 4 комунікаційної стратегії.
 *
 * @return array<string,array<int,array{0:string,1:string}>> 'РРРР-ММ' => [ [захід, як і де] ]
 */
function vitryla_profile_plan(): array {
	return array(
		'2026-09' => array(
			array( 'Розроблення комунікаційної стратегії ліцею', 'робоча нарада адміністрації' ),
			array( 'Анкетування учнів 9-х класів: потреби, очікування, обізнаність', 'Google Форми, класні чати' ),
			array( 'Інформування педагогічного колективу про підготовку до академічного ліцею', 'педагогічна рада' ),
		),
		'2026-10' => array(
			array( 'Інформаційні години для учнів 9-х класів про старшу профільну школу', 'класні години, презентації' ),
			array( 'Батьківські збори «Обираємо майбутнє разом: можливості старшої профільної школи»', 'очні зустрічі, батьківські чати' ),
			array( 'Допис для громади про підготовку ліцею до роботи як академічного ліцею', 'Facebook, сайт ліцею' ),
		),
		'2026-11' => array(
			array( 'Інфографіка: що таке старша профільна школа, її мета й переваги', 'Facebook, сайт, інформаційні стенди' ),
			array( 'Онлайн-опитування освітніх інтересів і професійних намірів учнів', 'Google Форми, класні години' ),
			array( 'Збір запитань і пропозицій батьків', 'батьківські чати, особисті консультації' ),
		),
		'2026-12' => array(
			array( 'Аналіз результатів опитування учнів', 'нарада адміністрації, педагогічна рада' ),
			array( 'Картки «Запитання та відповіді про старшу профільну школу»', 'Facebook, сайт, батьківські чати' ),
			array( 'Робоча зустріч педагогів щодо подальших кроків', 'педагогічна нарада' ),
			array( 'Класні години про профільне навчання й усвідомлений вибір', 'класні години, учнівські чати' ),
		),
		'2027-01' => array(
			array( 'Профорієнтаційні заходи: здібності, інтереси, схильності', 'тренінги, вправи, консультації' ),
		),
		'2027-02' => array(
			array( 'Тиждень «Професії майбутнього: обираємо свій шлях»', 'класні години, Facebook, сайт' ),
			array( 'Обговорення результатів опитування з батьками', 'батьківські збори, круглий стіл' ),
			array( 'Матеріали про поглиблене вивчення предметів', 'Facebook, сайт' ),
		),
		'2027-03' => array(
			array( 'Інформування про підтверджені профілі навчання', 'батьківські збори, сайт, Facebook' ),
			array( 'Повторне опитування: як учні й батьки розуміють зміни', 'Google Форми, чати' ),
			array( 'Відкрите обговорення запитань і пропозицій', 'очні зустрічі, онлайн-форми' ),
		),
		'2027-04' => array(
			array( 'Інформаційна кампанія «Біляївський ліцей №2 — академічний ліцей: нові можливості для учнів»', 'Facebook, сайт' ),
			array( 'День відкритих дверей для учнів і батьків', 'приміщення ліцею, сайт' ),
			array( 'Консультації щодо вибору освітньої траєкторії', 'індивідуальні та групові' ),
		),
		'2027-05' => array(
			array( 'Пам’ятка «Що потрібно знати про навчання в академічному ліцеї?»', 'Facebook, сайт, батьківські чати' ),
			array( 'Зустрічі про умови навчання, підтверджені профілі й подальші дії', 'батьківські збори, консультації' ),
			array( 'Пропозиції учнів, батьків і педагогів щодо інформаційного супроводу', 'анкетування' ),
		),
		'2027-06' => array(
			array( 'Підсумки комунікаційної роботи за навчальний рік', 'нарада адміністрації' ),
			array( 'Підсумковий допис і фотозвіт про підготовку ліцею', 'Facebook, сайт' ),
		),
		'2027-07' => array(
			array( 'Оновлені матеріали щодо навчання у 2027/2028 році', 'сайт, Facebook' ),
			array( 'Відповіді на запитання батьків щодо початку навчання', 'телефон, електронні звернення' ),
		),
		'2027-08' => array(
			array( 'Кампанія «Готуємося до нового етапу навчання»', 'дописи, пам’ятки, відеозвернення' ),
			array( 'Зустрічі з учнями й батьками про організацію освітнього процесу', 'очні зустрічі, сайт' ),
			array( 'Нарада педагогів щодо готовності до роботи академічного ліцею', 'педагогічна нарада' ),
		),
		'2027-09' => array(
			array( 'Старт навчання: адаптаційні зустрічі для старшокласників', 'етап 4 стратегії' ),
			array( 'Збір зворотного зв’язку після початку навчання', 'етап 4 стратегії' ),
		),
	);
}

/**
 * Етапи комунікаційної стратегії.
 *
 * @return array<int,array{0:string,1:string,2:string,3:string}> назва, з, по, підпис
 */
function vitryla_profile_stages(): array {
	return array(
		array( 'Інформаційно-підготовчий', '2026-09', '2026-11', 'вересень — листопад 2026' ),
		array( 'Діалог і залучення', '2026-12', '2027-02', 'грудень 2026 — лютий 2027' ),
		array( 'Уточнення та підготовка', '2027-03', '2027-06', 'березень — червень 2027' ),
		array( 'Старт і супровід змін', '2027-07', '2027-09', 'липень — вересень 2027' ),
	);
}

function vitryla_month_name( string $ym, bool $with_year = true ): string {
	$names = array( '', 'Січень', 'Лютий', 'Березень', 'Квітень', 'Травень', 'Червень', 'Липень', 'Серпень', 'Вересень', 'Жовтень', 'Листопад', 'Грудень' );
	[ $y, $m ] = array_map( 'intval', explode( '-', $ym ) );
	return $names[ $m ] . ( $with_year ? ' ' . $y : '' );
}

/** Скільки днів до старту академічного ліцею. */
function vitryla_days_to_lyceum(): int {
	$start = new DateTimeImmutable( '2027-09-01', wp_timezone() );
	$today = new DateTimeImmutable( current_time( 'Y-m-d' ), wp_timezone() );
	return max( 0, (int) $today->diff( $start )->format( '%r%a' ) );
}

function vitryla_plural( int $n, string $one, string $few, string $many ): string {
	$m10 = $n % 10;
	$m100 = $n % 100;
	if ( 1 === $m10 && 11 !== $m100 ) {
		return $one;
	}
	if ( $m10 >= 2 && $m10 <= 4 && ( $m100 < 12 || $m100 > 14 ) ) {
		return $few;
	}
	return $many;
}

/* ═════════ Шорткоди ═════════ */

/* Відлік до 1 вересня 2027 */
add_shortcode( 'vit_countdown', function (): string {
	$d = vitryla_days_to_lyceum();
	return '<div class="vit-countdown" role="group" aria-label="Відлік до старту академічного ліцею">'
		. '<span class="vit-cd-n" data-count="' . $d . '">' . $d . '</span>'
		. '<span class="vit-cd-l">' . vitryla_plural( $d, 'день', 'дні', 'днів' ) . ' до 1 вересня 2027</span>'
		. '<span class="vit-cd-s">старт академічного ліцею</span>'
		. '</div>';
} );

/* Дорожня карта з автоматичним «зараз» */
add_shortcode( 'vit_roadmap', function (): string {
	$now    = current_time( 'Y-m' );
	$plan   = vitryla_profile_plan();
	$stages = vitryla_profile_stages();

	// Смуга етапів.
	$out = '<div class="vit-roadmap"><ol class="vit-rm-stages">';
	foreach ( $stages as $i => [ $name, $from, $to, $label ] ) {
		$state = $now > $to ? 'is-past' : ( $now >= $from ? 'is-now' : 'is-next' );
		$out  .= '<li class="' . $state . '"><span class="vit-rm-sn">Етап ' . ( $i + 1 ) . '</span><b>' . esc_html( $name ) . '</b><span class="vit-rm-sl">' . esc_html( $label ) . '</span>'
			. ( 'is-now' === $state ? '<span class="vit-rm-badge">ми тут</span>' : '' ) . '</li>';
	}
	$out .= '</ol>';

	// Місяці — горизонтальна стрічка.
	$out .= '<div class="vit-rm-track" tabindex="0" role="region" aria-label="План заходів по місяцях. Гортайте вбік.">'
		. '<ol class="vit-rm-months">';
	foreach ( $plan as $ym => $items ) {
		$state = $ym < $now ? 'is-past' : ( $ym === $now ? 'is-now' : 'is-next' );
		$out  .= '<li class="vit-rm-m ' . $state . '"' . ( 'is-now' === $state ? ' aria-current="date"' : '' ) . '>'
			. '<div class="vit-rm-mh"><span class="vit-rm-dot" aria-hidden="true"></span><b>' . esc_html( vitryla_month_name( $ym ) ) . '</b>'
			. ( 'is-now' === $state ? '<span class="vit-rm-badge">зараз</span>' : '' ) . '</div><ul>';
		foreach ( $items as [ $what, $how ] ) {
			$out .= '<li>' . esc_html( $what ) . '<small>' . esc_html( $how ) . '</small></li>';
		}
		$out .= '</ul>';
		// Що вже відбулося цього місяця — дописи з рубрики «Профільна школа».
		[ $y, $m ] = array_map( 'intval', explode( '-', $ym ) );
		$done      = get_posts( array(
			'post_type'      => 'post',
			'category_name'  => 'profilna-shkola',
			'posts_per_page' => 4,
			'date_query'     => array( array( 'year' => $y, 'month' => $m ) ),
		) );
		if ( $done ) {
			$out .= '<div class="vit-rm-done"><span>Відбулося</span>';
			foreach ( $done as $dp ) {
				$out .= '<a href="' . esc_url( (string) get_permalink( $dp ) ) . '">' . esc_html( get_the_date( 'j.m', $dp ) . ' · ' . $dp->post_title ) . '</a>';
			}
			$out .= '</div>';
		}
		$out .= '</li>';
	}
	$out .= '</ol></div>';
	$out .= '<p class="vit-rm-note">Джерело: План комунікаційних заходів щодо впровадження старшої профільної школи на 2026–2027 роки. Поточний місяць підсвічується автоматично.</p></div>';
	return $out;
} );

/* Банер на головній */
add_shortcode( 'vit_profile_promo', function (): string {
	$page = vitryla_page_by_slug( 'profilna-shkola' );
	$url  = $page ? get_permalink( $page ) : '/navchannia/profilna-shkola/';
	$now  = current_time( 'Y-m' );
	$plan = vitryla_profile_plan();
	$cur  = $plan[ $now ] ?? null;
	$d    = vitryla_days_to_lyceum();

	return '<section class="vit-promo" aria-labelledby="vit-promo-h">'
		. '<div class="vit-promo-txt">'
		. '<p class="vit-promo-eyebrow">Старша профільна школа</p>'
		. '<h2 id="vit-promo-h">Академічний ліцей з 1 вересня 2027 року</h2>'
		. '<p>Що зміниться для нинішніх дев’ятикласників, як обрати профіль і де поставити запитання.</p>'
		. ( $cur ? '<p class="vit-promo-now"><span>' . esc_html( vitryla_month_name( $now, false ) ) . ' за планом</span>' . esc_html( $cur[0][0] ) . '</p>' : '' )
		. '<a class="vit-promo-btn" href="' . esc_url( (string) $url ) . '">Усе про профільну школу</a>'
		. '</div>'
		. '<div class="vit-promo-cd" aria-hidden="true"><b data-count="' . $d . '">' . $d . '</b><span>' . vitryla_plural( $d, 'день', 'дні', 'днів' ) . ' до старту</span></div>'
		. '</section>';
} );

/* Дописи з рубрики */
add_shortcode( 'vit_posts', function ( $atts ): string {
	$a = shortcode_atts( array( 'cat' => '', 'limit' => 6, 'empty' => 'Дописів поки немає.' ), $atts, 'vit_posts' );
	$posts = get_posts( array( 'post_type' => 'post', 'posts_per_page' => (int) $a['limit'], 'category_name' => $a['cat'] ) );
	if ( ! $posts ) {
		return '<p class="vit-empty">' . esc_html( $a['empty'] ) . '</p>';
	}
	$out = '<ul class="vit-postlist">';
	foreach ( $posts as $p ) {
		$out .= sprintf(
			'<li class="vit-glow"><a href="%s"><span class="vit-pl-d">%s</span><span class="vit-pl-t">%s</span></a></li>',
			esc_url( (string) get_permalink( $p ) ),
			esc_html( get_the_date( 'j F Y', $p ) ),
			esc_html( $p->post_title )
		);
	}
	return $out . '</ul>';
} );

/* Хто за що відповідає */
add_shortcode( 'vit_responsible', function (): string {
	$out = '<ul class="vit-resp">';
	foreach ( vitryla_responsible() as [ $ic, $area, $who, $pos ] ) {
		$out .= '<li><span class="vit-ic vit-ico-' . esc_attr( $ic ) . '" aria-hidden="true"></span>'
			. '<span class="vit-resp-a">' . esc_html( $area ) . '</span>'
			. '<span class="vit-resp-w">' . esc_html( $who ) . '</span>'
			. '<span class="vit-resp-p">' . esc_html( $pos ) . '</span></li>';
	}
	return $out . '</ul>';
} );

/* Тривога: дві зміни, контрольний час */
add_shortcode( 'vit_alarm_shifts', function (): string {
	$rows = array(
		array( 'is-blue', 'Перша зміна', '07:30', '09:00', 'Якщо тривога триває до 07:30 — учні залишаються вдома й не вирушають до ліцею.', 'Якщо тривога триває до 09:00 — навчання першої зміни переходить у дистанційний формат.' ),
		array( 'is-sun', 'Друга зміна', '11:30', '13:00', 'Якщо тривога триває до 11:30 — учні залишаються вдома. Початок занять другої зміни — о 12:00, якщо адміністрація не повідомила інше.', 'Якщо тривога триває до 13:00 — навчання другої зміни переходить у дистанційний формат.' ),
	);
	$out = '<div class="vit-shifts">';
	foreach ( $rows as [ $tone, $name, $home, $ctrl, $t1, $t2 ] ) {
		$out .= '<div class="vit-shift ' . $tone . '"><h3>' . esc_html( $name ) . '</h3>'
			. '<div class="vit-shift-row"><b>' . $home . '</b><p>' . esc_html( $t1 ) . '</p></div>'
			. '<div class="vit-shift-row is-ctrl"><b>' . $ctrl . '</b><p><strong>Контрольний час.</strong> ' . esc_html( $t2 ) . '</p></div></div>';
	}
	return $out . '</div>';
} );

/* ═════════ Заповнення ═════════ */

add_action( 'init', 'vitryla_apply_facts', 99 );

/**
 * Створює вкладення з файлу в uploads/vitryla/docs.
 */
function vitryla_attach_pdf( string $file, string $title ): int {
	$path = WP_CONTENT_DIR . '/uploads/vitryla/docs/' . $file;
	if ( ! file_exists( $path ) ) {
		return 0;
	}
	$existing = get_posts( array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'meta_key'       => '_wp_attached_file',
		'meta_value'     => 'vitryla/docs/' . $file,
		'posts_per_page' => 1,
		'fields'         => 'ids',
	) );
	if ( $existing ) {
		return (int) $existing[0];
	}
	$id = wp_insert_attachment( array(
		'post_mime_type' => 'application/pdf',
		'post_title'     => $title,
		'post_status'    => 'inherit',
		'guid'           => content_url( 'uploads/vitryla/docs/' . $file ),
	), $path );
	return is_wp_error( $id ) ? 0 : (int) $id;
}

/**
 * Запис у реєстрі документів (справжній — без позначки vit_demo).
 */
function vitryla_real_doc( string $title, string $section, string $file, string $approved, string $number = '', string $published = '' ): int {
	$found = get_posts( array( 'post_type' => 'vit_document', 'title' => $title, 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids' ) );
	$id    = $found ? (int) $found[0] : (int) wp_insert_post( array( 'post_type' => 'vit_document', 'post_status' => 'publish', 'post_title' => $title ) );
	if ( ! $id ) {
		return 0;
	}
	delete_post_meta( $id, 'vit_demo' );
	update_post_meta( $id, 'vit_approved', $approved );
	update_post_meta( $id, 'vit_published', $published ?: current_time( 'Y-m-d' ) );
	update_post_meta( $id, 'vit_valid', 'current' );
	if ( $number ) {
		update_post_meta( $id, 'vit_number', $number );
	}
	$att = vitryla_attach_pdf( $file, $title );
	if ( $att ) {
		update_post_meta( $id, 'vit_file', $att );
		wp_update_post( array( 'ID' => $att, 'post_parent' => $id ) );
	}
	$term = get_term_by( 'slug', $section, 'vit_doc_section' );
	if ( $term ) {
		wp_set_post_terms( $id, array( $term->term_id ), 'vit_doc_section' );
	}
	return $id;
}

/**
 * Співробітник адміністрації або служби (справжній запис).
 */
function vitryla_real_staff( string $name, string $pos, string $group, int $order, string $subj = '' ): void {
	$found = get_posts( array( 'post_type' => 'vit_staff', 'title' => $name, 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids' ) );
	$id    = $found ? (int) $found[0] : (int) wp_insert_post( array( 'post_type' => 'vit_staff', 'post_status' => 'publish', 'post_title' => $name ) );
	if ( ! $id ) {
		return;
	}
	delete_post_meta( $id, 'vit_demo' );
	wp_update_post( array( 'ID' => $id, 'menu_order' => $order ) );
	update_post_meta( $id, 'vit_position', $pos );
	if ( $subj ) {
		update_post_meta( $id, 'vit_subjects', $subj );
	}
	$term = get_term_by( 'slug', $group, 'vit_staff_group' );
	if ( $term ) {
		wp_set_post_terms( $id, array( $term->term_id ), 'vit_staff_group' );
	}
}

/**
 * Заголовок із якорем для навігації сторінкою.
 */
function vf_h( string $t, string $id, int $l = 2 ): string {
	$a = $l === 2 ? array( 'anchor' => $id ) : array( 'level' => $l, 'anchor' => $id );
	return '<!-- wp:heading ' . wp_json_encode( $a ) . " -->\n<h$l class=\"wp-block-heading\" id=\"$id\">$t</h$l>\n<!-- /wp:heading -->\n\n";
}

/**
 * Записує готовий текст сторінки й знімає позначку «шаблонний».
 */
function vitryla_put_page( string $slug, string $body, string $lead = '', string $title = '' ): void {
	$page = vitryla_page_by_slug( $slug );
	if ( ! $page ) {
		return;
	}
	$upd = array( 'ID' => $page->ID, 'post_content' => wp_slash( $body ) );
	if ( $lead ) {
		$upd['post_excerpt'] = $lead;
	}
	if ( $title ) {
		$upd['post_title'] = $title;
	}
	wp_update_post( $upd );
	delete_post_meta( $page->ID, 'vit_template' );
}

/**
 * Нова сторінка в розділі (якщо ще немає).
 */
function vitryla_add_page( string $parent_slug, string $slug, string $title, string $lead, string $body, bool $template, int $order ): void {
	if ( vitryla_page_by_slug( $slug ) ) {
		return;
	}
	$parent = vitryla_page_by_slug( $parent_slug );
	$id     = wp_insert_post( array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_name'    => $slug,
		'post_title'   => $title,
		'post_excerpt' => $lead,
		'post_content' => wp_slash( $body ),
		'post_parent'  => $parent ? $parent->ID : 0,
		'menu_order'   => $order,
	) );
	if ( $id && ! is_wp_error( $id ) && $template ) {
		update_post_meta( $id, 'vit_template', 1 );
	}
}

function vitryla_apply_facts(): void {
	if ( (int) get_option( 'vitryla_facts_version' ) >= VITRYLA_FACTS_VERSION ) {
		return;
	}
	if ( ! function_exists( 'vitryla_page_by_slug' ) || ! function_exists( 'vb_p' ) || ! taxonomy_exists( 'vit_doc_section' ) ) {
		return;
	}
	// Тільки один процес за раз.
	if ( get_transient( 'vitryla_facts_lock' ) ) {
		return;
	}
	set_transient( 'vitryla_facts_lock', 1, 120 );

	// Запуск без входу в адмінку: вимикаємо фільтр HTML, інакше зникнуть карта й розмітка блоків.
	kses_remove_filters();

	/* ── Нові рубрики ── */
	if ( ! term_exists( 'profilna', 'vit_doc_section' ) ) {
		wp_insert_term( 'Старша профільна школа', 'vit_doc_section', array( 'slug' => 'profilna' ) );
	}
	if ( ! term_exists( 'profilna-shkola', 'category' ) ) {
		wp_insert_term( 'Профільна школа', 'category', array( 'slug' => 'profilna-shkola', 'description' => 'Збори, зустрічі, консультації та новини про перехід до академічного ліцею.' ) );
	}

	/* ── «Дані ліцею» ── */
	$c = (array) get_option( 'vitryla_contacts', array() );
	$c['phone']        = '098 946 36 81';
	$c['phone_extra']  = '';
	$c['office_hours'] = 'Пн–Пт, 08:00–15:30';
	$c['reception']    = 'Середа, пʼятниця, 08:00–15:00 — особистий прийом директора';
	$c['public_info']  = 'Кліменко Наталя Михайлівна, директор ліцею';
	update_option( 'vitryla_contacts', $c );

	$n = (array) get_option( 'vitryla_numbers', array() );
	$n['pupils']        = '641';
	$n['pupils_date']   = 'станом на вересень 2026';
	$n['classes']       = '23';
	$n['classes_date']  = 'станом на вересень 2026';
	$n['shelters']      = '1';
	$n['shelters_note'] = 'на 100 осіб';
	update_option( 'vitryla_numbers', $n );

	$t = (array) get_option( 'vitryla_today', array() );
	$t['format']      = 'Очно, у дві зміни';
	$t['format_note'] = 'друга зміна — з 12:00';
	$t['alarm']       = 'Переходимо в укриття';
	$t['alarm_note']  = 'контрольний час — 09:00 і 13:00';
	$t['office']      = '098 946 36 81';
	$t['office_note'] = 'приймальня, Пн–Пт 08:00–15:30';
	update_option( 'vitryla_today', $t );

	/* ── Документи ── */
	vitryla_real_doc( 'Освітня програма початкової освіти (1–4 класи) на 2026/2027 навчальний рік', 'osvitni', 'osvitnia-prohrama-1-4-2026-2027.pdf', '2026-08-27' );
	vitryla_real_doc( 'Освітня програма базової середньої освіти (5–9 класи) на 2026/2027 навчальний рік', 'osvitni', 'osvitnia-prohrama-5-9-2026-2027.pdf', '2026-08-27' );
	$p1011 = vitryla_real_doc( 'Освітня програма профільної середньої освіти (10–11 класи) на 2026/2027 навчальний рік', 'osvitni', 'osvitnia-prohrama-10-11-2026-2027.pdf', '2026-08-27' );
	if ( $p1011 ) {
		$prof = get_term_by( 'slug', 'profilna', 'vit_doc_section' );
		if ( $prof ) {
			wp_set_post_terms( $p1011, array( $prof->term_id ), 'vit_doc_section', true );
		}
	}
	vitryla_real_doc( 'Алгоритм дій учасників освітнього процесу під час повітряної тривоги', 'bezpeka', 'alhorytm-povitriana-tryvoha-2026.pdf', '2026-09-05', 'додаток до наказу №192-о' );
	vitryla_real_doc( 'Комунікаційна стратегія щодо переходу до старшої профільної школи на 2026–2027 навчальний рік', 'profilna', 'komunikatsiina-stratehiia-profilna-shkola-2026-2027.pdf', '' ); // дату затвердження вписано від руки нерозбірливо — уточнити
	vitryla_real_doc( 'План комунікаційних заходів щодо впровадження старшої профільної школи на 2026–2027 роки', 'profilna', 'plan-komunikatsiinykh-zakhodiv-profilna-shkola-2026-2027.pdf', '' ); // те саме

	// Зразкова «інструкція» більше не потрібна — є справжній алгоритм.
	$demo_alarm = get_posts( array( 'post_type' => 'vit_document', 'title' => 'Інструкція з дій під час повітряної тривоги', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids' ) );
	if ( $demo_alarm && get_post_meta( $demo_alarm[0], 'vit_demo', true ) ) {
		wp_trash_post( $demo_alarm[0] );
	}

	/* ── Адміністрація і служби ── */
	vitryla_real_staff( 'Кліменко Наталя Михайлівна', 'Директор ліцею', 'administratsiya', 0 );
	vitryla_real_staff( 'Кирилюк Юлія Миколаївна', 'Заступник директора з навчально-виховної роботи', 'administratsiya', 1 );
	vitryla_real_staff( 'Ковальова Олена Сергіївна', 'Заступник директора з виховної роботи', 'administratsiya', 2 );
	vitryla_real_staff( 'Новак Світлана Олександрівна', 'Секретар-друкарка', 'inshi', 60 );
	vitryla_real_staff( 'Мітіна Галина Олегівна', 'Медична сестра', 'inshi', 61, 'Корпус №1, щодня 08:00–16:00' );
	vitryla_real_staff( 'Лопатко Іван Сергійович', 'Інженер-технік', 'inshi', 62 );

	// На старому сайті заступницею з НВР значилась інша людина — прибираємо зі складу адміністрації до уточнення.
	$old = get_posts( array( 'post_type' => 'vit_staff', 'title' => 'Курдогло Антоніна Василівна', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids' ) );
	if ( $old ) {
		update_post_meta( $old[0], 'vit_position', 'Вчителька' );
		$vch = get_term_by( 'slug', 'vchyteli', 'vit_staff_group' );
		if ( $vch ) {
			wp_set_post_terms( $old[0], array( $vch->term_id ), 'vit_staff_group' );
		}
	}

	/* ── Сторінки ── */
	vitryla_put_page( 'profilna-shkola', vitryla_body_profilna(), 'З 1 вересня 2027 року Біляївський ліцей №2 готується працювати як академічний ліцей. Що зміниться, коли і де отримати відповіді — на одній сторінці.', 'Профільна школа: академічний ліцей з 2027 року' );
	vitryla_put_page( 'bezpeka-i-ukryttia', vitryla_body_bezpeka(), 'Що робити під час повітряної тривоги — до початку занять і в ліцеї. За алгоритмом, затвердженим наказом №192-о від 05.09.2026.' );
	vitryla_put_page( 'vakansii', vitryla_body_vakansii(), 'Актуальні вакансії ліцею і як подати кандидатуру.' );
	vitryla_put_page( 'osvitni-programy', vitryla_body_programy(), 'Освітні програми ліцею на 2026/2027 навчальний рік для всіх трьох рівнів освіти.' );

	// Контакти: блок «Хто за що відповідає».
	$k = vitryla_page_by_slug( 'kontakty' );
	if ( $k && ! str_contains( $k->post_content, 'vit_responsible' ) ) {
		$body = str_replace(
			"[vit_contacts]\n<!-- /wp:shortcode -->\n\n",
			"[vit_contacts]\n<!-- /wp:shortcode -->\n\n" . vb_h( 'Хто за що відповідає' ) . vb_html( '[vit_responsible]' ),
			$k->post_content
		);
		wp_update_post( array( 'ID' => $k->ID, 'post_content' => wp_slash( $body ) ) );
		delete_post_meta( $k->ID, 'vit_template' );
	}

	// Публічна інформація: відповідальна особа.
	$pi = vitryla_page_by_slug( 'publichna-informacia' );
	if ( $pi ) {
		wp_update_post( array(
			'ID'           => $pi->ID,
			'post_content' => wp_slash( str_replace(
				'Працівника, відповідального за опрацювання запитів на публічну інформацію, буде вказано тут після призначення.',
				'Відповідальна за опрацювання запитів на публічну інформацію — <strong>Кліменко Наталя Михайлівна</strong>, директор ліцею. Телефон приймальні: 098 946 36 81 (Пн–Пт, 08:00–15:30).',
				$pi->post_content
			) ),
		) );
	}

	// Харчування: відповідальна особа на початку сторінки.
	$h = vitryla_page_by_slug( 'harchuvannia' );
	if ( $h && ! str_contains( $h->post_content, 'Ковальова' ) ) {
		wp_update_post( array(
			'ID'           => $h->ID,
			'post_content' => wp_slash( vb_callout( 'Відповідальна за організацію харчування — <strong>Ковальова Олена Сергіївна</strong>, заступник директора з виховної роботи.', 'green', 'Хто відповідає' ) . $h->post_content ),
		) );
	}

	// Розклад: вигадане правило про тривогу замінюємо на справжнє.
	$r = vitryla_page_by_slug( 'rozklad' );
	if ( $r ) {
		$fixed = preg_replace(
			'~<!-- wp:group \{"className":"vit-callout is-sail"[^>]*-->\s*<div class="wp-block-group vit-callout is-sail">.*?Під час повітряної тривоги.*?</div>\s*<!-- /wp:group -->~s',
			trim( vb_callout( 'Ліцей працює у дві зміни. Якщо тривога триває до 09:00 (перша зміна) або до 13:00 (друга зміна), навчання переходить у дистанційний формат. Докладно — на сторінці <a href="/pidtrymka-i-bezpeka/bezpeka-i-ukryttia/">«Безпека і укриття»</a>.', 'sail', 'Під час повітряної тривоги' ) ),
			$r->post_content,
			1
		);
		if ( $fixed && $fixed !== $r->post_content ) {
			wp_update_post( array( 'ID' => $r->ID, 'post_content' => wp_slash( $fixed ) ) );
		}
	}

	/* ── Нові сторінки ── */
	vitryla_add_page( 'pidtrymka-i-bezpeka', 'medychne-obsluhovuvannia', 'Медичне обслуговування', 'Медична сестра щодня в корпусі №1: до кого звертатися, якщо дитині стало зле.', vitryla_body_medychne(), false, 3 );
	vitryla_add_page( 'batkam-i-uchniam', 'pidviz', 'Підвіз учнів', 'Ліцей організовує підвіз учнів шкільним автобусом.', vitryla_body_pidviz(), true, 4 );

	update_option( 'vitryla_facts_version', VITRYLA_FACTS_VERSION );
	delete_transient( 'vitryla_facts_lock' );
	if ( ! current_user_can( 'unfiltered_html' ) ) {
		kses_init_filters();
	}
}

/* ═════════ Тексти сторінок ═════════ */

function vitryla_body_profilna(): string {
	$chips = '<nav class="vit-jump" aria-label="Розділи сторінки"><a href="#shcho-zminytsia">Що зміниться</a><a href="#zaraz">Профіль зараз</a><a href="#plan">План на рік</a><a href="#uchniam">Учням</a><a href="#batkam">Батькам</a><a href="#pedahoham">Педагогам</a><a href="#mify">Міфи і факти</a><a href="#dokumenty">Документи</a><a href="#novyny">Збори і новини</a></nav>';

	$compare = '<div class="vit-compare">'
		. '<div class="vit-cmp is-now"><p class="vit-cmp-k">Зараз</p><h3>10–11 класи</h3><ul>'
		. '<li>2 роки старшої школи</li><li>Профіль визначає заклад для всього класу</li><li>Усі предмети вивчають разом із класом</li><li>11 клас — переважно підготовка до вступу</li></ul></div>'
		. '<div class="vit-cmp-arrow" aria-hidden="true"></div>'
		. '<div class="vit-cmp is-next"><p class="vit-cmp-k">З 1 вересня 2027</p><h3>10–12 класи</h3><ul>'
		. '<li>3 роки: 10 клас — адаптаційний, 11–12 — поглиблене навчання</li><li>Учень сам обирає профіль, предмети й курси</li><li>Обов’язкові предмети — з класом, вибіркові — у групах</li><li>Профілі в межах трьох кластерів: STEM, мовно-літературного, соціально-гуманітарного</li></ul></div>'
		. '</div>';

	return vb_html( '[vit_countdown]' )
		. vb_p( '<strong>Біляївський ліцей №2 готується до роботи в статусі академічного ліцею з 1 вересня 2027 року.</strong> Наша мета — допомогти учням і батькам зрозуміти можливості старшої профільної школи, усвідомлено обрати освітню траєкторію та підготуватися до змін.' )
		. vb_html( $chips )

		. vf_h( 'Що зміниться', 'shcho-zminytsia' )
		. vb_p( 'Нова модель стосується тих, хто вступатиме до 10 класу з 1 вересня 2027 року. Тобто нинішні дев’ятикласники — перші, кого вона торкнеться.' )
		. vb_html( $compare )
		. vb_cards( array(
			array( 'award', 'Академічний ліцей', 'Готує до вступу в університет. Поглиблене вивчення профільних предметів і курсів, а ще — курси поза профілем.', 'blue' ),
			array( 'compass', 'Професійний коледж', 'Професія разом із повною загальною середньою освітою. Після коледжу теж можна вступити до університету.', 'sun' ),
		), '26rem' )

		. vf_h( 'Профільне навчання в ліцеї зараз', 'zaraz' )
		. vb_p( 'Поки що 10–11 класи навчаються за Освітньою програмою профільної середньої освіти, затвердженою 27.08.2026. Профіль на кожен рік визначає річний навчальний план.' )
		. vb_stats( array(
			array( '3', 'предмети на профільному рівні', 'українська мова, математика, історія України' ),
			array( '5,5', 'год на тиждень у 10 класі', 'профільні предмети, спецкурси, факультативи' ),
			array( '6,5', 'год на тиждень в 11 класі', 'профільна складова й додаткові години' ),
			array( '33', 'год на тиждень', 'гранично допустиме навантаження' ),
		) )

		. vf_h( 'План інформування на рік', 'plan' )
		. vb_p( 'Ліцей затвердив комунікаційну стратегію і помісячний план заходів: зустрічі, опитування, консультації, профорієнтація. Поточний місяць підсвічено.' )
		. vb_html( '[vit_roadmap]' )

		. vf_h( 'Учням 9-х класів', 'uchniam' )
		. vb_faq( array(
			array( 'Які в мене варіанти після 9 класу?', 'Два шляхи: академічний ліцей (поглиблене вивчення профільних предметів і підготовка до вступу в університет) або професійний коледж (професія разом із повною загальною середньою освітою).' ),
			array( 'Чи можна буде змінити профіль?', 'Так. 10 клас — адаптаційний: учні завершують загальну підготовку й остаточно визначаються. Після 10 класу профіль можна змінити.' ),
			array( 'Як зрозуміти, який профіль мені підходить?', 'За планом у січні 2027 року — профорієнтаційні тренінги й консультації, у лютому — тиждень «Професії майбутнього». Індивідуально допоможуть практичний психолог і класний керівник.' ),
			array( 'Які профілі будуть у нашому ліцеї?', 'Перелік ліцей оприлюднить після затвердження — за планом, у березні 2027 року. До того ми повідомляємо лише підтверджену інформацію.' ),
			array( 'Де пройти опитування?', 'Посилання на анкети надсилають класні керівники в учнівські чати. Від ваших відповідей залежить, які предмети й курси пропонуватиме ліцей.' ),
		) )

		. vf_h( 'Батькам', 'batkam' )
		. vb_faq( array(
			array( 'Чому школа триватиме 12 років?', 'Для якісного опанування програми старшокласникам потрібно щонайменше три роки — це міжнародна практика. 10 клас — адаптаційний, 11–12 — профільні.' ),
			array( 'Що, якщо обраний ліцей далеко від дому?', 'За роз’ясненням МОН: до 30 км — громада організовує підвіз шкільним автобусом, понад 30 км — учні можуть жити в пансіоні при ліцеї протягом тижня.' ),
			array( 'Де поставити запитання?', 'На батьківських зборах, у класного керівника 9-го класу або на особистій консультації. Організацію інформаційних заходів координує заступник директора з НВР Кирилюк Юлія Миколаївна.' ),
			array( 'Коли буде відомий порядок зарахування?', 'Щойно його визначать офіційно — ліцей повідомить на цій сторінці, на батьківських зборах і в чатах. За стратегією, це етап березня — червня 2027 року.' ),
		) )

		. vf_h( 'Педагогам', 'pedahoham' )
		. vb_faq( array(
			array( 'Як ліцей готує колектив?', 'Інформаційні наради й педагогічні ради — у вересні та грудні 2026 року, нарада щодо готовності до роботи академічного ліцею — у серпні 2027 року.' ),
			array( 'Що від мене очікують?', 'Учителі-предметники знайомлять учнів із можливостями поглибленого вивчення предметів і беруть участь у профорієнтаційних заходах. Класні керівники 9-х класів інформують учнів і батьків та збирають запитання.' ),
			array( 'Чи збережуть учителі роботу?', 'За роз’ясненням МОН, про звільнення вчителів через реформу не йдеться. Місце роботи може змінитися у зв’язку з перебудовою мережі закладів освіти.' ),
		) )

		. vf_h( 'Міфи і факти', 'mify' )
		. vb_html( '<div class="vit-myths">'
			. '<div class="vit-myth"><p class="vit-myth-m">«Профіль обирають раз і назавжди»</p><p class="vit-myth-f">10 клас — адаптаційний. Після нього профіль можна змінити.</p></div>'
			. '<div class="vit-myth"><p class="vit-myth-m">«У ліцеї вчитимуть лише профільні предмети»</p><p class="vit-myth-f">Обов’язкові предмети вивчають усі, а ще можна обирати курси поза профілем.</p></div>'
			. '<div class="vit-myth"><p class="vit-myth-m">«Після коледжу дорога до університету закрита»</p><p class="vit-myth-f">Коледж дає свідоцтво про повну загальну середню освіту. Після нього можна вступати до університету.</p></div>'
			. '<div class="vit-myth"><p class="vit-myth-m">«Школи з 1–9 класами закриють»</p><p class="vit-myth-f">Початкові школи й гімназії продовжують працювати. Реформа відокремлює саме старші класи.</p></div>'
			. '</div>' )

		. vf_h( 'Документи', 'dokumenty' )
		. vb_html( '[vit_documents section="profilna"]' )

		. vf_h( 'Збори, зустрічі і новини', 'novyny' )
		. vb_p( 'Звіти про батьківські збори, інформаційні години й консультації публікуємо в рубриці «Профільна школа» — вони автоматично з’являються тут.' )
		. vb_html( '[vit_posts cat="profilna-shkola" limit="6" empty="Перші дописи з’являться тут після перенесення новин зі сторінки ліцею у Facebook."]' )

		. vb_h( 'До кого звертатися' )
		. vb_cards( array(
			array( 'compass', 'Кирилюк Юлія Миколаївна', 'Заступник директора з НВР: інформаційні заходи, опитування, матеріали для учнів і батьків.', 'blue' ),
			array( 'users', 'Класні керівники 9-х класів', 'Інформують учнів і батьків, збирають запитання, допомагають долучитися до опитувань.', 'sun' ),
			array( 'heart', 'Практичний психолог', 'Профорієнтаційна підтримка й консультації: допомагає визначити інтереси і здібності.', 'green' ),
		) )
		. vb_buttons( array(
			array( 'Офіційний сайт реформи МОН', 'https://profilna.mon.gov.ua/' ),
			array( 'Профорієнтація і НМТ', '/navchannia/proforiientatsiia-nmt/' ),
		) );
}

function vitryla_body_bezpeka(): string {
	return vb_stats( array(
		array( '1', 'укриття', 'обладнане' ),
		array( '100', 'осіб', 'місткість укриття' ),
		array( '2', 'зміни', 'свій контрольний час для кожної' ),
	) )
		. vb_callout( 'Основний пріоритет — збереження життя і здоров’я дітей та працівників ліцею. Часові орієнтири нижче — не дозвіл виходити з укриття чи прямувати до ліцею під час тривоги.', 'sail', 'Головне' )
		. vb_h( 'Якщо тривога до початку занять' )
		. vb_p( 'Учні залишаються вдома або йдуть до найближчого укриття. Батьки не відправляють дітей до ліцею під час тривоги. Після відбою — перевірте повідомлення адміністрації про початок занять.' )
		. vb_html( '[vit_alarm_shifts]' )
		. vb_p( 'Перехід на дистанційне навчання відбувається незалежно від того, чи пролунав до контрольного часу відбій. Про рішення адміністрація повідомляє через офіційні канали ліцею.' )
		. vb_h( 'Якщо тривога під час уроків' )
		. vb_steps( array(
			'Учитель спокійно повідомляє про тривогу і припиняє заняття.',
			'Клас організовано виходить і йде до укриття визначеним маршрутом — без бігу, без штовханини, не повертаючись по речі.',
			'Учні, які потребують супроводу, зокрема діти з ООП та з обмеженою мобільністю, отримують допомогу першочергово.',
			'В укритті вчитель перевіряє присутність за списком і залишається з класом.',
			'Вихід — лише після офіційного відбою і за вказівкою відповідальної особи.',
		) )
		. vb_p( 'За належних умов і рішення адміністрації навчання може тривати в укритті. Після відбою вчителі ще раз перевіряють присутність, а адміністрація вирішує, чи продовжувати заняття, змінювати розклад або формат навчання.' )
		. vb_h( 'Батькам' )
		. vb_list( array(
			'Не відправляйте дитину до ліцею під час повітряної тривоги.',
			'Стежте за офіційними повідомленнями ліцею про початок занять, перенесення або зміну формату.',
			'Не вимагайте вивести дитину з укриття під час тривоги — забрати її можна після відбою і дозволу відповідальної особи.',
			'Під час тривоги не перевантажуйте вчителів повідомленнями — їхнє першочергове завдання зараз безпека дітей.',
			'Поясніть дитині, чому важливо дотримуватися правил і вказівок педагогів.',
		) )
		. vb_callout( 'Відповідальна за укриття та безпеку — <strong>Кліменко Наталя Михайлівна</strong>, директор ліцею.', 'blue', 'Хто відповідає' )
		. vb_h( 'Документ' )
		. vb_html( '[vit_documents section="bezpeka"]' );
}

function vitryla_body_vakansii(): string {
	return vb_callout( 'Станом на 01.09.2026 вакантна посада: <strong>вчитель хімії</strong>.', 'sun', 'Відкрита вакансія' )
		. vb_h( 'Як подати кандидатуру' )
		. vb_steps( array(
			'Зателефонуйте до приймальні: 098 946 36 81 (Пн–Пт, 08:00–15:30) або напишіть на shkola2bel@ukr.net.',
			'Надішліть або принесіть резюме.',
			'Пройдіть співбесіду з дирекцією ліцею.',
		) )
		. vb_p( 'Перелік вакансій оновлюється в разі змін. Дата поруч із вакансією показує, на який день інформація актуальна.' );
}

function vitryla_body_programy(): string {
	return vb_table( array( 'Рівень', 'Класи', 'Основа програми' ), array(
		array( 'Початкова освіта', '1–4', 'Типова освітня програма, розроблена під керівництвом Савченко О. Я. (наказ МОН від 12.08.2022 №743)' ),
		array( 'Базова середня освіта', '5–9', 'Типова освітня програма для 5–9 класів (наказ МОН від 19.02.2021 №235 у редакції наказу від 17.08.2026 №1430)' ),
		array( 'Профільна середня освіта', '10–11', 'Типова освітня програма закладів ЗСО III ступеня (наказ МОН від 20.04.2018 №408 у редакції наказу від 20.06.2025 №890)' ),
	) )
		. vb_p( 'Усі три програми схвалено педагогічною радою (протокол від 27.08.2026 №1) і затверджено директором ліцею 27.08.2026. Мова навчання — українська.' )
		. vb_html( '[vit_documents section="osvitni"]' );
}

function vitryla_body_medychne(): string {
	return vb_cards( array(
		array( 'heart', 'Медична сестра', 'Мітіна Галина Олегівна', 'green' ),
		array( 'home', 'Де', 'Медичний кабінет у корпусі №1', 'blue' ),
		array( 'clock', 'Коли', 'Щодня, 08:00–16:00', 'sun' ),
	) )
		. vb_h( 'Якщо дитині стало зле' )
		. vb_steps( array(
			'Учитель або класний керівник супроводжує дитину до медичного кабінету.',
			'Медична сестра надає допомогу і повідомляє батьків.',
			'Якщо потрібна екстрена допомога — викликають швидку (103) і одразу телефонують батькам.',
		) );
}

function vitryla_body_pidviz(): string {
	return vb_callout( 'Ліцей організовує підвіз учнів шкільним автобусом.', 'green', 'Підвіз є' )
		. vb_h( 'Маршрут і розклад' )
		. vb_table( array( 'Зупинка', 'Відправлення', 'Повернення' ), array(
			array( 'Уточнюється', '—', '—' ),
		) )
		. vb_p( 'Маршрут, зупинки, час відправлення і хто супроводжує дітей — буде опубліковано після уточнення в адміністрації.' );
}

<?php
/**
 * Демонстраційне наповнення — щоб оцінити вигляд сайту до того,
 * як надійдуть справжні матеріали.
 *
 * Кожен створений запис позначено полем vit_demo = 1. Поки в базі
 * є хоч один такий запис, сайт і адмінка показують попередження,
 * а в розділі «Дані ліцею» доступна кнопка повного прибирання.
 *
 * НІЧОГО З ЦЬОГО НЕ МОЖНА ЛИШАТИ НА ЖИВОМУ САЙТІ.
 *
 * @package vitryla
 */

defined( 'ABSPATH' ) || exit;

const VITRYLA_DEMO_VERSION = 1;

/* ─────────── Встановлення емблеми ─────────── */

add_action( 'admin_init', function (): void {
	if ( get_option( 'vitryla_logo_set' ) ) {
		return;
	}
	$src = WP_CONTENT_DIR . '/uploads/vitryla/emblema.png';
	if ( ! file_exists( $src ) ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';

	$id = wp_insert_attachment( array(
		'post_mime_type' => 'image/png',
		'post_title'     => 'Емблема Біляївського ліцею №2',
		'post_status'    => 'inherit',
	), $src );

	if ( $id && ! is_wp_error( $id ) ) {
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $src ) );
		update_post_meta( $id, '_wp_attachment_image_alt', 'Емблема Біляївського ліцею №2: червоні вітрила, жовте сонце, синя вода' );
		set_theme_mod( 'custom_logo', $id );
		update_option( 'site_icon', $id );
	}
	update_option( 'vitryla_logo_set', 1 );
} );

/* ─────────── Наповнення ─────────── */

add_action( 'admin_init', 'vitryla_seed_demo', 30 );

/**
 * Створює демонстраційні записи.
 */
function vitryla_seed_demo(): void {
	if ( (int) get_option( 'vitryla_demo_version' ) >= VITRYLA_DEMO_VERSION ) {
		return;
	}

	$mark = function ( int $id ): int {
		update_post_meta( $id, 'vit_demo', 1 );
		return $id;
	};

	/* ── Новини ── */
	$news = array(
		array( 'Перший тиждень нового навчального року', 'ogoloshennia', '-4 days',
			'Розповідаємо, як організовано навчання у трьох корпусах, коли починаються уроки і що робити під час повітряної тривоги. Для батьків першокласників — окрема зустріч у четвер.' ),
		array( 'Наші учні — призери районного етапу олімпіад', 'hordist', '-9 days',
			'Одинадцять призових місць із семи предметів. Вітаємо учасників і вчителів, які готували команди.' ),
		array( 'Як працює команда психолого-педагогічного супроводу', 'navchannia', '-15 days',
			'У ліцеї навчаються діти з особливими освітніми потребами, і супровід кожного забезпечує окрема команда фахівців. Пояснюємо, як це влаштовано і до кого звертатися батькам.' ),
		array( 'Оновлено порядок дій під час повітряної тривоги', 'bezpeka-news', '-21 days',
			'Після навчальної евакуації ми уточнили маршрути для кожного корпусу. Час переходу в укриття скоротився.' ),
		array( 'Відкрито набір до гуртків і клубів', 'zhyttia', '-27 days',
			'Цього року працюють дев’ять гуртків — від пластового куреня до клубу журналістики. Записатися можна у педагога-організатора.' ),
		array( 'Звіт директора за 2025/2026 навчальний рік', 'ogoloshennia', '-34 days',
			'Документ оприлюднено в розділі «Прозорість». Там же — кошторис і фінансова звітність за квартал.' ),
	);
	foreach ( $news as [ $title, $cat, $when, $text ] ) {
		$id = wp_insert_post( array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_content' => "<!-- wp:paragraph -->\n<p>$text</p>\n<!-- /wp:paragraph -->",
			'post_date'    => gmdate( 'Y-m-d H:i:s', strtotime( $when ) ),
		) );
		if ( $id && ! is_wp_error( $id ) ) {
			$mark( $id );
			$term = get_term_by( 'slug', $cat, 'category' );
			if ( $term ) {
				wp_set_post_terms( $id, array( $term->term_id ), 'category' );
			}
		}
	}

	/* ── Педагоги ── */
	$staff = array(
		array( 'Кліменко Наталя Михайлівна', 'Директорка ліцею', 'Українська мова та література', 'administratsiya' ),
		array( 'Курдогло Антоніна Василівна', 'Заступниця директора з навчально-виховної роботи', 'Музичне мистецтво', 'administratsiya' ),
		array( 'Ковальова Олена Сергіївна', 'Заступниця директора з виховної роботи', 'Інформатика', 'administratsiya' ),
		array( 'Крачкова Юлія Ярославівна', 'Педагогиня-організаторка', 'Історія', 'administratsiya' ),
		array( 'Зозуля Катерина Геннадіївна', 'Практична психологиня', '', 'fahivtsi' ),
		array( 'Гарматюк Тетяна Іванівна', 'Асистентка вчителя, логопединя', '', 'asystenty' ),
		array( 'Славова Алла Василівна', 'Вчителька', 'Українська мова, українська та зарубіжна література', 'vchyteli' ),
		array( 'Топов Михайло Федорович', 'Вчитель', 'Фізика та астрономія', 'vchyteli' ),
	);
	foreach ( $staff as $i => [ $name, $pos, $subj, $group ] ) {
		$id = wp_insert_post( array(
			'post_type'   => 'vit_staff',
			'post_status' => 'publish',
			'post_title'  => $name,
			'menu_order'  => $i,
		) );
		if ( $id && ! is_wp_error( $id ) ) {
			$mark( $id );
			update_post_meta( $id, 'vit_position', $pos );
			update_post_meta( $id, 'vit_subjects', $subj );
			$term = get_term_by( 'slug', $group, 'vit_staff_group' );
			if ( $term ) {
				wp_set_post_terms( $id, array( $term->term_id ), 'vit_staff_group' );
			}
		}
	}

	/* ── Гуртки ── */
	$clubs = array(
		array( 'Курінь імені Анни Ярославни', '5–11 класи', 'субота', '10:00–13:00', 'актова зала',
			'Пластовий осередок при ліцеї: мандрівки, табори, вишколи, служіння громаді.' ),
		array( 'Клуб журналістики', '7–11 класи', 'вівторок', '14:30–15:30', 'каб. 24',
			'Шкільні новини, інтерв’ю, зйомка та монтаж сюжетів.' ),
		array( 'Євроклуб', '8–11 класи', 'четвер', '15:00–16:00', 'каб. 31',
			'Міжнародні проєкти, listening-клуб, підготовка до обмінів.' ),
		array( 'Юні дослідники', '5–7 класи', 'середа', '14:00–15:00', 'кабінет біології',
			'Досліди, спостереження, підготовка до конкурсів МАН.' ),
		array( 'Спортивні секції', '1–11 класи', 'щодня', 'за розкладом', 'спортзал',
			'Волейбол, баскетбол, легка атлетика, настільний теніс.' ),
	);
	foreach ( $clubs as [ $title, $classes, $day, $time, $room, $desc ] ) {
		$id = wp_insert_post( array(
			'post_type'    => 'vit_club',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_content' => "<!-- wp:paragraph -->\n<p>$desc</p>\n<!-- /wp:paragraph -->",
		) );
		if ( $id && ! is_wp_error( $id ) ) {
			$mark( $id );
			update_post_meta( $id, 'vit_classes', $classes );
			update_post_meta( $id, 'vit_day', $day );
			update_post_meta( $id, 'vit_time', $time );
			update_post_meta( $id, 'vit_room', $room );
		}
	}

	/* ── Документи ── */
	$docs = array(
		array( 'Статут Біляївського ліцею №2', 'ustanovchi', '2024-02-14', '2024-02-20' ),
		array( 'Освітня програма на 2026/2027 навчальний рік', 'osvitni', '2026-08-28', '2026-09-02' ),
		array( 'Кошторис на 2026 рік', 'finansy', '2026-01-15', '2026-01-20' ),
		array( 'Фінансовий звіт за III квартал 2026 року', 'finansy', '2026-10-10', '2026-10-15' ),
		array( 'Звіт директора за 2025/2026 навчальний рік', 'zvity', '2026-09-20', '2026-09-24' ),
		array( 'План заходів щодо запобігання та протидії булінгу', 'bulinh', '2026-09-01', '2026-09-05' ),
		array( 'Правила доступу і перебування на території закладу', 'bezpeka', '2026-09-15', '' ),
		array( 'Перспективний план підвищення кваліфікації', 'kadry', '2026-09-10', '2026-09-12' ),
	);
	foreach ( $docs as [ $title, $section, $approved, $published ] ) {
		$id = wp_insert_post( array(
			'post_type'   => 'vit_document',
			'post_status' => 'publish',
			'post_title'  => $title,
		) );
		if ( $id && ! is_wp_error( $id ) ) {
			$mark( $id );
			update_post_meta( $id, 'vit_approved', $approved );
			update_post_meta( $id, 'vit_published', $published );
			update_post_meta( $id, 'vit_valid', 'current' );
			$term = get_term_by( 'slug', $section, 'vit_doc_section' );
			if ( $term ) {
				wp_set_post_terms( $id, array( $term->term_id ), 'vit_doc_section' );
			}
		}
	}

	/* ── Дані ліцею ── */
	update_option( 'vitryla_today', array(
		'format' => 'Очно, 5 днів', 'format_note' => 'Перший урок о 8:30',
		'alarm' => 'Переходимо в укриття', 'alarm_note' => 'Урок триває після відбою',
		'food' => 'Меню на тиждень', 'food_note' => '1–4 класи — безкоштовно',
		'office' => 'shkola2bel@ukr.net', 'office_note' => 'Щодня 8:00–17:00',
	) );
	update_option( 'vitryla_numbers', array(
		'founded' => '1897', 'pupils' => '619', 'pupils_date' => 'станом на 05.09.2024',
		'staff' => '48', 'staff_date' => 'станом на 11.2025',
		'shelters' => '2', 'shelters_note' => 'на 400 осіб',
	) );

	update_option( 'vitryla_demo_version', VITRYLA_DEMO_VERSION );
}

/* ─────────── Скільки демо-записів ─────────── */

/**
 * Кількість демонстраційних записів.
 */
function vitryla_demo_count(): int {
	global $wpdb;
	return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'vit_demo'" );
}

/* ─────────── Попередження ─────────── */

add_action( 'admin_notices', function (): void {
	$n = vitryla_demo_count();
	if ( ! $n ) {
		return;
	}
	printf(
		'<div class="notice notice-warning"><p><strong>На сайті %d демонстраційних записів.</strong> Вони створені, щоб оцінити вигляд, і містять вигадані дані. Прибрати їх треба до того, як сайт стане доступним людям — <a href="%s">Дані ліцею → Демонстраційне наповнення</a>.</p></div>',
		$n,
		esc_url( admin_url( 'admin.php?page=vitryla' ) )
	);
} );

add_action( 'wp_body_open', function (): void {
	$tpl = function_exists( 'vitryla_template_count' ) ? vitryla_template_count() : 0;
	if ( ! vitryla_demo_count() && ! $tpl ) {
		return;
	}
	echo '<div class="vit-demo-bar" role="note">Зразок сайту: тексти й цифри показують, як виглядатиме наповнений сайт, і будуть замінені на фактичні.</div>';
} );

/* ─────────── Прибирання ─────────── */

add_action( 'admin_post_vitryla_purge_demo', function (): void {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'vitryla_purge_demo' ) ) {
		wp_die( 'Немає прав.' );
	}

	global $wpdb;
	$ids = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'vit_demo'" );
	foreach ( $ids as $id ) {
		wp_delete_post( (int) $id, true );
	}
	delete_option( 'vitryla_demo_version' );
	update_option( 'vitryla_today', array() );
	update_option( 'vitryla_numbers', array() );

	wp_safe_redirect( admin_url( 'admin.php?page=vitryla&purged=' . count( $ids ) ) );
	exit;
} );

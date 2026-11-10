<?php
/**
 * Типи записів і таксономії.
 *
 * @package vitryla
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'vitryla_register_post_types' );
add_action( 'init', 'vitryla_register_taxonomies' );
add_action( 'init', 'vitryla_seed_terms', 20 );

/**
 * Документи, педагоги, гуртки.
 */
function vitryla_register_post_types(): void {

	register_post_type( 'vit_document', array(
		'labels' => array(
			'name'               => 'Документи',
			'singular_name'      => 'Документ',
			'add_new'            => 'Додати документ',
			'add_new_item'       => 'Новий документ',
			'edit_item'          => 'Редагувати документ',
			'search_items'       => 'Шукати документи',
			'not_found'          => 'Документів не знайдено',
			'menu_name'          => 'Документи',
		),
		'public'        => true,
		'has_archive'   => 'dokumenty',
		'rewrite'       => array( 'slug' => 'dokument', 'with_front' => false ),
		'menu_icon'     => 'dashicons-media-document',
		'menu_position' => 21,
		'supports'      => array( 'title', 'editor', 'revisions', 'custom-fields' ),
		'show_in_rest'  => true,
		'taxonomies'    => array( 'vit_doc_section' ),
	) );

	register_post_type( 'vit_staff', array(
		'labels' => array(
			'name'          => 'Педагоги',
			'singular_name' => 'Педагог',
			'add_new'       => 'Додати педагога',
			'add_new_item'  => 'Новий педагог',
			'edit_item'     => 'Редагувати',
			'search_items'  => 'Шукати педагогів',
			'not_found'     => 'Нікого не знайдено',
			'menu_name'     => 'Педагоги',
		),
		'public'        => true,
		'has_archive'   => 'kadrovyj-sklad',
		'rewrite'       => array( 'slug' => 'pedagog', 'with_front' => false ),
		'menu_icon'     => 'dashicons-groups',
		'menu_position' => 22,
		'supports'      => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'custom-fields' ),
		'show_in_rest'  => true,
		'taxonomies'    => array( 'vit_staff_group' ),
	) );

	register_post_type( 'vit_club', array(
		'labels' => array(
			'name'          => 'Гуртки і клуби',
			'singular_name' => 'Гурток',
			'add_new'       => 'Додати гурток',
			'add_new_item'  => 'Новий гурток',
			'edit_item'     => 'Редагувати гурток',
			'search_items'  => 'Шукати гуртки',
			'not_found'     => 'Гуртків не знайдено',
			'menu_name'     => 'Гуртки',
		),
		'public'        => true,
		'has_archive'   => 'gurtky',
		'rewrite'       => array( 'slug' => 'gurtok', 'with_front' => false ),
		'menu_icon'     => 'dashicons-star-filled',
		'menu_position' => 23,
		'supports'      => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
		'show_in_rest'  => true,
	) );
}

/**
 * Таксономії: розділ статті 30 і група педагогів.
 */
function vitryla_register_taxonomies(): void {

	register_taxonomy( 'vit_doc_section', array( 'vit_document' ), array(
		'labels' => array(
			'name'          => 'Розділи документів',
			'singular_name' => 'Розділ',
			'add_new_item'  => 'Додати розділ',
			'menu_name'     => 'Розділи',
		),
		'public'            => true,
		'hierarchical'      => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => array( 'slug' => 'rozdil-dokumentiv', 'with_front' => false ),
	) );

	register_taxonomy( 'vit_staff_group', array( 'vit_staff' ), array(
		'labels' => array(
			'name'          => 'Групи педагогів',
			'singular_name' => 'Група',
			'add_new_item'  => 'Додати групу',
			'menu_name'     => 'Групи',
		),
		'public'            => true,
		'hierarchical'      => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => array( 'slug' => 'grupa', 'with_front' => false ),
	) );
}

/**
 * Початкові терміни. Створюються один раз — далі редагуються руками.
 */
function vitryla_seed_terms(): void {
	if ( get_option( 'vitryla_terms_seeded' ) ) {
		return;
	}

	// Розділи документів — за переліком статті 30 Закону «Про освіту».
	$sections = array(
		'ustanovchi'   => 'Установчі документи',
		'osvitni'      => 'Освітні програми',
		'finansy'      => 'Фінансові документи',
		'blagodijna'   => 'Благодійна допомога та закупівлі',
		'zvity'        => 'Плани і звіти',
		'strategiya'   => 'Стратегія і самооцінювання',
		'kadry'        => 'Кадри й атестація',
		'pryjom'       => 'Прийом і територія обслуговування',
		'bulinh'       => 'Протидія булінгу',
		'bezpeka'      => 'Безпека і доступ',
		'dostupnist'   => 'Доступність для осіб з ООП',
		'monitorynh'   => 'Моніторинг якості освіти',
		'koruptsiya'   => 'Запобігання корупції',
		'normatyvna'   => 'Нормативно-правова база',
	);
	foreach ( $sections as $slug => $name ) {
		if ( ! term_exists( $slug, 'vit_doc_section' ) ) {
			wp_insert_term( $name, 'vit_doc_section', array( 'slug' => $slug ) );
		}
	}

	$groups = array(
		'administratsiya' => 'Адміністрація',
		'vchyteli'        => 'Вчителі',
		'asystenty'       => 'Асистенти вчителя',
		'fahivtsi'        => 'Фахівці супроводу',
		'inshi'           => 'Інші працівники',
	);
	foreach ( $groups as $slug => $name ) {
		if ( ! term_exists( $slug, 'vit_staff_group' ) ) {
			wp_insert_term( $name, 'vit_staff_group', array( 'slug' => $slug ) );
		}
	}

	// Рубрики новин.
	$cats = array(
		'ogoloshennia' => 'Оголошення',
		'hordist'      => 'Наша гордість',
		'navchannia'   => 'Навчання',
		'bezpeka-news' => 'Безпека',
		'zhyttia'      => 'Життя ліцею',
	);
	foreach ( $cats as $slug => $name ) {
		if ( ! term_exists( $slug, 'category' ) ) {
			wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
		}
	}

	update_option( 'vitryla_terms_seeded', 1 );
	flush_rewrite_rules();
}

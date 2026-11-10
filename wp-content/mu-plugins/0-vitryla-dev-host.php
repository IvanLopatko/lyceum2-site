<?php
/**
 * Plugin Name: Ліцей №2 — доступ з інших пристроїв (лише для показу)
 * Description: Дає відкрити локальний сайт з телефона: у тій самій Wi-Fi мережі або за тимчасовим публічним посиланням. Адмінка доступна лише з цього ноутбука. На справжньому хостингу цей файл треба видалити.
 *
 * @package vitryla
 */

defined( 'ABSPATH' ) || exit;

// Тимчасове публічне посилання працює через https-проксі — кажемо про це WordPress.
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === strtolower( (string) $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) {
	$_SERVER['HTTPS'] = 'on';
}

$vitryla_host = (string) ( $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? '' );
if ( '' === $vitryla_host || ! preg_match( '/^[a-z0-9.\-:\[\]]+$/i', $vitryla_host ) ) {
	return;
}

$vitryla_scheme = ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'] ) ? 'https://' : 'http://';
$vitryla_base   = $vitryla_scheme . $vitryla_host;
$vitryla_local  = in_array( preg_replace( '/:\d+$/', '', $vitryla_host ), array( '127.0.0.1', 'localhost', '[::1]' ), true );

// Адреса сайту — та, з якої його відкрили.
add_filter( 'option_home', fn() => $vitryla_base, 99 );
add_filter( 'option_siteurl', fn() => $vitryla_base, 99 );

// Стилі, скрипти й зображення WordPress прописує повною адресою ще до завантаження
// цього файлу — підміняємо її у готовій сторінці.
if ( ! $vitryla_local ) {
	ob_start( function ( string $html ) use ( $vitryla_base ): string {
		return str_replace(
			array( 'http://127.0.0.1:9400', 'http:\/\/127.0.0.1:9400' ),
			array( $vitryla_base, str_replace( '/', '\/', $vitryla_base ) ),
			$html
		);
	} );

	// Адмінка й вхід — лише з цього ноутбука. Відвідувачі з телефонів бачать тільки сайт.
	$vitryla_path = (string) parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
	if ( str_contains( $vitryla_path, '/wp-login.php' ) || ( str_contains( $vitryla_path, '/wp-admin' ) && ! str_contains( $vitryla_path, 'admin-ajax.php' ) ) ) {
		status_header( 403 );
		header( 'Content-Type: text/html; charset=utf-8' );
		echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Лише з ноутбука</title>'
			. '<div style="font:16px/1.6 -apple-system,sans-serif;max-width:520px;margin:15vh auto;padding:0 20px;color:#16224A">'
			. '<h1 style="font-size:22px">Керування сайтом — лише з ноутбука розробника</h1>'
			. '<p>Це демонстраційна версія. Переглядати сайт можна з будь-якого пристрою, а редагувати — тільки з компʼютера, на якому він запущений.</p>'
			. '<p><a href="/">← На головну</a></p></div>';
		exit;
	}
}

<?php
/**
 * Імпорт дописів зі сторінки ліцею у Facebook.
 *
 * Джерело — uploads/vitryla/fb/posts.json (готує скрипт на комп'ютері:
 * тексти, дати, рубрики, локальні копії фото). Кожен допис стає записом
 * у «Новинах» з датою оригіналу, рубрикою, фото й посиланням на джерело.
 * Повторний запуск не дублює: ключ — vit_fb_id.
 *
 * @package vitryla
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'vitryla_fb_import', 120 );

function vitryla_fb_import(): void {
	$file = WP_CONTENT_DIR . '/uploads/vitryla/fb/posts.json';
	if ( ! file_exists( $file ) ) {
		return;
	}
	$stamp = (string) filemtime( $file ) . ':' . (string) filesize( $file );
	if ( get_option( 'vitryla_fb_stamp' ) === $stamp || get_transient( 'vitryla_fb_lock' ) ) {
		return;
	}
	set_transient( 'vitryla_fb_lock', 1, 300 );
	kses_remove_filters();
	wp_defer_term_counting( true );
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 300 ); // phpcs:ignore
	}

	$posts = json_decode( (string) file_get_contents( $file ), true );
	if ( ! is_array( $posts ) ) {
		delete_transient( 'vitryla_fb_lock' );
		return;
	}

	$cats = array();
	foreach ( get_categories( array( 'hide_empty' => false ) ) as $c ) {
		$cats[ $c->slug ] = $c->term_id;
	}

	foreach ( $posts as $p ) {
		$exists = get_posts( array( 'post_type' => 'post', 'post_status' => 'any', 'meta_key' => 'vit_fb_id', 'meta_value' => (string) $p['id'], 'fields' => 'ids', 'posts_per_page' => 1 ) );
		if ( $exists ) {
			continue;
		}

		// Фото.
		$att = array();
		foreach ( (array) ( $p['images'] ?? array() ) as $i => $rel ) {
			$path = WP_CONTENT_DIR . '/uploads/vitryla/fb/' . $rel;
			if ( ! file_exists( $path ) ) {
				continue;
			}
			$aid = wp_insert_attachment( wp_slash( array(
				'post_mime_type' => 'image/jpeg',
				'post_title'     => $p['title'] . ( $i ? ' — фото ' . ( $i + 1 ) : '' ),
				'post_status'    => 'inherit',
				'guid'           => content_url( 'uploads/vitryla/fb/' . $rel ),
			) ), $path );
			if ( $aid && ! is_wp_error( $aid ) ) {
				$size = @getimagesize( $path ); // phpcs:ignore
				wp_update_attachment_metadata( $aid, array(
					'width'  => $size ? $size[0] : 0,
					'height' => $size ? $size[1] : 0,
					'file'   => 'vitryla/fb/' . $rel,
					'sizes'  => array(),
				) );
				update_post_meta( $aid, '_wp_attachment_image_alt', $p['alt'] ?? '' );
				$att[] = (int) $aid;
			}
		}

		// Текст.
		$body = '';
		foreach ( (array) $p['paras'] as $para ) {
			$body .= "<!-- wp:paragraph -->\n<p>" . $para . "</p>\n<!-- /wp:paragraph -->\n\n";
		}
		if ( count( $att ) > 1 ) {
			$inner = '';
			foreach ( array_slice( $att, 1 ) as $aid ) {
				$url    = wp_get_attachment_url( $aid );
				$inner .= '<!-- wp:image {"id":' . $aid . ',"sizeSlug":"full","linkDestination":"media"} -->' . "\n"
					. '<figure class="wp-block-image size-full"><a href="' . esc_url( $url ) . '"><img src="' . esc_url( $url ) . '" alt="' . esc_attr( $p['alt'] ?? '' ) . '" class="wp-image-' . $aid . '" loading="lazy"/></a></figure>' . "\n"
					. "<!-- /wp:image -->\n";
			}
			$body .= '<!-- wp:gallery {"linkTo":"media","className":"vit-fb-gallery"} -->' . "\n"
				. '<figure class="wp-block-gallery has-nested-images columns-default is-cropped vit-fb-gallery">' . $inner . "</figure>\n<!-- /wp:gallery -->\n\n";
		}
		if ( ! empty( $p['more'] ) ) {
			$body .= "<!-- wp:paragraph {\"className\":\"vit-fb-more\"} -->\n<p class=\"vit-fb-more\">Ще " . (int) $p['more'] . ' фото — у <a href="' . esc_url( $p['url'] ) . '" target="_blank" rel="noopener">дописі у Facebook</a>.</p>' . "\n<!-- /wp:paragraph -->\n\n";
		}
		$body .= "<!-- wp:paragraph {\"className\":\"vit-fb-src\"} -->\n<p class=\"vit-fb-src\">Джерело: <a href=\"" . esc_url( $p['url'] ) . '" target="_blank" rel="noopener">спільнота ліцею у Facebook</a>' . ( $p['author'] ? ', ' . esc_html( $p['author'] ) : '' ) . ".</p>\n<!-- /wp:paragraph -->\n";

		$cat_ids = array();
		foreach ( (array) $p['cats'] as $slug ) {
			if ( isset( $cats[ $slug ] ) ) {
				$cat_ids[] = $cats[ $slug ];
			}
		}

		$id = wp_insert_post( wp_slash( array(
			'post_type'     => 'post',
			'post_status'   => 'publish',
			'post_title'    => $p['title'],
			'post_content'  => $body,
			'post_excerpt'  => $p['excerpt'] ?? '',
			'post_date'     => $p['date'] . ':00',
			'post_category' => $cat_ids ?: array( $cats['zhyttia'] ?? 1 ),
		) ) );
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		update_post_meta( $id, 'vit_fb_id', (string) $p['id'] );
		update_post_meta( $id, 'vit_fb_url', $p['url'] );
		if ( $att ) {
			set_post_thumbnail( $id, $att[0] );
			foreach ( $att as $aid ) {
				wp_update_post( array( 'ID' => $aid, 'post_parent' => $id ) );
			}
		}
	}

	// Справжні новини є — зразкові більше не потрібні (у кошик, не назавжди).
	$demo = get_posts( array( 'post_type' => 'post', 'meta_key' => 'vit_demo', 'meta_value' => '1', 'fields' => 'ids', 'posts_per_page' => -1 ) );
	foreach ( $demo as $d ) {
		wp_trash_post( $d );
	}

	wp_defer_term_counting( false );
	update_option( 'vitryla_fb_stamp', $stamp );
	delete_transient( 'vitryla_fb_lock' );
	if ( ! current_user_can( 'unfiltered_html' ) ) {
		kses_init_filters();
	}
}

/*
 * Адреси новин — латиницею й короткі: /2026/10/13/123/.
 * Кириличні адреси ламаються при копіюванні сайту й погано читаються в месенджерах.
 */
add_action( 'init', function (): void {
	if ( get_option( 'vitryla_permalinks_v' ) === '1' ) {
		return;
	}
	global $wp_rewrite;
	$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%post_id%/' );
	flush_rewrite_rules( false );
	update_option( 'vitryla_permalinks_v', '1' );
}, 200 );

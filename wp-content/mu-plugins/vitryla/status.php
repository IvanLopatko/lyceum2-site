<?php
/**
 * Строки оприлюднення за статтею 30 Закону «Про освіту».
 *
 * Частина 4 статті 30: документи оприлюднюються не пізніше ніж через
 * 10 робочих днів з дня затвердження.
 * Закон «Про відкритість використання публічних коштів», ст. 3:
 * фінансова інформація — щоквартально, не пізніше 35 днів після кварталу.
 *
 * Свята не враховуються — лише субота й неділя. Тому строк,
 * який рахує сайт, трохи суворіший за фактичний. Це навмисно.
 *
 * @package vitryla
 */

defined( 'ABSPATH' ) || exit;

const VITRYLA_PUBLISH_DEADLINE_DAYS = 10; // робочих днів
const VITRYLA_FRESH_DAYS           = 30; // скільки днів документ вважається новим

/**
 * Кількість робочих днів між двома датами.
 */
function vitryla_working_days( string $from, string $to ): ?int {
	try {
		$a = new DateTimeImmutable( $from );
		$b = new DateTimeImmutable( $to );
	} catch ( Exception $e ) {
		return null;
	}
	if ( $b < $a ) {
		return 0;
	}

	$days = 0;
	$cur  = $a;
	while ( $cur < $b ) {
		$cur = $cur->modify( '+1 day' );
		if ( (int) $cur->format( 'N' ) < 6 ) {
			++$days;
		}
	}
	return $days;
}

/**
 * Стан документа.
 *
 * @return array{key:string,label:string,class:string,note:string}
 */
function vitryla_doc_state( int $post_id ): array {
	$approved  = (string) get_post_meta( $post_id, 'vit_approved', true );
	$published = (string) get_post_meta( $post_id, 'vit_published', true );
	$valid     = (string) get_post_meta( $post_id, 'vit_valid', true );
	$today     = current_time( 'Y-m-d' );

	if ( 'archive' === $valid ) {
		return array( 'key' => 'archive', 'label' => 'Архівний', 'class' => 'is-archive', 'note' => '' );
	}

	if ( ! $approved ) {
		return array( 'key' => 'nodate', 'label' => 'Дата не вказана', 'class' => 'is-stale', 'note' => 'Поставте дату затвердження — без неї строк не рахується.' );
	}

	if ( $published ) {
		$fresh = vitryla_working_days( $published, $today );
		if ( null !== $fresh && $fresh <= VITRYLA_FRESH_DAYS ) {
			return array( 'key' => 'fresh', 'label' => 'Нове', 'class' => 'is-fresh', 'note' => '' );
		}
		return array( 'key' => 'ok', 'label' => 'Оприлюднено', 'class' => '', 'note' => '' );
	}

	$passed = vitryla_working_days( $approved, $today );
	if ( null === $passed ) {
		return array( 'key' => 'nodate', 'label' => 'Дата не розпізнана', 'class' => 'is-stale', 'note' => '' );
	}

	$left = VITRYLA_PUBLISH_DEADLINE_DAYS - $passed;

	if ( $left < 0 ) {
		return array(
			'key'   => 'overdue',
			'label' => 'Прострочено',
			'class' => 'is-stale',
			'note'  => sprintf( 'Минуло %d робочих днів із дня затвердження, строк — %d.', $passed, VITRYLA_PUBLISH_DEADLINE_DAYS ),
		);
	}

	return array(
		'key'   => 'due',
		'label' => sprintf( 'Лишилось %d роб. дн.', $left ),
		'class' => 'is-due',
		'note'  => 'Документ ще не позначено як оприлюднений.',
	);
}

/**
 * Документи, які порушують або ось-ось порушать строк.
 *
 * @return WP_Post[]
 */
function vitryla_docs_needing_attention(): array {
	$posts = get_posts( array(
		'post_type'      => 'vit_document',
		'posts_per_page' => -1,
		'post_status'    => array( 'publish', 'draft', 'pending' ),
	) );

	return array_values( array_filter( $posts, function ( WP_Post $p ): bool {
		return in_array( vitryla_doc_state( $p->ID )['key'], array( 'overdue', 'due', 'nodate' ), true );
	} ) );
}

/**
 * Найближчий строк квартальної фінансової звітності.
 *
 * @return array{quarter:string,period_end:string,deadline:string,days_left:int,published:bool}
 */
function vitryla_finance_deadline(): array {
	$now   = new DateTimeImmutable( current_time( 'Y-m-d' ) );
	$year  = (int) $now->format( 'Y' );
	$month = (int) $now->format( 'n' );

	// Квартал, що завершився останнім.
	$q      = (int) ceil( $month / 3 ) - 1;
	if ( 0 === $q ) {
		$q    = 4;
		$year = $year - 1;
	}
	$endMonth   = $q * 3;
	$period_end = new DateTimeImmutable( sprintf( '%d-%02d-01', $year, $endMonth ) );
	$period_end = $period_end->modify( 'last day of this month' );
	$deadline   = $period_end->modify( '+35 days' );

	$roman = array( 1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV' );

	// Чи є фінансовий документ, оприлюднений після завершення кварталу.
	$published = false;
	$docs      = get_posts( array(
		'post_type'      => 'vit_document',
		'posts_per_page' => -1,
		'tax_query'      => array( array(
			'taxonomy' => 'vit_doc_section',
			'field'    => 'slug',
			'terms'    => 'finansy',
		) ),
	) );
	foreach ( $docs as $d ) {
		$p = (string) get_post_meta( $d->ID, 'vit_published', true );
		if ( $p && $p > $period_end->format( 'Y-m-d' ) ) {
			$published = true;
			break;
		}
	}

	return array(
		'quarter'    => sprintf( '%s квартал %d', $roman[ $q ], $year ),
		'period_end' => $period_end->format( 'd.m.Y' ),
		'deadline'   => $deadline->format( 'd.m.Y' ),
		'days_left'  => (int) $now->diff( $deadline )->format( '%r%a' ),
		'published'  => $published,
	);
}

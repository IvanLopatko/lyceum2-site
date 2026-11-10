<?php
/**
 * Тема «Вітрила юності» — Біляївський ліцей №2.
 *
 * Правило поділу: усе про ВИГЛЯД — тут і в theme.json.
 * Усе про ДАНІ (типи записів, поля) — у mu-plugin, щоб пережило зміну теми.
 *
 * @package vitryla
 */

defined( 'ABSPATH' ) || exit;

const VITRYLA_VERSION = '0.1.0';

/* ─────────────────────────────────────────────────────────
   Підтримка можливостей
   ───────────────────────────────────────────────────────── */
add_action( 'after_setup_theme', function () {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_editor_style( 'style.css' );
	load_theme_textdomain( 'vitryla', get_template_directory() . '/languages' );
} );

/* ─────────────────────────────────────────────────────────
   Стилі та шрифти
   ───────────────────────────────────────────────────────── */
add_action( 'wp_enqueue_scripts', function () {
	// TODO перед запуском: перенести шрифти на свій сервер,
	// щоб сайт не залежав від Google і працював без зовнішніх запитів.
	wp_enqueue_style(
		'vitryla-fonts',
		'https://fonts.googleapis.com/css2?family=Unbounded:wght@600;700;800&family=Onest:wght@400;500;600;700&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'vitryla',
		get_stylesheet_uri(),
		array( 'vitryla-fonts' ),
		VITRYLA_VERSION
	);

	$dir = get_template_directory();
	$uri = get_template_directory_uri();

	wp_enqueue_style( 'vitryla-icons', $uri . '/assets/css/icons.css', array( 'vitryla' ), (string) filemtime( $dir . '/assets/css/icons.css' ) );
	wp_enqueue_style( 'vitryla-ui', $uri . '/assets/css/ui.css', array( 'vitryla', 'vitryla-icons' ), (string) filemtime( $dir . '/assets/css/ui.css' ) );

	wp_enqueue_script( 'vitryla-motion', $uri . '/assets/js/motion.js', array(), (string) filemtime( $dir . '/assets/js/motion.js' ), array(
		'in_footer' => true,
		'strategy'  => 'defer',
	) );
} );

/* Стилі компонентів — і в редакторі, щоб сторінки там виглядали так само. */
add_action( 'after_setup_theme', function () {
	add_editor_style( array( 'assets/css/icons.css', 'assets/css/ui.css' ) );
}, 11 );

/* ─────────────────────────────────────────────────────────
   Категорія власних патернів
   ───────────────────────────────────────────────────────── */
add_action( 'init', function () {
	register_block_pattern_category( 'vitryla', array(
		'label' => __( 'Ліцей №2', 'vitryla' ),
	) );
} );

/*
 * Посилання «Перейти до вмісту» своє не додаємо: блокові теми
 * отримують його від ядра WordPress (id="wp-skip-link"), і воно
 * саме знаходить елемент <main>. Друге таке посилання лише
 * заважало б тим, хто користується клавіатурою.
 */

/* ─────────────────────────────────────────────────────────
   Панель доступності: розмір шрифту, контраст, інтервал.
   Свідомо без сторонніх плагінів-накладок — вони імітують
   відповідність і ламають читання з екрана.
   ───────────────────────────────────────────────────────── */
add_action( 'wp_footer', function () {
	?>
	<button type="button" class="vit-a11y-toggle" id="vit-a11y-btn"
			aria-expanded="false" aria-controls="vit-a11y-panel"
			aria-label="<?php esc_attr_e( 'Налаштування доступності', 'vitryla' ); ?>">
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
			 stroke-width="2" stroke-linecap="round" aria-hidden="true">
			<circle cx="12" cy="4.5" r="2"/><path d="M4 8.5h16M12 8.5V15M12 15l-3.5 6M12 15l3.5 6"/>
		</svg>
	</button>

	<div class="vit-a11y-panel" id="vit-a11y-panel" hidden>
		<h2><?php esc_html_e( 'Доступність', 'vitryla' ); ?></h2>

		<div class="vit-a11y-row" role="group" aria-label="<?php esc_attr_e( 'Розмір шрифту', 'vitryla' ); ?>">
			<button type="button" data-scale="1"    aria-pressed="true">А</button>
			<button type="button" data-scale="1.15" aria-pressed="false" style="font-size:1.05rem">А</button>
			<button type="button" data-scale="1.3"  aria-pressed="false" style="font-size:1.2rem">А</button>
		</div>

		<div class="vit-a11y-row" role="group" aria-label="<?php esc_attr_e( 'Міжрядковий інтервал', 'vitryla' ); ?>">
			<button type="button" data-lead="1.62" aria-pressed="true"><?php esc_html_e( 'Звичайний', 'vitryla' ); ?></button>
			<button type="button" data-lead="2"    aria-pressed="false"><?php esc_html_e( 'Просторий', 'vitryla' ); ?></button>
		</div>

		<div class="vit-a11y-row" role="group" aria-label="<?php esc_attr_e( 'Контраст', 'vitryla' ); ?>">
			<button type="button" data-contrast="0" aria-pressed="true"><?php esc_html_e( 'Звичайний', 'vitryla' ); ?></button>
			<button type="button" data-contrast="1" aria-pressed="false"><?php esc_html_e( 'Високий', 'vitryla' ); ?></button>
		</div>

		<button type="button" class="reset" id="vit-a11y-reset"><?php esc_html_e( 'Скинути налаштування', 'vitryla' ); ?></button>
	</div>

	<script>
	(function () {
		var btn   = document.getElementById('vit-a11y-btn');
		var panel = document.getElementById('vit-a11y-panel');
		var root  = document.documentElement;
		var KEY   = 'vitryla-a11y';
		var st    = { scale: 1, lead: 1.62, contrast: 0 };

		try { st = Object.assign(st, JSON.parse(localStorage.getItem(KEY) || '{}')); } catch (e) {}

		function paint() {
			root.style.setProperty('--a11y-scale', st.scale);
			root.style.setProperty('--a11y-leading', st.lead);
			document.body.classList.toggle('a11y-contrast', st.contrast === 1);
			panel.querySelectorAll('[data-scale]').forEach(function (b) {
				b.setAttribute('aria-pressed', String(parseFloat(b.dataset.scale) === parseFloat(st.scale)));
			});
			panel.querySelectorAll('[data-lead]').forEach(function (b) {
				b.setAttribute('aria-pressed', String(parseFloat(b.dataset.lead) === parseFloat(st.lead)));
			});
			panel.querySelectorAll('[data-contrast]').forEach(function (b) {
				b.setAttribute('aria-pressed', String(parseInt(b.dataset.contrast, 10) === st.contrast));
			});
			try { localStorage.setItem(KEY, JSON.stringify(st)); } catch (e) {}
		}

		btn.addEventListener('click', function () {
			var open = panel.hasAttribute('hidden');
			panel.toggleAttribute('hidden', !open);
			btn.setAttribute('aria-expanded', String(open));
			if (open) { panel.querySelector('button').focus(); }
		});

		panel.addEventListener('click', function (e) {
			var b = e.target.closest('button'); if (!b) return;
			if (b.dataset.scale)    { st.scale = parseFloat(b.dataset.scale); }
			if (b.dataset.lead)     { st.lead = parseFloat(b.dataset.lead); }
			if (b.dataset.contrast) { st.contrast = parseInt(b.dataset.contrast, 10); }
			if (b.id === 'vit-a11y-reset') { st = { scale: 1, lead: 1.62, contrast: 0 }; }
			paint();
		});

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && !panel.hasAttribute('hidden')) {
				panel.setAttribute('hidden', '');
				btn.setAttribute('aria-expanded', 'false');
				btn.focus();
			}
		});

		paint();
	})();
	</script>
	<?php
} );

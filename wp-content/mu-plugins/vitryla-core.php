<?php
/**
 * Plugin Name: Ліцей №2 — дані
 * Description: Типи записів, поля й логіка строків оприлюднення за статтею 30. Лежить у mu-plugins, щоб дані пережили будь-яку зміну теми.
 * Version: 0.1.0
 * Requires PHP: 8.1
 *
 * @package vitryla
 */

defined( 'ABSPATH' ) || exit;

define( 'VITRYLA_CORE_DIR', __DIR__ . '/vitryla' );

require_once VITRYLA_CORE_DIR . '/cpt.php';
require_once VITRYLA_CORE_DIR . '/meta.php';
require_once VITRYLA_CORE_DIR . '/status.php';
require_once VITRYLA_CORE_DIR . '/admin.php';
require_once VITRYLA_CORE_DIR . '/settings.php';
require_once VITRYLA_CORE_DIR . '/shortcodes.php';
require_once VITRYLA_CORE_DIR . '/scaffold.php';
require_once VITRYLA_CORE_DIR . '/content.php';
require_once VITRYLA_CORE_DIR . '/demo.php';
require_once VITRYLA_CORE_DIR . '/ui.php';
require_once VITRYLA_CORE_DIR . '/pages-content.php';
require_once VITRYLA_CORE_DIR . '/facts.php';
require_once VITRYLA_CORE_DIR . '/fb-import.php';

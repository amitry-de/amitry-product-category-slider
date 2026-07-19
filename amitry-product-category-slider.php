<?php
/**
 * Plugin Name:          Amitry Product & Category Slider for WooCommerce
 * Plugin URI:           https://amitry.de/amitry-product-category-slider/
 * Description:          Display WooCommerce products or product categories as a responsive, customizable slider. Gutenberg block, Elementor widget and shortcode.
 * Version:              4.3.0
 * Requires at least:    6.3
 * Tested up to:         7.0
 * Requires PHP:         7.4
 * Requires Plugins:     woocommerce
 * Author:               Daniel & Yuzay
 * Author URI:           https://amitry.de
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          amitry-product-category-slider
 * Domain Path:          /languages
 * WC requires at least: 7.0
 * WC tested up to:      9.6
 *
 * @package AmitryProductCategorySlider
 */

// Hard exit on direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ──────────────────────────────────────────────────────────────────────────
 * Plugin Constants
 *
 * All constants use the WCSP_ prefix (carried over from previous versions
 * for backward compatibility with existing Pro add-on integrations).
 * ────────────────────────────────────────────────────────────────────────── */

define( 'WCSP_VERSION', '4.3.0' );

/**
 * Public Hook API version. Pro add-ons compare against this constant
 * to verify they are compatible with the free plugin's hook surface.
 *
 * Bump this only when breaking changes are made to public hooks.
 */
define( 'WCSP_HOOK_VERSION', '1' );

define( 'WCSP_PLUGIN_FILE', __FILE__ );
define( 'WCSP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCSP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WCSP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WCSP_TEXT_DOMAIN', 'amitry-product-category-slider' );
define( 'WCSP_SLUG', 'amitry-product-category-slider' );

/* ──────────────────────────────────────────────────────────────────────────
 * Activation: WooCommerce Dependency Check
 * ────────────────────────────────────────────────────────────────────────── */

/**
 * On activation: refuse if WooCommerce is not active.
 */
function wcsp_activation_check() {
	$wc_active = class_exists( 'WooCommerce' )
		|| in_array( 'woocommerce/woocommerce.php', (array) get_option( 'active_plugins', array() ), true );

	if ( ! $wc_active ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html__( 'Amitry Product & Category Slider for WooCommerce requires WooCommerce. Please install and activate WooCommerce first.', 'amitry-product-category-slider' ),
			esc_html__( 'Plugin dependency missing', 'amitry-product-category-slider' ),
			array( 'back_link' => true )
		);
	}
}
register_activation_hook( __FILE__, 'wcsp_activation_check' );

/**
 * Runtime: show admin notice if WooCommerce gets deactivated later.
 */
function wcsp_admin_woo_notice() {
	if ( class_exists( 'WooCommerce' ) ) {
		return;
	}
	?>
	<div class="notice notice-error">
		<p>
			<strong>Amitry Product &amp; Category Slider for WooCommerce:</strong>
			<?php esc_html_e( 'WooCommerce is not active. Please activate WooCommerce to use this plugin.', 'amitry-product-category-slider' ); ?>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'wcsp_admin_woo_notice' );

/* ──────────────────────────────────────────────────────────────────────────
 * WooCommerce Compatibility Declarations
 *
 * Declare compatibility with WooCommerce features. Must fire on
 * `before_woocommerce_init` (very early, before WC's compatibility check).
 * ────────────────────────────────────────────────────────────────────────── */

function wcsp_declare_wc_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
}
add_action( 'before_woocommerce_init', 'wcsp_declare_wc_compatibility' );

/* ──────────────────────────────────────────────────────────────────────────
 * Plugin Action Links (Plugins screen)
 * ────────────────────────────────────────────────────────────────────────── */

/**
 * Add Settings and Get Pro links to the plugin row on the Plugins screen.
 *
 * The Settings link is only added when the settings page actually exists
 * as a registered admin page. Linking to a non-existent admin page
 * triggers a `preg_replace() null` deprecation in WP core's
 * get_plugin_page_hookname() on PHP 8.1+.
 *
 * @param array $links Existing action links.
 * @return array
 */
function wcsp_plugin_action_links( $links ) {
	// Settings link - only when the page is actually wired up by WCSP_Settings.
	if ( wcsp_settings_page_is_registered() ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=wcsp-settings' ) ),
			esc_html__( 'Settings', 'amitry-product-category-slider' )
		);
		array_unshift( $links, $settings_link );
	}

	// Pro upsell link - always shown.
	$pro_link = sprintf(
		'<a href="%s" target="_blank" rel="noopener noreferrer" style="color:#2d5a3f;font-weight:600;">%s</a>',
		esc_url( 'https://amitry.de/amitry-product-category-slider/' ),
		esc_html__( 'Get Pro', 'amitry-product-category-slider' )
	);
	$links[] = $pro_link;

	return $links;
}
add_filter( 'plugin_action_links_' . WCSP_PLUGIN_BASENAME, 'wcsp_plugin_action_links' );

/**
 * Check whether the settings admin page has been registered.
 *
 * Walks the global $submenu array (populated by add_submenu_page() etc.)
 * and looks for our slug. This avoids hard-coding a class check that
 * would need to know about admin-only files which may not be loaded.
 *
 * @return bool
 */
function wcsp_settings_page_is_registered() {
	global $submenu, $menu;

	if ( is_array( $menu ) ) {
		foreach ( $menu as $item ) {
			if ( isset( $item[2] ) && 'wcsp-settings' === $item[2] ) {
				return true;
			}
		}
	}

	if ( is_array( $submenu ) ) {
		foreach ( $submenu as $items ) {
			if ( ! is_array( $items ) ) {
				continue;
			}
			foreach ( $items as $item ) {
				if ( isset( $item[2] ) && 'wcsp-settings' === $item[2] ) {
					return true;
				}
			}
		}
	}

	return false;
}

/* ──────────────────────────────────────────────────────────────────────────
 * Bootstrap
 *
 * Load core classes and boot the main plugin instance once WooCommerce
 * is loaded. Elementor integration is wired separately because Elementor
 * loads later than WooCommerce in the typical stack.
 * ────────────────────────────────────────────────────────────────────────── */

/**
 * Load core plugin classes.
 *
 * Files are required here (not autoloaded) to keep the file count low and
 * the load path explicit. If the project grows, this can be swapped for
 * a PSR-4 autoloader without touching the rest of the codebase.
 */
function wcsp_load_core() {
	$base = WCSP_PLUGIN_DIR . 'includes/';

	// Main controller.
	require_once $base . 'class-wcsp-plugin.php';

	// Data sources.
	require_once $base . 'data/class-wcsp-data-source.php';
	require_once $base . 'data/class-wcsp-data-source-products.php';
	require_once $base . 'data/class-wcsp-data-source-categories.php';

	// Renderers.
	require_once $base . 'render/class-wcsp-renderer.php';
	require_once $base . 'render/class-wcsp-card-product.php';
	require_once $base . 'render/class-wcsp-card-category.php';

	// Integrations.
	require_once $base . 'class-wcsp-block.php';
	require_once $base . 'class-wcsp-shortcode.php';
	require_once $base . 'class-wcsp-assets.php';

	// Admin.
	if ( is_admin() ) {
		require_once $base . 'admin/class-wcsp-admin.php';
		require_once $base . 'admin/class-wcsp-settings.php';
	}
}

/**
 * Boot the main plugin instance.
 *
 * Refuses to run if WooCommerce is not active (notice handled separately).
 */
function wcsp_boot() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	wcsp_load_core();

	WCSP_Plugin::instance()->init();
}
add_action( 'plugins_loaded', 'wcsp_boot' );

/**
 * Wire up the Elementor integration once Elementor is loaded.
 *
 * Priority 20 ensures Elementor has had a chance to fire its
 * `elementor/loaded` flag.
 */
function wcsp_init_elementor() {
	if ( ! did_action( 'elementor/loaded' ) && ! class_exists( '\Elementor\Plugin' ) ) {
		return;
	}
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	require_once WCSP_PLUGIN_DIR . 'includes/class-wcsp-elementor.php';
	WCSP_Elementor::instance()->register_hooks();
}
add_action( 'plugins_loaded', 'wcsp_init_elementor', 20 );

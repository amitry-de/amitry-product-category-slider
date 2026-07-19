<?php
/**
 * Main plugin controller.
 *
 * Responsible for booting all subsystems and exposing the public
 * hook surface that Pro add-ons rely on.
 *
 * @package AmitryProductCategorySlider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WCSP_Plugin
 *
 * Singleton main controller. Instantiated once via `WCSP_Plugin::instance()`
 * from the bootstrap file.
 */
final class WCSP_Plugin {

	/**
	 * Option key for the plugin's global settings array.
	 *
	 * Stored as a single serialized array under this key in wp_options.
	 * Pro add-ons can read this via get_option() but must not write to it
	 * directly - use the documented update_settings() method instead.
	 */
	const OPTION_KEY = 'wcsp_settings';

	/**
	 * Singleton instance.
	 *
	 * @var WCSP_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Whether init() has already run, to guard against double-bootstrapping.
	 *
	 * @var bool
	 */
	private $initialized = false;

	/**
	 * Get the singleton instance.
	 *
	 * @return WCSP_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor - singleton pattern.
	 */
	private function __construct() {}

	/**
	 * Boot the plugin.
	 *
	 * Wires up translations, registers the block, shortcode and assets.
	 * Admin classes are wired up only when is_admin() to keep frontend
	 * memory lean.
	 */
	public function init() {
		if ( $this->initialized ) {
			return;
		}
		$this->initialized = true;

		// Custom image size for sharp card thumbnails.
		add_action( 'after_setup_theme', array( $this, 'register_image_size' ) );

		// Register block, shortcode, assets.
		add_action( 'init', array( $this, 'register_integrations' ) );

		// Admin subsystem.
		if ( is_admin() ) {
			add_action( 'init', array( $this, 'register_admin' ) );
		}

		/**
		 * Fires after the main plugin controller has initialized.
		 *
		 * Pro add-ons should use this hook to register their own
		 * integrations against the public hook surface.
		 *
		 * @since 4.0.0
		 *
		 * @param WCSP_Plugin $plugin The main plugin instance.
		 */
		do_action( 'wcsp_init', $this );
	}

	/**
	 * Image size handling.
	 *
	 * The slider no longer registers a hard-cropped size. Cards request a
	 * proportional size ("large") and the visible aspect ratio is applied
	 * with CSS (aspect-ratio box plus object-fit), so photos keep their
	 * real proportions on disk and the "show full image" mode can display
	 * the true original. Nothing to register here anymore; the method is
	 * kept as a stable, filterable hook point.
	 */
	public function register_image_size() {
		/**
		 * Fires when the plugin would register image sizes. Kept for
		 * add-ons that want to register their own proportional sizes.
		 */
		do_action( 'wcsp_register_image_sizes' );
	}

	/**
	 * Register block, shortcode, asset manager.
	 *
	 * Each integration is wrapped in a class_exists() guard so partial
	 * loads (e.g. during install) don't fatal.
	 */
	public function register_integrations() {
		if ( class_exists( 'WCSP_Assets' ) ) {
			WCSP_Assets::instance()->register_hooks();
		}
		if ( class_exists( 'WCSP_Block' ) ) {
			WCSP_Block::instance()->register();
		}
		if ( class_exists( 'WCSP_Shortcode' ) ) {
			WCSP_Shortcode::instance()->register();
		}
	}

	/**
	 * Wire up admin subsystem (settings page, plugin-row links etc.).
	 */
	public function register_admin() {
		if ( class_exists( 'WCSP_Admin' ) ) {
			WCSP_Admin::instance()->register_hooks();
		}
	}

	/* ──────────────────────────────────────────────────────────────────
	 * Settings API
	 *
	 * Centralized accessor for the plugin's global settings. All
	 * subsystems should read defaults via get_setting() so the Settings
	 * page can override them globally without touching subsystem code.
	 * ────────────────────────────────────────────────────────────────── */

	/**
	 * Hard-coded factory defaults.
	 *
	 * These are the values used when no setting has ever been saved.
	 * They mirror the block attribute defaults so blocks rendered before
	 * any setting was saved behave identically to freshly-inserted ones.
	 *
	 * @return array
	 */
	public static function default_settings() {
		return array(
			// Data defaults.
			'sliderType'         => 'categories',
			'productCount'       => 12,
			'maxCategories'      => 12,
			'productFilter'      => 'newest',
			'categorySortBy'     => 'name',
			'hideEmpty'          => true,

			// Layout defaults.
			'slidesPerViewDesktop' => 4,
			'slidesPerViewTablet'  => 2,
			'slidesPerViewMobile'  => 1,
			'scaleDesktop'         => 100,
			'scaleTablet'          => 100,
			'scaleMobile'          => 100,
			'spaceBetween'         => 24,

			// Behavior defaults.
			'autoplay'      => false,
			'autoplayDelay' => 3000,
			'loop'          => false,
			'pauseOnHover'  => true,
			'speed'         => 600,
			'touchEnabled'  => true,
			'mouseDrag'     => true,

			// Navigation defaults.
			'showArrows'        => true,
			'showPaginationDots' => false,
			'showScrollbar'     => false,
			'showProgress'      => false,
			'showCounter'       => false,
			'keyboardEnabled'   => false,

			// Style defaults.
			'styleVariant'    => 'clean-card',
			'imageShape'      => 'rounded',
			'aspectRatio'     => '4-3',
			'cardRadius'      => 16,
			'cardBackgroundColor' => '#ffffff',
			'cardPadding'     => 20,
			'shadowIntensity' => 'soft',
			'hoverEffect'     => 'lift',
			'showOverlayGradient' => false,

			// Performance.
			'conditionalAssets' => true,
			'queryCache'        => false,
			'queryCacheTtl'     => 300,
			'debugMode'         => false,
		);
	}

	/**
	 * Get the merged settings array (saved values on top of defaults).
	 *
	 * @return array
	 */
	public function get_settings() {
		$saved = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		$merged = array_merge( self::default_settings(), $saved );

		/**
		 * Filter the merged settings array.
		 *
		 * Pro add-ons can use this to inject defaults for their own
		 * extension-reserved block attributes.
		 *
		 * @since 4.0.0
		 *
		 * @param array $merged Merged settings (defaults + saved).
		 * @param array $saved  Raw saved settings from the database.
		 */
		return apply_filters( 'wcsp_settings', $merged, $saved );
	}

	/**
	 * Get a single setting value with a fallback.
	 *
	 * @param string $key      Setting key.
	 * @param mixed  $fallback Fallback value if the key does not exist.
	 * @return mixed
	 */
	public function get_setting( $key, $fallback = null ) {
		$settings = $this->get_settings();
		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $fallback;
	}

	/**
	 * Persist settings to the database.
	 *
	 * Only keys listed in default_settings() (plus those whitelisted
	 * by Pro add-ons via `wcsp_allowed_setting_keys`) are saved.
	 *
	 * @param array $new_settings New settings array.
	 * @return bool True on success.
	 */
	public function update_settings( array $new_settings ) {
		$allowed_keys = array_keys( self::default_settings() );

		/**
		 * Filter the allowed setting keys.
		 *
		 * Pro add-ons must register their own setting keys through this
		 * filter, otherwise their values are dropped on save.
		 *
		 * @since 4.0.0
		 *
		 * @param array $allowed_keys Whitelisted setting keys.
		 */
		$allowed_keys = apply_filters( 'wcsp_allowed_setting_keys', $allowed_keys );

		$filtered = array();
		foreach ( $allowed_keys as $key ) {
			if ( array_key_exists( $key, $new_settings ) ) {
				$filtered[ $key ] = $new_settings[ $key ];
			}
		}

		return update_option( self::OPTION_KEY, $filtered );
	}

	/**
	 * Wipe settings (used by Reset to Defaults button on settings page).
	 *
	 * @return bool
	 */
	public function reset_settings() {
		return delete_option( self::OPTION_KEY );
	}
}

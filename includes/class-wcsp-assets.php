<?php
/**
 * Frontend asset manager.
 *
 * Enqueues Swiper, our frontend JS and the compiled stylesheet on the
 * front-end. Conditional loading (only on pages containing a slider)
 * is added in a later stage.
 *
 * @package AmitryProductCategorySlider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WCSP_Assets
 */
final class WCSP_Assets {

	/** Style/script handles. */
	const STYLE_HANDLE        = 'wcsp-frontend';
	const SWIPER_STYLE_HANDLE = 'wcsp-swiper';
	const SWIPER_SCRIPT_HANDLE = 'wcsp-swiper';
	const SCRIPT_HANDLE        = 'wcsp-frontend';

	/**
	 * Singleton instance.
	 *
	 * @var WCSP_Assets|null
	 */
	private static $instance = null;

	/**
	 * Get singleton.
	 *
	 * @return WCSP_Assets
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {}

	/**
	 * Wire up WordPress hooks.
	 */
	public function register_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor' ) );
	}

	/**
	 * Enqueue Swiper and our frontend JS in the block editor so
	 * the editor preview can use the same slider engine as the front-end.
	 *
	 * Editor preview will set a flag (`data-wcsp-context="editor"`) on
	 * each wrapper so frontend.js can disable autoplay there.
	 */
	public function enqueue_editor() {
		// Swiper CSS.
		wp_enqueue_style(
			self::SWIPER_STYLE_HANDLE,
			WCSP_PLUGIN_URL . 'assets/vendor/swiper/swiper-bundle.min.css',
			array(),
			'11.1.14'
		);

		// Swiper JS.
		wp_enqueue_script(
			self::SWIPER_SCRIPT_HANDLE,
			WCSP_PLUGIN_URL . 'assets/vendor/swiper/swiper-bundle.min.js',
			array(),
			'11.1.14',
			true
		);

		// Our frontend JS (the editor will call wcsp.init() after each SSR update).
		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			WCSP_PLUGIN_URL . 'assets/js/frontend.js',
			array( self::SWIPER_SCRIPT_HANDLE ),
			WCSP_VERSION,
			true
		);
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * Order: Swiper CSS -> our compiled CSS (so our rules win where
	 * needed), Swiper JS -> our frontend JS (depends on Swiper).
	 */
	public function enqueue_frontend() {
		if ( is_admin() ) {
			return;
		}

		// Swiper CSS.
		wp_enqueue_style(
			self::SWIPER_STYLE_HANDLE,
			WCSP_PLUGIN_URL . 'assets/vendor/swiper/swiper-bundle.min.css',
			array(),
			'11.1.14'
		);

		// Our compiled stylesheet.
		$style_file = WCSP_PLUGIN_DIR . 'build/style-index.css';
		if ( file_exists( $style_file ) ) {
			wp_enqueue_style(
				self::STYLE_HANDLE,
				WCSP_PLUGIN_URL . 'build/style-index.css',
				array( self::SWIPER_STYLE_HANDLE ),
				WCSP_VERSION
			);
		}

		// Swiper JS.
		wp_enqueue_script(
			self::SWIPER_SCRIPT_HANDLE,
			WCSP_PLUGIN_URL . 'assets/vendor/swiper/swiper-bundle.min.js',
			array(),
			'11.1.14',
			true
		);

		// Our frontend JS.
		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			WCSP_PLUGIN_URL . 'assets/js/frontend.js',
			array( self::SWIPER_SCRIPT_HANDLE ),
			WCSP_VERSION,
			true
		);

		/**
		 * Fires after the free plugin's frontend assets have been enqueued.
		 *
		 * Pro add-ons enqueue their own assets here, depending on
		 * WCSP_Assets::SCRIPT_HANDLE / STYLE_HANDLE so they always load
		 * after the free ones.
		 *
		 * @since 4.0.0
		 */
		do_action( 'wcsp_after_enqueue_frontend' );
	}
}

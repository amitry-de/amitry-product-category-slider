<?php
/**
 * Elementor integration.
 *
 * Registers a single "Amitry Slider" widget that renders through the
 * same WCSP_Renderer used by the block and the shortcode, so the output
 * and styling are identical across all three entry points.
 *
 * This file is only loaded when Elementor and WooCommerce are both
 * active (guarded in the main plugin file), so it can safely reference
 * Elementor base classes.
 *
 * @package AmitryProductCategorySlider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WCSP_Elementor
 */
final class WCSP_Elementor {

	/**
	 * Singleton instance.
	 *
	 * @var WCSP_Elementor|null
	 */
	private static $instance = null;

	/**
	 * Get singleton.
	 *
	 * @return WCSP_Elementor
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
	 * Register Elementor hooks.
	 */
	public function register_hooks() {
		add_action( 'elementor/widgets/register', array( $this, 'register_widget' ) );
	}

	/**
	 * Register the widget with Elementor.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 */
	public function register_widget( $widgets_manager ) {
		require_once WCSP_PLUGIN_DIR . 'includes/class-wcsp-elementor-widget.php';
		$widgets_manager->register( new WCSP_Elementor_Widget() );
	}
}

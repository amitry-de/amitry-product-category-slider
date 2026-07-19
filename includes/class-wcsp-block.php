<?php
/**
 * Gutenberg block registration.
 *
 * Registers the block from the compiled build/ folder. The build folder
 * contains block.json (copied from src/), index.js, style-index.css,
 * index.css and render.php (also copied from src/).
 *
 * @package AmitryProductCategorySlider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WCSP_Block
 */
final class WCSP_Block {

	/**
	 * Singleton.
	 *
	 * @var WCSP_Block|null
	 */
	private static $instance = null;

	/**
	 * Get singleton.
	 *
	 * @return WCSP_Block
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
	 * Register the block from the build folder.
	 *
	 * register_block_type() auto-detects block.json's `editorScript`,
	 * `style`, `render` and other file:./ references. Once registered,
	 * we wire up wp_set_script_translations so the editor JS can pick
	 * up its localized strings from the .json files in /languages.
	 */
	public function register() {
		$build_dir = WCSP_PLUGIN_DIR . 'build';

		if ( ! file_exists( $build_dir . '/block.json' ) ) {
			// Build folder missing - skip registration silently.
			return;
		}

		$block_type = register_block_type( $build_dir );

		// Hook up translations for the editor JS. register_block_type
		// produces a script handle of the form
		// "{namespace}-{name}-editor-script". WordPress will look for a
		// JSON file in the configured /languages directory whose name
		// matches: {text-domain}-{locale}-{md5(relative path of source script)}.json
		if ( $block_type instanceof \WP_Block_Type && ! empty( $block_type->editor_script_handles ) ) {
			foreach ( $block_type->editor_script_handles as $handle ) {
				wp_set_script_translations(
					$handle,
					'amitry-product-category-slider',
					WCSP_PLUGIN_DIR . 'languages'
				);
			}
		}
	}
}

<?php
/**
 * Shortcode handler.
 *
 * Registers the [amitry_slider] shortcode and maps its snake_case
 * attributes onto the camelCase attribute names used by the renderer
 * and the block editor.
 *
 * Example:
 *   [amitry_slider type="products" filter="newest" count="6" slides_desktop="4"]
 *   [amitry_slider type="categories" sort="count" exclude="42,43"]
 *   [amitry_slider type="products" filter="manual" products="12,15,18"]
 *
 * @package AmitryProductCategorySlider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WCSP_Shortcode
 */
final class WCSP_Shortcode {

	/**
	 * Singleton instance.
	 *
	 * @var WCSP_Shortcode|null
	 */
	private static $instance = null;

	/**
	 * Shortcode tag.
	 *
	 * Chosen for backward compatibility with the Elementor widget name
	 * (`amitry_slider`) used in the live version.
	 */
	const TAG = 'amitry_slider';

	/**
	 * Get singleton.
	 *
	 * @return WCSP_Shortcode
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
	 * Register the shortcode.
	 */
	public function register() {
		add_shortcode( self::TAG, array( $this, 'render' ) );
	}

	/**
	 * Shortcode render callback.
	 *
	 * @param array|string $atts    Raw shortcode atts.
	 * @param string|null  $content Inner content (unused).
	 * @return string
	 */
	public function render( $atts, $content = null ) {
		if ( ! is_array( $atts ) ) {
			$atts = array();
		}

		$mapped = $this->map_attributes( $atts );

		return WCSP_Renderer::render( $mapped );
	}

	/**
	 * Map snake_case shortcode attributes onto the camelCase keys used
	 * by the renderer.
	 *
	 * Unknown attributes are silently dropped so a typo doesn't break
	 * the shortcode.
	 *
	 * @param array $atts Raw shortcode atts.
	 * @return array
	 */
	protected function map_attributes( array $atts ) {
		// Lowercase keys to make the shortcode case-insensitive.
		$atts = array_change_key_case( $atts, CASE_LOWER );

		$map = $this->attribute_map();

		/**
		 * Filter the shortcode attribute map.
		 *
		 * Pro add-ons can register their own snake_case shortcode keys.
		 *
		 * @since 4.0.0
		 *
		 * @param array $map snake_case key => camelCase key.
		 */
		$map = apply_filters( 'wcsp_shortcode_attribute_map', $map );

		$out = array();
		foreach ( $atts as $key => $value ) {
			if ( ! isset( $map[ $key ] ) ) {
				continue;
			}
			$target = $map[ $key ];
			$out[ $target ] = $this->cast_value( $target, $value );
		}

		// Convenience aliases for the most-used parameters.
		if ( isset( $atts['type'] ) ) {
			$out['sliderType'] = sanitize_key( (string) $atts['type'] );
		}
		if ( isset( $atts['filter'] ) ) {
			$out['productFilter'] = sanitize_key( (string) $atts['filter'] );
		}
		if ( isset( $atts['sort'] ) ) {
			$out['categorySortBy'] = sanitize_key( (string) $atts['sort'] );
		}
		if ( isset( $atts['count'] ) ) {
			// `count` applies to whichever type is active.
			$type = isset( $out['sliderType'] ) ? $out['sliderType'] : 'categories';
			if ( 'products' === $type ) {
				$out['productCount'] = max( 1, (int) $atts['count'] );
			} else {
				$out['maxCategories'] = max( 1, (int) $atts['count'] );
			}
		}
		if ( isset( $atts['products'] ) ) {
			$out['selectedProducts'] = $this->parse_id_list( (string) $atts['products'] );
		}
		if ( isset( $atts['categories'] ) ) {
			$out['selectedCategories'] = $this->parse_id_list( (string) $atts['categories'] );
		}
		if ( isset( $atts['exclude'] ) ) {
			$out['excludeCategories'] = $this->parse_id_list( (string) $atts['exclude'] );
		}

		return $out;
	}

	/**
	 * The full snake_case → camelCase attribute map.
	 *
	 * @return array
	 */
	protected function attribute_map() {
		return array(
			// Data.
			'type'                  => 'sliderType',
			'filter'                => 'productFilter',
			'sort'                  => 'categorySortBy',
			'count'                 => 'productCount', // overridden in map_attributes based on type.
			'products'              => 'selectedProducts',
			'categories'            => 'selectedCategories',
			'exclude'               => 'excludeCategories', // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- shortcode attribute name, not a query parameter.
			'hide_empty'            => 'hideEmpty',
			'max_categories'        => 'maxCategories',
			'product_count'         => 'productCount',

			// Layout.
			'slides_desktop'        => 'slidesPerViewDesktop',
			'slides_tablet'         => 'slidesPerViewTablet',
			'slides_mobile'         => 'slidesPerViewMobile',
			'scale_desktop'         => 'scaleDesktop',
			'scale_tablet'          => 'scaleTablet',
			'scale_mobile'          => 'scaleMobile',
			'gap'                   => 'spaceBetween',
			'space_between'         => 'spaceBetween',

			// Behavior.
			'autoplay'              => 'autoplay',
			'autoplay_delay'        => 'autoplayDelay',
			'loop'                  => 'loop',
			'pause_on_hover'        => 'pauseOnHover',
			'speed'                 => 'speed',
			'effect'                => 'transitionEffect',
			'touch'                 => 'touchEnabled',
			'mouse_drag'            => 'mouseDrag',

			// Navigation.
			'arrows'                => 'showArrows',
			'dots'                  => 'showPaginationDots',
			'scrollbar'             => 'showScrollbar',
			'progress'              => 'showProgress',
			'counter'               => 'showCounter',
			'keyboard'              => 'keyboardEnabled',

			// Elements.
			'show_image'            => 'showImage',
			'show_title'            => 'showTitle',
			'show_price'            => 'showPrice',
			'show_rating'           => 'showRating',
			'show_excerpt'          => 'showExcerpt',
			'show_add_to_cart'      => 'showAddToCart',
			'show_stock'            => 'showStock',
			'show_sale_badge'       => 'showSaleBadge',
			'show_count'            => 'showCount',
			'show_description'      => 'showDescription',

			// Style.
			'style'                 => 'styleVariant',
			'image_shape'           => 'imageShape',
			'aspect'                => 'aspectRatio',
			'aspect_ratio'          => 'aspectRatio',
			'fit'                   => 'imageFit',
			'image_fit'             => 'imageFit',
			'align'                 => 'contentAlign',
			'content_align'         => 'contentAlign',
			'max_width'             => 'maxWidth',
			'card_radius'           => 'cardRadius',
			'card_bg'               => 'cardBackgroundColor',
			'card_padding'          => 'cardPadding',
			'shadow'                => 'shadowIntensity',
			'hover'                 => 'hoverEffect',
			'overlay'               => 'showOverlayGradient',

			// Arrow / Dots styling.
			'arrow_color'           => 'arrowColor',
			'arrow_bg'              => 'arrowBgColor',
			'arrow_size'            => 'arrowSize',

			// Section / View All.
			'title'                 => 'sectionTitle',
			'subtitle'              => 'sectionSubtitle',
			'view_all'              => 'showViewAllButton',
			'view_all_url'          => 'viewAllUrl',
			'view_all_text'         => 'viewAllText',
		);
	}

	/**
	 * Cast a raw string value to the right PHP type for the given attribute.
	 *
	 * Booleans accept "1"/"0"/"true"/"false"/"yes"/"no".
	 * Numbers go through (int).
	 * Strings are sanitized as text.
	 *
	 * @param string $target_key Target camelCase key.
	 * @param mixed  $value      Raw value.
	 * @return mixed
	 */
	protected function cast_value( $target_key, $value ) {
		// Integer keys.
		$int_keys = array(
			'productCount', 'maxCategories',
			'slidesPerViewDesktop', 'slidesPerViewTablet', 'slidesPerViewMobile',
			'scaleDesktop', 'scaleTablet', 'scaleMobile',
			'spaceBetween', 'autoplayDelay', 'speed',
			'cardRadius', 'cardPadding', 'arrowSize',
			'maxWidth',
		);
		if ( in_array( $target_key, $int_keys, true ) ) {
			return (int) $value;
		}

		// Boolean keys.
		$bool_keys = array(
			'hideEmpty',
			'autoplay', 'loop', 'pauseOnHover', 'touchEnabled', 'mouseDrag',
			'showArrows', 'showPaginationDots', 'showScrollbar', 'showProgress', 'showCounter', 'keyboardEnabled',
			'showImage', 'showTitle', 'showPrice', 'showRating', 'showExcerpt',
			'showAddToCart', 'showStock', 'showSaleBadge', 'showCount', 'showDescription',
			'showOverlayGradient', 'showViewAllButton',
		);
		if ( in_array( $target_key, $bool_keys, true ) ) {
			return $this->to_bool( $value );
		}

		// Default: string with light sanitization.
		return sanitize_text_field( (string) $value );
	}

	/**
	 * Loose boolean parser.
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	protected function to_bool( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}
		$v = strtolower( trim( (string) $value ) );
		return in_array( $v, array( '1', 'true', 'yes', 'on' ), true );
	}

	/**
	 * Parse a comma-separated ID list into an integer array.
	 *
	 * @param string $list "12,15,18".
	 * @return int[]
	 */
	protected function parse_id_list( $list ) {
		$parts = explode( ',', $list );
		$ids   = array();
		foreach ( $parts as $p ) {
			$p = trim( $p );
			if ( '' === $p ) {
				continue;
			}
			$id = (int) $p;
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}
		return $ids;
	}
}

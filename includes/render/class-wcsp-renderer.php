<?php
/**
 * Slider renderer.
 *
 * Takes a normalized attributes array, fetches items from the appropriate
 * data source, and renders the slider HTML skeleton with cards inside.
 *
 * At Stage 2 the renderer outputs a static, CSS-only grid. Swiper.js
 * initialization and inline behavior styles are added in a later stage.
 *
 * @package AmitryProductCategorySlider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WCSP_Renderer
 */
class WCSP_Renderer {

	/**
	 * Render a slider from an attributes array.
	 *
	 * This is the single entry point used by the block render_callback,
	 * the shortcode handler and the Elementor widget.
	 *
	 * @param array $atts       Slider attributes (already merged with defaults).
	 * @param bool  $skip_outer When true, emit only the inner content without
	 *                          the .wcsp-outer wrapper. The block editor's
	 *                          ServerSideRender uses this because React wraps
	 *                          its own outer around us.
	 * @return string Sanitized HTML.
	 */
	public static function render( array $atts, $skip_outer = false ) {
		// Merge with plugin defaults so callers don't have to pass every key.
		if ( class_exists( 'WCSP_Plugin' ) ) {
			$defaults = WCSP_Plugin::default_settings();
			$atts     = array_merge( $defaults, $atts );
		}

		/**
		 * Filter the attributes right before rendering.
		 *
		 * Pro add-ons can use this to inject extension-reserved attributes
		 * (Pro-only settings stored on the same slider).
		 *
		 * @since 4.0.0
		 *
		 * @param array $atts Final attributes.
		 */
		$atts = apply_filters( 'wcsp_render_attributes', $atts );

		$type = isset( $atts['sliderType'] ) ? (string) $atts['sliderType'] : 'categories';

		// Get the data source.
		$source = WCSP_Data_Source::create( $type, $atts );
		if ( ! $source ) {
			return self::render_empty_state( __( 'Unknown slider type.', 'amitry-product-category-slider' ) );
		}

		$items = $source->get_items();

		if ( empty( $items ) ) {
			return self::render_empty_state( __( 'No items to display.', 'amitry-product-category-slider' ) );
		}

		// Pick the card renderer for this data source type.
		$card_class = self::resolve_card_class( $source->get_type() );
		if ( ! $card_class ) {
			return self::render_empty_state( __( 'No card renderer available for this slider type.', 'amitry-product-category-slider' ) );
		}

		return self::render_wrapper( $atts, $items, $card_class, $skip_outer );
	}

	/**
	 * Resolve which card renderer class handles a given data source type.
	 *
	 * @param string $type Data source type ("products", "categories", or a Pro-registered type).
	 * @return string|null Class name (must implement ::render( $item, $atts )).
	 */
	protected static function resolve_card_class( $type ) {
		$map = array(
			'products'   => 'WCSP_Card_Product',
			'categories' => 'WCSP_Card_Category',
		);

		/**
		 * Filter the card renderer class map.
		 *
		 * Pro add-ons register their own card renderers for their own
		 * data source types (and may also override the free renderers
		 * if they replace a card style entirely).
		 *
		 * @since 4.0.0
		 *
		 * @param array $map Map of type slug => card class name.
		 */
		$map = apply_filters( 'wcsp_card_renderers', $map );

		if ( ! isset( $map[ $type ] ) ) {
			return null;
		}

		$class = $map[ $type ];

		return class_exists( $class ) ? $class : null;
	}

	/**
	 * Render the slide markup for a set of items.
	 *
	 * @param array  $items      Items from the data source.
	 * @param string $card_class Card renderer class name.
	 * @param array  $atts       Slider attributes.
	 * @return string
	 */
	protected static function slides_html( array $items, $card_class, array $atts ) {
		$html = '';

		foreach ( $items as $item ) {
			$card_html = call_user_func( array( $card_class, 'render' ), $item, $atts );

			/**
			 * Filter the rendered HTML for a single card.
			 *
			 * Pro add-ons can wrap, replace or extend individual card markup here.
			 *
			 * @since 4.0.0
			 *
			 * @param string $card_html The card HTML.
			 * @param mixed  $item      The data item (WC_Product, WP_Term, or Pro-defined).
			 * @param array  $atts      Slider attributes.
			 */
			$card_html = apply_filters( 'wcsp_card_html', $card_html, $item, $atts );

			$html .= '<div class="wcsp-slide">' . $card_html . '</div>';
		}

		return $html;
	}

	/**
	 * Public API: render only the slides for a set of attributes.
	 *
	 * Add-ons that reload a slider's contents (for example a category
	 * selector fetching a different category over AJAX) use this so the
	 * markup is byte for byte what a normal render would produce,
	 * including the card filters and the before/after render actions
	 * that Pro features rely on.
	 *
	 * @since 4.3.0
	 *
	 * @param array $atts Slider attributes.
	 * @return string Slide HTML, or an empty string when there is nothing to show.
	 */
	public static function render_slides( array $atts ) {
		$atts = apply_filters( 'wcsp_render_attributes', $atts );

		$type   = isset( $atts['sliderType'] ) ? (string) $atts['sliderType'] : 'categories';
		$source = WCSP_Data_Source::create( $type, $atts );
		if ( ! $source ) {
			return '';
		}

		$items = $source->get_items();
		if ( empty( $items ) ) {
			return '';
		}

		$card_class = self::resolve_card_class( $source->get_type() );
		if ( ! $card_class ) {
			return '';
		}

		// Fire the same actions a normal render does, so features that
		// filter image attributes during rendering (hover zoom, lightbox)
		// behave identically here.
		do_action( 'wcsp_before_render', $atts );
		$html = self::slides_html( $items, $card_class, $atts );
		do_action( 'wcsp_after_render', $atts );

		return $html;
	}

	/**
	 * Build the slider wrapper and inject card HTML.
	 *
	 * @param array  $atts       Attributes.
	 * @param array  $items      Items from the data source.
	 * @param string $card_class Card renderer class name.
	 * @param bool   $skip_outer Skip the .wcsp-outer wrapper (used by editor).
	 * @return string
	 */
	protected static function render_wrapper( array $atts, array $items, $card_class, $skip_outer = false ) {
		$wrapper_classes = self::wrapper_classes( $atts );
		$outer_classes   = self::outer_classes( $atts );
		$wrapper_style   = self::wrapper_inline_style( $atts );
		$wrapper_attrs   = self::wrapper_data_attrs( $atts );

		/**
		 * Fires before the slider wrapper is opened.
		 *
		 * Pro add-ons may echo additional markup (e.g. structured data,
		 * lightbox containers) here.
		 *
		 * @since 4.0.0
		 *
		 * @param array $atts Slider attributes.
		 */
		ob_start();
		do_action( 'wcsp_before_render', $atts );
		$before = ob_get_clean();

		$section_title    = ! empty( $atts['sectionTitle'] ) ? (string) $atts['sectionTitle'] : '';
		$section_subtitle = ! empty( $atts['sectionSubtitle'] ) ? (string) $atts['sectionSubtitle'] : '';

		ob_start();
		?>
		<?php echo $before; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already-rendered HTML from Pro add-ons. ?>

		<?php if ( ! $skip_outer ) : ?>
		<div class="<?php echo esc_attr( implode( ' ', $outer_classes ) ); ?>" style="<?php echo esc_attr( $wrapper_style ); ?>">
		<?php endif; ?>

			<?php
			/**
			 * Filter whether the section head renders.
			 *
			 * By default it appears when a title or subtitle is set. Pro
			 * add-ons that place controls in the head (for example a
			 * category selector) can force it on even when both are empty.
			 *
			 * @since 4.3.0
			 *
			 * @param bool  $show Whether to render the section head.
			 * @param array $atts Slider attributes.
			 */
			$show_section_head = apply_filters(
				'wcsp_render_section_head',
				( '' !== $section_title || '' !== $section_subtitle ),
				$atts
			);
			?>
			<?php if ( $show_section_head ) : ?>
				<div class="wcsp-section-head">
					<div class="wcsp-section-head-text">
						<?php if ( '' !== $section_title ) : ?>
							<h2 class="wcsp-section-title"><?php echo esc_html( $section_title ); ?></h2>
						<?php endif; ?>
						<?php if ( '' !== $section_subtitle ) : ?>
							<p class="wcsp-section-subtitle"><?php echo esc_html( $section_subtitle ); ?></p>
						<?php endif; ?>
					</div>
					<?php
					/**
					 * Fires at the end of the section head, after title and subtitle.
					 *
					 * Pro add-ons render head level controls here.
					 *
					 * @since 4.3.0
					 *
					 * @param array $atts Slider attributes.
					 */
					do_action( 'wcsp_section_head_end', $atts );
					?>
				</div>
			<?php endif; ?>

			<?php
			// View-All button can sit above OR below the slider. The
			// position attribute controls which call below renders.
			$va_pos_render = isset( $atts['viewAllPosition'] ) ? (string) $atts['viewAllPosition'] : 'below';
			if ( 'above' === $va_pos_render ) {
				echo self::render_view_all( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>

			<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>"<?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<div class="wcsp-inner">
					<div class="wcsp-track">
						<?php echo self::slides_html( $items, $card_class, $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside the card renderer. ?>
					</div>
				</div>
				<?php echo self::render_navigation( $atts, is_array( $items ) ? count( $items ) : 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<?php
			/**
			 * Fires directly after the slider, still inside the outer
			 * wrapper. Add-ons render controls that belong to the slider
			 * here, for example a "load more" button.
			 *
			 * @since 4.3.0
			 *
			 * @param array $atts  Slider attributes.
			 * @param int   $count Number of items rendered.
			 */
			do_action( 'wcsp_after_slider', $atts, is_array( $items ) ? count( $items ) : 0 );
			?>

			<?php
			if ( 'above' !== $va_pos_render ) {
				echo self::render_view_all( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>

		<?php if ( ! $skip_outer ) : ?>
		</div>
		<?php endif; ?>

		<?php
		/**
		 * Fires after the slider wrapper is closed.
		 *
		 * @since 4.0.0
		 *
		 * @param array $atts Slider attributes.
		 */
		do_action( 'wcsp_after_render', $atts );

		return ob_get_clean();
	}

	/**
	 * Build the wrapper wrapper CSS class list.
	 *
	 * These classes go on .wcsp-outer (not .wcsp-slider) so the editor
	 * can override them via a higher-specificity class on its own
	 * wrapper without a server roundtrip.
	 *
	 * @param array $atts Attributes.
	 * @return string[]
	 */
	protected static function outer_classes( array $atts ) {
		$classes = array( 'wcsp-outer' );

		$style_variant = isset( $atts['styleVariant'] ) ? sanitize_html_class( (string) $atts['styleVariant'] ) : 'clean-card';
		$classes[]     = 'wcsp-style-' . $style_variant;

		$shadow    = isset( $atts['shadowIntensity'] ) ? sanitize_html_class( (string) $atts['shadowIntensity'] ) : 'soft';
		$classes[] = 'wcsp-shadow-' . $shadow;

		$hover     = isset( $atts['hoverEffect'] ) ? sanitize_html_class( (string) $atts['hoverEffect'] ) : 'lift';
		$classes[] = 'wcsp-hover-' . $hover;

		$image_shape = isset( $atts['imageShape'] ) ? sanitize_html_class( (string) $atts['imageShape'] ) : 'rounded';
		$classes[]   = 'wcsp-shape-' . $image_shape;

		$aspect    = isset( $atts['aspectRatio'] ) ? sanitize_html_class( (string) $atts['aspectRatio'] ) : '4-3';
		$classes[] = 'wcsp-aspect-' . $aspect;

		$image_fit = isset( $atts['imageFit'] ) ? sanitize_html_class( (string) $atts['imageFit'] ) : 'cover';
		$classes[] = 'wcsp-fit-' . $image_fit;

		$dots_shape = isset( $atts['dotsShape'] ) ? sanitize_html_class( (string) $atts['dotsShape'] ) : 'round';
		$classes[]  = 'wcsp-dots-' . $dots_shape;

		$title_align = isset( $atts['sectionTitleAlign'] ) ? sanitize_html_class( (string) $atts['sectionTitleAlign'] ) : 'left';
		$classes[]   = 'wcsp-title-align-' . $title_align;
		$sub_align   = isset( $atts['sectionSubtitleAlign'] ) ? sanitize_html_class( (string) $atts['sectionSubtitleAlign'] ) : 'left';
		$classes[]   = 'wcsp-sub-align-' . $sub_align;

		$va_pos   = isset( $atts['viewAllPosition'] ) ? sanitize_html_class( (string) $atts['viewAllPosition'] ) : 'below';
		$classes[] = 'wcsp-viewall-pos-' . $va_pos;
		$va_align = isset( $atts['viewAllAlign'] ) ? sanitize_html_class( (string) $atts['viewAllAlign'] ) : 'right';
		$classes[] = 'wcsp-viewall-align-' . $va_align;

		$content_align = isset( $atts['contentAlign'] ) ? sanitize_html_class( (string) $atts['contentAlign'] ) : 'left';
		$classes[]     = 'wcsp-content-align-' . $content_align;
		$va_icon  = isset( $atts['viewAllIcon'] ) ? sanitize_html_class( (string) $atts['viewAllIcon'] ) : 'none';
		$classes[] = 'wcsp-viewall-icon-' . $va_icon;

		// Arrows: per-device hide classes (CSS hides at the matching media query).
		if ( ! self::resolve_boolean( $atts, 'showArrows', 'desktop' ) ) $classes[] = 'wcsp-hide-arrows-dsk';
		if ( ! self::resolve_boolean( $atts, 'showArrows', 'tablet'  ) ) $classes[] = 'wcsp-hide-arrows-tab';
		if ( ! self::resolve_boolean( $atts, 'showArrows', 'mobile'  ) ) $classes[] = 'wcsp-hide-arrows-mob';

		// Pagination dots: per-device hide classes.
		if ( ! self::resolve_boolean( $atts, 'showPaginationDots', 'desktop' ) ) $classes[] = 'wcsp-hide-dots-dsk';
		if ( ! self::resolve_boolean( $atts, 'showPaginationDots', 'tablet'  ) ) $classes[] = 'wcsp-hide-dots-tab';
		if ( ! self::resolve_boolean( $atts, 'showPaginationDots', 'mobile'  ) ) $classes[] = 'wcsp-hide-dots-mob';

		if ( empty( $atts['showOverlayGradient'] ) ) {
			$classes[] = 'wcsp-no-overlay';
		} else {
			$classes[] = 'wcsp-overlay';
		}

		// Element hide-classes.
		$element_map = array(
			'showImage'       => 'image',
			'showTitle'       => 'title',
			'showPrice'       => 'price',
			'showRating'      => 'rating',
			'showExcerpt'     => 'excerpt',
			'showAddToCart'   => 'cart',
			'showStock'       => 'stock',
			'showSaleBadge'   => 'sale-badge',
			'showCount'       => 'count',
			'showDescription' => 'excerpt', // category description shares the excerpt slot
		);
		foreach ( $element_map as $attr => $class_suffix ) {
			if ( isset( $atts[ $attr ] ) && ! $atts[ $attr ] ) {
				$classes[] = 'wcsp-hide-' . $class_suffix;
			}
		}

		/**
		 * Filter the outer wrapper CSS classes.
		 *
		 * @since 4.0.0
		 *
		 * @param string[] $classes Class list.
		 * @param array    $atts    Attributes.
		 */
		return apply_filters( 'wcsp_outer_classes', $classes, $atts );
	}

	/**
	 * Build the inner slider CSS class list (.wcsp-slider).
	 *
	 * Only keeps data-affecting / Swiper-relevant classes here. Visual
	 * style classes live on the outer wrapper.
	 *
	 * @param array $atts Attributes.
	 * @return string[]
	 */
	protected static function wrapper_classes( array $atts ) {
		$classes = array( 'wcsp-slider' );

		/**
		 * Filter the slider wrapper CSS classes.
		 *
		 * @since 4.0.0
		 *
		 * @param string[] $classes Class list.
		 * @param array    $atts    Attributes.
		 */
		return apply_filters( 'wcsp_wrapper_classes', $classes, $atts );
	}

	/**
	 * Build the wrapper inline style (CSS variables for user-set values).
	 *
	 * Inline styles are only used for values the user can change (radius,
	 * colors, scale). Everything else lives in the static stylesheet.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	protected static function wrapper_inline_style( array $atts ) {
		$vars = array();

		if ( ! empty( $atts['maxWidth'] ) && (int) $atts['maxWidth'] > 0 ) {
			$vars['--wcsp-max-w'] = (int) $atts['maxWidth'] . 'px';
		}
		if ( isset( $atts['cardRadius'] ) ) {
			$vars['--wcsp-radius'] = (int) $atts['cardRadius'] . 'px';
		}
		if ( ! empty( $atts['cardBackgroundColor'] ) ) {
			$vars['--wcsp-card-bg'] = self::safe_color( $atts['cardBackgroundColor'] );
		}
		if ( isset( $atts['cardPadding'] ) ) {
			$vars['--wcsp-card-pad']     = (int) $atts['cardPadding'] . 'px';
			$vars['--wcsp-card-pad-tab'] = self::resolve_numeric( $atts, 'cardPadding', 'tablet', 20 ) . 'px';
			$vars['--wcsp-card-pad-mob'] = self::resolve_numeric( $atts, 'cardPadding', 'mobile', 20 ) . 'px';
		}
		if ( ! empty( $atts['arrowColor'] ) ) {
			$vars['--wcsp-arrow-color'] = self::safe_color( $atts['arrowColor'] );
		}
		if ( ! empty( $atts['arrowBgColor'] ) ) {
			$vars['--wcsp-arrow-bg'] = self::safe_color( $atts['arrowBgColor'] );
		}
		if ( isset( $atts['arrowSize'] ) ) {
			$vars['--wcsp-arrow-size']     = (int) $atts['arrowSize'] . 'px';
			$vars['--wcsp-arrow-size-tab'] = self::resolve_numeric( $atts, 'arrowSize', 'tablet', 40 ) . 'px';
			$vars['--wcsp-arrow-size-mob'] = self::resolve_numeric( $atts, 'arrowSize', 'mobile', 40 ) . 'px';
		}
		if ( isset( $atts['spaceBetween'] ) ) {
			$vars['--wcsp-gap']     = (int) $atts['spaceBetween'] . 'px';
			$vars['--wcsp-gap-tab'] = self::resolve_gap( $atts, 'tablet' ) . 'px';
			$vars['--wcsp-gap-mob'] = self::resolve_gap( $atts, 'mobile' ) . 'px';
		}
		if ( isset( $atts['slidesPerViewDesktop'] ) ) {
			$vars['--wcsp-cols-dsk'] = (int) $atts['slidesPerViewDesktop'];
		}
		if ( isset( $atts['slidesPerViewTablet'] ) ) {
			$vars['--wcsp-cols-tab'] = (int) $atts['slidesPerViewTablet'];
		}
		if ( isset( $atts['slidesPerViewMobile'] ) ) {
			$vars['--wcsp-cols-mob'] = (int) $atts['slidesPerViewMobile'];
		}
		if ( isset( $atts['scaleDesktop'] ) ) {
			$vars['--wcsp-scale-dsk'] = ( (int) $atts['scaleDesktop'] ) / 100;
		}
		if ( isset( $atts['scaleTablet'] ) ) {
			$vars['--wcsp-scale-tab'] = ( (int) $atts['scaleTablet'] ) / 100;
		}
		if ( isset( $atts['scaleMobile'] ) ) {
			$vars['--wcsp-scale-mob'] = ( (int) $atts['scaleMobile'] ) / 100;
		}
		if ( ! empty( $atts['titleColor'] ) ) {
			$vars['--wcsp-title-color'] = self::safe_color( $atts['titleColor'] );
		}
		if ( ! empty( $atts['priceColor'] ) ) {
			$vars['--wcsp-price-color'] = self::safe_color( $atts['priceColor'] );
		}
		// Section title typography.
		if ( isset( $atts['sectionTitleSize'] ) ) {
			$vars['--wcsp-section-title-size'] = (int) $atts['sectionTitleSize'] . 'px';
		}
		if ( ! empty( $atts['sectionTitleWeight'] ) ) {
			$vars['--wcsp-section-title-weight'] = sanitize_html_class( (string) $atts['sectionTitleWeight'] );
		}
		if ( ! empty( $atts['sectionTitleColor'] ) ) {
			$vars['--wcsp-section-title-color'] = self::safe_color( $atts['sectionTitleColor'] );
		}
		// Section subtitle typography.
		if ( isset( $atts['sectionSubtitleSize'] ) ) {
			$vars['--wcsp-section-sub-size'] = (int) $atts['sectionSubtitleSize'] . 'px';
		}
		if ( ! empty( $atts['sectionSubtitleWeight'] ) ) {
			$vars['--wcsp-section-sub-weight'] = sanitize_html_class( (string) $atts['sectionSubtitleWeight'] );
		}
		if ( ! empty( $atts['sectionSubtitleColor'] ) ) {
			$vars['--wcsp-section-sub-color'] = self::safe_color( $atts['sectionSubtitleColor'] );
		}
		// View-All button customization.
		if ( ! empty( $atts['viewAllBgColor'] ) ) {
			$vars['--wcsp-viewall-bg'] = self::safe_color( $atts['viewAllBgColor'] );
		}
		if ( ! empty( $atts['viewAllTextColor'] ) ) {
			$vars['--wcsp-viewall-color'] = self::safe_color( $atts['viewAllTextColor'] );
		}
		if ( isset( $atts['viewAllRadius'] ) ) {
			$vars['--wcsp-viewall-radius'] = (int) $atts['viewAllRadius'] . 'px';
		}
		if ( isset( $atts['viewAllPadding'] ) ) {
			$vars['--wcsp-viewall-pad'] = (int) $atts['viewAllPadding'] . 'px';
		}

		$parts = array();
		foreach ( $vars as $name => $value ) {
			$parts[] = $name . ':' . $value;
		}

		return implode( ';', $parts );
	}

	/**
	 * Build wrapper data-* attributes (consumed by the Swiper init later).
	 *
	 * Returns a pre-escaped string starting with a leading space so it can
	 * be concatenated directly inside an HTML tag.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	protected static function wrapper_data_attrs( array $atts ) {
		$data = array(
			'data-wcsp-type'      => isset( $atts['sliderType'] ) ? (string) $atts['sliderType'] : 'categories',
			'data-wcsp-cols-dsk'  => isset( $atts['slidesPerViewDesktop'] ) ? (int) $atts['slidesPerViewDesktop'] : 4,
			'data-wcsp-cols-tab'  => isset( $atts['slidesPerViewTablet'] ) ? (int) $atts['slidesPerViewTablet'] : 2,
			'data-wcsp-cols-mob'  => isset( $atts['slidesPerViewMobile'] ) ? (int) $atts['slidesPerViewMobile'] : 1,
			'data-wcsp-gap'       => isset( $atts['spaceBetween'] ) ? (int) $atts['spaceBetween'] : 24,
			'data-wcsp-gap-tab'   => self::resolve_gap( $atts, 'tablet' ),
			'data-wcsp-gap-mob'   => self::resolve_gap( $atts, 'mobile' ),
			'data-wcsp-autoplay'  => ! empty( $atts['autoplay'] ) ? '1' : '0',
			'data-wcsp-delay'     => isset( $atts['autoplayDelay'] ) ? (int) $atts['autoplayDelay'] : 3000,
			'data-wcsp-loop'      => ! empty( $atts['loop'] ) ? '1' : '0',
			'data-wcsp-pause'     => ! empty( $atts['pauseOnHover'] ) ? '1' : '0',
			'data-wcsp-speed'     => isset( $atts['speed'] ) ? (int) $atts['speed'] : 600,
			'data-wcsp-touch'     => ! empty( $atts['touchEnabled'] ) ? '1' : '0',
			'data-wcsp-drag'      => ! empty( $atts['mouseDrag'] ) ? '1' : '0',
			'data-wcsp-keyboard'  => ! empty( $atts['keyboardEnabled'] ) ? '1' : '0',
			'data-wcsp-effect'    => isset( $atts['transitionEffect'] ) ? (string) $atts['transitionEffect'] : 'slide',
		);

		$out = '';
		foreach ( $data as $key => $value ) {
			$out .= ' ' . esc_attr( $key ) . '="' . esc_attr( (string) $value ) . '"';
		}
		return $out;
	}

	/**
	 * Resolve the gap (spaceBetween) for a given device.
	 *
	 * Per-device gaps store -1 to mean "inherit from Desktop". Mobile
	 * additionally inherits from Tablet if Tablet was set but Mobile
	 * was not, so that a Tablet-first override carries down.
	 *
	 * @param array  $atts   Attributes.
	 * @param string $device 'tablet' | 'mobile'.
	 * @return int Resolved gap value in pixels.
	 */
	protected static function resolve_gap( array $atts, $device ) {
		return self::resolve_numeric( $atts, 'spaceBetween', $device, 24 );
	}

	/**
	 * Resolve a numeric per-device attribute with inheritance.
	 *
	 * @param array  $atts    Attributes.
	 * @param string $base    Desktop attribute key (e.g. 'cardPadding').
	 * @param string $device  'tablet' | 'mobile'.
	 * @param int    $default Desktop default if attribute is missing.
	 * @return int
	 */
	protected static function resolve_numeric( array $atts, $base, $device, $default = 0 ) {
		$desktop = isset( $atts[ $base ] ) ? (int) $atts[ $base ] : $default;
		$tab_key = $base . 'Tablet';
		$mob_key = $base . 'Mobile';
		$tablet  = isset( $atts[ $tab_key ] ) ? (int) $atts[ $tab_key ] : -1;
		$mobile  = isset( $atts[ $mob_key ] ) ? (int) $atts[ $mob_key ] : -1;

		if ( 'mobile' === $device ) {
			if ( $mobile >= 0 ) return $mobile;
			if ( $tablet >= 0 ) return $tablet;
			return $desktop;
		}
		if ( 'tablet' === $device ) {
			if ( $tablet >= 0 ) return $tablet;
			return $desktop;
		}
		return $desktop;
	}

	/**
	 * Resolve a boolean per-device attribute with inheritance.
	 * Tablet/Mobile carry the string 'inherit', 'true', or 'false'.
	 *
	 * @param array  $atts   Attributes.
	 * @param string $base   Desktop attribute key (e.g. 'showArrows').
	 * @param string $device 'tablet' | 'mobile'.
	 * @return bool
	 */
	protected static function resolve_boolean( array $atts, $base, $device ) {
		$desktop = ! empty( $atts[ $base ] );
		$tab     = isset( $atts[ $base . 'Tablet' ] ) ? (string) $atts[ $base . 'Tablet' ] : 'inherit';
		$mob     = isset( $atts[ $base . 'Mobile' ] ) ? (string) $atts[ $base . 'Mobile' ] : 'inherit';

		if ( 'mobile' === $device ) {
			if ( 'true' === $mob )  return true;
			if ( 'false' === $mob ) return false;
			if ( 'true' === $tab )  return true;
			if ( 'false' === $tab ) return false;
			return $desktop;
		}
		if ( 'tablet' === $device ) {
			if ( 'true' === $tab )  return true;
			if ( 'false' === $tab ) return false;
			return $desktop;
		}
		return $desktop;
	}

	/**
	 * Render the navigation controls (arrows, dots, scrollbar, progress, counter).
	 *
	 * At Stage 2 these are static markup only - Swiper hooks into them later.
	 *
	 * @param array $atts        Attributes.
	 * @param int   $total_count Total number of slides (for the counter widget).
	 * @return string
	 */
	protected static function render_navigation( array $atts, $total_count = 0 ) {
		$out = '';

		// Render arrows if any device wants them visible. Per-device hide
		// classes on the outer wrapper take care of hiding them at the
		// matching breakpoint.
		$any_arrows =
			self::resolve_boolean( $atts, 'showArrows', 'desktop' ) ||
			self::resolve_boolean( $atts, 'showArrows', 'tablet'  ) ||
			self::resolve_boolean( $atts, 'showArrows', 'mobile'  );
		if ( $any_arrows ) {
			$out .= '<button type="button" class="wcsp-btn wcsp-btn--prev" aria-label="' . esc_attr__( 'Previous', 'amitry-product-category-slider' ) . '">' .
				'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"></polyline></svg>' .
				'</button>';
			$out .= '<button type="button" class="wcsp-btn wcsp-btn--next" aria-label="' . esc_attr__( 'Next', 'amitry-product-category-slider' ) . '">' .
				'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"></polyline></svg>' .
				'</button>';
		}

		// Pagination dots: same pattern.
		$any_dots =
			self::resolve_boolean( $atts, 'showPaginationDots', 'desktop' ) ||
			self::resolve_boolean( $atts, 'showPaginationDots', 'tablet'  ) ||
			self::resolve_boolean( $atts, 'showPaginationDots', 'mobile'  );
		if ( $any_dots ) {
			$out .= '<div class="wcsp-dots" aria-hidden="true"></div>';
		}

		if ( ! empty( $atts['showScrollbar'] ) ) {
			$out .= '<div class="wcsp-scrollbar" aria-hidden="true"></div>';
		}

		if ( ! empty( $atts['showProgress'] ) ) {
			$out .= '<div class="wcsp-progress" aria-hidden="true"><div class="wcsp-progress__bar"></div></div>';
		}

		if ( ! empty( $atts['showCounter'] ) ) {
			$out .= '<div class="wcsp-counter" aria-hidden="true"><span class="wcsp-counter__current">1</span> / <span class="wcsp-counter__total">' . (int) $total_count . '</span></div>';
		}

		return $out;
	}

	/**
	 * Render the optional "View All" button.
	 *
	 * Color, radius, padding all come from CSS variables set on the outer
	 * wrapper. Position (above/below) and alignment are handled by classes
	 * on the outer that the CSS uses to flip layout.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	protected static function render_view_all( array $atts ) {
		if ( empty( $atts['showViewAllButton'] ) || empty( $atts['viewAllUrl'] ) ) {
			return '';
		}

		$text = ! empty( $atts['viewAllText'] ) ? (string) $atts['viewAllText'] : __( 'View All', 'amitry-product-category-slider' );
		$url  = esc_url( (string) $atts['viewAllUrl'] );

		// Optional icon (decorative arrow-right). Hidden via class
		// wcsp-viewall-icon-none on the outer; otherwise the SVG is
		// shown next to the text.
		$icon = '<svg class="wcsp-view-all__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"></polyline></svg>';

		return sprintf(
			'<div class="wcsp-view-all"><a class="wcsp-view-all__link" href="%s">%s%s</a></div>',
			$url,
			esc_html( $text ),
			$icon // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hard-coded literal.
		);
	}

	/**
	 * Render an empty state (no results or invalid config).
	 *
	 * Only visible when WP_DEBUG is on, to avoid scaring end visitors.
	 *
	 * @param string $message Reason.
	 * @return string
	 */
	protected static function render_empty_state( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return '<div class="wcsp-empty" style="padding:1em;border:1px dashed #ccc;color:#666;font-size:0.9em;">' .
				esc_html__( 'Amitry Slider:', 'amitry-product-category-slider' ) . ' ' . esc_html( $message ) .
				'</div>';
		}
		return '';
	}

	/**
	 * Sanitize a user-supplied color value.
	 *
	 * Accepts hex (#rgb, #rrggbb, #rrggbbaa) and rgb/rgba functional notation.
	 * Anything else returns an empty string.
	 *
	 * @param string $color Color input.
	 * @return string
	 */
	protected static function safe_color( $color ) {
		$color = trim( (string) $color );

		// Hex.
		if ( preg_match( '/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/', $color ) ) {
			return $color;
		}

		// rgb() / rgba().
		if ( preg_match( '/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d+)\s*)?\)$/', $color ) ) {
			return $color;
		}

		return '';
	}
}

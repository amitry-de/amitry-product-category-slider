<?php
/**
 * Product card renderer.
 *
 * Renders a single product card HTML string. Uses a non-interactive
 * <div> as the card wrapper with separate <a> tags for the image/title
 * area and the add-to-cart button, avoiding the invalid HTML pattern
 * of nested anchors.
 *
 * Element visibility is controlled by hide-classes on the outer
 * wrapper so toggling visibility in the editor is instant (CSS only,
 * no server roundtrip).
 *
 * @package AmitryProductCategorySlider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WCSP_Card_Product
 */
class WCSP_Card_Product {

	/**
	 * Render one product card.
	 *
	 * @param WC_Product $product Product object.
	 * @param array      $atts    Slider attributes.
	 * @return string
	 */
	public static function render( $product, array $atts ) {
		if ( ! $product instanceof WC_Product ) {
			return '';
		}

		$permalink = $product->get_permalink();
		$title     = $product->get_name();
		$is_sale   = $product->is_on_sale();
		$link_aria = sprintf(
			/* translators: %s: product name */
			__( 'View %s', 'amitry-product-category-slider' ),
			$title
		);

		ob_start();
		?>
		<div class="wcsp-card wcsp-card--product">

			<a class="wcsp-card__link wcsp-card__link--media wcsp-el wcsp-el--image"
			   href="<?php echo esc_url( $permalink ); ?>"
			   aria-label="<?php echo esc_attr( $link_aria ); ?>">
				<div class="wcsp-media">
					<div class="wcsp-img-wrap">
						<?php echo self::image_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php if ( $is_sale ) : ?>
							<span class="wcsp-badge wcsp-badge--sale"><?php echo esc_html( self::sale_badge_text( $product ) ); ?></span>
						<?php endif; ?>
						<?php
						/**
						 * Fires inside the image wrapper, after the image element.
						 *
						 * @since 4.0.0
						 *
						 * @param WC_Product $product Current product.
						 * @param array      $atts    Slider attributes.
						 */
						do_action( 'wcsp_card_product_media', $product, $atts );
						?>
					</div>
				</div>
			</a>

			<div class="wcsp-content">
				<a class="wcsp-card__link wcsp-card__link--text wcsp-el wcsp-el--title"
				   href="<?php echo esc_url( $permalink ); ?>">
					<h3 class="wcsp-title"><?php echo esc_html( $title ); ?></h3>
				</a>

				<div class="wcsp-el wcsp-el--price wcsp-price">
					<?php echo wp_kses_post( $product->get_price_html() ); ?>
				</div>

				<div class="wcsp-el wcsp-el--rating">
					<?php echo self::rating_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>

				<div class="wcsp-el wcsp-el--excerpt wcsp-excerpt">
					<?php echo wp_kses_post( wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 18, '...' ) ); ?>
				</div>

				<div class="wcsp-el wcsp-el--stock">
					<?php echo self::stock_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>

				<div class="wcsp-el wcsp-el--cart">
					<?php echo self::add_to_cart_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>

				<?php
				/**
				 * Fires at the end of a product card's content area.
				 *
				 * @since 4.0.0
				 *
				 * @param WC_Product $product Current product.
				 * @param array      $atts    Slider attributes.
				 */
				do_action( 'wcsp_card_product_content_end', $product, $atts );
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Build the product image HTML, with a graceful fallback.
	 *
	 * @param WC_Product $product Product.
	 * @return string
	 */
	protected static function image_html( $product ) {
		$image_id = $product->get_image_id();

		if ( $image_id ) {
			$alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
			if ( ! $alt ) {
				$alt = $product->get_name();
			}
			return wp_get_attachment_image(
				$image_id,
				apply_filters( 'wcsp_image_size', 'large' ),
				false,
				array(
					'class'   => 'wcsp-img',
					'alt'     => $alt,
					'loading' => 'lazy',
				)
			);
		}

		$initial = strtoupper( mb_substr( $product->get_name(), 0, 1 ) );
		return '<span class="wcsp-img-fallback" aria-hidden="true">' . esc_html( $initial ) . '</span>';
	}

	/**
	 * Build sale badge text.
	 *
	 * @param WC_Product $product Product.
	 * @return string
	 */
	protected static function sale_badge_text( $product ) {
		$regular = (float) $product->get_regular_price();
		$sale    = (float) $product->get_sale_price();

		if ( $regular > 0 && $sale > 0 && $sale < $regular ) {
			$percent = (int) round( ( ( $regular - $sale ) / $regular ) * 100 );
			/* translators: %d: percentage off */
			return sprintf( __( '-%d%%', 'amitry-product-category-slider' ), $percent );
		}

		return __( 'Sale', 'amitry-product-category-slider' );
	}

	/**
	 * Build the star rating HTML.
	 *
	 * @param WC_Product $product Product.
	 * @return string
	 */
	protected static function rating_html( $product ) {
		$rating = (float) $product->get_average_rating();
		if ( $rating <= 0 ) {
			return '<div class="wcsp-rating wcsp-rating--empty"></div>';
		}

		$count = (int) $product->get_review_count();

		$out  = '<div class="wcsp-rating" aria-label="' . esc_attr(
			sprintf(
				/* translators: %s: rating value out of 5 */
				__( 'Rated %s out of 5', 'amitry-product-category-slider' ),
				number_format_i18n( $rating, 1 )
			)
		) . '">';

		for ( $i = 1; $i <= 5; $i++ ) {
			$filled = $i <= round( $rating );
			$out   .= '<span class="wcsp-star' . ( $filled ? ' wcsp-star--filled' : '' ) . '" aria-hidden="true">&#9733;</span>';
		}

		$out .= '<span class="wcsp-rating-num">(' . esc_html( $count ) . ')</span>';
		$out .= '</div>';
		return $out;
	}

	/**
	 * Build stock status HTML.
	 *
	 * @param WC_Product $product Product.
	 * @return string
	 */
	protected static function stock_html( $product ) {
		if ( $product->is_in_stock() ) {
			if ( $product->managing_stock() ) {
				$qty = (int) $product->get_stock_quantity();
				$low = (int) get_option( 'woocommerce_notify_low_stock_amount', 2 );
				if ( $qty > 0 && $qty <= $low ) {
					return '<div class="wcsp-stock wcsp-stock--low">' .
						esc_html(
							sprintf(
								/* translators: %d: stock quantity */
								_n( 'Only %d left', 'Only %d left', $qty, 'amitry-product-category-slider' ),
								$qty
							)
						) .
						'</div>';
				}
			}
			return '<div class="wcsp-stock wcsp-stock--in">' . esc_html__( 'In stock', 'amitry-product-category-slider' ) . '</div>';
		}

		return '<div class="wcsp-stock wcsp-stock--out">' . esc_html__( 'Out of stock', 'amitry-product-category-slider' ) . '</div>';
	}

	/**
	 * Build Add-to-Cart button HTML.
	 *
	 * Builds the URL from the site root explicitly to avoid the REST
	 * endpoint URL being used as base when called from the block editor
	 * (where add_to_cart_url() returns a relative URL resolved against
	 * the REST request URL).
	 *
	 * @param WC_Product $product Product.
	 * @return string
	 */
	protected static function add_to_cart_html( $product ) {
		// Simple products: direct add-to-cart URL relative to the site root.
		// Variable / external / grouped products: link to product page.
		$product_id = (int) $product->get_id();

		if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) {
			$url   = wp_nonce_url(
				add_query_arg( 'add-to-cart', $product_id, home_url( '/' ) ),
				'add-to-cart-' . $product_id,
				'_wpnonce'
			);
			$label = __( 'Add to cart', 'amitry-product-category-slider' );
		} else {
			$url   = $product->get_permalink();
			$label = __( 'View product', 'amitry-product-category-slider' );
		}

		return sprintf(
			'<a class="wcsp-cart-btn" href="%s" data-product_id="%d">%s</a>',
			esc_url( $url ),
			$product_id,
			esc_html( $label )
		);
	}
}

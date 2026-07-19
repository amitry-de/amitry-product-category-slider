<?php
/**
 * Category card renderer.
 *
 * Renders a single product-category card. Uses a non-interactive
 * <div> as the card wrapper with separate <a> tags for the image
 * and title link, following the same pattern as the product card.
 *
 * ALWAYS includes every possible element - visibility is controlled
 * by hide-classes on the outer wrapper for instant editor toggling.
 *
 * @package AmitryProductCategorySlider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WCSP_Card_Category
 */
class WCSP_Card_Category {

	/**
	 * Render one category card.
	 *
	 * @param WP_Term $term Term object (taxonomy product_cat).
	 * @param array   $atts Slider attributes.
	 * @return string
	 */
	public static function render( $term, array $atts ) {
		if ( ! ( $term instanceof WP_Term ) ) {
			return '';
		}

		$permalink = get_term_link( $term );
		if ( is_wp_error( $permalink ) ) {
			$permalink = '#';
		}

		$link_aria = sprintf(
			/* translators: %s: category name */
			__( 'View %s', 'amitry-product-category-slider' ),
			$term->name
		);

		ob_start();
		?>
		<div class="wcsp-card wcsp-card--category">

			<a class="wcsp-card__link wcsp-card__link--media wcsp-el wcsp-el--image"
			   href="<?php echo esc_url( $permalink ); ?>"
			   aria-label="<?php echo esc_attr( $link_aria ); ?>">
				<div class="wcsp-media">
					<div class="wcsp-img-wrap">
						<?php echo self::image_html( $term ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php
						/**
						 * Fires inside the image wrapper of a category card.
						 *
						 * @since 4.0.0
						 *
						 * @param WP_Term $term Current term.
						 * @param array   $atts Slider attributes.
						 */
						do_action( 'wcsp_card_category_media', $term, $atts );
						?>
					</div>
				</div>
			</a>

			<div class="wcsp-content">
				<a class="wcsp-card__link wcsp-card__link--text wcsp-el wcsp-el--title"
				   href="<?php echo esc_url( $permalink ); ?>">
					<h3 class="wcsp-title"><?php echo esc_html( $term->name ); ?></h3>
				</a>

				<div class="wcsp-el wcsp-el--count wcsp-count">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: number of products in the category */
							_n( '%d product', '%d products', (int) $term->count, 'amitry-product-category-slider' ),
							(int) $term->count
						)
					);
					?>
				</div>

				<div class="wcsp-el wcsp-el--excerpt wcsp-excerpt">
					<?php echo wp_kses_post( wp_trim_words( wp_strip_all_tags( $term->description ), 18, '...' ) ); ?>
				</div>

				<?php
				/**
				 * Fires at the end of a category card's content area.
				 *
				 * @since 4.0.0
				 *
				 * @param WP_Term $term Current term.
				 * @param array   $atts Slider attributes.
				 */
				do_action( 'wcsp_card_category_content_end', $term, $atts );
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Build the category image HTML.
	 *
	 * Order:
	 *  1. Category thumbnail (set in admin)
	 *  2. Image of the first product in the category (fallback)
	 *  3. Letter placeholder
	 *
	 * @param WP_Term $term Term.
	 * @return string
	 */
	protected static function image_html( $term ) {
		$thumb_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
		if ( $thumb_id > 0 ) {
			$image = wp_get_attachment_image(
				$thumb_id,
				apply_filters( 'wcsp_image_size', 'large' ),
				false,
				array(
					'class'   => 'wcsp-img',
					'alt'     => $term->name,
					'loading' => 'lazy',
				)
			);
			if ( $image ) {
				return $image;
			}
		}

		$fallback = self::first_product_image( $term );
		if ( '' !== $fallback ) {
			return $fallback;
		}

		$initial = strtoupper( mb_substr( $term->name, 0, 1 ) );
		return '<span class="wcsp-img-fallback" aria-hidden="true">' . esc_html( $initial ) . '</span>';
	}

	/**
	 * First product image of a category (fallback when no thumbnail set).
	 *
	 * @param WP_Term $term Term.
	 * @return string
	 */
	protected static function first_product_image( $term ) {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return '';
		}

		$products = wc_get_products(
			array(
				'status'   => 'publish',
				'limit'    => 1,
				'category' => array( $term->slug ),
				'orderby'  => 'date',
				'order'    => 'DESC',
				'return'   => 'objects',
			)
		);

		if ( empty( $products ) ) {
			return '';
		}

		$first    = $products[0];
		$image_id = $first->get_image_id();
		if ( ! $image_id ) {
			return '';
		}

		return wp_get_attachment_image(
			$image_id,
			apply_filters( 'wcsp_image_size', 'large' ),
			false,
			array(
				'class'   => 'wcsp-img',
				'alt'     => $term->name,
				'loading' => 'lazy',
			)
		);
	}
}

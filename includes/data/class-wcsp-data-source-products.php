<?php
/**
 * Product data source.
 *
 * Builds the list of WC_Product objects for the product slider based on
 * the chosen filter mode.
 *
 * Supported filters (`productFilter` attribute):
 *   - newest        WC products ordered by date DESC
 *   - bestselling   ordered by total_sales DESC
 *   - on_sale       only products on sale, ordered by date DESC
 *   - featured      featured products only
 *   - top_rated     ordered by average rating DESC
 *   - manual        explicit ID list from `selectedProducts`
 *   - by_category   products from `selectedCategories`
 *
 * @package AmitryProductCategorySlider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WCSP_Data_Source_Products
 */
class WCSP_Data_Source_Products extends WCSP_Data_Source {

	/**
	 * Data source type identifier.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'products';
	}

	/**
	 * Get products to render.
	 *
	 * @return WC_Product[]
	 */
	public function get_items() {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return array();
		}

		$filter = isset( $this->atts['productFilter'] ) ? (string) $this->atts['productFilter'] : 'newest';
		$count  = isset( $this->atts['productCount'] ) ? max( 1, (int) $this->atts['productCount'] ) : 12;

		// Offset lets add-ons fetch a later batch of the same query, for
		// example a "load more" control paging through a large archive.
		$offset = isset( $this->atts['productOffset'] ) ? max( 0, (int) $this->atts['productOffset'] ) : 0;

		// Cache lookup. The offset belongs in the key, otherwise every
		// batch would be served the first one from cache.
		$cache_key = $this->cache_key( array( 'productFilter', 'productCount', 'productOffset', 'selectedProducts', 'selectedCategories' ) );
		$cached    = $this->cache_get( $cache_key );
		if ( null !== $cached && is_array( $cached ) ) {
			return $this->ids_to_products( $cached );
		}

		// Build the query args based on filter mode.
		$args = $this->build_query_args( $filter, $count, $offset );

		/**
		 * Filter the WC_Product_Query arguments before the query runs.
		 *
		 * Pro add-ons can extend this to add their own filter modes
		 * (e.g. cross-sells, upsells, wishlist items).
		 *
		 * @since 4.0.0
		 *
		 * @param array  $args   Query arguments for wc_get_products().
		 * @param string $filter The chosen filter mode.
		 * @param array  $atts   The full slider attributes array.
		 */
		$args = apply_filters( 'wcsp_products_query_args', $args, $filter, $this->atts );

		$products = wc_get_products( $args );
		if ( ! is_array( $products ) ) {
			$products = array();
		}

		// Manual mode preserves user-defined order.
		if ( 'manual' === $filter ) {
			$products = $this->reorder_manual( $products );
		}

		// Cache IDs only.
		$ids = array_map(
			static function ( $product ) {
				return $product->get_id();
			},
			$products
		);
		$this->cache_set( $cache_key, $ids );

		/**
		 * Filter the final product list before it is handed to the renderer.
		 *
		 * @since 4.0.0
		 *
		 * @param WC_Product[] $products Array of WC_Product objects.
		 * @param array        $atts     Slider attributes.
		 */
		return apply_filters( 'wcsp_products_result', $products, $this->atts );
	}

	/**
	 * Convert cached IDs back into hydrated WC_Product objects.
	 *
	 * @param int[] $ids Product IDs.
	 * @return WC_Product[]
	 */
	private function ids_to_products( array $ids ) {
		$products = array();
		foreach ( $ids as $id ) {
			$p = wc_get_product( (int) $id );
			if ( $p ) {
				$products[] = $p;
			}
		}
		return $products;
	}

	/**
	 * Reorder products to match the manual selection order.
	 *
	 * wc_get_products() does not preserve `include` order, so we restore it here.
	 *
	 * @param WC_Product[] $products Products from the query.
	 * @return WC_Product[]
	 */
	private function reorder_manual( array $products ) {
		$selected = isset( $this->atts['selectedProducts'] ) && is_array( $this->atts['selectedProducts'] )
			? array_map( 'intval', $this->atts['selectedProducts'] )
			: array();

		if ( empty( $selected ) ) {
			return $products;
		}

		$by_id = array();
		foreach ( $products as $p ) {
			$by_id[ $p->get_id() ] = $p;
		}

		$ordered = array();
		foreach ( $selected as $id ) {
			if ( isset( $by_id[ $id ] ) ) {
				$ordered[] = $by_id[ $id ];
			}
		}
		return $ordered;
	}

	/**
	 * Build the wc_get_products() argument array for the given filter mode.
	 *
	 * @param string $filter Filter mode.
	 * @param int    $count  Number of items requested.
	 * @return array
	 */
	private function build_query_args( $filter, $count, $offset = 0 ) {
		$args = array(
			'status'  => 'publish',
			'limit'   => $count,
			'orderby' => 'date',
			'order'   => 'DESC',
			'return'  => 'objects',
		);

		if ( $offset > 0 ) {
			$args['offset'] = (int) $offset;
		}

		switch ( $filter ) {
			case 'newest':
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
				break;

			case 'bestselling':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['order']    = 'DESC';
				break;

			case 'on_sale':
				// wc_get_products() supports an `on_sale` arg as of WC 3.0+.
				$args['on_sale'] = true;
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
				break;

			case 'featured':
				$args['featured'] = true;
				$args['orderby']  = 'date';
				$args['order']    = 'DESC';
				break;

			case 'top_rated':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_wc_average_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['order']    = 'DESC';
				break;

			case 'manual':
				$selected = isset( $this->atts['selectedProducts'] ) && is_array( $this->atts['selectedProducts'] )
					? array_filter( array_map( 'intval', $this->atts['selectedProducts'] ) )
					: array();

				if ( empty( $selected ) ) {
					// No products selected - return empty result instead of falling back to all products.
					$args['include'] = array( 0 );
				} else {
					$args['include'] = $selected;
					$args['limit']   = count( $selected );
				}
				break;

			case 'by_category':
				$cats = isset( $this->atts['selectedCategories'] ) && is_array( $this->atts['selectedCategories'] )
					? array_filter( array_map( 'intval', $this->atts['selectedCategories'] ) )
					: array();

				if ( ! empty( $cats ) ) {
					// wc_get_products() accepts an array of category slugs OR a category arg with term IDs via tax_query.
					$args['category'] = $this->cat_ids_to_slugs( $cats );
				}
				break;

			default:
				// Unknown filter - default to newest.
				break;
		}

		return $args;
	}

	/**
	 * Convert category term IDs to slugs (wc_get_products expects slugs for `category`).
	 *
	 * @param int[] $ids Term IDs.
	 * @return string[]
	 */
	private function cat_ids_to_slugs( array $ids ) {
		$slugs = array();
		foreach ( $ids as $id ) {
			$term = get_term( (int) $id, 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				$slugs[] = $term->slug;
			}
		}
		return $slugs;
	}
}

<?php
/**
 * Category data source.
 *
 * Builds the list of WP_Term objects (taxonomy `product_cat`) for the
 * category slider based on the chosen options.
 *
 * Supported options:
 *   - excludeCategories  array of term IDs to exclude
 *   - hideEmpty          boolean - skip categories with 0 products
 *   - categorySortBy     'name' | 'count' | 'menu_order'
 *   - maxCategories      maximum number of categories to return
 *
 * @package AmitryProductCategorySlider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WCSP_Data_Source_Categories
 */
class WCSP_Data_Source_Categories extends WCSP_Data_Source {

	/**
	 * Data source type identifier.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'categories';
	}

	/**
	 * Get product categories to render.
	 *
	 * @return WP_Term[]
	 */
	public function get_items() {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return array();
		}

		// Cache lookup.
		$cache_key = $this->cache_key( array( 'excludeCategories', 'hideEmpty', 'categorySortBy', 'maxCategories' ) );
		$cached    = $this->cache_get( $cache_key );
		if ( null !== $cached && is_array( $cached ) ) {
			return $this->ids_to_terms( $cached );
		}

		$args = $this->build_query_args();

		/**
		 * Filter the get_terms() arguments before the query runs.
		 *
		 * Pro add-ons can extend this to add their own sorting/filtering.
		 *
		 * @since 4.0.0
		 *
		 * @param array $args Arguments for get_terms().
		 * @param array $atts The full slider attributes array.
		 */
		$args = apply_filters( 'wcsp_categories_query_args', $args, $this->atts );

		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			$terms = array();
		}

		// Exclude the "uncategorized" default term unless the store has renamed it intentionally.
		$terms = $this->filter_uncategorized( $terms );

		// Cache IDs only.
		$ids = array_map(
			static function ( $term ) {
				return $term->term_id;
			},
			$terms
		);
		$this->cache_set( $cache_key, $ids );

		/**
		 * Filter the final category list before it is handed to the renderer.
		 *
		 * @since 4.0.0
		 *
		 * @param WP_Term[] $terms Array of WP_Term objects.
		 * @param array     $atts  Slider attributes.
		 */
		return apply_filters( 'wcsp_categories_result', $terms, $this->atts );
	}

	/**
	 * Hydrate cached term IDs back into WP_Term objects.
	 *
	 * @param int[] $ids Term IDs.
	 * @return WP_Term[]
	 */
	private function ids_to_terms( array $ids ) {
		$terms = array();
		foreach ( $ids as $id ) {
			$term = get_term( (int) $id, 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				$terms[] = $term;
			}
		}
		return $terms;
	}

	/**
	 * Build the get_terms() argument array.
	 *
	 * @return array
	 */
	private function build_query_args() {
		$sort_by    = isset( $this->atts['categorySortBy'] ) ? (string) $this->atts['categorySortBy'] : 'name';
		$hide_empty = isset( $this->atts['hideEmpty'] ) ? (bool) $this->atts['hideEmpty'] : true;
		$max        = isset( $this->atts['maxCategories'] ) ? max( 1, (int) $this->atts['maxCategories'] ) : 12;

		$exclude = isset( $this->atts['excludeCategories'] ) && is_array( $this->atts['excludeCategories'] )
			? array_filter( array_map( 'intval', $this->atts['excludeCategories'] ) )
			: array();

		$args = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => $hide_empty,
			'number'     => $max,
			'exclude'    => $exclude, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- user-chosen category exclusions, small list.
		);

		switch ( $sort_by ) {
			case 'count':
				$args['orderby'] = 'count';
				$args['order']   = 'DESC';
				break;

			case 'menu_order':
				// WooCommerce stores the manual sort order in term meta `order`.
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = 'order'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['order']    = 'ASC';
				break;

			case 'name':
			default:
				$args['orderby'] = 'name';
				$args['order']   = 'ASC';
				break;
		}

		return $args;
	}

	/**
	 * Remove the default "uncategorized" term from the result set.
	 *
	 * Stores rarely want this to appear in a public-facing slider.
	 *
	 * @param WP_Term[] $terms Term list.
	 * @return WP_Term[]
	 */
	private function filter_uncategorized( array $terms ) {
		$default_id = (int) get_option( 'default_product_cat', 0 );
		if ( ! $default_id ) {
			return $terms;
		}

		return array_values(
			array_filter(
				$terms,
				static function ( $term ) use ( $default_id ) {
					return (int) $term->term_id !== $default_id;
				}
			)
		);
	}
}

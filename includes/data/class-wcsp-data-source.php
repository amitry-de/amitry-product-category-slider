<?php
/**
 * Abstract base class for slider data sources.
 *
 * A data source is responsible for taking the slider's attributes
 * (filter, count, sort etc.) and producing a normalized list of
 * items that the renderer can iterate over.
 *
 * Concrete implementations:
 * - WCSP_Data_Source_Products
 * - WCSP_Data_Source_Categories
 *
 * Pro add-ons add new data sources by extending this class and
 * registering them via the `wcsp_data_sources` filter.
 *
 * @package AmitryProductCategorySlider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WCSP_Data_Source
 *
 * Subclasses must implement get_items().
 */
abstract class WCSP_Data_Source {

	/**
	 * The slider attributes passed in from the block / shortcode / Elementor widget.
	 *
	 * @var array
	 */
	protected $atts;

	/**
	 * Constructor.
	 *
	 * @param array $atts Slider attributes (already sanitized upstream).
	 */
	public function __construct( array $atts ) {
		$this->atts = $atts;
	}

	/**
	 * Get the items to render in the slider.
	 *
	 * Each item must be either:
	 *  - a WC_Product instance (for product cards), or
	 *  - a WP_Term instance with taxonomy `product_cat` (for category cards),
	 *  - or any object/array that a registered card renderer accepts.
	 *
	 * @return array
	 */
	abstract public function get_items();

	/**
	 * Get the data source type identifier (e.g. "products", "categories").
	 *
	 * Used by the renderer to pick the correct card class.
	 *
	 * @return string
	 */
	abstract public function get_type();

	/**
	 * Factory: create the right data source instance for a given slider type.
	 *
	 * @param string $type Slider type ("products", "categories", or a Pro-registered type).
	 * @param array  $atts Slider attributes.
	 * @return WCSP_Data_Source|null
	 */
	public static function create( $type, array $atts ) {
		$map = array(
			'products'   => 'WCSP_Data_Source_Products',
			'categories' => 'WCSP_Data_Source_Categories',
		);

		/**
		 * Filter the data source class map.
		 *
		 * Pro add-ons register their own data sources here. Each entry
		 * must be a class that extends WCSP_Data_Source.
		 *
		 * Example (in a Pro add-on):
		 *
		 *     add_filter( 'wcsp_data_sources', function( $map ) {
		 *         $map['tags']         = 'WCSP_PRO_Data_Source_Tags';
		 *         $map['cross_sells']  = 'WCSP_PRO_Data_Source_CrossSells';
		 *         return $map;
		 *     } );
		 *
		 * @since 4.0.0
		 *
		 * @param array $map Map of type slug => class name.
		 */
		$map = apply_filters( 'wcsp_data_sources', $map );

		if ( ! isset( $map[ $type ] ) ) {
			return null;
		}

		$class = $map[ $type ];

		if ( ! class_exists( $class ) ) {
			return null;
		}

		$instance = new $class( $atts );

		if ( ! $instance instanceof WCSP_Data_Source ) {
			return null;
		}

		return $instance;
	}

	/* ──────────────────────────────────────────────────────────────────
	 * Cache helpers
	 *
	 * Optional query cache. Controlled by the `queryCache` global
	 * setting on the admin page. Subclasses opt in by calling
	 * cache_get() / cache_set() around their query logic.
	 * ────────────────────────────────────────────────────────────────── */

	/**
	 * Whether query caching is enabled globally.
	 *
	 * @return bool
	 */
	protected function cache_enabled() {
		if ( ! class_exists( 'WCSP_Plugin' ) ) {
			return false;
		}
		return (bool) WCSP_Plugin::instance()->get_setting( 'queryCache', false );
	}

	/**
	 * Get the cache TTL in seconds.
	 *
	 * @return int
	 */
	protected function cache_ttl() {
		if ( ! class_exists( 'WCSP_Plugin' ) ) {
			return 300;
		}
		$ttl = (int) WCSP_Plugin::instance()->get_setting( 'queryCacheTtl', 300 );
		return $ttl > 0 ? $ttl : 300;
	}

	/**
	 * Build a stable cache key from the relevant attributes.
	 *
	 * @param array $keys Subset of attribute keys that affect the query.
	 * @return string
	 */
	protected function cache_key( array $keys ) {
		$payload = array();
		foreach ( $keys as $k ) {
			$payload[ $k ] = isset( $this->atts[ $k ] ) ? $this->atts[ $k ] : null;
		}
		return 'wcsp_' . $this->get_type() . '_' . md5( wp_json_encode( $payload ) );
	}

	/**
	 * Get a cached value, or null if not cached.
	 *
	 * @param string $key Cache key.
	 * @return mixed|null
	 */
	protected function cache_get( $key ) {
		if ( ! $this->cache_enabled() ) {
			return null;
		}
		$value = get_transient( $key );
		return ( false === $value ) ? null : $value;
	}

	/**
	 * Store a value in cache.
	 *
	 * @param string $key   Cache key.
	 * @param mixed  $value Value (must be serializable; we store IDs, not objects).
	 * @return void
	 */
	protected function cache_set( $key, $value ) {
		if ( ! $this->cache_enabled() ) {
			return;
		}
		set_transient( $key, $value, $this->cache_ttl() );
	}
}

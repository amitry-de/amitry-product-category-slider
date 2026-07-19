<?php
/**
 * Elementor widget for the Amitry slider.
 *
 * Exposes the full set of slider options as Elementor controls and renders
 * the result through WCSP_Renderer, the same engine used by the Gutenberg
 * block and the [amitry_slider] shortcode, so output and styling match.
 *
 * Extension points for the Pro add-on:
 *   - Controls: hook Elementor's
 *     `elementor/element/amitry_slider/{section}/before_section_end`
 *     or add a section via `elementor/element/after_section_end`.
 *   - Render attributes: filter `wcsp_elementor_render_atts` to add Pro
 *     attributes (e.g. proHoverSwap) onto the atts passed to the renderer.
 *
 * @package AmitryProductCategorySlider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WCSP_Elementor_Widget
 */
class WCSP_Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget slug. Kept as `amitry_slider` for backward compatibility.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'amitry_slider';
	}

	/**
	 * Widget title shown in the Elementor panel.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Amitry Slider', 'amitry-product-category-slider' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-slider-push';
	}

	/**
	 * Widget categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'woocommerce-elements', 'general' );
	}

	/**
	 * Search keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'woocommerce', 'product', 'category', 'slider', 'carousel', 'amitry' );
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls() {

		/* ──────────────────────────── SOURCE ──────────────────────────── */
		$this->start_controls_section(
			'section_source',
			array(
				'label' => __( 'Source', 'amitry-product-category-slider' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'slider_type',
			array(
				'label'   => __( 'Slider Type', 'amitry-product-category-slider' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'products',
				'options' => array(
					'products'   => __( 'Products', 'amitry-product-category-slider' ),
					'categories' => __( 'Categories', 'amitry-product-category-slider' ),
				),
			)
		);

		$this->add_control(
			'product_filter',
			array(
				'label'     => __( 'Products From', 'amitry-product-category-slider' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'newest',
				'options'   => array(
					'newest'      => __( 'Newest', 'amitry-product-category-slider' ),
					'bestselling' => __( 'Best Selling', 'amitry-product-category-slider' ),
					'on_sale'     => __( 'On Sale', 'amitry-product-category-slider' ),
					'featured'    => __( 'Featured', 'amitry-product-category-slider' ),
					'top_rated'   => __( 'Top Rated', 'amitry-product-category-slider' ),
				),
				'condition' => array( 'slider_type' => 'products' ),
			)
		);

		$this->add_control(
			'product_count',
			array(
				'label'     => __( 'Number of Products', 'amitry-product-category-slider' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 48,
				'default'   => 12,
				'condition' => array( 'slider_type' => 'products' ),
			)
		);

		$this->add_control(
			'category_sort',
			array(
				'label'     => __( 'Sort Categories By', 'amitry-product-category-slider' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'name',
				'options'   => array(
					'name'  => __( 'Name', 'amitry-product-category-slider' ),
					'count' => __( 'Product Count', 'amitry-product-category-slider' ),
					'menu'  => __( 'Menu Order', 'amitry-product-category-slider' ),
				),
				'condition' => array( 'slider_type' => 'categories' ),
			)
		);

		$this->add_control(
			'max_categories',
			array(
				'label'     => __( 'Number of Categories', 'amitry-product-category-slider' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 48,
				'default'   => 12,
				'condition' => array( 'slider_type' => 'categories' ),
			)
		);

		$this->add_control(
			'hide_empty',
			array(
				'label'        => __( 'Hide Empty Categories', 'amitry-product-category-slider' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'slider_type' => 'categories' ),
			)
		);

		$this->end_controls_section();

		/* ──────────────────────────── LAYOUT ──────────────────────────── */
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => __( 'Layout', 'amitry-product-category-slider' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'slides_desktop',
			array(
				'label'   => __( 'Slides per View (Desktop)', 'amitry-product-category-slider' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 8,
				'default' => 4,
			)
		);

		$this->add_control(
			'slides_tablet',
			array(
				'label'   => __( 'Slides per View (Tablet)', 'amitry-product-category-slider' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 6,
				'default' => 2,
			)
		);

		$this->add_control(
			'slides_mobile',
			array(
				'label'   => __( 'Slides per View (Mobile)', 'amitry-product-category-slider' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 3,
				'default' => 1,
			)
		);

		$this->add_control(
			'scale_desktop',
			array(
				'label'   => __( 'Card Scale Desktop (%)', 'amitry-product-category-slider' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 50,
				'max'     => 100,
				'default' => 100,
			)
		);

		$this->add_control(
			'scale_tablet',
			array(
				'label'   => __( 'Card Scale Tablet (%)', 'amitry-product-category-slider' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 50,
				'max'     => 100,
				'default' => 100,
			)
		);

		$this->add_control(
			'scale_mobile',
			array(
				'label'   => __( 'Card Scale Mobile (%)', 'amitry-product-category-slider' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 50,
				'max'     => 100,
				'default' => 100,
			)
		);

		$this->add_control(
			'space_between',
			array(
				'label'   => __( 'Gap (px)', 'amitry-product-category-slider' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 0,
				'max'     => 80,
				'default' => 24,
			)
		);

		$this->end_controls_section();

		/* ─────────────────────────── ELEMENTS ─────────────────────────── */
		$this->start_controls_section(
			'section_elements',
			array(
				'label' => __( 'Elements', 'amitry-product-category-slider' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_image',
			array(
				'label'        => __( 'Image', 'amitry-product-category-slider' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_title',
			array(
				'label'        => __( 'Title', 'amitry-product-category-slider' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_count',
			array(
				'label'        => __( 'Product Count', 'amitry-product-category-slider' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'slider_type' => 'categories' ),
			)
		);

		$this->add_control(
			'content_align',
			array(
				'label'   => __( 'Content Alignment', 'amitry-product-category-slider' ),
				'type'    => \Elementor\Controls_Manager::CHOOSE,
				'default' => 'left',
				'options' => array(
					'left'   => array(
						'title' => __( 'Left', 'amitry-product-category-slider' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => __( 'Center', 'amitry-product-category-slider' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => __( 'Right', 'amitry-product-category-slider' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
			)
		);

		$this->add_control(
			'max_width',
			array(
				'label'       => __( 'Max Width (px)', 'amitry-product-category-slider' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 1600,
				'step'        => 10,
				'default'     => 0,
				'description' => __( '0 = full container width. Caps the slider width and centers it.', 'amitry-product-category-slider' ),
			)
		);

		$this->add_control(
			'show_price',
			array(
				'label'        => __( 'Price', 'amitry-product-category-slider' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'slider_type' => 'products' ),
			)
		);

		$this->add_control(
			'show_sale_badge',
			array(
				'label'        => __( 'Sale Badge', 'amitry-product-category-slider' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'slider_type' => 'products' ),
			)
		);

		$this->add_control(
			'show_rating',
			array(
				'label'        => __( 'Rating Stars', 'amitry-product-category-slider' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'slider_type' => 'products' ),
			)
		);

		$this->add_control(
			'show_excerpt',
			array(
				'label'        => __( 'Short Description', 'amitry-product-category-slider' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => array( 'slider_type' => 'products' ),
			)
		);

		$this->add_control(
			'show_stock',
			array(
				'label'        => __( 'Stock Status', 'amitry-product-category-slider' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'slider_type' => 'products' ),
			)
		);

		$this->add_control(
			'show_add_to_cart',
			array(
				'label'        => __( 'Add to Cart Button', 'amitry-product-category-slider' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'slider_type' => 'products' ),
			)
		);

		$this->end_controls_section();

		/* ─────────────────────────── BEHAVIOR ─────────────────────────── */
		$this->start_controls_section(
			'section_behavior',
			array(
				'label' => __( 'Behavior', 'amitry-product-category-slider' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'autoplay',
			array(
				'label'        => __( 'Autoplay', 'amitry-product-category-slider' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'autoplay_delay',
			array(
				'label'     => __( 'Autoplay Delay (ms)', 'amitry-product-category-slider' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 1000,
				'max'       => 10000,
				'step'      => 250,
				'default'   => 3000,
				'condition' => array( 'autoplay' => 'yes' ),
			)
		);

		$this->add_control(
			'pause_on_hover',
			array(
				'label'        => __( 'Pause on Hover', 'amitry-product-category-slider' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'autoplay' => 'yes' ),
			)
		);

		$this->add_control(
			'loop',
			array(
				'label'        => __( 'Loop', 'amitry-product-category-slider' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'speed',
			array(
				'label'   => __( 'Transition Speed (ms)', 'amitry-product-category-slider' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 100,
				'max'     => 2000,
				'step'    => 50,
				'default' => 600,
			)
		);

		$this->add_control(
			'transition_effect',
			array(
				'label'       => __( 'Transition', 'amitry-product-category-slider' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'slide',
				'options'     => array(
					'slide' => __( 'Slide', 'amitry-product-category-slider' ),
					'fade'  => __( 'Fade', 'amitry-product-category-slider' ),
				),
				'description' => __( 'Fade shows one item at a time, ideal for a single large image.', 'amitry-product-category-slider' ),
			)
		);

		$this->add_control(
			'touch',
			array(
				'label'        => __( 'Touch / Swipe', 'amitry-product-category-slider' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'mouse_drag',
			array(
				'label'        => __( 'Mouse Drag', 'amitry-product-category-slider' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'keyboard',
			array(
				'label'        => __( 'Keyboard Control', 'amitry-product-category-slider' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->end_controls_section();

		/* ────────────────────────── NAVIGATION ────────────────────────── */
		$this->start_controls_section(
			'section_navigation',
			array(
				'label' => __( 'Navigation', 'amitry-product-category-slider' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'arrows',
			array(
				'label'        => __( 'Arrows', 'amitry-product-category-slider' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'dots',
			array(
				'label'        => __( 'Pagination Dots', 'amitry-product-category-slider' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'scrollbar',
			array(
				'label'        => __( 'Scrollbar', 'amitry-product-category-slider' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'progress',
			array(
				'label'        => __( 'Progress Bar', 'amitry-product-category-slider' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'counter',
			array(
				'label'        => __( 'Slide Counter', 'amitry-product-category-slider' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->end_controls_section();

		/* ──────────────────────────── STYLE ───────────────────────────── */
		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Style', 'amitry-product-category-slider' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'style_variant',
			array(
				'label'   => __( 'Card Style', 'amitry-product-category-slider' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'clean-card',
				'options' => apply_filters(
					'wcsp_elementor_style_options',
					array(
						'clean-card' => __( 'Clean Card', 'amitry-product-category-slider' ),
						'minimal'    => __( 'Minimal', 'amitry-product-category-slider' ),
					)
				),
			)
		);

		$this->add_control(
			'image_shape',
			array(
				'label'   => __( 'Image Shape', 'amitry-product-category-slider' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'rounded',
				'options' => array(
					'rounded' => __( 'Rounded', 'amitry-product-category-slider' ),
					'square'  => __( 'Square', 'amitry-product-category-slider' ),
					'circle'  => __( 'Circle', 'amitry-product-category-slider' ),
				),
			)
		);

		$this->add_control(
			'aspect_ratio',
			array(
				'label'   => __( 'Image Aspect Ratio', 'amitry-product-category-slider' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '4-3',
				'options' => array(
					'1-1'  => '1:1',
					'4-3'  => '4:3',
					'3-4'  => '3:4',
					'16-9' => '16:9',
				),
			)
		);

		$this->add_control(
			'image_fit',
			array(
				'label'       => __( 'Image Fit', 'amitry-product-category-slider' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'cover',
				'options'     => array(
					'cover'   => __( 'Crop to fill', 'amitry-product-category-slider' ),
					'contain' => __( 'Show full image', 'amitry-product-category-slider' ),
				),
				'description' => __( 'Show full image keeps photos uncropped, ideal for photography.', 'amitry-product-category-slider' ),
			)
		);

		$this->add_control(
			'card_radius',
			array(
				'label'   => __( 'Card Corner Radius (px)', 'amitry-product-category-slider' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 0,
				'max'     => 40,
				'default' => 16,
			)
		);

		$this->add_control(
			'card_padding',
			array(
				'label'   => __( 'Card Padding (px)', 'amitry-product-category-slider' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 0,
				'max'     => 60,
				'default' => 20,
			)
		);

		$this->add_control(
			'card_bg',
			array(
				'label'   => __( 'Card Background', 'amitry-product-category-slider' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '#ffffff',
			)
		);

		$this->add_control(
			'shadow',
			array(
				'label'   => __( 'Shadow', 'amitry-product-category-slider' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'soft',
				'options' => array(
					'none'   => __( 'None', 'amitry-product-category-slider' ),
					'soft'   => __( 'Soft', 'amitry-product-category-slider' ),
					'medium' => __( 'Medium', 'amitry-product-category-slider' ),
					'strong' => __( 'Strong', 'amitry-product-category-slider' ),
				),
			)
		);

		$this->add_control(
			'hover',
			array(
				'label'   => __( 'Hover Effect', 'amitry-product-category-slider' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'lift',
				'options' => array(
					'none'       => __( 'None', 'amitry-product-category-slider' ),
					'lift'       => __( 'Lift', 'amitry-product-category-slider' ),
					'zoom'       => __( 'Zoom', 'amitry-product-category-slider' ),
					'shine'      => __( 'Shine', 'amitry-product-category-slider' ),
					'lift_shine' => __( 'Lift + Shine', 'amitry-product-category-slider' ),
					'lift_zoom'  => __( 'Lift + Zoom', 'amitry-product-category-slider' ),
				),
			)
		);

		$this->add_control(
			'overlay',
			array(
				'label'        => __( 'Overlay Gradient', 'amitry-product-category-slider' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => __( 'Title Color', 'amitry-product-category-slider' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '',
				'condition' => array( 'show_title' => 'yes' ),
			)
		);

		$this->add_control(
			'price_color',
			array(
				'label'     => __( 'Price Color', 'amitry-product-category-slider' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '',
				'condition' => array(
					'slider_type' => 'products',
					'show_price'  => 'yes',
				),
			)
		);

		$this->add_control(
			'arrow_color',
			array(
				'label'   => __( 'Arrow Color', 'amitry-product-category-slider' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '',
			)
		);

		$this->add_control(
			'arrow_bg',
			array(
				'label'   => __( 'Arrow Background', 'amitry-product-category-slider' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '',
			)
		);

		$this->add_control(
			'arrow_size',
			array(
				'label'   => __( 'Arrow Size (px)', 'amitry-product-category-slider' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 24,
				'max'     => 72,
				'default' => 40,
			)
		);

		$this->end_controls_section();

		/* ─────────────────── SECTION HEADER & VIEW ALL ────────────────── */
		$this->start_controls_section(
			'section_header',
			array(
				'label' => __( 'Section Header & View All', 'amitry-product-category-slider' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'section_title',
			array(
				'label'       => __( 'Title', 'amitry-product-category-slider' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => __( 'e.g. Featured Products', 'amitry-product-category-slider' ),
			)
		);

		$this->add_control(
			'section_subtitle',
			array(
				'label'   => __( 'Subtitle', 'amitry-product-category-slider' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '',
			)
		);

		$this->add_control(
			'section_title_size',
			array(
				'label'     => __( 'Title Size (px)', 'amitry-product-category-slider' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 12,
				'max'       => 64,
				'default'   => 28,
				'condition' => array( 'section_title!' => '' ),
			)
		);

		$this->add_control(
			'section_title_weight',
			array(
				'label'     => __( 'Title Weight', 'amitry-product-category-slider' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'bold',
				'options'   => array(
					'normal' => __( 'Normal', 'amitry-product-category-slider' ),
					'500'    => __( 'Medium', 'amitry-product-category-slider' ),
					'bold'   => __( 'Bold', 'amitry-product-category-slider' ),
					'800'    => __( 'Extra Bold', 'amitry-product-category-slider' ),
				),
				'condition' => array( 'section_title!' => '' ),
			)
		);

		$this->add_control(
			'section_title_color',
			array(
				'label'     => __( 'Title Color', 'amitry-product-category-slider' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '',
				'condition' => array( 'section_title!' => '' ),
			)
		);

		$this->add_control(
			'section_subtitle_size',
			array(
				'label'     => __( 'Subtitle Size (px)', 'amitry-product-category-slider' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 10,
				'max'       => 32,
				'default'   => 15,
				'condition' => array( 'section_subtitle!' => '' ),
			)
		);

		$this->add_control(
			'section_subtitle_color',
			array(
				'label'     => __( 'Subtitle Color', 'amitry-product-category-slider' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '',
				'condition' => array( 'section_subtitle!' => '' ),
			)
		);

		$this->add_control(
			'view_all',
			array(
				'label'        => __( 'Show View All Button', 'amitry-product-category-slider' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'view_all_text',
			array(
				'label'     => __( 'View All Text', 'amitry-product-category-slider' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'View All', 'amitry-product-category-slider' ),
				'condition' => array( 'view_all' => 'yes' ),
			)
		);

		$this->add_control(
			'view_all_url',
			array(
				'label'     => __( 'View All URL', 'amitry-product-category-slider' ),
				'type'      => \Elementor\Controls_Manager::URL,
				'default'   => array( 'url' => '' ),
				'condition' => array( 'view_all' => 'yes' ),
			)
		);

		$this->add_control(
			'view_all_bg',
			array(
				'label'     => __( 'Button Background', 'amitry-product-category-slider' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '',
				'condition' => array( 'view_all' => 'yes' ),
			)
		);

		$this->add_control(
			'view_all_text_color',
			array(
				'label'     => __( 'Button Text Color', 'amitry-product-category-slider' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '',
				'condition' => array( 'view_all' => 'yes' ),
			)
		);

		$this->add_control(
			'view_all_radius',
			array(
				'label'     => __( 'Button Radius (px)', 'amitry-product-category-slider' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 40,
				'default'   => 8,
				'condition' => array( 'view_all' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Map Elementor settings onto the renderer's camelCase attributes and
	 * render the slider.
	 */
	protected function render() {
		$s = $this->get_settings_for_display();

		$view_all_url = '';
		if ( isset( $s['view_all_url']['url'] ) ) {
			$view_all_url = $s['view_all_url']['url'];
		}

		$atts = array(
			// Source.
			'sliderType'           => isset( $s['slider_type'] ) ? sanitize_key( $s['slider_type'] ) : 'products',
			'productFilter'        => isset( $s['product_filter'] ) ? sanitize_key( $s['product_filter'] ) : 'newest',
			'productCount'         => isset( $s['product_count'] ) ? (int) $s['product_count'] : 12,
			'categorySortBy'       => isset( $s['category_sort'] ) ? sanitize_key( $s['category_sort'] ) : 'name',
			'maxCategories'        => isset( $s['max_categories'] ) ? (int) $s['max_categories'] : 12,
			'hideEmpty'            => ! empty( $s['hide_empty'] ),

			// Layout.
			'slidesPerViewDesktop' => isset( $s['slides_desktop'] ) ? (int) $s['slides_desktop'] : 4,
			'slidesPerViewTablet'  => isset( $s['slides_tablet'] ) ? (int) $s['slides_tablet'] : 2,
			'slidesPerViewMobile'  => isset( $s['slides_mobile'] ) ? (int) $s['slides_mobile'] : 1,
			'scaleDesktop'         => isset( $s['scale_desktop'] ) ? (int) $s['scale_desktop'] : 100,
			'scaleTablet'          => isset( $s['scale_tablet'] ) ? (int) $s['scale_tablet'] : 100,
			'scaleMobile'          => isset( $s['scale_mobile'] ) ? (int) $s['scale_mobile'] : 100,
			'spaceBetween'         => isset( $s['space_between'] ) ? (int) $s['space_between'] : 24,

			// Elements.
			'showImage'            => ! empty( $s['show_image'] ),
			'showTitle'            => ! empty( $s['show_title'] ),
			'showCount'            => ! empty( $s['show_count'] ),
			'showPrice'            => ! empty( $s['show_price'] ),
			'contentAlign'         => isset( $s['content_align'] ) ? sanitize_key( $s['content_align'] ) : 'left',
			'maxWidth'             => isset( $s['max_width'] ) ? (int) $s['max_width'] : 0,
			'showSaleBadge'        => ! empty( $s['show_sale_badge'] ),
			'showRating'           => ! empty( $s['show_rating'] ),
			'showExcerpt'          => ! empty( $s['show_excerpt'] ),
			'showStock'            => ! empty( $s['show_stock'] ),
			'showAddToCart'        => ! empty( $s['show_add_to_cart'] ),

			// Behavior.
			'autoplay'             => ! empty( $s['autoplay'] ),
			'autoplayDelay'        => isset( $s['autoplay_delay'] ) ? (int) $s['autoplay_delay'] : 3000,
			'pauseOnHover'         => ! empty( $s['pause_on_hover'] ),
			'loop'                 => ! empty( $s['loop'] ),
			'speed'                => isset( $s['speed'] ) ? (int) $s['speed'] : 600,
			'transitionEffect'     => isset( $s['transition_effect'] ) ? (string) $s['transition_effect'] : 'slide',
			'touchEnabled'         => ! empty( $s['touch'] ),
			'mouseDrag'            => ! empty( $s['mouse_drag'] ),
			'keyboardEnabled'      => ! empty( $s['keyboard'] ),

			// Navigation.
			'showArrows'           => ! empty( $s['arrows'] ),
			'showPaginationDots'   => ! empty( $s['dots'] ),
			'showScrollbar'        => ! empty( $s['scrollbar'] ),
			'showProgress'         => ! empty( $s['progress'] ),
			'showCounter'          => ! empty( $s['counter'] ),

			// Style.
			'styleVariant'         => isset( $s['style_variant'] ) ? sanitize_key( $s['style_variant'] ) : 'clean-card',
			'imageShape'           => isset( $s['image_shape'] ) ? sanitize_key( $s['image_shape'] ) : 'rounded',
			'aspectRatio'          => isset( $s['aspect_ratio'] ) ? sanitize_text_field( $s['aspect_ratio'] ) : '4-3',
			'imageFit'             => isset( $s['image_fit'] ) ? sanitize_key( $s['image_fit'] ) : 'cover',
			'cardRadius'           => isset( $s['card_radius'] ) ? (int) $s['card_radius'] : 16,
			'cardPadding'          => isset( $s['card_padding'] ) ? (int) $s['card_padding'] : 20,
			'cardBackgroundColor'  => isset( $s['card_bg'] ) ? sanitize_hex_color( $s['card_bg'] ) : '#ffffff',
			'shadowIntensity'      => isset( $s['shadow'] ) ? sanitize_key( $s['shadow'] ) : 'soft',
			'hoverEffect'          => isset( $s['hover'] ) ? sanitize_key( $s['hover'] ) : 'lift',
			'showOverlayGradient'  => ! empty( $s['overlay'] ),
			'titleColor'           => isset( $s['title_color'] ) ? sanitize_hex_color( $s['title_color'] ) : '',
			'priceColor'           => isset( $s['price_color'] ) ? sanitize_hex_color( $s['price_color'] ) : '',
			'arrowColor'           => isset( $s['arrow_color'] ) ? sanitize_hex_color( $s['arrow_color'] ) : '',
			'arrowBgColor'         => isset( $s['arrow_bg'] ) ? sanitize_hex_color( $s['arrow_bg'] ) : '',
			'arrowSize'            => isset( $s['arrow_size'] ) ? (int) $s['arrow_size'] : 40,

			// Section header / View All.
			'sectionTitle'         => isset( $s['section_title'] ) ? sanitize_text_field( $s['section_title'] ) : '',
			'sectionSubtitle'      => isset( $s['section_subtitle'] ) ? sanitize_text_field( $s['section_subtitle'] ) : '',
			'sectionTitleSize'     => isset( $s['section_title_size'] ) ? (int) $s['section_title_size'] : 28,
			'sectionTitleWeight'   => isset( $s['section_title_weight'] ) ? sanitize_html_class( $s['section_title_weight'] ) : 'bold',
			'sectionTitleColor'    => isset( $s['section_title_color'] ) ? sanitize_hex_color( $s['section_title_color'] ) : '',
			'sectionSubtitleSize'  => isset( $s['section_subtitle_size'] ) ? (int) $s['section_subtitle_size'] : 15,
			'sectionSubtitleColor' => isset( $s['section_subtitle_color'] ) ? sanitize_hex_color( $s['section_subtitle_color'] ) : '',
			'showViewAllButton'    => ! empty( $s['view_all'] ),
			'viewAllText'          => isset( $s['view_all_text'] ) ? sanitize_text_field( $s['view_all_text'] ) : '',
			'viewAllUrl'           => esc_url_raw( $view_all_url ),
			'viewAllBgColor'       => isset( $s['view_all_bg'] ) ? sanitize_hex_color( $s['view_all_bg'] ) : '',
			'viewAllTextColor'     => isset( $s['view_all_text_color'] ) ? sanitize_hex_color( $s['view_all_text_color'] ) : '',
			'viewAllRadius'        => isset( $s['view_all_radius'] ) ? (int) $s['view_all_radius'] : 8,
		);

		/**
		 * Filter the attributes passed to the renderer from the Elementor
		 * widget. The Pro add-on uses this to map its own Elementor controls
		 * (e.g. premium designs and feature toggles) onto renderer attributes.
		 *
		 * @param array $atts Mapped renderer attributes.
		 * @param array $s    Raw Elementor settings for display.
		 */
		$atts = apply_filters( 'wcsp_elementor_render_atts', $atts, $s );

		// WCSP_Renderer escapes its own output.
		echo WCSP_Renderer::render( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside renderer.
	}
}

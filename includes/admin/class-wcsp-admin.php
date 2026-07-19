<?php
/**
 * Admin subsystem bootstrap.
 *
 * Registers the plugin's settings page under Settings -> Amitry Slider.
 * The page is informational: a Welcome/Features tab, a Guide tab, and a
 * Free vs Pro comparison tab. No options are persisted here (the block
 * itself carries all configuration), so the page is purely static
 * marketing + documentation.
 *
 * @package AmitryProductCategorySlider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WCSP_Admin
 */
final class WCSP_Admin {

	const PAGE_SLUG = 'amitry-slider';
	const PRO_URL   = 'https://amitry.de/woocommerce-product-slider-plugin/';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . WCSP_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );
	}

	public function add_menu_page() {
		add_options_page(
			__( 'Amitry Slider', 'amitry-product-category-slider' ),
			__( 'Amitry Slider', 'amitry-product-category-slider' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	public function add_action_links( $links ) {
		$settings_url = admin_url( 'options-general.php?page=' . self::PAGE_SLUG );
		$settings     = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'amitry-product-category-slider' ) . '</a>';
		array_unshift( $links, $settings );
		return $links;
	}

	public function enqueue_assets( $hook ) {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style(
			'wcsp-admin',
			WCSP_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			WCSP_VERSION
		);
	}

	private function get_active_tab() {
		$tab     = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'welcome'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$allowed = array( 'welcome', 'guide', 'pro' );
		return in_array( $tab, $allowed, true ) ? $tab : 'welcome';
	}

	private function tab_url( $tab ) {
		return admin_url( 'options-general.php?page=' . self::PAGE_SLUG . '&tab=' . $tab );
	}

	public function render_page() {
		$active = $this->get_active_tab();
		$tabs   = array(
			'welcome' => __( 'Welcome & Features', 'amitry-product-category-slider' ),
			'guide'   => __( 'Guide', 'amitry-product-category-slider' ),
			'pro'     => __( 'Free vs Pro', 'amitry-product-category-slider' ),
		);
		?>
		<div class="wrap wcsp-admin">
			<div class="wcsp-hero">
				<div class="wcsp-hero__inner">
					<span class="wcsp-hero__mark" aria-hidden="true">
						<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<rect x="2" y="5" width="6" height="14" rx="2" fill="currentColor" opacity="0.5"/>
							<rect x="9" y="3" width="6" height="18" rx="2" fill="currentColor"/>
							<rect x="16" y="5" width="6" height="14" rx="2" fill="currentColor" opacity="0.5"/>
						</svg>
					</span>
					<div class="wcsp-hero__text">
						<h1 class="wcsp-hero__title">
							<?php esc_html_e( 'Amitry Product &amp; Category Slider', 'amitry-product-category-slider' ); ?>
						</h1>
						<p class="wcsp-hero__sub">
							<?php esc_html_e( 'A fast, modern, fully responsive slider for WooCommerce.', 'amitry-product-category-slider' ); ?>
						</p>
					</div>
					<span class="wcsp-hero__version">v<?php echo esc_html( WCSP_VERSION ); ?></span>
				</div>
			</div>

			<div class="wcsp-support">
				<div class="wcsp-support__text">
					<p class="wcsp-support__title">
						<?php esc_html_e( 'Made by two students, with love', 'amitry-product-category-slider' ); ?>
					</p>
					<p class="wcsp-support__body">
						<?php esc_html_e( 'Hi! We are Daniel and Yuzay, two students building this plugin between lectures and late-night study sessions. If it helps your shop, a quick 5-star review means the world to us and helps other store owners discover it. And if you would like to fuel the next coding night, you can buy us a coffee.', 'amitry-product-category-slider' ); ?>
					</p>
				</div>
				<div class="wcsp-support__actions">
					<a class="wcsp-support__btn wcsp-support__btn--review" href="https://wordpress.org/support/plugin/amitry-product-category-slider/reviews/#new-post" target="_blank" rel="noopener noreferrer">
						<span aria-hidden="true">&#9733;</span>
						<?php esc_html_e( 'Leave a 5-star review', 'amitry-product-category-slider' ); ?>
					</a>
					<a class="wcsp-support__btn wcsp-support__btn--donate" href="https://www.paypal.com/donate/?hosted_button_id=D8JUQG5NJ4AXS" target="_blank" rel="noopener noreferrer">
						<span aria-hidden="true">&#9749;</span>
						<?php esc_html_e( 'Buy us a coffee', 'amitry-product-category-slider' ); ?>
					</a>
				</div>
			</div>

			<nav class="nav-tab-wrapper wcsp-admin__tabs">
				<?php foreach ( $tabs as $slug => $label ) : ?>
					<a
						href="<?php echo esc_url( $this->tab_url( $slug ) ); ?>"
						class="nav-tab <?php echo $active === $slug ? 'nav-tab-active' : ''; ?>"
					>
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="wcsp-admin__content">
				<?php
				switch ( $active ) {
					case 'guide':
						$this->render_tab_guide();
						break;
					case 'pro':
						$this->render_tab_pro();
						break;
					case 'welcome':
					default:
						$this->render_tab_welcome();
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	private function render_tab_welcome() {
		$features = array(
			array(
				'icon'  => 'grid',
				'title' => __( 'Products & Categories', 'amitry-product-category-slider' ),
				'desc'  => __( 'Show a slider of WooCommerce products or product categories - your choice per block.', 'amitry-product-category-slider' ),
			),
			array(
				'icon'  => 'devices',
				'title' => __( 'Per-Device Settings', 'amitry-product-category-slider' ),
				'desc'  => __( 'Set slides per view, spacing, card scale, padding and arrow size separately for Desktop, Tablet and Mobile.', 'amitry-product-category-slider' ),
			),
			array(
				'icon'  => 'palette',
				'title' => __( 'Two Card Designs', 'amitry-product-category-slider' ),
				'desc'  => __( 'Clean Card and Minimal styles, each with adjustable shadows, hover effects, radius and colors.', 'amitry-product-category-slider' ),
			),
			array(
				'icon'  => 'compass',
				'title' => __( 'Smart Navigation', 'amitry-product-category-slider' ),
				'desc'  => __( 'Arrows, pagination dots, scrollbar, progress bar, slide counter and keyboard navigation - all toggleable.', 'amitry-product-category-slider' ),
			),
			array(
				'icon'  => 'play',
				'title' => __( 'Autoplay & Loop', 'amitry-product-category-slider' ),
				'desc'  => __( 'Optional autoplay with adjustable delay, pause-on-hover and seamless infinite looping.', 'amitry-product-category-slider' ),
			),
			array(
				'icon'  => 'heading',
				'title' => __( 'Section Header & Button', 'amitry-product-category-slider' ),
				'desc'  => __( 'Add a styled section title, subtitle and a customizable "View All" button with icon.', 'amitry-product-category-slider' ),
			),
		);
		?>
		<div class="wcsp-card-box wcsp-welcome">
			<h2><?php esc_html_e( 'Welcome to Amitry Slider', 'amitry-product-category-slider' ); ?></h2>
			<p class="wcsp-lead">
				<?php esc_html_e( 'A fast, modern, fully responsive slider for your WooCommerce products and categories - built as a native block with live preview.', 'amitry-product-category-slider' ); ?>
			</p>
			<p>
				<?php
				printf(
					/* translators: %s: "Guide" linked to the Guide tab. */
					esc_html__( 'New here? Head to the %s to add your first slider in under a minute.', 'amitry-product-category-slider' ),
					'<a href="' . esc_url( $this->tab_url( 'guide' ) ) . '">' . esc_html__( 'Guide', 'amitry-product-category-slider' ) . '</a>'
				);
				?>
			</p>
		</div>

		<h2 class="wcsp-section-heading"><?php esc_html_e( 'Features', 'amitry-product-category-slider' ); ?></h2>
		<div class="wcsp-feature-grid">
			<?php foreach ( $features as $feature ) : ?>
				<div class="wcsp-feature">
					<span class="wcsp-feature__icon" aria-hidden="true"><?php echo $this->icon( $feature['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<h3><?php echo esc_html( $feature['title'] ); ?></h3>
					<p><?php echo esc_html( $feature['desc'] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Return an inline SVG icon by name. All icons are 24x24, stroke-based,
	 * and inherit currentColor so CSS controls their color.
	 *
	 * @param string $name Icon key.
	 * @return string SVG markup (safe, hard-coded).
	 */
	private function icon( $name ) {
		$paths = array(
			'grid'    => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
			'devices' => '<rect x="2" y="4" width="14" height="10" rx="1.5"/><path d="M2 18h14"/><rect x="18" y="9" width="4" height="11" rx="1"/>',
			'palette' => '<circle cx="12" cy="12" r="9"/><circle cx="8.5" cy="9.5" r="1.2" fill="currentColor" stroke="none"/><circle cx="15.5" cy="9.5" r="1.2" fill="currentColor" stroke="none"/><circle cx="9" cy="15" r="1.2" fill="currentColor" stroke="none"/>',
			'compass' => '<circle cx="12" cy="12" r="9"/><polygon points="16 8 13 13 8 16 11 11"/>',
			'play'    => '<circle cx="12" cy="12" r="9"/><polygon points="10 8 16 12 10 16" fill="currentColor" stroke="none"/>',
			'heading' => '<path d="M6 4v16M18 4v16M6 12h12"/>',
			'rocket'  => '<path d="M5 15c-1 1-1.5 4-1.5 4s3-.5 4-1.5"/><path d="M9 13l-2-2c1-5 5-8 9-8 0 4-3 8-8 9l-2-2z"/><circle cx="14" cy="9" r="1.4" fill="currentColor" stroke="none"/>',
			'bolt'    => '<polygon points="13 2 4 14 11 14 10 22 19 9 13 9"/>',
			'cursor'  => '<path d="M5 3l14 7-6 2-2 6z"/>',
		);
		$inner = isset( $paths[ $name ] ) ? $paths[ $name ] : '';
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $inner . '</svg>';
	}

	private function render_tab_guide() {
		$steps = array(
			array(
				'title' => __( 'Add the block', 'amitry-product-category-slider' ),
				'desc'  => __( 'Edit any page or post, click the + button and search for "Amitry". Insert the "Category/Product Slider" block.', 'amitry-product-category-slider' ),
			),
			array(
				'title' => __( 'Choose your data source', 'amitry-product-category-slider' ),
				'desc'  => __( 'In the block sidebar under "Data Source", pick Products or Categories. Set how many to show and which to include.', 'amitry-product-category-slider' ),
			),
			array(
				'title' => __( 'Adjust the layout', 'amitry-product-category-slider' ),
				'desc'  => __( 'Under "Layout", use the device tabs (Desktop / Tablet / Mobile) to set slides per view, spacing and card scale per device.', 'amitry-product-category-slider' ),
			),
			array(
				'title' => __( 'Preview each device', 'amitry-product-category-slider' ),
				'desc'  => __( 'Use the editor preview switcher (top toolbar) to see exactly how the slider looks on each screen size. The sidebar follows your selection.', 'amitry-product-category-slider' ),
			),
			array(
				'title' => __( 'Style your cards', 'amitry-product-category-slider' ),
				'desc'  => __( 'Open the Styles tab (the half-moon icon) to set the design, shadow, hover effect, colors, image shape and more.', 'amitry-product-category-slider' ),
			),
			array(
				'title' => __( 'Publish', 'amitry-product-category-slider' ),
				'desc'  => __( 'Hit Publish or Update. Your slider is live, responsive and touch-ready out of the box.', 'amitry-product-category-slider' ),
			),
		);
		?>
		<div class="wcsp-card-box">
			<h2><?php esc_html_e( 'Quick Start Guide', 'amitry-product-category-slider' ); ?></h2>
			<ol class="wcsp-steps">
				<?php foreach ( $steps as $i => $step ) : ?>
					<li class="wcsp-step">
						<span class="wcsp-step__num"><?php echo esc_html( $i + 1 ); ?></span>
						<div class="wcsp-step__body">
							<h3><?php echo esc_html( $step['title'] ); ?></h3>
							<p><?php echo esc_html( $step['desc'] ); ?></p>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>

		<div class="wcsp-card-box">
			<h2><?php esc_html_e( 'Tips', 'amitry-product-category-slider' ); ?></h2>
			<ul class="wcsp-tips">
				<li><?php esc_html_e( 'Empty per-device values inherit from Desktop, so you only set what differs.', 'amitry-product-category-slider' ); ?></li>
				<li><?php esc_html_e( 'On mobile, arrows automatically move onto the image area to avoid covering text.', 'amitry-product-category-slider' ); ?></li>
				<li><?php esc_html_e( 'Use the "Minimal" design for a transparent, borderless look that blends with your theme.', 'amitry-product-category-slider' ); ?></li>
			</ul>
		</div>
		<?php
	}

	private function render_tab_pro() {
		// Free baseline features (shown as a quick list, all included in Free).
		$free_features = array(
			__( 'Product & category sliders', 'amitry-product-category-slider' ),
			__( 'Per-device responsive settings', 'amitry-product-category-slider' ),
			__( 'Clean Card & Minimal designs', 'amitry-product-category-slider' ),
			__( 'Autoplay, loop & full navigation', 'amitry-product-category-slider' ),
			__( 'Section header & View All button', 'amitry-product-category-slider' ),
			__( 'Add to cart button', 'amitry-product-category-slider' ),
			__( 'Unlimited sliders', 'amitry-product-category-slider' ),
		);

		// Pro features grouped by category. Each group: heading + list of rows.
		// Every row here is Pro-only (Free column shows a dash, Pro a check).
		// This list reflects features that actually ship in the Pro add-on.
		$pro_groups = array(
			array(
				'group' => __( 'Premium designs', 'amitry-product-category-slider' ),
				'items' => array(
					__( 'Overlay design', 'amitry-product-category-slider' ),
					__( 'Circle design', 'amitry-product-category-slider' ),
					__( '3D Carousel design', 'amitry-product-category-slider' ),
					__( 'Masonry design', 'amitry-product-category-slider' ),
				),
			),
			array(
				'group' => __( 'Conversion', 'amitry-product-category-slider' ),
				'items' => array(
					__( 'AJAX add to cart (no reload)', 'amitry-product-category-slider' ),
					__( 'Quick View popup', 'amitry-product-category-slider' ),
					__( 'Custom badges (New, Bestseller)', 'amitry-product-category-slider' ),
					__( 'Hover image swap (second product image)', 'amitry-product-category-slider' ),
				),
			),
			array(
				'group' => __( 'Engagement', 'amitry-product-category-slider' ),
				'items' => array(
					__( 'Wishlist with dedicated page', 'amitry-product-category-slider' ),
					__( 'Sale countdown timer', 'amitry-product-category-slider' ),
					__( 'Stock bar (visual inventory level)', 'amitry-product-category-slider' ),
					__( '"Only X left in stock" label', 'amitry-product-category-slider' ),
				),
			),
			array(
				'group' => __( 'Support', 'amitry-product-category-slider' ),
				'items' => array(
					__( 'Priority support', 'amitry-product-category-slider' ),
				),
			),
		);

		$yes = '<span class="wcsp-yes" aria-label="' . esc_attr__( 'Included', 'amitry-product-category-slider' ) . '">&#10003;</span>';
		$no  = '<span class="wcsp-no" aria-label="' . esc_attr__( 'Not included', 'amitry-product-category-slider' ) . '">&minus;</span>';
		?>
		<div class="wcsp-card-box">
			<h2><?php esc_html_e( 'Free vs Pro', 'amitry-product-category-slider' ); ?></h2>
			<p class="wcsp-lead">
				<?php esc_html_e( 'Everything in Free stays free. Pro unlocks premium designs and conversion-boosting features.', 'amitry-product-category-slider' ); ?>
			</p>

			<table class="wcsp-compare">
				<thead>
					<tr>
						<th class="wcsp-compare__feature"><?php esc_html_e( 'Feature', 'amitry-product-category-slider' ); ?></th>
						<th class="wcsp-compare__free"><?php esc_html_e( 'Free', 'amitry-product-category-slider' ); ?></th>
						<th class="wcsp-compare__pro"><?php esc_html_e( 'Pro', 'amitry-product-category-slider' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php // Free baseline group. ?>
					<tr class="wcsp-compare__group">
						<td colspan="3"><?php esc_html_e( 'Core (included in Free)', 'amitry-product-category-slider' ); ?></td>
					</tr>
					<?php foreach ( $free_features as $feature ) : ?>
						<tr>
							<td><?php echo esc_html( $feature ); ?></td>
							<td class="wcsp-compare__cell"><?php echo $yes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
							<td class="wcsp-compare__cell"><?php echo $yes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
						</tr>
					<?php endforeach; ?>

					<?php // Pro groups. ?>
					<?php foreach ( $pro_groups as $group ) : ?>
						<tr class="wcsp-compare__group">
							<td colspan="3"><?php echo esc_html( $group['group'] ); ?></td>
						</tr>
						<?php foreach ( $group['items'] as $item ) : ?>
							<tr>
								<td><?php echo esc_html( $item ); ?></td>
								<td class="wcsp-compare__cell"><?php echo $no; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
								<td class="wcsp-compare__cell"><?php echo $yes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</tbody>
			</table>

			<div class="wcsp-cta">
				<a class="button button-primary button-hero" href="<?php echo esc_url( self::PRO_URL ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Upgrade to Pro', 'amitry-product-category-slider' ); ?>
				</a>
			</div>
		</div>
		<?php
	}
}

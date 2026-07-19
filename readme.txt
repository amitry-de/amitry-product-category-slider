=== Amitry Product & Category Slider for WooCommerce ===
Contributors: amitry
Donate link: https://www.paypal.com/donate/?hosted_button_id=D8JUQG5NJ4AXS
Tags: product carousel, product gallery, product grid, product table, woocommerce product slider
Requires at least: 6.3
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 4.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Responsive WooCommerce product slider and category carousel. Gutenberg block, Elementor widget and shortcode. Fast, no-code, fully customizable.

== Description ==

Amitry Product & Category Slider is a fast, responsive slider for WooCommerce that lets you showcase products or product categories in a clean, customizable carousel. Build a WooCommerce product slider or a category carousel in minutes with a Gutenberg block, an Elementor widget, or a simple shortcode, with no code required.

Whether you want to highlight featured products on your homepage, scroll through best sellers, or guide shoppers into product categories, this plugin gives you a lightweight, mobile-friendly slider that loads only where you use it.

**[Get the Pro add-on](https://amitry.de/amitry-product-category-slider/)** for premium designs, a thumbnail strip, a full screen lightbox, a category dropdown, a masonry grid view and more.

Live demo: see the product slider, category carousel and every premium design in action on the [live demo site](https://slider.amitry.de/).

= Why choose this WooCommerce slider =

* **No code needed.** Add a slider with a block, widget or shortcode
* **Product slider and category slider** in one plugin
* **Fully responsive.** Separate settings for desktop, tablet and mobile
* **Lightweight and fast.** Assets load only on pages with a slider
* **SEO-friendly.** Server-side rendered HTML, content visible without JavaScript
* **Works with any theme.** Block themes (FSE) and classic themes
* **Accessible.** Keyboard navigation and respects the system reduced-motion setting
* **Translation-ready.** Ships with a .pot file
* **Honest free version.** Everything described here is in the free plugin

= Three ways to add a slider =

* **Gutenberg block.** Drag the Amitry Slider block into any page or post with a live preview
* **Elementor widget.** A dedicated Amitry Slider widget appears in the Elementor editor
* **Shortcode.** Drop `[amitry_slider]` into any content area

All three share one render engine, so every option behaves identically wherever you use it.

= Popular use cases =

* **Homepage product slider.** Feature your best products front and center
* **New arrivals carousel.** Show the newest products in your store automatically
* **Best sellers slider.** Highlight your top selling WooCommerce products
* **Sale and deals slider.** Display on-sale products, with an optional countdown timer in Pro
* **Featured products showcase.** Present a hand-picked selection of products
* **Product category carousel.** Help shoppers browse your store by category
* **Related products slider.** Keep visitors shopping on single product pages
* **Photography and portfolio galleries.** Show uncropped photos at their true proportions
* **Shop and landing page carousels.** Add a product carousel anywhere with the block, widget or shortcode

= Product slider =

Pull products from any of these sources:

* Newest products
* Best selling
* On sale
* Featured
* Top rated
* Manual selection
* By category

Show or hide each element: product image, title, price with sale strike-through, sale badge, rating stars, short description, stock status and an add-to-cart button.

= Category slider =

* Show all product categories with an optional exclude list
* Sort by name, product count or menu order
* Hide empty categories
* Falls back to the first product image when a category has no thumbnail
* Optional product count per category

= Layout and design =

* Two card styles: Clean Card and Minimal
* Rounded, square or circle image shapes
* Aspect ratios: 4:3, 1:1, 3:4 and 16:9
* **Image Fit.** Crop to fill for uniform cards, or show the full image uncropped on a neutral backdrop, ideal for photography
* **Content Alignment.** Align the card title, price and other text left, center or right
* **Max Width.** Cap how wide the slider grows and center it, which keeps single-column sliders tidy on full width themes
* Adjustable columns per device, gap, card padding, radius and shadow
* Hover effects: lift, zoom, shine and lift plus zoom
* Section heading, subheading and an optional View All button

= Behavior and navigation =

* **Transitions.** Slide, or Fade for one large image at a time
* Arrows, dots, scrollbar, progress bar, slide counter and keyboard control
* Autoplay with delay, loop and pause on hover
* Per-device control over which navigation appears

= Block editor integration =

The Gutenberg block has a clean two-panel sidebar: a Settings panel for the data source, layout, behavior and navigation, and a Styles panel for card style, colors, shadows and hover effects. A live server-side preview shows exactly what visitors will see.

= Shortcode examples =

    [amitry_slider type="products" filter="newest" count="12"]
    [amitry_slider type="products" filter="sale" slides_desktop="4"]
    [amitry_slider type="products" effect="fade" fit="contain"]
    [amitry_slider type="products" align="center" max_width="1140"]
    [amitry_slider type="categories" sort="count"]

All block options are available as shortcode attributes.

= Performance =

* CSS and JavaScript load only on pages where a slider is rendered
* Optional query caching for product and category queries
* Images are requested at a proportional size, so photos keep their real proportions and no extra crop is generated on upload
* Swiper is bundled locally, with no external CDN requests

= WooCommerce compatible =

* HPOS (High-Performance Order Storage) compatible
* Cart and Checkout blocks compatible
* Works with the latest WooCommerce and WordPress

= Go further with Pro =

The free plugin above is complete and works fully on its own. The separate Pro add-on requires it and extends it through the public hook API, with nothing removed from the free version.

**Viewing and navigation**

* **Thumbnail strip.** A row of clickable thumbnails below the slider, synced with the main image
* **Lightbox.** An expand button opens a full screen viewer with crossfading photos, a slideshow button, a full screen mode and swipe on touch devices. Each photo links to its product
* **Hover Zoom.** A magnifier lens follows the pointer and zooms into detail using the original image
* **Grid View Toggle.** Visitors switch between the slider and a true masonry grid of uncropped photos, with a control for the number of columns

**Browsing and scale**

* **Category Dropdown.** A category selector in the section head lets visitors switch category without leaving the page, so the slider can stand in for a shop page. Products load on demand
* **Load More.** Show one batch and append the next on demand, which keeps large catalogues fast

**Selling**

* **Hover Image Swap.** Show the second product image on hover
* **Custom Badges.** Automatic New and Bestseller badges
* **AJAX Add to Cart.** Add to the cart without reloading the page
* **Quick View.** A product preview popup
* **Sale Countdown.** A timer on products whose sale has an end date
* **Wishlist.** A heart button with a dedicated wishlist page
* **Stock Bar and Low Stock Label.** Show scarcity on low-stock products

**Premium designs**

* Overlay, Circle, 3D Carousel and Masonry

See the [live demo](https://slider.amitry.de/), or [get the Pro add-on](https://amitry.de/amitry-product-category-slider/).

== Installation ==

1. Make sure WooCommerce is installed and active.
2. Install the plugin from the WordPress plugin directory, or upload the plugin folder to `/wp-content/plugins/`.
3. Activate the plugin through the Plugins screen.
4. Add the Amitry Slider block to any page, drop the Elementor widget into a section, or use the `[amitry_slider]` shortcode.
5. Set global defaults under Settings, then Amitry Slider.

== Frequently Asked Questions ==

= Does this plugin work without WooCommerce? =

No. WooCommerce is required because the plugin reads WooCommerce products and product categories.

= How do I add a product slider without code? =

Add the Amitry Slider block in the editor and choose Products as the type, or use the Elementor widget, or paste the `[amitry_slider type="products"]` shortcode. No code is required.

= Can I show product categories as a carousel? =

Yes. Set the slider type to Categories to display a category carousel with images, names and optional product counts.

= Are my photos cropped? =

Only visually, and only if you want. The slider requests a proportional image and applies the shape you choose with CSS, so the file on disk keeps its real proportions. Set Image Fit to "Crop to fill" for uniform cards, or to "Show full image" to display the whole photo uncropped. Nothing is cropped on upload.

= My photos are 3:2 or 2:3, not 4:3. Will they work? =

Yes. Pick the aspect ratio that suits them, or choose "Show full image" to show them whole. Photos of any ratio keep their true proportions.

= The slider is too wide on my theme. What can I do? =

Set the Max Width option in the Styles panel. The slider will grow no wider than that value and will center itself, which is useful on themes with a wide or full width content area.

= Is it compatible with HPOS (High-Performance Order Storage)? =

Yes. The plugin declares compatibility with `custom_order_tables`. It does not read or write orders.

= Is it compatible with block themes (FSE)? =

Yes. The block uses server-side rendering and works in any theme that supports the block editor.

= Does it work with Elementor? =

Yes. A dedicated Amitry Slider widget is registered automatically when Elementor is active.

= Will the slider slow down my site? =

No. The slider only loads its CSS and JavaScript on pages where it is actually used, and Swiper is bundled locally instead of loaded from an external CDN.

= Is the slider responsive on mobile? =

Yes. You can set a different number of slides per view for desktop, tablet and mobile, and choose which navigation appears on each.

= Is it accessible? =

The slider supports keyboard navigation, and it respects the system reduced-motion setting: autoplay is disabled, transitions are instant and hover motion is removed.

= Is it translation-ready? =

Yes. The plugin uses standard WordPress internationalization and ships with a .pot file in `/languages/`.

= Is there a live demo? =

Yes. You can see the product slider, category carousel and all premium designs in action on the [live demo](https://slider.amitry.de/).

= Can I create a featured products slider? =

Yes. Choose Featured as the product source to build a featured products slider. You can also pull from newest, best selling, on sale, top rated, manual selection or a specific category.

= Can I show products from a specific category? =

Yes. Set the product source to a category to show products from that category, or use the category slider to display your product categories as a carousel.

= Does the slider support autoplay and infinite loop? =

Yes. You can enable autoplay with an adjustable delay, pause on hover and seamless infinite looping.

= Can I customize the slider design and colors? =

Yes. You can choose a card style, image shape, aspect ratio, image fit, content alignment, max width, columns per device, gaps, shadows and hover effects, plus colors for titles, prices and buttons.

= Does it work on the WooCommerce shop and product pages? =

Yes. You can place a slider on any page or post, including the homepage, shop page, single product pages and landing pages, using the block, the Elementor widget or the shortcode.

= What does the Pro add-on add? =

Pro adds a thumbnail strip, a full screen lightbox, a hover zoom magnifier, a masonry grid view with a toggle, a category dropdown, a load more button, hover image swap, automatic badges, AJAX add to cart, quick view, a sale countdown, a wishlist, stock indicators and four premium designs. The free plugin stays complete on its own, and Pro requires it.

= Can I extend the plugin from my own code? =

Yes. The plugin exposes a documented hook API for add-ons, including filters for the rendered classes, the card markup, the image size and the render attributes, plus actions around the render and inside the section head.

== Screenshots ==

1. A WooCommerce product slider on the storefront, with sale badges, prices, ratings and an add to cart button
2. A product category carousel, with round images and a product count per category
3. Building a slider in the block editor: pick the source, the layout and the navigation, with a live preview beside you
4. The Styles panel: card design, image shape, aspect ratio, image fit, alignment, shadows and hover effects
5. Global defaults under Settings, Amitry Slider, so every new slider starts the way you want it
6. A six step quick start guide and practical tips, built into the settings page, so nobody has to guess how to get going

== Changelog ==

= 4.3.0 =
* Fixed: themes that style their content links and headings could override the card typography, leaving product titles underlined, recoloured or oversized. Astra was one of them. The card rules now carry enough specificity to hold their own, without using !important, so your own customizations still work.
* New for developers: the products data source accepts a productOffset attribute, so add-ons can fetch a later batch of the same query for paging.
* New for developers: the wcsp_after_slider action renders controls directly after the slider, inside the wrapper.
* New for developers: the section head can now be filtered on with wcsp_render_section_head, and add-ons can render controls inside it with the wcsp_section_head_end action.
* New for developers: WCSP_Renderer::render_slides() renders just the slides for a set of attributes, so add-ons can reload a slider's contents without duplicating the card pipeline.

= 4.2.0 =
* New: Content Alignment option (Left, Center, Right) for the card title, price and other text. Available in the block, the Elementor widget and the shortcode with align="center".
* New: Max Width option. Caps how wide the slider can grow and centers it, which keeps single-column sliders pleasant on full width themes. Available in the block, the Elementor widget and the shortcode with max_width="1140".
* Improved: the slider can no longer grow wider than its parent container.
* Fixed: the Fade transition now also removes the gap between slides, so a single fading image fills the full width with no dead space or shift.
* Fixed: product and category images are no longer hard cropped to 4:3 on disk. The slider now uses a proportional image and applies the chosen aspect ratio with CSS, so photos of any ratio (for example 3:2 or 2:3) keep their real proportions and the "show full image" mode shows the true original. The image size can be changed with the wcsp_image_size filter.
* Fixed: in the block editor preview the slider now recalculates its size when a max width or layout applies, so the image stays centered and the navigation arrows line up with it. Enabled Swiper size observers for more robust resizing.

= 4.1.0 =
* New: Fade transition, in addition to Slide. Fade shows one item at a time, which is ideal for a single large image. Available in the block, the Elementor widget and the shortcode with effect="fade".
* New: Image Fit option. "Show full image" displays photos uncropped on a neutral backdrop, ideal for photography. Available in the block, the Elementor widget and the shortcode with fit="contain".

= 4.0.5 =
* Improved: The slider now respects the system "reduced motion" setting. When enabled, autoplay is disabled, slide transitions are instant and hover motion is removed.
* Improved: Nicer category multi-select in the block editor (roomier rows, hover and selected highlight, softer scrollbar).

= 4.0.3 =
* Improved: Expanded documentation with popular use cases and additional FAQs
* Added: Link to the live demo

= 4.0.2 =
* New: Lift + Zoom hover effect (combines the card lift with the image zoom)
* New: Support section on the settings page with review and donation links
* Changed: Refreshed plugin directory tags

= 4.0.1 =
* New: Elementor widget with full option parity to the block (block, Elementor and shortcode share one render engine)
* Changed: Lowered the minimum PHP requirement to 7.4 for wider hosting compatibility
* Improved: Documentation and minor refinements

= 4.0.0 =
* Rewritten with a cleaner, faster architecture
* New: Shine hover effect
* New: Per-device scale control (desktop, tablet, mobile)
* New: Two card styles (Clean Card, Minimal), with hooks for add-ons to add more
* New: Public hook API for add-on integrations
* New: Settings page with global defaults, shortcode generator, performance, cache and help sections
* Improved: Block editor sidebar split into Settings and Styles panels
* Improved: Only relevant options are shown in the block sidebar
* Improved: All block attributes preserved for backward compatibility with existing pages

= 3.0.2 =
* Previous stable release

== Upgrade Notice ==

= 4.3.0 =
Fixes product titles appearing underlined or oversized on themes that style content links and headings. Adds the hooks the Pro add-on needs for its category dropdown and load more button. If you use Pro, update this plugin first, then Pro. After updating, clear any caches once.

= 4.2.0 =
Fixes image cropping: photos are no longer hard cropped to 4:3 on upload and keep their real proportions. Adds Content Alignment and Max Width options. After updating, clear any caches once.

= 4.1.0 =
Adds a Fade transition option alongside Slide. After updating, clear any caches once.

= 4.0.5 =
Accessibility improvement: the slider now respects the system reduced-motion setting. After updating, clear any caches once.

= 4.0.3 =
Documentation update with use cases, more FAQs and a live demo link. No functional changes.

= 4.0.2 =
Adds a support section on the settings page and refreshed directory tags.

= 4.0.1 =
Adds an Elementor widget and lowers the minimum required PHP version to 7.4. After updating, clear any caches and hard-refresh once.

= 4.0.0 =
Faster rewrite. Existing blocks and shortcodes keep working, since all attributes are preserved. After updating, clear any page or fragment caches and hard-refresh once.

/**
 * Editor component for the Amitry Slider block.
 *
 * Architecture:
 *  - Visual changes (style variant, image shape, aspect, shadow, hover,
 *    colors, element toggles, layout) update INSTANTLY via:
 *      (a) CSS classes on a React-rendered .wcsp-editor-outer wrapper
 *      (b) CSS custom properties via a per-instance <style> tag
 *  - Only data changes (slider type, filter, count, sort, section text)
 *    trigger ServerSideRender.
 *  - The SSR'd .wcsp-outer is wrapped by our editor-outer so our classes
 *    win via specificity (.wcsp-editor-outer .wcsp-card etc).
 */

import { __, sprintf } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	BlockControls,
	PanelColorSettings,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	RangeControl,
	TextControl,
	TextareaControl,
	ToolbarGroup,
	ToolbarDropdownMenu,
	Notice,
	Dropdown,
	CheckboxControl,
	Button,
	Spinner,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import ServerSideRender from '@wordpress/server-side-render';
import { useState, useMemo, useRef, useEffect } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';

const PRO_URL = 'https://amitry.de/amitry-product-category-slider/';

/* Inline SVG icons */
const productIcon = (
	<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
		<path d="M20.5 7.27 12 12 3.5 7.27"/>
		<path d="M12 22V12"/>
		<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
	</svg>
);

const categoryIcon = (
	<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
		<rect x="3" y="3" width="7" height="7" rx="1"/>
		<rect x="14" y="3" width="7" height="7" rx="1"/>
		<rect x="3" y="14" width="7" height="7" rx="1"/>
		<rect x="14" y="14" width="7" height="7" rx="1"/>
	</svg>
);

/* Only these attributes trigger server-side re-render. Everything else
   is handled by instant CSS overrides in the editor wrapper.

   Behavior + layout attrs MUST be in here so the editor SSR output
   has the correct data-wcsp-* attributes, which the editor's Swiper
   instance reads to configure itself (loop, autoplay, etc.). */
const DATA_ATTRS = [
	'sliderType',
	'productFilter',
	'productCount',
	'selectedProducts',
	'selectedCategories',
	'excludeCategories',
	'hideEmpty',
	'maxCategories',
	'categorySortBy',
	// Layout (drives Swiper slidesPerView + gap).
	'slidesPerViewDesktop', 'slidesPerViewTablet', 'slidesPerViewMobile',
	'spaceBetween',
	'spaceBetweenTablet', 'spaceBetweenMobile',
	'cardPaddingTablet', 'cardPaddingMobile',
	'arrowSizeTablet', 'arrowSizeMobile',
	'showArrowsTablet', 'showArrowsMobile',
	'showPaginationDotsTablet', 'showPaginationDotsMobile',
	// Behavior (drives Swiper autoplay, loop, speed, touch, drag, keyboard).
	'autoplay', 'autoplayDelay', 'loop', 'pauseOnHover', 'speed', 'transitionEffect',
	'touchEnabled', 'mouseDrag', 'keyboardEnabled',
	// Section + navigation visibility.
	'sectionTitle',
	'sectionTitleSize', 'sectionTitleWeight', 'sectionTitleColor', 'sectionTitleAlign',
	'sectionSubtitle',
	'sectionSubtitleSize', 'sectionSubtitleWeight', 'sectionSubtitleColor', 'sectionSubtitleAlign',
	'contentAlign',
	'showViewAllButton', 'viewAllUrl', 'viewAllText',
	'viewAllPosition', 'viewAllAlign', 'viewAllBgColor', 'viewAllTextColor',
	'viewAllRadius', 'viewAllPadding', 'viewAllIcon',
	'showPaginationDots', 'showScrollbar', 'showProgress', 'showCounter',
];

function ProBanner( { onDismiss } ) {
	return (
		<div className="wcsp-pro-banner">
			<div className="wcsp-pro-banner__text">
				<strong>{ __( 'Discover Pro', 'amitry-product-category-slider' ) }</strong>
				<span>{ __( 'More designs, Quick-View, AJAX-Cart', 'amitry-product-category-slider' ) }</span>
			</div>
			<div className="wcsp-pro-banner__actions">
				<a href={ PRO_URL } target="_blank" rel="noreferrer noopener" className="wcsp-pro-banner__link">
					{ __( 'Learn more', 'amitry-product-category-slider' ) } &rarr;
				</a>
				<button
					type="button"
					className="wcsp-pro-banner__close"
					onClick={ onDismiss }
					aria-label={ __( 'Close banner', 'amitry-product-category-slider' ) }
				>
					&times;
				</button>
			</div>
		</div>
	);
}

/**
 * Per-device value resolver for NUMERIC settings.
 * Tablet/Mobile store -1 to mean "inherit from Desktop". Mobile additionally
 * inherits from Tablet when Mobile was not explicitly set.
 *
 * @param {Object} atts   All block attributes.
 * @param {string} base   Desktop attribute key (e.g. 'cardPadding').
 * @param {string} device Lowercased device key: 'desktop' | 'tablet' | 'mobile'.
 * @returns {number} Resolved value.
 */
function resolveNumeric( atts, base, device ) {
	const desktop = atts[ base ];
	const tabKey  = base + 'Tablet';
	const mobKey  = base + 'Mobile';
	const tablet  = atts[ tabKey ];
	const mobile  = atts[ mobKey ];
	if ( device === 'mobile' ) {
		if ( typeof mobile === 'number' && mobile >= 0 ) return mobile;
		if ( typeof tablet === 'number' && tablet >= 0 ) return tablet;
		return desktop;
	}
	if ( device === 'tablet' ) {
		if ( typeof tablet === 'number' && tablet >= 0 ) return tablet;
		return desktop;
	}
	return desktop;
}

/**
 * Per-device value resolver for BOOLEAN settings.
 * Tablet/Mobile store the string 'inherit' to fall back to Desktop, otherwise
 * 'true' or 'false'.
 *
 * @param {Object} atts   All block attributes.
 * @param {string} base   Desktop attribute key (e.g. 'showArrows').
 * @param {string} device 'desktop' | 'tablet' | 'mobile'.
 * @returns {boolean} Resolved boolean.
 */
function resolveBoolean( atts, base, device ) {
	const desktop = !! atts[ base ];
	const tablet  = atts[ base + 'Tablet' ];
	const mobile  = atts[ base + 'Mobile' ];
	if ( device === 'mobile' ) {
		if ( mobile === 'true' )  return true;
		if ( mobile === 'false' ) return false;
		if ( tablet === 'true' )  return true;
		if ( tablet === 'false' ) return false;
		return desktop;
	}
	if ( device === 'tablet' ) {
		if ( tablet === 'true' )  return true;
		if ( tablet === 'false' ) return false;
		return desktop;
	}
	return desktop;
}


function pickDataAttrs( atts ) {
	const out = {};
	for ( const key of DATA_ATTRS ) {
		if ( key in atts ) {
			out[ key ] = atts[ key ];
		}
	}
	return out;
}

/**
 * Per-device tab bar.
 *
 * Shows three pills (Desktop / Tablet / Mobile). The active one matches
 * the WordPress editor's current preview device. Clicking a different
 * pill changes the WordPress preview (which in turn re-renders the
 * editor canvas and our slider).
 *
 * Used inside any panel that holds per-device settings (e.g. Layout)
 * so the user always knows which device's values they're editing.
 */
function DeviceTabBar( { deviceType, onSwitch } ) {
	const devices = [
		{ key: 'Desktop', label: __( 'Desktop', 'amitry-product-category-slider' ) },
		{ key: 'Tablet',  label: __( 'Tablet',  'amitry-product-category-slider' ) },
		{ key: 'Mobile',  label: __( 'Mobile',   'amitry-product-category-slider' ) },
	];
	return (
		<div className="wcsp-device-tabs" style={ {
			display: 'flex',
			gap: '4px',
			padding: '4px',
			marginBottom: '16px',
			background: '#f0f0f0',
			borderRadius: '6px',
		} }>
			{ devices.map( ( d ) => {
				const isActive = deviceType === d.key;
				return (
					<button
						key={ d.key }
						type="button"
						onClick={ () => onSwitch( d.key ) }
						style={ {
							flex: 1,
							padding: '6px 10px',
							background: isActive ? '#ffffff' : 'transparent',
							color: isActive ? '#1e1e1e' : '#6b7280',
							border: 0,
							borderRadius: '4px',
							fontSize: '12px',
							fontWeight: isActive ? 600 : 400,
							cursor: 'pointer',
							boxShadow: isActive ? '0 1px 2px rgba(0,0,0,0.08)' : 'none',
							transition: 'all 0.15s ease',
						} }
					>
						{ d.label }
					</button>
				);
			} ) }
		</div>
	);
}

/**
 * Multi-select dropdown for WooCommerce product categories.
 *
 * Renders a clickable button (showing count of selected) that opens a
 * popover with a scrollable checkbox list. No typing needed - user just
 * clicks the categories they want.
 */
function CategoryDropdownPicker( { label, help, value, onChange } ) {
	const { categories, isLoading } = useSelect( ( select ) => {
		const core = select( 'core' );
		const query = { taxonomy: 'product_cat', per_page: 100, _fields: 'id,name,count' };
		const terms = core.getEntityRecords( 'taxonomy', 'product_cat', query );
		return {
			categories: terms || [],
			isLoading: ! core.hasFinishedResolution( 'getEntityRecords', [ 'taxonomy', 'product_cat', query ] ),
		};
	}, [] );

	const selectedIds = Array.isArray( value ) ? value : [];

	const toggleCategory = ( catId ) => {
		const id = parseInt( catId, 10 );
		if ( selectedIds.includes( id ) ) {
			onChange( selectedIds.filter( ( x ) => x !== id ) );
		} else {
			onChange( [ ...selectedIds, id ] );
		}
	};

	const summaryLabel = () => {
		if ( selectedIds.length === 0 ) {
			return __( 'All Categories', 'amitry-product-category-slider' );
		}
		if ( selectedIds.length === 1 ) {
			const cat = categories.find( ( c ) => c.id === selectedIds[ 0 ] );
			return cat ? cat.name : __( '1 selected', 'amitry-product-category-slider' );
		}
		/* translators: %d: number of selected categories */
		return sprintf( __( '%d categories selected', 'amitry-product-category-slider' ), selectedIds.length );
	};

	if ( isLoading ) {
		return (
			<div style={ { padding: '8px 0', display: 'flex', alignItems: 'center', gap: '8px' } }>
				<Spinner />
				<span style={ { fontSize: '12px', color: '#757575' } }>
					{ __( 'Loading categories...', 'amitry-product-category-slider' ) }
				</span>
			</div>
		);
	}

	return (
		<div style={ { marginBottom: '16px' } }>
			{ label && (
				<label style={ { display: 'block', fontSize: '11px', fontWeight: 500, textTransform: 'uppercase', marginBottom: '8px', color: '#1e1e1e' } }>
					{ label }
				</label>
			) }
			<Dropdown
				className="wcsp-cat-dropdown"
				contentClassName="wcsp-cat-dropdown__content"
				popoverProps={ { placement: 'bottom-start' } }
				renderToggle={ ( { isOpen, onToggle } ) => (
					<Button
						variant="secondary"
						onClick={ onToggle }
						aria-expanded={ isOpen }
						style={ { width: '100%', justifyContent: 'space-between', display: 'flex' } }
					>
						<span>{ summaryLabel() }</span>
						<span style={ { marginLeft: '6px' } }>{ isOpen ? '▲' : '▼' }</span>
					</Button>
				) }
				renderContent={ () => (
					<div style={ { padding: '8px 12px', maxHeight: '300px', overflowY: 'auto', minWidth: '220px' } }>
						{ categories.length === 0 && (
							<p style={ { margin: 0, fontSize: '12px', color: '#757575' } }>
								{ __( 'No categories available', 'amitry-product-category-slider' ) }
							</p>
						) }
						{ categories.map( ( cat ) => (
							<CheckboxControl
								key={ cat.id }
								label={ `${ cat.name } (${ cat.count || 0 })` }
								checked={ selectedIds.includes( cat.id ) }
								onChange={ () => toggleCategory( cat.id ) }
								__nextHasNoMarginBottom
							/>
						) ) }
					</div>
				) }
			/>
			{ help && (
				<p style={ { fontSize: '12px', color: '#757575', marginTop: '6px', lineHeight: 1.4 } }>
					{ help }
				</p>
			) }
		</div>
	);
}

/* Compute CSS custom properties from attributes - mirrors PHP renderer. */
function buildCssVars( atts ) {
	const vars = {};
	if ( atts.cardRadius !== undefined )           vars[ '--wcsp-radius' ]       = atts.cardRadius + 'px';
	if ( atts.cardBackgroundColor )                 vars[ '--wcsp-card-bg' ]     = atts.cardBackgroundColor;
	if ( atts.cardPadding !== undefined ) {
		vars[ '--wcsp-card-pad' ]     = atts.cardPadding + 'px';
		vars[ '--wcsp-card-pad-tab' ] = resolveNumeric( atts, 'cardPadding', 'tablet' ) + 'px';
		vars[ '--wcsp-card-pad-mob' ] = resolveNumeric( atts, 'cardPadding', 'mobile' ) + 'px';
	}
	if ( atts.maxWidth !== undefined && atts.maxWidth > 0 ) vars[ '--wcsp-max-w' ] = atts.maxWidth + 'px';
	if ( atts.arrowColor )                          vars[ '--wcsp-arrow-color' ] = atts.arrowColor;
	if ( atts.arrowBgColor )                        vars[ '--wcsp-arrow-bg' ]    = atts.arrowBgColor;
	if ( atts.arrowSize !== undefined ) {
		vars[ '--wcsp-arrow-size' ]     = atts.arrowSize + 'px';
		vars[ '--wcsp-arrow-size-tab' ] = resolveNumeric( atts, 'arrowSize', 'tablet' ) + 'px';
		vars[ '--wcsp-arrow-size-mob' ] = resolveNumeric( atts, 'arrowSize', 'mobile' ) + 'px';
	}
	if ( atts.spaceBetween !== undefined ) {
		vars[ '--wcsp-gap' ] = atts.spaceBetween + 'px';
		// Resolve tablet/mobile with fallback to desktop.
		const tab = ( atts.spaceBetweenTablet !== undefined && atts.spaceBetweenTablet >= 0 )
			? atts.spaceBetweenTablet
			: atts.spaceBetween;
		const mob = ( atts.spaceBetweenMobile !== undefined && atts.spaceBetweenMobile >= 0 )
			? atts.spaceBetweenMobile
			: ( ( atts.spaceBetweenTablet !== undefined && atts.spaceBetweenTablet >= 0 ) ? atts.spaceBetweenTablet : atts.spaceBetween );
		vars[ '--wcsp-gap-tab' ] = tab + 'px';
		vars[ '--wcsp-gap-mob' ] = mob + 'px';
	}
	if ( atts.slidesPerViewDesktop !== undefined )  vars[ '--wcsp-cols-dsk' ]    = atts.slidesPerViewDesktop;
	if ( atts.slidesPerViewTablet !== undefined )   vars[ '--wcsp-cols-tab' ]    = atts.slidesPerViewTablet;
	if ( atts.slidesPerViewMobile !== undefined )   vars[ '--wcsp-cols-mob' ]    = atts.slidesPerViewMobile;
	if ( atts.scaleDesktop !== undefined )          vars[ '--wcsp-scale-dsk' ]   = ( atts.scaleDesktop / 100 );
	if ( atts.scaleTablet !== undefined )           vars[ '--wcsp-scale-tab' ]   = ( atts.scaleTablet / 100 );
	if ( atts.scaleMobile !== undefined )           vars[ '--wcsp-scale-mob' ]   = ( atts.scaleMobile / 100 );
	if ( atts.titleColor )                          vars[ '--wcsp-title-color' ] = atts.titleColor;
	if ( atts.priceColor )                          vars[ '--wcsp-price-color' ] = atts.priceColor;

	// Section title/subtitle typography vars.
	if ( atts.sectionTitleSize !== undefined )      vars[ '--wcsp-section-title-size' ]    = atts.sectionTitleSize + 'px';
	if ( atts.sectionTitleWeight )                  vars[ '--wcsp-section-title-weight' ]  = atts.sectionTitleWeight;
	if ( atts.sectionTitleColor )                   vars[ '--wcsp-section-title-color' ]   = atts.sectionTitleColor;
	if ( atts.sectionSubtitleSize !== undefined )   vars[ '--wcsp-section-sub-size' ]      = atts.sectionSubtitleSize + 'px';
	if ( atts.sectionSubtitleWeight )               vars[ '--wcsp-section-sub-weight' ]    = atts.sectionSubtitleWeight;
	if ( atts.sectionSubtitleColor )                vars[ '--wcsp-section-sub-color' ]     = atts.sectionSubtitleColor;

	// View All button customization.
	if ( atts.viewAllBgColor )                      vars[ '--wcsp-viewall-bg' ]    = atts.viewAllBgColor;
	if ( atts.viewAllTextColor )                    vars[ '--wcsp-viewall-color' ] = atts.viewAllTextColor;
	if ( atts.viewAllRadius !== undefined )         vars[ '--wcsp-viewall-radius' ] = atts.viewAllRadius + 'px';
	if ( atts.viewAllPadding !== undefined )        vars[ '--wcsp-viewall-pad' ]   = atts.viewAllPadding + 'px';
	return vars;
}

/* Compute visual classes from attributes - mirrors PHP renderer's
   outer_classes() method exactly. Uses .wcsp-outer so it shares CSS
   with the frontend renderer. */
function buildOuterClasses( atts ) {
	const c = [ 'wcsp-outer' ];

	c.push( 'wcsp-style-' + ( atts.styleVariant || 'clean-card' ) );
	c.push( 'wcsp-shadow-' + ( atts.shadowIntensity || 'soft' ) );
	c.push( 'wcsp-hover-' + ( atts.hoverEffect || 'lift' ) );
	c.push( 'wcsp-shape-' + ( atts.imageShape || 'rounded' ) );
	c.push( 'wcsp-aspect-' + ( atts.aspectRatio || '4-3' ) );
	c.push( 'wcsp-fit-' + ( atts.imageFit || 'cover' ) );
	c.push( 'wcsp-dots-' + ( atts.dotsShape || 'round' ) );
	c.push( 'wcsp-title-align-' + ( atts.sectionTitleAlign || 'left' ) );
	c.push( 'wcsp-sub-align-' + ( atts.sectionSubtitleAlign || 'left' ) );
	c.push( 'wcsp-content-align-' + ( atts.contentAlign || 'left' ) );
	c.push( 'wcsp-viewall-pos-' + ( atts.viewAllPosition || 'below' ) );
	c.push( 'wcsp-viewall-align-' + ( atts.viewAllAlign || 'right' ) );
	c.push( 'wcsp-viewall-icon-' + ( atts.viewAllIcon || 'none' ) );

	// Arrows: per-device hide classes. Frontend.js + CSS hide them at
	// the matching breakpoint. Desktop-only class hides on Desktop-width
	// only; we use the same approach as for the other media-query toggles.
	if ( ! resolveBoolean( atts, 'showArrows', 'desktop' ) ) c.push( 'wcsp-hide-arrows-dsk' );
	if ( ! resolveBoolean( atts, 'showArrows', 'tablet'  ) ) c.push( 'wcsp-hide-arrows-tab' );
	if ( ! resolveBoolean( atts, 'showArrows', 'mobile'  ) ) c.push( 'wcsp-hide-arrows-mob' );

	// Pagination dots: same per-device pattern.
	if ( ! resolveBoolean( atts, 'showPaginationDots', 'desktop' ) ) c.push( 'wcsp-hide-dots-dsk' );
	if ( ! resolveBoolean( atts, 'showPaginationDots', 'tablet'  ) ) c.push( 'wcsp-hide-dots-tab' );
	if ( ! resolveBoolean( atts, 'showPaginationDots', 'mobile'  ) ) c.push( 'wcsp-hide-dots-mob' );

	if ( atts.showOverlayGradient )  c.push( 'wcsp-overlay' );

	if ( ! atts.showImage )      c.push( 'wcsp-hide-image' );
	if ( ! atts.showTitle )      c.push( 'wcsp-hide-title' );
	if ( ! atts.showPrice )      c.push( 'wcsp-hide-price' );
	if ( ! atts.showRating )     c.push( 'wcsp-hide-rating' );
	if ( ! atts.showExcerpt )    c.push( 'wcsp-hide-excerpt' );
	if ( ! atts.showAddToCart )  c.push( 'wcsp-hide-cart' );
	if ( ! atts.showStock )      c.push( 'wcsp-hide-stock' );
	if ( ! atts.showSaleBadge )  c.push( 'wcsp-hide-sale-badge' );
	if ( ! atts.showCount )      c.push( 'wcsp-hide-count' );
	if ( ! atts.showDescription ) c.push( 'wcsp-hide-excerpt' );

	/**
	 * Filter the outer wrapper classes in the editor.
	 *
	 * Mirrors the PHP `wcsp_outer_classes` filter so add-ons can add the
	 * same scoping classes in the editor that they add on the front end.
	 * The editor builds the outer wrapper in React (not via SSR), so a
	 * PHP-only filter wouldn't reach it.
	 *
	 * @param {string[]} classes Array of class names.
	 * @param {Object}   atts    All block attributes.
	 */
	const filtered = applyFilters( 'wcsp.editorOuterClasses', c, atts );

	return ( Array.isArray( filtered ) ? filtered : c ).join( ' ' );
}

export default function Edit( { attributes, setAttributes, clientId } ) {
	const blockProps = useBlockProps( {
		className: 'wcsp-editor-wrapper',
	} );

	// Hide the Pro promo banner if the Pro add-on is active. Pro sets
	// window.wcspProActive = true from its editor bundle.
	const proActive = typeof window !== 'undefined' && !! window.wcspProActive;
	const [ bannerVisible, setBannerVisible ] = useState( ! proActive );

	const dataAttrs = useMemo(
		() => {
			const base = pickDataAttrs( attributes );
			/**
			 * Filter the attributes sent to ServerSideRender in the editor.
			 *
			 * Add-ons (e.g. Pro) use this to inject their own attributes so
			 * their server-side rendering shows up in the editor preview,
			 * not just on the front end. The full attribute object is
			 * passed as the second arg so add-ons can read their values.
			 *
			 * @param {Object} base       The whitelisted Free attributes.
			 * @param {Object} attributes All block attributes.
			 */
			return applyFilters( 'wcsp.editorDataAttrs', base, attributes );
		},
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[ attributes ]
	);

	const cssVars = buildCssVars( attributes );
	const outerClasses = buildOuterClasses( attributes );
	const instanceClass = 'wcsp-instance-' + ( clientId || '0' ).replace( /[^a-z0-9]/gi, '' );

	const styleRules = useMemo( () => {
		const decls = Object.entries( cssVars )
			.map( ( [ k, v ] ) => `${ k }: ${ v } !important;` )
			.join( ' ' );
		// Apply vars to our editor wrapper - cascades down to the SSR'd
		// .wcsp-outer and everything inside.
		return `.${ instanceClass }.wcsp-outer { ${ decls } }`;
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, Object.values( cssVars ).concat( instanceClass ) );

	// Click delegation for the editor preview:
	// 1. Arrow clicks scroll the slider horizontally (no Swiper in editor).
	// 2. Card/link clicks are prevented so they don't navigate away.
	// :hover keeps working because we don't disable pointer-events.
	const wrapperRef = useRef( null );

	/**
	 * Read the current preview device type from the WordPress editor store.
	 * Returns 'Desktop' | 'Tablet' | 'Mobile'. Falls back to 'Desktop' if
	 * the editor store isn't available.
	 *
	 * Swiper in the editor lives in the parent window, so its breakpoints
	 * (which read window.innerWidth) always see the full browser width.
	 * We bypass that by forcing slidesPerView explicitly per device, based
	 * on what the editor toolbar is currently previewing.
	 */
	const deviceType = useSelect( ( select ) => {
		// Try the Site Editor store first (FSE themes / Site Editor),
		// then fall back to the Post Editor store, then to Desktop.
		const tryStores = [ 'core/edit-site', 'core/edit-post' ];
		for ( const storeName of tryStores ) {
			const store = select( storeName );
			if ( store && typeof store.__experimentalGetPreviewDeviceType === 'function' ) {
				const value = store.__experimentalGetPreviewDeviceType();
				if ( value ) {
					return value;
				}
			}
		}
		return 'Desktop';
	}, [] );

	// Dispatchers for switching the editor preview device. Hooks must be
	// called at top level, so we acquire both up-front and use whichever
	// one actually provides the action.
	const siteDispatch = useDispatch( 'core/edit-site' );
	const postDispatch = useDispatch( 'core/edit-post' );
	const setPreviewDevice = ( device ) => {
		if ( siteDispatch && typeof siteDispatch.__experimentalSetPreviewDeviceType === 'function' ) {
			siteDispatch.__experimentalSetPreviewDeviceType( device );
			return;
		}
		if ( postDispatch && typeof postDispatch.__experimentalSetPreviewDeviceType === 'function' ) {
			postDispatch.__experimentalSetPreviewDeviceType( device );
		}
	};

	/**
	 * Initialize Swiper inside the editor preview after each render.
	 * The ServerSideRender component re-renders the HTML on every
	 * attribute change, so we need to:
	 *   1) destroy any previously-initialised slider
	 *   2) tag the new wrapper with data-wcsp-context="editor" so
	 *      autoplay stays off and slidesPerView/spaceBetween are forced
	 *      from explicit data attributes matching the preview device
	 *   3) call window.wcsp.initSlider() to instantiate Swiper
	 *
	 * A MutationObserver watches for the SSR-rendered .wcsp-slider
	 * element appearing or being swapped out.
	 */
	useEffect( () => {
		const root = wrapperRef.current;
		if ( ! root ) {
			return undefined;
		}

		let currentSlider = null;

		const reinit = () => {
			const slider = root.querySelector( '.wcsp-slider' );
			if ( ! slider ) {
				return;
			}
			// If slider element is the same and we already initialised it,
			// but the device type changed, we still need to re-init.
			const sameSlider = slider === currentSlider;
			const sameDevice = slider.dataset.wcspEditorDevice === deviceType;
			if ( sameSlider && sameDevice ) {
				return;
			}
			// Destroy old instance if still around (either swap of HTML
			// OR device change).
			if ( currentSlider && window.wcsp && window.wcsp.destroySlider ) {
				window.wcsp.destroySlider( currentSlider );
			}
			currentSlider = slider;
			// Mark as editor context so autoplay stays off.
			slider.dataset.wcspContext = 'editor';
			// Tell frontend.js which device mode the editor is previewing.
			slider.dataset.wcspEditorDevice = deviceType;
			// Wait for Swiper to be ready and initialise.
			if ( window.wcsp && window.wcsp.initSlider ) {
				window.wcsp.initSlider( slider );
			}
		};

		// Initial init in case SSR already rendered before observer attached.
		reinit();

		// Re-init whenever SSR replaces the slider HTML.
		const observer = new MutationObserver( reinit );
		observer.observe( root, { childList: true, subtree: true } );

		// Suppress link navigation inside the preview, but allow block selection.
		const clickGuard = ( e ) => {
			// Pfeil-Clicks DURCHLASSEN - Swiper handhabt sie selbst.
			if ( e.target.closest( '.wcsp-btn' ) ) {
				return;
			}
			// Dot/Scrollbar Clicks ebenfalls durchlassen.
			if ( e.target.closest( '.wcsp-dots, .wcsp-scrollbar' ) ) {
				return;
			}
			const link = e.target.closest( 'a' );
			if ( link && root.contains( link ) ) {
				e.preventDefault();
			}
		};
		root.addEventListener( 'click', clickGuard, true );

		return () => {
			observer.disconnect();
			root.removeEventListener( 'click', clickGuard, true );
			if ( currentSlider && window.wcsp && window.wcsp.destroySlider ) {
				window.wcsp.destroySlider( currentSlider );
			}
		};
	}, [ deviceType ] );

	const {
		sliderType,
		productFilter,
		productCount,
		maxCategories,
		categorySortBy,
		hideEmpty,
		excludeCategories,
		slidesPerViewDesktop,
		slidesPerViewTablet,
		slidesPerViewMobile,
		scaleDesktop,
		scaleTablet,
		scaleMobile,
		spaceBetween,
		spaceBetweenTablet,
		spaceBetweenMobile,
		autoplay,
		autoplayDelay,
		loop,
		pauseOnHover,
		speed,
		transitionEffect,
		touchEnabled,
		mouseDrag,
		showArrows,
		showPaginationDots,
		showScrollbar,
		showProgress,
		showCounter,
		keyboardEnabled,
		showImage,
		showTitle,
		showPrice,
		showRating,
		showExcerpt,
		showAddToCart,
		showStock,
		showSaleBadge,
		contentAlign,
		maxWidth,
		showCount,
		showDescription,
		styleVariant,
		imageShape,
		aspectRatio,
		imageFit,
		cardRadius,
		cardPadding,
		cardBackgroundColor,
		shadowIntensity,
		hoverEffect,
		showOverlayGradient,
		titleColor,
		priceColor,
		arrowColor,
		arrowBgColor,
		arrowSize,
		dotsShape,
		sectionTitle,
		sectionTitleSize,
		sectionTitleWeight,
		sectionTitleColor,
		sectionTitleAlign,
		sectionSubtitle,
		sectionSubtitleSize,
		sectionSubtitleWeight,
		sectionSubtitleColor,
		sectionSubtitleAlign,
		showViewAllButton,
		viewAllUrl,
		viewAllText,
		viewAllPosition,
		viewAllAlign,
		viewAllBgColor,
		viewAllTextColor,
		viewAllRadius,
		viewAllPadding,
		viewAllIcon,
	} = attributes;

	const isProducts   = sliderType === 'products';
	const isCategories = sliderType === 'categories';

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarDropdownMenu
						icon={ isProducts ? productIcon : categoryIcon }
						label={ __( 'Slider Type', 'amitry-product-category-slider' ) }
						controls={ [
							{
								title: __( 'Products', 'amitry-product-category-slider' ),
								icon: productIcon,
								isActive: isProducts,
								onClick: () => setAttributes( { sliderType: 'products' } ),
							},
							{
								title: __( 'Categories', 'amitry-product-category-slider' ),
								icon: categoryIcon,
								isActive: isCategories,
								onClick: () => setAttributes( { sliderType: 'categories' } ),
							},
						] }
					/>
				</ToolbarGroup>
			</BlockControls>

			{ /* ─── GEAR TAB ───────────────────────────────────────── */ }
			<InspectorControls>
				{ ! proActive && bannerVisible && (
					<ProBanner onDismiss={ () => setBannerVisible( false ) } />
				) }

				<PanelBody title={ __( 'Data Source', 'amitry-product-category-slider' ) } initialOpen={ true }>
					<SelectControl
						label={ __( 'Show', 'amitry-product-category-slider' ) }
						value={ sliderType }
						options={ [
							{ label: __( 'Products', 'amitry-product-category-slider' ),    value: 'products' },
							{ label: __( 'Categories', 'amitry-product-category-slider' ), value: 'categories' },
						] }
						onChange={ ( v ) => setAttributes( { sliderType: v } ) }
						__nextHasNoMarginBottom
					/>

					{ isProducts && (
						<>
							<SelectControl
								label={ __( 'Filter', 'amitry-product-category-slider' ) }
								value={ productFilter }
								options={ [
									{ label: __( 'Newest',         'amitry-product-category-slider' ), value: 'newest' },
									{ label: __( 'Best Selling',   'amitry-product-category-slider' ), value: 'bestselling' },
									{ label: __( 'On Sale',      'amitry-product-category-slider' ), value: 'on_sale' },
									{ label: __( 'Featured',   'amitry-product-category-slider' ), value: 'featured' },
									{ label: __( 'Top Rated',   'amitry-product-category-slider' ), value: 'top_rated' },
									{ label: __( 'Manual Selection','amitry-product-category-slider' ), value: 'manual' },
									{ label: __( 'From Category',   'amitry-product-category-slider' ), value: 'by_category' },
								] }
								onChange={ ( v ) => setAttributes( { productFilter: v } ) }
								__nextHasNoMarginBottom
							/>
							{ productFilter !== 'manual' && (
								<RangeControl
									label={ __( 'Number of Products', 'amitry-product-category-slider' ) }
									value={ productCount }
									onChange={ ( v ) => setAttributes( { productCount: v } ) }
									min={ 1 } max={ 48 }
									__nextHasNoMarginBottom __next40pxDefaultSize
								/>
							) }
							{ productFilter === 'manual' && (
								<Notice status="info" isDismissible={ false }>
									{ __( 'Manual product selection coming in the next version.', 'amitry-product-category-slider' ) }
								</Notice>
							) }
							{ productFilter === 'by_category' && (
								<CategoryDropdownPicker
									label={ __( 'From These Categories', 'amitry-product-category-slider' ) }
									help={ __( 'Type to search and select. Empty = all.', 'amitry-product-category-slider' ) }
									value={ attributes.selectedCategories || [] }
									onChange={ ( ids ) => setAttributes( { selectedCategories: ids } ) }
								/>
							) }
						</>
					) }

					{ isCategories && (
						<>
							<RangeControl
								label={ __( 'Max Categories', 'amitry-product-category-slider' ) }
								value={ maxCategories }
								onChange={ ( v ) => setAttributes( { maxCategories: v } ) }
								min={ 1 } max={ 48 }
								__nextHasNoMarginBottom __next40pxDefaultSize
							/>
							<SelectControl
								label={ __( 'Sort Order', 'amitry-product-category-slider' ) }
								value={ categorySortBy }
								options={ [
									{ label: __( 'Name (A-Z)',     'amitry-product-category-slider' ), value: 'name' },
									{ label: __( 'Product Count', 'amitry-product-category-slider' ), value: 'count' },
									{ label: __( 'Manual (Menu Order)', 'amitry-product-category-slider' ), value: 'menu_order' },
								] }
								onChange={ ( v ) => setAttributes( { categorySortBy: v } ) }
								__nextHasNoMarginBottom
							/>
							<ToggleControl
								label={ __( 'Hide Empty Categories', 'amitry-product-category-slider' ) }
								checked={ !! hideEmpty }
								onChange={ ( v ) => setAttributes( { hideEmpty: v } ) }
								__nextHasNoMarginBottom
							/>
							<CategoryDropdownPicker
								label={ __( 'Exclude Categories', 'amitry-product-category-slider' ) }
								help={ __( 'Type to search and select.', 'amitry-product-category-slider' ) }
								value={ excludeCategories || [] }
								onChange={ ( ids ) => setAttributes( { excludeCategories: ids } ) }
							/>
						</>
					) }
				</PanelBody>

				<PanelBody title={ __( 'Layout', 'amitry-product-category-slider' ) } initialOpen={ false }>
					<DeviceTabBar deviceType={ deviceType } onSwitch={ setPreviewDevice } />

					<RangeControl
						label={ __( 'Slides Per View', 'amitry-product-category-slider' ) }
						value={
							deviceType === 'Mobile'  ? slidesPerViewMobile  :
							deviceType === 'Tablet'  ? slidesPerViewTablet  :
							slidesPerViewDesktop
						}
						onChange={ ( v ) => {
							if ( deviceType === 'Mobile' )      setAttributes( { slidesPerViewMobile:  v } );
							else if ( deviceType === 'Tablet' ) setAttributes( { slidesPerViewTablet:  v } );
							else                                setAttributes( { slidesPerViewDesktop: v } );
						} }
						min={ 1 }
						max={ deviceType === 'Mobile' ? 3 : deviceType === 'Tablet' ? 6 : 8 }
						__nextHasNoMarginBottom __next40pxDefaultSize
					/>

					<RangeControl
						label={ __( 'Space Between Slides (px)', 'amitry-product-category-slider' ) }
						value={ (() => {
							if ( deviceType === 'Mobile' ) {
								// Mobile inherits from Tablet (which inherits from Desktop)
								// when not explicitly set.
								if ( spaceBetweenMobile >= 0 ) return spaceBetweenMobile;
								if ( spaceBetweenTablet >= 0 ) return spaceBetweenTablet;
								return spaceBetween;
							}
							if ( deviceType === 'Tablet' ) {
								if ( spaceBetweenTablet >= 0 ) return spaceBetweenTablet;
								return spaceBetween;
							}
							return spaceBetween;
						})() }
						onChange={ ( v ) => {
							if ( deviceType === 'Mobile' )      setAttributes( { spaceBetweenMobile: v } );
							else if ( deviceType === 'Tablet' ) setAttributes( { spaceBetweenTablet: v } );
							else                                setAttributes( { spaceBetween: v } );
						} }
						min={ 0 } max={ 80 }
						__nextHasNoMarginBottom __next40pxDefaultSize
					/>

					<RangeControl
						label={ __( 'Card Scale (%)', 'amitry-product-category-slider' ) }
						value={
							deviceType === 'Mobile' ? scaleMobile :
							deviceType === 'Tablet' ? scaleTablet :
							scaleDesktop
						}
						onChange={ ( v ) => {
							if ( deviceType === 'Mobile' )      setAttributes( { scaleMobile:  v } );
							else if ( deviceType === 'Tablet' ) setAttributes( { scaleTablet:  v } );
							else                                setAttributes( { scaleDesktop: v } );
						} }
						min={ 50 } max={ 100 } step={ 5 }
						__nextHasNoMarginBottom __next40pxDefaultSize
						help={ __( '100% = default. Scales the entire card proportionally.', 'amitry-product-category-slider' ) }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Behavior', 'amitry-product-category-slider' ) } initialOpen={ false }>
					<ToggleControl
						label={ __( 'Autoplay', 'amitry-product-category-slider' ) }
						checked={ !! autoplay }
						onChange={ ( v ) => setAttributes( { autoplay: v } ) }
						__nextHasNoMarginBottom
					/>
					{ autoplay && (
						<>
							<RangeControl
								label={ __( 'Autoplay Delay (ms)', 'amitry-product-category-slider' ) }
								value={ autoplayDelay }
								onChange={ ( v ) => setAttributes( { autoplayDelay: v } ) }
								min={ 1000 } max={ 10000 } step={ 250 }
								__nextHasNoMarginBottom __next40pxDefaultSize
							/>
							<ToggleControl
								label={ __( 'Pause on Hover', 'amitry-product-category-slider' ) }
								checked={ !! pauseOnHover }
								onChange={ ( v ) => setAttributes( { pauseOnHover: v } ) }
								__nextHasNoMarginBottom
							/>
						</>
					) }
					<ToggleControl
						label={ __( 'Infinite Loop', 'amitry-product-category-slider' ) }
						checked={ !! loop }
						onChange={ ( v ) => setAttributes( { loop: v } ) }
						__nextHasNoMarginBottom
					/>
					<RangeControl
						label={ __( 'Animation Speed (ms)', 'amitry-product-category-slider' ) }
						value={ speed }
						onChange={ ( v ) => setAttributes( { speed: v } ) }
						min={ 200 } max={ 2000 } step={ 50 }
						__nextHasNoMarginBottom __next40pxDefaultSize
					/>
					<SelectControl
						label={ __( 'Transition', 'amitry-product-category-slider' ) }
						value={ transitionEffect }
						options={ [
							{ label: __( 'Slide', 'amitry-product-category-slider' ), value: 'slide' },
							{ label: __( 'Fade', 'amitry-product-category-slider' ), value: 'fade' },
						] }
						onChange={ ( v ) => setAttributes( { transitionEffect: v } ) }
						help={ __( 'Fade shows one item at a time, ideal for a single large image.', 'amitry-product-category-slider' ) }
						__nextHasNoMarginBottom __next40pxDefaultSize
					/>
					<ToggleControl
						label={ __( 'Touch Swipe on Mobile', 'amitry-product-category-slider' ) }
						checked={ !! touchEnabled }
						onChange={ ( v ) => setAttributes( { touchEnabled: v } ) }
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Mouse Drag on Desktop', 'amitry-product-category-slider' ) }
						checked={ !! mouseDrag }
						onChange={ ( v ) => setAttributes( { mouseDrag: v } ) }
						__nextHasNoMarginBottom
					/>
				</PanelBody>

				<PanelBody title={ __( 'Navigation', 'amitry-product-category-slider' ) } initialOpen={ false }>
					{ /* Per-device toggles for arrows and dots. */ }
					<DeviceTabBar deviceType={ deviceType } onSwitch={ setPreviewDevice } />
					<ToggleControl
						label={ __( 'Arrows', 'amitry-product-category-slider' ) }
						checked={ resolveBoolean( attributes, 'showArrows', deviceType.toLowerCase() ) }
						onChange={ ( v ) => {
							if ( deviceType === 'Mobile' )      setAttributes( { showArrowsMobile: v ? 'true' : 'false' } );
							else if ( deviceType === 'Tablet' ) setAttributes( { showArrowsTablet: v ? 'true' : 'false' } );
							else                                setAttributes( { showArrows: v } );
						} }
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Pagination Dots', 'amitry-product-category-slider' ) }
						checked={ resolveBoolean( attributes, 'showPaginationDots', deviceType.toLowerCase() ) }
						onChange={ ( v ) => {
							if ( deviceType === 'Mobile' )      setAttributes( { showPaginationDotsMobile: v ? 'true' : 'false' } );
							else if ( deviceType === 'Tablet' ) setAttributes( { showPaginationDotsTablet: v ? 'true' : 'false' } );
							else                                setAttributes( { showPaginationDots: v } );
						} }
						__nextHasNoMarginBottom
					/>
					<ToggleControl label={ __( 'Scrollbar', 'amitry-product-category-slider' ) }
						checked={ !! showScrollbar } onChange={ ( v ) => setAttributes( { showScrollbar: v } ) }
						__nextHasNoMarginBottom />
					<ToggleControl label={ __( 'Progress Bar', 'amitry-product-category-slider' ) }
						checked={ !! showProgress } onChange={ ( v ) => setAttributes( { showProgress: v } ) }
						__nextHasNoMarginBottom />
					<ToggleControl label={ __( 'Slide Counter', 'amitry-product-category-slider' ) }
						checked={ !! showCounter } onChange={ ( v ) => setAttributes( { showCounter: v } ) }
						__nextHasNoMarginBottom />
					<ToggleControl label={ __( 'Keyboard Navigation', 'amitry-product-category-slider' ) }
						checked={ !! keyboardEnabled } onChange={ ( v ) => setAttributes( { keyboardEnabled: v } ) }
						__nextHasNoMarginBottom />
				</PanelBody>

				<PanelBody title={ __( 'Card Content', 'amitry-product-category-slider' ) } initialOpen={ false }>
					<ToggleControl label={ __( 'Image', 'amitry-product-category-slider' ) }
						checked={ !! showImage } onChange={ ( v ) => setAttributes( { showImage: v } ) }
						__nextHasNoMarginBottom />
					<ToggleControl label={ __( 'Title', 'amitry-product-category-slider' ) }
						checked={ !! showTitle } onChange={ ( v ) => setAttributes( { showTitle: v } ) }
						__nextHasNoMarginBottom />
					<SelectControl
						label={ __( 'Content Alignment', 'amitry-product-category-slider' ) }
						value={ contentAlign }
						options={ [
							{ label: __( 'Left', 'amitry-product-category-slider' ), value: 'left' },
							{ label: __( 'Center', 'amitry-product-category-slider' ), value: 'center' },
							{ label: __( 'Right', 'amitry-product-category-slider' ), value: 'right' },
						] }
						onChange={ ( v ) => setAttributes( { contentAlign: v } ) }
						help={ __( 'Aligns the title, price and other card text.', 'amitry-product-category-slider' ) }
						__nextHasNoMarginBottom __next40pxDefaultSize />
					{ isProducts && (
						<>
							<ToggleControl label={ __( 'Price', 'amitry-product-category-slider' ) }
								checked={ !! showPrice } onChange={ ( v ) => setAttributes( { showPrice: v } ) }
								__nextHasNoMarginBottom />
							<ToggleControl label={ __( 'Sale Badge', 'amitry-product-category-slider' ) }
								checked={ !! showSaleBadge } onChange={ ( v ) => setAttributes( { showSaleBadge: v } ) }
								__nextHasNoMarginBottom />
							<ToggleControl label={ __( 'Rating Stars', 'amitry-product-category-slider' ) }
								checked={ !! showRating } onChange={ ( v ) => setAttributes( { showRating: v } ) }
								__nextHasNoMarginBottom />
							<ToggleControl label={ __( 'Short Description', 'amitry-product-category-slider' ) }
								checked={ !! showExcerpt } onChange={ ( v ) => setAttributes( { showExcerpt: v } ) }
								__nextHasNoMarginBottom />
							<ToggleControl label={ __( 'Stock Status', 'amitry-product-category-slider' ) }
								checked={ !! showStock } onChange={ ( v ) => setAttributes( { showStock: v } ) }
								__nextHasNoMarginBottom />
							<ToggleControl label={ __( 'Add to Cart Button', 'amitry-product-category-slider' ) }
								checked={ !! showAddToCart } onChange={ ( v ) => setAttributes( { showAddToCart: v } ) }
								__nextHasNoMarginBottom />
						</>
					) }
					{ isCategories && (
						<>
							<ToggleControl label={ __( 'Product Count', 'amitry-product-category-slider' ) }
								checked={ !! showCount } onChange={ ( v ) => setAttributes( { showCount: v } ) }
								__nextHasNoMarginBottom />
							<ToggleControl label={ __( 'Category Description', 'amitry-product-category-slider' ) }
								checked={ !! showDescription } onChange={ ( v ) => setAttributes( { showDescription: v } ) }
								__nextHasNoMarginBottom />
						</>
					) }
				</PanelBody>

				<PanelBody title={ __( 'Section', 'amitry-product-category-slider' ) } initialOpen={ false }>
					<TextControl label={ __( 'Section Title', 'amitry-product-category-slider' ) }
						value={ sectionTitle } onChange={ ( v ) => setAttributes( { sectionTitle: v } ) }
						__nextHasNoMarginBottom __next40pxDefaultSize />

					{ sectionTitle && (
						<>
							<RangeControl
								label={ __( 'Title Size (px)', 'amitry-product-category-slider' ) }
								value={ sectionTitleSize }
								onChange={ ( v ) => setAttributes( { sectionTitleSize: v } ) }
								min={ 12 } max={ 60 }
								__nextHasNoMarginBottom __next40pxDefaultSize
							/>
							<SelectControl
								label={ __( 'Title Weight', 'amitry-product-category-slider' ) }
								value={ sectionTitleWeight }
								options={ [
									{ label: __( 'Normal',    'amitry-product-category-slider' ), value: 'normal' },
									{ label: __( 'Medium',    'amitry-product-category-slider' ), value: '500' },
									{ label: __( 'Bold',      'amitry-product-category-slider' ), value: 'bold' },
									{ label: __( 'Extra Bold', 'amitry-product-category-slider' ), value: '800' },
								] }
								onChange={ ( v ) => setAttributes( { sectionTitleWeight: v } ) }
								__nextHasNoMarginBottom __next40pxDefaultSize
							/>
							<SelectControl
								label={ __( 'Title Alignment', 'amitry-product-category-slider' ) }
								value={ sectionTitleAlign }
								options={ [
									{ label: __( 'Left',   'amitry-product-category-slider' ), value: 'left' },
									{ label: __( 'Center',  'amitry-product-category-slider' ), value: 'center' },
									{ label: __( 'Right',  'amitry-product-category-slider' ), value: 'right' },
								] }
								onChange={ ( v ) => setAttributes( { sectionTitleAlign: v } ) }
								__nextHasNoMarginBottom __next40pxDefaultSize
							/>
						</>
					) }

					<TextareaControl label={ __( 'Section Subtitle', 'amitry-product-category-slider' ) }
						value={ sectionSubtitle } onChange={ ( v ) => setAttributes( { sectionSubtitle: v } ) }
						__nextHasNoMarginBottom />

					{ sectionSubtitle && (
						<>
							<RangeControl
								label={ __( 'Subtitle Size (px)', 'amitry-product-category-slider' ) }
								value={ sectionSubtitleSize }
								onChange={ ( v ) => setAttributes( { sectionSubtitleSize: v } ) }
								min={ 10 } max={ 32 }
								__nextHasNoMarginBottom __next40pxDefaultSize
							/>
							<SelectControl
								label={ __( 'Subtitle Weight', 'amitry-product-category-slider' ) }
								value={ sectionSubtitleWeight }
								options={ [
									{ label: __( 'Normal',    'amitry-product-category-slider' ), value: 'normal' },
									{ label: __( 'Medium',    'amitry-product-category-slider' ), value: '500' },
									{ label: __( 'Bold',      'amitry-product-category-slider' ), value: 'bold' },
								] }
								onChange={ ( v ) => setAttributes( { sectionSubtitleWeight: v } ) }
								__nextHasNoMarginBottom __next40pxDefaultSize
							/>
							<SelectControl
								label={ __( 'Subtitle Alignment', 'amitry-product-category-slider' ) }
								value={ sectionSubtitleAlign }
								options={ [
									{ label: __( 'Left',   'amitry-product-category-slider' ), value: 'left' },
									{ label: __( 'Center',  'amitry-product-category-slider' ), value: 'center' },
									{ label: __( 'Right',  'amitry-product-category-slider' ), value: 'right' },
								] }
								onChange={ ( v ) => setAttributes( { sectionSubtitleAlign: v } ) }
								__nextHasNoMarginBottom __next40pxDefaultSize
							/>
						</>
					) }

					<ToggleControl label={ __( 'Show "View All" Button', 'amitry-product-category-slider' ) }
						checked={ !! showViewAllButton } onChange={ ( v ) => setAttributes( { showViewAllButton: v } ) }
						__nextHasNoMarginBottom />
					{ showViewAllButton && (
						<>
							<TextControl label={ __( 'Button URL', 'amitry-product-category-slider' ) }
								value={ viewAllUrl } onChange={ ( v ) => setAttributes( { viewAllUrl: v } ) }
								type="url" __nextHasNoMarginBottom __next40pxDefaultSize />
							<TextControl label={ __( 'Button Text', 'amitry-product-category-slider' ) }
								value={ viewAllText } onChange={ ( v ) => setAttributes( { viewAllText: v } ) }
								placeholder={ __( 'View All', 'amitry-product-category-slider' ) }
								__nextHasNoMarginBottom __next40pxDefaultSize />
							<SelectControl
								label={ __( 'Position', 'amitry-product-category-slider' ) }
								value={ viewAllPosition }
								options={ [
									{ label: __( 'Below Slider', 'amitry-product-category-slider' ), value: 'below' },
									{ label: __( 'Above Slider', 'amitry-product-category-slider' ), value: 'above' },
								] }
								onChange={ ( v ) => setAttributes( { viewAllPosition: v } ) }
								__nextHasNoMarginBottom __next40pxDefaultSize
							/>
							<SelectControl
								label={ __( 'Alignment', 'amitry-product-category-slider' ) }
								value={ viewAllAlign }
								options={ [
									{ label: __( 'Left',  'amitry-product-category-slider' ), value: 'left' },
									{ label: __( 'Center', 'amitry-product-category-slider' ), value: 'center' },
									{ label: __( 'Right', 'amitry-product-category-slider' ), value: 'right' },
								] }
								onChange={ ( v ) => setAttributes( { viewAllAlign: v } ) }
								__nextHasNoMarginBottom __next40pxDefaultSize
							/>
							<SelectControl
								label={ __( 'Icon', 'amitry-product-category-slider' ) }
								value={ viewAllIcon }
								options={ [
									{ label: __( 'None',          'amitry-product-category-slider' ), value: 'none' },
									{ label: __( 'Arrow Right',  'amitry-product-category-slider' ), value: 'arrow-right' },
								] }
								onChange={ ( v ) => setAttributes( { viewAllIcon: v } ) }
								__nextHasNoMarginBottom __next40pxDefaultSize
							/>
							<RangeControl
								label={ __( 'Border Radius (px)', 'amitry-product-category-slider' ) }
								value={ viewAllRadius }
								onChange={ ( v ) => setAttributes( { viewAllRadius: v } ) }
								min={ 0 } max={ 32 }
								__nextHasNoMarginBottom __next40pxDefaultSize
							/>
							<RangeControl
								label={ __( 'Padding (px)', 'amitry-product-category-slider' ) }
								value={ viewAllPadding }
								onChange={ ( v ) => setAttributes( { viewAllPadding: v } ) }
								min={ 4 } max={ 32 }
								__nextHasNoMarginBottom __next40pxDefaultSize
							/>
						</>
					) }
				</PanelBody>
			</InspectorControls>

			{ /* ─── HALF-MOON TAB (Styles) ────────────────────────── */ }
			<InspectorControls group="styles">
				<PanelBody title={ __( 'Card Style', 'amitry-product-category-slider' ) } initialOpen={ true }>
					<SelectControl
						label={ __( 'Design', 'amitry-product-category-slider' ) }
						value={ styleVariant }
						options={ applyFilters(
							'wcsp.designOptions',
							[
								{ label: __( 'Clean Card', 'amitry-product-category-slider' ), value: 'clean-card' },
								{ label: __( 'Minimal',    'amitry-product-category-slider' ), value: 'minimal' },
							]
						) }
						onChange={ ( v ) => setAttributes( { styleVariant: v } ) }
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __( 'Shadow', 'amitry-product-category-slider' ) }
						value={ shadowIntensity }
						options={ [
							{ label: __( 'None', 'amitry-product-category-slider' ), value: 'none' },
							{ label: __( 'Soft',  'amitry-product-category-slider' ), value: 'soft' },
							{ label: __( 'Medium', 'amitry-product-category-slider' ), value: 'medium' },
							{ label: __( 'Strong',  'amitry-product-category-slider' ), value: 'strong' },
						] }
						onChange={ ( v ) => setAttributes( { shadowIntensity: v } ) }
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __( 'Hover Effect', 'amitry-product-category-slider' ) }
						value={ hoverEffect }
						options={ [
							{ label: __( 'None',            'amitry-product-category-slider' ), value: 'none' },
							{ label: __( 'Lift',         'amitry-product-category-slider' ), value: 'lift' },
							{ label: __( 'Zoom',            'amitry-product-category-slider' ), value: 'zoom' },
							{ label: __( 'Shine',   'amitry-product-category-slider' ), value: 'shine' },
							{ label: __( 'Lift + Shine', 'amitry-product-category-slider' ), value: 'lift_shine' },
							{ label: __( 'Lift + Zoom', 'amitry-product-category-slider' ), value: 'lift_zoom' },
						] }
						onChange={ ( v ) => setAttributes( { hoverEffect: v } ) }
						__nextHasNoMarginBottom
					/>
					<RangeControl
						label={ __( 'Border Radius (px)', 'amitry-product-category-slider' ) }
						value={ cardRadius } onChange={ ( v ) => setAttributes( { cardRadius: v } ) }
						min={ 0 } max={ 48 } __nextHasNoMarginBottom __next40pxDefaultSize
					/>
					<RangeControl
						label={ __( 'Max Width (px)', 'amitry-product-category-slider' ) }
						value={ maxWidth }
						onChange={ ( v ) => setAttributes( { maxWidth: v } ) }
						min={ 0 } max={ 1600 } step={ 10 }
						help={ __( '0 = full container width. Caps how wide the slider grows and centers it, useful with full width themes.', 'amitry-product-category-slider' ) }
						__nextHasNoMarginBottom __next40pxDefaultSize
					/>

					{ /* Per-device sub-section for card padding. */ }
					<p className="wcsp-section-label" style={ { marginTop: '20px', marginBottom: '8px' } }>
						{ __( 'Card Padding (px)', 'amitry-product-category-slider' ) }
					</p>
					<DeviceTabBar deviceType={ deviceType } onSwitch={ setPreviewDevice } />
					<RangeControl
						value={ resolveNumeric( attributes, 'cardPadding', deviceType.toLowerCase() ) }
						onChange={ ( v ) => {
							if ( deviceType === 'Mobile' )      setAttributes( { cardPaddingMobile: v } );
							else if ( deviceType === 'Tablet' ) setAttributes( { cardPaddingTablet: v } );
							else                                setAttributes( { cardPadding: v } );
						} }
						min={ 0 } max={ 48 } __nextHasNoMarginBottom __next40pxDefaultSize
					/>
				</PanelBody>

				<PanelColorSettings
					title={ __( 'Colors', 'amitry-product-category-slider' ) }
					initialOpen={ false }
					colorSettings={ [
						{
							value: cardBackgroundColor,
							onChange: ( v ) => setAttributes( { cardBackgroundColor: v || '' } ),
							label: __( 'Card Background', 'amitry-product-category-slider' ),
						},
						{
							value: titleColor,
							onChange: ( v ) => setAttributes( { titleColor: v || '' } ),
							label: __( 'Title', 'amitry-product-category-slider' ),
						},
						{
							value: priceColor,
							onChange: ( v ) => setAttributes( { priceColor: v || '' } ),
							label: __( 'Price', 'amitry-product-category-slider' ),
						},
						{
							value: arrowColor,
							onChange: ( v ) => setAttributes( { arrowColor: v || '' } ),
							label: __( 'Arrows - Symbol', 'amitry-product-category-slider' ),
						},
						{
							value: arrowBgColor,
							onChange: ( v ) => setAttributes( { arrowBgColor: v || '' } ),
							label: __( 'Arrows - Background', 'amitry-product-category-slider' ),
						},
						{
							value: sectionTitleColor,
							onChange: ( v ) => setAttributes( { sectionTitleColor: v || '' } ),
							label: __( 'Section Title', 'amitry-product-category-slider' ),
						},
						{
							value: sectionSubtitleColor,
							onChange: ( v ) => setAttributes( { sectionSubtitleColor: v || '' } ),
							label: __( 'Section Subtitle', 'amitry-product-category-slider' ),
						},
						{
							value: viewAllBgColor,
							onChange: ( v ) => setAttributes( { viewAllBgColor: v || '' } ),
							label: __( 'View All Button - Background', 'amitry-product-category-slider' ),
						},
						{
							value: viewAllTextColor,
							onChange: ( v ) => setAttributes( { viewAllTextColor: v || '' } ),
							label: __( 'View All Button - Text', 'amitry-product-category-slider' ),
						},
					] }
				/>

				<PanelBody title={ __( 'Image', 'amitry-product-category-slider' ) } initialOpen={ false }>
					<SelectControl
						label={ __( 'Image Shape', 'amitry-product-category-slider' ) }
						value={ imageShape }
						options={ [
							{ label: __( 'Square',      'amitry-product-category-slider' ), value: 'square' },
							{ label: __( 'Rounded', 'amitry-product-category-slider' ), value: 'rounded' },
							{ label: __( 'Circle',      'amitry-product-category-slider' ), value: 'circle' },
						] }
						onChange={ ( v ) => setAttributes( { imageShape: v } ) }
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __( 'Image Ratio', 'amitry-product-category-slider' ) }
						value={ aspectRatio }
						options={ [
							{ label: '1:1',  value: '1-1' },
							{ label: '4:3',  value: '4-3' },
							{ label: '3:4',  value: '3-4' },
							{ label: '16:9', value: '16-9' },
						] }
						onChange={ ( v ) => setAttributes( { aspectRatio: v } ) }
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __( 'Image Fit', 'amitry-product-category-slider' ) }
						value={ imageFit }
						options={ [
							{ label: __( 'Crop to fill', 'amitry-product-category-slider' ), value: 'cover' },
							{ label: __( 'Show full image', 'amitry-product-category-slider' ), value: 'contain' },
						] }
						onChange={ ( v ) => setAttributes( { imageFit: v } ) }
						help={ __( 'Show full image keeps photos uncropped, ideal for photography.', 'amitry-product-category-slider' ) }
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Overlay Gradient on Image', 'amitry-product-category-slider' ) }
						checked={ !! showOverlayGradient }
						onChange={ ( v ) => setAttributes( { showOverlayGradient: v } ) }
						__nextHasNoMarginBottom
					/>
				</PanelBody>

				<PanelBody title={ __( 'Arrows', 'amitry-product-category-slider' ) } initialOpen={ false }>
					<p className="wcsp-section-label" style={ { marginBottom: '8px' } }>
						{ __( 'Size (px)', 'amitry-product-category-slider' ) }
					</p>
					<DeviceTabBar deviceType={ deviceType } onSwitch={ setPreviewDevice } />
					<RangeControl
						value={ resolveNumeric( attributes, 'arrowSize', deviceType.toLowerCase() ) }
						onChange={ ( v ) => {
							if ( deviceType === 'Mobile' )      setAttributes( { arrowSizeMobile: v } );
							else if ( deviceType === 'Tablet' ) setAttributes( { arrowSizeTablet: v } );
							else                                setAttributes( { arrowSize: v } );
						} }
						min={ 24 } max={ 80 } __nextHasNoMarginBottom __next40pxDefaultSize
					/>
				</PanelBody>

				<PanelBody title={ __( 'Dots', 'amitry-product-category-slider' ) } initialOpen={ false }>
					<SelectControl label={ __( 'Shape', 'amitry-product-category-slider' ) }
						value={ dotsShape }
						options={ [
							{ label: __( 'Round',   'amitry-product-category-slider' ), value: 'round' },
							{ label: __( 'Bar', 'amitry-product-category-slider' ), value: 'bar' },
						] }
						onChange={ ( v ) => setAttributes( { dotsShape: v } ) }
						__nextHasNoMarginBottom />
				</PanelBody>
			</InspectorControls>

			{ /* ─── Preview ───────────────────────────────────────── */ }
			<div { ...blockProps }>
				<style>{ styleRules }</style>
				<div ref={ wrapperRef } className={ `${ outerClasses } ${ instanceClass }` }>
					<ServerSideRender
						block="amitry-product-category-slider/slider"
						attributes={ dataAttrs }
						EmptyResponsePlaceholder={ () => (
							<div className="wcsp-editor-empty">
								{ __( 'No items to display.', 'amitry-product-category-slider' ) }
							</div>
						) }
						LoadingResponsePlaceholder={ () => (
							<div className="wcsp-editor-loading">
								{ __( 'Loading preview...', 'amitry-product-category-slider' ) }
							</div>
						) }
					/>
				</div>
			</div>
		</>
	);
}

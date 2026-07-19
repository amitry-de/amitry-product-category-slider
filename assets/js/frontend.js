/**
 * Amitry Product & Category Slider - Frontend JS.
 *
 * Finds every .wcsp-slider on the page and initializes Swiper.js
 * with options pulled from the wrapper's data-wcsp-* attributes.
 *
 * Public JS hook API (Pro add-ons hook into these):
 *   wcsp.beforeInit       - before Swiper instance is created
 *   wcsp.swiperOptions    - filter Swiper config before instantiation
 *   wcsp.afterInit        - after Swiper instance is created
 *
 * Hook registration (Pro side):
 *   window.wcsp = window.wcsp || {};
 *   window.wcsp.hooks = window.wcsp.hooks || {};
 *   window.wcsp.hooks.afterInit = window.wcsp.hooks.afterInit || [];
 *   window.wcsp.hooks.afterInit.push(function(swiper, wrapper) { ... });
 *
 * Global namespace, no AMD/UMD - we don't ship a build tool for the
 * frontend script.
 */
( function () {
	'use strict';

	if ( typeof window === 'undefined' ) {
		return;
	}

	// Expose namespace + hooks registry.
	window.wcsp = window.wcsp || {};
	window.wcsp.hooks = window.wcsp.hooks || {};
	window.wcsp.instances = window.wcsp.instances || [];

	// Respect the user's reduced-motion preference: when set, autoplay is
	// disabled and slide transitions are instant (WCAG-friendly).
	const prefersReducedMotion = !! ( window.matchMedia &&
		window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );

	/**
	 * Run a list of hook callbacks. Falsy returns are ignored, the value
	 * is threaded through filters and returned at the end.
	 */
	function runHook( name, value, ...extraArgs ) {
		const list = window.wcsp.hooks[ name ];
		if ( ! Array.isArray( list ) ) {
			return value;
		}
		let current = value;
		for ( const fn of list ) {
			if ( typeof fn !== 'function' ) {
				continue;
			}
			try {
				const result = fn( current, ...extraArgs );
				if ( typeof result !== 'undefined' ) {
					current = result;
				}
			} catch ( err ) {
				// One bad hook should not break the slider.
				// eslint-disable-next-line no-console
				console.error( '[wcsp] Hook error in ' + name + ':', err );
			}
		}
		return current;
	}

	/**
	 * Parse a data-* attribute as a positive integer with a fallback.
	 */
	function getInt( el, attr, fallback ) {
		const v = el.getAttribute( attr );
		if ( v === null || v === '' ) {
			return fallback;
		}
		const n = parseInt( v, 10 );
		return Number.isFinite( n ) ? n : fallback;
	}

	/**
	 * Parse a data-* attribute as a boolean ("1"/"0").
	 */
	function getBool( el, attr, fallback ) {
		const v = el.getAttribute( attr );
		if ( v === null ) {
			return fallback;
		}
		return v === '1' || v === 'true';
	}

	/**
	 * Build the Swiper options object from a wrapper element.
	 */
	function buildOptions( wrapper ) {
		const colsDsk  = getInt( wrapper, 'data-wcsp-cols-dsk', 4 );
		const colsTab  = getInt( wrapper, 'data-wcsp-cols-tab', 2 );
		const colsMob  = getInt( wrapper, 'data-wcsp-cols-mob', 1 );
		const gapDsk   = getInt( wrapper, 'data-wcsp-gap', 24 );
		// Per-device gap data attrs already carry resolved values (the
		// PHP renderer applied the inheritance chain). If missing, fall
		// back to the desktop value.
		const gapTab   = getInt( wrapper, 'data-wcsp-gap-tab', gapDsk );
		const gapMob   = getInt( wrapper, 'data-wcsp-gap-mob', gapDsk );
		const isEditor = wrapper.dataset.wcspContext === 'editor';

		// In the editor, breakpoints don't work as expected because Swiper
		// runs in the parent window which always reports the full browser
		// width. Force specific cols + gap matching the editor preview
		// device (Desktop / Tablet / Mobile).
		let editorCols = null;
		let editorGap  = gapDsk;
		if ( isEditor ) {
			const device = ( wrapper.dataset.wcspEditorDevice || 'Desktop' ).toLowerCase();
			if ( device === 'mobile' ) {
				editorCols = colsMob;
				editorGap  = gapMob;
			} else if ( device === 'tablet' ) {
				editorCols = colsTab;
				editorGap  = gapTab;
			} else {
				editorCols = colsDsk;
				editorGap  = gapDsk;
			}
		}

		const opts = {
			slidesPerView: isEditor ? editorCols : colsMob,
			spaceBetween: isEditor ? editorGap : gapMob,
			speed: prefersReducedMotion ? 0 : getInt( wrapper, 'data-wcsp-speed', 600 ),
			loop: getBool( wrapper, 'data-wcsp-loop', false ),
			allowTouchMove: isEditor ? false : getBool( wrapper, 'data-wcsp-touch', true ),
			simulateTouch: isEditor ? false : getBool( wrapper, 'data-wcsp-drag', true ),
			grabCursor: isEditor ? false : getBool( wrapper, 'data-wcsp-drag', true ),
			watchOverflow: true, // hide controls when not enough slides
			// Recalculate slide widths when the slider or a parent
			// changes size. Without this, Swiper measures once at init;
			// in the block editor a max-width or layout can apply just
			// after init, leaving slide widths (and thus the image
			// centering) slightly off. Observers keep it in sync.
			observer: true,
			observeParents: true,
		};

		// Frontend uses Swiper's breakpoints. Editor uses the forced
		// value from above and doesn't need breakpoints.
		if ( ! isEditor ) {
			opts.breakpoints = {
				641: {
					slidesPerView: colsTab,
					spaceBetween: gapTab,
				},
				1025: {
					slidesPerView: colsDsk,
					spaceBetween: gapDsk,
				},
			};
		}

		// Autoplay (skipped in editor context - too distracting while authoring,
		// and skipped entirely when the user prefers reduced motion).
		if ( ! isEditor && ! prefersReducedMotion && getBool( wrapper, 'data-wcsp-autoplay', false ) ) {
			opts.autoplay = {
				delay: getInt( wrapper, 'data-wcsp-delay', 3000 ),
				disableOnInteraction: false,
				pauseOnMouseEnter: getBool( wrapper, 'data-wcsp-pause', true ),
			};
		}

		// Keyboard - arrow keys navigate the slider. Works when the
		// slider is in the viewport; onlyInViewport=false would let it
		// react globally, which can fight with other keyboard handlers.
		if ( ! isEditor && getBool( wrapper, 'data-wcsp-keyboard', false ) ) {
			opts.keyboard = { enabled: true, onlyInViewport: true };
			// Make the slider element focusable so keyboard events reach it.
			if ( ! wrapper.hasAttribute( 'tabindex' ) ) {
				wrapper.setAttribute( 'tabindex', '0' );
			}
		}

		// Arrows.
		const prev = wrapper.querySelector( '.wcsp-btn--prev' );
		const next = wrapper.querySelector( '.wcsp-btn--next' );
		if ( prev && next ) {
			opts.navigation = {
				prevEl: prev,
				nextEl: next,
				disabledClass: 'wcsp-btn--disabled',
			};
		}

		// Pagination dots.
		const dots = wrapper.querySelector( '.wcsp-dots' );
		if ( dots ) {
			opts.pagination = {
				el: dots,
				clickable: true,
				bulletClass: 'wcsp-dot',
				bulletActiveClass: 'wcsp-dot--active',
			};
		}

		// Scrollbar.
		const scrollbar = wrapper.querySelector( '.wcsp-scrollbar' );
		if ( scrollbar ) {
			opts.scrollbar = { el: scrollbar, draggable: true };
		}

		// Fade transition. Swiper's fade works one slide at a time, so we
		// force a single column (across breakpoints) for a clean cross-fade.
		// Fade shows one slide at a time, so a gap between slides is
		// meaningless and would leave dead space beside the image and
		// offset the crossfade. Force a single column AND zero spacing.
		if ( wrapper.getAttribute( 'data-wcsp-effect' ) === 'fade' ) {
			opts.effect = 'fade';
			opts.fadeEffect = { crossFade: true };
			opts.slidesPerView = 1;
			opts.spaceBetween = 0;
			if ( opts.breakpoints ) {
				Object.keys( opts.breakpoints ).forEach( function ( bp ) {
					opts.breakpoints[ bp ].slidesPerView = 1;
					opts.breakpoints[ bp ].spaceBetween = 0;
				} );
			}
		}

		return opts;
	}

	/**
	 * Initialize a single slider wrapper.
	 */
	function initSlider( wrapper ) {
		if ( wrapper.dataset.wcspReady === '1' ) {
			return; // already initialized
		}
		if ( typeof window.Swiper !== 'function' ) {
			// eslint-disable-next-line no-console
			console.warn( '[wcsp] Swiper not loaded yet.' );
			return;
		}

		// The track inside our wrapper is the Swiper container's wrapper.
		const inner = wrapper.querySelector( '.wcsp-inner' );
		const track = wrapper.querySelector( '.wcsp-track' );
		if ( ! inner || ! track ) {
			return;
		}

		// Swiper expects:
		//   <div class="swiper">
		//     <div class="swiper-wrapper">
		//       <div class="swiper-slide"></div>
		//     </div>
		//   </div>
		// We add those classes alongside our own so theme CSS still targets ours.
		inner.classList.add( 'swiper' );
		track.classList.add( 'swiper-wrapper' );
		wrapper.querySelectorAll( '.wcsp-slide' ).forEach( ( slide ) => {
			slide.classList.add( 'swiper-slide' );
		} );

		// Reveal the arrows now that JS has taken over.
		wrapper.classList.add( 'wcsp-slider--js' );

		runHook( 'beforeInit', wrapper );

		let opts = buildOptions( wrapper );
		opts = runHook( 'swiperOptions', opts, wrapper );

		const swiper = new window.Swiper( inner, opts );

		// In the block editor the final width can settle a moment after
		// init (max-width applying, SSR layout). Nudge Swiper to remeasure
		// so the image stays centered and the arrows line up with it.
		if ( wrapper.dataset.wcspContext === 'editor' ) {
			const nudge = () => {
				try {
					swiper.update();
				} catch ( e ) {
					// Swiper may be gone if the block re-rendered; ignore.
				}
			};
			requestAnimationFrame( nudge );
			setTimeout( nudge, 200 );
		}

		// Wire up progress bar (if present): width follows Swiper.progress (0..1).
		const progressBar = wrapper.querySelector( '.wcsp-progress__bar' );
		if ( progressBar ) {
			const updateProgress = ( s ) => {
				const p = Math.max( 0, Math.min( 1, s.progress || 0 ) );
				progressBar.style.width = ( p * 100 ) + '%';
			};
			swiper.on( 'progress', updateProgress );
			swiper.on( 'slideChange', updateProgress );
			updateProgress( swiper );
		}

		// Wire up counter (if present): "currentIndex / totalSlides".
		const counterCurrent = wrapper.querySelector( '.wcsp-counter__current' );
		const counterTotal   = wrapper.querySelector( '.wcsp-counter__total' );
		if ( counterCurrent && counterTotal ) {
			const updateCounter = ( s ) => {
				// `realIndex` accounts for loop-mode duplicated slides.
				const idx = ( typeof s.realIndex === 'number' ? s.realIndex : s.activeIndex ) + 1;
				const total = s.slides ? s.slides.length : 0;
				counterCurrent.textContent = idx;
				counterTotal.textContent = total;
			};
			swiper.on( 'slideChange', updateCounter );
			updateCounter( swiper );
		}

		runHook( 'afterInit', swiper, wrapper );

		wrapper.dataset.wcspReady = '1';
		window.wcsp.instances.push( { wrapper: wrapper, swiper: swiper } );
	}

	/**
	 * Initialize all sliders currently on the page.
	 */
	function initAll() {
		const wrappers = document.querySelectorAll( '.wcsp-slider:not([data-wcsp-ready="1"])' );
		wrappers.forEach( initSlider );
	}

	// Run on DOM ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initAll );
	} else {
		initAll();
	}

	// Re-scan on common late-DOM events (Elementor preview, AJAX page loads).
	document.addEventListener( 'wcsp:rescan', initAll );

	/**
	 * Destroy a Swiper instance attached to the given wrapper, if any.
	 * Removes the swiper-* classes we added so re-init is possible.
	 */
	function destroySlider( wrapper ) {
		if ( ! wrapper ) {
			return;
		}
		const idx = window.wcsp.instances.findIndex( ( i ) => i.wrapper === wrapper );
		if ( idx === -1 ) {
			return;
		}
		const entry = window.wcsp.instances[ idx ];
		try {
			entry.swiper.destroy( true, true );
		} catch ( e ) {
			// Swallow; we tear down what we can.
		}
		const inner = wrapper.querySelector( '.wcsp-inner' );
		const track = wrapper.querySelector( '.wcsp-track' );
		if ( inner ) { inner.classList.remove( 'swiper' ); }
		if ( track ) { track.classList.remove( 'swiper-wrapper' ); }
		wrapper.querySelectorAll( '.wcsp-slide' ).forEach( ( s ) => s.classList.remove( 'swiper-slide' ) );
		wrapper.classList.remove( 'wcsp-slider--js' );
		delete wrapper.dataset.wcspReady;
		window.wcsp.instances.splice( idx, 1 );
	}

	// Public API.
	window.wcsp.init = initAll;
	window.wcsp.initSlider = initSlider;
	window.wcsp.destroySlider = destroySlider;
} )();

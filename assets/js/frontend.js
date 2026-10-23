/**
 * Summarize with AI — front end. Dependency-free, ~2 KB minified.
 */
( function () {
	'use strict';

	var config = window.swaiConfig || {};
	var i18n = config.i18n || {};
	var device = window.matchMedia && window.matchMedia( '(max-width: 767px)' ).matches ? 'mobile' : 'desktop';

	// Must stay in sync with Renderer::tracked_url() / build_prompt().
	function trackedUrl( url, service ) {
		if ( ! config.utm || ! url ) {
			return url;
		}
		return url + ( url.indexOf( '?' ) === -1 ? '?' : '&' ) +
			'utm_source=' + encodeURIComponent( config.utm ) +
			'&utm_medium=' + encodeURIComponent( service );
	}

	function buildPrompt( template, ctx, service ) {
		var url = trackedUrl( ctx.url, service );
		var prompt = template
			.split( '{url}' ).join( url )
			.split( '{title}' ).join( ctx.title )
			.split( '{site}' ).join( ctx.site )
			.split( '{excerpt}' ).join( ctx.excerpt );
		if ( template.indexOf( '{url}' ) === -1 ) {
			prompt += ' ' + url;
		}
		return prompt.trim();
	}

	function track( box, event, service ) {
		if ( ! config.endpoint ) {
			return;
		}
		var body = new URLSearchParams( {
			event: event,
			post: String( box.data.post || 0 ),
			service: service || '',
			mode: box.data.mode,
			device: device
		} );
		if ( navigator.sendBeacon ) {
			navigator.sendBeacon( config.endpoint, body );
		} else if ( window.fetch ) {
			fetch( config.endpoint, { method: 'POST', body: body, keepalive: true, credentials: 'omit' } );
		}
	}

	function copyText( text ) {
		if ( navigator.clipboard && window.isSecureContext ) {
			return navigator.clipboard.writeText( text );
		}
		return new Promise( function ( resolve, reject ) {
			var area = document.createElement( 'textarea' );
			area.value = text;
			area.setAttribute( 'readonly', '' );
			area.style.cssText = 'position:fixed;opacity:0;pointer-events:none';
			document.body.appendChild( area );
			area.select();
			var ok = false;
			try {
				ok = document.execCommand( 'copy' );
			} catch ( e ) {}
			document.body.removeChild( area );
			( ok ? resolve : reject )();
		} );
	}

	function toast( box, message ) {
		var el = box.el.querySelector( '.swai__toast' );
		if ( ! el ) {
			return;
		}
		el.textContent = message;
		el.classList.add( 'is-visible' );
		clearTimeout( box.toastTimer );
		box.toastTimer = setTimeout( function () {
			el.classList.remove( 'is-visible' );
		}, 2600 );
	}

	function currentPrompt( box, service ) {
		return buildPrompt( box.data.modes[ box.data.mode ], box.data.ctx, service );
	}

	function refreshLinks( box ) {
		box.el.querySelectorAll( '.swai__btn[data-tpl]' ).forEach( function ( a ) {
			if ( a.hasAttribute( 'data-copy' ) ) {
				return;
			}
			var prompt = currentPrompt( box, a.getAttribute( 'data-service' ) );
			a.href = a.getAttribute( 'data-tpl' ).split( '{q}' ).join( encodeURIComponent( prompt ) );
		} );
	}

	function init( el ) {
		var box = { el: el };
		try {
			box.data = JSON.parse( el.getAttribute( 'data-swai' ) );
		} catch ( e ) {
			return;
		}

		el.addEventListener( 'click', function ( e ) {
			var mode = e.target.closest( '.swai__mode' );
			if ( mode ) {
				box.data.mode = mode.getAttribute( 'data-mode' );
				el.querySelectorAll( '.swai__mode' ).forEach( function ( b ) {
					b.setAttribute( 'aria-pressed', b === mode ? 'true' : 'false' );
				} );
				refreshLinks( box );
				return;
			}

			if ( e.target.closest( '.swai__copy' ) ) {
				var btn = e.target.closest( '.swai__copy' );
				copyText( currentPrompt( box, 'copy' ) ).then( function () {
					btn.classList.add( 'is-done' );
					setTimeout( function () {
						btn.classList.remove( 'is-done' );
					}, 1600 );
					toast( box, i18n.copied );
				}, function () {
					toast( box, i18n.copyFail );
				} );
				track( box, 'click', 'copy' );
				return;
			}

			var link = e.target.closest( '.swai__btn[data-service]' );
			if ( ! link ) {
				return;
			}
			var service = link.getAttribute( 'data-service' );
			if ( link.hasAttribute( 'data-copy' ) ) {
				// No prompt parameter: copy first, then let the link open.
				copyText( currentPrompt( box, service ) ).then( function () {
					toast( box, ( i18n.copiedFor || '%s' ).replace( '%s', link.getAttribute( 'data-name' ) ) );
				}, function () {} );
			}
			track( box, 'click', service );
		} );

		if ( config.trackViews && 'IntersectionObserver' in window ) {
			var io = new IntersectionObserver( function ( entries ) {
				if ( entries[ 0 ].isIntersecting ) {
					io.disconnect();
					track( box, 'view', '' );
				}
			}, { threshold: 0.5 } );
			io.observe( el );
		}
	}

	function initFab( fab ) {
		var toggle = fab.querySelector( '.swai-fab__toggle' );
		var panel = fab.querySelector( '.swai-fab__panel' );
		function setOpen( open ) {
			panel.hidden = ! open;
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			if ( open ) {
				var first = panel.querySelector( '.swai__btn, .swai__mode' );
				if ( first ) {
					first.focus();
				}
			}
		}
		toggle.addEventListener( 'click', function () {
			setOpen( panel.hidden );
		} );
		fab.querySelector( '.swai-fab__close' ).addEventListener( 'click', function () {
			setOpen( false );
			toggle.focus();
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && ! panel.hidden ) {
				setOpen( false );
				toggle.focus();
			}
		} );
		document.addEventListener( 'click', function ( e ) {
			if ( ! panel.hidden && ! fab.contains( e.target ) ) {
				setOpen( false );
			}
		} );
	}

	function boot() {
		document.querySelectorAll( '.swai[data-swai]' ).forEach( init );
		document.querySelectorAll( '.swai-fab' ).forEach( initFab );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );

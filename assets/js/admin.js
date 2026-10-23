/**
 * Summarize with AI — admin screens.
 * Dependency-free SVG charts for the dashboard and the settings page interactions.
 */
( function () {
	'use strict';

	var NS = 'http://www.w3.org/2000/svg';
	var rtl = document.documentElement.dir === 'rtl';
	var fmt = new Intl.NumberFormat( document.documentElement.lang || undefined );

	function $( sel, root ) {
		return ( root || document ).querySelector( sel );
	}
	function $$( sel, root ) {
		return Array.prototype.slice.call( ( root || document ).querySelectorAll( sel ) );
	}
	function svg( tag, attrs, parent ) {
		var node = document.createElementNS( NS, tag );
		Object.keys( attrs || {} ).forEach( function ( k ) {
			node.setAttribute( k, attrs[ k ] );
		} );
		if ( parent ) {
			parent.appendChild( node );
		}
		return node;
	}
	function niceMax( v ) {
		if ( v <= 4 ) {
			return 4;
		}
		var p = Math.pow( 10, Math.floor( Math.log10( v ) ) );
		var n = v / p;
		return ( n <= 1 ? 1 : n <= 2 ? 2 : n <= 2.5 ? 2.5 : n <= 5 ? 5 : 10 ) * p;
	}
	// Smooth path through points (monotone-ish Catmull-Rom → Bézier).
	function smooth( pts ) {
		if ( pts.length < 2 ) {
			return pts.length ? 'M' + pts[ 0 ][ 0 ] + ',' + pts[ 0 ][ 1 ] : '';
		}
		var d = 'M' + pts[ 0 ][ 0 ] + ',' + pts[ 0 ][ 1 ];
		for ( var i = 0; i < pts.length - 1; i++ ) {
			var p0 = pts[ i - 1 ] || pts[ i ];
			var p1 = pts[ i ];
			var p2 = pts[ i + 1 ];
			var p3 = pts[ i + 2 ] || p2;
			var t = 0.18;
			var c1y = Math.min( Math.max( p1[ 1 ] + ( p2[ 1 ] - p0[ 1 ] ) * t, Math.min( p1[ 1 ], p2[ 1 ] ) ), Math.max( p1[ 1 ], p2[ 1 ] ) );
			var c2y = Math.min( Math.max( p2[ 1 ] - ( p3[ 1 ] - p1[ 1 ] ) * t, Math.min( p1[ 1 ], p2[ 1 ] ) ), Math.max( p1[ 1 ], p2[ 1 ] ) );
			d += ' C' + ( p1[ 0 ] + ( p2[ 0 ] - p0[ 0 ] ) * t ) + ',' + c1y + ' ' + ( p2[ 0 ] - ( p3[ 0 ] - p1[ 0 ] ) * t ) + ',' + c2y + ' ' + p2[ 0 ] + ',' + p2[ 1 ];
		}
		return d;
	}

	/* ------------------------------------------------------------------
	 * Dashboard
	 * ---------------------------------------------------------------- */

	function areaChart( host, data ) {
		var tip = document.createElement( 'div' );
		tip.className = 'swai-tip';
		tip.style.opacity = '0';

		function draw() {
			host.textContent = '';
			host.appendChild( tip );
			var W = host.clientWidth;
			var H = host.clientHeight;
			var pad = { t: 10, r: 8, b: 26, l: 36 };
			var n = data.clicks.length;
			var max = niceMax( Math.max.apply( null, data.clicks.concat( data.prev, [ 1 ] ) ) );
			var iw = W - pad.l - pad.r;
			var ih = H - pad.t - pad.b;
			var x = function ( i ) {
				var pos = n > 1 ? ( i / ( n - 1 ) ) * iw : iw / 2;
				return pad.l + ( rtl ? iw - pos : pos );
			};
			var y = function ( v ) {
				return pad.t + ih - ( v / max ) * ih;
			};
			var root = svg( 'svg', { viewBox: '0 0 ' + W + ' ' + H, preserveAspectRatio: 'none' } );
			var defs = svg( 'defs', {}, root );
			var grad = svg( 'linearGradient', { id: 'swai-area', x1: 0, y1: 0, x2: 0, y2: 1 }, defs );
			svg( 'stop', { offset: '0%', 'stop-color': '#6d5dfc', 'stop-opacity': 0.28 }, grad );
			svg( 'stop', { offset: '100%', 'stop-color': '#6d5dfc', 'stop-opacity': 0 }, grad );

			for ( var g = 0; g <= 4; g++ ) {
				var gy = pad.t + ( ih / 4 ) * g;
				svg( 'line', { class: 'grid', x1: pad.l, x2: W - pad.r, y1: gy, y2: gy }, root );
				var label = svg( 'text', { class: 'axis', x: rtl ? W - 4 : pad.l - 8, y: gy + 4, 'text-anchor': rtl ? 'end' : 'end' }, root );
				label.textContent = fmt.format( Math.round( max - ( max / 4 ) * g ) );
				if ( rtl ) {
					label.setAttribute( 'x', W - pad.r + 30 );
				}
			}
			var step = Math.max( 1, Math.ceil( n / 7 ) );
			for ( var i = 0; i < n; i += step ) {
				var t = svg( 'text', { class: 'axis', x: x( i ), y: H - 6, 'text-anchor': 'middle' }, root );
				t.textContent = data.labels[ i ];
			}

			var pts = data.clicks.map( function ( v, i ) {
				return [ x( i ), y( v ) ];
			} );
			var prev = data.prev.map( function ( v, i ) {
				return [ x( i ), y( v ) ];
			} );
			var line = smooth( pts );
			svg( 'path', { d: smooth( prev ), class: 'line line--prev' }, root );
			svg( 'path', { d: line + ' L' + pts[ n - 1 ][ 0 ] + ',' + ( pad.t + ih ) + ' L' + pts[ 0 ][ 0 ] + ',' + ( pad.t + ih ) + ' Z', fill: 'url(#swai-area)' }, root );
			var path = svg( 'path', { d: line, class: 'line' }, root );
			var len = path.getTotalLength ? path.getTotalLength() : 0;
			if ( len && ! matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
				path.style.strokeDasharray = len;
				path.style.strokeDashoffset = len;
				path.getBoundingClientRect();
				path.style.transition = 'stroke-dashoffset 1s cubic-bezier(.2,.8,.2,1)';
				path.style.strokeDashoffset = '0';
			}

			var cursor = svg( 'line', { class: 'cursor', y1: pad.t, y2: pad.t + ih, opacity: 0 }, root );
			var dot = svg( 'circle', { class: 'dot', r: 5, opacity: 0 }, root );
			var hit = svg( 'rect', { x: pad.l, y: 0, width: iw, height: H, fill: 'transparent' }, root );

			function show( evt ) {
				var rect = root.getBoundingClientRect();
				var px = ( evt.clientX - rect.left ) * ( W / rect.width );
				var rel = ( px - pad.l ) / iw;
				if ( rtl ) {
					rel = 1 - rel;
				}
				var i = Math.max( 0, Math.min( n - 1, Math.round( rel * ( n - 1 ) ) ) );
				cursor.setAttribute( 'x1', pts[ i ][ 0 ] );
				cursor.setAttribute( 'x2', pts[ i ][ 0 ] );
				cursor.setAttribute( 'opacity', 1 );
				dot.setAttribute( 'cx', pts[ i ][ 0 ] );
				dot.setAttribute( 'cy', pts[ i ][ 1 ] );
				dot.setAttribute( 'opacity', 1 );
				tip.innerHTML = '';
				var b = document.createElement( 'b' );
				b.textContent = data.labels[ i ];
				tip.appendChild( b );
				[ [ '#6d5dfc', data.i18n.clicks, data.clicks[ i ] ], [ '#c7cbe0', data.i18n.previous, data.prev[ i ] ] ].forEach( function ( r ) {
					var row = document.createElement( 'div' );
					var dotEl = document.createElement( 'i' );
					dotEl.style.background = r[ 0 ];
					row.appendChild( dotEl );
					row.appendChild( document.createTextNode( r[ 1 ] + ': ' + fmt.format( r[ 2 ] ) ) );
					tip.appendChild( row );
				} );
				var left = ( pts[ i ][ 0 ] / W ) * rect.width;
				left = Math.max( 75, Math.min( rect.width - 75, left ) );
				tip.style.left = left + 'px';
				tip.style.top = Math.max( 0, ( pts[ i ][ 1 ] / H ) * rect.height - 78 ) + 'px';
				tip.style.opacity = '1';
			}
			hit.addEventListener( 'mousemove', show );
			hit.addEventListener( 'mouseleave', function () {
				tip.style.opacity = '0';
				cursor.setAttribute( 'opacity', 0 );
				dot.setAttribute( 'opacity', 0 );
			} );
			host.insertBefore( root, tip );
		}

		draw();
		if ( window.ResizeObserver ) {
			var last = host.clientWidth;
			new ResizeObserver( function () {
				if ( Math.abs( host.clientWidth - last ) > 4 ) {
					last = host.clientWidth;
					draw();
				}
			} ).observe( host );
		}
	}

	function donut( host, items, i18n ) {
		var total = items.reduce( function ( s, it ) {
			return s + it.value;
		}, 0 );
		var ring = document.createElement( 'div' );
		ring.className = 'swai-donut__ring';
		var root = svg( 'svg', { viewBox: '0 0 120 120' }, null );
		var r = 46;
		var c = 2 * Math.PI * r;
		var offset = 0;
		svg( 'circle', { cx: 60, cy: 60, r: r, stroke: '#f0f1f7' }, root );
		var arcs = items.map( function ( it ) {
			var len = total ? ( it.value / total ) * c : 0;
			var gap = items.length > 1 ? Math.min( 2, len / 2 ) : 0;
			var arc = svg( 'circle', {
				cx: 60, cy: 60, r: r,
				stroke: it.color,
				'stroke-dasharray': Math.max( 0, len - gap ) + ' ' + ( c - Math.max( 0, len - gap ) ),
				'stroke-dashoffset': -offset,
				'stroke-linecap': 'butt'
			}, root );
			offset += len;
			return arc;
		} );
		ring.appendChild( root );
		var center = document.createElement( 'div' );
		center.className = 'swai-donut__center';
		var strong = document.createElement( 'strong' );
		strong.textContent = fmt.format( total );
		var span = document.createElement( 'span' );
		span.textContent = i18n.total;
		center.appendChild( strong );
		center.appendChild( span );
		ring.appendChild( center );

		var list = document.createElement( 'ul' );
		list.className = 'swai-donut__list';
		items.forEach( function ( it, idx ) {
			var li = document.createElement( 'li' );
			var sw = document.createElement( 'i' );
			sw.style.background = it.color;
			var name = document.createElement( 'span' );
			name.textContent = it.name;
			var val = document.createElement( 'b' );
			val.textContent = fmt.format( it.value );
			var pct = document.createElement( 'em' );
			pct.textContent = total ? Math.round( ( it.value / total ) * 100 ) + '%' : '0%';
			[ sw, name, val, pct ].forEach( function ( n ) {
				li.appendChild( n );
			} );
			li.addEventListener( 'mouseenter', function () {
				arcs.forEach( function ( a, j ) {
					a.classList.toggle( 'is-dim', j !== idx );
				} );
				strong.textContent = fmt.format( it.value );
				span.textContent = it.name;
			} );
			li.addEventListener( 'mouseleave', function () {
				arcs.forEach( function ( a ) {
					a.classList.remove( 'is-dim' );
				} );
				strong.textContent = fmt.format( total );
				span.textContent = i18n.total;
			} );
			list.appendChild( li );
		} );
		host.appendChild( ring );
		host.appendChild( list );
	}

	function bars( host, items ) {
		var max = Math.max.apply( null, items.map( function ( i ) {
			return i.value;
		} ).concat( [ 1 ] ) );
		host.className = 'swai-bars';
		items.forEach( function ( it ) {
			var wrap = document.createElement( 'div' );
			var row = document.createElement( 'div' );
			row.className = 'swai-bar__row';
			var a = document.createElement( 'span' );
			a.textContent = it.name;
			var b = document.createElement( 'span' );
			b.textContent = fmt.format( it.value );
			row.appendChild( a );
			row.appendChild( b );
			var track = document.createElement( 'div' );
			track.className = 'swai-progress';
			var fill = document.createElement( 'span' );
			fill.style.width = Math.round( ( it.value / max ) * 100 ) + '%';
			track.appendChild( fill );
			wrap.appendChild( row );
			wrap.appendChild( track );
			host.appendChild( wrap );
		} );
	}

	function spark( host, values ) {
		if ( ! values.length ) {
			return;
		}
		var W = 96;
		var H = 32;
		var max = Math.max.apply( null, values.concat( [ 1 ] ) );
		var pts = values.map( function ( v, i ) {
			var px = values.length > 1 ? ( i / ( values.length - 1 ) ) * W : W / 2;
			return [ rtl ? W - px : px, H - 3 - ( v / max ) * ( H - 6 ) ];
		} );
		var root = svg( 'svg', { viewBox: '0 0 ' + W + ' ' + H, preserveAspectRatio: 'none' } );
		var d = smooth( pts );
		svg( 'path', { d: d + ' L' + pts[ pts.length - 1 ][ 0 ] + ',' + H + ' L' + pts[ 0 ][ 0 ] + ',' + H + ' Z', fill: 'rgba(109,93,252,.12)' }, root );
		svg( 'path', { d: d, fill: 'none', stroke: '#6d5dfc', 'stroke-width': 2, 'stroke-linecap': 'round', 'vector-effect': 'non-scaling-stroke' }, root );
		host.appendChild( root );
	}

	function initDashboard() {
		var node = $( '#swai-report' );
		if ( ! node ) {
			return;
		}
		var data = JSON.parse( node.textContent );
		var area = $( '[data-chart="area"]' );
		if ( area ) {
			areaChart( area, data );
		}
		var ring = $( '[data-chart="donut"]' );
		if ( ring ) {
			donut( ring, data.services, data.i18n );
		}
		var bar = $( '[data-chart="bars"]' );
		if ( bar ) {
			bars( bar, data.modes );
		}
		$$( '[data-spark]' ).forEach( function ( el ) {
			spark( el, JSON.parse( el.getAttribute( 'data-spark' ) ) );
		} );
	}

	/* ------------------------------------------------------------------
	 * Settings
	 * ---------------------------------------------------------------- */

	function initSections() {
		var links = $$( '.swai-side__item' );
		var sections = $$( '.swai-section' );
		if ( ! links.length ) {
			return;
		}
		function open( id ) {
			if ( ! $( '#swai-section-' + id ) ) {
				id = 'general';
			}
			sections.forEach( function ( s ) {
				s.hidden = s.getAttribute( 'data-section' ) !== id;
			} );
			links.forEach( function ( l ) {
				var on = l.getAttribute( 'data-section' ) === id;
				l.classList.toggle( 'is-active', on );
				if ( on ) {
					l.setAttribute( 'aria-current', 'page' );
				} else {
					l.removeAttribute( 'aria-current' );
				}
			} );
			// Show the save bar only for sections inside the main form.
			var bar = $( '.swai-savebar' );
			if ( bar ) {
				bar.hidden = id === 'tools';
			}
		}
		links.forEach( function ( l ) {
			l.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var id = l.getAttribute( 'data-section' );
				history.replaceState( null, '', '#' + id );
				open( id );
				window.scrollTo( { top: 0, behavior: 'smooth' } );
			} );
		} );
		open( ( location.hash || '#general' ).slice( 1 ) );

		// Keep the tab on the redirect after saving.
		var form = $( '#swai-form' );
		if ( form ) {
			form.addEventListener( 'submit', function () {
				var ref = form.querySelector( 'input[name="_wp_http_referer"]' );
				if ( ref ) {
					ref.value = ref.value.split( '#' )[ 0 ] + location.hash;
				}
			} );
		}
	}

	function initDirty() {
		var form = $( '#swai-form' );
		var bar = $( '.swai-savebar' );
		if ( ! form || ! bar ) {
			return;
		}
		var dirty = false;
		form.addEventListener( 'input', mark );
		form.addEventListener( 'change', mark );
		function mark() {
			dirty = true;
			bar.classList.add( 'is-dirty' );
		}
		form.addEventListener( 'submit', function () {
			dirty = false;
		} );
		window.addEventListener( 'beforeunload', function ( e ) {
			if ( dirty ) {
				e.preventDefault();
				e.returnValue = '';
			}
		} );
	}

	function initSortable( onReorder ) {
		var list = $( '#swai-services' );
		if ( ! list ) {
			return;
		}
		var dragging = null;
		list.addEventListener( 'dragstart', function ( e ) {
			dragging = e.target.closest( '.swai-sortable__item' );
			if ( ! dragging || e.target.closest( 'input' ) ) {
				e.preventDefault();
				return;
			}
			dragging.classList.add( 'is-dragging' );
			e.dataTransfer.effectAllowed = 'move';
		} );
		list.addEventListener( 'dragover', function ( e ) {
			e.preventDefault();
			var over = e.target.closest( '.swai-sortable__item' );
			if ( ! over || over === dragging ) {
				return;
			}
			var rect = over.getBoundingClientRect();
			var after = e.clientY > rect.top + rect.height / 2;
			list.insertBefore( dragging, after ? over.nextSibling : over );
		} );
		list.addEventListener( 'dragend', function () {
			if ( dragging ) {
				dragging.classList.remove( 'is-dragging' );
				dragging = null;
				list.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				onReorder();
			}
		} );
		// Keyboard: Alt+↑/↓ on a focused row control moves the row.
		list.addEventListener( 'keydown', function ( e ) {
			if ( ! e.altKey || ( e.key !== 'ArrowUp' && e.key !== 'ArrowDown' ) ) {
				return;
			}
			var item = e.target.closest( '.swai-sortable__item' );
			var sib = e.key === 'ArrowUp' ? item.previousElementSibling : item.nextElementSibling;
			if ( sib ) {
				e.preventDefault();
				list.insertBefore( item, e.key === 'ArrowUp' ? sib : sib.nextSibling );
				e.target.focus();
				list.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				onReorder();
			}
		} );
	}

	function initPreview() {
		var form = $( '#swai-form' );
		var box = $( '#swai-preview-box .swai' );
		var page = $( '.swai-preview__page' );
		if ( ! form || ! box ) {
			return function () {};
		}
		var row = $( '.swai__row', box );

		function val( name ) {
			var el = form.querySelector( '[name="swai_settings[' + name + ']"]:checked' ) || form.querySelector( '[name="swai_settings[' + name + ']"]' );
			if ( ! el ) {
				return '';
			}
			return el.type === 'checkbox' ? el.checked : el.value;
		}

		function labelIcon() {
			var label = $( '.swai__label', box );
			if ( ! label ) {
				return;
			}
			var icon = $( '.swai__label-icon', label );
			var choice = form.querySelector( '[name="swai_settings[label_icon]"]:checked' );
			if ( ! icon ) {
				icon = document.createElement( 'span' );
				icon.className = 'swai__icon swai__label-icon';
				label.insertBefore( icon, label.firstChild );
			}
			icon.hidden = ! choice || choice.value === 'none';
			if ( choice && choice.value !== 'none' ) {
				var glyph = choice.parentNode.querySelector( '.swai-icon-picker__glyph' );
				icon.innerHTML = glyph ? glyph.innerHTML : '';
			}
			var size = val( 'label_icon_size' ) + 'px';
			icon.style.color = val( 'label_icon_color' );
			icon.style.width = size;
			icon.style.height = size;
		}

		function services() {
			var order = $$( '#swai-services .swai-sortable__item' );
			order.forEach( function ( item ) {
				var id = item.getAttribute( 'data-service' );
				var btn = box.querySelector( '.swai__btn[data-service="' + id + '"]' );
				if ( ! btn ) {
					return;
				}
				btn.hidden = ! item.querySelector( 'input[type="checkbox"]' ).checked;
				var name = item.querySelector( '.swai-sortable__name' );
				$( '.swai__name', btn ).textContent = name.value || name.placeholder;
				row.insertBefore( btn, $( '.swai__copy', row ) );
			} );
		}

		function modes() {
			var wrap = $( '.swai__modes', box );
			$$( '.swai-mode-card' ).forEach( function ( card ) {
				var toggle = card.querySelector( 'input[type="checkbox"]' );
				var label = card.querySelector( '.swai-mode-card__label' );
				var id = toggle.getAttribute( 'data-mode' );
				card.classList.toggle( 'is-off', ! toggle.checked );
				if ( ! wrap ) {
					return;
				}
				var btn = wrap.querySelector( '[data-mode="' + id + '"]' );
				if ( ! btn && toggle.checked ) {
					btn = document.createElement( 'button' );
					btn.type = 'button';
					btn.className = 'swai__mode';
					btn.setAttribute( 'data-mode', id );
					btn.setAttribute( 'aria-pressed', 'false' );
					wrap.appendChild( btn );
				}
				if ( btn ) {
					btn.hidden = ! toggle.checked;
					btn.textContent = label.value;
				}
			} );
			if ( wrap ) {
				wrap.hidden = ! val( 'show_modes' );
			}
		}

		function design() {
			var keep = box.className.split( ' ' ).filter( function ( c ) {
				return ! /^swai--(outline|filled|pill|minimal|icons|card|light|dark|auto)$/.test( c ) && c !== 'swai--brand';
			} );
			keep.push( 'swai--' + val( 'template' ), 'swai--' + val( 'theme' ) );
			if ( val( 'brand_colors' ) ) {
				keep.push( 'swai--brand' );
			}
			box.className = keep.join( ' ' );
			var map = { accent: '--swai-accent', bg: '--swai-bg', text: '--swai-text', border: '--swai-border', hover_border: '--swai-hover' };
			Object.keys( map ).forEach( function ( k ) {
				box.style.setProperty( map[ k ], val( k ) );
			} );
			box.style.setProperty( '--swai-radius', val( 'radius' ) + 'px' );
			box.style.setProperty( '--swai-justify', { start: 'flex-start', center: 'center', end: 'flex-end' }[ val( 'align' ) ] );
			var dark = val( 'theme' ) === 'dark' || ( val( 'theme' ) === 'auto' && matchMedia( '(prefers-color-scheme: dark)' ).matches );
			page.classList.toggle( 'is-dark', dark );
			// Dark theme uses its own palette; drop the light-scheme overrides.
			if ( dark ) {
				[ '--swai-bg', '--swai-text', '--swai-border', '--swai-hover' ].forEach( function ( p ) {
					box.style.removeProperty( p );
				} );
			}
			var showIcons = val( 'show_icons' ) || val( 'template' ) === 'icons';
			$$( '.swai__btn[data-service] .swai__icon', box ).forEach( function ( i ) {
				i.hidden = ! showIcons;
			} );
			var copy = $( '.swai__copy', box );
			if ( copy ) {
				copy.hidden = ! val( 'copy_button' );
			}
			var label = $( '.swai__label', box );
			if ( label ) {
				label.hidden = val( 'label' ) === '';
				$( 'span:last-child', label ).textContent = val( 'label' );
			}
		}

		function all() {
			design();
			labelIcon();
			services();
			modes();
		}

		form.addEventListener( 'input', all );
		form.addEventListener( 'change', all );
		all();
		return all;
	}

	function initFields() {
		// Range outputs and color codes.
		$$( '.swai-range input' ).forEach( function ( input ) {
			input.addEventListener( 'input', function () {
				input.nextElementSibling.textContent = input.value + 'px';
			} );
		} );
		$$( '.swai-color input' ).forEach( function ( input ) {
			input.addEventListener( 'input', function () {
				input.parentNode.querySelector( 'code' ).textContent = input.value;
				if ( input.name === 'swai_settings[label_icon_color]' ) {
					$$( '.swai-icon-picker__glyph' ).forEach( function ( g ) {
						g.style.color = input.value;
					} );
				}
			} );
		} );

		// Insert {variables} into the last focused prompt.
		var lastPrompt = null;
		document.addEventListener( 'focusin', function ( e ) {
			if ( e.target.matches( '.swai-mode-card textarea' ) ) {
				lastPrompt = e.target;
			}
		} );
		$$( '[data-insert]' ).forEach( function ( chip ) {
			chip.addEventListener( 'click', function () {
				var t = lastPrompt || $( '.swai-mode-card textarea' );
				if ( ! t ) {
					return;
				}
				var s = t.selectionStart || t.value.length;
				t.value = t.value.slice( 0, s ) + chip.getAttribute( 'data-insert' ) + t.value.slice( t.selectionEnd || s );
				t.focus();
				t.dispatchEvent( new Event( 'input', { bubbles: true } ) );
			} );
		} );

		// Media library picker for a custom label icon.
		var upload = $( '#swai-icon-upload' );
		if ( upload && window.wp && wp.media ) {
			var frame;
			upload.addEventListener( 'click', function () {
				if ( ! frame ) {
					frame = wp.media( { library: { type: 'image' }, multiple: false } );
					frame.on( 'select', function () {
						var url = frame.state().get( 'selection' ).first().get( 'url' );
						var field = $( '#swai-icon-url' );
						field.value = url;
						var glyph = $( '#swai-custom-icon' );
						glyph.textContent = '';
						var img = document.createElement( 'img' );
						img.src = url;
						img.alt = '';
						glyph.appendChild( img );
						var radio = document.querySelector( 'input[name="swai_settings[label_icon]"][value="custom"]' );
						radio.checked = true;
						radio.dispatchEvent( new Event( 'change', { bubbles: true } ) );
					} );
				}
				frame.open();
			} );
		}

		// Export settings as a JSON download.
		var exp = $( '#swai-export' );
		if ( exp ) {
			exp.addEventListener( 'click', function () {
				var blob = new Blob( [ exp.getAttribute( 'data-json' ) ], { type: 'application/json' } );
				var a = document.createElement( 'a' );
				a.href = URL.createObjectURL( blob );
				a.download = 'summarize-with-ai-settings.json';
				document.body.appendChild( a );
				a.click();
				setTimeout( function () {
					URL.revokeObjectURL( a.href );
					a.remove();
				}, 0 );
			} );
		}

		// Confirm destructive tool actions.
		$$( 'form[data-confirm]' ).forEach( function ( f ) {
			f.addEventListener( 'submit', function ( e ) {
				if ( ! window.confirm( f.getAttribute( 'data-confirm' ) ) ) {
					e.preventDefault();
				}
			} );
		} );
	}

	function boot() {
		initDashboard();
		initSections();
		initDirty();
		initFields();
		var refresh = initPreview();
		initSortable( refresh );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );

/**
 * Editor UI for the swai/summarize block. Plain ES5 + wp globals: no build step.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var be = wp.blockEditor;
	var c = wp.components;
	var ServerSideRender = wp.serverSideRender;
	var data = window.swaiBlock || { services: [], modes: [], enabled: [] };

	var templates = [
		{ value: '', label: __( 'Default (from settings)', 'summarize-with-ai' ) },
		{ value: 'outline', label: __( 'Outline', 'summarize-with-ai' ) },
		{ value: 'filled', label: __( 'Filled', 'summarize-with-ai' ) },
		{ value: 'pill', label: __( 'Pill', 'summarize-with-ai' ) },
		{ value: 'minimal', label: __( 'Minimal', 'summarize-with-ai' ) },
		{ value: 'icons', label: __( 'Icons only', 'summarize-with-ai' ) },
		{ value: 'card', label: __( 'Card', 'summarize-with-ai' ) }
	];
	var themes = [
		{ value: '', label: __( 'Default (from settings)', 'summarize-with-ai' ) },
		{ value: 'light', label: __( 'Light', 'summarize-with-ai' ) },
		{ value: 'dark', label: __( 'Dark', 'summarize-with-ai' ) },
		{ value: 'auto', label: __( 'Match visitor system', 'summarize-with-ai' ) }
	];

	wp.blocks.registerBlockType( 'swai/summarize', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			var selected = a.services && a.services.length ? a.services : data.enabled;

			function toggleService( id, on ) {
				var next = selected.filter( function ( s ) {
					return s !== id;
				} );
				if ( on ) {
					next.push( id );
				}
				set( { services: next } );
			}

			return el(
				'div',
				be.useBlockProps(),
				el(
					be.InspectorControls,
					null,
					el(
						c.PanelBody,
						{ title: __( 'AI assistants', 'summarize-with-ai' ) },
						data.services.map( function ( s ) {
							return el( c.CheckboxControl, {
								key: s.value,
								label: s.label,
								checked: selected.indexOf( s.value ) !== -1,
								onChange: function ( on ) {
									toggleService( s.value, on );
								}
							} );
						} )
					),
					el(
						c.PanelBody,
						{ title: __( 'Content', 'summarize-with-ai' ) },
						el( c.TextControl, {
							label: __( 'Label', 'summarize-with-ai' ),
							help: __( 'Leave empty to use the label from settings.', 'summarize-with-ai' ),
							value: a.label || '',
							onChange: function ( v ) {
								set( { label: v || undefined } );
							}
						} ),
						el( c.ToggleControl, {
							label: __( 'Show mode switcher', 'summarize-with-ai' ),
							checked: a.showModes,
							onChange: function ( v ) {
								set( { showModes: v } );
							}
						} ),
						el( c.SelectControl, {
							label: __( 'Default mode', 'summarize-with-ai' ),
							value: a.mode,
							options: [ { value: '', label: __( 'Default (from settings)', 'summarize-with-ai' ) } ].concat( data.modes ),
							onChange: function ( v ) {
								set( { mode: v } );
							}
						} )
					),
					el(
						c.PanelBody,
						{ title: __( 'Design', 'summarize-with-ai' ) },
						el( c.SelectControl, {
							label: __( 'Style', 'summarize-with-ai' ),
							value: a.template,
							options: templates,
							onChange: function ( v ) {
								set( { template: v } );
							}
						} ),
						el( c.SelectControl, {
							label: __( 'Color scheme', 'summarize-with-ai' ),
							value: a.theme,
							options: themes,
							onChange: function ( v ) {
								set( { theme: v } );
							}
						} )
					)
				),
				el(
					c.Disabled,
					null,
					el( ServerSideRender, {
						block: 'swai/summarize',
						attributes: a,
						urlQueryArgs: { post_id: wp.data.select( 'core/editor' ) ? wp.data.select( 'core/editor' ).getCurrentPostId() : 0 }
					} )
				)
			);
		},
		save: function () {
			return null;
		}
	} );
}( window.wp ) );

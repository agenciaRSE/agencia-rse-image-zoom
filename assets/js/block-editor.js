/**
 * OpenVault Image Zoom: "Hover zoom" switch in the sidebar of the Image block.
 * It only adds or removes a CSS class in the block's "Additional CSS class(es)".
 */
( function ( wp, config ) {
	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var cls = config.cssClass;

	function hasClass( className ) {
		return ( className || '' ).split( /\s+/ ).indexOf( cls ) !== -1;
	}

	function toggleClass( className, on ) {
		var list = ( className || '' ).split( /\s+/ ).filter( function ( c ) {
			return c && c !== cls;
		} );
		if ( on ) {
			list.push( cls );
		}
		return list.length ? list.join( ' ' ) : undefined;
	}

	var withZoomToggle = wp.compose.createHigherOrderComponent( function ( BlockEdit ) {
		return function ( props ) {
			if ( props.name !== 'core/image' ) {
				return el( BlockEdit, props );
			}
			return el(
				wp.element.Fragment,
				null,
				el( BlockEdit, props ),
				el(
					wp.blockEditor.InspectorControls,
					null,
					el(
						wp.components.PanelBody,
						{ title: __( 'Hover zoom', 'agencia-rse-image-zoom' ), initialOpen: true },
						el( wp.components.ToggleControl, {
							label: __( 'Zoom in on the image under the cursor', 'agencia-rse-image-zoom' ),
							checked: hasClass( props.attributes.className ),
							onChange: function ( on ) {
								props.setAttributes( { className: toggleClass( props.attributes.className, on ) } );
							},
							__nextHasNoMarginBottom: true
						} )
					)
				)
			);
		};
	}, 'withAgrseZoomToggle' );

	wp.hooks.addFilter( 'editor.BlockEdit', 'agencia-rse-image-zoom/toggle', withZoomToggle );
} )( window.wp, window.AgrseZoomEditor );

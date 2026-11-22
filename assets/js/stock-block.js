/* Editor registration for vectra/stock-by-warehouse. No build step: uses WordPress globals.
   The editor shows a sample; the real stock is rendered on the site by inc/stock.php. */
( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { createElement: el } = wp.element;
	const { useBlockProps } = wp.blockEditor;

	registerBlockType( 'vectra/stock-by-warehouse', {
		apiVersion: 3,
		title: 'Stock by warehouse',
		category: 'woocommerce',
		usesContext: [ 'postId', 'postType' ],
		edit: function () {
			return el( 'div', useBlockProps( { className: 'vectra-stock' } ),
				el( 'span', { className: 'vectra-stock__row is-in' }, el( 'i', { 'aria-hidden': true } ), el( 'b', null, 'CPT' ), ' In stock' ),
				el( 'span', { className: 'vectra-stock__row is-low' }, el( 'i', { 'aria-hidden': true } ), el( 'b', null, 'JHB' ), ' 2 left' )
			);
		},
		save: function () { return null; },
	} );
} )( window.wp );

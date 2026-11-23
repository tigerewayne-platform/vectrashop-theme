/* Registers "Pay on my 30-day account" with the WooCommerce block checkout. No build step needed. */
( function () {
	const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
	const { getSetting } = window.wc.wcSettings;
	const { createElement: el } = window.wp.element;
	const { decodeEntities } = window.wp.htmlEntities;
	const data = getSetting( 'vectra_account_data', {} );
	const title = decodeEntities( data.title || 'Pay on my 30-day account' );
	const Content = () => el( 'p', null, decodeEntities( data.description || '' ) );
	registerPaymentMethod( {
		name: 'vectra_account',
		label: el( 'span', null, title ),
		ariaLabel: title,
		content: el( Content ),
		edit: el( Content ),
		canMakePayment: () => true,
		supports: { features: [ 'products' ] },
	} );
} )();

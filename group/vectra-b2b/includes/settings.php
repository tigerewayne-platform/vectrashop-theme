<?php
/**
 * Settings: WooCommerce > Settings > Vectra B2B.
 *
 * @package VectraB2B
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read one setting with its default.
 */
function vectra_b2b_opt( string $key ) {
	$defaults = array(
		'hold_days'       => 4,
		'notify_email'    => get_option( 'admin_email' ),
		'ai_key'          => '',
		'ai_model'        => 'claude-opus-5-5',
		'support_price'   => 350,
		'manual_reply'    => '2 working hours',
		'quote_markup'    => 0,
	);
	$value = get_option( 'vectra_b2b_' . $key, '' );
	if ( '' === $value || null === $value ) {
		$value = $defaults[ $key ] ?? '';
	}
	// The API key can live in wp-config.php instead of the database.
	if ( 'ai_key' === $key && defined( 'VECTRA_B2B_ANTHROPIC_KEY' ) ) {
		$value = VECTRA_B2B_ANTHROPIC_KEY;
	}
	return $value;
}

add_filter( 'woocommerce_settings_tabs_array', function ( $tabs ) {
	$tabs['vectra_b2b'] = __( 'Vectra B2B', 'vectra-b2b' );
	return $tabs;
}, 50 );

function vectra_b2b_settings_fields(): array {
	return array(
		array( 'title' => __( 'Quotes', 'vectra-b2b' ), 'type' => 'title', 'id' => 'vectra_b2b_quotes' ),
		array( 'title' => __( 'Hold quotes for (working days)', 'vectra-b2b' ), 'id' => 'vectra_b2b_hold_days', 'type' => 'number', 'default' => 4, 'custom_attributes' => array( 'min' => 1, 'max' => 20 ), 'desc' => __( 'Price held and stock reserved until 17:00 on the last day.', 'vectra-b2b' ) ),
		array( 'title' => __( 'Send new quotes and RFQs to', 'vectra-b2b' ), 'id' => 'vectra_b2b_notify_email', 'type' => 'email', 'default' => get_option( 'admin_email' ) ),
		array( 'title' => __( 'Promised reply time for lines we could not match', 'vectra-b2b' ), 'id' => 'vectra_b2b_manual_reply', 'type' => 'text', 'default' => '2 working hours' ),
		array( 'title' => __( 'Managed support price per device per month (R, ex VAT)', 'vectra-b2b' ), 'id' => 'vectra_b2b_support_price', 'type' => 'number', 'default' => 350 ),
		array( 'type' => 'sectionend', 'id' => 'vectra_b2b_quotes' ),
		array( 'title' => __( 'AI reading of RFQs', 'vectra-b2b' ), 'type' => 'title', 'id' => 'vectra_b2b_ai', 'desc' => __( 'Optional. With a Claude API key the quote box can read PDF RFQs and messy lists. The AI only reads the request; prices and stock always come from your own catalogue. Without a key, pasted text is matched by keywords.', 'vectra-b2b' ) ),
		array( 'title' => __( 'Anthropic API key', 'vectra-b2b' ), 'id' => 'vectra_b2b_ai_key', 'type' => 'password', 'desc' => __( 'Or define VECTRA_B2B_ANTHROPIC_KEY in wp-config.php.', 'vectra-b2b' ) ),
		array( 'title' => __( 'Model', 'vectra-b2b' ), 'id' => 'vectra_b2b_ai_model', 'type' => 'text', 'default' => 'claude-opus-5-5' ),
		array( 'type' => 'sectionend', 'id' => 'vectra_b2b_ai' ),
	);
}

add_action( 'woocommerce_settings_tabs_vectra_b2b', function () {
	woocommerce_admin_fields( vectra_b2b_settings_fields() );
} );

add_action( 'woocommerce_update_options_vectra_b2b', function () {
	woocommerce_update_options( vectra_b2b_settings_fields() );
} );

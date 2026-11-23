<?php
/**
 * Vectra Group child theme.
 *
 * The parent (Vectra) owns tokens, type, fonts and the stock block.
 * This file only changes what is different for business buyers.
 *
 * @package VectraGroup
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', function () {
	$path = get_stylesheet_directory() . '/assets/css/group.css';
	wp_enqueue_style( 'vectra-group', get_stylesheet_directory_uri() . '/assets/css/group.css', array( 'vectra' ), wp_get_theme()->get( 'Version' ) . '.' . filemtime( $path ) );
	if ( is_front_page() ) {
		wp_enqueue_script( 'vectra-group-offices', get_stylesheet_directory_uri() . '/assets/js/offices.js', array(), wp_get_theme()->get( 'Version' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
}, 20 );

add_action( 'after_setup_theme', function () {
	add_editor_style( 'assets/css/group.css' );
}, 20 );

add_action( 'init', function () {
	register_block_pattern_category( 'vectra-group', array( 'label' => __( 'Vectra Group', 'vectra-group' ) ) );
} );

/**
 * Business prices are shown ex VAT. The parent adds "incl. VAT"; replace it.
 * Set WooCommerce > Settings > Tax > "Display prices in the shop" to "Excluding tax" to match.
 */
add_filter( 'woocommerce_get_price_suffix', function ( $suffix ) {
	$suffix = str_replace( esc_html__( 'incl. VAT', 'vectra' ), esc_html__( 'ex VAT', 'vectra-group' ), (string) $suffix );
	return $suffix;
}, 20 );

/**
 * Warn in wp-admin when the plugin that does the B2B work is missing.
 */
add_action( 'admin_notices', function () {
	if ( defined( 'VECTRA_B2B_VERSION' ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	echo '<div class="notice notice-warning"><p>' . esc_html__( 'Vectra Group theme: activate the Vectra B2B plugin. Without it there is no quote box, prices are public and checkout has no PO field.', 'vectra-group' ) . '</p></div>';
} );

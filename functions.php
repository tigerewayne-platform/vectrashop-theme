<?php
/**
 * Vectra theme setup.
 *
 * Design decisions live in theme.json, which is generated from the brand tokens.
 * Keep PHP here to things theme.json cannot express.
 *
 * @package Vectra
 */

defined( 'ABSPATH' ) || exit;

define( 'VECTRA_VERSION', wp_get_theme()->get( 'Version' ) );

require_once get_theme_file_path( 'inc/stock.php' );

add_action( 'after_setup_theme', function () {
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/vectra.css' );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'vectra', get_theme_file_uri( 'assets/css/vectra.css' ), array(), VECTRA_VERSION );
} );

/**
 * Favicons from the brand set, used only when no Site Icon is set in WordPress.
 */
add_action( 'wp_head', function () {
	if ( has_site_icon() ) {
		return;
	}
	$base = get_theme_file_uri( 'assets/logo/' );
	printf( '<link rel="icon" href="%s" sizes="any">' . "\n", esc_url( $base . 'favicon.ico' ) );
	printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( $base . 'favicon.svg' ) );
	printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( $base . 'apple-touch-icon.png' ) );
}, 5 );

/**
 * Pattern category for Vectra sections.
 */
add_action( 'init', function () {
	register_block_pattern_category( 'vectra', array( 'label' => __( 'Vectra', 'vectra' ) ) );
} );

/**
 * Prices show "incl. VAT" after the amount, per the brand rules.
 */
add_filter( 'woocommerce_get_price_suffix', function ( $suffix, $product ) {
	if ( '' !== trim( (string) $suffix ) ) {
		return $suffix;
	}
	return ' <small class="vectra-vat">' . esc_html__( 'incl. VAT', 'vectra' ) . '</small>';
}, 10, 2 );

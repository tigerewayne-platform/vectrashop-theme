<?php
/**
 * Plugin Name: Vectra B2B
 * Description: Business buying for the Vectra Group site: approved business accounts, prices behind login, RFQ-to-quote with held prices and reserved stock, PO and cost centre at checkout, 30-day account payment, saved kits and an asset register.
 * Version: 0.1.2
 * Author: The Vectra Group
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Requires Plugins: woocommerce
 * WC requires at least: 9.0
 * License: Proprietary
 * Text Domain: vectra-b2b
 *
 * @package VectraB2B
 */

defined( 'ABSPATH' ) || exit;

define( 'VECTRA_B2B_VERSION', '0.1.2' );
define( 'VECTRA_B2B_FILE', __FILE__ );
define( 'VECTRA_B2B_DIR', plugin_dir_path( __FILE__ ) );
define( 'VECTRA_B2B_URL', plugin_dir_url( __FILE__ ) );

require_once VECTRA_B2B_DIR . 'includes/settings.php';
require_once VECTRA_B2B_DIR . 'includes/accounts.php';
require_once VECTRA_B2B_DIR . 'includes/matcher.php';
require_once VECTRA_B2B_DIR . 'includes/ai.php';
require_once VECTRA_B2B_DIR . 'includes/quotes.php';
require_once VECTRA_B2B_DIR . 'includes/checkout.php';
require_once VECTRA_B2B_DIR . 'includes/my-account.php';

add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'vectra-b2b', VECTRA_B2B_URL . 'assets/b2b.css', array(), VECTRA_B2B_VERSION );
	wp_enqueue_script( 'vectra-b2b', VECTRA_B2B_URL . 'assets/b2b.js', array(), VECTRA_B2B_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
} );

/**
 * Activation: pages the site needs, the expiry job, and the account endpoints.
 */
register_activation_hook( __FILE__, function () {
	$pages = array(
		'quote'           => array( 'Your quote', '<!-- wp:shortcode -->[vectra_quote]<!-- /wp:shortcode -->' ),
		'open-an-account' => array( 'Open a business account', '<!-- wp:shortcode -->[vectra_b2b_register]<!-- /wp:shortcode -->' ),
	);
	foreach ( $pages as $slug => $page ) {
		if ( get_page_by_path( $slug ) ) {
			continue;
		}
		$id = wp_insert_post( array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_name'    => $slug,
			'post_title'   => $page[0],
			'post_content' => $page[1],
		) );
		if ( $id && 'quote' === $slug ) {
			update_post_meta( $id, '_wp_page_template', 'page-quote' );
		}
	}
	if ( ! wp_next_scheduled( 'vectra_b2b_expire_quotes' ) ) {
		wp_schedule_event( time() + 300, 'hourly', 'vectra_b2b_expire_quotes' );
	}
	vectra_b2b_register_quote_type();
	vectra_b2b_register_endpoints();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function () {
	wp_clear_scheduled_hook( 'vectra_b2b_expire_quotes' );
	flush_rewrite_rules();
} );

/**
 * Format a rand amount the Vectra way: "R14 950", no cents unless non-zero.
 */
function vectra_b2b_money( float $amount ): string {
	$cents = round( $amount * 100 ) % 100;
	return 'R' . number_format( $amount, $cents ? 2 : 0, '.', ' ' );
}

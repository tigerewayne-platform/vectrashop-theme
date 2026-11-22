<?php
/**
 * Stock by warehouse.
 *
 * The catalogue sync writes three meta fields per product:
 *   _vectra_stock_cpt  integer, units in Cape Town
 *   _vectra_stock_jhb  integer, units in Johannesburg
 *   _vectra_eta        Y-m-d, next shipment date, or empty
 * If they are missing, the block falls back to WooCommerce's own stock status.
 *
 * Brand rule: 6+ = "In stock", 1 to 5 = "N left", 0 with ETA = "Back around 14 Dec", 0 without = "Out of stock".
 *
 * @package Vectra
 */

defined( 'ABSPATH' ) || exit;

const VECTRA_LOW_STOCK = 5;

/**
 * Status for one warehouse.
 *
 * @return array{state:string,label:string}
 */
function vectra_warehouse_status( ?int $qty, string $eta ): array {
	if ( null === $qty ) {
		return array( 'state' => 'unknown', 'label' => '' );
	}
	if ( $qty > VECTRA_LOW_STOCK ) {
		return array( 'state' => 'in', 'label' => __( 'In stock', 'vectra' ) );
	}
	if ( $qty > 0 ) {
		/* translators: %d: units left */
		return array( 'state' => 'low', 'label' => sprintf( __( '%d left', 'vectra' ), $qty ) );
	}
	if ( $eta && strtotime( $eta ) > time() ) {
		/* translators: %s: date like 14 Dec */
		return array( 'state' => 'eta', 'label' => sprintf( __( 'Back around %s', 'vectra' ), wp_date( 'j M', strtotime( $eta ) ) ) );
	}
	return array( 'state' => 'out', 'label' => __( 'Out of stock', 'vectra' ) );
}

function vectra_meta_int( int $id, string $key ): ?int {
	$v = get_post_meta( $id, $key, true );
	return ( '' === $v || null === $v ) ? null : (int) $v;
}

/**
 * Render the stock-by-warehouse markup for a product.
 */
function vectra_render_stock( $attributes, $content, $block ): string {
	$product_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
	$product    = $product_id ? wc_get_product( $product_id ) : null;
	if ( ! $product ) {
		return '';
	}

	$cpt = vectra_meta_int( $product_id, '_vectra_stock_cpt' );
	$jhb = vectra_meta_int( $product_id, '_vectra_stock_jhb' );
	$eta = (string) get_post_meta( $product_id, '_vectra_eta', true );

	$rows = array();
	if ( null === $cpt && null === $jhb ) {
		// No warehouse data yet: fall back to WooCommerce stock status.
		$in     = $product->is_in_stock();
		$rows[] = array( '', $in ? 'in' : 'out', $in ? __( 'In stock', 'vectra' ) : __( 'Out of stock', 'vectra' ) );
	} else {
		foreach ( array( 'CPT' => $cpt, 'JHB' => $jhb ) as $code => $qty ) {
			$s      = vectra_warehouse_status( $qty, $eta );
			$rows[] = array( $code, $s['state'], $s['label'] );
		}
	}

	$wrapper = get_block_wrapper_attributes( array( 'class' => 'vectra-stock' ) );
	$html    = '<div ' . $wrapper . '>';
	foreach ( $rows as list( $code, $state, $label ) ) {
		if ( 'unknown' === $state ) {
			continue;
		}
		$html .= sprintf(
			'<span class="vectra-stock__row is-%1$s"><i aria-hidden="true"></i>%2$s%3$s</span>',
			esc_attr( $state ),
			$code ? '<b>' . esc_html( $code ) . '</b> ' : '',
			esc_html( $label )
		);
	}
	return $html . '</div>';
}

add_action( 'init', function () {
	wp_register_script(
		'vectra-stock-editor',
		get_theme_file_uri( 'assets/js/stock-block.js' ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor' ),
		VECTRA_VERSION,
		true
	);
	register_block_type(
		'vectra/stock-by-warehouse',
		array(
			'api_version'     => 3,
			'title'           => __( 'Stock by warehouse', 'vectra' ),
			'category'        => 'woocommerce',
			'description'     => __( 'Shows Cape Town and Johannesburg stock for a product.', 'vectra' ),
			'uses_context'    => array( 'postId', 'postType' ),
			'editor_script'   => 'vectra-stock-editor',
			'render_callback' => 'vectra_render_stock',
			'supports'        => array( 'html' => false, 'spacing' => array( 'margin' => true ) ),
		)
	);
} );

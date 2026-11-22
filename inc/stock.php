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
 * Brand rule (stock line): real counts per city, "Back in Cape Town around 14 Dec" when out with an ETA, "None in Johannesburg" otherwise.
 *
 * @package Vectra
 */

defined( 'ABSPATH' ) || exit;

const VECTRA_LOW_STOCK = 5;

/**
 * Status for one warehouse, in the brand's stock-line format.
 * "12 in Cape Town", "3 in Johannesburg", "Back in Cape Town around 14 Dec", "None in Johannesburg".
 * Counts are real; above 50 we show "50+".
 *
 * @return array{state:string,label:string}
 */
function vectra_warehouse_status( ?int $qty, string $eta, string $place = '' ): array {
	if ( null === $qty ) {
		return array( 'state' => 'unknown', 'label' => '' );
	}
	if ( $qty > 0 ) {
		$n = $qty > 50 ? '50+' : (string) $qty;
		/* translators: 1: units, 2: city */
		return array( 'state' => $qty > VECTRA_LOW_STOCK ? 'in' : 'low', 'label' => sprintf( __( '%1$s in %2$s', 'vectra' ), $n, $place ) );
	}
	if ( $eta && strtotime( $eta ) > time() ) {
		/* translators: 1: city, 2: date like 14 Dec */
		return array( 'state' => 'eta', 'label' => sprintf( __( 'Back in %1$s around %2$s', 'vectra' ), $place, wp_date( 'j M', strtotime( $eta ) ) ) );
	}
	/* translators: %s: city */
	return array( 'state' => 'out', 'label' => sprintf( __( 'None in %s', 'vectra' ), $place ) );
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
	return vectra_stock_html( (int) $product_id, get_block_wrapper_attributes( array( 'class' => 'vectra-stock' ) ) );
}

/**
 * Stock-by-warehouse markup for any product. Used by the block and by the homepage rails.
 */
function vectra_stock_html( int $product_id, string $wrapper = 'class="vectra-stock"' ): string {
	$product = $product_id ? wc_get_product( $product_id ) : null;
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
		foreach ( array( 'CPT' => array( $cpt, __( 'Cape Town', 'vectra' ) ), 'JHB' => array( $jhb, __( 'Johannesburg', 'vectra' ) ) ) as $code => list( $qty, $place ) ) {
			$s      = vectra_warehouse_status( $qty, $eta, $place );
			$rows[] = array( $code, $s['state'], $s['label'] );
		}
	}

	$html = '<div ' . $wrapper . '>';
	foreach ( $rows as list( $code, $state, $label ) ) {
		if ( 'unknown' === $state ) {
			continue;
		}
		$html .= sprintf(
			'<span class="vectra-stock__row is-%1$s" data-wh="%2$s"><i aria-hidden="true"></i>%3$s</span>',
			esc_attr( $state ),
			esc_attr( $code ),
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

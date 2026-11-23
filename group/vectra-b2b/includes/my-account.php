<?php
/**
 * My account: Quotes, Saved kits, Assets (serials and warranty dates).
 *
 * Serials: on an order item in wp-admin, add the item meta "Serial numbers" (comma-separated).
 * Warranty: product meta _vectra_warranty_months (default 12), counted from the order's completed date.
 *
 * @package VectraB2B
 */

defined( 'ABSPATH' ) || exit;

function vectra_b2b_register_endpoints() {
	add_rewrite_endpoint( 'quotes', EP_ROOT | EP_PAGES );
	add_rewrite_endpoint( 'kits', EP_ROOT | EP_PAGES );
	add_rewrite_endpoint( 'assets', EP_ROOT | EP_PAGES );
}
add_action( 'init', 'vectra_b2b_register_endpoints' );

add_filter( 'woocommerce_get_query_vars', function ( $vars ) {
	foreach ( array( 'quotes', 'kits', 'assets' ) as $e ) {
		$vars[ $e ] = $e;
	}
	return $vars;
} );

add_filter( 'woocommerce_account_menu_items', function ( $items ) {
	$new = array();
	foreach ( $items as $k => $v ) {
		if ( 'orders' === $k ) {
			$new['quotes'] = __( 'Quotes', 'vectra-b2b' );
		}
		$new[ $k ] = $v;
		if ( 'orders' === $k ) {
			$new['kits']   = __( 'Saved kits', 'vectra-b2b' );
			$new['assets'] = __( 'Assets and warranty', 'vectra-b2b' );
		}
	}
	unset( $new['downloads'] );
	return $new;
} );

/**
 * Dashboard: status, terms and balance up top.
 */
add_action( 'woocommerce_account_dashboard', function () {
	$uid    = get_current_user_id();
	$status = get_user_meta( $uid, 'vectra_b2b_status', true );
	$co     = get_user_meta( $uid, 'vectra_b2b_company', true );
	echo '<div class="vb-card vb-dash">';
	if ( $co ) {
		echo '<p class="vb-label">' . esc_html( $co ) . '</p>';
	}
	if ( 'approved' === $status ) {
		if ( vectra_b2b_has_terms( $uid ) ) {
			$limit = (float) get_user_meta( $uid, 'vectra_b2b_limit', true );
			echo '<h3>' . esc_html__( '30-day account', 'vectra-b2b' ) . '</h3><p>' . esc_html( sprintf( 'Unpaid: %s%s', vectra_b2b_money( vectra_b2b_account_balance( $uid ) ), $limit ? ' of ' . vectra_b2b_money( $limit ) . ' limit' : '' ) ) . '</p>';
		} else {
			echo '<h3>' . esc_html__( 'Business account, pay before dispatch', 'vectra-b2b' ) . '</h3><p class="vb-muted">' . esc_html__( 'Want 30-day terms? Reply to any quote email and we will send the application.', 'vectra-b2b' ) . '</p>';
		}
	} elseif ( 'pending' === $status ) {
		echo '<h3>' . esc_html__( 'We are checking your business account', 'vectra-b2b' ) . '</h3><p class="vb-muted">' . esc_html__( 'Usually within one working day. You can ask for quotes in the meantime.', 'vectra-b2b' ) . '</p>';
	} else {
		echo '<h3>' . esc_html__( 'Not a business account yet', 'vectra-b2b' ) . '</h3><p><a href="' . esc_url( home_url( '/open-an-account/' ) ) . '">' . esc_html__( 'Apply for a business account', 'vectra-b2b' ) . '</a></p>';
	}
	$fleet = get_user_meta( $uid, 'vectra_b2b_fleet', true );
	if ( $fleet ) {
		$until = get_user_meta( $uid, 'vectra_b2b_fleet_until', true );
		echo '<div class="vb-fleet"><span class="vb-label">' . esc_html__( 'Your fleet standard', 'vectra-b2b' ) . '</span><strong>' . esc_html( $fleet ) . '</strong>';
		if ( $until ) {
			echo '<span class="vb-muted">' . esc_html( sprintf( 'Locked until %s. If it changes, we tell you 60 days before.', wp_date( 'j M Y', strtotime( $until ) ) ) ) . '</span>';
		}
		echo '</div>';
	}
	echo '</div>';
} );

/* ---------- Quotes ---------- */

add_action( 'woocommerce_account_quotes_endpoint', function () {
	$uid  = get_current_user_id();
	$user = wp_get_current_user();
	$ids  = get_posts( array(
		'post_type'      => 'vectra_quote',
		'posts_per_page' => 50,
		'fields'         => 'ids',
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
			'relation' => 'OR',
			array( 'key' => '_vq_user', 'value' => $uid ),
			array( 'key' => '_vq_email', 'value' => $user->user_email ),
		),
	) );
	echo '<p><a class="vb-btn vb-btn--copper" href="' . esc_url( home_url( '/#quote' ) ) . '">' . esc_html__( 'New quote', 'vectra-b2b' ) . '</a></p>';
	if ( ! $ids ) {
		echo '<p class="vb-muted">' . esc_html__( 'No quotes yet. Paste a list on the home page and your quotes land here.', 'vectra-b2b' ) . '</p>';
		return;
	}
	echo '<div class="vb-list">';
	foreach ( $ids as $id ) {
		$lines = (array) get_post_meta( $id, '_vq_lines', true );
		$t     = vectra_b2b_quote_totals( $lines );
		$open  = vectra_b2b_quote_is_open( $id );
		$st    = get_post_meta( $id, '_vq_status', true );
		$label = $open ? '' : ( 'accepted' === $st ? __( 'Ordered', 'vectra-b2b' ) : __( 'Expired', 'vectra-b2b' ) );
		$first = $lines[0]['line'] ?? '';
		printf(
			'<a class="vb-card vb-qrow" href="%s"><span><span class="vb-sku">%s</span><strong>%s</strong><span class="vb-muted">%s</span></span><span class="vb-qrow__money"><strong>%s</strong><span class="vb-muted">ex VAT</span></span><span class="vb-qrow__time">%s</span></a>',
			esc_url( vectra_b2b_quote_url( $id ) ),
			esc_html( vectra_b2b_quote_number( $id ) ),
			esc_html( wp_trim_words( $first, 8 ) ),
			esc_html( sprintf( _n( '%d line', '%d lines', count( $lines ), 'vectra-b2b' ), count( $lines ) ) ),
			esc_html( vectra_b2b_money( $t['sub'] ) ),
			$open ? '<span class="vb-left" data-expires="' . esc_attr( (int) get_post_meta( $id, '_vq_expires', true ) * 1000 ) . '"></span>' : '<span class="vb-muted">' . esc_html( $label ) . '</span>' // phpcs:ignore WordPress.Security.EscapeOutput
		);
	}
	echo '</div>';
} );

/* ---------- Saved kits ---------- */

add_action( 'woocommerce_account_kits_endpoint', function () {
	$kits = array_values( array_filter( (array) get_user_meta( get_current_user_id(), 'vectra_b2b_kits', true ) ) );
	if ( ! $kits ) {
		echo '<p class="vb-muted">' . esc_html__( 'No kits yet. On any quote, use "Save as a kit" to keep a standard set, like a new-starter laptop, dock and headset.', 'vectra-b2b' ) . '</p>';
		return;
	}
	echo '<div class="vb-kits">';
	foreach ( $kits as $i => $kit ) {
		echo '<form class="vb-card vb-kit" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="vectra_b2b_quote_kit"><input type="hidden" name="kit" value="' . (int) $i . '">';
		wp_nonce_field( 'vectra_b2b_kit' );
		echo '<p class="vb-label">' . esc_html__( 'Saved kit', 'vectra-b2b' ) . '</p><h3>' . esc_html( $kit['name'] ) . '</h3><ul>';
		$per = 0.0;
		foreach ( $kit['lines'] as $kl ) {
			$p = wc_get_product( $kl['product_id'] );
			if ( ! $p ) {
				echo '<li class="vb-muted">' . esc_html__( 'A product no longer sold. We suggest a replacement when you quote.', 'vectra-b2b' ) . '</li>';
				continue;
			}
			$unit = (float) wc_get_price_excluding_tax( $p );
			$per += $unit * $kl['qty'];
			echo '<li><span>' . (int) $kl['qty'] . ' × ' . esc_html( $p->get_name() ) . '</span><span class="vb-muted">' . esc_html( vectra_b2b_money( $unit ) ) . '</span></li>';
		}
		echo '</ul><div class="vb-kit__foot"><label>' . esc_html__( 'How many?', 'vectra-b2b' ) . ' <input type="number" name="times" min="1" max="500" value="1"></label><span class="vb-muted">' . esc_html( sprintf( '%s per kit ex VAT today', vectra_b2b_money( $per ) ) ) . '</span>';
		echo '<button class="vb-btn vb-btn--copper" type="submit">' . esc_html__( 'Quote this kit', 'vectra-b2b' ) . '</button>';
		echo '<button class="vb-btn vb-btn--ghost" type="submit" name="delete" value="1">' . esc_html__( 'Delete', 'vectra-b2b' ) . '</button></div></form>';
	}
	echo '</div>';
} );

add_action( 'admin_post_vectra_b2b_quote_kit', function () {
	check_admin_referer( 'vectra_b2b_kit' );
	$uid  = get_current_user_id();
	$kits = array_values( array_filter( (array) get_user_meta( $uid, 'vectra_b2b_kits', true ) ) );
	$i    = absint( $_POST['kit'] ?? 0 );
	if ( ! isset( $kits[ $i ] ) ) {
		wp_safe_redirect( wc_get_account_endpoint_url( 'kits' ) );
		exit;
	}
	if ( ! empty( $_POST['delete'] ) ) {
		array_splice( $kits, $i, 1 );
		update_user_meta( $uid, 'vectra_b2b_kits', $kits );
		wp_safe_redirect( wc_get_account_endpoint_url( 'kits' ) );
		exit;
	}
	$id = vectra_b2b_quote_kit( $kits[ $i ], max( 1, min( 500, absint( $_POST['times'] ?? 1 ) ) ) );
	wp_safe_redirect( vectra_b2b_quote_url( $id ) );
	exit;
} );

/* ---------- Assets and warranty ---------- */

/**
 * One row per unit shipped to this customer.
 */
function vectra_b2b_asset_rows( int $uid ): array {
	$rows   = array();
	$orders = wc_get_orders( array( 'customer_id' => $uid, 'status' => array( 'completed' ), 'limit' => 200 ) );
	foreach ( $orders as $o ) {
		$po      = vectra_b2b_order_po( $o );
		$shipped = $o->get_date_completed() ? $o->get_date_completed()->getTimestamp() : $o->get_date_created()->getTimestamp();
		foreach ( $o->get_items() as $item ) {
			$product = $item->get_product();
			$months  = $product ? (int) $product->get_meta( '_vectra_warranty_months' ) : 0;
			$months  = $months > 0 ? $months : 12;
			$ends    = strtotime( '+' . $months . ' months', $shipped );
			$serials = array_filter( array_map( 'trim', explode( ',', (string) $item->get_meta( 'Serial numbers' ) ) ) );
			for ( $n = 0; $n < $item->get_quantity(); $n++ ) {
				$rows[] = array(
					'order'   => $o->get_order_number(),
					'po'      => $po['po'],
					'cc'      => $po['cc'],
					'item'    => $item->get_name(),
					'sku'     => $product ? $product->get_sku() : '',
					'serial'  => $serials[ $n ] ?? '',
					'shipped' => wp_date( 'Y-m-d', $shipped ),
					'ends'    => wp_date( 'Y-m-d', $ends ),
					'soon'    => $ends - time() < 60 * DAY_IN_SECONDS,
				);
			}
		}
	}
	return $rows;
}

add_action( 'woocommerce_account_assets_endpoint', function () {
	$rows = vectra_b2b_asset_rows( get_current_user_id() );
	if ( ! $rows ) {
		echo '<p class="vb-muted">' . esc_html__( 'Once an order ships, every device lands here with its serial number and warranty end date.', 'vectra-b2b' ) . '</p>';
		return;
	}
	echo '<p><a class="vb-btn vb-btn--ghost" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=vectra_b2b_assets_csv' ), 'vectra_b2b_assets_csv' ) ) . '">' . esc_html__( 'Download CSV for your asset register', 'vectra-b2b' ) . '</a></p>';
	echo '<div class="vb-tablewrap"><table class="vb-table"><thead><tr><th>Order</th><th>PO · cost centre</th><th>Item</th><th>Serial</th><th>Warranty ends</th></tr></thead><tbody>';
	foreach ( $rows as $r ) {
		printf(
			'<tr><td class="vb-sku">%s</td><td>%s</td><td>%s</td><td class="vb-sku">%s</td><td>%s</td></tr>',
			esc_html( $r['order'] ),
			esc_html( trim( $r['po'] . ( $r['cc'] ? ' · ' . $r['cc'] : '' ) ) ),
			esc_html( $r['item'] ),
			esc_html( $r['serial'] ? $r['serial'] : 'to follow' ),
			$r['soon'] ? '<span class="vb-st vb-st--low"><i></i>' . esc_html( $r['ends'] ) . '</span>' : esc_html( $r['ends'] ) // phpcs:ignore WordPress.Security.EscapeOutput
		);
	}
	echo '</tbody></table></div>';
} );

add_action( 'admin_post_vectra_b2b_assets_csv', function () {
	check_admin_referer( 'vectra_b2b_assets_csv' );
	$rows = vectra_b2b_asset_rows( get_current_user_id() );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="vectra-assets-' . wp_date( 'Y-m-d' ) . '.csv"' );
	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'Order', 'PO', 'Cost centre', 'Item', 'SKU', 'Serial', 'Shipped', 'Warranty ends' ) );
	foreach ( $rows as $r ) {
		fputcsv( $out, array( $r['order'], $r['po'], $r['cc'], $r['item'], $r['sku'], $r['serial'], $r['shipped'], $r['ends'] ) );
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
} );

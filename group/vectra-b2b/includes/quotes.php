<?php
/**
 * Quotes: RFQ box, quote page with held price and reserved stock, accept to cart, expiry.
 *
 * A quote is a private post of type vectra_quote. Meta:
 *   _vq_lines    list of lines (see vectra_b2b_build_lines())
 *   _vq_status   open | accepted | expired
 *   _vq_expires  unix time the hold ends
 *   _vq_token    secret for the emailed link
 *   _vq_user, _vq_email, _vq_company, _vq_source (ai|keywords), _vq_seconds, _vq_order
 *
 * @package VectraB2B
 */

defined( 'ABSPATH' ) || exit;

function vectra_b2b_register_quote_type() {
	register_post_type( 'vectra_quote', array(
		'labels'          => array(
			'name'          => __( 'Quotes', 'vectra-b2b' ),
			'singular_name' => __( 'Quote', 'vectra-b2b' ),
			'menu_name'     => __( 'Quotes', 'vectra-b2b' ),
			'all_items'     => __( 'Quotes', 'vectra-b2b' ),
		),
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => 'woocommerce',
		'supports'        => array( 'title' ),
		'capability_type' => 'shop_order',
		'map_meta_cap'    => true,
		'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
	) );
}
add_action( 'init', 'vectra_b2b_register_quote_type' );

function vectra_b2b_quote_number( int $id ): string {
	return 'VQ-' . ( 100000 + $id );
}

/**
 * End of the hold: N working days from $from, at 17:00 in Johannesburg. Weekends skipped.
 */
function vectra_b2b_expiry( int $from, int $days ): int {
	$tz = new DateTimeZone( 'Africa/Johannesburg' );
	$d  = ( new DateTimeImmutable( '@' . $from ) )->setTimezone( $tz );
	$added = 0;
	while ( $added < $days ) {
		$d = $d->modify( '+1 day' );
		if ( (int) $d->format( 'N' ) < 6 ) {
			++$added;
		}
	}
	return $d->setTime( 17, 0 )->getTimestamp();
}

function vectra_b2b_quote_is_open( int $id ): bool {
	return 'open' === get_post_meta( $id, '_vq_status', true ) && (int) get_post_meta( $id, '_vq_expires', true ) > time();
}

/**
 * Units reserved for a product by open quotes, per warehouse. Loaded once per request.
 */
function vectra_b2b_reserved_for( int $product_id, int $exclude_quote = 0 ): array {
	static $map = null;
	if ( null === $map ) {
		$map = array();
		$ids = get_posts( array(
			'post_type'      => 'vectra_quote',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array( 'key' => '_vq_status', 'value' => 'open' ),
				array( 'key' => '_vq_expires', 'value' => time(), 'compare' => '>', 'type' => 'NUMERIC' ),
			),
		) );
		foreach ( $ids as $qid ) {
			foreach ( (array) get_post_meta( $qid, '_vq_lines', true ) as $l ) {
				if ( empty( $l['product_id'] ) ) {
					continue;
				}
				$map[ $l['product_id'] ][ $qid ] = array( 'cpt' => (int) $l['reserve']['cpt'], 'jhb' => (int) $l['reserve']['jhb'] );
			}
		}
	}
	$out = array( 'cpt' => 0, 'jhb' => 0 );
	foreach ( $map[ $product_id ] ?? array() as $qid => $r ) {
		if ( $qid === $exclude_quote ) {
			continue;
		}
		$out['cpt'] += $r['cpt'];
		$out['jhb'] += $r['jhb'];
	}
	return $out;
}

/**
 * Turn parsed request lines into priced quote lines.
 */
function vectra_b2b_build_lines( array $parsed ): array {
	$lines = array();
	foreach ( array_slice( $parsed, 0, 60 ) as $req ) {
		$line = array(
			'line'       => $req['line'],
			'qty'        => (int) $req['qty'],
			'product_id' => 0,
			'alt'        => false,
			'reserve'    => array( 'cpt' => 0, 'jhb' => 0, 'short' => 0 ),
		);
		$m = vectra_b2b_match_line( $req['text'], (int) $req['qty'] );
		if ( $m ) {
			$p = wc_get_product( $m['product_id'] );
			$line = array_merge( $line, array(
				'product_id' => $p->get_id(),
				'name'       => $p->get_name(),
				'sku'        => $p->get_sku(),
				'unit'       => (float) wc_get_price_excluding_tax( $p ), // shown on the quote, ex VAT
				'raw'        => (float) $p->get_price(),                  // what the cart charges, as the store enters prices
				'alt'        => $m['alt'],
				'reserve'    => $m['reserve'],
				'score'      => $m['score'],
			) );
		}
		$lines[] = $line;
	}
	return $lines;
}

function vectra_b2b_quote_totals( array $lines ): array {
	$sub = 0.0;
	foreach ( $lines as $l ) {
		if ( ! empty( $l['product_id'] ) ) {
			$sub += $l['unit'] * $l['qty'];
		}
	}
	$vat = round( $sub * 0.15, 2 );
	return array( 'sub' => $sub, 'vat' => $vat, 'total' => $sub + $vat );
}

function vectra_b2b_quote_url( int $id ): string {
	$page = get_page_by_path( 'quote' );
	$base = $page ? get_permalink( $page ) : home_url( '/quote/' );
	return add_query_arg( array( 'q' => $id, 'k' => get_post_meta( $id, '_vq_token', true ) ), $base );
}

function vectra_b2b_create_quote( array $lines, array $who, string $source, float $seconds ): int {
	$id = wp_insert_post( array(
		'post_type'   => 'vectra_quote',
		'post_status' => 'publish',
		'post_title'  => 'Quote',
	) );
	wp_update_post( array( 'ID' => $id, 'post_title' => vectra_b2b_quote_number( $id ) . ' · ' . ( $who['company'] ? $who['company'] : $who['email'] ) ) );
	update_post_meta( $id, '_vq_lines', $lines );
	update_post_meta( $id, '_vq_status', 'open' );
	update_post_meta( $id, '_vq_expires', vectra_b2b_expiry( time(), (int) vectra_b2b_opt( 'hold_days' ) ) );
	update_post_meta( $id, '_vq_token', wp_generate_password( 32, false ) );
	update_post_meta( $id, '_vq_user', (int) $who['user'] );
	update_post_meta( $id, '_vq_email', $who['email'] );
	update_post_meta( $id, '_vq_company', $who['company'] );
	update_post_meta( $id, '_vq_source', $source );
	update_post_meta( $id, '_vq_seconds', round( $seconds ) );
	vectra_b2b_send_quote_emails( $id );
	return $id;
}

function vectra_b2b_send_quote_emails( int $id ) {
	$lines  = get_post_meta( $id, '_vq_lines', true );
	$t      = vectra_b2b_quote_totals( $lines );
	$exp    = wp_date( 'l j M, H:i', (int) get_post_meta( $id, '_vq_expires', true ), new DateTimeZone( 'Africa/Johannesburg' ) );
	$manual = count( array_filter( $lines, static fn( $l ) => empty( $l['product_id'] ) ) );
	$no     = vectra_b2b_quote_number( $id );
	$body   = "Your quote {$no} is ready: " . vectra_b2b_quote_url( $id ) . "\n\n"
		. 'Total ' . vectra_b2b_money( $t['sub'] ) . ' ex VAT (' . vectra_b2b_money( $t['total'] ) . " incl. VAT).\n"
		. "Prices are held and stock is reserved for you until {$exp}.\n";
	if ( $manual ) {
		$body .= "\n{$manual} line(s) need a person to check. We reply within " . vectra_b2b_opt( 'manual_reply' ) . ".\n";
	}
	$body .= "\nThe Vectra Group";
	wp_mail( get_post_meta( $id, '_vq_email', true ), "Your Vectra quote {$no}", $body );
	wp_mail(
		vectra_b2b_opt( 'notify_email' ),
		"New quote {$no}" . ( $manual ? " ({$manual} lines to check by hand)" : '' ),
		get_post_meta( $id, '_vq_company', true ) . ' <' . get_post_meta( $id, '_vq_email', true ) . ">\n" . admin_url( 'post.php?action=edit&post=' . $id ) . "\n\n" . $body
	);
}

/* ---------- The RFQ box: [vectra_rfq] ---------- */

add_shortcode( 'vectra_rfq', function () {
	$err = isset( $_GET['vb_rfq_err'] ) ? sanitize_text_field( wp_unslash( $_GET['vb_rfq_err'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$ai  = vectra_b2b_ai_enabled();
	ob_start();
	?>
	<form class="vb-rfq" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="vectra_b2b_rfq">
		<?php wp_nonce_field( 'vectra_b2b_rfq' ); ?>
		<label for="vb-rfq-text" class="vb-rfq__label"><?php esc_html_e( 'What do you need?', 'vectra-b2b' ); ?></label>
		<?php if ( $err ) : ?><p class="vb-note vb-note--error"><?php echo esc_html( $err ); ?></p><?php endif; ?>
		<textarea id="vb-rfq-text" name="rfq" rows="8" spellcheck="false" placeholder="<?php echo esc_attr( "15 x laptops, i5, 16GB RAM, 512GB SSD\n15 x USB-C docking stations\n6 x 24 inch monitors" ); ?>"></textarea>
		<div class="vb-rfq__row">
			<label class="vb-rfq__file"><input type="file" name="rfq_file" accept="<?php echo $ai ? '.pdf,.txt,.csv' : '.txt,.csv'; ?>"><span><?php echo $ai ? esc_html__( 'Upload RFQ (PDF, CSV or TXT)', 'vectra-b2b' ) : esc_html__( 'Upload a list (CSV or TXT)', 'vectra-b2b' ); ?></span></label>
		</div>
		<?php if ( ! is_user_logged_in() ) : ?>
			<div class="vb-rfq__who">
				<input type="email" name="email" required placeholder="<?php esc_attr_e( 'Work email, for your quote link', 'vectra-b2b' ); ?>" autocomplete="email" aria-label="<?php esc_attr_e( 'Work email', 'vectra-b2b' ); ?>">
				<input type="text" name="company" placeholder="<?php esc_attr_e( 'Company or school', 'vectra-b2b' ); ?>" autocomplete="organization" aria-label="<?php esc_attr_e( 'Company or school', 'vectra-b2b' ); ?>">
			</div>
		<?php endif; ?>
		<input type="text" name="website" class="vb-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
		<button class="vb-btn vb-btn--copper" type="submit"><?php esc_html_e( 'Get my quote', 'vectra-b2b' ); ?></button>
		<p class="vb-rfq__hint"><?php echo esc_html( sprintf( __( 'Prices ex VAT. Anything we cannot match is checked by a person within %s.', 'vectra-b2b' ), vectra_b2b_opt( 'manual_reply' ) ) ); ?></p>
	</form>
	<?php
	return ob_get_clean();
} );

function vectra_b2b_handle_rfq() {
	check_admin_referer( 'vectra_b2b_rfq' );
	$start = microtime( true );
	$back  = wp_get_referer() ? remove_query_arg( 'vb_rfq_err', wp_get_referer() ) : home_url( '/' );
	$fail  = static function ( string $msg ) use ( $back ) {
		wp_safe_redirect( add_query_arg( 'vb_rfq_err', rawurlencode( $msg ), $back ) . '#quote' );
		exit;
	};
	if ( ! empty( $_POST['website'] ) ) { // honeypot
		$fail( __( 'Something went wrong. Please try again.', 'vectra-b2b' ) );
	}
	// At most 10 quotes an hour per address, so nobody can reserve the warehouse.
	$ip_key = 'vb_rfq_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$count  = (int) get_transient( $ip_key );
	if ( $count >= 10 && ! current_user_can( 'manage_woocommerce' ) ) {
		$fail( __( 'That is a lot of quotes in an hour. Email quotes@vectragroup.co.za and we will help.', 'vectra-b2b' ) );
	}
	set_transient( $ip_key, $count + 1, HOUR_IN_SECONDS );

	$user  = wp_get_current_user();
	$email = $user->exists() ? $user->user_email : sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	if ( ! is_email( $email ) ) {
		$fail( __( 'Add your work email so we can send you the quote link.', 'vectra-b2b' ) );
	}
	$company = $user->exists() ? (string) get_user_meta( $user->ID, 'vectra_b2b_company', true ) : sanitize_text_field( wp_unslash( $_POST['company'] ?? '' ) );

	$text = sanitize_textarea_field( wp_unslash( $_POST['rfq'] ?? '' ) );
	$pdf  = null;
	if ( ! empty( $_FILES['rfq_file']['tmp_name'] ) && UPLOAD_ERR_OK === $_FILES['rfq_file']['error'] ) {
		$name = sanitize_file_name( wp_unslash( $_FILES['rfq_file']['name'] ) );
		$tmp  = $_FILES['rfq_file']['tmp_name']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
		if ( in_array( $ext, array( 'txt', 'csv' ), true ) ) {
			$file_text = (string) file_get_contents( $tmp, false, null, 0, 200000 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			$text     .= "\n" . str_replace( array( ',', "\t" ), ' ', sanitize_textarea_field( $file_text ) );
		} elseif ( 'pdf' === $ext && vectra_b2b_ai_enabled() ) {
			$pdf = $tmp;
		} else {
			$fail( __( 'We can read CSV and TXT lists. For a PDF or Word RFQ, paste the item list into the box.', 'vectra-b2b' ) );
		}
	}
	if ( '' === trim( $text ) && ! $pdf ) {
		$fail( __( 'Paste your list or upload your RFQ first.', 'vectra-b2b' ) );
	}

	$source = 'keywords';
	$parsed = null;
	if ( vectra_b2b_ai_enabled() ) {
		$ai = vectra_b2b_ai_read( $text, $pdf );
		if ( ! is_wp_error( $ai ) && $ai ) {
			$parsed = $ai;
			$source = 'ai';
		} elseif ( is_wp_error( $ai ) ) {
			error_log( 'Vectra B2B AI read failed: ' . $ai->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
			if ( $pdf && '' === trim( $text ) ) {
				$fail( __( 'We could not read that PDF just now. Paste the item list into the box and try again.', 'vectra-b2b' ) );
			}
		}
	}
	if ( null === $parsed ) {
		$parsed = vectra_b2b_parse_lines( $text );
	}
	if ( ! $parsed ) {
		$fail( __( 'We could not find any items in that. Put one item per line, with the quantity first.', 'vectra-b2b' ) );
	}

	$lines = vectra_b2b_build_lines( $parsed );
	$id    = vectra_b2b_create_quote( $lines, array( 'user' => $user->ID, 'email' => $email, 'company' => $company ), $source, microtime( true ) - $start );
	wp_safe_redirect( vectra_b2b_quote_url( $id ) );
	exit;
}
add_action( 'admin_post_vectra_b2b_rfq', 'vectra_b2b_handle_rfq' );
add_action( 'admin_post_nopriv_vectra_b2b_rfq', 'vectra_b2b_handle_rfq' );

/**
 * Who may see a quote: its owner, anyone with the emailed link, or staff.
 */
function vectra_b2b_can_view_quote( int $id, string $token = '' ): bool {
	if ( 'vectra_quote' !== get_post_type( $id ) ) {
		return false;
	}
	if ( current_user_can( 'manage_woocommerce' ) ) {
		return true;
	}
	$owner = (int) get_post_meta( $id, '_vq_user', true );
	if ( $owner && get_current_user_id() === $owner ) {
		return true;
	}
	return '' !== $token && hash_equals( (string) get_post_meta( $id, '_vq_token', true ), $token );
}

/* ---------- The quote page: [vectra_quote] ---------- */

function vectra_b2b_stock_label( array $l ): string {
	$r = $l['reserve'];
	if ( $r['short'] > 0 ) {
		$have = $r['cpt'] + $r['jhb'];
		return '<span class="vb-st vb-st--out"><i></i>' . esc_html( sprintf( '%d available, %d short', $have, $r['short'] ) ) . '</span>';
	}
	$parts = array();
	if ( $r['cpt'] ) {
		$parts[] = ( $r['cpt'] >= 999 ? 'Cape Town' : $r['cpt'] . ' CPT' );
	}
	if ( $r['jhb'] ) {
		$parts[] = $r['jhb'] . ' JHB';
	}
	return '<span class="vb-st vb-st--in"><i></i>' . esc_html( 'Reserved: ' . implode( ' + ', $parts ) ) . '</span>';
}

add_shortcode( 'vectra_quote', function () {
	$id    = absint( $_GET['q'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
	$token = sanitize_text_field( wp_unslash( $_GET['k'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! $id || ! vectra_b2b_can_view_quote( $id, $token ) ) {
		return '<div class="vb-note">' . esc_html__( 'We could not find that quote. Use the link in your quote email, or sign in to see your quotes.', 'vectra-b2b' ) . '</div>';
	}
	$lines   = (array) get_post_meta( $id, '_vq_lines', true );
	$t       = vectra_b2b_quote_totals( $lines );
	$expires = (int) get_post_meta( $id, '_vq_expires', true );
	$open    = vectra_b2b_quote_is_open( $id );
	$status  = get_post_meta( $id, '_vq_status', true );
	$tz      = new DateTimeZone( 'Africa/Johannesburg' );
	$matched = array_filter( $lines, static fn( $l ) => ! empty( $l['product_id'] ) );
	$alts    = array_filter( $lines, static fn( $l ) => ! empty( $l['alt'] ) );
	$manual  = count( $lines ) - count( $matched );
	$units   = array_sum( array_map( static fn( $l ) => $l['reserve']['cpt'] + $l['reserve']['jhb'], $matched ) );
	$notice  = isset( $_GET['vb_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['vb_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	ob_start();
	?>
	<div class="vb-quote">
		<?php if ( $notice ) : ?><p class="vb-note"><?php echo esc_html( $notice ); ?></p><?php endif; ?>
		<div class="vb-quote__head">
			<div>
				<p class="vb-label"><?php echo esc_html( 'Quote ' . vectra_b2b_quote_number( $id ) ); ?></p>
				<h1><?php echo 'accepted' === $status ? esc_html__( 'Quote accepted', 'vectra-b2b' ) : ( $open ? esc_html__( 'Your quote is ready', 'vectra-b2b' ) : esc_html__( 'This quote has expired', 'vectra-b2b' ) ); ?></h1>
				<p class="vb-muted"><?php echo esc_html( sprintf( '%d lines read · %d matched to stock · %d alternative%s · %d for a person to check', count( $lines ), count( $matched ), count( $alts ), 1 === count( $alts ) ? '' : 's', $manual ) ); ?></p>
			</div>
		</div>

		<div class="vb-hold vb-card">
			<div>
				<?php if ( $open ) : ?>
					<strong><?php echo esc_html( sprintf( 'Prices held and %d items reserved for you until %s, 17:00', $units, wp_date( 'D j M', $expires, $tz ) ) ); ?></strong>
					<span class="vb-muted"><?php esc_html_e( 'If the rand moves before then, the price on this quote still stands.', 'vectra-b2b' ); ?></span>
				<?php elseif ( 'accepted' === $status ) : ?>
					<strong><?php esc_html_e( 'Ordered. You will find serials and warranty dates under My account once it ships.', 'vectra-b2b' ); ?></strong>
				<?php else : ?>
					<strong><?php echo esc_html( sprintf( 'The hold ended %s. Prices and stock may have changed.', wp_date( 'D j M, H:i', $expires, $tz ) ) ); ?></strong>
				<?php endif; ?>
			</div>
			<?php if ( $open ) : ?>
				<div class="vb-clock" data-expires="<?php echo esc_attr( $expires * 1000 ); ?>" aria-label="<?php esc_attr_e( 'Time left on this quote', 'vectra-b2b' ); ?>"></div>
			<?php endif; ?>
		</div>

		<div class="vb-tablewrap">
			<table class="vb-table">
				<thead><tr><th><?php esc_html_e( 'You asked for', 'vectra-b2b' ); ?></th><th><?php esc_html_e( 'We matched', 'vectra-b2b' ); ?></th><th class="num"><?php esc_html_e( 'Qty', 'vectra-b2b' ); ?></th><th><?php esc_html_e( 'Stock', 'vectra-b2b' ); ?></th><th class="num"><?php esc_html_e( 'Unit ex VAT', 'vectra-b2b' ); ?></th><th class="num"><?php esc_html_e( 'Line ex VAT', 'vectra-b2b' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $lines as $l ) : ?>
					<tr>
						<td class="vb-req"><?php echo esc_html( $l['line'] ); ?></td>
						<?php if ( empty( $l['product_id'] ) ) : ?>
							<td><span class="vb-muted"><?php esc_html_e( 'Checking by hand', 'vectra-b2b' ); ?></span><br><span class="vb-tag"><?php echo esc_html( sprintf( __( 'A person replies within %s', 'vectra-b2b' ), vectra_b2b_opt( 'manual_reply' ) ) ); ?></span></td>
							<td class="num"><?php echo (int) $l['qty']; ?></td><td class="vb-muted">n/a</td><td class="num vb-muted">n/a</td><td class="num vb-muted">n/a</td>
						<?php else : ?>
							<td><a class="vb-pname" href="<?php echo esc_url( get_permalink( $l['product_id'] ) ); ?>"><?php echo esc_html( $l['name'] ); ?></a><?php if ( $l['sku'] ) : ?><br><span class="vb-sku"><?php echo esc_html( $l['sku'] ); ?></span><?php endif; ?>
								<?php if ( ! empty( $l['alt'] ) ) : ?><br><span class="vb-tag vb-tag--alt"><?php esc_html_e( 'Alternative: the closest match is short, this one does the same job', 'vectra-b2b' ); ?></span><?php endif; ?></td>
							<td class="num"><?php echo (int) $l['qty']; ?></td>
							<td><?php echo $open ? vectra_b2b_stock_label( $l ) : '<span class="vb-muted">' . esc_html__( 'Hold ended', 'vectra-b2b' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
							<td class="num"><?php echo esc_html( vectra_b2b_money( $l['unit'] ) ); ?></td>
							<td class="num"><strong><?php echo esc_html( vectra_b2b_money( $l['unit'] * $l['qty'] ) ); ?></strong></td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div class="vb-quote__foot">
			<div class="vb-card vb-actions">
				<?php if ( $open && $matched ) : ?>
					<?php if ( vectra_b2b_is_approved() ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="vectra_b2b_accept"><input type="hidden" name="q" value="<?php echo (int) $id; ?>"><input type="hidden" name="k" value="<?php echo esc_attr( $token ); ?>">
							<?php wp_nonce_field( 'vectra_b2b_accept_' . $id ); ?>
							<h3><?php esc_html_e( 'Approve this quote', 'vectra-b2b' ); ?></h3>
							<p class="vb-muted"><?php echo vectra_b2b_has_terms() ? esc_html__( 'You add your PO number and cost centre at checkout, then pay on your 30-day account.', 'vectra-b2b' ) : esc_html__( 'You add your PO number and cost centre at checkout, then pay by EFT or card.', 'vectra-b2b' ); ?></p>
							<button class="vb-btn vb-btn--copper" type="submit"><?php esc_html_e( 'Approve and go to checkout', 'vectra-b2b' ); ?></button>
						</form>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="vb-inline">
							<input type="hidden" name="action" value="vectra_b2b_save_kit"><input type="hidden" name="q" value="<?php echo (int) $id; ?>"><input type="hidden" name="k" value="<?php echo esc_attr( $token ); ?>">
							<?php wp_nonce_field( 'vectra_b2b_save_kit_' . $id ); ?>
							<input name="kit_name" placeholder="<?php esc_attr_e( 'Kit name, e.g. New starter', 'vectra-b2b' ); ?>" aria-label="<?php esc_attr_e( 'Kit name', 'vectra-b2b' ); ?>">
							<button class="vb-btn vb-btn--ghost" type="submit"><?php esc_html_e( 'Save as a kit', 'vectra-b2b' ); ?></button>
						</form>
					<?php elseif ( is_user_logged_in() ) : ?>
						<h3><?php esc_html_e( 'Your account is being checked', 'vectra-b2b' ); ?></h3>
						<p class="vb-muted"><?php esc_html_e( 'You can approve this quote as soon as we have approved your business account, usually within one working day. The hold keeps running.', 'vectra-b2b' ); ?></p>
					<?php else : ?>
						<h3><?php esc_html_e( 'To order, sign in or open an account', 'vectra-b2b' ); ?></h3>
						<p class="vb-muted"><?php esc_html_e( 'Business accounts order on a PO. The hold keeps running while you sign up.', 'vectra-b2b' ); ?></p>
						<p><a class="vb-btn vb-btn--copper" href="<?php echo esc_url( home_url( '/open-an-account/' ) ); ?>"><?php esc_html_e( 'Open a business account', 'vectra-b2b' ); ?></a> <a class="vb-btn vb-btn--ghost" href="<?php echo esc_url( add_query_arg( 'redirect_to', rawurlencode( vectra_b2b_quote_url( $id ) ), wc_get_page_permalink( 'myaccount' ) ) ); ?>"><?php esc_html_e( 'Sign in', 'vectra-b2b' ); ?></a></p>
					<?php endif; ?>
				<?php elseif ( 'accepted' !== $status ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="vectra_b2b_requote"><input type="hidden" name="q" value="<?php echo (int) $id; ?>"><input type="hidden" name="k" value="<?php echo esc_attr( $token ); ?>">
						<?php wp_nonce_field( 'vectra_b2b_requote_' . $id ); ?>
						<h3><?php esc_html_e( 'Reprice against today’s stock', 'vectra-b2b' ); ?></h3>
						<button class="vb-btn vb-btn--copper" type="submit"><?php esc_html_e( 'Get a fresh quote', 'vectra-b2b' ); ?></button>
					</form>
				<?php endif; ?>
				<p class="vb-muted vb-small"><?php esc_html_e( 'Need a change? Reply to the quote email or call us, and quote the number above.', 'vectra-b2b' ); ?></p>
			</div>
			<div class="vb-card vb-sum">
				<div><span class="vb-muted"><?php esc_html_e( 'Subtotal ex VAT', 'vectra-b2b' ); ?></span><span><?php echo esc_html( vectra_b2b_money( $t['sub'] ) ); ?></span></div>
				<div><span class="vb-muted"><?php esc_html_e( 'VAT 15%', 'vectra-b2b' ); ?></span><span><?php echo esc_html( vectra_b2b_money( $t['vat'] ) ); ?></span></div>
				<div class="vb-sum__total"><span><?php esc_html_e( 'Total', 'vectra-b2b' ); ?></span><span><?php echo esc_html( vectra_b2b_money( $t['total'] ) ); ?></span></div>
				<p class="vb-muted vb-small"><?php esc_html_e( 'Delivery is confirmed at checkout.', 'vectra-b2b' ); ?></p>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
} );

/* ---------- Accept, kit, reprice ---------- */

function vectra_b2b_quote_from_post( string $nonce_action ): int {
	$id    = absint( $_POST['q'] ?? 0 );
	$token = sanitize_text_field( wp_unslash( $_POST['k'] ?? '' ) );
	check_admin_referer( $nonce_action . $id );
	if ( ! vectra_b2b_can_view_quote( $id, $token ) ) {
		wp_die( esc_html__( 'You cannot use that quote.', 'vectra-b2b' ), 403 );
	}
	return $id;
}

add_action( 'admin_post_vectra_b2b_accept', function () {
	$id = vectra_b2b_quote_from_post( 'vectra_b2b_accept_' );
	if ( ! vectra_b2b_is_approved() ) {
		wp_die( esc_html__( 'Your business account needs to be approved first.', 'vectra-b2b' ), 403 );
	}
	if ( ! vectra_b2b_quote_is_open( $id ) ) {
		wp_safe_redirect( add_query_arg( 'vb_msg', rawurlencode( __( 'This quote has expired. Get a fresh quote below.', 'vectra-b2b' ) ), vectra_b2b_quote_url( $id ) ) );
		exit;
	}
	// The person who approves becomes the quote's owner.
	if ( ! (int) get_post_meta( $id, '_vq_user', true ) ) {
		update_post_meta( $id, '_vq_user', get_current_user_id() );
	}
	wc_load_cart();
	WC()->cart->empty_cart();
	foreach ( (array) get_post_meta( $id, '_vq_lines', true ) as $i => $l ) {
		if ( empty( $l['product_id'] ) ) {
			continue;
		}
		WC()->cart->add_to_cart( $l['product_id'], (int) $l['qty'], 0, array(), array( 'vectra_quote' => $id, 'vectra_line' => $i ) );
	}
	wp_safe_redirect( wc_get_checkout_url() );
	exit;
} );

add_action( 'admin_post_vectra_b2b_save_kit', function () {
	$id   = vectra_b2b_quote_from_post( 'vectra_b2b_save_kit_' );
	$name = sanitize_text_field( wp_unslash( $_POST['kit_name'] ?? '' ) );
	$name = '' !== $name ? $name : vectra_b2b_quote_number( $id );
	$kit  = array( 'name' => $name, 'lines' => array() );
	foreach ( (array) get_post_meta( $id, '_vq_lines', true ) as $l ) {
		if ( ! empty( $l['product_id'] ) ) {
			$kit['lines'][] = array( 'product_id' => (int) $l['product_id'], 'qty' => (int) $l['qty'] );
		}
	}
	$kits   = (array) get_user_meta( get_current_user_id(), 'vectra_b2b_kits', true );
	$kits   = array_values( array_filter( $kits ) );
	$kits[] = $kit;
	update_user_meta( get_current_user_id(), 'vectra_b2b_kits', $kits );
	wp_safe_redirect( add_query_arg( 'vb_msg', rawurlencode( sprintf( __( 'Saved as the kit "%s". Find it under My account, Saved kits.', 'vectra-b2b' ), $name ) ), vectra_b2b_quote_url( $id ) ) );
	exit;
} );

add_action( 'admin_post_vectra_b2b_requote', 'vectra_b2b_handle_requote' );
add_action( 'admin_post_nopriv_vectra_b2b_requote', 'vectra_b2b_handle_requote' );
function vectra_b2b_handle_requote() {
	$id     = vectra_b2b_quote_from_post( 'vectra_b2b_requote_' );
	$parsed = array();
	foreach ( (array) get_post_meta( $id, '_vq_lines', true ) as $l ) {
		$parsed[] = array( 'line' => $l['line'], 'qty' => (int) $l['qty'], 'text' => ! empty( $l['name'] ) ? $l['name'] : $l['line'] );
	}
	$new = vectra_b2b_create_quote( vectra_b2b_build_lines( $parsed ), array(
		'user'    => (int) get_post_meta( $id, '_vq_user', true ),
		'email'   => (string) get_post_meta( $id, '_vq_email', true ),
		'company' => (string) get_post_meta( $id, '_vq_company', true ),
	), 'requote', 0 );
	wp_safe_redirect( vectra_b2b_quote_url( $new ) );
	exit;
}

/**
 * Quote a saved kit: used by My account.
 */
function vectra_b2b_quote_kit( array $kit, int $multiplier ): int {
	$parsed = array();
	foreach ( $kit['lines'] as $kl ) {
		$p = wc_get_product( $kl['product_id'] );
		if ( $p ) {
			$parsed[] = array( 'line' => $kit['name'] . ': ' . $p->get_name(), 'qty' => $kl['qty'] * $multiplier, 'text' => $p->get_name() );
		}
	}
	$user = wp_get_current_user();
	return vectra_b2b_create_quote( vectra_b2b_build_lines( $parsed ), array( 'user' => $user->ID, 'email' => $user->user_email, 'company' => (string) get_user_meta( $user->ID, 'vectra_b2b_company', true ) ), 'kit', 0 );
}

/* ---------- Held prices in the cart ---------- */

add_action( 'woocommerce_before_calculate_totals', function ( $cart ) {
	foreach ( $cart->get_cart() as $item ) {
		if ( empty( $item['vectra_quote'] ) ) {
			continue;
		}
		$qid   = (int) $item['vectra_quote'];
		$lines = (array) get_post_meta( $qid, '_vq_lines', true );
		$l     = $lines[ $item['vectra_line'] ] ?? null;
		if ( $l && vectra_b2b_quote_is_open( $qid ) && (int) $l['product_id'] === (int) $item['data']->get_id() ) {
			$item['data']->set_price( $l['raw'] );
		}
	}
}, 20 );

add_action( 'woocommerce_checkout_create_order_line_item', function ( $order_item, $key, $values ) {
	if ( ! empty( $values['vectra_quote'] ) ) {
		$order_item->add_meta_data( '_vectra_quote', (int) $values['vectra_quote'], true );
	}
}, 10, 3 );

function vectra_b2b_mark_quotes_accepted( $order ) {
	$order = is_numeric( $order ) ? wc_get_order( $order ) : $order;
	if ( ! $order ) {
		return;
	}
	foreach ( $order->get_items() as $item ) {
		$qid = (int) $item->get_meta( '_vectra_quote' );
		if ( $qid && 'accepted' !== get_post_meta( $qid, '_vq_status', true ) ) {
			update_post_meta( $qid, '_vq_status', 'accepted' );
			update_post_meta( $qid, '_vq_order', $order->get_id() );
		}
	}
}
add_action( 'woocommerce_checkout_order_processed', 'vectra_b2b_mark_quotes_accepted', 20 );
add_action( 'woocommerce_store_api_checkout_order_processed', 'vectra_b2b_mark_quotes_accepted', 20 );

/* ---------- Expiry ---------- */

add_action( 'vectra_b2b_expire_quotes', function () {
	$ids = get_posts( array(
		'post_type'      => 'vectra_quote',
		'posts_per_page' => 200,
		'fields'         => 'ids',
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array( 'key' => '_vq_status', 'value' => 'open' ),
			array( 'key' => '_vq_expires', 'value' => time(), 'compare' => '<=', 'type' => 'NUMERIC' ),
		),
	) );
	foreach ( $ids as $id ) {
		update_post_meta( $id, '_vq_status', 'expired' );
	}
} );

/* ---------- Admin list and detail ---------- */

add_filter( 'manage_vectra_quote_posts_columns', function () {
	return array( 'cb' => '<input type="checkbox">', 'title' => __( 'Quote', 'vectra-b2b' ), 'vq_total' => __( 'Ex VAT', 'vectra-b2b' ), 'vq_status' => __( 'Status', 'vectra-b2b' ), 'vq_manual' => __( 'To check by hand', 'vectra-b2b' ), 'date' => __( 'Created', 'vectra-b2b' ) );
} );
add_action( 'manage_vectra_quote_posts_custom_column', function ( $col, $id ) {
	$lines = (array) get_post_meta( $id, '_vq_lines', true );
	if ( 'vq_total' === $col ) {
		echo esc_html( vectra_b2b_money( vectra_b2b_quote_totals( $lines )['sub'] ) );
	} elseif ( 'vq_status' === $col ) {
		$s = vectra_b2b_quote_is_open( $id ) ? 'open until ' . wp_date( 'D j M H:i', (int) get_post_meta( $id, '_vq_expires', true ) ) : get_post_meta( $id, '_vq_status', true );
		echo esc_html( 'open' === get_post_meta( $id, '_vq_status', true ) && ! vectra_b2b_quote_is_open( $id ) ? 'expired' : $s );
	} elseif ( 'vq_manual' === $col ) {
		$n = count( array_filter( $lines, static fn( $l ) => empty( $l['product_id'] ) ) );
		echo $n ? '<strong>' . (int) $n . '</strong>' : '0';
	}
}, 10, 2 );

add_action( 'add_meta_boxes_vectra_quote', function () {
	add_meta_box( 'vq_lines', __( 'Lines', 'vectra-b2b' ), function ( $post ) {
		echo '<p>' . esc_html( get_post_meta( $post->ID, '_vq_company', true ) . ' · ' . get_post_meta( $post->ID, '_vq_email', true ) . ' · read by ' . get_post_meta( $post->ID, '_vq_source', true ) ) . ' · <a href="' . esc_url( vectra_b2b_quote_url( $post->ID ) ) . '" target="_blank">' . esc_html__( 'Open the customer view', 'vectra-b2b' ) . '</a></p>';
		echo '<table class="widefat striped"><thead><tr><th>Asked for</th><th>Matched</th><th>Qty</th><th>Reserved</th><th>Unit ex VAT</th></tr></thead><tbody>';
		foreach ( (array) get_post_meta( $post->ID, '_vq_lines', true ) as $l ) {
			printf(
				'<tr><td>%s</td><td>%s</td><td>%d</td><td>%s</td><td>%s</td></tr>',
				esc_html( $l['line'] ),
				empty( $l['product_id'] ) ? '<strong>' . esc_html__( 'No match: reply by hand', 'vectra-b2b' ) . '</strong>' : esc_html( $l['name'] . ( ! empty( $l['alt'] ) ? ' (alternative)' : '' ) ),
				(int) $l['qty'],
				esc_html( sprintf( 'CPT %d · JHB %d%s', $l['reserve']['cpt'], $l['reserve']['jhb'], $l['reserve']['short'] ? ' · short ' . $l['reserve']['short'] : '' ) ),
				empty( $l['product_id'] ) ? '' : esc_html( vectra_b2b_money( $l['unit'] ) )
			);
		}
		echo '</tbody></table>';
	}, 'vectra_quote', 'normal', 'high' );
} );

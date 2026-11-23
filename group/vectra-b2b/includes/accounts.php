<?php
/**
 * Business accounts: sign-up, approval, payment terms and prices behind login.
 *
 * User meta:
 *   vectra_b2b_status   pending | approved | declined
 *   vectra_b2b_terms    eft | 30day
 *   vectra_b2b_limit    credit limit in rand (30-day accounts)
 *   vectra_b2b_company, vectra_b2b_reg_no, vectra_b2b_vat_no, vectra_b2b_staff, vectra_b2b_sector
 *
 * @package VectraB2B
 */

defined( 'ABSPATH' ) || exit;

function vectra_b2b_is_approved( ?int $user_id = null ): bool {
	$user_id = $user_id ?? get_current_user_id();
	if ( ! $user_id ) {
		return false;
	}
	if ( user_can( $user_id, 'manage_woocommerce' ) ) {
		return true;
	}
	return 'approved' === get_user_meta( $user_id, 'vectra_b2b_status', true );
}

function vectra_b2b_has_terms( ?int $user_id = null ): bool {
	$user_id = $user_id ?? get_current_user_id();
	return vectra_b2b_is_approved( $user_id ) && '30day' === get_user_meta( $user_id, 'vectra_b2b_terms', true );
}

/* ---------- Prices behind login ---------- */

add_filter( 'woocommerce_get_price_html', function ( $html, $product ) {
	if ( vectra_b2b_is_approved() || is_admin() ) {
		return $html;
	}
	$msg = is_user_logged_in()
		? __( 'Price shows once your account is approved', 'vectra-b2b' )
		: __( 'Sign in for your business price', 'vectra-b2b' );
	$url = is_user_logged_in() ? wc_get_account_endpoint_url( 'dashboard' ) : wc_get_page_permalink( 'myaccount' );
	return '<a class="vb-price-gate" href="' . esc_url( $url ) . '">' . esc_html( $msg ) . '</a>';
}, 50, 2 );

add_filter( 'woocommerce_is_purchasable', function ( $purchasable ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $purchasable;
	}
	return $purchasable && vectra_b2b_is_approved();
}, 50 );

/**
 * The block-based shop reads prices from the Store API. Strip them there too.
 */
add_filter( 'rest_post_dispatch', function ( $response, $server, $request ) {
	if ( vectra_b2b_is_approved() || ! str_starts_with( $request->get_route(), '/wc/store' ) || ! str_contains( $request->get_route(), '/products' ) ) {
		return $response;
	}
	$data  = $response->get_data();
	$strip = static function ( $item ) {
		if ( is_array( $item ) ) {
			unset( $item['prices'] );
			$item['price_html']     = '';
			$item['is_purchasable'] = false;
		}
		return $item;
	};
	$data = ( isset( $data['id'] ) ) ? $strip( $data ) : array_map( $strip, (array) $data );
	$response->set_data( $data );
	return $response;
}, 10, 3 );

/**
 * Product structured data (JSON-LD) carries offers.price straight from get_price(),
 * which would put trade prices in the page source for guests and search engines.
 */
add_filter( 'woocommerce_structured_data_product', function ( $markup ) {
	if ( ! vectra_b2b_is_approved() && is_array( $markup ) ) {
		unset( $markup['offers'] );
	}
	return $markup;
}, 50 );

/* ---------- Sign-up form: [vectra_b2b_register] ---------- */

add_shortcode( 'vectra_b2b_register', function () {
	if ( is_user_logged_in() ) {
		$status = get_user_meta( get_current_user_id(), 'vectra_b2b_status', true );
		if ( 'approved' === $status || vectra_b2b_is_approved() ) {
			return '<div class="vb-note">' . esc_html__( 'Your business account is approved.', 'vectra-b2b' ) . ' <a href="' . esc_url( wc_get_account_endpoint_url( 'quotes' ) ) . '">' . esc_html__( 'Go to your quotes', 'vectra-b2b' ) . '</a></div>';
		}
		return '<div class="vb-note">' . esc_html__( 'We have your application. We check every business by hand and reply within one working day.', 'vectra-b2b' ) . '</div>';
	}
	$done = isset( $_GET['vb_applied'] ); // phpcs:ignore WordPress.Security.NonceVerification
	$err  = isset( $_GET['vb_err'] ) ? sanitize_text_field( wp_unslash( $_GET['vb_err'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	ob_start();
	if ( $done ) {
		echo '<div class="vb-note">' . esc_html__( 'Application received. Check your email to set a password. We check every business by hand and reply within one working day. You can ask for quotes in the meantime.', 'vectra-b2b' ) . '</div>';
		return ob_get_clean();
	}
	if ( $err ) {
		echo '<div class="vb-note vb-note--error">' . esc_html( $err ) . '</div>';
	}
	?>
	<form class="vb-form vb-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="vectra_b2b_register">
		<?php wp_nonce_field( 'vectra_b2b_register' ); ?>
		<p class="vb-muted"><?php esc_html_e( 'Approved accounts see their business prices, can order on a PO and can apply for 30-day terms.', 'vectra-b2b' ); ?></p>
		<div class="vb-grid2">
			<label><span><?php esc_html_e( 'Company name', 'vectra-b2b' ); ?></span><input name="company" required autocomplete="organization"></label>
			<label><span><?php esc_html_e( 'Registration number', 'vectra-b2b' ); ?></span><input name="reg_no" placeholder="2019/123456/07"></label>
			<label><span><?php esc_html_e( 'VAT number', 'vectra-b2b' ); ?></span><input name="vat_no" placeholder="4XXXXXXXXX"></label>
			<label><span><?php esc_html_e( 'Type of organisation', 'vectra-b2b' ); ?></span>
				<select name="sector"><option value="office"><?php esc_html_e( 'Business / office', 'vectra-b2b' ); ?></option><option value="government"><?php esc_html_e( 'Government or parastatal (Zimbabwe)', 'vectra-b2b' ); ?></option><option value="school"><?php esc_html_e( 'School', 'vectra-b2b' ); ?></option><option value="partner"><?php esc_html_e( 'IT service provider', 'vectra-b2b' ); ?></option><option value="other"><?php esc_html_e( 'Other', 'vectra-b2b' ); ?></option></select></label>
			<label><span><?php esc_html_e( 'Number of staff', 'vectra-b2b' ); ?></span>
				<select name="staff"><option selected>1-9</option><option>10-49</option><option>50-200</option><option>200+</option></select></label>
			<label><span><?php esc_html_e( 'Your name', 'vectra-b2b' ); ?></span><input name="contact" required autocomplete="name"></label>
			<label><span><?php esc_html_e( 'Work email', 'vectra-b2b' ); ?></span><input type="email" name="email" required autocomplete="email"></label>
			<label><span><?php esc_html_e( 'Phone', 'vectra-b2b' ); ?></span><input name="phone" autocomplete="tel"></label>
		</div>
		<label class="vb-check"><input type="checkbox" name="want_terms" value="1"> <?php esc_html_e( 'We would like to apply for a 30-day account (we will ask for trade references)', 'vectra-b2b' ); ?></label>
		<button class="vb-btn vb-btn--copper" type="submit"><?php esc_html_e( 'Apply for an account', 'vectra-b2b' ); ?></button>
	</form>
	<?php
	return ob_get_clean();
} );

function vectra_b2b_handle_register() {
	check_admin_referer( 'vectra_b2b_register' );
	$back  = wp_get_referer() ? wp_get_referer() : home_url( '/open-an-account/' );
	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	if ( ! is_email( $email ) ) {
		wp_safe_redirect( add_query_arg( 'vb_err', rawurlencode( __( 'That email address does not look right.', 'vectra-b2b' ) ), $back ) );
		exit;
	}
	if ( email_exists( $email ) ) {
		wp_safe_redirect( add_query_arg( 'vb_err', rawurlencode( __( 'There is already an account for that email. Sign in instead, or reset your password.', 'vectra-b2b' ) ), $back ) );
		exit;
	}
	$contact = sanitize_text_field( wp_unslash( $_POST['contact'] ?? '' ) );
	$user_id = wc_create_new_customer( $email, '', wp_generate_password( 24 ), array( 'first_name' => $contact ) );
	if ( is_wp_error( $user_id ) ) {
		wp_safe_redirect( add_query_arg( 'vb_err', rawurlencode( $user_id->get_error_message() ), $back ) );
		exit;
	}
	$fields = array( 'company', 'reg_no', 'vat_no', 'sector', 'staff', 'phone' );
	foreach ( $fields as $f ) {
		update_user_meta( $user_id, 'vectra_b2b_' . $f, sanitize_text_field( wp_unslash( $_POST[ $f ] ?? '' ) ) );
	}
	update_user_meta( $user_id, 'billing_company', sanitize_text_field( wp_unslash( $_POST['company'] ?? '' ) ) );
	update_user_meta( $user_id, 'billing_phone', sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ) );
	update_user_meta( $user_id, 'vectra_b2b_status', 'pending' );
	update_user_meta( $user_id, 'vectra_b2b_terms', 'eft' );
	update_user_meta( $user_id, 'vectra_b2b_wants_terms', empty( $_POST['want_terms'] ) ? '' : '1' );

	wp_new_user_notification( $user_id, null, 'user' );
	wp_mail(
		vectra_b2b_opt( 'notify_email' ),
		sprintf( 'New business account to approve: %s', get_user_meta( $user_id, 'vectra_b2b_company', true ) ),
		sprintf( "%s (%s) applied for a business account.\nStaff: %s · Type: %s · Wants 30-day terms: %s\n\nApprove it here: %s", $contact, $email, get_user_meta( $user_id, 'vectra_b2b_staff', true ), get_user_meta( $user_id, 'vectra_b2b_sector', true ), empty( $_POST['want_terms'] ) ? 'no' : 'yes', admin_url( 'user-edit.php?user_id=' . $user_id . '#vectra-b2b' ) )
	);
	wc_set_customer_auth_cookie( $user_id );
	wp_safe_redirect( add_query_arg( 'vb_applied', '1', $back ) );
	exit;
}
add_action( 'admin_post_nopriv_vectra_b2b_register', 'vectra_b2b_handle_register' );
add_action( 'admin_post_vectra_b2b_register', 'vectra_b2b_handle_register' );

/* ---------- Admin: approve accounts on the user screen ---------- */

function vectra_b2b_user_fields( $user ) {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	$m = static fn( $k ) => get_user_meta( $user->ID, 'vectra_b2b_' . $k, true );
	?>
	<h2 id="vectra-b2b"><?php esc_html_e( 'Vectra business account', 'vectra-b2b' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr><th><?php esc_html_e( 'Company', 'vectra-b2b' ); ?></th><td><?php echo esc_html( $m( 'company' ) ); ?> · Reg <?php echo esc_html( $m( 'reg_no' ) ); ?> · VAT <?php echo esc_html( $m( 'vat_no' ) ); ?> · <?php echo esc_html( $m( 'staff' ) ); ?> staff · <?php echo esc_html( $m( 'sector' ) ); ?><?php echo $m( 'wants_terms' ) ? ' · <strong>' . esc_html__( 'asked for 30-day terms', 'vectra-b2b' ) . '</strong>' : ''; ?></td></tr>
		<tr><th><label for="vectra_b2b_status"><?php esc_html_e( 'Status', 'vectra-b2b' ); ?></label></th><td>
			<select name="vectra_b2b_status" id="vectra_b2b_status">
				<?php foreach ( array( '' => '(not a business account)', 'pending' => 'Pending', 'approved' => 'Approved', 'declined' => 'Declined' ) as $v => $l ) : ?>
					<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $m( 'status' ), $v ); ?>><?php echo esc_html( $l ); ?></option>
				<?php endforeach; ?>
			</select> <span class="description"><?php esc_html_e( 'Approving emails the customer.', 'vectra-b2b' ); ?></span></td></tr>
		<tr><th><label for="vectra_b2b_terms"><?php esc_html_e( 'Payment terms', 'vectra-b2b' ); ?></label></th><td>
			<select name="vectra_b2b_terms" id="vectra_b2b_terms">
				<option value="eft" <?php selected( $m( 'terms' ), 'eft' ); ?>><?php esc_html_e( 'Pay before dispatch (EFT or card)', 'vectra-b2b' ); ?></option>
				<option value="30day" <?php selected( $m( 'terms' ), '30day' ); ?>><?php esc_html_e( '30-day account', 'vectra-b2b' ); ?></option>
			</select></td></tr>
		<tr><th><label for="vectra_b2b_limit"><?php esc_html_e( 'Credit limit (R)', 'vectra-b2b' ); ?></label></th><td><input type="number" min="0" step="1000" name="vectra_b2b_limit" id="vectra_b2b_limit" value="<?php echo esc_attr( $m( 'limit' ) ); ?>"> <span class="description"><?php esc_html_e( 'Unpaid account orders plus the new order may not go over this.', 'vectra-b2b' ); ?></span></td></tr>
		<tr><th><label for="vectra_b2b_fleet"><?php esc_html_e( 'Fleet standard laptop', 'vectra-b2b' ); ?></label></th><td><input type="text" name="vectra_b2b_fleet" id="vectra_b2b_fleet" class="regular-text" value="<?php echo esc_attr( $m( 'fleet' ) ); ?>" placeholder="HP ProBook 450 G10 · i5 · 16GB · 512GB"> <input type="date" name="vectra_b2b_fleet_until" value="<?php echo esc_attr( $m( 'fleet_until' ) ); ?>"> <span class="description"><?php esc_html_e( 'Shown in their account as the model we keep supplying until that date.', 'vectra-b2b' ); ?></span></td></tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'vectra_b2b_user_fields' );
add_action( 'edit_user_profile', 'vectra_b2b_user_fields' );

function vectra_b2b_save_user_fields( $user_id ) {
	if ( ! current_user_can( 'manage_woocommerce' ) || ! isset( $_POST['vectra_b2b_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- core verifies the profile nonce.
		return;
	}
	$old = get_user_meta( $user_id, 'vectra_b2b_status', true );
	$new = sanitize_key( wp_unslash( $_POST['vectra_b2b_status'] ) );
	update_user_meta( $user_id, 'vectra_b2b_status', $new );
	update_user_meta( $user_id, 'vectra_b2b_terms', '30day' === ( $_POST['vectra_b2b_terms'] ?? '' ) ? '30day' : 'eft' );
	update_user_meta( $user_id, 'vectra_b2b_limit', absint( $_POST['vectra_b2b_limit'] ?? 0 ) );
	update_user_meta( $user_id, 'vectra_b2b_fleet', sanitize_text_field( wp_unslash( $_POST['vectra_b2b_fleet'] ?? '' ) ) );
	update_user_meta( $user_id, 'vectra_b2b_fleet_until', sanitize_text_field( wp_unslash( $_POST['vectra_b2b_fleet_until'] ?? '' ) ) );
	if ( 'approved' === $new && 'approved' !== $old ) {
		$user = get_userdata( $user_id );
		wp_mail( $user->user_email, 'Your Vectra business account is approved', "Your account is approved. Sign in to see your business prices and order on a PO:\n" . wc_get_page_permalink( 'myaccount' ) . "\n\nThe Vectra Group" );
	}
}
add_action( 'personal_options_update', 'vectra_b2b_save_user_fields' );
add_action( 'edit_user_profile_update', 'vectra_b2b_save_user_fields' );

add_filter( 'manage_users_columns', function ( $cols ) {
	$cols['vectra_b2b'] = __( 'Business account', 'vectra-b2b' );
	return $cols;
} );
add_filter( 'manage_users_custom_column', function ( $out, $col, $user_id ) {
	if ( 'vectra_b2b' !== $col ) {
		return $out;
	}
	$s = get_user_meta( $user_id, 'vectra_b2b_status', true );
	if ( ! $s ) {
		return '';
	}
	$t = '30day' === get_user_meta( $user_id, 'vectra_b2b_terms', true ) ? ' · 30-day' : '';
	return esc_html( ucfirst( $s ) . $t . ' · ' . get_user_meta( $user_id, 'vectra_b2b_company', true ) );
}, 10, 3 );

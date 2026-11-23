<?php
/**
 * Checkout: PO number and cost centre, and a "30-day account" payment method for approved accounts.
 *
 * @package VectraB2B
 */

defined( 'ABSPATH' ) || exit;

/* ---------- PO number and cost centre ---------- */

/**
 * Block checkout (WooCommerce 8.9+): additional fields API.
 */
add_action( 'woocommerce_init', function () {
	if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
		return;
	}
	woocommerce_register_additional_checkout_field( array(
		'id'       => 'vectra/po-number',
		'label'    => __( 'PO number', 'vectra-b2b' ),
		'location' => 'order',
		'type'     => 'text',
		'required' => false,
	) );
	woocommerce_register_additional_checkout_field( array(
		'id'       => 'vectra/cost-centre',
		'label'    => __( 'Cost centre (optional)', 'vectra-b2b' ),
		'location' => 'order',
		'type'     => 'text',
		'required' => false,
	) );
} );

/**
 * Classic checkout fallback.
 */
add_filter( 'woocommerce_checkout_fields', function ( $fields ) {
	$fields['order']['vectra_po']          = array( 'label' => __( 'PO number', 'vectra-b2b' ), 'required' => false, 'priority' => 5 );
	$fields['order']['vectra_cost_centre'] = array( 'label' => __( 'Cost centre (optional)', 'vectra-b2b' ), 'required' => false, 'priority' => 6 );
	return $fields;
} );

add_action( 'woocommerce_checkout_create_order', function ( $order, $data ) {
	if ( ! empty( $data['vectra_po'] ) ) {
		$order->update_meta_data( '_vectra_po', sanitize_text_field( $data['vectra_po'] ) );
	}
	if ( ! empty( $data['vectra_cost_centre'] ) ) {
		$order->update_meta_data( '_vectra_cost_centre', sanitize_text_field( $data['vectra_cost_centre'] ) );
	}
}, 10, 2 );

/**
 * One place to read PO and cost centre, whichever checkout saved them.
 */
function vectra_b2b_order_po( WC_Order $order ): array {
	$po = $order->get_meta( '_vectra_po' );
	$cc = $order->get_meta( '_vectra_cost_centre' );
	if ( ! $po ) {
		$po = $order->get_meta( '_wc_other/vectra/po-number' );
	}
	if ( ! $cc ) {
		$cc = $order->get_meta( '_wc_other/vectra/cost-centre' );
	}
	return array( 'po' => (string) $po, 'cc' => (string) $cc );
}

/**
 * A 30-day account order must carry a PO: procurement will ask for it, and so will we when chasing payment.
 */
add_action( 'woocommerce_after_checkout_validation', function ( $data, $errors ) {
	if ( 'vectra_account' === ( $data['payment_method'] ?? '' ) && empty( $data['vectra_po'] ) ) {
		$errors->add( 'vectra_po', __( 'Add your PO number to pay on account.', 'vectra-b2b' ) );
	}
}, 10, 2 );
/* Show PO and cost centre in admin, emails and the customer's order view. */
add_action( 'woocommerce_admin_order_data_after_billing_address', function ( $order ) {
	$p = vectra_b2b_order_po( $order );
	if ( $p['po'] || $p['cc'] ) {
		echo '<p><strong>' . esc_html__( 'PO', 'vectra-b2b' ) . ':</strong> ' . esc_html( $p['po'] ) . '<br><strong>' . esc_html__( 'Cost centre', 'vectra-b2b' ) . ':</strong> ' . esc_html( $p['cc'] ) . '</p>';
	}
} );
add_filter( 'woocommerce_email_order_meta_fields', function ( $fields, $sent_to_admin, $order ) {
	$p = vectra_b2b_order_po( $order );
	if ( $p['po'] ) {
		$fields['vectra_po'] = array( 'label' => __( 'PO number', 'vectra-b2b' ), 'value' => $p['po'] );
	}
	if ( $p['cc'] ) {
		$fields['vectra_cc'] = array( 'label' => __( 'Cost centre', 'vectra-b2b' ), 'value' => $p['cc'] );
	}
	return $fields;
}, 10, 3 );

/* ---------- Pay on 30-day account ---------- */

add_action( 'plugins_loaded', function () {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	class Vectra_B2B_Account_Gateway extends WC_Payment_Gateway {
		public function __construct() {
			$this->id                 = 'vectra_account';
			$this->method_title       = __( '30-day account', 'vectra-b2b' );
			$this->method_description = __( 'Approved business accounts with 30-day terms pay by invoice. Shown only to those customers, and only when the order fits their credit limit.', 'vectra-b2b' );
			$this->has_fields         = false;
			$this->init_form_fields();
			$this->init_settings();
			$this->title       = $this->get_option( 'title', __( 'Pay on my 30-day account', 'vectra-b2b' ) );
			$this->description = $this->get_option( 'description', __( 'We invoice you when the order ships. Payment is due 30 days from the end of the month.', 'vectra-b2b' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}

		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'     => array( 'title' => __( 'Enable', 'vectra-b2b' ), 'type' => 'checkbox', 'default' => 'yes', 'label' => __( 'Allow approved 30-day accounts to pay on account', 'vectra-b2b' ) ),
				'title'       => array( 'title' => __( 'Title', 'vectra-b2b' ), 'type' => 'text', 'default' => __( 'Pay on my 30-day account', 'vectra-b2b' ) ),
				'description' => array( 'title' => __( 'Description', 'vectra-b2b' ), 'type' => 'textarea', 'default' => __( 'We invoice you when the order ships. Payment is due 30 days from the end of the month.', 'vectra-b2b' ) ),
			);
		}

		public function is_available() {
			if ( ! parent::is_available() || ! vectra_b2b_has_terms() ) {
				return false;
			}
			$limit = (float) get_user_meta( get_current_user_id(), 'vectra_b2b_limit', true );
			if ( $limit <= 0 ) {
				return true;
			}
			$total = WC()->cart ? (float) WC()->cart->get_total( 'edit' ) : 0;
			return vectra_b2b_account_balance( get_current_user_id() ) + $total <= $limit;
		}

		public function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( '' === trim( vectra_b2b_order_po( $order )['po'] ) ) {
				wc_add_notice( __( 'Add your PO number to pay on account.', 'vectra-b2b' ), 'error' );
				return array( 'result' => 'failure' );
			}
			$order->update_status( 'processing', __( 'Ordered on 30-day account.', 'vectra-b2b' ) );
			$order->update_meta_data( '_vectra_on_account', 'yes' );
			$order->save();
			WC()->cart->empty_cart();
			return array( 'result' => 'success', 'redirect' => $this->get_return_url( $order ) );
		}
	}

	add_filter( 'woocommerce_payment_gateways', function ( $gateways ) {
		$gateways[] = 'Vectra_B2B_Account_Gateway';
		return $gateways;
	} );
} );

/**
 * Make the account payment method show in the block checkout too.
 */
add_action( 'woocommerce_blocks_loaded', function () {
	if ( ! class_exists( \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType::class ) ) {
		return;
	}

	final class Vectra_B2B_Account_Blocks extends \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType {
		protected $name = 'vectra_account';

		public function initialize() {
			$this->settings = get_option( 'woocommerce_vectra_account_settings', array() );
		}

		public function is_active() {
			$gateways = WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array();
			return isset( $gateways['vectra_account'] ) && $gateways['vectra_account']->is_available();
		}

		public function get_payment_method_script_handles() {
			wp_register_script( 'vectra-b2b-account-pay', VECTRA_B2B_URL . 'assets/account-pay.js', array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities' ), VECTRA_B2B_VERSION, true );
			return array( 'vectra-b2b-account-pay' );
		}

		public function get_payment_method_data() {
			return array(
				'title'       => $this->settings['title'] ?? __( 'Pay on my 30-day account', 'vectra-b2b' ),
				'description' => $this->settings['description'] ?? __( 'We invoice you when the order ships. Payment is due 30 days from the end of the month.', 'vectra-b2b' ),
			);
		}
	}

	add_action( 'woocommerce_blocks_payment_method_type_registration', function ( $registry ) {
		$registry->register( new Vectra_B2B_Account_Blocks() );
	} );
} );

/**
 * Unpaid account orders: everything on account that is not marked paid.
 * Mark an invoice paid by adding the order meta _vectra_paid_date (Y-m-d), or with the "Mark account invoice paid" order action.
 */
function vectra_b2b_account_balance( int $user_id ): float {
	$orders = wc_get_orders( array(
		'customer_id' => $user_id,
		'status'      => array( 'processing', 'completed', 'on-hold' ),
		'limit'       => -1,
		'meta_query'  => array( array( 'key' => '_vectra_on_account', 'value' => 'yes' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
	) );
	$sum = 0.0;
	foreach ( $orders as $o ) {
		if ( ! $o->get_meta( '_vectra_paid_date' ) ) {
			$sum += (float) $o->get_total();
		}
	}
	return $sum;
}

add_filter( 'woocommerce_order_actions', function ( $actions, $order ) {
	if ( $order && 'yes' === $order->get_meta( '_vectra_on_account' ) && ! $order->get_meta( '_vectra_paid_date' ) ) {
		$actions['vectra_mark_paid'] = __( 'Mark account invoice paid', 'vectra-b2b' );
	}
	return $actions;
}, 10, 2 );
add_action( 'woocommerce_order_action_vectra_mark_paid', function ( $order ) {
	$order->update_meta_data( '_vectra_paid_date', wp_date( 'Y-m-d' ) );
	$order->add_order_note( __( 'Account invoice marked paid.', 'vectra-b2b' ) );
	$order->save();
} );

<?php
/**
 * REST API Payment API controller
 *
 * Handles requests to the Payments API endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package WooCommerce\RestApi
 * @since    3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use WooCommerce\RestApi;

/**
 * REST API Report Top Sellers controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Report_Sales_V1_Controller
 */
class Pinaka_Payments_Api_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'pinaka-pos/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'payments';
	// public function __construct() 
	// {
	// 	add_action( 'shutdown', [ $this, 'capture_fatal_errors' ] );
	// }
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/create-payment',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'payment_create_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-payments-by-user-id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_payments_by_user_id_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-payments-by-shift-id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_payments_by_shift_id_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

		/**
		 * Register Update Payment API Route
		 */
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/update-payment',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'payment_update_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/delete-payment',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'payment_delete_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-payment-by-id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_payment_by_id_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-payments-by-order-id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_payments_by_order_id_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);


		register_rest_route(
			$this->namespace,
			$this->rest_base . '/void-payment',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'pos_api_void_razorpay_payment' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route( 
			$this->namespace,
			$this->rest_base . '/void-order', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'void_order_callback' ),
			'permission_callback' => '__return_true', // Change to proper auth later
		) );

		//update payment meta
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/update-payment-meta',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_payment_meta_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-all-paments-for-admin',
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_all_payments_for_admin' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

				'args' => array(
					'page' => array(
						'description'       => 'Current page number',
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
					'per_page' => array(
						'description'       => 'Number of records per page',
						'type'              => 'integer',
						'default'           => 10,
						'sanitize_callback' => 'absint',
					),
					'search' => array(
						'description'       => 'Search by staff name or shift title',
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'status' => array(
						'description'       => 'Shift status (open or closed)',
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

	}


    /**
	 * Check whether a given request has permission to view system status.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	/**
	 * Check whether a given request has permission to view system status.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function check_user_role_permission( $request ) {

		$user_id = get_current_user_id();
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			$this->log_payment_event(
				'error',
				'AUTH_FAILED',
				'User authentication failed',
				[ 'user_id' => $user_id ]
			);
			return new WP_Error(
				'pinakapos_rest_cannot_view',
				esc_html__( 'Sorry, you cannot give permission.', 'pinaka-pos' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		// Get all registered roles
		global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}
		$all_roles = array_keys( $wp_roles->get_names() );

		// Allow if user has any of the registered roles
		foreach ( $user->roles as $role ) {
			if ( in_array( $role, $all_roles, true ) ) {
				return true;
			}
		}
		$this->log_payment_event(
			'error',
			'PERMISSION_FAILED',
			'You dont have permission to login',
			[ 'user_roles' => $user->roles ]
		);
		return new WP_Error(
			'pinakapos_rest_cannot_view',
			esc_html__( 'Sorry, you cannot give permission.', 'pinaka-pos' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}

	/**
	 * Update Payment Meta API Callback
	 * payload: { post_id:123, key:'_payment_notes', value:'New note' }
	 * curl -X POST http://example.com/wp-json/pinaka-pos/v1/payments/update-payment-meta -H "Content-Type: application/json" -d '{"post_id":123,"key":"_payment_notes","value":"New note"}'
	 */

	public function update_payment_meta_callback( WP_REST_Request $request ) {

		$data = $request->get_json_params();
		$post_id = isset( $data['payment_id'] ) ? intval( $data['payment_id'] ) : 0;

		if ( empty( $post_id ) || get_post_type( $post_id ) !== 'payments' ) {
			$this->log_payment_event(
				'error',
				'VALIDATION_FAILED',
				'Invalid Payment ID',
				[
					'payment_id' => $post_id
				]
			);
			return new WP_Error( 'invalid_post', 'Invalid Payment ID', array( 'status' => 400 ) );
		}

		$meta_keys = array( 'key', 'value' );
		foreach ( $meta_keys as $key ) {
			if ( isset( $data[ $key ] ) ) {
				update_post_meta( $post_id, sanitize_text_field( $data['key'] ), sanitize_text_field( $data['value'] ) );
			}
		}
		return array( 'payment_id' => $post_id, 'message' => 'Payment Meta Updated Successfully' );
	}

	public function payment_create_callback( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		$data    = $request->get_json_params();

		// Validate required fields
		if ( empty( $data['order_id'] ) || ! isset( $data['amount'] ) || $data['payment_method'] === '' ) {
			$this->log_payment_event(
				'error',
				'VALIDATION_FAILED',
				'Missing required payment fields. Order ID, amount, and payment method are required',
				[
					'payload' => $data
				]
			);
			return new WP_Error( 'invalid_data', 'Order ID, amount, and payment method are required', array( 'status' => 400 ) );
		}

		if ( empty( $data['shift_id'] ) ) {
			$this->log_payment_event(
				'error',
				'VALIDATION_FAILED',
				'Shift Id Required or Invalid',
				[
					'shift_id' => 0,
				]
			);
			return new WP_Error( 'invalid_data', 'Shift Id Required or Invalid.', array( 'status' => 400 ) );
		}

		$order_id = absint( $data['order_id'] );
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			$this->log_payment_event(
				'error',
				'VALIDATION_FAILED',
				'Invalid order ID',
				[
					'order_id' => $order_id ?? null,
				]
			);
			return new WP_Error( 'invalid_order', 'Invalid order ID', array( 'status' => 404 ) );
		}

		// Create the Payment post
		$post_id = wp_insert_post( array(
		    
			'post_title' => isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : 'Payment for Order ' . $order_id,
            'post_content' => '',
			'post_type'    => 'payments',
			'post_status'  => 'publish',
		) );

		if ( is_wp_error( $post_id ) ) {
			$this->log_payment_event(
				'error',
				'PAYMENT_POST_TYPE_INSERTION_FAILED',
				'Payments post type not created',
				[
					'post_id' => 0,
					'payload' => $data
				]
			);
			return $post_id;
		}

		// Save meta fields
		$meta_fields = array(
			'order_id'       => '_payment_order_id',
			'amount'         => '_payment_amount',
			'payment_method' => '_payment_method',
			'shift_id'       => '_payment_shift_id',
			'user_id'        => '_payment_user_id',
			'service_type'   => '_payment_service_type',
			'datetime'       => '_payment_datetime',
			'notes'          => '_payment_notes',
			'transaction_id' => '_payment_transaction_id',
		);

		foreach ( $meta_fields as $key => $meta_key ) {
			if ( isset( $data[ $key ] ) ) { // allow 0, disallow only null
				update_post_meta( $post_id, $meta_key, sanitize_text_field( $data[ $key ] ) );
			}
		}

		update_post_meta( $post_id, '_payment_user_id', $user_id );
		update_post_meta( $post_id, '_payment_datetime', date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ) );

		$void = false;
		update_post_meta( $post_id, '_payment_void', $void );

		if ( ! empty( $data['transaction_details'] ) && is_array( $data['transaction_details'] ) ) {
			update_post_meta( $post_id, '_payment_transaction_details', wp_json_encode( $data['transaction_details'] ) );
		}

		/*
		|--------------------------------------------------------------------------
		| Calculate Total Paid & Update Order Status
		|--------------------------------------------------------------------------
		*/
		$order_total = (float) $order->get_total();
		$new_amt = $order->get_meta('new_netpay_amt');

		$order_total = ($new_amt !== '' && $new_amt !== null) ? floatval($new_amt) : $order_total;
		// Sum of all non-voided payments
		$existing_payments = get_posts( array(
			'post_type'   => 'payments',
			'numberposts' => -1,
			'meta_query'  => array(
				array(
					'key'   => '_payment_order_id',
					'value' => $order_id,
				),
			),
		) );

		$total_paid = 0;
		foreach ( $existing_payments as $payment_post ) {
			$is_void = get_post_meta( $payment_post->ID, '_payment_void', true );
			if ( $is_void && $is_void !== 'false' ) {
				continue; // skip void payments
			}
			$total_paid += (float) get_post_meta( $payment_post->ID, '_payment_amount', true );
		}
		/* ---------------- PAYOUT DETECTION ---------------- */

		// $has_sale_items   = false;
		// $has_payout_items = false;

		// $payout_product_id = (int) get_option( 'pinaka_payout_product_id', 0 );

		// foreach ( $order->get_items( 'line_item' ) as $item ) {

		// 	if (
		// 		( $payout_product_id && $item->get_product_id() == $payout_product_id ) ||
		// 		( (float) $item->get_total() < 0 )
		// 	) {
		// 		$has_payout_items = true;
		// 		continue;
		// 	}

		// 	if ( (float) $item->get_total() > 0 ) {
		// 		$has_sale_items = true;
		// 	}
		// }

		// $is_payout_only = $has_payout_items && ! $has_sale_items;
		/* ---------------- STATUS LOGIC ---------------- */

		if ( $total_paid < 0 ) {
			$order->update_status( 'processing', __( 'All payments voided/refunded or covered by coupons.', 'pinaka-pos' ) );
		} elseif ( $total_paid < $order_total) {
			$order->update_status( 'pending', __( 'Partial payment received.', 'pinaka-pos' ) );
		} else {
			$order->payment_complete();
			$order->update_status( 'completed', __( 'All payments voided/refunded or covered by coupons.', 'pinaka-pos' ) );
		}
		// $order->save();
		$coupons = [];

		if ( $order->get_status() === 'completed' ) {

			$coupons = $this->pinaka_get_all_shop_coupons( $order_total, $order_id );

			if ( ! empty( $coupons ) ) {

				$coupon_ids   = [];
				$coupon_codes = [];

				foreach ( $coupons as $coupon_data ) {

					$coupon_ids[]   = $coupon_data['id'];
					$coupon_codes[] = $coupon_data['code'];

					// Mark coupon as shown for limit tracking
					$flag_key = 'pinaka_coupon_response_shown';

					if ( ! $order->get_meta( $flag_key ) ) {
						$order->update_meta_data( $flag_key, 'yes' );
					}
				}

				// ✅ Store full coupon data
				$order->update_meta_data(
					'pinaka_available_coupons',
					$coupons
				);

				// ✅ Store quick-access lists
				$order->update_meta_data(
					'pinaka_available_coupon_ids',
					$coupon_ids
				);

				$order->update_meta_data(
					'pinaka_available_coupon_codes',
					$coupon_codes
				);

				$order->save();
			}
		}


		$remaining_amount = max( 0, $order_total - $total_paid );
		// Return unified response
		return array(
			'success'          => true,
			'message'          => 'Payment Created Successfully',
			'payment_id'       => $post_id,
			'order_id'         => $order_id,
			'paid_amount'      => (float) $data['amount'],
			'total_paid'       => $total_paid,
			'remaining_amount' => $remaining_amount,
			'order_total'      => $order_total,
			'order_status'     => $order->get_status(),
			'void'             => $void,
			'available_coupons'  => is_array( $coupons ) ? count( $coupons ) : 0,
			'coupons'          => $coupons,
		);
	}

	/**
	 * Get all shop coupons
	 */
	private function pinaka_get_all_shop_coupons( float $order_total, int $current_order_id ) {

		$coupons = get_posts( [
			'post_type'      => 'shop_coupon',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => [
				[
					'key'   => '_pinaka_enable_generate_coupon',
					'value' => 'yes',
				],
			],
		] );

		$response = [];

		foreach ( $coupons as $coupon_post ) {

			$coupon = new WC_Coupon( $coupon_post->ID );

			// Order total validation
			$min_amount = (float) $coupon->get_minimum_amount();
			$max_amount = (float) $coupon->get_maximum_amount();

			if ( $min_amount > 0 && $order_total < $min_amount ) continue;
			if ( $max_amount > 0 && $order_total > $max_amount ) continue;

			// Hide expired coupons
			$expiry = $coupon->get_date_expires();
			if ( $expiry && $expiry->getTimestamp() < current_time( 'timestamp' ) ) {
				continue;
			}

			// Hide coupon if WooCommerce usage limit reached
			$usage_limit = $coupon->get_usage_limit();
			$usage_count = $coupon->get_usage_count();

			if ( $usage_limit && $usage_count >= $usage_limit ) {
				continue;
			}

			// Generate config
			$generate_type  = get_post_meta(
				$coupon_post->ID,
				'_pinaka_generate_coupon_type',
				true
			);

			$generate_limit = (int) get_post_meta(
				$coupon_post->ID,
				'_pinaka_generate_coupon_limit',
				true
			);

			$expiry_date = $coupon->get_date_expires();

			// Stop showing after limit reached
			if ( $generate_type && $generate_limit > 0 ) {

				$shown_count = $this->pinaka_get_coupon_response_count(
					$coupon_post->ID,
					$generate_type,
					$current_order_id
				);

				if ( $shown_count >= $generate_limit ) {
					continue;
				}
			}

			$response[] = [
				'id'            => $coupon_post->ID,
				'code'          => $coupon->get_code(),
				'discount_type' => $coupon->get_discount_type(),
				'amount'        => (float) $coupon->get_amount(),
				'min_amount'    => $min_amount ?: null,
				'max_amount'    => $max_amount ?: null,
				'generate_type' => $generate_type ?: null,
				'generate_limit'=> $generate_limit ?: null,
				'expiry_date'    => $coupon->get_date_expires() ? $coupon->get_date_expires()->date( 'Y-m-d' ) : null,
			];
		}

		return $response;
	}


	private function pinaka_get_coupon_response_count(int $coupon_id, string $generate_type, int $current_order_id) {
		$now = current_time( 'timestamp' );

		switch ( $generate_type ) {

			case 'daily':
				$after = date( 'Y-m-d 00:00:00', $now );
				break;

			case 'weekly':
				$after = date(
					'Y-m-d 00:00:00',
					strtotime( 'monday this week', $now )
				);
				break;

			case 'monthly':
				$after = date( 'Y-m-01 00:00:00', $now );
				break;

			case 'yearly':
				$after = date( 'Y-01-01 00:00:00', $now );
				break;

			default:
				return 0;
		}

		$orders = wc_get_orders( [
			'status'     => 'completed',
			'limit'      => -1,
			'date_query' => [
				[
					'after'  => $after,
					'before' => current_time( 'mysql' ), // ⬅️ IMPORTANT
				],
			],
			'exclude'    => [ $current_order_id ], // ⬅️ KEY FIX
			'meta_query' => [
				[
					'key'   => '_pinaka_coupon_response_shown_' . $coupon_id,
					'value' => 'yes',
				],
			],
		] );

		return count( $orders );
	}

	public function get_payments_by_user_id_callback( WP_REST_Request $request ) 
	{
		$user_id = $request->get_param( 'user_id' );
		if ( empty( $user_id ) ) {
			return new WP_Error( 'invalid_user', 'User ID is required', array( 'status' => 400 ) );
		}
	
		$args = array(
			'post_type'   => 'payments',
			'post_status' => 'publish',
			'meta_query'  => array(
				array(
					'key'     => '_payment_user_id',
					'value'   => $user_id,
					'compare' => '='
				)
			),
			'numberposts' => -1
		);
	
		$payments = get_posts( $args );
		$results = array();
		$meta_keys = array( 'order_id', 'amount', 'shift_id', 'vendor_id', 'user_id', 'service_type', 'datetime', 'notes', 'transaction_id', 'transaction_details' );
	
		foreach ( $payments as $payment ) {
			$meta_data = array();
			foreach ( $meta_keys as $key ) {
				$meta_data[ $key ] = get_post_meta( $payment->ID, '_payment_' . $key, true );
			}
			$payment_data = (array) $payment;
			$results[] = array_merge( $payment_data, $meta_data );
			$results['payment_method'] = get_post_meta( $payment->ID, '_payment_method', true );
		}
		
		return rest_ensure_response( $results );
	}

	/**
	 * Update Payment API Callback
	 */
	public function payment_update_callback( WP_REST_Request $request ) {
		$data = $request->get_json_params();
		$post_id = isset( $data['post_id'] ) ? intval( $data['post_id'] ) : 0;

		if ( empty( $post_id ) || get_post_type( $post_id ) !== 'payments' ) {
			$this->log_payment_event(
				'error',
				'INVALID_PAYMENT_ID',
				'Invalid Payment ID',
				[
					'payment_id' => $post_id ?? null,
				]
			);
			return new WP_Error( 'invalid_post', 'Invalid Payment ID', array( 'status' => 400 ) );
		}

		$meta_keys = array( 'order_id', 'amount', 'payment_method', 'shift_id', 'vendor_id', 'user_id', 'service_type', 'datetime', 'notes', 'transaction_id', 'transaction_details', 'void' );
		foreach ( $meta_keys as $key ) {
			if ( isset( $data[ $key ] ) ) {
				update_post_meta( $post_id, '_payment_' . $key, sanitize_text_field( $data[ $key ] ) );
			}
		}
		return array( 'post_id' => $post_id, 'message' => 'Payment Updated Successfully' );
	}

	/**
 	* Delete Payment API Callback
	*/
	public function payment_delete_callback( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'payment_id' );

		if ( empty( $post_id ) || get_post_type( $post_id ) !== 'payments' ) {
			$this->log_payment_event(
				'error',
				'INVALID_PAYMENT_ID',
				'Invalid Payment ID',
				[
					'payment_id' => $post_id ?? null,
				]
			);
			return new WP_Error( 'invalid_post', 'Invalid Payment ID', array( 'status' => 400 ) );
		}

		if ( wp_delete_post( $post_id, true ) ) {
			return array( 'post_id' => $post_id, 'message' => 'Payment Deleted Successfully' );
		} else {
			$this->log_payment_event(
				'error',
				'PAYMENT_DELETED_FAILED',
				'Failed to delete payment',
				[
					'payment_id' => $post_id ?? null,
				]
			);
			return new WP_Error( 'delete_failed', 'Failed to delete payment', array( 'status' => 500 ) );
		}
	}

	/**
 	* Get Payment by ID API Callback
	*/
	public function get_payment_by_id_callback( WP_REST_Request $request ) {
		$payment_id = $request->get_param( 'payment_id' );
		if ( empty( $payment_id ) ) {
			return new WP_Error( 'invalid_payment', 'Payment ID is required', array( 'status' => 400 ) );
		}

		$args = array(
			'post_type'   => 'payments',
			'post_status' => 'publish',
			'p'           => $payment_id,
		);

		$payments = get_posts( $args );

		if ( empty( $payments ) ) {
			return new WP_Error( 'not_found', 'Payment not found', array( 'status' => 404 ) );
		}

		$meta_keys = array(
			'order_id', 'amount', 'payment_method', 'shift_id',
			'vendor_id', 'user_id', 'service_type', 'datetime',
			'notes', 'transaction_id', 'transaction_details', 'void'
		);

		$results = array();

		foreach ( $payments as $payment ) {
			$meta_data = array();

			// Normal meta fields
			foreach ( $meta_keys as $key ) {
				$meta_data[ $key ] = get_post_meta( $payment->ID, '_payment_' . $key, true );
			}

			// Handle void as proper boolean
			$void_meta = get_post_meta( $payment->ID, '_payment_void', true );
			$meta_data['void'] = ( $void_meta == '1' || $void_meta === true ) ? true : false;

			// Add order status if order exists
			$order_status = null;
			if ( ! empty( $meta_data['order_id'] ) ) {
				$order = wc_get_order( $meta_data['order_id'] );
				if ( $order ) {
					$order_status = $order->get_status();
				}
			}
			$meta_data['order_status'] = $order_status;

			// Base payment data
			$payment_data = array(
				'ID'      => $payment->ID,
				'title'   => $payment->post_title,
				'content' => $payment->post_content,
				'date'    => $payment->post_date,
			);

			$results[] = array_merge( $payment_data, $meta_data );
		}

		return rest_ensure_response( $results );
	}

	public function get_payments_by_order_id_callback( WP_REST_Request $request ) {
		$order_id = $request->get_param( 'order_id' );
		if ( empty( $order_id ) ) {
			return new WP_Error( 'invalid_user', 'Order ID is required', array( 'status' => 400 ) );
		}

		$args = array(
			'post_type'   => 'payments',
			'post_status' => 'publish',
			'meta_query'  => array(
				array(
					'key'     => '_payment_order_id',
					'value'   => $order_id,
					'compare' => '='
				)
			),
			'numberposts' => -1
		);

		$payments = get_posts( $args );
		$results = array();
		$meta_keys = array( 'order_id', 'amount', 'shift_id', 'vendor_id', 'user_id', 'service_type', 'datetime', 'notes', 'transaction_id', 'transaction_details', 'void' );

		foreach ( $payments as $payment ) {
			$meta_data = array();

			// Load meta keys
			foreach ( $meta_keys as $key ) {
				$value = get_post_meta( $payment->ID, '_payment_' . $key, true );

				// Normalize void to boolean
				if ( $key === 'void' ) {
					$value = ( ! empty( $value ) && $value == '1' ) ? true : false;
				}

				$meta_data[ $key ] = $value;
			}

			// Payment method
			$meta_data['payment_method'] = get_post_meta( $payment->ID, '_payment_method', true );

			// Get order status if valid order ID exists
			$order_status = null;
			if ( ! empty( $meta_data['order_id'] ) && wc_get_order( $meta_data['order_id'] ) ) {
				$order = wc_get_order( $meta_data['order_id'] );
				$order_status = $order->get_status(); // e.g. "processing", "completed"
			}
			$meta_data['order_status'] = $order_status;

			// Merge payment object + meta
			$payment_data = (array) $payment;
			$results[] = array_merge( $payment_data, $meta_data );
		}

		return rest_ensure_response( $results );
	}

	function pos_api_void_razorpay_payment( WP_REST_Request $request ) {
		$order_id   = $request->get_param('order_id');
		$payment_id = $request->get_param('payment_id');

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			$this->log_payment_event(
				'error',
				'ORDER_NOT_FOUND',
				'Order not found',
				[
					'order_id' => $request->get_param('order_id') ?? null,
				]
			);
			return new WP_Error( 'no_order', 'Order not found', [ 'status' => 404 ] );
		}

		// 1. Mark the payment record as void
		update_post_meta( $payment_id, '_payment_void', true );

		// 2. Add order note
		$order->add_order_note( "Payment ID {$payment_id} marked as voided via POS (Razorpay)." );

		// 3. Recalculate total paid (excluding void payments)
		$order_total   = (float) $order->get_total();
		$new_amt = $order->get_meta('new_netpay_amt');

		$order_total = ($new_amt !== '' && $new_amt !== null) ? floatval($new_amt) : $order_total;
		$existing_payments = get_posts( [
			'post_type'   => 'payments',
			'numberposts' => -1,
			'meta_query'  => [
				[
					'key'   => '_payment_order_id',
					'value' => $order_id,
				],
			],
		] );

		$total_paid = 0;
		foreach ( $existing_payments as $payment_post ) {
			$is_void = get_post_meta( $payment_post->ID, '_payment_void', true );
			if ( $is_void ) {
				continue; // skip voided payments
			}
			$total_paid += (float) get_post_meta( $payment_post->ID, '_payment_amount', true );
		}

		// 4. Update order status based on remaining paid
		if ( $total_paid < 0 ) {
			$order->update_status( 'processing', 'All payments voided/refunded, order back to processing.' );
			$this->pinaka_redeem_credited_points_for_order( $order_id );
		} elseif ( $total_paid < $order_total ) {
			$order->update_status( 'pending', 'Partial payment remains after void/refund.' );
			$this->pinaka_redeem_credited_points_for_order( $order_id );
		} else {
			$order->payment_complete();
			$order->update_status( 'completed', __( 'All payments voided/refunded or covered by coupons.', 'pinaka-pos' ) );
		}

		// 5. Update order meta with tracking fields
		update_post_meta( $order_id, '_amount_paid', $total_paid );
		update_post_meta( $order_id, '_remaining_amount', max( 0, $order_total - $total_paid ) );
		return [
			'success'          => true,
			'message'          => 'Payment voided successfully',
			'void_payment_id'  => $payment_id,
			'order_id'         => $order_id,
			'order_total'      => $order_total,
			'total_paid'       => $total_paid,
			'remaining_amount' => max( 0, $order_total - $total_paid ),
			'order_status'     => $order->get_status(),
		];
	}
	// public function pinaka_redeem_credited_points_for_order( $order_id ) {

	// 	// if ( ! $order instanceof WC_Order ) {
	// 	// 	return;
	// 	// }

	// 	// ---- Idempotency check ----
	// 	// if ( $order->get_meta('_pinaka_credit_redeemed') === 'yes' ) {
	// 	// 	return;
	// 	// }

	// 	global $wpdb;
	// 	$table_points = "{$wpdb->prefix}pinaka_loyalty_points";

	// 	// ---- Get credited points for THIS order (including user_id) ----
	// 	$rows = $wpdb->get_results(
	// 		$wpdb->prepare(
	// 			"
	// 			SELECT id, user_id, credited_points, redeemed_points, expired_points
	// 			FROM $table_points
	// 			WHERE order_id = %d
	// 			AND credited_points > 0
	// 			AND points_status = 'active'
	// 			",
	// 			$order_id
	// 		)
	// 	);

	// 	if ( empty( $rows ) ) {
	// 		return;
	// 	}

	// 	$total_redeemed_now = 0;

	// 	// Use user_id from table (take from first row)
	// 	$user_id = (int) $rows[0]->user_id;

	// 	foreach ( $rows as $row ) {

	// 		$available = (float) $row->credited_points
	// 				- (float) $row->redeemed_points
	// 				- (float) $row->expired_points;

	// 		if ( $available <= 0 ) {
	// 			continue;
	// 		}

	// 		$wpdb->update(
	// 			$table_points,
	// 			[
	// 				'redeemed_points' => $row->redeemed_points + $available,
	// 				'points_status'   => 'used'
	// 			],
	// 			['id' => $row->id],
	// 			['%f','%s'],
	// 			['%d']
	// 		);

	// 		$total_redeemed_now += $available;
	// 	}

	// 	// ---- Update user meta using USER ID FROM TABLE ----
	// 	if ( $user_id > 0 ) {

	// 		$existing_total_credited = (float) get_user_meta( $user_id, 'pinaka_total_credited', true );
	// 		$existing_total_redeemed = (float) get_user_meta( $user_id, 'pinaka_total_redeemed', true );
	// 		$existing_total_expired  = (float) get_user_meta( $user_id, 'pinaka_total_expired', true );

	// 		$new_total_redeemed = $existing_total_redeemed + $total_redeemed_now;

	// 		$new_available = max(
	// 			0,
	// 			$existing_total_credited - $new_total_redeemed - $existing_total_expired
	// 		);

	// 		update_user_meta( $user_id, 'pinaka_total_redeemed', round( $new_total_redeemed ) );
	// 		update_user_meta( $user_id, 'pinaka_available_points', round( $new_available ) );
	// 		update_user_meta( $user_id, 'pinaka_last_updated', current_time( 'mysql' ) );
	// 	}
	// }
	public function pinaka_redeem_credited_points_for_order( $order_id ) {
		global $wpdb;
		$table_points = "{$wpdb->prefix}pinaka_loyalty_points";
		// -------------------------------------------------
		// 1. Fetch rows for THIS order (to get user_id)
		// -------------------------------------------------
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT id, user_id, credited_points, redeemed_points, expired_points
				FROM $table_points
				WHERE order_id = %d
				AND credited_points > 0
				",
				$order_id
			)
		);

		if ( empty( $rows ) ) {
			return;
		}

		$user_id = (int) $rows[0]->user_id;

		if ( $user_id <= 0 ) {
			return;
		}

		// -------------------------------------------------
		// 2. DELETE credited records for this order
		// -------------------------------------------------
		$wpdb->delete(
			$table_points,
			[ 'order_id' => $order_id ],
			[ '%d' ]
		);

		// -------------------------------------------------
		// 3. Recalculate user totals from remaining records
		// -------------------------------------------------
		$totals = $wpdb->get_row(
			$wpdb->prepare(
				"
				SELECT
					COALESCE(SUM(credited_points), 0) AS total_credited,
					COALESCE(SUM(redeemed_points), 0) AS total_redeemed,
					COALESCE(SUM(expired_points), 0)  AS total_expired
				FROM $table_points
				WHERE user_id = %d
				",
				$user_id
			),
			ARRAY_A
		);

		$total_credited = (float) $totals['total_credited'];
		$total_redeemed = (float) $totals['total_redeemed'];
		$total_expired  = (float) $totals['total_expired'];

		$available = max(
			0,
			$total_credited - $total_redeemed - $total_expired
		);

		// -------------------------------------------------
		// 4. Update user meta (single source of truth = DB)
		// -------------------------------------------------
		update_user_meta( $user_id, 'pinaka_total_credited', round( $total_credited ) );
		update_user_meta( $user_id, 'pinaka_total_redeemed', round( $total_redeemed ) );
		update_user_meta( $user_id, 'pinaka_total_expired', round( $total_expired ) );
		update_user_meta( $user_id, 'pinaka_available_points', round( $available ) );
		update_user_meta( $user_id, 'pinaka_last_updated', current_time( 'mysql' ) );
	}
	public function void_order_callback( WP_REST_Request $request ) {
		$order_id = $request->get_param( 'order_id' );
		if ( empty( $order_id ) ) {
			$this->log_payment_event(
				'error',
				'ORDER_ID_REQUIRED',
				'Order ID is required',
				[
					'order_id' => $request->get_param('order_id') ?? null,
				]
			);
			return new WP_Error( 'invalid_order', 'Order ID is required', array( 'status' => 400 ) );
		}

		// Get the WooCommerce order
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			$this->log_payment_event(
				'error',
				'ORDER_NOT_FOUND',
				'Order not found',
				[
					'order_id' => $order_id ?? null,
				]
			);
			return new WP_Error( 'not_found', 'Order not found', array( 'status' => 404 ) );
		}

		// 1. Void the order
		$order->update_status( 'cancelled', 'Order voided via API' );

		// 2. Find related payments
		$args = array(
			'post_type'   => 'payments',
			'post_status' => 'publish',
			'meta_query'  => array(
				array(
					'key'   => '_payment_order_id',
					'value' => $order_id,
				),
			),
			'numberposts' => -1,
		);

		$payments = get_posts( $args );

		// 3. Mark payments as void
		foreach ( $payments as $payment ) {
			update_post_meta( $payment->ID, '_payment_void', true );
		}
		return rest_ensure_response( array(
			'success'     => true,
			'message'     => 'Order and payments voided successfully.',
			'order_id'    => $order_id,
			'order_status'=> $order->get_status(),
			'voided_payments' => wp_list_pluck( $payments, 'ID' ),
		) );
	}

	public function get_payments_by_shift_id_callback( WP_REST_Request $request ) {
		$shift_id   = $request->get_param('shift_id');
		$args = array(
			'post_type'   => 'payments',
			'post_status' => 'publish',
			'meta_query'  => array(
				array(
					'key'     => '_payment_shift_id',
					'value'   => $shift_id,
					'compare' => '='
				)
			),
			'numberposts' => -1
		);
	
		$payments = get_posts( $args );
		$results = array();
		$meta_keys = array( 'order_id', 'amount', 'method', 'shift_id', 'datetime', 'transaction_id', 'transaction_details', 'void');
	
		foreach ( $payments as $payment ) {
			$meta_data = array();
			foreach ( $meta_keys as $key ) {
				$meta_data[ $key ] = get_post_meta( $payment->ID, '_payment_' . $key, true );
			}
			$payment_data = (array) $payment;
			$results[] = array_merge( $payment_data, $meta_data );
		}
	
		return rest_ensure_response( $results );

	}
	public function log_payment_event($level, $event, $message = '', $context = []) 
	{
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		$logger = wc_get_logger();

		$day    = current_time( 'Y-m-d' );
		$source = 'pinaka-pos-payment-logs-'. $day;

		$log_context = array_merge(
			[
				'source' => $source,
				'event'  => sanitize_text_field( $event ),
				'date'   => $day,
				'ip'     => $_SERVER['REMOTE_ADDR'] ?? '',
			],
			$context
		);

		$level = in_array( $level, [ 'info', 'warning', 'error', 'critical' ], true )
			? $level
			: 'info';

		$logger->log(
			$level,
			$message ?: $event,
			$log_context
		);
	}
	// public function capture_fatal_errors() {

	// 	$error = error_get_last();

	// 	if ( $error && in_array(
	// 		$error['type'],
	// 		[ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ],
	// 		true
	// 	) ) {
	// 		$this->log_payment_event(
	// 			'critical',
	// 			'APP_CRASH',
	// 			$error['message'],
	// 			[
	// 				'file' => $error['file'],
	// 				'line' => $error['line'],
	// 			]
	// 		);
	// 	}
	// }

	public function get_all_payments_for_admin( WP_REST_Request $request ) {

    $page   = max( 1, (int) $request->get_param( 'page' ) );
    $limit  = max( 10, (int) $request->get_param( 'per_page' ) );
    $search = sanitize_text_field( $request->get_param( 'search' ) );
    // $status = sanitize_text_field( $request->get_param( 'status' ) );
	$paymode = sanitize_text_field( $request->get_param( 'paymentMode' ) );
    $args = array(
		'post_type'      => 'payments',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'paged'          => $page,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	$meta_query = [];

	if ( ! empty( $search ) ) {

		$meta_query[] = [
			'relation' => 'OR',
			[
				'key'     => '_payment_order_id',
				'value'   => $search,
				'compare' => 'LIKE',
			],
			[
				'key'     => '_payment_method',
				'value'   => $search,
				'compare' => 'LIKE',
			]
		];
	}

	if ( ! empty( $paymode ) ) {

		$meta_query[] = [
			'key'     => '_payment_method',
			'value'   => $paymode,
			'compare' => 'LIKE',
		];
	}

	if ( ! empty( $meta_query ) ) {
		$args['meta_query'] = $meta_query;
	}
	// if ( is_numeric( $search ) ) {
	// 	$args['post__in'] = array_merge(
	// 		$args['post__in'] ?? [],
	// 		[ (int) $search ]
	// 	);
	// }
	$query = new WP_Query( $args );

    $results = [];

    $meta_keys = [
        'order_id','amount','payment_method','shift_id','vendor_id',
        'user_id','service_type','datetime','notes','transaction_id',
        'transaction_details','void','status',
    ];

    // ✅ GROUP PAYMENTS BY ORDER (for balance & change)
    $payments_by_order = [];
	// echo json_encode($query->posts);
    foreach ( $query->posts as $payment ) {

        $order_id = get_post_meta($payment->ID, '_payment_order_id', true);
        $amount   = floatval(get_post_meta($payment->ID, '_payment_amount', true));
        $void     = get_post_meta($payment->ID, '_payment_void', true);

        if ($void && $void !== 'false') {
            $amount = 0;
        }

        $payments_by_order[$order_id][] = [
            'id' => $payment->ID,
            'amount' => $amount
        ];
    }

    if ( $query->have_posts() ) {
        foreach ( $query->posts as $payment ) {

            // ✅ COLLECT META
            $meta_data = [];
            foreach ( $meta_keys as $key ) {
                $meta_data[$key] = get_post_meta(
                    $payment->ID,
                    '_payment_' . $key,
                    true
                );
            }

            $order_id = $meta_data['order_id'];
            $amount   = floatval($meta_data['amount']);
            $void     = $meta_data['void'];

            if ($void && $void !== 'false') {
                $amount = 0;
            }

            /* =============================
               ORDER TOTAL
            ==============================*/
            $order_total = 0;
            if ($order_id) {
                $order = wc_get_order($order_id);
                if ($order) {
                    $order_total = floatval($order->get_total());
                    $new_amt = $order->get_meta('new_netpay_amt');

                    if ($new_amt !== '' && $new_amt !== null) {
                        $order_total = floatval($new_amt);
                    }
                }
            }

            /* =============================
               RUNNING BALANCE
            ==============================*/
            $remaining = $order_total;

            if (!empty($payments_by_order[$order_id])) {
                foreach ($payments_by_order[$order_id] as $p) {

                    if ($p['id'] == $payment->ID) {
                        $remaining -= $p['amount'];
                        break;
                    }

                    $remaining -= $p['amount'];
                }
            }

            if ($remaining < 0) $remaining = 0;

            /* =============================
               TOTAL PAID
            ==============================*/
            $total_paid = 0;
            foreach ($payments_by_order[$order_id] as $p) {
                $total_paid += $p['amount'];
            }

            /* =============================
               CHANGE AMOUNT (last payment)
            ==============================*/
            $change_amount = 0;
            $last_payment = end($payments_by_order[$order_id]);
            reset($payments_by_order[$order_id]);

            if ($last_payment && $last_payment['id'] == $payment->ID) {
                $over = $total_paid - $order_total;
                if ($over > 0) {
                    $change_amount = $over;
                }
            }

            /* =============================
               TRANSACTION ID FROM NOTES
            ==============================*/
            $transaction_id = $payment->ID;

            if (!empty($meta_data['notes'])) {
                $decoded = json_decode($meta_data['notes'], true);
                if (json_last_error() === JSON_ERROR_NONE &&
                    isset($decoded['sunmiTransactionId'])) {
                    $transaction_id = sanitize_text_field($decoded['sunmiTransactionId']);
                }
            }
				
            /* =============================
               PAYMENT ACCEPTED BY (User)
            ==============================*/
            $accepted_by = null;
            if (!empty($meta_data['user_id'])) {
                $user = get_userdata($meta_data['user_id']);
                if ($user) {
                    $accepted_by = $user->display_name;
                }
            }

            /* =============================
               FINAL RESPONSE
            ==============================*/
            $results[] = [
                'id' => $payment->ID,
                'title' => $payment->post_title,
                'created_at' => $payment->post_date,

                'order_total'    => html_entity_decode(get_woocommerce_currency_symbol()).''.$order_total,
                'tender_amount'  => html_entity_decode(get_woocommerce_currency_symbol()).''.$amount,
                'balance_amount' => html_entity_decode(get_woocommerce_currency_symbol()).''.$remaining,
                'change_amount'  => html_entity_decode(get_woocommerce_currency_symbol()).''.$change_amount,
                'transaction_id' => $transaction_id,
				'pay_mode'       => ucfirst(get_post_meta( $payment->ID, '_payment_method', true )),
                'accepted_by'    => $accepted_by,
				'payment_method' => $this->pinaka_get_order_payment_methods( $order_id,$paymode ),
                'meta' => $meta_data,
            ];
        }
    }

    return rest_ensure_response( [
        'data' => $results,
        'pagination' => [
            'page'        => $page,
            'per_page'    => $limit,
            'total'       => (int) $query->found_posts,
            'total_pages' => (int) $query->max_num_pages,
            'has_more'    => $page < $query->max_num_pages,
        ],
    ] );
}
function pinaka_get_order_payment_methods( $order_id,$paymode='' ) {
		$args = [
			'post_type'      => 'payments',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => [
				[
					'key'   => '_payment_order_id',
					'value' => $order_id,
				],
			],
		];
		$payment_ids = get_posts( $args );
		$methods = [];
		foreach ( $payment_ids as $payment_id ) {
			$is_void = get_post_meta( $payment_id, '_payment_void', true );
			if ( ! empty( $is_void ) && $is_void !== 'false' ) {
				continue;
			}
			$method = strtolower(get_post_meta( $payment_id, '_payment_method', true ));
			$method = strtolower(trim( $method ));
			if (!empty($paymode) && $method!==$paymode) {
				continue;
			}
			$methods[] = ucfirst($method);
		}
		$methods = array_unique( $methods );
		return implode( ', ', $methods );
	}

}

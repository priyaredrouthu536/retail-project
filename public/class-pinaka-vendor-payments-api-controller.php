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
class Pinaka_Vendor_Payments_Api_Controller {

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
	protected $rest_base = 'vendor_payments';


    /**
	 * Register the routes for sales reports.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/create-vendor-payment',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'vendor_payment_create_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-vendor-payments-by-user-id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_vendor_payments_by_user_id_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

		/**
		 * Register Update Payment API Route
		 */
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/update-vendor-payment',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'vendor_payment_update_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/delete-vendor-payment',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_vendor_payment_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-vendor-payment-by-id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_vendor_payment_by_id_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-vendor-payments-by-vendor-id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_vendor_payments_by_vendor_id_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-vendor-payments-by-shift-id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_vendor_payments_by_shift_id_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-all-vendors-for-admin',
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_all_vendors_for_admin_callback' ),
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
					)
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/create-vendor',
			array(
				'methods'             => 'POST', // POST
				'callback'            => array( $this, 'pinaka_create_vendor' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			),
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/update-vendor/(?P<id>\d+)', 
			[
				'methods'             => 'POST', // POST
				'callback'            => array( $this, 'pinaka_update_vendor' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			],
		);

		register_rest_route($this->namespace,
			'/' . $this->rest_base . '/delete-vendor/(?P<id>\d+)', [
			[
				'methods'             => 'DELETE', // DELETE
				'callback'            =>  array( $this, 'pinaka_delete_vendor'),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			],
		]);
		
	}
	
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

		return new WP_Error(
			'pinakapos_rest_cannot_view',
			esc_html__( 'Sorry, you cannot give permission.', 'pinaka-pos' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}

    /**
	 * Get token by sending POST request to pinaka-pos/v1/token.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response The response.
	 */
	public function vendor_payment_create_callback( WP_REST_Request $request ) {
		$data = $request->get_json_params();
		$user_id = get_current_user_id();
		// Validate the data
		if (empty( $data['vendor_id'] ) || empty( $data['amount'] ) || empty( $data['payment_method'] ) ) {
			return new WP_Error( 'invalid_data', 'Vendor, Amount, and payment method are required', array( 'status' => 400 ) );
		}
	
		// Create the post
		$post_id = wp_insert_post( array(
			'post_title'   => sanitize_text_field( $data['title'] ),
			'post_content' => '',
			'post_type'    => 'vendor_payments',
			'post_status'  => 'publish',
		) );
	
		if ( is_wp_error( $post_id ) ) {
			return $post_id; // Return error if post creation fails
		}
	
		// Save the meta fields
		$meta_fields = array(
			'amount' => '_vendor_payment_amount',
			'payment_method' => '_vendor_payment_method',
			'shift_id' => '_vendor_payment_shift_id',
			'vendor_id' => '_vendor_id',
			'service_type' => '_vendor_payment_service_type',
			'notes' => '_vendor_payment_notes'
		);
	
		foreach ( $meta_fields as $key => $meta_key ) {
			if ( ! empty( $data[ $key ] ) ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( $data[ $key ] ) );
			}
		}

		update_post_meta( $post_id, '_vendor_payment_user_id', $user_id );
		update_post_meta( $post_id, '_vendor_payment_datetime', date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ) );
	
		// Save the JSON transaction details
		if ( ! empty( $data['transaction_details'] ) && is_array( $data['transaction_details'] ) ) {
			update_post_meta( $post_id, '_payment_transaction_details', wp_json_encode( $data['transaction_details'] ) );
		}
	
		return array( 'vendor_payment_id' => $post_id, 'message' => 'Vendor Payment Created Successfully' );
	}


	public function get_vendor_payments_by_user_id_callback( WP_REST_Request $request ) {

		$user_id = $request->get_param( 'user_id' ) ?? get_current_user_id();
		if ( empty( $user_id ) ) {
			return new WP_Error( 'invalid_user', 'User ID is required', array( 'status' => 400 ) );
		}
	
		$args = array(
			'post_type'   => 'vendor_payments',
			'post_status' => 'publish',
			'meta_query'  => array(
				array(
					'key'     => '_vendor_payment_user_id',
					'value'   => $user_id,
					'compare' => '='
				)
			),
			'numberposts' => -1
		);
	
		$payments = get_posts( $args );
		$results = array();
		$meta_fields = array(
			'_vendor_payment_amount','_vendor_payment_method','_vendor_payment_shift_id','_vendor_id','_vendor_payment_service_type','_vendor_payment_notes'
		);
		
		foreach ( $payments as $payment ) {
			// Get vendor ID from post meta
			$vendor_id = get_post_meta( $payment->ID, '_vendor_id', true );
			$vendor_name = get_post( $vendor_id, 'post_title', true );
			$meta_data = array();
			foreach ( $meta_fields as $meta_key ) {
				$meta_data[ str_replace( '_vendor_payment_', '', $meta_key ) ] = get_post_meta( $payment->ID, $meta_key, true );
			}
			$payment_data = (array) $payment;
			// Add vendor name to the payment data
			$payment_data['vendor_name'] = $vendor_name->post_title;
			
			$results[] = array_merge( $payment_data, $meta_data );
		}

		return rest_ensure_response( $results );
	}


	/**
	 * Update Payment API Callback
	 */
	public function vendor_payment_update_callback( WP_REST_Request $request ) {
		$data = $request->get_json_params();
		$post_id = isset( $data['vendor_payment_id'] ) ? intval( $data['vendor_payment_id'] ) : 0;

		if ( empty( $post_id ) || get_post_type( $post_id ) !== 'vendor_payments' ) {
			return new WP_Error( 'invalid_post', 'Invalid Payment ID', array( 'status' => 400 ) );
		}

		// Save the meta fields
		$meta_fields = array(
			'amount' => '_vendor_payment_amount',
			'payment_method' => '_vendor_payment_method',
			'shift_id' => '_vendor_payment_shift_id',
			'vendor_id' => '_vendor_id',
			'service_type' => '_vendor_payment_service_type',
			'notes' => '_vendor_payment_notes'
		);
	
		foreach ( $meta_fields as $key => $meta_key ) {
			if ( ! empty( $data[ $key ] ) ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( $data[ $key ] ) );
			}
		}

		$user_id = get_current_user_id();

		update_post_meta( $post_id, '_vendor_payment_user_id', $user_id );
		update_post_meta( $post_id, '_vendor_payment_updated_datetime', date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ) );
	
		// Save the JSON transaction details
		if ( ! empty( $data['transaction_details'] ) && is_array( $data['transaction_details'] ) ) {
			update_post_meta( $post_id, '_payment_transaction_details', wp_json_encode( $data['transaction_details'] ) );
		}
	
		return array( 'vendor_payment_id' => $post_id, 'message' => 'Vendor Payment Updated Successfully' );
	}


	/**
 	* Delete Payment API Callback
	*/
	public function delete_vendor_payment_callback( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'vendor_payment_id' );

		if ( empty( $post_id ) || get_post_type( $post_id ) !== 'vendor_payments' ) {
			return new WP_Error( 'invalid_post', 'Invalid Vendor Payment ID', array( 'status' => 400 ) );
		}

		if ( wp_delete_post( $post_id, true ) ) {
			return array( 'post_id' => $post_id, 'message' => 'Payment Deleted Successfully' );
		} else {
			return new WP_Error( 'delete_failed', 'Failed to delete payment', array( 'status' => 500 ) );
		}
	}

	/**
 	* Get Payment by ID API Callback
	*/
	public function get_vendor_payment_by_id_callback( WP_REST_Request $request ) {
		$payment_id = $request->get_param( 'payment_id' );
		if ( empty( $payment_id ) ) {
			return new WP_Error( 'invalid_payment', 'Payment ID is required', array( 'status' => 400 ) );
		}
	
		$args = array(
			'post_type'   => 'vendor_payments',
			'post_status' => 'publish',
			'p'          => $payment_id, // Correct argument for specific post ID
		);
	
		$payments = get_posts( $args );

		if ( empty( $payments ) ) {
			return new WP_Error( 'not_found', 'Payment not found', array( 'status' => 404 ) );
		}
	
		$meta_keys = array( 'order_id', 'amount', 'payment_method', 'shift_id', 'vendor_id', 'user_id', 'service_type', 'datetime', 'notes', 'transaction_id', 'transaction_details' );
		$results = array();
	
		foreach ( $payments as $payment ) {
			$meta_data = array();
			foreach ( $meta_keys as $key ) {
				$meta_data[ $key ] = get_post_meta( $payment->ID, '_payment_' . $key, true );
			}
			$payment_data = array(
				'ID'    => $payment->ID,
				'title' => $payment->post_title,
				'content' => $payment->post_content,
				'date' => $payment->post_date,
			);
			$results[] = array_merge( $payment_data, $meta_data );
		}
	
		return rest_ensure_response( $results );
	}



	public function get_vendor_payments_by_vendor_id_callback( WP_REST_Request $request ) {
		$order_id = $request->get_param( 'order_id' );
		if ( empty( $order_id ) ) {
			return new WP_Error( 'invalid_user', 'Order ID is required', array( 'status' => 400 ) );
		}
	
		$args = array(
			'post_type'   => 'vendor_payments',
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
		$meta_keys = array( 'order_id', 'amount', 'payment_method', 'shift_id', 'vendor_id', 'user_id', 'service_type', 'datetime', 'notes',  'transaction_id', 'transaction_details');
	
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


	public function get_vendor_payments_by_shift_id_callback( WP_REST_Request $request ) {

		$shift_id = $request->get_param( 'shift_id' );
		if ( empty( $shift_id ) ) {
			return new WP_Error( 'invalid_user', 'User ID is required', array( 'status' => 400 ) );
		}
	
		$args = array(
			'post_type'   => 'vendor_payments',
			'post_status' => 'publish',
			'meta_query'  => array(
				array(
					'key'     => '_vendor_payment_shift_id',
					'value'   => $shift_id,
					'compare' => '='
				)
			),
			'numberposts' => -1
		);
	
		$payments = get_posts( $args );
		$results = array();
		$meta_fields = array(
			'_vendor_payment_amount','_vendor_payment_method','_vendor_payment_shift_id','_vendor_id','_vendor_payment_service_type','_vendor_payment_notes'
		);
		
		foreach ( $payments as $payment ) {
			// Get vendor ID from post meta
			$vendor_id = get_post_meta( $payment->ID, '_vendor_id', true );
			$vendor_name = get_post( $vendor_id, 'post_title', true );
			$meta_data = array();
			foreach ( $meta_fields as $meta_key ) {
				$meta_data[ $meta_key ] = get_post_meta( $payment->ID, $meta_key, true );
			}
			$payment_data = (array) $payment;
			// Add vendor name to the payment data
			$payment_data['_vendor_name'] = $vendor_name->post_title;
			
			$results[] = array_merge( $payment_data, $meta_data );
		}

		return rest_ensure_response( $results );
	}

	public function get_all_vendors_for_admin_callback( WP_REST_Request $request ) {

		$page   = max( 1, (int) $request->get_param( 'page' ) );
		$limit  = max( 10, (int) $request->get_param( 'per_page' ) );
		$search = sanitize_text_field( $request->get_param( 'search' ) );
		$status = sanitize_text_field( $request->get_param( 'status' ) );

		$args = array(
			'post_type'      => 'vendor',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		// 🔍 Search by title
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		// 🟢 Payment status filter (optional)
		// if ( ! empty( $status ) ) {
		// 	$args['meta_query'] = array(
		// 		array(
		// 			'key'   => '_payment_status',
		// 			'value' => $status,
		// 		),
		// 	);
		// }

		$query = new WP_Query( $args );

		$results = array();

		$meta_keys = array(
			'vendor_contact_info',
			'vendor_linked_payments',
			'_vendor_phone',
			'_vendor_email',
			'_vendor_address',
			'status',
		);

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $vendor ) {

				$meta_data = array();
				foreach ( $meta_keys as $key ) {
					$meta_data[ $key ] = get_post_meta(
						$vendor->ID,
						$key,
						true
					);
				}

				$results[] = array(
					'id'         => $vendor->ID,
					'title'      => $vendor->post_title,
					'created_at' => $vendor->post_date,
					'meta'       => $meta_data,
				);
			}
		}

		return rest_ensure_response( array(
			'data' => $results,
			'pagination' => array(
				'page'        => $page,
				'per_page'    => $limit,
				'total'       => (int) $query->found_posts,
				'total_pages' => (int) $query->max_num_pages,
				'has_more'    => $page < $query->max_num_pages,
			),
		) );
	}


	function pinaka_create_vendor(WP_REST_Request $request) {

		$params = $request->get_json_params();

		if (empty($params['title'])) {
			return new WP_Error('missing_title', 'Vendor title required', ['status' => 400]);
		}

		$vendor_id = wp_insert_post([
			'post_type'   => 'vendor',
			'post_title'  => sanitize_text_field($params['title']),
			'post_status' => 'publish',
		]);

		if (is_wp_error($vendor_id)) {
			return $vendor_id;
		}

		update_post_meta($vendor_id, '_vendor_phone', sanitize_text_field($params['phone'] ?? ''));
		update_post_meta($vendor_id, '_vendor_email', sanitize_email($params['email'] ?? ''));
		update_post_meta($vendor_id, '_vendor_address', sanitize_textarea_field($params['address'] ?? ''));

		return rest_ensure_response([
			'success' => true,
			'id'      => $vendor_id,
		]);
	}


	function pinaka_update_vendor(WP_REST_Request $request) {

		$vendor_id = (int) $request->get_param('id');
		$params = $request->get_json_params();

		if (empty($vendor_id) || get_post_type($vendor_id) !== 'vendor') {
			return new WP_Error('invalid_vendor', 'Invalid Vendor ID', ['status' => 400]);
		}

		$update_data = [
			'ID'         => $vendor_id,
			'post_title' => sanitize_text_field($params['title'] ?? get_the_title($vendor_id)),
		];

		$result = wp_update_post($update_data);

		if (is_wp_error($result)) {
			return $result;
		}

		if (isset($params['phone'])) {
			update_post_meta($vendor_id, '_vendor_phone', sanitize_text_field($params['phone']));
		}
		if (isset($params['email'])) {
			update_post_meta($vendor_id, '_vendor_email', sanitize_email($params['email']));
		}
		if (isset($params['address'])) {
			update_post_meta($vendor_id, '_vendor_address', sanitize_textarea_field($params['address']));
		}

		return rest_ensure_response([
			'success' => true,
			'id'      => $vendor_id,
		]);
	}

	function pinaka_delete_vendor(WP_REST_Request $request) {

		$vendor_id = (int) $request->get_param('id');

		if (empty($vendor_id) || get_post_type($vendor_id) !== 'vendor') {
			return new WP_Error('invalid_vendor', 'Invalid Vendor ID', ['status' => 400]);
		}

		$result = wp_delete_post($vendor_id, true);

		if ($result === false) {
			return new WP_Error('deletion_failed', 'Failed to delete vendor', ['status' => 500]);
		}

		return rest_ensure_response([
			'success' => true,
			'id'      => $vendor_id,
		]);
	}

}

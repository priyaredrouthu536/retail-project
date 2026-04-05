<?php
/**
 * REST API Custom Discount API controller
 *
 * Handles requests to the discount API endpoint.
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
use Automattic\WooCommerce\Utilities\OrderUtil;
/**
 * REST API Report Top Sellers controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Report_Sales_V1_Controller
 */
class Pinaka_Custom_Discount_Api_Controller {

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
	protected $rest_base = 'custom-discount';


    /**
	 * Register the routes for sales reports.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/apply/(?P<order_id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'custom_discount_api_handler'),
				'permission_callback' => array($this, 'check_user_role_permission'),
				'args'                => array(
					'order_id' => array(
						'required' => true,
						'type'     => 'integer',
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/remove/(?P<order_id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'remove_custom_discount_handler'),
				'permission_callback' => array($this, 'check_user_role_permission'),
				'args'                => array(
					'order_id' => array(
						'required' => true,
						'type'     => 'integer',
					),
				),
			)
		);

		register_rest_route(
            $this->namespace, 
             $this->rest_base . '/get-all-discounts-for-admin', 
             [
                'methods'  => 'GET',
                'callback' => array( $this, 'pinaka_get_all_discounts_for_admin' ),
                'permission_callback' => '__return_true', // or token
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
            ]
        );

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/create-discount',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_discount' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/delete-discount',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'delete_discount' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/by-ids',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'pinaka_get_products_by_ids' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/update-discount',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_discount' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);


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

	public function custom_discount_api_handler($request) {
		$order_id = absint($request->get_param('order_id'));
		$code     = sanitize_text_field($request->get_param('discount_code'));

		if (!$order_id || !$code) {
			return new WP_REST_Response(['success' => false, 'message' => 'Missing order ID or discount code.'], 400);
		}

		$order = wc_get_order($order_id);
		if (!$order || !$order instanceof WC_Order) {
			return new WP_REST_Response(['success' => false, 'message' => 'Invalid order ID.'], 404);
		}

		// Look up discount post by title
		$discount_post = get_page_by_title($code, OBJECT, 'discounts');
		if (!$discount_post || $discount_post->post_status !== 'publish') {
			return new WP_REST_Response(['success' => false, 'message' => 'Invalid or inactive discount code.'], 404);
		}

		// Load discount fields
		$discount_type   = get_post_meta($discount_post->ID, '_discount_type', true);
		$coupon_amount   = floatval(get_post_meta($discount_post->ID, '_coupon_amount', true));
		$min_spend       = floatval(get_post_meta($discount_post->ID, '_minimum_amount', true));
		$max_usage       = intval(get_post_meta($discount_post->ID, '_maximum_amount', true));
		$expiry_date     = get_post_meta($discount_post->ID, '_expiry_date', true);
		$usage_count     = intval(get_post_meta($discount_post->ID, '_discount_usage_count', true));
		$min_quantity    = intval(get_post_meta($discount_post->ID, '_min_qty', true));
		$max_quantity    = intval(get_post_meta($discount_post->ID, '_max_qty', true));
		$allowed_products = get_post_meta($discount_post->ID, '_product_ids', true); // should be an array

		if (!is_array($allowed_products)) {
			$allowed_products = [];
		}

		// Validate expiration
		if ($expiry_date && strtotime($expiry_date) < time()) {
			return new WP_REST_Response(['success' => false, 'message' => 'This discount code has expired.'], 400);
		}

		// Validate usage limit
		if ($max_usage && $usage_count >= $max_usage) {
			return new WP_REST_Response(['success' => false, 'message' => 'This discount code has reached its usage limit.'], 400);
		}

		$order_total = floatval($order->get_subtotal());
		$order_items = $order->get_items();
		
		$total_quantity = 0;
		$has_allowed_product = empty($allowed_products); // if no restriction, allow all

		foreach ($order_items as $item) {
			$product_id = $item->get_product_id();
			$quantity   = $item->get_quantity();

			$total_quantity += $quantity;

			if (in_array($product_id, $allowed_products)) {
				$has_allowed_product = true;
			}
		}

		// Validate min/max quantity
		if ($min_quantity && $total_quantity < $min_quantity) {
			return new WP_REST_Response(['success' => false, 'message' => 'Minimum quantity not met for this discount.'], 400);
		}
		if ($max_quantity && $total_quantity > $max_quantity) {
			return new WP_REST_Response(['success' => false, 'message' => 'Maximum quantity exceeded for this discount.'], 400);
		}

		// Validate product presence
		if (!$has_allowed_product) {
			return new WP_REST_Response(['success' => false, 'message' => 'This discount code is not applicable to the products in the order.'], 400);
		}

		// Validate min spend
		if ($min_spend && $order_total < $min_spend) {
			return new WP_REST_Response(['success' => false, 'message' => 'Minimum spend not met for this discount.'], 400);
		}

		// Calculate discount amount
		$discount_amount = ($discount_type === 'percent')
			? ($order_total * ($coupon_amount / 100))
			: $coupon_amount;

		// Add discount as a negative fee
		$fee = new WC_Order_Item_Fee();
		$fee->set_name(sprintf('Custom Discount (%s)', $code));
		$fee->set_amount(-$discount_amount);
		$fee->set_total(-$discount_amount);
		$fee->set_tax_status('none');
		$order->add_item($fee);
		$order->update_meta_data('_custom_discount_code', $code);

		// Increment usage count
		update_post_meta($discount_post->ID, '_discount_usage_count', $usage_count + 1);

		// Recalculate and save
		$order->calculate_totals();
		$order->save();

		return new WP_REST_Response([
			'success'         => true,
			'message'         => 'Discount applied to order.',
			'discount_code'   => $code,
			'discount_amount' => wc_price($discount_amount),
			'order_id'        => $order_id,
		]);
	}

	
	// public function custom_discount_api_handler($request) {
	// 	$order_id = absint($request->get_param('order_id'));
	// 	$code     = sanitize_text_field($request->get_param('discount_code'));
	
	// 	if (!$order_id || !$code) {
	// 		return new WP_REST_Response([
	// 			'success' => false,
	// 			'message' => 'Missing order ID or discount code.',
	// 		], 400);
	// 	}
	
	// 	$order = wc_get_order($order_id);
	// 	if (!$order || !is_a($order, 'WC_Order')) {
	// 		return new WP_REST_Response([
	// 			'success' => false,
	// 			'message' => 'Invalid order ID.',
	// 		], 404);
	// 	}
	
	// 	$custom_discounts = [
	// 		'BULK10' => ['type' => 'percent', 'amount' => 10, 'min_spend' => 20],
	// 		'FLAT20' => ['type' => 'fixed', 'amount' => 20, 'min_spend' => 100],
	// 	];
	
	// 	if (!isset($custom_discounts[$code])) {
	// 		return new WP_REST_Response([
	// 			'success' => false,
	// 			'message' => 'Invalid discount code.',
	// 		], 400);
	// 	}
	
	// 	$discount = $custom_discounts[$code];
	// 	$order_total = (float) $order->get_subtotal();
	
	// 	if ($order_total < (float) $discount['min_spend']) {
	// 		return new WP_REST_Response([
	// 			'success' => false,
	// 			'message' => sprintf('Minimum spend for %s is $%s.', $code, $discount['min_spend']),
	// 		], 400);
	// 	}
	
	// 	// Calculate discount amount
	// 	$discount_amount = ($discount['type'] === 'percent')
	// 		? $order_total * ((float) $discount['amount'] / 100)
	// 		: (float) $discount['amount'];
	
	// 	// Create negative fee item
	// 	$fee = new WC_Order_Item_Fee();
	// 	$fee->set_name(sprintf('Custom Discount (%s)', $code));
	// 	$fee->set_amount(-1 * abs((float) $discount_amount));
	// 	$fee->set_total(-1 * abs((float) $discount_amount));
	// 	$fee->set_tax_status('none'); // Important to prevent tax calculations
	// 	$order->add_item($fee);
	
	// 	// Recalculate totals (forces correct order total update)
	// 	$order->calculate_totals(false); // false disables taxes
	
	// 	// Save custom code to order meta
	// 	$order->update_meta_data('_custom_discount_code', $code);
	// 	$order->save();
	
	// 	return new WP_REST_Response([
	// 		'success' => true,
	// 		'message' => 'Discount applied to order.',
	// 		'order_id' => $order_id,
	// 		'discount_applied' => wc_price($discount_amount),
	// 	]);
	// }

	public function remove_custom_discount_handler( $request ) {
		$order_id     = absint( $request->get_param( 'order_id' ) );
		$discount_code = sanitize_text_field( $request->get_param( 'discount_code' ) );
	
		$order = wc_get_order( $order_id );
	
		if ( ! $order || ! $order instanceof WC_Order ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => 'Invalid order ID.' ], 404 );
		}
	
		$removed = false;
	
		foreach ( $order->get_fees() as $item_id => $fee ) {
			$fee_name = $fee->get_name();
	
			if (
				( $discount_code && stripos( $fee_name, $discount_code ) !== false ) ||
				( ! $discount_code && stripos( $fee_name, 'Custom Discount' ) !== false )
			) {
				$order->remove_item( $item_id );
				$removed = true;
			}
		}
	
		if ( $removed ) {
			$order->calculate_totals();
			$order->save();
	
			return new WP_REST_Response( [
				'success' => true,
				'message' => 'Custom discount removed.',
				'order_id' => $order_id,
			] );
		}
	
		return new WP_REST_Response( [
			'success' => false,
			'message' => 'No matching custom discount found on order.',
		], 404 );
	}
	public function delete_discount(WP_REST_Request $request)
	{
		$raw_body = $request->get_body();
		$params   = json_decode( $raw_body, true );

		if ( ! is_array( $params ) ) {
			$params = $request->get_params();
		}
		$discount_id = intval( $params['id'] ?? 0 );
    	$disc_type   = sanitize_text_field( (string) ( $params['type'] ?? '' ) );
		if ( $disc_type === 'multipack' ) 
		{
			$rows = get_option( 'multipack_discount_settings', [] );
			if ( ! is_array( $rows ) ) {
				$rows = [];
			}
			$removed = false;
			foreach ( $rows as $index => $row ) {
				if ( (int) $index !== (int) $discount_id ) {
					continue;
				}
				$used_count = isset( $row['used_count'] ) ? (int) $row['used_count'] : 0;
				if ( $used_count > 0 ) {
					return [
						'success' => false,
						'message' => 'This multipack discount has already been used and cannot be deleted.',
						'id'      => $discount_id,
						'type'    => 'multipack',
					];
				}
				unset( $rows[ $index ] );
				$removed = true;
				break;
			}
			if ( $removed ) {
				$rows = array_values( $rows ); // reindex array
				update_option( 'multipack_discount_settings', $rows );
			}
			return [
				'success' => $removed,
				'message' => $removed
					? 'Discount deleted successfully.'
					: 'Discount not found.',
				'id'      => $discount_id,
				'type'    => 'multipack',
			];
		}
		if (!$discount_id) {
			return new WP_Error('invalid_id', 'Invalid discount ID', ['status' => 400]);
		}
		$post = get_post($discount_id);
		if (!$post) {
			return new WP_Error('not_found', 'Discount not found', ['status' => 404]);
		}
		$deleted = wp_trash_post($discount_id);
		if (!$deleted) 
		{
			return new WP_Error('delete_failed', 'Failed to delete discount', ['status' => 500]);
		}
		return [
			'success' => true,
			'message' => 'Discount deleted successfully',
			'id'      => $discount_id,
			'type'    => $post->post_type
		];
	}

	public function pinaka_get_all_discounts_for_admin( WP_REST_Request $request ) {

		$page   = max( 1, (int) $request->get_param( 'page' ) );
		$limit  = max( 10, (int) $request->get_param( 'per_page' ) );
		$search = sanitize_text_field( $request->get_param( 'search' ) );
		$status = sanitize_text_field( $request->get_param( 'status' ) );
		$args = array(
			'post_type'      => 'discounts',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}
		$query = new WP_Query( $args );
		$discounts = array();
		foreach ( $query->posts as $post ) {
			$pr_id = (int) get_post_meta( $post->ID, '_product_label', true );
			$pr_name = '';$pr_price='';
			if($pr_id)
			{
				$product = wc_get_product( $pr_id );
				if($product)
				{
					$pr_name = $product->get_name();
					$pr_price = $product->get_price();
					$discounts['auto'][] = array(
						'id'               => $post->ID,
						'code'             => $post->post_title,
						'discount_type'    => get_post_meta( $post->ID, '_discount_type', true ),
						'coupon_amount'    => get_post_meta( $post->ID, '_discount_amount', true ),
						'start_date'       => get_post_meta( $post->ID, '_start_date', true ),
						'expiry_date'      => get_post_meta( $post->ID, '_expiry_date', true ),
						'product_id'       => $pr_id,
						'product_label'    => $pr_name,
						'selectedProductPrice' => $pr_price,
						'usage_limit'      => get_post_meta( $post->ID, '_usage_limit', true ),
						'qty'              => '',
						'discount_product_ids' => [],
						'pinaka_discount_auto_apply' => get_post_meta( $post->ID, '_pinaka_discount_auto_apply', true ),
						'type' => 'auto'
					);
				}
			}
			
		}
		$args = array(
			'post_type'      => 'mix_match_discounts',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
	
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		$query = new WP_Query( $args );
		foreach ( $query->posts as $post ) {
			$selected_id = (int) get_post_meta($post->ID, '_mix_match_parent_product_id', true);
			$selected_product = $selected_id ? wc_get_product($selected_id) : false;
			$prd_name = '';$product_price='';
			if ($selected_product)
			{
				$prd_name = $selected_product->get_name();
				$product_price = $selected_product->get_price();
				$selected_child_ids = (array) get_post_meta($post->ID, '_mix_match_child_product_ids', true);
				$discounts['mix_match'][] = array(
					'id'               => $post->ID,
					'code'             => $post->post_title,
					'discount_type'    => get_post_meta( $post->ID, '_mix_match_discount_type', true ),
					'coupon_amount'    => get_post_meta( $post->ID, '_mix_match_discount_amount', true ),
					'start_date'       => get_post_meta( $post->ID, '_start_date', true ),
					'expiry_date'      => get_post_meta( $post->ID, '_expiry_date', true ),
					'product_id' => $selected_id,
					'product_label'    => $prd_name,
					'selectedProductPrice' => $product_price,
					'pinaka_discount_auto_apply' => '',
					'discount_product_ids' => $selected_child_ids,
					'usage_limit' => '',
					'qty'  => '',
					'type' => 'mix_match'
				);
			}
		}
		$multipack_rules = get_option( 'multipack_discount_settings', [] );
		$cnt = 0;
		foreach ( $multipack_rules as $index => $rule ) {
			$raw = trim($rule['discount']);
			$is_percentage = is_string($raw) && strpos($raw, '%') !== false;
			$cnt++;
			$product_id  = (int) $rule['product_id'];
			$product = wc_get_product( $product_id );

			if ( $product ) {
				$discounts['multipack'][] = [
					'id'      => $index,
					'code'    => $product->get_name().'-'.esc_attr( $rule['qty'] ),
					'coupon_amount' => (float) $rule['discount'],
					'discount_type' => $is_percentage ? 'percent' : 'price',
					'start_date'       => $rule['start_date'],
					'expiry_date'     => $rule['end_date'],
					'product_id' => $product_id,
					'product_label'    => $product->get_name(),
					'selectedProductPrice' => $product->get_price(),
					'pinaka_discount_auto_apply' => '',
					'discount_product_ids' => [],
					'usage_limit' => $rule['order_usage'],
					'qty'              => esc_attr( $rule['qty'] ),
					'type' => 'multipack'
				];
			}
		}
		return rest_ensure_response( array(
			'data' => $discounts,
			'pagination' => array(
				'page'        => $page,
				'per_page'    => $limit,
				'total'       => (int) $query->found_posts,
				'total_pages' => (int) $query->max_num_pages,
				'has_more'    => $page < $query->max_num_pages,
			),
		) );
	}
	public function pinaka_get_products_by_ids(WP_REST_Request $request) {
		$raw_body = $request->get_body();
		$params   = json_decode( $raw_body, true );

		if ( ! is_array( $params ) ) {
			$params = $request->get_params(); // fallback
		}
		$ids = array_filter(
			array_map( 'intval', (array) ( $params['ids'] ?? [] ) )
		);
		if (empty($ids)) {
			return new WP_REST_Response(['data' => []], 200);
		}
		$query = new WP_Query([
			'post_type' => 'product',
			'post__in' => $ids,
			'orderby' => 'post__in',
			'posts_per_page' => -1
		]);

		$products = [];
		foreach ($query->posts as $post) {
			$products[] = [
			'id' => $post->ID,
			'name' => $post->post_title
			];
		}
		return new WP_REST_Response([
			'data' => $products
		], 200);
	}
	public function create_discount( WP_REST_Request $request ) {
		$raw_body = $request->get_body();
		$params   = json_decode( $raw_body, true );

		if ( ! is_array( $params ) ) {
			$params = $request->get_params(); // fallback safety
		}
		$code           = sanitize_text_field( (string) ( $params['code'] ?? '' ) );
		$discount_type  = sanitize_text_field( (string) ( $params['discount_type'] ?? '' ) );
		$coupon_amount  = floatval( $params['amount'] ?? 0 );
		$minimum_amount = floatval( $params['minimum_amount'] ?? 0 );
		$maximum_amount = floatval( $params['maximum_amount'] ?? 0 );

		$start_date     = sanitize_text_field( (string) ( $params['date_starts'] ?? '' ) );
		$expiry_date    = sanitize_text_field( (string) ( $params['date_expires'] ?? '' ) );

		$min_qty        = intval( $params['min_qty'] ?? 0 );
		$max_qty        = intval( $params['max_qty'] ?? 0 );

		$product_ids = array_filter(
			array_map( 'intval', (array) ( $params['product_ids'] ?? [] ) )
		);

		$usage_limit = intval( $params['usage_limit'] ?? 0 );

		$product_label = intval( $params['product_id'] ?? 0 );

		$pinaka_discount_auto_apply = sanitize_text_field(
			(string) ( $params['pinaka_discount_auto_apply'] ?? '' )
		);

		$type = sanitize_text_field( (string) ( $params['type'] ?? '' ) );

		$discounted_products = array_filter(
			array_map( 'intval', (array) ( $params['discount_product_ids'] ?? [] ) )
		);
		if ( $type === 'multipack' ) {

			$pack_qty = intval( $params['qty'] ?? 0 );

			$rows = get_option( 'multipack_discount_settings', [] );
			if ( ! is_array( $rows ) ) {
				$rows = [];
			}
			if ( $discount_type === 'percent' ) {
				$coupon_amount = rtrim( $coupon_amount, '%' ) . '%';
			}

			$product_id = $product_label;

			$row = [
				'product_id'  => $product_id,
				'qty'         => $pack_qty,
				'discount'    => $coupon_amount,
				'start_date'  => $start_date,
				'end_date'    => $expiry_date,
				'order_usage' => (int) $usage_limit,
			];

			// if ( isset( $index_map[ $new_key ] ) ) {
			// 	$rows[ $index_map[ $new_key ] ] = $row;
			// } else {
				$rows[] = $row;
			// }

			update_option( 'multipack_discount_settings', $rows );

			return new WP_REST_Response(
				[
					'success'      => true,
					'message'      => 'Multipack discount saved successfully.',
					// 'discount_key' => $new_key,
				],
				201
			);
		}
		else if($type === 'auto')
		{
			$discount_post = array(
				'post_title'  => $code,
				'post_type'   => 'discounts',
				'post_status' => 'publish',
			);
		}
		else
		{
			$discount_post = array(
				'post_title'  => $code,
				'post_type'   => 'mix_match_discounts',
				'post_status' => 'publish',
			);
		}
		$discount_id = wp_insert_post( $discount_post );
		if ( is_wp_error( $discount_id ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => 'Failed to create discount.' ], 500 );
		}
		if($type === 'auto')
		{
			update_post_meta( $discount_id, '_discount_type', $discount_type );
			update_post_meta( $discount_id, '_discount_amount', $coupon_amount );
		}
		if($type === 'mix_match')
		{
			update_post_meta( $discount_id, '_mix_match_discount_type', $discount_type );
			update_post_meta( $discount_id, '_mix_match_discount_amount', $coupon_amount );
		}
		if($minimum_amount)
		{
			update_post_meta( $discount_id, '_minimum_amount', $minimum_amount );
		}
		if($maximum_amount)
		{
			update_post_meta( $discount_id, '_maximum_amount', $maximum_amount );
		}
		update_post_meta( $discount_id, '_start_date', $start_date );
		update_post_meta( $discount_id, '_expiry_date', $expiry_date );
		if($min_qty)
		{
			update_post_meta( $discount_id, '_min_qty', $min_qty );
		}
		if($max_qty)
		{
			update_post_meta( $discount_id, '_max_qty', $max_qty );
		}
		if($product_ids)
		{
			update_post_meta( $discount_id, '_product_ids', $product_ids );
		}
		update_post_meta( $discount_id, '_discount_usage_count', 0 );
		if($usage_limit)
		{
			update_post_meta( $discount_id, '_usage_limit', $usage_limit );
		}
		if(isset($product_label) && $type === 'auto')
		{
			update_post_meta( $discount_id, '_product_label', $product_label );
		}
		if(isset($product_label) && $type === 'mix_match')
		{
			update_post_meta( $discount_id, '_mix_match_parent_product_id', $product_label );
			update_post_meta($discount_id,'_mix_match_child_product_ids',$discounted_products);
		}
		if($pinaka_discount_auto_apply === 'yes' && $type === 'auto')
		{
			update_post_meta( $discount_id, '_pinaka_discount_auto_apply', 'yes' );
		}
		return new WP_REST_Response( [ 'success' => true, 'message' => 'Discount created successfully.', 'discount_id' => $discount_id ], 201 );
	}
	// public function create_discount( WP_REST_Request $request ) {
	// 	// ---------- BASIC SANITIZATION ----------
	// 	$raw_body = $request->get_body();
	// 	$params   = json_decode( $raw_body, true );

	// 	if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $params ) ) {
	// 		$params = $request->get_params(); // fallback
	// 	}
	// 	$code           = sanitize_text_field( $request->get_param( 'code' ) );
	// 	$discount_type  = sanitize_text_field( $request->get_param( 'discount_type' ) );
	// 	$coupon_amount  = floatval( $request->get_param( 'amount' ) );
	// 	$minimum_amount = floatval( $request->get_param( 'minimum_amount' ) );
	// 	$maximum_amount = floatval( $request->get_param( 'maximum_amount' ) );

	// 	$start_date_raw  = sanitize_text_field( $request->get_param( 'date_starts' ) );
	// 	$expiry_date_raw = sanitize_text_field( $request->get_param( 'date_expires' ) );

	// 	$start_date  = ! empty( $start_date_raw ) ? date( 'Y-m-d', strtotime( $start_date_raw ) ) : '';
	// 	$expiry_date = ! empty( $expiry_date_raw ) ? date( 'Y-m-d', strtotime( $expiry_date_raw ) ) : '';

	// 	$min_qty     = intval( $request->get_param( 'min_qty' ) );
	// 	$max_qty     = intval( $request->get_param( 'max_qty' ) );
	// 	$usage_limit = intval( $request->get_param( 'usage_limit' ) ?: 0 );

	// 	$product_label = intval( $request->get_param( 'product_id' ) ?: 0 );
	// 	$type          = sanitize_text_field( $request->get_param( 'type' ) );
	// 	$pinaka_discount_auto_apply = sanitize_text_field( $request->get_param( 'pinaka_discount_auto_apply' ) );

	// 	$product_ids = array_filter(
	// 		array_map( 'intval', (array) $request->get_param( 'product_ids' ) )
	// 	);

	// 	$discounted_products = array_filter(
	// 		array_map( 'intval', (array) $request->get_param( 'discount_product_ids' ) )
	// 	);

	// 	// ---------- BASIC VALIDATION ----------
	// 	if ( empty( $code ) ) {
	// 		return new WP_REST_Response(
	// 			[ 'success' => false, 'message' => 'Discount code is required.' ],
	// 			400
	// 		);
	// 	}

	// 	if ( ! in_array( $type, [ 'multipack', 'auto', 'mix_match' ], true ) ) {
	// 		return new WP_REST_Response(
	// 			[ 'success' => false, 'message' => 'Invalid discount type.' ],
	// 			400
	// 		);
	// 	}

	// 	// ======================================================
	// 	// MULTIPACK DISCOUNT
	// 	// ======================================================
	// 	if ( $type === 'multipack' ) {

	// 		$pack_qty = intval( $request->get_param( 'qty' ) );

	// 		if ( $product_label <= 0 || $pack_qty <= 0 ) {
	// 			return new WP_REST_Response(
	// 				[ 'success' => false, 'message' => 'Product and quantity are required for multipack.' ],
	// 				400
	// 			);
	// 		}

	// 		$rows = get_option( 'multipack_discount_settings', [] );
	// 		if ( ! is_array( $rows ) ) {
	// 			$rows = [];
	// 		}

	// 		// IMPORTANT: store amount as numeric only
	// 		$row = [
	// 			'product_id'  => $product_label,
	// 			'qty'         => $pack_qty,
	// 			'discount'    => $coupon_amount,
	// 			'discount_type' => $discount_type,
	// 			'start_date'  => $start_date,
	// 			'end_date'    => $expiry_date,
	// 			'order_usage' => $usage_limit,
	// 			'used_count'  => 0,
	// 		];

	// 		$rows[] = $row;
	// 		update_option( 'multipack_discount_settings', $rows );

	// 		return new WP_REST_Response(
	// 			[
	// 				'success' => true,
	// 				'message' => 'Multipack discount saved successfully.',
	// 			],
	// 			201
	// 		);
	// 	}

	// 	// ======================================================
	// 	// AUTO / MIX MATCH POST CREATION
	// 	// ======================================================
	// 	$discount_post = [
	// 		'post_title'  => $code,
	// 		'post_status' => 'publish',
	// 		'post_type'   => ( $type === 'auto' ) ? 'discounts' : 'mix_match_discounts',
	// 	];

	// 	$discount_id = wp_insert_post( $discount_post );

	// 	if ( is_wp_error( $discount_id ) ) {
	// 		return new WP_REST_Response(
	// 			[ 'success' => false, 'message' => 'Failed to create discount.' ],
	// 			500
	// 		);
	// 	}

	// 	// ---------- COMMON META ----------
	// 	update_post_meta( $discount_id, '_start_date', $start_date );
	// 	update_post_meta( $discount_id, '_expiry_date', $expiry_date );
	// 	update_post_meta( $discount_id, '_discount_usage_count', 0 );

	// 	if ( $minimum_amount > 0 ) {
	// 		update_post_meta( $discount_id, '_minimum_amount', $minimum_amount );
	// 	}

	// 	if ( $maximum_amount > 0 ) {
	// 		update_post_meta( $discount_id, '_maximum_amount', $maximum_amount );
	// 	}

	// 	if ( $min_qty > 0 ) {
	// 		update_post_meta( $discount_id, '_min_qty', $min_qty );
	// 	}

	// 	if ( $max_qty > 0 ) {
	// 		update_post_meta( $discount_id, '_max_qty', $max_qty );
	// 	}

	// 	if ( ! empty( $product_ids ) ) {
	// 		update_post_meta( $discount_id, '_product_ids', $product_ids );
	// 	}

	// 	if ( $usage_limit > 0 ) {
	// 		update_post_meta( $discount_id, '_usage_limit', $usage_limit );
	// 	}

	// 	// ---------- TYPE-SPECIFIC META ----------
	// 	if ( $type === 'auto' ) {

	// 		update_post_meta( $discount_id, '_discount_type', $discount_type );
	// 		update_post_meta( $discount_id, '_discount_amount', $coupon_amount );

	// 		if ( $product_label > 0 ) {
	// 			update_post_meta( $discount_id, '_product_label', $product_label );
	// 		}

	// 		if ( $pinaka_discount_auto_apply === 'yes' ) {
	// 			update_post_meta( $discount_id, '_pinaka_discount_auto_apply', 'yes' );
	// 		}
	// 	}

	// 	if ( $type === 'mix_match' ) {

	// 		update_post_meta( $discount_id, '_mix_match_discount_type', $discount_type );
	// 		update_post_meta( $discount_id, '_mix_match_discount_amount', $coupon_amount );

	// 		if ( $product_label > 0 ) {
	// 			update_post_meta( $discount_id, '_mix_match_parent_product_id', $product_label );
	// 		}

	// 		if ( ! empty( $discounted_products ) ) {
	// 			update_post_meta( $discount_id, '_mix_match_child_product_ids', $discounted_products );
	// 		}
	// 	}

	// 	return new WP_REST_Response(
	// 		[
	// 			'success'     => true,
	// 			'message'     => 'Discount created successfully.',
	// 			'discount_id' => $discount_id,
	// 		],
	// 		201
	// 	);
	// }
	protected function get_multipack_used_count( $product_id, $qty ): int {

        if ( ! $product_id ) {
            return 0;
        }

        global $wpdb;

        $counts = [];

        // HPOS enabled?
        $hpos_enabled = wc_get_container()
            ->get( \Automattic\WooCommerce\Utilities\OrderUtil::class )
            ->custom_orders_table_usage_is_enabled();

        if ( $hpos_enabled ) {

            $orders_table = $wpdb->prefix . 'wc_orders';
            $meta_table   = $wpdb->prefix . 'wc_orders_meta';

            $sql = "
                SELECT om.meta_value AS pack_type, COUNT(*) AS total
                FROM {$orders_table} o
                INNER JOIN {$meta_table} om
                    ON o.id = om.order_id
                WHERE o.type = 'shop_order'
                AND o.status = 'wc-completed'
                AND om.meta_key = 'pack_type'
                GROUP BY om.meta_value
            ";

        } else {

            $sql = "
                SELECT pm.meta_value AS pack_type, COUNT(*) AS total
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm
                    ON p.ID = pm.post_id
                WHERE p.post_type = 'shop_order'
                AND p.post_status = 'wc-completed'
                AND pm.meta_key = 'pack_type'
                GROUP BY pm.meta_value
            ";
        }

        $results = $wpdb->get_results( $sql, ARRAY_A );

        foreach ( $results as $row ) {
            $counts[ $row['pack_type'] ] = (int) $row['total'];
        }
        $used = 0;
        $target = $product_id . '-' . $qty;
        foreach ( $counts as $pack_type => $total ) {
            if ( trim( $pack_type ) === $target ) {
                $used += $total;
            }
        }
        return $used;
    }
	public function update_discount( WP_REST_Request $request ) {
		$raw_body = $request->get_body();
		$params   = json_decode( $raw_body, true );

		if ( ! is_array( $params ) ) {
			$params = $request->get_params(); // fallback safety
		}
		$discount_id   = intval( $params['discount_id'] ?? 0 );
		$code          = sanitize_text_field( (string) ( $params['code'] ?? '' ) );
		$discount_type = sanitize_text_field( (string) ( $params['discount_type'] ?? '' ) );
		$coupon_amount = floatval( $params['amount'] ?? 0 );

		$minimum_amount = floatval( $params['minimum_amount'] ?? 0 );
		$maximum_amount = floatval( $params['maximum_amount'] ?? 0 );

		$start_date  = sanitize_text_field( (string) ( $params['date_starts'] ?? '' ) );
		$expiry_date = sanitize_text_field( (string) ( $params['date_expires'] ?? '' ) );

		$min_qty     = intval( $params['min_qty'] ?? 0 );
		$max_qty     = intval( $params['max_qty'] ?? 0 );
		$usage_limit = intval( $params['usage_limit'] ?? 0 );

		$product_ids = array_filter(
			array_map( 'intval', (array) ( $params['product_ids'] ?? [] ) )
		);

		$product_label = intval( $params['product_id'] ?? 0 );

		$pinaka_discount_auto_apply = sanitize_text_field(
			(string) ( $params['pinaka_discount_auto_apply'] ?? '' )
		);

		$type = sanitize_text_field( (string) ( $params['type'] ?? '' ) );

		$discounted_products = array_filter(
			array_map( 'intval', (array) ( $params['discount_product_ids'] ?? [] ) )
		);
		
		if ( $type === 'multipack' ) {

			$pack_qty = intval( $params['qty'] ?? 0 );

			$rows = get_option( 'multipack_discount_settings', [] );
			if ( ! is_array( $rows ) ) {
				$rows = [];
			}

			// Normalize percent discount
			if ( $discount_type === 'percent' ) {
				$coupon_amount = rtrim( $coupon_amount, '%' ) . '%';
			}
			$product_id = $product_label;

			foreach ( $rows as $row ) {
				if (
					isset( $row['discount_id'], $row['product_id'], $row['qty'] ) &&
					(int) $row['product_id'] === $product_id &&
					(int) $row['qty'] === $pack_qty &&
					(int) $row['discount_id'] !== $discount_id
				) {
					return new WP_REST_Response(
						[
							'success' => false,
							'message' => 'A multipack discount already exists for this product and quantity.',
						],
						409
					);
				}
			}
			$used_count = $this->get_multipack_used_count($product_id,$pack_qty);
			if ( $used_count && $used_count >= $usage_limit ) {
				return new WP_REST_Response(
					[
						'success' => false,
						'message' => 'Usage limit already reached. Cannot update this discount.',
					],
					409
				);
			}
			$row_data = [
				'product_id'  => $product_id,
				'qty'         => $pack_qty,
				'discount'    => $coupon_amount,
				'start_date'  => $start_date,
				'end_date'    => $expiry_date,
				'order_usage' => $usage_limit
			];

			if ( $discount_id !== null ) {
				$rows[ $discount_id ] = $row_data;
				$message = 'Multipack discount updated successfully.';
			} else {
				$rows[]  = $row_data;
				$message = 'Multipack discount created successfully.';
			}

			update_option( 'multipack_discount_settings', $rows );
			return new WP_REST_Response(
				[
					'success'     => true,
					'message'     => $message,
					'discount_id' => $discount_id,
				],
				200
			);
		}
		$discount_post = array(
			'ID'          => $discount_id,
			'post_title'  => $code,
		);

		$result = wp_update_post( $discount_post );

		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => 'Failed to update discount.' ], 500 );
		}
		if ( $type === 'mix_match' ) {
			update_post_meta( $discount_id, '_mix_match_discount_type', $discount_type );
			update_post_meta( $discount_id, '_mix_match_discount_amount', $coupon_amount );
		} else {
			update_post_meta( $discount_id, '_discount_type', $discount_type );
			update_post_meta( $discount_id, '_discount_amount', $coupon_amount );
		}
		// update_post_meta( $discount_id, '_discount_type', $discount_type );
		// update_post_meta( $discount_id, '_discount_amount', $coupon_amount );
		if($minimum_amount)
		{
			update_post_meta( $discount_id, '_minimum_amount', $minimum_amount );
		}
		if($maximum_amount)
		{
			update_post_meta( $discount_id, '_maximum_amount', $maximum_amount );
		}
		update_post_meta( $discount_id, '_start_date', $start_date );
		update_post_meta( $discount_id, '_expiry_date', $expiry_date );
		if($min_qty)
		{
			update_post_meta( $discount_id, '_min_qty', $min_qty );
		}
		if($max_qty)
		{
			update_post_meta( $discount_id, '_max_qty', $max_qty );
		}
		if($product_ids)
		{
			update_post_meta( $discount_id, '_product_ids', $product_ids );
		}
		if($usage_limit)
		{
			update_post_meta( $discount_id, '_usage_limit', $usage_limit );
		}
		if(isset($product_label) && $type === 'auto')
		{
			update_post_meta( $discount_id, '_product_label', $product_label );
		}
		if(isset($product_label) && $type === 'mix_match')
		{
			update_post_meta( $discount_id, '_mix_match_parent_product_id', $product_label );
			update_post_meta($discount_id,'_mix_match_child_product_ids',$discounted_products);
		}
		if($pinaka_discount_auto_apply === 'yes' && $type === 'auto')
		{
			update_post_meta( $discount_id, '_pinaka_discount_auto_apply', 'yes' );
		}
		return new WP_REST_Response( [ 'success' => true, 'message' => 'Discount updated successfully.', 'discount_id' => $discount_id ], 200 );
	}
}

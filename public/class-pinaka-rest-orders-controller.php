<?php
/**
 * REST API Orders controller
 *
 * Handles requests to the /orders endpoint.
 *
 * @package WooCommerce\RestApi
 * @since    2.6.0
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Orders controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Orders_V2_Controller
 */
class Pinaka_REST_Orders_Controller extends WC_REST_Orders_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'pinaka-pos/v1';

	/**
	 * Register the routes for orders.
	 */
	public function register_routes() {

		parent::register_routes();

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/items',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'order_items_callback' ),
					// 'permission_callback' => 'my_custom_permission_callback',
					'permission_callback' => array( $this, '[]' ),
					// 'args'                => $this->get_collection_params(),
				),
				// 'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/gst-report',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'order_items_gst_report_callback' ),
					// 'permission_callback' => 'my_custom_permission_callback',
					'permission_callback' => array( $this, '[]' ),
					// 'args'                => $this->get_collection_params(),
				),
				// 'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/total-orders',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'pinaka_get_total_orders' ),
					// 'permission_callback' => 'my_custom_permission_callback',
					'permission_callback' => array( $this, 'get_items_permissions_check' )
				),
				// 'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/completed-orders',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'pinaka_get_refund_total_orders' ),
					// 'permission_callback' => 'my_custom_permission_callback',
					'permission_callback' => array( $this, 'get_items_permissions_check' )
				),
				// 'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-amt-by-paymethod',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'pinaka_get_refund_amount_by_method' ),
					// 'permission_callback' => 'my_custom_permission_callback',
					'permission_callback' => array( $this, 'get_items_permissions_check' )
				),
				// 'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/refund-valid-user',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'pinaka_refund_valid_user' ),
					// 'permission_callback' => 'my_custom_permission_callback',
					'permission_callback' => array( $this, 'get_items_permissions_check' )
				),
				// 'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/full-order-refund',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'pinaka_full_order_refund' ),
					// 'permission_callback' => 'my_custom_permission_callback',
					'permission_callback' => array( $this, 'get_items_permissions_check' )
				),
				// 'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/partial-order-refund',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'pinaka_partial_order_refund' ),
					// 'permission_callback' => 'my_custom_permission_callback',
					'permission_callback' => array( $this, 'get_items_permissions_check' )
				),
				// 'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
		register_rest_route( 
			$this->namespace,
			'/' . $this->rest_base . '/add-discount',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'pinaka_pos_add_discount' ),
					// 'permission_callback' => 'my_custom_permission_callback',
					'permission_callback' => array( $this, 'get_items_permissions_check' )
				),
				// 'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route( 
			$this->namespace,
			'/' . $this->rest_base . '/add-payout',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'pinaka_pos_add_payout' ),
					// 'permission_callback' => 'my_custom_permission_callback',
					'permission_callback' => array( $this, 'get_items_permissions_check' )
				),
				// 'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route( 
			$this->namespace,
			'/' . $this->rest_base . '/add-cashback',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'pinaka_pos_add_cashback' ),
					// 'permission_callback' => 'my_custom_permission_callback',
					'permission_callback' => array( $this, 'get_items_permissions_check' )
				),
				// 'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/sync-offline-orders', 
			array(
			'methods'  => 'POST',
			'callback' => array( $this, 'sync_offline_orders'),
			// 'permission_callback' => 'my_custom_permission_callback',
			'permission_callback' => array( $this, 'get_items_permissions_check' )
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/order-counts-for-admin-app', 
			array(
			'methods'  => 'GET',
			'callback' => array( $this, 'get_all_order_status_counts'),
			// 'permission_callback' => 'my_custom_permission_callback',
			'permission_callback' => array( $this, 'get_items_permissions_check' )
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/custom-user-roles', 
			array(
			'methods'  => 'GET',
			'callback' => array( $this, 'pinaka_get_custom_user_roles'),
			// 'permission_callback' => 'my_custom_permission_callback',
			'permission_callback' => array( $this, 'get_items_permissions_check' )
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/issuing-coupons',
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'pinaka_issuing_coupons_api' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/create-invoice',
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'pinaka_create_invoice' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/scan-invoice',
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'pinaka_create_invoice' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
			]
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-mobile-partial-orders',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_mobile_partial_orders' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
			]
		);
	}
	public function get_mobile_partial_orders(WP_REST_Request $request)
	{
		$page     = $request->get_param('page') ? max(1, intval($request->get_param('page'))) : 1;
		$per_page = $request->get_param('per_page') ? max(1, intval($request->get_param('per_page'))) : 10;
		$orderby  = $request->get_param('orderby') ? sanitize_text_field($request->get_param('orderby')) : 'date_created_gmt';
		$order    = $request->get_param('order') ? sanitize_text_field($request->get_param('order')) : 'DESC';
		$include  = $request->get_param('include') ? intval($request->get_param('include')) : 0;

		global $wpdb;
		$wc_orders_table = $wpdb->prefix . 'wc_orders';

		$where = "WHERE type = 'shop_order' AND status = 'partial-refund'";

		if ($include) {
			$where .= $wpdb->prepare(" AND id = %d", $include);
		}

		$offset = ($page - 1) * $per_page;

		$sql = "
		SELECT id
		FROM {$wc_orders_table}
		{$where}
		ORDER BY date_created_gmt {$order}
		LIMIT %d OFFSET %d
		";

		$sql = $wpdb->prepare($sql, $per_page, $offset);

		$order_ids = $wpdb->get_col($sql);

		$response_orders = [];

		$controller = new WC_REST_Orders_Controller();

		foreach ($order_ids as $order_id) {

			$order = wc_get_order($order_id);

			if (!$order) {
				continue;
			}

			$data = $controller->prepare_object_for_response($order, $request);
			$response_orders[] = $controller->prepare_response_for_collection($data);
		}

		return rest_ensure_response($response_orders);
	}
	public function pinaka_issuing_coupons_api( WP_REST_Request $request ) {

		$params     = $request->get_params();
		$line_items = $params['line_items'] ?? [];

		if ( empty( $line_items ) ) {
			return new WP_Error(
				'pinaka_invalid_line_items',
				__( 'line_items are required', 'pinaka-pos' ),
				[ 'status' => 400 ]
			);
		}

		/**
		 * ✅ 1. Calculate order total from line_items subtotal
		 */
		$order_total = $this->pinaka_calculate_order_total_from_line_items( $line_items );

		if ( $order_total <= 0 ) {
			return new WP_Error(
				'pinaka_invalid_order_total',
				__( 'Calculated order total is invalid', 'pinaka-pos' ),
				[ 'status' => 400 ]
			);
		}

		/**
		 * ✅ 2. Create virtual order (NOT saved)
		 *     Only for product & category validation
		 */
		$order = $this->pinaka_create_virtual_order_from_items( $line_items );

		/**
		 * No real order
		 */
		$current_order_id = 0;

		/**
		 * ✅ 3. Reuse existing coupon validation logic
		 */
		$coupons = $this->pinaka_get_all_shop_coupons(
			$order_total,
			$current_order_id,
			$order
		);

		// Cleanup
		$order->remove_order_items();

		return rest_ensure_response( [
			'order_total' => $order_total,
			'coupons'     => $coupons,
		] );
	}

	private function pinaka_calculate_order_total_from_line_items( array $line_items ): float {

		$total = 0.0;

		foreach ( $line_items as $item ) {

			if ( ! isset( $item['subtotal'] ) ) {
				continue;
			}

			$total += (float) $item['subtotal'];
		}

		return round( $total, 2 );
	}

	private function pinaka_create_virtual_order_from_items( array $line_items ): WC_Order {

		$order = new WC_Order();

		foreach ( $line_items as $item ) {

			if ( empty( $item['product_id'] ) || empty( $item['quantity'] ) ) {
				continue;
			}

			$product_id = (int) ( $item['variation_id'] ?? $item['product_id'] );
			$product    = wc_get_product( $product_id );

			if ( ! $product ) {
				continue;
			}

			$order->add_product(
				$product,
				(int) $item['quantity']
			);
		}

		/**
		 * ❌ DO NOT SAVE ORDER
		 */

		return $order;
	}


	private function pinaka_get_all_shop_coupons( float $order_total, int $current_order_id, WC_Order $order ) {

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

			// ❗ Product & Category validation (WooCommerce-style)
			if ( ! $this->pinaka_is_coupon_applicable_for_order( $coupon, $order ) ) {
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

	/**
	 * Check whether a coupon is applicable for a given order
	 * (Products, Excluded Products, Categories, Excluded Categories)
	 */
	private function pinaka_is_coupon_applicable_for_order( WC_Coupon $coupon, WC_Order $order ): bool {

		$order_product_ids  = [];
		$order_category_ids = [];

		foreach ( $order->get_items() as $item ) {

			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$product_id   = $item->get_product_id();
			$variation_id = $item->get_variation_id();
			$pid          = $variation_id ?: $product_id;

			$order_product_ids[] = $pid;

			$parent_id     = wp_get_post_parent_id( $pid );
			$cat_source_id = $parent_id ?: $pid;

			$terms = get_the_terms( $cat_source_id, 'product_cat' );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$order_category_ids[] = $term->term_id;
				}
			}
		}

		$order_product_ids  = array_unique( $order_product_ids );
		$order_category_ids = array_unique( $order_category_ids );

		// Allowed products
		$allowed_products = $coupon->get_product_ids();
		if ( ! empty( $allowed_products ) ) {
			if ( empty( array_intersect( $allowed_products, $order_product_ids ) ) ) {
				return false;
			}
		}

		// Excluded products
		$excluded_products = $coupon->get_excluded_product_ids();
		if ( ! empty( array_intersect( $excluded_products, $order_product_ids ) ) ) {
			return false;
		}

		// Allowed categories
		$allowed_categories = $coupon->get_product_categories();
		if ( ! empty( $allowed_categories ) ) {
			if ( empty( array_intersect( $allowed_categories, $order_category_ids ) ) ) {
				return false;
			}
		}

		// Excluded categories
		$excluded_categories = $coupon->get_excluded_product_categories();
		if ( ! empty( array_intersect( $excluded_categories, $order_category_ids ) ) ) {
			return false;
		}

		return true;
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

	/**
	 * Get total orders callback.
	 *
	 * @param WP_REST_Request $request .
	 * @return array|WP_Error
	 */
	function pinaka_get_total_orders(WP_REST_Request $request) {
		$params = $request->get_params();

		$page     = isset($params['page']) ? max(1, intval($params['page'])) : 1;
		$per_page = isset($params['per_page']) ? max(1, intval($params['per_page'])) : 100;

		$statuses = isset($params['status']) ? explode(',', str_replace(' ', '', $params['status'])) : ['any'];
		$has_partial_refund = false;

		if ( in_array( 'partial-refund', $statuses, true ) ) {
			$has_partial_refund = true;
			$statuses = array_values(
				array_diff( $statuses, [ 'partial-refund' ] )
			);
		}
		// $query_args = [
		// 	'post_type'           => 'shop_order',
		// 	'post_status'         => [],
		// 	'posts_per_page'      => $per_page,
		// 	'paged'               => $page,
		// 	'orderby'             => 'date ID',
		// 	'order'               => 'DESC',
		// 	'ignore_sticky_posts' => true,
		// ];
		$query_args = [
			'post_type'           => 'shop_order',
			'post_status'         => [],
			'posts_per_page'      => $per_page,
			'paged'               => $page,
			'orderby'             => 'modified',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
		];
		// Statuses
		foreach ($statuses as $status) {
			$query_args['post_status'][] = strpos($status, 'wc-') === 0 ? $status : 'wc-' . $status;
		}
		
		$author = sanitize_text_field($params['author']) ?: get_current_user_id();

		// Author filter
		$query_args['meta_query'][] = [
			'key'     => '_wc_order_created_by',
			'value'   => $author,
			'compare' => '=',
		];

		// only act when created_via is provided
		if ( isset( $params['created_via'] ) && $params['created_via'] !== '' ) {
			if ( $params['created_via'] === 'rest-api' ) {
				$query_args['meta_query'][] = array(
					'key'     => 'order_created_via',
					'value'   => 'pinaka_pos',
					'compare' => '=',
				);
			} else {
				$query_args['meta_query'][] = array(
					'key'     => 'order_created_via',
					'value'   => $params['created_via'],
					'compare' => '=',
				);
			}
		}

		// Date query
		$date_query = [];
		$site_timezone = new DateTimeZone(wp_timezone_string());
		$utc_timezone  = new DateTimeZone('UTC');

		$today  = new DateTime( 'now', $site_timezone );
		$after  = ( clone $today )->modify( '-7 days' )->setTime( 0, 0, 0 );
		$before = ( clone $today )->setTime( 23, 59, 59 );

		$after->setTimezone( $utc_timezone );
		$before->setTimezone( $utc_timezone );
		
		$date_query[] = [
			'column'    => 'post_modified_gmt',
			'after'     => $after->format( 'Y-m-d H:i:s' ),
			'before'    => $before->format( 'Y-m-d H:i:s' ),
			'inclusive' => true,
		];
		if (!empty($date_query)) {
			$query_args['date_query'] = $date_query;
		}
		$total_query_args = $query_args;
		$total_query_args['posts_per_page'] = -1;
		$total_query_args['fields'] = 'ids'; // Only get IDs for performance

		$total_query = new \WC_Order_Query($total_query_args);
		$total_orders = $total_query->get_orders();
		$total_count = count($total_orders);

		// Get paginated orders
		$query  = new \WC_Order_Query($query_args);
		$orders = $query->get_orders();

		// Setup controllers
		$api_product = new WC_REST_Products_Controller();
		$api_var     = new WC_REST_Product_Variations_Controller();

		$formatted_orders = [];
		if($has_partial_refund)
		{
			$orders = [];
			global $wpdb;
			$wc_orders_table = $wpdb->prefix . 'wc_orders';
			$partial_sql = "
			SELECT 
				id,
				status,
				date_created_gmt,
				date_updated_gmt
			FROM {$wc_orders_table}
			WHERE type = 'shop_order' AND status = 'partial-refund'
			ORDER BY date_created_gmt DESC
			";
			$partial_orders = $wpdb->get_results( $partial_sql );
			$partial_orders_arr = [];
			foreach ( $partial_orders as $row ) {
				$order = wc_get_order( $row->id );
				if ( ! $order instanceof WC_Order ) {
					continue;
				}
				$partial_orders_arr[] = $order;
			}
			$orders = array_merge($orders,$partial_orders_arr);
		}
		foreach ($orders as $order) {
			if (!$order instanceof WC_Order) {
				continue;
			}
	
			$pos_client_id = $order->get_meta( '_pos_client_order_id', true );
			$order_id = $order->get_id();
			$refunded_item_ids = [];

			foreach ( $order->get_refunds() as $refund ) {
				foreach ( $refund->get_items() as $refund_item ) {

					$line_item_id = $refund_item->get_meta('_refunded_item_id', true);

					if ( ! $line_item_id ) {
						continue;
					}

					$refunded_item_ids[ $line_item_id ] = true;
				}
			}
			$refund_order_total      = (float) $order->get_total();
			$total_refunded          = (float) $order->get_total_refunded();
			$refund_remaining_amount = max( 0, $refund_order_total - $total_refunded );

			// Process line items
			$line_items = [];
			foreach ($order->get_items() as $item) {
				$product = wc_get_product($item->get_product_id());
				$image_id = $product ? $product->get_image_id() : null;
				$image_src = $image_id ? wp_get_attachment_url($image_id) : '';
				$item_meta_data = [];
				foreach ( $item->get_meta_data() as $meta ) {
					$item_meta_data[] = [
						'id'            => $meta->id,
						'key'           => $meta->key,
						'value'         => maybe_unserialize( $meta->value ),
						'display_key'   => wc_attribute_label( $meta->key ),
						'display_value' =>  maybe_unserialize( $meta->value )
					];
				}
				$is_refunded = isset( $refunded_item_ids[ $item->get_id() ] );
				if ( $order->has_status( 'refunded' ) ) {
    				$is_refunded = false;
				}
				$line = [
					'id'           => $item->get_id(),
					'name'         => $item->get_name(),
					'product_id'   => $item->get_product_id(),
					'variation_id' => $item->get_variation_id(),
					'quantity'     => $item->get_quantity(),
					'tax_class'    => $item->get_tax_class(),
					'subtotal'     => $item->get_subtotal(),
					'subtotal_tax' => $item->get_subtotal_tax(),
					'total'        => $item->get_total(),
					'total_tax'    => $item->get_total_tax(),
					'taxes'        => $item->get_taxes(),
					'meta_data'    => $item_meta_data,
					'is_refund_item' => $is_refunded,
				];

				if (!empty($line['variation_id'])) {
					$req_var = new WP_REST_Request('GET');
					$req_var->set_query_params(['id' => $line['variation_id']]);
					$res_var = $api_var->get_item($req_var);
					$line['product_variation_data'] = is_wp_error($res_var) ? null : $res_var->get_data();
				}

				// $req_prod = new WP_REST_Request('GET');
				// $req_prod->set_query_params(['id' => $line['product_id']]);
				// $res_prod = $api_product->get_item($req_prod);
				// $line['product_data'] = is_wp_error($res_prod) ? null : $res_prod->get_data();
				$line_items[] = $line;
			}

			// Process fee lines
			$fee_lines = [];
			foreach ($order->get_fees() as $fee) {
				$taxes = [];
				foreach ($fee->get_taxes()['total'] ?? [] as $tax_id => $total_tax) {
					$taxes[] = [
						'id'       => (int) $tax_id,
						'total'    => wc_format_decimal($total_tax),
						'subtotal' => '',
					];
				}

				$fee_lines[] = [
					'id'         => $fee->get_id(),
					'name'       => $fee->get_name(),
					'tax_class'  => $fee->get_tax_class(),
					'tax_status' => $fee->get_tax_status(),
					'amount'     => '',
					'total'      => wc_format_decimal($fee->get_total()),
					'total_tax'  => wc_format_decimal($fee->get_total_tax()),
					'taxes'      => $taxes,
					'meta_data'  => array_map(function ($meta) {
						return [
							'id'            => $meta->id,
							'key'           => $meta->key,
							'value'         => $meta->value,
							'display_key'   => $meta->key,
							'display_value' => $meta->value,
						];
					}, $fee->get_meta_data()),
				];
			}
			$coupons  = array_values( array_filter( array_map( function ( $item ) {
					$coupon_code = $item->get_code();

					// get coupon post ID quickly (no expensive WC_Coupon object yet)
					$coupon_id = wc_get_coupon_id_by_code( $coupon_code );

					// treat missing ID as non-internal (or adjust if you want to skip unknown codes)
					$is_internal = $coupon_id ? get_post_meta( $coupon_id, '_is_internal_coupon', true ) : '';

					// skip internal coupons
					if ( $is_internal === 'yes' ) {
						return null; // array_filter will remove nulls
					}

					// create WC_Coupon only for coupons we keep
					$coupon = new WC_Coupon( $coupon_code );

					return [
						'id'            => $item->get_id(),
						'code'          => $coupon_code,
						'discount'      => wc_format_decimal( $item->get_discount(), 2 ),
						'discount_tax'  => wc_format_decimal( $item->get_discount_tax(), 2 ),
						'meta_data'     => array_map( function ( $meta ) {
							return [
								'id'            => $meta->id,
								'key'           => $meta->key,
								'value'         => $meta->value,
								'display_key'   => $meta->key,
								'display_value' => $meta->value,
							];
						}, $item->get_meta_data() ),
						'discount_type' => $coupon->get_discount_type(),
						// 'nominal_amount'=> floatval( $item->get_discount() ),
						// 'free_shipping' => (bool) $coupon->get_free_shipping(),
					];
				}, $order->get_items( 'coupon' ) ), 'is_array' ) );
			$formatted_order = [
				'id'              => $pos_client_id ? (int)$pos_client_id : $order_id,
				'woo_order_id'     => $order_id,
				'status'               => $order->get_status(),
				"date_created" 		   => $order->get_date_created()->date('Y-m-d H:i:s'),
				"date_modified"		   => $order->get_date_modified()->date('Y-m-d H:i:s'),
				'total'                => $order->get_total(),
				'discount_total'       => $order->get_discount_total(),
				'discount_tax'         => $order->get_discount_tax(),
				'total_tax'            => $order->get_total_tax(),
				'created_via'          => $order->get_created_via(),
				'author'			   => $order->get_meta( '_wc_order_created_by' ),
				'currency'                 => esc_attr( get_woocommerce_currency() ),
				'currency_symbol'          => get_option("currency_symbol"), // e.g., '₹'
				'date_completed'       => $order->get_date_completed() ? $order->get_date_completed()->date('c') : null,
				'date_paid'            => $order->get_date_paid() ? $order->get_date_paid()->date('c') : null,
				'coupon_lines' 			=> $coupons, // keep only non-null results
				'line_items'           => $line_items,
				'fee_lines'            => $fee_lines,
				'refunds'              => $order->get_refunds(),
				'refund_order_total'   => $refund_order_total,
				'refund_total' => $total_refunded,
				'net_payment' => $refund_remaining_amount
			];

			if ($order_type = get_post_meta($order_id, '_order_type', true)) {
				$formatted_order['order_type'] = $order_type;
			}

			$formatted_orders[] = $formatted_order;
		}

		return new WP_REST_Response([
			'order_total_count' => $total_count,
			'page'              => $page,
			'per_page'          => $per_page,
			'orders_data'       => $formatted_orders,
		]);
	}

	function pinaka_get_refund_total_orders(WP_REST_Request $request) {
		$params = $request->get_params();

		$page     = isset($params['page']) ? max(1, intval($params['page'])) : 1;
		$per_page = isset($params['per_page']) ? max(1, intval($params['per_page'])) : 100;

		// $statuses = isset($params['status']) ? explode(',', str_replace(' ', '', $params['status'])) : ['any'];

		$query_args = [
			'post_type'           => 'shop_order',
			'post_status'         => [],
			'posts_per_page'      => $per_page,
			'paged'               => $page,
			'orderby'             => 'modified',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
		];

		// Statuses
		// foreach ($statuses as $status) {
		// 	$query_args['post_status'][] = strpos($status, 'wc-') === 0 ? $status : 'wc-' . $status;
		// }
		$query_args['post_status'] = [ 'wc-completed'];
		
		// $author = sanitize_text_field($params['author']) ?: get_current_user_id();

		// // Author filter
		// $query_args['meta_query'][] = [
		// 	'key'     => '_wc_order_created_by',
		// 	'value'   => $author,
		// 	'compare' => '=',
		// ];
			
		// only act when created_via is provided
		if ( isset( $params['created_via'] ) && $params['created_via'] !== '' ) {
			if ( $params['created_via'] === 'rest-api' ) {
				$query_args['meta_query'][] = array(
					'key'     => 'order_created_via',
					'value'   => 'pinaka_pos',
					'compare' => '=',
				);
			} else {
				$query_args['meta_query'][] = array(
					'key'     => 'order_created_via',
					'value'   => $params['created_via'],
					'compare' => '=',
				);
			}
		}

		// Date query
		$date_query = [];
		$site_timezone = new DateTimeZone(wp_timezone_string());
		$utc_timezone  = new DateTimeZone('UTC');
		
		$today  = new DateTime( 'now', $site_timezone );
		$after  = ( clone $today )->modify( '-7 days' )->setTime( 0, 0, 0 );
		$before = ( clone $today )->setTime( 23, 59, 59 );

		// Override AFTER if provided
		if ( ! empty( $params['after'] ) && is_string( $params['after'] ) ) {
			try {
				if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $params['after'] ) ) {
					$after = new DateTime( $params['after'] . ' 00:00:00', $site_timezone );
				} else {
					$after = new DateTime( $params['after'], $site_timezone );
				}
			} catch ( Exception $e ) {}
		}

		// Override BEFORE if provided
		if ( ! empty( $params['before'] ) && is_string( $params['before'] ) ) {
			try {
				if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $params['before'] ) ) {
					$before = new DateTime( $params['before'] . ' 23:59:59', $site_timezone );
				} else {
					$before = new DateTime( $params['before'], $site_timezone );
				}
			} catch ( Exception $e ) {}
		}

		// Convert to UTC (IMPORTANT for post_date_gmt)
		$after->setTimezone( $utc_timezone );
		$before->setTimezone( $utc_timezone );
		
		$date_query[] = [
			'column'    => 'post_modified_gmt',
			'after'     => $after->format( 'Y-m-d H:i:s' ),
			'before'    => $before->format( 'Y-m-d H:i:s' ),
			'inclusive' => true,
		];
		if (!empty($date_query)) {
			$query_args['date_query'] = $date_query;
		}

		// Get total matching orders without pagination
		$total_query_args = $query_args;
	
		$total_query_args['posts_per_page'] = -1;
		$total_query_args['fields'] = 'ids'; // Only get IDs for performance

		$total_query = new \WC_Order_Query($total_query_args);
		$total_orders = $total_query->get_orders();
		$total_count = count($total_orders);

		// Get paginated orders
		$query  = new \WC_Order_Query($query_args);
		$orders = $query->get_orders();

		// Setup controllers
		$api_product = new WC_REST_Products_Controller();
		$api_var     = new WC_REST_Product_Variations_Controller();

		$formatted_orders = [];
		global $wpdb;
		$wc_orders_table = $wpdb->prefix . 'wc_orders';
		$partial_sql = "
		SELECT 
			id,
			status,
			date_created_gmt,
			date_updated_gmt
		FROM {$wc_orders_table}
		WHERE type = 'shop_order' AND status = 'partial-refund'
		ORDER BY date_created_gmt DESC
		";
		$partial_orders = $wpdb->get_results( $partial_sql );

		$partial_orders_arr = [];

		foreach ( $partial_orders as $row ) {

			$order = wc_get_order( $row->id );

			if ( ! $order instanceof WC_Order ) {
				continue;
			}

			$partial_orders_arr[] = $order;
		}
		
		$pinaka_discount_product_ids = get_option( 'pinaka_discount_product_id', 0 );
		$orders = array_merge($orders,$partial_orders_arr);
		foreach ($orders as $order) {
			if (!$order instanceof WC_Order) {
				continue;
			}
			$order_disc_total = 0;
			$pos_client_id = $order->get_meta( '_pos_client_order_id', true );
			$order_id = $order->get_id();

			$line_items = [];
			$refunded_item_ids = [];

			foreach ( $order->get_refunds() as $refund ) {
				foreach ( $refund->get_items() as $refund_item ) {

					$line_item_id = $refund_item->get_meta('_refunded_item_id', true);

					if ( ! $line_item_id ) {
						continue;
					}

					$refunded_item_ids[ $line_item_id ] = true;
				}
			}
			foreach ($order->get_items() as $item) {
				if(isset($refunded_item_ids[ $item->get_id() ]))
				{
					continue;
				}
				$product = wc_get_product($item->get_product_id());
				$image_id = $product ? $product->get_image_id() : null;
				$image_src = $image_id ? wp_get_attachment_url($image_id) : '';
				$item_meta_data = [];
				foreach ( $item->get_meta_data() as $meta ) {
					$item_meta_data[] = [
						'id'            => $meta->id,
						'key'           => $meta->key,
						'value'         => maybe_unserialize( $meta->value ),
						'display_key'   => wc_attribute_label( $meta->key ),
						'display_value' =>  maybe_unserialize( $meta->value )
					];
				}
				$discount_type = $item->get_meta('_pos_discount_type', true);
				$has_discount  = ! empty($discount_type);

				$discount_label = '';

				if ($discount_type === 'auto') {
					$discount_label = 'Auto Discount';
				} elseif ($discount_type === 'multipack') {
					$discount_label = 'Multipack Discount';
				} elseif ($discount_type === 'mixmatch') {
					$discount_label = 'Combo Discount';
				}
				
				// if(!$is_refunded)
				// {
					$line = [
						'id'           => $item->get_id(),
						'name'         => $item->get_name(),
						'product_id'   => $item->get_product_id(),
						'variation_id' => $item->get_variation_id(),
						'quantity'     => $item->get_quantity(),
						'tax_class'    => $item->get_tax_class(),
						'subtotal'     => $item->get_subtotal(),
						'subtotal_tax' => $item->get_subtotal_tax(),
						// 'total'        => $final_item_total,
						'total'        => $item->get_total(),
						'is_items_has_discount' => $has_discount ? 'Yes' : 'No',
						'item_discount_type'     => $discount_label,
						'total_tax'    => $item->get_total_tax(),
						'taxes'        => $item->get_taxes(),
						'image'		   => [
							'id'  => $image_id,
							'src' => $image_src,
						],
						'meta_data'    => $item_meta_data,
					];
				// }
				// if (!empty($line['variation_id'])) {
				// 	$req_var = new WP_REST_Request('GET');
				// 	$req_var->set_query_params(['id' => $line['variation_id']]);
				// 	$res_var = $api_var->get_item($req_var);
				// 	$line['product_variation_data'] = is_wp_error($res_var) ? null : $res_var->get_data();
				// }

				// $req_prod = new WP_REST_Request('GET');
				// $req_prod->set_query_params(['id' => $line['product_id']]);
				// $res_prod = $api_product->get_item($req_prod);
				// $line['product_data'] = is_wp_error($res_prod) ? null : $res_prod->get_data();

				$line_items[] = $line;
				$absol_prod_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
				if($absol_prod_id == $pinaka_discount_product_ids)
				{
					$order_disc_total = abs($item->get_total());
				}
			}

			// Process fee lines
			$fee_lines = [];
			foreach ($order->get_fees() as $fee) {
				$taxes = [];
				foreach ($fee->get_taxes()['total'] ?? [] as $tax_id => $total_tax) {
					$taxes[] = [
						'id'       => (int) $tax_id,
						'total'    => wc_format_decimal($total_tax),
						'subtotal' => '',
					];
				}

				$fee_lines[] = [
					'id'         => $fee->get_id(),
					'name'       => $fee->get_name(),
					'tax_class'  => $fee->get_tax_class(),
					'tax_status' => $fee->get_tax_status(),
					'amount'     => '',
					'total'      => wc_format_decimal($fee->get_total()),
					'total_tax'  => wc_format_decimal($fee->get_total_tax()),
					'taxes'      => $taxes,
					'meta_data'  => array_map(function ($meta) {
						return [
							'id'            => $meta->id,
							'key'           => $meta->key,
							'value'         => $meta->value,
							'display_key'   => $meta->key,
							'display_value' => $meta->value,
						];
					}, $fee->get_meta_data()),
				];
			}
			$coupons  = array_values( array_filter( array_map( function ( $item ) {
					$coupon_code = $item->get_code();

					// get coupon post ID quickly (no expensive WC_Coupon object yet)
					$coupon_id = wc_get_coupon_id_by_code( $coupon_code );

					// treat missing ID as non-internal (or adjust if you want to skip unknown codes)
					$is_internal = $coupon_id ? get_post_meta( $coupon_id, '_is_internal_coupon', true ) : '';

					// skip internal coupons
					if ( $is_internal === 'yes' ) {
						return null; // array_filter will remove nulls
					}

					// create WC_Coupon only for coupons we keep
					$coupon = new WC_Coupon( $coupon_code );

					return [
						'id'            => $item->get_id(),
						'code'          => $coupon_code,
						'discount'      => wc_format_decimal( $item->get_discount(), 2 ),
						'discount_tax'  => wc_format_decimal( $item->get_discount_tax(), 2 ),
						'meta_data'     => array_map( function ( $meta ) {
							return [
								'id'            => $meta->id,
								'key'           => $meta->key,
								'value'         => $meta->value,
								'display_key'   => $meta->key,
								'display_value' => $meta->value,
							];
						}, $item->get_meta_data() ),
						'discount_type' => $coupon->get_discount_type(),
						// 'nominal_amount'=> floatval( $item->get_discount() ),
						// 'free_shipping' => (bool) $coupon->get_free_shipping(),
					];
				}, $order->get_items( 'coupon' ) ), 'is_array' ) );
			$total_without_tax = (float) $order->get_total() - (float) $order->get_total_tax() - (float) $order->get_meta( '_order_discount', true );
			$order_type = ( $order->get_created_via() === 'rest-api' ) ? 'Shop Order' : $order->get_created_via();
			// $refunded_data = [];

			// foreach ( $order->get_refunds() as $refund ) {

			// 	foreach ( $refund->get_items() as $refund_item ) {

			// 		$line_item_id = $refund_item->get_meta('_refunded_item_id');

			// 		if ( ! $line_item_id ) {
			// 			continue;
			// 		}

			// 		if ( ! isset( $refunded_data[$line_item_id] ) ) {
			// 			$refunded_data[$line_item_id] = [
			// 				'qty'   => 0,
			// 				'total' => 0,
			// 				'tax'   => 0,
			// 			];
			// 		}

			// 		$refunded_data[$line_item_id]['qty']   += abs( $refund_item->get_quantity() );
			// 		$refunded_data[$line_item_id]['total'] += abs( $refund_item->get_total() );
			// 		$refunded_data[$line_item_id]['tax']   += abs( $refund_item->get_total_tax() );
			// 	}
			// }
			// $refund_line_items = [];

			// foreach ( $order->get_items() as $item ) {

			// 	$item_id = $item->get_id();

			// 	$refunded_qty   = $refunded_data[$item_id]['qty']   ?? 0;
			// 	$refunded_total = $refunded_data[$item_id]['total'] ?? 0;
			// 	$refunded_tax   = $refunded_data[$item_id]['tax']   ?? 0;

			// 	$net_qty   = $item->get_quantity() - $refunded_qty;
			// 	$net_total = $item->get_total() - $refunded_total;
			// 	$net_tax   = $item->get_total_tax() - $refunded_tax;

			// 	$item_total_with_tax =
			// 		$item->get_total() +
			// 		$item->get_total_tax();

			// 	$item_refunded_with_tax =
			// 		$refunded_total +
			// 		$refunded_tax;

			// 	$net_payment =
			// 		$item_total_with_tax -
			// 		$item_refunded_with_tax;
			// 	if($order->get_status() === 'refunded')
			// 	{
			// 		$net_payment = $net_tax = $net_total = 0;
			// 	}
			// 	$lines = [
			// 		'id'                 => $item_id,
			// 		'name'               => $item->get_name(),

			// 		'ordered_quantity'   => $item->get_quantity(),
			// 		'refunded_quantity'  => $refunded_qty,
			// 		'remaining_quantity' => $net_qty,

			// 		'total'              => $item->get_total(),
			// 		'total_tax'          => $item->get_total_tax(),

			// 		'refunded_total'     => round($refunded_total,2),
			// 		'refunded_tax'       => round($refunded_tax,2),

			// 		'remaining_total'    => round($net_total,2),
			// 		'remaining_tax'      => round($net_tax,2),
			// 		'net_payment'        => round($net_payment,2),
			// 	];

			// 	$refund_line_items[] = $lines;
			// }

			$formatted_order = [
				'id'              => $order_id,
				'order_id'     => $pos_client_id ? (int)$pos_client_id : $order_id,
				'status'               => $order->get_status() === 'partial-refund' ? 'Partially Refunded' : $order->get_status(),
				"date" 		   => $order->get_date_created()->date('Y-m-d H:i:s'),
				"date_modified"		   => $order->get_date_modified()->date('Y-m-d H:i:s'),
				'currency'             => $order->get_currency(),
				// 'prices_include_tax'   => $order->get_prices_include_tax(),
				// 'discount_total'       => $coupons ? $order->get_discount_total() : '0.00',
				// 'discount_tax'         => $coupons ? $order->get_discount_tax() : '0.00',
				// 'shipping_total'       => $order->get_shipping_total(),
				// 'shipping_tax'         => $order->get_shipping_tax(),
				// 'cart_tax'             => $order->get_cart_tax(),
				'amount'    => wc_format_decimal( max( 0, $total_without_tax ), 2 ),
				'items_tax'            => $order->get_total_tax(),
				'total'                => $order->get_total(),
				// 'order_key'            => $order->get_order_key(),
				'transaction_id'       => $this->pinaka_get_order_transaction_ids($order->get_id()),
				'payment_method'       => $this->pinaka_get_order_payment_methods( $order->get_id() ),
				'discount'             => $order_disc_total ?: 0,
				'order_type'           => $order_type,
				'author'			   => $order->get_meta( '_wc_order_created_by' ),
				'currency'                 => esc_attr( get_woocommerce_currency() ),
				'currency_symbol'          => get_option("currency_symbol"), // e.g., '₹'
				'date_completed'       => $order->get_date_completed() ? $order->get_date_completed()->date('c') : null,
				'date_paid'            => $order->get_date_paid() ? $order->get_date_paid()->date('c') : null,
				'number'               => $order->get_order_number(),
				'coupon_lines' 			=> $coupons,
				'line_items'           => $line_items,
				'fee_lines'            => $fee_lines,
				// 'refunds'              => $order->get_refunds(),
				// 'refunds'              => $refund_line_items
			];

			if ($order_type = get_post_meta($order_id, '_order_type', true)) {
				$formatted_order['order_type'] = $order_type;
			}
			
			$formatted_orders[] = $formatted_order;
		}

		return new WP_REST_Response([
			'order_total_count' => $total_count,
			'page'              => $page,
			'per_page'          => $per_page,
			'orders_data'       => $formatted_orders,
		]);
	}
	function pinaka_get_refund_amount_by_method(WP_REST_Request $request)
	{
		$params = $request->get_json_params();
		global $wpdb;
		$pos_client_id = (string) $params['order_id'];
		$order_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT order_id 
				FROM {$wpdb->prefix}wc_orders_meta
				WHERE meta_key = %s
				AND meta_value = %s
				LIMIT 1",
				'_pos_client_order_id',
				$pos_client_id
			)
		);

		$static_refund_type = $params['refund_type'] ?: 'Full';
		$refund_type = strtolower($static_refund_type);
		$items          = $params['items'] ?? [];
		if ( ! $order_id ) {
			return new WP_Error(
				'invalid_order',
				'Invalid order ID',
				[ 'status' => 400 ]
			);
		}
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_REST_Response( [ 'error' => 'Order not found' ], 404 );
		}
		if(!$refund_type)
		{
			return new WP_Error(
				'invalid_refund_type',
				'Invalid Refund Type. valid Refund Types are [Full, Partial]',
				[ 'status' => 400 ]
			);
		}
		if ( $refund_type=== 'partial' && empty( $items ) && ! is_array( $items )) {
			return new WP_Error( 'no_items', 'Please select items', [ 'status' => 400 ] );
		}
		$methods = [];
		$method_totals = [];
		$grand_total   = 0;
		if($refund_type === 'partial')
		{
			$has_any_discount = false;
			$discounted_items = [];
			$discount_posts = get_posts([
				'post_type'      => 'mix_match_discounts',
				'post_status'    => 'publish',
				'posts_per_page' => -1
			]);
			$order_items = $order->get_items( 'line_item' );
			foreach ( $items as $row ) {
				$item_id           = absint( $row['order_item_id'] );
				$order_item = $order->get_item( $item_id );
				if ( ! isset( $order_items[ $item_id ] )) {
					return new WP_Error(
						'invalid_item',
						'Invalid refund item',
						[ 'status' => 400 ]
					);
				}
				if ( ! $order_item ) {
					return new WP_Error( 'invalid_item', 'Invalid refund item', [ 'status' => 400 ] );
				}
				$item_amt = (float) $row['order_item_amount'];
				$grand_total += $item_amt;
				$discount_rule_id = $order_item->get_meta( '_pos_discount_rule_id', true );
				if ( ! empty( $discount_rule_id ) ) {
					$has_any_discount = true;
					$discounted_items[] = $order_item->get_name();
				}
				$product_id = $order_item->get_variation_id()? $order_item->get_variation_id() : $order_item->get_product_id();
				foreach ($discount_posts as $post) {
					$parent_id = (int) get_post_meta($post->ID, '_mix_match_parent_product_id', true);
					$child_ids = (array) get_post_meta($post->ID, '_mix_match_child_product_ids', true);
					if ($product_id == $parent_id || in_array($product_id, $child_ids)) {
						$has_any_discount = true;
						$discounted_items[] = $order_item->get_name();
						break;
					}
				}
			}
			if ( $has_any_discount ) {
				return new WP_Error(
					'discounted_items_found',
					sprintf(
						'Refund not allowed. Discount applied to: %s',
						implode( ', ', array_unique( $discounted_items ) )
					),
					[ 'status' => 400 ]
				);
			}
		}
		if($refund_type === 'full')
		{
			$args = [
				'post_type'      => 'payments',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => [
					[
						'key'   => '_payment_order_id',
						'value' => $order_id,
					]
				],
			];
			
			$payment_ids = get_posts( $args );

			foreach ( $payment_ids as $payment_id ) {

				$amount = (float) get_post_meta( $payment_id, '_payment_amount', true );
				if ( $amount <= 0 ) {
					continue;
				}

				$method = get_post_meta( $payment_id, '_payment_method', true );
				$method = $method ? ucfirst( strtolower( $method ) ) : 'Cash';
			
				if ( ! isset( $method_totals[ $method ] ) ) {
					$method_totals[ $method ] = 0;
				}

				$method_totals[ $method ] += $amount;
				$grand_total += $amount;
			}

			$order_total = (float) $order->get_total();
			if ( $grand_total > $order_total ) {
				$grand_total = $order_total;
			}
			if ( $grand_total <= 0 ) {
				$grand_total = $order_total;
			}
			
			foreach ( $method_totals as $method => $amount ) {
				$methods[] = [
					'method' => $method,
					'amount' => round( $amount, 2 ),
				];
			}
			$refund_order_total      = (float) $order->get_total();
			$total_refunded          = (float) $order->get_total_refunded();
			$grand_total = max( 0, $refund_order_total - $total_refunded );
		}

		return [
			'success'  => true,
    		'message'  => 'Refund amount calculated successfully',
			'order_id' => $order_id,
			'methods'  => $methods,
			'total'    => round( $grand_total, 2 ),
		];
	}
	function pinaka_refund_valid_user(WP_REST_Request $request)
	{
		$params = $request->get_params();
		$employee_pin = isset($params['employee_pin']) ? $params['employee_pin'] : 0;
		if(!$employee_pin)
		{
			return new WP_Error('invalid_pin', __('Invalid PIN or user not found.', 'pinaka-pos'),[ 'status' => 400 ]);
		}
		// Validate PIN (must be exactly 6 digits)
		if (!preg_match('/^\d{6}$/', $employee_pin)) {
			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 400,
					'code'       => 'invalid_pin_format',
					'message'    => esc_html__('PIN must be exactly 6 digits.', 'pinaka-pos'),
					'data'       => array(),
				),
				400
			);
		}

		$users = get_users([
			'meta_key'   => 'emp_login_pin',
			'meta_value' => $employee_pin,
			'number'     => 1,
		]);

		if (empty($users)) {
			return new WP_Error('invalid_pin', __('Invalid PIN or user not found.', 'pinaka-pos'),[ 'status' => 400 ]);
		}

		$user = $users[0];
		$user_id = (int) $user->ID;
		$login_user_id = (int) get_current_user_id();
		if ( $user_id !== $login_user_id ) {
			return new WP_Error(
				'unauthorized_user',
				__( 'You are not authorized to access this resource.', 'pinaka-pos' ),
				[ 'status' => 403 ]
			);
		}
		return new WP_REST_Response(
			[
				'success' => true,
				'message' => 'User verified successfully'
			],
			200
		);
	}
	function pinaka_full_order_refund(WP_REST_Request $request)
	{
		$params = $request->get_json_params();
		global $wpdb;
		$pos_client_id = (string) $params['order_id'];
		$order_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT order_id 
				FROM {$wpdb->prefix}wc_orders_meta
				WHERE meta_key = %s
				AND meta_value = %s
				LIMIT 1",
				'_pos_client_order_id',
				$pos_client_id
			)
		);
		$refund_amount = $params['amount'] ?: 0;
		$reason = $params['reason'] ?: '';
		$items_reusable = strtolower( $params['items_reusable'] ?? 'no' );
		if(!$order_id)
		{
			return new WP_Error( 'invalid_order_id', 'Invalid Order Id', [ 'status' => 400 ] );
		}
		$order = wc_get_order( $order_id );
		if ( ! $order )
		{
			return new WP_Error( 'invalid_order', 'Invalid Order', [ 'status' => 400 ] );
		}
		
		if ( $order->get_remaining_refund_amount() <= 0 ) {
			return new WP_Error(
				'order_already_refunded',
				'This order has already been fully refunded.',
				[ 'status' => 400 ]
			);
		}
		if ( ! $refund_amount )
		{
			return new WP_Error( 'invalid_refund_amt', 'Invalid Refund Amount', [ 'status' => 400 ] );
		}
		
		if ( ! $reason )
		{
			return new WP_Error( 'invalid_reason', 'Please Provide Reason', [ 'status' => 400 ] );
		}
		$order_amt = (float) $order->get_total();
		$refund_amount = (float) $refund_amount;
		if($refund_amount > $order_amt)
		{
			$refund_amount = $order_amt;
		}
		$restock = ( $items_reusable === 'yes' );
		$refund_items = [];

		foreach ( $order->get_items() as $item_id => $item ) {

			$qty = $item->get_quantity();

			$already_refunded_qty = abs(
				(int) $order->get_qty_refunded_for_item( $item_id )
			);

			$remaining_qty = $qty - $already_refunded_qty;

			if ( $remaining_qty <= 0 ) {
				continue;
			}

			$line_total = $item->get_total();

			$already_refunded_amount = abs(
				(float) $order->get_total_refunded_for_item( $item_id )
			);

			$remaining_amount = $line_total - $already_refunded_amount;

			if ( $remaining_amount <= 0 ) {
				continue;
			}

			$refund_items[ $item_id ] = [
				'qty'          => $remaining_qty,
				'refund_total' => wc_format_decimal( $remaining_amount ),
				'refund_tax'   => [],
			];
		}
		$refund = wc_create_refund([
			'order_id'       => $order_id,
			'amount'         => $refund_amount,
			'reason'         => $reason ?: 'Full refund',
			'line_items'     => $refund_items,
			'restock_items'  => $restock,
		]);
		if ( is_wp_error( $refund ) ) {
			return $refund;
		}
		$order->update_status(
			'refunded',
			'Order fully refunded via POS API'
		);
		$order->save();
		return [
			'success'        => true,
			'order_id'       => $order_id,
			'payment_method'=> "Cash",
			'refunded_amount'=> $refund_amount,
			'order_status'  => $order->get_status(),
			'restock'      => $restock,
			'refund_id'     => $refund->get_id(),
		];
	}
	function pinaka_partial_order_refund( WP_REST_Request $request ) 
	{
		$params = $request->get_json_params();
		global $wpdb;
		$pos_client_id = (string) $params['order_id'];
		$order_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT order_id 
				FROM {$wpdb->prefix}wc_orders_meta
				WHERE meta_key = %s
				AND meta_value = %s
				LIMIT 1",
				'_pos_client_order_id',
				$pos_client_id
			)
		);
		$reason         = sanitize_text_field( $params['reason'] ?? '' );
		$items_reusable = strtolower( $params['items_reusable'] ?? 'no' );
		$items          = $params['items'] ?? [];
		// $refund_amount  = isset( $params['amount'] ) ? wc_format_decimal( $params['amount'] ) : null;
		
		if ( ! $order_id ) {
			return new WP_Error( 'invalid_order_id', 'Invalid Order Id', [ 'status' => 400 ] );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_Error( 'invalid_order', 'Invalid Order', [ 'status' => 400 ] );
		}
		
		if ( $order->get_remaining_refund_amount() <= 0 ) {
			return new WP_Error(
				'order_already_refunded',
				'This order has already been fully refunded.',
				[ 'status' => 400 ]
			);
		}
		// if($refund_amount <= 0)
		// {
		// 	return new WP_Error( 'invalid_amount', 'Invalid Amount Given', [ 'status' => 400 ] );
		// }
		if ( ! $reason ) 
		{
			return new WP_Error( 'invalid_reason', 'Please provide refund reason', [ 'status' => 400 ] );
		}

		if ( empty( $items ) || ! is_array( $items ) ) {
			return new WP_Error( 'no_items', 'Please select items', [ 'status' => 400 ] );
		}

		$restock = ( $items_reusable === 'yes' );
		$refund_items = [];
		$refund_total = 0.0;
		$decimals     = wc_get_price_decimals();
		$payout_total   = 0;
		$positive_total = 0;
		$order_items = $order->get_items( 'line_item' );
		foreach ( $order_items as $order_item ) {

			$line_total = (float) $order_item->get_total();

			if ( $line_total < 0 ) {
				$payout_total += abs( $line_total );
			} else {
				$positive_total += $line_total;
			}
		}

		$payout_ratio = 0;

		if ( $positive_total > 0 && $payout_total > 0 ) {
			$payout_ratio = $payout_total / $positive_total;
		}
		foreach ( $items as $row ) {
			$item_id           = absint( $row['order_item_id'] );
			$qty               = absint( $row['qty'] );
			$refundable_amount = wc_format_decimal( $row['refundable_amount'] );
			if ( ! isset( $order_items[ $item_id ] ) || $qty <= 0 ) {
				return new WP_Error(
					'invalid_item',
					'Invalid refund item',
					[ 'status' => 400 ]
				);
			}
			$order_item = $order->get_item( $item_id );
			if ( ! $order_item || $qty <= 0 ) {
				return new WP_Error( 'invalid_item', 'Invalid refund item', [ 'status' => 400 ] );
			}
			if ( $refundable_amount <= 0 ) {
				return new WP_Error(
					'invalid_line_amount',
					'Refund amount must be greater than zero',
					[ 'status' => 400 ]
				);
			}
			$max_qty = $order_item->get_quantity();
			$line_paid_total = (float) $order_item->get_total();

			$already_refunded_qty    = abs( (int) $order->get_qty_refunded_for_item( $item_id ));
			$already_refunded_amount = abs(
				(float) $order->get_total_refunded_for_item( $item_id )
			);
			
			$remaining_qty    = $max_qty - $already_refunded_qty;
			$remaining_amount = $line_paid_total - $already_refunded_amount;

			if ( $qty > $remaining_qty ) {
				return new WP_Error(
					'qty_error',
					sprintf(
						'Refund quantity exceeds remaining quantity. Remaining: %d',
						$remaining_qty
					),
					[ 'status' => 400 ]
				);
			}

			// if ( $refundable_amount > wc_format_decimal( $remaining_amount ) ) {
			// 	return new WP_Error(
			// 		'amount_exceeds',
			// 		sprintf(
			// 			'Refund amount exceeds remaining refundable amount. Remaining: %s',
			// 			wc_format_decimal( $remaining_amount )
			// 		),
			// 		[ 'status' => 400 ]
			// 	);
			// }

			// $refund_items[ $item_id ] = [
			// 	'qty'          => $qty,
			// 	'refund_total' => wc_format_decimal( $refundable_amount ),
			// 	'refund_tax'   => [],
			// ];

			// $refund_total += $refundable_amount;
			$adjusted_amount = $refundable_amount;

			if ( $payout_ratio > 0 ) {

				$payout_deduction = $refundable_amount * $payout_ratio;

				$adjusted_amount = $refundable_amount - $payout_deduction;

				if ( $adjusted_amount < 0 ) {
					$adjusted_amount = 0;
				}
			}

			$refund_items[ $item_id ] = [
				'qty'          => $qty,
				'refund_total' => wc_format_decimal( $adjusted_amount ),
				'refund_tax'   => [],
			];

			$refund_total += $adjusted_amount;
		}
		$refund_total = (float) wc_format_decimal( $refund_total );
		if ( $refund_total <= 0 ) {
			return new WP_Error(
				'invalid_amount',
				'Refund amount must be greater than zero',
				[ 'status' => 400 ]
			);
		}

		$order_total = (float) $order->get_total();
		$epsilon     = pow( 10, -$decimals );
		
		if ( abs( $refund_total - $order_total ) < $epsilon ) {
			$refund_total = (float) wc_format_decimal( $order_total );
		}

		// if ( $refund_amount !== null && abs( $refund_amount - $refund_total ) >= $epsilon ) {
		// 	return new WP_Error(
		// 		'invalid_refund_amt',
		// 		sprintf(
		// 			'Refund mismatch. Requested: %s | Calculated: %s',
		// 			wc_format_decimal( $refund_amount ),
		// 			wc_format_decimal( $refund_total )
		// 		),
		// 		[ 'status' => 400 ]
		// 	);
		// }

		if ( $refund_total > $order->get_remaining_refund_amount() ) {
			return new WP_Error(
				'exceeds_remaining',
				'Refund amount exceeds remaining refundable amount',
				[ 'status' => 400 ]
			);
		}
		
		$refund = wc_create_refund([
			'order_id'      => $order_id,
			'amount'        => wc_format_decimal( $refund_total ),
			'reason'        => $reason,
			'line_items'    => $refund_items,
			'restock_items' => $restock,
		]);

		if ( is_wp_error( $refund ) ) {
			return $refund;
		}
		$order->update_status(
			'wc-partial-refund',
			'Order Partially refunded via POS API'
		);
		$order->save();
		return [
			'success'          => true,
			'order_id'         => $order_id,
			'refunded_amount' => wc_format_decimal( $refund_total ),
			'order_status'    => $order->get_status(),
			'restock'         => $restock,
			'refund_id'       => $refund->get_id(),
		];
	}
	function pinaka_get_order_transaction_ids( $order_id ) {
		global $wpdb;

		// Fetch payment post IDs linked to the order
		$payment_ids = $wpdb->get_col(
			$wpdb->prepare(
				"
				SELECT DISTINCT p.ID
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
				WHERE p.post_type = 'payments'
				AND p.post_status = 'publish'
				AND pm.meta_key = '_payment_order_id'
				AND pm.meta_value = %d
				",
				$order_id
			)
		);

		if ( empty( $payment_ids ) ) {
			return '';
		}

		$transaction_ids = [];

		foreach ( $payment_ids as $payment_id ) {
			$is_void = get_post_meta( $payment_id, '_payment_void', true );

			if ( ! empty( $is_void ) && $is_void !== 'false' ) {
				continue;
			}
			$method = get_post_meta( $payment_id, '_payment_method', true );
			$method = strtolower(trim( $method ));

			if ( empty( $method ) || $method === 'void' ) {
				continue;
			}
			$transaction_ids[] = (string) $payment_id;
		}
		return implode( ', ', array_unique( array_filter( $transaction_ids ) ) );
	}

	function pinaka_get_order_payment_methods( $order_id ) {
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
			$method = get_post_meta( $payment_id, '_payment_method', true );
			$method = strtolower(trim( $method ));
			if ( empty( $method ) || $method === 'void' ) {
				continue;
			}
			$methods[] = $method;
		}
		$methods = array_unique( $methods );
		return implode( ', ', $methods );
	}

	function pinaka_pos_add_payout( WP_REST_Request $request ) {
		$params   = $request->get_json_params();
		$order_id = isset( $params['order_id'] ) ? absint( $params['order_id'] ) : 0;
		$amount   = isset( $params['amount'] ) ? wc_format_decimal( $params['amount'] ) : 0;

		if ( ! $order_id || ! $amount ) {
			return new WP_REST_Response( [ 'error' => 'Order ID and amount are required' ], 400 );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_REST_Response( [ 'error' => 'Order not found' ], 404 );
		}

		// Find "Payout" product
		$payout_product_id = get_option( 'pinaka_payout_product_id', 0 );
		if ( ! $payout_product_id ) {
			return new WP_REST_Response( [ 'error' => 'Payout product not found' ], 404 );
		}

		// ✅ Check if payout product already exists
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( (int) $item->get_product_id() === (int) $payout_product_id ) {
				return new WP_Error(
									'woocommerce_rest_invalid_discount',
									__( 'Payout already exists for this order.', 'woocommerce' ),
									array( 'status' => 400 )
								);
			}
		}

		$payout_product = wc_get_product( $payout_product_id );

		// Remove payout fee if exists (old logic cleanup)
		foreach ( $order->get_fees() as $fee_id => $fee_item ) {
			if ( strtolower( $fee_item->get_name() ) === 'payout' ) {
				$order->remove_item( $fee_id );
			}
		}

		// Add payout product line (only once)
		$item_id = $order->add_product( $payout_product, 1, [
			'subtotal' => $amount,
			'total'    => $amount,
		] );

		if ( $item_id ) {
			$order->update_meta_data( '_order_payout', $amount );
		}

		$order->calculate_totals();
		$order->save();

		// ✅ Return WooCommerce REST order response
		$controller = new WC_REST_Orders_V2_Controller();
		$rest_request = new WP_REST_Request( 'GET' );
		$rest_request->set_param( 'context', 'view' );

		$response = $controller->prepare_object_for_response( $order, $rest_request );
		return rest_ensure_response( $response->get_data() );
	}

	/**
	 * Calculate the cashback fee for a given amount based on settings.
	 */
	function pinaka_pos_get_cashback_fee_for_amount( $amount ) {
		$settings = get_option( 'pinaka_pos_cashback_settings', [] );

		if ( empty( $settings['enabled'] ) ) {
			return 0;
		}

		$amount = floatval( $amount );
		$max    = isset( $settings['max_cashback'] ) ? floatval( $settings['max_cashback'] ) : 0;
		$tiers  = isset( $settings['tiers'] ) && is_array( $settings['tiers'] ) ? $settings['tiers'] : [];

		if ( $amount <= 0 ) {
			return 0;
		}

		if ( $max > 0 && $amount > $max ) {
			return 0; // Exceeds allowed cashback limit
		}

		foreach ( $tiers as $tier ) {
			$from = floatval( $tier['from'] ?? 0 );
			$to   = floatval( $tier['to'] ?? 0 );
			$fee  = floatval( $tier['fee'] ?? 0 );

			// Inclusive range match
			if ( $amount >= $from && $amount <= $to ) {
				return $fee;
			}
		}

		return 0;
	}

	
	function pinaka_pos_add_cashback( WP_REST_Request $request ) {

		$cashback_settings = get_option( 'pinaka_pos_cashback_settings', [] );
		if( empty( $cashback_settings['enabled'] ) ) {
			return new WP_Error(
				'pinaka_cashback_disabled',
				__( 'Cashback feature is disabled.', 'pinaka-pos' ),
				[ 'status' => 400 ]
			);
		}
		$params   = $request->get_json_params();
		$order_id = isset( $params['order_id'] ) ? absint( $params['order_id'] ) : 0;
		$amount   = isset( $params['amount'] ) ? wc_format_decimal( $params['amount'] ) : 0;
		$fee = $this->pinaka_pos_get_cashback_fee_for_amount( $amount );
		$cashback_amount = $amount + $fee;
		
		if ( ! $order_id || ! $amount ) {
			return new WP_REST_Response( [ 'error' => 'Order ID and amount are required' ], 400 );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_REST_Response( [ 'error' => 'Order not found' ], 404 );
		}

		// Find "Cashback" product
		$payout_product_id = get_option( 'pinaka_cashback_product_id', 0 );
		if ( ! $payout_product_id ) {
			return new WP_REST_Response( [ 'error' => 'Payout product not found' ], 404 );
		}

		// ✅ Check if payout product already exists
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( (int) $item->get_product_id() === (int) $payout_product_id ) {
				return new WP_Error(
									'woocommerce_rest_invalid_discount',
									__( 'Payout already exists for this order.', 'woocommerce' ),
									array( 'status' => 400 )
								);
			}
		}

		$payout_product = wc_get_product( $payout_product_id );

		// Remove payout fee if exists (old logic cleanup)
		foreach ( $order->get_fees() as $fee_id => $fee_item ) {
			if ( strtolower( $fee_item->get_name() ) === 'payout' ) {
				$order->remove_item( $fee_id );
			}
		}

		// Add payout product line (only once)
		$item_id = $order->add_product( $payout_product, 1, [
			'subtotal' => $cashback_amount,
			'total'    => $cashback_amount,
		] );

		if ( $item_id ) {
			$order->update_meta_data( '_order_cashback_fee', $fee );
			$order->update_meta_data( '_order_cashback', wc_format_decimal( $amount ) );
		}


		
		$order->calculate_totals();
		$order->save();

		// ✅ Return WooCommerce REST order response
		$controller = new WC_REST_Orders_V2_Controller();
		$rest_request = new WP_REST_Request( 'GET' );
		$rest_request->set_param( 'context', 'view' );

		$response = $controller->prepare_object_for_response( $order, $rest_request );
		return rest_ensure_response( $response->get_data() );
	}


	function pinaka_pos_add_discount( WP_REST_Request $request ) {
		$params   = $request->get_json_params();
		$order_id = isset( $params['order_id'] ) ? absint( $params['order_id'] ) : 0;
		$amount   = isset( $params['amount'] ) ? wc_format_decimal( $params['amount'] ) : 0;

		if ( ! $order_id || ! $amount ) {
			return new WP_Error(
				'pinaka_empty_order_no_discount_only',
				__( 'Merchant discount cannot be applied to an empty cart.', 'pinaka-pos' ),
				[ 'status' => 400 ]
			);
			// return new WP_REST_Response( [ 'error' => 'Order ID and amount are required' ], 400 );
		}

		$order = wc_get_order( $order_id );
		
		$order_total = $order->get_total();
		if ( empty($order->get_items( 'line_item' )) && $amount ) {
			return new WP_Error(
				'pinaka_empty_order_no_discount_only',
				__( 'Merchant discount cannot be applied to an empty cart.', 'pinaka-pos' ),
				[ 'status' => 400 ]
			);
		}
		if( $order_total + $amount <= 0 ) {
			return new WP_Error(
				'pinaka_invalid_discount_amount',
				__( 'Discount amount cannot be greater than order total.', 'pinaka-pos' ),
				[ 'status' => 400 ]
			);
		}

		if ( ! $order ) {
			return new WP_REST_Response( [ 'error' => 'Order not found' ], 404 );
		}

		// Find "Payout" product
		$discount_product_id = get_option( 'pinaka_discount_product_id', 0 );
		if ( ! $discount_product_id ) {
			return new WP_REST_Response( [ 'error' => 'Discount product not found' ], 404 );
		}

		// ✅ Check if payout product already exists
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( (int) $item->get_product_id() === (int) $discount_product_id ) {
				return new WP_Error(
									'woocommerce_rest_invalid_discount',
									__( 'Discount already exists for this order.', 'woocommerce' ),
									array( 'status' => 400 )
								);
			}
		}

		$payout_product = wc_get_product( $discount_product_id );

		// Remove payout fee if exists (old logic cleanup)
		foreach ( $order->get_fees() as $fee_id => $fee_item ) {
			if ( strtolower( $fee_item->get_name() ) === 'discount' ) {
				$order->remove_item( $fee_id );
			}
		}

		// Add payout product line (only once)
		$item_id = $order->add_product( $payout_product, 1, [
			'subtotal' => $amount,
			'total'    => $amount,
		] );

		if ( $item_id ) {
			$order->update_meta_data( '_order_discount', $amount );
		}

		$order->calculate_totals();
		$order->save();

		// ✅ Return WooCommerce REST order response
		$controller = new WC_REST_Orders_V2_Controller();
		$rest_request = new WP_REST_Request( 'GET' );
		$rest_request->set_param( 'context', 'view' );

		$response = $controller->prepare_object_for_response( $order, $rest_request );
		return rest_ensure_response( $response->get_data() );
	}


	function pinaka_get_product_by_title( $title ) {
		$query = new WP_Query( [
			'post_type'      => 'product',
			'title'          => $title,
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		] );

		if ( $query->have_posts() ) {
			return $query->posts[0]; // product ID
		}

		return false;
	}


	/**
	 * Get products/attributes/terms  callback.
	 *
	 * @param WP_REST_Request $request .
	 * @return array|WP_Error
	 */
	public function order_items_callback( WP_REST_Request $request ) {

		$params     = $request->get_params();
		$product_id = $params['product_id'];
		$page       = isset( $params['page'] ) ? $params['page'] : 1;
		$per_page   = isset( $params['per_page'] ) ? $params['per_page'] : 10;

		global $wpdb;

		// Calculate offset and limit based on page and per_page values
		$offset = ( $page - 1 ) * $per_page;
		$limit  = $per_page;

		// Modify the SQL query to include offset and limit clauses
		$query = $wpdb->prepare(
			"
        SELECT
            oi.order_id,
            o.post_date,
            o.post_date_gmt,
            oim2.meta_key,
            oim2.meta_value
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim
        ON oi.order_item_id = oim.order_item_id
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim2
        ON oim.order_item_id = oim2.order_item_id
        JOIN {$wpdb->prefix}posts AS o
        ON oi.order_id = o.ID
        WHERE oi.order_item_type = 'line_item'
        AND (
            oim.meta_key = '_product_id'
            OR oim.meta_key = '_variation_id'
        )
        AND oim.meta_value = %d
        AND (
            oim2.meta_key = '_reduced_stock'
            OR oim2.meta_key = '_increased_stock'
        )
        GROUP BY oi.order_id, oim2.meta_key, oim2.meta_value
        ORDER BY o.post_date_gmt DESC
        LIMIT %d OFFSET %d
        ",
			$product_id,
			$limit,
			$offset
		);

		$order_stock_changes = $wpdb->get_results( $query );

		return new WP_REST_Response(
			$order_stock_changes,
			200
		);
	}

	/**
	 * Get order items for gst report
	 *
	 * @param WP_REST_Request $request .
	 * @return array|WP_Error
	 */
	public function order_items_gst_report_callback( WP_REST_Request $request ) {

		$params = $request->get_params();

		$page       = isset( $params['page'] ) ? $params['page'] : 1;
		$per_page   = isset( $params['per_page'] ) ? $params['per_page'] : 10;
		$start_date = isset( $params['start_date'] ) ? $params['start_date'] : 10;
		$end_date   = isset( $params['end_date'] ) ? $params['end_date'] : 10;

		global $wpdb;

		// Calculate offset and limit based on page and per_page values
		$offset = ( $page - 1 ) * $per_page;
		$limit  = $per_page;

		// Modify the SQL query to include offset and limit clauses
		$query = $wpdb->prepare(
			"
            SELECT
    oi.*,
    p.ID,
    p.post_date,
    customer_meta.meta_value AS user_id,
    first_name_meta.meta_value AS first_name,
    last_name_meta.meta_value AS last_name,
    state_meta.meta_value AS billing_state,
    user_meta.meta_value AS gst_number,
    item_qty_meta.meta_value AS quantity,
    item_subtotal_meta.meta_value AS subtotal,
    item_total_meta.meta_value AS total,
    item_product_meta.meta_value AS product_id,
    item_variation_meta.meta_value AS variation_id,
    hsn_meta.meta_value AS hsn_sac,
    cgst_meta.meta_value AS mp_tax_cgst,
    sgst_meta.meta_value AS mp_tax_sgst,
    cess_meta.meta_value AS mp_tax_cess
FROM
{$wpdb->prefix}posts AS p
JOIN {$wpdb->prefix}woocommerce_order_items AS oi
ON
    oi.order_id = p.ID
LEFT JOIN {$wpdb->prefix}postmeta AS customer_meta
ON
    p.ID = customer_meta.post_id AND customer_meta.meta_key = '_customer_user'
LEFT JOIN {$wpdb->prefix}postmeta AS first_name_meta
ON
    p.ID = first_name_meta.post_id AND first_name_meta.meta_key = '_billing_first_name'
LEFT JOIN {$wpdb->prefix}postmeta AS last_name_meta
ON
    p.ID = last_name_meta.post_id AND last_name_meta.meta_key = '_billing_last_name'
LEFT JOIN {$wpdb->prefix}postmeta AS state_meta
ON
    p.ID = state_meta.post_id AND state_meta.meta_key = '_billing_state'
LEFT JOIN {$wpdb->prefix}usermeta AS user_meta
ON
    customer_meta.meta_value = user_meta.user_id AND user_meta.meta_key = 'gstin_number'
LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS item_qty_meta
ON
    oi.order_item_id = item_qty_meta.order_item_id AND item_qty_meta.meta_key = '_qty'
LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS item_subtotal_meta
ON
    oi.order_item_id = item_subtotal_meta.order_item_id AND item_subtotal_meta.meta_key = '_line_subtotal'
LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS item_total_meta
ON
    oi.order_item_id = item_total_meta.order_item_id AND item_total_meta.meta_key = '_line_total'
LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS item_product_meta
ON
    oi.order_item_id = item_product_meta.order_item_id AND item_product_meta.meta_key = '_product_id'
LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS item_variation_meta
ON
    oi.order_item_id = item_variation_meta.order_item_id AND item_variation_meta.meta_key = '_variation_id'
LEFT JOIN {$wpdb->prefix}postmeta AS hsn_meta
ON
    (
        CASE WHEN item_variation_meta.meta_value = 0 THEN item_product_meta.meta_value ELSE item_variation_meta.meta_value
    END
) = hsn_meta.post_id AND hsn_meta.meta_key = 'hsn_sac'
LEFT JOIN {$wpdb->prefix}postmeta AS cgst_meta
ON
    item_product_meta.meta_value = cgst_meta.post_id AND cgst_meta.meta_key = 'mp_tax_cgst'
LEFT JOIN {$wpdb->prefix}postmeta AS sgst_meta
ON
    item_product_meta.meta_value = sgst_meta.post_id AND sgst_meta.meta_key = 'mp_tax_sgst'
LEFT JOIN {$wpdb->prefix}postmeta AS cess_meta
ON
    item_product_meta.meta_value = cess_meta.post_id AND cess_meta.meta_key = 'mp_tax_cess'
WHERE
    oi.order_item_type = 'line_item' AND p.post_status = 'wc-completed' AND p.post_type = 'shop_order'
    AND p.post_date BETWEEN %s AND %s
    LIMIT %d OFFSET %d;
        ",
			$start_date,
			$end_date,
			$limit,
			$offset
		);

		$order_stock_changes = $wpdb->get_results( $query );

		return new WP_REST_Response(
			$order_stock_changes,
			200
		);
	}


	/**
	 * Calculate coupons.
	 *
	 * @throws WC_REST_Exception When fails to set any item.
	 * @param WP_REST_Request $request Request object.
	 * @param WC_Order        $order   Order data.
	 * @return bool
	 */
	protected function calculate_coupons( $request, $order ) {
		do_action( 'woocommerce_apply_coupon_order_rest', $order, $request );

		if ( ! isset( $request['coupon_lines'] ) ) {
			return false;
		}

		// Validate input and at the same time store the processed coupon codes to apply.

		$coupon_codes = array();
		$discounts    = new WC_Discounts( $order );

		$current_order_coupons      = array_values( $order->get_coupons() );
		$current_order_coupon_codes = array_map(
			function ( $coupon ) {
				return $coupon->get_code();
			},
			$current_order_coupons
		);

		foreach ( $request['coupon_lines'] as $item ) {
			if ( ! empty( $item['id'] ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_coupon_item_id_readonly', __( 'Coupon item ID is readonly.', 'woocommerce' ), 400 );
			}

			if ( empty( $item['code'] ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_invalid_coupon', __( 'Coupon code is required.', 'woocommerce' ), 400 );
			}

			$coupon_code = wc_format_coupon_code( wc_clean( $item['code'] ) );
			$coupon      = new WC_Coupon( $coupon_code );

			// Skip check if the coupon is already applied to the order, as this could wrongly throw an error for single-use coupons.
			if ( ! in_array( $coupon_code, $current_order_coupon_codes, true ) ) {
				$check_result = $discounts->is_coupon_valid( $coupon );

				if ( is_wp_error( $check_result ) ) {
					throw new WC_REST_Exception( 'woocommerce_rest_' . $check_result->get_error_code(), $check_result->get_error_message(), 400 );
				}
				// echo "Asdfasdf";

			}

			$coupon_codes[] = $coupon_code;
		}

		// Remove all coupons first to ensure calculation is correct.
		foreach ( $order->get_items( 'coupon' ) as $existing_coupon ) {
			$order->remove_coupon( $existing_coupon->get_code() );
		}

		// Apply the coupons.
		foreach ( $coupon_codes as $new_coupon ) {
			$results = $order->apply_coupon( $new_coupon );

			if ( is_wp_error( $results ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_' . $results->get_error_code(), $results->get_error_message(), 400 );
			}
		}

		return true;
	}

	protected function auto_apply_product_coupons( WC_Order $order ) {
		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
			return array();
		}

		$apply_codes = [];
		$this->clear_coupon_metadata_from_items( $order );

		// --- 1) Collect product IDs present in the order (initial snapshot) ---
		$product_ids_in_order = [];
		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}
			$pid = absint( $item->get_product_id() );
			$vid = absint( $item->get_variation_id() );
			if ( $pid ) {
				$product_ids_in_order[] = $pid;
			}
			if ( $vid ) {
				$product_ids_in_order[] = $vid;
			}
		}
		$product_ids_in_order = array_values( array_unique( $product_ids_in_order ) );

		// --- 2) Try to attach missing product objects to order items (variation first, then product) ---
		$bad_item_ids = [];
		foreach ( $order->get_items() as $item_id => $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			// If the item already provides a product object, skip
			if ( false !== $item->get_product() ) {
				continue;
			}

			$pid = absint( $item->get_product_id() );
			$vid = absint( $item->get_variation_id() );
			$product = null;

			if ( $vid ) {
				$product = wc_get_product( $vid );
			}
			if ( ! $product && $pid ) {
				$product = wc_get_product( $pid );
			}

			if ( $product ) {
				// Attach product object to item, save item
				if ( method_exists( $item, 'set_product' ) ) {
					$item->set_product( $product );
				} else {
					// Fallback for very old WC versions
					$item->product = $product;
				}
				try {
					$item->save();
				} catch ( Exception $e ) {
					// ignore save errors here; we will save the order below
				}
			} else {
				// Could not resolve product — mark for removal later
				$bad_item_ids[] = (int) $item_id;
			}
		}

		// --- 3) Save + reload order so WC internal caches pick up repaired product objects ---
		if ( ! empty( $order->get_id() ) ) {
			try {
				$order->save();
				$order = wc_get_order( $order->get_id() );
			} catch ( Exception $e ) {
				// best-effort: log and continue (we'll still check items)
				if ( function_exists( 'wc_get_logger' ) ) {
					$logger = wc_get_logger();
					$logger->error( 'auto_apply_product_coupons: order save/reload failed for order ' . $order->get_id() . ' — ' . $e->getMessage(), [ 'source' => 'pinaka-pos' ] );
				}
			}
		}

		// --- 4) Re-check items after reload to find any still-missing products ---
		$still_bad = [];
		foreach ( $order->get_items() as $item_id => $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}
			if ( false === $item->get_product() ) {
				$still_bad[] = [
					'item_id'      => (int) $item_id,
					'name'         => $item->get_name(),
					'product_id'   => (int) $item->get_product_id(),
					'variation_id' => (int) $item->get_variation_id(),
					'qty'          => $item->get_quantity(),
				];
			}
		}

		// --- 5) If there are still-bad items, remove them (safe fallback) and log + add order note ---
		if ( ! empty( $still_bad ) ) {
			$removed_ids = [];
			foreach ( $still_bad as $bi ) {
				$removed_ids[] = $bi['item_id'];
				try {
					$order->remove_item( $bi['item_id'] );
				} catch ( Exception $e ) {
					// ignore removal errors; we'll log them
				}
			}

			// Save order after removals and reload
			try {
				$order->save();
				$order = wc_get_order( $order->get_id() );
			} catch ( Exception $e ) {
				// log save failure
				if ( function_exists( 'wc_get_logger' ) ) {
					$logger = wc_get_logger();
					$logger->error( 'auto_apply_product_coupons: failed to save order after removing bad items for order ' . $order->get_id() . ' — ' . $e->getMessage(), [ 'source' => 'pinaka-pos' ] );
				}
			}

			// Log details and add an order note for admin awareness
			$note = sprintf(
				'Auto-coupon: removed %d line item(s) because product could not be resolved: %s',
				count( $removed_ids ),
				implode( ',', $removed_ids )
			);
			$order->add_order_note( $note );
			if ( function_exists( 'wc_get_logger' ) ) {
				$logger = wc_get_logger();
				$logger->warning( 'auto_apply_product_coupons: removed items for order ' . $order->get_id() . ' — ' . wp_json_encode( $still_bad ), [ 'source' => 'pinaka-pos' ] );
			}

			// Rebuild product id list after removal
			$product_ids_in_order = [];
			foreach ( $order->get_items() as $item ) {
				if ( ! $item instanceof WC_Order_Item_Product ) {
					continue;
				}
				$pid = absint( $item->get_product_id() );
				$vid = absint( $item->get_variation_id() );
				if ( $pid ) {
					$product_ids_in_order[] = $pid;
				}
				if ( $vid ) {
					$product_ids_in_order[] = $vid;
				}
			}
			$product_ids_in_order = array_values( array_unique( $product_ids_in_order ) );
		}

		// --- 6) Find coupons to auto-apply based on required_product_ids ---
		$coupon_ids = get_posts( [
			'post_type'      => 'shop_coupon',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => [
				[
					'key'     => 'required_product_ids',
					'compare' => 'EXISTS',
				],
			],
		] );

		if ( ! empty( $coupon_ids ) ) {
			foreach ( $coupon_ids as $coupon_id ) {
				// Remove coupon if already present (defensive)
				$coupon_name = wc_format_coupon_code( get_the_title( $coupon_id ) );
				try {
					$order->remove_coupon( $coupon_name );
				} catch ( Exception $e ) {
					// ignore
				}

				$required_ids = get_post_meta( $coupon_id, 'required_product_ids', true );

				if ( is_string( $required_ids ) ) {
					$required_ids = array_filter( array_map( 'absint', array_map( 'trim', explode( ',', $required_ids ) ) ) );
				} elseif ( is_array( $required_ids ) ) {
					$required_ids = array_filter( array_map( 'absint', $required_ids ) );
				} else {
					$required_ids = [];
				}

				if ( empty( $required_ids ) ) {
					continue;
				}

				// only if ALL required products are in the order
				if ( empty( array_diff( $required_ids, $product_ids_in_order ) ) ) {
					$code = wc_format_coupon_code( get_the_title( $coupon_id ) );
					// queue the code for application; actual validity will be checked by apply_coupon()
					if ( $code ) {
						$apply_codes[] = $code;
					}
				}
			}
		}

		// --- 7) Finalize list to apply (unique) ---
		$apply_codes = array_values( array_unique( $apply_codes ) );

		// --- 8) Apply queued coupons (apply_coupon will return WP_Error if invalid) ---
		foreach ( $apply_codes as $code ) {
			$result = $order->apply_coupon( $code );
			// if ( is_wp_error( $result ) ) {
			// 	// wrap WP_Error into WC_REST_Exception so API client receives a proper REST error response
			// 	throw new WC_REST_Exception(
			// 		'woocommerce_rest_' . $result->get_error_code(),
			// 		$result->get_error_message(),
			// 		400
			// 	);
			// }

			// add coupon metadata to discounted items (keep existing behaviour)
			$this->add_coupon_metadata_to_discounted_items( $order, $code );
		}

		// --- 9) Recalculate totals and save final state ---
		$order->calculate_totals();
		try {
			$order->save();
		} catch ( Exception $e ) {
			// log but don't break
			if ( function_exists( 'wc_get_logger' ) ) {
				$logger = wc_get_logger();
				$logger->error( 'auto_apply_product_coupons: failed to save order after coupon application for order ' . $order->get_id() . ' — ' . $e->getMessage(), [ 'source' => 'pinaka-pos' ] );
			}
		}

		return true;
	}



	protected function clear_coupon_metadata_from_items( $order ) {
		// foreach ( $order->get_items() as $item ) {
		// 	$item->delete_meta_data( 'Discount Type' );
		// 	$item->delete_meta_data( 'Discount Applied' );
		// 	$item->delete_meta_data( 'Discounted Unit Price' );
		// 	$item->save();
		// }
	}

	protected function add_coupon_metadata_to_discounted_items( $order, $coupon_code ) {
		$coupon = new WC_Coupon( $coupon_code );
		// Only run if required products are set
		$required_ids = (array) get_post_meta( $coupon->get_id(), 'required_product_ids', true );
		$is_internal = get_post_meta( $coupon->get_id(), '_is_internal_coupon', true );
		// Stop if there are no required IDs (removes empty strings)
		if ( $is_internal === 'yes' && ! empty( $is_internal ) ) {
			// Check if all required products exist in the order
			$found_product_ids = [];
			$include_products = array_map( 'intval', (array) $coupon->get_product_ids() );

			foreach ( $order->get_items() as $item ) {
				$found_product_ids[] = $item->get_product_id();
			}
	
			// If not all required products are in the order, do nothing
			foreach ( $required_ids as $required_id ) {
				if ( ! in_array( $required_id, $found_product_ids ) ) {
					return;
				}
			}
	
			// Apply metadata only to non-required discounted products
			foreach ( $order->get_items() as $item ) {
				$product_id = $item->get_product_id();
	
				// Skip required items
				if ( in_array( $product_id, $required_ids ) ) {
					continue;
				}

				if( in_array( $product_id, $include_products ) ) {
					$qty = $item->get_quantity();
					$product = $item->get_product();
		
					if ( ! $product ) {
						continue;
					}
		
					$original_price  = floatval( $product->get_price() );
					$total_before    = $original_price * $qty;
					$total_after     = floatval( $item->get_total() );
					$discount_amount = $total_before - $total_after;
		
					if ( $discount_amount > 0 ) {
						$new_unit_price = $total_after / $qty;
		
						$item->add_meta_data( 'Discount Type', 'Combo Discount', true );
						$item->add_meta_data( 'Discount Applied', wc_format_decimal( $discount_amount ), true );
						$item->add_meta_data( 'Discounted Unit Price', wc_format_decimal( $new_unit_price ), true );
						$item->save();
					}
				}
	
			}
		}
	}

	
	/**
	 * Prepare a single order for create or update.
	 *
	 * @throws WC_REST_Exception When fails to set any item.
	 * @param  WP_REST_Request $request Request object.
	 * @param  bool            $creating If is creating a new object.
	 * @return WP_Error|WC_Data
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {

		$id        = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		$order     = new WC_Order( $id );
		$schema    = $this->get_item_schema();
		// --- 1) PRE-SCAN line_items to find Cashback items and prepare fee lines + immediate fee items ---
		$cashback_fee_amount = 0.0;
    	$injected_fee_lines = array();

		 // Get line_items
    	$line_items = ( $request instanceof WP_REST_Request ) ? $request->get_param( 'line_items' ) : ( $request['line_items'] ?? [] );
		
		if ( isset( $request['line_items'] ) && is_array( $request['line_items'] ) ) {
			$line_items = $request['line_items'];
			foreach ( $line_items as &$item ) 
			{

				if ($item['product_id'] == get_option( 'pinaka_cashback_product_id', 0 ) ) {
					$cashback_fee_amount = $this->pinaka_pos_get_cashback_fee_for_amount( floatval( $item['total'] ?? 0 ) );
				}
				/** Detect custom POS item */
				if ( empty( $item['product_id'] ) && ! empty( $item['name'] ) ) {

					$name  = sanitize_text_field( $item['name'] );
					$price = isset( $item['price'] ) ? floatval( $item['price'] ) : 0;
					$sku   = isset( $item['sku'] ) ? sanitize_text_field( $item['sku'] ) : '';
					

					$product_id = 0;
					if ( ! empty( $sku ) ) {
						$args = [
							'post_type'      => ['product', 'product_variation'],
							'posts_per_page' => 1,
							'post_status'    => 'publish',
							'meta_query'     => [
								[
									'key'     => '_sku',
									'value'   => $sku,
									'compare' => '=',
								]
							],
							'fields' => 'ids'
						];

						$query = new WP_Query( $args );
						if ( ! empty( $query->posts ) ) {
							$product_id = (int) $query->posts[0];
						}
					}
					if ( ! $product_id ) {
						$product = new WC_Product_Simple();
						$product->set_name( $name );
						$product->set_regular_price( $price );
						$product->set_status( 'publish' );
						
						// ✅ TAX SETTINGS
						$product->set_tax_status( 'taxable' );      // taxable | shipping | none
						if($item['tax_class'] == 'standard'){
							$product->set_tax_class( '' );               // 
						}else{
							$product->set_tax_class( $item['tax_class'] );               // '' = Standard tax class
						}

						if ( ! empty( $sku ) ) {
							$product->set_sku( $sku );
						}
						$product->update_meta_data( 'custom_item', 'yes' );
						$product_id = $product->save();
					}
					$item['product_id'] = $product_id;
					// unset( $item['name'], $item['price'], $item['sku'] );
				}

				if ( $order && ! empty( $item['product_id'] ) ) {

					$existing_line_item_id = 0;

					foreach ( $order->get_items() as $line_id => $line_item ) {

						if ( (int) $line_item->get_product_id() === (int) $item['product_id'] ) {
							$existing_line_item_id = $line_id;
							break;
						}
					}
					if ( $existing_line_item_id ) {

						$line = $order->get_item( $existing_line_item_id );

						// Merge quantities (existing + new)
						$new_qty = (int) $line->get_quantity() + ( isset( $item['quantity'] ) ? (int) $item['quantity'] : 1 );

						// Correct REST format
						$item = [
							'id'         => $existing_line_item_id,
							'product_id' => (int) $item['product_id'],
							'quantity'   => $new_qty,
							'subtotal'   => wc_format_decimal( $line->get_subtotal() ),
							'total'      => wc_format_decimal( $line->get_total() ),
						];
					}
				}

			}
			// $request->set_param( 'line_items', $line_items );
			$request['line_items'] = $line_items;
			unset( $item );
		}
		$data_keys = array_keys( array_filter( $schema['properties'], array( $this, 'filter_writable_props' ) ) );
		// Inject cashback fee line if applicable
		if ( $cashback_fee_amount > 0 ) {
			$injected_fee_lines[] = array(
				'name'        => 'Cashback Fee',
				'tax_class'  => '',
				'tax_status' => 'none',
				'amount'     => wc_format_decimal( $cashback_fee_amount ),
				'total'      => wc_format_decimal( $cashback_fee_amount ),
			);
		}
		if ( ! empty( $injected_fee_lines ) ) {
			$request->set_param( 'fee_lines', $injected_fee_lines );
		}
		// Handle all writable props.
		foreach ( $data_keys as $key ) {
			$value = $request[ $key ];

			if ( ! is_null( $value ) ) {
				switch ( $key ) {
					case 'coupon_lines':
					case 'status':
						// Change should be done later so transitions have new data.
						break;
					case 'billing':
					case 'shipping':
						$this->update_address( $order, $value, $key );
						break;
					case 'line_items':
					case 'shipping_lines':
					case 'fee_lines':
						if ( is_array( $value ) ) {
							foreach ( $value as $item ) {
								if ( is_array( $item ) ) {
									if ( $this->item_is_null( $item ) || ( isset( $item['quantity'] ) && 0 === $item['quantity'] ) ) {
										$order->remove_item( $item['id'] );
										do_action( 'mp_order_item_removed', $item['id'], $order );
									} else {
										$this->set_item( $order, $key, $item );
									}
								}
							}
						}
						break;
						case 'meta_data':
						if ( is_array( $value ) ) {
							foreach ( $value as $meta ) {
								$order->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
							}
						}
						$order->update_meta_data( '_wc_order_created_by', get_current_user_id() );

						break;

					default:
						if ( is_callable( array( $order, "set_{$key}" ) ) ) {
							$order->{"set_{$key}"}( $value );
						}
						break;
				}
			}
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`,
		 * refers to the object type slug.
		 *
		 * @param WC_Data         $order    Object object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating If is creating a new object.
		 */
		return apply_filters( "woocommerce_rest_pre_insert_{$this->post_type}_object", $order, $request, $creating );
	}

	/**
	 * Save an object data.
	 *
	 * @since  3.0.0
	 * @throws WC_REST_Exception But all errors are validated before returning any data.
	 * @param  WP_REST_Request $request  Full details about the request.
	 * @param  bool            $creating If is creating a new object.
	 * @return WC_Data|WP_Error
	 */
	/**
	 * Save an object data.
	 *
	 * @since  3.0.0
	 * @throws WC_REST_Exception But all errors are validated before returning any data.
	 * @param  WP_REST_Request $request  Full details about the request.
	 * @param  bool            $creating If is creating a new object.
	 * @return WC_Data|WP_Error
	 */
	protected function save_object( $request, $creating = false ) {
		try {
			$pos_order_tag =  $this->get_meta_value( (array) $request->get_param( 'meta_data' ), 'pos_order_tag' );
			/* ======================================================
			* PRELOAD OPTIONS (CACHE)
			* ====================================================== */
			$payout_product_id   = (int) get_option( 'pinaka_payout_product_id', 0 );
			$cashback_product_id = (int) get_option( 'pinaka_cashback_product_id', 0 );
			$discount_product_id = (int) get_option( 'pinaka_discount_product_id', 0 );

			if ( $pos_order_tag === 'updated_from_pos' ) {
				$order = wc_get_order( $request['id'] );
				foreach ( $order->get_items('line_item') as $item_id => $item ) {
					$order->remove_item( $item_id );
				}
				// remove coupon line
				foreach ( $order->get_items('coupon') as $item_id => $item ) {
					$order->remove_item( $item_id );
				}
				// remove fee lines
				foreach ( $order->get_items('fee') as $item_id => $item ) {
					$order->remove_item( $item_id );
				}
				$order->calculate_totals();
				$order->save();
			}
			$object = $this->prepare_object_for_database( $request, $creating );
			$object->update_meta_data( 'order_created_via', 'pinaka_pos' );
			$object->update_meta_data( '_wc_order_created_by', get_current_user_id() );
			if ( is_wp_error( $object ) ) {
				return $object;
			}

			// Make sure gateways are loaded so hooks from gateways fire on save/create.
			WC()->payment_gateways();

			if ( ! is_null( $request['customer_id'] ) && 0 !== $request['customer_id'] ) {
				// Make sure customer exists.
				if ( false === get_user_by( 'id', $request['customer_id'] ) ) {
					throw new WC_REST_Exception( 'woocommerce_rest_invalid_customer_id', __( 'Customer ID is invalid.', 'woocommerce' ), 400 );
				}

				// Make sure customer is part of blog.
				if ( is_multisite() && ! is_user_member_of_blog( $request['customer_id'] ) ) {
					add_user_to_blog( get_current_blog_id(), $request['customer_id'], 'customer' );
				}
			}

			if ( $creating ) {
				$object->set_created_via( 'rest-api' );
				$object->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );
				$object->calculate_totals();
			} else {
				// If items have changed, recalculate order totals.
				if ( isset( $request['billing'] ) || isset( $request['shipping'] ) || isset( $request['line_items'] ) || isset( $request['shipping_lines'] ) || isset( $request['fee_lines'] ) || isset( $request['coupon_lines'] ) ) {
					// 1) Re-apply/clean existing payout/discount lines and get recovered amounts
					$object->calculate_totals( true );
				}
			}
			$recovered_amount          = $this->pinaka_remove_existing_payout_lines( $object );
					
			$recovered_amount_discount = $this->pinaka_remove_existing_discount_lines( $object );
			
			$this->calculate_coupons( $request, $object );
			if ( $recovered_amount ) {
				if ( $payout_product_id && $p = wc_get_product( $payout_product_id ) ) {
					$object->add_product( $p, 1, [
						'subtotal' => $recovered_amount,
						'total'    => $recovered_amount,
					] );
				}
			}

			if ( $recovered_amount_discount ) {
				if ( $discount_product_id && $p = wc_get_product( $discount_product_id ) ) {
					$object->add_product( $p, 1, [
						'subtotal' => $recovered_amount_discount,
						'total'    => $recovered_amount_discount,
					] );
				}
			}
			$object->calculate_totals( true );
			// Set coupons.
			// $this->calculate_coupons( $request, $object );

			/**
			 * Adds custom order statuses to the WooCommerce order status dropdown menu.
			 *
			 * This function adds three custom order statuses to the existing array of
			 * order statuses that is used to populate the order status dropdown menu
			 * on the WooCommerce admin pages. The custom statuses are "Pending Purchase",
			 * "Ordered Purchase", and "Received Purchase".
			 *
			 * @param array $statuses The existing array of order statuses.
			 * @return array The updated array of order statuses.
			 */
			function add_custom_order_statuses( $statuses ) {
				// Add your custom order statuses to the existing array of statuses.
				$custom_statuses = array(
					'wc-pending-purchase'  => _x( 'Pending Purchase', 'Order status', 'pinaka-pos' ),
					'wc-ordered-purchase'  => _x( 'Ordered Purchase', 'Order status', 'pinaka-pos' ),
					'wc-received-purchase' => _x( 'Received Purchase', 'Order status', 'pinaka-pos' ),
				);
				return array_merge( $custom_statuses, $statuses );
			}

			$pos_payments = $this->get_pos_payments_from_request( $request );

			// Add the callback function to the filter hook.
			add_filter( 'wc_order_statuses', 'add_custom_order_statuses', 10, 1 );
			// Set status.
			// if ( ! empty( $request['status'] ) ) {
			// 	$object->set_status( $request['status'] );
			// }
			// Actions for after the order is saved.
			$shift_id = $this->get_meta_value( (array) $request->get_param( 'meta_data' ), 'shift_id' );
			if ( true === $request['set_paid'] ) {
				$new_status = $this->determine_order_status_from_payments( $object, $pos_payments, $shift_id );

				if ( $new_status ) {
					$object->set_status( $new_status );
				}
			}

			$object->save();

			remove_filter( 'wc_order_statuses', 'add_custom_order_statuses', 10, 1 );

			return $this->get_object( $object->get_id() );
		} catch ( WC_Data_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		} catch ( WC_REST_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}
	/**
	 * Apply multipack discounts based on multipack settings.
	 *
	 * This will create negative fee lines named "Multipack discount: {productname}".
	 * Discount supports values like "10" (fixed amount per pack) or "5%" (percentage of matched item's subtotal).
	 */
	protected function remove_products_multipack_discount( $order ) {

		if ( ! $order || ! $order instanceof WC_Order ) {
			return;
		}

		$rules = $this->pinaka_product_multipack_rules();
		if ( empty( $rules ) ) {
			return;
		}

		$items    = $order->get_items( 'line_item' );
		$decimals = wc_get_price_decimals();

		foreach ( $items as $item ) {

			// Only if multipack was applied earlier
			if ( $item->get_meta( 'Multipack Discount' ) !== 'yes' ) {
				continue;
			}

			$product = $item->get_product();
			if ( ! $product ) {
				continue;
			}

			// Restore ORIGINAL subtotal (never recalc dynamically)
			$original_unit_price = (float) $item->get_meta( '_pinaka_multipack_unit_price_before' );
			$item_qty            = (int) $item->get_quantity();

			if ( $original_unit_price <= 0 || $item_qty <= 0 ) {
				continue;
			}

			$original_subtotal = wc_format_decimal(
				$original_unit_price * $item_qty,
				$decimals
			);

			// Recalculate tax
			$tax_class = $product->get_tax_class();
			$rates     = WC_Tax::get_rates( $tax_class );
			$taxes     = WC_Tax::calc_tax( $original_subtotal, $rates, false );
			$tax_total = array_sum( $taxes );

			// Restore prices
			$item->set_subtotal( $original_subtotal );
			$item->set_total( $original_subtotal );
			$item->set_subtotal_tax( wc_format_decimal( $tax_total, $decimals ) );
			$item->set_total_tax( wc_format_decimal( $tax_total, $decimals ) );

			// Remove ALL multipack meta
			$item->delete_meta_data( 'Multipack Discount' );
			$item->delete_meta_data( '_pinaka_multipack_unit_price_before' );
			$item->delete_meta_data( '_pinaka_multipack_unit_price_after' );
			$item->delete_meta_data( '_pinaka_multipack_product_discount' );
			$item->delete_meta_data( '_pinaka_multipack_original_subtotal' );
			$item->delete_meta_data( '_pinaka_multipack_hint' );
			$item->delete_meta_data( '_pinaka_multipack_applied' );
			$order->delete_meta_data('pack_type');

			$item->save();
		}

		$order->calculate_totals();
	}
	protected function apply_products_multipack_discount( $order ) {

		if ( ! $order || ! $order instanceof WC_Order ) {
			return;
		}

		$rules = $this->pinaka_product_multipack_rules();
		if ( empty( $rules ) ) {
			return;
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

			/* ---------------- LEGACY TABLES ---------------- */
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

		$items    = $order->get_items( 'line_item' );
		$decimals = wc_get_price_decimals();
		$today = current_time( 'Y-m-d' );

		foreach ( $items as $item ) {

			if ( $item->get_meta( '_pinaka_multipack_applied' ) === 'yes' ) {
				continue;
			}
			
			$product_id = $item->get_variation_id() ?: $item->get_product_id();
			if ( empty( $rules[ $product_id ] ) ) {
				continue;
			}
			// $is_dynamic = (int) get_post_meta( $product_id, '_dynamic_price_exists', true ) === 1;
			// if ( $is_dynamic ) {
			// 	continue;
			// }
			$discount_type = $item->get_meta( 'Discount Type' );
			if ( is_string( $discount_type ) && stripos( $discount_type, 'combo discount' ) !== false ) {
				continue;
			}

			$auto = $item->get_meta( '_pinaka_discount_amount_auto_apply' );
			if ( in_array( $auto, [ 'yes', '1', 1, true ], true ) ) {
				continue;
			}

			$product = $item->get_product();
			if ( ! $product ) {
				continue;
			}
			
			/* =====================================================
			* DYNAMIC PRICE vs MULTIPACK CHECK (FINAL LOGIC)
			* ===================================================== */

			// Base price without dynamic pricing (sale > regular)
			$base_price = (float) (
				$product->get_sale_price() ?: $product->get_regular_price()
			);

			// Variation fallback
			if ( $base_price <= 0 && $product->is_type( 'variation' ) ) {
				$parent = wc_get_product( $product->get_parent_id() );
				if ( $parent ) {
					$base_price = (float) (
						$parent->get_sale_price() ?: $parent->get_regular_price()
					);
				}
			}

			// Current effective price (may include dynamic pricing)
			$current_price = (float) $product->get_price();

			// Detect whether dynamic pricing is ACTIVE
			$dynamic_active = (
				$base_price > 0 &&
				abs( $current_price - $base_price ) > 0.0001
			);

			/*
			* RULE:
			* - Only multipack → APPLY
			* - Dynamic price >= base price → APPLY
			* - Dynamic price < base price → BLOCK
			*/
			if ( $dynamic_active && $current_price < $base_price ) {
				continue; // ❌ block multipack discount
			}


			$item_qty = (int) $item->get_quantity();

			$matched = null;
			foreach ( $rules[ $product_id ] as $tier ) 
			{
				$start_date = $tier['start_date'] ?? '';
				$end_date   = $tier['end_date'] ?? '';

				if ( $start_date && $today < $start_date ) {
					continue;
				}

				if ( $end_date && $today > $end_date ) {
					continue;
				}

				/* ---------------- Order usage validation ---------------- */
				$usage_limit = isset( $tier['order_usage'] )
					? (int) $tier['order_usage']
					: 0;

				$pack_key = $product_id . '-' . $tier['qty'];
				$used     = $counts[ $pack_key ] ?? 0;

				if ( $usage_limit > 0 && $used >= $usage_limit ) {
					continue;
				}

				if ( $item_qty >= (int) $tier['qty'] ) {
					$matched = $tier;
				}
			}

			if ( ! $matched ) {
				continue;
			}

			$required_qty = (int) $matched['qty'];
			$discount_raw = $matched['discount'];

			// Unit price
			$unit_price = (float) (
				$product->get_price() ??
				$product->get_regular_price() ??
				get_post_meta( $product->get_id(), '_price', true )
			);

			if ( $unit_price <= 0 ) {
				continue;
			}

			$unit_price_ex = wc_get_price_excluding_tax( $product, [ 'price' => $unit_price ] );

			$original_subtotal = $item->get_meta( '_pinaka_multipack_original_subtotal' );
			if ( $original_subtotal === '' ) {
				$original_subtotal = wc_format_decimal(
					$unit_price_ex * $item_qty,
					$decimals
				);
				$item->update_meta_data(
					'_pinaka_multipack_original_subtotal',
					$original_subtotal
				);
			}

			$original_subtotal = (float) $original_subtotal;

			$discount_amount = 0.0;

			if ( substr( $discount_raw, -1 ) === '%' ) {

				$percent = (float) rtrim( $discount_raw, '%' );
				if ( $percent > 0 ) {
					$discount_amount = ( $percent / 100 ) * ( $unit_price_ex * $required_qty );
				}

			} else {

				$fixed = (float) str_replace( ',', '', $discount_raw );
				if ( $fixed > 0 ) {
					$discount_amount = $fixed;
				}
			}

			$discount_amount = min(
				round( $discount_amount, $decimals ),
				$original_subtotal
			);

			if ( $discount_amount <= 0 ) {
				continue;
			}

			$new_subtotal = wc_format_decimal(
				$original_subtotal - $discount_amount,
				$decimals
			);

			$tax_class = $product->get_tax_class();
			$rates     = WC_Tax::get_rates( $tax_class );
			$taxes     = WC_Tax::calc_tax( $new_subtotal, $rates, false );
			$tax_total = array_sum( $taxes );

			$item->set_subtotal( $new_subtotal );
			$item->set_total( $new_subtotal );
			$item->set_subtotal_tax( wc_format_decimal( $tax_total, $decimals ) );
			$item->set_total_tax( wc_format_decimal( $tax_total, $decimals ) );

			$item->update_meta_data( 'Multipack Discount', 'yes' );
			$pack_key = $product_id . '-' . $required_qty;
			// $order->update_meta_data('pack_type',$pack_key);
			if ( ! in_array( $pack_key, wp_list_pluck( $order->get_meta( 'pack_type', false ), 'value' ), true ) ) {
				$order->add_meta_data( 'pack_type', $pack_key, false );
			}

			$item->update_meta_data(
				'_pinaka_multipack_unit_price_before',
				wc_format_decimal( $unit_price_ex, $decimals )
			);
			$item->update_meta_data(
				'_pinaka_multipack_unit_price_after',
				wc_format_decimal( $new_subtotal / $item_qty, $decimals )
			);
			$item->update_meta_data(
				'_pinaka_multipack_product_discount',
				wc_format_decimal( $discount_amount, $decimals )
			);
			$item->update_meta_data( '_pinaka_multipack_applied', 'yes' );

			$item->save();
		}
		$order->calculate_totals();
	}

	protected function format_multipack_discount_label( $discount_raw ) {

		if ( substr( $discount_raw, -1 ) === '%' ) {
			return rtrim( $discount_raw, '%' ) . '% off';
		}

		$amount = (float) str_replace( ',', '', $discount_raw );

		return function_exists( 'wc_price' )
			? wc_price( $amount )
			: '$' . number_format( $amount, 2 );
	}

	protected function pinaka_product_multipack_rules() {

		$rules = get_option( 'multipack_discount_settings', [] );
		if ( ! is_array( $rules ) ) {
			return [];
		}

		$grouped = [];

		foreach ( $rules as $rule ) {

			if (
				empty( $rule['product_id'] ) ||
				empty( $rule['qty'] ) ||
				empty( $rule['discount'] )
			) {
				continue;
			}

			$product_id = (int) $rule['product_id'];
			$qty        = max( 1, (int) $rule['qty'] );
			$discount   = trim( (string) $rule['discount'] );
			$start_date = $rule['start_date'];
			$end_date = $rule['end_date'];
			$order_usage = $rule['order_usage'];

			$grouped[ $product_id ][] = [
				'qty'      => $qty,
				'discount' => $discount,
				'start_date' => $start_date,
				'end_date' => $end_date,
				'order_usage' => $order_usage
			];
		}

		// Sort tiers by qty ASC
		foreach ( $grouped as &$tiers ) {
			usort( $tiers, function ( $a, $b ) {
				return $a['qty'] <=> $b['qty'];
			});
		}
		unset( $tiers );

		return $grouped;
	}

	protected function pinaka_auto_apply_discount_to_order( WC_Order $order ) {

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$today          = current_time( 'Y-m-d' );
		$order_items    = $order->get_items();
		$order_subtotal = (float) $order->get_subtotal();

		// Track discount state
		$order_has_auto_discount = false;
		$total_discount_amount  = 0;

		$discounts = get_posts([
			'post_type'   => 'discounts',
			'post_status' => 'publish',
			'numberposts' => -1,
			'meta_query'  => [
				[
					'key'   => '_pinaka_discount_auto_apply',
					'value' => 'yes',
				],
			],
		]);

		foreach ( $discounts as $discount ) {

			/* ----------------------------
			* DISCOUNT META
			* ---------------------------- */
			$product_id      = (int) get_post_meta( $discount->ID, '_product_label', true );
			$discount_type   = get_post_meta( $discount->ID, '_discount_type', true ); // percent | fixed_cart
			$discount_amount = (float) get_post_meta( $discount->ID, '_discount_amount', true );
			$start_date      = get_post_meta( $discount->ID, '_start_date', true );
			$expiry_date     = get_post_meta( $discount->ID, '_expiry_date', true );

			/* ----------------------------
			* RESTRICTIONS
			* ---------------------------- */
			$minimum_amount = (float) get_post_meta( $discount->ID, '_minimum_amount', true );
			$maximum_amount = (float) get_post_meta( $discount->ID, '_maximum_amount', true );

			/* ----------------------------
			* USAGE LIMIT
			* ---------------------------- */
			$usage_limit = (int) get_post_meta( $discount->ID, '_usage_limit', true );
			$usage_count = (int) get_post_meta( $discount->ID, '_usage_count', true );

			if ( $usage_limit > 0 && $usage_count >= $usage_limit ) {
				continue;
			}

			/* ----------------------------
			* DATE VALIDATION
			* ---------------------------- */
			if ( $start_date && $today < $start_date ) continue;
			if ( $expiry_date && $today > $expiry_date ) continue;

			/* ----------------------------
			* SUBTOTAL VALIDATION
			* ---------------------------- */
			if ( $minimum_amount && $order_subtotal < $minimum_amount ) continue;
			if ( $maximum_amount && $order_subtotal > $maximum_amount ) continue;

			$applied = false;

			/* ----------------------------
			* APPLY TO ITEMS
			* ---------------------------- */
			foreach ( $order_items as $item ) {

				if ( $item->get_product_id() !== $product_id ) {
					continue;
				}

				// Prevent double discount
				if ( $item->get_meta( '_pinaka_discount_amount_auto_apply', true ) ) {
					continue;
				}

				$product = $item->get_product();
				if ( ! $product ) continue;

				$qty = (int) $item->get_quantity();

				$original_price = (float) (
					$product->get_sale_price() ?: $product->get_regular_price()
				);

				/* ----------------------------
				* DISCOUNT CALCULATION
				* ---------------------------- */
				if ( $discount_type === 'percent' ) {
					// Example: 5% of 50 = 2.5
					$per_item_discount = ( $original_price * $discount_amount ) / 100;
				} else {
					// Fixed discount
					$per_item_discount = $discount_amount;
				}

				// Safety guards
				$per_item_discount = min( $per_item_discount, $original_price );
				$per_item_discount = round( $per_item_discount, 2 );

				$new_price = round( $original_price - $per_item_discount, 2 );

				// Totals
				$item_discount = ( $original_price - $new_price ) * $qty;
				$total_discount_amount += $item_discount;

				$item->set_subtotal( $new_price * $qty );
				$item->set_total( $new_price * $qty );

				/* ---------------------------------
				* LINE ITEM DISCOUNT AMOUNT (ONLY)
				* --------------------------------- */
				$item->update_meta_data( '_pinaka_discount_auto_apply', 'yes' );
				$item->update_meta_data(
					'auto_discount_amount',
					wc_format_decimal( $item_discount )
				);

				$item->save();

				$applied = true;
				$order_has_auto_discount = true;
			}

			/* ----------------------------
			* USAGE COUNT UPDATE
			* ---------------------------- */
			// if ( $applied ) {
			// 	update_post_meta(
			// 		$discount->ID,
			// 		'_usage_count',
			// 		$usage_count + 1
			// 	);
			// }
		}

		/* ----------------------------
		* FINAL ORDER META + TOTALS
		* ---------------------------- */
		if ( $order_has_auto_discount ) {
			$order->update_meta_data( '_pinaka_discount_amount_auto_apply', 'yes' );
			$order->update_meta_data( '_discount_amount', wc_format_decimal( $total_discount_amount ) );
			// $order->update_meta_data( '_pinaka_discount_auto_discount_apply', 'yes' );
		}

		$order->calculate_taxes();
		$order->calculate_totals();
		$order->save();
	}


	/**
	 * Apply multipack discounts based on multipack settings.
	 *
	 * This will create negative fee lines named "Multipack discount: {productname}".
	 * Discount supports values like "10" (fixed amount per pack) or "5%" (percentage of matched item's subtotal).
	 */
	protected function apply_multipack_discounts( $order ) {
		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		// Remove previously added multipack fee lines (optional cleanup)
		$this->pinaka_remove_existing_multipack_discount_lines( $order );

		// Load rules
		$rules = $this->pinaka_get_all_multipack_rules();
		if ( empty( $rules ) ) {
			return;
		}

		$items = $order->get_items( 'line_item' );
		if ( empty( $items ) ) {
			return;
		}

		$decimals = wc_get_price_decimals();
		$add_fee_line_for_visibility = false; // optional toggle

		foreach ( $rules as $rule ) {
			$rule_prodname = strtolower( sanitize_text_field( $rule['productname'] ) );
			$required_qty   = max( 1, intval( $rule['quantity'] ) );
			$discount_raw   = trim( $rule['discount'] );
			$discount_name   = trim( $rule['discount_name'] );

			foreach ( $items as $item_id => $item ) {
				$product = $item->get_product();
				if ( ! $product ) {
					continue;
				}

				// Compare by product title (case-insensitive)
				$title = strtolower( $product->get_name() );
				if ( $title !== $rule_prodname && stripos( $title, $rule_prodname ) === false ) {
					continue;
				}

				// ---------------- NEW CHECKBOX LOGIC (FIXED) ----------------
				$item_qty = (int) $item->get_quantity();

				$checkbox = intval( get_post_meta( $product->get_id(), 'multi_pack_checkbox', true ) );

				if ( $checkbox === 1 ) {
					// ENABLED → discount per item
					$required_qty = 1;
					$packs        = $item_qty;
				} else {
					// DISABLED → normal multipack rule
					if ( $item_qty < $required_qty ) {
						continue;
					}
					$packs = floor( $item_qty / $required_qty );
				}

				if ( $packs <= 0 ) {
					continue;
				}


				// Get reliable unit price
				$unit_price = $product->get_price();
				if ( $unit_price === '' || $unit_price === null ) {
					$unit_price = $product->get_regular_price();
				}
				if ( $unit_price === '' || $unit_price === null ) {
					$unit_price = get_post_meta( $product->get_id(), '_price', true );
				}
				$unit_price = (float) $unit_price;

				$unit_ex_tax  = wc_get_price_excluding_tax( $product, array( 'price' => $unit_price ) );
				$unit_inc_tax = wc_get_price_including_tax( $product, array( 'price' => $unit_price ) );

				$old_subtotal_ex = (float) $item->get_subtotal();
				if ( $old_subtotal_ex <= 0 ) {
					$old_subtotal_ex = wc_format_decimal( $unit_ex_tax * $item_qty, $decimals );
					$old_subtotal_ex = (float) $old_subtotal_ex;
				}

				// --- Calculate discount (ex-tax) ---
				$discount_amount_ex = 0.0;
				if ( substr( $discount_raw, -1 ) === '%' ) {
					$percent = floatval( rtrim( $discount_raw, '%' ) );
					if ( $percent > 0 ) {
						$per_pack_subtotal_ex = ( $unit_ex_tax * $required_qty );
						$discount_amount_ex   = ( $percent / 100.0 ) * $per_pack_subtotal_ex * $packs;
					}
				} else {
					$fixed = floatval( str_replace( ',', '', $discount_raw ) );
					if ( $fixed > 0 ) {
						$discount_amount_ex = $fixed * $packs;
					}
				}

				$discount_amount_ex = round( (float) $discount_amount_ex, $decimals );
				if ( $discount_amount_ex <= 0 ) {
					continue;
				}

				$new_subtotal_ex = max( 0, $old_subtotal_ex - $discount_amount_ex );

				// --- Recalculate taxes for new subtotal ---
				$tax_class = $product->get_tax_class();
				$rates = WC_Tax::get_rates( $tax_class );
				$taxes_calc = WC_Tax::calc_tax( $new_subtotal_ex, $rates, false );
				$new_subtotal_tax = array_sum( $taxes_calc );

				// --- Apply new totals to item ---
				$item->set_subtotal( wc_format_decimal( $new_subtotal_ex, $decimals ) );
				$item->set_subtotal_tax( wc_format_decimal( $new_subtotal_tax, $decimals ) );
				$item->set_total( wc_format_decimal( $new_subtotal_ex, $decimals ) );
				$item->set_total_tax( wc_format_decimal( $new_subtotal_tax, $decimals ) );

				// --- NEW: Clean meta format ---
				$discount_name  = $discount_name ? $discount_name : 'DISCOUNT MULTIPACK';
				$discount_value = wc_format_decimal( $discount_amount_ex, $decimals );
                $multi_pack_loyalty_id = $rule['multipack_loyalty_id'] ?? '';

				// Remove old serialized meta if present
				$item->delete_meta_data( '_pinaka_multipack_discount' );

				// Add your clean fields
				$item->update_meta_data( '_discount_name', $discount_name );
				$item->update_meta_data( '_discount_value', $discount_value );
                $item->update_meta_data( '_loyalty_id', $multi_pack_loyalty_id );


				// --- Save item after discount ---
                $item->save();

				// --- Optional: fee line for visibility ---
				if ( $add_fee_line_for_visibility ) {
					$fee = new WC_Order_Item_Fee();
					$fee_name = sprintf( 'Multipack discount: %s (x%d)', $rule['productname'], $packs );
					$amount_float = -1 * $discount_amount_ex;
					$fee->set_name( $fee_name );
					$fee->set_total( wc_format_decimal( $amount_float, $decimals ) );
					$fee->set_tax_status( 'none' );
					$fee->set_taxes( [ 'total' => [], 'shipping' => [] ] );
					$fee->add_meta_data( '_pinaka_multipack_rule', maybe_serialize( $rule ) );
					$order->add_item( $fee );
				}
			}
		}
		$order->calculate_totals();
        
	}


	/**
	 * Remove any existing multipack discount fee lines previously added by this plugin.
	 */
	protected function pinaka_remove_existing_multipack_discount_lines( $order ) {
		foreach ( $order->get_items( 'fee' ) as $item_id => $fee_item ) {
			// Use a marker in the fee name/description to identify our multipack discounts
			$name = method_exists( $fee_item, 'get_name' ) ? $fee_item->get_name() : '';
			if ( strpos( $name, 'Multipack discount:' ) !== false ) {
				$order->remove_item( $item_id );
			}
		}
	}

	/**
	 * Read all multipack rules from posts which have _multipack_settings meta.
	 * Returns array of rules: [ ['productname'=>'Tomato', 'quantity'=>2, 'discount'=>'10%'], ... ]
	 */
	protected function pinaka_get_all_multipack_rules() {
		$rules = [];

		$posts = get_posts([
			'post_type'      => 'program',
			'posts_per_page' => -1,
			'meta_query'     => [
				[
					'key'     => '_multipack_settings',
					'compare' => 'EXISTS',
				],
			],
			'fields' => 'ids',
		]);

		if ( empty( $posts ) ) {
			return $rules;
		}

		foreach ( $posts as $pid ) {
			$json = get_post_meta( $pid, '_multipack_settings', true );
			if ( empty( $json ) ) {
				continue;
			}

			$data = json_decode( $json, true );
			if ( ! is_array( $data ) ) {
				continue;
			}

			foreach ( $data as $row ) {

				$prodname      = isset( $row['productname'] ) ? trim( $row['productname'] ) : '';
				$qty           = isset( $row['quantity'] ) ? intval( $row['quantity'] ) : 0;
				$discount      = isset( $row['discount'] ) ? trim( $row['discount'] ) : '';
				$discount_name = isset( $row['discount_name'] ) ? trim( $row['discount_name'] ) : '';
				$loyalty_id    = isset( $row['loyalty_id'] ) ? trim( $row['loyalty_id'] ) : '';
				$checkbox      = isset( $row['checkbox'] ) ? intval( $row['checkbox'] ) : 0; 

				if ( $prodname !== '' && $qty > 0 && $discount !== '' ) {
					$rules[] = [
						'productname'          => $prodname,
						'quantity'             => $qty,
						'discount'             => $discount,
						'discount_name'        => $discount_name,
						'multipack_loyalty_id' => $loyalty_id,
						'checkbox'             => $checkbox,  
					];
				}
			}
		}

		return $rules;
	}
	
	
	/**
	 * Compute and store the order's EBT-eligible total.
	 * Sums (line total + line total tax) for any product line that has the "EBT Eligible" tag.
	 *
	 * @param WC_Order $order
	 * @return float The computed EBT-eligible total (already formatted to WC decimals)
	 */
	protected function pinaka_update_ebt_eligible_meta( $order ) {
		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
			return 0.0;
		}

		$decimals = wc_get_price_decimals();
		$sum = 0.0;
		$sum_wo_tax   = 0.0; // excludes tax
		$sum_tax_only = 0.0;

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			/** @var WC_Order_Item_Product $item */
			// get product object (handles variation fallback)
			$product = null;
			if ( method_exists( $item, 'get_product' ) ) {
				$product = $item->get_product();
			}

			// fallback by product_id / variation_id
			if ( ! $product ) {
				$variation_id = method_exists( $item, 'get_variation_id' ) ? (int) $item->get_variation_id() : 0;
				$product_id   = method_exists( $item, 'get_product_id' ) ? (int) $item->get_product_id() : 0;

				if ( $variation_id ) {
					$product = wc_get_product( $variation_id );
				} elseif ( $product_id ) {
					$product = wc_get_product( $product_id );
				}
			}

			if ( ! $product || ! $product->get_id() ) {
				continue;
			}

			// prefer parent product tags for variations
			$check_product_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
			if ( ! $check_product_id ) {
				$check_product_id = $product->get_id();
			}

			// get product tag names (defensive and case-insensitive)
			$tag_names = wp_get_post_terms( $check_product_id, 'product_tag', array( 'fields' => 'names' ) );
			if ( is_wp_error( $tag_names ) || empty( $tag_names ) ) {
				continue;
			}

			$found = false;
			foreach ( $tag_names as $tname ) {
				if ( ! is_string( $tname ) ) {
					continue;
				}
				$lower = strtolower( trim( $tname ) );
				if ( $lower === 'ebt eligible' || $lower === 'ebt-eligible' || $lower === 'ebt_eligible' ) {
					$found = true;
					break;
				}
			}

			// also check slug directly (in case slug used and name differs)
			if ( ! $found ) {
				$terms = wp_get_post_terms( $check_product_id, 'product_tag', array( 'fields' => 'slugs' ) );
				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					foreach ( $terms as $slug ) {
						if ( ! is_string( $slug ) ) {
							continue;
						}
						if ( strtolower( $slug ) === 'ebt-eligible' ) {
							$found = true;
							break;
						}
					}
				}
			}

			if ( ! $found ) {
				continue;
			}

			// sum this line's total + taxes (safe numeric casts)
			$line_total     = (float) $item->get_total();       // usually excludes tax
			$line_total_tax = (float) $item->get_total_tax();   // tax for this line
			$sum += ( $line_total + $line_total_tax );
			$sum_wo_tax += $line_total; // without tax
			$sum_tax_only += $line_total_tax;
		}

		// format/round and save to order meta
		$sum = (float) wc_format_decimal( $sum, $decimals );

		// store (use update_meta_data so it persists on $order->save())
		$order->update_meta_data( '_pinaka_ebt_eligible_total', $sum );
		$order->update_meta_data( '_pinaka_ebt_eligible_total_wo_tax', $sum_wo_tax );
		$order->update_meta_data( '_pinaka_ebt_eligible_total_tax_only', $sum_tax_only );

		return $sum;
	}

	/**
	 * Validate line_items in the incoming request against stock without mutating order.
	 *
	 * @param WP_REST_Request $request
	 * @param WC_Order|null   $existing_order  Existing order (when updating), or null when creating
	 * @return null|WP_Error
	 */
	private function validate_line_items_stock_request( $request, $existing_order = null ) {
		$data = $request->get_json_params();
		if ( empty( $data['line_items'] ) || ! is_array( $data['line_items'] ) ) {
			return null;
		}

		foreach ( $data['line_items'] as $item ) {
			$requested_qty = isset( $item['quantity'] ) ? intval( $item['quantity'] ) : 1;
			if ( $requested_qty <= 0 ) {
				// treat as removal or skip
				continue;
			}
			if(!isset($item['product_id']))
			{
				continue;
			}
			// Prefer variation_id then product_id
			$prod_id = 0;
			if ( ! empty( $item['variation_id'] ) ) {
				$prod_id = absint( $item['variation_id'] );
			} elseif ( ! empty( $item['product_id'] ) ) {
				$prod_id = absint( $item['product_id'] );
			}

			// If this is an update to an existing order item, we can derive product and existing qty from existing_order
			$existing_qty = 0;
			if ( ! empty( $item['id'] ) && $existing_order instanceof WC_Order ) {
				$existing_item = $existing_order->get_item( $item['id'] );
				if ( $existing_item ) {
					$existing_qty = (int) $existing_item->get_quantity();
					// if product id not provided in payload, derive from existing order item
					if ( ! $prod_id ) {
						$existing_product = $existing_item->get_product();
						if ( $existing_product ) {
							// prefer variation id
							$prod_id = ( $existing_item->get_variation_id() ) ? $existing_item->get_variation_id() : $existing_product->get_id();
						}
					}
				}
			}

			if ( ! $prod_id ) {
				return new WP_Error(
					'woocommerce_rest_invalid_product',
					__( 'Line item missing product_id/variation_id and unable to detect product from existing order.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}

			$product = wc_get_product( $prod_id );
			if ( ! $product ) {
				return new WP_Error(
					'woocommerce_rest_invalid_product',
					__( 'Invalid product/variation ID in line_items.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}

			// If product does not manage stock, allow by default (change if you want stricter behavior).
			if ( ! $product->managing_stock() ) {
				continue;
			}

			// Disallow if backorders are not permitted by your policy for API changes.
			if ( $product->backorders_allowed() ) {
				// if you want to *reject* backorders for API, replace continue; with return error.
				continue;
			}

			$delta = $requested_qty - $existing_qty;
			if ( $delta <= 0 ) {
				continue; // reducing qty or same qty -> safe
			}

			$available_stock = (int) $product->get_stock_quantity();
			if ( $available_stock < $delta ) {
				return new WP_Error(
					'woocommerce_rest_stock_error',
					sprintf(
						__( 'This item is out of stock. Please check.' ),
						$product->get_name(),
						$available_stock,
					),
					array( 'status' => 400 )
				);
			}
		}

		return null;
	}

	/**
	 * Remove any existing payout representations from the order (product or fee).
	 * Returns a recovered payout amount if we find one; otherwise 0.0.
	 */
// 	private function pinaka_remove_existing_payout_lines( $order ): float {
// 		$recovered = 0.0;
// 		// 1) Get Order meta _order_payout (if any)
// 		$meta_payout = $order->get_meta( '_order_payout', true );
// 		if ( is_numeric( $meta_payout ) ) {
// 			$recovered = floatval( $meta_payout );
// 		}

// 		// 2) Remove payout product lines (legacy representation)
// 		// If you used a fixed product ID, set it here; else rely on _is_payout meta.
// 		$payout_product_id = get_option( 'pinaka_payout_product_id', 0 ); // e.g. 9999 if you had one; else 0
// 		foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
// 			if ( ( $payout_product_id && $item->get_product_id() == $payout_product_id ) ) {
// 				$order->remove_item( $item_id );
// 			}
// 		}
// 		return $recovered;
// 	}
    private function pinaka_remove_existing_payout_lines( $order ): float {
		$recovered = 0.0;
		$meta_payout = $order->get_meta( '_order_payout', true );
		if ( is_numeric( $meta_payout ) ) {
			$recovered = (float) $meta_payout;
		}

		$payout_product_id = (int) get_option( 'pinaka_payout_product_id', 0 );

		foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {

			if ( $payout_product_id && $item->get_product_id() == $payout_product_id ) {
				$recovered = $item->get_total();

				$order->remove_item( $item_id );
				break;
			}
		}

		return $recovered;
	}

	/**
	 * Remove any existing payout representations from the order (product or fee).
	 * Returns a recovered payout amount if we find one; otherwise 0.0.
	 */
	private function pinaka_remove_existing_discount_lines( $order ): float {
		$recovered = 0.0;

		// 1) Get Order meta _order_payout (if any)
		$meta_discount = $order->get_meta( '_order_discount', true );
		if ( is_numeric( $meta_discount ) ) {
			$recovered = floatval( $meta_discount );
		}
		if($recovered == 0.0)
		{
			foreach ( $order->get_items( 'line_item' ) as $item ) {
				if($item->get_name() === 'Discount')
				{
					$recovered = $item->get_total();
					break;
				}
			}
			// return $recovered;
		}
		// 2) Remove payout product lines (legacy representation)
		// If you used a fixed product ID, set it here; else rely on _is_payout meta.
		$discount_product_id = get_option( 'pinaka_discount_product_id', 0 );; // e.g. 9999 if you had one; else 0
		foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
			if ( ( $discount_product_id && $item->get_product_id() == $discount_product_id ) ) {
				$order->remove_item( $item_id );
			}
		}
		return $recovered;
	}

	/**
	 * Get formatted item data.
	 *
	 * @param WC_Order $order WC_Data instance.
	 * @return array
	 */
	protected function get_formatted_item_data( $order ) {
        // Fourth Step
		$item_data       = parent::get_formatted_item_data( $order );
		$cpt_hidden_keys = array();

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$cpt_hidden_keys = ( new \WC_Order_Data_Store_CPT() )->get_internal_meta_keys();
		}

		// XXX: This might be removed once we finalize the design for internal keys vs meta vs props in COT.
		if ( ! empty( $item_data['meta_data'] ) ) {
			$item_data['meta_data'] = array_filter(
				$item_data['meta_data'],
				function ( $meta ) use ( $cpt_hidden_keys ) {
					return ! in_array( $meta->key, $cpt_hidden_keys, true );
				}
			);
		}

		return $item_data;
	}
    
	/**
	 * Prepare objects query.
	 *
	 * @since  3.0.0
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {	
		// This is needed to get around an array to string notice in WC_REST_Orders_V2_Controller::prepare_objects_query.
		$statuses = $request['status'];
		unset( $request['status'] );

		// Prevents WC_REST_Orders_V2_Controller::prepare_objects_query() from generating a meta_query for 'customer'.
		// which COT can handle as a native field.
		$cot_customer =
			( OrderUtil::custom_orders_table_usage_is_enabled() && isset( $request['customer'] ) )
			? $request['customer']
			: null;

		if ( $cot_customer ) {
			unset( $request['customer'] );
		}


		$args = parent::prepare_objects_query( $request );

		$args['post_status'] = array();
		foreach ( $statuses as $status ) {
			if ( in_array( $status, $this->get_order_statuses(), true ) ) {
				$args['post_status'][] = 'wc-' . $status;
			} elseif ( 'any' === $status ) {

				foreach ( $this->get_order_statuses() as $status_order ) {
					$args['post_status'][] = 'wc-' . $status_order;
				}
				break;
			} else {
				$args['post_status'][] = $status;
			}
		}

		// Put the statuses back for further processing (next/prev links, etc).
		$request['status'] = $statuses;
		
		if ( isset( $request['created_via'] ) && $request['created_via'] === 'doordash' ) {
			$args['created_via'] = 'doordash';
		} else {
			$args['order_created_via'] = 'pinaka_pos';
		}

		// Add back 'customer' to args and request.
		if ( ! is_null( $cot_customer ) ) {
			$args['customer']    = $cot_customer;
			$request['customer'] = $cot_customer;
		}

		$args['author'] = intval( $request['author'] ) ?? get_current_user_id();
		return $args;
	}

	/**
	 * Get objects.
	 *
	 * @param  array $query_args Query args.
	 * @return array
	 */
	protected function get_objects( $query_args ) {
		// Do not use WC_Order_Query for the CPT datastore.
		if ( ! OrderUtil::custom_orders_table_usage_is_enabled() ) {
			return parent::get_objects( $query_args );
		}
		
		if($query_args['author'] == 0){
			unset( $query_args['author'] );
			$query_args['author'] = get_current_user_id();
		}
		if(	isset( $query_args['created_via'] ) && $query_args['created_via'] == 'doordash' ){

			return array(
				'objects' => array(),
				'total'   => 0,
				'pages'   => 0,
			);
		}else{
			$query   = new \WC_Order_Query(
				array_merge(
					$query_args,
					array(
						'paginate' => true,
						'meta_query' => array(
							array(
								'key'     => '_wc_order_created_by',
								'value'   => $query_args['author'],
								'compare' => '=', // or 'LIKE', 'IN', '!=' etc.
							),
						),
					)
				)
			);
			

			$results = $query->get_orders();
			
			return array(
				'objects' => $results->orders,
				'total'   => $results->total,
				'pages'   => $results->max_num_pages,
			);
		}

	}

	/**
	 * Get the Order's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

		$schema['properties']['coupon_lines']['items']['properties']['discount']['readonly'] = true;
		$schema['properties']['status']['enum'] = array_merge( array( 'pending-purchase', 'ordered-purchase', 'received-purchase' ), $this->get_order_statuses() );

		return $schema;
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
 
		$params = parent::get_collection_params();

		$params['status'] = array(
			'default'           => 'any',
			'description'       => __( 'Limit result set to orders which have specific statuses.', 'woocommerce' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
				'enum' => array_merge( array( 'any', 'trash', 'wc-pending-purchase', 'wc-ordered-purchase', 'wc-received-purchase' ), $this->get_order_statuses() ),
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Read service charge settings with safe defaults.
	 * Returns an array with keys: enabled, charge_type, apply_to, max_charge, tiers (sorted).
	 */
	protected function pinaka_get_service_charge_settings() {
		$option_key = 'pinaka_pos_service_charge_settings';
		$opts = get_option( $option_key, [] );

		$defaults = [
			'enabled'     => 0,
			'charge_type' => 'fixed',
			'apply_to'    => 'order',
			'max_charge'  => 0,
			'tiers'       => [],
		];

		$opts = wp_parse_args( (array) $opts, $defaults );

		// Normalize: ensure types
		$opts['enabled']     = empty( $opts['enabled'] ) ? 0 : 1;
		$opts['charge_type'] = in_array( $opts['charge_type'], [ 'fixed', 'percentage' ], true ) ? $opts['charge_type'] : 'fixed';
		$opts['apply_to']    = in_array( $opts['apply_to'], [ 'order', 'line_items' ], true ) ? $opts['apply_to'] : 'order';
		$opts['max_charge']  = isset( $opts['max_charge'] ) && $opts['max_charge'] !== '' ? floatval( $opts['max_charge'] ) : 0;

		// sanitize tiers (already expected to be stored sanitized, but be defensive)
		$tiers = [];
		if ( ! empty( $opts['tiers'] ) && is_array( $opts['tiers'] ) ) {
			foreach ( $opts['tiers'] as $t ) {
				if ( ! is_array( $t ) ) {
					continue;
				}
				$from = isset( $t['from'] ) ? floatval( $t['from'] ) : 0;
				$to   = isset( $t['to'] ) ? floatval( $t['to'] ) : 0;
				$fee  = isset( $t['fee'] ) ? floatval( $t['fee'] ) : 0;
				$ft   = isset( $t['fee_type'] ) && in_array( $t['fee_type'], [ 'fixed', 'percentage' ], true ) ? $t['fee_type'] : 'fixed';

				if ( $to < $from ) {
					continue;
				}
				$tiers[] = [
					'from'     => $from,
					'to'       => $to,
					'fee'      => $fee,
					'fee_type' => $ft,
				];
			}
			// sort by from
			usort( $tiers, function( $a, $b ) {
				return $a['from'] <=> $b['from'];
			} );
		}
		$opts['tiers'] = $tiers;

		return $opts;
	}

	/**
	 * Remove existing service charge fee lines added by this plugin.
	 * Marks removed fee totals returned (sum of removed fees) — useful for debugging if needed.
	 */
	protected function pinaka_remove_existing_service_charge_lines( $order ) {
		$removed_total = 0.0;

		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
			return 0.0;
		}

		foreach ( $order->get_items( 'fee' ) as $item_id => $fee_item ) {
			$meta = $fee_item->get_meta( '_pinaka_service_charge', true );
			if ( $meta ) { // our marker
				$removed_total += (float) $fee_item->get_total();
				$order->remove_item( $item_id );
			}
		}

		return $removed_total;
	}

	/**
	 * Compute service charge amount for the order based on settings.
	 * - If apply_to == 'order', base is order subtotal (or order total before fees)
	 * - If apply_to == 'line_items', sum per-line base amounts and compute per-line fees (then sum)
	 *
	 * $order is WC_Order object that has current line items set (but might not have fee lines)
	 * $request optionally used if you want to base on incoming payload instead of order object.
	 *
	 * Returns a float (amount) >= 0.0
	 */
	protected function pinaka_compute_service_charge_amount( $order, $request = null ) {
		$settings = $this->pinaka_get_service_charge_settings();
		if ( empty( $settings['enabled'] ) ) {
			return 0.0;
		}

		// Choose base amounts
		// If apply_to == order, use order subtotal (excl. fees) — best to use $order->get_subtotal() if exists, otherwise compute
		$base_total = 0.0;

		if ( $settings['apply_to'] === 'line_items' ) {
			// sum each product line total (excluding existing fees)
			foreach ( $order->get_items( 'line_item' ) as $li ) {
				$qty = (float) $li->get_quantity();
				$line_total = (float) $li->get_total(); // totals are typically ex-tax; we'll rely on same base for percentage
				$base_total += $line_total;
			}
		} else {
			// order-level: use order subtotal (sum of line items totals)
			$sum = 0.0;
			foreach ( $order->get_items( 'line_item' ) as $li ) {
				$sum += (float) $li->get_total();
			}
			$base_total = $sum;
		}

		// Now determine which tier applies (based on base_total)
		$amount = 0.0;
		$found = false;
		if ( ! empty( $settings['tiers'] ) ) {
			foreach ( $settings['tiers'] as $tier ) {
				$from = floatval( $tier['from'] );
				$to   = floatval( $tier['to'] );
				if ( $base_total >= $from && $base_total <= $to ) {
					// apply this tier
					if ( $tier['fee_type'] === 'percentage' ) {
						// percentage of base_total
						$amount = ( $base_total * floatval( $tier['fee'] ) ) / 100.0;
					} else {
						// fixed fee for the whole order (or treat as per line? we use whole-order fixed)
						$amount = floatval( $tier['fee'] );
					}
					$found = true;
					break;
				}
			}
		}

		// if no tier matched, fall back to global charge_type
		if ( ! $found ) {
			if ( $settings['charge_type'] === 'percentage' ) {
				// If there is no explicit percentage value stored globally, assume 0
				$global_percentage = 0;
				// Try to find a "global fallback" tier stored maybe as a single-tier with from=0,to=0 — but keep simple
				// We'll treat global percentage as 0 unless you store it elsewhere.
				$amount = ( $base_total * floatval( $global_percentage ) ) / 100.0;
			} else {
				$amount = 0.0; // default no charge if no tiers and no global value present
			}
		}

		// Apply max_charge cap if provided
		if ( $settings['max_charge'] > 0 && $amount > $settings['max_charge'] ) {
			$amount = $settings['max_charge'];
		}

		// Round to WooCommerce decimals
		$decimals = wc_get_price_decimals();
		$amount = (float) wc_format_decimal( $amount, $decimals );

		if ( $amount < 0 ) {
			$amount = 0.0;
		}

		return $amount;
	}

	/**
	 * Add a non-taxable service charge fee line to the order.
	 * Marker meta _pinaka_service_charge added so it can be removed later.
	 */
	protected function pinaka_add_service_charge_fee( $order, $amount ) {
		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		// Skip if zero or negative
		if ( empty( $amount ) || floatval( $amount ) <= 0 ) {
			return;
		}

		$amount = (float) wc_format_decimal( $amount, wc_get_price_decimals() );

		$fee = new WC_Order_Item_Fee();
		$fee->set_name( __( 'Service Charge', 'pinaka-pos' ) );
		$fee->set_amount( $amount );
		$fee->set_total( $amount );

		// ✅ Make sure it's explicitly non-taxable
		$fee->set_tax_status( 'none' );   // mark as non-taxable
		$fee->set_tax_class( '' );        // no tax class
		$fee->set_taxes( [] );            // no tax data

		$fee->add_meta_data( '_pinaka_service_charge', 1, true ); // marker for removal

		$order->add_item( $fee );

		// Ensure WooCommerce doesn't try to tax this fee later
		if ( method_exists( $fee, 'set_total_tax' ) ) {
			$fee->set_total_tax( 0 );
		}
	}


	function sync_offline_orders(WP_REST_Request $request) {
		$body = $request->get_json_params();
		$get_cashback_fee = 0;
		if (empty($body['orders']) || !is_array($body['orders'])) {
			return new WP_REST_Response(['error' => 'Invalid payload'], 400);
		}

		$results = [];
		foreach ($body['orders'] as $ord) {
			$client_id = $ord['client_order_id'] ?? null;
			if (!$client_id) {
				$results[] = ['status' => 'skipped', 'reason' => 'no client_order_id'];
				continue;
			}

			// Idempotency: check if order with this client id already exists
			// $existing = get_posts([
			// 	'post_type' => 'shop_order',
			// 	'meta_key'  => '_client_order_id',
			// 	'meta_value'=> $client_id,
			// 	'posts_per_page' => 1,
			// ]);
			// if (!empty($existing)) {
			// 	$results[] = ['status' => 'exists', 'client_order_id' => $client_id, 'order_id' => $existing[0]->ID];
			// 	continue;
			// }

			try {
				$order = wc_create_order();
				$order->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );
				// billing
				if (!empty($ord['billing'])) {
					$order->set_address($ord['billing'], 'billing');
				}
				if (!empty($ord['shipping'])) {
					$order->set_address($ord['shipping'], 'shipping');
				}

				// 
				// line items
				if (!empty($ord['line_items']) && is_array($ord['line_items'])) {
					foreach ($ord['line_items'] as $li) {

						// Get product id (or create)
						if (empty($li['product_id'])) {
							$product_id = $this->createCustomProduct($li);
						} else {
							$product_id = intval($li['product_id']);
							$variation_id = intval($li['variation_id'] ?? 0);
						}

						$qty = intval($li['quantity'] ?? 1);

						$product = wc_get_product($variation_id ?: $product_id);
						if ($product_id == get_option( 'pinaka_cashback_product_id', 0 ) ) {
							$get_cashback_fee = $this->pinaka_pos_get_cashback_fee_for_amount( floatval( $li['total'] ?? 0 ) );
						}
						if (!$product) {
							continue;
						}

						$price = floatval($li['total'] ?? $product->get_price());
						$line_subtotal = $price * $qty;

						// TAX CALCULATION
						$tax_class = $product->get_tax_class();
						$tax_rates = WC_Tax::get_rates($tax_class);
						$taxes = WC_Tax::calc_tax($line_subtotal, $tax_rates, wc_prices_include_tax());

						// Add product using low-level API to set totals manually
						$item = new WC_Order_Item_Product();
						$item->set_product($product);
						$item->set_quantity($qty);
						$item->set_subtotal($line_subtotal);
						$item->set_total($line_subtotal);
						$item->set_subtotal_tax(array_sum($taxes));
						$item->set_total_tax(array_sum($taxes));
						$item->set_taxes(['total' => $taxes, 'subtotal' => $taxes]);

						$order->add_item($item);
					}
				}

				// fees
				if ($get_cashback_fee > 0) {
					$fee = new WC_Order_Item_Fee();
					$fee->set_name('Cashback Fee');
					$fee->set_amount(floatval($get_cashback_fee ?? 0));
					$fee->set_total(floatval($get_cashback_fee ?? 0));
					$order->add_item($fee);
				}

				// meta data
				if (!empty($ord['meta_data'])) {
					foreach ($ord['meta_data'] as $m) {
						if (!empty($m['key'])) {
							$order->update_meta_data($m['key'], $m['value'] ?? '');
						}
					}
				}
				// store client id for idempotency
				$order->update_meta_data('_pos_client_order_id', $client_id);
				$order->update_meta_data( 'order_created_via', 'pinaka_pos' );
				$order->update_meta_data( '_wc_order_created_by', get_current_user_id() );
				
				$order->set_created_via( 'rest-api' );
				$order->calculate_totals();
				// POS payments → amount
				$paid_amount = 0.0;
				if (!empty($ord['meta_data'])) {
					$paid_amount = $this->get_pos_payment_total($ord['meta_data']);
				}

				// Order total
				$order_total = floatval($order->get_total());

				// Decide status
				$status = 'cancelled'; // default

				// Set payment method safely
				$pm = $ord['payment']['method'] ?? 'cash';
				$order->set_payment_method($pm);

				// Apply status
				$order->set_status('wc-cancelled');

				// Store paid amount (optional but VERY useful)
				$order->update_meta_data('_pos_paid_total', $paid_amount);
				$order->update_meta_data('_pos_order_total', $order_total);
				$order->save();

				$results[] = ['status' => 'created', 'client_order_id' => $client_id, 'order_id' => $order->get_id(), 'order_status' => $status];
			} catch (Exception $e) {
				$results[] = ['status' => 'error', 'client_order_id' => $client_id, 'message' => $e->getMessage()];
			}
		}

		return new WP_REST_Response(['results' => $results], 200);
	}

	private function get_pos_payment_total(array $meta_data): float {
		$total = 0.0;

		foreach ($meta_data as $m) {
			if (($m['key'] ?? '') === '_pos_payments' && is_array($m['value'])) {
				foreach ($m['value'] as $payment) {
					if (
						($payment['status'] ?? '') === 'successful' &&
						isset($payment['amount'])
					) {
						$total += floatval($payment['amount']);
					}
				}
			}
		}

		return round($total, 2);
	}


	private function decide_order_status(float $paid, float $order_total): string {
		$order_total = round($order_total, 2);
		$paid        = round($paid, 2);

		if ($paid <= 0) {
			return 'pending';
		}

		if ($paid >= $order_total) {
			return 'completed';
		}

		return 'processing'; // or custom: partially-paid
	}



	public function createCustomProduct( &$item ) {
		$name  = sanitize_text_field( $item['name'] );
		$price = isset( $item['price'] ) ? floatval( $item['price'] ) : 0;
		$sku   = isset( $item['sku'] ) ? sanitize_text_field( $item['sku'] ) : '';

		$product_id = 0;
		if ( ! empty( $sku ) ) {
			$args = [
				'post_type'      => ['product', 'product_variation'],
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'meta_query'     => [
					[
						'key'     => '_sku',
						'value'   => $sku,
						'compare' => '=',
					]
				],
				'fields' => 'ids'
			];

			$query = new WP_Query( $args );
			if ( ! empty( $query->posts ) ) {
				$product_id = (int) $query->posts[0];
			}
		}
		if ( ! $product_id ) {
			$product = new WC_Product_Simple();
			$product->set_name( $name );
			$product->set_regular_price( $price );
			$product->set_status( 'publish' );

			if ( ! empty( $sku ) ) {
				$product->set_sku( $sku );
			}
			$product->update_meta_data( 'custom_item', 'yes' );
			$product_id = $product->save();
		}
		return $product_id;
	}


	function get_all_order_status_counts( WP_REST_Request $request ) {
		global $wpdb;

		$status = sanitize_text_field( $request->get_param('status') ); // e.g. wc-completed
		$table  = $wpdb->prefix . 'wc_orders';

		$results = $wpdb->get_results(
			"SELECT status, COUNT(id) AS total
			FROM {$table}
			GROUP BY status",
			OBJECT_K
		);

		$count = 0;

		if ( $status && isset( $results[ $status ] ) ) {
			$count = (int) $results[ $status ]->total;
		}

		$response = array(
			'success' => true,
			'status'  => $status,
			'total'   => $count,
		);

		return rest_ensure_response( $response );
	}


	private function get_meta_value( array $meta, string $key ) {
		foreach ( $meta as $m ) {
			if ( ($m['key'] ?? '') === $key ) {
				return $m['value'] ?? null;
			}
		}
		return null;
	}

	private function getOrderMetaByKey( WC_Order $order, string $key ) {
		$meta_items = $order->get_meta_data();
		foreach ( $meta_items as $meta ) {
			if ( $meta->key === $key ) {
				return $meta->value;
			}
		}
		return null;
	}


	/**
	 * Validate shift_id passed via meta_data for POS orders.
	 *
	 * Rules:
	 * - shift_id must be present in meta_data
	 * - shift_id must not be empty
	 * - shift_id must be a valid post ID
	 * - shift must exist
	 * - shift must NOT be closed
	 *
	 * @throws WP_Error
	 */
	private function validate_shift_id( WP_REST_Request $request ) {

		$meta_data = $request->get_param( 'meta_data' );

		if ( ! is_array( $meta_data ) ) {
			return new WP_Error(
				'woocommerce_rest_missing_shift_id',
				__( 'The shift_id is required in meta_data.', 'pinaka-pos' ),
				[ 'status' => 400 ]
			);
		}

		$shift_id_raw = $this->get_meta_value( $request->get_param('meta_data'), 'shift_id' );;

		/* ------------------------------------
		* RULE 1: shift_id must exist
		* ------------------------------------ */
		if ( $shift_id_raw === null ) {
			return new WP_Error(
				'woocommerce_rest_missing_shift_id',
				__( 'The shift_id is required in meta_data.', 'pinaka-pos' ),
				[ 'status' => 400 ]
			);
		}

		/* ------------------------------------
		* RULE 2: shift_id must not be empty
		* ------------------------------------ */
		if ( $shift_id_raw === '' || $shift_id_raw === false ) {
			return new WP_Error(
				'woocommerce_rest_missing_shift_id',
				__( 'The shift_id cannot be empty.', 'pinaka-pos' ),
				[ 'status' => 400 ]
			);
		}

		/* ------------------------------------
		* RULE 3: shift_id must be valid integer
		* ------------------------------------ */
		$shift_id = absint( $shift_id_raw );

		if ( $shift_id <= 0 ) {
			return new WP_Error(
				'woocommerce_rest_invalid_shift_id',
				__( 'The shift_id provided is not a valid ID.', 'pinaka-pos' ),
				[ 'status' => 400 ]
			);
		}

		/* ------------------------------------
		* RULE 4: shift post must exist
		* ------------------------------------ */
		$shift_post = get_post( $shift_id );

		if ( ! $shift_post ) {
			return new WP_Error(
				'woocommerce_rest_invalid_shift_id',
				__( 'The shift referenced by shift_id does not exist.', 'pinaka-pos' ),
				[ 'status' => 400 ]
			);
		}

		/* ------------------------------------
		* RULE 5: shift must be OPEN
		* ------------------------------------ */
		$shift_status = get_post_meta( $shift_id, '_shift_status', true );

		if ( $shift_status === 'closed' ) {
			return new WP_Error(
				'woocommerce_rest_invalid_shift_id',
				__( 'The shift is already closed. Orders cannot be created.', 'pinaka-pos' ),
				[ 'status' => 400 ]
			);
		}

		return true;
	}

	protected function get_pos_payments_from_request( $request ) {
		$meta_data = (array) $request->get_param( 'meta_data' );

		foreach ( $meta_data as $meta ) {
			if ( isset( $meta['key'] ) && $meta['key'] === '_pos_payments' && is_array( $meta['value'] ) ) {
				return $meta['value'];
			}
		}
		return [];
	}

	protected function get_completed_payment_total( array $payments, int $order_id, int $shift_id = 0 ): float {

		$total = 0;

		foreach ( $payments as $payment ) {

			// --- KEEP YOUR ORIGINAL INSERT LOGIC ---
			$post_id = wp_insert_post( array(
				'post_title'   => isset( $payment['title'] ) ? sanitize_text_field( $payment['title'] ) : 'Payment for Order ' . $order_id,
				'post_content' => '',
				'post_type'    => 'payments',
				'post_status'  => 'publish',
			) );

			update_post_meta( $post_id, '_payment_order_id', $order_id );
			update_post_meta( $post_id, '_payment_method', isset( $payment['method'] ) ? sanitize_text_field( $payment['method'] ) : 'unknown' );
			update_post_meta( $post_id, '_payment_amount', isset( $payment['amount'] ) ? floatval( $payment['amount'] ) : 0 );
			update_post_meta( $post_id, '_payment_status', isset( $payment['status'] ) ? sanitize_text_field( $payment['status'] ) : 'pending' );
			update_post_meta( $post_id, '_payment_shift_id', $shift_id );
			update_post_meta( $post_id, '_payment_user_id', get_current_user_id() );
			update_post_meta( $post_id, '_payment_datetime', isset( $payment['created_at'] ) ? sanitize_text_field( $payment['created_at'] ) : '' );
			update_post_meta( $post_id, '_payment_transaction_id', isset( $payment['transaction_id'] ) ? sanitize_text_field( $payment['transaction_id'] ) : '' );

			// --- KEEP YOUR SUM LOGIC ---
			if ( $payment['status'] === 'completed' || $payment['status'] === 'pending' ) {
				$total += (float) $payment['amount'];
			}
		}

		// ✅ NEW: Limit total to order total
		$order       = wc_get_order( $order_id );
		$order_total = (float) $order->get_total();

		$change = 0;

		if ( $total > $order_total ) {
			$change = $total - $order_total;
			$total  = $order_total; // Only apply order amount
		}

		// Save change separately (optional)
		if ( $change > 0 ) {
			update_post_meta( $post_id, '_pos_change_returned', wc_format_decimal( $change ) );
		}

		return wc_format_decimal( $total );
	}


	protected function determine_order_status_from_payments( WC_Order $order, array $payments, int $shift_id = 0 ) {
	
		$order_total   = (float) $order->get_total();
		$order_id 	= $order->get_id();
		$paid_amount   = (float) $this->get_completed_payment_total( $payments, $order_id, $shift_id );
		$has_sale_items   = false;
		$has_payout_items = false;

		$payout_product_id = (int) get_option( 'pinaka_payout_product_id', 0 );

		foreach ( $order->get_items( 'line_item' ) as $item ) {

			if (
				( $payout_product_id && $item->get_product_id() == $payout_product_id ) ||
				( (float) $item->get_total() < 0 )
			) {
				$has_payout_items = true;
				continue;
			}

			if ( (float) $item->get_total() > 0 ) {
				$has_sale_items = true;
			}
		}

		$is_payout_only = $has_payout_items && ! $has_sale_items;
		if($is_payout_only)
		{
			$order->payment_complete();
			$order->update_status(
				'completed',
				__( 'Payout-only order. No customer payment required.', 'pinaka-pos' )
			);
			$order->save();
			$paid_amount = $order_total;
			$logger  = wc_get_logger();
			$context = [
				'source'   => 'pinaka-payout-debug',
				'order_id' => $order->get_id(),
			];

			$logger->info(
				sprintf(
					'Payout-only order completed | Paid Amount: %s | Order Total: %s',
					wc_format_decimal( $paid_amount ),
					wc_format_decimal( $order_total )
				),
				$context
			);
			return $order->get_status();
		}

		return $this->determine_order_status_from_payments_test($payments);

	}

	private function determine_order_status_from_payments_test($pos_payments)
	{
		$has_pending = false;
		$has_voided  = false;

		foreach ($pos_payments as $payment) {

			if ($payment['status'] === 'completed') {
				return 'completed'; // Highest priority
			}

			if ($payment['status'] === 'pending') {
				$has_pending = true;
			}

			if ($payment['status'] === 'voided') {
				$has_voided = true;
			}
		}

		// If no completed found
		if ($has_pending) {
			return 'pending';
		}

		if ($has_voided) {
			return 'voided';
		}

		return 'pending'; // default fallback
	}

	function pinaka_create_invoice( WP_REST_Request $request ) {
		$logger      = wc_get_logger();
		$log_context = array( 'source' => 'pinaka-invoice' );
		// Get full request JSON
		$request_data = $request->get_json_params();
		// Log request + token
		$logger->info(
			'Logger → request data: ' . print_r( $request_data, true ) .
			' | Token: ' . $token,
			$log_context
		);
		
		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Invoice creation endpoint hit successfully.',
			),
			200
		 );
	}

}	
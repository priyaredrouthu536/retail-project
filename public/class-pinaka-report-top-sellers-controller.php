<?php
/**
 * REST API Reports controller
 *
 * Handles requests to the reports/top_sellers endpoint.
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
class Pinaka_Report_Top_Sellers_Controller extends WC_REST_Report_Top_Sellers_Controller {

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
	protected $rest_base = 'reports/top_sellers';


	/**
	 * Register the routes for sales reports.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/reports/orders/payments',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'report_order_payments_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/reports/orders/totals',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'report_order_totals_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/reports/purchases/totals',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'report_purchases_totals_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/reports/sales',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'report_sales_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/reports/employee-sales',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'report_employee_sales_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/reports/item-sales',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'report_item_sales_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/reports/inventory',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'report_inventory_callback' ),
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
	 * Get report_order_totals callback.
	 *
	 * @param WP_REST_Request $request .
	 * @return array|WP_Error
	 */
	public function report_order_payments_callback( WP_REST_Request $request ) {
		$params   = $request;
		$min_date = isset( $params['date_min'] ) ? sanitize_text_field( $params['date_min'] ) : '';
		$max_date = isset( $params['date_max'] ) ? sanitize_text_field( $params['date_max'] ) : '';

		global $wpdb;

		$custom_where = '';
		if ( null !== $request->get_param( 'from_pos' ) && ! current_user_can( 'manage_options' ) ) {
			$id           = get_current_user_id();
			$custom_where = "And post_author = $id";
		}

		// $results = (array) $wpdb->get_results(
		// $wpdb->prepare(
		// "SELECT
		// post.ID as post_id
		// FROM
		// {$wpdb->posts} AS post
		// INNER JOIN
		// {$wpdb->postmeta} AS meta ON post.ID = meta.post_id
		// WHERE post_type = %s AND DATE(post_date_gmt) BETWEEN %s AND %s {$custom_where}",
		// 'shop_order',
		// gmdate( 'Y-m-d', strtotime( $min_date ) ),
		// gmdate( 'Y-m-d', strtotime( $max_date ) ),
		// ),
		// ARRAY_A
		// );

		$order_status = array( 'completed' );
		$results      = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm.meta_value  as payment_method, pm2.meta_value as total, 
                pm3.meta_value as remaining,
                p.ID as order_id FROM {$wpdb->posts} p
			LEFT JOIN $wpdb->postmeta pm ON p . ID                     = pm . post_id
			And pm . meta_key                                        = %s
		
			LEFT JOIN $wpdb->postmeta pm2 ON p . ID                     = pm2 . post_id
			And pm2 . meta_key                                        = %s
		
			LEFT JOIN $wpdb->postmeta pm3 ON p . ID                     = pm3 . post_id
			And pm3 . meta_key                                        = %s
		
			where p . post_type                      = %s
			{$custom_where}
			and p . post_status   IN( 'wc-" . implode( "', 'wc-", $order_status ) . "' )
			and p . post_date BETWEEN %s and %s ",
				'_payment_method',
				'_order_total',
				'pos_remaining_amount',
				'shop_order',
				gmdate( 'Y-m-d', strtotime( $min_date ) ),
				gmdate( 'Y-m-d', ( strtotime( $max_date ) + 86400 ) ),
			),
			ARRAY_A
		);

		return new WP_REST_Response(
			$results,
			200
		);
	}


	/**
	 * Get report_order_totals callback.
	 *
	 * @param WP_REST_Request $request .
	 * @return array|WP_Error
	 */
	public function report_order_totals_callback( WP_REST_Request $request ) {
		$params   = $request;
		$min_date = isset( $params['date_min'] ) ? sanitize_text_field( $params['date_min'] ) : '';
		$max_date = isset( $params['date_max'] ) ? sanitize_text_field( $params['date_max'] ) : '';

		global $wpdb;

		$custom_where = '';
		if ( null !== $request->get_param( 'from_pos' ) && ! current_user_can( 'manage_options' ) ) {
			$id           = get_current_user_id();
			$custom_where = "And post_author = $id";
		}

		$results = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts}
        WHERE post_type = %s AND DATE(post_date_gmt) BETWEEN %s AND %s {$custom_where} GROUP BY post_status",
				'shop_order',
				gmdate( 'Y-m-d', strtotime( $min_date ) ),
				gmdate( 'Y-m-d', strtotime( $max_date ) ),
			),
			ARRAY_A
		);

		$data = array();
		foreach ( $results as $row ) {
			$totals[ $row['post_status'] ] = $row['num_posts'];
		}
		$status = wc_get_order_statuses();

		foreach ( $status as $slug => $name ) {
			$total = 0;
			if ( isset( $totals[ $slug ] ) ) {
				$total = (int) $totals[ $slug ];
			}

			$data[] = array(
				'slug'  => esc_attr( str_replace( 'wc-', '', $slug ) ),
				'name'  => esc_html( $name ),
				'total' => $total,
			);
		}

		return new WP_REST_Response(
			$data,
			200
		);
	}


	/**
	 * Get report_purchases_totals callback.
	 *
	 * @param WP_REST_Request $request .
	 * @return array|WP_Error
	 */
	public function report_purchases_totals_callback( WP_REST_Request $request ) {
		$params   = $request;
		$min_date = isset( $params['date_min'] ) ? sanitize_text_field( $params['date_min'] ) : '';
		$max_date = isset( $params['date_max'] ) ? sanitize_text_field( $params['date_max'] ) : '';

		global $wpdb;

		$custom_where = '';
		if ( null !== $request->get_param( 'from_pos' ) && ! current_user_can( 'manage_options' ) ) {
			$id           = get_current_user_id();
			$custom_where = "And post_author = $id";
		}

		$results = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts}
        WHERE post_type = %s AND DATE(post_date_gmt) BETWEEN %s AND %s {$custom_where} GROUP BY post_status",
				'shop_order',
				gmdate( 'Y-m-d', strtotime( $min_date ) ),
				gmdate( 'Y-m-d', strtotime( $max_date ) ),
			),
			ARRAY_A
		);

		$data = array();
		foreach ( $results as $row ) {
			$totals[ $row['post_status'] ] = $row['num_posts'];

		}
		$status = array(

			'wc-pending-purchase'  => _x( 'Pending Purchase', 'Order status', 'pinaka-pos' ),
			'wc-ordered-purchase'  => _x( 'Ordered Purchase', 'Order status', 'pinaka-pos' ),
			'wc-received-purchase' => _x( 'Received Purchase', 'Order status', 'pinaka-pos' ),
		);

		foreach ( $status as $slug => $name ) {
			$total = 0;
			if ( isset( $totals[ $slug ] ) ) {
				$total = (int) $totals[ $slug ];
			}

			$data[] = array(
				'slug'  => esc_attr( str_replace( 'wc-', '', $slug ) ),
				'name'  => esc_html( $name ),
				'total' => $total,
			);
		}

		return new WP_REST_Response(
			$data,
			200
		);
	}


	/**
	 * Get sales reports.
	 *
	 * @param WP_REST_Request $request .
	 * @return array|WP_Error
	 */
	public function get_items( $request ) {
		// Set date filtering.
		$filter = array(
			'period'   => $request['period'],
			'date_min' => $request['date_min'],
			'date_max' => $request['date_max'],
			'page'     => $request['page'],
			'per_page' => $request['per_page'],
		);
		$this->setup_report( $filter );

		$report_data = $this->report->get_order_report_data(
			array(
				'data'         => array(
					'_product_id'     => array(
						'type'            => 'order_item_meta',
						'order_item_type' => 'line_item',
						'function'        => '',
						'name'            => 'product_id',
					),
					'order_item_name' => array(
						'type'            => 'order_item',
						'order_item_type' => 'line_item',
						'function'        => '',
						'name'            => 'item_name',
					),
					'_variation_id'   => array(
						'type'            => 'order_item_meta',
						'order_item_type' => 'line_item',
						'function'        => '',
						'name'            => 'variation_id',
					),
					'_qty'            => array(
						'type'            => 'order_item_meta',
						'order_item_type' => 'line_item',
						'function'        => 'SUM',
						'name'            => 'order_item_qty',
					),
					'_line_total'     => array(
						'type'            => 'order_item_meta',
						'order_item_type' => 'line_item',
						'function'        => 'SUM',
						'name'            => 'line_total',
					),
				),
				'order_by'     => 'order_item_qty DESC',
				'group_by'     => 'item_name',
				'limit'        => isset( $filter['per_page'] ) ? absint( $filter['per_page'] ) : 12,
				'query_type'   => 'get_results',
				'filter_range' => true,
			)
		);

		$top_sellers = array();

		foreach ( $report_data as $item ) {
			$product = wc_get_product( $item->product_id );

			$image_str = '';

			if ( $product ) {
				$image = wp_get_attachment_image_src( $product->get_image_id(), 'thumbnail' );
				if ( is_array( $image ) ) {
					$image_str = $image[0];
				}
				$top_sellers[] = array(
					'name'         => $item->item_name,
					'image'        => $image_str,
					'product_id'   => (int) $item->product_id,
					'variation_id' => (int) $item->variation_id,
					'line_total'   => $item->line_total,
					'quantity'     => wc_stock_amount( $item->order_item_qty ),
				);
			}
		}

		$data = array();
		foreach ( $top_sellers as $top_seller ) {
			$item   = $this->prepare_item_for_response( (object) $top_seller, $request );
			$data[] = $this->prepare_response_for_collection( $item );
		}
		return rest_ensure_response( $data );
	}
	/**
	 * Prepare a report sales object for serialization.
	 *
	 * @param stdClass        $top_seller .
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $top_seller, $request ) {

		$data = array(
			'name'         => esc_html( $top_seller->name ),
			'image'        => esc_html( $top_seller->image ),
			'product_id'   => esc_html( $top_seller->product_id ),
			'variation_id' => esc_html( $top_seller->variation_id ),
			'line_total'   => esc_html( $top_seller->line_total ),
			'quantity'     => esc_html( $top_seller->quantity ),
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );
		$response->add_links(
			array(
				'about'   => array(
					'href' => rest_url( sprintf( '%s/reports', $this->namespace ) ),
				),
				'product' => array(
					'href' => rest_url( sprintf( '/%s/products/%s', $this->namespace, $top_seller->product_id ) ),
				),
			)
		);

		/**
		 * Filter a report top sellers returned from the API.
		 *
		 * Allows modification of the report top sellers data right before it is returned.
		 *
		 * @param WP_REST_Response $response   The response object.
		 * @param stdClass         $top_seller The original report object.
		 * @param WP_REST_Request  $request    Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_report_top_sellers', $response, $top_seller, $request );
	}

	public function report_sales_callback( WP_REST_Request $request ) {
		global $wpdb;

		$date = sanitize_text_field( $request->get_param('date') );

		$start = $date . ' 00:00:00';
		$end   = $date . ' 23:59:59';

		// TOTAL SALES
		$total = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(pm.meta_value)
				FROM {$wpdb->posts} p
				JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE p.post_type = 'shop_order'
				AND p.post_status = 'wc-completed'
				AND pm.meta_key = '_order_total'
				AND p.post_date BETWEEN %s AND %s",
				$start,
				$end
			)
		);

		// HOURLY SALES
		$hourly = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT HOUR(p.post_date) as hour,
						SUM(pm.meta_value) as total
				FROM {$wpdb->posts} p
				JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE p.post_type = 'shop_order'
				AND p.post_status = 'wc-completed'
				AND pm.meta_key = '_order_total'
				AND p.post_date BETWEEN %s AND %s
				GROUP BY hour
				ORDER BY hour ASC",
				$start,
				$end
			),
			ARRAY_A
		);

		return rest_ensure_response(array(
			'total_sales' => floatval($total),
			'hourly'      => $hourly
		));
	}


	public function report_employee_sales_callback( WP_REST_Request $request ) {
		global $wpdb;

		$date  = sanitize_text_field( $request->get_param('date') );
		$start = $date . ' 00:00:00';
		$end   = $date . ' 23:59:59';

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT emp.meta_value as employee_id,
						SUM(total.meta_value) as total
				FROM {$wpdb->posts} p
				JOIN {$wpdb->postmeta} emp ON p.ID = emp.post_id
				JOIN {$wpdb->postmeta} total ON p.ID = total.post_id
				WHERE p.post_type = 'shop_order'
				AND p.post_status = 'wc-completed'
				AND emp.meta_key = '_wc_order_created_by'
				AND total.meta_key = '_order_total'
				AND p.post_date BETWEEN %s AND %s
				GROUP BY emp.meta_value",
				$start,
				$end
			),
			ARRAY_A
		);

		$data = [];
		foreach ( $results as $row ) {
			$user = get_userdata( $row['employee_id'] );
			$data[] = array(
				'employee' => $user ? $user->display_name : 'Unknown',
				'total'    => floatval( $row['total'] )
			);
		}

		return rest_ensure_response( $data );
	}

	public function report_item_sales_callback( WP_REST_Request $request ) {
		global $wpdb;

		$date  = sanitize_text_field( $request->get_param('date') );
		$start = $date . ' 00:00:00';
		$end   = $date . ' 23:59:59';

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT oi.order_item_name as product,
						SUM(oim.meta_value) as qty
				FROM {$wpdb->prefix}woocommerce_order_items oi
				JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim
					ON oi.order_item_id = oim.order_item_id
				JOIN {$wpdb->posts} p ON oi.order_id = p.ID
				WHERE p.post_status = 'wc-completed'
				AND oim.meta_key = '_qty'
				AND p.post_date BETWEEN %s AND %s
				GROUP BY oi.order_item_name",
				$start,
				$end
			),
			ARRAY_A
		);

		return rest_ensure_response( $results );
	}



	public function report_inventory_callback( WP_REST_Request $request ) {
		global $wpdb;

		$date = sanitize_text_field( $request->get_param('date') );

		if ( empty( $date ) ) {
			return new WP_Error(
				'invalid_date',
				'Date parameter is required',
				array( 'status' => 400 )
			);
		}

		$page     = max( 1, absint( $request->get_param('page') ) );
		$per_page = min( 200, max( 1, absint( $request->get_param('per_page') ?: 50 ) ) );
		$offset   = ( $page - 1 ) * $per_page;

		$start = $date . ' 00:00:00';
		$end   = $date . ' 23:59:59';

		/* -----------------------------
		* TOTAL PRODUCT COUNT
		* ----------------------------- */
		$total_products = (int) $wpdb->get_var(
			"SELECT COUNT(ID)
			FROM {$wpdb->posts}
			WHERE post_type = 'product'
			AND post_status = 'publish'"
		);

		/* -----------------------------
		* FETCH PRODUCTS (LIMITED)
		* ----------------------------- */
		$products = wc_get_products( array(
			'limit'  => $per_page,
			'offset'=> $offset,
			'status' => 'publish',
		) );

		$data = [];

		foreach ( $products as $product ) {

			$product_id = $product->get_id();

			/* OPENING STOCK */
			$opening = get_post_meta(
				$product_id,
				'_opening_stock_' . str_replace('-', '', $date),
				true
			);

			if ( $opening === '' ) {
				$opening = $product->get_stock_quantity();
			}

			$opening = intval( $opening );

			/* SOLD QTY */
			$sold = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT SUM(oim.meta_value)
					FROM {$wpdb->prefix}woocommerce_order_items oi
					JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim
					ON oi.order_item_id = oim.order_item_id
					JOIN {$wpdb->posts} p ON oi.order_id = p.ID
					WHERE p.post_status = 'wc-completed'
					AND oim.meta_key = '_qty'
					AND oi.order_item_type = 'line_item'
					AND p.post_date BETWEEN %s AND %s
					AND oim.order_item_id IN (
						SELECT order_item_id
						FROM {$wpdb->prefix}woocommerce_order_itemmeta
						WHERE meta_key = '_product_id'
						AND meta_value = %d
					)",
					$start,
					$end,
					$product_id
				)
			);

			$data[] = array(
				'product_id' => $product_id,
				'product'    => $product->get_name(),
				'opening'    => $opening,
				'sold'       => $sold,
				'remaining'  => max( 0, $opening - $sold ),
			);
		}

		return rest_ensure_response( array(
			'page'       => $page,
			'per_page'   => $per_page,
			'total'      => $total_products,
			'has_more'   => ( $offset + $per_page ) < $total_products,
			'items'      => $data,
		) );
	}

	

}

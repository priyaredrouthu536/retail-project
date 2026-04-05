<?php
/**
 * REST API Modify Controller
 *
 * Handles requests to the deleted/*.
 *
 * @link       https://www.PinakaPOS.com/
 * @since      1.0.0
 *
 * @package    Pinaka_Pos
 * @subpackage Pinaka_Pos/public
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Modify query for modify date controller class.
 *
 * @package WooCommerce\RestApi
 */
class Pinaka_Modify_Query_Controller {

	/**
	 * Filter arguments, before passing to WP_User_Query, when querying users via the REST API.
	 * for customer/tags/category etc .
	 *
	 * @see https://developer.wordpress.org/reference/classes/wp_user_query/
	 *
	 * @param array           $prepared_args Array of arguments for WP_User_Query.
	 * @param WP_REST_Request $request       The current request.
	 */
	public function add_modified_after_filter_to_meta( $prepared_args, $request ) {
		if ( $request->get_param( 'modified_after' ) ) {
			$prepared_args['meta_query'] = array(
				array(
					'key'     => 'last_update',
					'value'   => (int) strtotime( $request->get_param( 'modified_after' ) ),
					'compare' => '>=',
				),
			);
		}

		return $prepared_args;
	}

	/**
	 * Modify shop order schema for float quanity,
	 *
	 * @param array $properties  shema.
	 */
	public function rest_shop_order_schema( $properties ) {
		if ( isset( $properties['line_items']['items']['properties']['quantity']['type'] ) ) {
			$properties['line_items']['items']['properties']['quantity']['type'] = 'float';
		}
		return $properties;
	}

	/**
	 * Modify product schema for float stock_quantity,
	 *
	 * @param array $properties  shema.
	 */
	public function rest_product_schema( $properties ) {
		if ( isset( $properties['stock_quantity'] ) ) {
			$properties['stock_quantity']['type'] = 'float';
		}
		return $properties;
	}

	/**
	 * Add filter for stock float value,
	 */
	public function woocommerce_stock_amount_filters() {
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', 'floatval' );
	}


	public function adjust_rest_order_total_with_payout( $response, $object, $request ) {
		$order = wc_get_order( $object->get_id() );

		$balance = (float) get_post_meta( $order->get_id(), '_payout_balance', true );
		if ( ! is_numeric( $balance ) ) {
			$balance = 0.0;
		}

		$new_total = (float) $order->get_total() + $balance;

		// overwrite total in REST response
		$data = $response->get_data();
		$data['total'] = $new_total;
		$response->set_data( $data );

		return $response;
	}

	/**
	 * Modify query for product name and sku
	 *
	 * @param string $where query.
	 */
	public function add_search_criteria_to_wp_query_where( $where ) {
		global $wpdb;
		if ( strpos( $where, "IN ('product', 'product_variation')" ) ) {

			$where = str_replace( "IN ('product', 'product_variation')", "IN ('product')", $where );
		}
		if ( strpos( $where, 'wc_product_meta_lookup.sku' ) !== false ) {
			if ( isset( $_GET['search_sku'] ) ) {
				$sku         = sanitize_text_field( wp_unslash( $_GET['search_sku'] ) );
				$like_search = '%' . $wpdb->esc_like( $sku ) . '%';
				$where      .= ' OR ' . $wpdb->prepare( '(' . $wpdb->posts . '.post_title LIKE %s)', $like_search );
				$where       = str_replace( ') OR (', ' OR ', $where );
			}
		}
		if ( current_user_can( 'manage_options' ) ) {
			if ( strpos( $where, "wp_posts.post_type = 'shop_order'" ) ) {
				// wp_posts.post_author IN (1)  AND
				$user_id = get_current_user_id();
				$where   = str_replace( "wp_posts.post_author IN ($user_id)  AND", '', $where );
			}
		}
		// echo $where;
		return $where;
	}

	/**
	 * Modify arguments for stock order by
	 *
	 * @param array $args arguments.
	 */
	public function stock_status_value_on_order_item_view( $args ) {
		if ( isset( $_GET['meta_key'] ) ) {
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = sanitize_text_field( wp_unslash( $_GET['meta_key'] ) );
		}
		return $args;
	}
	/**
	 * Add Custome Purchase Price on WooCommerce.
	 */
	public function add_custom_purchase_price_woocommerce() {
		woocommerce_wp_text_input(
			array(
				'id'        => 'purchase_price',
				'data_type' => 'price',
				'label'     => esc_html__( 'Purchase price', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')',
			)
		);
	}
	/**
	 * Save Custome Purchase Price on WooCommerce. .
	 *
	 * @param int $post_id      Post object.
	 */
	public function save_custom_purchase_price_woocommerce( $post_id ) {
		// Custom Product Text Field.
		$woocommerce_purchase_price = isset( $_POST['purchase_price'] ) ? sanitize_text_field( $_POST['purchase_price'] ) : '';
		if ( ! empty( $woocommerce_purchase_price ) ) {
			update_post_meta( $post_id, 'purchase_price', esc_attr( $woocommerce_purchase_price ) );
		}
	}

	/**
	 * Filter Customers, before passing to WP_User_Query, when querying users via the REST API.
	 * for customer/tags/category etc .
	 *
	 * @see https://developer.wordpress.org/reference/classes/wp_user_query/
	 *
	 * @param array           $prepared_args Array of arguments for WP_User_Query.
	 * @param WP_REST_Request $request       The current request.
	 */
	public function add_shop_order_filter_to_meta( $prepared_args, $request ) {
		$params = $request->get_params();

		if ( ! isset( $prepared_args['meta_query'] ) ) {
			$prepared_args['meta_query'] = array();
		}
		if ( ! empty( $params['show_un_paid_only'] ) && $params['show_un_paid_only'] == 'true' ) {

			$prepared_args['meta_query'][] = array(
				array(
					'key'     => 'pos_remaining_amount',
					'value'   => 0,
					'compare' => '<',
				),
			);

		}
		if ( ! empty( $params['payment_method'] ) ) {
			if ( $params['payment_method'] == 'upi' ) {
				$params['payment_method'] = 'qr-code';
			}
			$prepared_args['meta_query'][] = array(
				array(
					'key'   => '_payment_method',
					'value' => $params['payment_method'],
				),
			);
		}

		return $prepared_args;
	}

	/**
	 * Get Order Report data with perPage and page, before passing to WP_User_Query, when querying users via the REST API.
	 * for report/top_sellers etc .
	 *
	 * @see https://developer.wordpress.org/reference/classes/wp_user_query/
	 *
	 * @param array $query Array of arguments for WP_User_Query.
	 */
	public function add_custom_order_report_query( $query ) {

		if ( ! isset( $query['limit'] ) ) {
			return $query;
		}

		// Get the page number and per_page from the GET request
		$page_number = isset( $_GET['page'] ) ? intval( $_GET['page'] ) : 1; // Default to page 1 if not provided
		$per_page    = isset( $_GET['per_page'] ) ? intval( $_GET['per_page'] ) : 10; // Default per_page to 10 if not provided

		// Calculate the offset based on the page number and per_page
		$offset = ( $page_number - 1 ) * $per_page;

		// Set the offset in the query
		$query['offset'] = "OFFSET $offset";

		return $query;
	}

	/**
	 * Filter Customers, before passing to WP_User_Query, when querying users via the REST API.
	 * for customer/tags/category etc .
	 *
	 * @see https://developer.wordpress.org/reference/classes/wp_user_query/
	 *
	 * @param array           $prepared_args Array of arguments for WP_User_Query.
	 * @param WP_REST_Request $request       The current request.
	 */
	public function add_customer_filter_to_meta( $prepared_args, $request ) {
		$params = $request->get_params();

		if ( ! empty( $params['modified_after'] ) ) {
			$prepared_args['meta_query'] = array(
				array(
					'key'     => 'last_update',
					'value'   => (int) strtotime( sanitize_text_field( $request->get_param( 'modified_after' ) ) ),
					'compare' => '>=',
				),
			);
		}
		if ( ! empty( $params['meta_key'] ) && null !== $params['meta_key'] && null !== $params['meta_value'] ) {
			$prepared_args['meta_query'] = array(
				array(
					'key'   => sanitize_text_field( $request->get_param( 'meta_key' ) ),
					'value' => sanitize_text_field( $request->get_param( 'meta_value' ) ),
				),
			);
		}
		if ( ! empty( $params['meta_order'] ) && null !== $params['order'] && null !== $params['meta_order'] ) {
			// Check if the value of the 'meta_key' parameter is 'due_date'.
			if ( 'balance_total_due' === $params['meta_order'] || 'balance_due_date' == $params['meta_order'] ) {
				$prepared_args['meta_key'] = sanitize_text_field( $params['meta_order'] );
				$prepared_args['orderby']  = 'meta_value_num';
				$prepared_args['order']    = sanitize_text_field( $params['order'] );
			}
		}

		if ( ! empty( $params['search'] ) && strpos( $params['search'], '@' ) === false ) {
			$first_name = '';
			$last_name  = '';
			$phone      = '';
			if ( strpos( $params['search'], ' ' ) !== false ) {
				$name_parts                  = explode( ' ', $params['search'] );
				$first_name                  = $name_parts[0];
				$last_name                   = $name_parts[1];
				$prepared_args['meta_query'] = array(
					'relation' => 'AND',
					array(
						'key'     => 'first_name',
						'value'   => sanitize_text_field( $first_name ),
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'last_name',
						'value'   => sanitize_text_field( $last_name ),
						'compare' => 'LIKE',
					),
				);
			} else {
				$first_name                  = $params['search'];
				$last_name                   = $params['search'];
				$prepared_args['meta_query'] = array(
					'relation' => 'OR',
					array(
						'key'     => 'first_name',
						'value'   => sanitize_text_field( $first_name ),
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'last_name',
						'value'   => sanitize_text_field( $last_name ),
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'billing_email',
						'value'   => sanitize_text_field( $first_name ),
						'compare' => 'LIKE',
					),
				);
			}
			if ( is_numeric( $params['search'] ) ) {
				$phone                       = $params['search'];
				$prepared_args['meta_query'] = array(
					'relation' => 'AND',
					array(
						'key'     => 'billing_phone',
						'value'   => sanitize_text_field( $phone ),
						'compare' => 'LIKE',
					),
				);
			}

				unset( $prepared_args['search'] );

		} else {

				$d                       = $prepared_args['search'];
				$prepared_args['search'] = str_replace( '@', '', $d );

		}
		return $prepared_args;
	}

	/**
	 * Filter arguments, before passing to WP_User_Query, when querying users via the REST API.
	 * for customer .
	 *
	 * @see https://developer.wordpress.org/reference/classes/wp_user_query/
	 *
	 * @param int $term_id int of term id .
	 */
	public function add_modified_date_terms_meta( $term_id ) {
		update_term_meta( $term_id, 'last_update', time() );
	}


	/**
	 * Filter arguments, before passing to WP_User_Query, when querying users via the REST API.
	 * for customer .
	 *
	 * @see https://developer.wordpress.org/reference/classes/wp_user_query/
	 *
	 * @param int        $count int of order count .
	 * @param WooCutomer $customer .
	 */
	public function custom_customer_get_order_count( $count, $customer ) {
		if ( $customer->role != 'shop_manager' ) {
			return $count;
		}
		global $wpdb;

		$count = $wpdb->get_var(
            // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			"SELECT COUNT(*)
				FROM $wpdb->posts as posts
				WHERE
				   posts.post_type = 'shop_order'
				AND     posts.post_author = '" . esc_sql( $customer->get_id() ) . "'
				AND     posts.post_status IN ( '" . implode( "','", array_map( 'esc_sql', array_keys( wc_get_order_statuses() ) ) ) . "' )
				"
            // phpcs:enable
		);
		return absint( $count );
	}
	/**
	 * Calculate purchase price while updateing an order .
	 *
	 * @see https://developer.wordpress.org/reference/classes/wp_user_query/
	 *
	 * @param int $order_id .
	 */
	public function calculate_profit_order( $order ) {

		$order_id       = $order->get_id();
		$total_purchase = $this->calculate_profit( $order_id );
		update_post_meta( $order_id, 'mp_order_total_purchase', $total_purchase );
	}
	/**
	 * Calculate purchase price while creating a refund .
	 *
	 * @see https://developer.wordpress.org/reference/classes/wp_user_query/
	 *
	 * @param int   $refund_id .
	 * @param array $args .
	 */
	public function new_refund_created( $refund_id, $args ) {
		$total = $this->calculate_profit( $refund_id );
		update_post_meta( $refund_id, 'mp_order_total_purchase', '-' . $total, );
	}
	/**
	 * Calcuation of profit from order id  .
	 *
	 * @see https://developer.wordpress.org/reference/classes/wp_user_query/
	 *
	 * @param int $order_id .
	 * @return float $total_purchase_price .
	 */
	public function calculate_profit( $order_id ) {
		$total = 0.0;
		global $wpdb;
		$total_purchase = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT SUM(
                    CASE WHEN (pm.meta_value = '' OR pm.meta_value = 0 )  THEN im3.meta_value ELSE pm.meta_value * im2.meta_value
                END
                ) AS mp_order_total_purchase
                FROM {$wpdb->prefix}woocommerce_order_items i
                RIGHT JOIN {$wpdb->prefix}woocommerce_order_itemmeta im ON im.order_item_id = i.order_item_id AND im.meta_key = '_product_id'
                RIGHT JOIN {$wpdb->prefix}woocommerce_order_itemmeta im4 ON im4.order_item_id = i.order_item_id AND im4.meta_key = '_variation_id'
                RIGHT JOIN {$wpdb->prefix}woocommerce_order_itemmeta im2 ON im2.order_item_id = i.order_item_id AND im2.meta_key = '_qty'
                RIGHT JOIN {$wpdb->prefix}woocommerce_order_itemmeta im3 ON im3.order_item_id = i.order_item_id AND im3.meta_key = '_line_subtotal'
                RIGHT JOIN {$wpdb->prefix}postmeta pm ON pm.post_id = (CASE WHEN im4.meta_value = 0 THEN im.meta_value ELSE im4.meta_value END) AND pm.meta_key = 'purchase_price'
                WHERE i.order_id = %d AND i.order_item_type = 'line_item'",
				$order_id,
			),
			// $wpdb->prepare(
			// "SELECT  SUM(  CASE
			// WHEN pm.meta_value = 0 THEN im3.meta_value
			// ELSE pm.meta_value * im2.meta_value
			// END)
			// as mp_order_total_purchase
			// FROM
			// {$wpdb->prefix}woocommerce_order_items i
			// Right JOIN {$wpdb->prefix}woocommerce_order_itemmeta im ON
			// im.order_item_id = i.order_item_id
			// Right JOIN {$wpdb->prefix}woocommerce_order_itemmeta im2 ON
			// im2.order_item_id = i.order_item_id AND im2.meta_key = '_qty'
			// Right JOIN {$wpdb->prefix}woocommerce_order_itemmeta im3 ON
			// im3.order_item_id = i.order_item_id AND im3.meta_key = '_line_subtotal'
			// Right JOIN {$wpdb->prefix}posts p ON
			// (
			// im.meta_key = '_product_id' OR im.meta_key = '_variation_id'
			// ) AND p.ID = im.meta_value
			// Right JOIN {$wpdb->prefix}postmeta pm ON
			// pm.post_id = p.ID AND pm.meta_key = 'purchase_price'
			// WHERE
			// i.order_id = %s AND(
			// im.meta_key = '_product_id' OR im.meta_key = '_variation_id'
			// )AND i.order_item_type ='line_item'",
			// $order_id,
			// ),
		);

		$total_fee = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
    SUM(im3.meta_value) as total_fee
FROM
{$wpdb->prefix}woocommerce_order_items i
LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta im3 ON
    im3.order_item_id = i.order_item_id AND im3.meta_key = '_line_total' AND im3.meta_value >0 
WHERE
    i.order_id = %s AND i.order_item_type = 'fee'",
				$order_id,
			),
		);

		$total_tax = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
  SUM(im3.meta_value) as total_tax
FROM
{$wpdb->prefix}woocommerce_order_items i
Left JOIN {$wpdb->prefix}woocommerce_order_itemmeta im3 ON
    im3.order_item_id = i.order_item_id AND im3.meta_key = 'tax_amount' AND im3.meta_value >0 
WHERE
    i.order_id = %s AND i.order_item_type = 'tax'",
				$order_id,
			),
		);

		if ( ! empty( $total_purchase ) ) {
			$total = $total_purchase[0]->mp_order_total_purchase;
		}
		if ( ! empty( $total_fee ) ) {
			$total = $total + $total_fee[0]->total_fee;
		}
		if ( ! empty( $total_tax ) ) {
			$total = $total + $total_tax[0]->total_tax;
		}

		// $total = number_format( $total, 2 );
		return $total;
	}

	/**
	 * Filter arguments, before passing to WP_User_Query, when querying users via the REST API.
	 * for customer .
	 *
	 * @see https://developer.wordpress.org/reference/classes/wp_user_query/
	 *
	 * @param int        $count int of total amount .
	 * @param WooCutomer $customer .
	 */
	public function custom_customer_get_total_spent( $count, $customer ) {
		if ( $customer->role != 'shop_manager' ) {
			return $count;
		}
		global $wpdb;

		$statuses = array_map( 'esc_sql', wc_get_is_paid_statuses() );
		$spent    = $wpdb->get_var(
            // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			apply_filters(
				'woocommerce_customer_get_total_spent_query',
				"SELECT SUM( meta2 . meta_value )
					FROM $wpdb->posts as posts
					LEFT JOIN {$wpdb->postmeta} as meta2 ON posts . ID = meta2 . post_id
					WHERE   posts . post_author                        = '" . esc_sql( $customer->get_id() ) . "'
					and posts . post_type                              = 'shop_order'
					and posts . post_status   IN( 'wc-" . implode( "', 'wc-", $statuses ) . "' )
					and meta2 . meta_key                               = '_order_total'",
				$customer
			)
            // phpcs:enable
		);

		if ( ! $spent ) {
			$spent = 0;
		}

		return wc_format_decimal( $spent, 2 );
	}

	/**
	 * After updating an attribute update mp_last_update column .
	 *
	 * @param int    $id       Attribute ID.
	 * @param string $attribute_name     Attribute name.
	 * @param string $old_attribute_name Old attribute name.
	 */
	public function update_last_update_column( $id, $attribute_name, $old_attribute_name ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'woocommerce_attribute_taxonomies';

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$table_name}` SET `mp_last_update`           = current_timestamp() WHERE attribute_id = %s",
				$id,
			),
		);
	}


	/**
	 * Filter arguments, before passing to post query, when querying users via the REST API.
	 * for customer .
	 *
	 * @param array           $prepared_args Array of arguments for WP_User_Query.
	 * @param WP_REST_Request $request       The current request.
	 */
	public function add_modified_after_filter_to_post( $prepared_args, $request ) {
		if ( null !== sanitize_text_field( $request->get_param( 'from_pos' ) ) ) {
			$prepared_args['author'] = absint( get_current_user_id() );
		}

		return $prepared_args;
	}


	/**
	 * Filter arguments, before passing to post query, when querying users via the REST API.
	 * for customer .
	 *
	 * @param string $min_date will get it from endpoints.
	 * @param string $max_date will get it from endpoints.
	 */
	public function calculate_total_purchase( $min_date, $max_date ) {
		$total = 0.0;
		global $wpdb;

		$total_purchase = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT SUM(
                    CASE WHEN (pm.meta_value = '' OR pm.meta_value = 0 )  THEN im3.meta_value ELSE pm.meta_value * im2.meta_value
                END
                ) AS mp_order_total_purchase
                FROM {$wpdb->prefix}posts p
                RIGHT JOIN {$wpdb->prefix}woocommerce_order_items i ON i.order_id = p.ID
                RIGHT JOIN {$wpdb->prefix}woocommerce_order_itemmeta im ON im.order_item_id = i.order_item_id AND im.meta_key = '_product_id'
                RIGHT JOIN {$wpdb->prefix}woocommerce_order_itemmeta im4 ON im4.order_item_id = i.order_item_id AND im4.meta_key = '_variation_id'
                RIGHT JOIN {$wpdb->prefix}woocommerce_order_itemmeta im2 ON im2.order_item_id = i.order_item_id AND im2.meta_key = '_qty'
                RIGHT JOIN {$wpdb->prefix}woocommerce_order_itemmeta im3 ON im3.order_item_id = i.order_item_id AND im3.meta_key = '_line_subtotal'
                RIGHT JOIN {$wpdb->prefix}postmeta pm ON pm.post_id = (CASE WHEN im4.meta_value = 0 THEN im.meta_value ELSE im4.meta_value END) AND pm.meta_key = 'purchase_price'
                WHERE i.order_item_type = 'line_item' and (p.post_date BETWEEN %s and %s ) and p.post_status = %s  group by p.post_status",
				gmdate( 'Y-m-d', strtotime( $min_date ) ),
				gmdate( 'Y-m-d', ( strtotime( $max_date ) ) ),
				'wc-completed'
			),
		);

		$total_fee = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
    SUM(im3.meta_value) as total_fee
    FROM {$wpdb->prefix}posts p
                RIGHT JOIN {$wpdb->prefix}woocommerce_order_items i ON i.order_id = p.ID
LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta im3 ON
    im3.order_item_id = i.order_item_id AND im3.meta_key = '_line_total' AND im3.meta_value >0 
WHERE
     i.order_item_type = 'fee' and( p . post_date BETWEEN %s and %s ) and p.post_status = %s  group by p.post_status",
				gmdate( 'Y-m-d', strtotime( $min_date ) ),
				gmdate( 'Y-m-d', ( strtotime( $max_date ) ) ),
				'wc-completed'
			),
		);

		$total_tax = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
  SUM(im3.meta_value) as total_tax
  FROM {$wpdb->prefix}posts p
                RIGHT JOIN {$wpdb->prefix}woocommerce_order_items i ON i.order_id = p.ID
Left JOIN {$wpdb->prefix}woocommerce_order_itemmeta im3 ON
    im3.order_item_id = i.order_item_id AND im3.meta_key = 'tax_amount' AND im3.meta_value >0 
    
WHERE
     i.order_item_type = 'tax' and (p . post_date BETWEEN %s and %s)  and p.post_status = %s  group by p.post_status",
				gmdate( 'Y-m-d', strtotime( $min_date ) ),
				gmdate( 'Y-m-d', ( strtotime( $max_date ) ) ),
				'wc-completed'
			),
		);

		if ( ! empty( $total_purchase ) ) {
			$total = $total_purchase[0]->mp_order_total_purchase;

		}
		if ( ! empty( $total_fee ) ) {
			$total = $total + $total_fee[0]->total_fee;

		}
		if ( ! empty( $total_tax ) ) {
			$total = $total + $total_tax[0]->total_tax;

		}
		return $total;
	}

	/**
	 * Add payment data to report sale .
	 *
	 * @param WP_REST_Request $response .
	 * @param array           $sales_data .
	 * @param array           $request .
	 * @return array|WP_Error
	 */
	public function get_custom_wc_report_sale( $response, $sales_data, $request ) {
		$params   = $request;
		$min_date = $params['date_min'];
		$max_date = $params['date_max'];

		$custom_where = '';
		if ( null !== $request->get_param( 'from_pos' ) && ! current_user_can( 'manage_options' ) ) {
			$id           = get_current_user_id();
			$custom_where = " and p . post_author                      = $id";
		}

		global $wpdb;
		$order_status = array( 'completed' );
		$results      = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm . meta_value, SUM( pm2 . meta_value ) as total FROM {$wpdb->posts} p
			LEFT JOIN $wpdb->postmeta pm ON p . ID                     = pm . post_id
			LEFT JOIN $wpdb->postmeta pm2 ON pm . post_id              = pm2 . post_id
            LEFT JOIN $wpdb->postmeta AS pm3
        ON 
            p.ID = pm3.post_id AND pm3.meta_key = 'pos_remaining_amount'
			WHERE pm . meta_key                                        = %s
			and pm2 . meta_key                     = %s
			and p . post_type                      = %s
            AND COALESCE(pm3.meta_value, 0) >= 0
			{$custom_where}
			and p . post_status   IN( 'wc-" . implode( "', 'wc-", $order_status ) . "' )
			and p . post_date BETWEEN %s and %s GROUP BY pm . meta_value",
				'_payment_method',
				'_order_total',
				'shop_order',
				gmdate( 'Y-m-d', strtotime( $min_date ) ),
				gmdate( 'Y-m-d', ( strtotime( $max_date ) + 86400 ) ),
			),
			ARRAY_A
		);

		// $total_purchase = (array) $wpdb->get_results(
		// $wpdb->prepare(
		// "SELECT  SUM( pm . meta_value ) as total_profit FROM {$wpdb->posts} p
		// LEFT JOIN $wpdb->postmeta pm ON p . ID = pm . post_id
		// WHERE pm . meta_key                    = %s
		// and p . post_status                    = %s
		// {$custom_where}
		// and p . post_date BETWEEN %s and %s GROUP BY p . post_status",
		// 'mp_order_total_purchase',
		// 'wc-completed',
		// gmdate( 'Y-m-d', strtotime( $min_date ) ),
		// gmdate( 'Y-m-d', ( strtotime( $max_date ) + 86400 ) ),
		// ),
		// );

		$total_purchase         = $this->calculate_total_purchase(
			gmdate( 'Y-m-d', strtotime( $min_date ) ),
			gmdate( 'Y-m-d', ( strtotime( $max_date ) + 86400 ) )
		);
		$customer_balance_table = $wpdb->prefix . 'pp_customer_balance';
		$total_customer_balance = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT  SUM( pm.meta_value ) as balance FROM {$wpdb->prefix}posts p
                   JOIN {$wpdb->prefix}postmeta AS pm
        ON
            p.ID = pm.post_id AND pm.meta_key = 'pos_remaining_amount' AND pm.meta_value < 0
        
			WHERE (p.post_date BETWEEN %s and %s ) and p.post_status = %s ",
				gmdate( 'Y-m-d', strtotime( $min_date ) ),
				gmdate( 'Y-m-d', ( strtotime( $max_date ) + 86400 ) ),
				'wc-completed'
			),
		);

		$sales_data->payment_details = $results;
		if ( $total_purchase ) {
			$sales_data->total_purchase = '' . $total_purchase;
		} else {
			$sales_data->total_purchase = '0.0';
		}
		if ( $total_customer_balance ) {
			$sales_data->total_customer_balance = $total_customer_balance[0]->balance;
		} else {
			$sales_data->total_customer_balance = '0.0';
		}

		return $sales_data;
	}

	/**
	 * Add child count to category .
	 *
	 * @param WP_REST_Request $response .
	 * @param array           $category .
	 * @param array           $request .
	 * @return array|WP_Error
	 */
	public function get_custom_wc_cat_child_count( $response, $category, $request ) {
		global $wpdb;

		$count = $wpdb->get_var(
            // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			"SELECT COUNT(*)
				FROM $wpdb->term_taxonomy as posts
				WHERE
				   posts.taxonomy = 'product_cat'
				AND     posts.parent = '$category->term_id'"
            // phpcs:enable
		);

		$response->data['child_count'] = (int) $count;

		return $response;
	}


	/**
	 * Add Custom Variable Purchase Price on WooCommerce.
	 *
	 * @param WP_Post         $post      Post object.
	 * @param WP_REST_Request $request   Request object.
	 */

	public function add_variation_options_pricing( $loop, $variation_data, $variation ) {
		woocommerce_wp_text_input(
			array(
				'id'            => 'purchase_price[' . $loop . ']',
				'wrapper_class' => 'form-row form-row-first',
				'class'         => 'short wc_input_price',
				'label'         => esc_html__( 'Purchase price', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')',
				'data_type'     => 'price',
				'value'         => get_post_meta( $variation->ID, 'purchase_price', true ),
			)
		);
	}
	/**
	 * Save Custom Variation Purchase Price on WooCommerce. .
	 *
	 * @param WP_Post         $post      Post object.
	 * @param WP_REST_Request $request   Request object.
	 */
	public function save_variation_options_pricing( $variation_id, $i ) {
		// Custom Product Text Field
		$woocommerce_var_purchase_price = isset( $_POST['purchase_price'][ $i ] ) ? sanitize_text_field( $_POST['purchase_price'][ $i ] ) : '';

		if ( isset( $woocommerce_var_purchase_price ) ) {
			update_post_meta( $variation_id, 'purchase_price', esc_attr( $woocommerce_var_purchase_price ) );
		}
	}
	/**
	 * Add Custom Variation Purchase Price on WooCommerce. .
	 *
	 * @param WP_Post         $post      Post object.
	 * @param WP_REST_Request $request   Request object.
	 */

	public function add_custom_field_variation_data( $variations ) {
		$variations['purchase_price'] = '<div class="woocommerce_custom_field">Purchase Price: <span>' . get_post_meta( $variations['variation_id'], 'purchase_price', true ) . '</span></div>';
		return $variations;
	}
	/**
	 * Modify line items with product data.
	 *
	 * @param array $args .
	 *
	 * @return array|args
	 */

	/**
	 * Modify line items with product data and remove internal coupons.
	 *
	 * @param WP_REST_Response $response .
	 *
	 * @return WP_REST_Response
	 */
	public function custom_wc_rest_prepare_shop_order_object( $response ) {
		if ( empty( $response->data ) ) {
			return $response;
		}

		$api            = new WC_REST_Products_Controller();
		$api_var        = new WC_REST_Product_Variations_Controller();
		$req            = new WP_REST_Request( 'GET' );
		$line_items     = array();
		$customer_id    = isset( $response->data['customer_id'] ) ? $response->data['customer_id'] : '';
		$customer_gstin = get_user_meta( $customer_id, 'gstin_number', true );

		// Add GSTIN if available
		if ( ! empty( $customer_gstin ) ) {
			$response->data['customer_gstin'] = $customer_gstin;
		}

		$order_id = isset( $response->data['id'] ) ? $response->data['id'] : 0;

		if ( $order_id ) {
			$order = wc_get_order( $order_id );

			if ( $order ) {
				$response->data['discount_total'] = wc_format_decimal( $order->get_discount_total(), 2 );
				$response->data['discount_tax']   = wc_format_decimal( $order->get_discount_tax(), 2 );
			} else {
				$response->data['discount_total'] = '0.00';
				$response->data['discount_tax']   = '0.00';
			}
		} else {
			$response->data['discount_total'] = '0.00';
			$response->data['discount_tax']   = '0.00';
		}

 		// Rebuild line items with product + variation data
		if ( ! empty( $response->data['line_items'] ) ) {
			foreach ( $response->data['line_items'] as $item ) {
				$product_id   = $item['product_id'];
				$variation_id = $item['variation_id'];

				// Add variation data
				if ( ! empty( $variation_id ) ) {
					$req->set_query_params( array( 'id' => $variation_id ) );
					$res = $api_var->get_item( $req );
					$item['product_variation_data'] = is_wp_error( $res ) ? null : $res->get_data();
				}

				// Add product data
				$req2 = new WP_REST_Request( 'GET' );
				$req2->set_query_params( array( 'id' => $product_id ) );
				$res2 = $api->get_item( $req2 );
				$item['product_data'] = is_wp_error( $res2 ) ? null : $res2->get_data();

				$line_items[] = $item;
			}
			$response->data['line_items'] = $line_items;
		}
		return $response;
	}


	// function pinaka_apply_mix_and_match_discounts($cart) {
		
	// 	if (is_admin() && !defined('DOING_AJAX')) return;

	// 	// Avoid recursion
	// 	if (did_action('woocommerce_before_calculate_totals') >= 2) return;

	// 	$discounts = get_posts([
	// 		'post_type' => 'discounts',
	// 		'posts_per_page' => -1,
	// 		'post_status' => 'publish',
	// 	]);

	// 	foreach ($discounts as $discount) {
	// 		$required_product_ids = (array) get_post_meta($discount->ID, '_required_product_ids', true);
	// 		$target_product_ids   = (array) get_post_meta($discount->ID, '_product_ids', true);
	// 		$discount_type        = get_post_meta($discount->ID, '_discount_type', true);
	// 		$coupon_amount        = floatval(get_post_meta($discount->ID, '_coupon_amount', true));

	// 		if (empty($required_product_ids) || empty($target_product_ids) || !$coupon_amount) {
	// 			continue;
	// 		}

	// 		// Step 1: Count how many required products (e.g. pizzas) are in the cart
	// 		$required_qty = 0;
	// 		foreach ($cart->get_cart() as $cart_item) {
	// 			if (in_array($cart_item['product_id'], $required_product_ids)) {
	// 				$required_qty += $cart_item['quantity'];
	// 			}
	// 		}

	// 		// Step 2: If no required product, skip
	// 		if ($required_qty === 0) continue;

	// 		// Step 3: Loop through target products and apply discount to only up to $required_qty
	// 		foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
	// 			if (!in_array($cart_item['product_id'], $target_product_ids)) continue;

	// 			$original_price = $cart_item['data']->get_regular_price(); // get original price
	// 			$item_qty       = $cart_item['quantity'];
	// 			$discounted_qty = min($required_qty, $item_qty);
	// 			$full_price_qty = $item_qty - $discounted_qty;

	// 			// Calculate discounted price
	// 			if ($discount_type === 'percent') {
	// 				$discounted_price = $original_price * (1 - ($coupon_amount / 100));
	// 			} elseif ($discount_type === 'fixed_product') {
	// 				$discounted_price = max(0, $original_price - $coupon_amount);
	// 			} else {
	// 				continue;
	// 			}

	// 			// Calculate weighted average price for the mixed quantities
	// 			$new_price = (
	// 				($discounted_price * $discounted_qty) +
	// 				($original_price * $full_price_qty)
	// 			) / $item_qty;

	// 			$cart_item['data']->set_price(wc_format_decimal($new_price, wc_get_price_decimals()));
				
	// 			// Decrease required_qty so no over-discounting
	// 			$required_qty -= $discounted_qty;
	// 			if ($required_qty <= 0) break; // no more discounts available
	// 		}
	// 	}
	// }


	/**
	 * Modify report/sales with post_author .
	 *
	 * @param array $args .
	 *
	 * @return array|args
	 */
	public function get_wc_order_report_data_args( $args ) {
		if ( isset( $_GET['from_pos'] ) && ! current_user_can( 'manage_options' ) ) {
			$id    = get_current_user_id();
			$where = array(
				array(
					'key'      => 'posts.post_author',
					'value'    => $id,
					'operator' => '=',
				),
			);
			if ( array_key_exists( 'where', $args ) ) {
				$args['where'] = array_merge( $args['where'], $where );
			} else {
				$args['where'] = $where;
			}
		}

		return $args;
	}

	/**
	 * Modify Order post_author to user_id .
	 *
	 * @param WP_Post         $post      Post object.
	 * @param WP_REST_Request $request   Request object.
	 */
	public function add_post_author_to_order( $post, $request ) {
		$params = $request->get_params();
		if ( isset( $params['from_pos'] ) && ! current_user_can( 'manage_options' ) ) {
			$id = get_current_user_id();
			wp_update_post(
				array(
					'ID'          => $post->ID,
					'post_author' => $id,
				),
			);
		}
	}

	public function Plain_Text_Errors($result, $server, $request) {
		if ($result instanceof WP_REST_Response) {
			$data = $result->get_data();

			// Only run if message exists
			if (!empty($data['message']) && is_string($data['message'])) {
				// Remove HTML tags and decode entities
				$data['message'] = html_entity_decode( wp_strip_all_tags($data['message']) );
				$result->set_data($data);
			}
		}
		return $result;
	}
}

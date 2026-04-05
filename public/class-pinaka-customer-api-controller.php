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
class Pinaka_Customer_Api_Controller extends WC_REST_Controller {

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
	protected $rest_base = 'customer';


	/**
	 * Register the routes for sales reports.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/check-create',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'customer_check_create_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/accounts',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'customer_accounts_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/accounts',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'customer_accounts_list_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/points',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'customer_points_list_callback' ),
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
	 * Get check customer if exist return or create and return callback.
	 *
	 * @param WP_REST_Request $request .
	 * @return array|WP_Error
	 */
	public function customer_check_create_callback( WP_REST_Request $request ) {

		$contact    = ! empty( $request->get_param( 'contact' ) ) ? sanitize_text_field( $request->get_param( 'contact' ) ) : '';
		$name       = ! empty( $request->get_param( 'name' ) ) ? sanitize_text_field( $request->get_param( 'name' ) ) : '';
		$first_name = '';
		$last_name  = '';

		if ( ! empty( $name ) ) {
			if ( $name === trim( $name ) && str_contains( $name, ' ' ) !== false ) {
				$split_string = explode( ' ', $name );
				$first_name   = sanitize_text_field( $split_string[0] );
				$last_name    = sanitize_text_field( $split_string[1] );
			} else {
				$first_name = $name;
			}
		}

		$default_password = wp_generate_password();

		$user = get_user_by( 'login', $contact );

		if ( ! $user ) {
			$wc_customer = new WC_Customer();
			$wc_customer->set_username( $contact );
			$wc_customer->set_email( sanitize_email( $contact . '@PinakaPOS.com' ) );
			$wc_customer->set_billing_email( sanitize_email( $contact . '@PinakaPOS.com' ) );
			$wc_customer->set_password( $default_password );
			$wc_customer->set_first_name( $first_name );
			$wc_customer->set_last_name( $last_name );
			$wc_customer->set_display_name( $name );
			$wc_customer->set_billing_first_name( $first_name );
			$wc_customer->set_billing_last_name( $last_name );
			$wc_customer->set_billing_phone( $contact );
			$wc_customer->save();
			wp_update_user(
				array(
					'ID'            => $wc_customer->get_id(),
					'user_nicename' => sanitize_text_field( $name ),
				)
			);
		} else {
			$wc_customer = new WC_Customer( $user->ID );
		}

			$data = $wc_customer->get_data();
			return new WP_REST_Response(
				wp_parse_args( $data ),
				200
			);
	}


	/**
	 * Adds a new customer balance record to the database.
	 *
	 * @param int    $user_id The ID of the user associated with the balance record.
	 * @param int    $order_id The ID of the order associated with the balance record.
	 * @param string $due_date The due date of the balance record in 'YYYY-MM-DD' format.
	 * @param string $payment_method The payment method used for the balance record.
	 * @param string $remark Optional. A remark or note about the balance record.
	 * @param string $created_date The date and time the balance record was created in 'YYYY-MM-DD HH:MM:SS' format.
	 * @param string $created_date_gmt The GMT/UTC date and time the balance record was created in 'YYYY-MM-DD HH:MM:SS' format.
	 * @param float  $amount The amount of the balance record.
	 * @param float  $total The total amount of the order associated with the balance record.
	 * @param string $status The status of the balance record, e.g. 'pending', 'paid', 'overdue'.
	 */
	public function add_customer_balance( $user_id, $order_id, $due_date, $payment_method, $remark, $created_date, $created_date_gmt, $amount, $total, $status ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pp_customer_balance';

		$data = array(
			'user_id'          => $user_id,
			'order_id'         => $order_id,
			'due_date'         => $due_date,
			'payment_method'   => $payment_method,
			'remark'           => $remark,
			'created_date'     => $created_date,
			'created_date_gmt' => $created_date_gmt,
			'amount'           => $amount,
			'total'            => $total,
			'status'           => $status,
		);
		// Insert the data into the table.
		$wpdb->insert(
			$table_name,
			$data,
			array(
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%f',
				'%f',
				'%s',
			)
		);

		$total_billing = (int) get_user_meta( $user_id, 'total_billing_length' );
		if ( empty( $total_billing ) ) {
			$total_billing = 0;
		}
		++$total_billing;
		update_user_meta( $user_id, 'total_billing_length', $total_billing );

		if ( $total < 0 && ! empty( $due_date ) ) {
			update_user_meta( $user_id, 'balance_due_date', $due_date );
		} elseif ( $total >= 0 ) {
			update_user_meta( $user_id, 'balance_due_date', '' );
		}
		update_user_meta( $user_id, 'balance_total_due', $total );

		if ( $wpdb->insert_id ) {
			$data['id'] = $wpdb->insert_id;
			do_action( 'pinakapos_customer_billing_added', $data );
		}

		return $wpdb->insert_id;
	}



	/**
	 * Add cutomer credit and debit details in accounts
	 *
	 * @param WP_REST_Request $request .
	 * @return array|WP_Error
	 */
	public function customer_accounts_callback( WP_REST_Request $request ) {

		$customer_id    = ! empty( $request->get_param( 'customer_id' ) ) ? absint( $request->get_param( 'customer_id' ) ) : '';
		$payment_method = ! empty( $request->get_param( 'payment_method' ) ) ? sanitize_text_field( $request->get_param( 'payment_method' ) ) : '';
		$remark         = ! empty( $request->get_param( 'remark' ) ) ? sanitize_text_field( $request->get_param( 'remark' ) ) : '';
		$order_id       = ! empty( $request->get_param( 'order_id' ) ) ? absint( $request->get_param( 'order_id' ) ) : '';
		$amount         = ! empty( $request->get_param( 'amount' ) ) ? floatval( $request->get_param( 'amount' ) ) : '';
		$due_date       = ! empty( $request->get_param( 'due_date' ) ) ? sanitize_text_field( $request->get_param( 'due_date' ) ) : '';

		global $wpdb;
		$table_name      = $wpdb->prefix . 'mp_customer_balance';
		$total           = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT total FROM $table_name WHERE user_id = %d ORDER BY id DESC LIMIT 1",
				$customer_id
			)
		);
		$total_remaining = $amount;
		if ( $total_remaining > 0 ) {
			if ( ! empty( $order_id ) ) {
				// customer willing to pay due with an order id will check
				// if the order have remaining payment compaire with amount that
				// customer willing to pay.
				$result = $wpdb->get_results(
					"
         SELECT
                pm2.post_id,
                pm2.meta_value AS order_total
            FROM
                {$wpdb->prefix}postmeta AS pm
            JOIN {$wpdb->prefix}postmeta AS pm2
            ON
                pm.post_id = pm2.post_id AND pm2.meta_key = 'pos_remaining_amount' AND pm2.meta_value < 0
            WHERE
                pm.meta_value = {$customer_id} and pm.meta_key = '_customer_user' and pm.post_id = {$order_id}
            HAVING
                order_total < 0
            ORDER BY
                pm.post_id ASC
            Limit 1  
        "
				);
				if ( ! empty( $result ) ) {
					$for_insert_amount = $total_remaining;
					$total_remaining   = $result[0]->order_total + $total_remaining;
					if ( $total_remaining >= 0 ) {
						$remaining_order_total = 0;
                        $for_insert_amount = (-1*$result[0]->order_total);
					} else {
						$remaining_order_total = $total_remaining;
					}

					$remain_order_id = $result[0]->post_id;

					$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}postmeta SET meta_value = %d WHERE meta_key = %s AND post_id = %d", $remaining_order_total, 'pos_remaining_amount', $remain_order_id ) );

					$total = $total + $for_insert_amount;
					$id    = $this->add_customer_balance(
						$customer_id, // user_id.
						$remain_order_id, // order_id.
						$due_date, // due_date.
						$payment_method, // payment_method.
						$remark, // remark.
						current_time( 'mysql' ), // created_date.
						current_time( 'mysql', 1 ), // created_date_gmt.
						$for_insert_amount, // amount.
						$total, // total.
						'pending' // status.
					);
				}
			}
			$remark = $remark . 'Total Paid : ' . $amount;
			while ( $total_remaining > 0 ) {

				$nagative_total_remaining = -1 * $total_remaining;

				$results = $wpdb->get_results(
					"
            SELECT
                pm2.post_id,
                pm2.meta_value AS order_total
            FROM
                {$wpdb->prefix}postmeta AS pm
            JOIN {$wpdb->prefix}postmeta AS pm2
            ON
                pm.post_id = pm2.post_id AND pm2.meta_key = 'pos_remaining_amount' AND CAST(pm2.meta_value AS DECIMAL(10,2)) < 0
            WHERE
                pm.meta_value = {$customer_id} and pm.meta_key = '_customer_user'
            HAVING
                order_total < 0
            ORDER BY
                
            CASE
         WHEN CAST(pm2.meta_value AS DECIMAL(10,2)) = {$nagative_total_remaining} THEN 0
        WHEN CAST(pm2.meta_value AS DECIMAL(10,2)) > {$nagative_total_remaining} THEN 1
        ELSE 2
    END,
    ABS(CAST(pm2.meta_value AS DECIMAL(10,2)) + {$total_remaining}) ASC
    
            Limit 1                 
        "
				);
				if ( empty( $results ) ) {
					$total           = $total + $total_remaining;
					$id              = $this->add_customer_balance(
						$customer_id, // user_id.
						'', // order_id.
						$due_date, // due_date.
						$payment_method, // payment_method.
						$remark, // remark.
						current_time( 'mysql' ), // created_date.
						current_time( 'mysql', 1 ), // created_date_gmt.
						$total_remaining, // amount.
						$total, // total.
						'pending' // status.
					);
					$total_remaining = 0;
				} else {
$remark_per_order =$remark.' - Order remaining : '.(-1*$results[0]->order_total);
					$for_insert_amount = $total_remaining;
					$total_remaining   = $results[0]->order_total + $total_remaining;
					if ( $total_remaining >= 0 ) {
						$remaining_order_total = 0;
                        $for_insert_amount = (-1*$results[0]->order_total);
					} else {
						$remaining_order_total = $total_remaining;
					}

					$remain_order_id = $results[0]->post_id;

					$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}postmeta SET meta_value = %d WHERE meta_key = %s AND post_id = %d", $remaining_order_total, 'pos_remaining_amount', $remain_order_id ) );
					$total = $total + $for_insert_amount;
					$id    = $this->add_customer_balance(
						$customer_id, // user_id.
						$remain_order_id, // order_id.
						$due_date, // due_date.
						$payment_method, // payment_method.
						$remark_per_order, // remark.
						current_time( 'mysql' ), // created_date.
						current_time( 'mysql', 1 ), // created_date_gmt.
						$for_insert_amount, // amount.
						$total, // total.
						'pending' // status.
					);
				}
			}
		} else {

			$total = $total + $amount;
			$id    = $this->add_customer_balance(
				$customer_id, // user_id.
				$order_id, // order_id.
				$due_date, // due_date.
				$payment_method, // payment_method.
				$remark, // remark.
				current_time( 'mysql' ), // created_date.
				current_time( 'mysql', 1 ), // created_date_gmt.
				$amount, // amount.
				$total, // total.
				'pending' // status.
			);
		}

		return new WP_REST_Response(
			array(
				'status'   => 'success',
				'id'       => $id,
				'total'    => $total,
				'amount'   => $amount,
				'user_id'  => $customer_id,
				'order_id' => $order_id,
				'due_date' => $due_date,

			),
			200
		);
	}

	/**
	 * Get list of  cutomer credit and debit details in accounts
	 *
	 * @param WP_REST_Request $request .
	 * @return array|WP_Error
	 */
	public function customer_accounts_list_callback( WP_REST_Request $request ) {

			global $wpdb;

			// Get the customer ID parameter.
			$customer_id = absint( $request->get_param( 'customer_id' ) );

			// Get the start date parameter.
			$start_date = sanitize_text_field( $request->get_param( 'start_date' ) );

			// Get the end date parameter.
			$end_date = sanitize_text_field( $request->get_param( 'end_date' ) );

			// Get the due date parameter.
			$due_date = sanitize_text_field( $request->get_param( 'due_date' ) );

			// Get the order ID parameter.
			$order_id = absint( $request->get_param( 'order_id' ) );

			// Get the page parameter.
			$page = absint( $request->get_param( 'page' ) );

			// Get the per page parameter.
			$per_page   = absint( $request->get_param( 'per_page' ) );
			$table_name = $wpdb->prefix . 'pp_customer_balance';

			// Build the SQL query using placeholders.
			$sql = "SELECT * FROM $table_name WHERE 1=1";

		if ( ! empty( $customer_id ) ) {
			$sql       .= ' AND user_id = %d';
			$sql_args[] = $customer_id;
		}

		if ( ! empty( $start_date ) ) {
			$sql       .= ' AND created_date >= %s';
			$sql_args[] = $start_date;
		}

		if ( ! empty( $end_date ) ) {
			$sql       .= ' AND created_date <= %s';
			$sql_args[] = $end_date;
		}

		if ( ! empty( $due_date ) ) {
			$sql       .= ' AND due_date = %s';
			$sql_args[] = $due_date;
		}

		if ( ! empty( $order_id ) ) {
			$sql       .= ' AND order_id = %d';
			$sql_args[] = $order_id;
		}
		$sql .= ' ORDER BY id DESC';
		if ( ! empty( $page ) && ! empty( $per_page ) ) {
			$offset     = ( $page - 1 ) * $per_page;
			$sql       .= ' LIMIT %d, %d';
			$sql_args[] = $offset;
			$sql_args[] = $per_page;
		}

			// Prepare and execute the SQL query with placeholders.
			$prepared_sql = $wpdb->prepare( $sql, $sql_args );
			$results      = $wpdb->get_results( $prepared_sql );

			// Return the results.

		return new WP_REST_Response(
			$results,
			200
		);
	}

	/**
	 * Get list of  cutomer rewards and points from rewards plugin
	 *
	 * @param WP_REST_Request $request .
	 * @return array|WP_Error
	 */
	public function customer_points_list_callback( WP_REST_Request $request ) {

		if ( ! is_plugin_active( 'points-and-rewards-for-woocommerce/points-rewards-for-woocommerce.php' ) ) {

			$results = array();
			return new WP_REST_Response(
				$results,
				200
			);
		}

			global $wpdb;

			// Get the customer ID parameter.
			$customer_id = absint( $request->get_param( 'customer_id' ) );

			// Get the page parameter.
			$page = absint( $request->get_param( 'page' ) );

			// Get the per page parameter.
			$per_page    = absint( $request->get_param( 'per_page' ) );
			$table_name  = $wpdb->prefix . 'customer_points_woocommerce';
			$table_name2 = $wpdb->prefix . 'customer_points_meta_data';

			// Build the SQL query using placeholders.
			// $sql = "SELECT * FROM $table_name cp JOIN $table_name2 cpm  ON cpm.customer_point_id =";
			$sql = "SELECT *, CONCAT('{', COALESCE( (SELECT GROUP_CONCAT(CONCAT('\"', cpm.meta_key, '\": \"', cpm.meta_value, '\"') SEPARATOR ', ') FROM $table_name2 cpm WHERE cp.id = cpm.customer_point_id ), ''), '}') AS meta FROM  $table_name cp";

		if ( ! empty( $customer_id ) ) {
			$sql       .= ' WHERE customer_id = %d';
			$sql_args[] = $customer_id;
		}
		$sql .= ' ORDER BY cp.id DESC';
		if ( ! empty( $page ) && ! empty( $per_page ) ) {
			$offset     = ( $page - 1 ) * $per_page;
			$sql       .= ' LIMIT %d, %d';
			$sql_args[] = $offset;
			$sql_args[] = $per_page;
		}

			// Prepare and execute the SQL query with placeholders.
			$prepared_sql = $wpdb->prepare( $sql, $sql_args );
			$results      = $wpdb->get_results( $prepared_sql );
			// echo $wpdb->last_query;

			// Return the results.

		return new WP_REST_Response(
			$results,
			200
		);
	}

}

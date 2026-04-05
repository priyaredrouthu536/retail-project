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
class Pinaka_Rest_Api_Controller extends WC_REST_Controller {

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
			'reports/employee/totals',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'report_employee_totals_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

		register_rest_route(
			$this->namespace,
			'deleted/data',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'deleted_data_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

		register_rest_route(
			$this->namespace,
			'firebase_token_refresh',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'firebase_token_refresh_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

		register_rest_route(
			$this->namespace,
			'forgot_password',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'forgot_password_callback' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$this->namespace,
			'reports/orders/totals',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'report_order_totals_callback' ),
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
	 * Get report_employee_totals_callback callback.
	 *
	 * @param WP_REST_Request $request .
	 * @return array|WP_Error
	 */
	public function report_employee_totals_callback( WP_REST_Request $request ) {
		$params     = $request;
		$meta_key   = $params['meta_key'];
		$meta_value = $params['meta_value'];

		$customers_query = new WP_User_Query(
			array(
				'role'       => 'shop_manager',

				'meta_query' => array( // WPCS: slow query ok.
					array(
						'key'   => $meta_key,
						'value' => $meta_value,
					),
				),
			)
		);

		$total_customers = (int) $customers_query->get_total();

		$data = array(
			'total' => $total_customers,
		);
		return new WP_REST_Response(
			$data,
			200
		);
	}



	/**
	 * To refresh firebase token of user meta & send currency data.
	 *
	 * @param WP_REST_Request $request .
	 * @return array|WP_Error
	 */
	public function firebase_token_refresh_callback( WP_REST_Request $request ) {

		$params  = $request;
		$token   = isset( $params['token'] ) ? $params['token'] : '';
		$user_id = get_current_user_id();

		if ( ! empty( $token ) ) {
			global $wpdb;
			$check_tokens = (array) $wpdb->get_col(
				$wpdb->prepare(
					"SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_value = %s ",
					$token,
				),
			);
			if ( empty( $check_tokens ) ) {
				add_user_meta( $user_id, 'mp_firebase_token', $token );
			}
		}

		$wps_wpr_cart_points_rate=null;
		$wps_wpr_cart_price_rate=null;
		if ( is_plugin_active( 'points-and-rewards-for-woocommerce/points-rewards-for-woocommerce.php' ) ) {

									/*Check is custom points on cart is enable*/
							$wps_wpr_custom_points_on_cart     = $this->wps_wpr_get_general_settings_num( 'wps_wpr_custom_points_on_cart' );
							$wps_wpr_custom_points_on_checkout = $this->wps_wpr_get_general_settings_num( 'wps_wpr_apply_points_checkout' );

			if ( 1 == $wps_wpr_custom_points_on_cart || 1 == $wps_wpr_custom_points_on_checkout ) {
				/*Get the cart point rate*/
				$wps_wpr_cart_points_rate = $this->wps_wpr_get_general_settings_num( 'wps_wpr_cart_points_rate' );
				$wps_wpr_cart_points_rate = ( 0 == $wps_wpr_cart_points_rate ) ? 1 : $wps_wpr_cart_points_rate;
				$wps_wpr_cart_price_rate  = $this->wps_wpr_get_general_settings_num( 'wps_wpr_cart_price_rate' );
				$wps_wpr_cart_price_rate  = ( 0 == $wps_wpr_cart_price_rate ) ? 1 : $wps_wpr_cart_price_rate;

			}
		}
		$currency = get_woocommerce_currency();

		$response_data = array(
			'status'                   => 'success',
			'currency'                 => esc_attr( get_woocommerce_currency() ),
			'currency_symbol'          => esc_attr( get_woocommerce_currency_symbol() ),
			'country'                  => esc_attr( wc_get_base_location()['country'] ),
			'license_key'              => esc_attr( get_option( 'pinaka_pos_license_key' ) ),
			'item_id'                  => esc_attr( get_option( 'pinaka_pos_item_id' ) ),
			'store_state'                  => esc_attr( get_option( 'woocommerce_store_state' ) ),
			'wps_wpr_cart_price_rate'  => $wps_wpr_cart_price_rate,
			'wps_wpr_cart_points_rate' => $wps_wpr_cart_points_rate,
		);

		$response = new WP_REST_Response( $response_data, 200 );
		return $response;
	}

	/**
	 * This function is used get the general settings
	 *
	 * @name wps_wpr_get_general_settings
	 * @since    1.0.0
	 * @author WP Swings <webmaster@wpswings.com>
	 * @link https://www.wpswings.com/
	 * @param string $id  id of the general settings.
	 */
	public function wps_wpr_get_general_settings_num( $id ) {
		$wps_wpr_value    = 0;
		$general_settings = get_option( 'wps_wpr_settings_gallery', true );
		if ( ! empty( $general_settings[ $id ] ) ) {
			$wps_wpr_value = (int) $general_settings[ $id ];
		}
		return $wps_wpr_value;
	}

	/**
	 * Forgot password and send reset email.
	 *
	 * @param WP_REST_Request $request .
	 * @return array|WP_Error
	 */
	public function forgot_password_callback( WP_REST_Request $request ) {

		$username_req = sanitize_text_field( $request->get_param( 'user_login' ) );

		$errors = new WP_Error();
		if ( empty( $username_req ) || ! is_string( $username_req ) ) {
			return $this->send_error( 'empty_username', esc_html__( 'Enter a username or email address.', 'pinaka-pos' ), 400 );
		} elseif ( strpos( $username_req, '@' ) ) {
			$user_data = get_user_by( 'email', trim( wp_unslash( $username_req ) ) );
			if ( empty( $user_data ) ) {
				return $this->send_error( 'invalid_email', esc_html__( 'There is no account with that username or email address.', 'pinaka-pos' ), 400 );
			}
		} else {
			$login     = trim( $username_req );
			$user_data = get_user_by( 'login', $login );
		}
		if ( ! $user_data ) {
			return $this->send_error( 'invalid_email', esc_html__( 'There is no account with that username or email address.', 'pinaka-pos' ), 400 );
		}

		$user_login = esc_html( $user_data->user_login );
		$user_email = esc_html( $user_data->user_email );
		$key        = esc_html( get_password_reset_key( $user_data ) );

		if ( is_wp_error( $key ) ) {
			return $key;
		}

		if ( is_multisite() ) {
			$site_name = esc_html( get_network()->site_name );
		} else {
			$site_name = esc_html( wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) );
		}

		$message = esc_html__( 'Someone has requested a password reset for the following account:' ) . "\r\n\r\n";
		/* translators: %s: Site Name */
		$message .= sprintf( esc_html__( 'Site Name: %s' ), $site_name ) . "\r\n\r\n";
		/* translators: %s: Username */
		$message .= sprintf( esc_html__( 'Username: %s' ), $user_login ) . "\r\n\r\n";
		$message .= esc_html__( 'If this was a mistake, just ignore this email and nothing will happen.' ) . "\r\n\r\n";
		$message .= esc_html__( 'To reset your password, visit the following address:' ) . "\r\n\r\n";
		$message .= '<' . network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) . ">\r\n";
		/* translators: %s: Password Reset */
		$title   = sprintf( esc_html__( '[%s] Password Reset' ), $site_name );
		$title   = apply_filters( 'retrieve_password_title', $title, $user_login, $user_data );
		$message = apply_filters( 'retrieve_password_message', $message, $key, $user_login, $user_data );

		if ( $message && ! wp_mail( $user_email, wp_specialchars_decode( $title ), $message ) ) {
			return $this->send_error( 'retrieve_password_email_failure', esc_html__( 'The email could not be sent. Your site may not be correctly configured to send emails.', 'pinaka-pos' ), 401 );
		}

		return new WP_REST_Response(
			array(
				'status' => 'success',
			),
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

		$params   = sanitize_text_field( $request );
		$min_date = strtotime( sanitize_text_field( $params['date_min'] ) );
		$max_date = strtotime( sanitize_text_field( $params['date_max'] ) );

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

		foreach ( $results as $row ) {
			$totals[ $row['post_status'] ] = $row['num_posts'];
		}
		$data = array();

		foreach ( wc_get_order_statuses() as $slug => $name ) {
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



}

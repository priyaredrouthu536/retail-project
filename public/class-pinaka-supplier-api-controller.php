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
 * REST API for Supplier.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Controller
 */
class Pinaka_Supplier_Api_Controller extends WC_REST_Controller {

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
	protected $rest_base = 'supplier';


	/**
	 * Register the routes for sales reports.
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'reports/supplier/totals',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'report_supplier_totals_callback' ),
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
	public function report_supplier_totals_callback( WP_REST_Request $request ) {
		$params     = $request;
		$meta_key   = $params['meta_key'];
		$meta_value = $params['meta_value'];

		$customers_query = new WP_User_Query(
			array(
				'role'       => 'supplier',
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





}

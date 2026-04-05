<?php
/**
 * REST API Product Attribute Terms controller
 *
 * Handles requests to the products/attributes/<attribute_id>/terms endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package WooCommerce\RestApi
 * @since    3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Product Attribute Terms controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Terms_Controller
 */
class Pinaka_Product_Attribute_Terms_Controller extends WC_REST_Product_Attribute_Terms_V1_Controller {

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
	protected $rest_base = 'products/attributes/terms';


	/**
	 * Register the routes for terms.
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/batch',
			array(
				'args'   => array(
					'attribute_id' => array(
						'description' => esc_html__( 'Unique identifier for the attribute of the terms.', 'woocommerce' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'batch_items' ),
					'permission_callback' => array( $this, 'check_user_role_permission' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_public_batch_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(

				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'products_attributes_terms_callback' ),
					'permission_callback' => array( $this, 'check_user_role_permission' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::READABLE ),

				),
				'schema' => array( $this, 'get_public_batch_schema' ),
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
	 * Get products/attributes/terms  callback.
	 *
	 * @param WP_REST_Request $request .
	 * @return array|WP_Error
	 */
	public function products_attributes_terms_callback( WP_REST_Request $request ) {
		$params         = $request;
		$modified_after = $params['modified_after'];
		$per_page       = $params['per_page'];
		$page           = $params['page'];
		$attribute_id   = $params['attribute_id'];

		global $wpdb;
		$attribute_table = $wpdb->prefix . 'woocommerce_attribute_taxonomies';

		$extra_query = '';
		if ( $attribute_id ) {
			$extra_query = "WHERE a.attribute_id = $attribute_id AND t.term_id IS NOT NULL ";
		}
		if ( $modified_after ) {
			$time        = (int) strtotime( $modified_after );
			$extra_query = $extra_query . "LEFT JOIN $wpdb->termmeta tm ON tm.term_id = t.term_id WHERE tm.meta_key = 'last_update' AND tm.meta_value >= $time";
		}

		if ( isset( $per_page ) && isset( $page ) ) {
			$page        = $page - 1;
			$offset      = $page * $per_page;
			$extra_query = $extra_query . "LIMIT $per_page OFFSET $offset ";
		}

		$results = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.attribute_id,t.term_id as id,t.* FROM {$attribute_table} a
                 LEFT JOIN $wpdb->term_taxonomy tt ON CONCAT('pa_', a.attribute_name) = tt.taxonomy
                 LEFT JOIN $wpdb->terms t ON t.term_id = tt.term_id $extra_query",
			),
			ARRAY_A
		);

		return new WP_REST_Response(
			$results,
			200
		);
	}
	/**
	 * Prepare a single product attribute term output for response .
	 *
	 * @param WP_Term         $item Term object .
	 * @param WP_REST_Request $request .
	 * @return WP_REST_Response $response
	 */
	public function prepare_item_for_response( $item, $request ) {
		// Get term order.

		$menu_order = sanitize_text_field( get_term_meta( absint( $item->term_id ), 'order_' . esc_html( $this->taxonomy ), true ) );

		$data = array(
			'attribute_id' => absint( wc_attribute_taxonomy_id_by_name( sanitize_text_field( $this->taxonomy ) ) ),
			'id'           => absint( $item->term_id ),
			'name'         => sanitize_text_field( $item->name ),
			'slug'         => sanitize_title( $item->slug ),
			'description'  => wp_kses_post( $item->description ),
			'menu_order'   => absint( $menu_order ),
			'count'        => absint( $item->count ),
		);

		$context = ! empty( $request['context'] ) ? sanitize_key( $request['context'] ) : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $item, $request ) );

		/**
		 * Filter a term item returned from the API.
		 *
		 * Allows modification of the term data right before it is returned.
		 *
		 * @param WP_REST_Response  $response  The response object.
		 * @param object            $item      The original term object.
		 * @param WP_REST_Request   $request   Request used to generate the response.
		 */
		return apply_filters( "woocommerce_rest_prepare_{$this->taxonomy}", $response, $item, $request );
	}

}

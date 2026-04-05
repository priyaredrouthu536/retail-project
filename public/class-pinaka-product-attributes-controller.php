<?php
/**
 * REST API Product Attributes controller
 *
 * Handles requests to the products/attributes endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Product Attributes controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Product_Attributes_V2_Controller
 */
class Pinaka_Product_Attributes_Controller extends WC_REST_Product_Attributes_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'pinaka-pos/v1';

	/**
	 * Get all attributes.
	 *
	 * @param WP_REST_Request $request The request to get the attributes from.
	 * @return array
	 */
	public function get_items( $request ) {

		$attributes = $this->wc_get_custom_attribute_taxonomies(  $request  );
		$data       = array();
		foreach ( $attributes as $attribute_obj ) {
			$attribute = $this->prepare_item_for_response( $attribute_obj, $request );
			$attribute = $this->prepare_response_for_collection( $attribute );
			$data[]    = $attribute;
		}

		$response = rest_ensure_response( $data );

		// This API call always returns all product attributes due to retrieval from the object cache.
		$response->header( 'X-WP-Total', count( $data ) );
		$response->header( 'X-WP-TotalPages', 1 );

		return $response;
	}

	/**
	 * Get attribute taxonomies.
	 *
	 * @param WP_REST_Request $request The request to get the attributes from.
	 * @return array of objects, @since 3.6.0 these are also indexed by ID.
	 */
	public function wc_get_custom_attribute_taxonomies( $request ) {

		global $wpdb;
		$custom_query = '';
		$per_page     = absint( sanitize_text_field( $request->get_param( 'per_page' ) ) );
		$page         = absint( sanitize_text_field( $request->get_param( 'page' ) ) );
		$search       = sanitize_text_field( $request->get_param( 'search' ) );

		if ( $request->get_param( 'modified_after' ) ) {
			$date         = $request->get_param( 'modified_after' );
			$custom_query = "AND mp_last_update >= '$date'";
		}
		if ( isset( $search ) ) {
			$custom_query = " AND attribute_label like '%" . $search . "%'";
		}
		$extra_query = '';
		if ( isset( $per_page ) && isset( $page ) ) {
			$page        = --$page;
			$offset      = $page * $per_page;
			$extra_query = $extra_query . " LIMIT $per_page OFFSET $offset ";
		}

		$raw_attribute_taxonomies = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name != '' $custom_query ORDER BY attribute_name ASC $extra_query ;" );

		set_transient( 'wc_attribute_taxonomies', $raw_attribute_taxonomies );

		// Index by ID for easier lookups.
		$attribute_taxonomies = array();

		foreach ( $raw_attribute_taxonomies as $result ) {
			$attribute_taxonomies[ 'id:' . $result->attribute_id ] = $result;
		}

		return $attribute_taxonomies;
	}
}

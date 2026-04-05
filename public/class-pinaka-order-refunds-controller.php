<?php
/**
 * REST API Order Refunds controller
 *
 * Handles requests to the /orders/<order_id>/refunds endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\RestApiUtil;

/**
 * REST API Order Refunds controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Order_Refunds_V2_Controller
 */
class Pinaka_Order_Refunds_Controller extends WC_REST_Order_Refunds_V2_Controller {

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
	protected $rest_base = 'orders/refunds';



	/**
	 * Register the routes for order refunds.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				// 'args'   => array(
				// 'order_id' => array(
				// 'description' => __( 'The order ID.', 'woocommerce' ),
				// 'type'        => 'integer',
				// ),
				// ),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'order_id' => array(
						'description' => esc_html__( 'The order ID.', 'woocommerce' ),
						'type'        => 'integer',
					),
					'id'       => array(
						'description' => esc_html__( 'Unique identifier for the resource.', 'woocommerce' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'default'     => true,
							'type'        => 'boolean',
							'description' => esc_html__( 'Required to be true, as resource does not support trashing.', 'woocommerce' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/batch',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'batch_items' ),
					'permission_callback' => array( $this, 'batch_items_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_public_batch_schema' ),
			)
		);
	}

	/**
	 * Prepares one object for create or update operation.
	 *
	 * @since  3.0.0
	 * @param  WP_REST_Request $request Request object.
	 * @param  bool            $creating If is creating a new object.
	 * @return WP_Error|WC_Data The prepared item, or WP_Error object on failure.
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		RestApiUtil::adjust_create_refund_request_parameters( $request );

		$order_id = absint( $request['order_id'] );
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return new WP_Error( 'woocommerce_rest_invalid_order_id', esc_html__( 'Invalid order ID.', 'woocommerce' ), 404 );
		}

		if ( 0 > $request['amount'] ) {
			return new WP_Error( 'woocommerce_rest_invalid_order_refund', esc_html__( 'Refund amount must be greater than zero.', 'woocommerce' ), 400 );
		}

		// Create the refund.
		$refund = wc_create_refund(
			array(
				'order_id'       => $order->get_id(),
				'amount'         => $request['amount'],
				'reason'         => $request['reason'],
				'line_items'     => $request['line_items'],
				'refund_payment' => $request['api_refund'],
				'restock_items'  => $request['api_restock'],
			)
		);

		if ( is_wp_error( $refund ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_create_order_refund', $refund->get_error_message(), 500 );
		}

		if ( ! $refund ) {
			return new WP_Error( 'woocommerce_rest_cannot_create_order_refund', esc_html__( 'Cannot create order refund, please try again.', 'woocommerce' ), 500 );
		}

		if ( ! empty( $request['meta_data'] ) && is_array( $request['meta_data'] ) ) {
			foreach ( $request['meta_data'] as $meta ) {
				$refund->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
			}
			$refund->save_meta_data();
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`,
		 * refers to the object type slug.
		 *
		 * @param WC_Data         $coupon   Object object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating If is creating a new object.
		 */
		return apply_filters( "woocommerce_rest_pre_insert_{$this->post_type}_object", $refund, $request, $creating );
	}

	/**
	 * Prepare objects query.
	 *
	 * @since  3.0.0
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );
		unset( $args['post_parent__in'] );
		$args['post_status'] = 'any';
		return $args;
	}


	/**
	 * Prepare a single order output for response.
	 *
	 * @since  3.0.0
	 *
	 * @param  WC_Data         $object  Object data.
	 * @param  WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function prepare_object_for_response( $object, $request ) {
		$this->request       = $request;
		$this->request['dp'] = is_null( $this->request['dp'] ) ? wc_get_price_decimals() : absint( $this->request['dp'] );
		// $order               = wc_get_order( (int) $request['order_id'] );

		// if ( ! $order ) {
		// return new WP_Error( 'woocommerce_rest_invalid_order_id', __( 'Invalid order ID.', 'woocommerce' ), 404 );
		// }

		// if ( ! $object || $object->get_parent_id() !== $order->get_id() ) {
		// return new WP_Error( 'woocommerce_rest_invalid_order_refund_id', __( 'Invalid order refund ID.', 'woocommerce' ), 404 );
		// }

		$data = sanitize_text_field( $this->get_formatted_item_data( $object ) );
		$context = ! empty( $request['context'] ) ? sanitize_text_field( $request['context'] ) : 'view';
		$data = sanitize_text_field( $this->add_additional_fields_to_object( $data, $request ) );
		$data = sanitize_text_field( $this->filter_response_by_context( $data, $context ) );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $object, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->post_type,
		 * refers to object type being prepared for the response.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WC_Data          $object   Object data.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "woocommerce_rest_prepare_{$this->post_type}_object", $response, $object, $request );
	}


	/**
	 * Get formatted item data.
	 *
	 * @since  3.0.0
	 * @param  WC_Data $object WC_Data instance.
	 * @return array
	 */
	protected function get_formatted_item_data( $object ) {

		$data = $object->get_data();
		$format_decimal = array( 'amount' );
		$format_date = array( 'date_created' );
		$format_line_items = array( 'line_items', 'shipping_lines', 'tax_lines', 'fee_lines' );

		// Format decimal values.
		foreach ( $format_decimal as $key ) {
		    $data[ $key ] = wc_format_decimal( sanitize_text_field( $data[ $key ] ), absint( $this->request['dp'] ) );
		}

		// Format date values.
		foreach ( $format_date as $key ) {
		    $datetime = $data[ $key ];
		    $data[ $key ] = wc_rest_prepare_date_response( sanitize_text_field( $datetime ), false );
		    $data[ $key . '_gmt' ] = wc_rest_prepare_date_response( sanitize_text_field( $datetime ) );
		}

		// Format line items.
		foreach ( $format_line_items as $key ) {
		    $data[ $key ] = array_values( array_map( array( $this, 'get_order_item_data' ), $data[ $key ] ) );
		}
		
		return array(
		    'id' => absint( $object->get_id() ),
		    'order_id' => absint( $object->get_parent_id() ),
		    'date_created' => $data['date_created'],
		    'date_created_gmt' => $data['date_created_gmt'],
		    'amount' => $data['amount'],
		    'reason' => sanitize_text_field( $data['reason'] ),
		    'refunded_by' => sanitize_text_field( $data['refunded_by'] ),
		    'refunded_payment' => sanitize_text_field( $data['refunded_payment'] ),
		    'meta_data' => array_map( 'wc_sanitize_relative_html', $data['meta_data'] ),
		    'line_items' => $data['line_items'],
		    'shipping_lines' => $data['shipping_lines'],
		    'tax_lines' => $data['tax_lines'],
		    'fee_lines' => $data['fee_lines'],
		);
	}
}
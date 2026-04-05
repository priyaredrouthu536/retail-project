<?php

/**
 * REST API variations controller
 *
 * Handles requests to the /products/<product_id>/variations endpoints.
 *
 * @package WooCommerce\RestApi
 * @since   3.0.0
 */

defined('ABSPATH') || exit;
/**
 * REST API variations controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Product_Variations_V2_Controller
 */
class Pinaka_Get_Products_Controller 
{
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
    protected $rest_base = 'products';

    /**
	 * Register the routes for sales reports.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => 'GET',
                    'callback' => [$this, 'get_custom_products'],
					'permission_callback' => array( $this, 'check_user_role_permission' )
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

    /**
	 * Check whether a given request has permission to view system status.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
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
    
    public function get_custom_products($request) {
        $limit = $request->get_param('limit') ?? 10;
        $page = $request->get_param('page') ?? 1;
        $search = $request->get_param('search') ?? '';

        $args = [
            'post_type'      => 'product',
            'posts_per_page' => $limit,
            'paged'          => $page,
            's'              => $search, // Search in title and content
            'meta_query'     => [],
        ];

        // Search by SKU (if search term is provided)
        if (!empty($search)) {
            $args['meta_query'][] = [
                'relation' => 'OR',
                [
                    'key'     => '_sku',
                    'value'   => $search,
                    'compare' => 'LIKE',
                ],
                [
                    'key'     => '_post_title',
                    'value'   => $search,
                    'compare' => 'LIKE',
                ],
                [
                    'key'     => '_post_content',
                    'value'   => $search,
                    'compare' => 'LIKE',
                ],
            ];
        }

        $query = new WP_Query($args);
        $products = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product = wc_get_product(get_the_ID());

                $products[] = $this->prepare_object_for_response($product, $request);
            }
            wp_reset_postdata();
        }

        return rest_ensure_response($products);
    }

    /**
     * Prepare a single product output for response.
     */
    public function prepare_object_for_response($product, $request) {
        $data = [
            'id'                    => $product->get_id(),
            'name'                  => $product->get_name(),
            'slug'                  => $product->get_slug(),
            'date_created'          => wc_rest_prepare_date_response($product->get_date_created(), false),
            'date_modified'         => wc_rest_prepare_date_response($product->get_date_modified(), false),
            'status'                => $product->get_status(),
            'description'           => wc_format_content($product->get_description()),
            'short_description'     => wc_format_content($product->get_short_description()),
            'sku'                   => $product->get_sku(),
            'price'                 => $product->get_price(),
            'regular_price'         => $product->get_regular_price(),
            'sale_price'            => $product->get_sale_price(),
            'date_on_sale_from'     => wc_rest_prepare_date_response($product->get_date_on_sale_from(), false),
            'date_on_sale_to'       => wc_rest_prepare_date_response($product->get_date_on_sale_to(), false),
            'on_sale'               => $product->is_on_sale(),
            'purchasable'           => $product->is_purchasable(),
            'stock_quantity'        => $product->get_stock_quantity(),
            'stock_status'          => $product->get_stock_status(),
            'backorders'            => $product->get_backorders(),
            'backorders_allowed'    => $product->backorders_allowed(),
            'low_stock_amount'      => $product->get_low_stock_amount(),
            'weight'                => $product->get_weight(),
            'dimensions'            => [
                'length' => $product->get_length(),
                'width'  => $product->get_width(),
                'height' => $product->get_height(),
            ],
            'categories'            => wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']),
            'tags'                  => wp_get_post_terms($product->get_id(), 'product_tag', ['fields' => 'names']),
            'images'                => $this->get_images($product),
            'attributes'            => $this->get_attributes($product),
            'meta_data'             => $product->get_meta_data(),
            'permalink'             => $product->get_permalink(),
            'variations'            => $product->get_type() === 'variable' ? $product->get_children() : [],
        ];

        return $data;
    }

    /**
     * Get product images
     */
    private function get_images($product) {
        $images = [];
        $attachment_ids = $product->get_gallery_image_ids();

        // Add main product image
        if ($product->get_image_id()) {
            $images[] = wp_get_attachment_url($product->get_image_id());
        }

        // Add gallery images
        foreach ($attachment_ids as $attachment_id) {
            $images[] = wp_get_attachment_url($attachment_id);
        }

        return $images;
    }

    /**
     * Get product attributes
     */
    private function get_attributes($product) {
        $attributes = [];

        foreach ($product->get_attributes() as $attribute) {
            $name  = wc_attribute_label($attribute->get_name());
            $values = $attribute->is_taxonomy()
                ? wc_get_product_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names'])
                : $attribute->get_options();

            $attributes[] = [
                'name'   => $name,
                'values' => $values,
                'visible' => $attribute->get_visible(),
                'variation' => $attribute->get_variation(),
            ];
        }

        return $attributes;
    }
}

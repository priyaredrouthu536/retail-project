<?php
if (!defined('ABSPATH')) {
    exit;
}

class Custom_Variation_Controller extends WC_REST_Product_Variations_Controller {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;

        // Hook into BOTH single + collection variations
        add_filter('woocommerce_rest_prepare_product_variation', [$this, 'add_custom_fields_to_variation'], 10, 3);
        add_filter('woocommerce_rest_prepare_product_variation_object', [$this, 'add_custom_fields_to_variation'], 10, 3);
        add_filter('woocommerce_rest_prepare_shop_order_object', [$this, 'filter_parent_order_response'], 20, 3);
    }

    public function add_custom_fields_to_variation( $response, $object, $request ) {
        $route = $request->get_route();

        // Only apply for product variations list endpoint
        if ( ! preg_match( '#^/wc/v3/products/\d+/variations$#', $route ) ) {
            return $response; // Skip everything else
        }
        $variation_attributes = wc_get_formatted_variation( $object, true, false, false );

        // Remove extra formatting (commas only, no <br>)
        $variation_name = strip_tags( $variation_attributes );
        // Instead of full WooCommerce response, return only the fields you want
        $data = [
            'product_id'   => $object->get_parent_id(),
            'variation_id' => $object->get_id(),
            'name'         => $variation_name,
            'quantity'     => $object->get_stock_quantity(),
            'image'        => wp_get_attachment_url( $object->get_image_id() ),
            'price'       => $object->get_price(),
        ];

        $response->set_data( $data );
        return $response;
    }
    public function filter_parent_order_response( $response, $object, $request ) {
        // Ensure it's a REST response
        if ( ! ($response instanceof WP_REST_Response) ) {
            return $response;
        }

        // Check if _parent_order meta exists and is "yes"
        $is_parent_order = $object->get_meta('_parent_order');
        if ( $is_parent_order !== 'yes' ) {
            return $response; // Only modify parent orders
        }

        // Extract table and zone IDs from meta
        $table_id = intval( $object->get_meta('_table_id') );
        $zone_id  = intval( $object->get_meta('_zone_id') );

        $table_post = get_post( $table_id );
        $zone_post  = get_post( $zone_id );

        $table_name = $table_post ? $table_post->post_title : '';
        $zone_name  = $zone_post ? $zone_post->post_title : '';

        // Prepare simplified response
        $data = [
            'order_id'   => $object->get_id(),
            'status'     => $object->get_status(),
            'table_name' => $table_name,
            'zone_name'  => $zone_name,
        ];

        $response->set_data( $data );
        return $response;
    }

}

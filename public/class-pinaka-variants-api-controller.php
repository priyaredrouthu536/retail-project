<?php
if (!defined('ABSPATH')) {
    exit;
}
class Pinaka_Variants_API_Controller {

    protected $namespace = 'pinaka-restaurant-pos/v1';
    protected $rest_base = 'variants';

    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-all-variants',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_all_variable_products' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/search-variants',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'search_variable_products' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );
    }

    public function check_user_role_permission( $request ) 
	{
		$user_id = get_current_user_id();
		$user    = get_userdata( $user_id );
		if ( $user == null ) {
			return new WP_Error( 'pinakapos_rest_cannot_view', esc_html__( 'Sorry, you cannot give permission.', 'pinaka-pos' ), array( 'status' => rest_authorization_required_code() ) );
		}
		if ( in_array( 'administrator', (array) $user->roles ) ) {
			return true;

		} elseif ( in_array( 'shop_manager', (array) $user->roles ) ) {
			return true;

		} elseif ( in_array( 'employee', (array) $user->roles ) ) {
			return true;

		} else {
			return new WP_Error( 'pinakapos_rest_cannot_view', esc_html__( 'Sorry, you cannot give permission.', 'pinaka-pos' ), array( 'status' => rest_authorization_required_code() ) );
		}
	}

    private function prepare_variable_product($product) 
    {
        $variations = [];
        
        if ($product->is_type('variable')) 
        {
            $variation_ids = $product->get_children();
            foreach ($variation_ids as $variation_id) {
                $variation = wc_get_product($variation_id);
                if (!$variation) continue;

                $variations[] = [
                    'id'         => $variation->get_id(),
                    'name'       => $variation->get_name(),
                    'sku'        => $variation->get_sku(),
                    'price'      => wc_get_price_to_display($variation),
                    'attributes' => $variation->get_attributes(),
                ];
            }
        }

        return [
            'id'        => $product->get_id(),
            'name'      => $product->get_name(),
            'sku'       => $product->get_sku(),
            'type'      => $product->get_type(), // should be "variable"
            'price'     => wc_get_price_to_display($product),
            'image'     => $product->get_image_id() ? wp_get_attachment_url($product->get_image_id()) : false,
            'variations' => $variations,
        ];
    }

    public function get_all_variable_products() 
    {
        $products = wc_get_products([
            'status'  => 'publish',
            'limit'   => -1,
            'type'    => 'variable',
        ]);
        if (empty($products)) 
        {
            return rest_ensure_response([
                'status'  => 'success',
                'data'    => [],
                'message' => __('No Variable products found.', 'pinaka-pos'),
            ]);
        }
        $data = [];

        foreach ($products as $product) 
        {
            $data[] = $this->prepare_variable_product($product);
        }

        return rest_ensure_response(['status' => 'success', 'data' => $data]);
    }

    public function search_variable_products($request) 
    {
        global $wpdb;

        if (!isset($request['product_id']) || empty($request['product_id'])) {
            return new WP_Error('invalid_product_id', __('Search Product Id required.', 'pinaka-pos'), ['status' => 400]);
        }

        $product_id = sanitize_text_field($request['product_id']);

        $query = $wpdb->prepare("
            SELECT p.ID
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'product'
            AND p.post_status = 'publish'
            AND p.ID = %d
            AND EXISTS (
                SELECT 1
                FROM {$wpdb->posts} v
                WHERE v.post_type = 'product_variation'
                AND v.post_parent = p.ID
                AND v.post_status = 'publish'
            )
        ", $product_id);
   
        $product_ids = $wpdb->get_col($query);

        if (empty($product_ids)) {
            return rest_ensure_response([
                'status'  => 'success',
                'data'    => [],
                'message' => __('No Variable product found.', 'pinaka-pos'),
            ]);
        }

        $data = [];
        foreach ($product_ids as $product_id) 
        {
            $product = wc_get_product($product_id);
            if ($product && $product->get_type() === 'variable') 
            {
                $data[] = $this->prepare_variable_product($product);
            }
        }
        return rest_ensure_response(['status' => 'success', 'data' => $data]);
    }
}
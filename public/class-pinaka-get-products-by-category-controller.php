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
class Pinaka_Get_Products_By_Category_Controller 
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
    protected $rest_base = 'products-by-category';

    /**
     * Register the routes for sales reports.
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<category_id>\d+)',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => [$this, 'get_products_by_category'],
                    'permission_callback' => [$this, 'check_user_role_permission'],
                ),
                'schema' => [$this, 'get_public_item_schema'],
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

    public function get_products_by_category(WP_REST_Request $request) {
        $category_id = $request->get_param('category_id');

        if (!$category_id || !is_numeric($category_id)) {
            return new WP_REST_Response(array('error' => 'Invalid category ID'), 400);
        }

        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'tax_query'      => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $category_id,
                ),
            ),
        );

        $query = new WP_Query($args);
        $products = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product = wc_get_product(get_the_ID());

                $products[] = $this->prepare_object_for_response($product);
            }
        }
        wp_reset_postdata();

        return new WP_REST_Response($products, 200);
    }
    
    /**
     * Prepare a single product output for response.
     */
    public function prepare_object_for_response($product) {

        /* ---------- META DATA (runtime override, SAFE) ---------- */
        // $meta_data = [];

        // // Convert existing product meta to array
        // foreach ( $product->get_meta_data() as $meta ) {
        //     $meta_data[] = [
        //         'id'    => isset( $meta->id ) ? $meta->id : 0,
        //         'key'   => $meta->key,
        //         'value' => $meta->value,
        //     ];
        // }

        // Get discount state + amount
        $discount_data   = $this->get_discount_data_for_product( $product->get_id() );
        $disc_id = $discount_data['id'];
        $auto_discount   = $discount_data['apply'];   // yes / no
        $discount_amount = $discount_data['amount'];  // number

        // Flags
        $found_apply  = false;
        $found_amount = false;

        // // Override existing meta
        // foreach ( $meta_data as &$meta ) {

        //     if ( $meta['key'] === '_pinaka_discount_amount_auto_apply' ) {
        //         $meta['value'] = $auto_discount;
        //         $found_apply = true;
        //     }

        //     if ( $meta['key'] === '_discount_amount' ) {
        //         $meta['value'] = $discount_amount;
        //         $found_amount = true;
        //     }
        // }
        // unset( $meta );

        // Append if missing
        // if ( ! $found_apply ) {
            $meta_data = array();
            if($disc_id> 0)
            {
                $meta_data[] = [
                    'id'    => $disc_id,
                    'key'   => '_pinaka_discount_amount_auto_apply',
                    'value' => $auto_discount,
                ];
                $meta_data[] = [
                    'id'    => $disc_id,
                    'key'   => '_discount_amount',
                    'value' => $discount_amount,
                ];
            }
        // }

        // if ( ! $found_amount ) {
            // $meta_data[] = [
            //     'id'    => $disc_id,
            //     'key'   => '_discount_amount',
            //     'value' => $discount_amount,
            // ];
        // }

        // Basic product fields
        $data = [
            'id'                    => $product->get_id(),
            'name'                  => $product->get_name(),
            'sku'                   => $product->get_sku(),
            'price'                 => $product->get_price(),
            'regular_price'         => $product->get_regular_price(),
            'sale_price'            => $product->get_sale_price(),
            'categories'            => array_map(function($item){
                return array(
                    'name' => $item->name,
                    'slug' => $item->slug,
                    'id'   => $item->term_id
                );
            }, wp_get_post_terms($product->get_id(), 'product_cat')),
            'tags'                  => array_map(function($term) {
                return array(
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'id'   => $term->term_id
                );
            }, wp_get_post_terms($product->get_id(), 'product_tag')),
            'images'                => $this->get_images($product),
            'attributes'            => $this->get_attributes($product),
            'meta_data'             => $meta_data,
            'variations'            => $this->get_variations($product) ?: [],
            'type'                  => $product->get_type(),
        ];

        // --- TAX INFORMATION ---
        // Product-level tax class/status. Note: numeric tax amount depends on customer/store/location.
        $tax_class  = $product->get_tax_class();
        $taxable    = $product->is_taxable();
        $tax_status = method_exists($product, 'get_tax_status') ? $product->get_tax_status() : ''; // fallback

        $tax_rates = [];
        if ( $taxable ) {
            // Get rates for the product's tax class ('' = standard)
            $rates = WC_Tax::get_rates( $tax_class );
            foreach ( $rates as $rate ) {
                $tax_rates[] = [
                    'id'       => isset($rate['rate_id']) ? $rate['rate_id'] : (isset($rate['tax_rate_id']) ? $rate['tax_rate_id'] : ''),
                    'label'    => isset($rate['label']) ? $rate['label'] : '',
                    'rate'     => isset($rate['rate']) ? $rate['rate'] : (isset($rate['tax_rate']) ? $rate['tax_rate'] : ''),
                    'compound' => ! empty( $rate['compound'] ),
                    'shipping' => ! empty( $rate['shipping'] ),
                    'priority' => isset($rate['priority']) ? $rate['priority'] : '',
                    'country'  => isset($rate['country']) ? $rate['country'] : '',
                    'state'    => isset($rate['state']) ? $rate['state'] : '',
                    'postcode' => isset($rate['postcode']) ? $rate['postcode'] : '',
                    'city'     => isset($rate['city']) ? $rate['city'] : '',
                ];
            }
        }

        $data['tax'] = [
            'taxable'    => $taxable,
            'tax_class'  => $tax_class,
            'tax_status' => $tax_status,
            'tax_rates'  => $tax_rates,
        ];

        /*
        * OPTIONAL: If you want a numeric tax delta (price_including - price_excluding),
        * you can use WooCommerce helpers *if you have the correct context* (customer/location).
        * Example (uncomment and test if you want to include a computed value):
        *
        * // $price = $product->get_price();
        * // $incl = wc_get_price_including_tax( $product, array( 'qty' => 1, 'price' => $price ) );
        * // $excl = wc_get_price_excluding_tax( $product, array( 'qty' => 1, 'price' => $price ) );
        * // $data['tax']['price_including_tax'] = wc_format_decimal( $incl );
        * // $data['tax']['price_excluding_tax'] = wc_format_decimal( $excl );
        * // $data['tax']['tax_amount'] = wc_format_decimal( max( 0, $incl - $excl ) );
        *
        * NOTE: those functions rely on store/customer tax settings and possibly the current customer location.
        */

        return $data;
    }

    private function get_discount_data_for_product( $product_id ) {

        $today = current_time( 'Y-m-d' );

        $discounts = get_posts( [
            'post_type'   => 'discounts',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query'  => [
                [
                    'key'   => '_pinaka_discount_auto_apply',
                    'value' => 'yes',
                ],
            ],
        ] );

        foreach ( $discounts as $discount ) {

            $discount_product_id = (int) get_post_meta( $discount->ID, '_product_label', true );
            if ( $discount_product_id !== (int) $product_id ) {
                continue;
            }

            // Date check
            $start_date  = get_post_meta( $discount->ID, '_start_date', true );
            $expiry_date = get_post_meta( $discount->ID, '_expiry_date', true );

            if ( $start_date && $today < $start_date ) continue;
            if ( $expiry_date && $today > $expiry_date ) continue;

            // Usage limit
            $usage_limit = (int) get_post_meta( $discount->ID, '_usage_limit', true );
            $usage_count = (int) get_post_meta( $discount->ID, '_usage_count', true );

            if ( $usage_limit > 0 && $usage_count >= $usage_limit ) continue;

            /* ----------------------------
            * EXCLUDED CATEGORY CHECK (NEW)
            * ---------------------------- */
            $exclude_categories = get_post_meta( $discount->ID, '_exclude_categories', true );
            $exclude_categories = is_array( $exclude_categories )
                ? array_map( 'absint', $exclude_categories )
                : [];

            if ( ! empty( $exclude_categories ) ) {

                $product_cat_ids = wc_get_product_term_ids(
                    $product_id,
                    'product_cat'
                );

                // If product belongs to any excluded category → skip discount
                if ( array_intersect( $exclude_categories, $product_cat_ids ) ) {
                    continue;
                }
            }

            // Discount amount
            $amount = (float) get_post_meta( $discount->ID, '_discount_amount', true );

            return [
                'id' => $discount->ID,
                'apply'  => 'yes',
                'amount' => $amount,
            ];
        }

        return [
            'id' => 0,
            'apply'  => 'no',
            'amount' => 0,
        ];
    }


    
    protected function get_variations($product) {
        if (! $product->is_type('variable')) {
            return [];
        }

        return $product->get_children(); // Returns an array of variation IDs
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

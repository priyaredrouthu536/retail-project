<?php
if (!defined('ABSPATH')) {
    exit;
}

class Pinaka_Coupon_Api_Controller {

    protected $namespace = 'pinaka-pos/v1';
    protected $rest_base = 'coupons';

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/create',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'create_coupon'),
                'permission_callback' => '__return_true',
            )
        );
    }


    public function permissions_check() {
        return current_user_can( 'manage_woocommerce' );
    }

    /**
     * Create Coupon Function
     */
    public function create_coupon( $request ) {

        if ( ! class_exists( 'WC_Coupon' ) ) {
            return new WP_Error(
                'woocommerce_missing',
                'WooCommerce is not active',
                array( 'status' => 500 )
            );
        }

        $params = $request->get_json_params();

        // Basic Fields
        $code          = sanitize_text_field( $params['code'] ?? '' );
        $description   = sanitize_text_field( $params['description'] ?? '' );
        $amount        = floatval( $params['amount'] ?? 0 );
        $discount_type = sanitize_text_field( $params['type'] ?? 'fixed_cart' );

        // Usage Settings
        $individual_use        = ! empty( $params['individual_use'] );
        $usage_limit           = intval( $params['usage_limit'] ?? 0 );
        $usage_limit_per_user  = intval( $params['usage_limit_per_user'] ?? 0 );
        $exclude_sale_items    = ! empty( $params['exclude_sale_items'] );

        // Amount Limits
        $minimum_amount = floatval( $params['minimum_amount'] ?? 0 );
        $maximum_amount = floatval( $params['maximum_amount'] ?? 0 );

        // Expiry
        $expiry_date = sanitize_text_field( $params['expiry_date'] ?? '' );

        // Categories
        $include_categories = array_map( 'intval', $params['include_categories'] ?? array() );
        $exclude_categories = array_map( 'intval', $params['exclude_categories'] ?? array() );

        // Validation
        if ( empty( $code ) ) {
            return new WP_Error(
                'missing_code',
                'Coupon code is required',
                array( 'status' => 400 )
            );
        }

        if ( $amount <= 0 ) {
            return new WP_Error(
                'invalid_amount',
                'Amount must be greater than 0',
                array( 'status' => 400 )
            );
        }

        if ( wc_get_coupon_id_by_code( $code ) ) {
            return new WP_Error(
                'duplicate_coupon',
                'Coupon already exists',
                array( 'status' => 400 )
            );
        }

       try {

    $coupon = new WC_Coupon();

    $coupon->set_code( $code );
    $coupon->set_description( $description );
    $coupon->set_discount_type( $discount_type );
    $coupon->set_amount( $amount );
    $coupon->set_individual_use( $individual_use );
    $coupon->set_usage_limit( $usage_limit );
    $coupon->set_usage_limit_per_user( $usage_limit_per_user );
    $coupon->set_exclude_sale_items( $exclude_sale_items );
    $coupon->set_minimum_amount( $minimum_amount );
    $coupon->set_maximum_amount( $maximum_amount );
    $coupon->set_product_categories( $include_categories );
    $coupon->set_excluded_product_categories( $exclude_categories );

    if ( ! empty( $expiry_date ) ) {
        $coupon->set_date_expires( strtotime( $expiry_date ) );
    }

    $coupon_id = $coupon->save();

    return array(
        'success'   => true,
        'coupon_id' => $coupon_id,
        'message'   => 'Coupon created successfully'
    );

} catch ( Exception $e ) {

    return new WP_Error(
        'coupon_error',
        $e->getMessage(),
        array( 'status' => 500 )
    );
}    }
}
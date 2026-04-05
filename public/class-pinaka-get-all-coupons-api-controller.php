<?php
if (!defined('ABSPATH')) {
    exit;
}

class Pinaka_Get_All_Coupons_Api_Controller {

    protected $namespace = 'pinaka-pos/v1';
    protected $rest_base = 'coupons';

    public function register_routes() {

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
             array(
            'methods'  => 'GET',
            'callback' => array($this, 'get_coupons'),
            'permission_callback' => '__return_true',
        ),
        );
    }

   

    public function get_coupons($request) {

        $page = $request->get_param('page') ?: 1;
        $per_page = $request->get_param('per_page') ?: 20;

        $args = array(
            'post_type'      => 'shop_coupon',
            'posts_per_page' => $per_page,
            'paged'          => $page,
        );

        $query = new WP_Query($args);
        $coupons = [];

        foreach ($query->posts as $post) {
            $coupon = new WC_Coupon($post->ID);

            $coupons[] = array(
                'id' => $coupon->get_id(),
                'code' => $coupon->get_code(),
                'amount' => $coupon->get_amount(),
                'discount_type' => $coupon->get_discount_type(),
                'date_expires' => $coupon->get_date_expires()
                    ? $coupon->get_date_expires()->date('Y-m-d')
                    : '',
                'usage_limit' => $coupon->get_usage_limit(),
                'description' => $coupon->get_description(),
            );
        }

        return $coupons;
    }
}
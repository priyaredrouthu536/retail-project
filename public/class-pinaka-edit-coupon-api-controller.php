<?php
if (!defined('ABSPATH')) {
    exit;
}

class Pinaka_Edit_Coupons_Api_Controller {

    protected $namespace = 'pinaka-pos/v1';
    protected $rest_base = 'coupons';

    public function register_routes() {

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>\d+)',
            array(
                array(
                    'methods'  => 'GET',
                    'callback' => array($this, 'get_coupon'),
                    'permission_callback' => array($this, 'permissions_check'),
                ),
                array(
                    'methods'  => 'PUT',
                    'callback' => array($this, 'update_coupon'),
                    'permission_callback' => array($this, 'permissions_check'),
                ),
                array(
                    'methods'  => 'DELETE',
                    'callback' => array($this, 'delete_coupon'),
                    'permission_callback' => array($this, 'permissions_check'),
                ),
            )
        );
    }

    public function permissions_check() {
        return current_user_can('manage_woocommerce');
    }

    public function get_coupon($request) {

        $coupon_id = (int) $request['id'];
        $coupon = new WC_Coupon($coupon_id);

        if (!$coupon->get_id()) {
            return new WP_Error('not_found', 'Coupon not found', array('status' => 404));
        }

        return array(
            'id'                   => $coupon->get_id(),
            'code'                 => $coupon->get_code(),
            'description'          => $coupon->get_description(),
            'amount'               => $coupon->get_amount(),
            'discount_type'        => $coupon->get_discount_type(),
            'individual_use'       => $coupon->get_individual_use(),
            'usage_limit'          => $coupon->get_usage_limit(),
            'usage_limit_per_user' => $coupon->get_usage_limit_per_user(),
            'exclude_sale_items'   => $coupon->get_exclude_sale_items(),
            'minimum_amount'       => $coupon->get_minimum_amount(),
            'maximum_amount'       => $coupon->get_maximum_amount(),
            'date_expires'         => $coupon->get_date_expires()
                ? $coupon->get_date_expires()->date('Y-m-d')
                : '',
            'status'               => get_post_status($coupon_id),
        );
    }

    public function update_coupon($request) {

        $coupon_id = (int) $request['id'];
        $coupon = new WC_Coupon($coupon_id);

        if (!$coupon->get_id()) {
            return new WP_Error('not_found', 'Coupon not found', array('status' => 404));
        }

        $params = $request->get_json_params();

        if (!empty($params['code'])) {
            $coupon->set_code(sanitize_text_field($params['code']));
        }

        $coupon->set_description(sanitize_text_field($params['description'] ?? ''));
        $coupon->set_amount($params['amount'] ?? 0);
        $coupon->set_discount_type($params['discount_type'] ?? 'fixed_cart');
        $coupon->set_individual_use($params['individual_use'] ?? false);
        $coupon->set_usage_limit($params['usage_limit'] ?? '');
        $coupon->set_usage_limit_per_user($params['usage_limit_per_user'] ?? '');
        $coupon->set_exclude_sale_items($params['exclude_sale_items'] ?? false);
        $coupon->set_minimum_amount($params['minimum_amount'] ?? '');
        $coupon->set_maximum_amount($params['maximum_amount'] ?? '');

        if (!empty($params['date_expires'])) {
            $coupon->set_date_expires(strtotime($params['date_expires']));
        }

        $coupon->save();

        return array(
            'success' => true,
            'message' => 'Coupon updated successfully'
        );
    }

    public function delete_coupon($request) {

        $coupon_id = (int) $request['id'];
        $deleted = wp_delete_post($coupon_id, true);

        if (!$deleted) {
            return new WP_Error('delete_failed', 'Unable to delete coupon', array('status' => 500));
        }

        return array(
            'success' => true,
            'message' => 'Coupon deleted successfully'
        );
    }
}
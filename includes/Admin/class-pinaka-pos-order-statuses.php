<?php
/**
 * Custom WooCommerce Order Statuses for Pinaka POS Plugin
 */

if (!defined('WPINC')) {
    die;
}

class Pinaka_POS_Order_Statuses {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name = '', $version ='') {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
	    add_action( 'init', array( $this, 'register_custom_statuses' ) );
	    add_filter( 'wc_order_statuses', array( $this, 'add_custom_statuses_to_wc' ) );
    }
    public function register_custom_statuses() {
        $statuses = [
            'wc-partial-refund' => __('Partially Refunded', 'pinaka-pos'),
        ];

        foreach ($statuses as $key => $label) {
            register_post_status($key, [
                'label'                     => _x($label, 'Order status', 'pinaka-pos'),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop(
                    "$label <span class='count'>(%s)</span>",
                    "$label <span class='count'>(%s)</span>",
                    'pinaka-pos'
                ),
            ]);
        }
    }

    public function add_custom_statuses_to_wc($order_statuses) {
        $new_statuses = [];
        foreach ($order_statuses as $key => $label) {
            $new_statuses[$key] = $label;
            if ($key === 'wc-refunded') {
                $new_statuses['wc-partial-refund'] = _x('Partially Refunded', 'Order status', 'pinaka-pos');
            }
        }
        return $new_statuses;
    }
}
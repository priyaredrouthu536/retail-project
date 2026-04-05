<?php
/**
 * The admin-customer info functionality of the plugin.
 *
 * @link       https://www.pinaka.com/
 * @since      1.0.0
 *
 * @package    Pinaka_Pos
 * @subpackage Pinaka_Pos/admin
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pinaka_Customer_Info_Api_Controller {

	private $plugin_name;
	private $version;

	protected $namespace = 'pinaka-restaurant-pos/v1';
	protected $rest_base = 'customer-info';

	public function __construct($plugin_name, $version) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		add_action('rest_api_init', [$this, 'register_routes']);
		add_action('woocommerce_checkout_update_order_meta', [$this, 'save_order_customer_meta'], 10, 2);
	}

	public function register_routes() {
		register_rest_route($this->namespace, '/' . $this->rest_base . '/create-customer', [
			'methods'             => 'POST',
			'callback'            => [$this, 'create_customer_api'],
			'permission_callback' => [$this, 'check_user_role_permission'],
		]);

		register_rest_route($this->namespace, '/' . $this->rest_base . '/customer-orders', [
			'methods'             => 'GET',
			'callback'            => [$this, 'customer_orders_api'],
			'permission_callback' => [$this, 'check_user_role_permission'],
		]);
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

	/**
	 * Save from WooCommerce checkout hooks
	 */
	public function save_order_customer_meta($order_id, $data) {
		$order = wc_get_order($order_id);
		if (!$order) return;

		if (!empty($_POST['customer_name'])) {
			$order->update_meta_data('_customer_name', sanitize_text_field($_POST['customer_name']));
		}
		if (!empty($_POST['customer_mobile'])) {
			$order->update_meta_data('_customer_mobile', sanitize_text_field($_POST['customer_mobile']));
		}
		if (!empty($_POST['customer_email'])) {
			$order->update_meta_data('_customer_email', sanitize_email($_POST['customer_email']));
		}
		if (!empty($_POST['customer_id'])) {
			$order->update_meta_data('_customer_id', intval($_POST['customer_id']));
		}

		$order->save();
	}

	/**
	 * POST: /create-customer
	 */
	public function create_customer_api($request) 
    {
        $params = $request->get_json_params(); // <-- only reads JSON body

        if (!is_array($params)) {
            return new WP_Error('invalid_json', 'Invalid JSON body.', ['status' => 400]);
        }

        $order_id = isset($params['order_id']) ? intval($params['order_id']) : 0;
        if (!$order_id) {
            return new WP_Error('invalid_order', 'Order ID is required.', ['status' => 400]);
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return new WP_Error('invalid_order', 'Order not found.', ['status' => 400]);
        }

        $changed = false;

        if (!empty($params['customer_name'])) {
            $name = sanitize_text_field($params['customer_name']);
            if ($order->get_meta('_customer_name') !== $name) {
                $order->update_meta_data('_customer_name', $name);
                $changed = true;
            }
        }

        if (!empty($params['customer_mobile'])) {
            $mobile = sanitize_text_field($params['customer_mobile']);
            if ($order->get_meta('_customer_mobile') !== $mobile) {
                $order->update_meta_data('_customer_mobile', $mobile);
                $changed = true;
            }
        }

        if (!empty($params['customer_email'])) {
            $email = sanitize_email($params['customer_email']);
            if ($order->get_meta('_customer_email') !== $email) {
                $order->update_meta_data('_customer_email', $email);
                $changed = true;
            }
        }

        if (!empty($params['customer_id'])) {
            $cid = intval($params['customer_id']);
            if (intval($order->get_meta('_customer_id')) !== $cid) {
                $order->update_meta_data('_customer_id', $cid);
                $changed = true;
            }
        }

        if ($changed) {
            $order->save();
            return [
                'success'  => true,
                'message'  => 'Customer details updated for order.',
                'order_id' => $order_id
            ];
        } else {
            return [
                'success'  => true,
                'message'  => 'No changes detected. Data was already same.',
                'order_id' => $order_id
            ];
        }
    }
	/**
	 * GET: /customer-orders
	 */
	public function customer_orders_api($request) {
		$customer_id = intval($request->get_param('customer_id'));
		$mobile      = sanitize_text_field($request->get_param('mobile'));
		$email       = sanitize_email($request->get_param('email'));

		if (!$customer_id && !$mobile && !$email) {
			return new WP_Error('invalid_data', 'Provide at least customer_id, mobile or email.', ['status' => 400]);
		}

		$orders = $this->get_order_history($customer_id, $mobile, $email);

		return [
			'success' => true,
			'orders'  => $orders
		];
	}

	/**
	 * Helper: Retrieve orders with customer meta data
	 */
	public function get_order_history($customer_id = 0, $mobile = '', $email = '') {
		$meta_queries = [];

		if ($customer_id) {
			$meta_queries[] = ['key' => '_customer_id', 'value' => $customer_id];
		}
		if ($mobile) {
			$meta_queries[] = ['key' => '_customer_mobile', 'value' => $mobile];
		}
		if ($email) {
			$meta_queries[] = ['key' => '_customer_email', 'value' => $email];
		}

		$relation = count($meta_queries) > 1 ? 'OR' : 'AND';
        // print_r($meta_queries);die;
		$orders = wc_get_orders([
			'limit'      => -1,
			// 'meta_query' => array_merge(['relation' => $relation], $meta_queries)
		]);

		$results = [];
		foreach ($orders as $order) {
			$results[] = [
				'order_id' => $order->get_id(),
				'date'     => $order->get_date_created() ? $order->get_date_created()->date('Y-m-d H:i:s') : '',
				'total'    => $order->get_formatted_order_total(),
				'status'   => wc_get_order_status_name($order->get_status()),
				'meta'     => [
					'customer_name'   => $order->get_meta('_customer_name'),
					'customer_mobile' => $order->get_meta('_customer_mobile'),
					'customer_email'  => $order->get_meta('_customer_email'),
					'customer_id'     => $order->get_meta('_customer_id')
				]
			];
		}
		return $results;
	}
}
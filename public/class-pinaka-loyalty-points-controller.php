<?php
/**
 * REST API Payment API controller
 *
 * Handles requests to the Payments API endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package WooCommerce\RestApi
 * @since    3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use WooCommerce\RestApi;

/**
 * REST API Report Top Sellers controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Report_Sales_V1_Controller
 */
class Pinaka_Loyalty_Api_Controller {

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
	protected $rest_base = 'loyalty';


    /**
	 * Register the routes for sales reports.
	 */
	public function register_routes() {
        register_rest_route(
			$this->namespace,
			$this->rest_base . '/enable-disable-loyalty',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'enable_loyalty_program' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);
        register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-settings',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_settings' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);
        register_rest_route(
			$this->namespace,
			$this->rest_base . '/update-settings',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_settings' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-loyalty-points',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_loyalty_points' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);
		
        register_rest_route(
			$this->namespace,
			$this->rest_base . '/create-customer',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_customer' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);
        register_rest_route(
			$this->namespace,
			$this->rest_base . '/add-loyalty-points',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'add_loyalty_points' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

        register_rest_route(
			$this->namespace,
			$this->rest_base . '/remove-loyalty-points',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'remove_loyalty_points' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);
        register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-all-customers',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_all_customers' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);
        register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-customer-by-id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_customer_by_id' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);
        register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-redeem-percentage',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_redeem_percentage' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
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

    /**
	 * Get token by sending POST request to pinaka-pos/v1/token.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response The response.
	 */
    public function enable_loyalty_program(WP_REST_Request $request)
    {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        if (!is_plugin_active('pinaka-loyalty/pinaka-loyalty.php')) {
            return new WP_Error(
                'loyalty_plugin_inactive',
                'The Pinaka Loyalty plugin is not active. Loyalty points cannot be processed.',
                ['status' => 403]
            );
        }
        $data = $request->get_params();
        if(!isset($data['enable']))
        {
            return new WP_Error('invalid_value', 'Flag(Enable) is required.', ['status' => 400]);
        }
        if(isset($data['enable']) && !in_array($data['enable'],['yes','no']))
        {
            return new WP_Error('invalid_value', 'Flag(Enable) must be yes or no.', ['status' => 400]);
        }
        $enable = $data['enable'];
        update_option('pinaka_pos_enable_loyalty_points', $enable);
        return new WP_REST_Response([
            'success' => true,
            'message'    => 'Settings saved successfully'
        ], 200);
    }
    public function get_redeem_percentage()
    {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        if (!is_plugin_active('pinaka-loyalty/pinaka-loyalty.php')) {
            return new WP_Error(
                'loyalty_plugin_inactive',
                'The Pinaka Loyalty plugin is not active. Loyalty points cannot be processed.',
                ['status' => 403]
            );
        }
        $is_enabled = get_option('pinaka_pos_enable_loyalty_points', 'no');
        if ($is_enabled !== 'yes') {
            return new WP_Error(
                'loyalty_disabled',
                'The Loyalty Points system is currently disabled by the administrator.',
                ['status' => 403]
           );
        }
        $redeemable_perc = get_option( 'pinaka_loyalty_redeem_perc', 0 );
        $message = "Redemption is applicable only up to {$redeemable_perc}% of the net total.";
        return new WP_REST_Response([
            'success' => true,
            'message' => $message
        ], 200);
    }
    public function get_settings() 
    {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        if (!is_plugin_active('pinaka-loyalty/pinaka-loyalty.php')) {
            return new WP_Error(
                'loyalty_plugin_inactive',
                'The Pinaka Loyalty plugin is not active. Loyalty points cannot be processed.',
                ['status' => 403]
            );
        }
        $is_enabled = get_option('pinaka_pos_enable_loyalty_points', 'no');
        if ($is_enabled !== 'yes') {
            return new WP_Error(
                'loyalty_disabled',
                'The Loyalty Points system is currently disabled by the administrator.',
                ['status' => 403]
           );
        }
        $options = [
            'ratio_dollar'  => get_option( 'pinaka_loyalty_ratio_dollar', 0 ),
            'ratio_point'   => get_option( 'pinaka_loyalty_ratio_point', 0 ),
            'redeem_point'  => get_option( 'pinaka_loyalty_redeem_point', 0 ),
            'redeem_amt'    => get_option( 'pinaka_loyalty_redeem_amt', 0 ),
            'expiry_days'   => get_option( 'pinaka_loyalty_expiry_days', 365 ),
            'conflict_rule' => get_option( 'pinaka_loyalty_conflict_rule', 'enable' ),
            'min_spend'     => get_option( 'pinaka_loyalty_min_spend', 0 ),
            'max_spend'     => get_option( 'pinaka_loyalty_max_spend', 0 ),
            'min_points'    => get_option( 'pinaka_loyalty_min_points', 0 ),
            'max_points'    => get_option( 'pinaka_loyalty_max_points', 0 ),
            'redeem_perc'   => get_option( 'pinaka_loyalty_redeem_perc', 0 )
        ];

        return new WP_REST_Response([
            'success' => true,
            'data'    => $options
        ], 200);
    }
    public function update_settings( WP_REST_Request $request ) 
    {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        if (!is_plugin_active('pinaka-loyalty/pinaka-loyalty.php')) {
            return new WP_Error(
                'loyalty_plugin_inactive',
                'The Pinaka Loyalty plugin is not active. Loyalty points cannot be processed.',
                ['status' => 403]
            );
        }
        $is_enabled = get_option('pinaka_pos_enable_loyalty_points', 'no');
        if ($is_enabled !== 'yes') {
            return new WP_Error(
                'loyalty_disabled',
                'The Loyalty Points system is currently disabled by the administrator.',
                ['status' => 403]
           );
        }
        $params = $request->get_json_params();
        $integer_fields = [
            'pinaka_loyalty_ratio_dollar',
            'pinaka_loyalty_ratio_point',
            'pinaka_loyalty_expiry_days',
            'pinaka_loyalty_min_spend',
            'pinaka_loyalty_max_spend',
            'pinaka_loyalty_redeem_point',
            'pinaka_loyalty_redeem_amt',
            'pinaka_loyalty_min_points',
            'pinaka_loyalty_max_points',
            'pinaka_loyalty_redeem_perc'
        ];

        foreach ( $integer_fields as $field ) 
        {
            if ( isset( $params[ $field ] ) && !empty($params[ $field ])) 
            {
                $value = $params[ $field ];
                if ( !ctype_digit( strval( $value ) ) ) {
                    return new WP_Error(
                        'invalid_integer_field',
                        sprintf( __( 'The field "%s" must be an integer value. Given: %s', 'pinaka' ), $field, $value ),
                        [ 'status' => 400 ]
                    );
                }
            } else {
                return new WP_Error(
                    'missing_field',
                    sprintf( __( 'Missing required field: %s', 'pinaka' ), $field ),
                    [ 'status' => 400 ]
                );
            }
        }
        if ( isset( $params['pinaka_loyalty_conflict_rule'] ) ) {
            $perce = $params['pinaka_loyalty_conflict_rule'];
            if(!in_array($perce,['enable','disable']))
            {
                return new WP_Error(
                    'invalid_parameter',
                    __( 'The "pinaka_loyalty_conflict_rule" value must be enable or disable', 'pinaka' ),
                    [ 'status' => 400 ]
                );
            }
        }
        if ( isset( $params['pinaka_loyalty_redeem_perc'] ) ) {
            $perc = floatval( $params['pinaka_loyalty_redeem_perc'] );
            if ( $perc > 100 ) {
                return new WP_Error(
                    'invalid_percentage',
                    __( 'The "pinaka_loyalty_redeem_perc" value must be less than 100.', 'pinaka' ),
                    [ 'status' => 400 ]
                );
            }
        }
        if (isset($params['pinaka_loyalty_min_spend']) && isset($params['pinaka_loyalty_max_spend'])) {
            if ($params['pinaka_loyalty_min_spend'] > $params['pinaka_loyalty_max_spend']) {
                return new WP_Error(
                    'invalid_spend_range',
                    sprintf(
                        'Invalid spend range: Minimum spend (%s) cannot be greater than Maximum spend (%s).',
                        esc_html($params['pinaka_loyalty_min_spend']),
                        esc_html($params['pinaka_loyalty_max_spend'])
                    ),
                    ['status' => 400]
                );
            }
        }

        if (isset($params['pinaka_loyalty_min_points']) && isset($params['pinaka_loyalty_max_points'])) {
            if ($params['pinaka_loyalty_min_points'] > $params['pinaka_loyalty_max_points']) {
                return new WP_Error(
                    'invalid_points_range',
                    sprintf(
                        'Invalid points range: Minimum points (%s) cannot be greater than Maximum points (%s).',
                        esc_html($params['pinaka_loyalty_min_points']),
                        esc_html($params['pinaka_loyalty_max_points'])
                    ),
                    ['status' => 400]
                );
            }
        }

        $fields = [
            'pinaka_loyalty_ratio_dollar',
            'pinaka_loyalty_ratio_point',
            'pinaka_loyalty_redeem_point',
            'pinaka_loyalty_redeem_amt',
            'pinaka_loyalty_expiry_days',
            'pinaka_loyalty_conflict_rule',
            'pinaka_loyalty_min_spend',
            'pinaka_loyalty_max_spend',
            'pinaka_loyalty_min_points',
            'pinaka_loyalty_max_points',
            'pinaka_loyalty_redeem_perc'
        ];

        foreach ( $fields as $key ) {
            if ( $request->has_param( $key ) ) {
                update_option( $key, sanitize_text_field( $request->get_param( $key ) ) );
            }
        }
        $options = [
            'ratio_dollar'  => get_option( 'pinaka_loyalty_ratio_dollar', 0 ),
            'ratio_point'   => get_option( 'pinaka_loyalty_ratio_point', 0 ),
            'redeem_point'  => get_option( 'pinaka_loyalty_redeem_point', 0 ),
            'redeem_amt'    => get_option( 'pinaka_loyalty_redeem_amt', 0 ),
            'expiry_days'   => get_option( 'pinaka_loyalty_expiry_days', 365 ),
            'conflict_rule' => get_option( 'pinaka_loyalty_conflict_rule', 'enable' ),
            'min_spend'     => get_option( 'pinaka_loyalty_min_spend', 0 ),
            'max_spend'     => get_option( 'pinaka_loyalty_max_spend', 0 ),
            'min_points'    => get_option( 'pinaka_loyalty_min_points', 0 ),
            'max_points'    => get_option( 'pinaka_loyalty_max_points', 0 ),
            'redeem_perc'   => get_option( 'pinaka_loyalty_redeem_perc', 0 )
        ];
        return new WP_REST_Response([
            'success' => true,
            'message' => 'Loyalty settings updated successfully.',
            'data'    => $options
        ], 200);
    }
	public function get_loyalty_points(WP_REST_Request $request) 
    {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        if (!is_plugin_active('pinaka-loyalty/pinaka-loyalty.php')) 
        {
            return new WP_Error(
                'loyalty_plugin_inactive',
                'The Pinaka Loyalty plugin is not active. Loyalty points cannot be processed.',
                ['status' => 403]
            );
        }
        $is_enabled = get_option('pinaka_pos_enable_loyalty_points', 'no');
        if ($is_enabled !== 'yes') 
        {
            return new WP_Error(
                'loyalty_disabled',
                'The Loyalty Points system is currently disabled by the administrator.',
                ['status' => 403]
           );
        }
        $data = $request->get_params();

        $contact = isset($data['contact']) ? trim(sanitize_text_field($data['contact'])) : '';

        if (empty($contact)) {
            return new WP_Error('invalid_contact', 'Please enter mobile no or email', ['status' => 400]);
        }

        // Detect email
        $is_email = is_email($contact);

        if ($is_email) {

            // Search using billing email
            $user_query = new \WP_User_Query([
                'meta_key'   => 'billing_email',
                'meta_value' => $contact,
                'role'       => 'customer',
                'number'     => 1,
            ]);

        } else {

            // Search using mobile number
            $user_query = new \WP_User_Query([
                'meta_key'   => 'billing_phone',
                'meta_value' => $contact,
                'role'       => 'customer',
                'number'     => 1,
            ]);
        }

        $users = $user_query->get_results();
        if (empty($users)) 
        {
            return new WP_REST_Response([
                'success' => true,
                'message' => 'No loyalty points record found for the given contact.',
                'data' => [
                    'contact'        => $contact,
                    'available_points' => 0,
                ]
            ], 200);
        }

        $user = $users[0];
        $user_id = $user->ID;

        // Fetch points from user meta
        $available_points = floatval(get_user_meta($user_id, 'pinaka_available_points', true)) ?: 0;

        $response = [
            'success' => true,
            'message' => 'Loyalty points retrieved successfully.',
            'data' => [
                'contact'        => $contact,
                'available_points' => $available_points,
            ]
        ];

        return new WP_REST_Response($response, 200);
    }
    // public function create_customer(WP_REST_Request $request) 
    // {
    //     include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    //     if (!is_plugin_active('pinaka-loyalty/pinaka-loyalty.php')) 
    //     {
    //         return new WP_Error(
    //             'loyalty_plugin_inactive',
    //             'The Pinaka Loyalty plugin is not active. Loyalty points cannot be processed.',
    //             ['status' => 403]
    //         );
    //     }
    //     $is_enabled = get_option('pinaka_pos_enable_loyalty_points', 'no');
    //     if ($is_enabled !== 'yes') {
    //         return new WP_Error(
    //             'loyalty_disabled',
    //             'The Loyalty Points system is currently disabled by the administrator.',
    //             ['status' => 403]
    //        );
    //     }
    //     $data = $request->get_params();

    //     $order_id      = intval($request->get_param('order_id')) ?: 0;

    //     $order = wc_get_order($order_id);
    //     if (!$order) {
    //         return new WP_Error(
    //             'invalid_order',
    //             'The provided Order ID does not exist in WooCommerce orders.',
    //             ['status' => 404]
    //         );
    //     }

    //     $order_status = $order->get_status();
    //     if (in_array($order_status, ['completed', 'cancelled', 'refunded'], true)) {
    //         return new WP_Error(
    //             'invalid_order_status',
    //             sprintf(
    //                 'Cannot redeem points for an order that is already %s.',
    //                 ucfirst($order_status)
    //             ),
    //             ['status' => 400]
    //         );
    //     }
    //     $min_points = floatval(get_option('pinaka_loyalty_min_points', 0));
    //     $max_points = floatval(get_option('pinaka_loyalty_max_points', 0));

    //     $min_points = !empty($min_points) ? $min_points : 0;
    //     $max_points = !empty($max_points) ? $max_points : 0;

    //     if ($min_points <= 0) {
    //         return new WP_Error(
    //             'invalid_min_points',
    //             sprintf(
    //                 'Invalid "Minimum Redeem Points" setting detected. Please set a valid value in Loyalty Settings. (Current: %s)',
    //                 $min_points
    //             ),
    //             ['status' => 400]
    //         );
    //     }
    //     if ($max_points <= 0) {
    //         return new WP_Error(
    //             'invalid_max_points',
    //             sprintf(
    //                 'Invalid "Maximum Redeem Points" setting detected. Please set a valid value in Loyalty Settings. (Current: %s)',
    //                 $max_points
    //             ),
    //             ['status' => 400]
    //         );
    //     }
    //     $credit_unit  = floatval(get_option('pinaka_loyalty_ratio_dollar', 0));
    //     $credit_point = floatval(get_option('pinaka_loyalty_ratio_point', 0));
    //     $credit_unit = !empty($credit_unit) ? $credit_unit : 0;
    //     $credit_point = !empty($credit_point) ? $credit_point : 0;
    //     if ($credit_unit <= 0) {
    //         return new WP_Error(
    //             'invalid_credit_unit',
    //             sprintf(
    //                 'Invalid "Dollar" setting detected. Please set a valid value in Loyalty Settings. (Current: %s)',
    //                 $credit_unit
    //             ),
    //             ['status' => 400]
    //         );
    //     }

    //     if ($credit_point <= 0) {
    //         return new WP_Error(
    //             'invalid_credit_point',
    //             sprintf(
    //                 'Invalid "Point per dollar" setting detected. Please set a valid value in Loyalty Settings. (Current: %s)',
    //                 $credit_point
    //             ),
    //             ['status' => 400]
    //         );
    //     }

    //     $point_unit  = floatval(get_option('pinaka_loyalty_redeem_point', 0));
    //     $amount_unit = floatval(get_option('pinaka_loyalty_redeem_amt', 0));
    //     $redeem_perc = floatval(get_option('pinaka_loyalty_redeem_perc', 0));

    //     $point_unit = !empty($point_unit) ? $point_unit : 0;
    //     $amount_unit = !empty($amount_unit) ? $amount_unit : 0;
    //     $redeem_perc = !empty($redeem_perc) ? $redeem_perc : 0;

    //     if ($point_unit <= 0) {
    //         return new WP_Error(
    //             'invalid_point_unit',
    //             sprintf(
    //                 'Invalid "Points per Unit" setting detected. Please set a valid value in Loyalty Settings. (Current: %s)',
    //                 $point_unit
    //             ),
    //             ['status' => 400]
    //         );
    //     }

    //     if ($amount_unit <= 0) {
    //         return new WP_Error(
    //             'invalid_amount_unit',
    //             sprintf(
    //                 'Invalid "Amount per Unit" setting detected. Please set a valid value in Loyalty Settings. (Current: %s)',
    //                 $amount_unit
    //             ),
    //             ['status' => 400]
    //         );
    //     }

    //     if ($redeem_perc <= 0 || $redeem_perc > 100) {
    //         return new WP_Error(
    //             'invalid_redeem_perc',
    //             sprintf(
    //                 'Invalid "Redeemable Percentage" setting detected. Please set a valid value in Loyalty Settings. (Current: %s)',
    //                 $redeem_perc
    //             ),
    //             ['status' => 400]
    //         );
    //     }
    //     $contact = isset($data['contact']) ? trim(sanitize_text_field($data['contact'])) : '';
    //     if (empty($contact)) {
    //         return new WP_Error('invalid_contact', 'Please enter email or mobile number.', ['status' => 400]);
    //     }

    //     $is_email = is_email($contact);
    //     $user = null;
    //     if ($is_email) 
    //     {
    //         $user = get_user_by('email', $contact);
    //         if ($user instanceof WP_User) {
    //             $user_id = $user->ID;
    //         }
    //     } 
    //     else 
    //     {
    //         $user_query = new WP_User_Query([
    //             'meta_key'   => 'billing_phone',
    //             'meta_value' => $contact,
    //             'number'     => 1,
    //         ]);

    //         $users = $user_query->get_results();
    //         $user  = !empty($users) ? $users[0] : null;

    //         if ($user instanceof WP_User) {
    //             $user_id = $user->ID;
    //         }
    //     }
    //     if ($user instanceof WP_User) {
    //         $user_id = $user->ID;
    //         update_user_meta($user_id, 'contact', $contact);
    //     }
    //     else 
    //     {
    //         $base = $is_email ? strstr($contact, '@', true) : preg_replace('/\D/', '', $contact);
    //         $base = strtolower($base ?: 'customer');
    //         $username = 'cust_' . $base;
    //         $i = 1;

    //         while (username_exists($username)) {
    //             $username = 'cust_' . $base . '_' . $i;
    //             $i++;
    //         }

    //         $password = wp_generate_password(12, true);

    //         $user_id = wp_insert_user([
    //             'user_login'   => $username,
    //             'user_pass'    => $password,
    //             'user_email'   => $is_email ? $contact : '',
    //             'role'         => 'customer',
    //             'display_name' => 'Customer ' . ($is_email ? strstr($contact, '@', true) : substr($contact, -4)),
    //         ]);

    //         if (is_wp_error($user_id)) {
    //             return $user_id;
    //         }
    //         if ($is_email) {
    //             update_user_meta($user_id, 'billing_email', $contact);
    //         } else {
    //             update_user_meta($user_id, 'billing_phone', $contact);
    //         }
    //         update_user_meta($user_id, 'contact', $contact);
    //     }

    //     $order->update_meta_data('loyaltyuser_id', $user_id);
    //     $order->update_meta_data('contact', $contact);
    //     $order->save();

    //     $current_available = floatval(get_user_meta($user_id, 'pinaka_available_points', true)) ?: 0;
      
    //     $point_unit  = floatval(get_option('pinaka_loyalty_redeem_point', 0));
    //     $amount_unit = floatval(get_option('pinaka_loyalty_redeem_amt', 0));
    //     $redeem_perc = floatval(get_option('pinaka_loyalty_redeem_perc', 0));

    //     $point_unit = !empty($point_unit) ? $point_unit : 0;
    //     $amount_unit = !empty($amount_unit) ? $amount_unit : 0;
    //     $redeem_perc = !empty($redeem_perc) ? $redeem_perc : 0;

    //     $order_exact_total = floatval($order->get_total());

    //     $min_redeem_amount    = ($min_points / $point_unit) * $amount_unit;
    //     $required_order_total = $min_redeem_amount / ($redeem_perc / 100);

    //     if ($order_exact_total < $required_order_total) {

    //         return new WP_REST_Response([
    //             'success' => false,
    //             'message' => 'Cannot Redeem Points. Cart total must be at least $' . round($required_order_total, 2) . '.',
    //             'data'    => [
    //                 'user_id'              => $user_id,
    //                 'order_id'             => $order_id,
    //                 'contact'              => $contact,
    //                 'existing_net_payable' => floatval($order->get_total()),
    //                 'redeem_points'        => 0,
    //                 'value_redeemed'       => 0,
    //                 'available_points'     => $current_available,
    //                 'new_payable_amount'   => floatval($order->get_total())
    //             ],
    //         ], 422);
    //     }

    //     $order_items_total = 0;
    //     foreach ($order->get_items('line_item') as $item) 
    //     {
    //         if (strtolower($item->get_name()) === 'cashback') {
    //             continue;
    //         }
    //         $order_items_total += $item->get_total();
    //     }

    //     $order_total = round($order_items_total, 2);
    //     $redeem_amount = floor(($order_total * $redeem_perc) / 100);
    //     $raw_used_points = floor(($redeem_amount / $amount_unit) * $point_unit);

    //     $min_points = floatval(get_option('pinaka_loyalty_min_points', 0));
    //     $max_points = floatval(get_option('pinaka_loyalty_max_points', 0));

    //     if($current_available > 0 && $min_points > 0 && $min_points > $current_available)
    //     {
    //         return new WP_REST_Response([
    //             'success' => false,
    //             'message' => 'Cannot Redeem Points. Minimum redeemable points (' . $min_points . ') are greater than available points (' . $current_available . ').',
    //             'data'    => [
    //                 'user_id'              => $user_id,
    //                 'order_id'             => $order_id,
    //                 'contact'              => $contact,
    //                 'existing_net_payable' => $order_exact_total,
    //                 'redeem_points'        => 0,
    //                 'value_redeemed'       => 0,
    //                 'available_points'     => $current_available,
    //                 'new_payable_amount'   => $order_exact_total
    //             ],
    //         ], 422);
    //     }
    //     if($raw_used_points < $min_points)
    //     {
    //         return new WP_REST_Response([
    //             'success' => false,
    //             'message' => 'Cannot Redeem Points. Redeemable points (' . $raw_used_points . ') should not be less than minimum redeemable points (' . $min_points . ').',
    //             'data'    => [
    //                 'user_id'              => $user_id,
    //                 'order_id'             => $order_id,
    //                 'contact'              => $contact,
    //                 'existing_net_payable' => $order_exact_total,
    //                 'redeem_points'        => 0,
    //                 'value_redeemed'       => 0,
    //                 'available_points'     => $current_available,
    //                 'new_payable_amount'   => $order_exact_total
    //             ],
    //         ], 422);
    //     }
    //     // if ($current_available <= 0) 
    //     // {
    //     //     return new WP_REST_Response([
    //     //         'success' => false,
    //     //         'message' => 'User has no available loyalty points.',
    //     //         'data'    => [
    //     //             'user_id'              => $user_id,
    //     //             'order_id'             => $order_id,
    //     //             'contact'              => $contact,
    //     //             'existing_net_payable' => $order_exact_total,
    //     //             'redeem_points'        => 0,
    //     //             'value_redeemed'       => 0,
    //     //             'available_points'     => $current_available,
    //     //             'new_payable_amount'   => $order_exact_total
    //     //         ],
    //     //     ], 422);
    //     // }
    //     $used_points = $raw_used_points;
    //     if($used_points > $current_available)
    //     {
    //         $used_points = $current_available;
    //     }
    //     if ($max_points > 0 && $used_points > $max_points) 
    //     {
    //         $used_points = $max_points;
    //     }
    //     $redeem_amount = floor(($used_points / $point_unit) * $amount_unit);
    //     $new_available_points = $current_available;
    //     $final_total = max(0, $order_exact_total - $redeem_amount);

    //     return [
    //         'success' => true,
    //         'message' => 'Loyalty points retrieved successfully.',
    //         'data'    => [
    //             'user_id'                 => $user_id,
    //             'order_id'                => $order_id,
    //             'contact'                 => $contact,
    //             'existing_net_payable'    => $order_exact_total,
    //             'redeem_points'           => intval($used_points),
    //             'value_redeemed'          => $redeem_amount,
    //             'available_points'        => intval($new_available_points),
    //             'new_payable_amount'      => round($final_total,2)
    //         ],
    //     ];
    // }
    public function create_customer(WP_REST_Request $request) 
    {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        if (!is_plugin_active('pinaka-loyalty/pinaka-loyalty.php')) 
        {
            return new WP_Error(
                'loyalty_plugin_inactive',
                'The Pinaka Loyalty plugin is not active. Loyalty points cannot be processed.',
                ['status' => 403]
            );
        }
        $is_enabled = get_option('pinaka_pos_enable_loyalty_points', 'no');
        if ($is_enabled !== 'yes') {
            return new WP_Error(
                'loyalty_disabled',
                'The Loyalty Points system is currently disabled by the administrator.',
                ['status' => 403]
           );
        }
        $data = $request->get_params();

        $order_id      = intval($request->get_param('order_id')) ?: 0;

        $order = wc_get_order($order_id);
        if (!$order) {
            return new WP_Error(
                'invalid_order',
                'The provided Order ID does not exist in WooCommerce orders.',
                ['status' => 404]
            );
        }

        $order_status = $order->get_status();
        if (in_array($order_status, ['completed', 'cancelled', 'refunded'], true)) {
            return new WP_Error(
                'invalid_order_status',
                sprintf(
                    'Cannot redeem points for an order that is already %s.',
                    ucfirst($order_status)
                ),
                ['status' => 400]
            );
        }
        
        $contact = isset($data['contact']) ? trim(sanitize_text_field($data['contact'])) : '';
        if (empty($contact)) {
            return new WP_Error('invalid_contact', 'Please enter email or mobile number.', ['status' => 400]);
        }

        $is_email = is_email($contact);
        $user = null;
        if ($is_email) 
        {
            $user = get_user_by('email', $contact);
            if ($user instanceof WP_User) {
                $user_id = $user->ID;
            }
        } 
        else 
        {
            $user_query = new WP_User_Query([
                'meta_key'   => 'billing_phone',
                'meta_value' => $contact,
                'number'     => 1,
            ]);

            $users = $user_query->get_results();
            $user  = !empty($users) ? $users[0] : null;

            if ($user instanceof WP_User) {
                $user_id = $user->ID;
            }
        }
        if ($user instanceof WP_User) {
            $user_id = $user->ID;
            update_user_meta($user_id, 'contact', $contact);
        }
        else 
        {
            $base = $is_email ? strstr($contact, '@', true) : preg_replace('/\D/', '', $contact);
            $base = strtolower($base ?: 'customer');
            $username = 'cust_' . $base;
            $i = 1;

            while (username_exists($username)) {
                $username = 'cust_' . $base . '_' . $i;
                $i++;
            }

            $password = wp_generate_password(12, true);

            $user_id = wp_insert_user([
                'user_login'   => $username,
                'user_pass'    => $password,
                'user_email'   => $is_email ? $contact : '',
                'role'         => 'customer',
                'display_name' => 'Customer ' . ($is_email ? strstr($contact, '@', true) : substr($contact, -4)),
            ]);

            if (is_wp_error($user_id)) {
                return $user_id;
            }
            if ($is_email) {
                update_user_meta($user_id, 'billing_email', $contact);
            } else {
                update_user_meta($user_id, 'billing_phone', $contact);
            }
            update_user_meta($user_id, 'contact', $contact);
        }

        $order->update_meta_data('loyaltyuser_id', $user_id);
        $order->update_meta_data('contact', $contact);
        $order->save();

        $current_available = floatval(get_user_meta($user_id, 'pinaka_available_points', true)) ?: 0;

        $point_unit  = floatval(get_option('pinaka_loyalty_redeem_point', 0));
        $amount_unit = floatval(get_option('pinaka_loyalty_redeem_amt', 0));
        $redeem_perc = floatval(get_option('pinaka_loyalty_redeem_perc', 0));

        $min_points  = floatval(get_option('pinaka_loyalty_min_points', 0)); 
        $max_points  = floatval(get_option('pinaka_loyalty_max_points', 0));

        /* ---------------------------- SAFETY CHECKS ----------------------------- */
        $order_exact_total    = floatval($order->get_total());
        $order_items_total    = 0;
        $used_points          = 0;
        $redeem_amount        = 0;
        $new_available_points = $current_available;
        $final_total          = $order_exact_total;
        $message = 'Loyalty points retrieved successfully.';

        if ($point_unit > 0 && $amount_unit > 0 && $redeem_perc > 0 && $min_points > 0 && $max_points > 0) 
        {
            foreach ($order->get_items('line_item') as $item) {
                if (strtolower($item->get_name()) === 'cashback') {
                    continue;
                }
                $order_items_total += $item->get_total();
            }

            $order_total = round($order_items_total, 2);
            /* ---------------------------- REQUIRED ORDER TOTAL LOGIC ------------------------------ */

            $min_redeem_amount    = ($min_points / $point_unit) * $amount_unit;
            $required_order_total = $min_redeem_amount / ($redeem_perc / 100);

            if ($order_exact_total < $required_order_total) {
                
                $order_exact_total    = floatval($order->get_total());
                $used_points          = 0;
                $redeem_amount        = 0;
                $new_available_points = $current_available;
                $final_total          = $order_exact_total;
                // $message = "Cart total is too low to redeem minimum points.";
                $message = "Cart total is too low to redeem minimum points. Minimum required total is $" . round($required_order_total, 2);
                goto end_redeem;
            }

            /* ------------------------- RAW REDEEM CALCULATION -------------------------- */

            $redeem_amount    = floor(($order_total * $redeem_perc) / 100);
            $raw_used_points  = floor(($redeem_amount / $amount_unit) * $point_unit);

            /* ------------------------- VALIDATION -------------------------- */

            // User must have at least min points
            if ($current_available > 0 && $min_points > 0 && $min_points > $current_available) {
                
                $order_exact_total    = floatval($order->get_total());
                $used_points          = 0;
                $redeem_amount        = 0;
                $new_available_points = $current_available;
                $final_total          = $order_exact_total;
                $message = "User does not have the minimum required points.";
                goto end_redeem;
            }
            if ($raw_used_points < $min_points) {
                
                $order_exact_total    = floatval($order->get_total());
                $used_points          = 0;
                $redeem_amount        = 0;
                $new_available_points = $current_available;
                $final_total          = $order_exact_total;
                $message = "Calculated redeemable points are below the minimum threshold.";
                goto end_redeem;
            }
            if ($current_available <= 0) 
            {
                $order_exact_total    = floatval($order->get_total());
                $used_points          = 0;
                $redeem_amount        = 0;
                $new_available_points = $current_available;
                $final_total          = $order_exact_total;
                $message = "User has no available loyalty points.";
                goto end_redeem;
            }
            $ebt_total = floatval( $order->get_meta('_pinaka_ebt_eligible_total') );
            if ( $ebt_total > 0 ) {
                $order_exact_total    = floatval($order->get_total());
                $used_points          = 0;
                $redeem_amount        = 0;
                $new_available_points = $current_available;
                $final_total          = $order_exact_total;
                $message = "Loyalty points cannot be used on EBT-eligible Order.";
                goto end_redeem;
            }
            /* ------------------------- FINAL POINTS -------------------------- */

            $used_points = $raw_used_points;
            
            if ($used_points > $current_available) {
                $used_points = $current_available;
            }

            // Cannot exceed max_points
            if ($max_points > 0 && $used_points > $max_points) {
                $used_points = $max_points;
            }

            // Recalculate amount based on final used points
            $redeem_amount = floor(($used_points / $point_unit) * $amount_unit);
            $new_available_points = $current_available;
            $final_total          = max(0, $order_exact_total - $redeem_amount);
        }
        end_redeem:
        /* ------------------------- SUCCESS RESPONSE -------------------------- */

        return [
            'success' => true,
            'message' => $message,
            'data'    => [
                'user_id'              => $user_id,
                'order_id'             => $order_id,
                'contact'              => $contact,
                'existing_net_payable' => $order_exact_total,
                'redeem_points'        => intval($used_points),
                'value_redeemed'       => $redeem_amount,
                'available_points'     => intval($new_available_points),
                'new_payable_amount'   => round($final_total, 2)
            ],
        ];
    }
    public function add_loyalty_points(WP_REST_Request $request) 
    {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        if (!is_plugin_active('pinaka-loyalty/pinaka-loyalty.php')) 
        {
            return new WP_Error(
                'loyalty_plugin_inactive',
                'The Pinaka Loyalty plugin is not active. Loyalty points cannot be processed.',
                ['status' => 403]
            );
        }
        $is_enabled = get_option('pinaka_pos_enable_loyalty_points', 'no');
        if ($is_enabled !== 'yes') {
            return new WP_Error(
                'loyalty_disabled',
                'The Loyalty Points system is currently disabled by the administrator.',
                ['status' => 403]
           );
        }
        $data = $request->get_json_params();
      
        $contact = isset($data['contact']) ? trim(sanitize_text_field($data['contact'])) : '';
        if (empty($contact)) {
            return new WP_Error('invalid_contact', 'Please enter mobile no or email', ['status' => 400]);
        }
        $redeem_points = isset($data['redeem_points']) ? intval($data['redeem_points']) : 0;
        $redeem_amount = isset($data['redeem_amount']) ? floatval($data['redeem_amount']) : 0;

        if ( ! is_numeric( $redeem_points ) || intval( $redeem_points ) != $redeem_points ) {
            return new WP_Error(
                'invalid_redeem_points',
                'Redeem points must be an integer value (no decimals).',
                [ 'status' => 400 ]
            );
        }
        $order_id = isset($data['order_id']) ? intval($data['order_id']) : 0;
        $order = wc_get_order($order_id);
        if (!$order) {
            return new WP_Error(
                'invalid_order',
                'The provided Order ID does not exist in WooCommerce orders.',
                ['status' => 404]
            );
        }

        $order_status = $order->get_status();
        if (in_array($order_status, ['completed', 'cancelled', 'refunded'], true)) {
            return new WP_Error(
                'invalid_order_status',
                sprintf(
                    'Cannot redeem points for an order that is already %s.',
                    ucfirst($order_status)
                ),
                ['status' => 400]
            );
        }
        $loyalty_id = get_option('pinaka_discount_loyalty_id', 0);
        if (!$loyalty_id || !get_post_status($loyalty_id)) {
            $loyalty_id = $this->pinaka_get_product_by_title('Loyalty');
        }
        if(!$loyalty_id) 
        {
            return new WP_Error('product_missing', 'Please create a "Loyalty" product before redeeming points.', ['status' => 400]);
        }
        $order_line_items = $order->get_items('line_item');
        if (empty($order_line_items)) {
            return new WP_Error(
                'no_line_items',
                'Cannot redeem points because the order has no line items.',
                ['status' => 400]
            );
        }

        $order_contact = $order->get_meta('contact') ?: '';
        $user_id = $order->get_meta('loyaltyuser_id') ?: 0;
        if (!empty($contact) && !empty($order_contact) && $contact !== $order_contact) {
            return new WP_Error(
                'contact_mismatch',
                sprintf(
                    'Contact mismatch. Order #%d belongs to %s, not %s.',
                    $order_id,
                    $order_contact ?: 'N/A',
                    $contact
                ),
                ['status' => 400]
            );
        }

        if ($redeem_points < 0) 
        {
            return new WP_Error('invalid_redeem', 'Redeemed points cannot be negative.', ['status' => 400]);
        }
        
        $allow_with_coupons = get_option('pinaka_loyalty_conflict_rule', 'enable');
        // $allow_with_coupons = 'disable';
        $has_discount = floatval($order->get_discount_total()) > 0;
        $has_coupons  = count($order->get_coupon_codes()) > 0;

        $has_payout_product = false;
        foreach ($order->get_items('line_item') as $item) {
            $product = $item->get_product();
            if ($product)
            {   
                $payname = strtolower($product->get_name());
                if ($payname === 'payout') {
                    $has_payout_product = true;
                    break;
                }
            }
        }

        $has_discount_line_item = false;
        foreach ($order->get_items('line_item') as $item) 
        {
            $product = $item->get_product();
            if ($product) {
                // Convert product name to lowercase
                $name = strtolower($product->get_name());
                if ($name === 'discount') {
                    $has_discount_line_item = true;
                    break;
                }
            }
        }

        $has_cashback_line_item = false;
        foreach ($order->get_items('line_item') as $item) 
        {
            $product = $item->get_product();
            if ($product) {
                // Convert product name to lowercase
                $name = strtolower($product->get_name());
                if ($name === 'cashback') {
                    $has_cashback_line_item = true;
                    break;
                }
            }
        }

        if (($has_discount || $has_coupons || $has_payout_product || $has_discount_line_item || $has_cashback_line_item) && $allow_with_coupons === 'disable') {
            $redeem_points = 0;
            $redeem_amount = 0;

            $order->update_meta_data('contact', $contact);
            $order->update_meta_data('redeem_points', 0);
            $order->update_meta_data('redeem_amount', 0);
            $order->update_meta_data('loyalty_redeem_blocked', 'yes');
            $order->save();

            return new WP_REST_Response([
                'success' => false,
                'message' => 'Redeem not allowed. Loyalty points cannot be redeemed when coupons, discounts, or payout products are used in the order.',
                'data' => [
                    'order_id'       => $order_id,
                    'order_total'    => floatval($order->get_total()),
                    'contact'      => $contact,
                    'redeem_points'  => 0,
                    'redeem_amount'  => 0
                ]
            ], 200);
        }

        $min_points = floatval(get_option('pinaka_loyalty_min_points', 0));
        $max_points = floatval(get_option('pinaka_loyalty_max_points', 0));

        $min_points = !empty($min_points) ? $min_points : 0;
        $max_points = !empty($max_points) ? $max_points : 0;

        if ($min_points <= 0) {
            return new WP_Error(
                'invalid_min_points',
                sprintf(
                    'Invalid "Minimum Redeem Points" setting detected. Please set a valid value in Loyalty Settings. (Current: %s)',
                    $min_points
                ),
                ['status' => 400]
            );
        }
        if ($max_points <= 0) {
            return new WP_Error(
                'invalid_max_points',
                sprintf(
                    'Invalid "Maximum Redeem Points" setting detected. Please set a valid value in Loyalty Settings. (Current: %s)',
                    $max_points
                ),
                ['status' => 400]
            );
        }
        $existing_redeem_points = floatval($order->get_meta('redeem_points', true));
        if ($existing_redeem_points > 0) 
        {
            return new WP_Error(
                'already_redeemed',
                sprintf(
                    'Loyalty points have already been redeemed for this order. (Previously Redeemed Points: %s)',
                    number_format_i18n($existing_redeem_points)
                ),
                ['status' => 400]
            );
        }

        $credit_unit  = floatval(get_option('pinaka_loyalty_ratio_dollar', 0));
        $credit_point = floatval(get_option('pinaka_loyalty_ratio_point', 0));
        $credit_unit = !empty($credit_unit) ? $credit_unit : 0;
        $credit_point = !empty($credit_point) ? $credit_point : 0;
        if ($credit_unit <= 0) {
            return new WP_Error(
                'invalid_credit_unit',
                sprintf(
                    'Invalid "Dollar" setting detected. Please set a valid value in Loyalty Settings. (Current: %s)',
                    $credit_unit
                ),
                ['status' => 400]
            );
        }

        if ($credit_point <= 0) {
            return new WP_Error(
                'invalid_credit_point',
                sprintf(
                    'Invalid "Point per dollar" setting detected. Please set a valid value in Loyalty Settings. (Current: %s)',
                    $credit_point
                ),
                ['status' => 400]
            );
        }

        $point_unit  = floatval(get_option('pinaka_loyalty_redeem_point', 0));
        $amount_unit = floatval(get_option('pinaka_loyalty_redeem_amt', 0));
        $redeem_perc = floatval(get_option('pinaka_loyalty_redeem_perc', 0));

        $point_unit = !empty($point_unit) ? $point_unit : 0;
        $amount_unit = !empty($amount_unit) ? $amount_unit : 0;
        $redeem_perc = !empty($redeem_perc) ? $redeem_perc : 0;

        if ($point_unit <= 0) {
            return new WP_Error(
                'invalid_point_unit',
                sprintf(
                    'Invalid "Points per Unit" setting detected. Please set a valid value in Loyalty Settings. (Current: %s)',
                    $point_unit
                ),
                ['status' => 400]
            );
        }

        if ($amount_unit <= 0) {
            return new WP_Error(
                'invalid_amount_unit',
                sprintf(
                    'Invalid "Amount per Unit" setting detected. Please set a valid value in Loyalty Settings. (Current: %s)',
                    $amount_unit
                ),
                ['status' => 400]
            );
        }

        if ($redeem_perc <= 0 || $redeem_perc > 100) {
            return new WP_Error(
                'invalid_redeem_perc',
                sprintf(
                    'Invalid "Redeemable Percentage" setting detected. Please set a valid value in Loyalty Settings. (Current: %s)',
                    $redeem_perc
                ),
                ['status' => 400]
            );
        }
  
        $meta_keys = ['contact', 'redeem_points', 'redeem_amount'];
        foreach ($meta_keys as $meta_key) 
        {
            $existing_value = $order->get_meta($meta_key, true);
            if (!empty($existing_value)) {
                $order->delete_meta_data($meta_key);
            }
        }

        // $redeem_amount = floor($redeem_amount);
        $order->update_meta_data('contact', $contact);
        $order->update_meta_data('redeem_points', $redeem_points);
        $order->update_meta_data('redeem_amount', $redeem_amount);
        
        if ($loyalty_id && get_post_status($loyalty_id)) 
        {
            $existing_item_id = null;
            foreach ($order->get_items('line_item') as $item_id => $item) 
            {
                $name = strtolower($item->get_name());
                // Check if the item name is exactly "loyalty"
                if ($name === 'loyalty') {
                    $existing_item_id = $item_id;
                    break;
                }
            }
            if ($existing_item_id) {
                $item = $order->get_item($existing_item_id);
                $item->set_subtotal(-$redeem_amount);
                $item->set_total(-$redeem_amount);
                $item->save();
            } else {
                $product = wc_get_product($loyalty_id);
                $item = new WC_Order_Item_Product();
                $item->set_product($product);
                $item->set_name('Loyalty');
                $item->set_quantity(1);
                $item->set_subtotal(-$redeem_amount);
                $item->set_total(-$redeem_amount);
                $order->add_item($item);
                $order->set_status('pending');
            }
        } 

        $order->calculate_taxes();
        $order->calculate_totals();
        $order->save();
        
        $current_available = intval(get_user_meta($user_id, 'pinaka_available_points', true));
        $redeem_points     = intval($redeem_points);
        $new_available_points = max(0, $current_available - $redeem_points);
        $new_total = $order->get_total();

        $order->update_meta_data('new_netpay_amt', floatval($new_total));
        $order->save();

        update_user_meta($user_id, 'pinaka_available_points', $new_available_points);
        
        return [
            'success' => true,
            'message' => 'Loyalty points redeemed successfully.',
            'data'    => [
                'order_id'                => $order_id,
                'order_total'             => floatval($new_total),
                'contact'                 => $contact,
                'redeem_amount'           => floatval($redeem_amount),
                'used_points'             => intval($redeem_points),
                'available_points'        => $new_available_points,
            ],
        ];

        // return new WP_REST_Response($response, 200);
    }
    public function pinaka_get_product_by_title( $title ) {
		$query = new WP_Query( [
			'post_type'      => 'product',
			'title'          => $title,
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		] );

		if ( $query->have_posts() ) {
			return $query->posts[0]; // product ID
		}

		return false;
	}
    /**
     * Remove loyalty points from an order.
     *
     * @param WP_REST_Request $request The request.
     * @return WP_REST_Response The response.
     */
    public function remove_loyalty_points(WP_REST_Request $request)
    {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        if (!is_plugin_active('pinaka-loyalty/pinaka-loyalty.php')) {
            return new WP_Error(
                'loyalty_plugin_inactive',
                'The Pinaka Loyalty plugin is not active. Loyalty points cannot be processed.',
                ['status' => 403]
            );
        }
        $is_enabled = get_option('pinaka_pos_enable_loyalty_points', 'no');
        if ($is_enabled !== 'yes') {
            return new WP_Error(
                'loyalty_disabled',
                'The Loyalty Points system is currently disabled by the administrator.',
                ['status' => 403]
           );
        }
        $data = $request->get_json_params();
        $order_id  = isset($data['order_id']) ? intval($data['order_id']) : 0;
        $contact = isset($data['contact']) ? trim(sanitize_text_field($data['contact'])) : '';

        if (empty($contact)) {
            return new WP_Error('invalid_contact', 'Please enter email or mobile number.', ['status' => 400]);
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return new WP_Error(
                'invalid_order',
                'The provided Order ID does not exist in WooCommerce orders.',
                ['status' => 404]
            );
        }
        $order_status = $order->get_status();

        if (in_array($order_status, ['completed', 'cancelled', 'refunded'], true)) {
            return new WP_Error(
                'invalid_order_status',
                sprintf(
                    'Cannot remove points for an order that is already %s.',
                    ucfirst($order_status)
                ),
                ['status' => 400]
            );
        }

        $order_contact = $order->get_meta('contact') ?: '';
        if (!empty($contact) && !empty($order_contact) && $contact !== $order_contact) {
            return new WP_Error(
                'contact_mismatch',
                sprintf(
                    'Contact mismatch. Order #%d belongs to %s, not %s.',
                    $order_id,
                    $order_contact ?: 'N/A',
                    $contact
                ),
                ['status' => 400]
            );
        }
        $user_id = $order->get_meta('loyaltyuser_id',true);
        
        $unredeem_points = floatval($order->get_meta('redeem_points', true));
        $current_points  = floatval(get_user_meta($user_id, 'pinaka_available_points', true)) ?: 0;

        if ($unredeem_points > 0) {
            $new_points = $current_points + $unredeem_points;
            update_user_meta($user_id, 'pinaka_available_points', $new_points);
        }
        foreach ($order->get_items('line_item') as $item_id => $item) {
            if (stripos($item->get_name(), 'Loyalty') !== false) {
                $order->remove_item($item_id);
            }
        }

        $order->delete_meta_data('redeem_points');
        $order->delete_meta_data('redeem_amount');
        $order->delete_meta_data('new_netpay_amt');

        $order->calculate_taxes();
        $order->calculate_totals();
        $order->save();

        $existing_payments = get_posts([
            'post_type'   => 'payments',
            'numberposts' => -1,
            'meta_query'  => [
                [
                    'key'   => '_payment_order_id',
                    'value' => $order_id,
                ],
            ],
        ]);

        // Count existing payments
        $payment_count = count($existing_payments);
        if ($payment_count === 0) { 
            $order->set_status('processing');
            $order->save();
        }
        $final_order_total = floatval($order->get_total());
        return new WP_REST_Response([
            'success' => true,
            'message' => 'points removed returned successfully.',
            'data' => [
                'order_id'         => $order_id,
                'contact'          => $contact,
                'order_total'      => $final_order_total,
            ]
        ], 200);
    }
    public function get_all_customers() 
    {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        if (!is_plugin_active('pinaka-loyalty/pinaka-loyalty.php')) 
        {
            return new WP_Error(
                'loyalty_plugin_inactive',
                'The Pinaka Loyalty plugin is not active. Loyalty points cannot be processed.',
                ['status' => 403]
            );
        }
        $is_enabled = get_option('pinaka_pos_enable_loyalty_points', 'no');
        if ($is_enabled !== 'yes') 
        {
            return new WP_Error(
                'loyalty_disabled',
                'The Loyalty Points system is currently disabled by the administrator.',
                ['status' => 403]
           );
        }
        global $wpdb;
        $table_points = $wpdb->prefix . 'pinaka_loyalty_points';

        $user_query = new \WP_User_Query([
            // 'role'    => 'customer',
            'orderby' => 'user_registered',
            'order'   => 'DESC',
        ]);

        $customers = $user_query->get_results();
        $result    = [];

        foreach ( $customers as $user ) 
        {
            $user_id      = $user->ID;
            $contact      = get_user_meta( $user_id, 'contact', true );
            $credited     = floatval( get_user_meta( $user_id, 'pinaka_total_credited', true ) ) ?: 0;
            $redeemed     = floatval( get_user_meta( $user_id, 'pinaka_total_redeemed', true ) ) ?: 0;
            $expired      = floatval( get_user_meta( $user_id, 'pinaka_total_expired', true ) ) ?: 0;
            $available    = floatval( get_user_meta( $user_id, 'pinaka_available_points', true ) ) ?: 0;
            $last_updated = get_user_meta( $user_id, 'pinaka_last_updated', true ) ?: $user->user_registered;

            $transactions = [];
            if ( ! empty( $contact ) ) {
                $transactions = $wpdb->get_results( $wpdb->prepare("
                    SELECT order_id, credited_points, redeemed_points, expired_points, points_status, points_created_date, points_expiry_date
                    FROM $table_points
                    WHERE contact = %s
                    ORDER BY points_created_date DESC
                ", $contact) );
            // }
                $transactions = array_map( function( $t ) {
                    $t = (array) $t;
                    $order_id = (int) $t['order_id'];
                    $points_expiry_date = $t['points_expiry_date'];
                    $t['points_expiry_date'] = date('Y-m-d',strtotime($points_expiry_date));
                    $order = wc_get_order( $order_id );
                    $get_redeem_points = $order ? $order->get_meta('redeem_points') : 0;
                    $t['order_redeem_points'] = $get_redeem_points;
                    return $t;
                }, $transactions );

                $result[] = [
                    'user_id'      => $user_id,
                    'name'         => $user->display_name,
                    'contact'      => $contact,
                    'credited'     => $credited,
                    'redeemed'     => $redeemed,
                    'expired'      => $expired,
                    'available'    => $available,
                    'last_updated' => $last_updated,
                    'transactions' => $transactions
                ];
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'total_customers' => count( $result ),
            'data' => $result
        ], 200);
    }
    public function get_customer_by_id(WP_REST_Request $request)
    {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        if (!is_plugin_active('pinaka-loyalty/pinaka-loyalty.php')) {
            return new WP_Error(
                'loyalty_plugin_inactive',
                'The Pinaka Loyalty plugin is not active. Loyalty points cannot be processed.',
                ['status' => 403]
            );
        }
        $is_enabled = get_option('pinaka_pos_enable_loyalty_points', 'no');
        if ($is_enabled !== 'yes') {
            return new WP_Error(
                'loyalty_disabled',
                'The Loyalty Points system is currently disabled by the administrator.',
                ['status' => 403]
           );
        }
        $data = $request->get_params();
        // $mobile_no = isset($data['mobile_no']) ? trim(sanitize_text_field($data['mobile_no'])) : '';
        if (empty($mobile_no)) {
            return new WP_Error('invalid_mobile', 'Invalid Mobile Number', ['status' => 400]);
        }
        global $wpdb;
        $table_points = $wpdb->prefix . 'pinaka_loyalty_points';
        // $user_query = new \WP_User_Query([
        //     'meta_key'   => 'billing_phone',
        //     'meta_value' => $mobile_no,
        //     'role'       => 'customer',
        //     'number'     => 1,
        // ]);
        // $customers = $user_query->get_results();
        $contact = isset($data['contact']) ? trim(sanitize_text_field($data['contact'])) : '';

        if (empty($contact)) {
            return new WP_Error('invalid_contact', 'Please enter email or mobile number.', ['status' => 400]);
        }

        $is_email = is_email($contact);

        // -----------------------------------------
        // SEARCH CUSTOMER
        // -----------------------------------------

        $query_args = [
            // 'role'   => 'customer',
            'number' => 1,
        ];

        if ($is_email) {
            // Search by billing email
            $query_args['meta_key']   = 'billing_email';
            $query_args['meta_value'] = $contact;
        } else {
            // Search by billing phone
            $query_args['meta_key']   = 'billing_phone';
            $query_args['meta_value'] = $contact;
        }

        $user_query = new \WP_User_Query($query_args);

        $customers = $user_query->get_results();

        $result    = [];

        foreach ( $customers as $user ) {
            $user_id      = $user->ID;
            $contact      = get_user_meta( $user_id, 'contact', true );
            $credited     = floatval( get_user_meta( $user_id, 'pinaka_total_credited', true ) ) ?: 0;
            $redeemed     = floatval( get_user_meta( $user_id, 'pinaka_total_redeemed', true ) ) ?: 0;
            $expired      = floatval( get_user_meta( $user_id, 'pinaka_total_expired', true ) ) ?: 0;
            $available    = floatval( get_user_meta( $user_id, 'pinaka_available_points', true ) ) ?: 0;
            $last_updated = get_user_meta( $user_id, 'pinaka_last_updated', true ) ?: $user->user_registered;

            $transactions = [];
            if ( ! empty( $contact ) ) 
            {
                $transactions = $wpdb->get_results( $wpdb->prepare("
                    SELECT order_id, credited_points, redeemed_points, expired_points, points_status, points_created_date, points_expiry_date
                    FROM $table_points
                    WHERE contact = %s
                    ORDER BY points_created_date DESC
                ", $contact) );
            // }
                $transactions = array_map( function( $t ) {
                    $t = (array) $t;
                    $order_id = (int) $t['order_id'];
                    $points_expiry_date = $t['points_expiry_date'];
                    $t['points_expiry_date'] = date('Y-m-d',strtotime($points_expiry_date));
                    $order = wc_get_order( $order_id );
                    $get_redeem_points = $order ? $order->get_meta('redeem_points') : 0;
                    $t['order_redeem_points'] = $get_redeem_points;
                    return $t;
                }, $transactions );
                
                $result[] = [
                    'user_id'      => $user_id,
                    'name'         => $user->display_name,
                    'contact'      => $contact,
                    'credited'     => $credited,
                    'redeemed'     => $redeemed,
                    'expired'      => $expired,
                    'available'    => $available,
                    'last_updated' => $last_updated,
                    'transactions' => $transactions
                ];
            }
        }
        return new WP_REST_Response([
            'success' => true,
            'data' => $result
        ], 200);
    }
}
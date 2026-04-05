<?php
if(!defined('ABSPATH')){
    exit;
}

class Pinaka_Settings_Api_Controller{
    protected $namespace = 'pinaka-pos/v1';
    protected $rest_base = 'settings';

    public function register_routes(){
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/general-settings',
            array(
                'methods'        => 'POST',
                'callback'       => array($this, 'pinaka_create_general_settings_rest'),
                'permission_callback' => array($this, 'check_user_role_permission'),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/device-settings',   
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'pinaka_create_device_settings_rest'),
                'permission_callback' => array($this, 'check_user_role_permission'), // or '__return_true'
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/cash-settings',   
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'pinaka_get_cash_settings'),
                'permission_callback' => array($this, 'check_user_role_permission'), // or '__return_true'
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/cash-settings',   
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'pinaka_save_cash_settings'),
                'permission_callback' => array($this, 'check_user_role_permission'), // or '__return_true'
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/business-info',   
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'pinaka_save_business_info'),
                'permission_callback' => array($this, 'check_user_role_permission'), // or '__return_true'
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/enable-taxes',   
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'pinaka_save_enable_taxes'),
                'permission_callback' => array($this, 'check_user_role_permission'), // or '__return_true'
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/enable-coupons',   
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'pinaka_save_enable_coupons'),
                'permission_callback' => array($this, 'check_user_role_permission'), // or '__return_true'
            )
        );
        
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/sequential-coupons',   
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'pinaka_save_sequential_coupons'),
                'permission_callback' => array($this, 'check_user_role_permission'), // or '__return_true'
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/enable-safes',   
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'pinaka_save_enable_safes'),
                'permission_callback' => array($this, 'check_user_role_permission'), // or '__return_true'
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/enable-safes-drop',   
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'pinaka_save_enable_safes_drop'),
                'permission_callback' => array($this, 'check_user_role_permission'), // or '__return_true'
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/enable-cashback',   
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'pinaka_save_enable_cashback'),
                'permission_callback' => array($this, 'check_user_role_permission'), // or '__return_true'
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/enable-service-charge',   
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'pinaka_save_enable_service_charge'),
                'permission_callback' => array($this, 'check_user_role_permission'), // or '__return_true'
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/enable-loyalty-points',   
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'pinaka_save_enable_loyalty_points'),
                'permission_callback' => array($this, 'check_user_role_permission'), // or '__return_true'
            )
        );

    }

    public function check_user_role_permission($request){
        $user_id = get_current_user_id();
        $user    = get_userdata($user_id);
        if($user == null){
            return new WP_Error('pinakapos_rest_cannot_view', esc_html__('sorry, you cannot give permission.', 'pinaka-pos'), array('status' => rest_authorization_required_code()));
        }
        if(in_array('administrator', (array) $user->roles)){
            return true;
        }elseif(in_array('shop_manager',(array) $user->roles)){
            return true;
        }elseif(in_array('employee', (array) $user->roles)){
            return true;
        }else{
            return new WP_Error('pinakapos_rest_cannot_view', esc_html__('Sorry, you cannot give permission.', 'pinaka-pos'), array('status' => rest_authorization_required_code()));
        }
    }

    public function pinaka_save_enable_cashback(WP_REST_Request $request) {

        $enable_cashback = (int) $request->get_param('enable_cashback');

        // ✅ Validate value
        if (!in_array($enable_cashback, [0, 1], true)) {
            return new WP_REST_Response([
                'status'  => false,
                'message' => 'Invalid value'
            ], 400);
        }

        // ✅ Get existing settings
        $settings = get_option('pinaka_pos_cashback_settings', []);

        // ✅ Update enabled flag
        $settings['enabled'] = $enable_cashback;

        update_option('pinaka_pos_cashback_settings', $settings);

        return new WP_REST_Response([
            'status'  => true,
            'message' => $enable_cashback ? 'Cashback enabled' : 'Cashback disabled',
            'data'    => [
                'enable_cashback' => $enable_cashback
            ]
        ], 200);
    }

    public function pinaka_save_enable_service_charge(WP_REST_Request $request) {

        $enable_service_charge = (int) $request->get_param('enable_service_charge');

        if (!in_array($enable_service_charge, [0, 1], true)) {
            return new WP_REST_Response([
                'status'  => false,
                'message' => 'Invalid value'
            ], 400);
        }

        // ✅ Default settings structure
        $defaults = [
            'enabled'     => 0,
            'charge_type' => 'fixed',
            'apply_to'    => 'order',
            'max_charge'  => '',
            'tiers'       => [],
        ];

        // ✅ Get existing settings
        $settings = get_option('pinaka_pos_service_charge_settings', []);

        // ✅ Merge to prevent missing keys
        $settings = wp_parse_args($settings, $defaults);

        // ✅ Update only enabled flag
        $settings['enabled'] = $enable_service_charge;

        update_option('pinaka_pos_service_charge_settings', $settings);

        return new WP_REST_Response([
            'status'  => true,
            'message' => $enable_service_charge ? 'Service charge enabled' : 'Service charge disabled',
            'data'    => [
                'enable_service_charge' => $enable_service_charge
            ]
        ], 200);
    }

    public function pinaka_save_enable_loyalty_points(WP_REST_Request $request) {

        $enable_loyalty_points = $request->get_param('enable_loyalty_points');

        // ✅ Validate value
        if (!in_array($enable_loyalty_points, ['yes', 'no'], true)) {
            return new WP_REST_Response([
                'status'  => false,
                'message' => 'Invalid value'
            ], 400);
        }

        // ✅ Save option
        update_option('pinaka_pos_enable_loyalty_points', $enable_loyalty_points);

        return new WP_REST_Response([
            'status'  => true,
            'message' => $enable_loyalty_points === 'yes'
                ? 'Loyalty points enabled'
                : 'Loyalty points disabled',
            'data'    => [
                'enable_loyalty_points' => $enable_loyalty_points
            ]
        ], 200);
    }

    public function pinaka_create_general_settings_rest(WP_REST_Request $request) {
        // ✅ Get request data (JSON or form-data)
        $data = $request->get_json_params();
        if (empty($data)) {
            $data = $request->get_body_params(); // fallback for form-data
        }

        // ✅ Update text fields
        update_option('pinaka_business_name', sanitize_text_field($data['business_name'] ?? ''));
        update_option('pinaka_email', sanitize_email($data['email'] ?? ''));
        update_option('pinaka_language', sanitize_text_field($data['language'] ?? ''));
        update_option('pinaka_nav_bar_location', sanitize_text_field($data['nav_bar_location'] ?? ''));
        update_option('pinaka_pos_theme', sanitize_text_field($data['pos_theme'] ?? ''));
        update_option('pinaka_contact_info', sanitize_text_field($data['contact_info'] ?? ''));
        update_option('pinaka_address', sanitize_text_field($data['address'] ?? ''));
        update_option('pinaka_currency', sanitize_text_field($data['currency'] ?? ''));
        update_option('pinaka_timezone', sanitize_text_field($data['timezone'] ?? ''));
        update_option('pinaka_email_notification', !empty($data['email_notification']) ? 1 : 0);
        update_option('pinaka_sound_notification', !empty($data['sound_notification']) ? 1 : 0);
        update_option('pinaka_version', sanitize_text_field($data['version'] ?? ''));

        // ✅ Handle Upload Photo (pinaka_image_url)
        if (isset($_FILES['image_upload']) && !empty($_FILES['image_upload']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            $uploaded = media_handle_upload('image_upload', 0);
            if (!is_wp_error($uploaded)) {
                update_option('pinaka_photo_url', wp_get_attachment_url($uploaded));
            }
        } elseif (!empty($data['image_url'])) {
            // If URL is sent in JSON
            update_option('pinaka_photo_url', esc_url_raw($data['image_url']));
        }

        // ✅ Handle Logo Upload (pinaka_logo_url)
        if (isset($_FILES['logo_upload']) && !empty($_FILES['logo_upload']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            $uploaded = media_handle_upload('logo_upload', 0);
            if (!is_wp_error($uploaded)) {
                update_option('pinaka_logo_url', wp_get_attachment_url($uploaded));
            }
        } elseif (!empty($data['logo_url'])) {
            // If URL is sent in JSON
            update_option('pinaka_logo_url', esc_url_raw($data['logo_url']));
        }

        // ✅ Build response
        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => 'Settings updated successfully',
                'data'    => array(
                    'business_name'      => get_option('pinaka_business_name'),
                    'email'              => get_option('pinaka_email'),
                    'language'           => get_option('pinaka_language'),
                    'nav_bar_location'   => get_option('pinaka_nav_bar_location'),
                    'pos_theme'          => get_option('pinaka_pos_theme'),
                    'contact_info'       => get_option('pinaka_contact_info'),
                    'address'            => get_option('pinaka_address'),
                    'currency'           => get_option('pinaka_currency'),
                    'timezone'           => get_option('pinaka_timezone'),
                    'email_notification' => get_option('pinaka_email_notification'),
                    'sound_notification' => get_option('pinaka_sound_notification'),
                    'version'            => get_option('pinaka_version'),
                    'image_url'          => get_option('pinaka_photo_url'),
                    'logo_url'           => get_option('pinaka_logo_url'),
                )
            ),
            200
        );
    }


    public function pinaka_create_device_settings_rest(WP_REST_Request $request) {
        $data = $request->get_json_params();
        if (empty($data)) {
            $data = $request->get_body_params(); // fallback for form-data
        }

        // ✅ Collect & sanitize
        $settings = [
            'kot_printer'     => sanitize_text_field($data['kot_printer'] ?? ''),
            'receipt_printer' => sanitize_text_field($data['receipt_printer'] ?? ''),
            'paper_size'      => sanitize_text_field($data['paper_size'] ?? ''),
            'connection_type' => sanitize_text_field($data['connection_type'] ?? ''),
            'kitchen_printer' => sanitize_text_field($data['kitchen_printer'] ?? ''),
            'bar_printer'     => sanitize_text_field($data['bar_printer'] ?? ''),
        ];

        // ✅ Save to wp_options
        update_option('pinaka_device_settings', $settings);

        // ✅ Build Response
        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => 'Device settings updated successfully',
                'data'    => $settings
            ),
            200
        );
    }

    public function pinaka_get_cash_settings(){
        $shop_info = [
            'pinaka_pos_name'  => get_option('pinaka_pos_name'),
            'pinaka_pos_email'          => get_option('pinaka_pos_email'),
            'pinaka_pos_phone'   => get_option('pinaka_pos_phone'),
            'pinaka_pos_business_address'        => get_option('pinaka_pos_business_address'),
            'pinaka_pos_business_city'        => get_option('pinaka_pos_business_city'),
            'pinaka_pos_business_state'        => get_option('pinaka_pos_business_state'),
            'pinaka_pos_business_postcode'        => get_option('pinaka_pos_business_postcode'),
            
        ];  
        $res = [
        'enable_safes'	  => get_option('enable_safes') ? true : false,
        'enable_safes_drop' => get_option('enable_safes_drop') ? true : false,
        'currency_symbol' => get_option('currency_symbol') ? get_option('currency_symbol'): '$',
        'currencies' => get_woocommerce_currencies(),
        'selected_currency' => get_option('woocommerce_currency', 'USD'),
        'pinaka_pos_cashback_settings' =>
            wp_parse_args(get_option('pinaka_pos_cashback_settings', []), [
                'enabled' => 0,
                'max_cashback' => '',
                'tiers' => [],
            ]),

        'pinaka_pos_service_charge_settings' =>
            wp_parse_args(get_option('pinaka_pos_service_charge_settings', []), [
                'enabled' => 0,
                'charge_type' => 'fixed',
                'apply_to' => 'order',
                'max_charge' => '',
                'tiers' => [],
            ]),
        'pinaka_pos_enable_loyalty_points' => get_option('pinaka_pos_enable_loyalty_points') === 'yes',
        'shop_info' => $shop_info,
        'tax_enabled' => get_option('woocommerce_calc_taxes') === 'yes' ? 1 : 0,
        'coupons_enabled' => get_option('woocommerce_enable_coupons') === 'yes' ? 1 : 0,
        //'cal_sequential_coupons' => get_option('woocommerce_calc_discounts_sequentially') ? 1 : 0,
        'calc_sequential_coupons' => get_option('woocommerce_calc_discounts_sequentially') ? 1 : 0,
        ];
        return new WP_REST_Response(
            array(
                'data'    => $res
            ),
            200
        );
    }

    public function pinaka_save_cash_settings(WP_REST_Request $request){
         $data = $request->get_json_params();
        if (empty($data)) {
            $data = $request->get_body_params(); // fallback for form-data
        }

        if (!empty($data['currency'])) {
            update_option('woocommerce_currency', sanitize_text_field($data['currency']));
        }
        update_option('enable_safes', sanitize_text_field($data['enable_safes']));
        update_option('enable_safes_drop', sanitize_text_field($data['enable_safes_drop']));

        // ✅ Build Response
        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => 'Device settings updated successfully'
            ),
            200
        );
    }

    public function pinaka_save_business_info(WP_REST_Request $request){

    $data = $request->get_json_params();
    if (empty($data)) {
        $data = $request->get_body_params();
    }

    $shop = $data['shop_address'] ?? [];

    if(isset($shop['pinaka_pos_name'])){
        update_option('pinaka_pos_name', sanitize_text_field($shop['pinaka_pos_name']));
    }

    if(isset($shop['pinaka_pos_email'])){
        update_option('pinaka_pos_email', sanitize_email($shop['pinaka_pos_email']));
    }

    if(isset($shop['pinaka_pos_phone'])){
        update_option('pinaka_pos_phone', sanitize_text_field($shop['pinaka_pos_phone']));
    }

    if(isset($shop['pinaka_pos_business_address'])){
        update_option('pinaka_pos_business_address', sanitize_text_field($shop['pinaka_pos_business_address']));
    }

    if(isset($shop['pinaka_pos_business_city'])){
        update_option('pinaka_pos_business_city', sanitize_text_field($shop['pinaka_pos_business_city']));
    }

    if(isset($shop['pinaka_pos_business_state'])){
        update_option('pinaka_pos_business_state', sanitize_text_field($shop['pinaka_pos_business_state']));
    }

    if(isset($shop['pinaka_pos_business_postcode'])){
        update_option('pinaka_pos_business_postcode', sanitize_text_field($shop['pinaka_pos_business_postcode']));
    }

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Business info updated successfully'
    ], 200);
}

    public function pinaka_save_enable_taxes(WP_REST_Request $request){
        $enable_taxes = (int) $request->get_param('enable_taxes');

        if (!in_array($enable_taxes, [0, 1], true)) {
            return new WP_REST_Response([
                'status' => false,
                'message' => 'Invalid value'
            ], 400);
        }

        // WooCommerce tax setting
        update_option('woocommerce_calc_taxes', $enable_taxes ? 'yes' : 'no');

        return new WP_REST_Response([
            'status' => true,
            'message' => $enable_taxes ? 'Taxes enabled' : 'Taxes disabled',
            'data' => [
                'enable_taxes' => $enable_taxes
            ]
        ], 200);
    }

    public function pinaka_save_enable_coupons(WP_REST_Request $request){
        $enable_coupons = (int) $request->get_param('enable_coupons');

        if (!in_array($enable_coupons, [0, 1], true)) {
            return new WP_REST_Response([
                'status' => false,
                'message' => 'Invalid value'
            ], 400);
        }

        // WooCommerce coupon setting
        update_option('woocommerce_enable_coupons', $enable_coupons ? 'yes' : 'no');

        return new WP_REST_Response([
            'status' => true,
            'message' => $enable_coupons ? 'Coupons enabled' : 'Coupons disabled',
            'data' => [
                'enable_coupons' => $enable_coupons
            ]
        ], 200);
    }

    public function pinaka_save_sequential_coupons(WP_REST_Request $request){
        $sequential_coupons = (int) $request->get_param('coupon_sequential');

        if (!in_array($sequential_coupons, [0, 1], true)) {
            return new WP_REST_Response([
                'status' => false,
                'message' => 'Invalid value'
            ], 400);
        }

        // Save sequential coupons setting
        update_option('woocommerce_calc_discounts_sequentially', $sequential_coupons);

        return new WP_REST_Response([
            'status' => true,
            'message' => $sequential_coupons ? 'Sequential coupons enabled' : 'Sequential coupons disabled',
            'data' => [
                'sequential_coupons' => $sequential_coupons
            ]
        ], 200);
    }
    
    public function pinaka_save_enable_safes(WP_REST_Request $request){
        $enable_safes = (int) $request->get_param('enable_safes');

        if (!in_array($enable_safes, [0, 1], true)) {
            return new WP_REST_Response([
                'status' => false,
                'message' => 'Invalid value'
            ], 400);
        }

        // Save enable safes setting
        update_option('enable_safes', $enable_safes);

        return new WP_REST_Response([
            'status' => true,
            'message' => $enable_safes ? 'Safes enabled' : 'Safes disabled',
            'data' => [
                'enable_safes' => $enable_safes
            ]
        ], 200);
    }

    public function pinaka_save_enable_safes_drop(WP_REST_Request $request){
        $enable_safes_drop = (int) $request->get_param('enable_safes_drop');

        if (!in_array($enable_safes_drop, [0, 1], true)) {
            return new WP_REST_Response([
                'status' => false,
                'message' => 'Invalid value'
            ], 400);
        }

        // Save enable safes drop setting
        update_option('enable_safes_drop', $enable_safes_drop);

        return new WP_REST_Response([
            'status' => true,
            'message' => $enable_safes_drop ? 'Safes drop enabled' : 'Safes drop disabled',
            'data' => [
                'enable_safes_drop' => $enable_safes_drop
            ]
        ], 200);
    }   
}
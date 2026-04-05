<?php
if(!defined('ABSPATH')){
    exit;
}

class Pinaka_Inventories_Api_Controller{
    protected $namespace = 'pinaka-pos/v1';
    protected $rest_base = 'inventories';

    public function register_routes(){
        // register_rest_route(
        //     $this->namespace,
        //     '/' . $this->rest_base . '/get-all-categories',
        //     [
        //         'methods'             => 'GET',
        //         'callback'            => [ $this, 'get_all_categories' ],
        //         'permission_callback' => [ $this, 'check_user_role_permission' ],
        //     ]
        // );
        // register_rest_route(
        //     $this->namespace,
        //     '/' . $this->rest_base . '/get-all-taxes',
        //     [
        //         'methods'             => 'GET',
        //         'callback'            => [ $this, 'get_all_taxes' ],
        //         'permission_callback' => [ $this, 'check_user_role_permission' ],
        //     ]
        // );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-all-units',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_all_units' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-product-types',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_product_types' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );
    }

    public function check_user_role_permission( $request ) {

		$user_id = get_current_user_id();
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			$this->log_payment_event(
				'error',
				'AUTH_FAILED',
				'User authentication failed',
				[ 'user_id' => $user_id ]
			);
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
		$this->log_payment_event(
			'error',
			'PERMISSION_FAILED',
			'You dont have permission to login',
			[ 'user_roles' => $user->roles ]
		);
		return new WP_Error(
			'pinakapos_rest_cannot_view',
			esc_html__( 'Sorry, you cannot give permission.', 'pinaka-pos' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}
    public function get_product_types() {
        $product_types = wc_get_product_types();

        // $allowed = [ 'simple', 'variable' ];

        // $filtered = array_intersect_key(
        //     $product_types,
        //     array_flip( $allowed )
        // );

        return rest_ensure_response([
            'success' => true,
            'data'    => $product_types,
        ]);
    }

    public function get_all_categories($request)
    {
        $terms = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        if ( is_wp_error( $terms ) ) {
            return rest_ensure_response([]);
        }

        $result = [];

        foreach ( $terms as $term ) {
            $result[] = [
                'id'   => (int) $term->term_id,
                'name' => html_entity_decode($term->name, ENT_QUOTES | ENT_HTML5),
            ];
        }

        // Ensure Uncategorized exists
        $default_cat_id = (int) get_option( 'default_product_cat' );

        if ( $default_cat_id ) {
            $exists = array_filter($result, fn($c) => $c['id'] === $default_cat_id);
            if ( empty($exists) ) {
                $term = get_term( $default_cat_id, 'product_cat' );
                if ( $term && ! is_wp_error($term) ) {
                    $result[] = [
                        'id'   => (int) $term->term_id,
                        'name' => $term->name,
                    ];
                }
            }
        }

        return rest_ensure_response( $result );
    }
    public function get_all_taxes()
    {
        global $wpdb;

        $table = $wpdb->prefix . 'woocommerce_tax_rates';

        $rows = $wpdb->get_results("
            SELECT tax_rate_id, tax_rate_name, tax_rate, tax_rate_class
            FROM $table
            ORDER BY tax_rate_order ASC
        ");

        $result = [];

        foreach ( $rows as $row ) {
            $result[] = [
                'id'    => (int) $row->tax_rate_id,
                'name'  => $row->tax_rate_name,
                // 'rate'  => $row->tax_rate,
                // 'class' => $row->tax_rate_class ?: 'standard',
            ];
        }

        return rest_ensure_response( $result );
    }
    public function get_all_units()
    {
        $units = [
            // Count / Packaging
            'unit',
            'pc',
            'pcs',
            'pk',
            'pack',
            'dozen',
            'case',
            'box',
            'bag',
            'bundle',

            // Volume (Metric)
            'ml',
            'cl',
            'l',
            'ltr',
            'kl',

            // Volume (US / Imperial)
            'oz',
            'floz',
            'pt',
            'qt',
            'gal',

            // Weight
            'mg',
            'g',
            'kg',
            'lb',

            // Length
            'mm',
            'cm',
            'm',
            'in',
            'ft',
            'yd',
        ];

        $result = [];

        foreach ( $units as $unit ) {
            $result[] = [
                'id'   => $unit,
                'name' => $unit,
            ];
        }

        return rest_ensure_response( $result );
    }
    public function pinaka_generate_giftcard($request)
    {
        $is_gc_enabled = get_option('pinaka_gc_enabled', 'no');
        if ($is_gc_enabled !== 'yes') 
		{
            return new WP_Error(
                'giftcard_functionality_disabled',
                'Giftcard functionality has been disabled by the administrator',
                ['status' => 404]
            );
        }
        global $wpdb;
        $contact     = $request->get_param( 'contact' );
        $giftcard_id = absint( $request->get_param( 'giftcard_id' ));
        $order_id   = absint( $request->get_param( 'order_id' ) );
        // $giftcard_amount  = floatval( $request->get_param( 'giftcard_amount' ) );
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
                    'Cannot create giftcard purchase for an order that is already %s.',
                    ucfirst($order_status)
                ),
                ['status' => 400]
            );
        }
        if ( empty( $contact ) || $order_id <= 0 || $giftcard_id <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid request data'
            ];
        }

        /* ---------------- Slab Matching ---------------- */

        $slabs         = get_option( 'pinaka_gc_slabs', [] );
        $worth_amount  = 0;
        $slab_pay      = 0;
        $slab_found    = false;

        foreach ( $slabs as $slab ) {
            if ( isset( $slab['id'] ) && (int) $slab['id'] === $giftcard_id ) {
                $slab_found   = true;
                $slab_pay     = floatval( $slab['pay'] );
                $worth_amount = floatval( $slab['worth'] );
                break;
            }
        }

        if ( ! $slab_found ) {
            return [
                'success' => false,
                'message' => 'Gift card slab not found'
            ];
        }
        // if ( $giftcard_amount > $worth_amount ) {
        //     return [
        //         'success' => false,
        //         'message' => 'Gift card amount should not be greater than slab value'
        //     ];
        // }

        /* ---------------- Expiry ---------------- */

        $expiry_days = absint( get_option( 'pinaka_gc_expiry_days', 365 ) );
        $expiry_date = date( 'Y-m-d', strtotime( "+{$expiry_days} days" ) );

        $table = $wpdb->prefix . 'pinaka_gift_cards';

        $giftcard_code = 'GC-' . strtoupper( wp_generate_password( 10, false, false ) );

        while ( $wpdb->get_var(
            $wpdb->prepare(
                "SELECT giftcard_code FROM {$table} WHERE giftcard_code = %s",
                $giftcard_code
            )
        ) ) {
            $giftcard_code = 'GC-' . strtoupper( wp_generate_password( 10, false, false ) );
        }

        $purchase_id = get_option('pinaka_giftcard_purchase_id', 0);
        if (!$purchase_id || !get_post_status($purchase_id)) {
            $purchase_id = $this->pinaka_get_product_by_title('GiftCard Purchase');
        }
        if(!$purchase_id)
        {
            return new WP_Error('product_missing', 'Please create a "GiftCard Purchase" product before purchasing GiftCard.', ['status' => 400]);
        }
        if ($purchase_id && get_post_status($purchase_id)) 
        {
            $existing_item_id = null;
            foreach ($order->get_items('line_item') as $item_id => $item) 
            {
                $name = strtolower($item->get_name());
                if ($name === 'giftcard purchase') {
                    $existing_item_id = $item_id;
                    break;
                }
            }
            if ($existing_item_id) {
                $item = $order->get_item($existing_item_id);
                $item->set_subtotal($slab_pay);
                $item->set_total($slab_pay);
                $item->save();
            } else {
                $product = wc_get_product($purchase_id);
                $item = new WC_Order_Item_Product();
                $item->set_product($product);
                $item->set_name('GiftCard Purchase');
                $item->set_quantity(1);
                $item->set_subtotal($slab_pay);
                $item->set_total($slab_pay);
                $order->add_item($item);
                $order->set_status('pending');
            }
            $order->calculate_taxes();
            $order->calculate_totals();
            $order->save();
        }
        $existing_giftcard_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE order_id = %d LIMIT 1",
                $order_id
            )
        );
        $curr_user_id = get_current_user_id();
        if ( $existing_giftcard_id ) 
        {
            $wpdb->update(
                $table,
                [
                    'giftcard_code'        => $giftcard_code,
                    'contact'              => $contact,
                    'giftcard_id'         => $giftcard_id,
                    'pay_amount'          => $slab_pay,
                    'actual_worth_amount' => $worth_amount,
                    // 'giftcard_amount'     => $giftcard_amount,
                    'balance'             => $worth_amount,
                    'expiry_date'         => $expiry_date,
                    'user_id'             => $curr_user_id
                ],
                [ 'id' => $existing_giftcard_id ],
                [
                    '%s', // giftcard_code
                    '%s', // contact
                    '%d', // giftcard_id
                    '%f', // pay_amount
                    '%f', // actual_worth_amount
                    // '%f', // giftcard_amount
                    '%f', // balance
                    '%s',
                    '%d'
                ],
                [ '%d' ]
            );

        } 
        else 
        {
            $wpdb->insert(
                $table,
                [
                    'giftcard_code'        => $giftcard_code,
                    'contact'               => $contact,
                    'order_id'             => $order_id,
                    'giftcard_id'          => $giftcard_id,
                    'pay_amount'           => $slab_pay,
                    'actual_worth_amount'  => $worth_amount,
                    // 'giftcard_amount'      => $giftcard_amount,
                    'balance'              => $worth_amount,
                    'expiry_date'          => $expiry_date,
                    'status'               => 'active',
                    'created_at'           => current_time( 'mysql' ),
                    'user_id'              => $curr_user_id
                ],
                [
                    '%s', // giftcard_code
                    '%s', // contact
                    '%d', // order_id
                    '%d', // giftcard_id
                    '%f', // pay_amount
                    '%f', // actual_worth_amount
                    // '%f', // giftcard_amount
                    '%f', // balance
                    '%s', // expiry_date
                    '%s', // status
                    '%s', // created_at
                    '%d'
                ]
            );
        }
        return [
            'success' => true,
            'giftcard' => [
                'code'          => $giftcard_code,
                'contact'        => $contact,
                'order_id'       => $order_id,
                'giftcard_id'    => $giftcard_id,
                // 'giftcard_amount' => $giftcard_amount,
                'payable_amount'     => $slab_pay,
                'worth_amount'  => $worth_amount,
                'balance'       => $worth_amount,
                'expiry_date'   => $expiry_date,
                'user_id'       => $curr_user_id
            ]
        ];
    }
}
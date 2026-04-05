<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.pinakapos.com/
 * @since      1.0.0
 *
 * @package    Pinaka_POS
 * @subpackage Pinaka_POS/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Pinaka_POS
 * @subpackage Pinaka_POS/includes
 * @author     Pinakageeks <info@pinakapos.com>
 */
class Pinaka_POS {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Pinaka_POS_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		
		if ( defined( 'Pinaka_POS_VERSION' ) ) {
			$this->version = Pinaka_POS_VERSION;
		} else {
			$this->version = '1.0.0';
		}

		$this->plugin_name = 'pinaka-pos';
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->create_extra_column();
		$this->create_log_table();

	

		
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Pinaka_POS_Loader. Orchestrates the hooks of the plugin.
	 * - Pinaka_POS_i18n. Defines internationalization functionality.
	 * - Pinaka_POS_Admin. Defines all hooks for the admin area.
	 * - Pinaka_POS_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/class-pinaka-pos-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/class-pinaka-pos-i18n.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'vendor/autoload.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-admin.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-admin-helper.php';

		/**
		 * The class responsible for defining all actions that releated to deleted data.
		 */
		require_once PINAKA_POS_PLUGIN_DIR . 'public/class-pinaka-deleted-data-controller.php';

		/**
		 * The class responsible for defining all actions that releated to modify query.
		 */
		require_once PINAKA_POS_PLUGIN_DIR . 'public/class-pinaka-modify-query-controller.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once PINAKA_POS_PLUGIN_DIR . 'public/class-pinaka-pos-public.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'public/class-pinaka-order-stock-controller.php';

		require_once PINAKA_POS_PLUGIN_DIR . 'public/jwt/class-pinaka-pos-auth.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'public/jwt/class-pinaka-pos-devices.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-vendor.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-shifts.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-payments.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-vendor-payments.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-fastkeys.php';
		//require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-safes.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-safedrops.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-dash.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-discounts.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-mix-match-discount.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-dynamic-price.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-login-customizer.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-multipack.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-reports.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-order-statuses.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-invoice.php';
		$this->loader = new Pinaka_POS_Loader();
	}

	/**
	 * Create extra column on table to get data for modify date .
	 *
	 * Table woocommerce_attribute_taxonomies
	 * column last_update.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function create_extra_column() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'woocommerce_attribute_taxonomies';

		$is_column = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = %s AND column_name = %s',
				$table_name,
				'mp_last_update',
			),
		);
		if ( empty( $is_column ) ) {
			$wpdb->query( "ALTER TABLE `{$table_name}` ADD `mp_last_update` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP" );
		}
	}


	/**
	 * Create a custom table for operation logs.
	 *
	 * This function creates a table to log operations performed by users.
	 * It includes fields for user ID, action, object type, object ID, details,
	 * and timestamp.
	 *
	 * @since    1.0.0
	 */
	function create_log_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'post_full_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			post_id BIGINT,
			post_type VARCHAR(100),
			action VARCHAR(50),
			user_id BIGINT,
			data LONGTEXT,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}


	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Pinaka_POS_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new Pinaka_POS_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Pinaka_POS_Admin( $this->get_plugin_name(), $this->get_version() );
		$vendor_admin = new Pinaka_POS_Vendor( $this->get_plugin_name(), $this->get_version() );
		$shifts_admin = new Pinaka_POS_Shifts( $this->get_plugin_name(), $this->get_version() );
		$payments_admin = new Pinaka_POS_Payments( $this->get_plugin_name(), $this->get_version() );
		$vendor_payments_admin = new Pinaka_Vendor_Payments( $this->get_plugin_name(), $this->get_version() );
		$fast_keys_admin = new Fast_Keys( $this->get_plugin_name(), $this->get_version() );
		$ordersstat_admin = new Pinaka_POS_Order_Statuses( $this->get_plugin_name(), $this->get_version() );
		//$safes_admin = new Safes( $this->get_plugin_name(), $this->get_version() );
		$safedrops_admin = new Safe_Drops( $this->get_plugin_name(), $this->get_version() );
		$merchantdash_admin = new Pinaka_Merchant_Dashboard( $this->get_plugin_name(), $this->get_version());
		$logincustomize_admin = new Pinaka_Login_Customizer( $this->get_plugin_name(), $this->get_version());
		$dynamic_price_admin = new WC_Dynamic_Time_Pricing( $this->get_plugin_name(), $this->get_version() );
		$multipack_admin = new Pinaka_Multipack_Discount( $this->get_plugin_name(), $this->get_version() );
		$reports_admin = new Pinaka_Reports( $this->get_plugin_name(), $this->get_version() );	
		$warehouse_admin = new Warehouse( $this->get_plugin_name(), $this->get_version() );	
		$discounts_admin = new Pinaka_POS_Discounts( $this->get_plugin_name(), $this->get_version() );
		$mix_match_discounts_admin = new Pinaka_POS_Mix_Match_Discounts( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'Pinaka_POS_setup_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'handle_registration' );
		$this->loader->add_action( 'plugins_loaded', $this, 'init_vendor' );
		
		// ✅ Ensure the vendor post type is registered early
		$this->loader->add_action('admin_menu', $multipack_admin, 'register_menu');

		$this->loader->add_action('admin_menu', $reports_admin, 'register_menu');

		$this->loader->add_action('admin_menu', $vendor_admin, 'register_vendor_post_type');

		$this->loader->add_action('admin_menu', $shifts_admin, 'register_shifts_post_type');

		$this->loader->add_action('admin_menu', $payments_admin, 'register_payments_post_type');

		$this->loader->add_action('admin_menu', $vendor_payments_admin, 'register_vendor_payments_post_type');

		$this->loader->add_action('admin_menu', $fast_keys_admin, 'register_fast_keys_post_type');

		$this->loader->add_action('admin_menu', $discounts_admin, 'register_discounts_post_type');

		$this->loader->add_action('admin_menu', $mix_match_discounts_admin, 'register_mix_match_discounts_post_type');

		$this->loader->add_action('admin_menu', $warehouse_admin, 'register_warehouse_post_type');

		//$this->loader->add_action('admin_menu', $safes_admin, 'register_safes_post_type');
		
		$this->loader->add_action('admin_menu', $safedrops_admin, 'register_safedrops_post_type');

		$this->loader->add_action('admin_menu', $this, 'pinaka_hide_admin_menu_items', 999);
		$this->loader->add_action('admin_menu', $this, 'pinaka_replace_woocommerce_menu', 1000);


		$this->loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_scripts');
		$this->loader->add_action('admin_enqueue_scripts', $this,  'enqueue_admin_media');
		$this->loader->add_action('wp_ajax_pinaka_product_search', $this, 'pinaka_product_search_callback');
		$this->loader->add_action('wp_ajax_multipack_search_products', $this, 'pinaka_multipack_search_products');

		$this->loader->add_action( 'wp_ajax_pinaka_manage_fastkey_images', $this, 'pinaka_manage_fastkey_images' );
		$this->loader->add_action( 'wp_ajax_pinaka_toggle_fastkey_status', $this, 'pinaka_toggle_fastkey_status' );

		$this->loader->add_action('wp_ajax_update_admin_color_scheme', $this, 'update_admin_color_scheme' );
		$this->loader->add_action('wp_ajax_save_pinaka_pos_menu_settings', $this, 'save_pinaka_pos_menu_settings');
		$this->loader->add_action('admin_init', $this, 'add_custom_variation_field_to_woocomarce_product');

		$this->loader->add_action('admin_init', $this, 'add_employee_order_caps', 999);
		$this->loader->add_action('init', $this, 'pinaka_customize_manager_employee_caps');

		$this->loader->add_action('wp_ajax_create_custom_role', $this, 'create_custom_role');

		$this->loader->add_action('admin_init', $this, 'save_tube_safe_cash');
		$this->loader->add_action('admin_init', $this, 'pinaka_create_pos_default_category');
		// $this->loader->add_action('admin_init', $this, 'pinaka_pos_register_settings');
		$cashback_settings = get_option('pinaka_pos_cashback_settings');


		if ( isset( $cashback_settings['enabled'] ) && $cashback_settings['enabled'] ) {
			$cahsback_id = $this->pinaka_get_product_by_title('Cashback');
			if ( ! $cahsback_id ) {
				$cahsback_id = wp_insert_post([
					'post_title'   => 'Cashback',
					'post_type'    => 'product',
					'post_status'  => 'publish',
					'post_content' => 'This product is used internally for cashback transactions.',
					'meta_input'   => [
						'_price'              => 0,
						'_regular_price'      => 0,
						'_virtual'            => 'yes',
						'_downloadable'       => 'no',
						'_catalog_visibility' => 'hidden',
						'_tax_status'         => 'none', // ✅ Non-taxable
					],
				]);

				if ( $cahsback_id && ! is_wp_error($cahsback_id) ) {
					require_once ABSPATH . 'wp-admin/includes/media.php';
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/image.php';

					$image_url = plugins_url('/images/payout.png', __FILE__);
					$attach_id = media_sideload_image($image_url, $cahsback_id, 'Payout Image', 'id');

					if ( ! is_wp_error($attach_id) ) {
						set_post_thumbnail($cahsback_id, $attach_id);
					}

					// Ensure tax status is non-taxable
					update_post_meta($cahsback_id, '_tax_status', 'none');
				}
			}
			if ( $cahsback_id && ! is_wp_error($cahsback_id) ) {
				update_option('pinaka_cashback_product_id', $cahsback_id);
			}
			
			// ✅ Assign POS Default Category (NEW PART)
			$pos_cat_id = (int) get_option( 'pinaka_pos_default_category_id', 0 );

			if ( $cahsback_id && ! is_wp_error( $cahsback_id ) && $pos_cat_id ) {
				wp_set_object_terms( $cahsback_id, [ $pos_cat_id ], 'product_cat', true );
			}

			update_option( 'pinaka_cashback_product_id', $cahsback_id );
		}
		// Create or get internal "Payout" product
		$payout_id = $this->pinaka_get_product_by_title('Payout');
		if ( ! $payout_id ) {
			$payout_id = wp_insert_post([
				'post_title'   => 'Payout',
				'post_type'    => 'product',
				'post_status'  => 'publish',
				'post_content' => 'This product is used internally for payouts.',
				'meta_input'   => [
					'_price'              => 0,
					'_regular_price'      => 0,
					'_virtual'            => 'yes',
					'_downloadable'       => 'no',
					'_catalog_visibility' => 'hidden',
					'_tax_status'         => 'none', // ✅ Non-taxable
				],
			]);

			if ( $payout_id && ! is_wp_error($payout_id) ) {
				require_once ABSPATH . 'wp-admin/includes/media.php';
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/image.php';

				$image_url = plugins_url('/images/payout.png', __FILE__);
				$attach_id = media_sideload_image($image_url, $payout_id, 'Payout Image', 'id');

				if ( ! is_wp_error($attach_id) ) {
					set_post_thumbnail($payout_id, $attach_id);
				}

				// Ensure tax status is non-taxable
				update_post_meta($payout_id, '_tax_status', 'none');
			}

			// ✅ Assign POS Default Category (NEW PART)
			$pos_cat_id = (int) get_option( 'pinaka_pos_default_category_id', 0 );

			if ( $payout_id && ! is_wp_error( $payout_id ) && $pos_cat_id ) {
				wp_set_object_terms( $payout_id, [ $pos_cat_id ], 'product_cat', true );
			}
		}

		if ( $payout_id && ! is_wp_error($payout_id) ) {
			update_option('pinaka_payout_product_id', $payout_id);
		}


		// ✅ Create or get internal "Discount" product
		$discount_id = $this->pinaka_get_product_by_title('Discount');
		if ( ! $discount_id ) {
			$discount_id = wp_insert_post([
				'post_title'   => 'Discount',
				'post_type'    => 'product',
				'post_status'  => 'publish',
				'post_content' => 'This product is used internally for discounts or promotions.',
				'meta_input'   => [
					'_price'              => 0,
					'_regular_price'      => 0,
					'_virtual'            => 'yes',
					'_downloadable'       => 'no',
					'_catalog_visibility' => 'hidden',
					'_tax_status'         => 'none', // ✅ Non-taxable
				],
			]);

			if ( $discount_id && ! is_wp_error($discount_id) ) {
				require_once ABSPATH . 'wp-admin/includes/media.php';
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/image.php';

				$image_url = plugins_url('/images/discount.png', __FILE__); // You can change path or reuse payout.png
				$attach_id = media_sideload_image($image_url, $discount_id, 'Discount Image', 'id');

				if ( ! is_wp_error($attach_id) ) {
					set_post_thumbnail($discount_id, $attach_id);
				}

				update_post_meta($discount_id, '_tax_status', 'none');
			}

			// ✅ Assign POS Default Category (NEW PART)
			$pos_cat_id = (int) get_option( 'pinaka_pos_default_category_id', 0 );

			if ( $discount_id && ! is_wp_error( $discount_id ) && $pos_cat_id ) {
				wp_set_object_terms( $discount_id, [ $pos_cat_id ], 'product_cat', true );
			}
		}

		if ( $discount_id && ! is_wp_error($discount_id) ) {
			update_option('pinaka_discount_product_id', $discount_id);
		}

		$is_enabled = get_option('pinaka_pos_enable_loyalty_points', 'no');
        if ($is_enabled === 'yes') 
		{
			$loyalty_id = $this->pinaka_get_product_by_title('Loyalty');
			if ( ! $loyalty_id ) {
				$loyalty_id = wp_insert_post([
					'post_title'   => 'Loyalty',
					'post_type'    => 'product',
					'post_status'  => 'publish',
					'post_content' => 'This product is used internally for loyalty points.',
					'meta_input'   => [
						'_price'              => 0,
						'_regular_price'      => 0,
						'_virtual'            => 'yes',
						'_downloadable'       => 'no',
						'_catalog_visibility' => 'hidden',
						'_tax_status'         => 'none', // ✅ Non-taxable
					],
				]);

				if ( $loyalty_id && ! is_wp_error($loyalty_id) ) {
					require_once ABSPATH . 'wp-admin/includes/media.php';
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/image.php';

					$image_url = plugins_url('/images/loyalty.jpeg', __FILE__); // You can change path or reuse loyalty.png
					$attach_id = media_sideload_image($image_url, $loyalty_id, 'Loyalty Image', 'id');

					if ( ! is_wp_error($attach_id) ) {
						set_post_thumbnail($loyalty_id, $attach_id);
					}

					update_post_meta($loyalty_id, '_tax_status', 'none');
				}

				// ✅ Assign POS Default Category (NEW PART)
				$pos_cat_id = (int) get_option( 'pinaka_pos_default_category_id', 0 );

				if ( $loyalty_id && ! is_wp_error( $loyalty_id ) && $pos_cat_id ) {
					wp_set_object_terms( $loyalty_id, [ $pos_cat_id ], 'product_cat', true );
				}
			}

			if ( $loyalty_id && ! is_wp_error($loyalty_id) ) {
				update_option('pinaka_discount_loyalty_id', $loyalty_id);
			}
		}
		$this->pinaka_manage_fastkey_images();
	}	


	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		delete_option('pinaka_employee_caps_added');	
		
		$plugin_public           = new Pinaka_POS_Public( $this->get_plugin_name(), $this->get_version() );
		
        $deleted_data_controller = new Pinaka_Deleted_Data_Controller();
		$modify_query_controller = new Pinaka_Modify_Query_Controller();
		$order_stock_controller  = new Pinaka_Order_Stock_Controller();
        

		$this->loader->add_action( 'admin_notices', $plugin_public, 'check_required_plugins' );

		$this->loader->add_action( 'rest_api_init', $plugin_public, 'add_Pinaka_POS_api_routes' );
		$this->loader->add_action( 'woocommerce_new_order', $plugin_public, 'new_order_notification', 10, 2 );
		
        $this->loader->add_action( 'pinakapos_customer_billing_added', $plugin_public, 'new_customer_billing_added', 10, 1 );

		$this->loader->add_action( 'woocommerce_after_order_object_save', $modify_query_controller, 'calculate_profit_order', 10, 2 );
		// $this->loader->add_action( 'woocommerce_after_order_object_save', $this, 'pinaka_fix_payout_discount_after_order_save', 20, 2 );

		$this->loader->add_action( 'woocommerce_refund_created', $modify_query_controller, 'new_refund_created', 10, 2 );

		$this->loader->add_filter( 'woocommerce_rest_prepare_report_sales', $modify_query_controller, 'get_custom_wc_report_sale', 10, 3 );
		$this->loader->add_filter( 'woocommerce_rest_prepare_shop_order_object', $modify_query_controller, 'custom_wc_rest_prepare_shop_order_object', 10, 1 );
		$this->loader->add_filter( 'woocommerce_rest_prepare_shop_order_refund_object', $modify_query_controller, 'custom_wc_rest_prepare_shop_order_object', 10, 1 );
		$this->loader->add_filter( 'woocommerce_rest_prepare_product_cat', $modify_query_controller, 'get_custom_wc_cat_child_count', 10, 3 );

		$this->loader->add_filter( 'rest_post_dispatch', $modify_query_controller, 'Plain_Text_Errors', 10, 3 );

		$this->loader->add_filter( 'woocommerce_reports_get_order_report_data_args', $modify_query_controller, 'get_wc_order_report_data_args', 10, 1 );
		$this->loader->add_filter( 'upload_mimes', $this, 'my_own_mime_types' );

		$this->loader->add_action( 'delete_term', $deleted_data_controller, 'save_deleted_term', 10, 5 );
		$this->loader->add_action( 'after_delete_post', $deleted_data_controller, 'save_deleted_post', 10, 2 );
		$this->loader->add_action( 'delete_attachment', $deleted_data_controller, 'save_deleted_post', 10, 2 );
		$this->loader->add_action( 'delete_user', $deleted_data_controller, 'save_deleted_user', 10, 1 );
		$this->loader->add_action( 'woocommerce_tax_rate_deleted', $deleted_data_controller, 'save_deleted_tax', 10, 1 );
		$this->loader->add_action( 'woocommerce_attribute_deleted', $deleted_data_controller, 'save_deleted_attribute', 10, 3 );

		$this->loader->add_action( 'woocommerce_attribute_updated', $modify_query_controller, 'update_last_update_column', 10, 3 );

		
		// 1. Add the field to coupon edit screen
		$this->loader->add_action( 'woocommerce_coupon_options', $this, 'woocommerce_wp_checkbox_coupon');

		// 2. Save the field when coupon is updated
		$this->loader->add_action( 'woocommerce_coupon_options_save', $this, 'update_wooc_checkbox_coupon' );
		/**
		 * Allow negative order totals for orders with payout fees
		 */
		$this->loader->add_filter('woocommerce_order_get_total', $this, 'allow_negative_order_totals_for_payout', 10, 2);
		$this->loader->add_filter( 'woocommerce_rest_product_query', $modify_query_controller, 'add_modified_after_filter_to_post', 10, 2 );
		$this->loader->add_filter( 'woocommerce_rest_orders_prepare_object_query', $modify_query_controller, 'add_modified_after_filter_to_post', 10, 2 );
		$this->loader->add_filter( 'woocommerce_rest_product_variation_query', $modify_query_controller, 'add_modified_after_filter_to_post', 10, 2 );
		$this->loader->add_filter( 'woocommerce_rest_product_cat_query', $modify_query_controller, 'add_modified_after_filter_to_meta', 10, 2 );
		$this->loader->add_filter( 'woocommerce_rest_product_tag_query', $modify_query_controller, 'add_modified_after_filter_to_meta', 10, 2 );
		$this->loader->add_filter( 'woocommerce_rest_customer_query', $modify_query_controller, 'add_customer_filter_to_meta', 10, 2 );
		$this->loader->add_filter( 'woocommerce_rest_orders_prepare_object_query', $modify_query_controller, 'add_shop_order_filter_to_meta', 10, 2 );

		$this->loader->add_filter( 'woocommerce_customer_get_order_count', $modify_query_controller, 'custom_customer_get_order_count', 10, 2 );
		$this->loader->add_filter( 'woocommerce_customer_get_total_spent', $modify_query_controller, 'custom_customer_get_total_spent', 10, 2 );

		$this->loader->add_action( 'woocommerce_rest_insert_shop_order_object', $modify_query_controller, 'add_post_author_to_order', 10, 2 );

		$this->loader->add_action( 'woocommerce_product_options_pricing', $modify_query_controller, 'add_custom_purchase_price_woocommerce', 10, 2 );
		$this->loader->add_action( 'woocommerce_process_product_meta', $modify_query_controller, 'save_custom_purchase_price_woocommerce', 10, 2 );

		$this->loader->add_filter( 'edit_terms', $modify_query_controller, 'add_modified_date_terms_meta', 10, 1 );
		$this->loader->add_filter( 'create_term', $modify_query_controller, 'add_modified_date_terms_meta', 10, 1 );

		$this->loader->add_filter( 'jwt_auth_whitelist', $plugin_public, 'white_listing_endpoints' );

		$auth    = new Pinaka_POS_Auth();

		$this->loader->add_action( 'rest_api_init', $auth, 'register_rest_routes' );
		$this->loader->add_filter( 'rest_api_init', $auth, 'add_cors_support' );
		$this->loader->add_filter( 'rest_pre_dispatch', $auth, 'rest_pre_dispatch', 10, 3 );
		$this->loader->add_filter( 'determine_current_user', $auth, 'determine_current_user' );

		$this->loader->add_action( 'init', $modify_query_controller, 'woocommerce_stock_amount_filters', 999999 );
		$this->loader->add_filter( 'woocommerce_rest_shop_order_schema', $modify_query_controller, 'rest_shop_order_schema' );
		$this->loader->add_filter( 'woocommerce_rest_product_schema', $modify_query_controller, 'rest_product_schema' );
		$this->loader->add_filter( 'woocommerce_rest_product_variation_schema', $modify_query_controller, 'rest_product_schema' );
		$this->loader->add_filter( 'posts_where', $modify_query_controller, 'add_search_criteria_to_wp_query_where', 20, 1 );
		$this->loader->add_filter( 'woocommerce_get_catalog_ordering_args', $modify_query_controller, 'stock_status_value_on_order_item_view' );

		$this->loader->add_action( 'woocommerce_variation_options_pricing', $modify_query_controller, 'add_variation_options_pricing', 10, 3 );
		$this->loader->add_action( 'woocommerce_save_product_variation', $modify_query_controller, 'save_variation_options_pricing', 10, 2 );
		$this->loader->add_filter( 'woocommerce_available_variation', $modify_query_controller, 'add_custom_field_variation_data', 10, 1 );
        $this->loader->add_filter( 'woocommerce_reports_get_order_report_query', $modify_query_controller, 'add_custom_order_report_query', 10, 1 );

		// supplier module.
		$this->loader->add_filter( 'woocommerce_register_shop_order_post_statuses', $plugin_public, 'add_custom_shop_order_post_statuses', 10, 1 );
		$this->loader->add_filter( 'woocommerce_order_status_changed', $order_stock_controller, 'update_order_status_callback', 10, 3 );
		$this->loader->add_filter( 'mp_order_item_removed', $order_stock_controller, 'mp_order_item_removed_callbacked', 10, 2 );
		// Hook to initialize meta field
		$this->loader->add_action('init', $this, 'register_emp_login_pin_meta');
		// Hook to save the field when updated
		$this->loader->add_action('personal_options_update', $this, 'save_emp_login_pin_field');
		$this->loader->add_action('edit_user_profile_update', $this, 'save_emp_login_pin_field');

		// Hook to display the field in user profile
		$this->loader->add_action('show_user_profile', $this, 'add_emp_login_pin_field');
		$this->loader->add_action('edit_user_profile', $this, 'add_emp_login_pin_field');

		$this->loader->add_action('wp_ajax_save_denominations', $this, 'save_denominations');
		$this->loader->add_action('wp_ajax_save_safedrop_denominations', $this, 'save_safedrop_denominations');
		$this->loader->add_action('wp_ajax_save_coins_denominations', $this, 'save_coins_denominations');
		$this->loader->add_action('wp_ajax_save_safe_denominations', $this, 'save_safe_denominations');

		$this->loader->add_action('save_post', $this, 'log_post_save', 10, 3);
		$this->loader->add_action( 'pre_get_posts',$this, 'force_title_only_search_for_products_rest_api' );

		// Add meta box to coupon edit screen
		$this->loader->add_action( 'add_meta_boxes', $this, 'add_coupon_meta_box');

		// Save the selected required product IDs
		$this->loader->add_action( 'save_post_shop_coupon', $this, 'save_required_product_ids' );

		$this->loader->add_action( 'product_cat_add_form_fields', $this, 'myplugin_add_sequence_field_to_category', 10, 2 );
		$this->loader->add_action( 'product_cat_edit_form_fields', $this, 'myplugin_edit_sequence_field_in_category', 10, 2 );

		$this->loader->add_action( 'created_product_cat', $this, 'myplugin_save_category_sequence_meta', 10, 2 );
		$this->loader->add_action( 'edited_product_cat', $this,  'myplugin_save_category_sequence_meta', 10, 2 );

		$this->loader->add_filter('manage_users_columns', $this, 'my_add_custom_user_column');

		$this->loader->add_action('manage_users_custom_column', $this, 'my_show_custom_user_column_content', 10, 3);
		// $this->loader->add_filter( 'woocommerce_order_item_coupon_discount_amount', $this, 'pinaka_block_coupon_on_payout_product', 20, 5 );
		$this->loader->add_action( 'wp_ajax_save_promotion_images', $this,  'pinaka_save_promotion_images' );

		$this->loader->add_action( 'woocommerce_rest_insert_shop_order_object', $this, 'recalculate_order_totals_on_update_rest', 10, 1 );
		// $this->loader->add_action('woocommerce_rest_insert_shop_order_object', $this, 'pinaka_api_apply_mix_match_simple_discount', 20,3 );

		$this->loader->add_filter('rest_prepare_user', $this, 'get_user_meta_for_rest_api', 10, 3);

		$this->loader->add_filter('woocommerce_rest_prepare_product_cat', $this, 'decode_category_name', 10, 3);
        add_action('manage_shop_order_posts_custom_column', 'pinaka_show_offline_order_column_classic', 20, 2);
        add_action('woocommerce_shop_order_list_table_custom_column', 'pinaka_show_offline_order_column_hpos', 20, 2);
        
		$this->loader->add_action('woocommerce_after_product_object_save',$this, 'pos_create_variations_from_meta', 30, 2);
		// add_action( 'wp_loaded', function() {
		// 	$roles_to_remove = [
		// 		'eac_accountant',
		// 		'eac_manager',
		// 		'vtpos-outlet-manager',
		// 		'vtpos-cashier',
		// 		'supplier',
		// 		'employee',
		// 		'captain',
		// 		'shopkeeper',
		// 		'merchant',
		// 		'manager',
		// 		'shopmanager',
		// 		'shop_keeper',
		// 		'cashier'
		// 	];

		// 	foreach ( $roles_to_remove as $role ) {
		// 		if ( get_role( $role ) ) {
		// 			remove_role( $role );
		// 		}
		// 	}
		// });	

		$this->loader->add_filter( 'woocommerce_rest_pre_insert_product_object', $this, 'pinaka_delete_trashed_product_with_same_sku', 10, 2 );
		$this->loader->add_filter( 'woocommerce_rest_pre_insert_product_object', $this, 'pinaka_rest_pre_insert_product_image', 10, 3 );
		$this->loader->add_filter('rest_product_collection_params', $this, 'maximum_api_filter');
		// Rename Order column label to Woo Order ID (HPOS + Classic)
    	add_filter('manage_edit-shop_order_columns', 'pinaka_rename_order_column', 20);
    	add_filter('woocommerce_shop_order_list_table_columns', 'pinaka_rename_order_column', 20);
        /* 1️⃣ Add Column */
        add_filter('manage_edit-shop_order_columns', 'pinaka_add_offline_order_column', 20);
        add_filter('woocommerce_shop_order_list_table_columns', 'pinaka_add_offline_order_column', 20);

        function pinaka_add_offline_order_column($columns) {
        
            $new_columns = [];
        
            foreach ($columns as $key => $label) {
        
                $new_columns[$key] = $label;
        
                // Add after Order column
                if ($key === 'order_number' || $key === 'title') {
                    $new_columns['offline_order_id'] = 'Offline Order ID';
                }
            }
        
            return $new_columns;
        }
        
        function pinaka_show_offline_order_column_classic($column, $post_id) {
        
            if ($column === 'offline_order_id') {
        
                $offline_id = get_post_meta($post_id, '_pos_client_order_id', true);
        
                echo !empty($offline_id) ? esc_html($offline_id) : '—';
            }
        }
        
        function pinaka_show_offline_order_column_hpos($column, $order) {
        
            if ($column === 'offline_order_id') {
        
                if (is_a($order, 'WC_Order')) {
                    $offline_id = $order->get_meta('_pos_client_order_id');
                    echo !empty($offline_id) ? esc_html($offline_id) : '—';
                }
            }
        }
        
        function pinaka_rename_order_column($columns) {
            
        	// HPOS
            if (isset($columns['order_number'])) {
                $columns['order_number'] = 'Woo Order ID';
            }
                
            // Classic fallback
            if (isset($columns['title'])) {
            $columns['title'] = 'Woo Order ID';
            }
                return $columns;
            }
            
    }

	function pinaka_create_pos_default_category() {

		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return;
		}

		$category_name = 'Default';
		$category_slug = 'default';

		// Check if category already exists
		$existing_term = term_exists( $category_name, 'product_cat' );

		if ( $existing_term ) {
			return;
		}

		// Create category
		$term = wp_insert_term(
			$category_name,
			'product_cat',
			[
				'slug' => $category_slug,
			]
		);

		if ( is_wp_error( $term ) ) {
			return;
		}

		// (Optional but recommended) Save term ID for later use
		update_option( 'pinaka_pos_default_category_id', (int) $term['term_id'] );
	}

	function decode_category_name($response, $item, $request) {
		if (isset($response->data['name'])) {
			$response->data['name'] = html_entity_decode(
				$response->data['name'],
				ENT_QUOTES | ENT_HTML5,
				'UTF-8'
			);
		}
		return $response;
	}

	function pos_create_variations_from_meta( $product, $data_store ) {

		if ( ! $product instanceof WC_Product_Variable ) {
			return;
		}

		$product_id = $product->get_id();
		$created_ids =  [];
		if ( get_post_meta( $product_id, '_pos_variations_processing', true ) === 'yes' ) {
			return;
		}

		$payload = get_post_meta( $product_id, '_pos_variations_payload', true );
		if ( empty( $payload ) || ! is_array( $payload ) ) {
			return;
		}

		update_post_meta( $product_id, '_pos_variations_processing', 'yes' );

		$this->pos_prepare_dynamic_attributes( $product, $payload );
		$product->save();

		$this->pos_force_parent_product_attributes( $product_id, $payload );

		$existing = [];

		foreach ( $product->get_children() as $vid ) {
			$attrs = $this->pos_get_variation_attributes_from_postmeta( $vid );
			if ( $attrs ) {
				$existing[ md5( json_encode( $attrs ) ) ] = $vid;
			}
		}

		foreach ( $payload as $row ) {

			if ( empty( $row['attributes'] ) ) {
				continue;
			}

			$variation_attrs = [];

			foreach ( $row['attributes'] as $attr_name => $value ) {
				$norm = $this->pos_normalize_attribute_and_term( $attr_name, $value );
				$taxonomy = $norm['taxonomy'];

				if ( ! taxonomy_exists( $taxonomy ) ) {
					continue;
				}
				$variation_attrs[ $norm['taxonomy'] ] = $norm['term_slug'];
			}

			ksort( $variation_attrs );
			$hash = md5( json_encode( $variation_attrs ) );

			if ( isset( $existing[ $hash ] ) ) {
				$variation = wc_get_product( $existing[ $hash ] );
			} else {
				$variation = new WC_Product_Variation();
				$variation->set_parent_id( $product_id );
				$variation->set_attributes( $variation_attrs );
			}

			if ( isset( $row['regular_price'] ) && $row['regular_price'] !== '' ) {
				$variation->set_regular_price( wc_format_decimal( $row['regular_price'] ) );
			}

			if ( isset( $row['sale_price'] ) && $row['sale_price'] !== '' ) {
				$variation->set_sale_price( wc_format_decimal( $row['sale_price'] ) );
			}

			$variation->set_manage_stock( true );
			$variation->set_stock_quantity( (int) ( $row['stock'] ?? 0 ) );

			$variation->save();

			foreach ( $variation_attrs as $taxonomy => $term_slug ) {
				update_post_meta(
					$variation->get_id(),
					'attribute_' . $taxonomy,
					$term_slug
				);
				$created_ids[] = $variation->get_id();
			}
			if ( ! empty( $row['image']['src'] ) ) {
				$this->pos_set_variation_image_from_url(
					$variation->get_id(),
					esc_url_raw( $row['image']['src'] )
				);
			}
			$existing[ $hash ] = $variation->get_id();
		}
		// update_post_data($product_id, '_updated_ids', $created_ids);
		delete_post_meta( $product_id, '_pos_variations_payload' );
		delete_post_meta( $product_id, '_pos_variations_processing' );
		update_post_meta( $product_id, '_pos_variations_processed', 'yes' );
	}
	function pos_get_variation_attributes_from_postmeta( $variation_id ) {

		$meta  = get_post_meta( $variation_id );
		$attrs = [];

		foreach ( $meta as $key => $value ) {
			if ( strpos( $key, 'attribute_pa_' ) === 0 ) {
				$taxonomy = str_replace( 'attribute_', '', $key );
				$attrs[ $taxonomy ] = $value[0];
			}
		}

		ksort( $attrs );
		return $attrs;
	}
	function pos_force_parent_product_attributes( $product_id, array $payload ) {

		$product_attributes = [];
		$position = 0;

		foreach ( $payload as $row ) {

			if ( empty( $row['attributes'] ) ) {
				continue;
			}

			foreach ( $row['attributes'] as $attr_name => $value ) {

				$taxonomy = $attr_name;
				if ( ! taxonomy_exists( $taxonomy ) ) {
					continue;
				}

				if ( isset( $seen[ $taxonomy ] ) ) {
					continue;
				}

				$seen[ $taxonomy ] = true;

				$product_attributes[ $taxonomy ] = [
					'name'         => $taxonomy,
					'value'        => '',
					'position'     => $position++,
					'is_visible'   => 1,
					'is_variation' => 1,
					'is_taxonomy'  => 1,
				];
			}
		}

		update_post_meta( $product_id, '_product_attributes', $product_attributes );
	}
	function pos_prepare_dynamic_attributes( WC_Product_Variable $product, array $payload ) {

		$product_id = $product->get_id();
		$attributes = [];

		foreach ( $payload as $row ) {

			if ( empty( $row['attributes'] ) || ! is_array( $row['attributes'] ) ) {
				continue;
			}

			foreach ( $row['attributes'] as $attr_name => $value ) {

				$norm     = $this->pos_normalize_attribute_and_term( $attr_name, $value );
				$taxonomy = $norm['taxonomy'];

				if ( ! taxonomy_exists( $taxonomy ) ) {
					continue;
				}

				if ( ! term_exists( $norm['term_slug'], $taxonomy ) ) {
					wp_insert_term(
						$norm['term_name'],
						$taxonomy,
						[ 'slug' => $norm['term_slug'] ]
					);
				}
				wp_set_object_terms(
					$product_id,
					$norm['term_slug'],
					$taxonomy,
					true
				);

				if ( ! isset( $attributes[ $taxonomy ] ) ) {

					$attr = new WC_Product_Attribute();
					$attr->set_name( $taxonomy );
					$attr->set_visible( true );
					$attr->set_variation( true );
					$attr->set_options( [ $norm['term_slug'] ] );

					$attributes[ $taxonomy ] = $attr;

				} else {

					$opts   = $attributes[ $taxonomy ]->get_options();
					$opts[] = $norm['term_slug'];
					$attributes[ $taxonomy ]->set_options( array_unique( $opts ) );
				}
			}
		}

		$product->set_attributes( $attributes );
	}
	function pos_normalize_attribute_and_term( $attribute, $value ) {
		return [
			'attribute_slug' => trim( $attribute ),
			'taxonomy'       => trim( $attribute ),
			'term_slug'      => trim( $value ),
			'term_name'      => trim( $value ),
		];
	}
	function pos_set_variation_image_from_url( $variation_id, $image_url ) {

		if ( empty( $image_url ) ) {
			return;
		}

		$attachment_id = attachment_url_to_postid( $image_url );
		if ( ! $attachment_id ) {
			return;
		}

		$current_id = get_post_thumbnail_id( $variation_id );

		if ( (int) $current_id === (int) $attachment_id ) {
			return;
		}

		set_post_thumbnail( $variation_id, $attachment_id );
	}
	function get_user_meta_for_rest_api($response, $user, $request) {

    if (!isset($response->data['meta'])) {
        $response->data['meta'] = [];
    }

    $response->data['meta']['emp_login_pin'] = get_user_meta($user->ID, 'emp_login_pin', true);
    $response->data['meta']['phone'] = get_user_meta($user->ID, 'phone', true);

	// remove wordpress meta
    unset($response->data['meta']['persisted_preferences']);

    return $response;
}



	function pinaka_api_apply_mix_match_simple_discount($order, $request, $creating) {

		if (!$order instanceof WC_Order) {
			return;
		}

		/* ---------------------------------------
		* RESET PREVIOUS MIX & MATCH
		* --------------------------------------- */
		foreach ($order->get_items() as $item) {

			if ($item->get_meta('_pinaka_mix_match_applied') === 'yes') {

				$original_subtotal = $item->get_meta('_pinaka_original_subtotal');

				if ($original_subtotal !== '') {
					$item->set_subtotal($original_subtotal);
					$item->set_total($original_subtotal);
				}

				$item->delete_meta_data('Discount Type');
				$item->delete_meta_data('Discount Applied');
				$item->delete_meta_data('Discounted Unit Price');

				$item->save();
			}
		}

		/* ---------------------------------------
		* BUILD PRODUCT LIST
		* --------------------------------------- */
		$product_ids = [];
		foreach ($order->get_items() as $item) {
			$product_ids[] = (int) $item->get_product_id();
		}

		/* ---------------------------------------
		* APPLY MIX & MATCH
		* --------------------------------------- */
		$discounts = get_posts([
			'post_type'   => 'mix_match_discounts',
			'post_status' => 'publish',
			'numberposts' => -1,
		]);

		foreach ($discounts as $discount) {

			$parent_id = (int) get_post_meta($discount->ID, '_mix_match_parent_product_id', true);
			$child_ids = (array) get_post_meta($discount->ID, '_mix_match_child_product_ids', true);

			$type   = get_post_meta($discount->ID, '_mix_match_discount_type', true);
			$amount = (float) get_post_meta($discount->ID, '_mix_match_discount_amount', true);

			if (!$parent_id || !in_array($parent_id, $product_ids, true)) {
				continue;
			}

			foreach ($order->get_items() as $item) {

				if (!in_array($item->get_product_id(), $child_ids, true)) {
					continue;
				}

				$qty = max(1, $item->get_quantity());

				if ($item->get_meta('_pinaka_original_subtotal') === '') {
					//$item->add_meta_data('_pinaka_original_subtotal', $item->get_subtotal(), true);
				}

				$unit_price = $item->get_subtotal() / $qty;

				$new_price = ($type === 'percent')
					? $unit_price - ($unit_price * ($amount / 100))
					: $unit_price - $amount;

				$new_price = max($new_price, 0);

				$item->set_subtotal($new_price * $qty);
				$item->set_total($new_price * $qty);

				//$item->add_meta_data('_pinaka_mix_match_applied', 'yes', true);
				//$item->add_meta_data('_pinaka_mix_match_discount_id', $discount->ID, true);

				$item->add_meta_data('Discount Type', 'Combo Discount', true);
				$item->add_meta_data('Discount Applied', wc_format_decimal($amount), true);
				$item->add_meta_data('Discounted Unit Price', wc_format_decimal($new_price), true);

				$item->save();
			}
		}

		/* ---------------------------------------
		* 🔥 CRITICAL COUPON FIX
		* --------------------------------------- */

		// 1️⃣ Store applied coupons
		$applied_coupons = $order->get_coupon_codes();

		// 2️⃣ Remove coupons
		foreach ($applied_coupons as $code) {
			$order->remove_coupon($code);
		}

		// 3️⃣ Recalculate without coupons
		$order->calculate_totals(true);

		// 4️⃣ Re-apply coupons
		foreach ($applied_coupons as $code) {
			$order->apply_coupon($code);
		}

		$recovered_amount          = $this->pinaka_remove_existing_payout_lines( $order );
					
		$recovered_amount_discount = $this->pinaka_remove_existing_discount_lines( $order );

		// 3) If we have recovered amounts, add the payout/discount product lines (if product exists)
		if ( $recovered_amount ) {
			$payout_product_id = (int) get_option( 'pinaka_payout_product_id', 0 );
			if ( $payout_product_id && $p = wc_get_product( $payout_product_id ) ) {
				$order->add_product( $p, 1, [
					'subtotal' => $recovered_amount,
					'total'    => $recovered_amount,
				] );
			}
		}

		if ( $recovered_amount_discount ) {
			$discount_product_id = (int) get_option( 'pinaka_discount_product_id', 0 );
			if ( $discount_product_id && $p = wc_get_product( $discount_product_id ) ) {
				$order->add_product( $p, 1, [
					'subtotal' => $recovered_amount_discount,
					'total'    => $recovered_amount_discount,
				] );
			}
		}
		
		// 5️⃣ Final recalculation
		$order->calculate_totals(true);
		$order->save();
	}

	/**
	 * Remove any existing payout representations from the order (product or fee).
	 * Returns a recovered payout amount if we find one; otherwise 0.0.
	 */
	private function pinaka_remove_existing_payout_lines( $order ): float {
		$recovered = 0.0;
		// 1) Get Order meta _order_payout (if any)
		$meta_payout = $order->get_meta( '_order_payout', true );
		if ( is_numeric( $meta_payout ) ) {
			$recovered = floatval( $meta_payout );
		}

		// 2) Remove payout product lines (legacy representation)
		// If you used a fixed product ID, set it here; else rely on _is_payout meta.
		$payout_product_id = get_option( 'pinaka_payout_product_id', 0 ); // e.g. 9999 if you had one; else 0
		foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
			if ( ( $payout_product_id && $item->get_product_id() == $payout_product_id ) ) {
				$order->remove_item( $item_id );
			}
		}
		return $recovered;
	}


	/**
	 * Remove any existing payout representations from the order (product or fee).
	 * Returns a recovered payout amount if we find one; otherwise 0.0.
	 */
	private function pinaka_remove_existing_discount_lines( $order ): float {
		$recovered = 0.0;

		// 1) Get Order meta _order_payout (if any)
		$meta_discount = $order->get_meta( '_order_discount', true );
		if ( is_numeric( $meta_discount ) ) {
			$recovered = floatval( $meta_discount );
		}
		if($recovered == 0.0)
		{
			foreach ( $order->get_items( 'line_item' ) as $item ) {
				if($item->get_name() === 'Discount')
				{
					$recovered = $item->get_total();
					break;
				}
			}
			// return $recovered;
		}
		// 2) Remove payout product lines (legacy representation)
		// If you used a fixed product ID, set it here; else rely on _is_payout meta.
		$discount_product_id = get_option( 'pinaka_discount_product_id', 0 );; // e.g. 9999 if you had one; else 0
		foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
			if ( ( $discount_product_id && $item->get_product_id() == $discount_product_id ) ) {
				$order->remove_item( $item_id );
			}
		}
		return $recovered;
	}


	function maximum_api_filter($query_params) {
		$query_params['per_page']["maximum"]=100000;
		return $query_params;
	}

	function pinaka_rest_pre_insert_product_image( $product, $request, $creating ) {
		$images = $request->get_param( 'images' );
		if ( ! empty( $images ) ) {
			return $product;
		}
		$image_url =  plugins_url('/images/no_image.png', __FILE__);;
		if ( empty( $image_url ) ) {
			return $product;
		}

		// Include WP media functions
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Download and sideload the image
		$tmp = download_url( $image_url );
		if ( is_wp_error( $tmp ) ) {
			return $product;
		}

		$file = [
			'name'     => basename( parse_url( $image_url, PHP_URL_PATH ) ),
			'tmp_name' => $tmp
		];

		$attach_id = media_handle_sideload( $file, 0 );
		if ( is_wp_error( $attach_id ) ) {
			@unlink( $tmp );
			return $product;
		}

		// Set as featured image
		$product->set_image_id( $attach_id );

		return $product;
	}
	
	function pinaka_delete_trashed_product_with_same_sku( $product_object, $request ) {

		// Get SKU from incoming REST request
		$sku = '';
		if ( is_a( $request, 'WP_REST_Request' ) ) {
			$sku = $request->get_param( 'sku' ) ?: $request->get_param( '_sku' );
		} elseif ( is_array( $request ) ) {
			$sku = isset( $request['sku'] ) ? $request['sku'] : ( isset( $request['_sku'] ) ? $request['_sku'] : '' );
		}
		$sku = trim( (string) $sku );
		if ( '' === $sku ) {
			return $product_object;
		}

		// 1) Try the normal helper first
		$existing_id = wc_get_product_id_by_sku( $sku );

		// 2) If not found, explicitly search postmeta for _sku including trashed posts
		if ( ! $existing_id ) {
			$posts = get_posts( [
				'post_type'   => 'product',
				'post_status' => [ 'trash', 'publish', 'draft', 'pending', 'private' ], // include trash explicitly
				'meta_query'  => [
					[
						'key'   => '_sku',
						'value' => $sku,
					],
				],
				'numberposts' => 1,
			] );

			if ( ! empty( $posts ) ) {
				$existing_id = (int) $posts[0]->ID;
			}
		}

		// If found and it's trashed, force-delete it
		if ( $existing_id ) {
			$status = get_post_status( $existing_id );
			if ( 'trash' === $status ) {
				// permanently delete trashed product so REST create won't fail
				wp_delete_post( $existing_id, true ); // true = force delete
				// optional logging:
				// error_log( "Deleted trashed product ID {$existing_id} with SKU {$sku}" );
			}
		}

		return $product_object;
	}

	function recalculate_order_totals_on_update_rest( $order ) {
		// Ensure the order object is valid
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return;
		}
		// Calculate totals based on current line items, shipping, fees, etc.
		$order->calculate_totals();
		// Save the order to persist the new totals
		$order->save(); // It's good practice to save after calculation
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		$logger  = wc_get_logger();
		$context = [ 'source' => 'pinaka-order-recalc' ];

		$items_data = [];

		foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {

			$items_data[] = [
				'item_id'       => $item_id,
				'product_id'    => $item->get_product_id(),
				'variation_id'  => $item->get_variation_id(),
				'name'          => $item->get_name(),
				'qty'           => $item->get_quantity(),
				'unit_price'    => wc_format_decimal(
					$item->get_subtotal() / max( 1, $item->get_quantity() ),
					wc_get_price_decimals()
				),
				'subtotal'      => $item->get_subtotal(),
				'total'         => $item->get_total(),
				'subtotal_tax'  => $item->get_subtotal_tax(),
				'total_tax'     => $item->get_total_tax(),
				'taxes'         => $item->get_taxes(),
				'meta'          => $item->get_meta_data(),
			];
		}

		$logger->info(
			wp_json_encode(
				[
					'event'     => 'ORDER_RECALCULATED',
					'order_id'  => $order->get_id(),
					'status'    => $order->get_status(),
					'items'     => $items_data,
					'total'     => $order->get_total(),
					'tax_total' => $order->get_total_tax(),
				],
				JSON_PRETTY_PRINT
			),
			$context
		);
	}

	public function enqueue_admin_media() {
		wp_enqueue_media();
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
	 * Add Internal Coupon checkbox
	 */
	public function woocommerce_wp_checkbox_coupon( $coupon_id ) {
		woocommerce_wp_checkbox( array(
			'id'          => '_is_internal_coupon',
			'label'       => __( 'Mix & Match Coupon', 'pinaka-pos' ),
			'description' => __( 'Mark this coupon as a Mix & Match coupon (used only for combo discounts, hidden from customers and API).', 'pinaka-pos' ),
			'value'       => get_post_meta( $coupon_id, '_is_internal_coupon', true ) === 'yes' ? 'yes' : 'no',
		) );
	}

	/**
	 * Save Internal Coupon checkbox
	 */
	public function update_wooc_checkbox_coupon( $coupon_id ) {
		$is_internal = isset( $_POST['_is_internal_coupon'] ) && $_POST['_is_internal_coupon'] === 'yes' ? 'yes' : 'no';
		update_post_meta( $coupon_id, '_is_internal_coupon', $is_internal );
	}

	function allow_negative_order_totals_for_payout($total, $order) {
		foreach ($order->get_fees() as $fee) {
			if (strtolower($fee->get_name()) === 'payout' && $fee->get_total() < 0) {
				// Return the actual total without any capping
				return $total;
			}
		}
		return $total;
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Pinaka_POS_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
	
	/**
	 * Set the timezone for the plugin.
	 */
	function your_plugin_set_timezone() {
		$timezone = get_option( 'timezone_string' );

		if ( $timezone ) {
			date_default_timezone_set( $timezone );
		}
	}

	public function add_coupon_meta_box() {
		add_meta_box(
			'coupon_required_products',
			'Required Products (for Auto Apply)',
			[ $this, 'coupon_required_products_meta_box' ],
			'shop_coupon',
			'side',
			'default'
		);
	}


	public function coupon_required_products_meta_box( $post ) {
		$selected_products = get_post_meta( $post->ID, 'required_product_ids', true );
		if ( ! is_array( $selected_products ) ) {
			$selected_products = explode( ',', $selected_products );
		}

		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		$products = get_posts( $args );

		echo '<select multiple style="width:100%;" name="required_product_ids[]">';
		foreach ( $products as $product ) {
			$selected = in_array( $product->ID, $selected_products ) ? 'selected' : '';
			echo '<option value="' . esc_attr( $product->ID ) . '" ' . $selected . '>' . esc_html( $product->post_title ) . '</option>';
		}
		echo '</select>';

		echo '<p style="font-size: 12px; color: #666;">Coupon will auto-apply only when all selected products are in the cart or order.</p>';
	}


	function save_required_product_ids( $post_id ) {
		if ( isset( $_POST['required_product_ids'] ) ) {
			$clean_ids = array_map( 'absint', $_POST['required_product_ids'] );
			update_post_meta( $post_id, 'required_product_ids', $clean_ids );
		} else {
			delete_post_meta( $post_id, 'required_product_ids' );
		}
	}

	function force_title_only_search_for_products_rest_api( $query ) {
		// Only affect REST API and product search
		$post_type = $query->get( 'post_type' );
		$search_term = $query->get( 's' );

		if ( $post_type === 'product' && ! empty( $search_term ) ) {

			// Remove default search
			$query->set( 's', '' );

			// Custom title-only search
			add_filter( 'posts_search', function ( $search, $wp_query ) use ( $search_term ) {
				global $wpdb;

				if ( $wp_query->get( 'post_type' ) === 'product' ) {
					return $wpdb->prepare(
						" AND {$wpdb->posts}.post_title LIKE %s ",
						'%' . $wpdb->esc_like( $search_term ) . '%'
					);
				}
				return $search;
			}, 10, 2 );
		}
	
	}


	function myplugin_add_sequence_field_to_category( $taxonomy ) {
		?>
		<div class="form-field">
			<label for="product_cat_sequence"><?php _e( 'Sequence Number', 'your-textdomain' ); ?></label>
			<input type="number" name="product_cat_sequence" id="product_cat_sequence" value="" />
			<p class="description"><?php _e( 'Set the order in which this category should appear.', 'your-textdomain' ); ?></p>
		</div>
		<?php
	}

	function myplugin_edit_sequence_field_in_category( $term, $taxonomy ) {
		$sequence = get_term_meta( $term->term_id, 'product_cat_sequence', true );
		?>
		<tr class="form-field">
			<th scope="row"><label for="product_cat_sequence"><?php _e( 'Sequence Number', 'your-textdomain' ); ?></label></th>
			<td>
				<input type="number" name="product_cat_sequence" id="product_cat_sequence" value="<?php echo esc_attr( $sequence ); ?>" />
				<p class="description"><?php _e( 'Set the order in which this category should appear.', 'your-textdomain' ); ?></p>
			</td>
		</tr>
		<?php
	}

	function myplugin_save_category_sequence_meta( $term_id, $tt_id ) {
		if ( isset( $_POST['product_cat_sequence'] ) ) {
			update_term_meta( $term_id, 'product_cat_sequence', intval( $_POST['product_cat_sequence'] ) );
		}
	}
	/**
	 * Save the tube size, safe drop amount, and cash drawer amount settings.
	 */
	function save_tube_safe_cash(){
		if (isset($_POST['save_tube_safe_cash'])) {
			if (!isset($_POST['tube_safe_cash_nonce']) || !wp_verify_nonce($_POST['tube_safe_cash_nonce'], 'save_tube_safe_cash_settings')) {
				return;
			}
			update_option('tube_size', sanitize_text_field($_POST['tube_size']));
			update_option('safe_drop_amount', sanitize_text_field($_POST['safe_drop_amount']));
			update_option('cash_drawer_amount', sanitize_text_field($_POST['cash_drawer_amount']));
			update_option('no_of_payouts', sanitize_text_field($_POST['no_of_payouts']));
			update_option('currency_symbol', sanitize_text_field($_POST['currency_symbol']));
			update_option('enable_safes', sanitize_text_field($_POST['enable_safes']));
			update_option('enable_safes_drop', sanitize_text_field($_POST['enable_safes_drop']));
			add_action('admin_notices', function () {
				echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
			});
		}
	}

	/**
	 * log the transactions.
	 */
	function log_post_save($post_id, $post, $update) {
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

		$post_type = get_post_type($post_id);
		if ($post_type === 'revision') return;

		// Get post meta
		$meta = get_post_meta($post_id);

		// Combine post object and meta
		$data = [
			'post' => $post,
			'meta' => $meta
		];

		global $wpdb;
		$wpdb->insert($wpdb->prefix . 'post_full_logs', [
			'post_id'   => $post_id,
			'post_type'=> $post_type,
			'action'    => $update ? 'update' : 'create',
			'user_id'   => get_current_user_id(),
			'data'      => json_encode($data),
		]);
	}

	
	function my_own_mime_types( $mimes ) {
		$mimes['svg'] = 'image/svg+xml';
		return $mimes;
	}

	// Hide specific menu items for certain roles
	public function pinaka_hide_admin_menu_items() {
		// Get the current user
		$current_user = wp_get_current_user();
		// print_r($current_user->roles);
		// die;

		// Check if the user has a specific role
		if (in_array('Employee', $current_user->roles) || in_array('administrator', $current_user->roles)) {  
			// Remove specific menu items for 'employee' role
			remove_menu_page('edit.php');                  // Posts
			// remove_menu_page('upload.php');                // Media
			remove_menu_page('edit.php?post_type=page');   // Pages
			remove_menu_page('edit-comments.php');         // Comments
			remove_menu_page('themes.php');               // Appearance/
			remove_menu_page('plugins.php');              // Plugins
			// remove_menu_page('users.php');                // Users
			// remove_menu_page('tools.php');                // Tools
			remove_menu_page('options-general.php');      // Settings
		}
	}

	public function enqueue_admin_scripts() {
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script('wp-color-picker');
		
		wp_enqueue_script('wc-enhanced-select');
		wp_enqueue_style('woocommerce_admin_styles'); // contains select2 styling
		
		wp_enqueue_script(
			'pinaka-admin-product-search',
			plugin_dir_url(__FILE__) . '../assests/js/pinaka-product-search.js',
			['jquery', 'select2'],
			false,
			true
		);

		wp_localize_script('pinaka-admin-product-search', 'pinakaProductSearch', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce'    => wp_create_nonce('pinaka_product_search'),
		]);

		wp_enqueue_media();
    	wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script(
			'pinaka-admin-fastkey-images',
			plugin_dir_url(__FILE__) . '../assests/js/fastkey-images.js',
			['jquery'],
			false,
			true
		);

		wp_localize_script( 'pinaka-admin-fastkey-images', 'PinakaFastKey', [
			'ajax_url'     => admin_url( 'admin-ajax.php' ),
			'save_nonce'   => wp_create_nonce( 'pinaka_fastkey_manage_nonce' ),
			'import_nonce' => wp_create_nonce( 'pinaka_fastkey_image_nonce' ),
		] );

		$screen = get_current_screen();
		if ( $screen && isset($screen->post_type) && $screen->post_type === 'product' ) {
			wp_enqueue_script('woocommerce_admin');
			wp_enqueue_script(
				'pinaka-dynamic-price-admin',
				plugin_dir_url(__FILE__) . '../assests/js/admin-dynamic-price.js',
				['jquery', 'woocommerce_admin'],
				time(),
				true
			);
		}
		wp_enqueue_script(
			'fastkey-media-upload',
			plugin_dir_url(__FILE__) . '../assests/js/fastkey-media.js',
			['jquery'],
			'1.0',
			true
		);

		wp_enqueue_script(
			'pinaka-multipack-discount-admin',
			plugin_dir_url( __FILE__ ) . '../assests/js/multipack-discount-admin.js',
			[ 'jquery', 'select2' ],
			'1.0.0',
			true
		);

		wp_localize_script(
			'pinaka-multipack-discount-admin',
			'multipackDiscount',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'multipack_search_products' ),
			]
		);
	}

	public function pinaka_multipack_search_products() {

		check_ajax_referer( 'multipack_search_products', 'security' );

		$search_term = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : '';

		if ( empty( $search_term ) ) {
			wp_send_json( [ 'results' => [] ] );
		}

		$results = [];

		$query = new WP_Query([
			'post_type'      => [ 'product', 'product_variation' ],
			'post_status'    => 'publish',
			'posts_per_page' => 30,
			's'              => $search_term,
			'fields'         => 'ids',
		]);

		foreach ( $query->posts as $post_id ) {

			$product = wc_get_product( $post_id );
			if ( ! $product ) {
				continue;
			}
			if ( $product->is_type( 'variation' ) ) {

				if ( ! $product->is_purchasable() ) {
					continue;
				}

				$results[ $product->get_id() ] = [
					'id'   => $product->get_id(),
					'text' => $product->get_name(),
				];

				continue;
			}
			if ( $product->is_type( 'variable' ) ) {

				foreach ( $product->get_children() as $variation_id ) {

					if ( isset( $results[ $variation_id ] ) ) {
						continue;
					}

					$variation = wc_get_product( $variation_id );
					if ( ! $variation || ! $variation->is_purchasable() ) {
						continue;
					}

					$results[ $variation_id ] = [
						'id'   => $variation->get_id(),
						'text' => $variation->get_name(),
					];
				}

				continue;
			}

			if ( $product->is_type( 'simple' ) ) {

				$results[ $product->get_id() ] = [
					'id'   => $product->get_id(),
					'text' => $product->get_name(),
				];
			}
		}

		wp_send_json([
			'results' => array_values( $results ),
		]);
	}


	function pinaka_product_search_callback() {
		check_ajax_referer('pinaka_product_search', 'security');

		$term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';

		$args = [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			's'              => $term,
			'posts_per_page' => -1,
		];

		$products = get_posts($args);
		$results = [];

		foreach ($products as $product) {
			$results[] = [
				'id'   => $product->ID,
				'text' => $product->post_title,
			];
		}

		wp_send_json($results);
	}
	function pinaka_manage_fastkey_images() 
	{
		$mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'import';

		$default_types = ['2D', '3D', 'JPG', 'JPEG', 'PNG'];

		$custom_types = get_option('pinaka_fastkey_custom_types', []);

		if (!is_array($custom_types)) {
			$custom_types = [];
		}

		$valid_folders = array_unique(
			array_map('strtoupper', array_merge($default_types, $custom_types))
		);
		if ($mode === 'save') {

			$type = strtoupper( sanitize_text_field($_POST['image_type'] ?? '') );

			if (!in_array($type, $valid_folders, true)) {
				wp_send_json_error([
					'message' => 'Please select a valid Image Type.'
				], 403);
			}
		}

		$allowed_exts = [
			'jpg','jpeg','png','gif','webp','svg',
			'bmp','tiff',
			'obj','stl','fbx','gltf','glb','ply','3ds','dae'
		];

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$existing = get_option('pinaka_fastkey_images', []);
		if (!is_array($existing)) $existing = [];

		if ($mode === 'save') {

			if (empty($_POST['save_nonce']) || 
				! wp_verify_nonce(sanitize_text_field($_POST['save_nonce']), 'pinaka_fastkey_manage_nonce')) 
			{
				wp_send_json_error(['message' => 'Invalid nonce.'], 400);
			}

			$type  = sanitize_text_field($_POST['image_type'] ?? '');
			$items = isset($_POST['items']) && is_array($_POST['items']) ? $_POST['items'] : [];

			if (!isset($existing[$type]) || !is_array($existing[$type])) {
				$existing[$type] = [];
			}
			$plugin_root_path = plugin_dir_path(dirname(__FILE__));
			$fastkey_path     = $plugin_root_path . 'fastkey-images/';
			$target_folder    = $fastkey_path . $type . '/';

			if (!is_dir($target_folder)) {
				wp_mkdir_p($target_folder);
			}

			foreach ($items as $item) {

				if (!is_array($item)) continue;

				$ext  = strtolower(sanitize_text_field($item['ext'] ?? ''));
				$attachment_id = intval($item['id'] ?? 0);
				$name = $item['name'] ?? '';
				$url  = esc_url_raw($item['url'] ?? '');

				if (!$ext || !$name || !$url) continue;
				if (!in_array($ext, $allowed_exts, true)) continue;

				if (!$attachment_id) {
					continue;
				}
				$upload_path = get_attached_file($attachment_id);
				if (!file_exists($upload_path)) {
					continue;
				}

				$file_basename = basename($upload_path);
				$destination_path = $target_folder . $file_basename;
				if (!copy($upload_path, $destination_path)) {
					continue;
				}
				update_post_meta($attachment_id, '_image_type', $type);
				$existing[$type][] = [
					'id'         => $attachment_id,
					'name'       => $name,
					'url'        => wp_get_attachment_url($attachment_id),
					'image_type' => $type,
					'isDeleted'  => false
				];
			}

			update_option('pinaka_fastkey_images', $existing);

			wp_send_json_success(['message' => 'FastKey images saved successfully.']);
		}

		if ($mode === 'import') 
		{
			$fastkey_root = plugin_dir_path(__FILE__) . '../fastkey-images/';

			if (!is_dir($fastkey_root)) {
				return;
			}

			$folders = array_filter(glob($fastkey_root . '*'), 'is_dir');
	
			$global_existing = [];
			$group_counts = [];
			foreach ($existing as $grp => $items) {
				if (!is_array($items)) continue;
				$group_counts[$grp] = 0;
				foreach ($items as $img) {
					if (is_array($img) && isset($img['name'], $img['id'])) {
						$group_counts[$grp]++;
						$global_existing[$grp][$img['name']] = intval($img['id']);
					}
				}
			}
			foreach ($folders as $folder_path) {

				$folder_name = strtoupper(basename($folder_path));
				if (!in_array($folder_name, $valid_folders, true)) {

					continue;
				}

				if (!isset($existing[$folder_name]) || !is_array($existing[$folder_name])) {
					$existing[$folder_name] = [];
				}
				
				$files = array_diff(scandir($folder_path), ['.', '..']);
				$db_count = $group_counts[$folder_name] ?? 0;
				if ($db_count === count($files)) {
					continue;
				}
				foreach ($files as $file) {

					$safe_name = $file;
					$full_path = $folder_path . '/' . $file;

					if (!file_exists($full_path)) {
						continue;
					}

					$ext = strtolower(pathinfo($safe_name, PATHINFO_EXTENSION));
					if (!in_array($ext, $allowed_exts, true)) {
						continue;
					}

					if (isset($global_existing[$folder_name][$safe_name])) {
						continue;
					}

					$upload_dir    = wp_upload_dir();
					$upload_folder = $upload_dir['path'];

					wp_mkdir_p($upload_folder);

					$target_path = trailingslashit($upload_folder) . $safe_name;

					if (!copy($full_path, $target_path)) {
						continue;
					}
					if (!file_exists($target_path) || filesize($target_path) < 100) {
						unlink($target_path);
						continue;
					}
					$img_info = @getimagesize($target_path);
					if ($img_info === false) {
						unlink($target_path);
						continue;
					}
					$filetype = wp_check_filetype_and_ext($target_path, $safe_name);

					$attach_id = wp_insert_attachment([
						'post_title'     => $safe_name,
						'post_mime_type' => $filetype['type'],
						'post_status'    => 'inherit',
					], $target_path);

					if (is_wp_error($attach_id) || empty($attach_id)) {
						continue;
					}

					require_once ABSPATH . 'wp-admin/includes/image.php';
					$metadata = @wp_generate_attachment_metadata($attach_id, $target_path);

					if (!empty($metadata)) {
						wp_update_attachment_metadata($attach_id, $metadata);
					}

					update_post_meta($attach_id, '_image_type', $folder_name);

					$existing[$folder_name][] = [
						'id'         => $attach_id,
						'name'       => $safe_name,
						'url'        => wp_get_attachment_url($attach_id),
						'image_type' => $folder_name,
						'isDeleted'  => false
					];

					$global_existing[$folder_name][$safe_name] = $attach_id;

				}
			}

			update_option('pinaka_fastkey_images', $existing);
			return;
		}
		wp_send_json_error(['message' => 'Invalid mode.'], 400);
	}

public function pinaka_replace_woocommerce_menu() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    remove_menu_page( 'woocommerce' );

    
    add_menu_page( 'Orders', 'Orders', 'manage_woocommerce', 'edit.php?post_type=shop_order', '', 'dashicons-cart', 54 );
    add_menu_page( 'Customers', 'Customers', 'manage_woocommerce', 'admin.php?page=wc-admin&path=%2Fcustomers', '', 'dashicons-groups', 55 );
	add_menu_page( 'Coupons', 'Coupons', 'manage_woocommerce', 'edit.php?post_type=shop_coupon&legacy_coupon_menu=1', '', 'dashicons-tickets', 56 );
	add_menu_page( 'Reports', 'Reports', 'manage_woocommerce', 'admin.php?page=wc-reports', '', 'dashicons-analytics', 57 );
	add_menu_page( 'Status', 'Status', 'manage_woocommerce', 'admin.php?page=wc-status', '', 'dashicons-update', 58 );

    add_menu_page( 'Extensions', 'Extensions', 'manage_woocommerce', 'admin.php?page=wc-admin&path=/extensions', '', 'dashicons-admin-plugins', 59 );

    add_menu_page( 'Settings', 'Settings', 'manage_options', 'admin.php?page=wc-settings', '', 'dashicons-admin-generic', 59 );
}




	function pinaka_toggle_fastkey_status()
	{
		$id        = intval($_POST['id']);
		$ext       = sanitize_text_field($_POST['ext']);
		$newStatus = sanitize_text_field($_POST['new_status']);
		if($newStatus == 'deactive')
		{
			$newStatus = true;
		}
		else
		{
			$newStatus = false;
		}
		$existing = get_option('pinaka_fastkey_images', []);

		if (!isset($existing[$ext])) {
			wp_send_json_error(['message' => 'Category not found']);
		}

		$found = false;

		foreach ($existing[$ext] as &$entry) {
			if ($entry['id'] == $id) 
			{
				$entry['isDeleted'] = $newStatus;
				$found = true;
				break;
			}
		}
		unset($entry);
		if (!$found) {
			wp_send_json_error(['message' => 'Image not found']);
		}

		update_option('pinaka_fastkey_images', $existing);

		wp_send_json_success(['message' => 'Status updated']);
	}
	function update_admin_color_scheme() {
		// Security Check
		check_ajax_referer( 'update_admin_color_scheme', 'color_scheme_nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'User not authenticated' ) );
		}

		$user_id = get_current_user_id();
		$new_color = sanitize_text_field( $_POST['admin_color'] ?? '' );

		if ( empty( $new_color ) ) {
			wp_send_json_error( array( 'message' => 'Invalid color scheme selected' ) );
		}

		update_user_option( $user_id, 'admin_color', $new_color );

		wp_send_json_success( array( 'message' => 'Color scheme updated successfully!' ) );
	}

	function create_custom_role() {
		check_ajax_referer('create_custom_role_action', 'create_custom_role_nonce');

		$role_key  = sanitize_key($_POST['new_role_key'] ?? '');
		$role_name = sanitize_text_field($_POST['new_role_name'] ?? '');

		if (empty($role_key) || empty($role_name)) {
			wp_send_json_error(['message' => 'Both Role Key and Role Name are required.']);
		}

		if (get_role($role_key)) {
			wp_send_json_error(['message' => 'Role already exists.']);
		}

		$capabilities = [
			'read'                     => true,
			'level_0'                  => true,
			'edit_posts'              => true,
			'upload_files'            => true,

			// WooCommerce
			'manage_woocommerce'      => true,
			'view_woocommerce_reports'=> true,

			// Product capabilities
			'edit_products'           => true,
			'read_products'           => true,
			'delete_products'         => true,
			'publish_products'        => true,
			'edit_published_products' => true,
			'read_private_products'   => true,
			'delete_published_products' => true,
            'edit_product_terms' => true,
            'assign_product_terms' => true,
            'manage_product_terms' => true,

			// Order capabilities
			'edit_shop_orders'        => true,
			'read_shop_order'         => true,
			'edit_shop_order'         => true,
			'delete_shop_orders'      => true,
			'publish_shop_orders'     => true,
			'edit_others_shop_orders' => true,
			'read_private_shop_orders'=> true,

			// User list
			'list_users'              => true,
		];

		$role_result = add_role($role_key, $role_name, $capabilities);

		if (!$role_result) {
			wp_send_json_error(['message' => 'Role creation failed.']);
		}

		wp_send_json_success(['message' => 'Role created successfully.', 'role' => $role_key]);
	}

	function save_pinaka_pos_menu_settings() {
		// Verify nonce for security
		if (!isset($_POST['pinaka_pos_menu_nonce']) || !wp_verify_nonce($_POST['pinaka_pos_menu_nonce'], 'pinaka_pos_menu_settings_action')) {
			wp_send_json_error(['message' => 'Security check failed!'], 403);
		}

		// Ensure the user has admin privileges
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Unauthorized action'], 403);
		}

		// Ensure role is passed and valid
		if (empty($_POST['role']) || !isset(wp_roles()->roles[$_POST['role']])) {
			wp_send_json_error(['message' => 'Invalid or missing role'], 400);
		}

		$role = sanitize_key($_POST['role']);
		$selected_menus = isset($_POST['menu_items']) ? array_map('sanitize_text_field', $_POST['menu_items']) : [];

		// Save role-specific menu settings
		$all_settings = get_option('pinaka_pos_menu_settings', []);
		$all_settings[$_POST['role']] = $selected_menus;
		// echo "<PRE>";
		// print_r($all_settings);
		// die;
		update_option('pinaka_pos_menu_settings', $all_settings);

		wp_send_json_success(['message' => 'Menu settings updated successfully!']);
	}

	/**
	 * Save denominations.
	 */
	/**
	 * Save denominations.
	 */
	public function save_denominations() {
		check_ajax_referer('save_denominations_action', 'save_denominations_nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Unauthorized']);
		}

		$denoms = $_POST['denominations']['denom'] ?? [];  // <-- this matches your input
		$images = $_POST['denominations']['image'] ?? [];

		$structured = [];

		for ($i = 0; $i < count($denoms); $i++) {
			$denom = sanitize_text_field($denoms[$i]);
			$image = esc_url_raw($images[$i] ?? '');

			if ($denom !== '') {
				$structured[] = [
					'denom' => $denom,
					'image' => $image,
				];
			}
		}

		usort($structured, function ($a, $b) {
			return strcmp($a['denom'], $b['denom']);
		});

		update_option('pinaka_pos_denominations', $structured);

		wp_send_json_success(['message' => 'Denominations saved successfully.']);
	}




	public function save_safedrop_denominations() {
		check_ajax_referer('save_safedrop_denominations_action', 'save_safedrop_denominations_nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Unauthorized']);
		}

		$denominations = isset($_POST['safedrop_denominations']) ? $_POST['safedrop_denominations'] : [];
		$limits        = isset($_POST['safedrop_denominations_limit']) ? $_POST['safedrop_denominations_limit'] : [];
		$symbols       = isset($_POST['safedrop_denominations_symbol']) ? $_POST['safedrop_denominations_symbol'] : [];

		$structured_data = [];

		// Ensure all arrays are the same length
		if (count($denominations) === count($limits) && count($limits) === count($symbols)) {
			for ($i = 0; $i < count($denominations); $i++) {
				$denom  = floatval($denominations[$i]);  // ✅ Allow decimal values
				$limit  = intval($limits[$i]);           // Limit remains integer
				$symbol = sanitize_text_field($symbols[$i]);

				// ✅ Allow 0.01 and above, reject zero or negative
				if ($denom < 0.01) {
					continue;
				}

				$structured_data[] = [
					'denom'  => round($denom, 2),  // Optional: Round to 2 decimal places
					'tube_limit'  => $limit,
					'symbol' => $symbol
				];
			}

			// ✅ Sort by value ascending
			usort($structured_data, function($a, $b) {
				return $a['value'] <=> $b['value'];
			});
		}

		update_option('pinaka_pos_safedrop_denominations', $structured_data);

		wp_send_json_success(['message' => 'Safe Drop Denominations saved successfully.']);
	}




	/**
	 * Save coins denominations.
	 */
	public function save_coins_denominations() {

		check_ajax_referer('save_coins_denominations_action', 'save_coins_denominations_nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Unauthorized']);
		}

		$denoms = $_POST['coins_denominations']['denom'] ?? [];  // <-- this matches your input
		$images = $_POST['coins_denominations']['image'] ?? [];

		$structured = [];

		for ($i = 0; $i < count($denoms); $i++) {
			$denom = sanitize_text_field($denoms[$i]);
			$image = esc_url_raw($images[$i] ?? '');

			if ($denom !== '') {
				$structured[] = [
					'denom' => $denom,
					'image' => $image,
				];
			}
		}

		usort($structured, function ($a, $b) {
			return strcmp($a['denom'], $b['denom']);
		});

		update_option('pinaka_pos_coins_denominations', $structured);
		wp_send_json_success(['message' => 'Coins Denominations saved successfully.']);
	}


	/**
	 * Save safe denominations.
	 */
	public function save_safe_denominations() {

		check_ajax_referer('save_safe_denominations_action', 'save_safe_denominations_nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Unauthorized']);
		}

		$denoms = $_POST['safe_denominations']['denom'] ?? [];  // <-- this matches your input
		$images = $_POST['safe_denominations']['image'] ?? [];

		$structured = [];

		for ($i = 0; $i < count($denoms); $i++) {
			$denom = sanitize_text_field($denoms[$i]);
			$image = esc_url_raw($images[$i] ?? '');

			if ($denom !== '') {
				$structured[] = [
					'denom' => $denom,
					'image' => $image,
				];
			}
		}

		usort($structured, function ($a, $b) {
			return strcmp($a['denom'], $b['denom']);
		});

		update_option('pinaka_pos_safe_denominations', $structured);
		wp_send_json_success(['message' => 'Coins Denominations saved successfully.']);
	}


	/**
	 * Register the emp_login_pin user meta field.
	 */
	function register_emp_login_pin_meta() {
		register_meta('user', 'emp_login_pin', [
			'type'         => 'string',
			'single'       => true,
			'show_in_rest' => [
				'schema' => [
					'type'        => 'string',
					'pattern'    => '^\d{6}$',
					'description'=> 'Employee Login PIN (6-digit)',
				],
			],
			'sanitize_callback' => function ($value) {
				return preg_match('/^\d{6}$/', $value) ? $value : '';
			},
			'auth_callback' => function () {
				return current_user_can('edit_user', get_current_user_id());
			},
		]);
		register_meta('user', 'phone', [
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => function () {
				return current_user_can('edit_users');
			},
		]);
		register_meta('user', 'cash_drawer_access', array(
			'type'         => 'boolean',
			'description'  => 'Cash Drawer Access',
			'single'       => true,
			'show_in_rest' => true, // Allows API access
			'auth_callback' => function() {
				return current_user_can('edit_users'); // Restrict access
			}
		));
	}

	/**
	 * emp_login_pin field to the user profile page (Only Numbers Allowed).
	 */
	function add_emp_login_pin_field($user) {
		?>
		<h3>Employee Login PIN</h3>
		<table class="form-table">
			<tr>
				<th><label for="emp_login_pin">Login PIN (6-digit)</label></th>
				<td>
					<input type="number" name="emp_login_pin" id="emp_login_pin" value="<?php echo esc_attr(get_user_meta($user->ID, 'emp_login_pin', true)); ?>" class="regular-text" minlength="6" maxlength="6" pattern="\d{6}">
					<p class="description">Enter a 6-digit numeric PIN for employee login.</p>
				</td>
			</tr>
			<tr>
				<th><label for="cash_drawer_access">Cash Drawer Access</label></th>
				<td>
					<input type="checkbox" name="cash_drawer_access" id="cash_drawer_access" value="1" <?php checked(1, get_user_meta($user->ID, 'cash_drawer_access', true)); ?>>
					<p class="description">Check to grant cash drawer access to this employee.</p>
				</td>
			</tr>
		</table>
		<script>
			document.getElementById('emp_login_pin').addEventListener('input', function (e) {
				e.target.value = e.target.value.replace(/\D/g, '').slice(0, 6); // Only allow numbers, max length 6
			});
		</script>
		<?php
	}




	/**
	 * Save emp_login_pin field when updating user profile (Only Numbers Allowed).
	 */
	function save_emp_login_pin_field($user_id) {
		// Check user permissions
		if (!current_user_can('edit_user', $user_id)) {
			return false;
		}

		// Validate PIN (must be exactly 6 digits and numeric)
		if (!empty($_POST['emp_login_pin']) && preg_match('/^\d{6}$/', $_POST['emp_login_pin'])) {
			update_user_meta($user_id, 'emp_login_pin', sanitize_text_field($_POST['emp_login_pin']));
		} else {
			delete_user_meta($user_id, 'emp_login_pin'); // Remove if invalid
		}

		// Save cash drawer access
		$access = isset($_POST['cash_drawer_access']) ? 1 : 0;
		update_user_meta($user_id, 'cash_drawer_access', $access);	
	}


	// Add custom field to product variations for variation name
	function add_custom_variation_field_to_woocomarce_product() {

		// Add custom field(s) to product variations
		add_action( 'woocommerce_variation_options_pricing', 'add_variation_setting_fields', 10, 3 );
		function add_variation_setting_fields( $loop, $variation_data, $variation ) {
			$field_key = 'custom_name';
			
			woocommerce_wp_text_input( array(
				'id'            => $field_key.'['.$loop.']',
				'label'         => __('Variation Name', 'woocommerce'),
				'wrapper_class' => 'form-row',
				'description'   => __('Add a custom Variation Name', 'woocommerce'),
				'desc_tip'      => true,
				'value'         => get_post_meta($variation->ID, $field_key, true)
			) );
		}

		// Save the custom field from product variations
		add_action('woocommerce_admin_process_variation_object', 'save_variation_setting_fields', 10, 2 );
		function save_variation_setting_fields($variation, $i) {
			$field_key = 'custom_name';

			if ( isset($_POST[$field_key][$i]) ) {
				$variation->update_meta_data($field_key, sanitize_text_field($_POST[$field_key][$i]));
			}
		}


		add_filter( 'woocommerce_product_title', 'custom_variation_title', 10, 2 );
			function custom_variation_title( $product_title, $variation ) {
				if ( $custom_title = $variation->get_meta('custom_name') ) {
					return $custom_title;
				}
				return $product_title;
			}

			add_filter( 'woocommerce_product_variation_get_name', 'custom_variation_name', 10, 2 );
			function custom_variation_name( $product_name, $variation ) {
				if ( $custom_name = $variation->get_meta('custom_name') ) {
					return $custom_name;
				}
				return $product_name;
		}
	}


	function add_employee_order_caps() {

    if (get_option('pinaka_employee_caps_added')) {
        return;
    }

    $admin_role = get_role('administrator');
    if (!$admin_role) return;

    $roles_to_upgrade = ['merchant', 'shop_manager'];

    foreach ($roles_to_upgrade as $role_slug) {

        $role = get_role($role_slug);

        if ($role) {
            foreach ($admin_role->capabilities as $cap => $grant) {
                $role->add_cap($cap);
            }
        }
    }

    update_option('pinaka_employee_caps_added', true);
}
function pinaka_customize_manager_employee_caps() {

    $admin_role = get_role('administrator');
    if (!$admin_role) return;

    $roles = ['manager', 'employee'];

    foreach ($roles as $role_slug) {

        $role = get_role($role_slug);
        if (!$role) continue;

        // 1️⃣ Copy all administrator capabilities
        foreach ($admin_role->capabilities as $cap => $grant) {
            $role->add_cap($cap);
        }

        // 2️⃣ Remove Coupon Permissions
        $role->remove_cap('edit_shop_coupons');
        $role->remove_cap('publish_shop_coupons');
        $role->remove_cap('delete_shop_coupons');
        $role->remove_cap('edit_others_shop_coupons');
        $role->remove_cap('read_shop_coupon');

        // 3️⃣ Remove User Management
        $role->remove_cap('create_users');
        $role->remove_cap('edit_users');
        $role->remove_cap('delete_users');
        $role->remove_cap('promote_users');
    }

    // 4️⃣ Extra restriction only for Employee (No Refund)
    $employee = get_role('employee');
    if ($employee) {
        $employee->remove_cap('edit_shop_orders');
        $employee->remove_cap('delete_shop_orders');
        $employee->remove_cap('edit_others_shop_orders');
    }
}


	function my_add_custom_user_column($columns) {
		$columns['emp_login_pin'] = 'Login Pin'; // Column key => Column label
		return $columns;
	}	

	function my_show_custom_user_column_content($value, $column_name, $user_id) {
		if ($column_name == 'emp_login_pin') {
			return esc_html(get_user_meta($user_id, 'emp_login_pin', true));
		}
		return $value;
	}

	function pinaka_save_promotion_images() {
		// log request for debugging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[pinaka] save_promotion_images called. POST keys: ' . implode( ',', array_keys( $_POST ) ) );
		}

		if ( empty( $_POST['save_promotion_images_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['save_promotion_images_nonce'] ) ), 'save_promotion_images_action' ) ) {
			error_log( '[pinaka] Invalid nonce.' );
			wp_send_json_error( array( 'message' => 'Invalid nonce.' ), 400 );
		}

		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_posts' ) ) {
			error_log( '[pinaka] Unauthorized user.' );
			wp_send_json_error( array( 'message' => 'Unauthorized.' ), 403 );
		}

		$option_key = 'pinaka_pos_promotion_images';

		try {
			$ids_csv = isset( $_POST['promotion_image_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['promotion_image_ids'] ) ) : '';
			$ids = array_filter( array_map( 'absint', explode( ',', $ids_csv ) ) );

			$urls = array();
			if ( isset( $_POST['promotion_image_urls'] ) && is_array( $_POST['promotion_image_urls'] ) ) {
				foreach ( $_POST['promotion_image_urls'] as $u ) {
					$u = esc_url_raw( wp_unslash( $u ) );
					if ( $u ) $urls[] = $u;
				}
			}

			$final = array_merge( $ids, $urls );

			if ( update_option( $option_key, $final ) === false ) {
				error_log( '[pinaka] update_option failed for ' . $option_key );
				wp_send_json_error( array( 'message' => 'Failed to save option.' ), 500 );
			}

			wp_send_json_success( array( 'message' => 'Promotion images saved.' ) );
		} catch ( Exception $e ) {
			error_log( '[pinaka] Exception in save_promotion_images: ' . $e->getMessage() );
			wp_send_json_error( array( 'message' => 'Server exception: ' . $e->getMessage() ), 500 );
		}
	}

	/**
	 * Sanitize & normalize cashback settings.
	 */
	public function sanitize_cashback_settings( $input ) {
		$out = [
			'enabled'      => empty($input['enabled']) ? 0 : 1,
			'max_cashback' => isset($input['max_cashback']) ? floatval($input['max_cashback']) : 0,
			'tiers'        => [],
		];

		if (!empty($input['tiers']) && is_array($input['tiers'])) {
			foreach ($input['tiers'] as $row) {
				$from = isset($row['from']) ? floatval($row['from']) : null;
				$to   = isset($row['to'])   ? floatval($row['to'])   : null;
				$fee  = isset($row['fee'])  ? floatval($row['fee'])  : null;

				// keep only valid rows
				if ($from !== null && $to !== null && $fee !== null && $from >= 0 && $to > 0 && $to >= $from) {
					$out['tiers'][] = [
						'from' => $from,
						'to'   => $to,
						'fee'  => $fee,
					];
				}
			}

			// sort tiers by "from"
			usort($out['tiers'], function($a, $b){ return $a['from'] <=> $b['from']; });
		}

		// Cap tiers to max_cashback if provided
		if ($out['max_cashback'] > 0 && !empty($out['tiers'])) {
			foreach ($out['tiers'] as &$t) {
				if ($t['to'] > $out['max_cashback']) {
					$t['to'] = $out['max_cashback'];
				}
				if ($t['from'] > $out['max_cashback']) {
					$t = null; // drop entirely if starts above max
				}
			}
			$out['tiers'] = array_values(array_filter($out['tiers']));
		}

		return $out;
	}
}





/**
 * Robust WooCommerce CSV Import: add stock (original + CSV sums), replace price, create new product.
 *
 * - Builds a pre-import snapshot of existing SKU => stock from DB (prevents doubling).
 * - Tracks running added qty per SKU for multiple CSV rows in same import.
 * - Uses CSV columns: sku, stock_quantity OR stock, regular_price, sale_price
 */

/* Prepare globals */
add_action('init', function () {
    if (!isset($GLOBALS['pinaka_import_snapshot'])) {
        $GLOBALS['pinaka_import_snapshot'] = [];
    }
    if (!isset($GLOBALS['pinaka_import_running_added'])) {
        $GLOBALS['pinaka_import_running_added'] = [];
    }
});

/* Before import: build snapshot of existing SKUs -> stock (query DB once) */
add_action('woocommerce_product_import_before_import', 'pinaka_build_import_snapshot');
function pinaka_build_import_snapshot() {
    global $wpdb;
    $snapshot = [];

    // Get postmeta pairs where meta_key = '_sku' and corresponding _stock meta (left join)
    $sku_meta_key   = '_sku';
    $stock_meta_key = '_stock';

    $sql = $wpdb->prepare("
        SELECT sku_meta.post_id, sku_meta.meta_value AS sku, stock_meta.meta_value AS stock
        FROM {$wpdb->postmeta} sku_meta
        LEFT JOIN {$wpdb->postmeta} stock_meta
            ON stock_meta.post_id = sku_meta.post_id
            AND stock_meta.meta_key = %s
        WHERE sku_meta.meta_key = %s
        AND sku_meta.meta_value != ''
    ", $stock_meta_key, $sku_meta_key);

    $rows = $wpdb->get_results($sql);

    if (!empty($rows)) {
        foreach ($rows as $r) {
            $sku = (string) $r->sku;
            $stock = $r->stock !== null ? (int) $r->stock : 0;
            // If multiple posts with same SKU (shouldn't happen), keep last non-zero or sum? keep last.
            $snapshot[$sku] = $stock;
        }
    }

    $GLOBALS['pinaka_import_snapshot'] = $snapshot;
    $GLOBALS['pinaka_import_running_added'] = []; // reset running counters
}

/* After import finishes (safety): clear snapshot & running added */
add_action('woocommerce_product_import_after_import', function () {
    $GLOBALS['pinaka_import_snapshot'] = [];
    $GLOBALS['pinaka_import_running_added'] = [];
});

/**
 * Main hook: after WooCommerce inserts/updates the product object for that CSV row,
 * compute final stock = snapshot_original + running_added_so_far + this_row_csv_qty,
 * then save product (and replace price).
 */
add_action('woocommerce_product_import_inserted_product_object', 'pinaka_apply_add_stock_final', 10, 2);
function pinaka_apply_add_stock_final($product, $data) {
    // Must have SKU to reliably match product
    if (empty($data['sku'])) {
        // still update price if present
        if (isset($data['regular_price']) && $data['regular_price'] !== '') {
            $product->set_regular_price($data['regular_price']);
        }
        if (isset($data['sale_price']) && $data['sale_price'] !== '') {
            $product->set_sale_price($data['sale_price']);
        }
        $product->save();
        return;
    }

    $sku = (string) $data['sku'];

    // Determine CSV qty (support both possible column names)
    $csv_qty = null;
    if (isset($data['stock_quantity']) && $data['stock_quantity'] !== '') {
        $csv_qty = (int) $data['stock_quantity'];
    } elseif (isset($data['stock']) && $data['stock'] !== '') {
        $csv_qty = (int) $data['stock'];
    }

    // Replace prices if present
    $price_changed = false;
    if (isset($data['regular_price']) && $data['regular_price'] !== '') {
        $product->set_regular_price($data['regular_price']);
        $price_changed = true;
    }
    if (isset($data['sale_price']) && $data['sale_price'] !== '') {
        $product->set_sale_price($data['sale_price']);
        $price_changed = true;
    }

    // Get original snapshot value (0 if not present)
    $original = 0;
    if (!empty($GLOBALS['pinaka_import_snapshot']) && isset($GLOBALS['pinaka_import_snapshot'][$sku])) {
        $original = (int) $GLOBALS['pinaka_import_snapshot'][$sku];
    }

    // Get running added so far for this SKU during this import
    $running = 0;
    if (!empty($GLOBALS['pinaka_import_running_added']) && isset($GLOBALS['pinaka_import_running_added'][$sku])) {
        $running = (int) $GLOBALS['pinaka_import_running_added'][$sku];
    }

    if ($csv_qty !== null) {
        // New running total (include this row)
        $running_new = $running + $csv_qty;

        // Final stock should be original_snapshot + running_new
        $final_stock = $original + $running_new;

        // Ensure manage stock and set quantity & status
        $product->set_manage_stock(true);
        $product->set_stock_quantity((int) $final_stock);
        $product->set_stock_status($final_stock > 0 ? 'instock' : 'outofstock');

        // Persist running counter for subsequent rows for same SKU in this import
        $GLOBALS['pinaka_import_running_added'][$sku] = $running_new;
    }

    // Save only once (price + stock together)
    $product->save();
}
add_action('template_redirect', 'redirect_home_to_login');
function redirect_home_to_login() {
    
    // Allow backend pages to work normally
    if ( is_admin() ) {
        return;
    }

    // Only redirect the homepage
    if ( is_front_page() || is_home() ) {

        // Allow logged-in users to avoid redirect loop
        if ( ! is_user_logged_in() ) {
            wp_redirect( wp_login_url() );
            exit;
        }
    }
}



// Add Barcode column to Coupons admin table
add_filter('manage_edit-shop_coupon_columns', function($columns) {
    $columns['coupon_barcode'] = __('Barcode', 'woocommerce');
    return $columns;
});

// Display Code-128 barcode + download link
add_action('manage_shop_coupon_posts_custom_column', function($column, $post_id) {
    if ($column === 'coupon_barcode') {
        $coupon_code = get_post_field('post_title', $post_id);

        if (!$coupon_code) {
            echo '—';
            return;
        }

        // PNG barcode URL
        $barcode_url = 'https://barcode.tec-it.com/barcode.ashx?data=' . rawurlencode($coupon_code) . '&code=Code128&dpi=96&filetype=png&translate-esc=true';


        echo '<div style="text-align:center;">';
        echo '<img src="' . esc_url($barcode_url) . '" height="50" alt="Barcode" /><br>';

        $download_url = admin_url('edit.php?post_type=shop_coupon&barcode_download=1&code=' . urlencode($coupon_code));

		echo '<a href="' . esc_url($download_url) . '" 
				style="text-decoration:none;font-size:14px;display:inline-block;margin-top:6px;" 
				title="Download Barcode PNG">
				⬇️ Download
			</a>';

    }
}, 10, 2);

add_action('admin_init', function() {
    if (!isset($_GET['barcode_download']) || !isset($_GET['code'])) {
        return;
    }

    $coupon_code = sanitize_text_field($_GET['code']);
    $barcode_url = 'https://barcode.tec-it.com/barcode.ashx?data=' . urlencode($coupon_code) . '&code=Code128&dpi=96&filetype=png';

    $image_data = wp_remote_retrieve_body(wp_remote_get($barcode_url));

    if (!$image_data) {
        wp_die('Error generating barcode.');
    }

    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="barcode-' . $coupon_code . '.png"');
    echo $image_data;
    exit;
});

/////// Start coupons issuing changes /////// 

/**
 * Add "Issuing Coupon" tab to coupon edit page
 */
add_filter( 'woocommerce_coupon_data_tabs', 'pinaka_add_generate_coupon_tab' );
function pinaka_add_generate_coupon_tab( $tabs ) {

	$tabs['generate_coupon'] = [
		'label'    => __( 'Issuing Coupon', 'pinaka-pos' ),
		'target'   => 'pinaka_generate_coupon_data',
		'class'    => [],
		'priority' => 60,
	];

	return $tabs;
}


/**
 * Render Issuing Coupon tab content
 */
add_action( 'woocommerce_coupon_data_panels', 'pinaka_render_generate_coupon_tab_content' );
function pinaka_render_generate_coupon_tab_content() {

	$coupon_id = get_the_ID();

	$enabled       = get_post_meta( $coupon_id, '_pinaka_enable_generate_coupon', true );
	$generate_type = get_post_meta( $coupon_id, '_pinaka_generate_coupon_type', true );
	$generate_limit = get_post_meta($coupon_id, '_pinaka_generate_coupon_limit', true);
	?>
	<div id="pinaka_generate_coupon_data" class="panel woocommerce_options_panel hidden">

		<!-- Enable Coupon -->
		<p class="form-field">
			<label for="pinaka_enable_generate_coupon">
				<?php esc_html_e( 'Enable Coupon', 'pinaka-pos' ); ?>
			</label>
			<input type="checkbox"
			       id="pinaka_enable_generate_coupon"
			       name="pinaka_enable_generate_coupon"
			       value="yes"
			       <?php checked( $enabled, 'yes' ); ?> />
		</p>

		<!-- Coupon Generate Type -->
		<p class="form-field">
			<label for="pinaka_generate_coupon_type">
				<?php esc_html_e( 'Coupon Generate Type', 'pinaka-pos' ); ?>
			</label>
			<select id="pinaka_generate_coupon_type"
			        name="pinaka_generate_coupon_type">
				<option value="">
					<?php esc_html_e( 'Select type', 'pinaka-pos' ); ?>
				</option>
				<option value="daily"   <?php selected( $generate_type, 'daily' ); ?>>
					<?php esc_html_e( 'Daily', 'pinaka-pos' ); ?>
				</option>
				<option value="weekly"  <?php selected( $generate_type, 'weekly' ); ?>>
					<?php esc_html_e( 'Weekly', 'pinaka-pos' ); ?>
				</option>
				<option value="monthly" <?php selected( $generate_type, 'monthly' ); ?>>
					<?php esc_html_e( 'Monthly', 'pinaka-pos' ); ?>
				</option>
				<option value="yearly"  <?php selected( $generate_type, 'yearly' ); ?>>
					<?php esc_html_e( 'Yearly', 'pinaka-pos' ); ?>
				</option>
			</select>
		</p>

		<!-- Coupon Generate Limit -->
		<p class="form-field">
			<label for="pinaka_generate_coupon_limit">
				<?php esc_html_e( 'Coupon Usage Limit', 'pinaka-pos' ); ?>
			</label>
			<input type="number"
			       min="0"
			       id="pinaka_generate_coupon_limit"
			       name="pinaka_generate_coupon_limit"
			       value="<?php echo esc_attr( $generate_limit ); ?>" />
		</p>

	</div>
	<?php
}


/**
 * Save Issuing Coupon tab fields
 */
add_action( 'woocommerce_coupon_options_save', 'pinaka_save_generate_coupon_tab_fields' );
function pinaka_save_generate_coupon_tab_fields( $coupon_id ) {

	// Enable Coupon
	$enabled = isset( $_POST['pinaka_enable_generate_coupon'] ) ? 'yes' : 'no';
	update_post_meta( $coupon_id, '_pinaka_enable_generate_coupon', $enabled );


	// Generate Type (Daily / Weekly / Monthly / Yearly)
	if ( isset( $_POST['pinaka_generate_coupon_type'] ) ) {
		$allowed = [ 'daily', 'weekly', 'monthly', 'yearly' ];
		$type    = sanitize_text_field( $_POST['pinaka_generate_coupon_type'] );

		if ( in_array( $type, $allowed, true ) ) {
			update_post_meta( $coupon_id, '_pinaka_generate_coupon_type', $type );
		} else {
			delete_post_meta( $coupon_id, '_pinaka_generate_coupon_type' );
		}
	}

	// Usage Limit
	if ( isset( $_POST['pinaka_generate_coupon_limit'] ) ) {
		update_post_meta(
			$coupon_id,
			'_pinaka_generate_coupon_limit',
			absint( $_POST['pinaka_generate_coupon_limit'] )
		);
	}
}

/**
 * Add custom columns to shop_coupon admin list
 */
add_filter( 'manage_edit-shop_coupon_columns', 'pinaka_add_coupon_order_columns' );
function pinaka_add_coupon_order_columns( $columns ) {

    // Insert after coupon code column
    $new_columns = [];

    foreach ( $columns as $key => $label ) {
        $new_columns[ $key ] = $label;

        if ( $key === 'coupon_code' ) {
            $new_columns['issued_order_id'] = __( 'Issued Order ID', 'pinaka-pos' );
            $new_columns['used_order_id']   = __( 'Used Order ID', 'pinaka-pos' );
        }
    }

    return $new_columns;
}

/**
 * Render Issued Order ID & Used Order ID columns
 */
add_action(
    'manage_shop_coupon_posts_custom_column',
    'pinaka_render_coupon_order_columns',
    10,
    2
);

function pinaka_render_coupon_order_columns( $column, $post_id ) {

    if ( ! in_array( $column, [ 'issued_order_id', 'used_order_id' ], true ) ) {
        return;
    }

    $coupon = new WC_Coupon( $post_id );
    $coupon_code = $coupon->get_code();

    if ( ! $coupon_code ) {
        echo '—';
        return;
    }

    /* -------------------------------------------------
	* Issued Order ID (with more / close)
	* ------------------------------------------------- */
	if ( $column === 'issued_order_id' ) {

		$orders = wc_get_orders( [
			'limit'      => -1,
			'status'     => [ 'completed', 'processing' ],
			'meta_query' => [
				[
					'key'     => 'pinaka_available_coupon_codes',
					'compare' => 'LIKE',
					'value'   => '"' . $coupon_code . '"',
				],
			],
		] );

		if ( empty( $orders ) ) {
			echo '—';
			return;
		}

		// ✅ SAFE way to get order IDs
		$order_ids = [];
		foreach ( $orders as $order ) {
			$order_ids[] = $order->get_id();
		}

		$visible = array_slice( $order_ids, 0, 2 );
		$hidden  = array_slice( $order_ids, 2 );
		$uid     = 'issued-orders-' . $post_id;

		echo '<div class="pinaka-issued-orders">';

		echo '<div class="pinaka-orders-visible">';
		foreach ( $visible as $order_id ) {
			echo '<a href="' . esc_url( get_edit_post_link( $order_id ) ) . '">
					#' . esc_html( $order_id ) . '
				</a><br>';
		}
		echo '</div>';

		if ( ! empty( $hidden ) ) {

			echo '<div id="' . esc_attr( $uid ) . '" class="pinaka-orders-hidden" style="display:none;">';
			foreach ( $hidden as $order_id ) {
				echo '<a href="' . esc_url( get_edit_post_link( $order_id ) ) . '">
						#' . esc_html( $order_id ) . '
					</a><br>';
			}
			echo '</div>';

			echo '<a href="javascript:void(0);" 
					class="pinaka-toggle-orders" 
					data-target="' . esc_attr( $uid ) . '"
					data-state="closed">
					… more
				</a>';
		}

		echo '</div>';
	}


    /* -------------------------------------------------
     * Used Order ID (actual applied coupon)
     * ------------------------------------------------- */
    if ( $column === 'used_order_id' ) {

        $orders = wc_get_orders( [
            'limit'  => -1,
            'status' => [ 'completed', 'processing' ],
        ] );

        $used = [];

        foreach ( $orders as $order ) {
            if ( in_array( $coupon_code, $order->get_coupon_codes(), true ) ) {
                $used[] = $order->get_id();
            }
        }

        if ( empty( $used ) ) {
            echo '—';
            return;
        }

        $visible_ids = array_slice( $used, 0, 2 );
        $hidden_ids  = array_slice( $used, 2 );
        $uid         = 'used-orders-' . $post_id;

        echo '<div class="pinaka-used-orders">';

        /* Visible */
        echo '<div class="pinaka-orders-visible">';
        foreach ( $visible_ids as $order_id ) {
            echo '<a href="' . esc_url( get_edit_post_link( $order_id ) ) . '">
                    #' . esc_html( $order_id ) . '
                  </a><br>';
        }
        echo '</div>';

        /* Hidden */
        if ( ! empty( $hidden_ids ) ) {

            echo '<div id="' . esc_attr( $uid ) . '" class="pinaka-orders-hidden" style="display:none;">';
            foreach ( $hidden_ids as $order_id ) {
                echo '<a href="' . esc_url( get_edit_post_link( $order_id ) ) . '">
                        #' . esc_html( $order_id ) . '
                      </a><br>';
            }
            echo '</div>';

            echo '<a href="javascript:void(0);" 
                    class="pinaka-toggle-orders" 
                    data-target="' . esc_attr( $uid ) . '"
                    data-state="closed">
                    … more
                  </a>';
        }

        echo '</div>';
    }
}


add_action( 'admin_footer', function () {
    ?>
    <script>
        document.addEventListener('click', function (e) {

            if (!e.target.classList.contains('pinaka-toggle-orders')) {
                return;
            }

            const btn = e.target;
            const targetId = btn.getAttribute('data-target');
            const target = document.getElementById(targetId);

            if (!target) return;

            const state = btn.getAttribute('data-state');

            if (state === 'closed') {
                // OPEN
                target.style.display = 'block';
                btn.textContent = 'Close';
                btn.setAttribute('data-state', 'open');
            } else {
                // CLOSE
                target.style.display = 'none';
                btn.textContent = '… more';
                btn.setAttribute('data-state', 'closed');
            }
        });
    </script>
    <?php
});

////// End of the Issueing Coupon changes //////



//Hiding Extensions Menu from WooCommerce
add_action('admin_menu', function () {
    global $menu;

    foreach ($menu as $key => $item) {
        if (isset($item[2]) && strpos($item[2], 'wc-admin&path=/extensions') !== false) {
            unset($menu[$key]);
        }
    }

	
}, 9999);

//Hiding Tools Menu from WooCommerce
add_action('admin_menu', function () {

    global $menu;

    foreach ($menu as $key => $item) {
        if (isset($item[2]) && $item[2] === 'tools.php') {
            unset($menu[$key]);
        }
    }

}, 9999);

// Hiding wp-mail-smtp plugin
add_action('admin_menu', function () {
    remove_menu_page('wp-mail-smtp');
}, PHP_INT_MAX);


//Hiding Collapse Menu
add_action('admin_head', function () {
    echo '<style>
        #collapse-menu {
            display: none !important;
        }
    </style>';
});


//Hide marketing 
// add_action('admin_menu', function () {
//     remove_menu_page('woocommerce-marketing');
// }, PHP_INT_MAX);



//********debug for submenu and main menu*******
// add_action('admin_menu', function () {
//     global $submenu;
//     echo '<pre>';
//     print_r($submenu['woocommerce']);
//     echo '</pre>';
//     exit;
// }, 9999);

// add_action('admin_menu', function () {
//     global $menu;
//     echo '<pre>';
//     print_r($menu);
//     echo '</pre>';
//     exit;
// }, 9999);


// Show field on Edit User
add_action('show_user_profile', 'pinaka_add_customer_phone_field');
add_action('edit_user_profile', 'pinaka_add_customer_phone_field');

// Show field on Add User
add_action('user_new_form', 'pinaka_add_customer_phone_field');

function pinaka_add_customer_phone_field($user) {
    $phone = '';
    if ($user instanceof WP_User) {
        $phone = get_user_meta($user->ID, 'customer_phone', true);
    }
    ?>
    <h3>Customer Phone</h3>
    <table class="form-table">
        <tr>
            <th><label for="customer_phone">Customer Phone</label></th>
            <td>
                <input type="text" name="customer_phone" id="customer_phone"
                    value="<?php echo esc_attr($phone); ?>"
                    class="regular-text" />
            </td>
        </tr>
    </table>
    <?php
}

add_action('personal_options_update', 'pinaka_save_customer_phone_field');
add_action('edit_user_profile_update', 'pinaka_save_customer_phone_field');
add_action('user_register', 'pinaka_save_customer_phone_field');

function pinaka_save_customer_phone_field($user_id) {

    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    if (isset($_POST['customer_phone'])) {
        update_user_meta(
            $user_id,
            'customer_phone',
            sanitize_text_field($_POST['customer_phone'])
        );
    }
}
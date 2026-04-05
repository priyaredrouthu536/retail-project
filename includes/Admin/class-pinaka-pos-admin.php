<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.pinaka.com/
 * @since      1.0.0
 *
 * @package    Pinaka_Pos
 * @subpackage Pinaka_Pos/admin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Ensure WordPress is properly loaded
if (!function_exists('add_action')) {
    if (!defined('ABSPATH')) {
        // Calculate the correct ABSPATH relative to this file's location
        $path = dirname(__FILE__);
        $path = str_replace('wp-content/plugins/pinaka-pos-wp/includes/Admin', '', $path);
        define('ABSPATH', $path);
    }
    require_once(ABSPATH . 'wp-load.php');
}

// Ensure WordPress is fully loaded
if (!function_exists('get_option')) {
    if (!defined('ABSPATH')) {
        // Calculate the correct ABSPATH relative to this file's location
        $path = dirname(__FILE__);
        $path = str_replace('wp-content/plugins/pinaka-pos-wp/includes/Admin', '', $path);
        define('ABSPATH', $path);
    }
    require_once(ABSPATH . 'wp-load.php');
    
    if (!function_exists('get_option')) {
        die('WordPress is not properly loaded. Please ensure this file is loaded through the main plugin file.');
    }
}

// Include WordPress core functions
require_once(ABSPATH . 'wp-includes/pluggable.php');
require_once 'Settings/SettingsPage.php';
require_once 'class-pinaka-pos-vendor.php';


/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Pinaka_Pos
 * @subpackage Pinaka_Pos/admin
 * @author     Pinakageeks <info@pinaka.com>
 */
use PinakaPos\Admin\Admin_Helper;
use PinakaPos\Admin\Settings\SettingsPage;

class Pinaka_Pos_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */

	private $license_data;
	private $license_expiry_date;
	 

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->license_data   = get_option( 'pinaka_pos_license_key_veryfied' );
		$this->license_expiry_date   = get_option( 'pinaka_pos_license_expiry_date' );
		// echo "<PRE>";
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Pinaka_Pos_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Pinaka_Pos_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/pinaka-pos-admin.css', array(), $this->version, 'all' );
	}

	// Add menu Setting
	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	 
	public function pinaka_pos_setup_menu() {
		global $dashboardPage, $shiftsPage, $vendorsPage, $paymentsPage, $settingsPage, $vendor_paymentsPage, $fast_keys;
	
		// Get saved menu settings
		$menu_settings = get_option('pinaka_pos_menu_settings', []);

		// discount menu
		
		add_menu_page('Admin Controls', 'Admin Controls', 'manage_woocommerce', 'pinaka-pos-dashboard', array($this, 'pinaka_pos_init_ui'),'dashicons-screenoptions',5);
		
		add_menu_page(
			'Discounts',
			'Discounts',
			'manage_woocommerce',
			'pinaka-pos-discounts',
			'__return_null',
			'dashicons-tag',
			6
		);

		add_submenu_page('pinaka-pos-discounts', 'Auto Discounts', 'Auto Discounts', 'manage_woocommerce', 'edit.php?post_type=discounts', '');
		
		add_submenu_page('pinaka-pos-discounts', 'Mix & Match Discounts', 'Mix & Match Discounts', 'manage_woocommerce', 'edit.php?post_type=mix_match_discounts', '');
		
		remove_submenu_page('pinaka-pos-discounts', 'pinaka-pos-discounts');
		
		// if (in_array('vendors', $menu_settings)) {
			add_submenu_page('pinaka-pos-dashboard', 'Vendor Directory', 'Vendor Directory', 'manage_woocommerce', 'edit.php?post_type=vendor', '');
		// }
	
		// if (in_array('vendor_payments', $menu_settings)) {
			// add_submenu_page('pinaka-pos-dashboard', 'Vendor Payments', 'Vendor Payments', 'manage_options', 'edit.php?post_type=vendor_payments', '');
		// }
	
		// if (in_array('shifts', $menu_settings)) {
			add_submenu_page('pinaka-pos-dashboard', 'Shift Management', 'Shift Management', 'manage_woocommerce', 'edit.php?post_type=shifts', '');
		// }
	
		// if (in_array('payments', $menu_settings)) {
			add_submenu_page('pinaka-pos-dashboard', 'Payment Records', 'Payment Records', 'manage_woocommerce', 'edit.php?post_type=payments', '');
		// }
		add_submenu_page('pinaka-pos-dashboard', 'Fast Keys', 'Fast Keys', 'manage_woocommerce', 'edit.php?post_type=fast_keys', '');

		//add_submenu_page('pinaka-pos-dashboard', 'Safe', 'Safe', 'manage_options', 'edit.php?post_type=safes', '');

		add_submenu_page('pinaka-pos-dashboard', 'Safe Drop', 'Safe Drop', 'manage_woocommerce', 'edit.php?post_type=safedrops', '');

		add_submenu_page('pinaka-pos-dashboard', 'Site Logs', 'Site Logs', 'manage_woocommerce', 'site-logs', array($this, 'render_logs_page'));
		
	add_submenu_page('pinaka-pos-dashboard', 'Warehouse', 'Warehouse', 'manage_woocommerce', 'edit.php?post_type=warehouse', '');
	
		// if (in_array('settings', $menu_settings)) {
			add_submenu_page('pinaka-pos-dashboard', 'Settings', 'Settings', 'manage_woocommerce', 'pinaka-pos-settings', [$this, 'render']);
		// }
	}
	

	public function pinaka_pos_init_ui() {
		load_template( dirname( __FILE__ ) . '/templates/pinaka-pos-admin-dashboard.php' );
	}

	public function render() {
		// Instantiate the SettingsPage class with required parameters
		$settings_page = new SettingsPage($this->plugin_name, $this->version);    
		// Call the render method of SettingsPage
		$settings_page->render();
	}

	public function vendorRender() {
		// Instantiate the SettingsPage class with required parameters
		$settings_page = new Pinaka_POS_Vendor($this->plugin_name, $this->version);    
		// Call the render method of SettingsPage
		$settings_page->vendorRender();
	}

	/**
	 * Check for activation.
	 */
    
	public function handle_registration() {
		$license_key =  get_option( 'pinaka_pos_license_key' );
		// Bail if already connected.
		if ( ! empty( $license_key ) ) {
		    return;
		}

		$nonce = Admin_Helper::get( 'nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'pinaka_pos_register_product' ) ) {
			return;
		}

		$status = Admin_Helper::get( 'status' );
        if ($status && $redirect_to = Admin_Helper::get_registration_url($status)) { //phpcs:ignore
			wp_redirect( $redirect_to );
			exit;
		}
	}

	/**
	 * Register the admin menu and settings page.
	 *
	 * @since    1.0.0
	 */
	function render_logs_page() {
		global $wpdb;
		$logs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}post_full_logs ORDER BY created_at DESC LIMIT 50");

		echo '<div class="wrap"><h1>Post Logs</h1><table class="widefat"><thead><tr><th>Time</th><th>User</th><th>Action</th><th>Post ID</th><th>Type</th><th>Data</th></tr></thead><tbody>';
		foreach ($logs as $log) {
			$user = get_userdata($log->user_id);
			echo '<tr>';
			echo "<td>{$log->created_at}</td>";
			echo "<td>" . ($user ? $user->user_login : 'System') . "</td>";
			echo "<td>{$log->action}</td>";
			echo "<td>{$log->post_id}</td>";
			echo "<td>{$log->post_type}</td>";
			echo "<td><details><summary>View JSON</summary><pre style='white-space:pre-wrap;'>".esc_html($log->data)."</pre></details></td>";
			echo '</tr>';
		}
		echo '</tbody></table></div>';
	}	

}

<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.pinaka.com/
 * @since      1.0.0
 *
 * @package    Pinaka_Pos
 * @subpackage Pinaka_Pos/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Pinaka_Pos
 * @subpackage Pinaka_Pos/includes
 * @author     Pinakageeks <info@pinaka.com>
 */
class Pinaka_Pos_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		self::create_customer_balance_tb();
		// self::pinaka_plugin_activate_create_tags();
		self::create_loyalty_tables();
		$loyalty_plugin = 'pinaka-loyalty/pinaka-loyalty.php';

		// Load plugin functions
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		// Check if plugin exists and is not already active
		if ( file_exists( WP_PLUGIN_DIR . '/' . $loyalty_plugin ) && !is_plugin_active( $loyalty_plugin ) ) {
			activate_plugin( $loyalty_plugin );
		}
	}


	function pinaka_plugin_activate_create_tags() {
		// The tags you want to create (label => slug) — change as needed
		$tags_to_create = array(
			'Age Restricted'         => '18',
			'EBT Eligible'          => 'ebt-eligible'
		);

		// Ensure WooCommerce taxonomy exists
		if ( ! taxonomy_exists( 'product_tag' ) ) {
			// taxonomy not registered yet; try again later
			return;
		}

		foreach ( $tags_to_create as $label => $slug ) {
			$label = trim( (string) $label );
			$slug  = sanitize_title( $slug );

			if ( $label === '' ) {
				continue;
			}

			// check if term exists by slug or name
			$term = term_exists( $slug, 'product_tag' );
			if ( $term === 0 || $term === null ) {
				// Create term
				$result = wp_insert_term( $label, 'product_tag', array(
					'slug' => $slug,
				) );

				// Optional: log failures for debugging (remove in production)
				// if ( is_wp_error( $result ) ) {
				//     error_log( 'Pinaka: could not create tag ' . $label . ' — ' . $result->get_error_message() );
				// }
			}
		}
	}

	function pinaka_create_product_tags_if_needed() {
		$flag = get_option( 'pinaka_create_product_tags_on_activate', false );
		if ( ! $flag ) {
			return;
		}

		// The tags you want to create (label => slug) — change as needed
		$tags_to_create = array(
			'Takeaway'         => 'takeaway',
			'Dine In'          => 'dine-in',
			'Age Restricted'   => 'age-restricted',
			'Fragile'          => 'fragile',
			'Wholesale'        => 'wholesale',
		);

		// Ensure WooCommerce taxonomy exists
		if ( ! taxonomy_exists( 'product_tag' ) ) {
			// taxonomy not registered yet; try again later
			return;
		}

		foreach ( $tags_to_create as $label => $slug ) {
			$label = trim( (string) $label );
			$slug  = sanitize_title( $slug );

			if ( $label === '' ) {
				continue;
			}

			// check if term exists by slug or name
			$term = term_exists( $slug, 'product_tag' );
			if ( $term === 0 || $term === null ) {
				// Create term
				$result = wp_insert_term( $label, 'product_tag', array(
					'slug' => $slug,
				) );

				// Optional: log failures for debugging (remove in production)
				// if ( is_wp_error( $result ) ) {
				//     error_log( 'Pinaka: could not create tag ' . $label . ' — ' . $result->get_error_message() );
				// }
			}
		}

		// remove the flag so we don't run again
		delete_option( 'pinaka_create_product_tags_on_activate' );
	}
	/**
	 * To create customer balance table to log data.
	 */
	public static function create_customer_balance_tb() {

		global $wpdb;

		$table_name = $wpdb->prefix . 'pp_customer_balance';

		// Check if the table already exists .
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {

			// Create the table.
			$sql = "CREATE TABLE $table_name (
                id INT NOT NULL AUTO_INCREMENT,
                user_id INT NOT NULL,
                order_id INT,
                due_date DATE NOT NULL,
                payment_method VARCHAR(255) NOT NULL,
                remark TEXT,
                created_date DATETIME NOT NULL,
                created_date_gmt DATETIME NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                total DECIMAL(10,2) NOT NULL,
                status VARCHAR(20) NOT NULL,
                PRIMARY KEY (id)
              );";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}
	}

	/**
	 * Create Loyalty Tables.
	 */
	public static function create_loyalty_tables() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();

		// $table_users  = "{$wpdb->prefix}pinaka_loyalty_users";
		$table_points = "{$wpdb->prefix}pinaka_loyalty_points";

		// $sql_users = "CREATE TABLE IF NOT EXISTS $table_users (
		// 	id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		// 	user_id BIGINT(20) UNSIGNED,
		// 	mobile_no VARCHAR(20) DEFAULT NULL,
		// 	total_credited FLOAT DEFAULT 0,
		// 	total_redeemed FLOAT DEFAULT 0,
		// 	total_expired FLOAT DEFAULT 0,
		// 	available_points FLOAT DEFAULT 0,
		// 	updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		// ) $charset;";

		// $sql_points = "CREATE TABLE IF NOT EXISTS $table_points (
		// 	id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		// 	user_id BIGINT(20) UNSIGNED,
		// 	mobile_no VARCHAR(20) DEFAULT NULL,
		// 	contact VARCHAR(100) DEFAULT NULL,
		// 	order_id BIGINT(20) UNSIGNED,
		// 	credited_points FLOAT DEFAULT 0,
		// 	redeemed_points FLOAT DEFAULT 0,
		// 	expired_points FLOAT DEFAULT 0,
		// 	points_created_date DATETIME NULL,
		// 	points_expiry_date DATETIME NULL,
		// 	points_status VARCHAR(20) DEFAULT 'active',
		// 	created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		// 	updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
		// ) $charset;";
		$sql_points = "CREATE TABLE IF NOT EXISTS $table_points (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			user_id BIGINT(20) UNSIGNED,
			contact VARCHAR(100) DEFAULT NULL,
			order_id BIGINT(20) UNSIGNED,
			credited_points FLOAT DEFAULT 0,
			redeemed_points FLOAT DEFAULT 0,
			expired_points FLOAT DEFAULT 0,
			points_created_date DATETIME NULL,
			points_expiry_date DATETIME NULL,
			points_status VARCHAR(20) DEFAULT 'active',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
		) $charset;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		// dbDelta( $sql_users );
		dbDelta( $sql_points );
	}

}

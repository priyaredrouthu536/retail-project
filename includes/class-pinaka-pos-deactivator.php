<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://www.pinaka.com/
 * @since      1.0.0
 *
 * @package    Pinaka_Pos
 * @subpackage Pinaka_Pos/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Pinaka_Pos
 * @subpackage Pinaka_Pos/includes
 * @author     Pinakageeks <info@pinaka.com>
 */
class Pinaka_Pos_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	// public static function deactivate() {
	// 	include_once ABSPATH . 'wp-admin/includes/plugin.php';
	// 	global $wpdb;

	// 	$loyalty_plugin = 'pinaka-loyalty/pinaka-loyalty.php';

	// 	if (is_plugin_active($loyalty_plugin)) {
	// 		deactivate_plugins($loyalty_plugin, false);
	// 	}

	// 	// $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pinaka_loyalty_users");
	// 	$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pinaka_loyalty_points");

	// 	$options = [
	// 		'pinaka_loyalty_ratio_dollar',
	// 		'pinaka_loyalty_ratio_point',
	// 		'pinaka_loyalty_redeem_point',
	// 		'pinaka_loyalty_redeem_amt',
	// 		'pinaka_loyalty_expiry_days',
	// 		'pinaka_loyalty_conflict_rule',
	// 		'pinaka_loyalty_min_spend',
	// 		'pinaka_loyalty_max_spend',
	// 		'pinaka_loyalty_min_points',
	// 		'pinaka_loyalty_max_points'
	// 	];

	// 	foreach ($options as $option_name) {
	// 		delete_option($option_name);
	// 	}
	// 	$meta_keys = [
	// 		'pinaka_total_credited',
	// 		'pinaka_total_redeemed',
	// 		'pinaka_available_points',
	// 		'pinaka_last_updated'
	// 	];

	// 	foreach ($meta_keys as $meta_key) {
	// 		$wpdb->query(
	// 			$wpdb->prepare(
	// 				"DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s",
	// 				$meta_key
	// 			)
	// 		);
	// 	}

	// 	wp_clean_plugins_cache(true);
	// 	wp_cache_flush();
	// 	clearstatcache();
	// }
	public static function deactivate() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		global $wpdb;

		// Path to dependent plugin (adjust if needed)
		$loyalty_plugin = 'pinaka-loyalty/pinaka-loyalty.php';

		// Defer the dependent plugin deactivation until after current deactivation finishes
		add_action('shutdown', function() use ($loyalty_plugin) {
			if (is_plugin_active($loyalty_plugin)) {
				deactivate_plugins($loyalty_plugin, true, false);
				$timestamp = wp_next_scheduled('pinaka_loyalty_expire_points_event');

				if ($timestamp) {
					wp_clear_scheduled_hook('pinaka_loyalty_expire_points_event');
				}
			}
		});

		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pinaka_loyalty_points");

		$options = [
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

		foreach ($options as $option_name) {
			delete_option($option_name);
		}

		$meta_keys = [
			'pinaka_total_credited',
			'pinaka_total_redeemed',
			'pinaka_total_expired',
			'pinaka_available_points',
			'pinaka_last_updated'
		];

		foreach ($meta_keys as $meta_key) {
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s",
					$meta_key
				)
			);
		}

		wp_clean_plugins_cache(true);
		wp_cache_flush();
		clearstatcache();
	}
}
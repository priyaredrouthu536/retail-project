<?php
/**
 * Plugin Name:       Pinaka POS
 * Plugin URI:        https://pinakapos.com/
 * Description:       A plugin to integrate a POS system with WordPress and manage data like payments, shifts, vendors, and more.
 * Version:           1.0.0
 * Requires at least: 6.1
 * Tested up to:      6.1
 * Requires PHP:      8.2
 * Author:            Pinaka POS
 * Author URI:        https://pinakapos.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       pinaka-pos-wp
 * Domain Path:       /languages/
 *
 * @package Pinaka POS
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit();

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PINAKA_POS_VERSION', '1.0.0' );

// Define constants.
define('PINAKA_POS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PINAKA_POS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files.
require_once PINAKA_POS_PLUGIN_DIR . 'includes/admin-functions.php';
// require_once PINAKA_POS_PLUGIN_DIR . 'includes/api-functions.php';
// require_once PINAKA_POS_PLUGIN_DIR . 'includes/public-functions.php';
// require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/Settings/Page.php';
require_once PINAKA_POS_PLUGIN_DIR . 'includes/class-pinaka-pos.php';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-pinaka-pos-activator.php
 */
function activate_pinaka_pos() {
	require_once PINAKA_POS_PLUGIN_DIR . 'includes/class-pinaka-pos-activator.php';
	Pinaka_Pos_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-pinaka-pos-deactivator.php
 */
function deactivate_pinaka_pos() {
	require_once PINAKA_POS_PLUGIN_DIR . 'includes/class-pinaka-pos-deactivator.php';
	Pinaka_Pos_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_pinaka_pos' );
register_deactivation_hook( __FILE__, 'deactivate_pinaka_pos' );


// Initialize the plugin.
function pinaka_pos_plugin_init() {
    // Add hooks and actions here.
    $plugin = new Pinaka_Pos();
	$plugin->run();
}

add_action('init', 'pinaka_pos_plugin_init');

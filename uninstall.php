<?php
/**
 * Pinakaposwp Uninstall
 *
 * Uninstalling Pinakapos deletes user roles, tables, and options.
 *
 * @package Pinakaposwp\Uninstaller
 * @version 1.1.0
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/*
 * Only remove ALL product and page data if PINAKA_POS_DATA constant is set to true in user's
 * wp-config.php. This is to prevent data loss when deleting the plugin from the backend
 * and to ensure only the site owner can perform this action.
 */
if ( defined( 'PPW_REMOVE_ALL_DATA' ) && true === PPW_REMOVE_ALL_DATA ) {
	\Pinakaposwp\Installer::uninstall();
}

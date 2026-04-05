<?php
/**
 * Admin helper Functions.
 *
 * This file contains functions needed on the admin screens.
 *
 * @since      0.9.0
 * @package    PinakaPos
 * @subpackage PinakaPos\Admin
 */

namespace PinakaPos\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Admin_Helper class.
 */
class Admin_Helper {

	/**
	 * Get product activation URL.
	 *
	 * @param string $redirect_to Redirect to url.
	 *
	 * @return string Activate URL.
	 */
	public static function get_activate_url( $redirect_to = null ) {
		if ( empty( $redirect_to ) ) {
		    $redirect_to = self::add_query_arg_raw(
		        array(
		            'page'  => 'pinakapos',
		            'nonce' => wp_create_nonce( 'pinaka_pos_register_product' ),
		        ),
		        ( is_multisite() && is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) ? network_admin_url( 'admin.php' ) : admin_url( 'admin.php' )
		    );
		} else {
		    $redirect_to = self::add_query_arg_raw(
		        array(
		            'nonce' => wp_create_nonce( 'pinaka_pos_register_product' ),
		        ),
		        $redirect_to
		    );
		}

		$args = array(
		    'site'           => rawurlencode( site_url() ),
		    'r'              => rawurlencode( $redirect_to ),
		    'POS_CONNECTING' => 'true',
		);

		return self::add_query_arg_raw( $args, 'https://mg.techkumard.com/login' );
	}

	/**
	 * Handle activation.
	 *
	 * @param  string $status Status parameter.
	 */
	public static function get_registration_url( $status ) {
		if ( 'cancel' ===  $status ) {
		}
		if ( 'banned' ===  $status ) {
		}
		if ( 'ok' ===  $status  &&  self::get_registration_params() ) {
			return self::remove_query_arg_raw( array( 'status', 'license_key', 'nonce', 'item_id' ) );
		}

		return false;

	}
	/**
	 * Check if 'license_key and item_id' contains all the data we need, in the
	 * correct format.
	 *
	 * @return bool|array Whether the input is valid.
	 */
	public static function get_registration_params() {
		$license_key = self::get( 'license_key' );
		$item_id     = self::get( 'item_id' );
		if ( false ===   $license_key )  {
			return false;
		}
		if ( false === absint( $item_id ) ) {
			return false;
		}
		delete_option( 'pinaka_pos_license_key' );
		delete_option( 'pinaka_pos_item_id' );

		add_option( 'pinaka_pos_license_key',  $license_key );
		add_option( 'pinaka_pos_item_id', absint( $item_id ) );

		return $license_key;
	}


	/**
	 * Check if siteurl & home options are both valid URLs.
	 *
	 * @return boolean
	 */
	public static function is_site_url_valid() {
		
		return (bool) filter_var( get_option( 'siteurl' ), FILTER_VALIDATE_URL ) && (bool) filter_var( get_option( 'home' ), FILTER_VALIDATE_URL );
	}

	/**
	 * Maybe show notice about invalid siteurl.
	 */
	public static function maybe_show_invalid_siteurl_notice() {
		if ( ! self::is_site_url_valid() ) {
			?>
		<p class="notice notice-warning notice-alt notice-connect-disabled">
					<?php
						printf(
							// Translators: 1 is "WordPress Address (URL)", 2 is "Site Address (URL)", 3 is a link to the General Settings, with "WordPress General Settings" as anchor text.
							esc_html__( 'PinakaPos cannot be connected because your site URL doesn\'t appear to be a valid URL. If the domain name contains special characters, please make sure to use the encoded version in the %1$s &amp; %2$s fields on the %3$s page.', 'pinaka-pos' ),
							'<strong>' . esc_html__( 'WordPress Address (URL)', 'pinaka-pos' ) . '</strong>',
							'<strong>' . esc_html__( 'Site Address (URL)', 'pinaka-pos' ) . '</strong>',
							'<a href="' . esc_url( admin_url( 'options-general.php' ) ) . '">' . esc_html__( 'WordPress General Settings', 'pinaka-pos' ) . '</a>'
						);
					?>
		</p>
			<?php
		}
	}

	public static function getPOSUrl() {
		return 'https://mg.techkumard.com/wp-json/pinaka/v1/activate-license/';
	}


	public static function get_license_data() {

		// $license_key =  get_option( 'pinaka_pos_license_key' );
		// $side_url    = esc_url( site_url() );
		// $item_id     = absint( get_option( 'pinaka_pos_item_id' ) );
		// $url         =  self::getPOSUrl();

		// $url_params = array(
		// 	'edd_action' => 'activate_license',
		// 	'item_id' => $item_id,
		// 	'license' => $license_key,
		// 	'url' => $side_url
		// );
		// echo $request_url = $url . '/?' . http_build_query( $url_params );
		// $response = wp_remote_get( $request_url ); 

		// if ( is_wp_error( $response ) ) {
		// 	$error_message =  $response->get_error_message();

		// 	// handle error
		// } else {
		// 	$response_body = wp_remote_retrieve_body( $response );
		// 	$responses = json_decode( $response_body , true );
		// }
		// print_r($responses);

		// return $responses;

		$license_key = get_option('pinaka_pos_license_key');
		$item_id     = absint( get_option( 'pinaka_pos_item_id' ) );
		$site_url = esc_url( site_url() );
		$api_url = self::getPOSUrl();

		$response = wp_remote_post($api_url, array(
			'body' => json_encode([
				'license' => $license_key,
				'url' => $site_url,
				'item_id' => $item_id
			]),
			'headers' => [
				'Content-Type' => 'application/json',
				'Accept' => 'application/json'
			],
			'timeout' => 10
		));

		if (is_wp_error($response)) {
			return 'Error connecting to the license server.';
		}

		$body = wp_remote_retrieve_body($response);
		$data = json_decode($body, true);

		return isset($data) ? $data : 'Unknown response';

	}
	/**
	 * Add query arg
	 *
	 * @param mixed ...$args Array of arguments.
	 *
	 * @return string
	 */
	public static function add_query_arg( ...$args ) {
		return esc_url( add_query_arg( ...$args ) );
	}

	/**
	 * Add query arg
	 *
	 * @param mixed ...$args Array of arguments.
	 *
	 * @return string
	 */
	public static function add_query_arg_raw( ...$args ) {
		return esc_url_raw( add_query_arg( ...$args ) );
	}

	/**
	 * Removes an item or items from a query string.
	 *
	 * @param string|array   $key    (Required) Query key or keys to remove.
	 * @param string|boolean $query  When false uses the current URL.
	 *
	 * @return string
	 */
	public static function remove_query_arg( $key, $query = false ) {
		return esc_url( remove_query_arg( $key, $query ) );
	}

	/**
	 * Removes an item or items from a query string.
	 *
	 * @param string|array   $key    (Required) Query key or keys to remove.
	 * @param string|boolean $query  When false uses the current URL.
	 *
	 * @return string
	 */
	public static function remove_query_arg_raw( $key, $query = false ) {
		return esc_url_raw( remove_query_arg( $key, $query ) );
	}



	/**
	 * Get field from query string.
	 *
	 * @param string $id      Field id to get.
	 * @param mixed  $default Default value to return if field is not found.
	 * @param int    $filter  The ID of the filter to apply.
	 * @param int    $flag    The ID of the flag to apply.
	 *
	 * @return mixed
	 */
	public static function get( $id, $default = false, $filter = FILTER_DEFAULT, $flag = array() ) {
		return filter_has_var( INPUT_GET, $id ) ? filter_input( INPUT_GET, $id, $filter, $flag ) : $default;
	}

	/**
	 * Get field from FORM post.
	 *
	 * @param string $id      Field id to get.
	 * @param mixed  $default Default value to return if field is not found.
	 * @param int    $filter  The ID of the filter to apply.
	 * @param int    $flag    The ID of the flag to apply.
	 *
	 * @return mixed
	 */
	public static function post( $id, $default = false, $filter = FILTER_DEFAULT, $flag = array() ) {
		return filter_has_var( INPUT_POST, $id ) ? filter_input( INPUT_POST, $id, $filter, $flag ) : $default;
	}

	/**
	 * Get field from request.
	 *
	 * @param string $id      Field id to get.
	 * @param mixed  $default Default value to return if field is not found.
	 * @param int    $filter  The ID of the filter to apply.
	 * @param int    $flag    The ID of the flag to apply.
	 *
	 * @return mixed
	 */
	public static function request( $id, $default = false, $filter = FILTER_DEFAULT, $flag = array() ) {
		return isset( $_REQUEST[ $id ] ) ? filter_var( $_REQUEST[ $id ], $filter, $flag ) : $default;
	}

	/**
	 * Get field from FORM server.
	 *
	 * @param string $id      Field id to get.
	 * @param mixed  $default Default value to return if field is not found.
	 * @param int    $filter  The ID of the filter to apply.
	 * @param int    $flag    The ID of the flag to apply.
	 *
	 * @return mixed
	 */
	public static function server( $id, $default = false, $filter = FILTER_DEFAULT, $flag = array() ) {
		return isset( $_SERVER[ $id ] ) ? filter_var( $_SERVER[ $id ], $filter, $flag ) : $default;
	}
}

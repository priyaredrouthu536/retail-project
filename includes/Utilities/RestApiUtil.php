<?php

namespace PinakaPosWp\Utilities;

defined( 'ABSPATH' ) || exit;

/**
 * Class RestApiUtil
 *
 * @since 1.0.0
 */
class RestApiUtil {

	/**
	 * Utility function to get the response from the API.
	 *
	 * @param string $path The path to send the request to.
	 * @param array  $args The arguments to send with the request.
	 * @param string $method The method to use for the request.
	 *
	 * @since 1.0.0
	 * @return mixed
	 */
	public static function get_response( $path, $args = array(), $method = 'GET' ) {
		$endpoint = get_rest_url( null, $path );
		$request  = new \WP_REST_Request( $method, $endpoint );
		if ( ! empty( $args ) ) {
			if ( 'GET' === $method ) {
				$request->set_query_params( $args );
			} else {
				$request->set_body_params( $args );
			}
		}

		$response = rest_do_request( $request );
		$server   = rest_get_server();
		$json     = wp_json_encode( $server->response_to_data( $response, false ) );
		return json_decode( $json, true );
	}
}

<?php
/**
 * Plugin Name: Pinaka POS Promotion Images API (Public)
 * Description: Exposes a public REST API endpoint to fetch promotion images.
 * Version: 1.0.2
 * Author: You
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pinaka_Promotion_Image_Api_Controller {

	protected $namespace = 'pinaka-pos/v1';
	protected $rest_base = 'promotion-images';

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'pinaka_rest_get_promotion_images' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function pinaka_rest_get_promotion_images( WP_REST_Request $request ) {
		$option_key = 'pinaka_pos_promotion_images';
		$items      = get_option( $option_key, array() );

		if ( ! is_array( $items ) ) {
			$items = array();
		}

		$result = array();

		foreach ( $items as $item ) {
			if ( is_numeric( $item ) && intval( $item ) ) {
				$id  = intval( $item );
				$url = wp_get_attachment_url( $id );
				if ( ! $url ) {
					continue;
				}

				$alt     = get_post_meta( $id, '_wp_attachment_image_alt', true );
				$title   = get_the_title( $id );
				$caption = wp_get_attachment_caption( $id );

				$thumb = wp_get_attachment_image_src( $id, 'thumbnail' );
				$full  = wp_get_attachment_image_src( $id, 'full' );

				$result[] = array(
					'id'      => $id,
					'url'     => esc_url( $url ),
					'alt'     => $alt ? wp_kses_post( $alt ) : '',
					'title'   => $title ? wp_kses_post( $title ) : '',
					'caption' => $caption ? wp_kses_post( $caption ) : '',
					'sizes'   => array(
						'thumbnail' => $thumb ? esc_url( $thumb[0] ) : '',
						'full'      => $full ? esc_url( $full[0] ) : '',
					),
				);
			} else {
				$url = esc_url_raw( $item );
				if ( empty( $url ) ) {
					continue;
				}
				$result[] = array(
					'id'      => null,
					'url'     => esc_url( $url ),
					'alt'     => '',
					'title'   => '',
					'caption' => '',
					'sizes'   => array(),
				);
			}
		}

		return rest_ensure_response( $result );
	}
}

// Register route
add_action( 'rest_api_init', function () {
	$controller = new Pinaka_Promotion_Image_Api_Controller();
	$controller->register_routes();
} );

// EARLY bypass of rest authentication for our public route.
// Priority 1 so it runs before most auth handlers (including JWT plugins).
add_filter( 'rest_authentication_errors', function( $result ) {
	// determine request path; fall back to REQUEST_URI if needed
	$path = '';
	if ( function_exists( 'get_rest_url' ) ) {
		// Try to build the route path relative to site root
		$rest_prefix = rest_get_url_prefix(); // 'wp-json' normally
		$path = '/' . $rest_prefix . '/pinaka-pos/v1/promotion-images';
	}

	// Also check the server REQUEST_URI in case rest_get_url_prefix isn't available
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';

	// If either matches, bypass auth by returning false (no error)
	if ( ( $path && false !== strpos( $request_uri, $path ) ) || false !== strpos( $request_uri, '/wp-json/pinaka-pos/v1/promotion-images' ) ) {
		return false;
	}

	// otherwise return the original result (could be null or WP_Error)
	return $result;
}, 1 );
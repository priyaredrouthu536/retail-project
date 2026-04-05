<?php
/**
 * REST API FastKeys API controller
 *
 * Handles requests to the FastKeys API endpoint.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API FastKeys Controller class.
 */
class Pinaka_FastKeys_Api_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'pinaka-pos/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'fastkeys';

	/**
	 * Register the routes for FastKeys.
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/create',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_fast_key_api' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/add-products',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'add_products_to_fast_key' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-by-user',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_fastkeys_by_user' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-by-fastkey-id/(?P<fastkey_id>\d+)?',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_products_by_fastkey_id' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-by-name/(?P<employee_name>[a-zA-Z0-9-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_fastkeys_by_name' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/delete-fastkey/(?P<fastkey_id>\d+)?', 
			array(
				'methods'  => 'GET',
				'callback' => array($this, 'delete_fast_key_api'), // Ensure method is inside class
				'permission_callback' => array($this, 'check_user_role_permission'),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/update-fastkey-products',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_fastkey_products' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/update-fastkey',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_fastkey' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/delete-product',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'delete_product_from_fast_key' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-all-fastkeys-products',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_all_fastkeys_products' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-all-fastkeys-images',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_all_fastkeys_images' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);
	}

	/**
	 * Check whether a given request has permission to view system status.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function check_user_role_permission( $request ) {

		$user_id = get_current_user_id();
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
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

		return new WP_Error(
			'pinakapos_rest_cannot_view',
			esc_html__( 'Sorry, you cannot give permission.', 'pinaka-pos' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}


	function create_fast_key_api( WP_REST_Request $request ) {
		$params  = $request->get_params();
		$user_id = get_current_user_id();

		// Validate required fields
		if ( empty( $params['fastkey_title'] ) ) {
			return new WP_REST_Response( [ 'message' => 'Missing required fields' ], 400 );
		}

		$fastkey_title = sanitize_text_field( $params['fastkey_title'] );

		// Check for duplicate title in 'fast_keys' post type FOR THIS USER only
		$existing = get_posts( [
			'post_type'      => 'fast_keys',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			's'              => $fastkey_title, // search by title
			'meta_query'     => [
				[
					'key'     => '_fast_keys_user_id',
					'value'   => $user_id,
					'compare' => '=',
				],
			],
		] );

		// Double-check exact title match (since 's' is partial search)
		if ( ! empty( $existing ) ) {
			foreach ( $existing as $id ) {
				// Decode HTML entities and strip tags for both titles before comparing
				$existing_title_raw = get_the_title( $id );
				$existing_title = html_entity_decode( wp_strip_all_tags( $existing_title_raw ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				$new_title = html_entity_decode( wp_strip_all_tags( $fastkey_title ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

				if ( strtolower( $existing_title ) === strtolower( $new_title ) ) {
					return new WP_REST_Response( [
						'status'  => 'error',
						'message' => 'You already have a Fast Key with this title.',
					], 409 );
				}
			}
		}

		// Insert new Fast Key post
		$post_id = wp_insert_post( [
			'post_title'  => $fastkey_title,
			'post_type'   => 'fast_keys',
			'post_status' => 'publish',
		] );

		if ( $post_id ) {
			// Store meta fields
			update_post_meta( $post_id, '_fast_keys_user_id', $user_id );
			update_post_meta( $post_id, '_fast_key_index', intval( $params['fastkey_index'] ) );

			// Handle image
			$fastkey_image_url = '';
			if ( ! empty( $params['fastkey_image'] ) ) {
				$fastkey_image_url = esc_url_raw( $params['fastkey_image'] );

				$attachment_id = attachment_url_to_postid( $fastkey_image_url );
				update_post_meta( $post_id, '_fast_key_image', $fastkey_image_url );

				if ( $attachment_id ) {
					set_post_thumbnail( $post_id, $attachment_id );
				}
			}

			return new WP_REST_Response( [
				'status'        => 'success',
				'message'       => 'Fast Key created',
				'fastkey_id'    => $post_id,
				'fastkey_title' => $fastkey_title,
				'fastkey_index' => intval( $params['fastkey_index'] ),
				'fastkey_image' => $fastkey_image_url ?: site_url() . '/wp-content/plugins/pinaka-pos-wp/assests/images/no-image.png',
			], 201 );
		}

		return new WP_REST_Response( [ 'message' => 'Error creating Fast Key', 'status' => 'error' ], 500 );
	}



	// function update_fastkey(WP_REST_Request $request) {
	// 	$params = $request->get_params();
	// 	$user_id = get_current_user_id();

	// 	if (empty($params['fastkey_id'])) {
	// 		return new WP_REST_Response(['message' => 'Missing fastkey_id'], 400);
	// 	}

	// 	$fastkey_id = intval($params['fastkey_id']);
	// 	$post = get_post($fastkey_id);

	// 	if (!$post || $post->post_type !== 'fast_keys') {
	// 		return new WP_REST_Response(['message' => 'Fast Key not found'], 404);
	// 	}

	// 	// Check for duplicate title if updating title
	// 	if (!empty($params['fastkey_title'])) {
	// 		$new_title = sanitize_text_field($params['fastkey_title']);

	// 		$existing = get_posts(array(
	// 			'post_type'   => 'fast_keys',
	// 			'post_status' => 'publish',
	// 			'title'       => $new_title,
	// 			'fields'      => 'ids',
	// 			'numberposts' => 1,
	// 			'exclude'     => array($fastkey_id),
	// 		));

	// 		if (!empty($existing)) {
	// 			return new WP_REST_Response([
	// 				'status'  => 'error',
	// 				'message' => 'A Fast Key with this title already exists.',
	// 			], 409);
	// 		}

	// 		// Update title
	// 		wp_update_post([
	// 			'ID'         => $fastkey_id,
	// 			'post_title' => $new_title,
	// 		]);
	// 	}

	// 	// Update index if provided
	// 	if (isset($params['fastkey_index'])) {
	// 		$new_index = intval($params['fastkey_index']);

	// 		// Get all fast_keys sorted by index
	// 		$all_fastkeys = get_posts([
	// 			'post_type'      => 'fast_keys',
	// 			'posts_per_page' => -1,
	// 			'meta_key'       => '_fast_key_index',
	// 			'orderby'        => 'meta_value_num',
	// 			'order'          => 'ASC',
	// 			'post_status'    => 'publish',
	// 		]);

	// 		// Rebuild ordered array excluding the current fastkey
	// 		$reordered = [];
	// 		foreach ($all_fastkeys as $fastkey) {
	// 			if ($fastkey->ID != $fastkey_id) {
	// 				$reordered[] = $fastkey->ID;
	// 			}
	// 		}

	// 		// Clamp new_index between 1 and count+1
	// 		$new_index = max(1, min($new_index, count($reordered) + 1));
	// 		array_splice($reordered, $new_index - 1, 0, [$fastkey_id]);

	// 		// Reassign indexes
	// 		foreach ($reordered as $i => $id) {
	// 			update_post_meta($id, '_fast_key_index', $i + 1);
	// 		}
	// 	}

	// 	// Update image if provided
	// 	$fastkey_image_url = '';
	// 	if (!empty($params['fastkey_image'])) {
	// 		$fastkey_image_url = $params['fastkey_image'];
	// 		$attachment_id = attachment_url_to_postid($fastkey_image_url);
	// 		update_post_meta($fastkey_id, '_fast_key_image', $fastkey_image_url);

	// 		if ($attachment_id) {
	// 			set_post_thumbnail($fastkey_id, $attachment_id);
	// 		}
	// 	}

	// 	return new WP_REST_Response([
	// 		'status'        => 'success',
	// 		'message'       => 'Fast Key updated',
	// 		'fastkey_id'    => $fastkey_id,
	// 		'fastkey_title' => get_the_title($fastkey_id),
	// 		'fastkey_index' => intval(get_post_meta($fastkey_id, '_fast_key_index', true)),
	// 		'fastkey_image' => get_the_post_thumbnail_url($fastkey_id, 'full') ?: site_url() . '/wp-content/plugins/pinaka-pos-wp/assests/images/no-image.png',
	// 	], 200);
	// }

	function update_fastkey( WP_REST_Request $request ) {	
		$params  = $request->get_params();
		$user_id = get_current_user_id();

		if ( empty( $params['fastkey_id'] ) ) {
			return new WP_REST_Response( [ 'message' => 'Missing fastkey_id' ], 400 );
		}

		$fastkey_id = (int) $params['fastkey_id'];
		$post       = get_post( $fastkey_id );

		if ( ! $post || $post->post_type !== 'fast_keys' ) {
			return new WP_REST_Response( [ 'message' => 'Fast Key not found' ], 404 );
		}

		$owner_id = get_post_meta( $fastkey_id, '_fast_keys_user_id', true );
		if ( (string) $owner_id !== (string) $user_id ) {
			return new WP_REST_Response( [ 'message' => 'You cannot modify this Fast Key' ], 403 );
		}

		// // Capability check (optional but recommended)
		// if ( ! current_user_can( 'edit_post', $fastkey_id ) ) {
		// 	return new WP_REST_Response( [ 'message' => 'Insufficient permissions' ], 403 );
		// }

		if ( ! empty( $params['fastkey_title'] ) ) {
			$new_title = sanitize_text_field( $params['fastkey_title'] );

			// Fetch all Fast Keys for this user
			$existing_posts = get_posts( [
				'post_type'      => 'fast_keys',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => [
					[
						'key'   => '_fast_keys_user_id',
						'value' => $user_id,
					],
				],
			] );

			foreach ( $existing_posts as $existing_id ) {
				if ( (int) $existing_id !== $fastkey_id ) { // Skip current FastKey
					// Decode and normalize both titles
					$existing_title_raw = get_the_title( $existing_id );
					$existing_title = html_entity_decode( wp_strip_all_tags( $existing_title_raw ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

					$new_title_normalized = html_entity_decode( wp_strip_all_tags( $new_title ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

					if ( strtolower( $existing_title ) === strtolower( $new_title_normalized ) ) {
						return new WP_REST_Response( [
							'status'  => 'error',
							'message' => 'You already have a Fast Key with this title.',
						], 409 );
					}
				}
			}


			// Update title (safe now)
			wp_update_post( [
				'ID'         => $fastkey_id,
				'post_title' => $new_title,
			] );
		}


		// === Update index / reorder within THIS USER only ===
		if ( isset( $params['fastkey_index'] ) ) {
			$new_index = (int) $params['fastkey_index'];

			// 1) Fetch all FastKeys for this user that HAVE an index, sorted by that index
			$with_index_ids = get_posts( [
				'post_type'      => 'fast_keys',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => [
					'relation'     => 'AND',
					'user_clause'  => [
						'key'     => '_fast_keys_user_id',
						'value'   => $user_id,
						'compare' => '=',
					],
					'index_clause' => [
						'key'     => '_fast_key_index',
						'compare' => 'EXISTS',
						'type'    => 'NUMERIC',
					],
				],
				// Order by the named meta_query clause to guarantee correct ORDER BY
				'orderby'        => [ 'index_clause' => 'ASC', 'ID' => 'ASC' ],
			] );

			// 2) Fetch all FastKeys for this user that DO NOT have an index (will be appended)
			$without_index_ids = get_posts( [
				'post_type'      => 'fast_keys',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => [
					'relation'    => 'AND',
					'user_clause' => [
						'key'     => '_fast_keys_user_id',
						'value'   => $user_id,
						'compare' => '=',
					],
					[
						'key'     => '_fast_key_index',
						'compare' => 'NOT EXISTS',
					],
				],
				'orderby'        => [ 'ID' => 'ASC' ],
			] );

			// Combine (indexed first, then non-indexed at the end)
			$ordered = array_values( array_unique( array_merge( $with_index_ids, $without_index_ids ) ) );

			// If for some reason the current ID isn't in the list yet, append it
			if ( ! in_array( $fastkey_id, $ordered, true ) ) {
				$ordered[] = $fastkey_id;
			}

			// Remove current from its old position
			$ordered = array_values( array_filter( $ordered, function( $id ) use ( $fastkey_id ) {
				return (int) $id !== (int) $fastkey_id;
			} ) );

			// Clamp new_index between 1 and count+1, then insert
			$new_index = max( 1, min( $new_index, count( $ordered ) + 1 ) );
			array_splice( $ordered, $new_index - 1, 0, [ $fastkey_id ] );

			// Renumber sequentially starting at 1
			foreach ( $ordered as $i => $id ) {
				update_post_meta( $id, '_fast_key_index', $i + 1 );
			}
		}

		// === Update image (optional) ===
		if ( ! empty( $params['fastkey_image'] ) ) {
			$fastkey_image_url = esc_url_raw( $params['fastkey_image'] );
			update_post_meta( $fastkey_id, '_fast_key_image', $fastkey_image_url );

			$attachment_id = attachment_url_to_postid( $fastkey_image_url );
			if ( $attachment_id ) {
				set_post_thumbnail( $fastkey_id, $attachment_id );
			}
		}
		$fastkey_title_raw = get_the_title( $fastkey_id );	
		$fastkey_title = html_entity_decode( wp_strip_all_tags( $fastkey_title_raw ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		return new WP_REST_Response( [
			'status'        => 'success',
			'message'       => 'Fast Key updated',
			'fastkey_id'    => $fastkey_id,
			'fastkey_title' => $fastkey_title,
			'fastkey_index' => (int) get_post_meta( $fastkey_id, '_fast_key_index', true ),
			'fastkey_image' => get_the_post_thumbnail_url( $fastkey_id, 'full' )
				?: site_url() . '/wp-content/plugins/pinaka-pos-wp/assests/images/no-image.png',
		], 200 );
	}

	public function add_products_to_fast_key( WP_REST_Request $request ) {
		$data = $request->get_json_params();
		if (empty($data['fastkey_id']) || empty($data['products'])) {
			return new WP_REST_Response(array('message' => 'Missing required fields', 'status' => 'error'), 400);
		}
	
		$fastkey_id = intval($data['fastkey_id']);
		$existing_fastkeys_json = get_post_meta($fastkey_id, '_fast_keys_data', true);
		$fastkey_index = get_post_meta($fastkey_id, '_fast_key_index', true);
		$existing_fastkeys = json_decode($existing_fastkeys_json, true) ?: [];
	
		$new_products = $data['products'];
		$updated_products = $existing_fastkeys;
		$failed_products = [];
	
		foreach ($new_products as $new_product) {
			$product_id = intval($new_product['product_id']);
			$sl_number = intval($new_product['sl_number']);
			$product = wc_get_product($product_id);

			if (!$product) {
				$failed_products[] = array(
					'product_id' => $product_id,
					'message' => 'Product does not exist',
					'status' => 'failed',
				);
				continue;
			}

			// Check if product already exists
			$exists = false;
			foreach ($updated_products as $existing_product) {
				if ($existing_product['product_id'] === $product_id) {
					$exists = true;
					break;
				}
			}

			if ($exists) {
				$failed_products[] = array(
					'product_id' => $product_id,
					'message'    => 'Product already added to this Fast Key',
					'status'     => 'duplicate',
				);
			} else {
				$updated_products[] = array(
					'product_id' => $product_id,
					'sl_number'  => $sl_number
				);
			}
		}

	
		// Update post meta with modified list
		update_post_meta($fastkey_id, '_fast_keys_data', wp_json_encode($updated_products));
	
		$enhanced_fastkeys = [];
		
		// Loop through assigned products
		foreach ($updated_products as $fastkey) {
			$product_id = intval($fastkey['product_id']);
			$product = wc_get_product($product_id);

			if ($product) {
				$is_variant = $product->is_type('variation');
				$has_variants = $product->is_type('variable') && !empty($product->get_children());

				$enhanced_fastkeys[] = array(
					'product_id'    => $product_id,
					'name'          => $product->get_name(),
					'price'         => $product->get_price(),
					'image'         => wp_get_attachment_url($product->get_image_id()) ?: '',
					'category'      => wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names')),
					'sl_number'     => $fastkey['sl_number'],
					'sku'           => $product->get_sku(),
					'is_variant'    => $is_variant,
					'has_variants'  => $has_variants,
					'tags'          => array_map(function($term) {
						return array(
							'name' => $term->name,
							'slug' => $term->slug,
							'id'   => $term->term_id,
						);
					}, wp_get_post_terms($product_id, 'product_tag')),
				);
			}
		}

	
		// Get product count
		$product_count = count($updated_products);
		$fastkey_title_raw = get_the_title( $fastkey_id );
		$fastkey_title = html_entity_decode( wp_strip_all_tags( $fastkey_title_raw ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		return array(
			'fastkey_id'  => $fastkey_id,
			'fastkey_title' => $fastkey_title,
			'fastkey_image' => get_the_post_thumbnail_url($fastkey_id, 'full') ?: site_url().'/wp-content/plugins/pinaka-pos-wp/assests/images/no-image.png',
			'fastkey_index' => intval($fastkey_index),
			'itemCount' => $product_count, // Added product count
			'message'  => !empty($failed_products) ? 'Some products failed to add' : 'FastKeys updated successfully',
			'status' => !empty($failed_products) ? 'partial_success' : 'success',
			'products' => $enhanced_fastkeys,
			'failed_products' => $failed_products,
		);
	}
	
	
	
	

	/**
	 * Get FastKeys for a user by user ID with product details.
	 *
	 * @return WP_REST_Response The response.
	 */
	public function get_fastkeys_by_user() {

		$user_id = get_current_user_id();
		
		// Retrieve FastKeys for the user
		$posts = get_posts(array(
			'post_type'  => 'fast_keys',
			'numberposts' => -1, // Get all posts
			'no_found_rows' => true,
			'cache_results' => false,
			'meta_query' => array(
				array(
					'key'   => '_fast_keys_user_id',
					'value' => $user_id,
				),
			),
			'meta_key'       => '_fast_key_index',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
		));

		$all_fastkeys = [];

		if (empty($posts)) {
			return array(
				'user_id'   => $user_id,
				'message'   => 'FastKeys retrieved successfully',
				'status'    => 'success',
				'fastkeys'  => [],  // Return empty fastkeys array instead of an error
			);
		}

		foreach ($posts as $post) {
			// Get FastKey meta data
			$fastkeys_json = get_post_meta($post->ID, '_fast_keys_data', true);
			$fastkeys_data = json_decode($fastkeys_json, true) ?: [];

			// Get Post Title & Featured Image
			$fastkey_title_raw = get_the_title( $post->ID );
			$fastkey_title = html_entity_decode( wp_strip_all_tags( $fastkey_title_raw ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$fastkey_image = get_the_post_thumbnail_url($post->ID, 'full') ?: site_url().'/wp-content/plugins/pinaka-pos-wp/assests/images/no-image.png'; // Ensure it's not null
			$fastkey_index = get_post_meta($post->ID, '_fast_key_index', true) ?: 1; // Ensure index has a value

			$enhanced_fastkeys = [];

			// Loop through assigned products
			foreach ($fastkeys_data as $fastkey) {
				$product_id = intval($fastkey['product_id']);
				$product = wc_get_product($product_id);

				if ($product) {
					$is_variant = $product->is_type('variation');
					$has_variants = $product->is_type('variable') && !empty($product->get_children());

					$enhanced_fastkeys[] = array(
						'product_id'    => $product_id,
						'name'          => $product->get_name(),
						'price'         => $product->get_price(),
						'image'         => wp_get_attachment_url($product->get_image_id()) ?: '',
						'category'      => wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names')),
						'sl_number'     => $fastkey['sl_number'],
						'sku'           => $product->get_sku(),
						'is_variant'    => $is_variant,
						'has_variants'  => $has_variants,
						'tags'          => array_map(function($term) {
							return array(
								'name' => $term->name,
								'slug' => $term->slug,
								'id'   => $term->term_id, // Include term ID for better identification
							);
						}, wp_get_post_terms($product_id, 'product_tag')),
					);
				}
			}


			$all_fastkeys[] = array(
				'fastkey_id'    => $post->ID,
				'fastkey_title' => $fastkey_title,
				'fastkey_image' => $fastkey_image ?: site_url().'/wp-content/plugins/pinaka-pos-wp/assests/images/no-image.png',
				'itemCount'     => count($fastkeys_data), // Product count
				'user_id'       => $user_id,
				'fastkey_index' => intval($fastkey_index),
				'products'      => $enhanced_fastkeys
			);
		}

		return array(
			'user_id'   => $user_id,
			'message'   => 'FastKeys retrieved successfully',
			'status'    => 'success',
			'fastkeys'  => $all_fastkeys,
		);
	}

	
	/**
	 * Get products by FastKey ID with product details.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response The response.
	 */

	public function get_products_by_fastkey_id( WP_REST_Request $request ) {

		$post_id = $request['fastkey_id'];
		if ( empty( $post_id ) ) {
			return new WP_Error( 'invalid_data', 'Fast Key ID is required', array( 'status' => 400 ) );
		}

		$fastkeys_json = get_post_meta($post_id, '_fast_keys_data', true);
	
		$fastkeys_data = json_decode($fastkeys_json, true) ?: [];

		// Get Post Title & Featured Image
		$fastkey_title_raw = get_the_title( $post->ID );
		$fastkey_title = html_entity_decode( wp_strip_all_tags( $fastkey_title_raw ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$fastkey_image = get_the_post_thumbnail_url($post_id, 'full') ?: site_url().'/wp-content/plugins/pinaka-pos-wp/assests/images/no-image.png';
		$fastkey_index = get_post_meta($post_id, '_fast_key_index', true);

		$enhanced_fastkeys = [];

		// Loop through assigned products
		foreach ($fastkeys_data as $fastkey) {
			$product_id = intval($fastkey['product_id']);
			$product = wc_get_product($product_id);

			if ($product) {
				$is_variant = $product->is_type('variation');
				$has_variants = $product->is_type('variable') && !empty($product->get_children());

				$enhanced_fastkeys[] = array(
					'product_id'    => $product_id,
					'name'          => $product->get_name(),
					'price'         => $product->get_price(),
					'image'         => wp_get_attachment_url($product->get_image_id()) ?: '',
					'category'      => wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names')),
					'sl_number'     => $fastkey['sl_number'],
					'sku'           => $product->get_sku(),
					'is_variant'    => $is_variant,
					'has_variants'  => $has_variants,
					'tags'          => array_map(function($term) {
						return array(
							'name' => $term->name,
							'slug' => $term->slug,
							'id'   => $term->term_id, // Include term ID for better identification
						);
					}, wp_get_post_terms($product_id, 'product_tag')),
				);
			}
		}

	
		return array(
			'fastkey_id'  => $post_id,
			'fastkey_title' => $fastkey_title,
			'fastkey_image' => $fastkey_image ?: site_url().'/wp-content/plugins/pinaka-pos-wp/assests/images/no-image.png',
			'fastkey_index' => intval($fastkey_index),
			'products' => $enhanced_fastkeys, // Now includes product details
			'message' => 'FastKey products retrieved successfully',
			'status' => 'success',
		);
	}

	/**
	 * Delete a FastKey by ID.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	function delete_fast_key_api(WP_REST_Request $request) {
		$parameters = $request->get_params();

		$fastkey_id = isset($parameters['fastkey_id']) ? intval($parameters['fastkey_id']) : 0;
		$user_id = get_current_user_id();
	
		if (!$fastkey_id) {
			return new WP_REST_Response(array('message' => 'FastKey ID is required', 'status' => 'error'), 400);
		}
	
		$fastkey_post = get_post($fastkey_id);
		
		if (!$fastkey_post || $fastkey_post->post_type !== 'fast_keys') {
			return new WP_REST_Response(array('message' => 'FastKey not found', 'status' => 'error'), 404);
		}
	
		// Validate ownership
		$owner_id = get_post_meta($fastkey_id, '_fast_keys_user_id', true);
		if ($owner_id != $user_id) {
			return new WP_REST_Response(array('message' => 'Unauthorized access', 'status' => 'error'), 403);
		}
	
		// Delete the FastKey
		$deleted = wp_delete_post($fastkey_id, true);
		if ($deleted) {
			return new WP_REST_Response(array('message' => 'FastKey deleted successfully', 'status' => 'success', 'fastkey_id' => $fastkey_id), 200);
		}
	
		return new WP_REST_Response(array('message' => 'Error deleting FastKey', 'status' => 'error'), 500);
	}

	public function update_fastkey_products(WP_REST_Request $request) {

		$data = $request->get_json_params();
		if (empty($data['fastkey_id']) || empty($data['products'])) {
			return new WP_REST_Response(['message' => 'Missing required fields', 'status' => 'error'], 400);
		}

		$fastkey_id = intval($data['fastkey_id']);
		$fastkey_index = get_post_meta($fastkey_id, '_fast_key_index', true);

		$new_products = $data['products'];
		$updated_products = [];
		$failed_products = [];

		foreach ($new_products as $new_product) {
			$product_id = intval($new_product['product_id']);
			$sl_number = intval($new_product['sl_number']);
			$product = wc_get_product($product_id);

			if (!$product) {
				$failed_products[] = [
					'product_id' => $product_id,
					'message' => 'Product does not exist',
					'status' => 'failed',
				];
				continue;
			}

			$updated_products[] = [
				'product_id' => $product_id,
				'sl_number' => $sl_number,
			];
		}

		// Replace old products with the new list
		update_post_meta($fastkey_id, '_fast_keys_data', wp_json_encode($updated_products));

		// Return enriched product info
		$enhanced_fastkeys = [];
		foreach ($updated_products as $fastkey) {
			$product_id = intval($fastkey['product_id']);
			$product = wc_get_product($product_id);

			if ($product) {
				$is_variant = $product->is_type('variation');
				$has_variants = $product->is_type('variable') && !empty($product->get_children());

				$enhanced_fastkeys[] = array(
					'product_id'    => $product_id,
					'name'          => $product->get_name(),
					'price'         => $product->get_price(),
					'image'         => wp_get_attachment_url($product->get_image_id()) ?: '',
					'category'      => wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names')),
					'sl_number'     => $fastkey['sl_number'],
					'sku'           => $product->get_sku(),
					'is_variant'    => $is_variant,
					'has_variants'  => $has_variants,
					'tags'          => array_map(function($term) {
						return array(
							'name' => $term->name,
							'slug' => $term->slug,
							'id'   => $term->term_id, // Include term ID for better identification
						);
					}, wp_get_post_terms($product_id, 'product_tag')),
				);
			}
		}

		$fastkey_title_raw = get_the_title( $fastkey_id );
		$fastkey_title = html_entity_decode( wp_strip_all_tags( $fastkey_title_raw ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		return [
			'fastkey_id'     => $fastkey_id,
			'fastkey_title'  => $fastkey_title,
			'fastkey_image'  => get_the_post_thumbnail_url($fastkey_id, 'full') ?: site_url().'/wp-content/plugins/pinaka-pos-wp/assests/images/no-image.png',
			'fastkey_index'  => intval($fastkey_index),
			'itemCount'      => count($updated_products),
			'message'        => !empty($failed_products) ? 'Some products failed to update' : 'FastKeys updated successfully',
			'status'         => !empty($failed_products) ? 'partial_success' : 'success',
			'products'       => $enhanced_fastkeys,
			'failed_products'=> $failed_products,
		];
	}

	function delete_product_from_fast_key(WP_REST_Request $request) {

		$params = $request->get_json_params();

		if (empty($params['fastkey_id']) || empty($params['product_id'])) {
			return new WP_REST_Response([
				'status' => 'error',
				'message' => 'Missing fastkey_id or product_id'
			], 400);
		}

		$fastkey_id = intval($params['fastkey_id']);
		$product_id = intval($params['product_id']);

		$existing_fastkeys_json = get_post_meta($fastkey_id, '_fast_keys_data', true);
		$existing_fastkeys = json_decode($existing_fastkeys_json, true) ?: [];

		$original_count = count($existing_fastkeys);

		// Filter out the product to delete
		$updated_fastkeys = array_filter($existing_fastkeys, function ($item) use ($product_id) {
			return intval($item['product_id']) !== $product_id;
		});

		$updated_count = count($updated_fastkeys);

		if ($original_count === $updated_count) {
			return new WP_REST_Response([
				'status' => 'error',
				'message' => 'Product not found in fastkey'
			], 404);
		}

		// Re-index array and update meta
		$updated_fastkeys = array_values($updated_fastkeys);
		update_post_meta($fastkey_id, '_fast_keys_data', wp_json_encode($updated_fastkeys));

		return new WP_REST_Response([
			'status' => 'success',
			'message' => 'Product removed from Fast Key',
			'fastkey_id' => $fastkey_id,
			'product_id' => $product_id,
			'itemCount' => count($updated_fastkeys)
		], 200);
	}
	// public function get_all_fastkeys_images() 
	// {
	// 	$default_types = ['2D','3D','PNG','JPG','JPEG'];
	// 	$custom_types = get_option('pinaka_fastkey_custom_types', []);

	// 	// Make sure custom types is array
	// 	if (!is_array($custom_types)) {
	// 		$custom_types = [];
	// 	}
	// 	// Merge default + custom
	// 	$valid_folders = array_unique(
	// 		array_map('strtoupper', array_merge($default_types, $custom_types))
	// 	);
	// 	$uploaded = get_option('pinaka_fastkey_images', []);

	// 	$response = [];

	// 	foreach ($valid_folders as $type) {

	// 		if (!isset($uploaded[$type]) || empty($uploaded[$type])) {
	// 			continue;
	// 		}

	// 		// Initialize arrays
	// 		$response[$type] = [
	// 			'active'  => [],
	// 			'deleted' => []
	// 		];

	// 		foreach ($uploaded[$type] as $item) {

	// 			$isDeleted = isset($item['isDeleted']) ? (bool) $item['isDeleted'] : false;

	// 			if ($isDeleted) {
	// 				$response[$type]['deleted'][] = $item;
	// 			} else {
	// 				$response[$type]['active'][] = $item;
	// 			}
	// 		}
	// 	}

	// 	return new WP_REST_Response([
	// 		'success' => true,
	// 		'message' => 'FastKey images fetched successfully',
	// 		'data'    => $response
	// 	], 200);
	// }
	public function get_all_fastkeys_images() 
	{
		$default_types = ['2D','3D','PNG','JPG','JPEG'];
		$custom_types  = get_option('pinaka_fastkey_custom_types', []);
		if (!is_array($custom_types)) {
			$custom_types = [];
		}

		$valid_folders = array_unique(
			array_map('strtoupper', array_merge($default_types, $custom_types))
		);

		$uploaded = get_option('pinaka_fastkey_images', []);
		$response = [];

		foreach ($valid_folders as $type) {
			if (!isset($uploaded[$type]) || empty($uploaded[$type])) {
				continue;
			}
			$response[$type] = [];
			foreach ($uploaded[$type] as $item) {
				$isDeleted = isset($item['isDeleted']) ? (bool) $item['isDeleted'] : false;
				if (!$isDeleted) {
					$response[$type][] = $item;
				}
			}
			if (empty($response[$type])) {
				unset($response[$type]);
			}
		}
		return new WP_REST_Response([
			'success' => true,
			'message' => 'FastKey images fetched successfully',
			'data'    => $response
		], 200);
	}
}

<?php

/**
 * REST API Categories controller
 *
 * Handles requests to the / endpoints.
 *
 * @package WooCommerce\RestApi
 * @since   1.0.0
 */

defined('ABSPATH') || exit;
/**
 * REST API Categories controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Product_Variations_V2_Controller
 */
class Pinaka_Get_Categories_Controller 
{
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
    protected $rest_base = 'categories';

    /**
	 * Register the routes for sales reports.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => 'GET',
                    'callback' => [$this, 'get_product_categories'],
					'permission_callback' => array( $this, 'check_user_role_permission' )
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

        register_rest_route(
			$this->namespace,
			'/' . $this->rest_base."/get-categories-products",
			array(
				array(
					'methods'             => 'GET',
                    'callback' => [$this, 'get_product_categories_with_products'],
					'permission_callback' => array( $this, 'check_user_role_permission' )
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

    /**
	 * Check whether a given request has permission to view system status.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
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
    
    public function get_product_categories($request) {
        $search = $request->get_param('search') ?? ''; // Get search parameter
        $parent = $request->get_param('parent') ?? ''; // Get parent category ID

        $args = [
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => $parent,
            'exclude'    => [1], // Exclude term ID 1 (usually Uncategorized)
        ];

        if (!empty($search)) {
            $args['name__like'] = $search;
        }

        $categories = get_terms($args);
        $data = [];

        if (!empty($categories) && !is_wp_error($categories)) {
            foreach ($categories as $category) {
                //if ($category->slug === 'uncategorized') {
                  // continue; // Just in case it's not ID 1
             //}
                $data[] = $this->prepare_category_for_response($category);
            }
        }

        return rest_ensure_response($data);
    }


    /**
     * Prepare a single category for API response
     */
    private function prepare_category_for_response($category) {
        return [
            'id'          => $category->term_id,
            'name'        =>  html_entity_decode($category->name, ENT_QUOTES | ENT_HTML5),
            'slug'        => $category->slug,
            'parent'      => $category->parent,
            'description' => $category->description,
            'count'       => $category->count,
            'image'       => $this->get_category_image($category->term_id),
        ];
    }

    /**
     * Get category image (if available)
     */
    private function get_category_image($category_id) {
        $thumbnail_id = get_term_meta($category_id, 'thumbnail_id', true);
        return $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : null;
    }


    public function get_product_categories_with_products($request) {
        $args = [
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => 0,
        ];

        $categories = get_terms($args);
        $data = [];

        if (!empty($categories) && !is_wp_error($categories)) {
            foreach ($categories as $category) {
                $data[] = $this->build_category_tree($category);
            }
        }

        return rest_ensure_response(['status' => 'success', 'category' => $data]);
    }


    private function build_category_tree($category) {
        $category_data = $this->prepare_category_for_response($category);

        // Get products for this category
        $category_data['products'] = $this->get_products_by_category($category->term_id);

        // Get child categories
        $children = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => $category->term_id,
        ]);

        $category_data['children'] = [];

        if (!empty($children) && !is_wp_error($children)) {
            foreach ($children as $child) {
                $category_data['children'][] = $this->build_category_tree($child); // 🔁 recursive call
            }
        }

        return $category_data;
    }

    private function get_products_by_category($cat_id) {
        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'tax_query'      => [
                [
                    'taxonomy'         => 'product_cat',
                    'field'            => 'term_id',
                    'terms'            => [$cat_id],
                    'include_children' => false, // Only for this specific category
                ],
            ],
        ];

        $query = new WP_Query($args);
        $products = [];

        foreach ($query->posts as $post) {
            $product = wc_get_product($post->ID);
            if (!$product) continue;

            // ✅ Get product tags
            $tags = wp_get_post_terms($post->ID, 'product_tag', ['fields' => 'all']);
            $tag_list = [];
            if (!empty($tags) && !is_wp_error($tags)) {
                foreach ($tags as $tag) {
                    $tag_list[] = [
                        'id'   => $tag->term_id,
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                    ];
                }
            }

            $products[] = [
                'id'    => $product->get_id(),
                'name'  => $product->get_name(),
                'price' => $product->get_price(),
                'image' => wp_get_attachment_url($product->get_image_id()),
                'tags'  => $tag_list, // ✅ Added tags
            ];
        }

        return $products;
    }

}

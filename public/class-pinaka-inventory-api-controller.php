<?php
/**
 * REST API Inventory API controller
 * Handles requests to the Inventory API endpoint.
 */

if(!defined('ABSPATH')){
    exit;
}

 /**
  * REST API Inventory Controller class.
  */
class Pinaka_Inventory_Api_Controller{

    /**
     * Endpoint namespace.
     * 
     * @var string
     */
    protected $namespace = 'pinaka-restaurant-pos/v1';

    /**
     * Route base.
     * 
     * @var string
     */
    protected $rest_base = 'inventory';

    /**
     * Register the routes for Inventory.
     */
    public function register_routes(){
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/update-inventory',
            array(
                'methods'              => 'POST',
                'callback'             => array($this, 'update_inventory_methods'),
                'permission_callback'  => array($this, 'check_user_role_permission'),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/delete-inventory',
            array(
                'methods'             =>'DELETE',
                'callback'            => array($this, 'delete_inventory_methods'),
                'permission_callback' => array($this, 'check_user_role_permission'),
            )
        );
    }

    /**
     * check whether a given request has permission to view system status.
     * 
     * @param WP_Rest_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function check_user_role_permission($request)
    {
        $user_id = get_current_user_id();
        $user    = get_userdata($user_id);
        if($user == null){
            return new WP_Error('pinakapos_rest_cannot_view', esc_html__('Sorry, you cannot give permission.', 'pinaka-pos'), array('status' => rest_authorization_require_code()));
        }
        if(in_array('administrator', (array) $user->roles)){
            return true;

        }elseif(in_array('shop_manager', (array) $user->roles)){
            return true;

        }elseif(in_array('employee', (array) $user->roles)){
            return true;

        }else{
            return new WP_Error('pinakapos_rest_cannot_view', esc_html__('Sorry, you cannot give permission.', 'pinaka-pos'), array('status' => rest_authorization_require_code()));
        }
    }

   public function update_inventory_methods($request)
{
    $params = $request->get_json_params();
	$user_id = get_current_user_id();

	// ✅ Required fields check
	$missing_fields = [];
	foreach (['inventory_id', 'inventory_restaurant_id', 'inventory_restaurant_name'] as $field) {
		if (!isset($params[$field]) || empty($params[$field])) {
			$missing_fields[] = $field;
		}
	}
	if (!empty($missing_fields)) {
		return new WP_REST_Response([
			'success' => false,
			'message' => implode(', ', $missing_fields) . ' is required.'
		], 400);
	}

	$inventory_id = intval($params['inventory_id']);
	$restaurant_id = intval($params['inventory_restaurant_id']);
	$restaurant_name = sanitize_text_field($params['inventory_restaurant_name']);

	// ✅ Post existence check
	$post = get_post($inventory_id);
	if (!$post || $post->post_type !== 'inventory' || $post->post_status !== 'publish') {
		return new WP_REST_Response([
			'success' => false,
			'message' => "Inventory item not found or unpublished."
		], 404);
	}

	// ✅ User's restaurant permission check
	$user_restaurant_id = intval(get_user_meta($user_id, 'user_restaurant_id', true));
	if ($user_restaurant_id !== $restaurant_id) {
		return new WP_REST_Response([
			'success' => false,
			'message' => "User not assigned to this restaurant."
		], 403);
	}

	// ✅ Allowed fields to update
	$fields = [
		'inventory_name'           => '_inventory_name',
		'inventory_in_name'        => '_inventory_in_name',
		'inventory_quantity'       => '_inventory_quantity',
		'inventory_quantity_unit'  => '_inventory_quantity_unit',
		'inventory_price'          => '_inventory_price',
		'inventory_restaurant_name'=> '_inventory_restaurant_name',
	];

	foreach ($fields as $param_key => $meta_key) {
		if (isset($params[$param_key])) {
			$value = $params[$param_key];
			if (in_array($param_key, ['inventory_quantity', 'inventory_price']) && (!is_numeric($value) || $value < 0)) {
				return new WP_REST_Response([
					'success' => false,
					'message' => ucfirst(str_replace('_', ' ', $param_key)) . " must be a valid non-negative number."
				], 400);
			}
			update_post_meta($inventory_id, $meta_key, is_numeric($value) ? floatval($value) : sanitize_text_field($value));
		}
	}

	// ✅ Threshold logic
	$min_qty = 5.0;
	$max_qty = 45.0;

	$current_qty = floatval(get_post_meta($inventory_id, '_inventory_quantity', true));
	$new_qty     = isset($params['inventory_quantity']) ? floatval($params['inventory_quantity']) : 0;
	$unit        = sanitize_text_field(get_post_meta($inventory_id, '_inventory_quantity_unit', true));
	$post_title  = get_the_title($inventory_id);
	$to          = get_option('admin_email');
	$headers     = ['Content-Type: text/plain; charset=UTF-8'];

	$remaining_qty = $current_qty - $new_qty;
	$final_qty = $current_qty + $new_qty;

	// ✅ Low stock alert
	if ($remaining_qty <= $min_qty) {
		$subject = "Inventory Alert: {$post_title}";
		$message = "Inventory '{$post_title}' is running low.\n\n"
		         . "Remaining Quantity: {$remaining_qty} {$unit}\n"
		         . "Threshold Limit: {$min_qty} {$unit}\n\n"
		         . "Please restock soon.";
		wp_mail($to, $subject, $message, $headers);
	}

	// ✅ Max stock alert
	if ($final_qty > $max_qty) {
		$subject = "Inventory Exceeded: {$post_title}";
		$message = "Inventory '{$post_title}' has exceeded the maximum limit.\n\n"
		         . "Final Quantity: {$final_qty} {$unit}\n"
		         . "Maximum Allowed: {$max_qty} {$unit}\n\n"
		         . "Please review the stock levels.";
		wp_mail($to, $subject, $message, $headers);
	}

	return new WP_REST_Response([
		'success'      => true,
		'message'      => 'Inventory updated successfully.',
		'inventory_id' => $inventory_id
	], 200);
}


    public function delete_inventory_methods($request) {
        $params = $request->get_json_params();

        // Check for required ID
        if (empty($params['inventory_id'])) {
            return new WP_REST_Response(['message' => 'inventory_id is required.'], 400);
        }

        $post_id = intval($params['inventory_id']);

        // Check post type and existence
        if (!$post_id || get_post_type($post_id) !== 'inventory') {
            return new WP_REST_Response(['message' => 'Invalid inventory ID.'], 400);
        }

        // Attempt to delete
       $result = wp_trash_post($post_id);
        if ($result) {
            return new WP_REST_Response(['message' => 'Inventory deleted successfully.'], 200);
        } else {
            return new WP_REST_Response(['message' => 'Failed to delete inventory.'], 500);
        }
    }
}
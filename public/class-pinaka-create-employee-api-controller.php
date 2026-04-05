<?php
if (!defined('ABSPATH')) {
    exit;
}

class Pinaka_Create_Employee_Api_Controller {

    protected $namespace = 'pinaka-pos/v1';
    protected $rest_base = 'employee';

    public function register_routes() {

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/create-employee',
                array(
                    'methods'  => 'POST',
                    'callback' => array($this, 'create_employee'),
                    'permission_callback' => array($this, 'permissions_check'),
                ),
                
        );
    register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/delete-employee/(?P<id>\d+)',
        array(
                    'methods'  => 'DELETE',
                    'callback' => array($this, 'delete_employee'),
                    'permission_callback' => array($this, 'permissions_check'),
                ),
    );

    register_rest_route(
    $this->namespace,
    '/' . $this->rest_base . '/update-employee/(?P<id>\d+)',
    array(
        'methods'  => 'POST',
        'callback' => array($this, 'update_employee'),
        'permission_callback' => array($this, 'permissions_check'),
    ),
);
    }


    public function permissions_check() {
        return current_user_can('manage_options'); 
        // Only admin can create employee
    }

    public function create_employee($request) {

        $params = $request->get_json_params();

        $username  = sanitize_user($params['username']);
        $email     = sanitize_email($params['email']);
        $first_name = sanitize_text_field($params['first_name']);
        $last_name  = sanitize_text_field($params['last_name']);
        $role       = sanitize_text_field($params['role']);
        $phone      = sanitize_text_field($params['user_phone']);
        $pin        = sanitize_text_field($params['emp_login_pin']);

        // ✅ Validation
        if (empty($username) || empty($email)) {
            return new WP_Error(
                'validation_error',
                'Username and Email are required',
                array('status' => 400)
            );
        }

        if (username_exists($username)) {
            return new WP_Error(
                'username_exists',
                'Username already exists',
                array('status' => 400)
            );
        }

        if (email_exists($email)) {
            return new WP_Error(
                'email_exists',
                'Email already exists',
                array('status' => 400)
            );
        }

        // 🔥 Auto generate password (not used in POS login)
        $random_password = wp_generate_password(12, false);

        $user_id = wp_insert_user(array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass'  => $random_password,
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'role'       => $role ?: 'employee',
        ));

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        // ✅ Save Custom Meta
        update_user_meta($user_id, 'user_phone', $phone);
        update_user_meta($user_id, 'emp_login_pin', $pin);

        return array(
            'success' => true,
            'message' => 'Employee created successfully',
            'user_id' => $user_id
        );
    }
    public function delete_employee($request) {

    $user_id = (int) $request['id'];

    if (!$user_id) {
        return new WP_Error(
            'invalid_id',
            'Invalid user ID',
            array('status' => 400)
        );
    }

    // Check if user exists
    $user = get_user_by('id', $user_id);

    if (!$user) {
        return new WP_Error(
            'user_not_found',
            'User not found',
            array('status' => 404)
        );
    }

    require_once(ABSPATH . 'wp-admin/includes/user.php');

    wp_delete_user($user_id);

    return array(
        'success' => true,
        'message' => 'Employee deleted successfully'
    );
}

public function update_employee($request) {

    $user_id = (int) $request['id'];
    $params  = $request->get_json_params();

    if (!$user_id) {
        return new WP_Error(
            'invalid_id',
            'Invalid user ID',
            array('status' => 400)
        );
    }

    $user = get_user_by('id', $user_id);

    if (!$user) {
        return new WP_Error(
            'user_not_found',
            'User not found',
            array('status' => 404)
        );
    }

    $username   = sanitize_user($params['username']);
    $email      = sanitize_email($params['email']);
    $first_name = sanitize_text_field($params['first_name']);
    $last_name  = sanitize_text_field($params['last_name']);
    $role       = sanitize_text_field($params['role']);
    $phone      = sanitize_text_field($params['phone']);
    $pin        = sanitize_text_field($params['emp_login_pin']);

    $userdata = array(
        'ID'         => $user_id,
        'user_login' => $username,
        'user_email' => $email,
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'role'       => $role ?: 'employee',
    );

    $updated = wp_update_user($userdata);

    if (is_wp_error($updated)) {
        return $updated;
    }

    // Update custom meta
    update_user_meta($user_id, 'phone', $phone);
    update_user_meta($user_id, 'emp_login_pin', $pin);

    return array(
        'success' => true,
        'message' => 'Employee updated successfully',
        'user_id' => $user_id
    );
}
}
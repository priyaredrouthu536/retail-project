<?php
if (!defined('ABSPATH')) {
    exit;
}

class Pinaka_User_Roles_Api {

    protected $namespace = 'pinaka-pos/v1';
    protected $rest_base = 'roles';

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    // ✅ YOU MUST ADD THIS METHOD
    public function register_routes() {

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/custom-user-roles',
            array(
                'methods'  => 'GET',
                'callback' => array($this, 'get_custom_user_roles'),
                'permission_callback' => '__return_true',
            )
        );
    }

    public function get_custom_user_roles($request) {

        $wp_roles = wp_roles();

        $roles = array();

        foreach ($wp_roles->roles as $key => $role) {
            $roles[] = array(
                'key'  => $key,
                'name' => $role['name']
            );
        }

        return rest_ensure_response($roles);
    }
}
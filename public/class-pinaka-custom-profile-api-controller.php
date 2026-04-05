<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Pinaka_Profile_Api_Controller {

    protected $namespace = 'pinaka-pos/v1';
    protected $rest_base = 'profile';

    public function register_routes() {

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'  => 'GET',
                    'callback' => array($this, 'get_user_profile'),
                    'permission_callback' => array($this, 'check_user_permission'),
                ),
                array(
                    'methods'  => 'POST',
                    'callback' => array($this, 'update_user_profile'),
                    'permission_callback' => array($this, 'check_user_permission'),
                ),
            )
        );
    }

    // ================= PERMISSION =================
    public function check_user_permission() {

        if ( ! is_user_logged_in() ) {
            return new WP_Error(
                'unauthorized',
                'Unauthorized',
                array( 'status' => 401 )
            );
        }

        return true;
    }

    // ================= GET PROFILE =================
    public function get_user_profile() {

        $user_id = get_current_user_id();
        $user    = get_userdata($user_id);

        return [
            'first_name' => $user->first_name,
            'username' => $user->user_login,
            'email'    => $user->user_email,
            'gender'   => get_user_meta($user_id, 'gender', true),
            'phone'    => get_user_meta($user_id, 'phone', true),
        ];
    }

    // ================= UPDATE PROFILE =================
    public function update_user_profile( WP_REST_Request $request ) {

    $user_id = get_current_user_id();
    $params  = $request->get_json_params();

    if ( empty($params) ) {
        return new WP_Error(
            'no_data',
            'No data provided.',
            array( 'status' => 400 )
        );
    }

    $updated = false;

     // Update First & Last Name
    if ( isset($params['first_name']) ) {

        wp_update_user([
            'ID'         => $user_id,
            'first_name' => sanitize_text_field($params['first_name'] ?? ''),
         
        ]);

        $updated = true;
    }

    // Update email
    if ( isset($params['email']) && ! empty($params['email']) ) {
        wp_update_user([
            'ID'         => $user_id,
            'user_email' => sanitize_email($params['email'])
        ]);
        $updated = true;
    }

    // Update gender ONLY if not empty
    if ( isset($params['gender']) && $params['gender'] !== '' ) {
        update_user_meta(
            $user_id,
            'gender',
            sanitize_text_field($params['gender'])
        );
        $updated = true;
    }

    // Update phone ONLY if not empty
    if ( isset($params['phone']) && $params['phone'] !== '' ) {
        update_user_meta(
            $user_id,
            'phone',
            sanitize_text_field($params['phone'])
        );
        $updated = true;
    }

    if ( ! $updated ) {
        return new WP_Error(
            'nothing_updated',
            'No valid fields provided.',
            array( 'status' => 400 )
        );
    }

    return [
        'status'  => true,
        'message' => 'Profile updated successfully'
    ];
}
}
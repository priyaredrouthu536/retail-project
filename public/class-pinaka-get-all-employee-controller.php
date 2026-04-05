<?php
if (!defined('ABSPATH')) {
    exit;
}

class Pinaka_Get_All_Employee_Api_controller {
    protected $namespace = 'pinaka-pos/v1';
    protected $rest_base = 'users';

    public function register_routes() {

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-all-employees',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => [$this, 'get_users_by_specific_roles'],
                    'permission_callback' => [$this, 'check_user_role_permission'],
                ),
                'schema' => [$this, 'get_public_item_schema'],
            )
        );


        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-all-shifts',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => [$this, 'get_all_shifts'],
                    'permission_callback' => [$this, 'check_user_role_permission'],
                ),
                'schema' => [$this, 'get_public_item_schema'],
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/create-user-with-meta',
            array(
                array(
                    'methods'             => 'POST',
                    'callback'            => [$this, 'create_user_with_meta'],
                    'permission_callback' => [$this, 'check_user_role_permission'],
                ),
                'schema' => [$this, 'get_public_item_schema'],
            )
        );

       register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/update-user-with-meta/(?P<id>\d+)',
            [
                [
                    'methods'             => WP_REST_Server::EDITABLE, // PUT/PATCH
                    'callback'            => [$this, 'update_user_with_meta'],
                    'permission_callback' => [$this, 'check_user_role_permission'],
                    'args' => [
                        'id' => [
                            'required' => true,
                            'validate_callback' => function ($param) {
                                return is_numeric($param);
                            }
                        ]
                    ]
                ],
                'schema' => [$this, 'get_public_item_schema'],
            ]
        );

    }

    public function check_user_role_permission($request) {
        $user_id = get_current_user_id();
        $user    = get_userdata($user_id);

        if (!$user) {
            return new WP_Error(
                'pinakapos_rest_cannot_view',
                __('Sorry, you cannot give permission.', 'pinaka-pos'),
                ['status' => rest_authorization_required_code()]
            );
        }

        $allowed_roles = ['administrator', 'shop_manager', 'employee'];
        foreach ($allowed_roles as $role) {
            if (in_array($role, (array) $user->roles)) {
                return true;
            }
        }

        return new WP_Error(
            'pinakapos_rest_cannot_view',
            __('Sorry, you cannot give permission.', 'pinaka-pos'),
            ['status' => rest_authorization_required_code()]
        );
    }

    public function get_users_by_specific_roles($request) {
        $target_roles = ['captain', 'manager', 'chef', 'employee'];

        $all_users = get_users(['number' => -1]);
        $filtered_users = [];

        foreach ($all_users as $user) {
            $user_roles = (array) $user->roles;
            $shift_id = get_user_meta( $user->ID, 'user_shift_number', true );
            $shifts_options = get_option('pinaka_pos_shift_settings');
            $shifts = $shifts_options['shifts'];

            $shift_name = $shifts[$shift_id]['name'];
            if (array_intersect($target_roles, $user_roles)) {
                $filtered_users[] = [
                    'ID'       => $user->ID,
                    'username' => $user->user_login,
                    'name'     => $user->display_name . ' (' . $shift_name . ')',
                    'email'    => $user->user_email,
                    'role'     => implode(',', $user_roles)
                ];
            }
        }

        return rest_ensure_response($filtered_users);
    }

    public function get_public_item_schema() {
        return [];
    }

    // public function get_all_shifts() {
    //     $current_timestamp = current_time('timestamp'); // WordPress safe timestamp
    //     $current_time = date('H:i', $current_timestamp);

    //     $shifts_options = get_option('pinaka_pos_shift_settings');
    //     $shifts = $shifts_options['shifts'];
    //     $shifts_array = [];
    //     $i = 1;

    //     foreach ($shifts as $shift) {
    //         $shift_start_time = strtotime($shift['start']);
    //         $shift_start_timestamp = strtotime(date('Y-m-d') . ' ' . $shift['start'], $current_timestamp);

    //         // Calculate 10-minute window before and after shift start time
    //         $min_time = $shift_start_timestamp - (180 * 60); // 10 mins before
    //         $max_time = $shift_start_timestamp + (60 * 60); // 10 mins after

    //         // if ($current_timestamp >= $min_time && $current_timestamp <= $max_time) {
    //             $shifts_array[] = [
    //                 'id' => $i,
    //                 'name' => $shift['name'],
    //                 'start_time' => $shift['start'],
    //                 'end_time' => $shift['end'],
    //             ];
    //         // }
    //         $i++;
    //     }

    //     return rest_ensure_response($shifts_array);
    // }

    public function get_all_shifts() {

        $current_timestamp = current_time('timestamp'); // UNIX timestamp
        $current_date = date('Y-m-d', $current_timestamp);

        $shifts_options = get_option('pinaka_pos_shift_settings');

        $shifts = $shifts_options['shifts'] ?? [];
  
        $shifts_array = [];
        $i = 1;

        foreach ($shifts as $shift) {
            $start_time_str = $shift['start'] ?? '';
            $end_time_str   = $shift['end'] ?? '';

            if (!$start_time_str || !$end_time_str) {
                continue;
            }

            // Convert shift start and end times to timestamps for today
            // $start_timestamp = strtotime("$current_date $start_time_str");
            // $end_timestamp   = strtotime("$current_date $end_time_str");

            // // Handle overnight shift (e.g., 22:00 to 02:00)
            // if ($end_timestamp < $start_timestamp) {
            //     $end_timestamp += 86400; // Add 24 hours
            // }

            // if ($current_timestamp >= $start_timestamp && $current_timestamp <= $end_timestamp) {
            //     $shifts_array[] = [
            //         'id'         => $i,
            //         'name'       => $shift['name'],
            //         'start_time' => $shift['start'],
            //         'end_time'   => $shift['end'],
            //     ];
            // }
            // Create DateTime objects from shift times
			$start_datetime = new DateTime("$current_date $start_time_str");
			$end_datetime   = new DateTime("$current_date $end_time_str");

			// Handle overnight shift (e.g., 6:00 PM to 12:00 AM — technically next day)
			if ($end_datetime < $start_datetime) {
				$end_datetime->modify('+1 day');
			}

			$start_timestamp = $start_datetime->getTimestamp();
			$end_timestamp   = $end_datetime->getTimestamp();

			// Check if current time is within this shift
			if ($current_timestamp >= $start_timestamp && $current_timestamp <= $end_timestamp) {
				$shifts_array = [
					'id'         => $i,
					'name'       => $shift['name'],
					// Properly formatted 12-hour AM/PM time
					'start_time' => $start_datetime->format('g:iA'),
					'end_time'   => $end_datetime->format('g:iA'),
				];
			}

            $i++;
        }

        return rest_ensure_response($shifts_array);
    }

    public function create_user_with_meta($request) {
        $parameters = $request->get_json_params();

        // Required fields
        $username = sanitize_user($parameters['username'] ?? '');
        $email    = sanitize_email($parameters['email'] ?? '');
        $password = $parameters['password'] ?? '';

        // Optional fields
        $roles      = $parameters['roles'] ?? ['employee'];
        $first_name = sanitize_text_field($parameters['first_name'] ?? '');
        $last_name  = sanitize_text_field($parameters['last_name'] ?? '');
        $meta       = $parameters['meta'] ?? [];
        $emp_login_pin = sanitize_text_field($parameters['meta']['emp_login_pin'] ?? '');

        // Validation
        if (empty($username) || empty($email) || empty($password)) {
            return new WP_Error(
                'pinakapos_missing_fields',
                __('Username, email, and password are required.', 'pinaka-pos'),
                ['status' => 400]
            );
        }

        if (username_exists($username) || email_exists($email)) {
            return new WP_Error(
                'pinakapos_user_exists',
                __('Username or email already exists.', 'pinaka-pos'),
                ['status' => 400]
            );
        }

        $existing_users = get_users([
            'meta_key'   => 'emp_login_pin',
            'meta_value' => $emp_login_pin,
            'number'     => 1,
            'fields'     => 'ID'
        ]);

        if (!empty($existing_users)) {
            return new WP_Error(
                'pin_already_exists',
                __('This PIN is already assigned to another employee.', 'pinaka-pos'),
                ['status' => 400]
            );
        }


        // Create user
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            return new WP_Error(
                'pinakapos_user_creation_failed',
                $user_id->get_error_message(),
                ['status' => 500]
            );
        }

        // Set role (first role from array)
        if (!empty($roles) && is_array($roles)) {
            wp_update_user([
                'ID'   => $user_id,
                'role' => sanitize_text_field($roles[0]),
            ]);
        }

        // Update name fields
        wp_update_user([
            'ID'         => $user_id,
            'first_name' => $first_name,
            'last_name'  => $last_name,
        ]);

        // Save user meta
        if (!empty($meta) && is_array($meta)) {
            foreach ($meta as $meta_key => $meta_value) {
                update_user_meta(
                    $user_id,
                    sanitize_key($meta_key),
                    sanitize_text_field($meta_value)
                );
            }
        }

        return rest_ensure_response([
            'success' => true,
            'user_id' => $user_id
        ]);
    }

    public function update_user_with_meta(WP_REST_Request $request) {

        $user_id = (int) $request->get_param('id');
        $params  = $request->get_json_params();

        // 1️⃣ Validate user
        if (!$user_id || !get_user_by('id', $user_id)) {
            return new WP_Error(
                'invalid_user',
                __('Invalid user ID.', 'pinaka-pos'),
                ['status' => 404]
            );
        }

        // 2️⃣ Prepare update array
        $user_data = ['ID' => $user_id];

        if (!empty($params['email'])) {
            if (!is_email($params['email'])) {
                return new WP_Error(
                    'invalid_email',
                    __('Invalid email address.', 'pinaka-pos'),
                    ['status' => 400]
                );
            }
            $user_data['user_email'] = sanitize_email($params['email']);
        }

        if (isset($params['first_name'])) {
            $user_data['first_name'] = sanitize_text_field($params['first_name']);
        }

        if (isset($params['last_name'])) {
            $user_data['last_name'] = sanitize_text_field($params['last_name']);
        }

        // 3️⃣ Update user core fields
        if (count($user_data) > 1) {
            $updated = wp_update_user($user_data);
            if (is_wp_error($updated)) {
                return new WP_Error(
                    'user_update_failed',
                    $updated->get_error_message(),
                    ['status' => 500]
                );
            }
        }

        // 4️⃣ Update role (first role only)
        if (!empty($params['roles']) && is_array($params['roles'])) {
            $role = sanitize_text_field($params['roles'][0]);
            $wp_user = new WP_User($user_id);
            $wp_user->set_role($role);
        }

        // 5️⃣ Handle meta updates
        if (!empty($params['meta']) && is_array($params['meta'])) {

            /* ===== EMP LOGIN PIN ===== */
            if (isset($params['meta']['emp_login_pin'])) {

                $pin = trim($params['meta']['emp_login_pin']);

                // Check uniqueness (hashed-safe)
                $users_with_pin = get_users([
                    'meta_key' => 'emp_login_pin',
                    'fields'   => ['ID'],
                ]);

                foreach ($users_with_pin as $u) {
                    if ((int) $u->ID === $user_id) {
                        continue; // skip current user
                    }

                    $stored_pin = get_user_meta($u->ID, 'emp_login_pin', true);

                    if ($stored_pin && $pin === $stored_pin) {
                        return new WP_Error(
                            'pin_already_exists',
                            __('This PIN is already assigned to another employee.', 'pinaka-pos'),
                            ['status' => 400]
                        );
                    }
                }

                // 3️⃣ Save hashed PIN
                update_user_meta($user_id, 'emp_login_pin', $pin);
            }

            // Save other meta fields (future-proof)
            foreach ($params['meta'] as $meta_key => $meta_value) {
                if ($meta_key === 'emp_login_pin') {
                    continue;
                }

                update_user_meta(
                    $user_id,
                    sanitize_key($meta_key),
                    sanitize_text_field($meta_value)
                );
            }
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('User updated successfully.', 'pinaka-pos'),
            'user_id' => $user_id,
        ]);
    }


}

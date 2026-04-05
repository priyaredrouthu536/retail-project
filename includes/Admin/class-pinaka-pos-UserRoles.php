<?php
if (!defined('WPINC')) {
    die;
}

class Pinaka_POS_UserRoles {
    public function __construct() {
        add_action('show_user_profile', [$this, 'render_multi_role_field']);
        add_action('edit_user_profile', [$this, 'render_multi_role_field']);
        add_action('personal_options_update', [$this, 'save_multi_roles']);
        add_action('edit_user_profile_update', [$this, 'save_multi_roles']);
        // Add PIN column in Users Dashboard
        add_filter('manage_users_columns', [$this, 'add_pin_column']);
        add_filter('manage_users_custom_column', [$this, 'show_pin_column_content'], 10, 3);
        add_filter('manage_users_columns', [$this, 'add_user_pin_column']);
        add_filter('manage_users_custom_column', [$this, 'show_User_pin_column_content'], 10, 3);
    }

    // Render checkboxes for roles
    public function render_multi_role_field($user) {
        if (!current_user_can('edit_users')) return;

        $all_roles  = wp_roles()->roles;
        $user_roles = $user->roles;

        echo '<h3>PinakaPOS: Assign Multiple Roles</h3>';
        echo '<table class="form-table"><tr><th><label>User Roles</label></th><td>';
        echo '<div style="border: 1px solid #ccc; padding: 10px; max-width: 400px;">';

        foreach ($all_roles as $role_slug => $details) {
            $checked = in_array($role_slug, $user_roles) ? 'checked' : '';
            echo '<label style="display: block; margin-bottom: 5px;">';
            echo '<input type="checkbox" name="pinaka_user_roles[]" value="' . esc_attr($role_slug) . '" ' . $checked . '> ';
            echo esc_html($details['name']);
            echo '</label>';
        }

        echo '</div>';
        echo '<p class="description">Tick all roles this user should have.</p>';
        echo '</td></tr></table>';
    }
    

    // Save roles safely
    public function save_multi_roles($user_id) {
        if (!current_user_can('edit_users')) return;

        if (isset($_POST['pinaka_user_roles']) && is_array($_POST['pinaka_user_roles'])) {
            $user = new WP_User($user_id);

            // Prevent WordPress from overriding with single role
            unset($_POST['role']);

            // Remove all current roles
            foreach ($user->roles as $role) {
                $user->remove_role($role);
            }

            // Add selected roles
            foreach ($_POST['pinaka_user_roles'] as $role) {
                $user->add_role(sanitize_text_field($role));
            }
        }
    }
    // Add column in Users list
    public function add_pin_column($columns) {
        $columns['emp_login_pin'] = 'Login PIN';
        return $columns;
    }

    // Show content in new column
    public function show_pin_column_content($value, $column_name, $user_id) {
        if ('emp_login_pin' === $column_name) {
            $pin = get_user_meta($user_id, 'emp_login_pin', true);
            return $pin ? esc_html($pin) : '<em>Not set</em>';
        }
        return $value;
    }

    // Add column in Users list
    public function add_user_pin_column($columns) {
        $columns['user_login_pin'] = 'User Login PIN';
        return $columns;
    }

    // Show content in new column
    public function show_user_pin_column_content($value, $column_name, $user_id) {
        if ('user_login_pin' === $column_name) {
            $pin = get_user_meta($user_id, 'user_login_pin', true);
            return $pin ? esc_html($pin) : '<em>Not set</em>';
        }
        return $value;
    }

}
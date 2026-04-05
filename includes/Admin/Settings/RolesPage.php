<?php
namespace PinakaPos\Admin\Roles;

if (!defined('WPINC')) {
    die;
}

class RolesPage {

    private $option_name = 'pinaka_roles_permissions';

    public function register_submenu() {
        add_submenu_page(
            'pinaka-pos',
            'Roles & Permissions',
            'Roles & Permissions',
            '__return_true',
            'pinaka-roles-permissions',
            [$this, 'render_permissions_page']
        );
    }

    private function get_roles_tabs() {
        return [
            'admin'    => 'Admin',
            'merchant' => 'Merchant',
            'manager'  => 'Manager',
            'captain'  => 'Captain',
            'chef'     => 'Chef',
        ];
    }

    public function render_permissions_page() {
        $option_name = $this->option_name;
        $saved_permissions = get_option($option_name, []);
        $active_tab = isset($_POST['active_tab']) ? sanitize_text_field($_POST['active_tab']) : 'admin';

        $all_permissions = [
            'Dashboard' => [
                'canAccessDashboard' => 'Access Dashboard'
            ],
            'Menu Management' => [
                'canViewMenu' => 'View Menu',
                'canEditMenu' => 'Edit Menu'
            ],
            'Table Management' => [
                'canSetupTables' => 'Setup Tables',
                'canEditTables' => 'Edit Tables',
                'canDeleteTables' => 'Delete Tables',
                'canDoubleTap' => 'Double Tap',
                'canViewTables' => 'View Tables',
                'canDefaultLayout' => 'Default Layout'
            ],
            'Order Management' => [
                'canViewOrderPanel' => 'View Order Panel',
                'canEditOrder' => 'Edit Order',
                'canDeleteOrder' => 'Delete Order'
            ],
            'KOT Management' => [
                'canViewKOTStatus' => 'View KOT Status',
                'canEditKOTStatus' => 'Edit KOT Status',
                'canDeleteKOTStatus' => 'Delete KOT Status',
                'canUpdateKOTStatus' => 'Update KOT Status',
                'canViewOrderType'  => 'View Order Type'
            ],
            'Inventory' => [
                'canViewInventory' => 'View Inventory',
                'canUpdateInventory' => 'Update Inventory'
            ],
            'Settings' => [
                'canAccessSettings' => 'Access Settings'
            ],
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role_slug'])) {
            $role = sanitize_text_field($_POST['role_slug']);
            $permissions_raw = $_POST['permissions'] ?? [];
            $permissions_clean = [];

            foreach ($permissions_raw as $key => $val) {
                if ($key === 'canDefaultLayout') {
                    if ($val === 'gridCommonImage') {
                        $permissions_clean[] = $key;
                    }
                } elseif ($val === '1') {
                    $permissions_clean[] = sanitize_text_field($key);
                }
            }

            $saved_permissions[$role] = $permissions_clean;
            update_option($option_name, $saved_permissions);

            echo '<div class="updated"><p>Permissions updated for ' . ucfirst($role) . '.</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Roles and Permissions</h1>

            <style>
                .nav-tab-wrapper { margin-bottom: 1em; }
                .tab-content { display: none; padding: 10px; border: 1px solid #ccc; background: #fff; }
                .tab-content.active { display: block; }

                .permission-row {
                    display: grid;
                    grid-template-columns: 200px 1fr;
                    align-items: center;
                    margin-bottom: 10px;
                    gap: 20px;
                }

                .permission-label {
                    font-weight: 500;
                }

                .permission-select label {
                    display: inline-block;
                    padding: 4px 10px;
                    min-width: 70px;
                    cursor: pointer;
                    font-weight: normal;
                    user-select: none;
                }

                .table-management-permission-select label {
                    min-width: 110px;
                }

                fieldset {
                    margin-bottom: 20px;
                    border: 1px solid #ddd;
                    padding: 15px;
                    border-radius: 5px;
                    background-color: #f9f9f9;
                }

                legend {
                    font-weight: bold;
                    padding: 0 5px;
                }
            </style>

            <h2 class="nav-tab-wrapper">
                <?php
                foreach ($this->get_roles_tabs() as $slug => $label) {
                    echo "<a href='#{$slug}' class='nav-tab" . ($slug === $active_tab ? ' nav-tab-active' : '') . "' onclick=\"showTab(event, '{$slug}')\">{$label}</a>";
                }
                ?>
            </h2>

            <?php
            foreach ($this->get_roles_tabs() as $role => $label) {
                $current_perms = isset($saved_permissions[$role]) ? $saved_permissions[$role] : [];

                if ($role === 'captain') {
                    $users = get_users(['role__in' => ['captain', 'manager']]);
                    foreach ($users as $user) {
                        if (in_array('captain', $user->roles) && in_array('manager', $user->roles)) {
                            $manager_perms = $saved_permissions['manager'] ?? [];
                            $current_perms = array_unique(array_merge($current_perms, $manager_perms));
                            break;
                        }
                    }
                }

                echo "<div id='{$role}' class='tab-content " . ($role === $active_tab ? 'active' : '') . "'>";
                echo "<h3>" . ucfirst($label) . " Permissions</h3>";
                echo "<form method='post' onsubmit=\"this.querySelector('[name=active_tab]').value='{$role}'\">";
                echo "<input type='hidden' name='role_slug' value='{$role}'>";
                echo "<input type='hidden' name='active_tab' value='{$role}'>";

                foreach ($all_permissions as $module => $sub_permissions) {
                    echo "<fieldset>";
                    echo "<legend>{$module}</legend>";

                    foreach ($sub_permissions as $perm_key => $perm_label) {
                        $permission_select_class = ($module === 'Table Management') ? 'permission-select table-management-permission-select' : 'permission-select';

                        echo "<div class='permission-row'>";
                        echo "<div class='permission-label'>{$perm_label}</div>";
                        echo "<div class='{$permission_select_class}'>";

                        if ($perm_key === 'canDefaultLayout') {
                            $current_value = in_array($perm_key, $current_perms) ? 'gridCommonImage' : 'normal';

                            echo "<label><input type='radio' name='permissions[{$perm_key}]' value='normal' " . checked($current_value, 'normal', false) . "> Normal</label> ";
                            echo "<label><input type='radio' name='permissions[{$perm_key}]' value='gridCommonImage' " . checked($current_value, 'gridCommonImage', false) . "> gridCommonImage</label>";
                        } else {
                            $current_value = in_array($perm_key, $current_perms) ? '1' : '0';
                            echo "<label><input type='radio' name='permissions[{$perm_key}]' value='1'" . checked($current_value, '1', false) . "> Yes</label> ";
                            echo "<label><input type='radio' name='permissions[{$perm_key}]' value='0'" . checked($current_value, '0', false) . "> No</label>";
                        }

                        echo "</div></div>";
                    }

                    echo "</fieldset>";
                }

                echo "<p><input type='submit' class='button button-primary' value='Save Permissions'></p>";
                echo "</form></div>";
            }
            ?>

            <script>
                function showTab(evt, tabId) {
                    evt.preventDefault();
                    const tabs = document.querySelectorAll('.tab-content');
                    const links = document.querySelectorAll('.nav-tab');

                    tabs.forEach(tab => tab.classList.remove('active'));
                    links.forEach(link => link.classList.remove('nav-tab-active'));

                    document.getElementById(tabId).classList.add('active');
                    evt.currentTarget.classList.add('nav-tab-active');
                }

                // Toggle radio buttons manually
                document.querySelectorAll('input[type="radio"]').forEach(radio => {
                    radio.addEventListener('click', function(e) {
                        if (this.previousChecked) {
                            this.checked = false;
                            this.previousChecked = false;
                            const event = new Event('change', { bubbles: true });
                            this.dispatchEvent(event);
                            e.preventDefault();
                        } else {
                            document.querySelectorAll(`input[name="${this.name}"]`).forEach(r => {
                                r.previousChecked = false;
                            });
                            this.previousChecked = true;
                        }
                    });
                    radio.previousChecked = radio.checked;
                });
            </script>
        </div>
        <?php
    }
}

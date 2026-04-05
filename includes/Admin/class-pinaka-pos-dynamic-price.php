<?php
if (!defined('ABSPATH')) exit;

class WC_Dynamic_Time_Pricing {
    private $plugin_name;
    private $version;
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        /* -----------------------------
         * ADMIN UI
         * ----------------------------- */
        add_filter('woocommerce_product_data_tabs', [$this, 'add_product_data_tab']);
        add_action('woocommerce_product_data_panels', [$this, 'render_product_tab']);
        add_action('woocommerce_admin_process_product_object', [$this, 'save_product_meta']);

        // add_action('woocommerce_variation_options_pricing', [$this, 'variation_fields'], 10, 3);
        // add_action('woocommerce_save_product_variation', [$this, 'save_variation_meta'], 10, 2);

        // add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        /* -----------------------------
         * FRONTEND PRICE FILTER
         * ----------------------------- */
        add_filter('woocommerce_product_get_price', [$this, 'filter_price'], 20, 2);
        // add_filter('woocommerce_product_variation_get_price', [$this, 'filter_price'], 20, 2);

        /* -----------------------------
         * CART & ORDER META
         * ----------------------------- */
        // add_filter('woocommerce_add_cart_item_data', [$this, 'store_cart_rule'], 20, 3);
        // add_filter('woocommerce_get_cart_item_from_session', [$this, 'load_cart_rule'], 20, 2);
        // add_action('woocommerce_before_calculate_totals', [$this, 'apply_cart_price'], 25);
        // add_action('woocommerce_checkout_create_order_line_item', [$this, 'store_order_item_meta'], 10, 4);
    }

    /* ---------------------------------------------
     * ADMIN: ADD TAB
     * --------------------------------------------- */

    public function add_product_data_tab($tabs)
    {
        $tabs['pinaka_dynamic_price'] = [
            'label'    => __('Dynamic Pricing', 'pinaka-dynamic-price'),
            'target'   => 'pinaka_dynamic_price_panel',
            'priority' => 80,
            // 'class'    => ['show_if_simple', 'show_if_variable'],
            'class'    => ['show_if_simple']
        ];
        return $tabs;
    }

    /* ---------------------------------------------
     * ADMIN: RENDER SIMPLE PRODUCT PANEL
     * --------------------------------------------- */

    public function render_product_tab()
    {
        global $post;
        $product_id = $post->ID;
        // $enable     = get_post_meta($product_id, '_enable_dynamic_price', true);
        $rules      = get_post_meta($product_id, '_dynamic_price_rules', true);
        $date_rules = get_post_meta($product_id, '_dynamic_price_date_rules', true);
        $product = wc_get_product($product_id);
        if (!is_array($rules)) {
            $rules = [];
        }
        if (!is_array($date_rules)) {
            $date_rules = [];
        }
        ?>
        <div id="pinaka_dynamic_price_panel" class="panel woocommerce_options_panel">
            <div class="options_group">
                <?php
                // woocommerce_wp_checkbox([
                //     'id'    => '_enable_dynamic_price',
                //     'label' => __('Enable Dynamic Pricing', 'pinaka-dynamic-price'),
                //     'value' => $enable === 'yes' ? 'yes' : 'no',
                // ]);
                ?>
                <p><strong><?php _e('Dynamic Pricing Rules (Type based)', 'pinaka-dynamic-price'); ?></strong></p>
                <div class="wc-dtp-table-wrapper" style="overflow:auto">
                    <table class="widefat" id="wc-dtp-simple-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Type (day)', 'pinaka-dynamic-price'); ?></th>
                                <th><?php esc_html_e('Start Time', 'pinaka-dynamic-price'); ?></th>
                                <th><?php esc_html_e('End Time', 'pinaka-dynamic-price'); ?></th>
                                <th nowrap><?php esc_html_e('Price (Existing)', 'pinaka-dynamic-price'); ?></th>
                                <th nowrap><?php esc_html_e('Price (Dynamic)', 'pinaka-dynamic-price'); ?></th>
                                <th><?php esc_html_e('Status (UI)', 'pinaka-dynamic-price'); ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rules as $index => $rule) : ?>
                            <?php
                            $type   = isset($rule['type']) ? $rule['type'] : '';
                            $start  = isset($rule['start']) ? $rule['start'] : '';
                            $end    = isset($rule['end']) ? $rule['end'] : '';
                            $price  = isset($rule['price']) ? $rule['price'] : '';
                            $status = isset($rule['status']) ? $rule['status'] : 'active';

                            $row_id = isset($rule['row_id']) ? $rule['row_id'] : uniqid('dt_', true);
                            ?>
                            <tr class="wc-dtp-row wc-dtp-type-row">
                                <td>
                                    <input type="hidden" name="dyn_row_id[]" value="<?php echo esc_attr($row_id); ?>">
                                    <select name="dyn_type[]" class="wc-dtp-type-select">
                                        <option value=""><?php esc_html_e('Select', 'pinaka-dynamic-price'); ?></option>
                                        <option value="Everyday" <?php selected($type, 'Everyday'); ?>>Everyday</option>
                                        <option value="Monday" <?php selected($type, 'Monday'); ?>>Monday</option>
                                        <option value="Tuesday" <?php selected($type, 'Tuesday'); ?>>Tuesday</option>
                                        <option value="Wednesday" <?php selected($type, 'Wednesday'); ?>>Wednesday</option>
                                        <option value="Thursday" <?php selected($type, 'Thursday'); ?>>Thursday</option>
                                        <option value="Friday" <?php selected($type, 'Friday'); ?>>Friday</option>
                                        <option value="Saturday" <?php selected($type, 'Saturday'); ?>>Saturday</option>
                                        <option value="Sunday" <?php selected($type, 'Sunday'); ?>>Sunday</option>
                                    </select>
                                </td>
                                <td><input type="time" name="dyn_start[]" value="<?php echo esc_attr($start); ?>"></td>
                                <td><input type="time" name="dyn_end[]" value="<?php echo esc_attr($end); ?>"></td>
                                <td><?php echo $product->get_sale_price() ?: $product->get_regular_price(); ?></td>
                                <td nowrap><input type="text" name="dyn_price[]" class="wc-dtp-price-field" autocomplete="off" inputmode="decimal" pattern="^\d+(\.\d{1,2})?$" placeholder="0.00" value="<?php echo esc_attr($price); ?>"></td>
                                <td>
                                    <!-- Checkbox serves as both saving and UI-group toggle. -->
                                    <input type="checkbox"
                                           class="wc-dtp-active-checkbox"
                                           name="dyn_active_ids[]"
                                           value="<?php echo esc_attr($row_id); ?>"
                                           <?php checked($status, 'active'); ?> />
                                </td>
                                <td><a href="#" class="button wc-dtp-remove-row"><?php esc_html_e('Remove', 'pinaka-dynamic-price'); ?></a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p><a href="#" class="button" id="wc-dtp-add-simple-row"><?php esc_html_e('Add Type Rule +', 'pinaka-dynamic-price'); ?></a></p>
                <!-- TYPE ROW TEMPLATE -->
                <script type="text/html" id="wc-dtp-simple-row-template">
                    <tr class="wc-dtp-row wc-dtp-type-row">
                        <td>
                            <input type="hidden" name="dyn_row_id[]" value="__ROWID__">
                            <select name="dyn_type[]" class="wc-dtp-type-select">
                                <option value="">Select</option>
                                <option value="Everyday">Everyday</option>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
                            </select>
                        </td>
                        <td><input type="time" name="dyn_start[]" value=""></td>
                        <td><input type="time" name="dyn_end[]" value=""></td>
                        <td><?php echo $product->get_sale_price() ?: $product->get_regular_price(); ?></td>
                        <td nowrap><input type="text" name="dyn_price[]" class="wc-dtp-price-field" autocomplete="off" inputmode="decimal" pattern="^\d+(\.\d{1,2})?$" placeholder="0.00" value=""></td>
                        <td>
                            <input type="checkbox"
                                   class="wc-dtp-active-checkbox"
                                   name="dyn_active_ids[]"
                                   value="__ROWID__" />
                        </td>
                        <td><a href="#" class="button wc-dtp-remove-row">Remove</a></td>
                    </tr>
                </script>
                <hr />
                <p><strong><?php _e('Dynamic Pricing Rules (Date range based)', 'pinaka-dynamic-price'); ?></strong></p>
                <div class="wc-dtp-table-wrapper" style="overflow:auto">
                    <table class="widefat" id="wc-dtp-date-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Start Day', 'pinaka-dynamic-price'); ?></th>
                                <th><?php esc_html_e('End Day', 'pinaka-dynamic-price'); ?></th>
                                <th><?php esc_html_e('Start Time', 'pinaka-dynamic-price'); ?></th>
                                <th><?php esc_html_e('End Time', 'pinaka-dynamic-price'); ?></th>
                                <th nowrap><?php esc_html_e('Price (Existing)', 'pinaka-dynamic-price'); ?></th>
                                <th nowrap><?php esc_html_e('Price (Dynamic)', 'pinaka-dynamic-price'); ?></th>
                                <th><?php esc_html_e('Status (UI)', 'pinaka-dynamic-price'); ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($date_rules as $index => $rule) : ?>
                            <?php
                            $start_day = isset($rule['start_day']) ? $rule['start_day'] : '';
                            $end_day   = isset($rule['end_day']) ? $rule['end_day'] : '';
                            $start     = isset($rule['start']) ? $rule['start'] : '';
                            $end       = isset($rule['end']) ? $rule['end'] : '';
                            $price     = isset($rule['price']) ? $rule['price'] : '';
                            $status    = isset($rule['status']) ? $rule['status'] : 'active';

                            $row_id = isset($rule['row_id']) ? $rule['row_id'] : uniqid('dd_', true);
                            ?>
                            <tr class="wc-dtp-row wc-dtp-date-row">
                                <td><input type="date" name="dyn_date_start_day[]" value="<?php echo esc_attr($start_day); ?>" class="wc-dtp-startdate" /></td>
                                <td><input type="date" name="dyn_date_end_day[]" value="<?php echo esc_attr($end_day); ?>" class="wc-dtp-enddate" /></td>
                                <td><input type="time" name="dyn_date_start[]" value="<?php echo esc_attr($start); ?>"></td>
                                <td><input type="time" name="dyn_date_end[]" value="<?php echo esc_attr($end); ?>"></td>
                                <td><?php echo $product->get_sale_price() ?: $product->get_regular_price(); ?></td>
                                <td nowrap><input type="text" name="dyn_date_price[]" class="wc-dtp-price-field" autocomplete="off" inputmode="decimal" pattern="^\d+(\.\d{1,2})?$" placeholder="0.00" value="<?php echo esc_attr($price); ?>"></td>
                                <td>
                                    <input type="hidden" name="dyn_row_id[]" value="<?php echo esc_attr($row_id); ?>">
                                    <input type="checkbox"
                                           class="wc-dtp-active-checkbox"
                                           name="dyn_active_ids[]"
                                           value="<?php echo esc_attr($row_id); ?>"
                                           <?php checked($status, 'active'); ?> />
                                </td>
                                <td><a href="#" class="button wc-dtp-remove-row"><?php esc_html_e('Remove', 'pinaka-dynamic-price'); ?></a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p><a href="#" class="button" id="wc-dtp-add-date-row"><?php esc_html_e('Add Date Rule +', 'pinaka-dynamic-price'); ?></a></p>
                <!-- DATE ROW TEMPLATE -->
                <script type="text/html" id="wc-dtp-date-row-template">
                    <tr class="wc-dtp-row wc-dtp-date-row">
                        <td><input type="date" name="dyn_date_start_day[]" value="" class="wc-dtp-startdate" /></td>
                        <td><input type="date" name="dyn_date_end_day[]" value="" class="wc-dtp-enddate" /></td>
                        <td><input type="time" name="dyn_date_start[]" value=""></td>
                        <td><input type="time" name="dyn_date_end[]" value=""></td>
                        <td><?php echo $product->get_sale_price() ?: $product->get_regular_price(); ?></td>
                        <td nowrap><input type="text" name="dyn_date_price[]" class="wc-dtp-price-field" autocomplete="off" inputmode="decimal" pattern="^\d+(\.\d{1,2})?$" placeholder="0.00" value=""></td>
                        <td>
                            <input type="hidden" name="dyn_row_id[]" value="__ROWID__">
                            <input type="checkbox"
                                   class="wc-dtp-active-checkbox"
                                   name="dyn_active_ids[]"
                                   value="__ROWID__" />
                        </td>
                        <td><a href="#" class="button wc-dtp-remove-row">Remove</a></td>
                    </tr>
                </script>
            </div>
        </div>
        <?php
    }
    /* ---------------------------------------------
     * SAVE SIMPLE PRODUCT RULES
     * --------------------------------------------- */

    public function save_product_meta($product)
    {
        if (!current_user_can('edit_products')) {
            return;
        }
        // $enable = isset($_POST['_enable_dynamic_price']) ? 'yes' : 'no';
        // $product->update_meta_data('_enable_dynamic_price', $enable);

        // All row IDs in submission, in DOM order (type + date rows)
        $row_ids    = $_POST['dyn_row_id']      ?? [];
        // Active row ids (checkboxes) — only the checked ones will appear here
        $active_ids = $_POST['dyn_active_ids']  ?? [];

        // TYPE-based rules
        $rules      = [];
        $types      = $_POST['dyn_type']   ?? [];
        $starts     = $_POST['dyn_start']  ?? [];
        $ends       = $_POST['dyn_end']    ?? [];
        $prices     = $_POST['dyn_price']  ?? [];

        $type_count = count($types);

        $row_index_pointer = 0;
        for ($i = 0; $i < $type_count; $i++) {
            $price = $prices[$i] ?? 0;
            if ($price === 0 || ($types[$i] ?? '') === '' || ($starts[$i] ?? '') === '' || ($ends[$i] ?? '') === '') {
                $row_index_pointer++;
                continue;
            }
            $start_ts = strtotime($starts[$i]);
            $end_ts   = strtotime($ends[$i]);

            if ($start_ts === false || $end_ts === false) {
                $row_index_pointer++;
                continue;
            }

            if ($start_ts > $end_ts) {
                $row_index_pointer++;
                continue;
            }
            $row_id = isset($row_ids[$row_index_pointer]) ? sanitize_text_field($row_ids[$row_index_pointer]) : uniqid('dt_', true);
            $is_active = in_array($row_id, $active_ids, true) ? 'active' : 'inactive';

            $rules[] = [
                'row_id' => $row_id,
                'type'   => sanitize_text_field($types[$i]   ?? ''),
                'start'  => sanitize_text_field($starts[$i]  ?? ''),
                'end'    => sanitize_text_field($ends[$i]    ?? ''),
                'price'  => wc_format_decimal($price),
                'status' => $is_active,
            ];

            $row_index_pointer++;
        }
        if(isset($rules) && count($rules) > 0)
        {
            $product->update_meta_data('_dynamic_price_rules', $rules);
        }
        else
        {
            $product->delete_meta_data('_dynamic_price_rules');
        }

        // DATE-based rules
        $date_rules   = [];
        $d_start_days = $_POST['dyn_date_start_day'] ?? [];
        $d_end_days   = $_POST['dyn_date_end_day']   ?? [];
        $d_starts     = $_POST['dyn_date_start']     ?? [];
        $d_ends       = $_POST['dyn_date_end']       ?? [];
        $d_prices     = $_POST['dyn_date_price']     ?? [];

        $d_count = count($d_prices);

        for ($i = 0; $i < $d_count; $i++) {
            $price = $d_prices[$i] ?? 0;
            if ($price === 0 || ($d_start_days[$i] ?? '') === '' || ($d_end_days[$i] ?? '') === '' || ($d_starts[$i] ?? '') === '' || ($d_ends[$i] ?? '') === '') {
                $row_index_pointer++;
                continue;
            }

            $start_ts_d = strtotime($d_starts[$i]);
            $end_ts_d   = strtotime($d_ends[$i]);

            if ($start_ts_d === false || $end_ts_d === false) {
                $row_index_pointer++;
                continue;
            }

            if ($start_ts_d > $end_ts_d) {
                $row_index_pointer++;
                continue;
            }
            $row_id    = isset($row_ids[$row_index_pointer]) ? sanitize_text_field($row_ids[$row_index_pointer]) : uniqid('dd_', true);
            $is_active = in_array($row_id, $active_ids, true) ? 'active' : 'inactive';

            $date_rules[] = [
                'row_id'    => $row_id,
                'start_day' => sanitize_text_field($d_start_days[$i] ?? ''),
                'end_day'   => sanitize_text_field($d_end_days[$i]   ?? ''),
                'start'     => sanitize_text_field($d_starts[$i]     ?? ''),
                'end'       => sanitize_text_field($d_ends[$i]       ?? ''),
                'price'     => wc_format_decimal($price),
                'status'    => $is_active,
            ];

            $row_index_pointer++;
        }
        if(isset($date_rules) && count($date_rules) > 0)
        {
            $product->update_meta_data('_dynamic_price_date_rules', $date_rules);
        }
        else
        {
            $product->delete_meta_data('_dynamic_price_date_rules');
        }
    }

    /* ---------------------------------------------
     * VARIATION FIELDS
     *  - Updated to use checkbox status only
     *  - No status <select>, table fits the tab
     * --------------------------------------------- */

    public function variation_fields($loop, $variation_data, $variation)
    {
        $variation_id = $variation->ID;

        // $enable = get_post_meta($variation_id, '_enable_dynamic_price', true);
        $type_rules = get_post_meta($variation_id, '_dynamic_price_rules', true);
        if (!is_array($type_rules)) $type_rules = [];

        $date_rules = get_post_meta($variation_id, '_dynamic_price_date_rules', true);
        if (!is_array($date_rules)) $date_rules = [];

        // echo '<div class="form-row form-row-full">';
        // woocommerce_wp_checkbox([
        //     'id'    => "_enable_dynamic_price_$variation_id",
        //     'label' => __('Enable Dynamic Pricing', 'pinaka-dynamic-price'),
        //     'value' => $enable === "yes" ? "yes" : "no",
        // ]);
        // echo '</div>';

        echo '<style>
            .woocommerce_variable_attributes .wc-dtp-table-wrapper {
                overflow-x:auto;
                padding:5px 0;
            }
            .woocommerce_variable_attributes .wc-dtp-table-wrapper table.widefat {
                width:100%;
            }
        </style>';

        /* ============================================================
        * TYPE RULES TABLE
        * ============================================================ */
        echo '<h4>Type-Based Rules</h4>';
        echo '<div class="wc-dtp-table-wrapper">
            <table class="widefat wc-dtp-type-table" id="wc-dtp-var-type-table-'.$variation_id.'">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Start</th>
                    <th>End</th>
                    <th nowrap>Price (Dynamic)</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>';

        foreach ($type_rules as $rule) {
            $row_id = $rule['row_id'] ?? uniqid('vdt_', true);
            $type   = $rule['type'] ?? '';
            $start  = $rule['start'] ?? '';
            $end    = $rule['end'] ?? '';
            $price  = $rule['price'] ?? '';
            $status = $rule['status'] ?? 'inactive';

            echo '<tr class="wc-dtp-row wc-dtp-type-row">
                <td>
                    <input type="hidden" name="dyn_var_row_id['.$variation_id.'][]" value="'.esc_attr($row_id).'">

                    <select name="dyn_var_type['.$variation_id.'][]" class="wc-dtp-type-select">
                        <option value="">Select</option>
                        <option value="Everyday" '.selected($type, "Everyday", false).'>Everyday</option>
                        <option value="Monday" '.selected($type, "Monday", false).'>Monday</option>
                        <option value="Tuesday" '.selected($type, "Tuesday", false).'>Tuesday</option>
                        <option value="Wednesday" '.selected($type, "Wednesday", false).'>Wednesday</option>
                        <option value="Thursday" '.selected($type, "Thursday", false).'>Thursday</option>
                        <option value="Friday" '.selected($type, "Friday", false).'>Friday</option>
                        <option value="Saturday" '.selected($type, "Saturday", false).'>Saturday</option>
                        <option value="Sunday" '.selected($type, "Sunday", false).'>Sunday</option>
                    </select>
                </td>

                <td><input type="time" name="dyn_var_start['.$variation_id.'][]" value="'.esc_attr($start).'"></td>
                <td><input type="time" name="dyn_var_end['.$variation_id.'][]" value="'.esc_attr($end).'"></td>
                <td nowrap><input type="text" name="dyn_var_price['.$variation_id.'][]" class="wc-dtp-price-field" autocomplete="off" inputmode="decimal" pattern="^\d+(\.\d{1,2})?$" placeholder="0.00" value="'.esc_attr($price).'"></td>

                <td>
                    <input type="checkbox"
                        class="wc-dtp-active-checkbox"
                        name="dyn_var_active_ids['.$variation_id.'][]"
                        value="'.esc_attr($row_id).'"
                        '.checked($status, "active", false).'>
                </td>

                <td><a href="#" class="button wc-dtp-remove-row">Remove</a></td>
            </tr>';
        }

        echo '</tbody></table></div>';

        echo '<p><a href="#" class="button wc-dtp-add-var-type-row" data-variation_id="'.$variation_id.'">Add Type Rule +</a></p>';


        /* ============================================================
        * DATE RANGE RULES TABLE
        * ============================================================ */
        echo '<h4>Date-Range Based Rules</h4>';
        echo '<div class="wc-dtp-table-wrapper">
            <table class="widefat wc-dtp-date-table" id="wc-dtp-var-date-table-'.$variation_id.'">
            <thead>
                <tr>
                    <th>Start Day</th>
                    <th>End Day</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th nowrap>Price (Dynamic)</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>';

        foreach ($date_rules as $rule) {
            $row_id    = $rule['row_id'] ?? uniqid('vdd_', true);
            $start_day = $rule['start_day'] ?? '';
            $end_day   = $rule['end_day']   ?? '';
            $start     = $rule['start']     ?? '';
            $end       = $rule['end']       ?? '';
            $price     = $rule['price']     ?? '';
            $status    = $rule['status']    ?? 'inactive';

            echo '<tr class="wc-dtp-row wc-dtp-date-row">
                <td><input type="date" name="dyn_var_date_start_day['.$variation_id.'][]" value="'.esc_attr($start_day).'"></td>
                <td><input type="date" name="dyn_var_date_end_day['.$variation_id.'][]" value="'.esc_attr($end_day).'"></td>

                <td><input type="time" name="dyn_var_date_start['.$variation_id.'][]" value="'.esc_attr($start).'"></td>
                <td><input type="time" name="dyn_var_date_end['.$variation_id.'][]" value="'.esc_attr($end).'"></td>

                <td nowrap><input type="text" name="dyn_var_date_price['.$variation_id.'][]" class="wc-dtp-price-field" autocomplete="off" inputmode="decimal" pattern="^\d+(\.\d{1,2})?$" placeholder="0.00" value="'.esc_attr($price).'"></td>

                <td>
                    <input type="checkbox"
                        class="wc-dtp-active-checkbox"
                        name="dyn_var_active_ids['.$variation_id.'][]"
                        value="'.esc_attr($row_id).'"
                        '.checked($status, "active", false).'>

                    <input type="hidden" name="dyn_var_row_id['.$variation_id.'][]" value="'.esc_attr($row_id).'">
                </td>

                <td><a href="#" class="button wc-dtp-remove-row">Remove</a></td>
            </tr>';
        }

        echo '</tbody></table></div>';

        echo '<p><a href="#" class="button wc-dtp-add-var-date-row" data-variation_id="'.$variation_id.'">Add Date Rule +</a></p>';


        /* ============================================================
        * TEMPLATES
        * ============================================================ */
        ?>

        <!-- TYPE RULE TEMPLATE -->
        <script type="text/html" id="wc-dtp-var-type-template-<?php echo $variation_id; ?>">
            <tr class="wc-dtp-row wc-dtp-type-row">
                <td>
                    <input type="hidden" name="dyn_var_row_id[<?php echo $variation_id; ?>][]" value="__ROWID__">

                    <select name="dyn_var_type[<?php echo $variation_id; ?>][]" class="wc-dtp-type-select">
                        <option value="">Select</option>
                        <option value="Everyday">Everyday</option>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday</option>
                    </select>
                </td>

                <td><input type="time" name="dyn_var_start[<?php echo $variation_id; ?>][]" value=""></td>
                <td><input type="time" name="dyn_var_end[<?php echo $variation_id; ?>][]" value=""></td>
                <td nowrap><input type="text" name="dyn_var_price[<?php echo $variation_id; ?>][]" class="wc-dtp-price-field" autocomplete="off" inputmode="decimal" pattern="^\d+(\.\d{1,2})?$" placeholder="0.00" value=""></td>

                <td>
                    <input type="checkbox"
                        class="wc-dtp-active-checkbox"
                        name="dyn_var_active_ids[<?php echo $variation_id; ?>][]"
                        value="__ROWID__">
                </td>

                <td><a href="#" class="button wc-dtp-remove-row">Remove</a></td>
            </tr>
        </script>

        <!-- DATE RULE TEMPLATE -->
        <script type="text/html" id="wc-dtp-var-date-template-<?php echo $variation_id; ?>">
            <tr class="wc-dtp-row wc-dtp-date-row">
                <td><input type="date" name="dyn_var_date_start_day[<?php echo $variation_id; ?>][]" value=""></td>
                <td><input type="date" name="dyn_var_date_end_day[<?php echo $variation_id; ?>][]" value=""></td>

                <td><input type="time" name="dyn_var_date_start[<?php echo $variation_id; ?>][]" value=""></td>
                <td><input type="time" name="dyn_var_date_end[<?php echo $variation_id; ?>][]" value=""></td>

                <td nowrap><input type="text" name="dyn_var_date_price[<?php echo $variation_id; ?>][]" class="wc-dtp-price-field" autocomplete="off" inputmode="decimal" pattern="^\d+(\.\d{1,2})?$" placeholder="0.00" value=""></td>

                <td>
                    <input type="checkbox"
                        class="wc-dtp-active-checkbox"
                        name="dyn_var_active_ids[<?php echo $variation_id; ?>][]"
                        value="__ROWID__">

                    <input type="hidden" name="dyn_var_row_id[<?php echo $variation_id; ?>][]" value="__ROWID__">
                </td>

                <td><a href="#" class="button wc-dtp-remove-row">Remove</a></td>
            </tr>
        </script>

        <?php
    }

    /* ---------------------------------------------
     * SAVE VARIATION
     *  - Status is derived only from checkbox (active_ids)
     * --------------------------------------------- */

    public function save_variation_meta($variation_id, $i)
    {
        /* ============================================================
        * ENABLE FLAG
        * ============================================================ */
        // $enable = isset($_POST["_enable_dynamic_price_$variation_id"]) ? 'yes' : 'no';
        // update_post_meta($variation_id, '_enable_dynamic_price', $enable);

        /* ============================================================
        * All ACTIVE row IDs (checkbox values)
        * ============================================================ */
        $active_ids = $_POST['dyn_var_active_ids'][$variation_id] ?? [];

        /* ============================================================
        * ALL row IDs IN DOM ORDER (TYPE + DATE rows)
        * ============================================================ */
        $row_ids = $_POST['dyn_var_row_id'][$variation_id] ?? [];

        /* POINTER → moves across row_ids[] array */
        $row_pointer = 0;


        /* ============================================================
        * 1) SAVE TYPE RULES
        * ============================================================ */
        $type_rules = [];

        $types  = $_POST['dyn_var_type'][$variation_id]  ?? [];
        $starts = $_POST['dyn_var_start'][$variation_id] ?? [];
        $ends   = $_POST['dyn_var_end'][$variation_id]   ?? [];
        $prices = $_POST['dyn_var_price'][$variation_id] ?? [];

        for ($x = 0; $x < count($prices); $x++) {

            $price = trim($prices[$x]);

            // skip completely empty rows
            if ($price === '' && ($types[$x] ?? '') === '' && ($starts[$x] ?? '') === '' && ($ends[$x] ?? '') === '') {
                $row_pointer++;
                continue;
            }

            $row_id = sanitize_text_field($row_ids[$row_pointer] ?? uniqid("vdt_", true));
            $row_pointer++;

            $type_rules[] = [
                'row_id' => $row_id,
                'type'   => sanitize_text_field($types[$x] ?? ''),
                'start'  => sanitize_text_field($starts[$x] ?? ''),
                'end'    => sanitize_text_field($ends[$x]   ?? ''),
                'price'  => wc_format_decimal($price),
                'status' => in_array($row_id, $active_ids, true) ? 'active' : 'inactive'
            ];
        }
        if(isset($type_rules) && count($type_rules) > 0)
        {
            update_post_meta($variation_id, '_dynamic_price_rules', $type_rules);
        }
        else
        {
            delete_post_meta($variation_id, '_dynamic_price_rules');
        }

        /* ============================================================
        * 2) SAVE DATE RANGE RULES
        * ============================================================ */
        $date_rules = [];

        $start_days = $_POST['dyn_var_date_start_day'][$variation_id] ?? [];
        $end_days   = $_POST['dyn_var_date_end_day'][$variation_id]   ?? [];
        $d_starts   = $_POST['dyn_var_date_start'][$variation_id]     ?? [];
        $d_ends     = $_POST['dyn_var_date_end'][$variation_id]       ?? [];
        $d_prices   = $_POST['dyn_var_date_price'][$variation_id]     ?? [];

        for ($i2 = 0; $i2 < count($d_prices); $i2++) {

            $price = trim($d_prices[$i2]);

            // skip empty date rows
            if ($price === '' &&
                ($start_days[$i2] ?? '') === '' &&
                ($end_days[$i2] ?? '') === '' &&
                ($d_starts[$i2] ?? '') === '' &&
                ($d_ends[$i2] ?? '') === '') {

                $row_pointer++;
                continue;
            }

            $row_id = sanitize_text_field($row_ids[$row_pointer] ?? uniqid("vdd_", true));
            $row_pointer++;

            $date_rules[] = [
                'row_id'    => $row_id,
                'start_day' => sanitize_text_field($start_days[$i2] ?? ''),
                'end_day'   => sanitize_text_field($end_days[$i2]   ?? ''),
                'start'     => sanitize_text_field($d_starts[$i2]   ?? ''),
                'end'       => sanitize_text_field($d_ends[$i2]     ?? ''),
                'price'     => wc_format_decimal($price),
                'status'    => in_array($row_id, $active_ids, true) ? 'active' : 'inactive'
            ];
        }
        if(isset($date_rules) && count($date_rules) > 0)
        {
            update_post_meta($variation_id, '_dynamic_price_date_rules', $date_rules);
        }
        else
        {
            delete_post_meta($variation_id, '_dynamic_price_date_rules');
        }
    }

    /* ---------------------------------------------
     * JS LOADER
     * --------------------------------------------- */
    public function enqueue_admin_assets()
    {
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'product') return;

        wp_enqueue_script('woocommerce_admin');

        wp_enqueue_script(
            'pinaka-dynamic-price-admin',
            plugin_dir_url(__FILE__) . 'admin-dynamic-price.js',
            ['jquery', 'woocommerce_admin'],
            time(),
            true
        );
    }

    /* ---------------------------------------------
     * FRONTEND PRICE FILTER
     * (uses _dynamic_price_rules only — rules with status='active' apply)
     * --------------------------------------------- */

    // public function filter_price($price, $product)
    // {
    //     $pid   = $product->get_id();
    //     $rules = get_post_meta($pid, '_dynamic_price_rules', true);
    //     error_log('rules'.print_r($rules,true));
    //     if (!$rules || !is_array($rules)) return $price;

    //     $now = current_time('H:i');

    //     foreach ($rules as $r) {

    //         if (isset($r['status']) && $r['status'] !== 'active') {
    //             continue;
    //         }
    //         $start = $r['start'] ?? '';
    //         $end   = $r['end']   ?? '';
    //         if ($start !== '' && $end !== '' && $now >= $start && $now <= $end) {
                
    //             return $r['price'];
    //         }
    //     }
    //     return $price;
    // }
    public function filter_price($price, $product)
    {
        $pid = $product->get_id();

        $type_rules = get_post_meta($pid, '_dynamic_price_rules', true);
        $date_rules = get_post_meta($pid, '_dynamic_price_date_rules', true);

        $now_time  = current_time('H:i');
        $now_date  = current_time('Y-m-d');
        $now_day   = date('l', current_time('timestamp')); // Monday, Tuesday...
        /* =====================================================
        * 1) DATE RULES → Highest Priority
        * ===================================================== */
        // error_log($now_day);
        
        if (is_array($date_rules) && count($date_rules) > 0) {
            foreach ($date_rules as $rule) {
                
                if (!isset($rule['status']) || $rule['status'] !== 'active') {
                    continue;
                }

                $start_day = $rule['start_day'] ?? '';
                $end_day   = $rule['end_day']   ?? '';
                $start     = $rule['start']     ?? '';
                $end       = $rule['end']       ?? '';

                if ($start_day && $end_day && $now_date >= $start_day && $now_date <= $end_day) {
                    // Time match
                    if ($start !== '' && $end !== '' && $now_time >= $start && $now_time <= $end) {
                        // update_post_meta($pid, '_dynamic_price_exists', 1);
                        return $rule['price'];
                    }
                }
            }
        }

        /* =====================================================
        * 2) TYPE (Everyday / Monday–Sunday)
        * ===================================================== */
        if (is_array($type_rules) && count($type_rules) > 0) {
            foreach ($type_rules as $rule) {
                
                if (!isset($rule['status']) || $rule['status'] !== 'active') {
                    continue;
                }
                
                $type  = $rule['type']  ?? '';
                $start = $rule['start'] ?? '';
                $end   = $rule['end']   ?? '';

                // Match type: Everyday or exact weekday
                
                if (
                    $type === 'Everyday' ||
                    $type === $now_day
                ) {
                    
                    // Time match
                    //error_log($now_time.'-'.$start.'-'.$end);
                    if ($start !== '' && $end !== '' && $now_time >= $start && $now_time <= $end) {
                        // update_post_meta($pid, '_dynamic_price_exists', 1);
                        //error_log('wewe'.$rule['price']);
                        return $rule['price'];
                    }
                }
            }
        }
        // delete_post_meta( $pid, '_dynamic_price_exists' );
        /* =====================================================
        * 3) No match → Return default price
        * ===================================================== */
        return $price;
    }

    /* ---------------------------------------------
     * CART STORE
     * --------------------------------------------- */

    public function store_cart_rule($cart_item, $pid, $vid)
    {
        $id = $vid ?: $pid;

        $product = wc_get_product($id);
        $price   = $product->get_price();

        $rules = get_post_meta($id, '_dynamic_price_rules', true);

        if (!$rules) return $cart_item;

        $now = current_time('H:i');
        foreach ($rules as $r) {

            if (isset($r['status']) && $r['status'] !== 'active') {
                continue;
            }

            $start = $r['start'] ?? '';
            $end   = $r['end']   ?? '';

            if ($start !== '' && $end !== '' && $now >= $start && $now <= $end) {
                $cart_item['dynamic_pricing'] = [
                    'price' => $r['price'],
                    'rule'  => $r
                ];
            }
        }

        return $cart_item;
    }

    public function load_cart_rule($item, $values)
    {
        if (isset($values['dynamic_pricing'])) {
            $item['dynamic_pricing'] = $values['dynamic_pricing'];
        }
        return $item;
    }

    public function apply_cart_price($cart)
    {
        foreach ($cart->get_cart() as $key => $item) {
            if (isset($item['dynamic_pricing']['price'])) {
                $item['data']->set_price($item['dynamic_pricing']['price']);
            }
        }
    }

    public function store_order_item_meta($item, $key, $values, $order)
    {
        if (isset($values['dynamic_pricing'])) {
            $item->add_meta_data('_dynamic_price', $values['dynamic_pricing']['price'], true);
            $item->add_meta_data('_dynamic_price_rule', wp_json_encode($values['dynamic_pricing']['rule']), true);
        }
    }
}

<?php 
if(!defined('WPINC')){
    die;
}

class Pinaka_POS_Inventory{
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version){
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('init',[$this, 'register_inventory_post_type']);
        add_action('add_meta_boxes',[$this, 'add_inventory_meta_boxes']);
        add_action('save_post',[$this, 'save_inventory_meta']);

        //Hooks for custom columns
        add_filter('manage_inventory_posts_columns', [$this, 'set_custom_columns']);
        add_action('manage_inventory_posts_custom_column', [$this, 'custom_inventory_column_content'], 10, 2);

        add_action('admin_enqueue_scripts', [$this, 'enqueue_import_assets']);
        add_action('admin_footer-edit.php', [$this, 'render_inventory_import_modal']);
        add_action('wp_ajax_pinaka_inventory_import_csv', [$this, 'handle_inventory_csv_import']);

        add_action('admin_notices', [$this, 'admin_notices']);

    }

    public function register_inventory_post_type(){
        $labels=[
            'name'=> __('Inventory', 'pinaka-pos'),
            'singular_name' => __('Inventory', 'pinaka-pos'),
            'add_new_item'=> __('Add New Inventory', 'pinaka-pos'),
            'edit_item'=> __('Edit Inventory', 'pinaka-pos'),
            'new_item' => __('New Inventory', 'pinaka-pos'),
            'view_item' => __('View Inventory', 'pinaka-pos'),
            'search_items'=> __('Search Inventories', 'pinaka-pos'),
            'not_found'=> __('No Inventories found', 'pinaka-pos'),
            'not_found_in_trash'=> __('No Inventories found in Trash', 'pinaka-pos'),
            'menu_name'=> __('Inventories', 'pinaka-pos'),
        ];

        $args = [
            'labels'=> $labels,
            'description'=> __('Restaurant Inventories like Inventory - 1, Inventory - 1, etc.','pinaka-pos'),
            'public'=> true,
            'publicly_queryable'=> true,
            'show_ui'=> true,
            'show_in_menu'=> false,  //set to true so it's visible
            'menu_position'=> null,
            'menu_icon'=> 'dashicons-grid-view',
            'query_var'=> true,
            'rewrite'=> ['slug' => 'inventories'],
            'capability_type'=> 'post',
            'has_archive'=> true,
            'hierarchical'=> false,
            'supports'=> ['title'],
        ];

        register_post_type('inventory',$args);
    }

    public function set_custom_columns($columns){
        //Rearranged and added custom fields
        unset($columns['date']);
        $columns['title'] = __('Inventory Name','pinaka-pos');
        $columns['inventory_in_name'] = __('In-Name', 'pinaka-pos');
        $columns['inventory_restaurant_id'] = __('Restaurant_Id', 'pinaka-pos');
        $columns['inventory_restaurant_name'] = __('Restaurant Name', 'pinaka-pos');
        $columns['inventory_quantity'] = __('Quantity', 'pinaka-pos');
        $columns['inventory_price'] = __('Price', 'pinaka-pos');
        $columns['inventory_threshold'] = __('Minimum Quantity', 'pinaka-pos');
        $columns['inventory_max_qty'] = __('Maximum Quantity', 'pinaka-pos');

        return $columns;
    }

    public function custom_inventory_column_content($column, $post_id){
    // Fetch quantity for use in any case
    $quantity = floatval(get_post_meta($post_id, '_inventory_quantity', true));
    
        switch ($column) {
        case 'inventory_quantity':
            $qty = get_post_meta($post_id, '_inventory_quantity', true);
            $unit = get_post_meta($post_id, '_inventory_quantity_unit', true);
            $unit_labels = ['kg' => 'Kgs', 'gms' => 'Gms', 'li' => 'Li'];
            $unit_label = isset($unit_labels[$unit]) ? $unit_labels[$unit] : $unit;
            echo esc_html("{$qty} {$unit_label}");
            break;

        case 'inventory_threshold':
            $threshold = get_post_meta($post_id, '_inventory_threshold', true);
            $unit = get_post_meta($post_id, '_inventory_threshold_unit', true);
            $unit_labels = ['kg' => 'Kgs', 'gms' => 'Gms', 'li' => 'Li'];
            $unit_label = isset($unit_labels[$unit]) ? $unit_labels[$unit] : $unit;
            
            // Only show the warning symbol if quantity < threshold
            $danger_icon = ($quantity <= $threshold) ? ' <span style="color: red;">&#9888;</span>' : '';

            echo esc_html("{$threshold} {$unit_label}") . $danger_icon;
            break;

        case 'inventory_price':
            $price = get_post_meta($post_id, '_inventory_price', true);
            echo '₹' . esc_html(number_format((float)$price, 2)); // Rs. formatting
            break;

        case 'inventory_in_name':
            echo esc_html(get_post_meta($post_id, '_inventory_in_name', true));
            break;

        case 'inventory_max_qty':
            $max_qty = get_post_meta($post_id, '_inventory_max_qty', true);
            $unit = get_post_meta($post_id, '_inventory_quantity_unit', true);
            $unit_labels = ['kg' => 'Kgs', 'gms' => 'Gms', 'li' => 'Li'];
            $unit_label = isset($unit_labels[$unit]) ? $unit_labels[$unit] : $unit;

            //Optional warning icon if quantity > max
            $exceed_icon = ($quantity > $max_qty) ? '<span style="color: orange;">&#9888;</span>' :'';

            echo esc_html("{$max_qty} {$unit_label}") . $exceed_icon;
            break;

        case 'inventory_restaurant_id':
            echo esc_html(get_post_meta($post_id, '_inventory_restaurant_id', true));
            break;

        case 'inventory_restaurant_name':
            echo esc_html(get_post_meta($post_id, '_inventory_restaurant_name', true));
            break;
        }
    }

    public function add_inventory_submenu_page(){
        add_submenu_page(
            'pin-pos-dashboard',
            __('Manage Inventories', 'pin-pos'),
            __('Manage Inventories', 'pin-pos'),
            'manage_options',
            'pin-pos-inventories',
            [$this, 'inventortRender']
        );
    }

    public function inventoryRender(){
        wp_safe_redirect(admin_url('edit.php?post_type=tables'));
        exit;
    }
    
    public function add_inventory_meta_boxes(){
        add_meta_box(
            'inventory_details',
            __('Inventory Details', 'pinaka-pos'),
            [$this, 'render_inventory_meta_box'],
            'inventory',
            'normal',
            'high'
        );
    }

    public function render_inventory_meta_box($post)
    {
        $meta_fields = [
            'inventory_quantity' => '',
            'inventory_quantity_unit'=> '',
            'inventory_in_name' => '',
            'inventory_restaurant_id' => '',
            'inventory_restaurant_name' => '',
            'inventory_price' => '',
            'inventory_threshold' => '',
            'inventory_threshold_unit'=> '',
            'inventory_max_qty' =>''
        ];

        foreach ($meta_fields as $key => $default){
            $meta_fields[$key] = get_post_meta($post->ID, '_' . $key, true);
        }


        $units = ['kg' => 'Kgs', 'gms' => 'Grams', 'li' => 'Litres'];

        wp_nonce_field('save_inventory_meta_box','inventory_meta_box_nonce');
        ?>
        <p>
            <label>Quantity:</label><br>
            <div style="display: flex; gap: 10px;">
                <input type="number" step="0.01" min="0" name="inventory_quantity" 
                    value="<?php echo esc_attr($meta_fields['inventory_quantity']); ?>" 
                    style="width: 60%;" required autocomplete="off"
                    placeholder="e.g., 5">
        
                <select name="inventory_quantity_unit" style="width: 38%;" required>
                    <option value="">Unit</option>
                    <?php
                    foreach ($units as $key => $label) {
                        $selected = selected($meta_fields['inventory_quantity_unit'], $key, false);
                        echo "<option value='" . esc_attr($key) . "' $selected>" . esc_html($label) . "</option>";
                    }
                    ?>
                </select>
            </div>
        </p>
        <p>
            <label>In-Name:</label><br>
            <input type="text" name="inventory_in_name" 
                value="<?php echo esc_attr($meta_fields['inventory_in_name']); ?>" 
                style="width:100%;"
                autocomplete="off" required>
        </p>
        <?php

        $restaurant_data = get_option('pinaka_pos_restaurants', []);
        $selected_restaurant_id = get_post_meta($post->ID, '_inventory_restaurant_id', true);
        $selected_restaurant_name = get_post_meta($post->ID, '_inventory_restaurant_name', true);
        ?>

        <p>
            <label for="inventory_restaurant_id">Restaurant Name</label><br>
                <select name="inventory_restaurant_id" id="inventory_restaurant_id">
                    <option value="">Select Restaurant</option>
                    <?php foreach ($restaurant_data as $id => $restaurant): ?>
                    <option value="<?php echo esc_attr($restaurant['id']); ?>" <?php selected($selected_restaurant_id, $id); ?>>
                        <?php echo esc_html($restaurant['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

            <input type="hidden" name="inventory_restaurant_name" id="inventory_restaurant_name"
            value="<?php echo esc_attr($selected_restaurant_name); ?>" />

        </p>

        <p>
            <label for="inventory_restaurant_id_display"><strong>Restaurant ID:</strong></label><br>
            <input type="text" id="inventory_restaurant_id_display" readonly
            value="<?php echo esc_attr($selected_restaurant_id); ?>" />
        </p>
    
            <script>

                document.addEventListener('DOMContentLoaded', function () {
                    const select = document.getElementById('inventory_restaurant_id');
                    const hiddenName = document.getElementById('inventory_restaurant_name');
                    const restaurantIdDisplay = document.getElementById('inventory_restaurant_id_display');
                    const restaurants = <?php echo json_encode($restaurant_data); ?>;

                    if (select && hiddenName) {
                        select.addEventListener('change', function () {
                            const selectedId = this.value;
                            hiddenName.value = restaurants[selectedId]?.name || '';
                            restaurantIdDisplay.value = selectedId;
                        });
                        if(select.value){
                            restaurantIdDisplay.value = select.value;
                        }
                    }
                });
            </script>
        </p>

        <p>
            <label>Price(Rs.):</label><br>
            <input type="number" step="0.01" name="inventory_price" 
                value="<?php echo esc_attr($meta_fields['inventory_price']); ?>"
                style="width:100%;"
                autocomplete="off" required>
        </p>
        <p>
            <label>Minimum Quantity:</label><br>
            <div style="display: flex; gap: 10px;">
            <input type="number" step="0.01" min="5" name="inventory_threshold"
                id="inventory_threshold"
            value="<?php echo esc_attr($meta_fields['inventory_threshold']); ?>"
            style="width: 60%;" required autocomplete="off"
            placeholder="Minimum 5" readonly>

        <select name="inventory_threshold_unit" id="inventory_threshold_unit" style="width: 38%;" required>
            <option value="">Unit</option>
            <?php
            foreach ($units as $key => $label) {
                $selected = selected($meta_fields['inventory_threshold_unit'], $key, false);
                echo "<option value='" . esc_attr($key) . "' $selected>" . esc_html($label) . "</option>";
            }
            ?>
        </select>
        </div>
        </p>

        <p>
            <label>Maximim Quantity:</label><br>
            <div style="display: flex; gap: 10px;">
        <input type="number" step="0.01" min="0" max="45" name="inventory_max_qty"
            id="inventory_max_qty"
            value="<?php echo esc_attr($meta_fields['inventory_max_qty']); ?>"
            style="width: 60%;" required autocomplete="off"
            placeholder="Max 45" readonly>

        <select name="inventory_max_qty_unit" id="inventory_max_qty_unit" style="width: 38%;" required>
            <option value="">Unit</option>
            <?php
            foreach ($units as $key => $label) {
                $selected = selected($meta_fields['inventory_max_qty_unit'], $key, false);
                echo "<option value='" . esc_attr($key) . "' $selected>" . esc_html($label) . "</option>";
            }
            ?>
        </select>
    </div>
        </p>   

        <script>
            document.addEventListener('DOMContentLoaded', function () {
            const qtyInput = document.querySelector('[name="inventory_quantity"]');
            const qtyUnit = document.querySelector('[name="inventory_quantity_unit"]');

            const thresholdInput = document.getElementById('inventory_threshold');
            const thresholdUnit = document.getElementById('inventory_threshold_unit');

            const maxQtyInput = document.getElementById('inventory_max_qty');
            const maxQtyUnit = document.getElementById('inventory_max_qty_unit');

            function setThresholdAndMax() {
        const qtyVal = parseFloat(qtyInput.value);
        const unitVal = qtyUnit.value;

        if (!isNaN(qtyVal)) {
            thresholdInput.value = 5;
            thresholdUnit.value = unitVal;

            maxQtyInput.value = 45;
            maxQtyUnit.value = unitVal;
        }
    }

    qtyInput.addEventListener('input', setThresholdAndMax);
    qtyUnit.addEventListener('change', setThresholdAndMax);

    // Initial set on page load
    setThresholdAndMax();
});
        </script>
        <?php
    }

    public function save_inventory_meta($post_id){
        if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if(!isset($_POST['inventory_meta_box_nonce']) || !wp_verify_nonce($_POST['inventory_meta_box_nonce'], 'save_inventory_meta_box')) return;
        if(!current_user_can('edit_post', $post_id)) return;
        if(get_post_type($post_id) !== 'inventory') return;

        //Prevent duplicate inventory post
        $name       = strtolower(sanitize_text_field($_POST['inventory_name'] ?? ''));
        $in_name    = strtolower(sanitize_text_field($_POST['inventory_in_name'] ?? ''));
        $res_id     = sanitize_text_field($_POST['inventory_restaurant_id'] ?? '');
        $res_name   = strtolower(sanitize_text_field($_POST['inventory_restaurant_name'] ?? ''));
        $quantity   = sanitize_text_field($_POST['inventory_quantity'] ?? '');
        $unit       = sanitize_text_field($_POST['inventory_quantity_unit'] ?? '');
        $price      = sanitize_text_field($_POST['inventory_price'] ?? '');
        $min_qty    = sanitize_text_field($_POST['inventory_threshold'] ?? '');
        $max_qty    = sanitize_text_field($_POST['inventory_max_qty'] ?? '');

        if($in_name && $res_id && $quantity){
           // Check by inventory name (title)
$existing_post = get_page_by_title($name, OBJECT, 'inventory');

if ($existing_post) {
    // Update the existing post
    $post_id = $existing_post->ID;

    wp_update_post([
        'ID' => $post_id,
        'post_status' => 'publish',
    ]);
} else {
    // Insert new post
    $post_id = wp_insert_post([
        'post_title' => $name,
        'post_type' => 'inventory',
        'post_status' => 'publish',
    ]);
}

// Update metadata in both cases
update_post_meta($post_id, '_inventory_in_name', $in_name);
update_post_meta($post_id, '_inventory_quantity', $qty);
update_post_meta($post_id, '_inventory_unit', $qty_unit);
update_post_meta($post_id, '_inventory_price', $price);
update_post_meta($post_id, '_inventory_restaurant_name', $restaurant_name);
update_post_meta($post_id, '_inventory_restaurant_id', $restaurant_id);

$imported++;

        }

        if (isset($_POST['inventory_restaurant_id'])) {
            update_post_meta($post_id, '_inventory_restaurant_id', sanitize_text_field($_POST['inventory_restaurant_id']));
        }

        if (isset($_POST['inventory_restaurant_name'])) {
            update_post_meta($post_id, '_inventory_restaurant_name', sanitize_text_field($_POST['inventory_restaurant_name']));
        }


        $fields = [
            'inventory_quantity'=> 'floatval',
            'inventory_quantity_unit'=> 'sanitize_text_field',
            'inventory_in_name'=> 'sanitize_text_field',
            'inventory_restaurant_id' => 'sanitize_text_field',
            'inventory_restaurant_name' => 'sanitize_text_field',
            'inventory_price'=> 'floatval',
            'inventory_threshold'=> 'floatval',
            'inventory_threshold_unit'=> 'sanitize_text_field',
            'inventory_max_qty' => 'floatval',
            'inventory_max_qty_unit' => 'sanitize_text_field'

        ];

        foreach($fields as $key => $sanitize_callback){
            if(isset($_POST[$key])){
                $value = call_user_func($sanitize_callback, $_POST[$key]);
                update_post_meta($post_id, '_' . $key, $value);
                $meta[$key] = $value;
            }
        }

        //Fetch stored quantity and threshod
        $current_quantity = floatval(get_post_meta($post_id, '_inventory_quantity', true));
        $new_value = floatval($_POST['inventory_quantity']);
        $threshold = floatval($_POST['inventory_threshold']);
        $max_qty = floatval($_POST['inventory_max_qty']);
        $unit = sanitize_text_field($_POST['inventory_quantity_unit']);
        

        //Calculate remaining quantity
        $remaining_quantity = $quantity - $new_value;

        if($remaining_quantity <= $threshold){
            $post_title = get_the_title($post_id);
            $to = get_option('admin_email');
            $subject = "Inventory Alert: {$post_title}";
            $message = "Inventory '{$post_title}' is running low.\n\n Remaining Quantity: {$remaining_quantity} {$unit}\n Threshold Limit: {$threshold} {$unit}\n\nPlease restock soon.";
            $headers = ['Content-Type: text/plain; charset=UTF-8'];

            wp_mail($to, $subject, $message, $headers);
        }

        $final_qty = $current_qty + $new_qty;

        //If final is more than max, trigger alert
        if($final_qty > $max_qty){
            $post_title = get_the_title($post_id);
            $to = get_option('admin_email');
            $subject = "Inventory Exceede: {$post_title}";
            $message = "Inventory '{$post_title}' has exceeded the maximum limit.\n\n"
                     . "Final Quantity: {$final_qty} {$unit}\n"
                     . "Maximim Allowed: {$max_qty} {$unit}\n\nPlease review the stock levels.";
            $headers = ['Content-Type: text/plain; charset=UTF-8'];
            wp_mail($to, $subject, $message, $headers);
        }
    }

    public function admin_notices() {
        if (isset($_GET['duplicate_inventory_error'])) {
            echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> Inventory with the same name, in-name, and restaurant ID already exists.</p></div>';
        }
    }


    // Enqueue JS and CSS (Thickbox)
    public function enqueue_import_assets($hook) {
        if ($hook !== 'edit.php' || ($_GET['post_type'] ?? '') !== 'inventory') return;

        wp_enqueue_script('jquery');
        wp_enqueue_script('thickbox');
        wp_enqueue_style('thickbox');
    }

    // Render the modal popup with JS
    public function render_inventory_import_modal() {
        if (($_GET['post_type'] ?? '') !== 'inventory') return;

        $nonce = wp_create_nonce('pinaka_inventory_import');
        $restaurant_data = get_option('pinaka_pos_restaurants', []);
        ?>
        <div id="pinaka-import-modal" style="display:none;">
            <div id="pinaka-step-1">
                <h2>Import Inventory from CSV</h2>
                <p>Select a CSV file to begin.</p>

                <!-- Restaurant Dropdown -->
                 <label for="import_restaurant_id"><strong>Select Restaurant Name:</strong></label>
                 <select id="import_restaurant_id" name="import_restaurant_id" required>
                    <option value="">-- Select Restaurant --</option>
                    <?php foreach($restaurant_data as $id => $rest) : ?>
                        <option value="<?php echo esc_attr($rest['id']); ?>" data-name="<?php echo esc_attr($rest['name']); ?>">
                            <?php echo esc_html($rest['name']); ?>
                        </option>

                    <?php endforeach; ?>
                </select>
                <br><br>

                <!-- File Input -->
                <input type="file" id="pinaka-csv-file" accept=".csv" /><br><br>
                <button class="button button-primary" id="pinaka-import-continue">Continue</button>
            </div>

            <div id="pinaka-step-2" style="display:none;">
                <h2>Ready to Import</h2>
                <p>Click “Start Import” to upload and process the CSV.</p>
                <button class="button button-secondary" id="pinaka-back">Back</button>
                <button class="button button-primary" id="pinaka-import-start">Start Import</button>
            </div>
        </div>

        <script>
jQuery(document).ready(function ($) {
    // ➕ Add "Import" link to page
    $('.wrap > h1').append('<a href="#TB_inline?width=600&height=400&inlineId=pinaka-import-modal" class="page-title-action thickbox">Import</a>');

    let totalRows = 0;

    // Continue button (step 1 → step 2)
    $('#pinaka-import-continue').on('click', function () {
        const fileInput = $('#pinaka-csv-file')[0];
        const file = fileInput.files[0];

        if (!file) {
            alert('Please choose a CSV file.');
            return;
        }

        const reader = new FileReader();

        reader.onload = function (e) {
            const lines = e.target.result.split('\n');
            totalRows = lines.length - 1; // minus header
            $('#row-preview').text(`This file contains ${totalRows} row(s). Ready to import?`);
            $('#pinaka-step-1').hide();
            $('#pinaka-step-2').show();
        };

        reader.readAsText(file);
    });

    // Back button
    $('#pinaka-back').on('click', function () {
        $('#pinaka-step-2').hide();
        $('#pinaka-step-1').show();
        $('#pinaka-loading').hide();
    });

    // Start Import button
    $('#pinaka-import-start').on('click', function () {
        if (!confirm('Are you sure you want to import ' + totalRows + ' rows?')) {
            return;
        }

        let fileInput = $('#pinaka-csv-file')[0];
        let formData = new FormData();
        formData.append('action', 'pinaka_inventory_import_csv');
        formData.append('nonce', '<?php echo $nonce; ?>');
        formData.append('file', fileInput.files[0]);

        // Get selected restaurant values
        const restaurantId = $('#import_restaurant_id').val();
        const restaurantName = $('#import_restaurant_id option:selected').data('name');

        // Add to form data
        formData.append('restaurant_id', restaurantId);
        formData.append('restaurant_name', restaurantName);


        $('#pinaka-loading').show(); // Show spinner

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                $('#pinaka-loading').hide(); // Hide spinner
                if (res.success) {
                    alert(res.data);
                    location.reload();
                } else {
                    alert('Error: ' + res.data);
                }
            },
            error: function () {
                $('#pinaka-loading').hide(); // Hide spinner
                alert('Import failed.');
            }
        });
    });
});
</script>

        <?php
    }

    public function handle_inventory_csv_import() {
    check_ajax_referer('pinaka_inventory_import', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    if (empty($_FILES['file']['tmp_name'])) {
        wp_send_json_error('No file uploaded');
    }

    $file = $_FILES['file']['tmp_name'];
    $handle = fopen($file, 'r');
    if (!$handle) {
        wp_send_json_error('Failed to open file.');
    }

    $imported = 0;
    $row = 0;

    while (($data = fgetcsv($handle, 1000, ',')) !== false) {
        $row++;
        if ($row === 1) continue; // Skip header row

        // Expecting: Inventory Name, In-Name, Quantity, Quantity Unit, Price
        list($name, $in_name, $qty, $qty_unit, $price) = array_map('trim', $data);

        $name = sanitize_text_field($name);
        $in_name = sanitize_text_field($in_name);
        $qty = floatval($qty);
        $qty_unit = sanitize_text_field($qty_unit);
        $price = floatval($price);

        // Restaurant data from dropdown
        $restaurant_id = isset($_POST['restaurant_id']) ? sanitize_text_field($_POST['restaurant_id']) : '';
        $restaurant_name = isset($_POST['restaurant_name']) ? sanitize_text_field($_POST['restaurant_name']) : '';

        // Check if post exists by in_name
        $existing = get_posts([
            'post_type' => 'inventory',
            'meta_key' => '_inventory_in_name',
            'meta_value' => $in_name,
            'posts_per_page' => 1,
            'post_status' => 'any',
        ]);

        if (!empty($existing)) {
            $post_id = $existing[0]->ID;
            wp_update_post([
                'ID' => $post_id,
                'post_title' => $name
            ]);
        } else {
            $post_id = wp_insert_post([
                'post_title' => $name,
                'post_type' => 'inventory',
                'post_status' => 'publish'
            ]);
        }

        if ($post_id && !is_wp_error($post_id)) {
            // Required meta fields
            update_post_meta($post_id, '_inventory_in_name', $in_name);
            update_post_meta($post_id, '_inventory_quantity', $qty);
            update_post_meta($post_id, '_inventory_quantity_unit', $qty_unit);
            update_post_meta($post_id, '_inventory_price', $price);

            // Auto-fill restaurant info
            update_post_meta($post_id, '_inventory_restaurant_id', $restaurant_id);
            update_post_meta($post_id, '_inventory_restaurant_name', $restaurant_name);

            // Auto-fill fixed min/max with same unit
            $min_qty = 5;
            $max_qty = 45;

            update_post_meta($post_id, '_inventory_threshold', $min_qty);
            update_post_meta($post_id, '_inventory_threshold_unit', $qty_unit);
            update_post_meta($post_id, '_inventory_max_qty', $max_qty);
            update_post_meta($post_id, '_inventory_max_qty_unit', $qty_unit);

            $imported++;
        }
    }

    fclose($handle);
    wp_send_json_success("Imported {$imported} records.");
}

}


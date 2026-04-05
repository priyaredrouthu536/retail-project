<?php
if (!defined('ABSPATH')) {
    exit;
}

class Warehouse {

    public function __construct() {

        // Register CPT
        add_action('init', [$this, 'register_warehouse_post_type']);

        // Meta box
        add_action('add_meta_boxes', [$this, 'add_warehouse_meta_box']);
        add_action('save_post', [$this, 'save_warehouse_meta']);

        // Admin columns
        add_filter('manage_edit-warehouse_columns', [$this, 'set_custom_columns']);
        add_action('manage_warehouse_posts_custom_column', [$this, 'custom_column_content'], 10, 2);
    }

    /*
    REGISTER CPT
    */
    public function register_warehouse_post_type() {

        $args = [
            'labels' => [
                'name' => 'Warehouses',
                'singular_name' => 'Warehouse',
                'add_new' => 'Add Warehouse',
                'add_new_item' => 'Add New Warehouse',
                'edit_item' => 'Edit Warehouse',
                'new_item' => 'New Warehouse',
                'view_item' => 'View Warehouse',
                'search_items' => 'Search Warehouses'
            ],

            'public' => true,
            'show_ui' => true,
            'menu_icon' => 'dashicons-store',
            'supports' => ['title'],
            'show_in_rest' => false
        ];

        register_post_type('warehouse', $args);
    }

    /*
    ADD META BOX
    */
    public function add_warehouse_meta_box() {

        add_meta_box(
            'warehouse_details',
            'Warehouse Details',
            [$this, 'render_warehouse_meta_box'],
            'warehouse',
            'normal',
            'high'
        );
    }

    /*
    META BOX FORM
    */
    public function render_warehouse_meta_box($post) {

        $invoice = get_post_meta($post->ID, '_warehouse_invoice_num', true);
        $vendor  = get_post_meta($post->ID, '_warehouse_vendor_name', true);
        $items   = get_post_meta($post->ID, '_warehouse_items', true);
        $total   = get_post_meta($post->ID, '_warehouse_total', true);

        wp_nonce_field('save_warehouse_meta', 'warehouse_nonce');
        ?>

        <table class="form-table">

            <tr>
                <th>Invoice Number</th>
                <td>
                    <input type="text" name="warehouse_invoice_num"
                    value="<?php echo esc_attr($invoice); ?>" style="width:300px;">
                </td>
            </tr>

            <tr>
                <th>Vendor Name</th>
                <td>
                    <input type="text" name="warehouse_vendor_name"
                    value="<?php echo esc_attr($vendor); ?>" style="width:300px;">
                </td>
            </tr>

            <tr>
                <th>Items</th>
                <td>
                    <input type="number" name="warehouse_items"
                    value="<?php echo esc_attr($items); ?>" style="width:300px;">
                </td>
            </tr>

            <tr>
                <th>Total</th>
                <td>
                    <input type="number" name="warehouse_total"
                    value="<?php echo esc_attr($total); ?>" style="width:300px;">
                </td>
            </tr>

        </table>

        <?php
    }

    /*
    SAVE META
    */
    public function save_warehouse_meta($post_id) {

        if (!isset($_POST['warehouse_nonce'])) return;

        if (!wp_verify_nonce($_POST['warehouse_nonce'], 'save_warehouse_meta')) return;

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        if (get_post_type($post_id) != 'warehouse') return;

        update_post_meta(
            $post_id,
            '_warehouse_invoice_num',
            sanitize_text_field($_POST['warehouse_invoice_num'] ?? '')
        );

        update_post_meta(
            $post_id,
            '_warehouse_vendor_name',
            sanitize_text_field($_POST['warehouse_vendor_name'] ?? '')
        );

        update_post_meta(
            $post_id,
            '_warehouse_items',
            sanitize_text_field($_POST['warehouse_items'] ?? '')
        );

        update_post_meta(
            $post_id,
            '_warehouse_total',
            sanitize_text_field($_POST['warehouse_total'] ?? '')
        );
    }

    /*
    ADMIN COLUMNS
    */
    public function set_custom_columns($columns) {

        $new_columns = [];

        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = 'Title';
        $new_columns['warehouse_invoice_num'] = 'Invoice Number';
        $new_columns['warehouse_vendor_name'] = 'Vendor';
        $new_columns['warehouse_items'] = 'Items';
        $new_columns['warehouse_total'] = 'Total';
        $new_columns['date'] = 'Date';

        return $new_columns;
    }

    /*
    COLUMN VALUES
    */
    public function custom_column_content($column, $post_id) {

        switch ($column) {

            case 'warehouse_invoice_num':
                echo esc_html(get_post_meta($post_id, '_warehouse_invoice_num', true));
                break;

            case 'warehouse_vendor_name':
                echo esc_html(get_post_meta($post_id, '_warehouse_vendor_name', true));
                break;

            case 'warehouse_items':
                echo esc_html(get_post_meta($post_id, '_warehouse_items', true));
                break;

            case 'warehouse_total':
                echo esc_html(get_post_meta($post_id, '_warehouse_total', true));
                break;
        }
    }
}

/* RUN CLASS */
new Warehouse();
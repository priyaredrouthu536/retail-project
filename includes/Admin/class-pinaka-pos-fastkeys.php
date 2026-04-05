<?php
/**
 * The admin-fast-keys functionality of the plugin.
 *
 * @package    Fast_Keys
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class Fast_Keys {
     /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $name        The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;
    private $loader;
    
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        add_action('init', [$this, 'register_fast_keys_post_type']);
        add_action('add_meta_boxes', [$this, 'add_fast_keys_meta_box']);
        add_action('save_post', [$this, 'save_fast_keys_meta']);
        add_filter('manage_fast_keys_posts_columns', [$this, 'set_custom_columns']);
        // add_action('manage_fast_keys_posts_custom_column', [$this, 'custom_column_content'], 10, 2);
        add_action('manage_fast_keys_posts_custom_column', [$this, 'render_custom_columns'], 10, 2);
        add_filter('post_row_actions', [$this, 'remove_view_link'], 10, 2);
        // add_filter('manage_fast_keys_posts_columns', [$this, 'move_date_column_last'], 20);
        add_filter('post_row_actions', [$this, 'remove_quick_edit'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_save_fastkeys_order', [$this, 'save_fastkeys_order']);
        add_action('admin_head', [$this, 'add_drag_column_css']);
        add_action('pre_get_posts', [$this, 'set_fastkeys_admin_order']);
    }

    public function register_fast_keys_post_type() {
        $args = [
            'labels' => [
                'name'          => __('Fast Keys', 'pinaka-pos'),
                'singular_name' => __('Fast Key', 'pinaka-pos'),
                'add_new'       => __('Add New Fast Key', 'pinaka-pos'),
                'add_new_item'  => __('Add New Fast Key', 'pinaka-pos'),
                'edit_item'     => __('Edit Fast Key', 'pinaka-pos'),
                'new_item'      => __('New Fast Key', 'pinaka-pos'),
                'view_item'     => __('View Fast Key', 'pinaka-pos'),
                'search_items'  => __('Search Fast Keys', 'pinaka-pos'),
                'not_found'     => __('No Fast Keys Found', 'pinaka-pos'),
                'not_found_in_trash' => __('No Fast Keys Found in Trash', 'pinaka-pos'),
            ],
            'public'        => true, // Ensure it's public
            'show_ui'       => true, // Enable UI in admin
            'show_in_menu'  => true, // Show in admin menu
            'menu_position' => 20, // Adjust position in admin menu
            'menu_icon'     => 'dashicons-editor-ul', // Set a WordPress icon
            'supports'      => ['title'], // Define supported features
            'has_archive'   => true, // Enable archive page
            'show_in_rest'  => true, // Enable for REST API
        ];
        register_post_type('fast_keys', $args);
    }

    // 1. Register new columns
   public function set_custom_columns($columns) {

    return [
        'cb' => $columns['cb'],
        
        'fast_key_image' => __('Image', 'pinaka-pos'),
        'title' => __('Title', 'pinaka-pos'),
        'fast_keys_data' => __('Fast Keys Data', 'pinaka-pos'),
        'fast_keys_user' => __('User Email', 'pinaka-pos'),
        'date' => __('Date', 'pinaka-pos'),
        'drag' => __('', 'pinaka-pos'),
    ];
}
public function add_drag_column_css() {
    global $typenow;

    if ($typenow !== 'fast_keys') return;

    echo '<style>
        .column-drag {
            width:40px;
            text-align:center;
        }
        .drag-handle {
            cursor:move;
            font-size:18px;
        }
    </style>';
}
public function set_fastkeys_admin_order($query) {

    if (!is_admin()) return;
    if (!$query->is_main_query()) return;

    global $typenow;

    if ($typenow === 'fast_keys') {

        $query->set('meta_key', '_fast_key_index');
        $query->set('orderby', 'meta_value_num');
        $query->set('order', 'ASC');
    }
}
    // 2. Fill column content
   public function render_custom_columns($column, $post_id) {

    switch ($column) {

        case 'fast_key_image':

            $image_url = get_post_meta($post_id, '_fast_key_image', true);

            if ($image_url) {
                echo '<img src="' . esc_url($image_url) . '" 
                      style="width:50px;height:50px;object-fit:cover;border-radius:6px;" />';
            } else {
                echo '<div style="
                    width:50px;
                    height:50px;
                    background:#f0f0f0;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    border-radius:6px;
                    color:#999;
                    font-size:12px;">
                    —
                </div>';
            }

            break;

        case 'fast_keys_data':

    $fast_keys = get_post_meta($post_id, '_fast_keys_data', true);

    if ($fast_keys) {

        $fast_keys_array = json_decode($fast_keys, true);

        if (!empty($fast_keys_array)) {

            foreach ($fast_keys_array as $key) {

                $product_id = intval($key['product_id']);
                $product_title = get_the_title($product_id);

                // If product deleted or invalid
                if (!$product_title) {
                    $product_title = 'Product #' . $product_id;
                }

                echo '<strong>' . esc_html($product_title) . '</strong>'
                    . ' - sl_number: '
                    . esc_html($key['sl_number'])
                    . '<br>';
            }

        } else {
            echo 'No Data';
        }

    } else {
        echo 'No Fast Keys';
    }

    break;
        case 'fast_keys_user':

            $user_id = get_post_meta($post_id, '_fast_keys_user_id', true);

            if ($user_id) {
                $user = get_userdata($user_id);
                echo $user ? esc_html($user->user_email) : 'Unknown';
            } else {
                echo 'N/A';
            }

            break;
            case 'drag':
    echo '<span class="drag-handle" style="cursor:move;font-size:18px;">☰</span>';
    break;
    }
}
public function enqueue_admin_scripts($hook) {

    global $typenow;

    if ($typenow !== 'fast_keys') return;

    wp_enqueue_script('jquery-ui-sortable');

    wp_add_inline_script('jquery-ui-sortable', '
        jQuery(document).ready(function($){

            var table = $(".wp-list-table tbody");

            table.sortable({
                items: "tr",
                handle: ".drag-handle",
                axis: "y",
                update: function(event, ui){

                    var order = [];

                    table.find("tr").each(function(){
                        order.push($(this).attr("id").replace("post-", ""));
                    });

                    $.post(ajaxurl, {
                        action: "save_fastkeys_order",
                        order: order
                    });
                }
            });

        });
    ');
}

    // Move Date column to last position
public function move_date_column_last($columns) {
    if (isset($columns['date'])) {
        $date = $columns['date'];
        unset($columns['date']);
        $columns['date'] = $date; // Add again at the end
    }
    return $columns;
}


    public function custom_column_content($column, $post_id) {
        if ($column === 'fast_keys_data') {
            $fast_keys = get_post_meta($post_id, '_fast_keys_data', true);
            if ($fast_keys) {
                $fast_keys_array = json_decode($fast_keys, true);
                if ($fast_keys_array) {
                    echo '<ul>';
                    foreach ($fast_keys_array as $key) {
                        echo '<li>' . esc_html($key['product_id']) . ' - sl_number: ' . esc_html($key['sl_number']) . '</li>';
                    }
                    echo '</ul>';
                } else {
                    echo __('Invalid JSON data', 'pinaka-pos');
                }
            } else {
                echo __('No Fast Keys', 'pinaka-pos');
            }
        }
    }

    public function add_fast_keys_meta_box() {
        add_meta_box(
            'fast_keys_details',
            __('Fast Keys Details', 'pinaka-pos'),
            [$this, 'render_fast_keys_meta_box'],
            'fast_keys',
            'normal',
            'high'
        );
    }
    
    public function render_fast_keys_meta_box($post) {
        // Load stored values
        $fast_keys_json  = get_post_meta($post->ID, '_fast_keys_data', true);
        $fast_keys       = json_decode($fast_keys_json, true) ?: [];

        $fastkey_index   = get_post_meta($post->ID, '_fast_key_index', true);
        $fastkey_user_id = get_post_meta($post->ID, '_fast_keys_user_id', true);
        $fastkey_image   = get_post_meta($post->ID, '_fast_key_image', true);

        ?>
        <h4><?php _e('Fast Key Settings', 'pinaka-pos'); ?></h4>

        <p>
            <label><strong><?php _e('Fast Key Index:', 'pinaka-pos'); ?></strong></label><br>
            <input type="number" name="fastkey_index" value="<?php echo esc_attr($fastkey_index); ?>" style="width:100px;">
        </p>

        <p>
            <label><strong><?php _e('User ID:', 'pinaka-pos'); ?></strong></label><br>
            <input type="text" name="fastkey_user_id" value="<?php echo esc_attr($fastkey_user_id); ?>" readonly style="width:120px; background:#f7f7f7;">
        </p>

        <p>
            <label><strong><?php _e('Fast Key Image:', 'pinaka-pos'); ?></strong></label><br>
            <input type="text" name="fastkey_image" id="fastkey_image" value="<?php echo esc_url($fastkey_image); ?>" style="width:80%;">
            <button type="button" class="button upload-fastkey-image"><?php _e('Upload', 'pinaka-pos'); ?></button>
            <?php if ($fastkey_image): ?>
                <div style="margin-top:10px;">
                    <img src="<?php echo esc_url($fastkey_image); ?>" style="max-width:150px; border:1px solid #ddd;">
                </div>
            <?php endif; ?>
        </p>

        <h4><?php _e('Fast Keys Data (Products)', 'pinaka-pos'); ?></h4>
        <table style="width:100%; border:1px solid #ddd; border-collapse:collapse;">
            <tr style="background:#f7f7f7;">
                <th style="padding:5px; border:1px solid #ddd;">Product ID</th>
                <th style="padding:5px; border:1px solid #ddd;">Sort Order</th>
            </tr>
            <?php if (!empty($fast_keys)) : ?>
                <?php foreach ($fast_keys as $key) : ?>
                    <tr>
                        <td style="padding:5px; border:1px solid #ddd;">
                            <input type="text" name="fast_keys[product_id][]" value="<?php echo esc_attr($key['product_id']); ?>" style="width:100%;">
                        </td>
                        <td style="padding:5px; border:1px solid #ddd;">
                            <input type="number" name="fast_keys[sl_number][]" value="<?php echo esc_attr($key['sl_number']); ?>" style="width:100px;">
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td><input type="text" name="fast_keys[product_id][]" value=""></td>
                    <td><input type="number" name="fast_keys[sl_number][]" value="1"></td>
                </tr>
            <?php endif; ?>
        </table>
        <?php
    }

public function save_fast_keys_meta($post_id) {

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Save fast_keys_data
    if (isset($_POST['fast_keys'])) {
        $fast_keys_data = [];
        $count = count($_POST['fast_keys']['product_id']);

        for ($i = 0; $i < $count; $i++) {
            $fast_keys_data[] = [
                'product_id' => sanitize_text_field($_POST['fast_keys']['product_id'][$i]),
                'sl_number'  => intval($_POST['fast_keys']['sl_number'][$i]),
            ];
        }

        update_post_meta($post_id, '_fast_keys_data', json_encode($fast_keys_data));
    }

    // Save image
    if (isset($_POST['fastkey_image'])) {
        update_post_meta($post_id, '_fast_key_image', esc_url_raw($_POST['fastkey_image']));
    }

    // Save user id
    if (isset($_POST['fastkey_user_id']) && !get_post_meta($post_id, '_fast_keys_user_id', true)) {
        update_post_meta($post_id, '_fast_keys_user_id', intval($_POST['fastkey_user_id']));
    }

    // Save index manually
    if (isset($_POST['fastkey_index'])) {
        update_post_meta($post_id, '_fast_key_index', intval($_POST['fastkey_index']));
    }


        // Save user id (only if new, prevent tampering)
        if (isset($_POST['fastkey_user_id']) && !get_post_meta($post_id, '_fast_keys_user_id', true)) {
            update_post_meta($post_id, '_fast_keys_user_id', intval($_POST['fastkey_user_id']));
        }

        // Handle reordering indexes
        if (isset($_POST['fastkey_index'])) {
            global $wpdb;

            $new_index = intval($_POST['fastkey_index']);
            $old_index = intval(get_post_meta($post_id, '_fast_key_index', true));

            // Update this post’s index
            update_post_meta($post_id, '_fast_key_index', $new_index);

            if ($new_index !== $old_index) {
                // Get all other fastkeys ordered by index
                $query = new WP_Query([
                    'post_type' => 'fast_keys',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'post__not_in'   => [$post_id],
                    'meta_key'       => '_fast_key_index',
                    'orderby'        => 'meta_value_num',
                    'order'          => 'ASC',
                ]);

                $counter = 1;
                if ($query->have_posts()) {
                    foreach ($query->posts as $post) {
                        if ($counter == $new_index) {
                            $counter++; // Skip spot taken by current post
                        }
                        update_post_meta($post->ID, '_fast_key_index', $counter);
                        $counter++;
                    }
                }
            }
        }
    }
    
public function save_fastkeys_order() {

    if (!current_user_can('edit_posts')) {
        wp_die();
    }

    if (!isset($_POST['order'])) {
        wp_die();
    }

    $order = $_POST['order'];
    $index = 1;

    foreach ($order as $id) {
        update_post_meta($id, '_fast_key_index', $index);
        $index++;
    }

    wp_die();
}
public function remove_view_link($actions, $post) {

    // Correct post type
    if ($post->post_type === 'fast_keys') {

        // Remove "View"
        if (isset($actions['view'])) {
            unset($actions['view']);
        }
    }

    return $actions;
}
// Remove Quick Edit from row actions
public function remove_quick_edit($actions, $post) {
    if ($post->post_type === 'fast_keys') {
        if (isset($actions['inline hide-if-no-js'])) {
            unset($actions['inline hide-if-no-js']);
        }
    }
    return $actions;
}



}

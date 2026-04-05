<?php
/**
 * The admin-safes functionality of the plugin.
 *
 * @package    Safes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class Safes {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('init', [$this, 'register_safes_post_type']);
        add_action('add_meta_boxes', [$this, 'add_safes_meta_box']);
        add_action('save_post', [$this, 'save_safes_meta']);
        add_filter('manage_safes_posts_columns', [$this, 'set_custom_columns']);
        add_action('manage_safes_posts_custom_column', [$this, 'custom_column_content'], 10, 2);
    }

    public function register_safes_post_type() {
        $args = [
            'labels' => [
                'name' => __('Safes', 'pinaka-pos'),
                'singular_name' => __('Safe', 'pinaka-pos'),
                'add_new' => __('Add New Safe', 'pinaka-pos'),
                'add_new_item' => __('Add New Safe', 'pinaka-pos'),
                'edit_item' => __('Edit Safe', 'pinaka-pos'),
                'new_item' => __('New Safe', 'pinaka-pos'),
                'view_item' => __('View Safe', 'pinaka-pos'),
                'search_items' => __('Search Safes', 'pinaka-pos'),
                'not_found' => __('No Safes Found', 'pinaka-pos'),
                'not_found_in_trash' => __('No Safes Found in Trash', 'pinaka-pos'),
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-editor-ul',
            'supports' => ['title'],
            'has_archive' => true,
            'show_in_rest' => true,
        ];
        register_post_type('safes', $args);
    }

    public function set_custom_columns($columns) {
        $columns['safes_data'] = __('Denominations', 'pinaka-pos');
        return $columns;
    }

    public function custom_column_content($column, $post_id) 
    {
        if ($column === 'safes_data') 
        {
            $safes_json = get_post_meta($post_id, '_safes_data', true);
            $total = 0;

            if ($safes_json) {
                $safes_array = json_decode($safes_json, true);
                if ($safes_array) {
                    echo '<ul>';
                    foreach ($safes_array as $entry) {
                        $line_total = floatval($entry['total'] ?? 0);
                        $total += $line_total;
                        echo '<li>' .
                            esc_html($entry['tube_count']) . ' tubes & ' .
                            esc_html($entry['cell_count']) . ' cells x ' .
                            esc_html($entry['denom']) .
                            ' = ' . number_format($line_total, 2) . '</li>';
                    }
                    echo '<li><strong>Total: ' . number_format($total, 2) . '</strong></li>';
                    echo '</ul>';
                } else {
                    echo __('Invalid JSON data', 'pinaka-pos');
                }
            } else {
                echo __('No Denominations', 'pinaka-pos');
            }
        }
    }

    public function add_safes_meta_box() {
        add_meta_box(
            'safes_details',
            __('Safe Denominations', 'pinaka-pos'),
            [$this, 'render_safes_meta_box'],
            'safes',
            'normal',
            'high'
        );
    }

    public function render_safes_meta_box($post) 
    {
        $safes_json = get_post_meta($post->ID, '_safes_data', true);
        $safes = json_decode($safes_json, true) ?: [];
        ?>
        <table class="widefat" style="width:100%; border:1px solid #ddd;">
            <thead>
                <tr>
                    <th>Denomination</th>
                    <th>Tubes</th>
                    <th>Cells</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($safes as $entry): ?>
                <tr>
                    <td><input type="number" name="safes[denomination][]" value="<?php echo esc_attr($entry['denom']); ?>" step="0.01"></td>
                    <td><input type="number" name="safes[tube_count][]" value="<?php echo esc_attr($entry['tube_count']); ?>"></td>
                    <td><input type="number" name="safes[cell_count][]" value="<?php echo esc_attr($entry['cell_count']); ?>"></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <button type="button" class="button" onclick="addSafeRow()">Add Row</button>
        <script>
        function addSafeRow() {
            const row = `<tr>
                <td><input type="number" name="safes[denomination][]" step="0.01"></td>
                <td><input type="number" name="safes[tube_count][]"></td>
                <td><input type="number" name="safes[cell_count][]"></td>
            </tr>`;
            document.querySelector('#safes_details table tbody').insertAdjacentHTML('beforeend', row);
        }
        </script>
        <?php
    }

    public function save_safes_meta($post_id) 
    {
        if (!isset($_POST['safes']) || !is_array($_POST['safes'])) {
            return;
        }

        $denominations = $_POST['safes']['denomination'] ?? [];
        $tubes = $_POST['safes']['tube_count'] ?? [];
        $cells = $_POST['safes']['cell_count'] ?? [];

        $cleaned = [];
        $total = 0;

        for ($i = 0; $i < count($denominations); $i++) {
            $denom = floatval($denominations[$i]);
            $tube_count = intval($tubes[$i]);
            $cell_count = intval($cells[$i]);

            if ($denom > 0 && ($tube_count > 0 || $cell_count > 0)) {
                $line_total = $denom * ($tube_count + $cell_count);
                $cleaned[] = [
                    'denom' => $denom,
                    'tube_count' => $tube_count,
                    'cell_count' => $cell_count,
                    'total' => $line_total
                ];
                $total += $line_total;
            }
        }

        update_post_meta($post_id, '_safes_data', wp_json_encode($cleaned));
        update_post_meta($post_id, '_safes_total', round($total, 2));
    }
}


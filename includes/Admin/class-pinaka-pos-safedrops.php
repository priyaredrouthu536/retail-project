<?php
/**
 * The admin-safedrops functionality of the plugin.
 *
 * @package    SafeDrops
 */

if (!defined('WPINC')) {
    die;
}

class Safe_Drops {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('init', [$this, 'register_safedrops_post_type']);
        add_action('add_meta_boxes', [$this, 'add_safedrops_meta_box']);
        add_action('save_post', [$this, 'save_safedrops_meta']);
        add_filter('manage_safedrops_posts_columns', [$this, 'set_custom_columns']);
        add_action('manage_safedrops_posts_custom_column', [$this, 'custom_column_content'], 10, 2);
        add_filter( 'post_row_actions', [$this, 'remove_quick_edit_and_view_from_shift'], 10, 2 );
        add_filter( 'manage_edit-safedrops_columns', [$this,'move_date_column_to_end'],10,1 );
        // add_filter( 'disable_months_dropdown',[$this, 'disable_months_dropdown'], 10, 2);
        add_action( 'restrict_manage_posts',[$this,'restcrict_posts']);
        add_action( 'pre_get_posts',[$this,'pre_get_posts_list']);
    }
    public function restcrict_posts()
    {
        global $typenow;
        if ( $typenow !== 'safedrops' ) {
            return;
        }

        $selected = $_GET['safedrops_date_filter'] ?? 'all';
        ?>
        <select name="safedrops_date_filter">
            <option value="today" <?php selected( $selected, 'today' ); ?>>Today</option>
            <option value="all" <?php selected( $selected, 'all' ); ?>>All</option>
        </select>
        <?php
    }
    public function pre_get_posts_list($query)
    {
        if (
        ! is_admin() ||
        ! $query->is_main_query()
        ) {
            return;
        }

        global $typenow;

        if ( $typenow !== 'safedrops' ) {
            return;
        }

        $filter = $_GET['safedrops_date_filter'] ?? 'all';

        if ( $filter === 'all' ) {
            return;
        }
        $today = current_time( 'Y-m-d' );
        $query->set( 'date_query', [
            [
                'after'     => $today . ' 00:00:00',
                'before'    => $today . ' 23:59:59',
                'inclusive' => true,
            ],
        ] );
    }
    // public function disable_months_dropdown( $disable, $post_type ) {
    //     return $post_type === 'safedrops';
    // }
    function move_date_column_to_end( $columns ) {
        if ( isset( $columns['date'] ) ) {
            $date = $columns['date'];
            unset( $columns['date'] );
            $columns['date'] = $date;
        }
        return $columns;
    }
    public function remove_quick_edit_and_view_from_shift( $actions, $post ) {
        if ( $post->post_type === 'safedrops' ) {
            unset( $actions['inline hide-if-no-js'] );
            unset( $actions['view'] );
        }
        return $actions;
    }
    public function register_safedrops_post_type() {
        $args = [
            'labels' => [
                'name' => __('Safe Drops' , 'pinaka-pos'),
                'singular_name' => __('Safe Drop', 'pinaka-pos'),
                'add_new' => __('Add New Safe Drop', 'pinaka-pos'),
                'add_new_item' => __('Add New Safe Drop', 'pinaka-pos'),
                'edit_item' => __('Edit Safe Drop', 'pinaka-pos'),
                'new_item' => __('New Safe Drop', 'pinaka-pos'),
                'view_item' => __('View Safe Drop', 'pinaka-pos'),
                'search_items' => __('Search Safe Drop', 'pinaka-pos'),
                'not_found' => __('No Safe Drops Found', 'pinaka-pos'),
                'not_found_in_trash' => __('No Safe Drops Found in Trash', 'pinaka-pos'),
            ],
            'public' => true,
            'show_ui' => true,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-money-alt',
            'supports' => ['title'],
            'has_archive' => true,
            'show_in_rest' => true,
        ];
        register_post_type('safedrops', $args);
    }

    public function set_custom_columns($columns) {
        $columns['safedrops_data'] = __('Denominations', 'pinaka-pos');
        return $columns;
    }

    public function custom_column_content($column, $post_id) {
        if ($column === 'safedrops_data') {

            $data_json = get_post_meta($post_id, '_safedrops_data', true);
            $data = json_decode($data_json, true);
            $total = 0;

            if (!empty($data)) {
                echo '<ul>';
                foreach ($data as $entry) {

                    /** BACKWARD FIX: Support old keys */
                    $denom = floatval($entry['denom'] ?? $entry['denom'] ?? 0);
                    $count = floatval($entry['denom_count'] ?? $entry['denom_count'] ?? 0);

                    $line_total = $denom * $count;
                    $total += $line_total;

                    $currency = '$ ';
                    echo '<li>' . esc_html($count) . ' x ' . $currency . number_format($denom) . ' = ' . $currency . number_format($line_total, 2) . '</li>';
                }
                echo '<li><strong>Total: $ ' . number_format($total, 2) . '</strong></li>';
                echo '</ul>';
            } else {
                echo __('No Denominations', 'pinaka-pos');
            }
        }
    }

    public function add_safedrops_meta_box() {
        add_meta_box(
            'safedrops_details',
            __('Safe Drops Denominations', 'pinaka-pos'),
            [$this, 'render_safedrops_meta_box'],
            'safedrops',
            'normal',
            'high'
        );
    }

    public function render_safedrops_meta_box($post) {

        $data_json = get_post_meta($post->ID, '_safedrops_data', true);
        $data = json_decode($data_json, true) ?: [];
        $total = 0;
        ?>
        <table class="widefat" style="width:100%;border:1px solid #ddd;">
            <thead>
                <tr>
                    <th>Denomination</th>
                    <th>Count</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
        <?php foreach ($data as $entry): ?>
            <?php
                /** BACKWARD FIX: Support old keys */
                $denom = floatval($entry['denom'] ?? $entry['denom'] ?? 0);
                $count = floatval($entry['denom_count'] ?? $entry['denom_count'] ?? 0);
                $line_total = $denom * $count;
                $total += $line_total;
            ?>
                <tr>
                    <td><input type="number" step="0.01" name="safedrops[denomination][]" value="<?php echo esc_attr($denom); ?>"></td>
                    <td><input type="number" name="safedrops[denomination_count][]" value="<?php echo esc_attr($count); ?>"></td>
                    <td><?php echo number_format($line_total, 2); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2">Total</th>
                    <th><?php echo number_format($total, 2); ?></th>
                </tr>
            </tfoot>
        </table>

        <button type="button" class="button" onclick="addSafeRow()">+ Add Row</button>
        <script>
        function addSafeRow() {
            const row = `<tr>
                <td><input type="number" step="0.01" name="safedrops[denomination][]"></td>
                <td><input type="number" name="safedrops[denomination_count][]"></td>
                <td>0.00</td>
            </tr>`;
            document.querySelector('#safedrops_details table tbody').insertAdjacentHTML('beforeend', row);
        }
        </script>
        <?php
    }

    public function save_safedrops_meta($post_id) {

        if (!isset($_POST['safedrops'])) {
            return;
        }

        $denoms = $_POST['safedrops']['denomination'] ?? [];
        $counts = $_POST['safedrops']['denomination_count'] ?? [];

        $save = [];
        $total = 0;

        for ($i = 0; $i < count($denoms); $i++) {
            $d = floatval($denoms[$i]);
            $c = intval($counts[$i]);

            if ($d > 0 && $c > 0) {
                $line_total = $d * $c;
                $save[] = [
                    'denom' => $d,
                    'denom_count' => $c,
                    'total' => $line_total
                ];
                $total += $line_total;
            }
        }

        update_post_meta($post_id, '_safedrops_data', wp_json_encode($save));
        update_post_meta($post_id, '_safedrops_total', number_format($total, 2, '.', ''));
    }
}

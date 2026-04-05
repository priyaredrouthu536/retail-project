<?php 
/**
 * The admin-shifts functionality of the plugin.
 *
 * @link       https://www.pinaka.com/
 * @since      1.0.0
 *
 * @package    Pinaka_Pos
 * @subpackage Pinaka_Pos/admin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class Pinaka_POS_Shifts {

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
        $this->loader = new Pinaka_POS_Loader();
        $this->define_admin_hooks();

        add_action('add_meta_boxes', [$this, 'add_shift_meta_boxes']);
        add_action('save_post', [$this, 'save_shift_meta']);
        add_filter('manage_shifts_posts_columns', [$this, 'set_custom_columns']);
        add_filter('views_edit-shifts', [$this, 'shift_views_with_summary']);
        add_action('admin_head', [$this, 'move_shift_status_to_header']);
        add_action('restrict_manage_posts', [$this, 'shifts_date_tabs_filter']);
        add_action('pre_get_posts', [$this, 'pre_get_posts_list']);
        add_action('manage_shifts_posts_custom_column', [$this, 'custom_column_content'], 10, 2);
        add_filter( 'post_row_actions', [$this, 'remove_quick_edit_and_view_from_shift'], 10, 2 );
        // add_filter( 'disable_months_dropdown',[$this, 'disable_months_dropdown'], 10, 2);
        // add_action( 'restrict_manage_posts',[$this,'restcrict_posts']);
        // add_action( 'pre_get_posts',[$this,'pre_get_posts_list']);
        add_filter( 'manage_edit-shifts_columns', [$this,'move_date_column_to_end'],10,1 );
        add_filter('default_hidden_meta_boxes', [$this, 'default_hide_meta_boxes'],10,2);
        add_action('admin_head-edit.php', [$this, 'hide_default_shift_filter_button']);
    }
    function default_hide_meta_boxes($hidden, $screen)
    {
        if ($screen->post_type === 'shifts') {
            $hidden[] = 'shift_cash_denominations_meta';
            $hidden[] = 'shift_tube_denominations_meta';
            $hidden[] = 'shift_vendor_payments_meta';
        }
        return $hidden;
    }
    function move_date_column_to_end( $columns ) {
        if ( isset( $columns['date'] ) ) {
            $date = $columns['date'];
            unset( $columns['date'] );
            $columns['date'] = $date;
        }
        return $columns;
    }
    public function hide_default_shift_filter_button() {

    global $typenow;

    if ($typenow !== 'shifts') {
        return;
    }

    echo '<style>
        #posts-filter .tablenav.top .alignleft.actions input[name="filter_action"] {
            display: none !important;
        }
    </style>';
}
    public function restcrict_posts()
    {
        global $typenow;
        if ( $typenow !== 'shifts' ) {
            return;
        }

        $selected = $_GET['shift_date_filter'] ?? 'all';
        ?>
        <select name="shift_date_filter">
            <option value="today" <?php selected( $selected, 'today' ); ?>>Today</option>
            <option value="all" <?php selected( $selected, 'all' ); ?>>All</option>
        </select>
        <?php
    }
    public function pre_get_posts_list($query)
{
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    // Ensure this runs only for shifts CPT
    $post_type = $query->get('post_type') ?: ($_GET['post_type'] ?? '');

    if ($post_type !== 'shifts') {
        return;
    }

    $range = $_GET['shift_range'] ?? '';

    if (!$range) {
        return;
    }

    $current_time = current_time('timestamp');

    // TODAY
    if ($range === 'today') {

        $query->set('date_query', [
            [
                'after'     => date('Y-m-d 00:00:00', $current_time),
                'before'    => date('Y-m-d 23:59:59', $current_time),
                'inclusive' => true,
            ],
        ]);
    }

    // THIS WEEK
    elseif ($range === 'week') {

        $start_of_week = strtotime('monday this week', $current_time);
        $end_of_week   = strtotime('sunday this week 23:59:59', $current_time);

        $query->set('date_query', [
            [
                'after'     => date('Y-m-d H:i:s', $start_of_week),
                'before'    => date('Y-m-d H:i:s', $end_of_week),
                'inclusive' => true,
            ],
        ]);
    }

    // THIS MONTH
    elseif ($range === 'month') {

        $start_of_month = date('Y-m-01 00:00:00', $current_time);
        $end_of_month   = date('Y-m-t 23:59:59', $current_time);

        $query->set('date_query', [
            [
                'after'     => $start_of_month,
                'before'    => $end_of_month,
                'inclusive' => true,
            ],
        ]);
    }

    // CUSTOM DATE
    elseif ($range === 'custom' && !empty($_GET['shift_custom_date'])) {

        $custom = sanitize_text_field($_GET['shift_custom_date']);
        $custom_time = strtotime($custom);

        $query->set('date_query', [
            [
                'after'     => date('Y-m-d 00:00:00', $custom_time),
                'before'    => date('Y-m-d 23:59:59', $custom_time),
                'inclusive' => true,
            ],
        ]);
    }
}
    // public function pre_get_posts_list($query)
    // {
    //     if (
    //     ! is_admin() ||
    //     ! $query->is_main_query()
    //     ) {
    //         return;
    //     }

    //     global $typenow;

    //     if ( $typenow !== 'shifts' ) {
    //         return;
    //     }

    //     $filter = $_GET['shift_date_filter'] ?? 'all';

    //     if ( $filter === 'all' ) {
    //         return;
    //     }
    //     $today = current_time( 'Y-m-d' );
    //     $query->set( 'date_query', [
    //         [
    //             'after'     => $today . ' 00:00:00',
    //             'before'    => $today . ' 23:59:59',
    //             'inclusive' => true,
    //         ],
    //     ] );
    // }
    public function remove_quick_edit_and_view_from_shift( $actions, $post ) {
        if ( $post->post_type === 'shifts' ) {
            // Remove Quick Edit
            unset( $actions['inline hide-if-no-js'] );
            // Remove View
            unset( $actions['view'] );
        }
        return $actions;
    }
   
public function move_shift_status_to_header() {

    global $post, $typenow;

    if ($typenow !== 'shifts' || !isset($post) || !is_object($post)) {
        return;
    }

    $status = get_post_meta($post->ID, '_shift_status', true);
    $status = $status ? $status : 'open';
    ?>

    <style>
        #shift_details .postbox-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .shift-status-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .shift-status-wrapper select {
            min-width: 120px;
        }
    </style>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const header = document.querySelector('#shift_details .postbox-header');
            if (!header) return;

            const wrapper = document.createElement('div');
            wrapper.className = 'shift-status-wrapper';

            wrapper.innerHTML = `
                <label><strong>Status:</strong></label>
                <select name="shift_status">
                    <option value="open" <?php selected($status, 'open'); ?>>Open</option>
                    <option value="closed" <?php selected($status, 'closed'); ?>>Closed</option>
                </select>
            `;

            header.appendChild(wrapper);
        });
    </script>

    <?php
}
public function shift_views_with_summary( $views ) {

    global $typenow;

    if ( $typenow !== 'shifts' ) {
        return $views;
    }

    $range = $_GET['shift_range'] ?? '';

    $args = [
        'post_type'      => 'shifts',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ];

    // ✅ Filter by TODAY only
   $current_time = current_time('timestamp');

if ($range === 'today') {

    $args['date_query'] = [
        [
            'after'     => date('Y-m-d 00:00:00', $current_time),
            'before'    => date('Y-m-d 23:59:59', $current_time),
            'inclusive' => true,
        ],
    ];
}

elseif ($range === 'week') {

    $start_of_week = strtotime('monday this week', $current_time);
    $end_of_week   = strtotime('sunday this week 23:59:59', $current_time);

    $args['date_query'] = [
        [
            'after'     => date('Y-m-d H:i:s', $start_of_week),
            'before'    => date('Y-m-d H:i:s', $end_of_week),
            'inclusive' => true,
        ],
    ];
}

elseif ($range === 'month') {

    $start_of_month = date('Y-m-01 00:00:00', $current_time);
    $end_of_month   = date('Y-m-t 23:59:59', $current_time);

    $args['date_query'] = [
        [
            'after'     => $start_of_month,
            'before'    => $end_of_month,
            'inclusive' => true,
        ],
    ];
}

elseif ($range === 'custom' && !empty($_GET['shift_custom_date'])) {

    $custom_time = strtotime($_GET['shift_custom_date']);

    $args['date_query'] = [
        [
            'after'     => date('Y-m-d 00:00:00', $custom_time),
            'before'    => date('Y-m-d 23:59:59', $custom_time),
            'inclusive' => true,
        ],
    ];
}

    $shift_ids = get_posts($args);

    $total_opening = 0;
    $total_closing = 0;
    $total_sales   = 0;
    $total_safe    = 0;
    $total_shifts  = count($shift_ids);
    $total_orders  = 0; // change later if you have orders CPT

    foreach ($shift_ids as $shift_id) {

        $total_sales   += floatval(get_post_meta($shift_id, '_shift_total_sales', true));
        $total_opening += floatval(get_post_meta($shift_id, '_shift_opening_balance', true));
        $total_closing += floatval(get_post_meta($shift_id, '_shift_closing_balance', true));

        $safe_drops = $this->get_shift_safe_drops($shift_id);
        $total_safe += floatval($safe_drops['total_safe_drop'] ?? 0);
    }
// ---------------------------------
// COUNT COMPLETED ORDERS (WooCommerce)
// ---------------------------------

$order_args = [
    'post_type'      => 'shop_order',
    'post_status'    => ['wc-completed'],
    'posts_per_page' => -1,
    'fields'         => 'ids',
];


// ---------------------------------
// COUNT VOID ORDERS (Cancelled Orders)
// ---------------------------------

$void_args = [
    'post_type'      => 'shop_order',
    'post_status'    => ['wc-cancelled'],
    'posts_per_page' => -1,
    'fields'         => 'ids',
];
// ✅ Apply SAME date filter to both (VERY IMPORTANT)
if (!empty($args['date_query'])) {
    $order_args['date_query'] = $args['date_query'];
    $void_args['date_query']  = $args['date_query'];
}

// ✅ NOW count (after applying filter)
$total_orders = count( get_posts($order_args) );
$total_voids  = count( get_posts($void_args) );



$total_voids = count( get_posts($void_args) );

    // 🔥 Replace entire row
    $views = [];

    $views['shift_summary'] = sprintf(
    '<span style="font-weight:600; font-size:14px;">
    Total Shifts: %s &nbsp; | &nbsp;
    Total Sales: $%s &nbsp; | &nbsp;
    Total Orders: %s &nbsp; | &nbsp;
    Cancelled orders: %s &nbsp; | &nbsp;
    Opening bal: $%s &nbsp; | &nbsp;
    Closing bal: $%s &nbsp; | &nbsp;
    Safe Drop: $%s
    </span>',
    number_format($total_shifts),
    number_format($total_sales, 2),
    number_format($total_orders),
    number_format($total_voids),
    number_format($total_opening, 2),
    number_format($total_closing, 2),
    number_format($total_safe, 2)
);

    return $views;
}
public function shifts_date_tabs_filter() {


    global $typenow;

    if ( $typenow !== 'shifts' ) {
        return;
    }

    $selected_range = $_GET['shift_range'] ?? '';
    $selected_date  = $_GET['shift_custom_date'] ?? '';

    ?>

    <div style="margin-bottom:10px; display:flex; gap:10px; align-items:center;">

        <!-- Tabs -->
        <a href="<?php echo admin_url('edit.php?post_type=shifts&shift_range=today'); ?>"
           class="button <?php echo ($selected_range=='today')?'button-primary':''; ?>">
           Today
        </a>

        <a href="<?php echo admin_url('edit.php?post_type=shifts&shift_range=week'); ?>"
           class="button <?php echo ($selected_range=='week')?'button-primary':''; ?>">
           This Week
        </a>

        <a href="<?php echo admin_url('edit.php?post_type=shifts&shift_range=month'); ?>"
           class="button <?php echo ($selected_range=='month')?'button-primary':''; ?>">
           This Month
        </a>

        <!-- Custom Date Picker -->
        <form method="get" style="display:flex; gap:5px; align-items:center;">
            <input type="hidden" name="post_type" value="shifts">
            <input type="hidden" name="shift_range" value="custom">

            <input type="date" name="shift_custom_date"
                   value="<?php echo esc_attr($selected_date); ?>">

            <button type="submit" class="button">Filter</button>
        </form>

    </div>

    <?php
}
    // public function disable_months_dropdown( $disable, $post_type ) {
    //     return $post_type === 'shifts';
    // }
    /**
     * Register the vendor custom post type.
     *
     * @since    1.0.0
     * @access   private
     */
    public function register_shifts_post_type() {
        $labels = array(
            'name'                  => __('Shifts', 'pinaka-pos'),
            'singular_name'         => __('Shift', 'pinaka-pos'),
            'add_new'               => __('Add New Shift', 'pinaka-pos'),
            'add_new_item'          => __('Add New Shift', 'pinaka-pos'),
            'edit_item'             => __('Edit Shift', 'pinaka-pos'),
            'new_item'              => __('New Shift', 'pinaka-pos'),
            'view_item'             => __('View Shift', 'pinaka-pos'),
            'search_items'          => __('Search Shifts', 'pinaka-pos'),
            'not_found'             => __('No shifts found', 'pinaka-pos'),
            'not_found_in_trash'    => __('No shifts found in Trash', 'pinaka-pos'),
            'menu_name'             => __('Shifts', 'pinaka-pos'),
        );

        $args = array(
            'labels'        => $labels,
            'description'   => __('Shifts custom post type', 'pinaka-pos'),
            'public'        => true,
            'publicly_queryable' => true,
            'show_ui'       => true,
            'show_in_menu'  => false, // Hide from top menu, shown under Pinaka POS
            'query_var'     => true,
            'rewrite'       => array('slug' => 'shifts'),
            'capability_type' => 'post',
            'has_archive'   => true,
            'hierarchical'  => false,
            'menu_position' => null,
            'supports'      => array('title'),
        );

        register_post_type('shifts', $args);
    }

    /**
     * Define the admin Table columns.
     */

    public function set_custom_columns($columns) {
        $columns['shift_start_time'] = __('Start Time', 'pinaka-pos');
        $columns['shift_total_sales'] = __('Total Sale Amount', 'pinaka-pos');
        $columns['shift_safe_drop_total'] = __('Safe Drop Total', 'pinaka-pos');
        // $columns['shift_assigned_staff'] = __('Assigned Staff', 'pinaka-pos');
        $columns['shift_status'] = __('Status', 'pinaka-pos');
        $columns['shift_opening_balance'] = __('Opening Bal', 'pinaka-pos');
        $columns['shift_closing_balance'] = __('Closing Bal ', 'pinaka-pos');
        $columns['shift_over_short'] = __('Over/Short ', 'pinaka-pos');
        $columns['shift_end_time'] = __('Closing Time', 'pinaka-pos');
        return $columns;
    }

    /**
     * Render the column values.
     */

    public function custom_column_content($column, $post_id) {
        switch ($column) {
            // case 'shift_assigned_staff':
            //     $shift_user_id = get_post_meta($post_id, '_shift_assigned_staff', true);
            //     $shift_user = get_userdata($shift_user_id);
            //     echo $shift_user ? esc_html($shift_user->display_name) : __('N/A', 'pinaka-pos');
            //     break;
            case 'shift_status':
                $sstatus = get_post_meta($post_id, '_shift_status', true);
                $sstatus = strtolower( trim( $sstatus ) );
                if($sstatus == 'update'){
                    $sstatus = 'open';
                }
                echo esc_html($sstatus);
            break;
            case 'shift_start_time':
                echo esc_html(get_post_meta($post_id, '_shift_start_time', true));
                break;
            case 'shift_total_sales':
        $value = floatval(get_post_meta($post_id, '_shift_total_sales', true));
        echo '$' . number_format($value, 2);
        break;

    case 'shift_safe_drop_total':
        $safe_drops = $this->get_shift_safe_drops($post_id);
        $safe_drop_total = floatval($safe_drops['total_safe_drop'] ?? 0);
        echo '$' . number_format($safe_drop_total, 2);
        break;

    case 'shift_opening_balance':
        $value = floatval(get_post_meta($post_id, '_shift_opening_balance', true));
        echo '$' . number_format($value, 2);
        break;

    case 'shift_closing_balance':
        $value = floatval(get_post_meta($post_id, '_shift_closing_balance', true));
        echo '$' . number_format($value, 2);
        break;

    case 'shift_over_short':
        $value = floatval(get_post_meta($post_id, '_shift_over_short', true));

        if ($value < 0) {
            echo '<span style="color:red;">-$' . number_format(abs($value), 2) . '</span>';
        } else {
            echo '<span style="color:green;">$' . number_format($value, 2) . '</span>';
        }
        break;

    case 'shift_end_time':
        echo esc_html(get_post_meta($post_id, '_shift_end_time', true));
        break;
    }
    }

    /**
     * Define the admin hooks.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        // ✅ Ensure post type is registered before menu is built
        $this->loader->add_action('init', $this, 'register_shifts_post_type');
        $this->loader->add_action('admin_menu', $this, 'add_shifts_submenu_page');
    }

    /**
     * Add the shifts submenu page.
     *
     * @since    1.0.0
     * @access   private
     */
    public function add_shifts_submenu_page() {
        add_submenu_page(
            'pinaka-pos-dashboard',
            __('Manage Shifts', 'pinaka-pos'),
            __('Manage Shifts', 'pinaka-pos'),
            'manage_options',
            'pinaka-pos-shiftss',
            [$this, 'shiftsRender']
        );
    }

    /**
     * Render the shifts submenu page.
     *
     * @since    1.0.0
     * @access   public
     */
    public function shiftsRender() {
        wp_safe_redirect(admin_url('edit.php?post_type=shifts'));
        exit;
    }

    /**
     * Add meta boxes to Shift post type
     */
    public function add_shift_meta_boxes() {
        add_meta_box(
            'shift_details',
            'Shift Details',
            [$this, 'render_shift_details_meta_box'],
            'shifts',
            'normal',
            'high'
        );
        add_meta_box(
            'shift_cash_denominations_meta',
            __('Cash Denominations', 'pinaka-pos'),
            [$this, 'render_shift_cash_denominations_meta_box'],
            'shifts',
            'normal',
            'default'
        );
        add_meta_box(
            'shift_tube_denominations_meta',
            __('Tube Denominations', 'pinaka-pos'),
            [$this, 'render_shift_tube_denominations_meta_box'],
            'shifts',
            'normal',
            'default'
        );
        add_meta_box(
            'shift_vendor_payments_meta',
            __('Vendor Payments', 'pinaka-pos'),
            [$this, 'render_shift_vendor_payments_meta_box'],
            'shifts',
            'side',
            'default'
        );
         add_meta_box(
            'shift_details',
            __('Shift Details', 'pinaka-pos'),
            [$this, 'render_shift_details_meta_box'],
            'shifts',
            'normal',
            'high'
        );
    }
    /**
     * Render Shift Details Meta Box
     */
    public function render_shift_details_meta_box($post) {
        $start_time = get_post_meta($post->ID, '_shift_start_time', true);
        $end_time = get_post_meta($post->ID, '_shift_end_time', true);
        $assigned_staff = get_post_meta($post->ID, '_shift_assigned_staff', true);

        $staff_name = get_user_meta($assigned_staff, 'first_name', true).' '.get_user_meta($assigned_staff, 'last_name', true);

        $total_sales = get_post_meta($post->ID, '_shift_total_sales', true);
        $safe_float = function($v) {
			return is_numeric($v) ? floatval($v) : 0.0;
		};
        $safe_drops = $this->get_shift_safe_drops( $post->ID );
		$safe_drop_total = $safe_float( $safe_drops['total_safe_drop'] ?? 0 );
        $safe_drop_total = $safe_drop_total;
        $opening_balance = get_post_meta($post->ID, '_shift_opening_balance', true);
        $closing_balance = get_post_meta($post->ID, '_shift_closing_balance', true);
        $status = get_post_meta($post->ID, '_shift_status', true);
        $status = strtolower( trim( $status ) );
        $drawer_opening_denominations = get_post_meta($post->ID, '_shift_drawer_denominations', true);
        $drawer = json_decode($drawer_opening_denominations, true) ?: [];
        $tube_opening_denominations = get_post_meta($post->ID, '_shift_tube_denominations', true);

        $tubes = json_decode($tube_opening_denominations, true) ?: [];
        $vendor_payments = $this->get_shift_vendor_payouts_total($post->ID);
        $total_drawe_total = get_post_meta($post->ID,'_shift_drawer_total',true);
        $total_tube_total = get_post_meta($post->ID,'_shift_tube_total',true);
        ?>
        <style>
        .shift-meta-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .shift-meta-grid .full-width {
            grid-column: 1 / -1;
        }

        .shift-meta-grid label {
            font-weight: 600;
        }

        .shift-meta-grid input,
        .shift-meta-grid select {
            width: 100%;
        }

        .shift-meta-grid table {
            margin-top: 10px;
        }
        .drawer-table-scroll {
            max-height: 300px;   /* adjust height */
            overflow-y: auto;
            border: 1px solid #ddd;
        }

        .drawer-table thead th {
            position: sticky;
            top: 0;
            background: #f6f7f7;
            z-index: 2;
        }
         .currency-input {
            position: relative;
            width: 120px;
        }
        
        .currency-input span {
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            color: #555;
            font-weight: 500;
        }
        
        .currency-input input {
            padding-left: 18px !important;
            width: 100%;
        }
        .currency-dollar {
    padding-left: 18px !important;
    background: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='20' height='20'><text x='2' y='14' font-size='14'>$</text></svg>") no-repeat 5px center;
}

        </style>
        <div class="shift-meta-grid">
               <p>
                <label for="shift_start_time">Start Time:</label><br>
                <input type="datetime-local" id="shift_start_time" name="shift_start_time" value="<?php echo esc_attr($start_time); ?>" style="width:100%;">
            </p>
            <p>
                <label for="shift_end_time">End Time:</label><br>
                <input type="datetime-local" id="shift_end_time" name="shift_end_time" value="<?php echo esc_attr($end_time); ?>" style="width:100%;">
            </p>
            <p>
                <label for="shift_assigned_staff">Assigned Staff:</label><br>
                <input type="text" value="<?php echo esc_attr($staff_name); ?>" style="width:100%;">
                <input type="hidden" id="shift_assigned_staff" name="shift_assigned_staff" value="<?php echo esc_attr($assigned_staff); ?>" style="width:100%;">
            </p>
           
            
            <p>
                    <label for="shift_opening_balance">Opening Balance:</label><br>
                    <input type="text"
                        id="shift_opening_balance"
                        name="shift_opening_balance"
                        value="<?php echo esc_attr(number_format((float)$opening_balance, 2)); ?>"
                        class="currency-dollar"
                        style="width:100%;"
                        oninput="this.value=this.value.replace(/[^0-9.]/g,'');">
                </p>
            <p>
                    <label for="shift_closing_balance">Closing Balance:</label><br>
                    <input type="text"
                        id="shift_closing_balance"
                        name="shift_closing_balance"
                        value="<?php echo esc_attr(number_format((float)$closing_balance, 2)); ?>"
                        class="currency-dollar"
                        style="width:100%;"
                        oninput="this.value=this.value.replace(/[^0-9.]/g,'');">
                </p>
                
            <p>
                <label for="total_tube_total">Safe:</label><br>
                <input type="text" id="total_tube_total" name="total_tube_total"
                    value="<?php echo esc_attr(number_format((float)$total_tube_total, 2)); ?>"
                    class="currency-dollar"
                    style="width:100%;">
            </p>
            <p>
                <label for="total_drawe_total">Till Amount:</label><br>
                <input type="text"
                    id="total_drawe_total"
                    name="total_drawe_total"
                    value="<?php echo esc_attr(number_format((float)$total_drawe_total, 2)); ?>"
                    class="currency-dollar"
                    style="width:100%;"
                    oninput="this.value=this.value.replace(/[^0-9.]/g,'');">
            </p>
        </div>    
        <?php
    }
    public function render_shift_cash_denominations_meta_box($post)
{
    $drawer_opening_denominations = get_post_meta($post->ID, '_shift_drawer_denominations', true);
    $drawer = json_decode($drawer_opening_denominations, true) ?: [];
    ?>
    <p>
        <label for="safes_denomination"><b>Cash Denominations:</b></label><br>
    </p>
    <div class="drawer-table-scroll">
        <table class="widefat" style="width:100%; border:1px solid #ddd;">
            <thead>
                <tr>
                    <th><?= __('Denomination', 'pinaka-pos') ?></th>
                    <th><?= __('Count', 'pinaka-pos') ?></th>
                    <th><?= __('Total', 'pinaka-pos') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php 
                $sum_of_all_denomination = 0; // Important: reset to 0

                foreach ($drawer as $entry): 
                    if ($entry['denomination'] > 0):

                        $total = $entry['denomination'] * $entry['denom_count'];
                        $sum_of_all_denomination += $total;
            ?>
                <tr>
            <td>
                <div class="currency-input">
                    <span>$</span>
                    <input type="number"
                        name="safes[denomination][]"
                        value="<?php echo esc_attr($entry['denomination']); ?>"
                        step="0.01">
                </div>
            </td>


                    <td>
                        <input type="number" 
                            name="safes[tube_count][]" 
                            value="<?php echo esc_attr($entry['denom_count']); ?>">
                    </td>

                    <td>
                        <?php echo '$' . number_format($total, 2); ?>
                    </td>
                </tr>

            <?php 
                    endif;
                endforeach; 
            ?>
            </tbody>
            <tfoot>
                <tr>
                    <td></td>
                    <td></td>
                    <td>
                        <?php echo '$' . number_format($sum_of_all_denomination, 2); ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php
}

    public function render_shift_tube_denominations_meta_box($post)
{
    $tube_opening_denominations = get_post_meta($post->ID, '_shift_tube_denominations', true);
    $tubes = json_decode($tube_opening_denominations, true) ?: [];

    if (!empty($tubes)) :
?>
    <p>
        <label><b>Opening Tube Denomination:</b></label><br>
    </p>

    <div class="drawer-table-scroll">
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php echo __('Tube Denomination', 'pinaka-pos'); ?></th>
                    <th><?php echo __('Tubes', 'pinaka-pos'); ?></th>
                    <th><?php echo __('Cells', 'pinaka-pos'); ?></th>
                    <th><?php echo __('Total', 'pinaka-pos'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sum_of_all_denomination = 0;

                foreach ($tubes as $entry) :

                    $denomination = isset($entry['denomination']) ? (float) $entry['denomination'] : 0;
                    $tube_count   = isset($entry['tube_count']) ? (int) $entry['tube_count'] : 0;
                    $cell_count   = isset($entry['cell_count']) ? (int) $entry['cell_count'] : 0;

                    if ($denomination > 0) :

                        $total = $denomination * $tube_count * $cell_count;
                        $sum_of_all_denomination += $total;
                ?>
                        <tr>
                            <td>
                                <div class="currency-input">
                                    <span>$</span>
                                    <input type="number" 
                                           name="safes[denomination][]" 
                                           value="<?php echo esc_attr($denomination); ?>" 
                                           step="0.01">
                                </div>
                            </td>

                            <td>
                                <input type="number" 
                                       name="safes[tube_count][]" 
                                       value="<?php echo esc_attr($tube_count); ?>" />
                            </td>

                            <td>
                                <input type="number" 
                                       name="safes[cell_count][]" 
                                       value="<?php echo esc_attr($cell_count); ?>" />
                            </td>

                            <td>
                                <?php echo '$' . number_format($total, 2); ?>
                            </td>
                        </tr>
                <?php 
                    endif;
                endforeach; 
                ?>
            </tbody>

            <tfoot>
                <tr>
                    <td></td>
                    <td></td>
                    <td><strong>Total:</strong></td>
                    <td><strong><?php echo '$' . number_format($sum_of_all_denomination, 2); ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

<?php
    endif;
}

    public function render_shift_vendor_payments_meta_box($post)
    {
        $vendor_payments = $this->get_shift_vendor_payouts_total($post->ID);
        if(count($vendor_payments) > 0):
        ?>
        <p>
            <label for="shift_opening_balance"><b>Vendor Payments:</b></label><br>
        </p>
        <div class="drawer-table-scroll">
            <table class="widefat" style="width:100%; border:1px solid #ddd;">
                <thead>
                    <tr>
                        <th><?= __('Vendor Name', 'pinaka-pos') ?></th>
                        <th><?= __('Amount', 'pinaka-pos') ?></th>

                    </tr>
                </thead>
                <tbody>
                <?php 
                    $sum_of_all_vendorpayments= 0;
                    foreach ($vendor_payments['vendor_payments'] as $vendor_payment): ?>
                    <tr>
                        <td><?=$vendor_payment['vendor_name'];?></td>
                        <td><?=number_format($vendor_payment['amount'],2);?></td>
                        <?php 
                        $total = $vendor_payment['amount'];
                        number_format($total, 2); 
                        ?>

                    </tr>
                <?php 
                    $sum_of_all_vendorpayments += $total;
                    endforeach; 
                ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td></td>
                        <td><?php echo number_format($sum_of_all_vendorpayments, 2);?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php
            endif;
    }
    /**
     * Save Shift Meta Fields
     */
    public function save_shift_meta($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        if (isset($_POST['shift_start_time'])) {
            update_post_meta($post_id, '_shift_start_time', sanitize_text_field($_POST['shift_start_time']));
        }

        if (isset($_POST['shift_end_time'])) {
            update_post_meta($post_id, '_shift_end_time', sanitize_text_field($_POST['shift_end_time']));
        }

        if (isset($_POST['shift_assigned_staff'])) {
            update_post_meta($post_id, '_shift_assigned_staff', sanitize_text_field($_POST['shift_assigned_staff']));
        }

        if (isset($_POST['shift_opening_balance'])) {
            update_post_meta($post_id, '_shift_opening_balance', floatval($_POST['shift_opening_balance']));
        }

        if (isset($_POST['shift_closing_balance'])) {
            update_post_meta($post_id, '_shift_closing_balance', floatval($_POST['shift_closing_balance']));
        }

        if (isset($_POST['shift_status'])) {
            update_post_meta($post_id, '_shift_status', sanitize_text_field($_POST['shift_status']));
        }
    }
    

    /**
	 * Get the total vendor payouts for a given shift.
	 *
	 * @param int $shift_id The ID of the shift.
	 * @return float The total vendor payouts amount.
	 */
	public function get_shift_vendor_payouts_total( $shift_id ) {
		$payouts = get_posts([
			'post_type'   => 'vendor_payments',
			'post_status' => 'publish',
			'numberposts' => -1,
			'meta_query'  => [
				[
					'key'   => '_vendor_payment_shift_id',
					'value' => $shift_id,
				]
			],
		]);

		$total_vendor_payouts = 0;
		$results = [];
		foreach ( $payouts as $payout ) {
			$total_vendor_payouts += floatval(get_post_meta($payout->ID, '_vendor_payment_amount', true));
            $vendor_info = get_post_meta($payout->ID, '_vendor_id', true);
			$results['vendor_payments'][] = [
				'id'        => $payout->ID,
				'amount'    => floatval(get_post_meta($payout->ID, '_vendor_payment_amount', true)),
				'note'      => get_post_meta($payout->ID, '_vendor_payment_note', true),
				'payment_method' => get_post_meta($payout->ID, '_vendor_payment_method', true),
				'time'      => get_post($payout->ID, 'post_date', true)->post_date,
				'vendor_name' => get_post($vendor_info, '_vendor_id', true)->post_title,
				'service_type'	 => get_post_meta($payout->ID, '_vendor_payment_service_type', true),
				'vendor_id' => get_post_meta($payout->ID, '_vendor_id', true),
			];
			$results['total_vendor_payments'] = $total_vendor_payouts;
		}
		return $results;
	}
    public function get_shift_safe_drops( $shift_id ) {
		$safe_drops = get_posts([
			'post_type'   => 'safedrops',
			'post_status' => 'publish',
			'numberposts' => -1,
			'meta_query'  => [
				[
					'key'   => '_safedrops_shift_id',
					'value' => $shift_id,
				],
			],
		]);

		$results = [];
		$total_safe_drop = 0;
		foreach ( $safe_drops as $drop ) {
			$time = get_post($drop->ID, 'post_date', true);
			$total_safe_drop += floatval(get_post_meta($drop->ID, '_safedrops_total', true));
			$results['safe_drops'][] = [
				'id'        => $drop->ID,
				'total'     => floatval(get_post_meta($drop->ID, '_safedrops_total', true)),
				'denominations' => json_decode(get_post_meta($drop->ID, '_safedrops_data', true), true),
				'note'      => get_post_meta($drop->ID, '_safe_drop_note', true),
				'time'      => $time->post_date,
			];
			$results['total_safe_drop'] = $total_safe_drop ?? 0;
		}
		
		return $results;
	}
}
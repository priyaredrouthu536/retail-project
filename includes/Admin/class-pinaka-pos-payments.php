<?php
/**
 * The admin-payments functionality of the plugin.
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

class Pinaka_POS_Payments {
    private $plugin_name;
    private $version;
    private $loader;
    private $sorted_payments = [];
    private $sorted_notes = [];
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->loader = new Pinaka_POS_Loader();
        $this->define_admin_hooks();

        add_action('add_meta_boxes', [$this, 'add_payment_meta_boxes']);
        add_action('save_post', [$this, 'save_payment_meta']);
        add_filter('manage_payments_posts_columns', [$this, 'set_custom_columns']);
        add_action('manage_payments_posts_custom_column', [$this, 'custom_column_content'], 10, 2);
        add_action('admin_head', [$this, 'payments_admin_styles']);
        add_action('load-edit.php', [$this, 'preload_payments']);
        add_action('pre_get_posts', [$this, 'research_pre_get_posts']);
        add_filter('post_row_actions', [$this, 'remove_view_link'], 10, 2);
        add_action('admin_init', [$this, 'remove_payment_editor_support']);
        add_filter('post_row_actions', [$this, 'remove_quick_edit'], 10, 2);
    }
    
    public function remove_view_link($actions, $post) {

    // Only for payments post type (change if needed)
    if ($post->post_type === 'payments') {
        if (isset($actions['view'])) {
            unset($actions['view']);
        }
    }

    return $actions;
}
// Remove Quick Edit from row actions
public function remove_quick_edit($actions, $post) {
    if ($post->post_type === 'payments') {
        if (isset($actions['inline hide-if-no-js'])) {
            unset($actions['inline hide-if-no-js']);
        }
    }
    return $actions;
}


    public function remove_payment_editor_support() {
    remove_post_type_support('payments', 'editor');

    }
    public function research_pre_get_posts($query)
    {
        if (!is_admin() || !$query->is_main_query()) return;
        if (!$query->is_search()) return;
        if ($query->get('post_type') !== 'payments') return;

        $search = trim($query->get('s'));
        if ($search === '') return;

        // Find matching users
        $users = get_users([
            'search'         => '*' . esc_attr($search) . '*',
            'search_columns' => ['display_name'],
            'fields'         => 'ID',
        ]);

        if (empty($users)) return;

        $query->set('s', '');

        $meta_query = $query->get('meta_query') ?: ['relation' => 'OR'];

        $meta_query[] = [
            'key'     => '_payment_user_id',
            'value'   => array_map('strval', $users),
            'compare' => 'IN',
        ];

        $query->set('meta_query', $meta_query);
    }
    public function preload_payments() {
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'payments') {
            return;
        }
        $args = array(
            'post_type'      => 'payments',
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
        );

        $payments = get_posts($args);

        foreach ($payments as $p) {
            $order_id = get_post_meta($p->ID, '_payment_order_id', true);
            if (!$order_id) continue;

            $amount = floatval(get_post_meta($p->ID, '_payment_amount', true));
            $is_void = get_post_meta($p->ID, '_payment_void', true);

            $this->sorted_payments[$order_id][] = [
                'id'     => $p->ID,
                'amount' => ($is_void && $is_void !== 'false') ? 0 : $amount
            ];
            $notes_pay = get_post_meta($p->ID, '_payment_notes', true);
            $sunmeeiTransactionId = $p->ID;
            if (!empty($notes_pay))
            {
                $decoded = json_decode($notes_pay, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($decoded['sunmiTransactionId'])) 
                {
                    $sunmeeiTransactionId = sanitize_text_field($decoded['sunmiTransactionId']);
                }
            }
            if (!empty($sunmeeiTransactionId)) {
                $this->sorted_notes[$order_id][$p->ID] = $sunmeeiTransactionId;
            }
        }
    }
    public function register_payments_post_type() {
        $labels = array(
            'name'                  => __('Payments', 'pinaka-pos'),
            'singular_name'         => __('Payment', 'pinaka-pos'),
            'add_new'               => __('Add New Payment', 'pinaka-pos'),
            'add_new_item'          => __('Add New Payment', 'pinaka-pos'),
            'edit_item'             => __('Edit Payment', 'pinaka-pos'),
            'new_item'              => __('New Payment', 'pinaka-pos'),
            'view_item'             => __('View Payment', 'pinaka-pos'),
            'search_items'          => __('Search Payments', 'pinaka-pos'),
            'not_found'             => __('No payments found', 'pinaka-pos'),
            'not_found_in_trash'    => __('No payments found in Trash', 'pinaka-pos'),
            'menu_name'             => __('Payments', 'pinaka-pos'),
        );

        $args = array(
            'labels'        => $labels,
            'description'   => __('Payments custom post type', 'pinaka-pos'),
            'public'        => true,
            'publicly_queryable' => true,
            'show_ui'       => true,
            'show_in_menu'  => false,
            'query_var'     => true,
            'rewrite'       => array('slug' => 'payments'),
            'capability_type' => 'post',
            'has_archive'   => true,
            'hierarchical'  => false,
            'menu_position' => null,
            'supports'      => array('title', 'editor'),
        );

        register_post_type('payments', $args);
    }

    public function set_custom_columns($columns) {
        // Keep default checkbox & title (if present), then insert custom columns.
        $new = array();

         if (isset($columns['cb'])) {
           $new['cb'] = $columns['cb'];
    }

        if (isset($columns['title'])) {
            $new['title'] = $columns['title'];
        }

        // Custom columns: Order ID, Order Total, Payment Amount, Change, Method, Accepted By
        $new['payment_order_id'] = __('Order Id', 'pinaka-pos');
        $new['order_total']      = __('Order Total', 'pinaka-pos');
        $new['payment_amount']   = __('Tender Amount', 'pinaka-pos');
        $new['balance_amount']   = __('Balance Amount', 'pinaka-pos');
        $new['change_amount']    = __('Change Amount', 'pinaka-pos');
        $new['payment_method']   = __('Transaction Id', 'pinaka-pos');
        $new['payment_user_id']  = __('Payment Accepted By', 'pinaka-pos');

        // Preserve existing date column if present
        if (isset($columns['date'])) {
            $new['date'] = $columns['date'];
        }

        return $new;
    }

    public function custom_column_content($column, $post_id) {

        switch ($column) {

            /* ----------------------------------------------
            * PAYMENT ORDER ID
            * ---------------------------------------------- */
            case 'payment_order_id':
                $order_id = get_post_meta($post_id, '_payment_order_id', true);
                echo $order_id ? esc_html($order_id) : __('N/A', 'pinaka-pos');
                break;


            /* ----------------------------------------------
            * ORDER TOTAL (ALWAYS ORIGINAL WC TOTAL)
            * ---------------------------------------------- */
            case 'order_total':

                $order_id = get_post_meta($post_id, '_payment_order_id', true);
                if (! $order_id) { echo __('N/A', 'pinaka-pos'); break; }

                $wc_order = wc_get_order($order_id);
                if (! $wc_order) { echo __('N/A', 'pinaka-pos'); break; }

                $order_total = floatval($wc_order->get_total());

                echo function_exists('wc_price')
                    ? wp_kses_post( wc_price($order_total) )
                    : esc_html( number_format_i18n($order_total, 2) );

                break;


            /* ----------------------------------------------
            * PAYMENT AMOUNT (VOID = 0)
            * ---------------------------------------------- */
            case 'payment_amount':

                $amount   = floatval(get_post_meta($post_id, '_payment_amount', true));
                $is_void  = get_post_meta($post_id, '_payment_void', true);

                if ($is_void && $is_void !== 'false') {
                    $amount = 0;
                }

                echo function_exists('wc_price')
                    ? wp_kses_post( wc_price($amount) )
                    : esc_html(number_format_i18n($amount, 2));

                break;


            /* ----------------------------------------------
            * BALANCE AMOUNT (RUNNING BALANCE)
            * Void payments are ignored
            * ---------------------------------------------- */
            case 'balance_amount':

                $order_id = get_post_meta($post_id, '_payment_order_id', true);
                if (! $order_id) { echo '-'; break; }

                $wc_order = wc_get_order($order_id);
                if (! $wc_order) { echo '-'; break; }

                $order_total = floatval($wc_order->get_total());
                $new_amt = $wc_order->get_meta('new_netpay_amt');

		        $order_total = ($new_amt !== '' && $new_amt !== null) ? floatval($new_amt) : $order_total;
                if (! isset($this->sorted_payments[$order_id])) {
                    echo wc_price($order_total);
                    break;
                }

                $remaining = $order_total;

                foreach ($this->sorted_payments[$order_id] as $p) {

                    if ($p['id'] == $post_id) {
                        $remaining -= $p['amount'];
                        break;
                    }

                    $remaining -= $p['amount'];
                }

                if ($remaining < 0) $remaining = 0;

                echo wc_price($remaining);
                break;


            /* ----------------------------------------------
            * CHANGE AMOUNT
            * (Only last payment shows over amount)
            * Void payments ignored
            * ---------------------------------------------- */
            case 'change_amount':

                $order_id = get_post_meta($post_id, '_payment_order_id', true);
                if (!$order_id) { echo 'N/A'; break; }

                $wc_order = wc_get_order($order_id);
                if (!$wc_order) { echo 'N/A'; break; }

                // ---- ORDER TOTAL ----
                $order_total = $this->normalize_amount($wc_order->get_total());
                $new_amt = $wc_order->get_meta('new_netpay_amt');
                if ($new_amt !== '' && $new_amt !== null) {
                    $order_total = $this->normalize_amount($new_amt);
                }

                // ---- TOTAL PAID ----
                $total_paid = 0.0;

                if (!empty($this->sorted_payments[$order_id])) {
                    foreach ($this->sorted_payments[$order_id] as $p) {
                        $total_paid += $this->normalize_amount($p['amount']);
                    }
                }

                // ---- LAST PAYMENT CHECK ----
                $payments = $this->sorted_payments[$order_id] ?? [];
                $last_payment = end($payments);
                reset($payments);

                $is_last_payment = ($last_payment && (int)$last_payment['id'] === (int)$post_id);

                if ($is_last_payment) {
                    $over_amount = $total_paid - $order_total;

                    if ($over_amount > 0) {
                        echo '<span style="color:green;font-weight:bold;">' .
                            wc_price($over_amount) .
                            '</span>';
                    } else {
                        echo wc_price(0);
                    }
                } else {
                    echo wc_price(0);
                }

                break;

            /* ----------------------------------------------
            * PAYMENT METHOD
            * ---------------------------------------------- */
            case 'payment_method':
                // echo esc_html(get_post_meta($post_id, '_payment_method', true)) ?: __('N/A', 'pinaka-pos');
                $order_id = get_post_meta($post_id, '_payment_order_id', true);
                if(isset($this->sorted_notes[$order_id][$post_id]))
                {
                    echo $this->sorted_notes[$order_id][$post_id];
                }
                break;

            /* ----------------------------------------------
            * PAYMENT USER
            * ---------------------------------------------- */
            case 'payment_user_id':
                $payment_user_id = get_post_meta($post_id, '_payment_user_id', true);
                $payment_user = $payment_user_id ? get_userdata($payment_user_id) : false;
                echo $payment_user ? esc_html($payment_user->display_name) : __('N/A', 'pinaka-pos');
                break;


            default:
                break;
        }
    }


    private function define_admin_hooks() {
        $this->loader->add_action('init', $this, 'register_payments_post_type');
    }
    private function normalize_amount($amount) {
        if ($amount === null || $amount === '') {
            return 0.0;
        }

        // Remove currency symbols and commas
        $amount = preg_replace('/[^\d.\-]/', '', (string) $amount);

        return (float) $amount;
    }

    public function add_payment_meta_boxes() {
        add_meta_box(
            'payment_details',
            'Payment Details',
            [$this, 'render_payment_details_meta_box'],
            'payments',
            'normal',
            'high'
        );
    }

    public function render_payment_details_meta_box($post) {
        // Nonce for security
        wp_nonce_field('pinaka_save_payment_meta', 'pinaka_payment_meta_nonce');

        $order_id = get_post_meta($post->ID, '_payment_order_id', true);
        $amount = get_post_meta($post->ID, '_payment_amount', true);
        $payment_method = get_post_meta($post->ID, '_payment_method', true);
        $shift_id = get_post_meta($post->ID, '_payment_shift_id', true);
        $vendor_id = get_post_meta($post->ID, '_payment_vendor_id', true);
        $user_id = get_post_meta($post->ID, '_payment_user_id', true);
        $service_type = get_post_meta($post->ID, '_payment_service_type', true);
        $datetime = get_post_meta($post->ID, '_payment_datetime', true);
        $notes = get_post_meta($post->ID, '_payment_notes', true);
        $is_void  = get_post_meta($post->ID, '_payment_void', true);
        if($is_void && $is_void !== 'false') { $amount = 0; }
        // Fetch Shifts
        $shifts = get_posts([
            'post_type'   => 'shifts',
            'post_status' => 'publish',
            'numberposts' => -1
        ]);

        $employees = get_users([
            'role'    => 'employee',
            'orderby' => 'display_name'
        ]);
        ?>
        <p>
            <label for="payment_order_id">Order ID:</label><br>
            <input type="text" id="payment_order_id" name="payment_order_id" value="<?php echo esc_attr($order_id); ?>" style="width:100%;">
        </p>
        <p>
            <label for="payment_amount">Amount:</label><br>
            <input type="number" step="0.01" id="payment_amount" name="payment_amount" value="<?php echo esc_attr($amount); ?>" style="width:100%;">
        </p>
        <p>
            <label for="payment_shift_id">Shift:</label><br>
            <select id="payment_shift_id" name="payment_shift_id">
                <option value="">Select Shift</option>
                <?php foreach ($shifts as $shift) : ?>
                    <option value="<?php echo $shift->ID; ?>" <?php selected($shift_id, $shift->ID); ?>>
                        <?php echo esc_html($shift->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="payment_method">Payment Method:</label><br>
            <select id="payment_method" name="payment_method">
                <option value="Cash" <?php selected($payment_method, 'Cash'); ?>>Cash</option>
                <option value="Card" <?php selected($payment_method, 'Card'); ?>>Card</option>
                <option value="EBT" <?php selected($payment_method, 'EBT'); ?>>EBT</option>
                <option value="Wallet" <?php selected($payment_method, 'Wallet'); ?>>Wallet</option>
                <option value="Other" <?php selected($payment_method, 'Other'); ?>>Other</option>
            </select>
        </p>
        <p>
            <label for="payment_notes">Notes:</label><br>
            <textarea id="payment_notes" name="payment_notes" style="width:100%;"><?php echo esc_textarea($notes); ?></textarea>
        </p>
        <?php
    }

    public function save_payment_meta($post_id) {
        // Basic checks
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!isset($_POST['pinaka_payment_meta_nonce'])) return;
        if (!wp_verify_nonce($_POST['pinaka_payment_meta_nonce'], 'pinaka_save_payment_meta')) return;

        // Only for payments CPT
        if (get_post_type($post_id) !== 'payments') return;

        if (!current_user_can('edit_post', $post_id)) return;

        // ----------------------------
        // SAVE ALL PAYMENT META FIELDS
        // ----------------------------
        $order_id = isset($_POST['payment_order_id']) ? sanitize_text_field($_POST['payment_order_id']) : '';
        $payment_amount = isset($_POST['payment_amount']) ? floatval($_POST['payment_amount']) : '';
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';

        update_post_meta($post_id, '_payment_order_id', $order_id);
        update_post_meta($post_id, '_payment_amount', $payment_amount);
        update_post_meta($post_id, '_payment_method', $payment_method);

        if (isset($_POST['payment_notes'])) {
            update_post_meta($post_id, '_payment_notes', sanitize_textarea_field($_POST['payment_notes']));
        }

        if (isset($_POST['payment_shift_id'])) {
            update_post_meta($post_id, '_payment_shift_id', intval($_POST['payment_shift_id']));
        }

        // if (isset($_POST['payment_vendor_id'])) {
        //     update_post_meta($post_id, '_payment_vendor_id', intval($_POST['payment_vendor_id']));
        // }

        // if (isset($_POST['payment_user_id'])) {
        //     update_post_meta($post_id, '_payment_user_id', intval($_POST['payment_user_id']));
        // }

        // if (isset($_POST['payment_service_type'])) {
        //     update_post_meta($post_id, '_payment_service_type', sanitize_text_field($_POST['payment_service_type']));
        // }

        if (isset($_POST['payment_datetime'])) {
            update_post_meta($post_id, '_payment_datetime', sanitize_text_field($_POST['payment_datetime']));
        }

        // ----------------------------
        // BUILD TITLE: order + paymentPostID + method
        // ----------------------------

        $final_order_id = !empty($order_id) ? $order_id : 'N/A';
        $final_method   = !empty($payment_method) ? $payment_method : 'N/A';
        $payment_id     = $post_id;

        // $new_title = $final_order_id . ' + ' . $payment_id . ' + ' . $final_method;

        // // Prevent infinite update loop
        // remove_action('save_post', [$this, 'save_payment_meta']);

        // wp_update_post([
        //     'ID'         => $post_id,
        //     'post_title' => $new_title,
        //     'post_name'  => sanitize_title($new_title)
        // ]);

        // // Restore hook
        // add_action('save_post', [$this, 'save_payment_meta']);
    }

    /**
     * Inject small admin CSS to style negative amounts.
     */
    public function payments_admin_styles() {
        echo '<style>.pinaka-negative-amount{color:#a00;font-weight:700;}</style>';
    }
}

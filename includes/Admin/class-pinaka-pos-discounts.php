<?php 
/**
 * The admin-discounts functionality of the plugin.
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

class Pinaka_POS_Discounts {

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

        add_action('add_meta_boxes', [$this, 'add_discount_meta_boxes']);
        add_action('save_post', [$this, 'save_discount_meta']);
        add_filter('manage_discounts_posts_columns', [$this, 'set_custom_columns']);
        add_action('manage_discounts_posts_custom_column', [$this, 'custom_column_content'], 10, 2);      
        add_action('woocommerce_before_cart_totals', 'custom_discount_code_field');
        add_action(
            'woocommerce_order_status_completed',
            [ $this, 'increment_discount_usage_on_completed_order' ],
            10,
            1
        );
        add_filter( 'post_row_actions', [ $this, 'remove_view_row_action' ], 10, 2 );



    }

    /**
     * Register the vendor custom post type.
     *
     * @since    1.0.0
     * @access   private
     */
    public function register_discounts_post_type() {
        $labels = array(
            'name'                  => __('Discounts', 'pinaka-pos'),
            'singular_name'         => __('Discount', 'pinaka-pos'),
            'add_new'               => __('Add New Discount', 'pinaka-pos'),
            'add_new_item'          => __('Add New Discount', 'pinaka-pos'),
            'edit_item'             => __('Edit Discount', 'pinaka-pos'),
            'new_item'              => __('New Discount', 'pinaka-pos'),
            'view_item'             => __('View Discount', 'pinaka-pos'),
            'search_items'          => __('Search Discounts', 'pinaka-pos'),
            'not_found'             => __('No Discounts found', 'pinaka-pos'),
            'not_found_in_trash'    => __('No Discounts found in Trash', 'pinaka-pos'),
            'menu_name'             => __('Discounts', 'pinaka-pos'),
        );

        $args = array(
            'labels'        => $labels,
            'description'   => __('Discounts custom post type', 'pinaka-pos'),
            'public'        => true,
            'publicly_queryable' => true,
            'show_ui'       => true,
            'show_in_menu'  => true, // Hide from top menu, shown under Pinaka POS
            'query_var'     => true,
            'rewrite'       => array('slug' => 'discounts'),
            'capability_type' => 'post',
            'has_archive'   => true,
            'hierarchical'  => false,
            'menu_position' => null,
            'supports'      => array('title'),
        );

        register_post_type('discounts', $args);
    }


    public function remove_view_row_action( $actions, $post ) {

        if ( $post->post_type === 'discounts' ) {

            // remove View
            unset( $actions['view'] );

            // remove Preview
            unset( $actions['preview'] );

            // remove Quick Edit
            unset( $actions['inline hide-if-no-js'] );
        }

        return $actions;
    }


    /**
     * Define the admin Table columns.
     */

    public function set_custom_columns($columns) {

        $new_columns = [];

        // keep checkbox first
        if ( isset($columns['cb']) ) {
            $new_columns['cb'] = $columns['cb'];
        }

        // title
        $new_columns['title'] = __('Discount Name', 'pinaka-pos');

        // custom columns
        $new_columns['discount_type']   = __('Discount type', 'pinaka-pos');
        $new_columns['discount_amount'] = __('Discount amount', 'pinaka-pos');
        $new_columns['product_label']   = __('Product', 'pinaka-pos');
        $new_columns['minimum_amount']  = __('Usage / Limit', 'pinaka-pos');
        $new_columns['start_date']      = __('Start Date', 'pinaka-pos');
        $new_columns['expiry_date']     = __('Expiry date', 'pinaka-pos');

        // move date to LAST
        if ( isset($columns['date']) ) {
            $new_columns['date'] = $columns['date'];
        }

        return $new_columns;
    }


    /**
     * Render the column values.
     */

    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'discount_type':
               echo esc_html(get_post_meta($post_id, '_discount_type', true));
                break;
            case 'discount_amount':
                echo esc_html(get_post_meta($post_id, '_discount_amount', true));
                break;
            case 'product_label':

                $product_id = get_post_meta($post_id, '_product_label', true);

                if ( $product_id ) {
                    $product = wc_get_product( $product_id );

                    if ( $product ) {
                        echo esc_html( $product->get_name() );
                    } else {
                        echo '—';
                    }
                } else {
                    echo '—';
                }

                break;

            case 'minimum_amount':

                $usage_count = (int) get_post_meta( $post_id, '_usage_count', true );
                $usage_limit = (int) get_post_meta( $post_id, '_usage_limit', true );

                if ( ! $usage_limit ) {
                    echo esc_html( $usage_count . ' / ∞' );
                } else {
                    echo esc_html( $usage_count . ' / ' . $usage_limit );
                }

                break;

            case 'start_date':
                echo esc_html(get_post_meta($post_id, '_start_date', true));
                break;
            case 'expiry_date':
                echo esc_html(get_post_meta($post_id, '_expiry_date', true));
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
        $this->loader->add_action('init', $this, 'register_discounts_post_type');
        $this->loader->add_action('admin_menu', $this, 'add_discounts_submenu_page');
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
            __('Manage Discounts', 'pinaka-pos'),
            __('Manage Discounts', 'pinaka-pos'),
            'manage_options',
            'pinaka-pos-discounts',
            [$this, 'discountsRender']
        );
    }

    /**
     * Render the shifts submenu page.
     *
     * @since    1.0.0
     * @access   public
     */
    public function discountsRender() {
        wp_safe_redirect(admin_url('edit.php?post_type=discounts'));
        exit;
    }

    /**
     * Add meta boxes to Shift post type
     */
    public function add_discount_meta_boxes() {
        add_meta_box(
            'discount_tabs_box',
            __('Discount Settings', 'pinaka-pos'),
            [$this, 'render_discount_tabs_box'], // Only one render callback
            'discounts',
            'normal',
            'high'
        );
    }



    /**
     * Render Discount Details Meta Box
     */
    public function render_discount_tabs_box($post) {
        // Load existing meta values
        $min_spend = get_post_meta($post->ID, '_discount_min_spend', true);
        $max_usage = get_post_meta($post->ID, '_discount_max_usage', true);
        $discount_type = get_post_meta($post->ID, '_discount_type', true);
        $discount_amount = get_post_meta($post->ID, '_discount_amount', true);
        $start_date = get_post_meta($post->ID, '_start_date', true);
        $expiry_date = get_post_meta($post->ID, '_expiry_date', true);

        $minimum_amount = get_post_meta($post->ID, '_minimum_amount', true);
        $maximum_amount = get_post_meta($post->ID, '_maximum_amount', true);
        // $min_quantity = get_post_meta($post->ID, '_min_qty', true);
        // $max_quantity = get_post_meta($post->ID, '_max_qty', true);
        $product_ids = get_post_meta($post->ID, '_product_label', true);
        $usage_limit = get_post_meta($post->ID, '_usage_limit', true);
        //$usage_limit_per_user = get_post_meta($post->ID, '_usage_limit_per_user', true);

        ?>
    
        <div class="discount-tabs-wrap">
            <ul class="tabs">
                <li class="active" data-tab="tab-general">General</li>
                <li data-tab="tab-restrictions">Usage Restrictions</li>
                <!-- <li data-tab="tab-limits">Usage Limits</li> -->
            </ul>
    
            <div class="tab-content active panel" id="tab-general">
                <table class="form-table">
                    <tbody>
                        <tr class="form-field discount_type_field">
                            <th scope="row">
                                <label for="discount_type"><?php _e('Discount Type', 'pinaka-pos'); ?></label>
                            </th>
                            <td>
                                <select name="discount_type" id="discount_type" class="select short">
                                    <option value="percent" <?php selected($discount_type, 'percent'); ?>>
                                        <?php _e('Percentage Discount', 'pinaka-pos'); ?>
                                    </option>
                                    <option value="fixed_cart" <?php selected($discount_type, 'fixed_cart'); ?>>
                                        <?php _e('Fixed Cart Discount', 'pinaka-pos'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>

                        <tr class="form-field discount_amount_field">
                            <th scope="row">
                                <label for="discount_amount"><?php _e('Discount Amount', 'pinaka-pos'); ?></label>
                            </th>
                            <td>
                                <input
                                    type="number"
                                    step="0.01"
                                    class="short wc_input_price"
                                    name="discount_amount"
                                    id="discount_amount"
                                    value="<?php echo esc_attr($discount_amount); ?>"
                                    placeholder="0"
                                />
                                <p class="description" id="discount_amount_desc"></p>
                            </td>
                        </tr>

                        <script>
                            jQuery(function ($) {

                                function updateDiscountAmountField() {
                                    var type   = $('#discount_type').val();
                                    var amount = $('#discount_amount');
                                    var desc   = $('#discount_amount_desc');

                                    if (type === 'percent') {
                                        amount.attr({
                                            min: 0,
                                            max: 100,
                                            step: 0.01
                                        });
                                        desc.text('Enter a percentage value (e.g. 10 for 10%).');
                                    } else if (type === 'fixed_cart') {
                                        amount.removeAttr('max');
                                        amount.attr({
                                            min: 0,
                                            step: 0.01
                                        });
                                        desc.text('Enter a fixed discount amount for the cart.');
                                    }
                                }

                                // Run on page load
                                updateDiscountAmountField();

                                // Run on change
                                $('#discount_type').on('change', function () {
                                    updateDiscountAmountField();
                                });

                            });
                            </script>

                        <!-- Product Label -->
                        <tr class="form-field product_label_field">
                            <th scope="row">
                                <label for="product_label"><?php _e('Product', 'pinaka-pos'); ?></label>
                            </th>
                            <td>
                                <select 
                                    id="product_label" 
                                    name="product_label" 
                                    class="product-select-search" 
                                    style="width: 100%;"
                                    data-placeholder="Search product..."
                                >
                                    <option value="">Select a product</option>

                                    <?php
                                    $selected_product = get_post_meta($post->ID, '_product_label', true);

                                    $products = wc_get_products([
                                        'status' => 'publish',
                                        'limit'  => -1,
                                        'orderby' => 'title',
                                        'order' => 'ASC',
                                    ]);

                                    foreach ($products as $product) {
                                        $selected = ($selected_product == $product->get_id()) ? 'selected' : '';
                                        echo '<option value="' . esc_attr($product->get_id()) . '" ' . $selected . '>'
                                                . esc_html($product->get_name()) . 
                                            '</option>';
                                    }
                                    ?>
                                </select>

                                <p class="description"><?php _e('Search and select a product.', 'pinaka-pos'); ?></p>
                            </td>
                        </tr>

                        

                        
                        <!-- Auto Apply -->
                        <tr class="form-field pinaka_discount_auto_apply_field">
                            <th scope="row">
                                <label for="pinaka_discount_auto_apply"><?php _e('Auto Apply', 'pinaka-pos'); ?></label>
                            </th>
                            <td>
                                <?php $pinaka_discount_auto_apply = get_post_meta($post->ID, '_pinaka_discount_auto_apply', true); ?>
                                <input type="checkbox" name="pinaka_discount_auto_apply" id="pinaka_discount_auto_apply" value="yes"
                                    class="checkbox" <?php checked($pinaka_discount_auto_apply, 'yes'); ?> />
                                <span class="description">
                                    <?php _e('Automatically apply the discount when conditions match.', 'pinaka-pos'); ?>
                                </span>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

            <div class="tab-content" id="tab-restrictions">
                <table class="form-table">
                    <tbody>
                        <!-- Minimum Spend -->
                        <tr class="form-field minimum_amount_field">
                            <th scope="row">
                                <label for="minimum_amount"><?php _e('Minimum spend', 'pinaka-pos'); ?></label>
                            </th>
                            <td>
                                <input 
                                    type="text" 
                                    class="short wc_input_price" 
                                    name="minimum_amount" 
                                    id="minimum_amount" 
                                    value="<?php echo esc_attr( $minimum_amount ); ?>" 
                                    placeholder="<?php esc_attr_e('No minimum', 'pinaka-pos'); ?>" 
                                />
                                <p class="description">
                                    <?php _e('This field allows you to set the minimum spend (subtotal) allowed to use the discount.', 'pinaka-pos'); ?>
                                </p>
                            </td>
                        </tr>

                        <!-- Maximum Spend -->
                        <tr class="form-field maximum_amount_field">
                            <th scope="row">
                                <label for="maximum_amount"><?php _e('Maximum spend', 'pinaka-pos'); ?></label>
                            </th>
                            <td>
                                <input 
                                    type="text" 
                                    class="short wc_input_price" 
                                    name="maximum_amount" 
                                    id="maximum_amount" 
                                    value="<?php echo esc_attr( $maximum_amount ); ?>" 
                                    placeholder="<?php esc_attr_e('No maximum', 'pinaka-pos'); ?>" 
                                />
                                <p class="description">
                                    <?php _e('This field allows you to set the maximum spend (subtotal) allowed when using the discount.', 'pinaka-pos'); ?>
                                </p>
                            </td>
                        </tr>

                        <!-- Discount Start Date -->
                        <tr class="form-field start_date_field">
                            <th scope="row">
                                <label for="start_date"><?php _e('Discount Start Date', 'pinaka-pos'); ?></label>
                            </th>
                            <td>
                                <?php $start_date = get_post_meta($post->ID, '_start_date', true); ?>
                                <input type="date" name="start_date" id="start_date"
                                    value="<?php echo esc_attr($start_date); ?>" placeholder="YYYY-MM-DD" />
                                <p class="description"><?php _e('Date from which the discount becomes active.', 'pinaka-pos'); ?></p>
                            </td>
                        </tr>

                        <!-- Expiry Date -->
                        <tr class="form-field expiry_date_field">
                            <th scope="row">
                                <label for="expiry_date"><?php _e('Discount Expiry Date', 'pinaka-pos'); ?></label>
                            </th>
                            <td>
                                <input type="date" name="expiry_date" id="expiry_date" value="<?php echo esc_attr($expiry_date); ?>" placeholder="YYYY-MM-DD" />
                                <p class="description"><?php _e('Set the expiry date for this discount.', 'pinaka-pos'); ?></p>
                            </td>
                        </tr>

                        <script>
                            jQuery(document).ready(function($) {
                                $('#product_label').select2({
                                    placeholder: "Search for a product",
                                    allowClear: true,
                                    width: 'resolve'
                                });
                            });
                        </script>
                        
                        <!-- Usage Limits -->
                        <tr class="form-field usage_limit_field">
                            <th scope="row">
                                <label for="usage_limit">
                                    <?php _e('Usage limit per discount', 'pinaka-pos'); ?>
                                </label>
                            </th>
                            <td>
                                <input 
                                    type="number"
                                    class="short"
                                    name="usage_limit"
                                    id="usage_limit"
                                    value="<?php echo esc_attr( $usage_limit ); ?>"
                                    min="0"
                                    placeholder="<?php esc_attr_e('Unlimited Usage', 'pinaka-pos'); ?>"
                                />
                                <p class="description">
                                    <?php _e('How many times this discount can be used in total.', 'pinaka-pos'); ?>
                                </p>
                            </td>
                        </tr>


                    </tbody>
                </table>
            </div>
    
        </div>

    
        <style>
            .discount-tabs-wrap .tabs {
                margin-bottom: 10px;
                list-style: none;
                padding: 0;
                display: flex;
                border-bottom: 1px solid #ccc;
            }
    
            .discount-tabs-wrap .tabs li {
                padding: 8px 14px;
                cursor: pointer;
                background: #f5f5f5;
                margin-right: 4px;
            }
    
            .discount-tabs-wrap .tabs li.active {
                background: #fff;
                border: 1px solid #ccc;
                border-bottom: none;
                border-radius: 4px 4px 0 0;
            }
    
            .discount-tabs-wrap .tab-content {
                display: none;
                background: #fff;
                padding: 10px;
                border: 1px solid #ccc;
                border-radius: 0 4px 4px 4px;
            }
    
            .discount-tabs-wrap .tab-content.active {
                display: block;
            }

            #bulk-discount-container {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
                margin-bottom: 10px;
            }

            #bulk-discount-container th,
            #bulk-discount-container td {
                border: 1px solid #ddd;
                padding: 10px;
                vertical-align: middle;
                text-align: center;
            }

            #bulk-discount-container thead th {
                background-color: #f9f9f9;
                font-weight: 600;
            }

            #bulk-discount-container input[type="number"] {
                width: 80%;
                padding: 4px;
                text-align: center;
            }

            #bulk-discount-container .wdr_desc_text {
                display: block;
                font-size: 11px;
                color: #666;
                margin-top: 4px;
            }

            #bulk-discount-container .dashicons-menu {
                cursor: move;
                font-size: 16px;
            }

            #bulk-discount-container .wdr_discount_remove {
                color: #a00;
                font-size: 18px;
                transition: color 0.2s ease;
            }

            #bulk-discount-container .wdr_discount_remove:hover {
                color: #dc3232;
            }

        </style>
    
        <script>
            jQuery(document).ready(function($) {
                $('.discount-tabs-wrap .tabs li').click(function() {
                    var tabId = $(this).data('tab');
                    $('.discount-tabs-wrap .tabs li').removeClass('active');
                    $(this).addClass('active');
                    $('.discount-tabs-wrap .tab-content').removeClass('active');
                    $('#' + tabId).addClass('active');
                });
            });

            jQuery(document).ready(function($) {
                // Tabs
                $('.discount-tabs-wrap .tabs li').on('click', function() {
                    var tab_id = $(this).data('tab');

                    $('.discount-tabs-wrap .tabs li').removeClass('active');
                    $('.discount-tabs-wrap .tab-content').removeClass('active');

                    $(this).addClass('active');
                    $('#' + tab_id).addClass('active');
                });

                // Bulk Discount Row Logic
                let bulkIndex = $('#bulk-discount-container .wdr-discount-group').length;

                $('#add-bulk-discount-row').on('click', function() {
                    let template = $('#bulk-discount-template').html().replace(/__INDEX__/g, bulkIndex);
                    $('#bulk-discount-container').append(template);
                    bulkIndex++;
                });

                $(document).on('click', '.wdr_discount_remove', function() {
                    $(this).closest('.wdr-discount-group').remove();
                });
            });

        </script>

        <?php
    }

    /**
     * Save Shift Meta Fields
     */
    public function save_discount_meta($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        // echo    '<pre>';
        // print_r($_POST);
        // echo    '</pre>';
        // die;
        if (array_key_exists('discount_type', $_POST)) {
            update_post_meta($post_id, '_discount_type', sanitize_text_field($_POST['discount_type']));
        }

        if (array_key_exists('discount_amount', $_POST)) {
            update_post_meta($post_id, '_discount_amount', sanitize_text_field($_POST['discount_amount']));
        }

        if (array_key_exists('start_date', $_POST)) {
            update_post_meta($post_id, '_start_date', sanitize_text_field($_POST['start_date']));
        } 

        if (array_key_exists('expiry_date', $_POST)) {
            update_post_meta($post_id, '_expiry_date', sanitize_text_field($_POST['expiry_date']));
        }

        if (array_key_exists('pinaka_discount_auto_apply', $_POST)) {
            update_post_meta($post_id, '_pinaka_discount_auto_apply', 'yes');
        }else {
            update_post_meta($post_id, '_pinaka_discount_auto_apply', 'no');
        }

        if (array_key_exists('minimum_amount', $_POST)) {
            update_post_meta($post_id, '_minimum_amount', sanitize_text_field($_POST['minimum_amount']));
        }

        if (array_key_exists('maximum_amount', $_POST)) {
            update_post_meta($post_id, '_maximum_amount', sanitize_text_field($_POST['maximum_amount']));
        } 

        if (isset($_POST['product_label'])) {
            update_post_meta($post_id, '_product_label', sanitize_text_field($_POST['product_label']));
        }

        // if (array_key_exists('min_qty', $_POST)) {
        //     update_post_meta($post_id, '_min_qty', sanitize_text_field($_POST['min_qty']));
        // } else {
        //     delete_post_meta($post_id, '_min_qty');
        // }

        // if (array_key_exists('max_qty', $_POST)) {
        //     update_post_meta($post_id, '_max_qty', sanitize_text_field($_POST['max_qty']));
        // } else {
        //     delete_post_meta($post_id, '_max_qty');
        // }
        
        if (array_key_exists('required_product_ids', $_POST)) {
            update_post_meta($post_id, '_required_product_ids', array_map('sanitize_text_field', $_POST['required_product_ids']));
        } else {
            delete_post_meta($post_id, '_required_product_ids');
        }

        if (isset($_POST['usage_limit'])) {
            update_post_meta($post_id, '_usage_limit', sanitize_text_field($_POST['usage_limit']));
        }

        // if (isset($_POST['usage_limit_per_user'])) {
        //     update_post_meta($post_id, '_usage_limit_per_user', sanitize_text_field($_POST['usage_limit_per_user']));
        // }

    }

    public function render_discount_description_box($post) {
        $value = get_post_meta($post->ID, '_discount_description', true);
        ?>
        <textarea 
            name="discount_description" 
            class="custom-discount-description" 
            placeholder="Enter discount description here..." 
            style="width:100%; min-height:120px;"><?php echo esc_textarea($value); ?></textarea>
        <?php
    }

    function custom_discount_code_field() {
        echo '<div class="custom-discount-field">
            <label for="custom_discount_code">Enter Discount Code:</label>
            <input type="text" name="custom_discount_code" id="custom_discount_code" value="">
        </div>';
    }

    /**
 * Increment discount usage count ONLY when order is completed
 */
public function increment_discount_usage_on_completed_order( $order_id ) {

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }

    // Ensure discount was actually applied
    if ( $order->get_meta( '_pinaka_discount_amount_auto_apply' ) !== 'yes' ) {
        return;
    }

    // Prevent double counting
    if ( $order->get_meta( '_pinaka_discount_usage_counted' ) === 'yes' ) {
        return;
    }

    // Fetch auto-apply discounts
    $discounts = get_posts([
        'post_type'   => 'discounts',
        'post_status' => 'publish',
        'numberposts' => -1,
        'meta_query'  => [
            [
                'key'   => '_pinaka_discount_auto_apply',
                'value' => 'yes',
            ],
        ],
    ]);

    foreach ( $discounts as $discount ) {

        $usage_limit = (int) get_post_meta( $discount->ID, '_usage_limit', true );
        $usage_count = (int) get_post_meta( $discount->ID, '_usage_count', true );

        // Respect usage limit
        if ( $usage_limit > 0 && $usage_count >= $usage_limit ) {
            continue;
        }

        update_post_meta(
            $discount->ID,
            '_usage_count',
            $usage_count + 1
        );
    }

    // Mark order as counted
    $order->update_meta_data( '_pinaka_discount_usage_counted', 'yes' );
    $order->save();
}

}
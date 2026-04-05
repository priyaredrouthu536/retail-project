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

class Pinaka_POS_Mix_Match_Discounts {

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
        add_filter(
            'manage_edit-mix_match_discounts_columns',
            [$this, 'set_custom_columns']
        );  
        add_action('woocommerce_before_cart_totals', 'custom_discount_code_field');
        add_action(
            'manage_mix_match_discounts_posts_custom_column',
            [$this, 'custom_column_content'],
            10,
            2
        );

        add_filter( 'post_row_actions', [ $this, 'remove_view_row_action' ], 10, 2 );
    }

    /**
     * Register the vendor custom post type.
     *
     * @since    1.0.0
     * @access   private
     */
    public function register_mix_match_discounts_post_type() {
        $labels = array(
            'name'                  => __('Mix & Match Discounts', 'pinaka-pos'),
            'singular_name'         => __('Mix Match Discount', 'pinaka-pos'),
            'add_new'               => __('Add New Mix Match Discount', 'pinaka-pos'),
            'add_new_item'          => __('Add New Mix Match Discount', 'pinaka-pos'),
            'edit_item'             => __('Edit Mix Match Discount', 'pinaka-pos'),
            'new_item'              => __('New Mix Match Discount', 'pinaka-pos'),
            'view_item'             => __('View Mix Match Discount', 'pinaka-pos'),
            'search_items'          => __('Search Mix & Match Discounts', 'pinaka-pos'),
            'not_found'             => __('No Mix & Match Discounts found', 'pinaka-pos'),
            'not_found_in_trash'    => __('No Mix & Match Discounts found in Trash', 'pinaka-pos'),
            'menu_name'             => __('Mix & Match Discounts', 'pinaka-pos'),
        );

        $args = array(
            'labels'        => $labels,
            'description'   => __('Mix & Match Discounts custom post type', 'pinaka-pos'),
            'public'        => true,
            'publicly_queryable' => true,
            'show_ui'       => true,
            'show_in_menu'  => false, // Hide from top menu, shown under Pinaka POS
            'query_var'     => true,
            'rewrite'       => array('slug' => 'mix_match_discounts'),
            'capability_type' => 'post',
            'has_archive'   => true,
            'hierarchical'  => false,
            'menu_position' => null,
            'supports'      => array('title'),
        );

        register_post_type('mix_match_discounts', $args);
    }

        public function remove_view_row_action( $actions, $post ) {

        if ( $post->post_type === 'mix_match_discounts' ) {

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
     * Define the admin table columns.
     */
    public function set_custom_columns($columns) {

        $columns = [];

        $columns['cb'] = '<input type="checkbox" />';
        $columns['title'] = __('Discount Name', 'pinaka-pos');
        $columns['mix_match_discount_type'] = __('Discount Type', 'pinaka-pos');
        $columns['mix_match_discount_amount'] = __('Discount Amount', 'pinaka-pos');
        $columns['parent_product'] = __('Main Product', 'pinaka-pos');
        $columns['child_products'] = __('Discounted Products', 'pinaka-pos');
        $columns['start_date'] = __('Start Date', 'pinaka-pos');
        $columns['expiry_date'] = __('Expiry Date', 'pinaka-pos');

        return $columns;
    }
    /**
     * Render the admin table column values.
     */
    public function custom_column_content($column, $post_id) {

        switch ($column) {

            case 'mix_match_discount_type':
                echo esc_html(
                    ucfirst(get_post_meta($post_id, '_mix_match_discount_type', true))
                );
                break;

            case 'mix_match_discount_amount':
                $amount = get_post_meta($post_id, '_mix_match_discount_amount', true);
                echo esc_html($amount);
                break;

            case 'parent_product':

                $parent_id = (int) get_post_meta(
                    $post_id,
                    '_mix_match_parent_product_id',
                    true
                );

                if ($parent_id) {
                    $product = wc_get_product($parent_id);
                    echo $product ? esc_html($product->get_name()) : '—';
                } else {
                    echo '—';
                }
                break;

            case 'child_products':

                $child_ids = (array) get_post_meta(
                    $post_id,
                    '_mix_match_child_product_ids',
                    true
                );

                if (!empty($child_ids)) {

                    $names = [];

                    foreach ($child_ids as $child_id) {
                        $product = wc_get_product($child_id);
                        if ($product) {
                            $names[] = $product->get_name();
                        }
                    }

                    echo esc_html(implode(', ', $names));
                } else {
                    echo '—';
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
        $this->loader->add_action('init', $this, 'register_mix_match_discounts_post_type');
        $this->loader->add_action('admin_menu', $this, 'add_mix_match_discounts_submenu_page');
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
            __('Manage Mix & Match Discounts', 'pinaka-pos'),
            __('Manage Mix & Match Discounts', 'pinaka-pos'),
            'manage_options',
            'pinaka-pos-mix-match-discounts',
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
        wp_safe_redirect(admin_url('edit.php?post_type=mix_match_discounts'));
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
            'mix_match_discounts',
            'normal',
            'high'
        );
    }



    /**
     * Render Discount Details Meta Box
     */
    public function render_discount_tabs_box($post) {
        // Load existing meta values
        $discount_type = get_post_meta($post->ID, '_mix_match_discount_type', true);
        $discount_amount = get_post_meta($post->ID, '_mix_match_discount_amount', true);
        $start_date = get_post_meta($post->ID, '_start_date', true);
        $expiry_date = get_post_meta($post->ID, '_expiry_date', true);
        $product_id = get_post_meta($post->ID, '_mix_match_product_label', true);
        $parent_product_id = get_post_meta($post->ID, '_mix_match_parent_product_label', true);
        ?>
    
        <div class="discount-tabs-wrap">
    
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
                                    <option value="fixed_item" <?php selected($discount_type, 'fixed_item'); ?>>
                                        <?php _e('Fixed Discount', 'pinaka-pos'); ?>
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
                                    } else if (type === 'fixed_item') {
                                        amount.removeAttr('max');
                                        amount.attr({
                                            min: 0,
                                            step: 0.01
                                        });
                                        desc.text('Enter a fixed discount amount.');
                                    }
                                }

                                updateDiscountAmountField();
                                $('#discount_type').on('change', updateDiscountAmountField);

                            });
                            </script>


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

                        <!-- Product Label -->
                        <tr class="form-field parent_product_field">
                            <th>
                                <label><?php _e('Main Product (Trigger)', 'pinaka-pos'); ?></label>
                            </th>
                            <td>
                                <?php
                                $selected_id = (int) get_post_meta($post->ID, '_mix_match_parent_product_id', true);
                                $selected_product = $selected_id ? wc_get_product($selected_id) : false;
                                ?>

                                <select
                                    class="wc-product-search"
                                    style="width:100%;"
                                    name="parent_product_id"
                                    data-placeholder="<?php esc_attr_e('Search a product...', 'pinaka-pos'); ?>"
                                    data-action="woocommerce_json_search_products_and_variations"
                                    data-allow_clear="true"
                                >
                                    <?php if ($selected_product) : ?>
                                        <option value="<?php echo esc_attr($selected_id); ?>" selected="selected">
                                            <?php echo esc_html($selected_product->get_name()); ?>
                                        </option>
                                    <?php endif; ?>
                                </select>
                                <p class="description"><?php _e('Search and select the main trigger product.', 'pinaka-pos'); ?></p>
                            </td>
                        </tr>

                        <tr class="form-field child_products_field">
                            <th>
                                <label><?php _e('Discounted Products', 'pinaka-pos'); ?></label>
                            </th>
                            <td>
                                <?php
                                $selected_child_ids = (array) get_post_meta($post->ID, '_mix_match_child_product_ids', true);
                                ?>

                                <select
                                    class="wc-product-search"
                                    multiple="multiple"
                                    style="width:100%;"
                                    name="child_product_ids[]"
                                    data-placeholder="<?php esc_attr_e('Search products...', 'pinaka-pos'); ?>"
                                    data-action="woocommerce_json_search_products_and_variations"
                                >
                                    <?php
                                    foreach ($selected_child_ids as $pid) {
                                        $p = wc_get_product($pid);
                                        if ($p) {
                                            echo '<option value="' . esc_attr($pid) . '" selected="selected">' . esc_html($p->get_name()) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                                <p class="description">These products will receive the discount</p>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
    
        </div>

        <?php
    }

    /**
     * Save Shift Meta Fields
     */
    public function save_discount_meta($post_id) {

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        update_post_meta(
            $post_id,
            '_mix_match_discount_type',
            sanitize_text_field($_POST['discount_type'] ?? '')
        );

        update_post_meta(
            $post_id,
            '_mix_match_discount_amount',
            floatval($_POST['discount_amount'] ?? 0)
        );

        update_post_meta(
            $post_id,
            '_start_date',
            sanitize_text_field($_POST['start_date'] ?? '')
        );

        update_post_meta(
            $post_id,
            '_expiry_date',
            sanitize_text_field($_POST['expiry_date'] ?? '')
        );

        // ✅ Parent product
        update_post_meta(
            $post_id,
            '_mix_match_parent_product_id',
            intval($_POST['parent_product_id'] ?? 0)
        );

        // ✅ Child products
        if (!empty($_POST['child_product_ids'])) {
            update_post_meta(
                $post_id,
                '_mix_match_child_product_ids',
                array_map('intval', $_POST['child_product_ids'])
            );
        } else {
            delete_post_meta($post_id, '_mix_match_child_product_ids');
        }
    }

}
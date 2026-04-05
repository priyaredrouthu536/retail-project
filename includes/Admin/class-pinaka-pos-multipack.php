<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Pinaka_Multipack_Discount {

    // public function __construct() {
    //     add_action(
    //         'woocommerce_before_calculate_totals',
    //         [ $this, 'apply_multipack_discount' ],
    //         10
    //     );
    // }
    public function register_menu() {

        add_submenu_page(
            'pinaka-pos-discounts',
            __( 'Multipack Discount', 'pinaka-pos' ),
            __( 'Multipack Discount', 'pinaka-pos' ),
            'manage_options',
            'multipack-discount',
            [ $this, 'render_page_mutlipack' ]
        );
    }

    public function render_page_mutlipack() {
        /* ---------- SAVE ---------- */
        if (
            isset( $_POST['save_multipack'] ) &&
            check_admin_referer( 'save_multipack_settings' )
        ) {

            $rows = [];
            $seen = [];
            $old_rows  = get_option( 'multipack_discount_settings', [] );
            $old_map  = [];
            foreach ( $old_rows as $row ) {
                $key = (int) $row['product_id'] . '-' . (int) $row['qty'];
                $old_map[ $key ] = $row;
            }
            if ( ! empty( $_POST['product_id'] ) ) {
                foreach ( $_POST['product_id'] as $i => $pid ) {

                    $pid         = (int) $pid;
                    $qty         = (int) ( $_POST['qty'][ $i ] ?? 0 );
                    $order_usage = (int) ( $_POST['order_usage'][ $i ] ?? 1 );
                    $discount   = $_POST['discount'][ $i ] ?? 0;
                    $start_date = sanitize_text_field( $_POST['start_date'][ $i ] ?? '' );
                    $end_date   = sanitize_text_field( $_POST['end_date'][ $i ] ?? '' );
                    if ( $qty <= 0 || $order_usage <= 0 ) {
                        continue;
                    }
                
                    $key = $pid . '-' . $qty;
                    if(isset($seen[ $key ]))
                    {
                        continue;
                    }
                    $seen[ $key ] = true;
                    $old_row    = $old_map[ $key ] ?? null;
                    $used_count = $this->get_multipack_used_count($pid, $qty);
                    
                    if ( $old_row && $used_count >= $order_usage ) {
                        $rows[] = $old_row;
                        continue;
                    }
                    $rows[] = [
                        'product_id' => $pid,
                        'qty'        => $qty,
                        'discount'   => $discount,
                        'start_date'  => $start_date,
                        'end_date'    => $end_date,
                        'order_usage' => $order_usage,
                    ];
                }
            }
            update_option( 'multipack_discount_settings', $rows );
            echo '<div class="notice notice-success"><p>Multipack discounts saved.</p></div>';
        }

        $rules = get_option( 'multipack_discount_settings', [] );
        ?>

        <div class="wrap" style="overflow:auto">
            <h1>Multipack Discount</h1>

            <form method="post">
                <?php wp_nonce_field( 'save_multipack_settings' ); ?>

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th style="width:10%">ID</th>
                            <th style="width:22%">Product</th>
                            <th style="width:8%">Qty</th>
                            <th style="width:10%">Discount</th>
                            <th style="width:10%">Start Date</th>
                            <th style="width:10%">End Date</th>
                            <th style="width:10%">Order Usage</th>
                            <th style="width:10%">Used Count</th>
                            <th style="width:10%">Action</th>
                        </tr>
                    </thead>
                    <tbody id="multipack-rows">

                    <?php foreach ( $rules as $rule ) :
                            $product_id  = (int) $rule['product_id'];
                            $order_usage = ( $rule['order_usage'] ?? "" );
                            $start_date  = $rule['start_date'] ?? '';
                            $end_date    = $rule['end_date'] ?? '';
                            $qty = (int) $rule['qty'];
                            // Calculate used count (read-only)
                            $used_count = $this->get_multipack_used_count( $product_id, $qty );
                        ?>
                        <tr>
                            <td><?php echo esc_html( $product_id ); ?></td>
                            <td>
                                <select name="product_id[]" class="wc-product-search" style="width:100%">
                                    <?php
                                    $product = wc_get_product( $product_id );
                                    if ( $product ) :
                                    ?>
                                        <option value="<?php echo esc_attr( $product->get_id() ); ?>" selected>
                                            <?php echo esc_html( $product->get_name() ); ?>
                                        </option>
                                    <?php endif; ?>
                                </select>
                            </td>
                            <td><input type="number" name="qty[]" value="<?php echo esc_attr( $rule['qty'] ); ?>" autocomplete="off" required></td>
                            <td><input type="text" name="discount[]" value="<?php echo esc_attr( $rule['discount'] ); ?>" autocomplete="off" required></td>
                            <td>
                                <input type="date" name="start_date[]" value="<?php echo esc_attr( $start_date ); ?>" required>
                            </td>
                            <td>
                                <input type="date" name="end_date[]" value="<?php echo esc_attr( $end_date ); ?>" required>
                            </td>
                            <td>
                                <input type="number" name="order_usage[]" value="<?php echo esc_attr( $order_usage ); ?>" min="1" required>
                            </td>
                            <td>
                                <strong>
                                    <?php echo esc_html( $used_count ); ?>
                                    /
                                    <?php $order_usage = !empty($order_usage) ? $order_usage : 0; echo esc_html( $order_usage ); ?>
                                </strong>
                            </td>
                            <?php if($used_count === 0) { ?>
                            <td><button type="button" class="button remove-row">Remove</button></td>
                            <?php } ?>
                        </tr>
                    <?php endforeach; ?>

                    </tbody>
                </table>

                <p>
                    <button type="button" class="button" id="add-multipack-row">+ Add Rule</button>
                </p>

                <p>
                    <button class="button button-primary" name="save_multipack">
                        Save Multipack Settings
                    </button>
                </p>
            </form>
        </div>

        <!-- TEMPLATE (OUTSIDE TABLE) -->
        <table style="display:none">
            <tr id="multipack-row-template">
                <td></td>
                <td>
                    <select name="product_id[]" class="wc-product-search" style="width:100%"></select>
                </td>
                <td><input type="number" name="qty[]" min="1" autocomplete="off" required></td>
                <td><input type="text" name="discount[]" autocomplete="off" required></td>
                <td><input type="date" name="start_date[]" required></td>
                <td><input type="date" name="end_date[]" required></td>
                <td><input type="number" name="order_usage[]" min="1" required></td>
                <td>0</td>
                <td><button type="button" class="button remove-row">Remove</button></td>
            </tr>
        </table>
        <?php
    }
    protected function get_multipack_used_count( int $product_id , $qty): int {

        if ( ! $product_id ) {
            return 0;
        }

        global $wpdb;

        $counts = [];

        // HPOS enabled?
        $hpos_enabled = wc_get_container()
            ->get( \Automattic\WooCommerce\Utilities\OrderUtil::class )
            ->custom_orders_table_usage_is_enabled();

        if ( $hpos_enabled ) {

            $orders_table = $wpdb->prefix . 'wc_orders';
            $meta_table   = $wpdb->prefix . 'wc_orders_meta';

            $sql = "
                SELECT om.meta_value AS pack_type, COUNT(*) AS total
                FROM {$orders_table} o
                INNER JOIN {$meta_table} om
                    ON o.id = om.order_id
                WHERE o.type = 'shop_order'
                AND o.status = 'wc-completed'
                AND om.meta_key = 'pack_type'
                GROUP BY om.meta_value
            ";

        } else {

            $sql = "
                SELECT pm.meta_value AS pack_type, COUNT(*) AS total
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm
                    ON p.ID = pm.post_id
                WHERE p.post_type = 'shop_order'
                AND p.post_status = 'wc-completed'
                AND pm.meta_key = 'pack_type'
                GROUP BY pm.meta_value
            ";
        }

        $results = $wpdb->get_results( $sql, ARRAY_A );

        $counts = [];
        foreach ( $results as $row ) {
            $counts[ $row['pack_type'] ] = (int) $row['total'];
        }

        $used = 0;
        $pack_key = $product_id . '-' . $qty;

        foreach ( $counts as $pack_type => $total ) {
            if ( $pack_type === $pack_key ) {
                $used += $total;
            }
        }

        return $used;
    }
}
?>

<?php
/**
 * REST API Modify Controller
 *
 * Handles requests to the deleted/*.
 *
 * @link       https://www.PinakaPOS.com/
 * @since      1.0.0
 *
 * @package    Pinaka_Pos
 * @subpackage Pinaka_Pos/public
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Modify query for modify date controller class.
 *
 * @package WooCommerce\RestApi
 */
class Pinaka_Order_Stock_Controller {



	/**
	 * Increase stock levels for items within an order.
	 *
	 * @since 3.0.0
	 * @param int|WC_Order $order_id Order ID or order instance.
	 */
	public function wc_increase_stock_levels( $order_id ) {
		if ( is_a( $order_id, 'WC_Order' ) ) {
			$order    = $order_id;
			$order_id = $order->get_id();
		} else {
			$order = wc_get_order( $order_id );
		}
		// We need an order, and a store with stock management to continue.
		if ( ! $order || 'yes' !== get_option( 'woocommerce_manage_stock' ) || ! apply_filters( 'woocommerce_mp_can_increase_order_stock', true, $order ) ) {
			return;
		}

		$changes = array();

		// Loop over all items.
		foreach ( $order->get_items() as $item ) {
			if ( ! $item->is_type( 'line_item' ) ) {
				continue;
			}

			// Only reduce stock once for each item.
			$product            = $item->get_product();
			$item_stock_reduced = $item->get_meta( '_increased_stock', true );

			if ( $item_stock_reduced || ! $product || ! $product->managing_stock() ) {
				continue;
			}

			/**
			 * Filter order item quantity.
			 *
			 * @param int|float             $quantity Quantity.
			 * @param WC_Order              $order    Order data.
			 * @param WC_Order_Item_Product $item Order item data.
			 */
			$qty       = apply_filters( 'woocommerce_order_item_quantity', $item->get_quantity(), $order, $item );
			$item_name = $product->get_formatted_name();
			$new_stock = wc_update_product_stock( $product, $qty, 'increase' );

			if ( is_wp_error( $new_stock ) ) {
				/* translators: %s item name. */
				$order->add_order_note( sprintf( __( 'Unable to increase stock for item %s.', 'woocommerce' ), $item_name ) );
				continue;
			}

			$item->add_meta_data( '_increased_stock', $qty, true );
			$item->save();

			$changes[] = $item_name . ' ' . ( $new_stock - $qty ) . '&rarr;' . $new_stock;

		}

		if ( $changes ) {
			$order->add_order_note( __( 'Stock levels increased:', 'woocommerce' ) . ' ' . implode( ', ', $changes ) );
		}

		do_action( 'woocommerce_mp_increase_order_stock', $order );
	}



	/**
	 * Reduce stock levels for items within an order, if stock has not already been reduced for the items.
	 *
	 * @since 3.0.0
	 * @param int|WC_Order $order_id Order ID or order instance.
	 */
	public function wc_reduce_stock_levels( $order_id ) {
		if ( is_a( $order_id, 'WC_Order' ) ) {
			$order    = $order_id;
			$order_id = $order->get_id();
		} else {
			$order = wc_get_order( $order_id );
		}

		// We need an order, and a store with stock management to continue.
		if ( ! $order || 'yes' !== get_option( 'woocommerce_manage_stock' ) || ! apply_filters( 'woocommerce_mp_can_restore_order_stock', true, $order ) ) {
			return;
		}

		$changes = array();

		// Loop over all items.
		foreach ( $order->get_items() as $item ) {
			if ( ! $item->is_type( 'line_item' ) ) {
				continue;
			}

			// Only increase stock once for each item.
			$product            = $item->get_product();
			$item_stock_reduced = $item->get_meta( '_increased_stock', true );

			if ( ! $item_stock_reduced || ! $product || ! $product->managing_stock() ) {
				continue;
			}

			$item_name = $product->get_formatted_name();
			$new_stock = wc_update_product_stock( $product, $item_stock_reduced, 'decrease' );

			if ( is_wp_error( $new_stock ) ) {
				/* translators: %s item name. */
				$order->add_order_note( sprintf( __( 'Unable to restore stock for item %s.', 'woocommerce' ), $item_name ) );
				continue;
			}

			$item->delete_meta_data( '_increased_stock' );
			$item->save();

			$changes[] = array(
				'product' => $product,
				'from'    => $new_stock + $item_stock_reduced,
				'to'      => $new_stock,
			);
		}
		wc_trigger_stock_change_notifications( $order, $changes );

		do_action( 'woocommerce_mp_restore_order_stock', $order );
	}

	/**
	 * When a payment is complete, we can reduce stock levels for items within an order.
	 *
	 * @since 3.0.0
	 * @param int $order_id Order ID.
	 */
	public function mp_maybe_reduce_stock_levels( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$stock_increased = wc_string_to_bool( get_post_meta( $order_id, '_order_stock_increased', true ) );

		// Only continue if we're reducing stock.
		if ( ! $stock_increased ) {
			return;
		}

		$this->wc_reduce_stock_levels( $order );

		// Ensure stock is marked as "reduced" in case payment complete or other stock actions are called.
		update_post_meta( $order_id, '_order_stock_increased', wc_bool_to_string( false ) );
	}
	/**
	 * When a payment is cancelled, restore stock.
	 *
	 * @since 3.0.0
	 * @param int $order_id Order ID.
	 */
	public function mp_maybe_increase_stock_levels( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$stock_increased = wc_string_to_bool( get_post_meta( $order_id, '_order_stock_increased', true ) );

		// Only continue if we're increasing stock.
		if ( $stock_increased ) {
			return;
		}
		$this->wc_increase_stock_levels( $order );

		update_post_meta( $order_id, '_order_stock_increased', wc_bool_to_string( true ) );
	}

	/**
	 * Executes code when an order's status is changed.
	 *
	 * @param int    $order_id   The ID of the order.
	 * @param string $old_status The old status of the order.
	 * @param string $new_status The new status of the order.
	 */
	public function update_order_status_callback( $order_id, $old_status, $new_status ) {
		if ( 'pending-purchase' === $new_status || 'ordered-purchase' === $new_status ) {
			$this->mp_maybe_reduce_stock_levels( $order_id );
		} elseif ( 'received-purchase' === $new_status ) {
			$this->mp_maybe_increase_stock_levels( $order_id );
		} elseif ( 'completed' != $new_status ) {

			$this->check_customer_billing( $order_id, false, $new_status );
		} else {
			$this->check_customer_billing( $order_id, true, $new_status );
		}
	}

	public function check_customer_billing( $order_id, $action, $new_status ) {

		global $wpdb;
		$table_name = $wpdb->prefix . 'pp_customer_balance';
		$data       = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE order_id = %d ORDER BY id DESC LIMIT 1",
				$order_id,
			)
		);
		if ( empty( $data ) ) {
			return;}

		$dataInsert = array(
			'user_id'          => $data->user_id,
			'order_id'         => $data->order_id,
			'due_date'         => $data->due_date,
			'payment_method'   => $data->payment_method,
			'remark'           => 'Order Status Changed ' . $new_status,
			'created_date'     => current_time( 'mysql' ), // created_date
			'created_date_gmt' => current_time( 'mysql', 1 ),

		);
		$shouldInsert = false;
		if ( $action ) {
			if ( $data->amount > 0 ) {

				$dataInsert['amount'] = ( -1 * ( $data->amount ) );
				$dataInsert['total']  = $data->total + ( -1 * ( $data->amount ) );
				$shouldInsert         = true;
			}
		} else {
			// completed
			if ( $data->amount < 0 ) {

				$dataInsert['amount'] = ( -1 * ( $data->amount ) );
				$dataInsert['total']  = $data->total + ( -1 * ( $data->amount ) );
				$shouldInsert         = true;
			}
		}

		$dataInsert['status'] = 'pending';
		// Insert the data into the table.
		if ( $shouldInsert ) {
			$wpdb->insert(
				$table_name,
				$dataInsert,
				array(
					'%d',
					'%d',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%f',
					'%f',
					'%s',
				)
			);
		}
	}

	/**
	 * Executes code when an order's status is changed.
	 *
	 * @param Woo_Order_Item $item The old status of the order.
	 */
	public function mp_order_item_removed_callbacked( $item_id, $order ) {
		$item = $order->get_item( $item_id );

		if ( ! $item->is_type( 'line_item' ) ) {
			return;
		}

		$payout_product_id = get_option( 'pinaka_payout_product_id', 0); 
		// <-- your payout product ID
		if ( (int) $item->get_product_id() === (int) $payout_product_id ) {
			// remove order-level payout meta and stop
			$order->delete_meta_data( '_order_payout' );
			$order->save_meta_data();
		}

		$discount_product_id = get_option( 'pinaka_discount_product_id', 0); 
		// <-- your payout product ID
		if ( (int) $item->get_product_id() === (int) $discount_product_id ) {
			// remove order-level payout meta and stop
			$order->delete_meta_data( '_order_discount' );
			$order->save_meta_data();
		}

		$product = $item->get_product();

		if ( ! $product || ! $product->managing_stock() ) {
			return;
		}

		$item_stock_reduced = $item->get_meta( '_increased_stock', true );

		if ( $item_stock_reduced ) {
			$item_name = $product->get_formatted_name();
			$new_stock = wc_update_product_stock( $product, $item_stock_reduced, 'decrease' );
			if ( is_wp_error( $new_stock ) ) {
				$order->add_order_note( sprintf( __( 'Unable to restore stock for item %s.', 'woocommerce' ), $item_name ) );
				return;
			}
			$item->delete_meta_data( '_increased_stock' );
			$item->save();
			$changes[] = $item_name . ' ' . ( $new_stock + $qty ) . '&rarr;' . $new_stock;
			if ( $changes ) {
				$order->add_order_note( __( 'Stock levels restore:', 'woocommerce' ) . ' ' . implode( ', ', $changes ) );
			}
		}

		$item_stock_reduced = $item->get_meta( '_reduced_stock', true );
		if ( $item_stock_reduced ) {
			$item_name = $product->get_formatted_name();
			$new_stock = wc_update_product_stock( $product, $item_stock_reduced, 'increase' );
			if ( is_wp_error( $new_stock ) ) {
				$order->add_order_note( sprintf( __( 'Unable to restore stock for item %s.', 'woocommerce' ), $item_name ) );
				return;
			}
			$item->delete_meta_data( '_reduced_stock' );
			$item->save();
			$changes[] = $item_name . ' ' . ( $new_stock - $item_stock_reduced ) . '&rarr;' . $new_stock;
			if ( $changes ) {
				$order->add_order_note( __( 'Stock levels increased:', 'woocommerce' ) . ' ' . implode( ', ', $changes ) );
			}
		}
	}
}

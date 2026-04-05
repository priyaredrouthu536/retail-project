<?php
/**
 * REST API Deleted Controller
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
 * REST API Report Top Sellers controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Report_Sales_V1_Controller
 */
class Pinaka_Deleted_Data_Controller {


	/**
	 * To save deleted data in option .        *
	 *
	 * @param int    $term         Term ID.
	 * @param String $option_name        option name.
	 */
	public function save_deleted_options( $term, $option_name ) {

		$this->delele_old_logs();
		$data = get_option( $option_name );
		if ( is_array( $data ) ) {
			$data[] = $term;
			update_option( $option_name, $data );
		} else {
			$data_array = array( $term );
			add_option( $option_name, $data_array );
		}

	}



	/**
	 * To delete old log which is 15 days plus.        *
	 */
	public function delele_old_logs() {
		$last_deleted_log = get_option( 'mp_deleted_last_log', null );
		if ( null !== $last_deleted_log ) {
			$date1    = new DateTime( gmdate( 'Y-m-d', strtotime( $last_deleted_log ) ) );
			$date2    = new DateTime( gmdate( 'Y-m-d' ) );
			$interval = date_diff( $date1, $date2 );
			$days     = $interval->format( '%a' );
			add_option( 'mp_deleted_last_log_da', $days );
			if ( $days >= 15 ) {
				global $wpdb;
				$results = (array) $wpdb->get_results(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->options}
                WHERE option_name LIKE %s",
						'mp_deleted%',
					),
					ARRAY_A
				);
			}
		} else {
			add_option( 'mp_deleted_last_log', gmdate( 'Y-m-d' ) );
		}
	}


	/**
	 * To save deleted post (products, product_variations, orders) for sync.        *
	 *
	 * @param int     $postid         Post ID.
	 * @param WP_Post $post       Post object.
	 */
	public function save_deleted_post( $postid, $post ) {
		$post_type = $post->post_type;
		$this->save_deleted_options( $postid, "mp_deleted_{$post_type}" );
	}

	/**
	 * To save deleted user (customer, etc) for sync.        *
	 *
	 * @param int $userid         User ID.
	 */
	public function save_deleted_user( $userid ) {
		$this->save_deleted_options( $userid, 'mp_deleted_user' );
	}

	/**
	 * To save deleted user (customer, etc) for sync.        *
	 *
	 * @param int $tax_rate_id         Tax Rate Id.
	 */
	public function save_deleted_tax( $tax_rate_id ) {
		$this->save_deleted_options( $tax_rate_id, 'mp_deleted_tax' );
	}


	/**
	 * After deleting an attribute.
	 *
	 * @param int    $id       Attribute ID.
	 * @param string $attribute_name     Attribute name.
	 * @param string $taxonomy Attribute taxonomy name.
	 */
	public function save_deleted_attribute( $id, $attribute_name, $taxonomy ) {
		$this->save_deleted_options( $id, 'mp_deleted_attributes' );
	}


	/** Save deleted term for sync .
	 *
	 * @param int     $term         Term ID.
	 * @param int     $tt_id        Term taxonomy ID.
	 * @param string  $taxonomy     Taxonomy slug.
	 * @param WP_Term $deleted_term Copy of the already-deleted term.
	 * @param array   $object_ids   List of term object IDs.
	 */
	public function save_deleted_term( $term, $tt_id, $taxonomy, $deleted_term, $object_ids ) {

		if ( strpos( $taxonomy, 'pa_' ) !== false ) {
			$this->save_deleted_options( $term, 'mp_deleted_attr_terms' );
		} elseif ( 'product_cat' === $taxonomy ) {
			$this->save_deleted_options( $term, 'mp_deleted_cat' );
		} elseif ( 'product_tag' === $taxonomy ) {
			$this->save_deleted_options( $term, 'mp_deleted_tag' );
		}

	}
}

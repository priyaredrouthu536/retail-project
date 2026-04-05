<?php
/**
 * REST API Payment API controller
 *
 * Handles requests to the Payments API endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package WooCommerce\RestApi
 * @since    3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use WooCommerce\RestApi;

/**
 * REST API Report Top Sellers controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Report_Sales_V1_Controller
 */
class Pinaka_Safes_Api_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'pinaka-pos/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'safes';

	public $curret_time;
	/**
	 * The timezone for the site.
	 *
	 * @var string
	 */

	/**
	 * Constructor.
	 * Sets the timezone to the site's timezone.
	 */
	public function __construct() {
		$this->curret_time = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ); // safer with date() // Should now return IST
	}


    /**
	 * Register the routes for sales reports.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/create-safe',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_safe_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/create-safe-drop',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_safedrop_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

		// register_rest_route(
		// 	$this->namespace,
		// 	$this->rest_base . '/update-shift',
		// 	array(
		// 		'methods'             => 'POST',
		// 		'callback'            => array( $this, 'shift_update_callback' ),
		// 		'permission_callback' => array( $this, 'check_user_role_permission' ),

		// 	)
		// );

		// register_rest_route(
		// 	$this->namespace,
		// 	$this->rest_base . '/get-shifts-by-user',
		// 	array(
		// 		'methods'             => 'GET',
		// 		'callback'            => array( $this, 'get_shifts_by_user_id_callback' ),
		// 		'permission_callback' => array( $this, 'check_user_role_permission' ),

		// 	)
		// );


		// register_rest_route(
		// 	$this->namespace,
		// 	$this->rest_base . '/get-shift-by-id',
		// 	array(
		// 		'methods'             => 'GET',
		// 		'callback'            => array( $this, 'get_shifts_by_id_callback' ),
		// 		'permission_callback' => array( $this, 'check_user_role_permission' ),

		// 	)
		// );
	}


    /**
	 * Check whether a given request has permission to view system status.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function check_user_role_permission( $request ) {

		$user_id = get_current_user_id();
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return new WP_Error(
				'pinakapos_rest_cannot_view',
				esc_html__( 'Sorry, you cannot give permission.', 'pinaka-pos' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		// Get all registered roles
		global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}
		$all_roles = array_keys( $wp_roles->get_names() );

		// Allow if user has any of the registered roles
		foreach ( $user->roles as $role ) {
			if ( in_array( $role, $all_roles, true ) ) {
				return true;
			}
		}

		return new WP_Error(
			'pinakapos_rest_cannot_view',
			esc_html__( 'Sorry, you cannot give permission.', 'pinaka-pos' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}

    /**
	 * Create a new shift post and calculate over/short.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response The response.
	 */

	public function create_safe_callback(WP_REST_Request $request) {

		$data = $request->get_json_params();
		$user_id = get_current_user_id();
		
		$shift_id = $data['shift_id'];
		$first_name = get_user_meta( $user_id, 'first_name', true );
		$last_name  = get_user_meta( $user_id, 'last_name', true );

		if (empty($data['safe_denom']) || !is_array($data['safe_denom'])) {
			return new WP_Error('invalid_data', 'Safe denominations are required', ['status' => 400]);
		}

		$post_id = wp_insert_post([
			'post_type' => 'safes',
			'post_status' => 'publish',
			'post_title' => 'Safe - ' . $first_name . ' ' . $last_name . ' - ' . date('Y-m-d H:i:s'),
		]);

		if (is_wp_error($post_id)) {
			return new WP_Error('insert_failed', 'Could not create Safe post', ['status' => 500]);
		}

		$safe_data = [];

		foreach ($data['safe_denom'] as $item) {
			$denomination = $item['denom'] ?? 0;
			$tube_count = intval($item['tube_count'] ?? 0);
			$cell_count = intval($item['cell_count'] ?? 0);
			$total = floatval($item['total'] ?? 0);

			if ($denomination > 0) {
				$safe_data[] = [
					'denom' => $denomination,
					'tube_count' => $tube_count,
					'cell_count' => $cell_count,
					'total' => $total
				];
			}
		}

		update_post_meta($post_id, '_safes_data', wp_json_encode($safe_data));
		update_post_meta($post_id, '_safe_coins_total', floatval($data['coins_total'] ?? 0));
		update_post_meta($post_id, '_safe_total_amount', floatval($data['total_amount'] ?? 0));
		update_post_meta($post_id, '_safe_shift_id', intval($shift_id));
		update_post_meta($post_id, '_safe_created_by', $user_id);

		return new WP_REST_Response([
			'message' => 'Safe created successfully',
			'post_id' => $post_id,
			'data' => $safe_data
		], 201);
	}


	/**
	 * Create a new safe drop post and calculate over/short.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response The response.
	 */

	public function create_safedrop_callback(WP_REST_Request $request) {

		$data = $request->get_json_params();
		$user_id = get_current_user_id();

		$shift_id = isset($data['shift_id']) ? intval($data['shift_id']) : 0;
		$first_name = get_user_meta($user_id, 'first_name', true);
		$last_name  = get_user_meta($user_id, 'last_name', true);

		if (empty($data['safe_drop_denom']) || !is_array($data['safe_drop_denom'])) {
			return new WP_Error('invalid_data', 'Safe Drop denomination are required', ['status' => 400]);
		}

		$post_id = wp_insert_post([
			'post_type'   => 'safedrops',
			'post_status' => 'publish',
			'post_title'  => 'Safe Drop - Shift ' . $shift_id . ' - ' . $first_name . ' ' . $last_name,
		]);

		if (is_wp_error($post_id)) {
			return new WP_Error('insert_failed', 'Could not create Safe Drop post', ['status' => 500]);
		}

		$safe_data = [];

		foreach ($data['safe_drop_denom'] as $item) {
			$denomination = $item['denom'] ?? 0;
			$denomination_count = intval($item['denom_count'] ?? 0);
			$total = floatval($item['total'] ?? 0);

			if ($denomination > 0 && $denomination_count > 0) {
				$safe_data[] = [
					'denom' => $denomination,
					'denom_count' => $denomination_count,
					'total' => $total
				];
			}
		}

		update_post_meta($post_id, '_safedrops_data', wp_json_encode($safe_data));
		update_post_meta($post_id, '_safedrops_total', floatval($data['total_cash'] ?? 0));
		update_post_meta($post_id, '_safedrops_total_notes', intval($data['total_notes'] ?? 0));
		update_post_meta($post_id, '_safedrops_shift_id', $shift_id);
		update_post_meta($post_id, '_safedrops_created_by', $user_id);

		return new WP_REST_Response([
			'message' => 'Safe Drop created successfully',
			'safe_drop_id' => $post_id,
			'safe_drop_denom' => $safe_data,
			'total_amount' => floatval($data['total_cash'] ?? 0),
			'total_notes' => intval($data['total_notes'] ?? 0),
			'shift_id' => $shift_id,
			'created_by' => $first_name . ' ' . $last_name,
			'created_at' => $this->curret_time,
		], 201);
	}

	/**
	 * Update Shift API Callback
	 */
	public function shift_update_callback( WP_REST_Request $request ) {

		$data    = $request->get_json_params();


		$post_id = intval( $data['shift_id'] );

		if ( ! $post_id || get_post_type( $post_id ) !== 'shifts' ) {
			return new WP_Error( 'invalid_shift', 'Invalid shift ID.', [ 'status' => 400 ] );
		}

		$closing_denominations_sum = $this->calculate_cash_total( $data['closing_denominations'] );

		$closing_balance = floatval( $data['closing_balance'] ?? 0 );
		
		//if denominations and opening balance are provided should same
		if ( ! empty( $data['closing_balance'] ) && is_numeric( $data['closing_balance'] ) ) {
			$closing_balance_input = floatval( $data['closing_balance'] );
			$diff = abs( $closing_denominations_sum - $closing_balance_input );

			if ( $diff > 20 ) {
				return new WP_Error(
					'invalid_data',
					sprintf(
						'Closing balance mismatch is too high. Difference: ₹%.2f (Allowed: ₹20.00)',
						$diff
					),
					array( 'status' => 400 )
				);
			}

			// Store mismatch note
			$note = '';
			if ( $diff > 0 ) {
				$note = sprintf(
					'Closing balance mismatch recorded. Difference of ₹%.2f accepted (within ₹20.00 tolerance).',
					$diff
				);
			} else {
				$note = 'Closing balance matches exactly with denominations.';
			}

			update_post_meta( $post_id, '_shift_closing_note_auto', $note );
		}


		
		// Optional updates
		if ( ! empty( $data['status'] ) ) {
			update_post_meta( $post_id, '_shift_status', sanitize_text_field( $data['status'] ) );
		}

		// if ( ! empty( $data['end_time'] ) ) {
			update_post_meta( $post_id, '_shift_end_time', sanitize_text_field( $data['end_time'] ) );
		// } else {
		update_post_meta( $post_id, '_shift_end_time', current_time( 'mysql' ) );
		// }

		if ( isset( $data['shift_closing_notes'] ) ) {
			update_post_meta( $post_id, '_shift_closing_notes', sanitize_text_field( $data['shift_closing_notes'] ) );
		}

		// Safe drop
		$safe_drop_total = 0;
		if ( ! empty( $data['safe_drop_denominations'] ) && is_array( $data['safe_drop_denominations'] ) ) {
			$safe_drop_total = $this->calculate_cash_total( $data['safe_drop_denominations'] );
			update_post_meta( $post_id, '_shift_safe_drop_total', $safe_drop_total );
			update_post_meta( $post_id, '_shift_safe_drop_denominations', wp_json_encode( $data['safe_drop_denominations'] ) );
		}

		// Closing denominations
		$closing_balance = 0;
		if ( ! empty( $data['closing_denominations'] ) && is_array( $data['closing_denominations'] ) ) {
			$closing_balance = $this->calculate_cash_total( $data['closing_denominations'] );
			update_post_meta( $post_id, '_shift_closing_balance', $closing_balance );
			update_post_meta( $post_id, '_shift_closing_denominations', wp_json_encode( $data['closing_denominations'] ) );
		} elseif ( isset( $data['closing_balance'] ) ) {
			$closing_balance = floatval( $data['closing_balance'] );
			update_post_meta( $post_id, '_shift_closing_balance', $closing_balance );
		}
		// --- Auto-fetch calculations ---

		$opening_balance = floatval( get_post_meta( $post_id, '_shift_opening_balance', true ) );

		// Get total cash sales
		$total_sales = $this->get_shift_sales_total( $post_id );
		update_post_meta( $post_id, '_shift_total_sales', $total_sales );

		// Get total payouts
		echo $total_payouts = $this->get_shift_payouts_total( $post_id );
		update_post_meta( $post_id, '_shift_total_payouts', $total_payouts );

		// Compute expected cash
		$expected_cash = $opening_balance + $total_sales - $total_payouts - $safe_drop_total;
		$over_short    = $closing_balance - $expected_cash;

		update_post_meta( $post_id, '_shift_over_short', round( $over_short, 2 ) );

		return $res = [
			'shift_id'        => $post_id,
			'status'          => get_post_meta( $post_id, '_shift_status', true ),
			'end_time'        => get_post_meta( $post_id, '_shift_end_time', true ) ?: $this->curret_time,
			'total_sales'     => $total_sales,
			'total_payouts'   => $total_payouts,
			'safe_drop'       => $safe_drop_total,
			'closing'         => $closing_balance,
			'expected_cash'   => round( $expected_cash, 2 ),
			'over_short'      => round( $over_short, 2 ),
		];
	}

	private function calculate_cash_total( $denominations ) {
		if ( ! is_array( $denominations ) ) {
			$denominations = json_decode( $denominations, true );
		}
		$total = 0;
		foreach ( $denominations as $denomination => $count ) {
			$total += floatval( $denomination ) * intval( $count );
		}
		return round( $total, 2 );
	}

	public function get_shifts_by_user_id_callback( WP_REST_Request $request ) {
		$user_id = $request->get_param( 'user_id' );
		if ( empty( $user_id ) ) {
			return new WP_Error( 'invalid_user', 'User ID is required', array( 'status' => 400 ) );
		}

		$args = array(
			'post_type'   => 'shifts',
			'post_status' => 'publish',
			'meta_query'  => array(
				array(
					'key'     => '_shift_user_id',
					'value'   => $user_id,
					'compare' => '='
				)
			),
			'numberposts' => -1
		);

		$shifts = get_posts( $args );
		$results = array();

		foreach ( $shifts as $shift ) {
			$results[] = $this->prepare_shift_data( $shift );
		}

		return rest_ensure_response( $results );
	}

	public function get_shifts_by_id_callback( WP_REST_Request $request ) {
		$shift_id = $request->get_param( 'shift_id' );
		if ( empty( $shift_id ) ) {
			return new WP_Error( 'invalid_shift_id', 'Shift ID is required', array( 'status' => 400 ) );
		}

		$shift = get_post( $shift_id );
		if ( ! $shift || $shift->post_type !== 'shifts' ) {
			return new WP_Error( 'not_found', 'Shift not found', array( 'status' => 404 ) );
		}

		return rest_ensure_response( $this->prepare_shift_data( $shift ) );
	}

	/**
	 * Helper method to structure shift data consistently
	 */
	private function prepare_shift_data( $shift ) {
		$meta_keys = array(
			'start_time', 'end_time', 'assigned_staff', 'total_sales', 'safe_drop_total',
			'opening_balance', 'closing_balance', 'notes', 'cash_count_details',
			'status', 'user_id', 'opening_denominations', 'closing_denominations', 'over_short'
		);

		$meta_data = array();
		foreach ( $meta_keys as $key ) {
			$value = get_post_meta( $shift->ID, '_shift_' . $key, true );

			// Decode specific JSON fields for client readability
			if ( in_array( $key, array( 'opening_denominations', 'closing_denominations', 'cash_count_details' ), true ) ) {
				$value = json_decode( $value, true );
			}

			$meta_data[ $key ] = $value;
		}

		$shift_data = array(
			'ID'      => $shift->ID,
			'title'   => $shift->post_title,
			'content' => $shift->post_content,
			'date'    => $shift->post_date,
		);

		return array_merge( $shift_data, $meta_data );
	}

	private function get_shift_sales_total( $shift_id ) {
		$payments = get_posts( [
			'post_type'   => 'payments',
			'post_status' => 'publish',
			'numberposts' => -1,
			'meta_query'  => [
				[
					'key'   => '_payment_shift_id',
					'value' => $shift_id,
				],
				[
					'key'     => '_payment_method',
					'value'   => 'Cash',
					'compare' => '='
				]
			],
		] );

		$total = 0;
		foreach ( $payments as $payment ) {
			$total += floatval( get_post_meta( $payment->ID, '_payment_amount', true ) );
		}

		return round( $total, 2 );
	}


	private function get_shift_payouts_total( $shift_id ) {
		$payouts = get_posts([
			'post_type'   => 'vendor_payments',
			'post_status' => 'publish',
			'numberposts' => -1,
			'meta_query'  => [
				[
					'key'   => '_payment_shift_id',
					'value' => $shift_id,
				],
				[
					'key'     => '_payment_method',
					'value'   => 'Cash',
					'compare' => '='
				]
			],
		]);

		$total = 0;
		foreach ( $payouts as $payout ) {
			$total += floatval( get_post_meta( $payout->ID, '_payment_amount', true ) );
		}
		return round( $total, 2 );
	}


}

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
class Pinaka_Shifts_Api_Controller {

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
	protected $rest_base = 'shifts';

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
			$this->rest_base . '/create-shift',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'shift_create_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/update-shift',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'shift_update_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-shifts-by-user',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_shifts_by_user_id_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);


		register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-shift-by-id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_shifts_by_id_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-all-shifts',
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_all_shifts_for_admin' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

				'args' => array(
					'page' => array(
						'description'       => 'Current page number',
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
					'per_page' => array(
						'description'       => 'Number of records per page',
						'type'              => 'integer',
						'default'           => 10,
						'sanitize_callback' => 'absint',
					),
					'search' => array(
						'description'       => 'Search by staff name or shift title',
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'status' => array(
						'description'       => 'Shift status (open or closed)',
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);


		
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

	public function shift_create_callback( WP_REST_Request $request ) {
		$logger      = wc_get_logger();
		$log_context = array( 'source' => 'pinaka-pos-shift-create' );

		// Get full request JSON
		$request_data = $request->get_json_params();

		// Get token from Authorization header
		$auth_header = $request->get_header( 'authorization' );
		$token       = '';

		if ( $auth_header && stripos( $auth_header, 'Bearer ' ) === 0 ) {
			$token = substr( $auth_header, 7 ); // Remove "Bearer "
		}

		// Log request + token
		$logger->info(
			'Logger → request data: ' . print_r( $request_data, true ) .
			' | Token: ' . $token,
			$log_context
		);	
		$data = $request->get_json_params();
				// Get current user early
		$user_id    = get_current_user_id();
		$first_name = get_user_meta($user_id, 'first_name', true);
		$last_name  = get_user_meta($user_id, 'last_name', true);

		// Check if user already has an OPEN shift
		$existing = get_posts([
			'post_type'      => 'shifts',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_query'     => [
				'relation' => 'AND',
				[
					'key'   => '_shift_assigned_staff',
					'value' => strval($user_id),
				],
				[
					'key'   => '_shift_status',
					'value' => 'open',
				],
			],
			'fields' => 'ids',
		]);

		if($data['status'] == 'open' && empty($existing)){
			// Validate consistency
			$calculated_drawer_total = 0;
			foreach ($data['drawer_denominations'] as $denom) {
				// echo $denom['denom'] . ' x ' . $denom['denom_count'] .' = ' . $denom['denom'] * $denom['denom_count'];
				// echo '||';
				$calculated_drawer_total += floatval($denom['denomination']) * intval($denom['denom_count']);
			}

			

			if (floatval($calculated_drawer_total) !== floatval($data['drawer_total_amount'])) {
				// return new WP_Error('invalid_data', 'Drawer total does not match denominations.', ['status' => 400]);
			}

			$calculated_tube_total = 0;
			foreach ($data['tube_denominations'] as $item) {
				if (!isset($item['denomination'], $item['tube_count'], $item['cell_count'], $item['total'])) {
					// return new WP_Error('invalid_data', 'Invalid tube denomination format.', ['status' => 400]);
				}
				$calculated = floatval($item['denomination']) * intval($item['tube_count']) * intval($item['cell_count']);
				if (floatval($item['total']) !== $calculated) {
					// return new WP_Error('invalid_data', 'Tube denomination total mismatch.', ['status' => 400]);
				}
				$calculated_tube_total += $calculated;
			}
			if (floatval($calculated_tube_total) !== floatval($data['tube_total_amount'])) {
				// return new WP_Error('invalid_data', 'Tube total does not match denominations.', ['status' => 400]);
			}

			$total_opening_balance = floatval($data['drawer_total_amount']) + floatval($data['tube_total_amount']);
			if (floatval($total_opening_balance) !== floatval($data['total_amount'])) {
				// return new WP_Error('invalid_data', 'Combined total amount mismatch.', ['status' => 400]);
			}

			$user_id    = get_current_user_id();
			$first_name = get_user_meta($user_id, 'first_name', true);
			$last_name  = get_user_meta($user_id, 'last_name', true);

			$post_id = wp_insert_post([
				'post_title'   => sanitize_text_field($data['title'] ?? 'Shift Start- ' . $first_name. ' ' .$last_name .' '. current_time('mysql')),
				'post_type'    => 'shifts',
				'post_status'  => 'publish',
			]);
			// $logger1 = wc_get_logger();
			// $log_context1 = array( 'source' => 'pinaka-pos-shift-open' );
			update_post_meta($post_id, '_shift_user_id', $user_id);
			update_post_meta($post_id, '_shift_start_time', sanitize_text_field($data['start_time'] ?? current_time('mysql')));
			update_post_meta($post_id, '_shift_assigned_staff', $user_id);
			update_post_meta($post_id, '_shift_status', 'open');
			update_post_meta($post_id, '_shift_start_notes', sanitize_textarea_field($data['shift_start_notes'] ?? ''));
			update_post_meta($post_id, '_shift_opening_balance', $total_opening_balance);
			add_post_meta($post_id, '_shift_prev_opening_balance', $total_opening_balance);

			update_post_meta($post_id, '_shift_drawer_denominations', wp_json_encode($data['drawer_denominations']));
			update_post_meta($post_id, '_shift_drawer_total', floatval($data['drawer_total_amount']));
			update_post_meta($post_id, '_shift_tube_denominations', wp_json_encode($data['tube_denominations']));
			update_post_meta($post_id, '_shift_tube_total', floatval($data['tube_total_amount']));
			
			return [
				'shift_id'        => $post_id,
				'user'            => trim($first_name . ' ' . $last_name),
				'total_amount'	  => $total_opening_balance,
				'status'          => 'open',
				'over_short'	  => 0,
			];
		}else if($data['status'] == 'open' && !empty($existing)){
			$data['shift_id'] = $existing[0];
			return $this->shift_update_callback( $data );
		}else {
			return $this->shift_update_callback( $data );
		}
	}
	public function shift_update_callback( $data ) {
		
		$post_id = intval( $data['shift_id'] );

		if ( ! $post_id || get_post_type( $post_id ) !== 'shifts' ) {
			return new WP_Error( 'invalid_shift', 'Invalid shift ID.', [ 'status' => 400 ] );
		}

		// --- Basic sanitization helpers ---
		$safe_float = function($v) {
			return is_numeric($v) ? floatval($v) : 0.0;
		};
		$safe_int = function($v) {
			return is_numeric($v) ? intval($v) : 0;
		};

		// --- Drawer total: calculate from denominations ---
		$calculated_drawer_total = 0.0;
		if ( ! empty( $data['drawer_denominations'] ) && is_array( $data['drawer_denominations'] ) ) {
			foreach ( $data['drawer_denominations'] as $denom ) {
				$denomination = $safe_float( $denom['denomination'] ?? 0 );
				$count = $safe_int( $denom['denom_count'] ?? 0 );
				$calculated_drawer_total += $denomination * $count;
			}
		}
		// If user supplied a drawer total, compare (optional)
		$provided_drawer_total = $safe_float( $data['drawer_total_amount'] ?? 0 );
		// If you want to enforce strict validation, uncomment the block below:
		/*
		if ( abs( $calculated_drawer_total - $provided_drawer_total ) > 0.01 ) {
			return new WP_Error( 'invalid_data', 'Drawer total mismatch.', [ 'status' => 400 ] );
		}
		*/
		// Prefer calculated value to avoid trusting the client
		$drawer_total = $calculated_drawer_total > 0 ? $calculated_drawer_total : $provided_drawer_total;

		// --- Tube total: calculate from provided tube_denominations ---
		$calculated_tube_total = 0.0;
		if ( ! empty( $data['tube_denominations'] ) && is_array( $data['tube_denominations'] ) ) {
			foreach ( $data['tube_denominations'] as $item ) {
				$denomination = $safe_float( $item['denomination'] ?? 0 );
				$tube_count   = $safe_int( $item['tube_count'] ?? 0 );
				$cell_count   = $safe_int( $item['cell_count'] ?? 0 );
				$calculated = $denomination * $tube_count * $cell_count;
				// optional per-item validation:
				// if ( isset($item['total']) && abs($safe_float($item['total']) - $calculated) > 0.01 ) { ... }
				$calculated_tube_total += $calculated;
			}
		}
		$provided_tube_total = $safe_float( $data['tube_total_amount'] ?? 0 );
		// prefer calculated tube total if it is non-zero
		$tube_total = $calculated_tube_total > 0 ? $calculated_tube_total : $provided_tube_total;
		
		// --- Combined total (drawer + tube) ---
		$combined_total = $data['total_amount'] ?? 0;
		// optional combined validation against client-provided total_amount
		$provided_total_amount = $safe_float( $data['total_amount'] ?? 0 );
		/*
		if ( abs( $combined_total - $provided_total_amount ) > 0.01 ) {
			return new WP_Error( 'invalid_data', 'Combined closing total mismatch.', [ 'status' => 400 ] );
		}
		*/

		// --- Update optional status meta ---
		if ( ! empty( $data['status'] ) ) {
			update_post_meta( $post_id, '_shift_status', sanitize_text_field( $data['status'] ) );
		}

		$status = isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : '';

		if ( $status === 'closed' ) { 
			// store closing notes if present
			if ( isset( $data['shift_closing_notes'] ) ) {
				update_post_meta( $post_id, '_shift_closing_notes', sanitize_text_field( $data['shift_closing_notes'] ) );
			}

			update_post_meta( $post_id, '_shift_closing_balance', $combined_total );

			update_post_meta( $post_id, '_shift_drawer_closing_denominations', wp_json_encode( $data['drawer_denominations'] ?? [] ) );
			update_post_meta( $post_id, '_shift_drawer_closing_total', $drawer_total );
			update_post_meta( $post_id, '_shift_tube_closing_denominations', wp_json_encode( $data['tube_denominations'] ?? [] ) );
			update_post_meta( $post_id, '_shift_tube_closing_total', $tube_total );

			// set end time (if provided, sanitized; otherwise use current time)
			update_post_meta( $post_id, '_shift_end_time', sanitize_text_field( $data['end_time'] ?? current_time( 'mysql' ) ) );

			update_post_meta( $post_id, '_shift_status', 'closed' );


			// --- Safe drop (optional) ---
			$safe_drops = $this->get_shift_safe_drops( $post_id );
			$safe_drop_total = $safe_float( $safe_drops['total_safe_drop'] ?? 0 );

			// --- Calculations (ensure opening_balance is read from meta after any update above) ---
			$opening_balance = get_post_meta( $post_id, '_shift_opening_balance', true );
			// NOTE: ensure get_shift_sales_total returns ONLY cash-paid orders (important)
			$total_sales   = $this->get_shift_sales_total( $post_id );
			$vendor_payouts = $this->get_shift_vendor_payouts_total( $post_id );
			$total_payouts = $vendor_payouts['total_vendor_payments'] ?? 0;
			
			// expected cash per your rule:
			// expected_cash = opening bal + order amount by cash - any payments to vendor - safe drop
			$expected_cash = $opening_balance + $total_sales - $total_payouts - $safe_drop_total;
			// difference between counted/combined_total and expected cash
			$over_short = $combined_total - $expected_cash;

			// separate over and short for clarity (one will be zero)
			$over_amount  = $over_short > 0 ? $over_short : 0.0;
			$short_amount = $over_short < 0 ? abs( $over_short ) : 0.0;

			// store results
			update_post_meta( $post_id, '_shift_total_sales', $total_sales );
			update_post_meta( $post_id, '_shift_total_payouts', $total_payouts );
			update_post_meta( $post_id, '_shift_over_short', $over_short );
			update_post_meta( $post_id, '_shift_over_amount', $over_amount );
			update_post_meta( $post_id, '_shift_short_amount', $short_amount );

			// who updated
			$user_id    = get_current_user_id();
			$first_name = get_user_meta( $user_id, 'first_name', true );
			$last_name  = get_user_meta( $user_id, 'last_name', true );


			// --- Return a clear response containing both over and short --- 
			return [
				'shift_id'        => $post_id,
				'user'			  => trim($first_name . ' ' . $last_name),
				'total_amount'	  => $combined_total,
				'status'          => get_post_meta( $post_id, '_shift_status', true ),
				'over_short'      => floor($over_short),
			];

		} else {

			// --- Opening / interim update path ---
			// Append the current combined total into prev list (history)
			// update_post_meta($post_id, '_shift_opening_balance', $combined_total);
			
			// store denominations / totals
			update_post_meta( $post_id, '_shift_drawer_denominations', wp_json_encode( $data['drawer_denominations'] ?? [] ) );
			update_post_meta( $post_id, '_shift_drawer_total', $drawer_total );
			update_post_meta( $post_id, '_shift_tube_denominations', wp_json_encode( $data['tube_denominations'] ?? [] ) );
			update_post_meta( $post_id, '_shift_tube_total', $tube_total );

			// --- Safe drop (optional) ---
			$safe_drops = $this->get_shift_safe_drops( $post_id );
			$safe_drop_total = $safe_float( $safe_drops['total_safe_drop'] ?? 0 );

			// --- Calculations (ensure opening_balance is read from meta after any update above) ---
			$opening_balance = get_post_meta( $post_id, '_shift_opening_balance', true );

			// NOTE: ensure get_shift_sales_total returns ONLY cash-paid orders (important)
			$total_sales   = $this->get_shift_sales_total( $post_id );
			$vendor_payouts = $this->get_shift_vendor_payouts_total( $post_id );
			$total_payouts = $vendor_payouts['total_vendor_payments'] ?? 0;

			// expected cash per your rule:
			// expected_cash = opening bal + order amount by cash - any payments to vendor - safe drop			
			$expected_cash =  $opening_balance + $total_sales - $total_payouts - $safe_drop_total;
			// difference between counted/combined_total and expected cash
			$over_short = $combined_total - $expected_cash;
			$logger      = wc_get_logger();
			$log_context = array( 'source' => 'Expected Cash' );
			$logger->info(
				'Logger → Cobmined: ' .$combined_total. 
				' Expected Cash '. $expected_cash. 
				'Safe Drop: ' . $safe_drop_total .
				' | Total Sales: ' . $total_sales .
				' | Total Payouts: ' . $total_payouts .
				' | Over Short: ' . $over_short,
				$log_context
			);	

			// separate over and short for clarity (one will be zero)
			$over_amount  = $over_short > 0 ? $over_short : 0.0;
			$short_amount = $over_short < 0 ? abs( $over_short ) : 0.0;

			// store results
			update_post_meta( $post_id, '_shift_total_sales', $total_sales );
			update_post_meta( $post_id, '_shift_total_payouts', $total_payouts );
			update_post_meta( $post_id, '_shift_over_short', $over_short );
			update_post_meta( $post_id, '_shift_over_amount', $over_amount );
			update_post_meta( $post_id, '_shift_short_amount', $short_amount );

			// who updated
			$user_id    = get_current_user_id();
			$first_name = get_user_meta( $user_id, 'first_name', true );
			$last_name  = get_user_meta( $user_id, 'last_name', true );

			// --- Return a clear response containing both over and short --- 
			return [
				'shift_id'        => $post_id,
				'user'			  => trim($first_name . ' ' . $last_name),
				'total_amount'	  => $combined_total,
				'status'          => get_post_meta( $post_id, '_shift_status', true ),
				'over_short'      => floor($over_short)
			];
		}
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
			'numberposts' => -1,
			'meta_query'  => array(
				array(
					'key'     => '_shift_user_id',
					'value'   => $user_id,
					'compare' => '='
				)
			),
			'date_query'  => array(
				array(
					'after'     => date( 'Y-m-d', strtotime( '-7 days' ) ),
					'inclusive' => true,
				)
			),
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
			'status', 'user_id', 'opening_denominations', 'closing_denominations', 'over_short', 'shift_updated_balance'
		);

		$sales_info = $this->get_shift_no_of_sales( $shift->ID );

		// Calculate total sales amount
		$total_sales = count( $sales_info );

		$total_sale_amount = 0;
		foreach ($sales_info as $sale) {
			$total_sale_amount += floatval($sale['total']);
		}


		$safe_drops = $this->get_shift_safe_drops( $shift->ID );
		
		$vendor_payouts = $this->get_shift_vendor_payouts_total( $shift->ID );
	
		$shift_updated = get_post_meta( $shift->ID, '_shift_updated', true );
		$opening_balance = floatval( get_post_meta( $shift->ID, '_shift_opening_balance', true ) );
		$updated_balance = floatval( get_post_meta( $shift->ID, '_shift_updated_balance', true ) );
		$start_time = get_post_meta( $shift->ID, '_shift_start_time', true );

		$end_time = get_post_meta( $shift->ID, '_shift_end_time', true );
		$shift_status = get_post_meta( $shift->ID, '_shift_status', true ) ?: 'open';
		$over_short = get_post_meta( $shift->ID, '_shift_over_short', true ) ?: 0;
		$openin_or_updated_bal = ($shift_updated == 'shift_updated') ? floatval($updated_balance) : $opening_balance;
		return array(
			'shift_id'                => $shift->ID,
			'title'                   => $shift->post_title,
			'user_id'                 => intval( get_post_meta( $shift->ID, '_shift_user_id', true ) ),
			'user_name'               => get_the_author_meta( 'display_name', get_post_meta( $shift->ID, '_shift_user_id', true ) ),
			'assigned_staff'          => intval( get_post_meta( $shift->ID, '_shift_assigned_staff', true ) ),
			'start_time'              => $start_time,
			'end_time'                => $end_time,
			'total_sales'             => $total_sales ?? 0,
			'total_sale_amount'       => round($total_sale_amount, 2) ?? 0,
			'safe_drop_total'         => floatval( @$safe_drops['total_safe_drop'] ) ?? 0,
			'safe_drops'              => $safe_drops['safe_drops'] ?? array(),
			'vendor_payouts'    	  => $vendor_payouts['vendor_payments'] ?? array(),
			'total_vendor_payments'	  => $vendor_payouts['total_vendor_payments'] ?? 0,
			'opening_balance'         => round( $openin_or_updated_bal, 2 ) ?? 0,
			'closing_balance'         => round( floatval( get_post_meta( $shift->ID, '_shift_closing_balance', true ) ), 2 ) ?? 0,
			'notes'                   => sanitize_textarea_field( get_post_meta( $shift->ID, '_shift_start_notes', true ) ) ?? '',
			'shift_closing_notes'     => sanitize_textarea_field( get_post_meta( $shift->ID, '_shift_closing_notes', true ) )?? '',
			'shift_status'			  => $shift_status,
			'over_short'			  => floor($over_short)
		);
	}

	// function get_total_
	/**
	 * Get the total sales amount for a given shift.
	 *
	 * @param int $shift_id The ID of the shift.
	 * @return float The total sales amount.
	 */
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
			$amount  = floatval( get_post_meta( $payment->ID, '_payment_amount', true ) );
			$change  = floatval( get_post_meta( $payment->ID, '_pos_change_returned', true ) );

			$total += ( $amount - $change );
		}


		return round( $total, 2 );
	}


	/**
	 * Get the full order data for a given shift.
	 *
	 * @param int $shift_id The ID of the shift.
	 * @return array The order data array.
	 */
	public function get_shift_no_of_sales( $shift_id ) {
		if ( ! $shift_id ) {
			return [];
		}

		$query = new \WC_Order_Query([
			'limit'      => -1, // get all results
			'status'     => array('wc-completed'), // or remove to get all
			'meta_query' => [
				[
					'key'     => 'shift_id',
					'value'   => $shift_id,
					'compare' => '=',
				],
			],
		]);

		$orders = $query->get_orders();

		$order_data = [];

		foreach ( $orders as $order ) {
			$order_data[] = [
				'id'            => $order->get_id(),
				'status'        => $order->get_status(),
				'date_created'  => $order->get_date_created()->date('Y-m-d H:i:s'),
				'total'         => $order->get_total(),
				'payment_method'=> $order->get_payment_method_title(),
				'billing_name'  => $order->get_formatted_billing_full_name(),
				'line_items'    => array_map( function( $item ) {
					return [
						'name'     => $item->get_name(),
						'qty'      => $item->get_quantity(),
						'total'    => $item->get_total(),
						'subtotal' => $item->get_subtotal(),
					];
				}, $order->get_items() ),
			];
		}

		return $order_data;
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
			// print_r($time->post_date);
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
			$vendor_id = get_post_meta($payout->ID, '_vendor_id', true);
			$results['vendor_payments'][] = [
				'id'        => $payout->ID,
				'amount'    => floatval(get_post_meta($payout->ID, '_vendor_payment_amount', true)),
				'note'      => get_post_meta($payout->ID, '_vendor_payment_notes', true),
				'payment_method' => get_post_meta($payout->ID, '_vendor_payment_method', true),
				'time'      => get_post($payout->ID, 'post_date', true)->post_date,
				'vendor_name' => get_post($vendor_id, '_vendor_id', true)->post_title,
				'service_type'	 => get_post_meta($payout->ID, '_vendor_payment_service_type', true),
				'vendor_id' => $vendor_id,
			];
			$results['total_vendor_payments'] = $total_vendor_payouts;
		}
		return $results;
	}

	private function get_vendor_cash_payouts( $shift_id ) {
		$payouts = get_posts([
			'post_type'   => 'vendor_payments',
			'post_status' => 'publish',
			'numberposts' => -1,
			'meta_query'  => [
				[
					'key'   => '_vendor_payment_shift_id',
					'value' => $shift_id,
				],
				[
					'key'     => '_vendor_payment_method',
					'value'   => 'Cash',
					'compare' => '='
				],
			],
		]);

		$total = 0;
		foreach ( $payouts as $payout ) {
			$total += floatval( get_post_meta( $payout->ID, '_vendor_payment_amount', true ) );
		}

		return round( $total, 2 );
	}

	public function get_all_shifts_for_admin( WP_REST_Request $request ) {
		$page   = max( 1, (int) $request->get_param( 'page' ) );
		$limit  = max( 10, (int) $request->get_param( 'per_page' ) );
		$search = sanitize_text_field( $request->get_param( 'search' ) );
		$status = sanitize_text_field( $request->get_param( 'status' ) );

		$args = array(
			'post_type'      => 'shifts',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		// 🔍 Search by title / staff name
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		// 🟢 Shift status filter (open / closed)
		if ( ! empty( $status ) ) {
			$args['meta_query'] = array(
				array(
					'key'     => 'shift_status',
					'value'   => $status,
					'compare' => '=',
				),
			);
		}

		$query = new WP_Query( $args );

		$results = array();
		if ( $query->have_posts() ) {
			foreach ( $query->posts as $shift ) {
				$results[] = $this->prepare_shift_data( $shift );
			}
		}

		return rest_ensure_response( array(
			'data'       => $results,
			'pagination' => array(
				'page'       => $page,
				'per_page'   => $limit,
				'total'      => (int) $query->found_posts,
				'total_page' => (int) $query->max_num_pages,
				'has_more'   => $page < $query->max_num_pages,
			),
		) );
	}

}

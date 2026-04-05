<?php

namespace PinakaPosWp\Utilities;

defined( 'ABSPATH' ) || exit;

/**
 * Class Reports
 *
 * @since 1.0.0
 * @package PinakaPosWp\Utilities
 */
class ReportsUtil {

	/**
	 * Retrieves key/label pairs of date filter options for use in a drop-down.
	 *
	 * @since 1.1.6
	 * @return array Key/label pairs of date filter options.
	 */
	public static function get_dates_filter_options() {
		$options = array(
			'today'        => __( 'Today', 'wp-ever-accounting' ),
			'yesterday'    => __( 'Yesterday', 'wp-ever-accounting' ),
			'this_week'    => __( 'This Week', 'wp-ever-accounting' ),
			'last_week'    => __( 'Last Week', 'wp-ever-accounting' ),
			'last_30_days' => __( 'Last 30 Days', 'wp-ever-accounting' ),
			'this_month'   => __( 'This Month', 'wp-ever-accounting' ),
			'last_month'   => __( 'Last Month', 'wp-ever-accounting' ),
			'this_quarter' => __( 'This Quarter', 'wp-ever-accounting' ),
			'last_quarter' => __( 'Last Quarter', 'wp-ever-accounting' ),
			'this_year'    => __( 'This Year', 'wp-ever-accounting' ),
			'last_year'    => __( 'Last Year', 'wp-ever-accounting' ),
			'custom'       => __( 'Custom', 'wp-ever-accounting' ),
		);

		return apply_filters( 'eac_dates_filter_options', $options );
	}

	/**
	 * Parse date range from date filter.
	 *
	 * @param string $date_filter Date filter.
	 *
	 * @since 1.1.6
	 * @return array
	 */
	public static function parse_date_range_filter( $date_filter = '' ) {
		switch ( $date_filter ) {
			case 'yesterday':
				$range['start_date'] = wp_date( 'Y-m-d', strtotime( 'yesterday' ) );
				$range['end_date']   = wp_date( 'Y-m-d', strtotime( 'yesterday' ) );
				break;
			case 'this_week':
				$range['start_date'] = wp_date( 'Y-m-d', strtotime( 'this week' ) );
				$range['end_date']   = wp_date( 'Y-m-d' );
				break;
			case 'last_week':
				$range['start_date'] = wp_date( 'Y-m-d', strtotime( 'last week' ) );
				$range['end_date']   = wp_date( 'Y-m-d', strtotime( 'last week' ) );
				break;
			case 'last_30_days':
				$range['start_date'] = wp_date( 'Y-m-d', strtotime( '-30 days' ) );
				$range['end_date']   = wp_date( 'Y-m-d' );
				break;
			case 'last_month':
				$range['start_date'] = wp_date( 'Y-m-01', strtotime( 'last month' ) );
				$range['end_date']   = wp_date( 'Y-m-t', strtotime( 'last month' ) );
				break;
			case 'this_quarter':
				$range['start_date'] = wp_date( 'Y-m-01', strtotime( 'first day of this quarter' ) );
				$range['end_date']   = wp_date( 'Y-m-d' );
				break;
			case 'last_quarter':
				$range['start_date'] = wp_date( 'Y-m-01', strtotime( 'first day of last quarter' ) );
				$range['end_date']   = wp_date( 'Y-m-t', strtotime( 'last day of last quarter' ) );
				break;

			case 'this_year':
				$range['start_date'] = wp_date( 'Y-01-01' );
				$range['end_date']   = wp_date( 'Y-m-d' );
				break;
			case 'last_year':
				$range['start_date'] = wp_date( 'Y-01-01', strtotime( 'last year' ) );
				$range['end_date']   = wp_date( 'Y-12-31', strtotime( 'last year' ) );
				break;

			case 'this_month':
			default:
				$range['start_date'] = wp_date( 'Y-m-01' );
				$range['end_date']   = wp_date( 'Y-m-d' );
				break;
		}

		return $range;
	}

	/**
	 * Get financial start date.
	 *
	 * @param string $year Year.
	 *
	 * @since 1.1.6
	 * @return string
	 */
	public static function get_year_start_date( $year = '' ) {
		if ( empty( $year ) ) {
			$year = wp_date( 'Y' );
		}

		$year_start = get_option( 'eac_year_start_date', '01-01' );
		$dates      = explode( '-', $year_start );
		$month      = ! empty( $dates[0] ) ? $dates[0] : '01';
		$day        = ! empty( $dates[1] ) ? $dates[1] : '01';
		$year       = empty( $year ) ? (int) wp_date( 'Y' ) : absint( $year );

		return wp_date( 'Y-m-d', mktime( 0, 0, 0, $month, $day, $year ) );
	}

	/**
	 * Get financial end date.
	 *
	 * @param string $year Year.
	 *
	 * @since 1.1.6
	 * @return string
	 */
	public static function get_year_end_date( $year = '' ) {
		if ( empty( $year ) ) {
			$year = wp_date( 'Y' );
		}

		$start_date = self::get_year_start_date( $year );
		// if the year is current year, then end date is today.

		if ( wp_date( 'Y' ) === $year ) {
			return wp_date( 'Y-m-d' );
		}

		return wp_date( 'Y-m-d', strtotime( $start_date . ' +1 year -1 day' ) );
	}


	/**
	 * Get months in range.
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 * @param string $format Format.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_months_in_range( $start_date, $end_date, $format = 'F,y' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$months = array();
		$start  = new \DateTime( $start_date );
		$end    = new \DateTime( $end_date );
		while ( $start <= $end ) {
			$months[] = wp_date( 'M, y', strtotime( $start->format( 'Y-m-01' ) ) );
			$start->modify( 'first day of next month' );
		}

		return $months;
	}

	/**
	 * Get date range.
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_dates_range( $start_date, $end_date ) {
		$dates    = array();
		$start    = new \DateTime( $start_date );
		$end      = new \DateTime( $end_date );
		$interval = \DateInterval::createFromDateString( '1 day' );
		$period   = new \DatePeriod( $start, $interval, $end );
		foreach ( $period as $date ) {
			$dates[] = $date->format( 'Y-m-d' );
		}

		return $dates;
	}

	/**
	 * Get the comparison date start and end date based on the start and end date.
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 *
	 * @return array
	 */
	public static function get_comparison_dates( $start_date, $end_date ) {
		// first check the date gap between start and end date. then set the previous start and end date.
		$gap = count( self::get_dates_range( $start_date, $end_date ) );
		// if the gap is less than 7 days, then set the previous start and end date as 7 days before.
		if ( $gap <= 7 ) {
			$start = wp_date( 'Y-m-d', strtotime( $start_date . ' -7 days' ) );
			$end   = wp_date( 'Y-m-d', strtotime( $end_date . ' -7 days' ) );
		} elseif ( $gap <= 30 ) {
			$start = wp_date( 'Y-m-d', strtotime( $start_date . ' -1 month' ) );
			$end   = wp_date( 'Y-m-d', strtotime( $end_date . ' -1 month' ) );
		} elseif ( $gap <= 90 ) {
			$start = wp_date( 'Y-m-d', strtotime( $start_date . ' -3 months' ) );
			$end   = wp_date( 'Y-m-d', strtotime( $end_date . ' -3 months' ) );
		} elseif ( $gap <= 180 ) {
			$start = wp_date( 'Y-m-d', strtotime( $start_date . ' -6 months' ) );
			$end   = wp_date( 'Y-m-d', strtotime( $end_date . ' -6 months' ) );
		} else {
			$start = wp_date( 'Y-m-d', strtotime( $start_date . ' -1 year' ) );
			$end   = wp_date( 'Y-m-d', strtotime( $end_date . ' -1 year' ) );
		}

		return array(
			'start' => $start,
			'end'   => $end,
		);
	}

	/**
	 * Get reporting colors.
	 * Get some colors for reporting charts.
	 *
	 * @param string $key Key.
	 *
	 * @since 1.1.6
	 * @return array
	 */
	public static function get_random_color( $key = null ) {
		static $picked = array();
		// 20 colors from Google charts.
		$colors = apply_filters(
			'eac_report_colors',
			array(
				'#3366cc',
				'#dc3912',
				'#ff9900',
				'#109618',
				'#990099',
				'#0099c6',
				'#dd4477',
				'#66aa00',
				'#b82e2e',
				'#316395',
				'#994499',
				'#22aa99',
				'#aaaa11',
				'#6633cc',
				'#e67300',
				'#8b0707',
				'#651067',
				'#329262',
				'#5574a6',
				'#3b3eac',
				'#b77322',
				'#16d620',
				'#b91383',
				'#f4359e',
				'#9c5935',
				'#a9c413',
				'#2a778d',
				'#668d1c',
				'#bea413',
				'#0c5922',
				'#743411',
			)
		);

		if ( ! empty( $key ) ) {
			if ( ! isset( $picked[ $key ] ) ) {
				$picked[ $key ] = $colors[ array_rand( $colors ) ];
			}

			return $picked[ $key ];
		}

		return $colors[ array_rand( $colors ) ];
	}

	/**
	 * Get Sales report based on year.
	 *
	 * @param int  $year Year.
	 * @param bool $force Force to get report from database.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_payments_report( $year = null, $force = false ) {
		global $wpdb;
		$reports     = get_transient( 'eac_payments_report' );
		$reports     = ! is_array( $reports ) ? array() : $reports;
		$year        = empty( $year ) ? wp_date( 'Y' ) : $year;
		$start_date  = self::get_year_start_date( $year );
		$end_date    = self::get_year_end_date( $year );
		$date_format = 'M, y';

		if ( $force || empty( $reports[ $year ] ) ) {
			$transactions = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT (t.amount/t.exchange_rate) amount, MONTH(t.payment_date) AS month, YEAR(t.payment_date) AS year, t.category_id
					FROM {$wpdb->prefix}ea_transactions AS t
					LEFT JOIN {$wpdb->prefix}ea_transfers AS it ON t.id = it.payment_id OR t.id = it.expense_id
					WHERE t.type = 'payment'
					AND it.payment_id IS NULL
					AND it.expense_id IS NULL
					AND t.payment_date BETWEEN %s AND %s
					ORDER BY t.payment_date ASC",
					$start_date,
					$end_date
				)
			);
			$months       = array_fill_keys( self::get_months_in_range( $start_date, $end_date, $date_format ), 0 );
			$month_count  = count( $months );
			$date_count   = count( self::get_dates_range( $start_date, $end_date ) );
			$data         = array(
				'total_amount' => 0,
				'total_count'  => 0,
				'daily_avg'    => 0,
				'month_avg'    => 0,
				'date_count'   => $date_count,
				'months'       => $months,
				'categories'   => array(),
			);
			foreach ( $transactions as $transaction ) {
				$trans_year  = $transaction->year;
				$month       = $transaction->month;
				$category_id = $transaction->category_id;
				$amount      = round( $transaction->amount, 2 );
				$month_year  = wp_date( $date_format, strtotime( $trans_year . '-' . $month . '-01' ) );

				// Total.
				$data['total_amount'] += round( $amount, 2 );
				++$data['total_count'];

				// months.
				if ( ! isset( $data['months'][ $month_year ] ) ) {
					$data['months'] = $months;
				}
				$data['months'][ $month_year ] += round( $amount, 2 );

				// Categories.
				if ( ! isset( $data['categories'][ $category_id ] ) ) {
					$data['categories'][ $category_id ] = $months;
				}
				$data['categories'][ $category_id ][ $month_year ] += round( $amount, 2 );
			}

			// Average daily.
			if ( $date_count > 0 && $data['total_amount'] > 0 ) {
				$data['daily_avg'] = round( $data['total_amount'] / $date_count, 2 );
			}
			// Average months.
			if ( $data['total_amount'] > 0 && $month_count > 0 ) {
				$data['month_avg'] = round( $data['total_amount'] / $month_count, 2 );
			}

			$reports[ $year ] = apply_filters( 'eac_payments_report', $data, $year );
			// Cache for 1 hour.
			set_transient( 'eac_payments_report', $reports, HOUR_IN_SECONDS );
		}

		return $reports[ $year ];
	}

	/**
	 * Get Purchase report based on year.
	 *
	 * @param int  $year Year.
	 * @param bool $force Force to get report from database.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_expenses_report( $year = null, $force = false ) {
		global $wpdb;
		$reports     = get_transient( 'get_expenses_report' );
		$reports     = ! is_array( $reports ) ? array() : $reports;
		$year        = empty( $year ) ? wp_date( 'Y' ) : $year;
		$start_date  = self::get_year_start_date( $year );
		$end_date    = self::get_year_end_date( $year );
		$date_format = 'M, y';

		if ( $force || ! isset( $reports[ $year ] ) ) {
			$transactions = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT (t.amount/t.exchange_rate) amount, MONTH(t.payment_date) AS month, YEAR(t.payment_date) AS year, t.category_id
					FROM {$wpdb->prefix}ea_transactions AS t
					LEFT JOIN {$wpdb->prefix}ea_transfers AS it ON t.id = it.payment_id OR t.id = it.expense_id
					WHERE t.type = 'expense'
					AND it.payment_id IS NULL
					AND it.expense_id IS NULL
					AND t.payment_date BETWEEN %s AND %s
					ORDER BY t.payment_date ASC",
					$start_date,
					$end_date
				)
			);

			$months      = array_fill_keys( self::get_months_in_range( $start_date, $end_date, $date_format ), 0 );
			$month_count = count( $months );
			$date_count  = count( self::get_dates_range( $start_date, $end_date ) );
			$data        = array(
				'total_amount' => 0,
				'total_count'  => 0,
				'daily_avg'    => 0,
				'month_avg'    => 0,
				'date_count'   => $date_count,
				'months'       => $months,
				'categories'   => array(),
			);
			foreach ( $transactions as $transaction ) {
				$trans_year  = $transaction->year;
				$month       = $transaction->month;
				$category_id = $transaction->category_id;
				$amount      = round( $transaction->amount, 2 );
				$month_year  = wp_date( $date_format, strtotime( $trans_year . '-' . $month . '-01' ) );

				// Total.
				$data['total_amount'] += round( $amount, 2 );
				++$data['total_count'];

				// months.
				if ( ! isset( $data['months'][ $month_year ] ) ) {
					$data['months'] = $months;
				}
				$data['months'][ $month_year ] += round( $amount, 2 );

				// Categories.
				if ( ! isset( $data['categories'][ $category_id ] ) ) {
					$data['categories'][ $category_id ] = $months;
				}
				$data['categories'][ $category_id ][ $month_year ] += round( $amount, 2 );
			}

			// Average daily.
			if ( $date_count > 0 && $data['total_amount'] > 0 ) {
				$data['daily_avg'] = round( $data['total_amount'] / $date_count, 2 );
			}
			// Average months.
			if ( $data['total_amount'] > 0 && $month_count > 0 ) {
				$data['month_avg'] = round( $data['total_amount'] / $month_count, 2 );
			}

			$reports[ $year ] = apply_filters( 'eac_expenses_report', $data, $year );
			// Cache for 1 hour.
			set_transient( 'eac_expenses_report', $reports, HOUR_IN_SECONDS );
		}

		return $reports[ $year ];
	}

	/**
	 * Get Profit report based on year.
	 *
	 * @param int  $year Year.
	 * @param bool $force Force to get report from database.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_profits_report( $year = null, $force = true ) {
		global $wpdb;
		$reports     = get_transient( 'get_profits_report' );
		$reports     = ! is_array( $reports ) ? array() : $reports;
		$year        = empty( $year ) ? wp_date( 'Y' ) : $year;
		$start_date  = self::get_year_start_date( $year );
		$end_date    = self::get_year_end_date( $year );
		$date_format = 'M, y';
		if ( $force || ! isset( $reports[ $year ] ) ) {
			$transactions = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT (t.amount/t.exchange_rate) amount, MONTH(t.payment_date) AS month, YEAR(t.payment_date) AS year, t.category_id, t.type
					FROM {$wpdb->prefix}ea_transactions AS t
					LEFT JOIN {$wpdb->prefix}ea_transfers AS it ON t.id = it.payment_id OR t.id = it.expense_id
					WHERE it.payment_id IS NULL
					AND it.expense_id IS NULL
					AND t.payment_date BETWEEN %s AND %s
					ORDER BY t.payment_date ASC",
					$start_date,
					$end_date
				)
			);

			$months      = array_fill_keys( self::get_months_in_range( $start_date, $end_date, $date_format ), 0 );
			$month_count = count( $months );
			$date_count  = count( self::get_dates_range( $start_date, $end_date ) );
			$data        = array(
				'total_profit' => 0,
				'total_count'  => 0,
				'daily_avg'    => 0,
				'month_avg'    => 0,
				'date_count'   => $date_count,
				'payments'     => $months,
				'expenses'     => $months,
				'profits'      => $months,
			);

			foreach ( $transactions as $transaction ) {
				$type       = $transaction->type;
				$trans_year = $transaction->year;
				$month      = $transaction->month;
				$amount     = round( $transaction->amount, 2 );
				$month_year = wp_date( $date_format, strtotime( $trans_year . '-' . $month . '-01' ) );

				// total count.
				++$data['total_count'];

				// Now based on type add or subtract.
				if ( 'payment' === $type ) {
					$data['total_profit']            += round( $amount, 2 );
					$data['payments'][ $month_year ] += round( $amount, 2 );
					$data['profits'][ $month_year ]  += round( $amount, 2 );
				} else {
					$data['total_profit']            -= round( $amount, 2 );
					$data['expenses'][ $month_year ] += round( $amount, 2 );
					$data['profits'][ $month_year ]  -= round( $amount, 2 );
				}
			}

			// Average daily.
			if ( $date_count > 0 && $data['total_profit'] > 0 ) {
				$data['daily_avg'] = round( $data['total_profit'] / $date_count, 2 );
			}

			// Average months.
			if ( $month_count > 0 && $data['total_profit'] > 0 ) {
				$data['month_avg'] = round( $data['total_profit'] / $month_count, 2 );
			}

			$reports[ $year ] = apply_filters( 'eac_profits_report', $data, $year );
			// Cache for 1 hour.
			set_transient( 'eac_profits_report', $reports, HOUR_IN_SECONDS );
		}

		return $reports[ $year ];
	}

	/**
	 * Generate chart data.
	 *
	 * @param array  $data Data.
	 * @param int    $year Year.
	 * @param string $date_format Date format.
	 *
	 * @since 1.0.0
	 * @return array Chart data.
	 */
	public static function annualize_data( $data, $year = null, $date_format = 'M, y' ) {
		$year       = empty( $year ) ? wp_date( 'Y' ) : absint( $year );
		$start_date = EAC()->business->get_year_start_date( $year );
		$end_date   = EAC()->business->get_year_end_date( $year );
		$months     = array_fill_keys( self::get_months_in_range( $start_date, $end_date, $date_format ), 0 );
		foreach ( $data as $datum ) {
			$datum = get_object_vars( $datum );
			$datum = wp_parse_args(
				$datum,
				array(
					'month'  => 0,
					'year'   => 0,
					'amount' => 0,
				)
			);

			// month and year must be set.
			if ( ! $datum['month'] || ! $datum['year'] || absint( $datum['year'] ) !== absint( $year ) ) {
				continue;
			}
			$months[ wp_date( 'M, y', mktime( 0, 0, 0, $datum['month'], 1, $datum['year'] ) ) ] = $datum['amount'];
		}

		return $months;
	}
}

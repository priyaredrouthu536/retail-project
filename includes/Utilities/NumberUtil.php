<?php

namespace PinakaPosWp\Utilities;

defined( 'ABSPATH' ) || exit;

/**
 * Class NumberUtil
 *
 * @since 1.0.0
 * @package PinakaPosWp\Utilities
 */
class NumberUtil {

	/**
	 * Round a number using the built-in `round` function.
	 *
	 * @param mixed $val The value to round.
	 * @param int   $precision The optional number of decimal digits to round to.
	 * @param int   $mode A constant to specify the mode in which rounding occurs.
	 *
	 * @return float The value rounded to the given precision as a float, or the supplied default value.
	 */
	public static function round( $val, $precision = 0, $mode = PHP_ROUND_HALF_UP ) {
		if ( ! is_numeric( $val ) ) {
			$val = floatval( $val );
		}
		return round( $val, $precision, $mode );
	}
}

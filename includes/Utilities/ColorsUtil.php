<?php

namespace PinakaPosWp\Utilities;

defined( 'ABSPATH' ) || exit;

/**
 * Class ColorsUtil
 *
 * @package PinakaPosWp\Utilities
 * @since   1.0.0
 */
class ColorsUtil {

	/**
	 * Make HEX color darker.
	 *
	 * @param mixed $color  Color.
	 * @param int   $factor Darker factor.
	 *                      Defaults to 30.
	 * @return string
	 */
	public static function darken( $color, $factor = 30 ) {
		$base  = wc_rgb_from_hex( $color );
		$color = '#';

		foreach ( $base as $k => $v ) {
			$amount      = $v / 100;
			$amount      = NumberUtil::round( $amount * $factor );
			$new_decimal = $v - $amount;

			$new_hex_component = dechex( $new_decimal );
			if ( strlen( $new_hex_component ) < 2 ) {
				$new_hex_component = '0' . $new_hex_component;
			}
			$color .= $new_hex_component;
		}

		return $color;
	}

	/**
	 * Make HEX color lighter.
	 *
	 * @param mixed $color  Color.
	 * @param int   $factor Lighter factor.
	 *                      Defaults to 30.
	 * @return string
	 */
	public static function lighten( $color, $factor = 30 ) {
		$base  = wc_rgb_from_hex( $color );
		$color = '#';

		foreach ( $base as $k => $v ) {
			$amount      = 255 - $v;
			$amount      = $amount / 100;
			$amount      = NumberUtil::round( $amount * $factor );
			$new_decimal = $v + $amount;

			$new_hex_component = dechex( $new_decimal );
			if ( strlen( $new_hex_component ) < 2 ) {
				$new_hex_component = '0' . $new_hex_component;
			}
			$color .= $new_hex_component;
		}

		return $color;
	}

	/**
	 * Determine whether a hex color is light.
	 *
	 * @param mixed $color Color.
	 * @return bool  True if a light color.
	 */
	public static function is_lighter( $color ) {
		$hex = str_replace( '#', '', $color ?? '' );

		$c_r = hexdec( substr( $hex, 0, 2 ) );
		$c_g = hexdec( substr( $hex, 2, 2 ) );
		$c_b = hexdec( substr( $hex, 4, 2 ) );

		$brightness = ( ( $c_r * 299 ) + ( $c_g * 587 ) + ( $c_b * 114 ) ) / 1000;

		return $brightness > 155;
	}


	/**
	 * Detect if we should use a light or dark color on a background color.
	 *
	 * @param mixed  $color Color.
	 * @param string $dark  Darkest reference.
	 *                      Defaults to '#000000'.
	 * @param string $light Lightest reference.
	 *                      Defaults to '#FFFFFF'.
	 * @return string
	 */
	public static function light_or_dark( $color, $dark = '#000000', $light = '#FFFFFF' ) {
		return self::is_lighter( $color ) ? $dark : $light;
	}
}

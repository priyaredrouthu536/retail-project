<?php
// ===============================
// General settings
// ===============================
function pinaka_pos_register_settings() {
    // General Settings
    register_setting('pinaka_pos_general_settings', 'pinaka_pos_name');
    register_setting('pinaka_pos_general_settings', 'pinaka_pos_email');
    register_setting('pinaka_pos_general_settings', 'pinaka_pos_phone');
    register_setting('pinaka_pos_general_settings', 'pinaka_pos_logo');

    register_setting('pinaka_pos_general_settings', 'pinaka_pos_business_address');
    register_setting('pinaka_pos_general_settings', 'pinaka_pos_business_city');
    register_setting('pinaka_pos_general_settings', 'pinaka_pos_business_state');
    register_setting('pinaka_pos_general_settings', 'pinaka_pos_business_postcode');
    register_setting('pinaka_pos_general_settings', 'pinaka_pos_business_country');
	register_setting( 'pinaka_pos_loyalty_points_settings', 'pinaka_pos_enable_loyalty_points' );
	register_setting(
		'pinaka_fastkey_settings',
		'pinaka_fastkey_custom_types', // ARRAY stored here
		[
			'type' => 'array',
			'sanitize_callback' => function($value) {
				// Accept only string, convert to array later
				return $value;
			}
		]
	);
}
add_action('admin_init', 'pinaka_pos_register_settings');

// If you're posting your general form to admin-post.php with action=update_pinaka_pos_settings,
// this handler will catch the logo upload. If you post to options.php, this won't run.
function pinaka_pos_handle_file_upload() {
    if (!empty($_FILES['pinaka_pos_logo']['name'])) {
        $uploaded_file = wp_handle_upload($_FILES['pinaka_pos_logo'], ['test_form' => false]);
        if (!isset($uploaded_file['error'])) {
            update_option('pinaka_pos_logo', $uploaded_file['url']);
        }
    }
}
add_action('admin_post_update_pinaka_pos_settings', 'pinaka_pos_handle_file_upload');


// ===============================
// Cashback settings
// ===============================
add_action('admin_init', 'pinaka_pos_register_cashback_settings');

/**
 * Register settings for Cashback.
 */
function pinaka_pos_register_cashback_settings() {
    register_setting(
        'pinaka_pos_cashback',                 // settings group (must match settings_fields(...))
        'pinaka_pos_cashback_settings',        // option name saved in wp_options
        [
            'type'              => 'array',
            'sanitize_callback' => 'sanitize_cashback_settings', // MUST be a callable, not an array of one string
            'default'           => [
                'enabled'      => 0,
                'max_cashback' => '',
                'tiers'        => [], // each: ['from' => 0, 'to' => 0, 'fee' => 0]
            ],
        ]
    );
}

/**
 * Sanitize & normalize cashback settings.
 *
 * Expected input structure:
 * [
 *   'enabled' => '1' or '',
 *   'max_cashback' => '50',
 *   'tiers' => [
 *     0 => ['from' => '1', 'to' => '10', 'fee' => '1'],
 *     '__INDEX__' => ['from'=>'','to'=>'','fee'=>'']  // ignore
 *   ]
 * ]
 */
function sanitize_cashback_settings( $input ) {
    $out = [
        'enabled'      => empty($input['enabled']) ? 0 : 1,
        'max_cashback' => isset($input['max_cashback']) ? floatval($input['max_cashback']) : 0,
        'tiers'        => [],
    ];

    if ( ! empty( $input['tiers'] ) && is_array( $input['tiers'] ) ) {
        foreach ( $input['tiers'] as $key => $row ) {
            // Ignore template/placeholder rows (non-numeric keys like "__INDEX__")
            if ( ! is_numeric( $key ) ) {
                continue;
            }

            // if row not an array, skip
            if ( ! is_array( $row ) ) {
                continue;
            }

            // if all fields empty, skip
            $raw_from = isset( $row['from'] ) ? trim( $row['from'] ) : '';
            $raw_to   = isset( $row['to'] )   ? trim( $row['to'] )   : '';
            $raw_fee  = isset( $row['fee'] )  ? trim( $row['fee'] )  : '';

            if ( $raw_from === '' && $raw_to === '' && $raw_fee === '' ) {
                continue;
            }

            // convert to floats (empty strings become 0.0)
            $from = $raw_from === '' ? null : floatval( $raw_from );
            $to   = $raw_to   === '' ? null : floatval( $raw_to );
            $fee  = $raw_fee  === '' ? null : floatval( $raw_fee );

            // keep only valid rows
            if ( $from !== null && $to !== null && $fee !== null && $from >= 0 && $to > 0 && $to >= $from ) {
                $out['tiers'][] = [
                    'from' => $from,
                    'to'   => $to,
                    'fee'  => $fee,
                ];
            }
        }

        // sort tiers by "from"
        if ( ! empty( $out['tiers'] ) ) {
            usort( $out['tiers'], function( $a, $b ) {
                return $a['from'] <=> $b['from'];
            } );
        }
    }

    // Cap tiers to max_cashback if provided
    if ( $out['max_cashback'] > 0 && ! empty( $out['tiers'] ) ) {
        foreach ( $out['tiers'] as $k => $t ) {
            if ( isset( $t['to'] ) && $t['to'] > $out['max_cashback'] ) {
                $out['tiers'][ $k ]['to'] = $out['max_cashback'];
            }
            if ( isset( $t['from'] ) && $t['from'] > $out['max_cashback'] ) {
                // mark for removal by setting to null
                $out['tiers'][ $k ] = null;
            }
        }
        // remove null rows and reindex
        $out['tiers'] = array_values( array_filter( $out['tiers'], function( $r ) {
            return $r !== null;
        } ) );
    }

    return $out;
}

/**
 * Register service charge settings so settings_fields() is allowed.
 * Put this on admin_init (run during plugin admin load).
 */
add_action( 'admin_init', 'pinaka_pos_register_service_charge_settings' );
function pinaka_pos_register_service_charge_settings() {
	$option_group = 'pinaka_pos_service_charge'; // MUST match settings_fields() group
	$option_name  = 'pinaka_pos_service_charge_settings'; // your option key

	// register the setting with WP's Settings API
	register_setting(
		$option_group,
		$option_name,
		[
			'type'              => 'array',
			'sanitize_callback' => 'sanitize_service_charge_settings', // your sanitiser
			'default'           => [
				'enabled'     => 0,
				'charge_type' => 'fixed',
				'apply_to'    => 'order',
				'max_charge'  => '',
				'tiers'       => [],
			],
		]
	);

	// (Optional) you can also add settings sections/fields if you want them to appear
	// in a settings page using do_settings_sections(). Not required if you render HTML manually.
}

/**
 * Sanitise Service Charge Settings
 */
function sanitize_service_charge_settings( $input ) {
	$out = [
		'enabled'     => empty( $input['enabled'] ) ? 0 : 1,
		'charge_type' => in_array( $input['charge_type'] ?? 'fixed', [ 'fixed', 'percentage' ], true ) ? $input['charge_type'] : 'fixed',
		'apply_to'    => in_array( $input['apply_to'] ?? 'order', [ 'order', 'line_items' ], true ) ? $input['apply_to'] : 'order',
		'max_charge'  => isset( $input['max_charge'] ) && $input['max_charge'] !== '' ? floatval( $input['max_charge'] ) : 0,
		'tiers'       => [],
	];

	if ( ! empty( $input['tiers'] ) && is_array( $input['tiers'] ) ) {
		foreach ( $input['tiers'] as $key => $row ) {
			// ignore non-numeric keys (template placeholders)
			if ( ! is_numeric( $key ) ) {
				continue;
			}
			if ( ! is_array( $row ) ) {
				continue;
			}

			$raw_from   = isset( $row['from'] ) ? trim( $row['from'] ) : '';
			$raw_to     = isset( $row['to'] ) ? trim( $row['to'] ) : '';
			$raw_fee    = isset( $row['fee'] ) ? trim( $row['fee'] ) : '';
			$raw_ftype  = isset( $row['fee_type'] ) ? trim( $row['fee_type'] ) : 'fixed';

			// skip totally empty rows
			if ( $raw_from === '' && $raw_to === '' && $raw_fee === '' ) {
				continue;
			}

			$from = $raw_from === '' ? null : floatval( $raw_from );
			$to   = $raw_to === '' ? null : floatval( $raw_to );
			$fee  = $raw_fee === '' ? null : floatval( $raw_fee );

			$fee_type = in_array( $raw_ftype, [ 'fixed', 'percentage' ], true ) ? $raw_ftype : 'fixed';

			// basic validation:
			// - from and to must be present and numeric
			// - from >= 0, to >= from
			// - fee >= 0
			// - if percentage, fee between 0 and 100
			if ( $from === null || $to === null || $fee === null ) {
				continue;
			}
			if ( $from < 0 || $to < 0 || $to < $from ) {
				continue;
			}
			if ( $fee < 0 ) {
				continue;
			}
			if ( $fee_type === 'percentage' && ( $fee > 100 ) ) {
				// invalid percentage (clip to 100)
				$fee = 100.0;
			}

			$out['tiers'][] = [
				'from'     => $from,
				'to'       => $to,
				'fee'      => $fee,
				'fee_type' => $fee_type,
			];
		}

		// sort tiers by "from"
		if ( ! empty( $out['tiers'] ) ) {
			usort( $out['tiers'], function( $a, $b ) {
				return $a['from'] <=> $b['from'];
			} );
		}
	}

	// apply max_charge cap to fixed fees' 'to' boundaries if max provided
	if ( $out['max_charge'] > 0 && ! empty( $out['tiers'] ) ) {
		foreach ( $out['tiers'] as $k => $t ) {
			// if tier 'to' > max_charge, clamp it
			if ( isset( $t['to'] ) && $t['to'] > $out['max_charge'] ) {
				$out['tiers'][ $k ]['to'] = $out['max_charge'];
			}
			// if tier 'from' > max_charge, drop tier
			if ( isset( $t['from'] ) && $t['from'] > $out['max_charge'] ) {
				$out['tiers'][ $k ] = null;
			}
		}
		$out['tiers'] = array_values( array_filter( $out['tiers'], function( $r ) {
			return $r !== null;
		} ) );
	}

	return $out;
}
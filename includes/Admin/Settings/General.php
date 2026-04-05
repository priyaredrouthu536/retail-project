<?php

namespace PinakaPosWp\Admin\Settings;

use PinakaPosWp\Utilities\I18nUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Class General.
 *
 * @since   1.0.0
 * @package PinakaPosWp\Admin\Settings
 */
class General extends Page {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct( 'general', __( 'General', 'pinaka-pos-wp' ) );
	}

	/**
	 * Get settings tab sections.
	 *
	 * @since 3.0.0
	 * @return array
	 */
	protected function get_own_sections() {
		return array(
			''         => __( 'General', 'pinaka-pos-wp' ),
			'currency' => __( 'Currency', 'pinaka-pos-wp' ),
		);
	}

	/**
	 * Get settings.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_default_section_settings() {
		return array(
			array(
				'title' => __( 'Business Information', 'pinaka-pos-wp' ),
				'type'  => 'title',
				'id'    => 'general_settings',
			),
			array(
				'title'       => __( 'Name', 'pinaka-pos-wp' ),
				'desc'        => __( 'The name of your business. This will be used in the invoice, bill, and other documents.', 'pinaka-pos-wp' ),
				'id'          => 'eac_business_name',
				'type'        => 'text',
				'placeholder' => 'e.g. XYZ Ltd.',
				'default'     => esc_html( get_bloginfo( 'name' ) ),
				'desc_tip'    => true,
			),
			array(
				'title'       => __( 'Email', 'pinaka-pos-wp' ),
				'desc'        => __( 'The email address of your business. This will be used in the invoice, bill, and other documents.', 'pinaka-pos-wp' ),
				'id'          => 'eac_business_email',
				'type'        => 'email',
				'placeholder' => get_option( 'admin_email' ),
				'default'     => get_option( 'admin_email' ),
				'desc_tip'    => true,
			),
			array(
				'title'       => __( 'Phone', 'pinaka-pos-wp' ),
				'desc'        => __( 'The phone number of your business. This will be used in the invoice, bill, and other documents.', 'pinaka-pos-wp' ),
				'id'          => 'eac_business_phone',
				'type'        => 'text',
				'placeholder' => 'e.g. +1 123 456 7890',
				'default'     => '',
				'desc_tip'    => true,
			),
			array(
				'title'       => __( 'Logo', 'pinaka-pos-wp' ),
				'desc'        => __( 'The logo of your business. This will be used in the invoice, bill, and other documents.', 'pinaka-pos-wp' ),
				'id'          => 'eac_business_logo',
				'type'        => 'text',
				'placeholder' => 'e.g. http://example.com/logo.png',
				'default'     => '',
				'desc_tip'    => true,
			),
			array(
				'title'       => __( 'Tax Number', 'pinaka-pos-wp' ),
				'desc'        => __( 'The tax number of your business. This will be used in the invoice, bill, and other documents.', 'pinaka-pos-wp' ),
				'id'          => 'eac_business_tax_number',
				'type'        => 'text',
				'placeholder' => 'e.g. 123456789',
				'default'     => '',
				'desc_tip'    => true,
			),
			array(
				'title'       => __( 'Financial Year Start', 'pinaka-pos-wp' ),
				'desc'        => __( 'The start date of your financial year.', 'pinaka-pos-wp' ),
				'id'          => 'eac_year_start_date',
				'type'        => 'text',
				'placeholder' => 'e.g. 01-01',
				'default'     => '01-01',
				'desc_tip'    => true,
				'class'       => 'eac_datepicker',
				'data-format' => 'mm-dd',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'general_settings',
			),
			array(
				'title' => __( 'Business Address', 'pinaka-pos-wp' ),
				'type'  => 'title',
				'id'    => 'business_address',
			),
			array(
				'title'       => __( 'Address', 'pinaka-pos-wp' ),
				'desc'        => __( 'The street address of your business.', 'pinaka-pos-wp' ),
				'id'          => 'eac_business_address',
				'type'        => 'text',
				'placeholder' => 'e.g. 123 Main Street',
				'default'     => '',
				'desc_tip'    => true,
			),
			array(
				'title'       => __( 'City', 'pinaka-pos-wp' ),
				'desc'        => __( 'The city in which your business is located. This will be used in the invoice, bill, and other documents.', 'pinaka-pos-wp' ),
				'id'          => 'eac_business_city',
				'type'        => 'text',
				'placeholder' => 'e.g. Manhattan',
				'default'     => '',
				'desc_tip'    => true,
			),
			array(
				'title'       => __( 'State', 'pinaka-pos-wp' ),
				'desc'        => __( 'The state in which your business is located.', 'pinaka-pos-wp' ),
				'id'          => 'eac_business_state',
				'type'        => 'text',
				'placeholder' => 'e.g. New York',
				'default'     => '',
				'desc_tip'    => true,
			),
			array(
				'title'       => __( 'ZIP', 'pinaka-pos-wp' ),
				'desc'        => __( 'The postcode or ZIP code of your business (if any). This will be used in the invoice, bill, and other documents.', 'pinaka-pos-wp' ),
				'id'          => 'eac_business_postcode',
				'type'        => 'text',
				'placeholder' => 'e.g. 10001',
				'default'     => '',
				'desc_tip'    => true,
			),
			array(
				'title'       => __( 'Country', 'pinaka-pos-wp' ),
				'desc'        => __( 'The country in which your business is located.', 'pinaka-pos-wp' ),
				'id'          => 'eac_business_country',
				'type'        => 'select',
				'options'     => I18nUtil::get_countries(),
				'class'       => 'eac_select2',
				'default'     => 'US',
				'placeholder' => __( 'Select a country&hellip;', 'pinaka-pos-wp' ),
				'desc_tip'    => true,
			),
			array(
				'type' => 'sectionend',
				'id'   => 'business_address',
			),
		);
	}

	/**
	 * Get currency section settings.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_currency_section_settings() {
		return array(
			// currency options.
			array(
				'title' => __( 'Currency Settings', 'pinaka-pos-wp' ),
				'type'  => 'title',
				'id'    => 'currency_options',
			),
			// currency.
			array(
				'title'    => __( 'Base Currency', 'pinaka-pos-wp' ),
				'desc'     => __( 'The base currency of your business. Currency can not be changed once you have recorded any transaction.', 'pinaka-pos-wp' ),
				'id'       => 'eac_base_currency',
				'type'     => 'select',
				'default'  => 'USD',
				'class'    => 'eac_select2',
				'options'  => wp_list_pluck( eac_get_currencies(), 'formatted_name', 'code' ),
				'value'    => get_option( 'eac_base_currency', 'USD' ),
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'Currency Position', 'pinaka-pos-wp' ),
				'desc'     => __( 'The position of the currency symbol.', 'pinaka-pos-wp' ),
				'id'       => 'eac_currency_position',
				'type'     => 'select',
				'default'  => 'before',
				'options'  => array(
					'before' => __( 'Before', 'pinaka-pos-wp' ),
					'after'  => __( 'After', 'pinaka-pos-wp' ),
				),
				'desc_tip' => true,
			),
			array(
				'title'       => __( 'Thousand Separator', 'pinaka-pos-wp' ),
				'desc'        => __( 'The character used to separate thousands.', 'pinaka-pos-wp' ),
				'id'          => 'eac_thousand_separator',
				'type'        => 'text',
				'placeholder' => ',',
				'default'     => ',',
				'desc_tip'    => true,
			),
			array(
				'title'       => __( 'Decimal Separator', 'pinaka-pos-wp' ),
				'desc'        => __( 'The character used to separate decimals.', 'pinaka-pos-wp' ),
				'id'          => 'eac_decimal_separator',
				'type'        => 'text',
				'placeholder' => '.',
				'default'     => '.',
				'desc_tip'    => true,
			),
			array(
				'title'       => __( 'Currency Precision', 'pinaka-pos-wp' ),
				'desc'        => __( 'The number of decimal places to display.', 'pinaka-pos-wp' ),
				'id'          => 'eac_currency_precision',
				'type'        => 'number',
				'placeholder' => '2',
				'default'     => 2,
				'desc_tip'    => true,
			),
			// exchange rates.
			array(
				'title' => __( 'Exchange Rates', 'pinaka-pos-wp' ),
				'id'    => 'eac_exchange_rates',
				'type'  => 'exchange_rates',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'currency_options',
			),
		);
	}
}
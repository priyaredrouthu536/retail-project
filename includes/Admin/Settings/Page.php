<?php

namespace PinakaPosWp\Admin\SettingsPage;

use PinakaPosWp\Admin\SettingsPage;

defined( 'ABSPATH' ) || exit;

/**
 * Class SettingsPage.
 *
 * @since   1.0.0
 * @package PinakaPosWp
 */
abstract class Page {
	/**
	 * Setting page id.
	 *
	 * @var string
	 */
	public $id = '';

	/**
	 * Setting tab label.
	 *
	 * @var string
	 */
	public $label = '';

	/**
	 * Current section.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	public $section = '';

	/**
	 * Page constructor.
	 *
	 * @param string $id Page ID.
	 * @param string $label Page label.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $id, $label ) {
		$this->id      = $id;
		$this->label   = $label;
		$this->section = (string) filter_input( INPUT_GET, 'section', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		add_action( 'eac_settings_page_' . $this->id . '_loaded', array( $this, 'save_settings' ) );
		add_action( 'eac_settings_page_' . $this->id . '_content', array( $this, 'render_sections' ) );
		add_action( 'eac_settings_page_' . $this->id . '_content', array( $this, 'render_content' ) );
	}

	/**
	 * Save settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function save_settings() {
		$settings = $this->get_section_settings( $this->section );
		if ( Settings::save_fields( $settings ) ) {
			EAC()->flash->success( __( 'Settings saved.', 'wp-ever-accounting' ) );
		}
	}

	/**
	 * Render Sections.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_sections() {
		$sections = $this->get_sections();

		if ( empty( $sections ) || 1 === count( $sections ) ) {
			return;
		}

		$array_keys = array_keys( $sections );
		echo '<ul class="subsubsub settings-sections-nav">';
		foreach ( $sections as $id => $label ) {
			$url       = admin_url( 'admin.php?page=eac-settings&tab=' . $this->id . '&section=' . sanitize_title( $id ) );
			$class     = ( $this->section === $id ? 'current' : '' );
			$separator = ( end( $array_keys ) === $id ? '' : '|' );
			$text      = esc_html( $label );
			printf( '<li><a href="%s" class="%s">%s</a> %s</li>', esc_url( $url ), esc_attr( $class ), esc_html( $text ), esc_html( $separator ) );
		}
		echo '</ul><br class="clear" />';
	}

	/**
	 * Render page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_content() {
		$settings = $this->get_section_settings( $this->section );
		$action   = 'eac_settings_' . $this->id . '_tab' . ( $this->section ? '_' . $this->section : '' ) . '_content';
		if ( has_action( $action ) ) : ?>
			<?php
			/**
			 * Fire action for the current tab.
			 *
			 * @param string $current_section Current section.
			 *
			 * @since 1.0.0
			 */
			do_action( $action, $this->section );
			?>
		<?php elseif ( ! empty( $settings ) ) : ?>
			<form method="post" id="mainform" action="" enctype="multipart/form-data">
				<?php Settings::output_fields( $settings ); ?>
				<?php wp_nonce_field( 'eac_save_settings' ); ?>
				<?php if ( apply_filters( 'eac_settings_save_button_' . $this->id, true, $this->section ) ) : ?>
					<p class="submit">
						<button name="save_settings" class="button-primary" type="submit" value="<?php esc_attr_e( 'Save changes', 'wp-ever-accounting' ); ?>">
							<?php esc_html_e( 'Save changes', 'wp-ever-accounting' ); ?>
						</button>
					</p>
				<?php endif; ?>
			</form>
		<?php endif; ?>
		<?php
	}

	/**
	 * Get own sections for this page.
	 * Example:
	 * return array(
	 *   ''        => __( 'General', 'woocommerce' ),
	 *   'foobars' => __( 'Foos & Bars', 'woocommerce' ),
	 * );
	 *
	 * @return array An associative array where keys are section identifiers and the values are translated section names.
	 */
	protected function get_own_sections() {
		return array( '' => __( 'Options', 'wp-ever-accounting' ) );
	}

	/**
	 * Get settings tab sections.
	 *
	 * @since 3.0.0
	 * @return array
	 */
	public function get_sections() {
		$sections = $this->get_own_sections();
		/**
		 * Filters the sections for this settings page.
		 *
		 * @since 2.0.0
		 * @param array $sections The sections for this settings page.
		 */
		return (array) apply_filters( 'eac_get_settings_sections_' . $this->id, $sections );
	}

	/**
	 * Get settings array.
	 *
	 * @param string $section The id of the section to return settings for, an empty string for the default section.
	 *
	 * @return array Settings array, each item being an associative array representing a setting.
	 */
	public function get_section_settings( $section ) {
		$settings = array();
		if ( empty( $section ) ) {
			$method = 'get_default_section_settings';
		} else {
			$method = 'get_' . $section . '_section_settings';
		}

		if ( method_exists( $this, $method ) ) {
			$settings = $this->$method( $section );
		}

		/**
		 * Filter the settings array.
		 *
		 * @param array  $settings The settings array.
		 * @param string $section The section id.
		 *
		 * @since 1.0.0
		 */
		return apply_filters( 'eac_get_' . $this->id . '_settings', $settings, $section );
	}

	/**
	 * Get default section settings.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_default_section_settings() {
		return array();
	}
}

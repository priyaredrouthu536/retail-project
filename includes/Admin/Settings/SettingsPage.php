<?php
namespace PinakaPos\Admin\Settings;
use PinakaPos\Admin\Admin_Helper;

use EverAccounting\Utilities\I18nUtil;

// Exit if accessed directly.
defined('ABSPATH') || exit();

/**
 * Class SettingsPage
 */
class SettingsPage {
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	*
	* @since    1.0.0
	* @access   private
	* @var      string    $version    The current version of this plugin.
	*/
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		// Register our settings
	}

    public function render() {
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'tab1';

    include_once(ABSPATH . 'wp-admin/includes/plugin.php');

    $active_plugins = (array) get_option('active_plugins', []);
    $network_plugins = is_multisite() ? (array) get_site_option('active_sitewide_plugins', []) : [];

    $is_loyalty_active = (
        in_array('pinaka-loyalty/pinaka-loyalty.php', $active_plugins, true)
        || isset($network_plugins['pinaka-loyalty/pinaka-loyalty.php'])
    );

    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Settings', 'pinaka-pos-wp'); ?></h1>
        <p><?php echo esc_html__('This page will display and manage settings.', 'pinaka-pos-wp'); ?></p>

        <!-- Tab Navigation -->
        <h2 class="nav-tab-wrapper">
            <a href="?page=pinaka-pos-settings&tab=tab1" 
               class="nav-tab <?php echo $current_tab === 'tab1' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('General Settings', 'pinaka-pos-wp'); ?>
            </a>
            <a href="?page=pinaka-pos-settings&tab=tab2" 
               class="nav-tab <?php echo $current_tab === 'tab2' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Admin Theme', 'pinaka-pos-wp'); ?>
            </a>
            <a href="?page=pinaka-pos-settings&tab=tab3" 
               class="nav-tab <?php echo $current_tab === 'tab3' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Roles - Menu', 'pinaka-pos-wp'); ?>
            </a>
            <a href="?page=pinaka-pos-settings&tab=tab4" 
               class="nav-tab <?php echo $current_tab === 'tab4' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Opening Denominations', 'pinaka-pos-wp'); ?>
            </a>
            <a href="?page=pinaka-pos-settings&tab=tab5" 
               class="nav-tab <?php echo $current_tab === 'tab5' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Promotion Images', 'pinaka-pos-wp'); ?>
            </a>
            <a href="?page=pinaka-pos-settings&tab=tab6" 
               class="nav-tab <?php echo $current_tab === 'tab6' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Cashback', 'pinaka-pos-wp'); ?>
            </a>
            <a href="?page=pinaka-pos-settings&tab=tab7" 
               class="nav-tab <?php echo $current_tab === 'tab7' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Service Charge', 'pinaka-pos-wp'); ?>
            </a>

            <!-- ✅ Show Loyalty Points tab only if plugin is active -->
            <?php if ($is_loyalty_active) : ?>
                <a href="?page=pinaka-pos-settings&tab=tab8" 
                   class="nav-tab <?php echo $current_tab === 'tab8' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Loyalty Points', 'pinaka-pos-wp'); ?>
                </a>
            <?php endif; ?>
			<a href="?page=pinaka-pos-settings&tab=tab9" 
               class="nav-tab <?php echo $current_tab === 'tab9' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('FastKeys Images', 'pinaka-pos-wp'); ?>
            </a>
        </h2>

        <!-- Tab Content -->
        <div class="tab-content">
            <?php
            if ($current_tab === 'tab1') {
                $this->render_general_settings_tab();
            } elseif ($current_tab === 'tab2') {
                $this->render_theme_settings_tab();
            } elseif ($current_tab === 'tab3') {
                $this->render_financial_settings_tab();
            } elseif ($current_tab === 'tab4') {
                $this->render_denominations_tab();
            } elseif ($current_tab === 'tab5') {
                $this->render_promotion_images_tab();
            } elseif ($current_tab === 'tab6') {
                $this->render_cash_back_settings_tab();
            } elseif ($current_tab === 'tab7') {
                $this->render_service_charge_settings_tab();
            } elseif ($current_tab === 'tab8' && $is_loyalty_active) {
                $this->loyalty_points_tab();
            } elseif ($current_tab === 'tab8' && ! $is_loyalty_active) {
                echo '<div class="notice notice-error"><p>' . esc_html__('Loyalty plugin is not active.', 'pinaka-pos-wp') . '</p></div>';
            }
			elseif ($current_tab === 'tab9') {
                // $this->fast_key_images();
				$this->pinaka_fastkey_images_page();
            }
            ?>
        </div>
    </div>
    <?php
}

	private function pinaka_fastkey_images_page() {
		$saved = get_option('pinaka_fastkey_images', []);
		$default_types = ['2D','3D','PNG','JPG','JPEG'];
		$custom_types = get_option('pinaka_fastkey_custom_types', []);

		// Make sure custom types is array
		if (!is_array($custom_types)) {
			$custom_types = [];
		}
		$valid_folders = array_unique(
			array_map('strtoupper', array_merge($default_types, $custom_types))
		);
		?>
		<div class="wrap">
			<form id="pk-fastkey-images" method="post" action="options.php">
				<?php 
					// Must match register_setting()
					settings_fields('pinaka_fastkey_settings');
				?>
				<h3>Add New Image Type</h3>
				<p style="display:flex; gap:10px; align-items:center;">
				<input type="text" 
					id="new-type" 
					name="pinaka_fastkey_custom_types" 
					placeholder="Enter custom type" autocomplete="off"/> <?php submit_button('Add', 'primary', 'submit', false); ?></p>
			</form>
			<h1>FastKey Images</h1>
			<form id="pk-fastkey-form">
				<hr>
				<h2>Manage FastKey Images</h2>

				<p>
					<button type="button" id="pk-upload-fastkey" class="button">Upload / Select Images</button>
					<!-- <select id="pk-fastkey-folder" name="image_type" class="regular-text" style="width:auto; margin-right:15px;">
						<option value="">Image Type</option>
						<option value="2D">2D</option>
						<option value="3D">3D</option>
						<option value="PNG">PNG</option>
						<option value="JPG">JPG</option>
						<option value="JPEG">JPEG</option>
					</select> -->
					<select id="pk-fastkey-folder" name="image_type" class="regular-text" style="width:auto; margin-right:15px;">
						<option value="">Image Type</option>
						<?php
						foreach ($valid_folders as $type) {
							echo '<option value="'.esc_attr($type).'">'.$type.'</option>';
						}
						?>
					</select>

					<button type="submit" id="pk-save-fastkey" class="button button-primary">Save FastKey Images</button>
					<span id="pk-save-msg" style="margin-left:10px;"></span>
				</p>

				<div id="pk-fastkey-groups">
					<?php
					// defensive: make sure $saved is an array
					if ( ! is_array( $saved ) ) {
						$saved = [];
					}
					if ( ! empty( $saved ) ) {
						foreach ( $saved as $ext => $items ) {
							// normalize extension to string for display/attributes
							$ext = (string) $ext;

							// if $items isn't countable, coerce to empty array
							if ( ! is_countable( $items ) ) {
								$items = [];
							}

							if ( empty( $items ) ) {
								continue;
							}
							?>
							<h3><?php echo esc_html( strtoupper( $ext ) ); ?> (<?php echo intval( count( $items ) ); ?>)</h3>
							<div class="pk-fastkey-group" data-ext="<?php echo esc_attr( $ext ); ?>"
								style="display:flex;flex-wrap:wrap;gap:15px;margin-bottom:15px;">

								<?php foreach ( $items as $img ): 
									// ensure $img is an array and has expected keys
									if ( ! is_array( $img ) ) {
										continue;
									}
									$id  = isset( $img['id'] ) ? $img['id'] : 0;
									$url  = isset( $img['url'] ) ? esc_url( $img['url'] ) : '';
									$name = isset( $img['name'] ) ? esc_html( $img['name'] ) : '';
									$is_deleted = !empty($img['isDeleted']);
									?>
									<div class="pk-fastkey-item"
										data-ext="<?php echo esc_attr( $ext ); ?>"
										data-name="<?php echo esc_attr( $name ); ?>"
										data-url="<?php echo esc_attr( $url ); ?>"
										style="width:140px;text-align:center;border:1px solid #ddd;padding:10px;border-radius:6px;background:#fff;">
										<?php
										echo "<img src='" . esc_url( $url ) . "' style='max-width:100%;height:90px;object-fit:cover;border-radius:4px;'>";
										?>
										<div style="font-size:12px;margin-top:6px;"><?php echo $name; ?></div>
										<!-- <a href="#" class="pk-remove-item" style="display:block;color:#a00;margin-top:6px;">Remove</a> -->
										 <button class="pk-toggle-status button"
												data-id="<?php echo $id; ?>"
												data-ext="<?php echo esc_attr($ext); ?>"
												data-name="<?php echo esc_attr($name); ?>"
												data-status="<?php echo $is_deleted ? 'active' : 'deactive'; ?>"
												style="margin-top:8px;">
											<?php echo $is_deleted ? 'Activate' : 'Deactivate'; ?>
										</button>
									</div>
								<?php endforeach; ?>
							</div>
							<?php
						}
					} else {
						echo "<p>No images uploaded yet.</p>";
					}
					?>
				</div>
			</form>

			<style>
				.pk-fastkey-item { cursor: move; }
			</style>
		</div>
		<?php
	}
	private function loyalty_points_tab(){
		?>
    <div class="wrap">
        <h2><?php esc_html_e('Loyalty Points Settings', 'pinaka-pos-wp'); ?></h2>

        <form method="post" action="options.php">
            <?php
                settings_fields('pinaka_pos_loyalty_points_settings');
                do_settings_sections('pinaka_pos_loyalty_points_settings');
                $enabled = get_option('pinaka_pos_enable_loyalty_points', 'no');
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="pinaka_pos_enable_loyalty_points"><?php esc_html_e('Enable Loyalty Points', 'pinaka-pos-wp'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="pinaka_pos_enable_loyalty_points" name="pinaka_pos_enable_loyalty_points" value="yes" <?php checked($enabled, 'yes'); ?> />
                        <p class="description"><?php esc_html_e('Check to enable the Loyalty Points system in POS.', 'pinaka-pos-wp'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Save Changes', 'pinaka-pos-wp')); ?>
        </form>
    </div>
    <?php
	}

	private function render_cash_back_settings_tab() {
		$cashback_option_key = 'pinaka_pos_cashback_settings';
		$opts = get_option($cashback_option_key, [
			'enabled'      => 0,
			'max_cashback' => '',
			'tiers'        => [],
		]);
		$tiers = is_array($opts['tiers'] ?? null) ? $opts['tiers'] : [];
		?>
		<form method="post" action="options.php">
			<?php settings_fields('pinaka_pos_cashback'); // nonce + group ?>
			<h2><?php esc_html_e('Cash Back Settings', 'pinaka-pos-wp'); ?></h2>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="pinaka-cb-enabled"><?php esc_html_e('Enable Cash Back', 'pinaka-pos-wp'); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" id="pinaka-cb-enabled"
									name="<?php echo esc_attr($cashback_option_key); ?>[enabled]"
									value="1" <?php checked(1, intval($opts['enabled'] ?? 0)); ?>>
								<?php esc_html_e('Enable the cash back feature at checkout/POS', 'pinaka-pos-wp'); ?>
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="pinaka-cb-max"><?php esc_html_e('Max Cash Back Limit', 'pinaka-pos-wp'); ?></label>
						</th>
						<td>
							<input type="number" min="0" step="0.01" id="pinaka-cb-max" class="regular-text"
								name="<?php echo esc_attr($cashback_option_key); ?>[max_cashback]"
								value="<?php echo esc_attr($opts['max_cashback']); ?>">
							<p class="description">
								<?php esc_html_e('Maximum cash back amount allowed per order.', 'pinaka-pos-wp'); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<?php esc_html_e('Cash Back Fee Tiers', 'pinaka-pos-wp'); ?>
						</th>
						<td>
							<p class="description" style="margin-bottom:8px;">
								<?php esc_html_e('Define fixed fee per cash back amount range (inclusive). Example: 1-10 = $1 fee; 10-20 = $2 fee.', 'pinaka-pos-wp'); ?>
							</p>

							<table class="widefat striped" id="pinaka-cb-tiers-table" style="max-width:800px;">
								<thead>
									<tr>
										<th style="width:25%"><?php esc_html_e('From (amount)', 'pinaka-pos-wp'); ?></th>
										<th style="width:25%"><?php esc_html_e('To (amount)', 'pinaka-pos-wp'); ?></th>
										<th style="width:25%"><?php esc_html_e('Fee (fixed)', 'pinaka-pos-wp'); ?></th>
										<th style="width:25%"><?php esc_html_e('Actions', 'pinaka-pos-wp'); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php if (!empty($tiers)) : foreach ($tiers as $i => $row) : ?>
										<tr>
											<td>
												<input type="number" min="0" step="0.01"
													name="<?php echo esc_attr($cashback_option_key); ?>[tiers][<?php echo esc_attr($i); ?>][from]"
													value="<?php echo esc_attr($row['from']); ?>" />
											</td>
											<td>
												<input type="number" min="0" step="0.01"
													name="<?php echo esc_attr($cashback_option_key); ?>[tiers][<?php echo esc_attr($i); ?>][to]"
													value="<?php echo esc_attr($row['to']); ?>" />
											</td>
											<td>
												<input type="number" min="0" step="0.01"
													name="<?php echo esc_attr($cashback_option_key); ?>[tiers][<?php echo esc_attr($i); ?>][fee]"
													value="<?php echo esc_attr($row['fee']); ?>" />
											</td>
											<td>
												<button type="button" class="button button-link-delete pinaka-cb-row-remove">
													<?php esc_html_e('Remove', 'pinaka-pos-wp'); ?>
												</button>
											</td>
										</tr>
									<?php endforeach; endif; ?>

									<!-- template row -->
									<tr class="pinaka-cb-template-row" style="display:none;">
										<td>
											<input type="number" min="0" step="0.01"
												name="<?php echo esc_attr($cashback_option_key); ?>[tiers][__INDEX__][from]" value="" />
										</td>
										<td>
											<input type="number" min="0" step="0.01"
												name="<?php echo esc_attr($cashback_option_key); ?>[tiers][__INDEX__][to]" value="" />
										</td>
										<td>
											<input type="number" min="0" step="0.01"
												name="<?php echo esc_attr($cashback_option_key); ?>[tiers][__INDEX__][fee]" value="" />
										</td>
										<td>
											<button type="button" class="button button-link-delete pinaka-cb-row-remove">
												<?php esc_html_e('Remove', 'pinaka-pos-wp'); ?>
											</button>
										</td>
									</tr>
								</tbody>
							</table>

							<p style="margin-top:10px;">
								<button type="button" class="button" id="pinaka-cb-add-row">
									<?php esc_html_e('Add Fee Tier', 'pinaka-pos-wp'); ?>
								</button>
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button(); ?>
		</form>

		<script>
			(function(){
				const table = document.getElementById('pinaka-cb-tiers-table');
				const addBtn = document.getElementById('pinaka-cb-add-row');
				const tmpl = table.querySelector('.pinaka-cb-template-row');
				let nextIndex = (function(){
					// find next index from existing rows
					const inputs = table.querySelectorAll('tbody tr:not(.pinaka-cb-template-row) input[name*="[tiers]"]');
					let max = -1;
					inputs.forEach(function(inp){
						const m = inp.name.match(/\[tiers\]\[(\d+)\]/);
						if (m && m[1]) {
							const n = parseInt(m[1], 10);
							if (n > max) max = n;
						}
					});
					return max + 1;
				})();

				table.addEventListener('click', function(e){
					if (e.target && e.target.classList.contains('pinaka-cb-row-remove')) {
						e.preventDefault();
						const tr = e.target.closest('tr');
						if (tr && !tr.classList.contains('pinaka-cb-template-row')) {
							tr.parentNode.removeChild(tr);
						}
					}
				});

				if (addBtn) {
					addBtn.addEventListener('click', function(e){
						e.preventDefault();
						const clone = tmpl.cloneNode(true);
						clone.style.display = '';
						clone.classList.remove('pinaka-cb-template-row');

						clone.querySelectorAll('input').forEach(function(inp){
							inp.name = inp.name.replace('__INDEX__', String(nextIndex));
						});

						table.querySelector('tbody').appendChild(clone);
						nextIndex++;
					});
				}
			})();
		</script>
		<?php
	}


	/**
 	* Render Service Charge Settings tab (mirrors cashback UI, with fee_type per tier)
	*/
	private function render_service_charge_settings_tab() {
		$option_key = 'pinaka_pos_service_charge_settings';
		$opts = get_option( $option_key, [
			'enabled'     => 0,
			'charge_type' => 'fixed', // global default (fixed|percentage) - used when no tiers exist
			'apply_to'    => 'order', // order | line_items
			'max_charge'  => '',
			'tiers'       => [],
		] );

		$tiers = is_array( $opts['tiers'] ?? null ) ? $opts['tiers'] : [];
		?>
		<form method="post" action="options.php">
			<?php settings_fields( 'pinaka_pos_service_charge' ); // nonce + group ?>
			<h2><?php esc_html_e( 'Service Charge Settings', 'pinaka-pos-wp' ); ?></h2>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="pinaka-sc-enabled"><?php esc_html_e( 'Enable Service Charge', 'pinaka-pos-wp' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" id="pinaka-sc-enabled"
									name="<?php echo esc_attr( $option_key ); ?>[enabled]"
									value="1" <?php checked( 1, intval( $opts['enabled'] ?? 0 ) ); ?> >
								<?php esc_html_e( 'Apply service charge at checkout / POS', 'pinaka-pos-wp' ); ?>
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Apply To', 'pinaka-pos-wp' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( $option_key ); ?>[apply_to]">
								<option value="order" <?php selected( $opts['apply_to'] ?? 'order', 'order' ); ?>><?php esc_html_e( 'Order total', 'pinaka-pos-wp' ); ?></option>
								<option value="line_items" <?php selected( $opts['apply_to'] ?? 'order', 'line_items' ); ?>><?php esc_html_e( 'Each line item', 'pinaka-pos-wp' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Choose whether service charge is calculated on the whole order or per item.', 'pinaka-pos-wp' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Default Charge Type', 'pinaka-pos-wp' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( $option_key ); ?>[charge_type]">
								<option value="fixed" <?php selected( $opts['charge_type'] ?? 'fixed', 'fixed' ); ?>><?php esc_html_e( 'Fixed amount', 'pinaka-pos-wp' ); ?></option>
								<option value="percentage" <?php selected( $opts['charge_type'] ?? 'fixed', 'percentage' ); ?>><?php esc_html_e( 'Percentage', 'pinaka-pos-wp' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Used when there are no tiers, or as a global fallback.', 'pinaka-pos-wp' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="pinaka-sc-max"><?php esc_html_e( 'Max Service Charge Limit', 'pinaka-pos-wp' ); ?></label>
						</th>
						<td>
							<input type="number" min="0" step="0.01" id="pinaka-sc-max" class="regular-text"
								name="<?php echo esc_attr( $option_key ); ?>[max_charge]"
								value="<?php echo esc_attr( $opts['max_charge'] ); ?>">
							<p class="description"><?php esc_html_e( 'Maximum service charge allowed per order (leave empty for no cap).', 'pinaka-pos-wp' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Service Charge Tiers', 'pinaka-pos-wp' ); ?></th>
						<td>
							<p class="description" style="margin-bottom:8px;">
								<?php esc_html_e( 'Define fee for order amount ranges. Fee can be a fixed amount or percentage. Example: 0–100 = 5% ; 100–500 = $10.', 'pinaka-pos-wp' ); ?>
							</p>

							<table class="widefat striped" id="pinaka-sc-tiers-table" style="max-width:900px;">
								<thead>
									<tr>
										<th style="width:18%"><?php esc_html_e( 'From (amount)', 'pinaka-pos-wp' ); ?></th>
										<th style="width:18%"><?php esc_html_e( 'To (amount)', 'pinaka-pos-wp' ); ?></th>
										<th style="width:18%"><?php esc_html_e( 'Fee', 'pinaka-pos-wp' ); ?></th>
										<th style="width:18%"><?php esc_html_e( 'Fee Type', 'pinaka-pos-wp' ); ?></th>
										<th style="width:20%"><?php esc_html_e( 'Actions', 'pinaka-pos-wp' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php if ( ! empty( $tiers ) ) : foreach ( $tiers as $i => $row ) : ?>
										<tr>
											<td>
												<input type="number" min="0" step="0.01"
													name="<?php echo esc_attr( $option_key ); ?>[tiers][<?php echo esc_attr( $i ); ?>][from]"
													value="<?php echo esc_attr( $row['from'] ?? '' ); ?>" />
											</td>
											<td>
												<input type="number" min="0" step="0.01"
													name="<?php echo esc_attr( $option_key ); ?>[tiers][<?php echo esc_attr( $i ); ?>][to]"
													value="<?php echo esc_attr( $row['to'] ?? '' ); ?>" />
											</td>
											<td>
												<input type="number" min="0" step="0.01"
													name="<?php echo esc_attr( $option_key ); ?>[tiers][<?php echo esc_attr( $i ); ?>][fee]"
													value="<?php echo esc_attr( $row['fee'] ?? '' ); ?>" />
											</td>
											<td>
												<select name="<?php echo esc_attr( $option_key ); ?>[tiers][<?php echo esc_attr( $i ); ?>][fee_type]">
													<option value="fixed" <?php selected( $row['fee_type'] ?? 'fixed', 'fixed' ); ?>><?php esc_html_e( 'Fixed', 'pinaka-pos-wp' ); ?></option>
													<option value="percentage" <?php selected( $row['fee_type'] ?? 'fixed', 'percentage' ); ?>><?php esc_html_e( 'Percentage', 'pinaka-pos-wp' ); ?></option>
												</select>
											</td>
											<td>
												<button type="button" class="button button-link-delete pinaka-sc-row-remove">
													<?php esc_html_e( 'Remove', 'pinaka-pos-wp' ); ?>
												</button>
											</td>
										</tr>
									<?php endforeach; endif; ?>

									<!-- template row -->
									<tr class="pinaka-sc-template-row" style="display:none;">
										<td>
											<input type="number" min="0" step="0.01"
												name="<?php echo esc_attr( $option_key ); ?>[tiers][__INDEX__][from]" value="" />
										</td>
										<td>
											<input type="number" min="0" step="0.01"
												name="<?php echo esc_attr( $option_key ); ?>[tiers][__INDEX__][to]" value="" />
										</td>
										<td>
											<input type="number" min="0" step="0.01"
												name="<?php echo esc_attr( $option_key ); ?>[tiers][__INDEX__][fee]" value="" />
										</td>
										<td>
											<select name="<?php echo esc_attr( $option_key ); ?>[tiers][__INDEX__][fee_type]">
												<option value="fixed"><?php esc_html_e( 'Fixed', 'pinaka-pos-wp' ); ?></option>
												<option value="percentage"><?php esc_html_e( 'Percentage', 'pinaka-pos-wp' ); ?></option>
											</select>
										</td>
										<td>
											<button type="button" class="button button-link-delete pinaka-sc-row-remove">
												<?php esc_html_e( 'Remove', 'pinaka-pos-wp' ); ?>
											</button>
										</td>
									</tr>
								</tbody>
							</table>

							<p style="margin-top:10px;">
								<button type="button" class="button" id="pinaka-sc-add-row">
									<?php esc_html_e( 'Add Fee Tier', 'pinaka-pos-wp' ); ?>
								</button>
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button(); ?>
		</form>

		<script>
			(function(){
				const table = document.getElementById('pinaka-sc-tiers-table');
				const addBtn = document.getElementById('pinaka-sc-add-row');
				const tmpl = table.querySelector('.pinaka-sc-template-row');
				let nextIndex = (function(){
					// find next index from existing rows
					const inputs = table.querySelectorAll('tbody tr:not(.pinaka-sc-template-row) input[name*="[tiers]"]');
					let max = -1;
					inputs.forEach(function(inp){
						const m = inp.name.match(/\[tiers\]\[(\d+)\]/);
						if (m && m[1]) {
							const n = parseInt(m[1], 10);
							if (n > max) max = n;
						}
					});
					return max + 1;
				})();

				table.addEventListener('click', function(e){
					if (e.target && e.target.classList.contains('pinaka-sc-row-remove')) {
						e.preventDefault();
						const tr = e.target.closest('tr');
						if (tr && !tr.classList.contains('pinaka-sc-template-row')) {
							tr.parentNode.removeChild(tr);
						}
					}
				});

				if (addBtn) {
					addBtn.addEventListener('click', function(e){
						e.preventDefault();
						const clone = tmpl.cloneNode(true);
						clone.style.display = '';
						clone.classList.remove('pinaka-sc-template-row');

						clone.querySelectorAll('input, select').forEach(function(inp){
							inp.name = inp.name.replace('__INDEX__', String(nextIndex));
						});

						table.querySelector('tbody').appendChild(clone);
						nextIndex++;
					});
				}
			})();
		</script>
		<?php
	}


	private function render_promotion_images_tab() {
    	// option key
		$option_key = 'pinaka_pos_promotion_images';

		// Get saved option. Could be an array of IDs or array of URLs from older versions.
		$saved = get_option( $option_key, [] );

		// Normalize to attachment IDs when possible, but keep URLs too.
		$saved_ids = [];
		$saved_urls = [];

		if ( is_array( $saved ) ) {
			foreach ( $saved as $v ) {
				// numeric -> treat as attachment ID
				if ( is_numeric( $v ) && intval( $v ) ) {
					$saved_ids[] = intval( $v );
				} elseif ( is_string( $v ) && ! empty( $v ) ) {
					// If it's a URL, keep it in URLs list (backwards compatibility)
					$saved_urls[] = esc_url_raw( $v );
				}
			}
		}

		// Build a combined preview list: attachment IDs first, then raw URLs.
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Manage Promotion Images', 'pinaka-pos-wp' ); ?></h2>

			<form id="promotion-images-form">
				<?php wp_nonce_field( 'save_promotion_images_action', 'save_promotion_images_nonce' ); ?>

				<div id="promotion-image-preview" style="margin-bottom:10px;">
					<?php
					// Show thumbnails for attachment IDs
					if ( ! empty( $saved_ids ) ) {
						foreach ( $saved_ids as $att_id ) {
							$thumb = wp_get_attachment_image_src( $att_id, 'thumbnail' );
							$full  = wp_get_attachment_url( $att_id );
							if ( $thumb ) {
								?>
								<div class="promotion-image-item" data-id="<?php echo esc_attr( $att_id ); ?>" style="display:inline-block; margin:6px; text-align:center;">
									<img src="<?php echo esc_url( $thumb[0] ); ?>" style="width:100px; height:auto; display:block; border:1px solid #ddd; padding:4px; background:#fff;" />
									<a href="#" class="remove-promotion-image" style="display:block; color:#a00; text-decoration:none;">Remove</a>
								</div>
								<?php
							}
						}
					}

					// Show fallback raw URLs (older data)
					if ( ! empty( $saved_urls ) ) {
						foreach ( $saved_urls as $url ) {
							?>
							<div class="promotion-image-item" data-url="<?php echo esc_url( $url ); ?>" style="display:inline-block; margin:6px; text-align:center;">
								<img src="<?php echo esc_url( $url ); ?>" style="width:100px; height:auto; display:block; border:1px solid #ddd; padding:4px; background:#fff;" />
								<a href="#" class="remove-promotion-image" style="display:block; color:#a00; text-decoration:none;">Remove</a>
							</div>
							<?php
						}
					}
					?>
				</div>

				<!-- Hidden field to hold CSV of attachment IDs -->
				<input type="hidden" id="promotion_image_ids" name="promotion_image_ids" value="<?php echo esc_attr( implode( ',', $saved_ids ) ); ?>" />

				<p>
					<button type="button" id="upload-promotion-images" class="button">Upload / Select Images</button>
					<button type="button" id="add-url-promotion-image" class="button">Add Image by URL</button>
					<button type="button" id="clear-promotion-images" class="button">Clear All</button>
				</p>

				<div id="promotion-image-list-urls" style="margin-top:8px;">
					<!-- UI will append URL rows here if user clicks Add Image by URL -->
				</div>

				<p><button type="submit" class="button button-primary">Save Promotion Images</button></p>
				<div id="promotion-image-message"></div>
			</form>
		</div>

		<style>
		.promotion-image-item img { display:block; margin:0 auto 6px; }
		.promotion-image-item .remove-promotion-image { font-size:13px; cursor:pointer; }
		.promotion-image-url-row { margin-bottom:8px; }
		.promotion-image-url-row input { width:420px; }
		</style>

		<script>
		jQuery(document).ready(function ($) {
			// Ensure wp.media is available (admin should enqueue wp_enqueue_media())
			var frame;
			$('#upload-promotion-images').on('click', function (e) {
				e.preventDefault();
				if (frame) { frame.open(); return; }
				frame = wp.media({
					title: 'Select Promotion Images',
					button: { text: 'Use these images' },
					library: { type: 'image' },
					multiple: true
				});

				frame.on('select', function () {
					var selection = frame.state().get('selection').toArray();
					var ids = $('#promotion_image_ids').val() ? $('#promotion_image_ids').val().split(',') : [];

					selection.forEach(function (att) {
						var id = att.id;
						// get thumbnail URL from att attributes if available
						var thumb = (att.attributes.sizes && att.attributes.sizes.thumbnail) ? att.attributes.sizes.thumbnail.url : att.attributes.url;

						// append preview
						$('#promotion-image-preview').append(
							'<div class="promotion-image-item" data-id="' + id + '" style="display:inline-block; margin:6px; text-align:center;">' +
							'<img src="' + thumb + '" style="width:100px; height:auto; display:block; border:1px solid #ddd; padding:4px; background:#fff;" />' +
							'<a href="#" class="remove-promotion-image" style="display:block; color:#a00; text-decoration:none;">Remove</a>' +
							'</div>'
						);

						ids.push(id);
					});

					// dedupe and set
					ids = Array.from(new Set(ids.map(Number))).filter(Boolean);
					$('#promotion_image_ids').val(ids.join(','));
				});

				frame.open();
			});

			// Remove image (attachment preview or URL preview)
			$('#promotion-image-preview').on('click', '.remove-promotion-image', function (e) {
				e.preventDefault();
				var container = $(this).closest('.promotion-image-item');
				var attId = container.data('id');
				var attUrl = container.data('url');

				if (attId) {
					var ids = $('#promotion_image_ids').val() ? $('#promotion_image_ids').val().split(',') : [];
					ids = ids.filter(function (v) { return Number(v) !== Number(attId); });
					$('#promotion_image_ids').val(ids.join(','));
				} else if (attUrl) {
					// If it's an old URL-only entry, we will remove any corresponding URL input rows too (if any)
					$('#promotion-image-list-urls').find('input[value="' + attUrl + '"]').closest('.promotion-image-url-row').remove();
				}

				container.remove();
			});

			// Add by URL button: append an input row to allow pasting a URL
			$('#add-url-promotion-image').on('click', function (e) {
				e.preventDefault();
				var row = '<div class="promotion-image-url-row">' +
					'<input type="url" name="promotion_image_urls[]" placeholder="Image URL (paste from Media)"/>' +
					' <button type="button" class="add-url-to-preview button">Add</button>' +
					' <button type="button" class="remove-url-row button">Remove</button>' +
					'</div>';
				$('#promotion-image-list-urls').append(row);
			});

			// Remove URL row
			$('#promotion-image-list-urls').on('click', '.remove-url-row', function (e) {
				e.preventDefault();
				$(this).closest('.promotion-image-url-row').remove();
			});

			// Add URL to preview area (and include as raw URL in saved option)
			$('#promotion-image-list-urls').on('click', '.add-url-to-preview', function (e) {
				e.preventDefault();
				var input = $(this).closest('.promotion-image-url-row').find('input[type="url"]');
				var url = input.val().trim();
				if (!url) { return; }
				$('#promotion-image-preview').append(
					'<div class="promotion-image-item" data-url="' + url + '" style="display:inline-block; margin:6px; text-align:center;">' +
					'<img src="' + url + '" style="width:100px; height:auto; display:block; border:1px solid #ddd; padding:4px; background:#fff;" />' +
					'<a href="#" class="remove-promotion-image" style="display:block; color:#a00; text-decoration:none;">Remove</a>' +
					'</div>'
				);
				// keep the URL row so user can remove or edit until save; you may remove the row after adding if you prefer
				input.val(''); // clear input
			});

			// Clear all
			$('#clear-promotion-images').on('click', function (e) {
				e.preventDefault();
				$('#promotion-image-preview').empty();
				$('#promotion_image_ids').val('');
				$('#promotion-image-list-urls').empty();
			});

			$('#promotion-images-form').on('submit', function (e) {
				e.preventDefault();

				var ids = $('#promotion_image_ids').val() || '';
				var urls = [];
				$('#promotion-image-preview').find('.promotion-image-item[data-url]').each(function () {
					var u = $(this).data('url');
					if (u) { urls.push(u); }
				});

				var nonce = $('input[name="save_promotion_images_nonce"]').val();

				var data = {
					action: 'save_promotion_images',
					save_promotion_images_nonce: nonce,
					promotion_image_ids: ids,
					promotion_image_urls: urls
				};

				// compute endpoint
				var ajaxEndpoint = (typeof PinakaAdmin !== 'undefined' && PinakaAdmin.ajax_url) ? PinakaAdmin.ajax_url
								: (typeof ajaxurl !== 'undefined' ? ajaxurl
								: '<?php echo admin_url("admin-ajax.php"); ?>');

				console.info('Sending AJAX to', ajaxEndpoint, data);

				$.ajax({
					url: ajaxEndpoint,
					method: 'POST',
					data: data,
					dataType: 'json', // expect JSON
					timeout: 15000,
					beforeSend: function () {
						$('#promotion-image-message').html('<p>Saving…</p>');
					},
					success: function (response) {
						console.info('AJAX success response:', response);
						if (response && response.success) {
							$('#promotion-image-message').html('<p style="color:green;">' + response.data.message + '</p>');
							setTimeout(function () { location.reload(); }, 400);
						} else {
							$('#promotion-image-message').html('<p style="color:red;">' + ((response && response.data && response.data.message) || 'Server returned an error') + '</p>');
						}
					},
					error: function (jqXHR, textStatus, errorThrown) {
						console.error('AJAX error:', textStatus, errorThrown);
						// show HTTP status and first part of server response
						var body = jqXHR.responseText || '(no response body)';
						console.log('HTTP status:', jqXHR.status);
						console.log('Server response (first 1000 chars):', body.substring ? body.substring(0, 1000) : body);
						$('#promotion-image-message').html('<p style="color:red;">Ajax error: ' + textStatus + '</p>');
					}
				});
			});
		});
		</script>
		<?php
	}



    private function render_general_settings_tab() {
        ?>
        <!-- Your general settings form -->
		<div class="wrap">
			<!-- <h1><?php esc_html_e('General Settings', 'pinaka-pos-wp'); ?></h1> -->
			
			<form method="post" action="options.php" enctype="multipart/form-data">
				<?php
					// Output nonce, action, and option_page for settings
					settings_fields('pinaka_pos_general_settings');
				?>

				<table class="form-table">
					<tr><h3><?php esc_html_e('Business Information', 'pinaka-pos-wp'); ?></h3></tr>
					<tr>
						<th scope="row">
							<label for="pinaka_pos_name"><?php esc_html_e('Name*', 'pinaka-pos-wp'); ?></label>
						</th>
						<td>
							<input type="text" id="pinaka_pos_name" name="pinaka_pos_name" 
								value="<?php echo esc_attr(get_option('pinaka_pos_name', '')); ?>" 
								class="regular-text" required/>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="pinaka_pos_email"><?php esc_html_e('Email', 'pinaka-pos-wp'); ?></label>
						</th>
						<td>
							<input type="email" id="pinaka_pos_email" name="pinaka_pos_email" 
								value="<?php echo esc_attr(get_option('pinaka_pos_email', '')); ?>" 
								class="regular-text" />
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="pinaka_pos_phone"><?php esc_html_e('Phone', 'pinaka-pos-wp'); ?></label>
						</th>
						<td>
							<input type="text" id="pinaka_pos_phone" name="pinaka_pos_phone" 
								value="<?php echo esc_attr(get_option('pinaka_pos_phone', '')); ?>" 
								class="regular-text" />
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="pinaka_pos_logo"><?php esc_html_e('Logo', 'pinaka-pos-wp'); ?></label>
						</th>
						<td>
							<input type="file" id="pinaka_pos_logo" name="pinaka_pos_logo" class="regular-text" />
							<?php
							$logo = get_option('pinaka_pos_logo', '');
							if ($logo) {
								echo '<p>' . esc_html__('Current Logo:', 'pinaka-pos-wp') . '</p>';
								echo '<img src="' . esc_url($logo) . '" alt="' . esc_attr__('Logo', 'pinaka-pos-wp') . '" style="max-width: 150px;"/>';
							}
							?>
						</td>
					</tr>

				</table>
				<table class="form-table">
					<tr><h3><?php esc_html_e('Business Address', 'pinaka-pos-wp'); ?></h3></tr>

					<tr>
						<th scope="row">
							<label for="pinaka_pos_business_address"><?php esc_html_e('Address', 'pinaka-pos-wp'); ?></label>
						</th>
						<td>
							<input type="text" id="pinaka_pos_business_address" name="pinaka_pos_business_address" 
								value="<?php echo esc_attr(get_option('pinaka_pos_business_address', '')); ?>" 
								class="regular-text" />
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="pinaka_pos_business_city"><?php esc_html_e('City', 'pinaka-pos-wp'); ?></label>
						</th>
						<td>
							<input type="text" id="pinaka_pos_business_city" name="pinaka_pos_business_city" 
								value="<?php echo esc_attr(get_option('pinaka_pos_business_city', '')); ?>" 
								class="regular-text" />
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="pinaka_pos_business_state"><?php esc_html_e('State', 'pinaka-pos-wp'); ?></label>
						</th>
						<td>
							<input type="text" id="pinaka_pos_business_state" name="pinaka_pos_business_state" 
								value="<?php echo esc_attr(get_option('pinaka_pos_business_state', '')); ?>" 
								class="regular-text" />
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="pinaka_pos_business_postcode"><?php esc_html_e('ZIP', 'pinaka-pos-wp'); ?></label>
						</th>
						<td>
							<input type="text" id="pinaka_pos_business_postcode" name="pinaka_pos_business_postcode" 
								value="<?php echo esc_attr(get_option('pinaka_pos_business_postcode', '')); ?>" 
								class="regular-text" />
						</td>
					</tr>
					
				</table>

				<?php submit_button(__('Save General Settings', 'pinaka-pos-wp')); ?>
			</form>
		</div>

        <?php
    }

    private function render_theme_settings_tab() {
        
        global $_wp_admin_css_colors;
	
		$user_id = get_current_user_id();
		$current_color = get_user_option( 'admin_color', $user_id );
	
		if ( empty( $current_color ) || ! isset( $_wp_admin_css_colors[ $current_color ] ) ) {
			$current_color = 'fresh'; // Default color scheme
		}
		?>
	
		<form id="color-scheme-form" method="post">
			<?php wp_nonce_field( 'update_admin_color_scheme', 'color_scheme_nonce' ); ?>
	
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php _e( 'Select Admin Color Scheme' ); ?></th>
					<td>
						<fieldset id="color-picker" class="scheme-list">
							<legend class="screen-reader-text"><span>
								<?php
								/* translators: Hidden accessibility text. */
								_e( 'Admin Color Scheme' );
								?>
							</span></legend>
							<?php
							wp_nonce_field( 'save-color-scheme', 'color-nonce', false );
							foreach ( $_wp_admin_css_colors as $color => $color_info ) :

								?>
								<div class="color-option <?php echo ( $color === $current_color ) ? 'selected' : ''; ?>">
									<input name="admin_color" id="admin_color_<?php echo esc_attr( $color ); ?>" type="radio" value="<?php echo esc_attr( $color ); ?>" class="tog" <?php checked( $color, $current_color ); ?> />
									<input type="hidden" class="css_url" value="<?php echo esc_url( $color_info->url ); ?>" />
									<input type="hidden" class="icon_colors" value="<?php echo esc_attr( wp_json_encode( array( 'icons' => $color_info->icon_colors ) ) ); ?>" />
									<label for="admin_color_<?php echo esc_attr( $color ); ?>"><?php echo esc_html( $color_info->name ); ?></label>
									<div class="color-palette">
									<?php
										foreach ( $color_info->colors as $html_color ) {
											?>
											<div class="color-palette-shade" style="background-color: <?php echo esc_attr( $html_color ); ?>">&nbsp;</div>
											<?php
										}
									?>
									</div>
								</div>
								<?php

							endforeach;
							?>
						</fieldset>
					</td>
				</tr>
			</table>
	
			<input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>" />
			<button type="submit" class="button button-primary"><?php _e( 'Save Color Scheme' ); ?></button>
		</form>
	
		<div id="color-scheme-message"></div>
	
		<script>
			jQuery(document).ready(function ($) {
				function submitColorSchemeForm() {
					var formData = $('#color-scheme-form').serialize();

					$.ajax({
						type: 'POST',
						url: ajaxurl,
						data: formData + '&action=update_admin_color_scheme',
						beforeSend: function () {
							$('#color-scheme-message').html('<p>Saving...</p>');
						},
						success: function (response) {
							$('#color-scheme-message').html('<p style="color:green;">' + response.data.message + '</p>');
							location.reload();
						},
						error: function (response) {
							let errorMsg = response.responseJSON && response.responseJSON.data && response.responseJSON.data.message
								? response.responseJSON.data.message
								: 'Error updating color scheme';
							$('#color-scheme-message').html('<p style="color:red;">' + errorMsg + '</p>');
						}
					});
				}

				$('#color-scheme-form').on('submit', function (e) {
					e.preventDefault();
					submitColorSchemeForm();
				});

				$('input[name="admin_color"]').on('change', function () {
					submitColorSchemeForm();
				});
			});

		</script>
		<?php
	
	}
    private function render_financial_settings_tab() {
		$all_roles = wp_roles()->roles;

		// Filter out default roles
		$custom_roles = array_filter($all_roles, function($key) {
			return !in_array($key, ['administrator', 'editor', 'author', 'contributor', 'subscriber', 'customer', 'shop_manager']);
		}, ARRAY_FILTER_USE_KEY);

		// Selected role from URL or default to first
		$selected_role = isset($_GET['selected_role']) ? sanitize_text_field($_GET['selected_role']) : key($custom_roles);
		$all_settings = get_option('pinaka_pos_menu_settings', []);
		$menu_settings = isset($all_settings[$selected_role]) ? $all_settings[$selected_role] : [];
		$menu_items = [
			'vendors' => 'Manage Vendors',
			'vendor_payments' => 'Manage Vendor Payments',
			'shifts' => 'Manage Shifts',
			'payments' => 'Manage Payments',
			'settings' => 'Settings'
		];
		?>

		<div class="wrap">
			<!-- Create new custom role -->
			<h3>Create New Role</h3>
			<form id="create-role-form">
				<input type="text" name="new_role_key" placeholder="Role Key (e.g. manager)" required>
				<input type="text" name="new_role_name" placeholder="Role Name (e.g. Manager)" required>
				<?php wp_nonce_field('create_custom_role_action', 'create_custom_role_nonce'); ?>
				<button type="submit" class="button">Create Role</button>
				<span id="role-create-message" style="margin-left: 10px;"></span>
			</form>

			<hr>

			<!-- Select existing role -->

			<form method="get" action="">
				<input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">
				<input type="hidden" name="tab" value="<?php echo esc_attr($_GET['tab']); ?>"> <!-- Add this line -->
				<select name="selected_role" onchange="this.form.submit()">
					<?php foreach (wp_roles()->roles as $role_key => $role_details): ?>
						<?php if (!in_array($role_key, ['administrator', 'editor', 'author', 'contributor', 'subscriber', 'customer', 'shop_manager'])): ?>
							<option value="<?php echo esc_attr($role_key); ?>" <?php selected($role_key, $selected_role); ?>>
								<?php echo esc_html($role_details['name']); ?>
							</option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
			</form>
			<?php 

			?>
			<!-- Privilege Form -->
			<form id="pinaka-pos-menu-settings-form">
				<input type="hidden" name="role" value="<?php echo esc_attr($selected_role); ?>">
				<?php wp_nonce_field('pinaka_pos_menu_settings_action', 'pinaka_pos_menu_nonce'); ?>
				<table class="form-table">
					<?php foreach ($menu_items as $key => $label) : ?>
						<tr>
							<th scope="row"><?php echo esc_html($label); ?></th>
							<td>
								<input type="checkbox" name="menu_items[]" value="<?php echo esc_attr($key); ?>" 
									<?php checked(in_array($key, $menu_settings)); ?>>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
				<p>
					<button type="submit" class="button button-primary">Save Menu Settings</button>
				</p>
				<div id="menu-settings-message"></div>
			</form>
		</div>

		<script>
		jQuery(document).ready(function($) {
			// Save privileges
			$('#pinaka-pos-menu-settings-form').on('submit', function(e) {
				e.preventDefault();
				var formData = $(this).serialize();

				$.post(ajaxurl, formData + '&action=save_pinaka_pos_menu_settings', function(response) {
					if (response.success) {
						$('#menu-settings-message').html('<p style="color:green;">' + response.data.message + '</p>');
						location.reload();
					} else {
						$('#menu-settings-message').html('<p style="color:red;">' + (response.data?.message || 'Error') + '</p>');
					}
				});
			});

			// Create new role
			$('#create-role-form').on('submit', function(e) {
				e.preventDefault();
				let formData = $(this).serialize();

				$.post(ajaxurl, formData + '&action=create_custom_role', function(response) {
					if (response.success) {
						$('#role-create-message').text(response.data.message).css('color', 'green');
						location.href = location.pathname + '?page=' + new URLSearchParams(location.search).get('page') + '&tab=tab3&selected_role=' + response.data.role;
					} else {
						$('#role-create-message').text(response.data.message).css('color', 'red');
					}
				});
			});
		});
		</script>
		<?php
	}

	private function render_denominations_tab() {
		$denominations = get_option('pinaka_pos_denominations', []);
		$safedrop_denominations = get_option('pinaka_pos_safedrop_denominations', []);
		$coins_denominations = get_option('pinaka_pos_coins_denominations', []);
		$safe_denominations = get_option('pinaka_pos_safe_denominations', []);
		?>

		<h3>Tube Size and Safe Drop and Cash Drawer Amount</h3>

		<form id="tube-safe-cash-form" method="post">
			<table class="form-table">
				<tr>
					<th><label for="enable_safes">Enable Safes:</label></th>
					<td>
						<input type="checkbox" name="enable_safes" id="enable_safes"
							value="1" <?php checked( get_option('enable_safes'), '1' ); ?> />
						<span class="description">Check to enable safe deposits functionality</span>
					</td>
				</tr>

				<tr>
					<th><label for="enable_safes">Enable Safes Drop:</label></th>
					<td>
						<input type="checkbox" name="enable_safes_drop" id="enable_safes_drop"
							value="1" <?php checked( get_option('enable_safes_drop'), '1' ); ?> />
						<span class="description">Check to enable safe drop deposits functionality</span>
					</td>
				</tr>
				<tr>
					<th><label for="currency_symbol">Currency Code (e.g. $, ₹ ):</label></th>
					<td><input type="text" name="currency_symbol" id="currency_symbol" value="<?php echo esc_attr(get_option('currency_symbol')); ?>" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label for="tube_size">Safe Drop Tube Size (e.g. 11):</label></th>
					<td><input type="text" name="tube_size" id="tube_size" value="<?php echo esc_attr(get_option('tube_size')); ?>" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label for="safe_drop_amount">Safe Drop Amount:</label></th>
					<td><input type="text" name="safe_drop_amount" id="safe_drop_amount" value="<?php echo esc_attr(get_option('safe_drop_amount')); ?>" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label for="cash_drawer_amount">Cash Drawer Amount:</label></th>
					<td><input type="text" name="cash_drawer_amount" id="cash_drawer_amount" value="<?php echo esc_attr(get_option('cash_drawer_amount')); ?>" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label for="cash_drawer_amount">No Of Payouts Per order:</label></th>
					<td><input type="text" name="no_of_payouts" id="no_of_payouts" value="<?php echo esc_attr(get_option('no_of_payouts')); ?>" class="regular-text" required></td>
				</tr>
			</table>

			<?php wp_nonce_field('save_tube_safe_cash_settings', 'tube_safe_cash_nonce'); ?>
			<p><input type="submit" name="save_tube_safe_cash" class="button button-primary" value="Save Settings"></p>
		</form>

		<div class="pinaka-denomination-grid">
			<div class="pinaka-col">
				<div class="wrap">
					<h2>Manage Cash Denominations</h2>

					<form id="denominations-form">
						<?php wp_nonce_field('save_denominations_action', 'save_denominations_nonce'); ?>

						<div id="denomination-list">
							<?php if (!empty($denominations)): ?>
								<?php foreach ($denominations as $denom): ?>
									<div class="denomination-row">
										<input type="text" name="denominations[denom][]" value="<?php echo esc_attr($denom['denom']); ?>" placeholder="Denomination (e.g., ₹100 Note)" required>
										<input type="url" name="denominations[image][]" value="<?php echo esc_attr($denom['image']); ?>" placeholder="Image URL (paste from Media)">
										<button type="button" class="remove-denomination button">Remove</button>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>

						<button type="button" id="add-denomination" class="button">Add Denomination</button>

						<p><button type="submit" class="button button-primary">Save Denominations</button></p>

						<div id="denomination-message"></div>
					</form>
				</div>

				<div class="wrap">
					<h2>Manage Tube Denominations</h2>

					<form id="safedrop-denominations-form">
						<?php wp_nonce_field('save_safedrop_denominations_action', 'save_safedrop_denominations_nonce'); ?>

						<div id="safedrop-denomination-list">
							<?php
								$safedrop_denominations = get_option('pinaka_pos_safedrop_denominations', []);
								if (!is_array($safedrop_denominations)) {
									$safedrop_denominations = [];
								}

								foreach ($safedrop_denominations as $item): ?>
									<div class="safedrop-denomination-row">
										<input type="text" name="safedrop_denominations[]" value="<?php echo esc_attr($item['denom'] ?? ''); ?>" required>
										<input type="text" name="safedrop_denominations_limit[]" value="<?php echo esc_attr($item['tube_limit'] ?? ''); ?>" step="1" required>
										<input type="text" name="safedrop_denominations_symbol[]" value="<?php echo esc_attr($item['symbol'] ?? ''); ?>" required>
										<button type="button" class="remove-safedrop-denomination button">Remove</button>
									</div>
							<?php endforeach; ?>
						</div>

						<button type="button" id="add-safedrop-denomination" class="button">Add Denomination</button>

						<p><button type="submit" class="button button-primary">Save Denominations</button></p>

						<div id="safedrop-denomination-message"></div>
					</form>
				</div>
			</div>
			<div class="pinaka-col">
				<div class="wrap">
					<h2>Manage Coin Denominations</h2>

					<form id="coins-denominations-form">
						<?php wp_nonce_field('save_coins_denominations_action', 'save_coins_denominations_nonce'); ?>

						<div id="coins-denomination-list">
							<?php if (!empty($coins_denominations)): ?>
								<?php foreach ($coins_denominations as $value): ?>
									<div class="coins-denomination-row">
										<input type="text" name="coins_denominations[denom][]" value="<?php echo esc_attr($value['denom']); ?>" required>
										<input type="text" name="coins_denominations[image][]" value="<?php echo esc_attr($value['image']); ?>" required>
										<button type="button" class="remove-coins-denomination button">Remove</button>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>

						<button type="button" id="add-coins-denomination" class="button">Add Denomination</button>

						<p><button type="submit" class="button button-primary">Save Denominations</button></p>

						<div id="coins-denomination-message"></div>
					</form>
				</div>

				<div class="wrap">
					<h2>Manage Safe Drops Denominations</h2>

					<form id="safe-denominations-form">
						<?php wp_nonce_field('save_safe_denominations_action', 'save_safe_denominations_nonce'); ?>

						<div id="safe-denomination-list">
							<?php if (!empty($safe_denominations)): ?>
								<?php foreach ($safe_denominations as $value): ?>
									<div class="safe-denomination-row">
										<input type="text" name="safe_denominations[denom][]" value="<?php echo esc_attr($value['denom']); ?>" required>
										<input type="text" name="safe_denominations[image][]" value="<?php echo esc_attr($value['image']); ?>" required>
										<button type="button" class="remove-safe-denomination button">Remove</button>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>

						<button type="button" id="add-safe-denomination" class="button">Add Denomination</button>

						<p><button type="submit" class="button button-primary">Save Denominations</button></p>

						<div id="safe-denomination-message"></div>
					</form>
				</div>
			</div>
		</div>
		<!-- <style>
			
			.denomination-row {
				margin-bottom: 8px;
			}
			.denomination-row input {
				width: 100px;
			}

			.safedrop-denomination-row {
				margin-bottom: 8px;
			}
			.coins-denomination-row {
				margin-bottom: 8px;
			}
			.coins-denomination-row input {
				width: 120px;
			}

			.denomination-row input {
				width: 100px;
			}

			.denomination-row input {
				width: 100px;
			}

			.safe-denomination-row {
				margin-bottom: 8px;
			}
			.safe-denomination-row input {
				width: 100px;
			}

			.safedrop-denomination-row input {
				width: 120px !important;
			}
			.pinaka-denomination-grid {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 24px;
				align-items: start;
			}

			.pinaka-col {
				display: flex;
				flex-direction: column;
				gap: 24px;
			}

			/* Make it responsive for smaller screens */
			@media (max-width: 1100px) {
				.pinaka-denomination-grid {
					grid-template-columns: 1fr;
				}
			}
			/* =========================================
			PINAKA – DENOMINATIONS ADMIN UI
			========================================= */

			/* ---- GRID LAYOUT ---- */
			.pinaka-denomination-grid {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 24px;
				align-items: start;
			}

			.pinaka-col {
				display: flex;
				flex-direction: column;
				gap: 24px;
			}

			/* Responsive */
			@media (max-width: 1100px) {
				.pinaka-denomination-grid {
					grid-template-columns: 1fr;
				}
			}

			/* ---- CARD STYLE ---- */
			.pinaka-denomination-grid .wrap {
				background: #fff;
				border: 1px solid #ccd0d4;
				border-radius: 6px;
				padding: 14px;
			}

			.pinaka-denomination-grid h2 {
				margin-top: 0;
			}

			/* ---- ROW LAYOUT ---- */
			.pinaka-denomination-grid
			.denomination-row,
			.coins-denomination-row,
			.safe-denomination-row,
			.safedrop-denomination-row {
				display: flex;
				align-items: center;
				gap: 10px;
				margin-bottom: 8px;
				flex-wrap: wrap;
			}

			/* ---- INPUT BASE ---- */
			.pinaka-denomination-grid input {
				padding: 4px 6px;
				font-size: 13px;
			}

			/* ---- INPUT WIDTHS (ROLE BASED) ---- */

			/* Denomination value */
			.pinaka-denomination-grid input[name*="[denom]"],
			{
				width: 120px;
			}
			.pinaka-denomination-grid input[name="safedrop_denominations[]"] 
			{
				width: 100px;
			}
			/* Limits / counts */
			.pinaka-denomination-grid input[name*="limit"] {
				width: 80px;
			}

			/* Currency symbol */
			.pinaka-denomination-grid input[name*="symbol"] {
				width: 90px;
				text-align: center;
			}

			/* Image URL */
			.pinaka-denomination-grid input[name*="[image]"] {
				width: 295px;
			}

			/* ---- BUTTONS ---- */
			.pinaka-denomination-grid .button {
				padding: 4px 8px;
				height: auto;
				line-height: 1.2;
			}

			.pinaka-denomination-grid .button-primary {
				min-width: 160px;
			}

			/* Space before action buttons */
			.pinaka-denomination-grid form > button,
			.pinaka-denomination-grid form > p {
				margin-top: 12px;
			}

			/* ---- REMOVE BUTTONS ---- */
			.remove-denomination,
			.remove-coins-denomination,
			.remove-safe-denomination,
			.remove-safedrop-denomination {
				white-space: nowrap;
			}

			/* ---- PLACEHOLDERS ---- */
			.pinaka-denomination-grid input::placeholder {
				font-size: 12px;
				opacity: 0.8;
			}
			/* =========================================
			FIT FORMS INSIDE CARD WIDTH
			========================================= */

			/* ----- GENERIC ROW GRID ----- */
			.pinaka-denomination-grid .denomination-row,
			.pinaka-denomination-grid .coins-denomination-row,
			.pinaka-denomination-grid .safe-denomination-row {
				display: grid;
				grid-template-columns: 90px 1fr auto; /* denom | image | button */
				gap: 10px;
				align-items: center;
				margin-bottom: 10px;
			}

			/* ----- SAFE DROP ROW (3 inputs + button) ----- */
			/* .pinaka-denomination-grid .safedrop-denomination-row {
				display: grid;
				grid-template-columns: 100px 100px 70px auto;
				gap: 10px;
				align-items: center;
				margin-bottom: 10px;
			} */

			/* ----- INPUTS ----- */
			.pinaka-denomination-grid input {
				width: 100%;
				box-sizing: border-box;
				padding: 6px 8px;
				font-size: 13px;
			}

			/* Symbol centered */
			.pinaka-denomination-grid input[name*="symbol"] {
				text-align: center;
			}

			/* ----- BUTTONS ----- */
			.pinaka-denomination-grid .button {
				white-space: nowrap;
				height: auto;
			}

			/* ----- ACTION BUTTON ALIGNMENT ----- */
			.pinaka-denomination-grid form > button,
			.pinaka-denomination-grid form > p {
				margin-top: 14px;
			}

			/* ----- RESPONSIVE FALLBACK ----- */
			@media (max-width: 1200px) {
				.pinaka-denomination-grid .denomination-row,
				.pinaka-denomination-grid .coins-denomination-row,
				.pinaka-denomination-grid .safe-denomination-row,
				.pinaka-denomination-grid .safedrop-denomination-row {
					grid-template-columns: 1fr;
				}
			}
			/* ===============================
			EQUAL HEIGHT DENOMINATION CARDS
			=============================== */

			.pinaka-denomination-grid {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 24px;
				align-items: stretch;
			}

			.pinaka-col {
				display: grid;
				grid-auto-rows: 1fr;
				gap: 24px;
			}

			.pinaka-denomination-grid .wrap {
				height: 100%;
				display: flex;
				flex-direction: column;
			}

			/* Push buttons to bottom */
			.pinaka-denomination-grid form {
				display: flex;
				flex-direction: column;
				height: 100%;
			}

			.pinaka-denomination-grid form > p:last-child,
			.pinaka-denomination-grid form > button:last-child {
				margin-top: auto;
			}

			/* Responsive */
			@media (max-width: 1100px) {
				.pinaka-denomination-grid {
					grid-template-columns: 1fr;
				}
			}


		</style> -->
		<style>
			.pinaka-denomination-grid {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 24px;
				align-items: stretch;
				margin:10px;
			}
			.pinaka-card {
				background: #ffffff;
				border: 1px solid #dcdcde;
				border-radius: 8px;
				padding: 16px;
				box-shadow: 0 1px 2px rgba(0,0,0,0.05);
			}
			.pinaka-col {
				display: grid;
				grid-auto-rows: 1fr; /* equal height blocks */
				gap: 24px;
			}

			/* Responsive */
			@media (max-width: 1100px) {
				.pinaka-denomination-grid {
					grid-template-columns: 1fr;
				}
			}

			/* ---------- CARD STYLE ---------- */
			/* .pinaka-denomination-grid .wrap {
				background: #fff;
				border: 1px solid #ccd0d4;
				border-radius: 6px;
				padding: 14px;
				height: 100%;
				display: flex;
				flex-direction: column;
			} */

			.pinaka-denomination-grid h2 {
				margin-top: 0;
			}

			/* ---------- FORM STRUCTURE ---------- */
			.pinaka-denomination-grid form {
				display: flex;
				flex-direction: column;
				height: 100%;
			}

			/* Push action buttons to bottom */
			.pinaka-denomination-grid form > p:last-child,
			.pinaka-denomination-grid form > button:last-child {
				margin-top: auto;
			}

			/* ---------- ROW LAYOUT ---------- */
			.pinaka-denomination-grid
			.denomination-row,
			.pinaka-denomination-grid
			.coins-denomination-row,
			.pinaka-denomination-grid
			.safe-denomination-row {
				display: grid;
				grid-template-columns: 90px 1fr auto; /* value | image | remove */
				gap: 10px;
				align-items: center;
				margin-bottom: 10px;
			}

			/* Safe Drop rows (3 inputs + remove) */
			.pinaka-denomination-grid
			.safedrop-denomination-row {
				display: grid;
				grid-template-columns: 100px 80px 70px auto;
				gap: 10px;
				align-items: center;
				margin-bottom: 10px;
			}

			/* ---------- INPUTS ---------- */
			.pinaka-denomination-grid input {
				width: 100%;
				box-sizing: border-box;
				padding: 6px 8px;
				font-size: 13px;
			}

			/* Symbol centered */
			.pinaka-denomination-grid input[name*="symbol"] {
				text-align: center;
			}

			/* ---------- BUTTONS ---------- */
			.pinaka-denomination-grid .button {
				padding: 4px 8px;
				height: auto;
				line-height: 1.2;
				white-space: nowrap;
			}

			.pinaka-denomination-grid .button-primary {
				min-width: 160px;
			}

			/* ---------- PLACEHOLDERS ---------- */
			.pinaka-denomination-grid input::placeholder {
				font-size: 12px;
				opacity: 0.8;
			}

			/* ---------- MOBILE FALLBACK ---------- */
			@media (max-width: 1200px) {
				.pinaka-denomination-grid
				.denomination-row,
				.pinaka-denomination-grid
				.coins-denomination-row,
				.pinaka-denomination-grid
				.safe-denomination-row,
				.pinaka-denomination-grid
				.safedrop-denomination-row {
					grid-template-columns: 1fr;
				}
			}

		</style>
        <script>
		jQuery(document).ready(function($) {
			$('#add-denomination').on('click', function () {
				const row = `
					<div class="denomination-row">
						<input type="text" name="denominations[denom][]" placeholder="Denomination (e.g., 50)" required>
						<input type="url" name="denominations[image][]" placeholder="Image URL (paste from Media)">
						<button type="button" class="remove-denomination button">Remove</button>
					</div>
				`;
				$('#denomination-list').append(row);
			});

			$('#denomination-list').on('click', '.remove-denomination', function () {
				$(this).closest('.denomination-row').remove();
			});

			$('#denominations-form').on('submit', function(e) {
				e.preventDefault();
				const formData = $(this).serialize();

				$.post(ajaxurl, formData + '&action=save_denominations', function(response) {
					if (response.success) {
						$('#denomination-message').html('<p style="color:green;">' + response.data.message + '</p>');
						setTimeout(() => location.reload(), 300);
					} else {
						$('#denomination-message').html('<p style="color:red;">' + (response.data?.message || 'Error saving.') + '</p>');
					}
				});
			});

			// Safedrop denominations
			$('#add-safedrop-denomination').on('click', function () {
				$('#safedrop-denomination-list').append(`
					<div class="safedrop-denomination-row">
						<input type="text" name="safedrop_denominations[]" placeholder="Denomination" step="0.01" required />
						<input type="text" name="safedrop_denominations_limit[]" placeholder="Denomination Limit" step="1" required />
						<input type="text" name="safedrop_denominations_symbol[]" placeholder="Symbol" required />
						<button type="button" class="remove-safedrop-denomination button">Remove</button>
					</div>
				`);
			});

			$('#safedrop-denomination-list').on('click', '.remove-safedrop-denomination', function () {
				$(this).closest('.safedrop-denomination-row').remove();
			});

			$('#safedrop-denominations-form').on('submit', function(e) {
				e.preventDefault();
				const formData = $(this).serialize();

				$.post(ajaxurl, formData + '&action=save_safedrop_denominations', function(response) {
					if (response.success) {
						$('#safedrop-denomination-message').html('<p style="color:green;">' + response.data.message + '</p>');
						setTimeout(() => location.reload(), 300);
					} else {
						$('#safedrop-denomination-message').html('<p style="color:red;">' + (response.data?.message || 'Error saving.') + '</p>');
					}
				});
			});


			// Coins denominations
			$('#add-coins-denomination').on('click', function () {
				$('#coins-denomination-list').append(`
					<div class="coins-denomination-row">
						<input type="text" placeholder="Denomination (e.g., 50)" name="coins_denominations[denom][]" required />
						<input type="text" placeholder="Image URL (paste from Media)"  name="coins_denominations[image][]" required>

						<button type="button" class="remove-coins-denomination button">Remove</button>
					</div>
				`);
			});

			$('#coins-denomination-list').on('click', '.remove-coins-denomination', function () {
				$(this).closest('.coins-denomination-row').remove();
			});

			$('#coins-denominations-form').on('submit', function(e) {
				e.preventDefault();
				const formData = $(this).serialize();

				$.post(ajaxurl, formData + '&action=save_coins_denominations', function(response) {
					if (response.success) {
						$('#coins-denomination-message').html('<p style="color:green;">' + response.data.message + '</p>');
						setTimeout(() => location.reload(), 300);
					} else {
						$('#coins-denomination-message').html('<p style="color:red;">' + (response.data?.message || 'Error saving.') + '</p>');
					}
				});
			});


			// Safe denominations
			$('#add-safe-denomination').on('click', function () {
				$('#safe-denomination-list').append(`
					<div class="safe-denomination-row">
						<input type="text" placeholder="Denomination (e.g., 50)" name="safe_denominations[denom][]" required />
						<input type="text" placeholder="Image URL (paste from Media)"  name="safe_denominations[image][]" required>

						<button type="button" class="remove-safe-denomination button">Remove</button>
					</div>
				`);
			});

			$('#safe-denomination-list').on('click', '.remove-safe-denomination', function () {
				$(this).closest('.safe-denomination-row').remove();
			});

			$('#safe-denominations-form').on('submit', function(e) {
				e.preventDefault();
				const formData = $(this).serialize();

				$.post(ajaxurl, formData + '&action=save_safe_denominations', function(response) {
					if (response.success) {
						$('#safe-denomination-message').html('<p style="color:green;">' + response.data.message + '</p>');
						setTimeout(() => location.reload(), 300);
					} else {
						$('#safe-denomination-message').html('<p style="color:red;">' + (response.data?.message || 'Error saving.') + '</p>');
					}
				});
			});
		});
		</script>
		<?php
	}

}


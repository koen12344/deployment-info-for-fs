<?php

namespace Koen12344\DeploymentInfoForFs;




use WP_REST_Request;

class Plugin
{
	const DOMAIN = 'deployment-info-for-fs';

	const VERSION = '0.1.0';

	private $plugin_info;

	private $sdk;

	public function __construct($file){
		$this->plugin_info = [
			'plugin_basename'       => plugin_basename($file),
			'plugin_domain'         => self::DOMAIN,
			'plugin_path'           => plugin_dir_path($file),
			'plugin_relative_path'  => basename(plugin_dir_path($file)),
			'plugin_url'            => plugin_dir_url($file),
		];
	}

	/**
	 * @return \Freemius_Api
	 */
	private function get_sdk(): \Freemius_Api
	{
		if(!is_null($this->sdk)){
			return $this->sdk;
		}

		//For some reason the package doesn't like to be loaded through composer
		require_once($this->plugin_info['plugin_path'].'/freemius/Freemius.php');
		$dev_id = get_option('difs_fs_dev_id');
		$fs_pub = get_option('difs_fs_pub');
		$fs_priv = get_option('difs_fs_priv');
		$this->sdk = new \Freemius_Api('developer', $dev_id, $fs_pub, $fs_priv);

		return $this->sdk;
	}

	public static function activate(){
		if(get_option('difs_rest_key')){
			return;
		}

		$bytes = random_bytes(20);
		$rest_key = bin2hex($bytes);
		update_option('difs_rest_key', $rest_key);
	}

	public static function deactivate(){

	}

	public function load_plugin(){
		add_shortcode('difs_plugin_info', [$this, 'plugin_info_shortcode']);

		add_action('admin_menu', [$this, 'add_options_page']);

		add_action('admin_init', [$this, 'register_settings']);

		add_action('rest_api_init', [$this, 'register_rest_routes']);
	}

	public function add_options_page(){
		add_options_page(
			__('Deployment info for FS', 'deployment-info-for-fs'),
			__('Deployment info for FS', 'deployment-info-for-fs'),
			'manage_options',
			'difs_options_page',
			[$this, 'render_options_page']
		);
	}

	public function render_options_page(){
		if(!current_user_can('manage_options')){
			return;
		}

		if (isset($_GET['settings-updated'])) {
			add_settings_error('difs_settings_messages', 'difs_settings_updated', __('Settings Saved', 'deployment-info-for-fs'), 'updated');
		}

		//settings_errors('difs_settings_messages');
		$rest_key = get_option('difs_rest_key');
		?>
		<div class="wrap">
			<?php esc_html_e('Please set up a new webhook in your Freemius dashboard for each plugin you want to show updates on, with the "plugin.version.released" event on the following URL:', 'deployment-info-for-fs'); ?><br /><br />
			<input type="text" readonly value="<?php echo esc_url(rest_url("difs/v1/plugin_update/?key={$rest_key}")); ?>" style="width:800px;" />
			<form action="options.php" method="post">
				<?php
				settings_fields('difs_fs');
				do_settings_sections('difs_plugin_settings');
				submit_button('Save Changes');
				?>
			</form>
		</div>
		<?php

	}

	public function register_settings(){
		register_setting('difs_fs', 'difs_fs_pub');
		register_setting('difs_fs', 'difs_fs_priv');
		register_setting('difs_fs', 'difs_fs_dev_id');

		add_settings_section(
			'difs_fs_settings_section',
			__('Freemius settings', 'deployment-info-for-fs'),
			function($args){},
			'difs_plugin_settings'
		);

		add_settings_field('difs_fs_dev_id', __('Freemius dev id', 'deployment-info-for-fs'), [$this, 'fs_dev_id_field'], 'difs_plugin_settings', 'difs_fs_settings_section');
		add_settings_field('difs_fs_pub_field', __('Freemius public key', 'deployment-info-for-fs'), [$this, 'fs_pub_field'], 'difs_plugin_settings', 'difs_fs_settings_section');
		add_settings_field('difs_fs_priv_field', __('Freemius private key', 'deployment-info-for-fs'), [$this, 'fs_priv_field'], 'difs_plugin_settings', 'difs_fs_settings_section');
	}

	public function fs_dev_id_field($args){
		$dev_id = get_option('difs_fs_dev_id');
		?>
		<input type="text" name="difs_fs_dev_id" id="difs_fs_dev_id" value="<?php echo isset($dev_id) ? intval($dev_id) : ''; ?>"/>
		<?php
	}

	public function fs_pub_field($args){
		$pub_key = get_option('difs_fs_pub');
		?>
		<input type="text" name="difs_fs_pub" id="difs_fs_pub" value="<?php echo isset($pub_key) ? esc_textarea($pub_key) : ''; ?>"/>
		<?php
	}

	public function fs_priv_field($args){
		$priv_key = get_option('difs_fs_priv');
		?>
		<input type="text" name="difs_fs_priv" id="difs_fs_priv" value="<?php echo isset($priv_key) ? esc_textarea($priv_key) : ''; ?>"/>
		<?php
	}


	public function register_rest_routes(){
		register_rest_route('difs/v1', '/plugin_update/', [
			'methods'	=> \WP_REST_Server::EDITABLE,
			'callback'	=> [$this, 'handle_update_webhook'],
		]);
	}

	public function handle_update_webhook(WP_REST_Request $request){
		$security_key = $request->get_param('key');
		$stored_key = get_option('difs_rest_key');
		if($security_key !== $stored_key){
			return new \WP_Error('invalid_sec_key', __('Invalid security key', 'deployment-info-for-fs'));
		}

		$type = $request->get_param('type');

		if($type !== 'plugin.version.released'){
			return new \WP_REST_Response("Hook not of type plugin.version.released, quitting.");
		}

		$plugin_id = intval($request->get_param('plugin_id'));

		$this->get_most_recent_release($plugin_id, true);
		$this->get_plugin_info($plugin_id, true);

		return new \WP_REST_Response("Latest release info updated");
	}
	//---


	/**
	 * @param $plugin_id
	 * @return object|null
	 */
	private function get_most_recent_release($plugin_id, $refresh = false) {
		/*
			[plugin_id] => 1828
			[developer_id] => 1657
			[slug] =>
			[premium_slug] => post-to-google-my-business-premium
			[version] => 3.1.14
			[sdk_version] => 2.5.10
			[requires_platform_version] => 4.9.0
			[requires_programming_language_version] => 7.0
			[tested_up_to_version] => 6.2.2
			[downloaded] => 69
			[has_free] => 1
			[has_premium] => 1
			[release_mode] => released
			[limit] =>
			[uniques] => 307
			[id] => 59212
			[created] => 2023-07-05 10:46:10
			[updated] => 2023-07-06 13:25:25
			[is_released] =>
		*/

		$cached = get_transient('difs_latest_tag_'.$plugin_id);

		if($cached && !$refresh){
			return $cached;
		}

		$query = build_query([
			'fields' => 'release_mode,created,version,requires_platform_version,tested_up_to_version,updated,requires_programming_language_version'
		]);

		$deployment = $this->get_sdk()->Api("/plugins/{$plugin_id}/tags.json?{$query}");


		if(empty($deployment->tags)){
			return null;
		}

		foreach ($deployment->tags as $release) {
			if (isset($release->release_mode) && $release->release_mode === 'released') {
				set_transient('difs_latest_tag_'.$plugin_id, $release);
				return $release;
			}
		}

		return null;
	}

	private function get_plugin_info($plugin_id, $refresh = false){

		/*
		 * [11-Jul-2023 19:51:26 UTC] stdClass Object
		(
			[parent_plugin_id] =>
			[developer_id] => 1657
			[store_id] => 674
			[install_id] => 1319985
			[slug] => post-to-google-my-business
			[title] => Post to Google My Business
			[environment] => 0
			[icon] => https://s3-us-west-2.amazonaws.com/freemius/plugins/1828/icons/c3ff730fa623106c4412f15cede5c2c2.png
			[default_plan_id] => 2701
			[plans] => 2701,2734,2718,2735
			[features] => 2995,10913,2071,10912,10909,7281,2633,2634,2066,2632,2067,11220,14544
			[money_back_period] => 30
			[refund_policy] => strict
			[annual_renewals_discount] =>
			[renewals_discount_type] => percentage
			[is_released] => 1
			[is_sdk_required] => 1
			[is_pricing_visible] => 1
			[is_wp_org_compliant] => 1
			[is_off] =>
			[is_only_for_new_installs] =>
			[installs_limit] =>
			[installs_count] => 17290
			[active_installs_count] => 5218
			[free_releases_count] => 88
			[premium_releases_count] => 88
			[total_purchases] => 18
			[total_subscriptions] => 683
			[total_renewals] => 2115
			[earnings] => 1234567689
			[commission] => {"1000":0.3,"5000":0.2,"above":0.1}
			[accepted_payments] => 0
			[plan_id] => 0
			[type] => plugin
			[is_static] =>
			[id] => 1828
			[created] => 2018-03-07 19:25:24
			[updated] => 2023-07-11 19:16:34
		)

		 */

		$cached = get_transient('difs_plugin_'.$plugin_id);

		if($cached && !$refresh){
			return $cached;
		}

		$query = build_query([
			'fields' => 'created,installs_count,title'
		]);

		$plugin_data = $this->get_sdk()->Api("/plugins/{$plugin_id}.json?{$query}");

		if(empty($plugin_data->error)){
			set_transient('difs_plugin_'.$plugin_id, $plugin_data);
			return $plugin_data;
		}
		return null;
	}

	public function plugin_info_shortcode($shortcode_attributes) {
		$shortcode_attributes = shortcode_atts([
			'plugin_id' => false,
			'show_installs' => '1',
			'structured_data' => '1',
		], $shortcode_attributes, 'difs_plugin_info');

		if(!$shortcode_attributes['plugin_id']){
			return 'plugin_id attribute is required';
		}

		$plugin_id = (int)$shortcode_attributes['plugin_id'];

		$most_recent_release = $this->get_most_recent_release($plugin_id);
		if(!$most_recent_release){
			return 'Could not find recent release for this plugin ID';
		}

		$updated_timestamp = strtotime($most_recent_release->created); // Convert the date string to a Unix timestamp.
		$formatted_update_date = date_i18n(get_option('date_format'), $updated_timestamp);

		$plugin_info = $this->get_plugin_info($plugin_id);

		if(empty($plugin_info)){
			return 'Could not retrieve plugin info';
		}

		$released_timestamp = strtotime($plugin_info->created);
		$release_date = date_i18n(get_option('date_format'), $released_timestamp);


		ob_start();
		?>

		<div class="difs-container">
			<span class="difs-item difs-version">
				<strong><?php esc_html_e('Version:', 'deployment-info-for-fs'); ?></strong>
				<?php echo $most_recent_release->version; ?>
			</span>
			<span class="difs-divider">|</span>
			<span class="difs-item difs-lastupdate">
				<strong><?php esc_html_e('Last Update:', 'deployment-info-for-fs'); ?></strong>
				<?php echo $formatted_update_date; ?>
			</span>

			<span class="difs-divider">|</span>
			<span class="difs-item difs-released">
					<strong><?php esc_html_e('Released:', 'deployment-info-for-fs'); ?></strong>
					<?php echo $release_date; ?>
			</span>

			<span class="difs-divider">|</span>
			<span class="difs-item difs-wordpress">
				<strong><?php esc_html_e('WordPress:', 'deployment-info-for-fs'); ?></strong>
				<?php echo $most_recent_release->tested_up_to_version; ?>
			</span>
			<?php if($shortcode_attributes['show_installs']): ?>
				<span class="difs-divider">|</span>
				<span class="difs-item difs-installs">
					<strong><?php esc_html_e('Installs:', 'deployment-info-for-fs'); ?></strong>
					<?php printf("%d+ sites", floor($plugin_info->installs_count / 100) * 100); ?>
				</span>
			<?php endif; ?>
		</div>

		<?php

		$output = ob_get_clean();

		if($shortcode_attributes['structured_data']){
			// Create the structured data.
			$structured_data = [
				"@context" => "https://schema.org",
				"@type" => "SoftwareApplication",
				"name" => $plugin_info->title,
				"softwareVersion" => $most_recent_release->version,
				"applicationCategory" => "BrowserApplication",
				"operatingSystem" => "WordPress",
				"datePublished" => $plugin_info->created,
				"dateModified" => $most_recent_release->created,
				"softwareRequirements" => "Requires WordPress " . $most_recent_release->requires_platform_version . " and PHP " . $most_recent_release->requires_programming_language_version
			];

			// Convert the structured data to JSON-LD.
			$structured_data_json = json_encode($structured_data);

			// Append the structured data to the HTML output.
			$output .= "<script type='application/ld+json'>{$structured_data_json}</script>";
		}

		return $output;
	}

}

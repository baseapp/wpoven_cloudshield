<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.wpoven.com
 * @since      1.0.0
 *
 * @package    Wpoven_Cloudshield
 * @subpackage Wpoven_Cloudshield/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wpoven_Cloudshield
 * @subpackage Wpoven_Cloudshield/admin
 * @author     WPOven <contact@wpoven.com>
 */
class Wpoven_Cloudshield_Admin
{

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
	private $_wpoven_cloudshield;
	private $config   = false;
	private $objects  = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		if (!class_exists('ReduxFramework') && file_exists(require_once plugin_dir_path(dirname(__FILE__)) . 'includes/libraries/redux-framework/redux-core/framework.php')) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'includes/libraries/redux-framework/redux-core/framework.php';
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

		if (!class_exists('WPOven_CloudShield_Logs_List_Table') or !class_exists('WPOven_CloudShield_IP_Block_List_Table')) {
			include_once WPOVEN_CLOUDSHIELD_PATH . 'includes/class-wpoven-cloudshield-logs-list-table.php';
		}

		if (!class_exists('WPOven_CloudShield_IP_Block_List_Table')) {
			include_once WPOVEN_CLOUDSHIELD_PATH . 'includes/class-wpoven-cloudshield-ip-block-list-table.php';
		}


		if (!$this->init_config()) {
			$this->config = $this->get_default_config();
			$this->update_config();
		}

		$this->include_classes();

		$this->action();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpoven_Cloudshield_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpoven_Cloudshield_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wpoven-cloudshield-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpoven_Cloudshield_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpoven_Cloudshield_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wpoven-cloudshield-admin.js', array('jquery'), $this->version, false);
	}

	function action()
	{
		add_action('init', array($this, 'wpoven_store_cloudshield_data_into_json_file'));

		add_action('wp_login_failed', array($this, 'set_401_status_on_failed_login'));
		add_action('wp_login_failed', array($this, 'cloudshield_track_failed_logins'));
		add_action('init', array($this, 'cloudshield_delete_block_ip_after_30_minutes'));

		add_filter('cron_schedules', array($this, 'cloudshield_add_cron_interval'));
		add_action('init', array($this, 'cloudshield_schedule_cron_job'));
		add_action('cloudshield_cron_hook', array($this, 'cloudshield_cron_exec'));

		add_action('init', array($this, 'update_current_date'));
		add_action('admin_footer', array($this, 'add_ajax_nonce_to_admin_footer'));
		add_action('wp_footer', array($this, 'add_ajax_nonce_to_admin_footer'));
		register_shutdown_function(array($this, 'log_cloudshild_performance_data'));

		add_action('wp_ajax_cloudshield_purge_all_logs', array($this, 'cloudshield_purge_all_logs'));
		add_action('wp_ajax_nopriv_cloudshield_purge_all_logs',  array($this, 'cloudshield_purge_all_logs'));


		add_action('wp_ajax_cloudshield_create_waf_custom_rule', array($this, 'cloudshield_create_waf_custom_rule'));
		add_action('wp_ajax_nopriv_cloudshield_create_waf_custom_rule',  array($this, 'cloudshield_create_waf_custom_rule'));

		add_action('wp_ajax_cloudshield_reset_all_settings_and_waf_rules', array($this, 'cloudshield_reset_all_settings_and_waf_rules'));
		add_action('wp_ajax_nopriv_cloudshield_reset_all_settings_and_waf_rules',  array($this, 'cloudshield_reset_all_settings_and_waf_rules'));

		add_action('wp_ajax_cloudshield_update_waf_rules', array($this, 'cloudshield_update_waf_rules'));
		add_action('wp_ajax_nopriv_cloudshield_update_waf_rules',  array($this, 'cloudshield_update_waf_rules'));

		add_action('wp_ajax_cloudshield_disable_waf_rules', array($this, 'cloudshield_disable_waf_rules'));
		add_action('wp_ajax_nopriv_cloudshield_disable_waf_rules',  array($this, 'cloudshield_disable_waf_rules'));

		add_action('wp_ajax_cloudshield_enable_php_waf_rules', array($this, 'cloudshield_enable_php_waf_rules'));
		add_action('wp_ajax_nopriv_cloudshield_enable_php_waf_rules',  array($this, 'cloudshield_enable_php_waf_rules'));

		add_action('wp_ajax_cloudshield_disable_php_waf_rules', array($this, 'cloudshield_disable_php_waf_rules'));
		add_action('wp_ajax_nopriv_cloudshield_disable_php_waf_rules',  array($this, 'cloudshield_disable_php_waf_rules'));

		add_action('admin_enqueue_scripts', array($this, 'custom_wp_list_table_styles'));
	}

	function wpoven_store_cloudshield_data_into_json_file()
	{
		global $wpdb;

		$file_path = plugin_dir_path(__DIR__) . 'includes/cloudshield-ip-security/wpoven-cloudshield.json';

		// Retrieve wpoven-cloudshield option
		$cloudshield_option = get_option('wpoven-cloudshield', []);

		// Fetch table data
		$blocked_ips = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}cloudshield_blocked_ip_logs", ARRAY_A);

		// Prepare data array
		$data = [
			'wpoven-cloudshield' => $cloudshield_option,
			'blocked_ip_logs' => $blocked_ips,
		];

		// Convert to JSON
		$json_data = json_encode($data, JSON_PRETTY_PRINT);

		// Write to file
		file_put_contents($file_path, $json_data);
	}

	function cloudshield_enable_php_waf_rules()
	{
		global $wp_filesystem;

		if (empty($wp_filesystem)) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$options = get_option('wpoven-cloudshield');
		$method = isset($options['cloudshield-waf-method']) ? $options['cloudshield-waf-method'] : 2;
		if ($method == 0) {
			if (!defined('MY_PLUGIN_PREPEND_FILE')) {
				define('MY_PLUGIN_PREPEND_FILE', plugin_dir_path(__DIR__) . 'includes/cloudshield-ip-security');

				// .user.ini path
				$user_ini_path = ABSPATH . '.user.ini';
				// Update .user.ini
				$user_ini_content = "auto_prepend_file = " . MY_PLUGIN_PREPEND_FILE . "/prepend.class.php\n"; // Adjust if the file name is different
				if (file_put_contents($user_ini_path, $user_ini_content) === false) {
					error_log("Error: Failed to write to .user.ini at: " . $user_ini_path);
					return;
				}
				// Check if the file exists before changing permissions
				if ($wp_filesystem->exists($user_ini_path)) {
					$wp_filesystem->chmod($user_ini_path, 0644);
				}

				$php_waf_enabled = update_option('cloudshield-php-waf-enabled', 1);
				$return_array['status'] = 'ok';
			}
		} else {
			$return_array['status'] = 'error';
		}
		die(wp_json_encode($return_array));
	}

	function cloudshield_disable_php_waf_rules()
	{
		$user_ini_path = ABSPATH . '.user.ini';
		if (file_exists($user_ini_path)) {
			// Attempt to delete the .user.ini file
			if (!wp_delete_file($user_ini_path)) {
				error_log("Error: Failed to delete .user.ini at: " . $user_ini_path);
				return;
			}
		}

		$php_waf_disabled = update_option('cloudshield-php-waf-enabled', 0);

		$return_array = array();
		if ($php_waf_disabled) {
			$return_array['status'] = 'ok';
		} else {
			$return_array['status'] = 'error';
			$return_array['message'] = 'Failed to update the option.';
		}

		// Return the response as JSON
		die(wp_json_encode($return_array));
	}

	function include_classes()
	{

		if (count($this->objects) > 0)
			return;

		$this->objects = array();

		require_once CLOUDSHIELD_PLUGIN_PATH . 'classes/cloudflare.class.php';

		$this->objects = apply_filters('cloudshield_include_libs_early', $this->objects);

		$this->objects['cloudflare'] = new CloudShield_Cloudflare(
			$this->get_single_config('cloudshield-cf-auth-mode'),
			$this->get_cloudflare_api_key(),
			$this->get_cloudflare_api_email(),
			$this->get_cloudflare_api_token(),
			$this->get_cloudflare_api_zone_id(),
			$this
		);
		$this->objects = apply_filters('cloudshield_include_libs_lately', $this->objects);
	}

	/**
	 * Adds an AJAX nonce to the admin footer for security in AJAX requests.
	 */
	function add_ajax_nonce_to_admin_footer()
	{
?>
		<script type="text/javascript">
			var ajax_nonce = '<?php echo esc_html(wp_create_nonce('wpoven_ajax_nonce')); ?>';
			var ajax_url = '<?php echo esc_html(admin_url('admin-ajax.php')); ?>';
			document.write('<div id="wpoven-ajax-nonce" style="display:none;">' + ajax_nonce + '</div>');
			document.write('<div id="wpoven-ajax-url" style="display:none;">' + ajax_url + '</div>');
		</script>
<?php
	}

	function set_401_status_on_failed_login($username)
	{
		status_header(401);
	}

	function cloudshield_track_failed_logins($username)
	{
		global $wpdb;
		$ip = $_SERVER['REMOTE_ADDR'];
		$cloudshield_blocked_ip_logs = $wpdb->prefix . 'cloudshield_blocked_ip_logs';

		// Fetch the existing record for the IP
		$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $cloudshield_blocked_ip_logs WHERE ip_address = %s", $ip));

		// Get Cloudflare settings
		$data = get_option('cloudshield-general-details');
		$custom_ruleset_id = $data['custom_ruleset_id'] ?? '';
		$cloudshield_cf_zoneid = $data['cloudshield-cf-zoneid'] ?? '';
		$is_enable = $data['cloudshield-cf-waf-enabled'] ?? 0;
		$options = get_option('wpoven-cloudshield');
		$login_block_request_rate = isset($options['cloudshield-login-block-request-rate']) ? $options['cloudshield-login-block-request-rate'] : 5; // Set to 5 failed attempts

		if ($row) {

			// If already blocked, check if the block should expire (after 30 minutes)
			if ($row->ip_status === 'blocked') {
				$thirty_minutes_ago = time() - (30 * 60); // Calculate timestamp for 30 minutes ago
				if (strtotime($row->timestamp) <= $thirty_minutes_ago) {
					$wpdb->delete($cloudshield_blocked_ip_logs, ['ip_address' => $row->ip_address]);
				}

				// Get updated blocked IPs after deletion
				$updated_blocked_ips = $wpdb->get_col("SELECT ip_address FROM $cloudshield_blocked_ip_logs WHERE ip_status = 'blocked'");

				if ($is_enable && $custom_ruleset_id && $cloudshield_cf_zoneid) {
					if (!empty($updated_blocked_ips)) {
						// Update Cloudflare rule with new blocked IP list
						$this->objects['cloudflare']->update_wrong_login_custom_rule($updated_blocked_ips, $options, $cloudshield_cf_zoneid, $custom_ruleset_id);
					}
				}

				return;
			}

			// Increase failed attempts
			$attempts = $row->failed_attempts + 1;

			if ($attempts >= $login_block_request_rate) {
				// Block the IP
				$wpdb->update($cloudshield_blocked_ip_logs, [
					'ip_status' => 'blocked',
					'blocked_at' => current_time('mysql')
				], ['ip_address' => $ip]);

				// Get updated blocked IPs after adding new block
				$updated_blocked_ips = $wpdb->get_col("SELECT ip_address FROM $cloudshield_blocked_ip_logs WHERE ip_status = 'blocked'");

				// Call Cloudflare API if enabled
				if ($is_enable && $custom_ruleset_id && $cloudshield_cf_zoneid) {
					if (!empty($updated_blocked_ips)) {
						$this->objects['cloudflare']->create_wrong_login_custom_rule($updated_blocked_ips, $options, $cloudshield_cf_zoneid, $custom_ruleset_id);
					}
				}
			} else {
				// Update failed attempt count
				$wpdb->update($cloudshield_blocked_ip_logs, ['failed_attempts' => $attempts], ['ip_address' => $ip]);
			}
		} else {
			if ($is_enable && $custom_ruleset_id && $cloudshield_cf_zoneid) {
				$this->objects['cloudflare']->delete_wrong_login_custom_rule($cloudshield_cf_zoneid, $custom_ruleset_id);
			}
			// Insert new record
			$wpdb->insert($cloudshield_blocked_ip_logs, [
				'ip_address' => $ip,
				'status_code' => '401',
				'failed_attempts' => 1,
				'ip_status' => 'active', // Default status
				'timestamp' => current_time('mysql')
			]);
		}
	}

	function cloudshield_delete_block_ip_after_30_minutes()
	{
		global $wpdb;
		$ip = $_SERVER['REMOTE_ADDR'];
		$cloudshield_blocked_ip_logs = $wpdb->prefix . 'cloudshield_blocked_ip_logs';

		// Fetch the existing record for the IP
		$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $cloudshield_blocked_ip_logs WHERE ip_address = %s", $ip));

		// Get Cloudflare settings
		$data = get_option('cloudshield-general-details');
		$custom_ruleset_id = $data['custom_ruleset_id'] ?? '';
		$cloudshield_cf_zoneid = $data['cloudshield-cf-zoneid'] ?? '';
		$is_enable = $data['cloudshield-cf-waf-enabled'] ?? 0;
		$options = get_option('wpoven-cloudshield');
		$login_block_request_rate = isset($options['cloudshield-login-block-request-rate']) ? $options['cloudshield-login-block-request-rate'] : 5; // Set to 5 failed attempts

		if ($row) {

			// If already blocked, check if the block should expire (after 30 minutes)
			if ($row->ip_status === 'blocked') {
				$thirty_minutes_ago = time() - (30 * 60); // Calculate timestamp for 30 minutes ago
				if (strtotime($row->timestamp) <= $thirty_minutes_ago) {
					$wpdb->delete($cloudshield_blocked_ip_logs, ['ip_address' => $row->ip_address]);
				}

				// Get updated blocked IPs after deletion
				$updated_blocked_ips = $wpdb->get_col("SELECT ip_address FROM $cloudshield_blocked_ip_logs WHERE ip_status = 'blocked'");

				if ($is_enable && $custom_ruleset_id && $cloudshield_cf_zoneid) {
					if (!empty($updated_blocked_ips)) {
						// Update Cloudflare rule with new blocked IP list
						$this->objects['cloudflare']->update_wrong_login_custom_rule($updated_blocked_ips, $options, $cloudshield_cf_zoneid, $custom_ruleset_id);
					}
				}

				return;
			} else {
				$thirty_minutes_ago = time() - (30 * 60); // Calculate timestamp for 30 minutes ago
				if (strtotime($row->timestamp) <= $thirty_minutes_ago) {
					$wpdb->delete($cloudshield_blocked_ip_logs, ['ip_address' => $row->ip_address]);
				}

				return;
			}
		}
	}

	function custom_wp_list_table_styles()
	{
		echo '<style>
			.wp-list-table .column-url {
				width: 900px; 
			}
			.wp-list-table .column-status {
				width: 200px;
			}
			
			@media screen and (max-width: 768px) {

			.wp-list-table .column-url {
				width: 230px; 
			}

			.wp-list-table .column-status, 
			.wp-list-table .column-ip_address,
			.wp-list-table .column-timestamp{
                display: none;
            }
			
			.wp-list-table .is-expanded .column-status, 
			.wp-list-table .is-expanded .column-ip_address,
			.wp-list-table .is-expanded .column-timestamp{
                 display: table-cell;
            }
		</style>';
	}

	function cloudshield_add_cron_interval($schedules)
	{
		$schedules['everyMinute'] = array(
			'interval' => 60,
			'display'  => esc_html__('Every 60 Seconds'),
		);
		return $schedules;
	}

	function cloudshield_schedule_cron_job()
	{
		if (!wp_next_scheduled('cloudshield_cron_hook')) {
			wp_schedule_event(time(), 'everyMinute', 'cloudshield_cron_hook');
		}
	}

	/**
	 * Run cron function every 60 seconds.
	 */
	function cloudshield_cron_exec()
	{
		global $wpdb;
		$options = get_option('wpoven-cloudshield');
		$request_rate = isset($options['cloudshield-request-rate']) ? $options['cloudshield-request-rate'] : 5;

		// Define table names
		$cloudshield_logs = $wpdb->prefix . 'cloudshield_logs';
		$blocked_ips_table = $wpdb->prefix . 'cloudshield_blocked_ip_logs';

		// Get the current timestamp and one minute earlier
		$current_time = current_time('mysql');
		$one_minute_ago = gmdate('Y-m-d H:i:s', strtotime('-1 minute', strtotime($current_time)));

		//Cleanup old data from the blocked IPs table
		$thirty_minutes_ago = gmdate('Y-m-d H:i:s', strtotime('-30 minutes', strtotime($current_time)));
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $blocked_ips_table WHERE blocked_at < %s",
				$thirty_minutes_ago
			)
		);

		$this->wpoven_store_cloudshield_data_into_json_file();

		$results_404 = array();

		// Query to find IPs with 10 or more 404 hits in the last minute
		$results_404 = $wpdb->get_results(
			$wpdb->prepare(
				"
            SELECT ip_address, COUNT(*) as count, status_code
            FROM $cloudshield_logs
            WHERE timestamp BETWEEN %s AND %s
              AND status_code = 404
            GROUP BY ip_address
            HAVING count >= %d
            ",
				$one_minute_ago,
				$current_time,
				$request_rate
			)
		);

		// Insert blocked IPs into the blocked IP logs table
		if (!empty($results_404)) {
			foreach ($results_404 as $row) {
				$ip_address = $row->ip_address;
				$status_code = $row->status_code;

				// Check if the IP is already blocked
				$exists = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM $blocked_ips_table WHERE ip_address = %s",
						$ip_address
					)

				);

				if (!$exists) {
					$wpdb->insert(
						$blocked_ips_table,
						[
							'ip_address' => $ip_address,
							'blocked_at' => $current_time,
							'status_code' => $status_code,
							'ip_status' => 'blocked', // Default status
							'failed_attempts' => 0,
							'timestamp' => $current_time,
						],
						['%s', '%s', '%s', '%s', '%d', '%s']
					);
				} else {
					$wpdb->update(
						$blocked_ips_table,
						[
							'blocked_at' => $current_time,
							'status_code' => $status_code,
							'ip_status' => 'blocked', // Default status
							'failed_attempts' => 0,
							'timestamp' => $current_time,
						],
						['ip_address' => $ip_address,]
					);
				}
			}
		}

		$results = $wpdb->get_col("SELECT ip_address FROM $blocked_ips_table");
		$data = get_option('cloudshield-general-details');
		$custom_ruleset_id = $data['custom_ruleset_id'] ?? '';
		$cloudshield_cf_zoneid = $data['cloudshield-cf-zoneid'] ?? '';
		$is_enable = $data['cloudshield-cf-waf-enabled'] ?? 0;

		if ($custom_ruleset_id && $cloudshield_cf_zoneid) {
			if ($is_enable) {
				if (!empty($results)) {
					$this->objects['cloudflare']->create_dynamic_waf_custom_rule($results, $options, $cloudshield_cf_zoneid, $custom_ruleset_id);
				} else {
					$this->objects['cloudflare']->delete_dynamic_waf_custom_rule($cloudshield_cf_zoneid, $custom_ruleset_id);
				}
			}
		}
	}

	/**
	 * Purge all logs from table.
	 */
	function cloudshield_purge_all_logs()
	{
		//check_ajax_referer('ajax_nonce', 'nonce');

		global $wpdb;
		$return_array = array();

		$table_name = $wpdb->prefix . 'cloudshield_logs'; // Use the correct table name with the WordPress prefix
		$deleted_rows = $wpdb->query("DELETE FROM {$table_name}");

		if ($deleted_rows !== false) {
			// If the deletion was successful
			$return_array['status'] = 'ok';
			$return_array['success_msg'] = __('All logs have been deleted successfully.', 'WPOven CloudShield');
		} else {
			// If there was an error during deletion
			$return_array['status'] = 'error';
			$return_array['error_msg'] = __('Failed to delete logs.', 'WPOven CloudShield');
		}

		die(wp_json_encode($return_array));
	}

	/**
	 * Create all cloudflare WAF custom rules.
	 */
	function cloudshield_create_waf_custom_rule()
	{
		$options = get_option('cloudshield-general-details');
		$custom_ruleset_id = $options['custom_ruleset_id'] ?? '';
		$cloudshield_cf_zoneid = $options['cloudshield-cf-zoneid'] ?? '';

		if ($custom_ruleset_id && $cloudshield_cf_zoneid) {
			$this->objects['cloudflare']->create_waf_custom_rule($options, $cloudshield_cf_zoneid, $custom_ruleset_id);
		}
	}

	/**
	 * Update all cloudflare WAF custom rules.
	 */
	function cloudshield_update_waf_rules()
	{
		$options = get_option('cloudshield-general-details');
		$waf_rules = get_option('cloudshield_waf_rules');
		$custom_ruleset_id = $options['custom_ruleset_id'] ?? '';
		$cloudshield_cf_zoneid = $options['cloudshield-cf-zoneid'] ?? '';
		if ($custom_ruleset_id && $cloudshield_cf_zoneid) {
			$this->objects['cloudflare']->delete_waf_custom_rule($cloudshield_cf_zoneid, $custom_ruleset_id, $waf_rules);
		}
	}

	/**
	 * Delete all cloudflare WAF custom rules.
	 */
	function cloudshield_disable_waf_rules()
	{
		$options = get_option('cloudshield-general-details');
		$custom_ruleset_id = $options['custom_ruleset_id'] ?? '';
		$cloudshield_cf_zoneid = $options['cloudshield-cf-zoneid'] ?? '';
		if ($custom_ruleset_id && $cloudshield_cf_zoneid) {
			$this->objects['cloudflare']->delete_waf_custom_rule($cloudshield_cf_zoneid, $custom_ruleset_id);
		}
	}

	/**
	 * Reset all WAF settings and delete all custom WAF rules.
	 */
	function cloudshield_reset_all_settings_and_waf_rules()
	{
		$options = get_option('cloudshield-general-details');
		//$waf_rules = get_option('cloudshield_waf_rules');
		$custom_ruleset_id = $options['custom_ruleset_id'] ?? '';
		$cloudshield_cf_zoneid = $options['cloudshield-cf-zoneid'] ?? '';

		delete_option('cloudshield-general-details');
		//delete_option('cloudshield_waf_rules');
		delete_option('wpoven-cloudshield');
		if ($custom_ruleset_id && $cloudshield_cf_zoneid) {
			$this->objects['cloudflare']->delete_waf_custom_rule($cloudshield_cf_zoneid, $custom_ruleset_id);
		}

		$this->cloudshield_disable_php_waf_rules();
	}

	function update_current_date()
	{
		// Get the current date in 'Y-m-d' format
		$current_date = gmdate('Y-m-d');
		$stored_date = get_option('cloudshield_log_current_date');

		// Check if the stored date exists and if it doesn't match the current date
		if ($stored_date !== $current_date) {
			update_option('cloudshield_log_current_date', $current_date);
		}
	}

	/**
	 * Create the database table for storing cloudshield logs.
	 */
	public function create_database_table()
	{
		global $wpdb;
		$cloudshield_logs = $wpdb->prefix . 'cloudshield_logs';
		$cloudshield_blocked_ip_logs = $wpdb->prefix . 'cloudshield_blocked_ip_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$cloudshield_logs_table = "CREATE TABLE $cloudshield_logs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            url TEXT NOT NULL,
			status_code TEXT NOT NULL,
            ip_address VARCHAR(255) DEFAULT NULL,
			state varchar(255) DEFAULT NULL,
            timestamp datetime DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";


		$cloudshield_blocked_ip_logs_table = "CREATE TABLE $cloudshield_blocked_ip_logs (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			ip_address VARCHAR(255) DEFAULT NULL,
			status_code TEXT NULL,
			ip_status TEXT NULL,
			failed_attempts INT(11) DEFAULT 0,
			blocked_at datetime DEFAULT NULL,
			timestamp datetime DEFAULT NULL,
			PRIMARY KEY (id)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($cloudshield_logs_table);
		dbDelta($cloudshield_blocked_ip_logs_table);
	}

	/**
	 * Log the performance data (URL, execution time, post type, IP address).
	 */
	public function log_cloudshild_performance_data()
	{
		global $wpdb;
		$options = get_option(WPOVEN_CLOUDSHIELD_SLUG);
		$enable_admin_page_logging = isset($options['enable-log']) ? $options['enable-log'] : false;
		$cloudshield_log_retention = isset($options['cloudshield-log-retention']) ? $options['cloudshield-log-retention'] : false;
		$table_name = $wpdb->prefix . 'cloudshield_logs';
		if ($enable_admin_page_logging) {
			if ($cloudshield_log_retention) {
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM $table_name WHERE timestamp < %s",
						gmdate('Y-m-d H:i:s', strtotime('-' . $cloudshield_log_retention . ' days'))
					)
				);
			}

			$filter_request = isset($options['filter-request']) ? $options['filter-request'] : null;

			//Check if the current request is for admin-ajax.php
			if (!empty($filter_request)) {
				$array = preg_split('/[\n,]+/', $filter_request);
				$array = array_map('trim', $array);
				$array = array_filter($array);

				foreach ($array as $item) {

					if (isset($_SERVER['REQUEST_URI']) && strpos(wp_unslash($_SERVER['REQUEST_URI']), $item)) {
						return;
					}
				}
			}


			// Get the current URL
			$url = esc_url($_SERVER['REQUEST_URI']);

			// Get the user's IP address
			$ip_address = !empty($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : (!empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : $_SERVER['REMOTE_ADDR']));

			// Prepare log data
			$data = array(
				'url'               => home_url() . $url,
				'status_code'       => http_response_code(),
				'ip_address'        => $ip_address,
				'state'             => 'active',
				'timestamp'         => current_time('mysql'),
			);

			// Store the log in the database
			$this->store_data_in_database($data);
		}
	}

	/**
	 * Store the log data in the database.
	 */
	private function store_data_in_database($data)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'cloudshield_logs';

		// Insert log into database
		$wpdb->insert(
			$table_name,
			array(
				'url'               => $data['url'],
				'status_code'       => $data['status_code'],
				'ip_address'        => $data['ip_address'],
				'state'             => $data['state'],
				'timestamp'         => $data['timestamp'],
			),
			array(
				'%s', // URL
				'%s', // status_code
				'%s', // IP address
				'%s', // IP state
				'%s', // Timestamp
			)
		);
	}

	/**
	 * Delete all logs from the table wp_cloudshield_logs.
	 */
	function wpoven_purge_all_logs()
	{
		//check_ajax_referer('ajax_nonce', 'nonce');

		global $wpdb;
		$return_array = array();

		$table_name = $wpdb->prefix . 'cloudshield_logs'; // Use the correct table name with the WordPress prefix
		$deleted_rows = $wpdb->query("DELETE FROM {$table_name}");

		if ($deleted_rows !== false) {
			// If the deletion was successful
			$return_array['status'] = 'ok';
			$return_array['success_msg'] = __('All logs have been deleted successfully.', 'WPOven CloudShield');
		} else {
			// If there was an error during deletion
			$return_array['status'] = 'error';
			$return_array['error_msg'] = __('Failed to delete logs.', 'WPOven CloudShield');
		}

		die(wp_json_encode($return_array));
	}

	function get_default_config()
	{
		// Cloudflare config
		$config = array();

		$config['cloudshield-cf-zoneid']                  = '';
		$config['cloudshield-cf-zoneid-list']             = array();
		$config['cloudshield-cf-email']                   = '';
		$config['cloudshield-cf-apitoken']                = '';
		$config['cloudshield-cf-apikey']                  = '';
		//$config['cloudshield-cf-token']                   = '';
		//$config['cloudshield-cf-apitoken-domain']         = '';
		$config['cloudshield-cf-auth-mode']               = CLOUDSHIELD_AUTH_MODE_API_KEY;
		$config['cloudshield-cf-waf-enabled']             = 0;
		$config['keep-settings-on-deactivation']          = 1;
		$config['cloudshield-cf-enable-captcha']          = false;
		$config['cloudshield-cf-block-xmlrpc']            = false;
		$config['cloudshield-cf-wrong-login']             = false;
		$config['cloudshield-login-block-request-rate']   = 3;
		$config['cloudshield-cf-country-block']            = false;
		$config['cloudshield-country-list']               = array();
		$config['cloudshield-cf-request-rate']            = false;
		$config['cloudshield-request-rate']               = 30;
		$config['cloudshield-cf-ip-block']                = false;
		$config['cloudshield-ip-list']                    = array();
		$config['cloudshield-cf-block-non-seo']           = false;
		$config['cloudshield-cf-block-ai-crawlers']       = false;
		$config['cloudshield-cf-404-protection']          = false;
		$config['custom_ruleset_id']                      = '';
		$config['custom_ratelimit_id']                    = '';


		return $config;
	}


	function get_single_config($name, $default = false)
	{
		if (isset($this->config)) {
			if (!is_array($this->config) || !isset($this->config[$name]))
				return $default;

			if (is_array($this->config[$name]))
				return $this->config[$name];

			return trim($this->config[$name]);
		}
	}


	function set_single_config($name, $value)
	{

		if (!is_array($this->config))
			$this->config = array();

		if (is_array($value))
			$this->config[trim($name)] = $value;
		else
			$this->config[trim($name)] = trim($value);
	}


	function update_config()
	{
		update_option('cloudshield-general-details', $this->config);
	}


	function init_config()
	{

		$this->config = get_option('cloudshield-general-details');

		if (!$this->config)
			return false;

		// If the option exists, return true
		return true;
	}


	function set_config($config)
	{
		$this->config = $config;
	}


	function get_config()
	{
		return $this->config;
	}

	function get_objects()
	{
		return $this->objects;
	}

	/**
	 * Get cloudflate zone id of domain.
	 */
	function get_cloudflare_api_zone_id()
	{

		if (defined('CLOUDSHIELD_CF_API_ZONE_ID'))
			return CLOUDSHIELD_CF_API_ZONE_ID;

		return $this->get_single_config('cloudshield-cf-zoneid', '');
	}

	/**
	 * Get cloudflate custom ruleset id for create custom rules.
	 */
	function get_cloudflare_custom_ruleset_id()
	{

		if (defined('CLOUDSHIELD_CF_CUSTOM_RULESET_ID'))
			return CLOUDSHIELD_CF_CUSTOM_RULESET_ID;

		return $this->get_single_config('custom_ruleset_id', '');
	}

	/**
	 * Get cloudflate API api key.
	 */
	function get_cloudflare_api_key()
	{

		if (defined('CLOUDSHIELD_CF_API_KEY'))
			return CLOUDSHIELD_CF_API_KEY;

		return $this->get_single_config('cloudshield-cf-apikey', '');
	}

	/**
	 * Get cloudflate API email.
	 */
	function get_cloudflare_api_email()
	{

		if (defined('CLOUDSHIELD_CF_API_EMAIL'))
			return CLOUDSHIELD_CF_API_EMAIL;

		return $this->get_single_config('cloudshield-cf-email', '');
	}

	/**
	 * Get cloudflate API token.
	 */
	function get_cloudflare_api_token()
	{

		if (defined('CLOUDSHIELD_CF_API_TOKEN'))
			return CLOUDSHIELD_CF_API_TOKEN;

		return $this->get_single_config('cloudshield-cf-apitoken', '');
	}


	/**
	 * CloudShield WAF controller for create, update, delete custom rules & reset all settings.
	 */

	function cloudshield_waf_controller()
	{
		$options = get_option('wpoven-cloudshield');
		$waf_method = isset($options['cloudshield-waf-method']) ? $options['cloudshield-waf-method'] : 0;
		$php_waf_enabled = get_option('cloudshield-php-waf-enabled');
		$php_waf_enabled = isset($php_waf_enabled) ? $php_waf_enabled : 0;

		$waf_contoller = array(
			'id'   => 'waf-controller',
			'type' => 'info',
		);

		$purge_all_logs_button = ['id' => 'cloudshield_submit_purge_all_logs', 'value' => 'reset', 'text' => 'Purge All Logs'];
		$reset_all_button = ['id' => 'cloudshield_submit_reset_all', 'value' => 'reset', 'text' => 'RESET ALL'];

		if ($waf_method != 0) {
			if (!$this->objects['cloudflare']->is_waf_enabled()) {
				$waf_contoller['desc'] = $this->cloudshield_generate_waf_buttons(
					'Enable WAF Security',
					'Now you can set up and activate <strong>Cloudflare WAF Rules</strong> to enhance this website security.',
					[
						['id' => 'cloudshield_submit_enable_waf_settings', 'value' => 'enable', 'text' => 'ENABLE WAF'],
						$purge_all_logs_button,
					]
				);
			} else {
				$waf_contoller['desc'] = $this->cloudshield_generate_waf_buttons(
					'WAF Actions Controller',
					'',
					[
						['id' => 'cloudshield_submit_disable_waf_settings', 'value' => 'disable', 'text' => 'Disable WAF Rules'],
						['id' => 'cloudshield_submit_update_waf_settings', 'value' => 'reset', 'text' => 'Update WAF Rules'],
						$reset_all_button,
						$purge_all_logs_button,
					]
				);
			}
		} else {
			if ($php_waf_enabled) {
				$waf_contoller['desc'] = $this->cloudshield_generate_waf_buttons(
					'WAF Actions Controller',
					'',
					[
						['id' => 'cloudshield_submit_disable_php_waf_settings', 'value' => 'disable', 'text' => 'Disable WAF Rules'],
						$reset_all_button,
						$purge_all_logs_button,
					]
				);
			} else {
				$waf_contoller['desc'] = $this->cloudshield_generate_waf_buttons(
					'Enable WAF Security',
					'Now you can activate PHP WAF rules to enhance this website security.',
					[
						['id' => 'cloudshield_submit_enable_php_waf_rules', 'value' => 'enable', 'text' => 'ENABLE WAF'],
						$purge_all_logs_button,
					]
				);
			}
		}

		return $waf_contoller;
	}

	private function cloudshield_generate_waf_buttons($title, $description, $buttons)
	{
		$html = '<div style="text-align: center;">';
		$html .= '<h2 style="text-align: center;">' . esc_html($title) . '</h2>';
		if (!empty($description)) {
			$html .= '<p style="text-align: center;">' . $description . '</p>';
		}
		$html .= '<br>';

		foreach ($buttons as $button) {
			$html .= sprintf(
				'<button type="submit" class="waf-controller button-primary %s" style="width: %spx; margin-right: 10px; text-align:center;" id="%s" value="%s">%s</button>',
				esc_attr($button['id'] === 'cloudshield_submit_enable_waf_settings' ? 'cloudshield_hide' : ''),
				esc_attr(strlen($button['text']) * 10), // Dynamic width based on text length
				esc_attr($button['id']),
				esc_attr($button['value']),
				esc_html($button['text'])
			);
		}

		$html .= '</div><br><br>';
		return $html;
	}


	/**
	 * WPOven CloudShield general setting for cloudflare.
	 */
	function cloudshield_general_settings()
	{
		$error_msg      = '';
		$domain_found   = false;
		$domain_zone_id = '';
		$message = '';
		if ((isset($_POST['cloudshield-cf-email']) && isset($_POST['cloudshield-cf-apikey'])) or (isset($_POST['cloudshield-cf-email']) && isset($_POST['cloudshield-cf-apitoken']))) {
			$this->set_single_config('cloudshield-cf-auth-mode', (int) $_POST['cloudshield-cf-auth-mode-select']);
			$this->set_single_config('cloudshield-cf-email', sanitize_email($_POST['cloudshield-cf-email']));
			$this->set_single_config('cloudshield-cf-apikey', sanitize_text_field($_POST['cloudshield-cf-apikey']));
			$this->set_single_config('cloudshield-cf-apitoken', sanitize_text_field($_POST['cloudshield-cf-apitoken']));

			// Force refresh on Cloudflare api class
			$this->objects['cloudflare']->set_auth_mode((int) $_POST['cloudshield-cf-auth-mode-select'] ?? 0);
			$this->objects['cloudflare']->set_api_key(sanitize_text_field($_POST['cloudshield-cf-apikey']));
			$this->objects['cloudflare']->set_api_email(sanitize_text_field($_POST['cloudshield-cf-email']));
			$this->objects['cloudflare']->set_api_token(sanitize_text_field($_POST['cloudshield-cf-apitoken']));

			$this->update_config();
			if (isset($_POST['cloudshield-cf-zoneid-select'])) {
				$this->set_single_config('cloudshield-cf-zoneid', trim(sanitize_text_field($_POST['cloudshield-cf-zoneid-select'])));
			}

			if (isset($_POST['cloudshield-cf-enable-captcha'])) {
				$this->set_single_config('cloudshield-cf-enable-captcha',  (int) $_POST['cloudshield-cf-enable-captcha']);
			}

			if (isset($_POST['cloudshield-cf-block-xmlrpc'])) {
				$this->set_single_config('cloudshield-cf-block-xmlrpc',  (int) $_POST['cloudshield-cf-block-xmlrpc']);
			}

			if (isset($_POST['cloudshield-cf-wrong-login'])) {
				$this->set_single_config('cloudshield-cf-wrong-login',  (int) $_POST['cloudshield-cf-wrong-login']);
			}

			if (isset($_POST['cloudshield-login-block-request-rate'])) {
				$this->set_single_config('cloudshield-login-block-request-rate', $_POST['cloudshield-login-block-request-rate']);
			}

			if (isset($_POST['cloudshield-cf-country-block'])) {
				$this->set_single_config('cloudshield-cf-country-block',  (int) $_POST['cloudshield-cf-country-block']);
			}

			if (isset($_POST['cloudshield-country-list-select'])) {
				$this->set_single_config('cloudshield-country-list', $_POST['cloudshield-country-list-select']);
			}

			if (isset($_POST['cloudshield-cf-request-rate'])) {
				$this->set_single_config('cloudshield-cf-request-rate',  (int) $_POST['cloudshield-cf-request-rate']);
			}

			if (isset($_POST['cloudshield-request-rate'])) {
				$this->set_single_config('cloudshield-request-rate', $_POST['cloudshield-request-rate']);
			}

			if (isset($_POST['cloudshield-cf-ip-block'])) {
				$this->set_single_config('cloudshield-cf-ip-block',  (int) $_POST['cloudshield-cf-ip-block']);
			}

			if (isset($_POST['cloudshield-ip-list-select'])) {
				$this->set_single_config('cloudshield-ip-list', $_POST['cloudshield-ip-list-select']);
			}

			if (isset($_POST['cloudshield-cf-block-non-seo'])) {
				$this->set_single_config('cloudshield-cf-block-non-seo',  (int) $_POST['cloudshield-cf-block-non-seo']);
			}
			if (isset($_POST['cloudshield-cf-block-ai-crawlers'])) {
				$this->set_single_config('cloudshield-cf-block-ai-crawlers',  (int) $_POST['cloudshield-cf-block-ai-crawlers']);
			}

			if (isset($_POST['cloudshield-cf-404-protection'])) {
				$this->set_single_config('cloudshield-cf-404-protection',  (int) $_POST['cloudshield-cf-404-protection']);
			}

			if (count($this->get_single_config('cloudshield-cf-zoneid-list', array())) == 0 && ($zone_id_list = $this->objects['cloudflare']->get_zone_id_list($error_msg))) {
				$this->set_single_config('cloudshield-cf-zoneid-list', $zone_id_list);
			}

			$custom_ruleset_id = '';
			if ($this->get_single_config('cloudshield-cf-zoneid', '')) {
				$custom_ruleset_id = $this->objects['cloudflare']->get_custom_ruleset_id($this->get_single_config('cloudshield-cf-zoneid', ''));
				$this->set_single_config(
					'custom_ruleset_id',
					$custom_ruleset_id['http_request_firewall_custom'] ?? ''
				);

				$this->set_single_config(
					'custom_ratelimit_id',
					$custom_ruleset_id['http_ratelimit'] ?? ''
				);
			} else {
				$message = '<h2 style="color: red;"><strong>Error : Can not connect to Cloudflare. Please check the API token or email.</strong></h2>';
			}

			$this->update_config();
		}

		$zone_id_list = $this->get_single_config('cloudshield-cf-zoneid-list', array());
		if ($zone_id_list) {
			$message = '';
		}
		if (is_array($zone_id_list) && count($zone_id_list) > 0) {

			// If the domain name is found in the zone list, I will show it only instead of full domains list
			$current_domain = str_replace(array('/', 'http:', 'https:', 'www.'), '', site_url());

			foreach ($zone_id_list as $zone_id_name => $zone_id) {

				if ($zone_id_name == $current_domain) {
					$domain_found = true;
					$domain_zone_id = $zone_id;
					break;
				}
			}
		} else {
			$zone_id_list = array();
		}

		$list = array();
		if ($domain_found) {
			$list = array(
				$domain_zone_id => $current_domain
			);
		} else {
			foreach ($zone_id_list as $zone_id_name => $zone_id) {
				$list[$zone_id] = $zone_id_name;
			}
		}

		$options = get_option(WPOVEN_CLOUDSHIELD_SLUG);
		$cloudshield_cf_auth_mode = $options['cloudshield-cf-auth-mode'] ?? null;
		$domain_key_domain = $options['cloudshield-cf-zoneid'] ?? null;
		$waf_method = isset($options['cloudshield-waf-method']) ? $options['cloudshield-waf-method'] : 0;
		$result = array();

		$waf_controller = $this->cloudshield_waf_controller();

		$enable_log = array(
			'id'       => 'enable-log',
			'type'     => 'checkbox',
			'title'    => esc_html__('Enable Logs', 'wpoven-cloudshield'),
			'default'  => '1'
		);

		$cloudshield_log_retention = array(
			'id'       => 'cloudshield-log-retention',
			'type'     => 'select',
			'title'    => 'Logs Retention',
			'options'  => array(
				'0' => 'Unlimited',
				'1' => '1 day only',
				'7' => '7 days only',
			),
			'default'  => '1',
			'desc'     => 'Choose log retention period: unlimited or 7 days (default).',
		);

		$cloudshield_waf_method = array(
			'id'       => 'cloudshield-waf-method',
			'type'     => 'select',
			'title'    => 'WAF Method',
			'options'  => array(
				'0' => 'PHP',
				'1' => 'Cloudflare',
			),
			'default'  => '0',
			'desc'     => 'Select and implement <strong>WAF</strong> method using <strong>PHP</strong> or <strong>Cloudflare</strong>.',
		);


		$cloudshield_cf_authentication_mode = array(
			'id'          => 'cloudshield-cf-auth-mode',
			'type'        => 'select',
			'title'       => 'Authentication mode',
			'options'     => array(
				0  => 'API Key ',
				1  => 'API Token',
			),
			'default'   => 1,
			'required' => array('cloudshield-waf-method', 'equals', true),
			'desc'     => 'Authentication mode to use to connect to your Cloudflare account.'
		);

		$cloudshield_cf_email = array(
			'id'      => 'cloudshield-cf-email',
			'type'    => 'text',
			'validate' => 'email',
			'title'   => 'Cloudflare e-mail<strong style="color:red;">*</strong>',
			'required' => array('cloudshield-waf-method', 'equals', true),
			'desc'     => 'The email address you use to log in to Cloudflare.'
		);

		$cloudshield_cf_apikey = array(
			'id'      => 'cloudshield-cf-apikey',
			'type'    => 'password',
			'title'   => 'Cloudflare API Key<strong style="color:red;">*</strong>',
			'required' => array('cloudshield-cf-auth-mode', 'equals', CLOUDSHIELD_AUTH_MODE_API_KEY),
			'desc'     => 'The Global API Key is extracted from your Cloudflare account.'
		);

		$cloudshield_cf_apitoken = array(
			'id'      => 'cloudshield-cf-apitoken',
			'type'    => 'password',
			'title'   => 'Cloudflare API Token<strong style="color:red;">*</strong>',
			'required' => array('cloudshield-cf-auth-mode', 'equals', CLOUDSHIELD_AUTH_MODE_API_TOKEN),
			'desc'     => 'The API Token is extracted from your Cloudflare account.'
		);

		if ($cloudshield_cf_auth_mode) {
			$cloudshield_cf_apitoken_domain = array(
				'id'          => 'cloudshield-cf-zoneid',
				'type'        => 'select',
				'title'       => 'Cloudflare Domain Name<strong style="color:red;">*</strong>',
				'placeholder' => 'Select an option',
				'options'     => $list,
				'required' => array('cloudshield-cf-auth-mode', 'equals', CLOUDSHIELD_AUTH_MODE_API_TOKEN),
				'desc'     => 'Select the domain for which you want to enable the WAF settings and click on Save Changes.'
			);
		} else {
			$cloudshield_cf_apiKey_domain = array(
				'id'          => 'cloudshield-cf-zoneid',
				'type'        => 'select',
				'title'       => 'Cloudflare Domain Name<strong style="color:red;">*</strong>',
				'placeholder' => 'Select an option',
				'options'     => $list,
				'required' => array('cloudshield-cf-auth-mode', 'equals', CLOUDSHIELD_AUTH_MODE_API_KEY),
				'desc'        => 'Select the domain for which you want to enable the WAF settings and click on Save Changes.'
			);
		}


		$waf_controller_settings_info = array(
			'id'      => 'waf-controller',
			'type'    => 'info',
			'required' => array('cloudshield-waf-method', 'equals', true),
			'desc'    => '<div> ' . $message . '
							<h2><strong>Note :</strong></h2>
							<p>
								This plugin needs to create a <strong>dummy custom rule</strong>. This step will generate the unique ID required for <code>http_request_firewall_custom</code>.
								<br>This ID is essential for creating and managing your actual custom rules, after creation delete the dummy rule to avoid clutter in your WAF dashboard.
							</p>
							<h2>Create a Dummy Custom Rule in Cloudflare : </h2>
							<p>Follow the steps below to create a custom rule for your domain in Cloudflare( <a href="' . plugin_dir_url(__FILE__) . "image/custom-rule.png" . '" target="_blank" >Click here to see example</a> ):</p>
							<ol>
								<li>
									<a href="https://dash.cloudflare.com/login" target="_blank">Log in to your Cloudflare account</a>.
								</li>
								<li>
									Select your <strong>domain</strong> from the dashboard.
								</li>
								<li>
									Click on <strong>Security</strong> in the left-hand menu.
								</li>
								<li>
									Inside the <strong>Security</strong> section, click on <strong>WAF</strong> (Web Application Firewall).
								</li>
								<li>
									On the WAF security dashboard, click on <strong>Custom Rules</strong>.
								</li>
								<li>
									Click on the <strong>Create Rule</strong> button to start creating your custom rule.
								</li>
								<li>
									Provide a descriptive name for your rule in the <strong>Rule Name</strong> field.
								</li>
								<li>
									Define the rule conditions:
									<ul>
										<li><strong>Field:</strong> Select <code>URI</code>, <code>URI Full</code>, or another appropriate field.</li>
										<li><strong>Operator:</strong> Choose <code>wildcard</code>, <code>equals</code>, or another operator.</li>
										<li><strong>Value:</strong> Enter the specific value, such as <code>/example.php</code>.</li>
									</ul>
								</li>
								<li>
									Take action when the rule matches:
									<ul>
										<li><strong>Choose Action:</strong> Select from the following:
											<ul>
												<li><code>Managed Challenge</code></li>
												<li><code>JS Challenge</code></li>
												<li><code>Interactive Challenge</code></li>
												<li><code>Block</code></li>
												<li><code>Skip</code></li>
											</ul>
										</li>
									</ul>
								</li>
								<li>Click <strong>Deploy</strong> to activate your custom rule.</li>
								<li>To <strong>delete the custom rules</strong>, click on the <strong>three dots</strong> on the right side of the dashboard, and then click the <strong>Delete</strong> button.</li>
							</ol>
							<p><strong>Message:</strong> Ensure the dummy custom rule is deleted after deploy to maintain a clean and organized rules list.</p>
					    </div>

						<div>
							<h2>Create Cloudflare API Token :</h2>
							<p>Follow these simple steps to create and use a secure API Token instead of the Global API Key( <a href="' . plugin_dir_url(__FILE__) . "image/api-token.png" . '" target="_blank" >Click here to see example</a> ):</p>
							<ol>
								<li>
									In <a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank">Cloudflare account</a> dashboard navigate to <strong>My Profile</strong>.
								</li>
								<li>Under the <strong>API Tokens</strong> section, click <strong>Create Token</strong>.</li>
								<li>
									Click on <strong>Get Started</strong> at Create Custom Token and set the following permissions:
									<ul>
										<li><strong>Token Name:</strong> As per your need.</li>
									</ul>
								</li>
								<li>
									<strong>Permissions:</strong> Select <strong>Zone</strong> and set the following:
									<ul>
										<li><strong>Zone WAF:</strong><code> Edit, Read</code></li>
										<li><strong>Zone:</strong> <code>Read</code></li>
									</ul>
								</li>
								<li>
									<strong>Zone Resources:</strong> Select zones to <strong>include</strong>. Ensure the token applies to the <strong>specific zone</strong> you wish to manage.
								</li>
								<li><strong>Client IP Address Filtering:</strong> Not required.</li>
								<li>
									Set the <strong>TTL (Time to Live)</strong> for the token: <strong>Not required</strong>
									<ul>
										<li><strong>Start Date:</strong> Like today.</li>
										<li><strong>End Date:</strong> As long as you need.</li>
										<strong>Note:</strong> If the token expires, your plugin will be unable to create, update, or delete Cloudflare WAF rules.
									</ul>
								</li>
								<li>Click on <strong>Countinue to summary</strong> & then Click <strong>Create Token</strong> and copy the generated token.</li>
								<li>Enter the API Token and your email address into the form below and click <strong>Save Changes</strong>.</li>
								<li>Select the <strong>domain</strong> for which you want to enable the WAF settings and click <strong>Save Changes</strong>.</li>
							</ol>
							<p><strong>Note:</strong> Using an API Token is more secure and recommended over the Global API Key as it provides fine-grained permissions and an expiration period.</p>
						</div>
						<br>'
		);

		if ($waf_method == 0 || ($domain_key_domain && in_array($cloudshield_cf_auth_mode, [0, 1]))) {
			$result[] = $waf_controller;
		} else {
			$result[] = $waf_controller_settings_info;
		}

		$result[] = $enable_log;
		$result[] = $cloudshield_log_retention;
		$result[] = $cloudshield_waf_method;
		$result[] = $cloudshield_cf_authentication_mode;
		$result[] = $cloudshield_cf_email;
		$result[] = $cloudshield_cf_apikey;
		$result[] = $cloudshield_cf_apitoken;

		if ($list) {
			if ($cloudshield_cf_auth_mode) {
				$result[] = $cloudshield_cf_apitoken_domain;
			} else {
				$result[] = $cloudshield_cf_apiKey_domain;
			}
		}

		return $result;
	}


	/**
	 * Get all countries data from the countries.csv file.
	 */
	function cloudshield_load_country_list()
	{
		global $wp_filesystem;

		// Ensure the WordPress filesystem API is initialized
		if (empty($wp_filesystem)) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$csv_file_path = CLOUDSHIELD_PLUGIN_PATH . 'countries.csv';

		// Check if the file exists
		if (!$wp_filesystem->exists($csv_file_path)) {
			return [];
		}

		// Read file contents
		$file_contents = $wp_filesystem->get_contents($csv_file_path);
		if ($file_contents === false) {
			return [];
		}

		$country_list = [];
		$lines = explode("\n", trim($file_contents));

		// Skip the first line (header)
		array_shift($lines);

		foreach ($lines as $line) {
			$data = str_getcsv($line);
			$country_code = isset($data[0]) ? trim($data[0]) : '';
			$country_name = isset($data[1]) ? trim($data[1]) : $country_code;

			if (!empty($country_code)) {
				$country_list[$country_code] = $country_name;
			}
		}

		return $country_list;
	}


	/**
	 * Get all IPs from wp_cloudshield_logs table.
	 */

	function cloudshield_load_ip_list()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'cloudshield_logs';

		// Check if table exists
		$table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
		if (!$table_exists) {
			error_log('CloudShield Logs table does not exist.');
			return [];
		}

		// Fetch distinct IP addresses
		$ip_list = $wpdb->get_col("SELECT DISTINCT ip_address FROM $table_name");

		// Check for SQL errors
		if ($wpdb->last_error) {
			error_log('Error fetching IPs: ' . $wpdb->last_error);
			return [];
		}

		// Ensure $ip_list is an array
		if (!is_array($ip_list)) {
			return [];
		}

		// Filter and sanitize IPs
		$ip_list = array_filter($ip_list, function ($ip) {
			return !empty($ip) && filter_var($ip, FILTER_VALIDATE_IP);
		});

		// Remove duplicates
		$ip_list = array_unique($ip_list);

		// Format options for return
		$ip_options = [];
		foreach ($ip_list as $ip) {
			$ip_options[$ip] = $ip;
		}

		return $ip_options;
	}



	// function cloudshield_load_ip_list()
	// {
	// 	global $wpdb;
	// 	$table_name = $wpdb->prefix . 'cloudshield_logs';
	// 	$ip_list = $wpdb->get_col("SELECT DISTINCT ip_address FROM $table_name");
	// 	if ($wpdb->last_error) {
	// 		error_log('Error fetching IPs: ' . $wpdb->last_error);
	// 		return [];
	// 	}
	// 	$ip_list = array_filter($ip_list, function ($ip) {
	// 		return !empty($ip) && filter_var($ip, FILTER_VALIDATE_IP);
	// 	});
	// 	$ip_list = array_unique($ip_list);

	// 	$ip_options = [];
	// 	foreach ($ip_list as $ip) {
	// 		$ip_options[$ip] = $ip; // Key and value are the same (IP address)
	// 	}

	// 	return $ip_options;
	// }

	/**
	 * Set WPOven CloudShield WAF basic settings.
	 */
	function cloudshield_waf_settings()
	{
		$result = array();

		// Login Protection 
		$login_protection = array(
			'id'      => 'login-protection',
			'type'    => 'content',
			'mode'    => 'heading',
			'content' => 'Login Protection'
		);

		$divide = array(
			'id'   => 'divide',
			'type' => 'divide',
		);

		$cloudshield_whitelist_ip = array(
			'id'       => 'cloudshield-cf-whitelist-ip',
			'type'     => 'textarea',
			'title'    => 'Whitelist IP Addresses',
			'rows'    => 3,
			'placeholder' => '127.0.0.0, 198.5.5.5, etc.',
			'subtitle' => 'Enter each IP address, separated by commas. Whitelisted IPs will bypass Cloudflare and PHP-based blocking mechanisms.',
			'validate' => 'no_html'
		);

		$cloudshield_enable_captcha = array(
			'id'      => 'cloudshield-cf-enable-captcha',
			'type'    => 'switch',
			'title'   => 'Enable Cloudflare Captcha',
			'subtitle'    => 'Enable Cloudflare Captcha to protect your website from bots and ensure only legitimate traffic can access it.'
		);

		$cloudshield_Block_XMLRPC = array(
			'id'      => 'cloudshield-cf-block-xmlrpc',
			'type'    => 'switch',
			'title'   => 'Block Cloudflare XMLRPC',
			'subtitle'    => "Block Cloudflare XMLRPC to prevent unauthorized access and enhance your website's security against brute force attacks."
		);

		$cloudshield_wrong_login = array(
			'id'      => 'cloudshield-cf-wrong-login',
			'type'    => 'switch',
			'title'   => 'Block Wrong Login',
			'subtitle'    => 'Block Wrong Login attempts after multiple failed tries to enhance security and prevent brute force attacks.'
		);

		$login_block_request_rate = array(
			'id'        => 'cloudshield-login-block-request-rate',
			'type'      => 'slider',
			'title'     => 'Request Rate',
			'desc'      => 'Block Wrong Login rules in Cloudflare control request rates to prevent multiple login failures within a specified time frame.',
			"default"   => 5,
			"min"       => 0,
			"step"      => 1,
			"max"       => 20,
			'display_value' => 'text',
			'required' => array('cloudshield-cf-wrong-login', 'equals', true),
			'subtitle'    => ''
		);


		$cloudshield_country_block = array(
			'id'      => 'cloudshield-cf-country-block',
			'type'    => 'switch',
			'title'   => 'Enable Country Block',
			'subtitle'    => 'Enable Country Block to restrict login access, allowing only specified countries to enhance security.'
		);

		$cloudshield_country_list = array(
			'id'       => 'cloudshield-country-list',
			'type'     => 'select',
			'title'    => 'Allow Country',
			'placeholder' => 'Select country to enable admin login.',
			'required' => array('cloudshield-cf-country-block', 'equals', true),
			'options'  => $this->cloudshield_load_country_list(),
			'multi'    => true,
			'subtitle'    => 'Allow Country feature enables login access only from specified countries, enhancing security by restricting unauthorized locations.'
		);

		// DDoS Protection
		$ddos_protection = array(
			'id'      => 'ddos-protection',
			'type'    => 'content',
			'mode'    => 'heading',
			'content' => 'DDoS Protection',

		);

		$divide1 = array(
			'id'   => 'divide-1',
			'type' => 'divide',
		);

		$cloudshield_request_rate = array(
			'id'      => 'cloudshield-cf-request-rate',
			'type'    => 'switch',
			'title'   => 'Enable Request Rate',
			'subtitle'    => 'Enable Request Rate to block IPs that exceed a specified number of requests within a given time period, preventing bot abuse.'
		);

		$request_rate = array(
			'id'        => 'cloudshield-request-rate',
			'type'      => 'slider',
			'title'     => 'Request Rate',
			'desc'      => 'Rate limiting rules in Cloudflare control request rates to prevent abuse, DDoS attacks, and server overload',
			"default"   => 10,
			"min"       => 0,
			"step"      => 1,
			"max"       => 50,
			'display_value' => 'text',
			'required' => array('cloudshield-cf-request-rate', 'equals', true),
			'subtitle'    => ''
		);


		$cloudshield_ip_block = array(
			'id'      => 'cloudshield-cf-ip-block',
			'type'    => 'switch',
			'title'   => 'Enable IP Block',
			'subtitle'    => 'Enable IP Block to restrict access from specific IP addresses, enhancing site security and control.'
		);

		$cloudshield_ip_list = array(
			'id'       => 'cloudshield-ip-list',
			'type'     => 'select',
			'title'    => 'Block IP',
			'placeholder' => 'Select IP to block site access.',
			'required' => array('cloudshield-cf-ip-block', 'equals', true),
			'options'  => $this->cloudshield_load_ip_list(),
			'multi'    => true,
			'subtitle'    => 'Enable IP Block to restrict access from specific IP addresses to enhance site security.'
		);

		//Crawler Protection
		$crawler_protection = array(
			'id'      => 'crawler-protection',
			'type'    => 'content',
			'mode'    => 'heading',
			'content' => 'Crawler Protection',
		);

		$divide2 = array(
			'id'   => 'divide-2',
			'type' => 'divide',
		);

		$cloudshield_block_non_seo = array(
			'id'      => 'cloudshield-cf-block-non-seo',
			'type'    => 'switch',
			'title'   => 'Enable Block NON SEO',
			'subtitle'    => 'Enable Block NON SEO prevents non-search engine bots and scrapers from accessing your site, ensuring only legitimate traffic is allowed, while improving security and SEO performance.'
		);

		$cloudshield_block_ai_crawlers = array(
			'id'      => 'cloudshield-cf-block-ai-crawlers',
			'type'    => 'switch',
			'title'   => 'Enable Block AI Crawlers',
			'subtitle'    => 'Crawler Protection prevents automated bots and crawlers from accessing your site, safeguarding against unwanted traffic and potential scraping.'
		);
		$cloudshield_404_protection = array(
			'id'      => 'cloudshield-cf-404-protection',
			'type'    => 'switch',
			'title'   => 'Enable 404 Protection',
			'subtitle'    => 'Enable 404 Protection blocks excessive 404 requests, preventing bot activity and reducing server load and security risks.'
		);

		$result[] = $login_protection;
		$result[] = $divide;
		$result[] = $cloudshield_whitelist_ip;
		$result[] = $cloudshield_enable_captcha;
		$result[] = $cloudshield_Block_XMLRPC;
		$result[] = $cloudshield_wrong_login;
		$result[] = $login_block_request_rate;
		$result[] = $cloudshield_country_block;
		$result[] = $cloudshield_country_list;
		$result[] = $ddos_protection;
		$result[] = $divide1;
		$result[] = $cloudshield_request_rate;
		$result[] = $request_rate;
		$result[] = $cloudshield_ip_block;
		$result[] = $cloudshield_ip_list;
		$result[] = $crawler_protection;
		$result[] = $divide2;
		$result[] = $cloudshield_block_non_seo;
		$result[] = $cloudshield_block_ai_crawlers;
		$result[] = $cloudshield_404_protection;

		return $result;
	}

	/**
	 * Create performance logs pages.
	 */
	function cloudshield_logs()
	{
		echo '<div class="wrap"><h1><strong>CloudShield Logs</strong></h1>';
		echo '<form method="post">';

		$table = new WPOven_CloudShield_Logs_List_Table();
		$table->prepare_items();
		$table->search_box('search', 'search_id');
		$table->display();

		echo '</div></form>';
	}

	function ip_block_logs()
	{
		echo '<div class="wrap"><h1><strong>CloudShield IP Block Logs</strong></h1>';
		echo '<form method="post">';

		$table = new WPOven_CloudShield_IP_Block_List_Table();
		$table->prepare_items();
		$table->search_box('search', 'search_id');
		$table->display();

		echo '</div></form>';
	}





	/**
	 * Set WPOven CloudShield admin page.
	 */
	function setup_gui()
	{
		if (!class_exists('Redux')) {
			return;
		}

		$opt_name = WPOVEN_CLOUDSHIELD_SLUG;

		Redux::disable_demo();

		$args = array(
			'opt_name'                  => $opt_name,
			'display_name'              => 'WPOven CloudShield',
			'display_version'           => ' ',
			//'menu_type'                 => 'menu',
			'allow_sub_menu'            => true,
			//	'menu_title'                => esc_html__('CloudShield', 'WPOven CloudShield'),
			'page_title'                => esc_html__('WPOven CloudShield', 'WPOven CloudShield'),
			'disable_google_fonts_link' => false,
			'admin_bar'                 => false,
			'admin_bar_icon'            => 'dashicons-portfolio',
			'admin_bar_priority'        => 90,
			'global_variable'           => $opt_name,
			'dev_mode'                  => false,
			'customizer'                => false,
			'open_expanded'             => false,
			'disable_save_warn'         => false,
			'page_priority'             => 90,
			'page_parent'               => 'themes.php',
			'page_permissions'          => 'manage_options',
			'menu_icon'                 => plugin_dir_url(__FILE__) . '/image/logo.png',
			'last_tab'                  => '',
			'page_icon'                 => 'icon-themes',
			'page_slug'                 => $opt_name,
			'save_defaults'             => false,
			'default_show'              => false,
			'default_mark'              => '',
			'show_import_export'        => false,
			'transient_time'            => 60 * MINUTE_IN_SECONDS,
			'output'                    => false,
			'output_tag'                => false,
			//'footer_credit'             => 'Please rate WPOven CloudShield ★★★★★ on WordPress.org to support us. Thank you!',
			'footer_credit'             => ' ',
			'use_cdn'                   => false,
			'admin_theme'               => 'wp',
			'flyout_submenus'           => true,
			'font_display'              => 'swap',
			'hide_reset'                => true,
			'database'                  => '',
			'network_admin'           => '',
			'search'                    => false,
			'hide_expand'            => true,
		);

		Redux::set_args($opt_name, $args);

		Redux::set_section(
			$opt_name,
			array(
				'title'      => esc_html__('Settings', 'WPOven CloudShield'),
				'id'         => 'general',
				'subsection' => false,
				'icon'       => 'el el-cloud',
				'heading'    => 'Cloudshield General Settings',
				'fields'     => $this->cloudshield_general_settings(),
			)
		);

		Redux::set_section(
			$opt_name,
			array(
				'title'      => esc_html__('WAF Settings', 'WPOven CloudShield'),
				'id'         => 'waf',
				'subsection' => true,
				'heading'    => 'CLOUDFLARE WAF SETTINGS',
				'desc'    => 'After changing the WAF settings, click on the <strong>Update WAF Rules</strong> button to update the custom rule from <strong>WAF Actions Controller</strong> in Cloudflare Settings.',
				'fields'     => $this->cloudshield_waf_settings(),
				'icon'       => 'el el-cog'
			)
		);

		Redux::set_section(
			$opt_name,
			array(
				'title'      => '<a href="admin.php?page=view-cloudshield-ip-block-logs"  class="view-cloudshield-ip-block-logs"> <span class="group_title">IP Block Logs</span></a>',
				'id'         => 'cloudshield-ip-block-logs',
				'class'      => 'cloudshield-ip-block-logs',
				'parent'     => 'waf',
				'subsection' => true,
				'icon'       => '', //el el-list
			)
		);

		Redux::set_section(
			$opt_name,
			array(
				'title'      => '<a href="admin.php?page=view-cloudshield-logs"  class="view-cloudshield-logs"> <span class="group_title">View Logs</span></a>',
				'id'         => 'cloudshield-logs',
				'class'      => 'cloudshield-logs',
				'parent'     => 'waf',
				'subsection' => true,
				'icon'       => '', //el el-list
			)
		);
	}

	/**
	 * Add a admin menu.
	 */
	function wpoven_cloudshield_menu()
	{
		add_menu_page('WPOven Plugins', 'WPOven Plugins', '', 'wpoven', 'manage_options', plugin_dir_url(__FILE__) . '/image/logo.png');
		add_submenu_page('wpoven', 'CloudShield', 'CloudShield', 'manage_options', 'admin.php?page=wpoven-cloudshield&tab=1');
		add_submenu_page('admin.php?page=wpoven-cloudshield&tab=1', 'IP Block Logs', 'IP Block Logs', 'manage_options', 'view-cloudshield-ip-block-logs', array($this, 'ip_block_logs'));
		add_submenu_page('admin.php?page=wpoven-cloudshield&tab=1', 'View Logs', 'View Logs', 'manage_options', 'view-cloudshield-logs', array($this, 'cloudshield_logs'));
	}

	/**
	 * Hook to add the admin menu.
	 */
	public function admin_main(Wpoven_Cloudshield $wpoven_cloudshield)
	{
		$this->_wpoven_cloudshield = $wpoven_cloudshield;
		add_action('admin_menu', array($this, 'wpoven_cloudshield_menu'));
		$this->setup_gui();
	}
}

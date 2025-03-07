<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.wpoven.com
 * @since             1.0.0
 * @package           Wpoven_Cloudshield
 *
 * @wordpress-plugin
 * Plugin Name:       WPOven CloudShield
 * Plugin URI:        https://wpoven.com/plugins/wpoven-cloudshield
 * Description:       Integrate Cloudflare’s WAF with WordPress to block threats, secure vulnerabilities, and manage protection settings from your dashboard.
 * Version:           1.0.0
 * Author:            WPOven
 * Author URI:        https://www.wpoven.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpoven-cloudshield
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('WPOVEN_CLOUDSHIELD_VERSION', '1.0.0');

if (!defined('WPOVEN_CLOUDSHIELD_SLUG'))
	define('WPOVEN_CLOUDSHIELD_SLUG', 'wpoven-cloudshield');

define('WPOVEN_CLOUDSHIELD', 'WPOven CloudShield Options');
define('WPOVEN_CLOUDSHIELD_ROOT_PL', __FILE__);
define('WPOVEN_CLOUDSHIELD_ROOT_URL', plugins_url('', WPOVEN_CLOUDSHIELD_ROOT_PL));
define('WPOVEN_CLOUDSHIELD_ROOT_DIR', dirname(WPOVEN_CLOUDSHIELD_ROOT_PL));
define('WPOVEN_CLOUDSHIELD_PLUGIN_DIR', plugin_dir_path(__DIR__));
define('WPOVEN_CLOUDSHIELD_PLUGIN_BASE', plugin_basename(WPOVEN_CLOUDSHIELD_ROOT_PL));

define('CLOUDSHIELD_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CLOUDSHIELD_AUTH_MODE_API_KEY',   0);
define('CLOUDSHIELD_AUTH_MODE_API_TOKEN', 1);
define('WPOVEN_CLOUDSHIELD_PATH', realpath(plugin_dir_path(WPOVEN_CLOUDSHIELD_ROOT_PL)) . '/');

if (!defined('CLOUDSHIELD_CURL_TIMEOUT'))
	define('CLOUDSHIELD_CURL_TIMEOUT', 10);

/**
 * Check plugin updates.
 */
require_once plugin_dir_path(__FILE__) . 'includes/libraries/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/baseapp/wpoven_cloudshield/',
	__FILE__,
	'wpoven_cloudshield'
);
$myUpdateChecker->getVcsApi()->enableReleaseAssets();



/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wpoven-cloudshield-activator.php
 */
function activate_wpoven_cloudshield()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-wpoven-cloudshield-activator.php';
	Wpoven_Cloudshield_Activator::activate();

	$wpoven_cloudshield_logs = new Wpoven_Cloudshield_Admin('wpoven-cloudshield-logs', '1.0.0');
	$wpoven_cloudshield_logs->create_database_table();
	update_option('cloudshield_log_current_date', gmdate('Y-m-d'));
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wpoven-cloudshield-deactivator.php
 */
function deactivate_wpoven_cloudshield()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-wpoven-cloudshield-deactivator.php';
	Wpoven_Cloudshield_Deactivator::deactivate();

	// Delete cron job id plugin deactivated.
	$timestamp = wp_next_scheduled('cloudshield_cron_hook');
	if ($timestamp) {
		wp_unschedule_event($timestamp, 'cloudshield_cron_hook');
	}

	$user_ini_path = ABSPATH . '.user.ini';

	if (file_exists($user_ini_path)) {
		if (!unlink($user_ini_path)) {
			error_log("Error: Failed to delete .user.ini at: " . $user_ini_path);
		}
	}
}

register_activation_hook(__FILE__, 'activate_wpoven_cloudshield');
register_deactivation_hook(__FILE__, 'deactivate_wpoven_cloudshield');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-wpoven-cloudshield.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wpoven_cloudshield()
{

	$plugin = new Wpoven_Cloudshield();
	$plugin->run();
}
run_wpoven_cloudshield();


function wpoven_cloudshield_plugin_settings_link($links)
{
	$settings_link = '<a href="' . admin_url('admin.php?page=' . WPOVEN_CLOUDSHIELD_SLUG) . '">Settings</a>';

	array_push($links, $settings_link);
	return $links;
}
add_filter('plugin_action_links_' . WPOVEN_CLOUDSHIELD_PLUGIN_BASE, 'wpoven_cloudshield_plugin_settings_link');

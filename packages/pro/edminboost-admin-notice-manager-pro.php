<?php
/**
 * Edminboost Admin Notice Manager — Pro edition bootstrap.
 *
 * @package EdminBoost\AdminNoticeManager
 *
 * Plugin Name: Edminboost - Admin Notice Manager Pro
 * Plugin URI: https://asphaltthemes.com/edminboost/admin-notice-manager
 * Description: Pro edition — full admin notice management with multisite, digest, webhooks, migration, and white label.
 * Version: 1.1.1
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: ashiquzzaman
 * Author URI: https://asphaltthemes.com
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: edminboost-admin-notice-manager
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/boot.php';

edminboost_anm_register(
	__FILE__,
	array(
		'is_pro'  => true,
		'slug'    => 'edminboost-admin-notice-manager-pro',
		'version' => '1.1.1',
	)
);

edminboost_anm_run_plugin();

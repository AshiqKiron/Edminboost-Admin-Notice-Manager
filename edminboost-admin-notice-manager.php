<?php
/**
 * Monorepo dev entry — loads Free edition from packages/core (use npm run build for release zips).
 *
 * @package EdminBoost\AdminNoticeManager
 *
 * Plugin Name: Edminboost - Admin Notice Manager
 * Plugin URI: https://asphaltthemes.com/edminboost/admin-notice-manager
 * Description: Manage, organize, and control WordPress admin notices from one central dashboard.
 * Version: 1.1.1
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: ashiquzzaman
 * Author URI: https://asphaltthemes.com
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: edminboost-admin-notice-manager
 * Domain Path: /packages/core/languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/packages/core/boot.php';

edminboost_anm_register(
	__FILE__,
	array(
		'is_pro'   => false,
		'slug'     => 'edminboost-admin-notice-manager',
		'version'  => '1.1.1',
		'core_dir' => __DIR__ . '/packages/core',
		'core_url' => plugins_url( 'packages/core/', __FILE__ ),
	)
);

edminboost_anm_run_plugin();

<?php
/**
 * PHPUnit bootstrap — loads WordPress test suite and this plugin.
 *
 * @package EdminBoost\AdminNoticeManager
 */

$autoload = dirname( __DIR__ ) . '/vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
}

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "WordPress test library not found at {$_tests_dir}.\n";
	echo "Run: bash tests/bin/install-wp-tests.sh\n";
	echo "Or set WP_TESTS_DIR to your wordpress-tests-lib path.\n";
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin (monorepo dev entry).
 */
function _edminboost_anm_tests_load_plugin() {
	$plugin = dirname( __DIR__ ) . '/edminboost-admin-notice-manager.php';
	if ( ! file_exists( $plugin ) ) {
		return;
	}
	require $plugin;
}

tests_add_filter( 'muplugins_loaded', '_edminboost_anm_tests_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

require_once dirname( __FILE__ ) . '/helpers/class-edminboost-anm-test-case.php';

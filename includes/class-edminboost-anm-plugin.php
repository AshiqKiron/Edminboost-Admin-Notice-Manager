<?php
/**
 * Core plugin orchestrator — wires all hooks in one place.
 *
 * Purpose: Instantiate dependencies and register WordPress actions/filters.
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
class EDMINBOOST_ANM_Plugin {

	/**
	 * Admin handler.
	 *
	 * @var EDMINBOOST_ANM_Admin
	 */
	protected $admin;

	/**
	 * Notice manager.
	 *
	 * @var EDMINBOOST_ANM_Notices
	 */
	protected $notices;

	/**
	 * Notice interceptor.
	 *
	 * @var EDMINBOOST_ANM_Interceptor
	 */
	protected $interceptor;

	/**
	 * AJAX handler.
	 *
	 * @var EDMINBOOST_ANM_Ajax
	 */
	protected $ajax;

	/**
	 * Digest handler.
	 *
	 * @var EDMINBOOST_ANM_Digest
	 */
	protected $digest;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->admin       = new EDMINBOOST_ANM_Admin();
		$this->notices     = new EDMINBOOST_ANM_Notices();
		$this->interceptor = new EDMINBOOST_ANM_Interceptor();
		$this->ajax        = new EDMINBOOST_ANM_Ajax();
		$this->digest      = new EDMINBOOST_ANM_Digest();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'admin_menu', array( $this->admin, 'register_menu' ) );
		add_action( 'admin_init', array( $this->admin, 'register_settings' ) );
		add_action( 'admin_init', array( $this->admin, 'maybe_activation_redirect' ) );
		add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_scripts' ) );
		add_filter( 'plugin_action_links_' . EDMINBOOST_ANM_PLUGIN_BASENAME, array( $this->admin, 'add_settings_link' ) );
		add_filter( 'plugin_row_meta', array( $this->admin, 'add_plugin_row_meta' ), 10, 2 );
		add_action( 'init', array( $this->notices, 'register_hooks' ) );
		add_action( 'init', array( $this->interceptor, 'register_hooks' ) );
		add_action( 'init', array( $this->ajax, 'register_hooks' ) );
		add_action( 'init', array( $this->digest, 'register_hooks' ) );

		if ( class_exists( 'EDMINBOOST_ANM_Debug' ) ) {
			add_action( 'init', array( 'EDMINBOOST_ANM_Debug', 'register_hooks' ) );
		}
	}

	/**
	 * Load plugin text domain.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			EDMINBOOST_ANM_TEXT_DOMAIN,
			false,
			dirname( EDMINBOOST_ANM_PLUGIN_BASENAME ) . '/languages'
		);
	}
}

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
	 * Ad redirect blocker.
	 *
	 * @var EDMINBOOST_ANM_Ad_Redirects
	 */
	protected $ad_redirects;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->admin         = new EDMINBOOST_ANM_Admin();
		$this->notices       = new EDMINBOOST_ANM_Notices();
		$this->interceptor   = new EDMINBOOST_ANM_Interceptor();
		$this->ajax          = new EDMINBOOST_ANM_Ajax();
		$this->digest        = new EDMINBOOST_ANM_Digest();
		$this->ad_redirects  = new EDMINBOOST_ANM_Ad_Redirects();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'admin_menu', array( $this->admin, 'register_menu' ) );
		add_action( 'network_admin_menu', array( $this->admin, 'register_network_menu' ) );
		add_action( 'admin_init', array( $this->admin, 'register_settings' ) );
		add_action( 'network_admin_init', array( $this->admin, 'register_network_settings' ) );
		add_action( 'admin_init', array( $this->admin, 'maybe_activation_redirect' ) );
		add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_scripts' ) );
		add_action( 'network_admin_enqueue_scripts', array( $this->admin, 'enqueue_styles' ) );
		add_action( 'network_admin_enqueue_scripts', array( $this->admin, 'enqueue_scripts' ) );
		add_action( 'wp_initialize_site', array( $this, 'handle_new_site' ), 20, 1 );
		add_filter( 'plugin_action_links_' . EDMINBOOST_ANM_PLUGIN_BASENAME, array( $this->admin, 'add_settings_link' ) );
		add_filter( 'network_admin_plugin_action_links_' . EDMINBOOST_ANM_PLUGIN_BASENAME, array( $this->admin, 'add_settings_link' ) );
		add_filter( 'plugin_row_meta', array( $this->admin, 'add_plugin_row_meta' ), 10, 2 );
		add_action( 'init', array( $this->notices, 'register_hooks' ) );
		add_action( 'init', array( $this->interceptor, 'register_hooks' ) );
		add_action( 'init', array( $this->ajax, 'register_hooks' ) );
		add_action( 'init', array( $this->digest, 'register_hooks' ) );
		add_action( 'init', array( $this->ad_redirects, 'register_hooks' ) );

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

	/**
	 * Seed plugin options when a new site is created on a multisite network.
	 *
	 * @param WP_Site $new_site New site object.
	 * @return void
	 */
	public function handle_new_site( $new_site ) {
		if ( ! is_multisite() || ! is_object( $new_site ) || empty( $new_site->blog_id ) ) {
			return;
		}

		EDMINBOOST_ANM_Multisite::maybe_seed_new_site( (int) $new_site->blog_id );
	}
}

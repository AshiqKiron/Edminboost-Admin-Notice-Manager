<?php
/**
 * Dashboard page template.
 *
 * @package EdminBoost\AdminNoticeManager
 *
 * @var array $settings Plugin settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edminboost_anm_is_enabled = ! empty( $settings['enabled'] );
$edminboost_anm_settings_url = admin_url( 'admin.php?page=' . EDMINBOOST_ANM_Admin::PAGE_SLUG . '-settings' );
?>
<div class="wrap edminboost-anm-wrap">
	<header class="edminboost-anm-hero">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<p class="edminboost-anm-hero__lead">
			<?php esc_html_e( 'Review and manage WordPress admin notices from one central place.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
		</p>
	</header>

	<div class="edminboost-anm-card">
		<h2><?php esc_html_e( 'Status', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
		<p>
			<?php esc_html_e( 'Plugin status:', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
			<span class="edminboost-anm-status <?php echo $edminboost_anm_is_enabled ? 'is-active' : 'is-inactive'; ?>">
				<?php echo $edminboost_anm_is_enabled ? esc_html__( 'Active', EDMINBOOST_ANM_TEXT_DOMAIN ) : esc_html__( 'Disabled', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
			</span>
		</p>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( $edminboost_anm_settings_url ); ?>">
				<?php esc_html_e( 'Configure Settings', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
			</a>
		</p>
	</div>

	<div class="edminboost-anm-card">
		<h2><?php esc_html_e( 'Getting Started', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
		<p><?php esc_html_e( 'Use the settings page to enable notice panel features and control how admin notices appear across wp-admin.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
	</div>
</div>

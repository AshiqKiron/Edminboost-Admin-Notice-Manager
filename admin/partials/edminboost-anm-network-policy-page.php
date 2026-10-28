<?php
/**
 * Network multisite policy settings.
 *
 * @package EdminBoost\AdminNoticeManager
 *
 * @var array $edminboost_anm_network Network policy option.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap edminboost-anm-wrap edminboost-anm-settings">
	<header class="edminboost-anm-hero">
		<h1><?php esc_html_e( 'Multisite policy', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h1>
		<p class="description">
			<?php esc_html_e( 'Control how Notice Manager settings and auto-rules apply across every site on the network.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
		</p>
	</header>

	<form method="post" action="options.php" class="edminboost-anm-settings-form">
		<?php settings_fields( EDMINBOOST_ANM_Multisite::NETWORK_POLICY_GROUP ); ?>

		<div class="edminboost-anm-card">
			<h2><?php esc_html_e( 'Configuration mode', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
			<?php include EDMINBOOST_ANM_PLUGIN_DIR . 'admin/partials/edminboost-anm-multisite-policy-fields.php'; ?>
		</div>

		<?php include EDMINBOOST_ANM_PLUGIN_DIR . 'admin/partials/edminboost-anm-multisite-policy-next-steps.php'; ?>

		<?php submit_button(); ?>
	</form>
</div>

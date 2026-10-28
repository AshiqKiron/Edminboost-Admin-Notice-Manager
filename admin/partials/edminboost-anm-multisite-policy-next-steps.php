<?php
/**
 * Multisite policy follow-up links after saving network policy.
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="edminboost-anm-card">
	<h2><?php esc_html_e( 'Network defaults & rules', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
	<ul class="ul-disc">
		<li>
			<a href="<?php echo esc_url( EDMINBOOST_ANM_Multisite::get_network_settings_admin_url() ); ?>">
				<?php esc_html_e( 'Edit network default settings', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
			</a>
		</li>
		<li>
			<a href="<?php echo esc_url( network_admin_url( 'admin.php?page=' . EDMINBOOST_ANM_Multisite::NETWORK_PAGE_SLUG . '-rules' ) ); ?>">
				<?php esc_html_e( 'Manage network auto-rules', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
			</a>
		</li>
	</ul>
	<p class="description">
		<?php esc_html_e( 'Each site keeps its own notice inbox and statistics. Digest emails and webhooks run per site using the effective settings for that site.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
	</p>
</div>

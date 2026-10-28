<?php
/**
 * Dashboard page template.
 *
 * @package EdminBoost\AdminNoticeManager
 *
 * @var array $settings Plugin settings.
 * @var array $metrics  Dashboard metrics.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edminboost_anm_is_enabled     = ! empty( $settings['enabled'] );
$edminboost_anm_preset_enabled = ! empty( $settings['upsell_preset']['enabled'] );
$edminboost_anm_top_plugins    = EDMINBOOST_ANM_Stats::get_top_plugins( 5 );
$edminboost_anm_max_hits       = 1;

foreach ( $edminboost_anm_top_plugins as $plugin ) {
	$edminboost_anm_max_hits = max( $edminboost_anm_max_hits, (int) ( $plugin['hits'] ?? 0 ) );
}

$edminboost_anm_dashboard_brand       = EDMINBOOST_ANM_Settings::get_dashboard_brand();
$edminboost_anm_can_toggle_enabled    = EDMINBOOST_ANM_Multisite::site_can_edit_settings();
?>
<div class="wrap edminboost-anm-wrap">
	<div class="edminboost-anm-topbar">
		<?php if ( ! empty( $edminboost_anm_dashboard_brand['show'] ) ) : ?>
		<div class="edminboost-anm-topbar__brand">
			<?php if ( ! empty( $edminboost_anm_dashboard_brand['logo_url'] ) ) : ?>
				<img class="edminboost-anm-topbar__logo" src="<?php echo esc_url( $edminboost_anm_dashboard_brand['logo_url'] ); ?>" alt="<?php echo esc_attr( $edminboost_anm_dashboard_brand['name'] ?? '' ); ?>" />
			<?php endif; ?>
			<?php if ( '' !== ( $edminboost_anm_dashboard_brand['name'] ?? '' ) ) : ?>
				<strong><?php echo esc_html( $edminboost_anm_dashboard_brand['name'] ); ?></strong>
			<?php endif; ?>
		</div>
		<?php endif; ?>
		<div class="edminboost-anm-topbar__status" data-edminboost-anm-quick-status>
			<?php esc_html_e( 'Quick Status:', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
			<label class="edminboost-anm-switch edminboost-anm-switch--inline">
				<input type="checkbox" class="edminboost-anm-enabled-toggle" <?php checked( $edminboost_anm_is_enabled ); ?> <?php disabled( ! $edminboost_anm_can_toggle_enabled ); ?> aria-label="<?php esc_attr_e( 'Toggle notice interception', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>" />
				<span class="edminboost-anm-switch__slider"></span>
			</label>
			<span class="edminboost-anm-status <?php echo $edminboost_anm_is_enabled ? 'is-active' : 'is-inactive'; ?>" data-edminboost-anm-enabled-status>
				<?php
				if ( $edminboost_anm_is_enabled ) {
					esc_html_e( 'Active', EDMINBOOST_ANM_TEXT_DOMAIN );
				} else {
					esc_html_e( 'Inactive', EDMINBOOST_ANM_TEXT_DOMAIN );
				}
				?>
			</span>
		</div>
	</div>

	<div class="edminboost-anm-metrics">
		<div class="edminboost-anm-metric-card">
			<h3><?php esc_html_e( 'Total Notices Blocked (This Month)', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h3>
			<p class="edminboost-anm-metric-card__value"><?php echo esc_html( number_format_i18n( $metrics['monthly_blocked'] ) ); ?></p>
		</div>
		<div class="edminboost-anm-metric-card">
			<h3><?php esc_html_e( 'Top Spammer Plugin', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h3>
			<p class="edminboost-anm-metric-card__value edminboost-anm-metric-card__value--sm">
				<?php
				echo esc_html( $metrics['top_spammer'] );
				if ( $metrics['top_spammer_hits'] > 0 ) {
					echo ' <span class="edminboost-anm-muted">(' . esc_html( sprintf(
						/* translators: %d: hit count */
						__( '%d hits', EDMINBOOST_ANM_TEXT_DOMAIN ),
						$metrics['top_spammer_hits']
					) ) . ')</span>';
				}
				?>
			</p>
		</div>
		<div class="edminboost-anm-metric-card">
			<h3><?php esc_html_e( 'Active Auto-Rules', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h3>
			<p class="edminboost-anm-metric-card__value">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: number of active rules */
						__( '%d Active', EDMINBOOST_ANM_TEXT_DOMAIN ),
						$metrics['active_rules']
					)
				);
				?>
			</p>
		</div>
	</div>

	<div class="edminboost-anm-toggle-card" data-edminboost-anm-preset>
		<div class="edminboost-anm-toggle-card__header">
			<span class="dashicons dashicons-performance" aria-hidden="true"></span>
			<h2><?php esc_html_e( 'One-Click "Kill All Upsells" Preset', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
			<label class="edminboost-anm-switch">
				<input type="checkbox" class="edminboost-anm-preset-toggle" <?php checked( $edminboost_anm_preset_enabled ); ?> aria-label="<?php esc_attr_e( 'Toggle upsell preset', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>" />
				<span class="edminboost-anm-switch__slider"></span>
			</label>
		</div>
		<p><?php esc_html_e( 'Toggle ON to instantly suppress standard review requests, Black Friday nags, and promotional upsells.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
		<p class="edminboost-anm-toggle-card__status">
			<?php esc_html_e( 'Status:', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
			<span class="edminboost-anm-status <?php echo $edminboost_anm_preset_enabled ? 'is-active' : 'is-inactive'; ?>" data-edminboost-anm-preset-status>
				<?php echo $edminboost_anm_preset_enabled ? esc_html__( 'ENABLED', EDMINBOOST_ANM_TEXT_DOMAIN ) : esc_html__( 'DISABLED', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
			</span>
			<span class="edminboost-anm-muted" data-edminboost-anm-preset-blacklist<?php echo $edminboost_anm_preset_enabled ? '' : ' hidden'; ?>>
				<?php if ( $edminboost_anm_preset_enabled ) : ?>
					( <?php
					echo esc_html(
						sprintf(
							/* translators: %d: phrase count */
							__( 'Active blacklist: %d phrases', EDMINBOOST_ANM_TEXT_DOMAIN ),
							(int) ( $metrics['preset_phrase_count'] ?? 0 )
						)
					);
					?> )
				<?php endif; ?>
			</span>
		</p>
	</div>

	<?php if ( class_exists( 'EDMINBOOST_ANM_Debug' ) && EDMINBOOST_ANM_Debug::is_available() ) : ?>
		<div class="edminboost-anm-debug-card" data-edminboost-anm-debug>
			<div class="edminboost-anm-debug-card__header">
				<span class="dashicons dashicons-admin-tools" aria-hidden="true"></span>
				<h2><?php esc_html_e( 'Debug: Generate Test Notices', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
				<span class="edminboost-anm-debug-card__badge"><?php esc_html_e( 'WP_DEBUG', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></span>
			</div>
			<p><?php esc_html_e( 'Create sample notices for each inbox type (critical, warning, success, info, upsell). This panel is hidden when WP_DEBUG is off.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
			<?php
			$edminboost_anm_debug_notice_levels = array(
				'critical' => __( 'Error', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'warning'  => __( 'Warning', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'success'  => __( 'Success', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'info'     => __( 'Info', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'upsell'   => __( 'Upsell', EDMINBOOST_ANM_TEXT_DOMAIN ),
			);
			?>
			<fieldset class="edminboost-anm-debug-card__levels">
				<legend><?php esc_html_e( 'Notice levels', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></legend>
				<div class="edminboost-anm-debug-card__levels-grid">
					<?php foreach ( $edminboost_anm_debug_notice_levels as $edminboost_anm_level_slug => $edminboost_anm_level_label ) : ?>
						<label class="edminboost-anm-debug-card__level">
							<input
								type="checkbox"
								class="edminboost-anm-debug-notice-type"
								name="edminboost_anm_debug_notice_types[]"
								value="<?php echo esc_attr( $edminboost_anm_level_slug ); ?>"
								data-notice-type="<?php echo esc_attr( $edminboost_anm_level_slug ); ?>"
								checked="checked"
							/>
							<?php echo esc_html( $edminboost_anm_level_label ); ?>
						</label>
					<?php endforeach; ?>
				</div>
			</fieldset>
			<div class="edminboost-anm-debug-card__actions">
				<button type="button" class="button button-secondary edminboost-anm-seed-test-notices">
					<?php esc_html_e( 'Add to Inbox', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
				</button>
				<button type="button" class="button button-secondary edminboost-anm-trigger-test-notices">
					<?php esc_html_e( 'Trigger Live Notices', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
				</button>
			</div>
			<p class="edminboost-anm-muted edminboost-anm-debug-card__hint">
				<?php esc_html_e( '“Add to Inbox” seeds the Notice Inbox directly. “Trigger Live Notices” redirects to the Dashboard home, fires real admin_notices output, and exercises PHP/JS interception (requires Quick Status to be active).', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
			</p>
			<p class="edminboost-anm-debug-card__feedback" data-edminboost-anm-debug-feedback hidden></p>
		</div>
	<?php endif; ?>

	<div class="edminboost-anm-split">
		<div class="edminboost-anm-card">
			<h2><?php esc_html_e( 'Plugin Spam Score (Top 5)', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
			<?php if ( ! empty( $edminboost_anm_top_plugins ) ) : ?>
				<div class="edminboost-anm-bar-chart">
					<?php foreach ( $edminboost_anm_top_plugins as $index => $plugin ) : ?>
						<?php
						$hits   = (int) ( $plugin['hits'] ?? 0 );
						$width  = round( ( $hits / $edminboost_anm_max_hits ) * 100 );
						$label  = $plugin['label'] ?? __( 'Unknown', EDMINBOOST_ANM_TEXT_DOMAIN );
						?>
						<div class="edminboost-anm-bar-chart__row">
							<span class="edminboost-anm-bar-chart__label"><?php echo esc_html( ( $index + 1 ) . '. ' . $label ); ?></span>
							<div class="edminboost-anm-bar-chart__track">
								<div class="edminboost-anm-bar-chart__bar" style="width: <?php echo esc_attr( $width ); ?>%;"></div>
							</div>
							<span class="edminboost-anm-bar-chart__value">(<?php echo esc_html( $hits ); ?>)</span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="edminboost-anm-muted"><?php esc_html_e( 'No intercepted notices yet. Stats will appear as notices are captured.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
			<?php endif; ?>
		</div>

		<div class="edminboost-anm-card">
			<?php
			$edminboost_anm_inbox_url = admin_url( 'admin.php?page=' . EDMINBOOST_ANM_Admin::PAGE_SLUG . '-inbox' );
			?>
			<div class="edminboost-anm-card__heading">
				<h2><?php esc_html_e( 'Live Blocked Feed', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
				<a class="button button-link" href="<?php echo esc_url( $edminboost_anm_inbox_url ); ?>">
					<?php esc_html_e( 'View more', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
				</a>
			</div>
			<?php if ( ! empty( $metrics['feed'] ) ) : ?>
				<div class="edminboost-anm-feed-scroll" tabindex="0" aria-label="<?php esc_attr_e( 'Recent blocked notices', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>">
					<ul class="edminboost-anm-feed">
						<?php foreach ( $metrics['feed'] as $item ) : ?>
							<li class="edminboost-anm-feed__item">
								<span class="edminboost-anm-feed__source"><?php echo esc_html( $item['source'] ?? '' ); ?></span>
								<span class="edminboost-anm-feed__message"><?php echo esc_html( $item['message'] ?? '' ); ?></span>
								<span class="edminboost-anm-feed__time"><?php echo esc_html( human_time_diff( (int) ( $item['time'] ?? 0 ), time() ) ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php else : ?>
				<p class="edminboost-anm-muted"><?php esc_html_e( 'No recent blocked notices.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</div>

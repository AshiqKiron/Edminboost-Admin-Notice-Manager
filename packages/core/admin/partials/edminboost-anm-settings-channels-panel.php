<?php
/**
 * Settings Channels tab — notification source × delivery matrix.
 *
 * @package EdminBoost\AdminNoticeManager
 *
 * @var string $edminboost_anm_option_name Option name for Settings API fields.
 * @var string $edminboost_anm_active_tab  Active settings tab slug.
 * @var array  $edminboost_anm_channel_prefs Merged source channel preferences.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edminboost_anm_registry        = EDMINBOOST_ANM_Notification_Sources::get_registry();
$edminboost_anm_channel_labels  = EDMINBOOST_ANM_Notification_Sources::get_channel_labels();
$edminboost_anm_fn_core_active  = EDMINBOOST_ANM_Feature_Notifications::is_available();
$edminboost_anm_is_pro          = function_exists( 'edminboost_anm_is_pro' ) && edminboost_anm_is_pro();
?>
<div
	id="<?php echo esc_attr( 'edminboost-anm-settings-panel-channels' ); ?>"
	class="edminboost-anm-tab-panel"
	role="tabpanel"
	data-tab="channels"
	aria-labelledby="<?php echo esc_attr( 'edminboost-anm-settings-tab-channels' ); ?>"
	<?php echo 'channels' !== $edminboost_anm_active_tab ? ' hidden' : ''; ?>
>
	<div class="edminboost-anm-card edminboost-anm-notification-channels" data-edminboost-anm-notification-channels>
		<h2><?php esc_html_e( 'Notification sources', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Choose where notices from each source are delivered. Admin covers the Notice Inbox and admin bar panel. Email covers the weekly digest recent list and aligns with core Feature Notifications email when that ships.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
		</p>
		<?php if ( $edminboost_anm_fn_core_active ) : ?>
			<p class="description">
				<strong><?php esc_html_e( 'WordPress Feature Notifications is active.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></strong>
				<?php esc_html_e( 'Admin and Email preferences here apply to notices this plugin captures until core subscription APIs can be synced.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
			</p>
		<?php endif; ?>

		<input type="hidden" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[notification_channels][_matrix]" value="1" />

		<div class="edminboost-anm-notification-channels-table-wrap">
			<table class="widefat striped edminboost-anm-notification-channels-table" role="grid" aria-describedby="edminboost-anm-notification-channels-caption">
				<caption id="edminboost-anm-notification-channels-caption" class="screen-reader-text">
					<?php esc_html_e( 'Notification source delivery channels', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
				</caption>
				<thead>
					<tr>
						<th scope="col" class="edminboost-anm-notification-channels-table__source">
							<?php esc_html_e( 'Notification source', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</th>
						<?php foreach ( $edminboost_anm_channel_labels as $edminboost_anm_ch_slug => $edminboost_anm_ch_label ) : ?>
							<th scope="col" class="edminboost-anm-notification-channels-table__channel">
								<span class="edminboost-anm-notification-channels-table__channel-label"><?php echo esc_html( $edminboost_anm_ch_label ); ?></span>
								<label class="edminboost-anm-notification-channels-table__select-column">
									<input
										type="checkbox"
										class="edminboost-anm-notification-channels-select-column"
										data-channel="<?php echo esc_attr( $edminboost_anm_ch_slug ); ?>"
										<?php disabled( 'webhook' === $edminboost_anm_ch_slug && ! $edminboost_anm_is_pro ); ?>
									/>
									<span class="screen-reader-text">
										<?php
										echo esc_html(
											sprintf(
												/* translators: %s: channel name */
												__( 'Select all %s', EDMINBOOST_ANM_TEXT_DOMAIN ),
												$edminboost_anm_ch_label
											)
										);
										?>
									</span>
								</label>
							</th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $edminboost_anm_registry as $edminboost_anm_section ) : ?>
						<?php
						$edminboost_anm_section_id = sanitize_key( (string) ( $edminboost_anm_section['id'] ?? '' ) );
						$edminboost_anm_children   = isset( $edminboost_anm_section['children'] ) && is_array( $edminboost_anm_section['children'] )
							? $edminboost_anm_section['children']
							: array();
						if ( empty( $edminboost_anm_children ) ) {
							continue;
						}
						?>
						<tr class="edminboost-anm-notification-channels-table__group">
							<th scope="row" colspan="<?php echo esc_attr( (string) ( 1 + count( $edminboost_anm_channel_labels ) ) ); ?>">
								<label class="edminboost-anm-notification-channels-table__group-toggle">
									<input
										type="checkbox"
										class="edminboost-anm-notification-channels-select-group"
										data-group="<?php echo esc_attr( $edminboost_anm_section_id ); ?>"
									/>
									<span><?php echo esc_html( (string) ( $edminboost_anm_section['label'] ?? $edminboost_anm_section_id ) ); ?></span>
								</label>
							</th>
						</tr>
						<?php foreach ( $edminboost_anm_children as $edminboost_anm_child ) : ?>
							<?php
							$edminboost_anm_source_id = sanitize_key( (string) ( $edminboost_anm_child['id'] ?? '' ) );
							if ( '' === $edminboost_anm_source_id ) {
								continue;
							}
							$edminboost_anm_row_prefs = $edminboost_anm_channel_prefs[ $edminboost_anm_source_id ] ?? array();
							?>
							<tr class="edminboost-anm-notification-channels-table__row" data-group="<?php echo esc_attr( $edminboost_anm_section_id ); ?>">
								<th scope="row" class="edminboost-anm-notification-channels-table__source edminboost-anm-notification-channels-table__source--child">
									<?php echo esc_html( (string) ( $edminboost_anm_child['label'] ?? $edminboost_anm_source_id ) ); ?>
								</th>
								<?php foreach ( array_keys( $edminboost_anm_channel_labels ) as $edminboost_anm_ch_slug ) : ?>
									<?php
									$edminboost_anm_checked = ! empty( $edminboost_anm_row_prefs[ $edminboost_anm_ch_slug ] );
									$edminboost_anm_disabled = ( 'webhook' === $edminboost_anm_ch_slug && ! $edminboost_anm_is_pro );
									$edminboost_anm_input_id = 'edminboost-anm-ch-' . $edminboost_anm_source_id . '-' . $edminboost_anm_ch_slug;
									?>
									<td class="edminboost-anm-notification-channels-table__cell">
										<label for="<?php echo esc_attr( $edminboost_anm_input_id ); ?>">
											<input
												type="checkbox"
												id="<?php echo esc_attr( $edminboost_anm_input_id ); ?>"
												class="edminboost-anm-notification-channels-cell"
												name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[notification_channels][sources][<?php echo esc_attr( $edminboost_anm_source_id ); ?>][<?php echo esc_attr( $edminboost_anm_ch_slug ); ?>]"
												value="1"
												data-channel="<?php echo esc_attr( $edminboost_anm_ch_slug ); ?>"
												data-group="<?php echo esc_attr( $edminboost_anm_section_id ); ?>"
												<?php checked( $edminboost_anm_checked ); ?>
												<?php disabled( $edminboost_anm_disabled ); ?>
											/>
											<span class="screen-reader-text">
												<?php
												echo esc_html(
													sprintf(
														/* translators: 1: source label, 2: channel name */
														__( '%1$s — %2$s', EDMINBOOST_ANM_TEXT_DOMAIN ),
														(string) ( $edminboost_anm_child['label'] ?? $edminboost_anm_source_id ),
														$edminboost_anm_channel_labels[ $edminboost_anm_ch_slug ]
													)
												);
												?>
											</span>
										</label>
									</td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</tbody>
				<tfoot>
					<tr>
						<td class="edminboost-anm-notification-channels-table__source">
							<?php esc_html_e( 'Select all', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</td>
						<?php foreach ( $edminboost_anm_channel_labels as $edminboost_anm_ch_slug => $edminboost_anm_ch_label ) : ?>
							<td class="edminboost-anm-notification-channels-table__cell">
								<label>
									<input
										type="checkbox"
										class="edminboost-anm-notification-channels-select-column edminboost-anm-notification-channels-select-column--footer"
										data-channel="<?php echo esc_attr( $edminboost_anm_ch_slug ); ?>"
										<?php disabled( 'webhook' === $edminboost_anm_ch_slug && ! $edminboost_anm_is_pro ); ?>
									/>
									<span class="screen-reader-text">
										<?php
										echo esc_html(
											sprintf(
												/* translators: %s: channel name */
												__( 'Select all %s', EDMINBOOST_ANM_TEXT_DOMAIN ),
												$edminboost_anm_ch_label
											)
										);
										?>
									</span>
								</label>
							</td>
						<?php endforeach; ?>
					</tr>
				</tfoot>
			</table>
		</div>
		<?php if ( ! $edminboost_anm_is_pro ) : ?>
			<p class="description">
				<?php esc_html_e( 'Per-source webhook delivery requires Notice Manager Pro. Type-based webhook routing on the Notifications tab still applies in Pro.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
			</p>
		<?php endif; ?>
	</div>
</div>

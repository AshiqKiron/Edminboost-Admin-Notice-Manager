<?php
/**
 * Inbox GitHub-style feed list items.
 *
 * @package EdminBoost\AdminNoticeManager
 *
 * @var array $notices Inbox notices for the current page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

foreach ( $notices as $notice ) :
	$edminboost_anm_is_converted  = EDMINBOOST_ANM_Inbox::is_converted_to_rule( $notice );
	$edminboost_anm_is_read       = EDMINBOOST_ANM_Inbox::is_marked_read( $notice );
	$edminboost_anm_is_snoozed    = EDMINBOOST_ANM_Inbox::is_actively_snoozed( $notice );
	$edminboost_anm_snooze_duration = EDMINBOOST_ANM_Inbox::get_snooze_duration_key( $notice );
	$edminboost_anm_snooze_undo_tip = esc_attr__( 'Snoozed. Click again to unsnooze.', EDMINBOOST_ANM_TEXT_DOMAIN );
	$edminboost_anm_snooze_days_num = '';
	if ( $edminboost_anm_is_snoozed ) {
		$edminboost_anm_snooze_days_num = '7d' === $edminboost_anm_snooze_duration ? '7' : '1';
	}

	$edminboost_anm_notice_type   = EDMINBOOST_ANM_Inbox::sanitize_notice_type_slug( $notice['type'] ?? 'info' );
	$edminboost_anm_severity_slug = EDMINBOOST_ANM_Inbox::get_severity_slug( $edminboost_anm_notice_type );
	$edminboost_anm_icon          = 'dashicons-info';
	if ( in_array( $edminboost_anm_severity_slug, array( 'critical', 'error' ), true ) ) {
		$edminboost_anm_icon = 'dashicons-warning';
	} elseif ( 'warning' === $edminboost_anm_severity_slug ) {
		$edminboost_anm_icon = 'dashicons-flag';
	} elseif ( 'success' === $edminboost_anm_severity_slug ) {
		$edminboost_anm_icon = 'dashicons-yes-alt';
	}

	$edminboost_anm_timestamp = (int) ( $notice['intercepted_at'] ?? $notice['last_seen'] ?? 0 );
	$edminboost_anm_when      = $edminboost_anm_timestamp > 0
		? sprintf(
			/* translators: %s: human-readable time difference */
			__( '%s ago', EDMINBOOST_ANM_TEXT_DOMAIN ),
			human_time_diff( $edminboost_anm_timestamp, time() )
		)
		: '';

	$edminboost_anm_title   = wp_html_excerpt( $notice['message'] ?? '', 80, '…' );
	$edminboost_anm_excerpt = wp_html_excerpt( $notice['message'] ?? '', 160, '…' );
	$edminboost_anm_source  = $notice['source_plugin'] ?? '';
	$edminboost_anm_repeat  = max( 1, (int) ( $notice['repeat_count'] ?? 1 ) );

	$edminboost_anm_item_class = 'edminboost-anm-inbox-feed__item';
	if ( $edminboost_anm_is_read ) {
		$edminboost_anm_item_class .= ' is-read';
	}
	?>
	<li class="<?php echo esc_attr( $edminboost_anm_item_class ); ?>" data-notice-id="<?php echo esc_attr( $notice['id'] ); ?>">
		<span class="edminboost-anm-inbox-feed__unread" aria-hidden="true"<?php echo $edminboost_anm_is_read ? ' hidden' : ''; ?>></span>
		<input type="checkbox" class="edminboost-anm-notice-check edminboost-anm-inbox-feed__check" value="<?php echo esc_attr( $notice['id'] ); ?>" aria-label="<?php esc_attr_e( 'Select notice', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>" />
		<span class="edminboost-anm-inbox-feed__icon dashicons <?php echo esc_attr( $edminboost_anm_icon ); ?>" aria-hidden="true"></span>
		<div class="edminboost-anm-inbox-feed__body">
			<p class="edminboost-anm-inbox-feed__title">
				<?php echo esc_html( $edminboost_anm_title ); ?>
				<?php if ( $edminboost_anm_repeat > 1 ) : ?>
					<span class="edminboost-anm-inbox-repeat-badge">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: number of similar captures */
								__( '×%d similar', EDMINBOOST_ANM_TEXT_DOMAIN ),
								$edminboost_anm_repeat
							)
						);
						?>
					</span>
				<?php endif; ?>
			</p>
			<?php if ( $edminboost_anm_excerpt !== $edminboost_anm_title ) : ?>
				<p class="edminboost-anm-inbox-feed__excerpt"><?php echo esc_html( $edminboost_anm_excerpt ); ?></p>
			<?php endif; ?>
		</div>
		<div class="edminboost-anm-inbox-feed__meta">
			<span class="edminboost-anm-badge edminboost-anm-badge--<?php echo esc_attr( $edminboost_anm_notice_type ); ?> edminboost-anm-badge--severity-<?php echo esc_attr( $edminboost_anm_severity_slug ); ?> edminboost-anm-inbox-feed__label">
				<?php echo esc_html( EDMINBOOST_ANM_Inbox::get_severity_label( $edminboost_anm_severity_slug ) ); ?>
			</span>
			<span class="edminboost-anm-inbox-feed__source" title="<?php echo esc_attr( $edminboost_anm_source ); ?>"><?php echo esc_html( wp_html_excerpt( $edminboost_anm_source, 24, '…' ) ); ?></span>
			<?php if ( '' !== $edminboost_anm_when ) : ?>
				<time class="edminboost-anm-inbox-feed__time"><?php echo esc_html( $edminboost_anm_when ); ?></time>
			<?php endif; ?>
		</div>
		<div class="edminboost-anm-inbox-feed__actions edminboost-anm-actions">
			<button type="button" class="button button-small edminboost-anm-inbox-read<?php echo $edminboost_anm_is_read ? ' is-marked-read' : ''; ?>" data-id="<?php echo esc_attr( $notice['id'] ); ?>"<?php echo $edminboost_anm_is_read ? ' disabled aria-disabled="true"' : ''; ?>><?php esc_html_e( 'Read', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
			<div class="edminboost-anm-dropdown">
				<button type="button" class="button button-small edminboost-anm-dropdown__toggle edminboost-anm-inbox-snooze-toggle<?php echo $edminboost_anm_is_snoozed ? ' is-snoozed' : ''; ?>" data-id="<?php echo esc_attr( $notice['id'] ); ?>" aria-expanded="false" aria-pressed="<?php echo $edminboost_anm_is_snoozed ? 'true' : 'false'; ?>"<?php echo $edminboost_anm_is_snoozed ? ' title="' . $edminboost_anm_snooze_undo_tip . '"' : ''; ?>><span class="edminboost-anm-inbox-snooze-toggle__label"><?php esc_html_e( 'Snooze', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></span><span class="edminboost-anm-inbox-snooze-toggle__days"<?php echo '' === $edminboost_anm_snooze_days_num ? ' hidden' : ''; ?>><?php echo esc_html( $edminboost_anm_snooze_days_num ); ?></span><span class="edminboost-anm-inbox-snooze-toggle__caret" aria-hidden="true"> ▾</span></button>
				<div class="edminboost-anm-dropdown__menu">
					<button type="button" class="edminboost-anm-inbox-snooze<?php echo '1d' === $edminboost_anm_snooze_duration ? ' is-snoozed-active' : ''; ?>" data-id="<?php echo esc_attr( $notice['id'] ); ?>" data-duration="1d" aria-pressed="<?php echo '1d' === $edminboost_anm_snooze_duration ? 'true' : 'false'; ?>"><?php esc_html_e( '1 Day', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
					<button type="button" class="edminboost-anm-inbox-snooze<?php echo '7d' === $edminboost_anm_snooze_duration ? ' is-snoozed-active' : ''; ?>" data-id="<?php echo esc_attr( $notice['id'] ); ?>" data-duration="7d" aria-pressed="<?php echo '7d' === $edminboost_anm_snooze_duration ? 'true' : 'false'; ?>"><?php esc_html_e( '7 Days', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
				</div>
			</div>
			<button type="button" class="button button-small edminboost-anm-inbox-convert<?php echo $edminboost_anm_is_converted ? ' is-converted-to-rule' : ''; ?>" data-id="<?php echo esc_attr( $notice['id'] ); ?>" aria-pressed="<?php echo $edminboost_anm_is_converted ? 'true' : 'false'; ?>"<?php echo $edminboost_anm_is_converted ? ' title="' . esc_attr__( 'Converted to rule. Click again to undo.', EDMINBOOST_ANM_TEXT_DOMAIN ) . '"' : ''; ?>><?php esc_html_e( 'Convert to Rule', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
		</div>
	</li>
	<?php
endforeach;

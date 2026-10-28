<?php
/**
 * Inbox table body rows.
 *
 * @package EdminBoost\AdminNoticeManager
 *
 * @var array $notices Inbox notices for the current page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

foreach ( $notices as $notice ) :
	$edminboost_anm_is_converted   = EDMINBOOST_ANM_Inbox::is_converted_to_rule( $notice );
	$edminboost_anm_is_read          = EDMINBOOST_ANM_Inbox::is_marked_read( $notice );
	$edminboost_anm_is_snoozed       = EDMINBOOST_ANM_Inbox::is_actively_snoozed( $notice );
	$edminboost_anm_snooze_duration  = EDMINBOOST_ANM_Inbox::get_snooze_duration_key( $notice );
	$edminboost_anm_snooze_undo_tip  = esc_attr__( 'Snoozed. Click again to unsnooze.', EDMINBOOST_ANM_TEXT_DOMAIN );
	$edminboost_anm_snooze_days_num  = '';
	if ( $edminboost_anm_is_snoozed ) {
		$edminboost_anm_snooze_days_num = '7d' === $edminboost_anm_snooze_duration ? '7' : '1';
	}
	?>
	<tr data-notice-id="<?php echo esc_attr( $notice['id'] ); ?>"<?php echo $edminboost_anm_is_read ? ' class="is-read"' : ''; ?>>
		<th scope="row" class="check-column">
			<input type="checkbox" class="edminboost-anm-notice-check" value="<?php echo esc_attr( $notice['id'] ); ?>" aria-label="<?php esc_attr_e( 'Select notice', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>" />
		</th>
		<td class="column-source" data-colname="<?php esc_attr_e( 'Source Plugin', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>"><?php echo esc_html( $notice['source_plugin'] ?? '' ); ?></td>
		<td class="column-message column-primary" data-colname="<?php esc_attr_e( 'Notice Message Preview', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>"><?php echo esc_html( wp_html_excerpt( $notice['message'] ?? '', 60, '…' ) ); ?></td>
		<?php $edminboost_anm_notice_type = EDMINBOOST_ANM_Inbox::sanitize_notice_type_slug( $notice['type'] ?? 'info' ); ?>
		<td class="column-type" data-colname="<?php esc_attr_e( 'Type', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>"><span class="edminboost-anm-badge edminboost-anm-badge--<?php echo esc_attr( $edminboost_anm_notice_type ); ?>"><?php echo esc_html( EDMINBOOST_ANM_Inbox::get_type_label( $edminboost_anm_notice_type ) ); ?></span></td>
		<td class="edminboost-anm-actions column-actions" data-colname="<?php esc_attr_e( 'Actions', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>">
			<button type="button" class="button button-small edminboost-anm-inbox-read<?php echo $edminboost_anm_is_read ? ' is-marked-read' : ''; ?>" data-id="<?php echo esc_attr( $notice['id'] ); ?>"<?php echo $edminboost_anm_is_read ? ' disabled aria-disabled="true"' : ''; ?>><?php esc_html_e( 'Read', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
			<div class="edminboost-anm-dropdown">
				<button type="button" class="button button-small edminboost-anm-dropdown__toggle edminboost-anm-inbox-snooze-toggle<?php echo $edminboost_anm_is_snoozed ? ' is-snoozed' : ''; ?>" data-id="<?php echo esc_attr( $notice['id'] ); ?>" aria-expanded="false" aria-pressed="<?php echo $edminboost_anm_is_snoozed ? 'true' : 'false'; ?>"<?php echo $edminboost_anm_is_snoozed ? ' title="' . $edminboost_anm_snooze_undo_tip . '"' : ''; ?>><span class="edminboost-anm-inbox-snooze-toggle__label"><?php esc_html_e( 'Snooze', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></span><span class="edminboost-anm-inbox-snooze-toggle__days"<?php echo '' === $edminboost_anm_snooze_days_num ? ' hidden' : ''; ?>><?php echo esc_html( $edminboost_anm_snooze_days_num ); ?></span><span class="edminboost-anm-inbox-snooze-toggle__caret" aria-hidden="true"> ▾</span></button>
				<div class="edminboost-anm-dropdown__menu">
					<button type="button" class="edminboost-anm-inbox-snooze<?php echo '1d' === $edminboost_anm_snooze_duration ? ' is-snoozed-active' : ''; ?>" data-id="<?php echo esc_attr( $notice['id'] ); ?>" data-duration="1d" aria-pressed="<?php echo '1d' === $edminboost_anm_snooze_duration ? 'true' : 'false'; ?>"><?php esc_html_e( '1 Day', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
					<button type="button" class="edminboost-anm-inbox-snooze<?php echo '7d' === $edminboost_anm_snooze_duration ? ' is-snoozed-active' : ''; ?>" data-id="<?php echo esc_attr( $notice['id'] ); ?>" data-duration="7d" aria-pressed="<?php echo '7d' === $edminboost_anm_snooze_duration ? 'true' : 'false'; ?>"><?php esc_html_e( '7 Days', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
				</div>
			</div>
			<button type="button" class="button button-small edminboost-anm-inbox-convert<?php echo $edminboost_anm_is_converted ? ' is-converted-to-rule' : ''; ?>" data-id="<?php echo esc_attr( $notice['id'] ); ?>" aria-pressed="<?php echo $edminboost_anm_is_converted ? 'true' : 'false'; ?>"<?php echo $edminboost_anm_is_converted ? ' title="' . esc_attr__( 'Converted to rule. Click again to undo.', EDMINBOOST_ANM_TEXT_DOMAIN ) . '"' : ''; ?>><?php esc_html_e( 'Convert to Rule', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
		</td>
	</tr>
	<?php
endforeach;

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
	$edminboost_anm_is_converted = EDMINBOOST_ANM_Inbox::is_converted_to_rule( $notice );
	?>
	<tr data-notice-id="<?php echo esc_attr( $notice['id'] ); ?>">
		<th scope="row" class="check-column">
			<input type="checkbox" class="edminboost-anm-notice-check" value="<?php echo esc_attr( $notice['id'] ); ?>" aria-label="<?php esc_attr_e( 'Select notice', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>" />
		</th>
		<td class="column-source"><?php echo esc_html( $notice['source_plugin'] ?? '' ); ?></td>
		<td class="column-message"><?php echo esc_html( wp_html_excerpt( $notice['message'] ?? '', 60, '…' ) ); ?></td>
		<td class="column-type"><span class="edminboost-anm-badge edminboost-anm-badge--<?php echo esc_attr( $notice['type'] ?? 'info' ); ?>"><?php echo esc_html( EDMINBOOST_ANM_Inbox::get_type_label( $notice['type'] ?? 'info' ) ); ?></span></td>
		<td class="edminboost-anm-actions column-actions">
			<button type="button" class="button button-small edminboost-anm-inbox-read" data-id="<?php echo esc_attr( $notice['id'] ); ?>"><?php esc_html_e( 'Read', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
			<div class="edminboost-anm-dropdown">
				<button type="button" class="button button-small edminboost-anm-dropdown__toggle" aria-expanded="false"><?php esc_html_e( 'Snooze', EDMINBOOST_ANM_TEXT_DOMAIN ); ?> ▾</button>
				<div class="edminboost-anm-dropdown__menu">
					<button type="button" class="edminboost-anm-inbox-snooze" data-id="<?php echo esc_attr( $notice['id'] ); ?>" data-duration="1d"><?php esc_html_e( '1 Day', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
					<button type="button" class="edminboost-anm-inbox-snooze" data-id="<?php echo esc_attr( $notice['id'] ); ?>" data-duration="7d"><?php esc_html_e( '7 Days', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
				</div>
			</div>
			<button type="button" class="button button-small edminboost-anm-inbox-convert<?php echo $edminboost_anm_is_converted ? ' is-converted-to-rule' : ''; ?>" data-id="<?php echo esc_attr( $notice['id'] ); ?>" aria-pressed="<?php echo $edminboost_anm_is_converted ? 'true' : 'false'; ?>"<?php echo $edminboost_anm_is_converted ? ' title="' . esc_attr__( 'Converted to rule. Click again to undo.', EDMINBOOST_ANM_TEXT_DOMAIN ) . '"' : ''; ?>><?php esc_html_e( 'Convert to Rule', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
		</td>
	</tr>
	<?php
endforeach;

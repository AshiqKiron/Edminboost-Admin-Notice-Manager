<?php
/**
 * Auto-Rules page template.
 *
 * @package EdminBoost\AdminNoticeManager
 *
 * @var array $settings   Plugin settings.
 * @var array $rules      Auto-rules list.
 * @var array $wp_presets WordPress core notice presets with conversion status.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edminboost_anm_option_name    = EDMINBOOST_ANM_Settings::OPTION_NAME;
$edminboost_anm_rules_readonly = ! empty( $edminboost_anm_rules_readonly );
?>
<div class="wrap edminboost-anm-wrap edminboost-anm-wrap--wide" data-edminboost-anm-rules<?php echo $edminboost_anm_rules_readonly ? ' data-edminboost-anm-rules-readonly="1"' : ''; ?>>
	<header class="edminboost-anm-page-header">
		<h1><?php esc_html_e( 'Auto-Rules & Keyword Filtering', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h1>
		<?php if ( ! $edminboost_anm_rules_readonly ) : ?>
			<button type="button" class="button button-primary edminboost-anm-add-rule"><?php esc_html_e( '+ Add New Rule', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
		<?php endif; ?>
	</header>

	<?php if ( $edminboost_anm_rules_readonly ) : ?>
		<div class="notice notice-warning">
			<p><?php esc_html_e( 'Auto-rules are managed by your network administrator on this site.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
		</div>
	<?php endif; ?>

	<div class="edminboost-anm-card edminboost-anm-card--flush edminboost-anm-card--list-table" data-edminboost-anm-rules-list>
		<?php if ( ! empty( $rules ) ) : ?>
			<table class="wp-list-table widefat fixed striped edminboost-anm-table edminboost-anm-rules-table">
				<thead>
					<tr>
						<th scope="col" class="manage-column column-primary"><?php esc_html_e( 'Rule Name / Pattern', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<th scope="col" class="manage-column"><?php esc_html_e( 'Target Type', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<th scope="col" class="manage-column"><?php esc_html_e( 'Schedule', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<th scope="col" class="manage-column"><?php esc_html_e( 'Hit Count', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<th scope="col" class="manage-column column-actions"><?php esc_html_e( 'Status / Actions', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rules as $rule ) : ?>
						<?php
						$edminboost_anm_schedule       = EDMINBOOST_ANM_Rules::sanitize_schedule( $rule['schedule'] ?? array() );
						$edminboost_anm_schedule_label = EDMINBOOST_ANM_Rules::get_schedule_summary( $rule );
						$edminboost_anm_outside        = ! empty( $rule['enabled'] ) && ! empty( $edminboost_anm_schedule['enabled'] ) && ! EDMINBOOST_ANM_Rules::is_schedule_active( $edminboost_anm_schedule );
						?>
						<tr data-rule-id="<?php echo esc_attr( $rule['id'] ); ?>"<?php echo ! empty( $rule['wp_notice_preset'] ) ? ' data-wp-notice-preset="' . esc_attr( $rule['wp_notice_preset'] ) . '"' : ''; ?>>
							<td class="column-primary" data-colname="<?php esc_attr_e( 'Rule Name / Pattern', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>">
								<strong><?php echo esc_html( $rule['name'] ?? '' ); ?></strong>
								<br /><code><?php echo esc_html( $rule['match_string'] ?? '' ); ?></code>
							</td>
							<td data-colname="<?php esc_attr_e( 'Target Type', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>"><?php echo esc_html( EDMINBOOST_ANM_Rules::get_match_type_label( $rule['match_type'] ?? 'keyword' ) ); ?></td>
							<td data-colname="<?php esc_attr_e( 'Schedule', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>">
								<?php if ( '' !== $edminboost_anm_schedule_label ) : ?>
									<span class="edminboost-anm-rule-schedule"><?php echo esc_html( $edminboost_anm_schedule_label ); ?></span>
									<?php if ( $edminboost_anm_outside ) : ?>
										<br /><span class="edminboost-anm-badge edminboost-anm-badge--muted"><?php esc_html_e( 'Outside schedule', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></span>
									<?php endif; ?>
								<?php else : ?>
									<span class="edminboost-anm-muted"><?php esc_html_e( 'Always on', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></span>
								<?php endif; ?>
							</td>
							<td data-colname="<?php esc_attr_e( 'Hit Count', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>">
								<?php
								echo esc_html(
									sprintf(
										/* translators: %d: hit count */
										__( '%d hits', EDMINBOOST_ANM_TEXT_DOMAIN ),
										(int) ( $rule['hit_count'] ?? 0 )
									)
								);
								?>
							</td>
							<td class="edminboost-anm-actions column-actions" data-colname="<?php esc_attr_e( 'Status / Actions', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>">
								<label class="edminboost-anm-switch edminboost-anm-switch--inline">
									<input type="checkbox" class="edminboost-anm-rule-toggle" data-id="<?php echo esc_attr( $rule['id'] ); ?>" <?php checked( ! empty( $rule['enabled'] ) ); ?> aria-label="<?php esc_attr_e( 'Toggle rule', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>" />
									<span class="edminboost-anm-switch__slider"></span>
								</label>
								<button type="button" class="button button-small edminboost-anm-edit-rule"
									data-id="<?php echo esc_attr( $rule['id'] ); ?>"
									data-name="<?php echo esc_attr( $rule['name'] ?? '' ); ?>"
									data-match-type="<?php echo esc_attr( $rule['match_type'] ?? 'keyword' ); ?>"
									data-match-string="<?php echo esc_attr( $rule['match_string'] ?? '' ); ?>"
									data-rule-action="<?php echo esc_attr( $rule['action'] ?? 'auto_dismiss_inbox' ); ?>"
									data-schedule="<?php echo esc_attr( wp_json_encode( $edminboost_anm_schedule ) ); ?>"
								><?php esc_html_e( 'Edit', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
								<a href="#"
									class="submitdelete edminboost-anm-delete-rule"
									role="button"
									data-id="<?php echo esc_attr( $rule['id'] ); ?>"
									aria-label="<?php echo esc_attr( sprintf( /* translators: %s: rule name */ __( 'Delete rule “%s”', EDMINBOOST_ANM_TEXT_DOMAIN ), $rule['name'] ?? '' ) ); ?>"
								><?php echo esc_html( _x( 'Delete', 'verb', EDMINBOOST_ANM_TEXT_DOMAIN ) ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<div class="edminboost-anm-empty">
				<p><?php esc_html_e( 'No auto-rules yet. Add a rule or convert an inbox notice to get started.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
			</div>
		<?php endif; ?>
	</div>

	<div class="edminboost-anm-card edminboost-anm-card--flush edminboost-anm-card--list-table edminboost-anm-wp-presets">
		<div class="edminboost-anm-card__heading edminboost-anm-wp-presets__heading">
			<div>
				<h2><?php esc_html_e( 'Default WordPress Notices', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
				<p class="description"><?php esc_html_e( 'Common core admin notices you can convert into filter rules with one click.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
			</div>
		</div>
		<?php if ( ! empty( $wp_presets ) ) : ?>
			<table class="wp-list-table widefat fixed striped edminboost-anm-table edminboost-anm-wp-presets-table">
				<thead>
					<tr>
						<th scope="col" class="manage-column column-source"><?php esc_html_e( 'Source Plugin', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<th scope="col" class="manage-column column-message column-primary"><?php esc_html_e( 'Notice Message Preview', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<th scope="col" class="manage-column column-type"><?php esc_html_e( 'Type', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<th scope="col" class="manage-column column-actions"><?php esc_html_e( 'Actions', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $wp_presets as $preset ) : ?>
						<?php
						$edminboost_anm_is_converted = ! empty( $preset['converted'] );
						?>
						<tr data-preset-id="<?php echo esc_attr( $preset['id'] ?? '' ); ?>">
							<td class="column-source" data-colname="<?php esc_attr_e( 'Source Plugin', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>"><?php echo esc_html( $preset['source'] ?? __( 'WordPress Core', EDMINBOOST_ANM_TEXT_DOMAIN ) ); ?></td>
							<td class="column-message column-primary" data-colname="<?php esc_attr_e( 'Notice Message Preview', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>"><?php echo esc_html( $preset['message'] ?? '' ); ?></td>
							<td class="column-type" data-colname="<?php esc_attr_e( 'Type', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>"><span class="edminboost-anm-badge edminboost-anm-badge--<?php echo esc_attr( $preset['type'] ?? 'info' ); ?>"><?php echo esc_html( EDMINBOOST_ANM_Inbox::get_type_label( $preset['type'] ?? 'info' ) ); ?></span></td>
							<td class="edminboost-anm-actions column-actions" data-colname="<?php esc_attr_e( 'Actions', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>">
								<button type="button" class="button button-small edminboost-anm-wp-preset-convert<?php echo $edminboost_anm_is_converted ? ' is-converted-to-rule' : ''; ?>" data-preset-id="<?php echo esc_attr( $preset['id'] ?? '' ); ?>" aria-pressed="<?php echo $edminboost_anm_is_converted ? 'true' : 'false'; ?>"<?php echo $edminboost_anm_is_converted ? ' title="' . esc_attr__( 'Converted to rule. Click again to undo.', EDMINBOOST_ANM_TEXT_DOMAIN ) . '"' : ''; ?>>
									<?php esc_html_e( 'Convert to Rule', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
				<tfoot>
					<tr>
						<th scope="col" class="manage-column column-source"><?php esc_html_e( 'Source Plugin', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<th scope="col" class="manage-column column-message column-primary"><?php esc_html_e( 'Notice Message Preview', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<th scope="col" class="manage-column column-type"><?php esc_html_e( 'Type', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<th scope="col" class="manage-column column-actions"><?php esc_html_e( 'Actions', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
					</tr>
				</tfoot>
			</table>
		<?php endif; ?>
	</div>

	<div class="edminboost-anm-modal" id="edminboost-anm-rule-modal" aria-hidden="true" role="dialog" aria-labelledby="edminboost-anm-rule-modal-title">
		<div class="edminboost-anm-modal__backdrop"></div>
		<div class="edminboost-anm-modal__content">
			<h2 id="edminboost-anm-rule-modal-title"><?php esc_html_e( 'Create New Filter Rule', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
			<form class="edminboost-anm-rule-form">
				<input type="hidden" name="id" id="edminboost-anm-rule-id" value="" />
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="edminboost-anm-rule-name"><?php esc_html_e( 'Rule Name', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
						</th>
						<td>
							<input type="text" id="edminboost-anm-rule-name" name="name" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Hide Feedback Nags', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>" required />
						</td>
					</tr>
					<tr class="edminboost-anm-rule-form__row-match-type">
						<th scope="row"><?php esc_html_e( 'Match Type', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<td>
							<fieldset class="edminboost-anm-rule-form__match-type">
								<legend class="screen-reader-text"><?php esc_html_e( 'Match Type', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></legend>
								<label><input type="radio" name="match_type" value="keyword" checked /> <?php esc_html_e( 'Keyword Match', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
								<label><input type="radio" name="match_type" value="regex" /> <?php esc_html_e( 'Regex Pattern', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
								<label><input type="radio" name="match_type" value="css" /> <?php esc_html_e( 'CSS Selector', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="edminboost-anm-rule-string"><?php esc_html_e( 'Match String', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
						</th>
						<td>
							<input type="text" id="edminboost-anm-rule-string" name="match_string" class="regular-text" placeholder="<?php esc_attr_e( 'Please rate our plugin', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>" required />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="edminboost-anm-rule-action"><?php esc_html_e( 'Action', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
						</th>
						<td>
							<select id="edminboost-anm-rule-action" name="rule_action" class="regular-text">
								<option value="auto_dismiss_inbox"><?php esc_html_e( 'Auto-Dismiss & Route to Inbox Archive', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></option>
								<option value="dismiss_only"><?php esc_html_e( 'Auto-Dismiss Only', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></option>
								<option value="route_inbox"><?php esc_html_e( 'Route to Inbox Only', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></option>
							</select>
						</td>
					</tr>
					<tr class="edminboost-anm-rule-form__row-schedule">
						<th scope="row"><?php esc_html_e( 'Schedule', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<td>
							<label class="edminboost-anm-rule-form__schedule-enable">
								<input type="checkbox" id="edminboost-anm-rule-schedule-enabled" name="schedule_enabled" value="1" />
								<?php esc_html_e( 'Only apply this rule during set days and hours', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Uses your site timezone. Leave all weekdays unchecked to run every day.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
							<fieldset class="edminboost-anm-rule-form__schedule-fields" id="edminboost-anm-rule-schedule-fields" hidden>
								<legend class="screen-reader-text"><?php esc_html_e( 'Rule schedule', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></legend>
								<div class="edminboost-anm-rule-form__schedule-days">
									<?php foreach ( EDMINBOOST_ANM_Rules::get_schedule_weekday_choices() as $edminboost_anm_day_slug => $edminboost_anm_day_label ) : ?>
										<label class="edminboost-anm-rule-form__schedule-day">
											<input type="checkbox" name="schedule_days[]" value="<?php echo esc_attr( $edminboost_anm_day_slug ); ?>" />
											<?php echo esc_html( $edminboost_anm_day_label ); ?>
										</label>
									<?php endforeach; ?>
								</div>
								<div class="edminboost-anm-rule-form__schedule-times">
									<label for="edminboost-anm-rule-schedule-start"><?php esc_html_e( 'From', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
									<input type="time" id="edminboost-anm-rule-schedule-start" name="schedule_start" value="09:00" step="60" />
									<label for="edminboost-anm-rule-schedule-end"><?php esc_html_e( 'Until', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
									<input type="time" id="edminboost-anm-rule-schedule-end" name="schedule_end" value="17:00" step="60" />
								</div>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row" aria-hidden="true"></th>
						<td class="edminboost-anm-modal__actions">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Rule', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
							<button type="button" class="button edminboost-anm-modal-close"><?php esc_html_e( 'Cancel', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
						</td>
					</tr>
				</table>
			</form>
		</div>
	</div>
</div>

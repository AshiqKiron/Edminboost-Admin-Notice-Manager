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

$edminboost_anm_option_name = EDMINBOOST_ANM_Settings::OPTION_NAME;
?>
<div class="wrap edminboost-anm-wrap edminboost-anm-wrap--wide" data-edminboost-anm-rules>
	<header class="edminboost-anm-page-header">
		<h1><?php esc_html_e( 'Auto-Rules & Keyword Filtering', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h1>
		<button type="button" class="button button-primary edminboost-anm-add-rule"><?php esc_html_e( '+ Add New Rule', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
	</header>

	<div class="edminboost-anm-card edminboost-anm-card--flush">
		<?php if ( ! empty( $rules ) ) : ?>
			<table class="edminboost-anm-table widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Rule Name / Pattern', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<th><?php esc_html_e( 'Target Type', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<th><?php esc_html_e( 'Hit Count', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<th><?php esc_html_e( 'Status / Actions', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rules as $rule ) : ?>
						<tr data-rule-id="<?php echo esc_attr( $rule['id'] ); ?>">
							<td>
								<strong><?php echo esc_html( $rule['name'] ?? '' ); ?></strong>
								<br /><code><?php echo esc_html( $rule['match_string'] ?? '' ); ?></code>
							</td>
							<td><?php echo esc_html( EDMINBOOST_ANM_Rules::get_match_type_label( $rule['match_type'] ?? 'keyword' ) ); ?></td>
							<td>
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
							<td class="edminboost-anm-actions">
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
								><?php esc_html_e( 'Edit', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
								<button type="button" class="button button-small edminboost-anm-delete-rule" data-id="<?php echo esc_attr( $rule['id'] ); ?>"><?php esc_html_e( 'Delete', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
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

	<div class="postbox edminboost-anm-wp-presets">
		<div class="postbox-header">
			<h2 class="hndle"><?php esc_html_e( 'Default WordPress Notices', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
		</div>
		<div class="inside">
		<?php if ( ! empty( $wp_presets ) ) : ?>
			<table class="wp-list-table widefat fixed striped edminboost-anm-table edminboost-anm-wp-presets-table">
				<thead>
					<tr>
						<th scope="col" class="manage-column column-source"><?php esc_html_e( 'Source Plugin', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<th scope="col" class="manage-column column-message"><?php esc_html_e( 'Notice Message Preview', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
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
							<td class="column-source"><?php echo esc_html( $preset['source'] ?? __( 'WordPress Core', EDMINBOOST_ANM_TEXT_DOMAIN ) ); ?></td>
							<td class="column-message"><?php echo esc_html( $preset['message'] ?? '' ); ?></td>
							<td class="column-type"><span class="edminboost-anm-badge edminboost-anm-badge--<?php echo esc_attr( $preset['type'] ?? 'info' ); ?>"><?php echo esc_html( EDMINBOOST_ANM_Inbox::get_type_label( $preset['type'] ?? 'info' ) ); ?></span></td>
							<td class="edminboost-anm-actions column-actions">
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
						<th scope="col" class="manage-column column-message"><?php esc_html_e( 'Notice Message Preview', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<th scope="col" class="manage-column column-type"><?php esc_html_e( 'Type', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<th scope="col" class="manage-column column-actions"><?php esc_html_e( 'Actions', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
					</tr>
				</tfoot>
			</table>
		<?php endif; ?>
		</div>
	</div>

	<div class="edminboost-anm-modal" id="edminboost-anm-rule-modal" aria-hidden="true" role="dialog" aria-labelledby="edminboost-anm-rule-modal-title">
		<div class="edminboost-anm-modal__backdrop"></div>
		<div class="edminboost-anm-modal__content">
			<h2 id="edminboost-anm-rule-modal-title"><?php esc_html_e( 'Create New Filter Rule', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
			<form class="edminboost-anm-rule-form">
				<input type="hidden" name="id" id="edminboost-anm-rule-id" value="" />
				<p>
					<label for="edminboost-anm-rule-name"><?php esc_html_e( 'Rule Name:', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
					<input type="text" id="edminboost-anm-rule-name" name="name" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Hide Feedback Nags', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>" required />
				</p>
				<fieldset>
					<legend><?php esc_html_e( 'Match Type:', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></legend>
					<label><input type="radio" name="match_type" value="keyword" checked /> <?php esc_html_e( 'Keyword Match', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
					<label><input type="radio" name="match_type" value="regex" /> <?php esc_html_e( 'Regex Pattern', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
					<label><input type="radio" name="match_type" value="css" /> <?php esc_html_e( 'CSS Selector', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
				</fieldset>
				<p>
					<label for="edminboost-anm-rule-string"><?php esc_html_e( 'Match String:', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
					<input type="text" id="edminboost-anm-rule-string" name="match_string" class="regular-text" placeholder="<?php esc_attr_e( 'Please rate our plugin', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>" required />
				</p>
				<p>
					<label for="edminboost-anm-rule-action"><?php esc_html_e( 'Action:', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
					<select id="edminboost-anm-rule-action" name="rule_action">
						<option value="auto_dismiss_inbox"><?php esc_html_e( 'Auto-Dismiss & Route to Inbox Archive', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></option>
						<option value="dismiss_only"><?php esc_html_e( 'Auto-Dismiss Only', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></option>
						<option value="route_inbox"><?php esc_html_e( 'Route to Inbox Only', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></option>
					</select>
				</p>
				<p class="edminboost-anm-modal__actions">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Rule', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
					<button type="button" class="button edminboost-anm-modal-close"><?php esc_html_e( 'Cancel', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
				</p>
			</form>
		</div>
	</div>
</div>

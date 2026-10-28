<?php
/**
 * Billing page — Free vs Pro comparison.
 *
 * @package EdminBoost\AdminNoticeManager
 *
 * @var array<int, array{feature: string, tip: string, free: bool|string, pro: bool|string}> $edminboost_anm_billing_rows Comparison rows.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edminboost_anm_pro_url = EDMINBOOST_ANM_PRO_URL;
?>
<div class="wrap edminboost-anm-wrap edminboost-anm-wrap--wide edminboost-anm-billing">
	<h1><?php esc_html_e( 'Billing & plans', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h1>

	<div class="edminboost-anm-card edminboost-anm-card--flush edminboost-anm-billing-card">
		<div class="edminboost-anm-billing-table-scroll">
			<table class="edminboost-anm-billing-table">
				<thead>
					<tr>
						<th scope="col" class="edminboost-anm-billing-table__feature-col"><?php esc_html_e( 'Feature', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<th scope="col" class="edminboost-anm-billing-table__plan-col edminboost-anm-billing-table__plan-col--free">
							<span class="edminboost-anm-billing-plan-badge edminboost-anm-billing-plan-badge--free"><?php esc_html_e( 'Free', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></span>
						</th>
						<th scope="col" class="edminboost-anm-billing-table__plan-col edminboost-anm-billing-table__plan-col--pro">
							<span class="edminboost-anm-billing-plan-badge edminboost-anm-billing-plan-badge--pro"><?php esc_html_e( 'Pro', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></span>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $edminboost_anm_billing_rows as $edminboost_anm_billing_row ) : ?>
						<?php
						$edminboost_anm_billing_tip = isset( $edminboost_anm_billing_row['tip'] ) ? (string) $edminboost_anm_billing_row['tip'] : '';
						?>
						<tr>
							<th scope="row" class="edminboost-anm-billing-table__feature-col">
								<span class="edminboost-anm-billing-feature-label">
									<?php if ( '' !== $edminboost_anm_billing_tip ) : ?>
										<?php echo EDMINBOOST_ANM_Admin::help_tip( $edminboost_anm_billing_tip ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
									<?php endif; ?>
									<span class="edminboost-anm-billing-feature-label__text"><?php echo esc_html( $edminboost_anm_billing_row['feature'] ); ?></span>
								</span>
							</th>
							<td class="edminboost-anm-billing-table__plan-col edminboost-anm-billing-table__plan-col--free">
								<?php echo EDMINBOOST_ANM_Admin::render_billing_comparison_cell( $edminboost_anm_billing_row['free'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
							</td>
							<td class="edminboost-anm-billing-table__plan-col edminboost-anm-billing-table__plan-col--pro">
								<?php echo EDMINBOOST_ANM_Admin::render_billing_comparison_cell( $edminboost_anm_billing_row['pro'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div class="edminboost-anm-billing-footer">
			<a class="button button-primary edminboost-anm-billing-upgrade" href="<?php echo esc_url( $edminboost_anm_pro_url ); ?>" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Upgrade', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
			</a>
		</div>
	</div>
</div>

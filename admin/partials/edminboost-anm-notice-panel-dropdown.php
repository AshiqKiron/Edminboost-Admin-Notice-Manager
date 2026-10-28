<?php
/**
 * Admin bar notification dropdown panel markup.
 *
 * @package EdminBoost\AdminNoticeManager
 *
 * @var array  $edminboost_anm_notices   Recent inbox notices.
 * @var string $edminboost_anm_inbox_url Inbox admin URL.
 * @var int    $edminboost_anm_total     Total active inbox count.
 * @var string $edminboost_anm_panel_layout Panel layout: detailed|compact.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edminboost_anm_panel_layout = sanitize_key( $edminboost_anm_panel_layout ?? 'detailed' );
if ( ! in_array( $edminboost_anm_panel_layout, array( 'detailed', 'compact' ), true ) ) {
	$edminboost_anm_panel_layout = 'detailed';
}

$edminboost_anm_panel_modifier = 'compact' === $edminboost_anm_panel_layout
	? 'edminboost-anm-notices-panel--compact'
	: 'edminboost-anm-notices-panel--detailed';
?>
<div class="edminboost-anm-notices-panel <?php echo esc_attr( $edminboost_anm_panel_modifier ); ?>" id="edminboost-anm-notices-panel" role="region" aria-label="<?php esc_attr_e( 'Admin notice inbox', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>" hidden>
	<div class="edminboost-anm-notices-panel__header">
		<strong><?php esc_html_e( 'Admin notices', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></strong>
		<button type="button" class="edminboost-anm-notices-panel__close" aria-label="<?php esc_attr_e( 'Close panel', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>">&times;</button>
	</div>
	<ul class="edminboost-anm-notices-panel__list" data-empty-label="<?php echo esc_attr__( 'No intercepted notices yet. Browse wp-admin to capture nags here.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>">
		<?php if ( empty( $edminboost_anm_notices ) ) : ?>
			<li class="edminboost-anm-notices-panel__empty">
				<?php esc_html_e( 'No intercepted notices yet. Browse wp-admin to capture nags here.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
			</li>
		<?php else : ?>
			<?php foreach ( $edminboost_anm_notices as $edminboost_anm_notice ) : ?>
				<?php
				$edminboost_anm_id      = $edminboost_anm_notice['id'] ?? '';
				$edminboost_anm_type    = EDMINBOOST_ANM_Inbox::sanitize_notice_type_slug( $edminboost_anm_notice['type'] ?? 'info' );
				$edminboost_anm_when    = (int) ( $edminboost_anm_notice['intercepted_at'] ?? 0 );
				$edminboost_anm_logged  = EDMINBOOST_ANM_Inbox::format_first_logged( $edminboost_anm_when );
				$edminboost_anm_html    = $edminboost_anm_notice['html'] ?? '';
				$edminboost_anm_message = $edminboost_anm_notice['message'] ?? '';
				$edminboost_anm_source  = $edminboost_anm_notice['source'] ?? '';

				if ( 'compact' === $edminboost_anm_panel_layout ) :
					$edminboost_anm_type_class = 'is-type-' . $edminboost_anm_type;
					if ( in_array( $edminboost_anm_type, array( 'error', 'critical' ), true ) ) {
						$edminboost_anm_type_class = 'is-type-critical';
					}
					$edminboost_anm_excerpt = wp_trim_words( wp_strip_all_tags( $edminboost_anm_message ), 18, '…' );
					if ( '' === $edminboost_anm_excerpt && '' !== trim( $edminboost_anm_html ) ) {
						$edminboost_anm_excerpt = wp_trim_words( wp_strip_all_tags( $edminboost_anm_html ), 18, '…' );
					}
					?>
					<li class="edminboost-anm-notices-panel__item <?php echo esc_attr( $edminboost_anm_type_class ); ?>" data-notice-id="<?php echo esc_attr( $edminboost_anm_id ); ?>">
						<span class="edminboost-anm-notices-panel__type"><?php echo esc_html( $edminboost_anm_type ); ?></span>
						<span class="edminboost-anm-notices-panel__message"><?php echo esc_html( $edminboost_anm_excerpt ); ?></span>
						<span class="edminboost-anm-notices-panel__meta">
							<?php if ( $edminboost_anm_source ) : ?>
								<span class="edminboost-anm-notices-panel__source"><?php echo esc_html( $edminboost_anm_source ); ?></span>
							<?php endif; ?>
							<?php if ( $edminboost_anm_logged ) : ?>
								<span class="edminboost-anm-notices-panel__time"><?php echo esc_html( $edminboost_anm_logged ); ?></span>
							<?php endif; ?>
						</span>
					</li>
					<?php
					continue;
				endif;

				if ( '' === trim( $edminboost_anm_html ) ) {
					$edminboost_anm_html = sprintf(
						'<div class="%1$s"><p>%2$s</p></div>',
						esc_attr( EDMINBOOST_ANM_Inbox::get_panel_notice_class( $edminboost_anm_type ) ),
						esc_html( $edminboost_anm_message )
					);
				}
				?>
				<li class="edminboost-anm-notices-panel__card" data-notice-id="<?php echo esc_attr( $edminboost_anm_id ); ?>">
					<div class="edminboost-anm-notices-panel__notice-wrap">
						<button type="button" class="edminboost-anm-notices-panel__dismiss" data-action="panel_dismiss" data-id="<?php echo esc_attr( $edminboost_anm_id ); ?>" aria-label="<?php esc_attr_e( 'Dismiss from panel', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>">&times;</button>
						<div class="edminboost-anm-notices-panel__notice-body">
							<?php echo wp_kses_post( $edminboost_anm_html ); ?>
						</div>
					</div>
					<div class="edminboost-anm-notices-panel__options">
						<?php if ( $edminboost_anm_logged ) : ?>
							<span class="edminboost-anm-notices-panel__logged">
								<?php
								printf(
									/* translators: %s: formatted date/time */
									esc_html__( 'First logged: %s', EDMINBOOST_ANM_TEXT_DOMAIN ),
									esc_html( $edminboost_anm_logged )
								);
								?>
							</span>
						<?php endif; ?>
						<span class="edminboost-anm-notices-panel__actions">
							<button type="button" class="edminboost-anm-notices-panel__action" data-action="display_normal" data-id="<?php echo esc_attr( $edminboost_anm_id ); ?>">
								<?php esc_html_e( 'Display notice as normal', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
							</button>
							<button type="button" class="edminboost-anm-notices-panel__action edminboost-anm-notices-panel__action--hide" data-action="hide_for_me" data-id="<?php echo esc_attr( $edminboost_anm_id ); ?>">
								<span class="dashicons dashicons-hidden" aria-hidden="true"></span>
								<span class="edminboost-anm-notices-panel__action-label"><?php esc_html_e( 'Hide for me', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></span>
							</button>
							<button type="button" class="edminboost-anm-notices-panel__action edminboost-anm-notices-panel__action--hide" data-action="hide_for_all" data-id="<?php echo esc_attr( $edminboost_anm_id ); ?>">
								<span class="dashicons dashicons-hidden" aria-hidden="true"></span>
								<span class="edminboost-anm-notices-panel__action-label"><?php esc_html_e( 'Hide for all', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></span>
							</button>
						</span>
					</div>
				</li>
			<?php endforeach; ?>
		<?php endif; ?>
	</ul>
	<div class="edminboost-anm-notices-panel__footer">
		<a href="<?php echo esc_url( $edminboost_anm_inbox_url ); ?>"><?php esc_html_e( 'See all notifications', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></a>
	</div>
</div>

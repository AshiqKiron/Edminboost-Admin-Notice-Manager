<?php
/**
 * Admin bar notification dropdown panel markup.
 *
 * @package EdminBoost\AdminNoticeManager
 *
 * @var array  $edminboost_anm_notices   Recent inbox notices.
 * @var string $edminboost_anm_inbox_url Inbox admin URL.
 * @var int    $edminboost_anm_total     Total active inbox count.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="edminboost-anm-notices-panel" id="edminboost-anm-notices-panel" role="region" aria-label="<?php esc_attr_e( 'Notice inbox preview', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>" hidden>
	<div class="edminboost-anm-notices-panel__header">
		<strong><?php esc_html_e( 'Notices', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></strong>
		<?php if ( $edminboost_anm_total > 0 ) : ?>
			<span class="edminboost-anm-notices-panel__count"><?php echo esc_html( (string) $edminboost_anm_total ); ?></span>
		<?php endif; ?>
	</div>
	<ul class="edminboost-anm-notices-panel__list">
		<?php if ( empty( $edminboost_anm_notices ) ) : ?>
			<li class="edminboost-anm-notices-panel__empty">
				<?php esc_html_e( 'No intercepted notices yet. Browse wp-admin to capture nags here.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
			</li>
		<?php else : ?>
			<?php foreach ( $edminboost_anm_notices as $edminboost_anm_notice ) : ?>
				<?php
				$edminboost_anm_type   = sanitize_key( $edminboost_anm_notice['type'] ?? 'info' );
				$edminboost_anm_source = $edminboost_anm_notice['source_plugin'] ?? '';
				$edminboost_anm_msg    = wp_trim_words( wp_strip_all_tags( $edminboost_anm_notice['message'] ?? '' ), 18, '…' );
				$edminboost_anm_when   = (int) ( $edminboost_anm_notice['intercepted_at'] ?? 0 );
				$edminboost_anm_time   = $edminboost_anm_when > 0
					? sprintf(
						/* translators: %s: human-readable time difference */
						__( '%s ago', EDMINBOOST_ANM_TEXT_DOMAIN ),
						human_time_diff( $edminboost_anm_when, time() )
					)
					: '';
				?>
				<li class="edminboost-anm-notices-panel__item is-type-<?php echo esc_attr( $edminboost_anm_type ); ?>">
					<span class="edminboost-anm-notices-panel__type"><?php echo esc_html( ucfirst( $edminboost_anm_type ) ); ?></span>
					<span class="edminboost-anm-notices-panel__message"><?php echo esc_html( $edminboost_anm_msg ); ?></span>
					<span class="edminboost-anm-notices-panel__meta">
						<?php echo esc_html( $edminboost_anm_source ); ?>
						<?php if ( $edminboost_anm_time ) : ?>
							<span class="edminboost-anm-notices-panel__time"><?php echo esc_html( $edminboost_anm_time ); ?></span>
						<?php endif; ?>
					</span>
				</li>
			<?php endforeach; ?>
		<?php endif; ?>
	</ul>
	<div class="edminboost-anm-notices-panel__footer">
		<a href="<?php echo esc_url( $edminboost_anm_inbox_url ); ?>"><?php esc_html_e( 'See all in Notice Inbox', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></a>
	</div>
</div>

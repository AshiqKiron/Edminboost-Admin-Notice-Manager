<?php
/**
 * Facebook-style admin bar notification dropdown.
 *
 * @package EdminBoost\AdminNoticeManager
 *
 * @var array  $edminboost_anm_notices   Recent inbox notices.
 * @var string $edminboost_anm_inbox_url    Inbox admin URL.
 * @var string $edminboost_anm_settings_url Settings admin URL (Admin bar tab).
 * @var int    $edminboost_anm_total        Total active inbox count.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edminboost_anm_fb_threshold = time() - DAY_IN_SECONDS;
$edminboost_anm_fb_new       = array();
$edminboost_anm_fb_earlier   = array();

foreach ( $edminboost_anm_notices as $edminboost_anm_notice ) {
	$edminboost_anm_ts = (int) ( $edminboost_anm_notice['intercepted_at'] ?? 0 );
	if ( $edminboost_anm_ts >= $edminboost_anm_fb_threshold ) {
		$edminboost_anm_fb_new[] = $edminboost_anm_notice;
	} else {
		$edminboost_anm_fb_earlier[] = $edminboost_anm_notice;
	}
}

/**
 * Render one Facebook-style notification row.
 *
 * @param array $notice Inbox notice row.
 * @return void
 */
$edminboost_anm_render_fb_row = static function ( array $notice ) {
	$edminboost_anm_id       = $notice['id'] ?? '';
	$edminboost_anm_when     = (int) ( $notice['intercepted_at'] ?? 0 );
	$edminboost_anm_message  = $notice['message'] ?? '';
	$edminboost_anm_source   = $notice['source_plugin'] ?? $notice['source'] ?? '';
	$edminboost_anm_is_read  = EDMINBOOST_ANM_Inbox::is_marked_read( $notice );
	$edminboost_anm_excerpt  = EDMINBOOST_ANM_Inbox::get_facebook_panel_message_excerpt(
		$edminboost_anm_message,
		$notice['html'] ?? ''
	);
	$edminboost_anm_label    = trim( (string) $edminboost_anm_source );
	if ( '' !== $edminboost_anm_label && false !== strpos( $edminboost_anm_label, ' - ' ) ) {
		$edminboost_anm_label = trim( substr( $edminboost_anm_label, 0, (int) strpos( $edminboost_anm_label, ' - ' ) ) );
	}
	if ( strlen( $edminboost_anm_label ) > 28 ) {
		$edminboost_anm_label = substr( $edminboost_anm_label, 0, 26 ) . '…';
	}
	$edminboost_anm_relative = EDMINBOOST_ANM_Inbox::format_panel_relative_time( $edminboost_anm_when );
	$edminboost_anm_item_cls = 'edminboost-anm-fb-notif__item';
	if ( $edminboost_anm_is_read ) {
		$edminboost_anm_item_cls .= ' is-read';
	}
	?>
	<li
		class="<?php echo esc_attr( $edminboost_anm_item_cls ); ?>"
		data-notice-id="<?php echo esc_attr( $edminboost_anm_id ); ?>"
	>
		<div class="edminboost-anm-fb-notif__body">
			<p class="edminboost-anm-fb-notif__text">
				<?php if ( $edminboost_anm_label ) : ?>
					<strong class="edminboost-anm-fb-notif__source"><?php echo esc_html( $edminboost_anm_label ); ?></strong>
					<span class="edminboost-anm-fb-notif__message"> <?php echo esc_html( $edminboost_anm_excerpt ); ?></span>
				<?php else : ?>
					<span class="edminboost-anm-fb-notif__message"><?php echo esc_html( $edminboost_anm_excerpt ); ?></span>
				<?php endif; ?>
			</p>
			<?php if ( $edminboost_anm_relative ) : ?>
				<span class="edminboost-anm-fb-notif__time"><?php echo esc_html( $edminboost_anm_relative ); ?></span>
			<?php endif; ?>
		</div>
		<?php if ( ! $edminboost_anm_is_read ) : ?>
			<span class="edminboost-anm-fb-notif__unread-dot" aria-hidden="true"></span>
		<?php endif; ?>
	</li>
	<?php
};
?>
<div class="edminboost-anm-notices-panel edminboost-anm-notices-panel--facebook" id="edminboost-anm-notices-panel" role="region" aria-label="<?php esc_attr_e( 'Notifications', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>" hidden>
	<div class="edminboost-anm-fb-notif__header">
		<h2 class="edminboost-anm-fb-notif__title"><?php esc_html_e( 'Notifications', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
		<div class="edminboost-anm-fb-notif__menu">
			<button
				type="button"
				class="edminboost-anm-fb-notif__menu-toggle"
				aria-label="<?php esc_attr_e( 'Notification options', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>"
				aria-haspopup="menu"
				aria-expanded="false"
				aria-controls="edminboost-anm-fb-notif-menu"
			>
				<span class="dashicons dashicons-ellipsis" aria-hidden="true"></span>
			</button>
			<div
				class="edminboost-anm-fb-notif__menu-popover"
				id="edminboost-anm-fb-notif-menu"
				role="menu"
				aria-label="<?php esc_attr_e( 'Notification options', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>"
				hidden
			>
				<button type="button" class="edminboost-anm-fb-notif__menu-item" role="menuitem" data-fb-menu-action="mark-all-read">
					<span class="dashicons dashicons-yes" aria-hidden="true"></span>
					<?php esc_html_e( 'Mark all as read', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
				</button>
				<a class="edminboost-anm-fb-notif__menu-item" role="menuitem" href="<?php echo esc_url( $edminboost_anm_settings_url ?? $edminboost_anm_inbox_url ); ?>">
					<span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
					<?php esc_html_e( 'Notification settings', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
				</a>
			</div>
		</div>
	</div>
	<div class="edminboost-anm-fb-notif__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Filter notifications', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>">
		<button type="button" class="edminboost-anm-fb-notif__tab is-active" role="tab" aria-selected="true" data-fb-filter="all"><?php esc_html_e( 'All', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
		<button type="button" class="edminboost-anm-fb-notif__tab" role="tab" aria-selected="false" data-fb-filter="unread"><?php esc_html_e( 'Unread', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
	</div>
	<div class="edminboost-anm-fb-notif__scroll">
		<?php if ( empty( $edminboost_anm_notices ) ) : ?>
			<p class="edminboost-anm-fb-notif__empty"><?php esc_html_e( 'No intercepted notices yet. Browse wp-admin to capture nags here.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
		<?php else : ?>
			<div class="edminboost-anm-fb-notif__section">
				<div class="edminboost-anm-fb-notif__section-head">
					<h3 class="edminboost-anm-fb-notif__section-title"><?php esc_html_e( 'New', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h3>
					<a class="edminboost-anm-fb-notif__see-all" href="<?php echo esc_url( $edminboost_anm_inbox_url ); ?>"><?php esc_html_e( 'See all', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></a>
				</div>
				<?php if ( ! empty( $edminboost_anm_fb_new ) ) : ?>
					<ul class="edminboost-anm-fb-notif__list">
						<?php foreach ( $edminboost_anm_fb_new as $edminboost_anm_notice ) : ?>
							<?php $edminboost_anm_render_fb_row( $edminboost_anm_notice ); ?>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p class="edminboost-anm-fb-notif__empty edminboost-anm-fb-notif__empty--inline"><?php esc_html_e( 'You have caught up on new notifications.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( ! empty( $edminboost_anm_fb_earlier ) ) : ?>
				<div class="edminboost-anm-fb-notif__section">
					<h3 class="edminboost-anm-fb-notif__section-title edminboost-anm-fb-notif__section-title--solo"><?php esc_html_e( 'Earlier', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h3>
					<ul class="edminboost-anm-fb-notif__list">
						<?php foreach ( $edminboost_anm_fb_earlier as $edminboost_anm_notice ) : ?>
							<?php $edminboost_anm_render_fb_row( $edminboost_anm_notice ); ?>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<div class="edminboost-anm-fb-notif__footer">
		<a class="edminboost-anm-fb-notif__footer-link" href="<?php echo esc_url( $edminboost_anm_inbox_url ); ?>"><?php esc_html_e( 'See previous notifications', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></a>
	</div>
</div>

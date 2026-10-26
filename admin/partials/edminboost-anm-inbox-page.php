<?php
/**
 * Notice Inbox page template.
 *
 * @package EdminBoost\AdminNoticeManager
 *
 * @var array  $settings Plugin settings.
 * @var array  $notices  Paginated inbox notices.
 * @var array  $counts   Filter tab counts.
 * @var string $filter   Active filter slug.
 * @var string $search   Search query.
 * @var int    $total    Total matching notices.
 * @var int    $page     Current page.
 * @var int    $per_page Items per page.
 * @var string $orderby  Active sort column.
 * @var string $order    Active sort direction (asc|desc).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'edminboost_anm_render_inbox_sortable_th' ) ) {
	/**
	 * Render a sortable inbox table column header.
	 *
	 * @param string $column   Sort key.
	 * @param string $label    Column label.
	 * @param string $orderby  Current orderby.
	 * @param string $order    Current order.
	 * @param string $base_url Base inbox URL.
	 * @param string $filter   Active filter.
	 * @param string $search   Search query.
	 * @return void
	 */
	function edminboost_anm_render_inbox_sortable_th( $column, $label, $orderby, $order, $base_url, $filter, $search ) {
	$is_active  = $orderby === $column;
	$next_order = ( $is_active && 'asc' === $order ) ? 'desc' : 'asc';
	$classes    = 'manage-column column-' . sanitize_key( $column ) . ' sortable';
	if ( $is_active ) {
		$classes .= ' sorted ' . sanitize_html_class( $order );
	}

	$url_args = array(
		'orderby' => $column,
		'order'   => $next_order,
		's'       => $search,
	);
	if ( 'all' !== $filter ) {
		$url_args['filter'] = $filter;
	}

	$sort_url = add_query_arg( $url_args, $base_url );
	?>
	<th scope="col" class="<?php echo esc_attr( $classes ); ?>" aria-sort="<?php echo $is_active ? esc_attr( 'asc' === $order ? 'ascending' : 'descending' ) : esc_attr( 'none' ); ?>">
		<a href="<?php echo esc_url( $sort_url ); ?>">
			<span><?php echo esc_html( $label ); ?></span>
			<span class="sorting-indicators">
				<span class="sorting-indicator asc" aria-hidden="true"></span>
				<span class="sorting-indicator desc" aria-hidden="true"></span>
			</span>
		</a>
	</th>
	<?php
	}
}

$edminboost_anm_base_url = admin_url( 'admin.php?page=' . EDMINBOOST_ANM_Admin::PAGE_SLUG . '-inbox' );
$edminboost_anm_start    = $total > 0 ? ( ( $page - 1 ) * $per_page ) + 1 : 0;
$edminboost_anm_end      = min( $page * $per_page, $total );

$edminboost_anm_tabs = array(
	'all'      => __( 'All', EDMINBOOST_ANM_TEXT_DOMAIN ),
	'critical' => __( 'Critical', EDMINBOOST_ANM_TEXT_DOMAIN ),
	'snoozed'  => __( 'Snoozed', EDMINBOOST_ANM_TEXT_DOMAIN ),
	'upsells'  => __( 'Upsells', EDMINBOOST_ANM_TEXT_DOMAIN ),
);
?>
<div class="wrap edminboost-anm-wrap edminboost-anm-wrap--wide" data-edminboost-anm-inbox>
	<header class="edminboost-anm-page-header">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form class="edminboost-anm-search" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
			<input type="hidden" name="page" value="<?php echo esc_attr( EDMINBOOST_ANM_Admin::PAGE_SLUG . '-inbox' ); ?>" />
			<?php if ( 'all' !== $filter ) : ?>
				<input type="hidden" name="filter" value="<?php echo esc_attr( $filter ); ?>" />
			<?php endif; ?>
			<?php if ( 'date' !== $orderby || 'desc' !== $order ) : ?>
				<input type="hidden" name="orderby" value="<?php echo esc_attr( $orderby ); ?>" />
				<input type="hidden" name="order" value="<?php echo esc_attr( $order ); ?>" />
			<?php endif; ?>
			<label class="screen-reader-text" for="edminboost-anm-inbox-search"><?php esc_html_e( 'Search notices', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
			<input type="search" id="edminboost-anm-inbox-search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search notices…', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>" />
			<button type="submit" class="button"><span class="dashicons dashicons-search" aria-hidden="true"></span><span class="screen-reader-text"><?php esc_html_e( 'Search', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></span></button>
		</form>
	</header>

	<nav class="edminboost-anm-tabs" aria-label="<?php esc_attr_e( 'Inbox filters', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>">
		<?php foreach ( $edminboost_anm_tabs as $tab_slug => $tab_label ) : ?>
			<?php
			$tab_url = add_query_arg(
				array(
					'filter'  => $tab_slug,
					's'       => $search,
					'orderby' => $orderby,
					'order'   => $order,
				),
				$edminboost_anm_base_url
			);
			$is_active = $filter === $tab_slug || ( 'all' === $tab_slug && 'all' === $filter );
			?>
			<a class="edminboost-anm-tabs__tab <?php echo $is_active ? 'is-active' : ''; ?>" href="<?php echo esc_url( $tab_url ); ?>">
				<?php echo esc_html( $tab_label ); ?>
				<span class="edminboost-anm-tabs__count">(<?php echo esc_html( $counts[ $tab_slug ] ?? 0 ); ?>)</span>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="edminboost-anm-card edminboost-anm-card--flush">
		<?php if ( ! empty( $notices ) ) : ?>
			<table class="edminboost-anm-table widefat">
				<thead>
					<tr>
						<td class="check-column"><input type="checkbox" id="edminboost-anm-select-all" aria-label="<?php esc_attr_e( 'Select all', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>" /></td>
						<?php
						edminboost_anm_render_inbox_sortable_th( 'source', __( 'Source Plugin', EDMINBOOST_ANM_TEXT_DOMAIN ), $orderby, $order, $edminboost_anm_base_url, $filter, $search );
						edminboost_anm_render_inbox_sortable_th( 'message', __( 'Notice Message Preview', EDMINBOOST_ANM_TEXT_DOMAIN ), $orderby, $order, $edminboost_anm_base_url, $filter, $search );
						edminboost_anm_render_inbox_sortable_th( 'type', __( 'Type', EDMINBOOST_ANM_TEXT_DOMAIN ), $orderby, $order, $edminboost_anm_base_url, $filter, $search );
						edminboost_anm_render_inbox_sortable_th( 'status', __( 'Actions', EDMINBOOST_ANM_TEXT_DOMAIN ), $orderby, $order, $edminboost_anm_base_url, $filter, $search );
						?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $notices as $notice ) : ?>
						<?php
						$edminboost_anm_is_converted = EDMINBOOST_ANM_Inbox::is_converted_to_rule( $notice );
						?>
						<tr data-notice-id="<?php echo esc_attr( $notice['id'] ); ?>" class="<?php echo $edminboost_anm_is_converted ? 'is-converted-to-rule' : ''; ?>">
							<th scope="row" class="check-column">
								<input type="checkbox" class="edminboost-anm-notice-check" value="<?php echo esc_attr( $notice['id'] ); ?>" aria-label="<?php esc_attr_e( 'Select notice', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>" />
							</th>
							<td><?php echo esc_html( $notice['source_plugin'] ?? '' ); ?></td>
							<td>
								<?php echo esc_html( wp_html_excerpt( $notice['message'] ?? '', 60, '…' ) ); ?>
								<?php if ( $edminboost_anm_is_converted ) : ?>
									<br /><span class="edminboost-anm-badge edminboost-anm-badge--converted edminboost-anm-inbox-converted-badge">
										<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
										<?php esc_html_e( 'Converted to rule', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
									</span>
								<?php endif; ?>
							</td>
							<td><span class="edminboost-anm-badge edminboost-anm-badge--<?php echo esc_attr( $notice['type'] ?? 'info' ); ?>"><?php echo esc_html( EDMINBOOST_ANM_Inbox::get_type_label( $notice['type'] ?? 'info' ) ); ?></span></td>
							<td class="edminboost-anm-actions">
								<button type="button" class="button button-small edminboost-anm-inbox-read" data-id="<?php echo esc_attr( $notice['id'] ); ?>"><?php esc_html_e( 'Read', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
								<div class="edminboost-anm-dropdown">
									<button type="button" class="button button-small edminboost-anm-dropdown__toggle" aria-expanded="false"><?php esc_html_e( 'Snooze', EDMINBOOST_ANM_TEXT_DOMAIN ); ?> ▾</button>
									<div class="edminboost-anm-dropdown__menu">
										<button type="button" class="edminboost-anm-inbox-snooze" data-id="<?php echo esc_attr( $notice['id'] ); ?>" data-duration="1d"><?php esc_html_e( '1 Day', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
										<button type="button" class="edminboost-anm-inbox-snooze" data-id="<?php echo esc_attr( $notice['id'] ); ?>" data-duration="7d"><?php esc_html_e( '7 Days', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
									</div>
								</div>
								<button type="button" class="button button-small edminboost-anm-inbox-convert" data-id="<?php echo esc_attr( $notice['id'] ); ?>" <?php disabled( $edminboost_anm_is_converted ); ?>><?php esc_html_e( 'Convert to Rule', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
				<tfoot>
					<tr>
						<td class="check-column"><input type="checkbox" aria-label="<?php esc_attr_e( 'Select all', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>" /></td>
						<?php
						edminboost_anm_render_inbox_sortable_th( 'source', __( 'Source Plugin', EDMINBOOST_ANM_TEXT_DOMAIN ), $orderby, $order, $edminboost_anm_base_url, $filter, $search );
						edminboost_anm_render_inbox_sortable_th( 'message', __( 'Notice Message Preview', EDMINBOOST_ANM_TEXT_DOMAIN ), $orderby, $order, $edminboost_anm_base_url, $filter, $search );
						edminboost_anm_render_inbox_sortable_th( 'type', __( 'Type', EDMINBOOST_ANM_TEXT_DOMAIN ), $orderby, $order, $edminboost_anm_base_url, $filter, $search );
						edminboost_anm_render_inbox_sortable_th( 'status', __( 'Actions', EDMINBOOST_ANM_TEXT_DOMAIN ), $orderby, $order, $edminboost_anm_base_url, $filter, $search );
						?>
					</tr>
				</tfoot>
			</table>
		<?php else : ?>
			<div class="edminboost-anm-empty">
				<p><?php esc_html_e( 'No notices in the inbox yet. Intercepted notices will appear here.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
			</div>
		<?php endif; ?>
	</div>

	<div class="edminboost-anm-table-footer">
		<div class="edminboost-anm-bulk-actions">
			<label for="edminboost-anm-bulk-action" class="screen-reader-text"><?php esc_html_e( 'Bulk actions', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
			<select id="edminboost-anm-bulk-action">
				<option value=""><?php esc_html_e( 'Bulk Actions', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></option>
				<option value="read"><?php esc_html_e( 'Mark as Read', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></option>
				<option value="snooze"><?php esc_html_e( 'Snooze', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></option>
				<option value="archive"><?php esc_html_e( 'Archive', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></option>
			</select>
			<button type="button" class="button edminboost-anm-bulk-apply"><?php esc_html_e( 'Apply', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
		</div>
		<p class="edminboost-anm-pagination">
			<?php
			echo esc_html(
				sprintf(
					/* translators: 1: start number, 2: end number, 3: total count */
					__( 'Showing %1$d–%2$d of %3$d notices', EDMINBOOST_ANM_TEXT_DOMAIN ),
					$edminboost_anm_start,
					$edminboost_anm_end,
					$total
				)
			);
			?>
		</p>
	</div>
</div>

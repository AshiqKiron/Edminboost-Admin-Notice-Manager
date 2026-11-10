/**
 * Admin Notice Manager — plugin admin scripts.
 *
 * @package EdminBoost\AdminNoticeManager
 */
(function () {
	'use strict';

	var data = window.edminboostAnmData || {};

	function withRulesContext(payload) {
		var next = payload || {};
		if (data.rulesContext && data.rulesContext !== 'site') {
			next.context = data.rulesContext;
		}
		return next;
	}

	function apiPost(action, payload) {
		var body = new FormData();
		body.append('action', action);
		body.append('nonce', data.nonce || '');

		if (payload) {
			Object.keys(payload).forEach(function (key) {
				var value = payload[key];
				if (Array.isArray(value)) {
					value.forEach(function (item) {
						body.append(key + '[]', item);
					});
				} else {
					body.append(key, value);
				}
			});
		}

		return fetch(data.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		}).then(function (response) {
			return response.json();
		});
	}

	var anmConfirmModalState = {
		previousFocus: null,
		onConfirm: null,
		openOptions: null,
		pausedModals: []
	};
	var anmConfirmRoot = null;
	var anmConfirmRootEl = null;

	function getWpConfirmDialog() {
		return window.wp && wp.components && wp.components.__experimentalConfirmDialog
			? wp.components.__experimentalConfirmDialog
			: null;
	}

	function ensureAnmConfirmRoot() {
		if (anmConfirmRootEl) {
			return;
		}

		anmConfirmRootEl = document.createElement('div');
		anmConfirmRootEl.id = 'edminboost-anm-wp-confirm-root';
		document.body.appendChild(anmConfirmRootEl);

		if (wp.element && typeof wp.element.createRoot === 'function') {
			anmConfirmRoot = wp.element.createRoot(anmConfirmRootEl);
		}
	}

	function renderAnmConfirmDialog(props) {
		var ConfirmDialog = getWpConfirmDialog();
		if (!ConfirmDialog || !wp.element || typeof wp.element.createElement !== 'function') {
			return false;
		}

		ensureAnmConfirmRoot();
		var element = wp.element.createElement(ConfirmDialog, props);
		if (anmConfirmRoot) {
			anmConfirmRoot.render(element);
		} else if (typeof wp.element.render === 'function') {
			wp.element.render(element, anmConfirmRootEl);
		} else {
			return false;
		}

		return true;
	}

	function unmountAnmConfirmDialog() {
		if (!anmConfirmRootEl) {
			return;
		}

		if (anmConfirmRoot) {
			anmConfirmRoot.render(null);
		} else if (wp.element && typeof wp.element.render === 'function') {
			wp.element.render(null, anmConfirmRootEl);
		}
	}

	function pauseAnmModals() {
		anmConfirmModalState.pausedModals = [];
		document.querySelectorAll('.edminboost-anm-modal.is-open').forEach(function (node) {
			anmConfirmModalState.pausedModals.push(node);
			node.classList.remove('is-open');
			node.setAttribute('aria-hidden', 'true');
		});
	}

	function resumeAnmModals() {
		anmConfirmModalState.pausedModals.forEach(function (node) {
			node.classList.add('is-open');
			node.setAttribute('aria-hidden', 'false');
		});
		anmConfirmModalState.pausedModals = [];
	}

	function finishAnmConfirmModal(resumeModals) {
		unmountAnmConfirmDialog();

		if (resumeModals !== false) {
			resumeAnmModals();
		} else {
			anmConfirmModalState.pausedModals = [];
		}

		if (anmConfirmModalState.previousFocus && typeof anmConfirmModalState.previousFocus.focus === 'function') {
			anmConfirmModalState.previousFocus.focus();
		}

		anmConfirmModalState.previousFocus = null;
		anmConfirmModalState.onConfirm = null;
		anmConfirmModalState.openOptions = null;
	}

	function buildAnmConfirmDialogProps(isBusy) {
		var i18n = data.i18n || {};
		var options = anmConfirmModalState.openOptions || {};
		var createElement = wp.element.createElement;
		var message = options.message || '';
		var children = message;

		if (options.title && options.title !== message) {
			children = createElement(
				wp.element.Fragment,
				null,
				createElement('strong', null, options.title),
				createElement('br', null),
				createElement('br', null),
				message
			);
		}

		return {
			isOpen: true,
			onCancel: function () {
				finishAnmConfirmModal(true);
			},
			onConfirm: function () {
				if (!anmConfirmModalState.onConfirm) {
					finishAnmConfirmModal(true);
					return;
				}

				var result;
				try {
					result = anmConfirmModalState.onConfirm();
				} catch (err) {
					window.alert(err.message || i18n.actionError || 'Action failed.');
					return;
				}

				if (result && typeof result.then === 'function') {
					renderAnmConfirmDialog(buildAnmConfirmDialogProps(true));
					result.then(function () {
						finishAnmConfirmModal(false);
					}).catch(function (err) {
						renderAnmConfirmDialog(buildAnmConfirmDialogProps(false));
						window.alert(err.message || i18n.actionError || 'Action failed.');
					});
					return;
				}

				finishAnmConfirmModal(false);
			},
			confirmButtonText: options.confirmText || i18n.confirmDeleteButton || 'Delete',
			cancelButtonText: i18n.cancel || 'Cancel',
			isBusy: !!isBusy,
			children: children
		};
	}

	function openAnmConfirmModal(options) {
		options = options || {};
		var i18n = data.i18n || {};

		function runLegacyConfirm() {
			if (window.confirm(options.message || i18n.confirmDelete || 'Are you sure?')) {
				if (typeof options.onConfirm === 'function') {
					options.onConfirm();
				}
			}
		}

		if (!getWpConfirmDialog()) {
			runLegacyConfirm();
			return;
		}

		pauseAnmModals();
		anmConfirmModalState.previousFocus = document.activeElement;
		anmConfirmModalState.onConfirm = typeof options.onConfirm === 'function' ? options.onConfirm : null;
		anmConfirmModalState.openOptions = options;

		if (!renderAnmConfirmDialog(buildAnmConfirmDialogProps(false))) {
			resumeAnmModals();
			anmConfirmModalState.onConfirm = null;
			anmConfirmModalState.openOptions = null;
			anmConfirmModalState.previousFocus = null;
			runLegacyConfirm();
		}
	}

	function initAnmConfirmModal() {
		ensureAnmConfirmRoot();
	}

	function anmPreviewEscapeHtml(text) {
		if (text === null || text === undefined) {
			return '';
		}
		var div = document.createElement('div');
		div.textContent = String(text);
		return div.innerHTML;
	}

	function anmPreviewStyleUsesBell(style, position) {
		if (style === 'bell_link' || style === 'dropdown' || style === 'dropdown_compact') {
			return true;
		}
		return style === 'link' && position === 'before_profile';
	}

	function anmPreviewIsDropdownStyle(style) {
		return style === 'dropdown' || style === 'dropdown_compact';
	}

	function anmPreviewGetPositionLayout(position) {
		var layouts = data.noticePanelPositionLayouts || {};
		return layouts[position] || 'right';
	}

	function anmReadNoticePanelPreviewOptions() {
		var styleSelect = document.getElementById('edminboost-anm-notice-panel-style');
		var badgeSelect = document.getElementById('edminboost-anm-notice-panel-badge');
		var positionSelect = document.getElementById('edminboost-anm-notice-panel-position');
		var colorFields = {
			badge: document.getElementById('edminboost-anm-notice-color-badge'),
			badge_text: document.getElementById('edminboost-anm-notice-color-badge-text'),
			accent: document.getElementById('edminboost-anm-notice-color-accent'),
			accent_hover: document.getElementById('edminboost-anm-notice-color-accent-hover'),
			bell_active: document.getElementById('edminboost-anm-notice-color-bell-active')
		};

		return {
			style: styleSelect ? styleSelect.value : 'dropdown_compact',
			badgeStyle: badgeSelect ? badgeSelect.value : 'count',
			position: positionSelect ? positionSelect.value : 'default',
			colors: {
				badge: (colorFields.badge && colorFields.badge.value) || '#d63638',
				badge_text: (colorFields.badge_text && colorFields.badge_text.value) || '#ffffff',
				accent: (colorFields.accent && colorFields.accent.value) || '#2271b1',
				accent_hover: (colorFields.accent_hover && colorFields.accent_hover.value) || '#135e96',
				bell_active: (colorFields.bell_active && colorFields.bell_active.value) || '#72aee6'
			}
		};
	}

	function anmApplyNoticePanelPreviewColors(root, colors) {
		if (!root) {
			return;
		}
		root.style.setProperty('--edminboost-anm-notice-badge-bg', colors.badge);
		root.style.setProperty('--edminboost-anm-notice-badge-text', colors.badge_text);
		root.style.setProperty('--edminboost-anm-notice-accent', colors.accent);
		root.style.setProperty('--edminboost-anm-notice-accent-hover', colors.accent_hover);
		root.style.setProperty('--edminboost-anm-notice-bell-active', colors.bell_active);
	}

	function anmPreviewFormatBadgeCount(count, badgeStyle) {
		if (badgeStyle === 'count_9plus' && count > 9) {
			return '9+';
		}
		if (badgeStyle === 'count_99plus' && count > 99) {
			return '99+';
		}
		return String(count);
	}

	function anmPreviewBuildBadgeHtml(badgeStyle, count) {
		if (count <= 0 || badgeStyle === 'none') {
			return '';
		}
		if (badgeStyle === 'dot' || badgeStyle === 'dot_pulse') {
			var dotClass = 'edminboost-anm-notices-badge edminboost-anm-notices-badge--dot';
			if (badgeStyle === 'dot_pulse') {
				dotClass += ' edminboost-anm-notices-badge--pulse';
			}
			return '<span class="' + dotClass + '"><span class="screen-reader-text">' + anmPreviewEscapeHtml(String(count)) + '</span></span>';
		}
		var display = anmPreviewFormatBadgeCount(count, badgeStyle);
		return '<span class="edminboost-anm-notices-badge"><span class="screen-reader-text">' + anmPreviewEscapeHtml(String(count)) + '</span><span aria-hidden="true">' + anmPreviewEscapeHtml(display) + '</span></span>';
	}

	function anmPreviewBuildPanelHeaderHtml(i18n) {
		return '<div class="edminboost-anm-notices-panel__header"><strong>'
			+ anmPreviewEscapeHtml(i18n.noticePanelPreviewAdminNotices || 'Admin notices')
			+ '</strong><button type="button" class="edminboost-anm-notices-panel__close" aria-label="'
			+ anmPreviewEscapeHtml(i18n.noticePanelPreviewClose || 'Close panel')
			+ '" tabindex="-1">&times;</button></div>';
	}

	function anmPreviewBuildPanelFooterHtml(i18n) {
		return '<div class="edminboost-anm-notices-panel__footer"><a href="#" tabindex="-1" onclick="return false;">'
			+ anmPreviewEscapeHtml(i18n.noticePanelPreviewSeeAll || 'See all notifications')
			+ '</a></div>';
	}

	function anmPreviewBuildNoticePanelFromSamples(i18n, samples) {
		var html = '<div class="edminboost-anm-notices-panel edminboost-anm-notices-panel--compact edminboost-anm-notice-panel-preview__panel">';
		html += anmPreviewBuildPanelHeaderHtml(i18n);
		html += '<ul class="edminboost-anm-notices-panel__list">';
		samples.forEach(function (item) {
			var typeClass = item.type === 'error' || item.type === 'critical' ? 'is-type-critical' : 'is-type-' + item.type;
			html += '<li class="edminboost-anm-notices-panel__item ' + typeClass + '">';
			html += '<span class="edminboost-anm-notices-panel__type">' + anmPreviewEscapeHtml(item.type) + '</span>';
			html += '<span class="edminboost-anm-notices-panel__message">' + anmPreviewEscapeHtml(item.message) + '</span>';
			html += '<span class="edminboost-anm-notices-panel__meta"><span class="edminboost-anm-notices-panel__source">' + anmPreviewEscapeHtml(item.source) + '</span></span>';
			html += '</li>';
		});
		html += '</ul>';
		html += anmPreviewBuildPanelFooterHtml(i18n);
		html += '</div>';
		return html;
	}

	function anmPreviewBuildNonEcomCapturedPanel(i18n) {
		var samples = [
			{
				type: 'info',
				message: i18n.displayExperiencePreviewNoticeUpdate || 'WordPress 6.7 is available. Please update now.',
				source: i18n.displayExperiencePreviewInboxFeedSource || 'WordPress'
			},
			{
				type: 'warning',
				message: i18n.displayExperiencePreviewNoticeLicense || 'Please activate your SEO plugin license to receive updates.',
				source: i18n.displayExperiencePreviewNoticeLicenseSource || 'Yoast SEO'
			}
		];

		return anmPreviewBuildNoticePanelFromSamples(i18n, samples);
	}

	function anmPreviewBuildCompactPanel(i18n) {
		var samples = [
			{
				type: 'info',
				message: i18n.noticePanelSample1 || 'There is a new version of Akismet Anti-spam available. View version 5.3.3 details or update now.',
				source: i18n.noticePanelSample1Source || 'Akismet Anti-spam'
			},
			{
				type: 'upsell',
				message: i18n.noticePanelSample2 || 'Enjoying WPForms? Please leave us a review on WordPress.org — it only takes a minute.',
				source: i18n.noticePanelSample2Source || 'WPForms'
			},
			{
				type: 'warning',
				message: i18n.noticePanelSample3 || 'Your license key expires in 14 days. Renew now to keep receiving updates and support.',
				source: i18n.noticePanelSample3Source || 'Advanced Custom Fields'
			}
		];
		return anmPreviewBuildNoticePanelFromSamples(i18n, samples);
	}

	function anmPreviewBuildFacebookPanelHtml(i18n) {
		var items = [
			{
				source: i18n.displayExperiencePreviewFbSample1Source || 'WordPress',
				message: i18n.displayExperiencePreviewFbSample1 || 'WordPress 6.7 is available. Please update now.',
				time: '2h',
				icon: 'dashicons-admin-comments',
				unread: true
			},
			{
				source: i18n.displayExperiencePreviewFbSample2Source || 'Yoast SEO',
				message: i18n.displayExperiencePreviewFbSample2 || 'Please activate your SEO plugin license to receive updates.',
				time: '8h',
				icon: 'dashicons-warning',
				unread: true
			},
			{
				source: i18n.displayExperiencePreviewFbSample3Source || 'WPForms',
				message: i18n.displayExperiencePreviewFbSample3 || 'You have a new form submission waiting in your inbox.',
				time: '11h',
				icon: 'dashicons-megaphone',
				unread: false
			}
		];

		function row(item) {
			var unreadDot = item.unread
				? '<span class="edminboost-anm-fb-notif__unread-dot" aria-hidden="true"></span>'
				: '';
			return '<li class="edminboost-anm-fb-notif__item' + (item.unread ? '' : ' is-read') + '">'
				+ '<div class="edminboost-anm-fb-notif__body">'
				+ '<p class="edminboost-anm-fb-notif__text"><strong class="edminboost-anm-fb-notif__source">' + anmPreviewEscapeHtml(item.source) + '</strong>'
				+ '<span class="edminboost-anm-fb-notif__message"> ' + anmPreviewEscapeHtml(item.message) + '</span></p>'
				+ '<span class="edminboost-anm-fb-notif__time">' + anmPreviewEscapeHtml(item.time) + '</span>'
				+ '</div>'
				+ unreadDot
				+ '</li>';
		}

		var title = i18n.displayExperiencePreviewFbTitle || i18n.displayExperiencePreviewInboxFeedTitle || 'Notifications';
		var tabAll = i18n.displayExperiencePreviewFbTabAll || i18n.displayExperiencePreviewInboxFeedTabAll || 'All';
		var tabUnread = i18n.displayExperiencePreviewFbTabUnread || i18n.displayExperiencePreviewInboxFeedTabUnread || 'Unread';
		var sectionNew = i18n.displayExperiencePreviewFbSectionNew || 'New';
		var sectionEarlier = i18n.displayExperiencePreviewFbSectionEarlier || 'Earlier';
		var seeAll = i18n.displayExperiencePreviewFbSeeAll || 'See all';
		var footer = i18n.displayExperiencePreviewFbFooter || 'See previous notifications';

		return '<div class="edminboost-anm-notices-panel edminboost-anm-notices-panel--facebook edminboost-anm-notice-panel-preview__panel">'
			+ '<div class="edminboost-anm-fb-notif__header">'
			+ '<h2 class="edminboost-anm-fb-notif__title">' + anmPreviewEscapeHtml(title) + '</h2>'
			+ '<div class="edminboost-anm-fb-notif__menu">'
			+ '<button type="button" class="edminboost-anm-fb-notif__menu-toggle" tabindex="-1" aria-hidden="true"><span class="dashicons dashicons-ellipsis"></span></button>'
			+ '</div>'
			+ '</div>'
			+ '<div class="edminboost-anm-fb-notif__tabs" role="presentation">'
			+ '<span class="edminboost-anm-fb-notif__tab is-active" tabindex="-1">' + anmPreviewEscapeHtml(tabAll) + '</span>'
			+ '<span class="edminboost-anm-fb-notif__tab" tabindex="-1">' + anmPreviewEscapeHtml(tabUnread) + '</span>'
			+ '</div>'
			+ '<div class="edminboost-anm-fb-notif__scroll">'
			+ '<div class="edminboost-anm-fb-notif__section">'
			+ '<div class="edminboost-anm-fb-notif__section-head">'
			+ '<h3 class="edminboost-anm-fb-notif__section-title">' + anmPreviewEscapeHtml(sectionNew) + '</h3>'
			+ '<span class="edminboost-anm-fb-notif__see-all" tabindex="-1">' + anmPreviewEscapeHtml(seeAll) + '</span>'
			+ '</div>'
			+ '<ul class="edminboost-anm-fb-notif__list">' + row(items[0]) + row(items[1]) + '</ul>'
			+ '</div>'
			+ '<div class="edminboost-anm-fb-notif__section">'
			+ '<h3 class="edminboost-anm-fb-notif__section-title edminboost-anm-fb-notif__section-title--solo">' + anmPreviewEscapeHtml(sectionEarlier) + '</h3>'
			+ '<ul class="edminboost-anm-fb-notif__list">' + row(items[2]) + '</ul>'
			+ '</div>'
			+ '</div>'
			+ '<div class="edminboost-anm-fb-notif__footer">'
			+ '<span class="edminboost-anm-fb-notif__footer-link" tabindex="-1">' + anmPreviewEscapeHtml(footer) + '</span>'
			+ '</div>'
			+ '</div>';
	}

	function anmPreviewFacebookPanelOptions(panelOptions) {
		return {
			style: 'dropdown',
			badgeStyle: 'dot',
			position: 'before_profile',
			colors: panelOptions.colors || {}
		};
	}

	function anmPreviewBuildTriggerHtml(i18n, style, badgeStyle, position, count) {
		if (anmPreviewStyleUsesBell(style, position)) {
			var triggerClass = 'edminboost-anm-notice-panel-preview__trigger edminboost-anm-notice-panel-preview__trigger--bell';
			if (position === 'before_profile') {
				triggerClass += ' is-facebook';
			}
			if (style === 'bell_link') {
				triggerClass += ' is-style-bell-link';
			} else if (anmPreviewIsDropdownStyle(style)) {
				triggerClass += ' is-style-dropdown';
			}
			return '<span class="' + triggerClass + '">'
				+ '<span class="ab-icon dashicons dashicons-bell edminboost-anm-notice-panel-preview__bell-icon" aria-hidden="true"></span>'
				+ anmPreviewBuildBadgeHtml(badgeStyle, count)
				+ '</span>';
		}
		return '<span class="edminboost-anm-notice-panel-preview__trigger edminboost-anm-notice-panel-preview__trigger--link">'
			+ anmPreviewEscapeHtml(i18n.noticePanelPreviewNotices || 'Notices')
			+ ' <span class="awaiting-mod count-' + anmPreviewEscapeHtml(String(count)) + '"><span class="pending-count">' + anmPreviewEscapeHtml(String(count)) + '</span></span>'
			+ '</span>';
	}

	function anmPreviewBuildMockBarHtml(i18n, panelOptions, openDropdown, noticeCount, customPanelHtml) {
		var style = panelOptions.style;
		var badgeStyle = panelOptions.badgeStyle;
		var position = panelOptions.position;
		var previewLayout = anmPreviewGetPositionLayout(position);
		var triggerHtml = anmPreviewBuildTriggerHtml(i18n, style, badgeStyle, position, noticeCount);
		var panelHtml = customPanelHtml || '';
		if (!panelHtml && openDropdown && anmPreviewIsDropdownStyle(style)) {
			panelHtml = style === 'dropdown_compact' ? anmPreviewBuildCompactPanel(i18n) : anmPreviewBuildCompactPanel(i18n);
		} else if (!panelHtml && openDropdown && !anmPreviewIsDropdownStyle(style)) {
			panelHtml = anmPreviewBuildCompactPanel(i18n);
		}

		var profileHtml = (previewLayout === 'profile_before' || previewLayout === 'profile_after')
			? '<span class="edminboost-anm-notice-panel-preview__profile">' + anmPreviewEscapeHtml(i18n.noticePanelPreviewHowdy || 'Howdy, Admin') + '</span>'
			: '';
		var siteHtml = previewLayout === 'left'
			? '<span class="edminboost-anm-notice-panel-preview__site">' + anmPreviewEscapeHtml(i18n.noticePanelPreviewSiteName || 'My Site') + '</span>'
			: '';
		var barInnerHtml = '';
		if (previewLayout === 'profile_after') {
			barInnerHtml = profileHtml + triggerHtml;
		} else if (previewLayout === 'left') {
			barInnerHtml = siteHtml + triggerHtml;
		} else {
			barInnerHtml = triggerHtml + profileHtml;
		}

		var mockBarClass = 'edminboost-anm-notice-panel-preview__mock-bar';
		if (previewLayout === 'left') {
			mockBarClass += ' is-left-slot';
		} else if (previewLayout === 'profile_before' || previewLayout === 'profile_after') {
			mockBarClass += ' is-profile-slot';
		}
		if (panelHtml) {
			mockBarClass += ' is-dropdown-open';
		}

		return '<div class="' + mockBarClass + '">'
			+ '<div class="edminboost-anm-notice-panel-preview__bar-inner">'
			+ barInnerHtml
			+ '</div>'
			+ (panelHtml ? '<div class="edminboost-anm-notice-panel-preview__panel-wrap">' + panelHtml + '</div>' : '')
			+ '</div>';
	}

	function anmPreviewBuildPlainAdminBarHtml(i18n) {
		return '<div class="edminboost-anm-notice-panel-preview__mock-bar">'
			+ '<div class="edminboost-anm-notice-panel-preview__bar-inner">'
			+ '<span class="edminboost-anm-notice-panel-preview__site">' + anmPreviewEscapeHtml(i18n.noticePanelPreviewSiteName || 'My Site') + '</span>'
			+ '<span class="edminboost-anm-notice-panel-preview__profile">' + anmPreviewEscapeHtml(i18n.noticePanelPreviewHowdy || 'Howdy, Admin') + '</span>'
			+ '</div></div>';
	}

	function anmManageModeFromPresetInput(input) {
		if (!input) {
			return '';
		}
		var mode = input.getAttribute('data-manage-mode');
		if (mode) {
			return mode;
		}
		var map = data.displayExperiencePresetModes || {};
		var slug = input.value || '';
		return map[slug] || '';
	}

	function anmNormalizeManageNoticesMode(mode) {
		if (!mode) {
			return mode;
		}
		var map = data.displayExperiencePresetModes || {};
		if (map[mode]) {
			return map[mode];
		}
		return mode;
	}

	function anmPreviewBuildGithubNotificationsHtml(i18n) {
		var items = [
			{
				title: i18n.displayExperiencePreviewInboxFeedSample1Title || 'WordPress 6.7 is available',
				excerpt: i18n.displayExperiencePreviewInboxFeedSample1Excerpt || i18n.displayExperiencePreviewInboxFeedSample1 || '',
				label: i18n.displayExperiencePreviewInboxFeedSample1Label || 'update available',
				source: i18n.displayExperiencePreviewInboxFeedSource || 'WordPress',
				date: i18n.displayExperiencePreviewInboxFeedSample1Date || 'on Mar 12',
				icon: 'dashicons-download',
				unread: true
			},
			{
				title: i18n.displayExperiencePreviewInboxFeedSample2Title || 'SEO plugin license',
				excerpt: i18n.displayExperiencePreviewInboxFeedSample2Excerpt || i18n.displayExperiencePreviewInboxFeedSample2 || '',
				label: i18n.displayExperiencePreviewInboxFeedSample2Label || 'warning',
				source: i18n.displayExperiencePreviewInboxFeedSourceYoast || 'Yoast SEO',
				date: i18n.displayExperiencePreviewInboxFeedSample2Date || 'on Mar 10',
				icon: 'dashicons-warning',
				unread: true
			},
			{
				title: i18n.displayExperiencePreviewInboxFeedSample3Title || 'New form submission',
				excerpt: i18n.displayExperiencePreviewInboxFeedSample3Excerpt || i18n.displayExperiencePreviewInboxFeedSample3 || '',
				label: i18n.displayExperiencePreviewInboxFeedSample3Label || 'info',
				source: i18n.displayExperiencePreviewInboxFeedSourceForms || 'WPForms',
				date: i18n.displayExperiencePreviewInboxFeedSample3Date || 'on Mar 8',
				icon: 'dashicons-info',
				unread: true
			}
		];
		var rows = items.map(function (item) {
			var sourceInitial = (item.source || '').trim().charAt(0).toUpperCase() || 'W';
			return '<li class="edminboost-anm-inbox-feed__item edminboost-anm-github-feed__item edminboost-anm-display-experience-preview__feed-item' + (item.unread ? '' : ' is-read') + '">'
				+ '<span class="edminboost-anm-inbox-feed__unread" aria-hidden="true"' + (item.unread ? '' : ' hidden') + '></span>'
				+ '<input type="checkbox" class="edminboost-anm-inbox-feed__check" tabindex="-1" disabled aria-hidden="true" />'
				+ '<span class="edminboost-anm-inbox-feed__icon dashicons ' + item.icon + '" aria-hidden="true"></span>'
				+ '<div class="edminboost-anm-inbox-feed__body">'
				+ '<p class="edminboost-anm-inbox-feed__title">' + anmPreviewEscapeHtml(item.title) + '</p>'
				+ '<p class="edminboost-anm-inbox-feed__excerpt">' + anmPreviewEscapeHtml(item.excerpt) + '</p>'
				+ '</div>'
				+ '<div class="edminboost-anm-inbox-feed__meta">'
				+ '<span class="edminboost-anm-github-feed__type">' + anmPreviewEscapeHtml(item.label) + '</span>'
				+ '<span class="edminboost-anm-github-feed__avatar" aria-hidden="true" title="' + anmPreviewEscapeHtml(item.source) + '">' + anmPreviewEscapeHtml(sourceInitial) + '</span>'
				+ '<time class="edminboost-anm-inbox-feed__time">' + anmPreviewEscapeHtml(item.date) + '</time>'
				+ '</div></li>';
		}).join('');

		var feedTitle = i18n.displayExperiencePreviewInboxFeedTitle || 'Notifications';
		var navInbox = i18n.displayExperiencePreviewInboxFeedNavInbox || 'Inbox';
		var navSaved = i18n.displayExperiencePreviewInboxFeedNavSaved || 'Saved';
		var navDone = i18n.displayExperiencePreviewInboxFeedNavDone || 'Done';
		var navAlerts = i18n.displayExperiencePreviewInboxFeedNavAlerts || 'Alerts';
		var navSnoozed = i18n.displayExperiencePreviewInboxFeedNavSnoozed || 'Snoozed';
		var navPromotions = i18n.displayExperiencePreviewInboxFeedNavPromo || 'Promotions';
		var filterUnread = i18n.displayExperiencePreviewInboxFeedFilterUnread || 'Unread';
		var filterUpdates = i18n.displayExperiencePreviewInboxFeedFilterUpdates || 'Updates';
		var filterPlugins = i18n.displayExperiencePreviewInboxFeedFilterPlugins || 'From plugins';
		var filtersLabel = i18n.displayExperiencePreviewInboxFeedFilters || 'Filters';
		var tabAll = i18n.displayExperiencePreviewInboxFeedTabAll || 'All';
		var tabUnread = i18n.displayExperiencePreviewInboxFeedTabUnread || 'Unread';
		var searchQuery = i18n.displayExperiencePreviewInboxFeedSearchQuery || 'is:unread';
		var sortBy = i18n.displayExperiencePreviewInboxFeedSortBy || 'Sort by:';
		var sortNewest = i18n.displayExperiencePreviewInboxFeedSortNewest || 'Newest to oldest';
		var groupBy = i18n.displayExperiencePreviewInboxFeedGroupBy || 'Group by:';
		var groupDate = i18n.displayExperiencePreviewInboxFeedGroupDate || 'Date';
		var selectAll = i18n.displayExperiencePreviewInboxFeedSelectAll || 'Select all';
		var proTip = i18n.displayExperiencePreviewInboxFeedProTip || '';
		var pager = i18n.displayExperiencePreviewInboxFeedPager || '1–3 of 3';

		function previewNav(label, count, active, icon) {
			return '<span class="edminboost-anm-github-feed__nav' + (active ? ' is-active' : '') + '" tabindex="-1">'
				+ (icon ? '<span class="edminboost-anm-github-feed__nav-icon dashicons ' + icon + '" aria-hidden="true"></span>' : '')
				+ '<span class="edminboost-anm-github-feed__nav-label">' + anmPreviewEscapeHtml(label) + '</span>'
				+ (count !== '' ? '<span class="edminboost-anm-github-feed__nav-count">' + anmPreviewEscapeHtml(String(count)) + '</span>' : '')
				+ '</span>';
		}

		function previewFilterNav(label, count, icon) {
			return '<span class="edminboost-anm-github-feed__nav edminboost-anm-github-feed__nav--filter" tabindex="-1">'
				+ '<span class="edminboost-anm-github-feed__nav-icon dashicons ' + icon + '" aria-hidden="true"></span>'
				+ '<span class="edminboost-anm-github-feed__nav-label">' + anmPreviewEscapeHtml(label) + '</span>'
				+ (count !== '' ? '<span class="edminboost-anm-github-feed__nav-count">' + anmPreviewEscapeHtml(String(count)) + '</span>' : '')
				+ '</span>';
		}

		return '<div class="edminboost-anm-github-feed edminboost-anm-display-experience-preview__inbox-feed" role="img" aria-label="' + anmPreviewEscapeHtml(feedTitle) + '">'
			+ '<div class="edminboost-anm-github-feed__titlebar" aria-hidden="true">'
			+ '<span class="edminboost-anm-github-feed__titlebar-mark"></span>'
			+ '<span class="edminboost-anm-github-feed__titlebar-heading">' + anmPreviewEscapeHtml(feedTitle) + '</span>'
			+ '</div>'
			+ '<div class="edminboost-anm-github-feed__layout">'
			+ '<aside class="edminboost-anm-github-feed__sidebar" aria-hidden="true">'
			+ previewNav(navInbox, 3, true, 'dashicons-email')
			+ previewNav(navSaved, '', false, 'dashicons-book')
			+ previewNav(navDone, '', false, 'dashicons-yes')
			+ '<p class="edminboost-anm-github-feed__filters-heading">' + anmPreviewEscapeHtml(filtersLabel) + '</p>'
			+ previewFilterNav(filterUnread, 3, 'dashicons-marker')
			+ previewFilterNav(navAlerts, 2, 'dashicons-warning')
			+ previewFilterNav(navSnoozed, 0, 'dashicons-clock')
			+ previewFilterNav(navPromotions, 1, 'dashicons-megaphone')
			+ previewFilterNav(filterUpdates, 1, 'dashicons-download')
			+ previewFilterNav(filterPlugins, 2, 'dashicons-admin-plugins')
			+ '</aside>'
			+ '<div class="edminboost-anm-github-feed__main">'
			+ '<div class="edminboost-anm-github-feed__tabs" aria-hidden="true">'
			+ '<span class="edminboost-anm-github-feed__tab">' + anmPreviewEscapeHtml(tabAll) + '</span>'
			+ '<span class="edminboost-anm-github-feed__tab is-active">' + anmPreviewEscapeHtml(tabUnread) + '</span>'
			+ '<span class="edminboost-anm-github-feed__search"><span class="edminboost-anm-github-feed__search-input">' + anmPreviewEscapeHtml(searchQuery) + '</span></span>'
			+ '</div>'
			+ '<div class="edminboost-anm-github-feed__meta-toolbar" aria-hidden="true">'
			+ '<span>' + anmPreviewEscapeHtml(sortBy) + ' <strong>' + anmPreviewEscapeHtml(sortNewest) + '</strong></span>'
			+ '<span>' + anmPreviewEscapeHtml(groupBy) + ' <strong>' + anmPreviewEscapeHtml(groupDate) + '</strong></span>'
			+ '</div>'
			+ '<div class="edminboost-anm-github-feed__toolbar" aria-hidden="true">'
			+ '<label class="edminboost-anm-inbox-feed__select-all">'
			+ '<input type="checkbox" tabindex="-1" disabled aria-hidden="true" /> '
			+ anmPreviewEscapeHtml(selectAll)
			+ '</label></div>'
			+ '<div class="edminboost-anm-github-feed__card">'
			+ '<ul class="edminboost-anm-inbox-feed__list">' + rows + '</ul>'
			+ '</div>'
			+ '<div class="edminboost-anm-github-feed__footer" aria-hidden="true">'
			+ '<span class="edminboost-anm-github-feed__protip">' + anmPreviewEscapeHtml(proTip) + '</span>'
			+ '<span class="edminboost-anm-github-feed__pager">' + anmPreviewEscapeHtml(pager) + '</span>'
			+ '</div>'
			+ '</div>'
			+ '</div>'
			+ '</div>';
	}

	function anmPreviewBuildDashboardNoticesHtml(i18n) {
		var lines = [
			{
				className: 'notice notice-info',
				text: i18n.displayExperiencePreviewNoticeUpdate || 'WordPress 6.7 is available. Please update now.'
			},
			{
				className: 'notice notice-warning',
				text: i18n.displayExperiencePreviewNoticeLicense || 'Please activate your SEO plugin license to receive updates.'
			},
			{
				className: 'notice notice-info',
				text: i18n.displayExperiencePreviewNoticeForm || 'You have a new form submission waiting in your inbox.'
			}
		];
		return lines.map(function (line) {
			return '<div class="' + line.className + ' edminboost-anm-display-experience-preview__notice"><p>'
				+ anmPreviewEscapeHtml(line.text)
				+ '</p></div>';
		}).join('');
	}

	function anmPreviewBuildEcomDashboardNoticesHtml(i18n, scope) {
		scope = scope || {};
		var lines = [];
		if (scope.woocommerce) {
			lines.push({
				className: 'notice notice-warning',
				text: i18n.displayExperiencePreviewNoticeWooStock || 'WooCommerce: 2 products are low in stock.'
			});
		}
		if (scope.edd) {
			lines.push({
				className: 'notice notice-info',
				text: i18n.displayExperiencePreviewNoticeEddPayment || 'Easy Digital Downloads: complete your payment gateway setup.'
			});
		}
		if (scope.other) {
			lines.push({
				className: 'notice notice-info',
				text: i18n.displayExperiencePreviewNoticeEcomOther || 'Your store plugin needs a quick configuration step.'
			});
		}
		if (!lines.length) {
			lines.push({
				className: 'notice notice-info edminboost-anm-display-experience-preview__empty',
				text: i18n.displayExperiencePreviewEcomDashboardEmpty || 'No ecommerce notice groups selected — store notices are sent to the inbox.'
			});
		}
		return lines.map(function (line) {
			return '<div class="' + line.className + ' edminboost-anm-display-experience-preview__notice edminboost-anm-display-experience-preview__notice--ecom"><p>'
				+ anmPreviewEscapeHtml(line.text)
				+ '</p></div>';
		}).join('');
	}

	function anmRenderDisplayExperiencePreview(mode) {
		var stage = document.querySelector('[data-edminboost-anm-display-experience-preview-stage]');
		var previewRoot = document.getElementById('edminboost-anm-display-experience-preview');
		var previewFrame = document.querySelector('[data-edminboost-anm-display-experience-preview-frame]');
		var previewCaption = document.querySelector('[data-edminboost-anm-display-experience-preview-caption]');
		var i18n = data.i18n || {};

		if (!stage) {
			return;
		}

		mode = anmNormalizeManageNoticesMode(mode);
		if (!mode) {
			mode = 'hide_selected';
		}
		if (previewFrame) {
			previewFrame.setAttribute('data-preview-mode', mode);
			previewFrame.classList.toggle('is-inbox-feed-mode', mode === 'inbox_feed');
			previewFrame.classList.toggle('is-ecom-dashboard-mode', mode === 'ecom_dashboard');
		}
		if (previewRoot) {
			previewRoot.classList.toggle('is-inbox-feed-mode', mode === 'inbox_feed');
			previewRoot.classList.toggle('is-ecom-dashboard-mode', mode === 'ecom_dashboard');
			previewRoot.classList.toggle('is-facebook-notifications-mode', mode === 'facebook_notifications');
		}

		var panelOptions = anmReadNoticePanelPreviewOptions();
		anmApplyNoticePanelPreviewColors(previewRoot, panelOptions.colors);

		var noticeCount = 3;
		var adminBarHtml = '';
		var dashboardInner = '';

		if (mode === 'show_all') {
			adminBarHtml = anmPreviewBuildPlainAdminBarHtml(i18n);
			dashboardInner = '<div class="edminboost-anm-display-experience-preview__notices">'
				+ anmPreviewBuildDashboardNoticesHtml(i18n)
				+ '</div>';
		} else if (mode === 'inbox_feed') {
			adminBarHtml = anmPreviewBuildMockBarHtml(i18n, panelOptions, true, noticeCount);
			dashboardInner = anmPreviewBuildGithubNotificationsHtml(i18n);
		} else if (mode === 'facebook_notifications') {
			var fbPanelOptions = anmPreviewFacebookPanelOptions(panelOptions);
			adminBarHtml = anmPreviewBuildMockBarHtml(
				i18n,
				fbPanelOptions,
				true,
				noticeCount,
				anmPreviewBuildFacebookPanelHtml(i18n)
			);
			dashboardInner = '<p class="edminboost-anm-display-experience-preview__empty">'
				+ anmPreviewEscapeHtml(i18n.displayExperiencePreviewDashboardClear || 'Dashboard notice area is clear.')
				+ '</p>';
			dashboardInner += '<p class="edminboost-anm-display-experience-preview__inbox-hint">'
				+ anmPreviewEscapeHtml(i18n.displayExperiencePreviewFbHint || i18n.displayExperiencePreviewInboxHint || '')
				+ '</p>';
		} else if (mode === 'ecom_dashboard') {
			var ecomScope = anmReadEcomDisplayScope();
			var nonEcomCapturedCount = 2;
			adminBarHtml = anmPreviewBuildMockBarHtml(
				i18n,
				panelOptions,
				true,
				nonEcomCapturedCount,
				anmPreviewBuildNonEcomCapturedPanel(i18n)
			);
			dashboardInner = '<p class="edminboost-anm-display-experience-preview__ecom-label description">'
				+ anmPreviewEscapeHtml(i18n.displayExperiencePreviewEcomDashboardLabel || 'Store notices on the dashboard')
				+ '</p>';
			dashboardInner += '<div class="edminboost-anm-display-experience-preview__notices edminboost-anm-display-experience-preview__notices--ecom-only">'
				+ anmPreviewBuildEcomDashboardNoticesHtml(i18n, ecomScope)
				+ '</div>';
		} else if (mode === 'hide_selected' || mode === 'compact_panel') {
			adminBarHtml = anmPreviewBuildMockBarHtml(i18n, panelOptions, true, noticeCount);
			dashboardInner = '<p class="edminboost-anm-display-experience-preview__empty">'
				+ anmPreviewEscapeHtml(i18n.displayExperiencePreviewDashboardClear || 'Dashboard notice area is clear.')
				+ '</p>';
			if (mode === 'hide_selected') {
				dashboardInner += '<p class="edminboost-anm-display-experience-preview__inbox-hint">'
					+ anmPreviewEscapeHtml(i18n.displayExperiencePreviewInboxHint || 'Matching notices appear in the inbox and admin bar panel above.')
					+ '</p>';
			}
		} else {
			adminBarHtml = anmPreviewBuildPlainAdminBarHtml(i18n);
			dashboardInner = '<p class="edminboost-anm-display-experience-preview__empty edminboost-anm-display-experience-preview__empty--muted">'
				+ anmPreviewEscapeHtml(i18n.displayExperiencePreviewHideAllDashboard || 'No notices shown on the dashboard.')
				+ '</p>';
		}

		if (mode === 'inbox_feed') {
			stage.innerHTML = adminBarHtml
				+ '<div class="edminboost-anm-display-experience-preview__dashboard edminboost-anm-display-experience-preview__dashboard--inbox-feed">'
				+ dashboardInner
				+ '</div>';
		} else {
			stage.innerHTML = adminBarHtml
				+ '<div class="edminboost-anm-display-experience-preview__dashboard">'
				+ '<p class="edminboost-anm-display-experience-preview__page-title">'
				+ anmPreviewEscapeHtml(i18n.displayExperiencePreviewDashboardTitle || 'Dashboard')
				+ '</p>'
				+ dashboardInner
				+ '</div>';
		}

		if (previewCaption) {
			var caption = '';
			switch (mode) {
				case 'show_all':
					caption = i18n.displayExperiencePreviewCaptionShowAll || '';
					break;
				case 'hide_selected':
					caption = i18n.displayExperiencePreviewCaptionHideSelected || '';
					break;
				case 'inbox_feed':
					caption = i18n.displayExperiencePreviewCaptionInboxFeed || '';
					break;
				case 'facebook_notifications':
					caption = i18n.displayExperiencePreviewCaptionFacebook || '';
					break;
				case 'ecom_dashboard':
					caption = i18n.displayExperiencePreviewCaptionEcomDashboard || '';
					break;
				case 'hide_all':
					caption = i18n.displayExperiencePreviewCaptionHideAll || '';
					break;
				case 'compact_panel':
					caption = i18n.displayExperiencePreviewCaptionCompactPanel || i18n.displayExperiencePreviewInboxHint || '';
					break;
				default:
					caption = '';
			}
			previewCaption.textContent = caption;
		}
	}

	function initGeneralOptionsPreview() {
		var root = document.querySelector('[data-edminboost-anm-general-options]');
		if (!root) {
			return;
		}

		var i18n = data.i18n || {};
		var previewState = {
			hiddenNoticeVisible: false
		};

		function renderGeneralOptionsPreview() {
			var pluginCb = document.getElementById('edminboost-anm-disable-plugin-updates');
			var coreCb = document.getElementById('edminboost-anm-disable-core-updates');
			var frame = root.querySelector('[data-edminboost-anm-general-options-preview-frame]');
			var stage = root.querySelector('[data-edminboost-anm-general-options-preview-stage]');
			var captionEl = root.querySelector('[data-edminboost-anm-general-options-preview-caption]');
			var hidePlugin = !!(pluginCb && pluginCb.checked);
			var hideCore = !!(coreCb && coreCb.checked);
			var scopeInput = root.querySelector('input[name="edminboost-anm-reset-hidden-scope"]:checked');
			var resetScope = scopeInput ? scopeInput.value : 'current_user';
			var hiddenVisible = previewState.hiddenNoticeVisible ? '1' : '0';

			if (frame) {
				frame.setAttribute('data-hide-plugin', hidePlugin ? '1' : '0');
				frame.setAttribute('data-hide-core', hideCore ? '1' : '0');
				frame.setAttribute('data-reset-scope', resetScope);
				frame.setAttribute('data-hidden-notice-visible', hiddenVisible);
			}

			if (stage) {
				var coreText = i18n.displayExperiencePreviewNoticeUpdate || 'WordPress 6.7 is available. Please update now.';
				var pluginText = i18n.generalOptionsPreviewNoticePlugin || 'There are updates available for 3 plugins. View details or update now.';
				var hiddenText = i18n.generalOptionsPreviewNoticeHidden || 'Thanks for using Contact Form 7! Please leave us a review on WordPress.org.';
				var badgeText = i18n.generalOptionsPreviewHiddenBadge || 'Hidden for you';
				var noticesHtml = ''
					+ '<div class="update-nag edminboost-anm-display-experience-preview__notice edminboost-anm-general-options-preview__notice--core"><p>'
					+ anmPreviewEscapeHtml(coreText)
					+ '</p></div>'
					+ '<div class="update-nag edminboost-anm-display-experience-preview__notice edminboost-anm-general-options-preview__notice--plugin"><p>'
					+ anmPreviewEscapeHtml(pluginText)
					+ '</p></div>'
					+ '<div class="notice notice-info edminboost-anm-display-experience-preview__notice edminboost-anm-general-options-preview__notice--user-hidden">'
					+ '<span class="edminboost-anm-general-options-preview__hidden-badge">' + anmPreviewEscapeHtml(badgeText) + '</span>'
					+ '<p>' + anmPreviewEscapeHtml(hiddenText) + '</p></div>';

				stage.innerHTML = '<div class="edminboost-anm-display-experience-preview__dashboard">'
					+ '<p class="edminboost-anm-display-experience-preview__page-title">'
					+ anmPreviewEscapeHtml(i18n.displayExperiencePreviewDashboardTitle || 'Dashboard')
					+ '</p>'
					+ '<div class="edminboost-anm-display-experience-preview__notices">' + noticesHtml + '</div>'
					+ '</div>';
			}

			if (captionEl) {
				var caption = '';
				if (hideCore && hidePlugin) {
					caption = i18n.generalOptionsPreviewCaptionHideBoth || '';
				} else if (hideCore) {
					caption = i18n.generalOptionsPreviewCaptionHideCore || '';
				} else if (hidePlugin) {
					caption = i18n.generalOptionsPreviewCaptionHidePlugin || '';
				}
				var resetHint = resetScope === 'all_users'
					? (i18n.generalOptionsPreviewCaptionResetAll || '')
					: '';
				if (resetHint) {
					caption = caption ? (caption + ' ' + resetHint) : resetHint;
				}
				captionEl.textContent = caption;
			}
		}

		function bindField(field) {
			if (!field) {
				return;
			}
			field.addEventListener('change', function () {
				renderGeneralOptionsPreview();
			});
		}

		bindField(document.getElementById('edminboost-anm-disable-plugin-updates'));
		bindField(document.getElementById('edminboost-anm-disable-core-updates'));

		root.querySelectorAll('input[name="edminboost-anm-reset-hidden-scope"]').forEach(function (radio) {
			radio.addEventListener('change', function () {
				previewState.hiddenNoticeVisible = false;
				renderGeneralOptionsPreview();
			});
		});

		root.addEventListener('edminboost-anm-reset-hidden-success', function () {
			previewState.hiddenNoticeVisible = true;
			renderGeneralOptionsPreview();
		});

		var settingsRoot = document.querySelector('[data-edminboost-anm-settings]');
		if (settingsRoot) {
			settingsRoot.addEventListener('edminboost-anm-settings-tab', function (event) {
				if (event.detail && event.detail.tab === 'general') {
					renderGeneralOptionsPreview();
				}
			});
		}

		renderGeneralOptionsPreview();
	}

	function initWhiteLabelPreview() {
		var root = document.querySelector('[data-edminboost-anm-white-label]');
		if (!root) {
			return;
		}

		var previewRoot = document.getElementById('edminboost-anm-white-label-preview');
		var stage = root.querySelector('[data-edminboost-anm-white-label-preview-stage]');
		var captionEl = root.querySelector('[data-edminboost-anm-white-label-preview-caption]');
		var i18n = data.i18n || {};
		var defaultDocsUrl = previewRoot && previewRoot.getAttribute('data-default-docs-url')
			? previewRoot.getAttribute('data-default-docs-url')
			: '';

		var fieldIds = [
			'edminboost-anm-white-label-enabled',
			'edminboost-anm-white-label-menu-title',
			'edminboost-anm-white-label-brand-name',
			'edminboost-anm-white-label-logo-url',
			'edminboost-anm-white-label-hide-brand',
			'edminboost-anm-white-label-hide-docs',
			'edminboost-anm-white-label-docs-url'
		];

		function readOptions() {
			function val(id) {
				var el = document.getElementById(id);
				return el ? el : null;
			}

			var enabledEl = val('edminboost-anm-white-label-enabled');
			var menuEl = val('edminboost-anm-white-label-menu-title');
			var brandEl = val('edminboost-anm-white-label-brand-name');
			var logoEl = val('edminboost-anm-white-label-logo-url');
			var hideBrandEl = val('edminboost-anm-white-label-hide-brand');
			var hideDocsEl = val('edminboost-anm-white-label-hide-docs');
			var docsEl = val('edminboost-anm-white-label-docs-url');

			return {
				enabled: !!(enabledEl && enabledEl.checked),
				menuTitle: menuEl ? menuEl.value.trim() : '',
				brandName: brandEl ? brandEl.value.trim() : '',
				logoUrl: logoEl ? logoEl.value.trim() : '',
				hideBrand: !!(hideBrandEl && hideBrandEl.checked),
				hideDocs: !!(hideDocsEl && hideDocsEl.checked),
				docsUrl: docsEl ? docsEl.value.trim() : ''
			};
		}

		function resolveMenuTitle(opts) {
			var fallback = i18n.whiteLabelPreviewDefaultMenuTitle || 'Notice Manager';
			if (!opts.enabled || !opts.menuTitle) {
				return fallback;
			}
			return opts.menuTitle;
		}

		function resolveBrand(opts) {
			var defaultBrand = i18n.whiteLabelPreviewDefaultBrand || 'Edminboost';
			if (!opts.enabled) {
				return { show: true, name: defaultBrand, logoUrl: '' };
			}
			if (opts.hideBrand) {
				return { show: false, name: '', logoUrl: '' };
			}
			if (!opts.brandName && !opts.logoUrl) {
				return { show: false, name: '', logoUrl: '' };
			}
			return {
				show: true,
				name: opts.brandName,
				logoUrl: opts.logoUrl
			};
		}

		function resolveDocs(opts) {
			if (!opts.enabled) {
				return { show: true, custom: false };
			}
			if (opts.hideDocs) {
				return { show: false, custom: false };
			}
			return {
				show: true,
				custom: !!opts.docsUrl,
				url: opts.docsUrl || defaultDocsUrl
			};
		}

		function buildBrandHtml(brand) {
			if (!brand.show) {
				return '';
			}
			var logoHtml = brand.logoUrl
				? '<img class="edminboost-anm-topbar__logo edminboost-anm-white-label-preview__logo" src="'
					+ anmPreviewEscapeHtml(brand.logoUrl)
					+ '" alt="" />'
				: '';
			var nameHtml = brand.name
				? '<strong>' + anmPreviewEscapeHtml(brand.name) + '</strong>'
				: '';
			return '<div class="edminboost-anm-topbar__brand edminboost-anm-white-label-preview__brand">'
				+ logoHtml + nameHtml + '</div>';
		}

		function renderWhiteLabelPreview() {
			var opts = readOptions();
			var menuTitle = resolveMenuTitle(opts);
			var brand = resolveBrand(opts);
			var docs = resolveDocs(opts);
			var pluginName = i18n.whiteLabelPreviewPluginName || 'Edminboost - Admin Notice Manager';
			var quickStatus = i18n.whiteLabelPreviewQuickStatus || 'Quick Status:';
			var activeLabel = i18n.statusActive || 'Active';
			var settingsLink = i18n.whiteLabelPreviewSettingsLink || 'Settings';
			var docsLink = i18n.whiteLabelPreviewDocsLink || 'Docs';
			var menuSection = i18n.whiteLabelPreviewMenuSection || 'Admin menu';
			var dashboardSection = i18n.whiteLabelPreviewDashboardSection || 'Dashboard top bar';
			var pluginsSection = i18n.whiteLabelPreviewPluginsSection || 'Plugins screen';
			var docsMeta = '';

			if (docs.show) {
				docsMeta = ' | <span class="edminboost-anm-white-label-preview__meta-link">'
					+ anmPreviewEscapeHtml(docsLink) + '</span>';
			}

			if (stage) {
				stage.innerHTML = ''
					+ '<div class="edminboost-anm-white-label-preview__block">'
					+ '<p class="edminboost-anm-white-label-preview__label">' + anmPreviewEscapeHtml(menuSection) + '</p>'
					+ '<div class="edminboost-anm-white-label-preview__menu" role="presentation">'
					+ '<span class="edminboost-anm-white-label-preview__menu-item is-current">'
					+ anmPreviewEscapeHtml(menuTitle)
					+ '</span>'
					+ '<span class="edminboost-anm-white-label-preview__menu-item is-muted">'
					+ anmPreviewEscapeHtml(menuTitle)
					+ '</span>'
					+ '</div></div>'
					+ '<div class="edminboost-anm-white-label-preview__block">'
					+ '<p class="edminboost-anm-white-label-preview__label">' + anmPreviewEscapeHtml(dashboardSection) + '</p>'
					+ '<div class="edminboost-anm-topbar edminboost-anm-white-label-preview__topbar">'
					+ buildBrandHtml(brand)
					+ '<div class="edminboost-anm-topbar__status">'
					+ anmPreviewEscapeHtml(quickStatus)
					+ ' <span class="edminboost-anm-status is-active">' + anmPreviewEscapeHtml(activeLabel) + '</span>'
					+ '</div></div></div>'
					+ '<div class="edminboost-anm-white-label-preview__block">'
					+ '<p class="edminboost-anm-white-label-preview__label">' + anmPreviewEscapeHtml(pluginsSection) + '</p>'
					+ '<div class="edminboost-anm-white-label-preview__plugin-row">'
					+ '<strong class="edminboost-anm-white-label-preview__plugin-name">'
					+ anmPreviewEscapeHtml(pluginName)
					+ '</strong>'
					+ '<p class="edminboost-anm-white-label-preview__plugin-meta">'
					+ '<span class="edminboost-anm-white-label-preview__meta-link">'
					+ anmPreviewEscapeHtml(settingsLink)
					+ '</span>'
					+ docsMeta
					+ '</p></div></div>';
			}

			if (captionEl) {
				var caption = '';
				if (!opts.enabled) {
					caption = i18n.whiteLabelPreviewCaptionDisabled || '';
				} else if (opts.hideBrand) {
					caption = i18n.whiteLabelPreviewCaptionBrandHidden || '';
				} else if (!brand.show) {
					caption = i18n.whiteLabelPreviewCaptionNoBrand || '';
				}
				if (opts.enabled && opts.hideDocs) {
					caption = caption
						? (caption + ' ' + (i18n.whiteLabelPreviewCaptionDocsHidden || ''))
						: (i18n.whiteLabelPreviewCaptionDocsHidden || '');
				} else if (opts.enabled && docs.show && docs.custom) {
					var customHint = i18n.whiteLabelPreviewCaptionCustomDocs || '';
					caption = caption ? (caption + ' ' + customHint) : customHint;
				}
				captionEl.textContent = caption;
			}
		}

		fieldIds.forEach(function (id) {
			var field = document.getElementById(id);
			if (!field) {
				return;
			}
			var eventName = field.type === 'checkbox' ? 'change' : 'input';
			field.addEventListener(eventName, renderWhiteLabelPreview);
		});

		var settingsRoot = document.querySelector('[data-edminboost-anm-settings]');
		if (settingsRoot) {
			settingsRoot.addEventListener('edminboost-anm-settings-tab', function (event) {
				if (event.detail && event.detail.tab === 'white_label') {
					renderWhiteLabelPreview();
				}
			});
		}

		renderWhiteLabelPreview();
	}

	function initWrap() {
		var wrap = document.querySelector('.edminboost-anm-wrap');
		if (wrap) {
			wrap.classList.add('is-ready');
		}
	}

	function initHidePluginAdminNotices() {
		if (!document.querySelector('.edminboost-anm-wrap')) {
			return;
		}

		var selector = '.notice, .update-nag, div.error, div.updated';

		function shouldHide(node) {
			if (!node || !node.matches || !node.matches(selector)) {
				return false;
			}
			if (node.closest('.edminboost-anm-notice-panel-preview, .edminboost-anm-display-experience-preview, .edminboost-anm-general-options-preview, .edminboost-anm-white-label-preview, #wpadminbar')) {
				return false;
			}
			return true;
		}

		function hideNotices(root) {
			if (!root || !root.querySelectorAll) {
				return;
			}
			root.querySelectorAll(selector).forEach(function (node) {
				if (shouldHide(node)) {
					node.classList.add('edminboost-anm-notice-hidden');
				}
			});
		}

		hideNotices(document.getElementById('wpbody-content'));

		var wpbody = document.getElementById('wpbody-content');
		if (wpbody && typeof MutationObserver !== 'undefined') {
			var observer = new MutationObserver(function (mutations) {
				mutations.forEach(function (mutation) {
					mutation.addedNodes.forEach(function (node) {
						if (node.nodeType !== 1) {
							return;
						}
						if (shouldHide(node)) {
							node.classList.add('edminboost-anm-notice-hidden');
						} else if (node.querySelectorAll) {
							hideNotices(node);
						}
					});
				});
			});
			observer.observe(wpbody, { childList: true, subtree: true });
		}
	}

	function initQuickStatusToggle() {
		var toggle = document.querySelector('.edminboost-anm-enabled-toggle');
		if (!toggle) {
			return;
		}

		toggle.addEventListener('change', function () {
			apiPost('edminboost_anm_toggle_enabled', { enabled: toggle.checked ? '1' : '0' })
				.then(function (result) {
					if (!result.success) {
						toggle.checked = !toggle.checked;
						return;
					}
					window.location.reload();
				});
		});
	}

	function initPresetToggle() {
		var toggle = document.querySelector('.edminboost-anm-preset-toggle');
		if (!toggle) {
			return;
		}

		toggle.addEventListener('change', function () {
			apiPost('edminboost_anm_toggle_preset', { enabled: toggle.checked ? '1' : '0' })
				.then(function (result) {
					if (!result.success) {
						toggle.checked = !toggle.checked;
						return;
					}
					var status = document.querySelector('[data-edminboost-anm-preset-status]');
					if (status) {
						status.textContent = toggle.checked ? 'ENABLED' : 'DISABLED';
						status.classList.toggle('is-active', toggle.checked);
						status.classList.toggle('is-inactive', !toggle.checked);
					}
					var blacklist = document.querySelector('[data-edminboost-anm-preset-blacklist]');
					if (blacklist) {
						if (toggle.checked) {
							var count = result.data && typeof result.data.count === 'number' ? result.data.count : 0;
							var label = (data.i18n && data.i18n.presetActiveBlacklist) || 'Active blacklist: %d phrases';
							blacklist.textContent = '( ' + label.replace('%d', String(count)) + ' )';
							blacklist.hidden = false;
						} else {
							blacklist.textContent = '';
							blacklist.hidden = true;
						}
					}
				});
		});
	}

	function syncRulesListEmptyState(root) {
		var listCard = root.querySelector('[data-edminboost-anm-rules-list]');
		if (!listCard) {
			return;
		}

		var tbody = listCard.querySelector('tbody');
		if (!tbody || tbody.querySelector('tr')) {
			return;
		}

		var i18n = data.i18n || {};
		listCard.innerHTML = '<div class="edminboost-anm-empty"><p>'
			+ anmPreviewEscapeHtml(i18n.rulesEmpty || 'No auto-rules yet. Add a rule or convert an inbox notice to get started.')
			+ '</p></div>';
	}

	function initRuleModal() {
		var root = document.querySelector('[data-edminboost-anm-rules]');
		if (root && root.getAttribute('data-edminboost-anm-rules-readonly') === '1') {
			return;
		}
		if (!root) {
			return;
		}

		var modal = document.getElementById('edminboost-anm-rule-modal');
		var form = modal ? modal.querySelector('.edminboost-anm-rule-form') : null;
		var title = document.getElementById('edminboost-anm-rule-modal-title');
		var scheduleEnabledField = document.getElementById('edminboost-anm-rule-schedule-enabled');
		var scheduleFieldsWrap = document.getElementById('edminboost-anm-rule-schedule-fields');
		var scheduleStartField = document.getElementById('edminboost-anm-rule-schedule-start');
		var scheduleEndField = document.getElementById('edminboost-anm-rule-schedule-end');
		var i18n = data.i18n || {};

		function syncRuleScheduleFieldsVisibility() {
			if (!scheduleFieldsWrap || !scheduleEnabledField) {
				return;
			}
			var show = scheduleEnabledField.checked;
			scheduleFieldsWrap.hidden = !show;
		}

		function applyRuleScheduleToForm(schedule) {
			var normalized = schedule && typeof schedule === 'object' ? schedule : {};
			var enabled = !!normalized.enabled;
			var days = Array.isArray(normalized.days) ? normalized.days : [];
			var start = normalized.start || '09:00';
			var end = normalized.end || '17:00';

			if (scheduleEnabledField) {
				scheduleEnabledField.checked = enabled;
			}
			if (form) {
				form.querySelectorAll('input[name="schedule_days[]"]').forEach(function (checkbox) {
					checkbox.checked = days.indexOf(checkbox.value) !== -1;
				});
			}
			if (scheduleStartField) {
				scheduleStartField.value = start;
			}
			if (scheduleEndField) {
				scheduleEndField.value = end;
			}
			syncRuleScheduleFieldsVisibility();
		}

		function readRuleScheduleFromForm() {
			var days = [];
			if (form) {
				form.querySelectorAll('input[name="schedule_days[]"]:checked').forEach(function (checkbox) {
					days.push(checkbox.value);
				});
			}
			return {
				enabled: scheduleEnabledField ? scheduleEnabledField.checked : false,
				days: days,
				start: scheduleStartField ? scheduleStartField.value : '09:00',
				end: scheduleEndField ? scheduleEndField.value : '17:00'
			};
		}

		if (scheduleEnabledField) {
			scheduleEnabledField.addEventListener('change', syncRuleScheduleFieldsVisibility);
		}

		function openModal(editData) {
			if (!modal || !form) {
				return;
			}
			form.reset();
			document.getElementById('edminboost-anm-rule-id').value = editData.id || '';
			document.getElementById('edminboost-anm-rule-name').value = editData.name || '';
			document.getElementById('edminboost-anm-rule-string').value = editData.matchString || '';
			document.getElementById('edminboost-anm-rule-action').value = editData.ruleAction || 'auto_dismiss_inbox';

			var matchType = editData.matchType || 'keyword';
			form.querySelectorAll('input[name="match_type"]').forEach(function (radio) {
				radio.checked = radio.value === matchType;
			});

			var schedule = editData.schedule;
			if (typeof schedule === 'string' && schedule) {
				try {
					schedule = JSON.parse(schedule);
				} catch (parseError) {
					schedule = null;
				}
			}
			applyRuleScheduleToForm(schedule);

			if (title) {
				title.textContent = editData.id ? (data.i18n && data.i18n.editRule) || 'Edit Filter Rule' : (data.i18n && data.i18n.createRule) || 'Create New Filter Rule';
			}

			modal.classList.add('is-open');
			modal.setAttribute('aria-hidden', 'false');

			var nameField = document.getElementById('edminboost-anm-rule-name');
			if (nameField && typeof nameField.focus === 'function') {
				nameField.focus();
			}
		}

		function closeModal() {
			if (modal) {
				modal.classList.remove('is-open');
				modal.setAttribute('aria-hidden', 'true');
			}
		}

		function onRuleModalKeydown(event) {
			if (event.key !== 'Escape' || !modal || !modal.classList.contains('is-open')) {
				return;
			}
			if (anmConfirmModalState.openOptions) {
				return;
			}
			closeModal();
		}

		document.addEventListener('keydown', onRuleModalKeydown);

		root.querySelectorAll('.edminboost-anm-add-rule').forEach(function (btn) {
			btn.addEventListener('click', function () {
				openModal({});
			});
		});

		root.querySelectorAll('.edminboost-anm-edit-rule').forEach(function (btn) {
			btn.addEventListener('click', function () {
				openModal({
					id: btn.dataset.id,
					name: btn.dataset.name,
					matchType: btn.dataset.matchType,
					matchString: btn.dataset.matchString,
					ruleAction: btn.dataset.ruleAction,
					schedule: btn.dataset.schedule
				});
			});
		});

		root.querySelectorAll('.edminboost-anm-delete-rule').forEach(function (link) {
			link.addEventListener('click', function (event) {
				event.preventDefault();
				openAnmConfirmModal({
					title: i18n.confirmDeleteTitle || 'Delete rule',
					message: i18n.confirmDelete || 'Are you sure you want to delete this rule?',
					confirmText: i18n.confirmDeleteButton || 'Delete',
					onConfirm: function () {
						return apiPost('edminboost_anm_delete_rule', withRulesContext({ id: link.dataset.id })).then(function (result) {
							if (!result.success) {
								window.alert((result.data && result.data.message) || i18n.actionError || 'Action failed.');
								return;
							}
							var row = link.closest('tr');
							var presetId = (result.data && result.data.wp_notice_preset) || (row && row.dataset.wpNoticePreset);
							if (row) {
								row.remove();
							}
							if (presetId) {
								var presetRow = root.querySelector('[data-preset-id="' + presetId + '"]');
								unmarkWpPresetConverted(presetRow);
							}
							syncRulesListEmptyState(root);
						});
					}
				});
			});
		});

		root.querySelectorAll('.edminboost-anm-rule-toggle').forEach(function (toggle) {
			toggle.addEventListener('change', function () {
				apiPost('edminboost_anm_toggle_rule', withRulesContext({
					id: toggle.dataset.id,
					enabled: toggle.checked ? '1' : '0'
				})).then(function (result) {
					if (!result.success) {
						toggle.checked = !toggle.checked;
					}
				});
			});
		});

		if (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();
				var matchTypeEl = form.querySelector('input[name="match_type"]:checked');
				var schedulePayload = readRuleScheduleFromForm();
				apiPost('edminboost_anm_save_rule', withRulesContext({
					id: document.getElementById('edminboost-anm-rule-id').value,
					name: document.getElementById('edminboost-anm-rule-name').value,
					match_type: matchTypeEl ? matchTypeEl.value : 'keyword',
					match_string: document.getElementById('edminboost-anm-rule-string').value,
					rule_action: document.getElementById('edminboost-anm-rule-action').value,
					enabled: '1',
					schedule_enabled: schedulePayload.enabled ? '1' : '0',
					schedule_start: schedulePayload.start,
					schedule_end: schedulePayload.end,
					schedule_days: schedulePayload.days
				})).then(function (result) {
					if (result.success) {
						window.location.reload();
						return;
					}
					window.alert((result.data && result.data.message) || i18n.actionError || 'Action failed.');
				});
			});
		}

		root.querySelectorAll('.edminboost-anm-modal-close, .edminboost-anm-modal__backdrop').forEach(function (el) {
			el.addEventListener('click', closeModal);
		});

		root.querySelectorAll('.edminboost-anm-wp-preset-convert').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var isConverted = btn.classList.contains('is-converted-to-rule');
				var payload = { preset_id: btn.dataset.presetId };
				if (isConverted) {
					payload.undo = '1';
				}
				apiPost('edminboost_anm_convert_wp_preset', payload).then(function (result) {
					if (!result.success) {
						window.alert((result.data && result.data.message) || i18n.actionError || 'Action failed.');
						return;
					}
					window.location.reload();
				});
			});
		});
	}

	function setConvertButtonConverted(btn, converted) {
		if (!btn) {
			return;
		}
		var convertedTooltip = (data.i18n && data.i18n.undoConversion) || 'Converted to rule. Click again to undo.';
		btn.classList.toggle('is-converted-to-rule', converted);
		btn.setAttribute('aria-pressed', converted ? 'true' : 'false');
		if (converted) {
			btn.setAttribute('title', convertedTooltip);
		} else {
			btn.removeAttribute('title');
		}
	}

	function markWpPresetConverted(row) {
		setConvertButtonConverted(row ? row.querySelector('.edminboost-anm-wp-preset-convert') : null, true);
	}

	function unmarkWpPresetConverted(row) {
		setConvertButtonConverted(row ? row.querySelector('.edminboost-anm-wp-preset-convert') : null, false);
	}

	function markInboxConverted(row) {
		setConvertButtonConverted(row ? row.querySelector('.edminboost-anm-inbox-convert') : null, true);
	}

	function unmarkInboxConverted(row) {
		setConvertButtonConverted(row ? row.querySelector('.edminboost-anm-inbox-convert') : null, false);
	}

	function anmClosestInboxRow(el) {
		return el && el.closest ? el.closest('[data-notice-id]') : null;
	}

	function setInboxReadState(row, read) {
		if (!row) {
			return;
		}
		var readBtn = row.querySelector('.edminboost-anm-inbox-read');
		row.classList.toggle('is-read', read);
		var unreadDot = row.querySelector('.edminboost-anm-inbox-feed__unread');
		if (unreadDot) {
			unreadDot.hidden = !!read;
		}
		if (readBtn) {
			readBtn.classList.toggle('is-marked-read', read);
			readBtn.disabled = read;
			if (read) {
				readBtn.setAttribute('aria-disabled', 'true');
			} else {
				readBtn.removeAttribute('aria-disabled');
			}
		}
	}

	function setInboxSnoozeState(row, snoozed, durationKey) {
		if (!row) {
			return;
		}
		var snoozeTooltip = (data.i18n && data.i18n.undoSnooze) || 'Snoozed. Click again to unsnooze.';
		var toggle = row.querySelector('.edminboost-anm-inbox-snooze-toggle');
		if (toggle) {
			toggle.classList.toggle('is-snoozed', snoozed);
			toggle.setAttribute('aria-pressed', snoozed ? 'true' : 'false');
			if (snoozed) {
				toggle.setAttribute('title', snoozeTooltip);
			} else {
				toggle.removeAttribute('title');
			}
			var daysEl = toggle.querySelector('.edminboost-anm-inbox-snooze-toggle__days');
			if (daysEl) {
				if (snoozed && durationKey) {
					daysEl.textContent = durationKey === '7d' ? '7' : '1';
					daysEl.hidden = false;
				} else {
					daysEl.textContent = '';
					daysEl.hidden = true;
				}
			}
		}
		row.querySelectorAll('.edminboost-anm-inbox-snooze').forEach(function (btn) {
			var active = snoozed && btn.dataset.duration === durationKey;
			btn.classList.toggle('is-snoozed-active', active);
			btn.setAttribute('aria-pressed', active ? 'true' : 'false');
		});
	}

	function updateInboxSortHeaders(table, orderby, order) {
		if (!table) {
			return;
		}

		table.querySelectorAll('th.sortable').forEach(function (th) {
			var link = th.querySelector('.edminboost-anm-inbox-sort');
			var column = link ? link.dataset.orderby : '';
			var isActive = column === orderby;

			th.classList.remove('sorted', 'asc', 'desc');
			th.setAttribute('aria-sort', isActive ? (order === 'asc' ? 'ascending' : 'descending') : 'none');
			if (isActive) {
				th.classList.add('sorted', order);
			}
		});
	}

	function updateInboxFeedSortLinks(root, orderby, order) {
		if (!root) {
			return;
		}

		root.querySelectorAll('.edminboost-anm-inbox-feed__sort .edminboost-anm-inbox-sort').forEach(function (link) {
			var isActive = (link.dataset.orderby || '') === orderby;
			link.classList.toggle('is-active', isActive);
			link.setAttribute('aria-current', isActive ? 'true' : 'false');
			link.classList.remove('is-sort-asc', 'is-sort-desc');
			if (isActive) {
				link.classList.add('desc' === order ? 'is-sort-desc' : 'is-sort-asc');
			}
		});
	}

	function syncInboxFeedEmptyState(root, hasRows) {
		if (!root || !root.classList.contains('edminboost-anm-wrap--inbox-feed')) {
			return;
		}
		var empty = root.querySelector('.edminboost-anm-inbox-feed__empty');
		var list = root.querySelector('#edminboost-anm-inbox-rows');
		if (!list) {
			return;
		}
		if (hasRows) {
			list.hidden = false;
			list.setAttribute('aria-hidden', 'false');
			if (empty) {
				empty.hidden = true;
				empty.setAttribute('aria-hidden', 'true');
			}
		}
	}

	function syncInboxSearchHiddenInput(searchForm, name, value, removeWhen) {
		if (!searchForm) {
			return;
		}
		var input = searchForm.querySelector('input[name="' + name + '"]');
		if (removeWhen) {
			if (input) {
				input.remove();
			}
			return;
		}
		if (!input) {
			input = document.createElement('input');
			input.type = 'hidden';
			input.name = name;
			searchForm.appendChild(input);
		}
		input.value = value;
	}

	function updateInboxUrlState(root, orderby, order, page) {
		page = parseInt(page, 10) || 1;
		var params = new URLSearchParams(window.location.search);
		if ('date' === orderby && 'desc' === order) {
			params.delete('orderby');
			params.delete('order');
		} else {
			params.set('orderby', orderby);
			params.set('order', order);
		}
		if (page <= 1) {
			params.delete('paged');
		} else {
			params.set('paged', String(page));
		}
		var query = params.toString();
		var nextUrl = window.location.pathname + (query ? '?' + query : '') + window.location.hash;
		window.history.replaceState({}, '', nextUrl);

		var searchForm = root.querySelector('.edminboost-anm-search');
		syncInboxSearchHiddenInput(searchForm, 'orderby', orderby, 'date' === orderby && 'desc' === order);
		syncInboxSearchHiddenInput(searchForm, 'order', order, 'date' === orderby && 'desc' === order);
		syncInboxSearchHiddenInput(searchForm, 'paged', String(page), page <= 1);
	}

	function resetInboxRowSelection(root) {
		root.querySelectorAll('.edminboost-anm-inbox-select-all').forEach(function (cb) {
			cb.checked = false;
		});
	}

	function closeInboxDropdowns(root) {
		root.querySelectorAll('.edminboost-anm-dropdown.is-open').forEach(function (dropdown) {
			dropdown.classList.remove('is-open');
			var toggle = dropdown.querySelector('.edminboost-anm-dropdown__toggle');
			if (toggle) {
				toggle.setAttribute('aria-expanded', 'false');
			}
		});
	}

	function fetchInboxRows(root, orderby, order) {
		var tbody = root.querySelector('#edminboost-anm-inbox-rows');
		var table = root.querySelector('.edminboost-anm-inbox-table');
		if (!tbody) {
			return;
		}

		tbody.classList.add('is-loading');

		apiPost('edminboost_anm_inbox_list', {
			filter: root.dataset.inboxFilter || 'all',
			s: root.dataset.inboxSearch || '',
			paged: root.dataset.inboxPage || '1',
			orderby: orderby,
			order: order
		}).then(function (result) {
			tbody.classList.remove('is-loading');
			if (!result.success) {
				window.alert((result.data && result.data.message) || (data.i18n && data.i18n.actionError) || 'Action failed.');
				return;
			}
			tbody.innerHTML = result.data.rows || '';
			syncInboxFeedEmptyState(root, Boolean((result.data.rows || '').trim()));
			root.dataset.inboxOrderby = result.data.orderby || orderby;
			root.dataset.inboxOrder = result.data.order || order;
			if (result.data.page) {
				root.dataset.inboxPage = String(result.data.page);
			}
			updateInboxSortHeaders(table, root.dataset.inboxOrderby, root.dataset.inboxOrder);
			updateInboxFeedSortLinks(root, root.dataset.inboxOrderby, root.dataset.inboxOrder);
			updateInboxUrlState(
				root,
				root.dataset.inboxOrderby,
				root.dataset.inboxOrder,
				root.dataset.inboxPage || '1'
			);
			resetInboxRowSelection(root);
			closeInboxDropdowns(root);
			var pager = root.querySelector('[data-edminboost-anm-inbox-pager]');
			if (pager && typeof result.data.pager === 'string') {
				pager.innerHTML = result.data.pager;
			}
			var showing = root.querySelector('[data-edminboost-anm-inbox-showing]');
			if (showing && result.data.showing) {
				showing.textContent = result.data.showing;
			}
		});
	}

	function initInboxKeyboardShortcut() {
		if (!data.inboxKeyboardShortcut || !data.inboxUrl) {
			return;
		}
		document.addEventListener('keydown', function (event) {
			if (!event.altKey || !event.shiftKey || 'KeyN' !== event.code) {
				return;
			}
			var tag = (event.target && event.target.tagName) ? event.target.tagName.toLowerCase() : '';
			if ('input' === tag || 'textarea' === tag || 'select' === tag || (event.target && event.target.isContentEditable)) {
				return;
			}
			event.preventDefault();
			window.location.href = data.inboxUrl;
		});
	}

	function initInboxListKeyboardNav() {
		var root = document.querySelector('[data-edminboost-anm-inbox]');
		if (!root) {
			return;
		}
		var focusIndex = -1;

		function getRows() {
			return Array.prototype.slice.call(root.querySelectorAll('#edminboost-anm-inbox-rows [data-notice-id]'));
		}

		function focusRow(index) {
			var rows = getRows();
			if (!rows.length) {
				return;
			}
			focusIndex = Math.max(0, Math.min(index, rows.length - 1));
			var btn = rows[focusIndex].querySelector('.edminboost-anm-inbox-read, .edminboost-anm-inbox-snooze-toggle, .edminboost-anm-inbox-convert');
			if (btn) {
				btn.focus();
			}
		}

		root.addEventListener('keydown', function (event) {
			if (!root.contains(document.activeElement) && document.activeElement !== document.body) {
				return;
			}
			var rows = getRows();
			if (!rows.length) {
				return;
			}
			if ('ArrowDown' === event.key || 'j' === event.key) {
				event.preventDefault();
				focusRow(focusIndex + 1);
			} else if ('ArrowUp' === event.key || 'k' === event.key) {
				event.preventDefault();
				focusRow(focusIndex <= 0 ? 0 : focusIndex - 1);
			} else if ('Escape' === event.key) {
				closeInboxDropdowns(root);
			}
		});
	}

	function initInboxTable() {
		var root = document.querySelector('[data-edminboost-anm-inbox]');
		if (!root) {
			return;
		}

		var table = root.querySelector('.edminboost-anm-inbox-table');
		updateInboxSortHeaders(table, root.dataset.inboxOrderby || 'date', root.dataset.inboxOrder || 'desc');
		updateInboxFeedSortLinks(root, root.dataset.inboxOrderby || 'date', root.dataset.inboxOrder || 'desc');

		root.addEventListener('change', function (event) {
			if (event.target.classList.contains('edminboost-anm-inbox-select-all')) {
				root.querySelectorAll('.edminboost-anm-notice-check').forEach(function (cb) {
					cb.checked = event.target.checked;
				});
				root.querySelectorAll('.edminboost-anm-inbox-select-all').forEach(function (cb) {
					cb.checked = event.target.checked;
				});
			}
		});

		document.addEventListener('click', function (event) {
			if (!root.contains(event.target)) {
				closeInboxDropdowns(root);
			}
		});

		root.addEventListener('click', function (event) {
			var pageLink = event.target.closest('.edminboost-anm-inbox-page-link');
			if (pageLink && root.contains(pageLink)) {
				event.preventDefault();
				var nextPage = pageLink.dataset.page || '1';
				root.dataset.inboxPage = nextPage;
				fetchInboxRows(root, root.dataset.inboxOrderby || 'date', root.dataset.inboxOrder || 'desc');
				return;
			}

			var sortLink = event.target.closest('.edminboost-anm-inbox-sort');
			if (sortLink) {
				event.preventDefault();
				var orderby = sortLink.dataset.orderby;
				var currentOrderby = root.dataset.inboxOrderby || 'date';
				var currentOrder = root.dataset.inboxOrder || 'desc';
				var order = 'asc';
				if (orderby === currentOrderby) {
					order = 'asc' === currentOrder ? 'desc' : 'asc';
				} else if ('date' === orderby) {
					order = 'desc';
				}
				root.dataset.inboxPage = '1';
				fetchInboxRows(root, orderby, order);
				return;
			}

			var dropdownToggle = event.target.closest('.edminboost-anm-inbox-snooze-toggle');
			if (dropdownToggle) {
				event.preventDefault();
				if (dropdownToggle.classList.contains('is-snoozed')) {
					apiPost('edminboost_anm_inbox_action', {
						inbox_action: 'unsnooze',
						id: dropdownToggle.dataset.id
					}).then(function (result) {
						if (result.success) {
							closeInboxDropdowns(root);
							setInboxSnoozeState(anmClosestInboxRow(dropdownToggle), false, '');
						}
					});
					return;
				}
				var dropdown = dropdownToggle.closest('.edminboost-anm-dropdown');
				if (dropdown) {
					var willOpen = !dropdown.classList.contains('is-open');
					closeInboxDropdowns(root);
					if (willOpen) {
						dropdown.classList.add('is-open');
						dropdownToggle.setAttribute('aria-expanded', 'true');
					}
				}
				return;
			}

			var readBtn = event.target.closest('.edminboost-anm-inbox-read');
			if (readBtn) {
				if (readBtn.disabled) {
					return;
				}
				apiPost('edminboost_anm_inbox_action', { inbox_action: 'read', id: readBtn.dataset.id }).then(function (result) {
					if (result.success) {
						setInboxReadState(anmClosestInboxRow(readBtn), true);
						return;
					}
					window.alert((result.data && result.data.message) || (data.i18n && data.i18n.actionError) || 'Action failed.');
				});
				return;
			}

			var snoozeBtn = event.target.closest('.edminboost-anm-inbox-snooze');
			if (snoozeBtn) {
				var snoozeRow = anmClosestInboxRow(snoozeBtn);
				apiPost('edminboost_anm_inbox_action', {
					inbox_action: 'snooze',
					id: snoozeBtn.dataset.id,
					duration: snoozeBtn.dataset.duration
				}).then(function (result) {
					if (!result.success) {
						return;
					}
					closeInboxDropdowns(root);
					var payload = result.data || {};
					setInboxSnoozeState(snoozeRow, !!payload.snoozed, payload.duration || '');
				});
				return;
			}

			var convertBtn = event.target.closest('.edminboost-anm-inbox-convert');
			if (convertBtn) {
				var inboxConverted = convertBtn.classList.contains('is-converted-to-rule');
				var inboxPayload = {
					inbox_action: inboxConverted ? 'unconvert' : 'convert',
					id: convertBtn.dataset.id
				};
				apiPost('edminboost_anm_inbox_action', inboxPayload).then(function (result) {
					if (!result.success) {
						window.alert((result.data && result.data.message) || (data.i18n && data.i18n.actionError) || 'Action failed.');
						return;
					}
					var row = anmClosestInboxRow(convertBtn);
					if (inboxConverted) {
						unmarkInboxConverted(row);
					} else {
						markInboxConverted(row);
					}
				});
				return;
			}

			var bulkApply = event.target.closest('.edminboost-anm-bulk-apply');
			if (bulkApply) {
				var actionEl = document.getElementById('edminboost-anm-bulk-action');
				var action = actionEl ? actionEl.value : '';
				if (!action) {
					return;
				}
				var ids = [];
				root.querySelectorAll('.edminboost-anm-notice-check:checked').forEach(function (cb) {
					ids.push(cb.value);
				});
				if (!ids.length) {
					return;
				}
				var bulkDuration = '1d';
				if ('snooze_7d' === action) {
					action = 'snooze';
					bulkDuration = '7d';
				}
				var inboxPayload = {
					inbox_action: 'bulk',
					bulk_action: action,
					ids: ids
				};
				if ('snooze' === action) {
					inboxPayload.duration = bulkDuration;
				}
				apiPost('edminboost_anm_inbox_action', inboxPayload).then(function (result) {
					if (result.success) {
						window.location.reload();
					}
				});
			}
		});
	}

	function initDebugTools() {
		var root = document.querySelector('[data-edminboost-anm-debug]');
		if (!root) {
			return;
		}

		var feedback = root.querySelector('[data-edminboost-anm-debug-feedback]');
		var i18n = data.i18n || {};

		function showFeedback(message, isError) {
			if (!feedback) {
				return;
			}
			feedback.hidden = false;
			feedback.textContent = message;
			feedback.classList.toggle('is-error', !!isError);
		}

		function getSelectedNoticeTypes() {
			var inputs = root.querySelectorAll('.edminboost-anm-debug-notice-type:checked');
			return Array.prototype.map.call(inputs, function (input) {
				return input.getAttribute('data-notice-type') || input.value;
			}).filter(function (slug) {
				return !!slug;
			});
		}

		function buildDebugNoticePayload(noticeTypes) {
			return {
				notice_types: noticeTypes,
				notice_types_csv: noticeTypes.join(','),
				notice_types_json: JSON.stringify(noticeTypes)
			};
		}

		function requireNoticeTypes() {
			var types = getSelectedNoticeTypes();
			if (types.length) {
				return types;
			}
			showFeedback(i18n.selectNoticeLevels || 'Select at least one notice level.', true);
			return null;
		}

		var seedBtn = root.querySelector('.edminboost-anm-seed-test-notices');
		if (seedBtn) {
			seedBtn.addEventListener('click', function () {
				var noticeTypes = requireNoticeTypes();
				if (!noticeTypes) {
					return;
				}
				seedBtn.disabled = true;
				apiPost('edminboost_anm_seed_test_notices', buildDebugNoticePayload(noticeTypes)).then(function (result) {
					seedBtn.disabled = false;
					if (!result.success) {
						showFeedback((result.data && result.data.message) || i18n.seedError || 'Could not seed test notices.', true);
						return;
					}
					var added = result.data.added || 0;
					var skipped = result.data.skipped || 0;
					var message = (i18n.seedSuccess || 'Added %1$d test notices to the inbox (%2$d skipped as duplicates).')
						.replace('%1$d', String(added))
						.replace('%2$d', String(skipped));
					showFeedback(message, false);
					if (result.data.inboxUrl && window.confirm(message + '\n\n' + (i18n.viewInbox || 'View Inbox') + '?')) {
						window.location.href = result.data.inboxUrl;
					}
				});
			});
		}

		var triggerBtn = root.querySelector('.edminboost-anm-trigger-test-notices');
		if (triggerBtn) {
			triggerBtn.addEventListener('click', function () {
				var noticeTypes = requireNoticeTypes();
				if (!noticeTypes) {
					return;
				}
				triggerBtn.disabled = true;
				apiPost('edminboost_anm_trigger_test_notices', buildDebugNoticePayload(noticeTypes)).then(function (result) {
					triggerBtn.disabled = false;
					if (!result.success) {
						showFeedback((result.data && result.data.message) || i18n.triggerError || 'Could not trigger live test notices.', true);
						return;
					}
					if (result.data.redirect) {
						window.location.href = result.data.redirect;
					}
				});
			});
		}
	}

	function initNotificationChannelsMatrix() {
		var root = document.querySelector('[data-edminboost-anm-notification-channels]');
		if (!root) {
			return;
		}

		function cellsForGroup(groupId) {
			return root.querySelectorAll(
				'.edminboost-anm-notification-channels-cell[data-group="' + groupId + '"]:not(:disabled)'
			);
		}

		function cellsForChannel(channel) {
			return root.querySelectorAll(
				'.edminboost-anm-notification-channels-cell[data-channel="' + channel + '"]:not(:disabled)'
			);
		}

		function syncGroupToggle(groupId) {
			var toggle = root.querySelector(
				'.edminboost-anm-notification-channels-select-group[data-group="' + groupId + '"]'
			);
			if (!toggle) {
				return;
			}
			var cells = cellsForGroup(groupId);
			if (!cells.length) {
				return;
			}
			var checkedCount = 0;
			cells.forEach(function (cell) {
				if (cell.checked) {
					checkedCount += 1;
				}
			});
			toggle.checked = checkedCount === cells.length;
			toggle.indeterminate = checkedCount > 0 && checkedCount < cells.length;
		}

		function syncColumnToggle(channel) {
			var toggles = root.querySelectorAll(
				'.edminboost-anm-notification-channels-select-column[data-channel="' + channel + '"]:not(:disabled)'
			);
			var cells = cellsForChannel(channel);
			if (!cells.length || !toggles.length) {
				return;
			}
			var checkedCount = 0;
			cells.forEach(function (cell) {
				if (cell.checked) {
					checkedCount += 1;
				}
			});
			var allOn = checkedCount === cells.length;
			var someOn = checkedCount > 0 && checkedCount < cells.length;
			toggles.forEach(function (toggle) {
				toggle.checked = allOn;
				toggle.indeterminate = someOn;
			});
		}

		root.querySelectorAll('.edminboost-anm-notification-channels-select-group').forEach(function (groupToggle) {
			groupToggle.addEventListener('change', function () {
				var groupId = groupToggle.dataset.group;
				var on = groupToggle.checked;
				groupToggle.indeterminate = false;
				cellsForGroup(groupId).forEach(function (cell) {
					cell.checked = on;
				});
				['admin', 'email', 'webhook'].forEach(syncColumnToggle);
			});
		});

		root.querySelectorAll('.edminboost-anm-notification-channels-select-column').forEach(function (columnToggle) {
			columnToggle.addEventListener('change', function () {
				var channel = columnToggle.dataset.channel;
				var on = columnToggle.checked;
				root.querySelectorAll(
					'.edminboost-anm-notification-channels-select-column[data-channel="' + channel + '"]:not(:disabled)'
				).forEach(function (peer) {
					peer.checked = on;
					peer.indeterminate = false;
				});
				cellsForChannel(channel).forEach(function (cell) {
					cell.checked = on;
				});
				root.querySelectorAll('.edminboost-anm-notification-channels-select-group').forEach(function (groupToggle) {
					syncGroupToggle(groupToggle.dataset.group);
				});
			});
		});

		root.querySelectorAll('.edminboost-anm-notification-channels-cell').forEach(function (cell) {
			cell.addEventListener('change', function () {
				syncGroupToggle(cell.dataset.group);
				syncColumnToggle(cell.dataset.channel);
			});
		});

		root.querySelectorAll('.edminboost-anm-notification-channels-select-group').forEach(function (groupToggle) {
			syncGroupToggle(groupToggle.dataset.group);
		});
		['admin', 'email', 'webhook'].forEach(syncColumnToggle);
	}

	function initSettingsTabs() {
		var root = document.querySelector('[data-edminboost-anm-settings]');
		if (!root) {
			return;
		}

		var tablist = root.querySelector('.edminboost-anm-settings-tabs');
		if (!tablist) {
			return;
		}

		var tabs = tablist.querySelectorAll('[role="tab"]');
		var panels = root.querySelectorAll('.edminboost-anm-tab-panel');
		var settingsForms = root.querySelectorAll('.edminboost-anm-settings-form');
		var primarySettingsForm = settingsForms.length ? settingsForms[0] : null;
		var defaultTab = root.dataset.settingsTab || 'general';

		function panelForTab(slug) {
			return root.querySelector('.edminboost-anm-tab-panel[data-tab="' + slug + '"]');
		}

		function tabFromUrl() {
			var params = new URLSearchParams(window.location.search);
			var slug = params.get('tab') || defaultTab;
			if (slug === 'upsell') {
				slug = 'filtering';
			}
			return panelForTab(slug) ? slug : 'general';
		}

		function syncFormSubmitVisibility(slug) {
			if (!primarySettingsForm) {
				return;
			}
			primarySettingsForm.classList.toggle('is-tab-tools', slug === 'tools' || slug === 'multisite');
		}

		function activate(slug) {
			tabs.forEach(function (tab) {
				var isActive = tab.dataset.tab === slug;
				tab.classList.toggle('nav-tab-active', isActive);
				tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
				tab.tabIndex = isActive ? 0 : -1;
			});

			panels.forEach(function (panel) {
				panel.hidden = panel.dataset.tab !== slug;
			});

			syncFormSubmitVisibility(slug);

			var url = new URL(window.location.href);
			if (slug === 'general') {
				url.searchParams.delete('tab');
			} else {
				url.searchParams.set('tab', slug);
			}
			window.history.replaceState({}, '', url.toString());
			root.dataset.settingsTab = slug;

			root.dispatchEvent(
				new CustomEvent('edminboost-anm-settings-tab', {
					detail: { tab: slug }
				})
			);
		}

		tabs.forEach(function (tab) {
			tab.addEventListener('click', function () {
				activate(tab.dataset.tab);
			});

			tab.addEventListener('keydown', function (event) {
				var currentIndex = Array.prototype.indexOf.call(tabs, tab);
				var nextIndex = -1;

				if (event.key === 'ArrowRight') {
					nextIndex = (currentIndex + 1) % tabs.length;
				} else if (event.key === 'ArrowLeft') {
					nextIndex = (currentIndex - 1 + tabs.length) % tabs.length;
				} else if (event.key === 'Home') {
					nextIndex = 0;
				} else if (event.key === 'End') {
					nextIndex = tabs.length - 1;
				}

				if (nextIndex >= 0) {
					event.preventDefault();
					tabs[nextIndex].focus();
					activate(tabs[nextIndex].dataset.tab);
				}
			});
		});

		settingsForms.forEach(function (form) {
			form.addEventListener('submit', function () {
				var referer = form.querySelector('input[name="_wp_http_referer"]');
				if (!referer) {
					return;
				}
				var url = new URL(window.location.href);
				var activeTab = root.dataset.settingsTab || 'general';
				if (form.classList.contains('edminboost-anm-multisite-policy-form')) {
					activeTab = 'multisite';
				}
				if (activeTab && activeTab !== 'general') {
					url.searchParams.set('tab', activeTab);
				} else {
					url.searchParams.delete('tab');
				}
				referer.value = url.pathname + url.search;
			});
		});

		activate(tabFromUrl());
	}

	function initSettingsImporter() {
		var root = document.querySelector('[data-edminboost-anm-settings]');
		if (!root) {
			return;
		}

		var importerBtn = root.querySelector('.edminboost-anm-run-importer');
		if (importerBtn) {
			importerBtn.addEventListener('click', function () {
				importerBtn.disabled = true;
				apiPost('edminboost_anm_run_importer', {}).then(function (result) {
					importerBtn.disabled = false;
					if (result.success) {
						window.alert((data.i18n && data.i18n.importSuccess) || 'Import completed.');
						window.location.reload();
					} else {
						window.alert((result.data && result.data.message) || (data.i18n && data.i18n.importError) || 'Import failed.');
					}
				});
			});
		}

		var importForm = root.querySelector('.edminboost-anm-import-form');
		if (importForm) {
			importForm.addEventListener('submit', function (event) {
				event.preventDefault();
				var fileInput = document.getElementById('edminboost-anm-import-file');
				if (!fileInput || !fileInput.files.length) {
					return;
				}
				var body = new FormData();
				body.append('action', 'edminboost_anm_import_rules');
				body.append('nonce', data.nonce || '');
				body.append('import_file', fileInput.files[0]);

				fetch(data.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: body
				}).then(function (response) {
					return response.json();
				}).then(function (result) {
					if (result.success) {
						window.alert((data.i18n && data.i18n.importSuccess) || 'Import completed.');
						window.location.reload();
					} else {
						window.alert((result.data && result.data.message) || (data.i18n && data.i18n.importError) || 'Import failed.');
					}
				});
			});
		}
	}

	function initHidingNotificationsSettings() {
		var root = document.querySelector('[data-edminboost-anm-hiding-visibility]');
		if (!root) {
			return;
		}

		var pickers = root.querySelectorAll('[data-edminboost-anm-user-picker]');
		var modeRadios = root.querySelectorAll('input[type="radio"][name*="[hiding_notifications][mode]"]');
		var optionNameMatch = root.querySelector('input[type="radio"][name*="[hiding_notifications][mode]"]');
		var usersFieldName = optionNameMatch ? optionNameMatch.name.replace('[mode]', '[users][]') : '';
		var i18n = data.i18n || {};
		var searchTimers = {};

		function activeMode() {
			var checked = root.querySelector('input[type="radio"][name*="[hiding_notifications][mode]"]:checked');
			return checked ? checked.value : 'all';
		}

		function readSelectedUsers() {
			var picker = pickers[0];
			if (!picker) {
				return [];
			}

			var users = [];
			picker.querySelectorAll('.edminboost-anm-user-picker__chip').forEach(function (chip) {
				var id = chip.getAttribute('data-user-id');
				var labelEl = chip.querySelector('.edminboost-anm-user-picker__chip-text');
				if (!id || !labelEl) {
					return;
				}
				users.push({ id: id, label: labelEl.textContent.trim() });
			});

			return users;
		}

		function renderPickers(users) {
			var mode = activeMode();

			pickers.forEach(function (picker) {
				var selected = picker.querySelector('[data-edminboost-anm-user-selected]');
				var pickerMode = picker.dataset.pickerMode;
				var isActive = mode === pickerMode;

				if (!selected) {
					return;
				}

				selected.innerHTML = '';
				users.forEach(function (user) {
					var chip = document.createElement('span');
					chip.className = 'edminboost-anm-user-picker__chip';
					chip.setAttribute('data-user-id', user.id);

					var text = document.createElement('span');
					text.className = 'edminboost-anm-user-picker__chip-text';
					text.textContent = user.label;

					var remove = document.createElement('button');
					remove.type = 'button';
					remove.className = 'edminboost-anm-user-picker__remove';
					remove.setAttribute('aria-label', i18n.removeUser || 'Remove user');
					remove.innerHTML = '&times;';

					var hidden = document.createElement('input');
					hidden.type = 'hidden';
					hidden.name = usersFieldName;
					hidden.value = user.id;
					hidden.disabled = !isActive;

					chip.appendChild(text);
					chip.appendChild(remove);
					chip.appendChild(hidden);
					selected.appendChild(chip);
				});

				picker.hidden = !isActive;

				var search = picker.querySelector('.edminboost-anm-user-picker__search');
				if (search) {
					search.disabled = !isActive;
					if (!isActive) {
						search.value = '';
					}
				}

				var results = picker.querySelector('[data-edminboost-anm-user-results]');
				if (results) {
					results.hidden = true;
					results.innerHTML = '';
				}
			});
		}

		function syncPickerVisibility() {
			renderPickers(readSelectedUsers());
		}

		function addUser(user) {
			var users = readSelectedUsers();
			if (users.some(function (item) { return item.id === String(user.id); })) {
				return;
			}
			users.push({ id: String(user.id), label: user.label });
			renderPickers(users);
		}

		function removeUser(userId) {
			var users = readSelectedUsers().filter(function (item) {
				return item.id !== String(userId);
			});
			renderPickers(users);
		}

		function searchUsers(picker, term) {
			var results = picker.querySelector('[data-edminboost-anm-user-results]');
			if (!results) {
				return;
			}

			if (term.length < 2) {
				results.hidden = true;
				results.innerHTML = '';
				return;
			}

			apiPost('edminboost_anm_search_users', { term: term }).then(function (response) {
				if (!response.success) {
					return;
				}

				var users = (response.data && response.data.users) || [];
				results.innerHTML = '';

				if (!users.length) {
					var empty = document.createElement('li');
					empty.textContent = i18n.userSearchEmpty || 'No users found.';
					results.appendChild(empty);
					results.hidden = false;
					return;
				}

				users.forEach(function (user) {
					var item = document.createElement('li');
					var button = document.createElement('button');
					button.type = 'button';
					button.textContent = user.label;
					button.addEventListener('click', function () {
						addUser(user);
						var search = picker.querySelector('.edminboost-anm-user-picker__search');
						if (search) {
							search.value = '';
						}
						results.hidden = true;
						results.innerHTML = '';
					});
					item.appendChild(button);
					results.appendChild(item);
				});

				results.hidden = false;
			});
		}

		modeRadios.forEach(function (radio) {
			radio.addEventListener('change', syncPickerVisibility);
		});

		root.addEventListener('click', function (event) {
			var removeBtn = event.target.closest('.edminboost-anm-user-picker__remove');
			if (removeBtn) {
				var chip = removeBtn.closest('.edminboost-anm-user-picker__chip');
				if (chip) {
					removeUser(chip.getAttribute('data-user-id'));
				}
			}
		});

		pickers.forEach(function (picker) {
			var search = picker.querySelector('.edminboost-anm-user-picker__search');
			if (!search) {
				return;
			}

			search.addEventListener('input', function () {
				var term = search.value.trim();
				clearTimeout(searchTimers[picker.dataset.pickerMode]);
				searchTimers[picker.dataset.pickerMode] = setTimeout(function () {
					searchUsers(picker, term);
				}, 250);
			});

			search.addEventListener('blur', function () {
				setTimeout(function () {
					var results = picker.querySelector('[data-edminboost-anm-user-results]');
					if (results) {
						results.hidden = true;
					}
				}, 150);
			});
		});

		syncPickerVisibility();
	}

	function initSettingsHelpTips() {
		var tips = document.querySelectorAll('.edminboost-anm-help-tip[data-tip]');
		if (!tips.length) {
			return;
		}

		var popup = document.createElement('div');
		popup.className = 'edminboost-anm-help-tip-popup';
		popup.setAttribute('role', 'tooltip');
		popup.hidden = true;
		document.body.appendChild(popup);

		function hideTip() {
			popup.hidden = true;
			popup.textContent = '';
		}

		function showTip(trigger) {
			var text = trigger.getAttribute('data-tip') || '';
			if (!text) {
				return;
			}

			popup.textContent = text;
			popup.hidden = false;

			var rect = trigger.getBoundingClientRect();
			var placement = trigger.getAttribute('data-tip-placement') || 'below';
			if (placement === 'left' && window.innerWidth < 782) {
				placement = 'below';
			}
			var popupRect = popup.getBoundingClientRect();
			var top;
			var left;

			if (placement === 'left') {
				top = rect.top + window.scrollY + (rect.height - popupRect.height) / 2;
				left = rect.left + window.scrollX - popupRect.width - 8;
				if (left < window.scrollX + 8) {
					left = rect.right + window.scrollX + 8;
				}
			} else {
				top = rect.bottom + window.scrollY + 6;
				left = rect.left + window.scrollX;
				var maxLeft = window.scrollX + document.documentElement.clientWidth - popupRect.width - 8;
				if (left > maxLeft) {
					left = Math.max(window.scrollX + 8, maxLeft);
				}
			}

			popup.style.top = top + 'px';
			popup.style.left = left + 'px';
		}

		tips.forEach(function (tip) {
			tip.addEventListener('click', function (event) {
				event.stopPropagation();
			});
			tip.addEventListener('mousedown', function (event) {
				event.stopPropagation();
			});
			tip.addEventListener('mouseenter', function () {
				showTip(tip);
			});
			tip.addEventListener('mouseleave', hideTip);
			tip.addEventListener('focus', function () {
				showTip(tip);
			});
			tip.addEventListener('blur', hideTip);
		});

		document.addEventListener('scroll', hideTip, true);
		window.addEventListener('resize', hideTip);
	}

	function anmReadEcomDisplayScope() {
		var wooEl = document.getElementById('edminboost-anm-ecom-scope-woocommerce');
		var eddEl = document.getElementById('edminboost-anm-ecom-scope-edd');
		var otherEl = document.getElementById('edminboost-anm-ecom-scope-other');
		if (!wooEl && !eddEl && !otherEl) {
			return {
				woocommerce: true,
				edd: true,
				other: true
			};
		}
		return {
			woocommerce: !!(wooEl && wooEl.checked),
			edd: !!(eddEl && eddEl.checked),
			other: !!(otherEl && otherEl.checked)
		};
	}

	function initDisplayExperience() {
		var hidden = document.getElementById('edminboost-anm-manage-notices-mode');
		var root = document.querySelector('[data-edminboost-anm-display-experience]');
		if (!hidden || !root) {
			return;
		}

		var presetInputs = root.querySelectorAll('.edminboost-anm-display-experience__input');
		var presetOptions = root.querySelectorAll('.edminboost-anm-display-experience__option');
		var advancedInputs = document.querySelectorAll('.edminboost-anm-display-experience-advanced__input');
		var ecomScopeRoot = document.querySelector('[data-edminboost-anm-ecom-display-scope]');
		var ecomScopeInputs = ecomScopeRoot
			? ecomScopeRoot.querySelectorAll('input[type="checkbox"]')
			: [];

		function presetSelectedForMode(mode) {
			var selected = null;
			presetInputs.forEach(function (radio) {
				if (radio.checked && anmManageModeFromPresetInput(radio) === mode) {
					selected = radio;
				}
			});
			return selected;
		}

		function syncUi(mode) {
			presetOptions.forEach(function (option) {
				var input = option.querySelector('.edminboost-anm-display-experience__input');
				option.classList.toggle('is-selected', !!(input && input.checked));
			});

			advancedInputs.forEach(function (radio) {
				radio.checked = radio.value === mode && !presetSelectedForMode(mode);
			});
		}

		function syncEcomScopeVisibility(mode) {
			if (!ecomScopeRoot) {
				return;
			}
			if (mode === 'ecom_dashboard') {
				ecomScopeRoot.removeAttribute('hidden');
			} else {
				ecomScopeRoot.setAttribute('hidden', 'hidden');
			}
		}

		function setMode(mode) {
			hidden.value = mode;
			syncUi(mode);
			syncEcomScopeVisibility(mode);
			anmRenderDisplayExperiencePreview(mode);
		}

		presetInputs.forEach(function (radio) {
			radio.addEventListener('change', function () {
				if (!radio.checked) {
					return;
				}
				setMode(anmManageModeFromPresetInput(radio));
			});
		});

		presetOptions.forEach(function (option) {
			option.addEventListener('click', function (event) {
				if (event.target.closest('.edminboost-anm-display-experience-advanced__input')) {
					return;
				}
				var input = option.querySelector('.edminboost-anm-display-experience__input');
				if (!input) {
					return;
				}
				input.checked = true;
				setMode(anmManageModeFromPresetInput(input));
			});
		});

		advancedInputs.forEach(function (radio) {
			radio.addEventListener('change', function () {
				if (!radio.checked) {
					return;
				}
				presetInputs.forEach(function (preset) {
					preset.checked = false;
				});
				setMode(radio.value);
			});
		});

		ecomScopeInputs.forEach(function (input) {
			input.addEventListener('change', function () {
				if (hidden.value === 'ecom_dashboard') {
					anmRenderDisplayExperiencePreview('ecom_dashboard');
				}
			});
		});

		[
			'edminboost-anm-notice-panel-style',
			'edminboost-anm-notice-panel-badge',
			'edminboost-anm-notice-panel-position',
			'edminboost-anm-notice-color-badge',
			'edminboost-anm-notice-color-badge-text',
			'edminboost-anm-notice-color-accent',
			'edminboost-anm-notice-color-accent-hover',
			'edminboost-anm-notice-color-bell-active'
		].forEach(function (fieldId) {
			var field = document.getElementById(fieldId);
			if (!field) {
				return;
			}
			field.addEventListener('change', function () {
				anmRenderDisplayExperiencePreview(hidden.value || '');
			});
			field.addEventListener('input', function () {
				anmRenderDisplayExperiencePreview(hidden.value || '');
			});
		});

		var settingsRoot = document.querySelector('[data-edminboost-anm-settings]');
		if (settingsRoot) {
			settingsRoot.addEventListener('edminboost-anm-settings-tab', function (event) {
				if (event.detail && event.detail.tab === 'general') {
					anmRenderDisplayExperiencePreview(hidden.value || '');
				}
			});
		}

		syncUi(hidden.value || '');
		syncEcomScopeVisibility(hidden.value || '');
		anmRenderDisplayExperiencePreview(hidden.value || '');
	}

	function initResetHiddenNotices() {
		var root = document.querySelector('[data-edminboost-anm-reset-hidden]');
		if (!root) {
			return;
		}

		var button = root.querySelector('.edminboost-anm-reset-hidden__button');
		var feedback = root.querySelector('[data-edminboost-anm-reset-hidden-feedback]');
		var i18n = data.i18n || {};

		if (!button) {
			return;
		}

		button.addEventListener('click', function () {
			var scopeInput = root.querySelector('input[name="edminboost-anm-reset-hidden-scope"]:checked');
			var scope = scopeInput ? scopeInput.value : 'current_user';

			if (scope === 'all_users' && !window.confirm(i18n.resetHiddenConfirmAll || 'Reset hidden notice preferences for every user on this site?')) {
				return;
			}

			button.disabled = true;
			apiPost('edminboost_anm_reset_hidden_notices', { scope: scope }).then(function (result) {
				button.disabled = false;
				if (!feedback) {
					return;
				}
				feedback.hidden = false;
				if (result.success) {
					feedback.textContent = (result.data && result.data.message) || i18n.resetHiddenSuccess || 'Hidden notices were reset.';
					feedback.classList.remove('is-error');
					feedback.classList.add('is-success');
					root.dispatchEvent(new CustomEvent('edminboost-anm-reset-hidden-success', { bubbles: true }));
				} else {
					feedback.textContent = (result.data && result.data.message) || i18n.resetHiddenError || 'Could not reset hidden notices.';
					feedback.classList.remove('is-success');
					feedback.classList.add('is-error');
				}
			});
		});
	}

	function initBlockAdRedirectsSettings() {
		var root = document.querySelector('[data-edminboost-anm-redirect-block]');
		if (!root) {
			return;
		}

		var tbody = root.querySelector('[data-edminboost-anm-redirect-block-rows]');
		var addBtn = root.querySelector('[data-edminboost-anm-add-redirect-block]');
		if (!tbody || !addBtn) {
			return;
		}

		function templateRow() {
			return tbody.querySelector('.edminboost-anm-redirect-block-row-template');
		}

		function visibleRows() {
			return tbody.querySelectorAll('tr:not(.edminboost-anm-redirect-block-row-template)');
		}

		function prepareClonedRow(clone) {
			clone.classList.remove('edminboost-anm-redirect-block-row-template');
			clone.hidden = false;
			clone.removeAttribute('aria-hidden');

			var input = clone.querySelector('input[type="text"]');
			if (input) {
				input.value = '';
				input.disabled = false;
				input.removeAttribute('tabindex');
				input.removeAttribute('id');
			}

			var label = clone.querySelector('label[for]');
			if (label) {
				label.removeAttribute('for');
			}

			var removeBtn = clone.querySelector('.edminboost-anm-redirect-block-remove');
			if (removeBtn) {
				removeBtn.removeAttribute('tabindex');
				removeBtn.removeAttribute('aria-hidden');
			}
		}

		function addRow() {
			var proto = templateRow();
			if (!proto) {
				return;
			}

			var clone = proto.cloneNode(true);
			prepareClonedRow(clone);
			tbody.insertBefore(clone, proto);
		}

		function ensureAtLeastOneRow() {
			if (visibleRows().length === 0) {
				addRow();
			}
		}

		addBtn.addEventListener('click', function (event) {
			event.preventDefault();
			addRow();
		});

		tbody.addEventListener('click', function (event) {
			var removeBtn = event.target.closest('.edminboost-anm-redirect-block-remove');
			if (!removeBtn) {
				return;
			}

			var row = removeBtn.closest('tr');
			if (!row || row.classList.contains('edminboost-anm-redirect-block-row-template')) {
				return;
			}

			row.remove();
			ensureAtLeastOneRow();
		});
	}

	function initDigestPreview() {
		var btn = document.getElementById('edminboost-anm-digest-preview-btn');
		var panel = document.getElementById('edminboost-anm-digest-preview');
		var settingsRoot = document.querySelector('[data-edminboost-anm-settings]');
		if (!btn || !panel) {
			return;
		}

		var toEl = panel.querySelector('[data-edminboost-anm-digest-preview-to]');
		var fromEl = panel.querySelector('[data-edminboost-anm-digest-preview-from]');
		var nextSendEl = panel.querySelector('[data-edminboost-anm-digest-preview-next-send]');
		var subjectEl = panel.querySelector('[data-edminboost-anm-digest-preview-subject]');
		var contentEl = panel.querySelector('[data-edminboost-anm-digest-preview-content]');
		var i18n = data.i18n || {};
		var refreshTimer = null;
		var loadingText = i18n.digestPreviewLoading || 'Loading preview…';

		function escapeHtml(text) {
			var div = document.createElement('div');
			div.textContent = text == null ? '' : String(text);
			return div.innerHTML;
		}

		function noticeTypeClass(type) {
			var slug = String(type || 'info').toLowerCase();
			if (slug === 'warning' || slug === 'warn') {
				return 'is-warning';
			}
			if (slug === 'error') {
				return 'is-error';
			}
			if (slug === 'success') {
				return 'is-success';
			}
			return 'is-info';
		}

		function renderDigestContent(content) {
			if (!contentEl) {
				return;
			}

			if (!content) {
				contentEl.className = 'edminboost-anm-digest-preview__content is-error';
				contentEl.textContent = i18n.digestPreviewError || 'Could not load digest preview.';
				return;
			}

			var html = '';
			html += '<p class="edminboost-anm-digest-preview__intro">' + escapeHtml(content.intro) + '</p>';

			if (content.stats) {
				html += '<section class="edminboost-anm-digest-preview__section">';
				html += '<h5 class="edminboost-anm-digest-preview__section-title">' + escapeHtml(i18n.digestPreviewAtGlance || 'At a glance') + '</h5>';
				html += '<ul class="edminboost-anm-digest-preview__stats">';
				html += '<li class="edminboost-anm-digest-preview__stat"><span class="edminboost-anm-digest-preview__stat-value">' + escapeHtml(content.stats.monthly_blocked) + '</span><span class="edminboost-anm-digest-preview__stat-label">' + escapeHtml(i18n.digestPreviewBlocked || 'Blocked this month') + '</span></li>';
				html += '<li class="edminboost-anm-digest-preview__stat"><span class="edminboost-anm-digest-preview__stat-value">' + escapeHtml(content.stats.active_rules) + '</span><span class="edminboost-anm-digest-preview__stat-label">' + escapeHtml(i18n.digestPreviewRules || 'Active auto-rules') + '</span></li>';
				html += '</ul></section>';
			}

			if (content.recent) {
				html += '<section class="edminboost-anm-digest-preview__section">';
				html += '<h5 class="edminboost-anm-digest-preview__section-title">' + escapeHtml(i18n.digestPreviewRecent || 'Recent intercepted notices') + '</h5>';
				if (content.recent.empty) {
					html += '<p class="edminboost-anm-digest-preview__empty">' + escapeHtml(i18n.digestPreviewNoNotices || 'No intercepted notices in the inbox yet.') + '</p>';
				} else {
					html += '<ul class="edminboost-anm-digest-preview__notice-list">';
					(content.recent.items || []).forEach(function (item) {
						html += '<li class="edminboost-anm-digest-preview__notice">';
						html += '<p class="edminboost-anm-digest-preview__notice-source"><span>' + escapeHtml(item.source) + '</span>';
						html += '<span class="edminboost-anm-digest-preview__notice-type ' + noticeTypeClass(item.type) + '">' + escapeHtml(item.type || 'info') + '</span></p>';
						html += '<p class="edminboost-anm-digest-preview__notice-message">' + escapeHtml(item.message) + '</p>';
						html += '</li>';
					});
					html += '</ul>';
				}
				html += '</section>';
			}

			if (content.footer) {
				html += '<p class="edminboost-anm-digest-preview__footer">' + escapeHtml(content.footer) + '</p>';
			}

			contentEl.className = 'edminboost-anm-digest-preview__content';
			contentEl.innerHTML = html;
		}

		function fieldValue(id) {
			var el = document.getElementById(id);
			return el ? el.value : '';
		}

		function fieldChecked(id) {
			var el = document.getElementById(id);
			return el ? el.checked : false;
		}

		function setLoadingState() {
			if (toEl) {
				toEl.textContent = loadingText;
			}
			if (fromEl) {
				fromEl.textContent = '';
			}
			if (nextSendEl) {
				nextSendEl.textContent = '';
			}
			if (subjectEl) {
				subjectEl.textContent = '';
			}
			if (contentEl) {
				contentEl.className = 'edminboost-anm-digest-preview__content is-loading';
				contentEl.textContent = loadingText;
			}
		}

		function updateNextSendPreview(nextSend) {
			if (!nextSendEl) {
				return;
			}
			if (nextSend) {
				nextSendEl.textContent = nextSend;
				return;
			}
			nextSendEl.textContent = fieldChecked('edminboost-anm-digest-enabled')
				? ''
				: (i18n.digestPreviewNextSendDisabled || 'Enable the digest to schedule sends.');
		}

		function loadDigestPreview() {
			var body = new FormData();
			body.append('action', 'edminboost_anm_digest_preview');
			body.append('nonce', data.nonce || '');
			body.append('digest[recipients]', fieldValue('edminboost-anm-digest-recipients'));
			body.append('digest[subject]', fieldValue('edminboost-anm-digest-subject'));
			body.append('digest[intro]', fieldValue('edminboost-anm-digest-intro'));
			body.append('digest[footer]', fieldValue('edminboost-anm-digest-footer'));
			body.append('digest[recent_notices_count]', fieldValue('edminboost-anm-digest-recent-count'));
			body.append('digest[send_weekday]', fieldValue('edminboost-anm-digest-send-weekday'));

			if (fieldChecked('edminboost-anm-digest-enabled')) {
				body.append('digest[enabled]', '1');
			}
			if (fieldChecked('edminboost-anm-digest-include-stats')) {
				body.append('digest[include_stats]', '1');
			}
			if (fieldChecked('edminboost-anm-digest-include-recent')) {
				body.append('digest[include_recent_notices]', '1');
			}

			btn.disabled = true;
			setLoadingState();

			return fetch(data.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: body
			}).then(function (response) {
				return response.json();
			}).then(function (result) {
				btn.disabled = false;
				if (!result.success || !result.data) {
					if (toEl) {
						toEl.textContent = '';
					}
					if (fromEl) {
						fromEl.textContent = '';
					}
					if (nextSendEl) {
						nextSendEl.textContent = '';
					}
					if (subjectEl) {
						subjectEl.textContent = '';
					}
					if (contentEl) {
						contentEl.className = 'edminboost-anm-digest-preview__content is-error';
						contentEl.textContent = (result.data && result.data.message) || i18n.digestPreviewError || 'Could not load digest preview.';
					}
					return;
				}

				if (toEl) {
					toEl.textContent = result.data.to || '';
				}
				if (fromEl) {
					fromEl.textContent = result.data.from || '';
				}
				updateNextSendPreview(result.data.next_send || '');
				if (subjectEl) {
					subjectEl.textContent = result.data.subject || '';
				}
				renderDigestContent(result.data.content);
			}).catch(function () {
				btn.disabled = false;
				if (toEl) {
					toEl.textContent = '';
				}
				if (fromEl) {
					fromEl.textContent = '';
				}
				if (nextSendEl) {
					nextSendEl.textContent = '';
				}
				if (subjectEl) {
					subjectEl.textContent = '';
				}
				if (contentEl) {
					contentEl.className = 'edminboost-anm-digest-preview__content is-error';
					contentEl.textContent = i18n.digestPreviewError || 'Could not load digest preview.';
				}
			});
		}

		function scheduleDigestPreviewRefresh() {
			clearTimeout(refreshTimer);
			refreshTimer = setTimeout(loadDigestPreview, 400);
		}

		btn.addEventListener('click', loadDigestPreview);

		[
			'edminboost-anm-digest-recipients',
			'edminboost-anm-digest-send-weekday',
			'edminboost-anm-digest-subject',
			'edminboost-anm-digest-intro',
			'edminboost-anm-digest-footer',
			'edminboost-anm-digest-recent-count',
			'edminboost-anm-digest-enabled',
			'edminboost-anm-digest-include-stats',
			'edminboost-anm-digest-include-recent'
		].forEach(function (fieldId) {
			var field = document.getElementById(fieldId);
			if (!field) {
				return;
			}
			field.addEventListener('input', scheduleDigestPreviewRefresh);
			field.addEventListener('change', scheduleDigestPreviewRefresh);
		});

		if (settingsRoot) {
			settingsRoot.addEventListener('edminboost-anm-settings-tab', function (event) {
				if (event.detail && event.detail.tab === 'notifications') {
					loadDigestPreview();
				}
			});
		}
	}

	function initWebhookPreview() {
		var btn = document.getElementById('edminboost-anm-webhook-preview-btn');
		var panel = document.getElementById('edminboost-anm-webhook-preview');
		var messageField = document.getElementById('edminboost-anm-webhook-message');
		var settingsRoot = document.querySelector('[data-edminboost-anm-settings]');
		if (!btn || !panel || !messageField) {
			return;
		}

		var sampleEl = panel.querySelector('[data-edminboost-anm-webhook-preview-sample]');
		var textEl = panel.querySelector('[data-edminboost-anm-webhook-preview-text]');
		var jsonEl = panel.querySelector('[data-edminboost-anm-webhook-preview-json]');
		var i18n = data.i18n || {};
		var refreshTimer = null;
		var loadingText = i18n.webhookPreviewLoading || i18n.digestPreviewLoading || 'Loading preview…';

		function escapeHtml(text) {
			var div = document.createElement('div');
			div.textContent = text == null ? '' : String(text);
			return div.innerHTML;
		}

		function noticeTypeClass(type) {
			var slug = String(type || 'info').toLowerCase();
			if (slug === 'warning' || slug === 'warn') {
				return 'is-warning';
			}
			if (slug === 'error') {
				return 'is-error';
			}
			if (slug === 'success') {
				return 'is-success';
			}
			return 'is-info';
		}

		function setLoadingState() {
			if (sampleEl) {
				sampleEl.textContent = loadingText;
			}
			if (textEl) {
				textEl.className = 'edminboost-anm-webhook-preview__text is-loading';
				textEl.textContent = loadingText;
			}
			if (jsonEl) {
				jsonEl.className = 'edminboost-anm-webhook-preview__json is-loading';
				jsonEl.textContent = loadingText;
			}
		}

		function setErrorState(message) {
			var errorText = message || i18n.webhookPreviewError || i18n.digestPreviewError || 'Could not load webhook preview.';
			if (sampleEl) {
				sampleEl.textContent = '';
			}
			if (textEl) {
				textEl.className = 'edminboost-anm-webhook-preview__text is-error';
				textEl.textContent = errorText;
			}
			if (jsonEl) {
				jsonEl.className = 'edminboost-anm-webhook-preview__json is-error';
				jsonEl.textContent = '';
			}
		}

		function renderPreview(preview) {
			if (!preview) {
				setErrorState();
				return;
			}

			var sample = preview.sample || {};
			var sampleLabel = preview.sample_from_inbox
				? (i18n.webhookPreviewInbox || 'Based on your latest inbox notice')
				: (i18n.webhookPreviewSample || 'Sample notice');

			if (sampleEl) {
				sampleEl.innerHTML = '<strong>' + escapeHtml(sampleLabel) + ':</strong> '
					+ escapeHtml(sample.source || '')
					+ ' <span class="edminboost-anm-webhook-preview__type ' + noticeTypeClass(sample.type) + '">'
					+ escapeHtml(sample.type || 'info')
					+ '</span> — '
					+ escapeHtml(sample.message || '');
			}

			if (textEl) {
				textEl.className = 'edminboost-anm-webhook-preview__text';
				textEl.textContent = preview.text || '';
			}

			if (jsonEl) {
				jsonEl.className = 'edminboost-anm-webhook-preview__json';
				try {
					jsonEl.textContent = JSON.stringify(preview.payload || {}, null, 2);
				} catch (err) {
					jsonEl.textContent = '';
				}
			}
		}

		function loadWebhookPreview() {
			var body = new FormData();
			body.append('action', 'edminboost_anm_webhook_preview');
			body.append('nonce', data.nonce || '');
			body.append('webhook[message]', messageField.value || '');

			btn.disabled = true;
			setLoadingState();

			return fetch(data.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: body
			}).then(function (response) {
				return response.json();
			}).then(function (result) {
				btn.disabled = false;
				if (!result.success || !result.data) {
					setErrorState(result.data && result.data.message);
					return;
				}
				renderPreview(result.data);
			}).catch(function () {
				btn.disabled = false;
				setErrorState();
			});
		}

		function scheduleWebhookPreviewRefresh() {
			clearTimeout(refreshTimer);
			refreshTimer = setTimeout(loadWebhookPreview, 400);
		}

		btn.addEventListener('click', loadWebhookPreview);
		messageField.addEventListener('input', scheduleWebhookPreviewRefresh);
		messageField.addEventListener('change', scheduleWebhookPreviewRefresh);

		if (settingsRoot) {
			settingsRoot.addEventListener('edminboost-anm-settings-tab', function (event) {
				if (event.detail && event.detail.tab === 'notifications') {
					loadWebhookPreview();
				}
			});
		}
	}

	function initNoticePanelPreview() {
		var styleSelect = document.getElementById('edminboost-anm-notice-panel-style');
		if (!styleSelect) {
			return;
		}

		var previewRoot = document.getElementById('edminboost-anm-notice-panel-preview');
		var stage = previewRoot ? previewRoot.querySelector('[data-edminboost-anm-notice-panel-preview-stage]') : null;
		var bellRows = document.querySelectorAll('.edminboost-anm-notice-panel-bell-row');
		var settingsRoot = document.querySelector('[data-edminboost-anm-settings]');

		var i18n = data.i18n || {};
		var badgeSelect = document.getElementById('edminboost-anm-notice-panel-badge');
		var positionSelect = document.getElementById('edminboost-anm-notice-panel-position');
		var colorFields = {
			badge: document.getElementById('edminboost-anm-notice-color-badge'),
			badge_text: document.getElementById('edminboost-anm-notice-color-badge-text'),
			accent: document.getElementById('edminboost-anm-notice-color-accent'),
			accent_hover: document.getElementById('edminboost-anm-notice-color-accent-hover'),
			bell_active: document.getElementById('edminboost-anm-notice-color-bell-active')
		};

		function escapeHtml(text) {
			if (text === null || text === undefined) {
				return '';
			}
			var div = document.createElement('div');
			div.textContent = String(text);
			return div.innerHTML;
		}

		function styleUsesBell(style, position) {
			if (style === 'bell_link' || style === 'dropdown' || style === 'dropdown_compact') {
				return true;
			}
			return style === 'link' && position === 'before_profile';
		}

		function getPositionPreviewLayout(position) {
			var layouts = data.noticePanelPositionLayouts || {};
			return layouts[position] || 'right';
		}

		function isDropdownStyle(style) {
			return style === 'dropdown' || style === 'dropdown_compact';
		}

		function readColors() {
			return {
				badge: (colorFields.badge && colorFields.badge.value) || '#d63638',
				badge_text: (colorFields.badge_text && colorFields.badge_text.value) || '#ffffff',
				accent: (colorFields.accent && colorFields.accent.value) || '#2271b1',
				accent_hover: (colorFields.accent_hover && colorFields.accent_hover.value) || '#135e96',
				bell_active: (colorFields.bell_active && colorFields.bell_active.value) || '#72aee6'
			};
		}

		function applyPreviewColorVars(colors) {
			if (!previewRoot) {
				return;
			}
			previewRoot.style.setProperty('--edminboost-anm-notice-badge-bg', colors.badge);
			previewRoot.style.setProperty('--edminboost-anm-notice-badge-text', colors.badge_text);
			previewRoot.style.setProperty('--edminboost-anm-notice-accent', colors.accent);
			previewRoot.style.setProperty('--edminboost-anm-notice-accent-hover', colors.accent_hover);
			previewRoot.style.setProperty('--edminboost-anm-notice-bell-active', colors.bell_active);
		}

		function formatBadgeDisplayCount(count, badgeStyle) {
			if (badgeStyle === 'count_9plus' && count > 9) {
				return '9+';
			}
			if (badgeStyle === 'count_99plus' && count > 99) {
				return '99+';
			}
			return String(count);
		}

		function buildBadgeHtml(badgeStyle, count, colors, facebook) {
			if (count <= 0 || badgeStyle === 'none') {
				return '';
			}
			if (badgeStyle === 'dot' || badgeStyle === 'dot_pulse') {
				var dotClass = 'edminboost-anm-notices-badge edminboost-anm-notices-badge--dot';
				if (badgeStyle === 'dot_pulse') {
					dotClass += ' edminboost-anm-notices-badge--pulse';
				}
				return '<span class="' + dotClass + '"><span class="screen-reader-text">' + escapeHtml(String(count)) + '</span></span>';
			}
			var display = formatBadgeDisplayCount(count, badgeStyle);
			return '<span class="edminboost-anm-notices-badge"><span class="screen-reader-text">' + escapeHtml(String(count)) + '</span><span aria-hidden="true">' + escapeHtml(display) + '</span></span>';
		}

		function buildBellTrigger(style, badgeStyle, facebook) {
			var triggerClass = 'edminboost-anm-notice-panel-preview__trigger edminboost-anm-notice-panel-preview__trigger--bell';
			if (facebook) {
				triggerClass += ' is-facebook';
			}
			if (style === 'bell_link') {
				triggerClass += ' is-style-bell-link';
			} else if (isDropdownStyle(style)) {
				triggerClass += ' is-style-dropdown';
			}
			return '<span class="' + triggerClass + '">'
				+ '<span class="ab-icon dashicons dashicons-bell edminboost-anm-notice-panel-preview__bell-icon" aria-hidden="true"></span>'
				+ buildBadgeHtml(badgeStyle, 3, null, facebook)
				+ '</span>';
		}

		function buildTriggerHtml(style, badgeStyle, position, facebook) {
			if (styleUsesBell(style, position)) {
				return buildBellTrigger(style, badgeStyle, facebook);
			}
			return buildLinkTrigger();
		}

		function buildLinkTrigger() {
			return '<span class="edminboost-anm-notice-panel-preview__trigger edminboost-anm-notice-panel-preview__trigger--link">'
				+ escapeHtml(i18n.noticePanelPreviewNotices || 'Notices')
				+ ' <span class="awaiting-mod count-3"><span class="pending-count">3</span></span>'
				+ '</span>';
		}

		function buildPanelHeaderHtml() {
			return '<div class="edminboost-anm-notices-panel__header"><strong>'
				+ escapeHtml(i18n.noticePanelPreviewAdminNotices || 'Admin notices')
				+ '</strong><button type="button" class="edminboost-anm-notices-panel__close" aria-label="'
				+ escapeHtml(i18n.noticePanelPreviewClose || 'Close panel')
				+ '" tabindex="-1">&times;</button></div>';
		}

		function buildPanelFooterHtml() {
			return '<div class="edminboost-anm-notices-panel__footer"><a href="#" tabindex="-1" onclick="return false;">'
				+ escapeHtml(i18n.noticePanelPreviewSeeAll || 'See all notifications')
				+ '</a></div>';
		}

		function compactItemTypeClass(type) {
			if (type === 'error' || type === 'critical') {
				return 'is-type-critical';
			}
			return 'is-type-' + type;
		}

		function panelNoticeClass(type) {
			var map = {
				success: 'notice notice-success',
				critical: 'notice notice-error',
				warning: 'notice notice-warning',
				upsell: 'notice notice-info',
				info: 'notice notice-info',
				error: 'notice notice-error'
			};
			return map[type] || 'notice notice-info';
		}

		function buildDetailedCardHtml(noticeClass, message) {
			var logged = escapeHtml(i18n.noticePanelSampleFirstLogged || 'First logged: Mar 15, 2024 10:30 am');
			var dismissLabel = escapeHtml(i18n.noticePanelPreviewDismiss || 'Dismiss from panel');
			var html = '<li class="edminboost-anm-notices-panel__card">';
			html += '<div class="edminboost-anm-notices-panel__notice-wrap">';
			html += '<button type="button" class="edminboost-anm-notices-panel__dismiss" aria-label="' + dismissLabel + '" tabindex="-1">&times;</button>';
			html += '<div class="edminboost-anm-notices-panel__notice-body"><div class="' + noticeClass + '"><p>' + escapeHtml(message) + '</p></div></div>';
			html += '</div>';
			html += '<div class="edminboost-anm-notices-panel__options">';
			html += '<span class="edminboost-anm-notices-panel__logged">' + logged + '</span>';
			html += '<span class="edminboost-anm-notices-panel__actions">';
			html += '<button type="button" class="edminboost-anm-notices-panel__action" tabindex="-1">' + escapeHtml(i18n.noticePanelPreviewDisplayNormal || 'Display notice as normal') + '</button>';
			html += '<button type="button" class="edminboost-anm-notices-panel__action edminboost-anm-notices-panel__action--hide" tabindex="-1">';
			html += '<span class="dashicons dashicons-hidden" aria-hidden="true"></span>';
			html += '<span class="edminboost-anm-notices-panel__action-label">' + escapeHtml(i18n.noticePanelPreviewHideForMe || 'Hide for me') + '</span></button>';
			html += '<button type="button" class="edminboost-anm-notices-panel__action edminboost-anm-notices-panel__action--hide" tabindex="-1">';
			html += '<span class="dashicons dashicons-hidden" aria-hidden="true"></span>';
			html += '<span class="edminboost-anm-notices-panel__action-label">' + escapeHtml(i18n.noticePanelPreviewHideForAll || 'Hide for all') + '</span></button>';
			html += '</span></div></li>';
			return html;
		}

		function buildCompactPanel() {
			var samples = [
				{
					type: 'info',
					message: i18n.noticePanelSample1 || 'There is a new version of Akismet Anti-spam available. View version 5.3.3 details or update now.',
					source: i18n.noticePanelSample1Source || 'Akismet Anti-spam'
				},
				{
					type: 'upsell',
					message: i18n.noticePanelSample2 || 'Enjoying WPForms? Please leave us a review on WordPress.org — it only takes a minute.',
					source: i18n.noticePanelSample2Source || 'WPForms'
				},
				{
					type: 'warning',
					message: i18n.noticePanelSample3 || 'Your license key expires in 14 days. Renew now to keep receiving updates and support.',
					source: i18n.noticePanelSample3Source || 'Advanced Custom Fields'
				}
			];
			var html = '<div class="edminboost-anm-notices-panel edminboost-anm-notices-panel--compact edminboost-anm-notice-panel-preview__panel">';
			html += buildPanelHeaderHtml();
			html += '<ul class="edminboost-anm-notices-panel__list">';
			samples.forEach(function (item) {
				var typeClass = compactItemTypeClass(item.type);
				html += '<li class="edminboost-anm-notices-panel__item ' + typeClass + '">';
				html += '<span class="edminboost-anm-notices-panel__type">' + escapeHtml(item.type) + '</span>';
				html += '<span class="edminboost-anm-notices-panel__message">' + escapeHtml(item.message) + '</span>';
				html += '<span class="edminboost-anm-notices-panel__meta"><span class="edminboost-anm-notices-panel__source">' + escapeHtml(item.source) + '</span></span>';
				html += '</li>';
			});
			html += '</ul>';
			html += buildPanelFooterHtml();
			html += '</div>';
			return html;
		}

		function buildDetailedPanel() {
			var detailedWarning = i18n.noticePanelSampleDetailed1 || i18n.noticePanelSample3 || 'Your license will expire in 3 days. Renew now to keep receiving updates and support.';
			var detailedUpsell = i18n.noticePanelSampleDetailed2 || i18n.noticePanelSample2 || 'Thanks for using Yoast SEO! Could you take a moment to leave a five-star review on WordPress.org?';
			var html = '<div class="edminboost-anm-notices-panel edminboost-anm-notices-panel--detailed edminboost-anm-notice-panel-preview__panel">';
			html += buildPanelHeaderHtml();
			html += '<ul class="edminboost-anm-notices-panel__list">';
			html += buildDetailedCardHtml(panelNoticeClass('warning'), detailedWarning);
			html += buildDetailedCardHtml(panelNoticeClass('upsell'), detailedUpsell);
			html += '</ul>';
			html += buildPanelFooterHtml();
			html += '</div>';
			return html;
		}

		function syncBellRows() {
			var position = positionSelect ? positionSelect.value : 'default';
			var show = styleUsesBell(styleSelect.value, position);
			bellRows.forEach(function (row) {
				row.hidden = !show;
			});
		}

		function renderPreview() {
			if (!stage) {
				return;
			}

			applyPreviewColorVars(readColors());

			var style = styleSelect ? styleSelect.value : 'link';
			var badgeStyle = badgeSelect ? badgeSelect.value : 'count';
			var position = positionSelect ? positionSelect.value : 'default';
			var previewLayout = getPositionPreviewLayout(position);
			var facebook = previewLayout === 'profile_before';

			var triggerHtml = buildTriggerHtml(style, badgeStyle, position, facebook);
			var panelHtml = '';
			if (isDropdownStyle(style)) {
				panelHtml = style === 'dropdown_compact' ? buildCompactPanel() : buildDetailedPanel();
			}

			var profileHtml = (previewLayout === 'profile_before' || previewLayout === 'profile_after')
				? '<span class="edminboost-anm-notice-panel-preview__profile">' + escapeHtml(i18n.noticePanelPreviewHowdy || 'Howdy, Admin') + '</span>'
				: '';
			var siteHtml = previewLayout === 'left'
				? '<span class="edminboost-anm-notice-panel-preview__site">' + escapeHtml(i18n.noticePanelPreviewSiteName || 'My Site') + '</span>'
				: '';
			var barInnerHtml = '';
			if (previewLayout === 'profile_after') {
				barInnerHtml = profileHtml + triggerHtml;
			} else if (previewLayout === 'left') {
				barInnerHtml = siteHtml + triggerHtml;
			} else {
				barInnerHtml = triggerHtml + profileHtml;
			}
			var mockBarClass = 'edminboost-anm-notice-panel-preview__mock-bar';
			if (previewLayout === 'left') {
				mockBarClass += ' is-left-slot';
			} else if (previewLayout === 'profile_before' || previewLayout === 'profile_after') {
				mockBarClass += ' is-profile-slot';
			}
			if (panelHtml) {
				mockBarClass += ' is-dropdown-open';
			}

			stage.innerHTML = '<div class="' + mockBarClass + '">'
				+ '<div class="edminboost-anm-notice-panel-preview__bar-inner">'
				+ barInnerHtml
				+ '</div>'
				+ (panelHtml ? '<div class="edminboost-anm-notice-panel-preview__panel-wrap">' + panelHtml + '</div>' : '')
				+ '</div>';
		}

		function bindColorField(field) {
			if (!field) {
				return;
			}
			field.addEventListener('input', renderPreview);
			field.addEventListener('change', renderPreview);
		}

		function onPanelLayoutChange() {
			syncBellRows();
			renderPreview();
		}

		function bindPanelField(field) {
			if (!field) {
				return;
			}
			field.addEventListener('change', onPanelLayoutChange);
			field.addEventListener('input', onPanelLayoutChange);
		}

		bindPanelField(styleSelect);
		bindPanelField(badgeSelect);
		bindPanelField(positionSelect);
		Object.keys(colorFields).forEach(function (key) {
			bindColorField(colorFields[key]);
		});

		if (settingsRoot) {
			settingsRoot.addEventListener('edminboost-anm-settings-tab', function (event) {
				if (event.detail && event.detail.tab === 'admin_bar') {
					syncBellRows();
					renderPreview();
				}
			});
		}

		syncBellRows();
		renderPreview();
	}

	document.addEventListener('DOMContentLoaded', function () {
		initWrap();
		initHidePluginAdminNotices();
		initAnmConfirmModal();
		initQuickStatusToggle();
		initPresetToggle();
		initRuleModal();
		initInboxKeyboardShortcut();
		initInboxTable();
		initInboxListKeyboardNav();
		initDebugTools();
		initDigestPreview();
		initWebhookPreview();
		initSettingsTabs();
		initNotificationChannelsMatrix();
		initSettingsImporter();
		initHidingNotificationsSettings();
		initBlockAdRedirectsSettings();
		initSettingsHelpTips();
		initDisplayExperience();
		initGeneralOptionsPreview();
		initWhiteLabelPreview();
		initResetHiddenNotices();
		initNoticePanelPreview();
	});
}());

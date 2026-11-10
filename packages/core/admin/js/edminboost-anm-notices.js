(function () {
	'use strict';

	var config = window.edminboostAnmNotices || {};

	function initNoticePanel() {
		var root = document.getElementById('wp-admin-bar-edminboost-anm-notices');
		if (!root) {
			return;
		}

		var trigger = root.querySelector('.ab-item');
		var panel = document.getElementById('edminboost-anm-notices-panel');
		if (!trigger || !panel || !root.classList.contains('edminboost-anm-notices--dropdown')) {
			return;
		}

		var sr = trigger.querySelector('.screen-reader-text');
		if (sr && sr.textContent) {
			trigger.setAttribute('aria-label', sr.textContent.trim());
		}
		trigger.removeAttribute('title');

		function getPanelWidth() {
			var measured = panel.offsetWidth;
			if (measured) {
				return measured;
			}
			if (panel.classList.contains('edminboost-anm-notices-panel--facebook')) {
				return 360;
			}
			return panel.classList.contains('edminboost-anm-notices-panel--detailed') ? 480 : 360;
		}

		function getMinPanelLeft(margin) {
			var wpcontent = document.getElementById('wpcontent');
			if (wpcontent) {
				return Math.max(margin, wpcontent.getBoundingClientRect().left);
			}

			var menu = document.getElementById('adminmenuwrap');
			if (menu) {
				return Math.max(margin, menu.getBoundingClientRect().right);
			}

			return margin;
		}

		function isMobileAdminViewport() {
			return document.body.classList.contains('mobile') ||
				window.matchMedia('(max-width: 782px)').matches;
		}

		function getPanelChromeHeight() {
			var chrome = 0;
			var parts = panel.querySelectorAll(
				'.edminboost-anm-notices-panel__header, .edminboost-anm-notices-panel__footer, ' +
					'.edminboost-anm-fb-notif__header, .edminboost-anm-fb-notif__tabs, .edminboost-anm-fb-notif__footer'
			);

			parts.forEach(function (el) {
				if (el && el.offsetHeight) {
					chrome += el.offsetHeight;
				}
			});

			return chrome;
		}

		function syncPanelScrollMaxHeight(budget) {
			var maxScroll = Math.max(120, Math.floor(budget));
			panel.querySelectorAll('.edminboost-anm-notices-panel__list, .edminboost-anm-fb-notif__scroll').forEach(function (el) {
				el.style.maxHeight = maxScroll + 'px';
			});
		}

		function clearPanelScrollMaxHeight() {
			panel.querySelectorAll('.edminboost-anm-notices-panel__list, .edminboost-anm-fb-notif__scroll').forEach(function (el) {
				el.style.maxHeight = '';
			});
		}

		function positionPanel() {
			var rect = trigger.getBoundingClientRect();
			var margin = 8;
			var minLeft = getMinPanelLeft(margin);
			var viewportWidth = window.innerWidth;
			var viewportHeight = window.innerHeight;
			var maxPanelWidth = Math.max(margin * 2, viewportWidth - margin * 2);
			var panelWidth = Math.min(getPanelWidth(), maxPanelWidth);
			var left = rect.right - panelWidth;

			if (viewportWidth <= 480) {
				panelWidth = maxPanelWidth;
				left = margin;
			} else {
				if (left < minLeft) {
					left = rect.left;
				}
				if (left + panelWidth > viewportWidth - margin) {
					left = viewportWidth - margin - panelWidth;
				}
				left = Math.max(minLeft, left);
			}

			panel.style.position = 'fixed';
			panel.style.left = Math.round(left) + 'px';
			panel.style.right = 'auto';
			panel.style.width = panelWidth + 'px';
			panel.style.maxWidth = maxPanelWidth + 'px';
			panel.style.zIndex = '100001';
			panel.style.bottom = 'auto';

			panel.style.top = Math.round(rect.bottom) + 'px';

			var panelHeight = panel.offsetHeight;
			var spaceBelow = viewportHeight - rect.bottom - margin;
			var spaceAbove = rect.top - margin;
			var openAbove = false;

			if (isMobileAdminViewport()) {
				openAbove = spaceAbove >= 120 || spaceAbove >= spaceBelow;
			} else if (panelHeight > spaceBelow && spaceAbove > spaceBelow) {
				openAbove = true;
			}

			var top;
			var scrollBudget;

			if (openAbove) {
				top = rect.top - panelHeight - margin;
				if (top < margin) {
					top = margin;
				}
				panel.classList.add('edminboost-anm-notices-panel--anchored-above');
				scrollBudget = rect.top - margin - top - getPanelChromeHeight() - margin;
			} else {
				panel.classList.remove('edminboost-anm-notices-panel--anchored-above');
				top = rect.bottom;
				if (top + panelHeight > viewportHeight - margin) {
					top = Math.max(margin, viewportHeight - margin - panelHeight);
				}
				scrollBudget = viewportHeight - top - margin - getPanelChromeHeight() - margin;
			}

			panel.style.top = Math.round(top) + 'px';
			syncPanelScrollMaxHeight(scrollBudget);
		}

		function resetPanelPosition() {
			panel.classList.remove('edminboost-anm-notices-panel--anchored-above');
			clearPanelScrollMaxHeight();
			panel.style.position = '';
			panel.style.top = '';
			panel.style.right = '';
			panel.style.left = '';
			panel.style.width = '';
			panel.style.maxWidth = '';
			panel.style.bottom = '';
			panel.style.zIndex = '';
		}

		function closePanel() {
			panel.hidden = true;
			resetPanelPosition();
			trigger.setAttribute('aria-expanded', 'false');
			root.classList.remove('edminboost-anm-notices--open');
			closeFacebookOptionsMenu();
		}

		function openPanel() {
			panel.hidden = false;
			positionPanel();
			trigger.setAttribute('aria-expanded', 'true');
			root.classList.add('edminboost-anm-notices--open');
		}

		function togglePanel(event) {
			event.preventDefault();
			if (panel.hidden) {
				openPanel();
			} else {
				closePanel();
			}
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

		function badgeCountFromResponse(counts) {
			if (!counts || typeof counts !== 'object') {
				return 0;
			}
			var key = config.badgeCountKey || 'all';
			return parseInt(counts[key], 10) || 0;
		}

		function updateBadgeCount(count) {
			var badgeStyle = config.badgeStyle || 'count';
			var badgeWrap = root.querySelector('.edminboost-anm-notices-badge');

			if (count <= 0 || badgeStyle === 'none') {
				if (badgeWrap) {
					badgeWrap.remove();
				}
				return;
			}

			if (badgeStyle === 'dot' || badgeStyle === 'dot_pulse') {
				return;
			}

			var badge = root.querySelector('.edminboost-anm-notices-badge [aria-hidden="true"]');
			if (badge) {
				badge.textContent = formatBadgeDisplayCount(count, badgeStyle);
			}
		}

		function runPanelAction(action, id, card) {
			if (!config.ajaxUrl || !config.nonce || !id) {
				return;
			}

			var body = new FormData();
			body.append('action', 'edminboost_anm_inbox_action');
			body.append('nonce', config.nonce);
			body.append('inbox_action', action);
			body.append('id', id);

			fetch(config.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: body
			}).then(function (response) {
				return response.json();
			}).then(function (result) {
				if (!result.success) {
					window.alert(config.i18n && config.i18n.actionError ? config.i18n.actionError : 'Request failed.');
					return;
				}

				if (result.data && result.data.reload) {
					window.location.reload();
					return;
				}

				if (card && card.parentNode) {
					card.parentNode.removeChild(card);
				}

				var list = panel.querySelector('.edminboost-anm-notices-panel__list');
				if (list && !list.querySelector('.edminboost-anm-notices-panel__card')) {
					var empty = document.createElement('li');
					empty.className = 'edminboost-anm-notices-panel__empty';
					empty.textContent = list.getAttribute('data-empty-label') || '';
					list.appendChild(empty);
				}

				if (result.data && result.data.counts) {
					updateBadgeCount(badgeCountFromResponse(result.data.counts));
				}
			}).catch(function () {
				window.alert(config.i18n && config.i18n.actionError ? config.i18n.actionError : 'Request failed.');
			});
		}

		trigger.setAttribute('aria-haspopup', 'true');
		trigger.setAttribute('aria-expanded', 'false');
		trigger.setAttribute('aria-controls', 'edminboost-anm-notices-panel');
		trigger.addEventListener('click', togglePanel);

		function applyFacebookFilter(filter) {
			var showUnreadOnly = filter === 'unread';

			panel.querySelectorAll('.edminboost-anm-fb-notif__item').forEach(function (item) {
				var hide = showUnreadOnly && item.classList.contains('is-read');
				item.hidden = hide;
				item.classList.toggle('is-fb-filter-hidden', hide);
				item.setAttribute('aria-hidden', hide ? 'true' : 'false');
			});

			panel.querySelectorAll('.edminboost-anm-fb-notif__empty--inline').forEach(function (emptyLine) {
				emptyLine.hidden = showUnreadOnly;
			});

			panel.querySelectorAll('.edminboost-anm-fb-notif__section').forEach(function (section) {
				var items = section.querySelectorAll('.edminboost-anm-fb-notif__item');
				var visibleCount = 0;

				items.forEach(function (item) {
					if (!item.hidden && !item.classList.contains('is-fb-filter-hidden')) {
						visibleCount += 1;
					}
				});

				if (showUnreadOnly && (!items.length || !visibleCount)) {
					section.hidden = true;
					section.setAttribute('aria-hidden', 'true');
				} else {
					section.hidden = false;
					section.removeAttribute('aria-hidden');
				}
			});

			var scroll = panel.querySelector('.edminboost-anm-fb-notif__scroll');
			var emptyUnread = scroll ? scroll.querySelector('.edminboost-anm-fb-notif__empty--unread-filter') : null;

			if (showUnreadOnly) {
				var hasVisible = !!panel.querySelector('.edminboost-anm-fb-notif__item:not(.is-fb-filter-hidden):not([hidden])');
				if (!hasVisible && scroll) {
					if (!emptyUnread) {
						emptyUnread = document.createElement('p');
						emptyUnread.className = 'edminboost-anm-fb-notif__empty edminboost-anm-fb-notif__empty--unread-filter';
						emptyUnread.textContent = config.i18n && config.i18n.fbUnreadEmpty
							? config.i18n.fbUnreadEmpty
							: 'You have no unread notifications.';
						scroll.appendChild(emptyUnread);
					}
					emptyUnread.hidden = false;
				} else if (emptyUnread) {
					emptyUnread.hidden = true;
				}
			} else if (emptyUnread) {
				emptyUnread.hidden = true;
			}
		}

		function closeFacebookOptionsMenu() {
			var menuWrap = panel.querySelector('.edminboost-anm-fb-notif__menu');
			if (!menuWrap) {
				return;
			}
			var menuToggle = menuWrap.querySelector('.edminboost-anm-fb-notif__menu-toggle');
			var menuPopover = menuWrap.querySelector('.edminboost-anm-fb-notif__menu-popover');
			if (menuPopover) {
				menuPopover.hidden = true;
			}
			if (menuToggle) {
				menuToggle.setAttribute('aria-expanded', 'false');
			}
		}

		function openFacebookOptionsMenu() {
			var menuWrap = panel.querySelector('.edminboost-anm-fb-notif__menu');
			if (!menuWrap) {
				return;
			}
			var menuToggle = menuWrap.querySelector('.edminboost-anm-fb-notif__menu-toggle');
			var menuPopover = menuWrap.querySelector('.edminboost-anm-fb-notif__menu-popover');
			if (menuPopover) {
				menuPopover.hidden = false;
			}
			if (menuToggle) {
				menuToggle.setAttribute('aria-expanded', 'true');
			}
		}

		function markAllFacebookNotificationsRead() {
			if (!config.ajaxUrl || !config.nonce) {
				return Promise.resolve();
			}

			var body = new FormData();
			body.append('action', 'edminboost_anm_inbox_action');
			body.append('nonce', config.nonce);
			body.append('inbox_action', 'mark_all_read');

			return fetch(config.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: body
			}).then(function (response) {
				return response.json();
			});
		}

		function applyFacebookMarkAllReadUi() {
			panel.querySelectorAll('.edminboost-anm-fb-notif__item').forEach(function (item) {
				item.classList.add('is-read');
				item.hidden = false;
				item.classList.remove('is-fb-filter-hidden');
				item.removeAttribute('aria-hidden');
				var dot = item.querySelector('.edminboost-anm-fb-notif__unread-dot');
				if (dot) {
					dot.remove();
				}
			});

			var activeTab = panel.querySelector('.edminboost-anm-fb-notif__tab.is-active');
			var activeFilter = activeTab ? activeTab.getAttribute('data-fb-filter') : 'all';
			applyFacebookFilter(activeFilter || 'all');
		}

		var fbMenuToggle = panel.querySelector('.edminboost-anm-fb-notif__menu-toggle');
		var fbMenuPopover = panel.querySelector('.edminboost-anm-fb-notif__menu-popover');
		if (fbMenuToggle && fbMenuPopover) {
			fbMenuToggle.addEventListener('click', function (event) {
				event.preventDefault();
				event.stopPropagation();
				if (fbMenuPopover.hidden) {
					openFacebookOptionsMenu();
				} else {
					closeFacebookOptionsMenu();
				}
			});

			fbMenuPopover.addEventListener('click', function (event) {
				var target = event.target;
				if (!target || !target.closest) {
					return;
				}
				var markAllBtn = target.closest('[data-fb-menu-action="mark-all-read"]');
				if (!markAllBtn) {
					return;
				}
				event.preventDefault();
				event.stopPropagation();
				closeFacebookOptionsMenu();
				markAllFacebookNotificationsRead().then(function (result) {
					if (!result || !result.success) {
						window.alert(
							config.i18n && config.i18n.fbMarkAllReadError
								? config.i18n.fbMarkAllReadError
								: 'Could not mark all notices as read.'
						);
						return;
					}
					applyFacebookMarkAllReadUi();
					if (result.data && result.data.counts) {
						updateBadgeCount(badgeCountFromResponse(result.data.counts));
					}
				}).catch(function () {
					window.alert(
						config.i18n && config.i18n.fbMarkAllReadError
							? config.i18n.fbMarkAllReadError
							: 'Could not mark all notices as read.'
					);
				});
			});
		}

		var fbTabs = panel.querySelectorAll('.edminboost-anm-fb-notif__tab[data-fb-filter]');
		if (fbTabs.length) {
			fbTabs.forEach(function (tab) {
				tab.addEventListener('click', function (event) {
					event.preventDefault();
					event.stopPropagation();
					var filter = tab.getAttribute('data-fb-filter') || 'all';
					fbTabs.forEach(function (other) {
						var isActive = other === tab;
						other.classList.toggle('is-active', isActive);
						other.setAttribute('aria-selected', isActive ? 'true' : 'false');
					});
					applyFacebookFilter(filter);
				});
			});
		}

		if (panel.classList.contains('edminboost-anm-notices-panel--facebook')) {
			panel.addEventListener('click', function (event) {
				var target = event.target;
				if (!target || !target.closest) {
					return;
				}
				if (
					target.closest('.edminboost-anm-fb-notif__tab')
					|| target.closest('.edminboost-anm-fb-notif__see-all')
					|| target.closest('.edminboost-anm-fb-notif__footer-link')
					|| target.closest('.edminboost-anm-fb-notif__menu')
				) {
					return;
				}
				var row = target.closest('.edminboost-anm-fb-notif__item[data-notice-id]');
				if (!row || !config.inboxUrl) {
					return;
				}
				if (!row.getAttribute('data-notice-id')) {
					return;
				}
				window.location.href = config.inboxUrl;
			});
		}

		var closeBtn = panel.querySelector('.edminboost-anm-notices-panel__close');
		if (closeBtn) {
			closeBtn.addEventListener('click', function (event) {
				event.preventDefault();
				closePanel();
				trigger.focus();
			});
		}

		panel.addEventListener('click', function (event) {
			var target = event.target;
			if (!target || !target.closest) {
				return;
			}

			var actionBtn = target.closest('[data-action][data-id]');
			if (!actionBtn || !panel.contains(actionBtn)) {
				return;
			}

			event.preventDefault();
			var action = actionBtn.getAttribute('data-action');
			var id = actionBtn.getAttribute('data-id');
			var card = actionBtn.closest('.edminboost-anm-notices-panel__card');
			runPanelAction(action, id, card);
		});

		function repositionOpenPanel() {
			if (!panel.hidden) {
				positionPanel();
			}
		}

		window.addEventListener('resize', repositionOpenPanel);
		window.addEventListener('scroll', repositionOpenPanel, true);

		document.addEventListener('click', function (event) {
			var fbMenuPopover = panel.querySelector('.edminboost-anm-fb-notif__menu-popover');
			if (fbMenuPopover && !fbMenuPopover.hidden && event.target && event.target.closest) {
				if (!event.target.closest('.edminboost-anm-fb-notif__menu')) {
					closeFacebookOptionsMenu();
				}
			}
			if (panel.hidden) {
				return;
			}
			if (!root.contains(event.target) && !panel.contains(event.target)) {
				closePanel();
			}
		});

		document.addEventListener('keydown', function (event) {
			if ('Escape' !== event.key) {
				return;
			}
			var fbMenuPopover = panel.querySelector('.edminboost-anm-fb-notif__menu-popover');
			if (fbMenuPopover && !fbMenuPopover.hidden) {
				closeFacebookOptionsMenu();
				var menuToggle = panel.querySelector('.edminboost-anm-fb-notif__menu-toggle');
				if (menuToggle) {
					menuToggle.focus();
				}
				event.preventDefault();
				return;
			}
			if (!panel.hidden) {
				closePanel();
				trigger.focus();
			}
		});
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', initNoticePanel);
	} else {
		initNoticePanel();
	}
})();

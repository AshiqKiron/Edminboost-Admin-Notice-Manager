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
			return panel.classList.contains('edminboost-anm-notices-panel--detailed') ? 480 : 360;
		}

		function positionPanel() {
			var rect = trigger.getBoundingClientRect();
			var margin = 8;
			var viewportWidth = window.innerWidth;
			var maxPanelWidth = Math.max(margin * 2, viewportWidth - margin * 2);
			var panelWidth = Math.min(getPanelWidth(), maxPanelWidth);
			var left = rect.right - panelWidth;

			if (left < margin) {
				left = rect.left;
			}
			if (left + panelWidth > viewportWidth - margin) {
				left = viewportWidth - margin - panelWidth;
			}
			left = Math.max(margin, left);

			panel.style.position = 'fixed';
			panel.style.top = rect.bottom + 'px';
			panel.style.left = left + 'px';
			panel.style.right = 'auto';
			panel.style.maxWidth = maxPanelWidth + 'px';
		}

		function resetPanelPosition() {
			panel.style.position = '';
			panel.style.top = '';
			panel.style.right = '';
			panel.style.left = '';
			panel.style.maxWidth = '';
		}

		function closePanel() {
			panel.hidden = true;
			resetPanelPosition();
			trigger.setAttribute('aria-expanded', 'false');
			root.classList.remove('edminboost-anm-notices--open');
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
					updateBadgeCount(result.data.counts.all || 0);
				}
			}).catch(function () {
				window.alert(config.i18n && config.i18n.actionError ? config.i18n.actionError : 'Request failed.');
			});
		}

		trigger.setAttribute('aria-haspopup', 'true');
		trigger.setAttribute('aria-expanded', 'false');
		trigger.setAttribute('aria-controls', 'edminboost-anm-notices-panel');
		trigger.addEventListener('click', togglePanel);

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

		window.addEventListener('resize', function () {
			if (!panel.hidden) {
				positionPanel();
			}
		});

		document.addEventListener('click', function (event) {
			if (panel.hidden) {
				return;
			}
			if (!root.contains(event.target) && !panel.contains(event.target)) {
				closePanel();
			}
		});

		document.addEventListener('keydown', function (event) {
			if ('Escape' === event.key && !panel.hidden) {
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

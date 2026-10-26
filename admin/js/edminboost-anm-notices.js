(function () {
	'use strict';

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

		function closePanel() {
			panel.hidden = true;
			trigger.setAttribute('aria-expanded', 'false');
			root.classList.remove('edminboost-anm-notices--open');
		}

		function openPanel() {
			panel.hidden = false;
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

		trigger.setAttribute('aria-haspopup', 'true');
		trigger.setAttribute('aria-expanded', 'false');
		trigger.setAttribute('aria-controls', 'edminboost-anm-notices-panel');
		trigger.addEventListener('click', togglePanel);

		document.addEventListener('click', function (event) {
			if (panel.hidden) {
				return;
			}
			if (!root.contains(event.target)) {
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

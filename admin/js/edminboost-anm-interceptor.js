/**
 * Admin Notice Manager — JS notice interceptor.
 *
 * @package EdminBoost\AdminNoticeManager
 */
(function () {
	'use strict';

	var config = window.edminboostAnmInterceptor || {};
	var processed = {};
	var ignoreSelectors = Array.isArray(config.cssIgnoreSelectors) ? config.cssIgnoreSelectors : [];

	function matchesIgnoreSelector(node) {
		if (!node || !node.matches || !ignoreSelectors.length) {
			return false;
		}

		for (var i = 0; i < ignoreSelectors.length; i++) {
			var selector = (ignoreSelectors[i] || '').trim();
			if (!selector) {
				continue;
			}
			try {
				if (node.matches(selector)) {
					return true;
				}
			} catch (e) {
				// Unsupported selector syntax — skip.
			}
		}

		return false;
	}

	function hashNotice(message, classes) {
		var str = (message || '').toLowerCase().trim() + '|' + (classes || '');
		var hash = 0;
		for (var i = 0; i < str.length; i++) {
			hash = ((hash << 5) - hash) + str.charCodeAt(i);
			hash |= 0;
		}
		return String(hash);
	}

	function captureNotice(node) {
		if (!node || node.classList.contains('edminboost-anm-notice-hidden') || matchesIgnoreSelector(node)) {
			return;
		}

		var message = node.textContent || '';
		var classes = node.className || '';
		var key = hashNotice(message, classes);

		if (processed[key]) {
			return;
		}

		processed[key] = true;

		var body = new FormData();
		body.append('action', 'edminboost_anm_capture_notice');
		body.append('nonce', config.nonce || '');
		body.append('html', node.outerHTML);
		body.append('source_plugin', '');

		fetch(config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		}).then(function (response) {
			return response.json();
		}).then(function (result) {
			if (result.success && result.data && result.data.suppress) {
				node.classList.add('edminboost-anm-notice-hidden');
			}
		}).catch(function () {
			delete processed[key];
		});
	}

	function scanNotices(root) {
		if (!root) {
			return;
		}
		root.querySelectorAll('.notice, .update-nag, div.error, div.updated').forEach(function (node) {
			captureNotice(node);
		});
	}

	function initObserver() {
		var target = document.getElementById('wpbody-content');
		if (!target) {
			return;
		}

		scanNotices(target);

		if (typeof MutationObserver === 'undefined') {
			return;
		}

		var observer = new MutationObserver(function (mutations) {
			mutations.forEach(function (mutation) {
				mutation.addedNodes.forEach(function (node) {
					if (node.nodeType !== 1) {
						return;
					}
					if (node.matches && node.matches('.notice, .update-nag, div.error, div.updated')) {
						captureNotice(node);
					} else if (node.querySelectorAll) {
						scanNotices(node);
					}
				});
			});
		});

		observer.observe(target, { childList: true, subtree: true });
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initObserver);
	} else {
		initObserver();
	}
}());

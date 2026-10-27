/**
 * Inline “Hide for me” / “Hide for all” controls on visible admin notices.
 *
 * @package EdminBoost.AdminNoticeManager
 */
(function () {
	'use strict';

	var config = window.edminboostAnmNotices || {};
	var inlineConfig = window.edminboostAnmNoticeInline || {};
	var ignoreSelectors = Array.isArray(inlineConfig.cssIgnoreSelectors) ? inlineConfig.cssIgnoreSelectors : [];
	var noticeSelector = '.notice, .update-nag, div.error, div.updated';

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

	function shouldSkipNotice(node) {
		if (!node || !node.classList) {
			return true;
		}
		if (node.classList.contains('edminboost-anm-notice-hidden')) {
			return true;
		}
		if (node.classList.contains('edminboost-anm-notice--managed')) {
			return true;
		}
		if (matchesIgnoreSelector(node)) {
			return true;
		}
		if (node.closest && (
			node.closest('.edminboost-anm-wrap') ||
			node.closest('#edminboost-anm-notices-panel') ||
			node.closest('#wpadminbar')
		)) {
			return true;
		}
		return false;
	}

	function buildActionsBar() {
		var bar = document.createElement('div');
		bar.className = 'edminboost-anm-notice-inline-actions';
		bar.setAttribute('role', 'group');
		bar.setAttribute('aria-label', config.i18n && config.i18n.hideForMe ? config.i18n.hideForMe : 'Notice actions');

		['hide_for_me', 'hide_for_all'].forEach(function (scope) {
			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'edminboost-anm-notice-inline-actions__btn';
			btn.setAttribute('data-scope', scope);

			var icon = document.createElement('span');
			icon.className = 'dashicons dashicons-hidden';
			icon.setAttribute('aria-hidden', 'true');
			btn.appendChild(icon);

			var label = document.createElement('span');
			label.className = 'edminboost-anm-notice-inline-actions__label';
			if ('hide_for_me' === scope) {
				label.textContent = config.i18n && config.i18n.hideForMe ? config.i18n.hideForMe : 'Hide for me';
			} else {
				label.textContent = config.i18n && config.i18n.hideForAll ? config.i18n.hideForAll : 'Hide for all';
			}
			btn.appendChild(label);

			bar.appendChild(btn);
		});

		return bar;
	}

	function noticeHtmlForRequest(node) {
		var clone = node.cloneNode(true);
		var actions = clone.querySelector('.edminboost-anm-notice-inline-actions');
		if (actions && actions.parentNode) {
			actions.parentNode.removeChild(actions);
		}
		return clone.outerHTML;
	}

	function hideNotice(node) {
		node.classList.add('edminboost-anm-notice-hidden');
	}

	function runHide(scope, node) {
		if (!config.ajaxUrl || !config.nonce || !node) {
			return;
		}

		var body = new FormData();
		body.append('action', 'edminboost_anm_notice_hide');
		body.append('nonce', config.nonce);
		body.append('scope', scope);
		body.append('html', noticeHtmlForRequest(node));

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
			hideNotice(node);
		}).catch(function () {
			window.alert(config.i18n && config.i18n.actionError ? config.i18n.actionError : 'Request failed.');
		});
	}

	function decorateNotice(node) {
		if (shouldSkipNotice(node)) {
			return;
		}

		node.classList.add('edminboost-anm-notice--managed');
		var bar = buildActionsBar();
		node.appendChild(bar);

		bar.addEventListener('click', function (event) {
			var target = event.target;
			if (!target || !target.closest) {
				return;
			}
			var btn = target.closest('[data-scope]');
			if (!btn || !bar.contains(btn)) {
				return;
			}
			event.preventDefault();
			event.stopPropagation();
			runHide(btn.getAttribute('data-scope'), node);
		});
	}

	function scanNotices(root) {
		if (!root || !root.querySelectorAll) {
			return;
		}
		root.querySelectorAll(noticeSelector).forEach(decorateNotice);
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
					if (node.matches && node.matches(noticeSelector)) {
						decorateNotice(node);
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

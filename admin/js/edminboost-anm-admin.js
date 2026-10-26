/**
 * Admin Notice Manager — plugin admin scripts.
 *
 * @package EdminBoost\AdminNoticeManager
 */
(function () {
	'use strict';

	var data = window.edminboostAnmData || {};

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

	function initWrap() {
		var wrap = document.querySelector('.edminboost-anm-wrap');
		if (wrap) {
			wrap.classList.add('is-ready');
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
					var status = document.querySelector('[data-edminboost-anm-enabled-status]');
					if (status) {
						var i18n = data.i18n || {};
						status.textContent = toggle.checked
							? (i18n.statusActive || 'Active')
							: (i18n.statusInactive || 'Inactive');
						status.classList.toggle('is-active', toggle.checked);
						status.classList.toggle('is-inactive', !toggle.checked);
					}
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
				});
		});
	}

	function initRuleModal() {
		var root = document.querySelector('[data-edminboost-anm-rules]');
		if (!root) {
			return;
		}

		var modal = document.getElementById('edminboost-anm-rule-modal');
		var form = modal ? modal.querySelector('.edminboost-anm-rule-form') : null;
		var title = document.getElementById('edminboost-anm-rule-modal-title');

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

			if (title) {
				title.textContent = editData.id ? (data.i18n && data.i18n.editRule) || 'Edit Filter Rule' : (data.i18n && data.i18n.createRule) || 'Create New Filter Rule';
			}

			modal.classList.add('is-open');
			modal.setAttribute('aria-hidden', 'false');
		}

		function closeModal() {
			if (modal) {
				modal.classList.remove('is-open');
				modal.setAttribute('aria-hidden', 'true');
			}
		}

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
					ruleAction: btn.dataset.ruleAction
				});
			});
		});

		root.querySelectorAll('.edminboost-anm-delete-rule').forEach(function (btn) {
			btn.addEventListener('click', function () {
				if (!window.confirm((data.i18n && data.i18n.confirmDelete) || 'Delete this rule?')) {
					return;
				}
				apiPost('edminboost_anm_delete_rule', { id: btn.dataset.id }).then(function (result) {
					if (result.success) {
						var row = btn.closest('tr');
						if (row) {
							row.remove();
						}
					}
				});
			});
		});

		root.querySelectorAll('.edminboost-anm-rule-toggle').forEach(function (toggle) {
			toggle.addEventListener('change', function () {
				apiPost('edminboost_anm_toggle_rule', {
					id: toggle.dataset.id,
					enabled: toggle.checked ? '1' : '0'
				});
			});
		});

		if (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();
				var matchTypeEl = form.querySelector('input[name="match_type"]:checked');
				apiPost('edminboost_anm_save_rule', {
					id: document.getElementById('edminboost-anm-rule-id').value,
					name: document.getElementById('edminboost-anm-rule-name').value,
					match_type: matchTypeEl ? matchTypeEl.value : 'keyword',
					match_string: document.getElementById('edminboost-anm-rule-string').value,
					rule_action: document.getElementById('edminboost-anm-rule-action').value,
					enabled: '1'
				}).then(function (result) {
					if (result.success) {
						window.location.reload();
					}
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
						window.alert((result.data && result.data.message) || (data.i18n && data.i18n.actionError) || 'Action failed.');
						return;
					}
					if (isConverted) {
						unmarkWpPresetConverted(btn.closest('tr'));
					} else {
						markWpPresetConverted(btn.closest('tr'));
					}
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

	function updateInboxUrlState(root, orderby, order) {
		var params = new URLSearchParams(window.location.search);
		if ('date' === orderby && 'desc' === order) {
			params.delete('orderby');
			params.delete('order');
		} else {
			params.set('orderby', orderby);
			params.set('order', order);
		}
		var query = params.toString();
		var nextUrl = window.location.pathname + (query ? '?' + query : '') + window.location.hash;
		window.history.replaceState({}, '', nextUrl);

		var searchForm = root.querySelector('.edminboost-anm-search');
		if (!searchForm) {
			return;
		}
		var orderbyInput = searchForm.querySelector('input[name="orderby"]');
		var orderInput = searchForm.querySelector('input[name="order"]');
		if ('date' === orderby && 'desc' === order) {
			if (orderbyInput) {
				orderbyInput.remove();
			}
			if (orderInput) {
				orderInput.remove();
			}
			return;
		}
		if (!orderbyInput) {
			orderbyInput = document.createElement('input');
			orderbyInput.type = 'hidden';
			orderbyInput.name = 'orderby';
			searchForm.appendChild(orderbyInput);
		}
		if (!orderInput) {
			orderInput = document.createElement('input');
			orderInput.type = 'hidden';
			orderInput.name = 'order';
			searchForm.appendChild(orderInput);
		}
		orderbyInput.value = orderby;
		orderInput.value = order;
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
				return;
			}
			tbody.innerHTML = result.data.rows || '';
			root.dataset.inboxOrderby = result.data.orderby || orderby;
			root.dataset.inboxOrder = result.data.order || order;
			updateInboxSortHeaders(table, root.dataset.inboxOrderby, root.dataset.inboxOrder);
			updateInboxUrlState(root, root.dataset.inboxOrderby, root.dataset.inboxOrder);
			var showing = root.querySelector('[data-edminboost-anm-inbox-showing]');
			if (showing && result.data.showing) {
				showing.textContent = result.data.showing;
			}
		});
	}

	function initInboxTable() {
		var root = document.querySelector('[data-edminboost-anm-inbox]');
		if (!root) {
			return;
		}

		root.addEventListener('change', function (event) {
			if (event.target.id === 'edminboost-anm-select-all') {
				root.querySelectorAll('.edminboost-anm-notice-check').forEach(function (cb) {
					cb.checked = event.target.checked;
				});
			}
		});

		root.addEventListener('click', function (event) {
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
				fetchInboxRows(root, orderby, order);
				return;
			}

			var dropdownToggle = event.target.closest('.edminboost-anm-dropdown__toggle');
			if (dropdownToggle) {
				var dropdown = dropdownToggle.closest('.edminboost-anm-dropdown');
				if (dropdown) {
					dropdown.classList.toggle('is-open');
				}
				return;
			}

			var readBtn = event.target.closest('.edminboost-anm-inbox-read');
			if (readBtn) {
				apiPost('edminboost_anm_inbox_action', { inbox_action: 'read', id: readBtn.dataset.id }).then(function (result) {
					if (result.success) {
						readBtn.closest('tr').classList.add('is-read');
					}
				});
				return;
			}

			var snoozeBtn = event.target.closest('.edminboost-anm-inbox-snooze');
			if (snoozeBtn) {
				apiPost('edminboost_anm_inbox_action', {
					inbox_action: 'snooze',
					id: snoozeBtn.dataset.id,
					duration: snoozeBtn.dataset.duration
				}).then(function (result) {
					if (result.success) {
						window.location.reload();
					}
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
					var row = convertBtn.closest('tr');
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
				apiPost('edminboost_anm_inbox_action', {
					inbox_action: 'bulk',
					bulk_action: action,
					ids: ids
				}).then(function (result) {
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

		var seedBtn = root.querySelector('.edminboost-anm-seed-test-notices');
		if (seedBtn) {
			seedBtn.addEventListener('click', function () {
				seedBtn.disabled = true;
				apiPost('edminboost_anm_seed_test_notices', {}).then(function (result) {
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
				triggerBtn.disabled = true;
				apiPost('edminboost_anm_trigger_test_notices', {}).then(function (result) {
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

	function initNoticePanelSettings() {
		var styleSelect = document.getElementById('edminboost-anm-notice-panel-style');
		var badgeRow = document.querySelector('.edminboost-anm-notice-panel-badge-row');

		if (!styleSelect || !badgeRow) {
			return;
		}

		function syncBadgeRow() {
			badgeRow.hidden = 'dropdown' !== styleSelect.value;
		}

		styleSelect.addEventListener('change', syncBadgeRow);
		syncBadgeRow();
	}

	document.addEventListener('DOMContentLoaded', function () {
		initWrap();
		initQuickStatusToggle();
		initPresetToggle();
		initRuleModal();
		initInboxTable();
		initDebugTools();
		initSettingsImporter();
		initNoticePanelSettings();
	});
}());

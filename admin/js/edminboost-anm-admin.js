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
	}

	function initInboxTable() {
		var root = document.querySelector('[data-edminboost-anm-inbox]');
		if (!root) {
			return;
		}

		var selectAll = document.getElementById('edminboost-anm-select-all');

		if (selectAll) {
			selectAll.addEventListener('change', function () {
				root.querySelectorAll('.edminboost-anm-notice-check').forEach(function (cb) {
					cb.checked = selectAll.checked;
				});
			});
		}

		root.querySelectorAll('.edminboost-anm-dropdown__toggle').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var dropdown = btn.closest('.edminboost-anm-dropdown');
				if (dropdown) {
					dropdown.classList.toggle('is-open');
				}
			});
		});

		root.querySelectorAll('.edminboost-anm-inbox-read').forEach(function (btn) {
			btn.addEventListener('click', function () {
				apiPost('edminboost_anm_inbox_action', { inbox_action: 'read', id: btn.dataset.id }).then(function (result) {
					if (result.success) {
						btn.closest('tr').classList.add('is-read');
					}
				});
			});
		});

		root.querySelectorAll('.edminboost-anm-inbox-snooze').forEach(function (btn) {
			btn.addEventListener('click', function () {
				apiPost('edminboost_anm_inbox_action', {
					inbox_action: 'snooze',
					id: btn.dataset.id,
					duration: btn.dataset.duration
				}).then(function (result) {
					if (result.success) {
						window.location.reload();
					}
				});
			});
		});

		root.querySelectorAll('.edminboost-anm-inbox-convert').forEach(function (btn) {
			btn.addEventListener('click', function () {
				apiPost('edminboost_anm_inbox_action', { inbox_action: 'convert', id: btn.dataset.id }).then(function (result) {
					if (result.success) {
						window.alert((data.i18n && data.i18n.ruleSaved) || 'Rule saved.');
					}
				});
			});
		});

		var bulkApply = root.querySelector('.edminboost-anm-bulk-apply');
		if (bulkApply) {
			bulkApply.addEventListener('click', function () {
				var action = document.getElementById('edminboost-anm-bulk-action').value;
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
			});
		}
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

	document.addEventListener('DOMContentLoaded', function () {
		initWrap();
		initQuickStatusToggle();
		initPresetToggle();
		initRuleModal();
		initInboxTable();
		initDebugTools();
		initSettingsImporter();
	});
}());

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
		var settingsForm = root.querySelector('.edminboost-anm-settings-form');
		var defaultTab = root.dataset.settingsTab || 'general';

		function panelForTab(slug) {
			return root.querySelector('.edminboost-anm-tab-panel[data-tab="' + slug + '"]');
		}

		function tabFromUrl() {
			var params = new URLSearchParams(window.location.search);
			var slug = params.get('tab') || defaultTab;
			return panelForTab(slug) ? slug : 'general';
		}

		function syncFormSubmitVisibility(slug) {
			if (!settingsForm) {
				return;
			}
			settingsForm.classList.toggle('is-tab-tools', slug === 'tools');
		}

		function activate(slug) {
			tabs.forEach(function (tab) {
				var isActive = tab.dataset.tab === slug;
				tab.classList.toggle('is-active', isActive);
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

		if (settingsForm) {
			settingsForm.addEventListener('submit', function () {
				var referer = settingsForm.querySelector('input[name="_wp_http_referer"]');
				if (!referer) {
					return;
				}
				var url = new URL(window.location.href);
				var activeTab = root.dataset.settingsTab || 'general';
				if (activeTab && activeTab !== 'general') {
					url.searchParams.set('tab', activeTab);
				} else {
					url.searchParams.delete('tab');
				}
				referer.value = url.pathname + url.search;
			});
		}

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
		document.querySelectorAll('.edminboost-anm-help-tip[data-tip]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				window.alert(btn.getAttribute('data-tip') || '');
			});
		});
	}

	function initSettingsSwitches() {
		document.querySelectorAll('[data-edminboost-anm-switch]').forEach(function (wrap) {
			var input = wrap.querySelector('.edminboost-anm-switch__input');
			var options = wrap.querySelectorAll('.edminboost-anm-switch__option');
			if (!input || !options.length) {
				return;
			}

			function sync() {
				var on = input.checked;
				options.forEach(function (option) {
					var active = (option.dataset.value === '1') === on;
					option.classList.toggle('is-active', active);
					option.setAttribute('aria-pressed', active ? 'true' : 'false');
				});
			}

			options.forEach(function (option) {
				option.addEventListener('click', function () {
					input.checked = option.dataset.value === '1';
					sync();
				});
			});

			sync();
		});
	}

	function initManageNoticesMode() {
		var fieldset = document.querySelector('[data-edminboost-anm-manage-mode]');
		var tip = document.querySelector('[data-edminboost-anm-compact-panel-tip]');
		if (!fieldset) {
			return;
		}

		fieldset.querySelectorAll('input[type="radio"]').forEach(function (radio) {
			radio.addEventListener('change', function () {
				if (tip) {
					tip.hidden = radio.value !== 'compact_panel' || !radio.checked;
				}
			});
		});
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
				} else {
					feedback.textContent = (result.data && result.data.message) || i18n.resetHiddenError || 'Could not reset hidden notices.';
					feedback.classList.remove('is-success');
					feedback.classList.add('is-error');
				}
			});
		});
	}

	function initBlockAdRedirectsSettings() {
		var tbody = document.querySelector('[data-edminboost-anm-redirect-block-rows]');
		var addBtn = document.querySelector('[data-edminboost-anm-add-redirect-block]');
		var template = document.getElementById('edminboost-anm-redirect-block-row-template');

		if (!tbody || !addBtn || !template) {
			return;
		}

		function ensureAtLeastOneRow() {
			if (!tbody.querySelector('tr')) {
				addRow();
			}
		}

		function addRow() {
			var clone = template.content.cloneNode(true);
			tbody.appendChild(clone);
		}

		addBtn.addEventListener('click', addRow);

		tbody.addEventListener('click', function (event) {
			var removeBtn = event.target.closest('.edminboost-anm-redirect-block-remove');
			if (!removeBtn) {
				return;
			}
			var row = removeBtn.closest('tr');
			if (row) {
				row.remove();
			}
			ensureAtLeastOneRow();
		});
	}

	function initNoticePanelSettings() {
		var styleSelect = document.getElementById('edminboost-anm-notice-panel-style');
		var dropdownRows = document.querySelectorAll('.edminboost-anm-notice-panel-dropdown-row');

		if (!styleSelect || !dropdownRows.length) {
			return;
		}

		function syncDropdownRows() {
			var show = 'dropdown' === styleSelect.value;
			dropdownRows.forEach(function (row) {
				row.hidden = !show;
			});
		}

		styleSelect.addEventListener('change', syncDropdownRows);
		syncDropdownRows();
	}

	document.addEventListener('DOMContentLoaded', function () {
		initWrap();
		initQuickStatusToggle();
		initPresetToggle();
		initRuleModal();
		initInboxTable();
		initDebugTools();
		initSettingsTabs();
		initSettingsImporter();
		initHidingNotificationsSettings();
		initBlockAdRedirectsSettings();
		initSettingsHelpTips();
		initSettingsSwitches();
		initManageNoticesMode();
		initResetHiddenNotices();
		initNoticePanelSettings();
	});
}());

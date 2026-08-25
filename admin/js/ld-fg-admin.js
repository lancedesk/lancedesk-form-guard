/**
 * LanceDesk Form Guard — admin scripts.
 */
(function ($) {
	'use strict';

	if (typeof window.ldFgAdmin === 'undefined' || typeof $ === 'undefined') {
		return;
	}

	function post(action, data) {
		var payload = $.extend({ action: action, nonce: ldFgAdmin.nonce }, data || {});
		return $.ajax({
			url: ldFgAdmin.ajaxUrl,
			method: 'POST',
			dataType: 'json',
			data: payload
		});
	}

	function toggleMessage(text, isError) {
		var $bar = $('#ld-fg-forms-toast');
		if (!$bar.length) {
			$bar = $('<div id="ld-fg-forms-toast" class="notice" style="margin:12px 0;"></div>');
			$('.ld-fg-admin-content').prepend($bar);
		}
		$bar
			.toggleClass('notice-error', !!isError)
			.toggleClass('notice-success', !isError)
			.html('<p>' + text + '</p>')
			.show();
		window.setTimeout(function () { $bar.fadeOut(200); }, 3200);
	}

	/**
	 * Snapshot form field state for dirty checking.
	 *
	 * @param {HTMLFormElement} form Form element.
	 * @return {string}
	 */
	function snapshotForm(form) {
		var parts = [];
		form.querySelectorAll('input, select, textarea').forEach(function (el) {
			var name = el.name || el.id || '';
			if (!name || el.disabled) {
				return;
			}
			if (el.type === 'checkbox' || el.type === 'radio') {
				parts.push(name + ':' + (el.value || 'on') + '=' + (el.checked ? '1' : '0'));
				return;
			}
			parts.push(name + '=' + String(el.value || ''));
		});
		parts.sort();
		return parts.join('\n');
	}

	/**
	 * Disable a save button until the watched form differs from its baseline.
	 *
	 * @param {HTMLFormElement} form    Form to watch.
	 * @param {HTMLButtonElement} saveBtn Save button.
	 * @return {{ isDirty: function(): boolean, markClean: function(): void, sync: function(): void }}
	 */
	function bindDirtySave(form, saveBtn) {
		var baseline = snapshotForm(form);

		function sync() {
			var dirty = snapshotForm(form) !== baseline;
			saveBtn.disabled = !dirty;
			saveBtn.setAttribute('aria-disabled', dirty ? 'false' : 'true');
		}

		['input', 'change'].forEach(function (evt) {
			form.addEventListener(evt, sync);
		});

		sync();

		return {
			isDirty: function () {
				return snapshotForm(form) !== baseline;
			},
			markClean: function () {
				baseline = snapshotForm(form);
				sync();
			},
			sync: sync
		};
	}

	// Toggle form protection — save immediately via admin-ajax.
	$(document).on('change', '.ld-fg-toggle-form', function () {
		var $input = $(this);
		if ($input.data('busy')) {
			return;
		}
		var wanted = $input.prop('checked');
		$input.data('busy', true);

		post('ld_fg_toggle_form', {
			integration: $input.attr('data-integration') || '',
			form_id: $input.attr('data-form-id') || '',
			protect: wanted ? '1' : '0'
		}).done(function (payload) {
			$input.data('busy', false);
			if (!payload || !payload.success) {
				$input.prop('checked', !wanted);
				toggleMessage(
					(payload && payload.data && payload.data.message) ? payload.data.message : 'Could not save form protection.',
					true
				);
				return;
			}
			$input.prop('checked', wanted);
		}).fail(function (xhr) {
			$input.data('busy', false);
			$input.prop('checked', !wanted);
			var msg = 'Could not save form protection.';
			if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
				msg = xhr.responseJSON.data.message;
			}
			toggleMessage(msg, true);
		});
	});

	// Dismiss setup
	var dismissBtn = document.querySelector('.ld-fg-dismiss-setup');
	if (dismissBtn) {
		dismissBtn.addEventListener('click', function () {
			post('ld_fg_dismiss_setup', {}).then(function () {
				window.location.reload();
			});
		});
	}

	// Test provider
	document.querySelectorAll('.ld-fg-test-provider').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var card = btn.closest('.ld-fg-provider-card');
			var provider = btn.getAttribute('data-provider');
			var keyInput = card ? card.querySelector('.ld-fg-provider-api-key') : null;
			var modelSelect = card ? card.querySelector('.ld-fg-provider-model') : null;
			btn.disabled = true;
			post('ld_fg_test_provider', {
				provider: provider,
				api_key: keyInput ? keyInput.value : '',
				model: modelSelect ? modelSelect.value : ''
			}).then(function (payload) {
				btn.disabled = false;
				var result = card ? card.querySelector('.ld-fg-provider-test-result') : null;
				if (result) {
					result.hidden = false;
					result.textContent = payload.success
						? (payload.data && payload.data.message ? payload.data.message : 'OK')
						: (payload.data && payload.data.message ? payload.data.message : 'Failed');
				}
			});
		});
	});

	// Save provider key — enabled only when the API key field has a value.
	document.querySelectorAll('.ld-fg-provider-card').forEach(function (card) {
		var keyInput = card.querySelector('.ld-fg-provider-api-key');
		var saveBtn = card.querySelector('.ld-fg-save-provider-key');
		if (!keyInput || !saveBtn) {
			return;
		}

		function syncSaveKeyState() {
			var hasValue = String(keyInput.value || '').trim().length > 0;
			saveBtn.disabled = !hasValue;
		}

		['input', 'change', 'paste', 'keyup'].forEach(function (evt) {
			keyInput.addEventListener(evt, function () {
				// Paste populates value after the event in some browsers.
				window.setTimeout(syncSaveKeyState, 0);
			});
		});
		syncSaveKeyState();
	});

	document.querySelectorAll('.ld-fg-save-provider-key').forEach(function (btn) {
		btn.addEventListener('click', function () {
			if (btn.disabled) {
				return;
			}
			var card = btn.closest('.ld-fg-provider-card');
			var provider = btn.getAttribute('data-provider');
			var keyInput = card ? card.querySelector('.ld-fg-provider-api-key') : null;
			var modelSelect = card ? card.querySelector('.ld-fg-provider-model') : null;
			var apiKey = keyInput ? String(keyInput.value || '').trim() : '';
			if (!apiKey) {
				return;
			}
			var settings = { providers: {} };
			settings.providers[provider] = {
				enabled: 1,
				api_key: apiKey,
				model: modelSelect ? modelSelect.value : ''
			};
			btn.disabled = true;
			post('ld_fg_save_settings', { settings: JSON.stringify(settings) }).then(function () {
				window.location.reload();
			}).fail(function () {
				btn.disabled = false;
			});
		});
	});

	// Log drawer
	var drawer = document.getElementById('ld-fg-log-drawer');
	var overlay = document.getElementById('ld-fg-drawer-overlay');
	var activeLogId = 0;
	var i18n = (typeof ldFgAdmin !== 'undefined' && ldFgAdmin.i18n) ? ldFgAdmin.i18n : {};

	function closeDrawer() {
		if (!drawer || !overlay) return;
		drawer.classList.add('translate-x-full');
		overlay.classList.add('opacity-0');
		setTimeout(function () {
			drawer.classList.add('hidden');
			overlay.classList.add('hidden');
		}, 300);
	}

	function setText(el, value) {
		if (!el) return;
		el.textContent = value == null || value === '' ? '—' : String(value);
	}

	function highlightFlagged(text) {
		var escaped = String(text)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
		return escaped.replace(
			/(\[url=[^\]]+\][^\[]*\[\/url\]|https?:\/\/[^\s<]+)/gi,
			'<span class="bg-error-container/50 text-error">$1</span>'
		);
	}

	function normalizeFields(fields, snippet) {
		var out = [];
		if (fields && typeof fields === 'object') {
			if (Array.isArray(fields)) {
				fields.forEach(function (value, index) {
					if (value == null || String(value).trim() === '') return;
					out.push({
						label: (i18n.fieldLabel || 'Field') + ' ' + (index + 1),
						value: String(value)
					});
				});
			} else {
				Object.keys(fields).forEach(function (key) {
					var value = fields[key];
					if (value == null || String(value).trim() === '') return;
					out.push({
						label: String(key),
						value: Array.isArray(value) ? value.join(', ') : String(value)
					});
				});
			}
		}
		if (!out.length && snippet) {
			out.push({
				label: i18n.capturedContent || 'Captured content',
				value: String(snippet)
			});
		}
		return out;
	}

	function renderCapturedFields(container, fields, snippet) {
		if (!container) return;
		container.innerHTML = '';
		var items = normalizeFields(fields, snippet);
		if (!items.length) {
			container.innerHTML = '<p class="font-body-sm text-body-sm text-on-surface-variant">' +
				(i18n.noCapturedFields || 'No captured fields for this entry.') + '</p>';
			return;
		}
		var longest = 0;
		items.forEach(function (item) {
			if (item.value.length > longest) longest = item.value.length;
		});
		items.forEach(function (item) {
			var isLong = item.value.length >= Math.max(80, longest * 0.6) || /\[url=|https?:\/\//i.test(item.value);
			var card = document.createElement('div');
			card.className = 'bg-surface-container-lowest border border-outline rounded p-3';
			var header = document.createElement('div');
			header.className = 'font-label-md text-label-md text-on-surface-variant mb-1 text-[11px] flex items-center justify-between gap-2';
			var label = document.createElement('span');
			label.textContent = (i18n.fieldPrefix || 'Field:') + ' ' + item.label;
			header.appendChild(label);
			if (isLong) {
				var flag = document.createElement('span');
				flag.className = 'text-error bg-error-container/30 px-1.5 rounded-sm';
				flag.textContent = i18n.flaggedContent || 'Flagged Content';
				header.appendChild(flag);
			}
			var body = document.createElement('div');
			body.className = 'font-body-sm text-body-sm text-on-surface bg-surface-container px-2 py-2 rounded font-mono text-[12px] whitespace-pre-wrap break-words leading-relaxed' +
				(isLong ? ' border-l-2 border-error' : '');
			if (isLong) {
				body.innerHTML = highlightFlagged(item.value);
			} else {
				body.textContent = item.value;
			}
			card.appendChild(header);
			card.appendChild(body);
			container.appendChild(card);
		});
	}

	function updateLogsUnreadBadge(delta) {
		var badge = document.getElementById('ld-fg-logs-unread-badge');
		if (!badge) {
			return;
		}

		var current = parseInt(badge.textContent, 10) || 0;
		if (badge.textContent.indexOf('+') !== -1) {
			current = 100;
		}

		var next = Math.max(0, current + (delta || 0));
		if (next <= 0) {
			badge.classList.add('hidden');
			badge.textContent = '0';
			badge.setAttribute('aria-label', '0 unread log entries');
			return;
		}

		badge.classList.remove('hidden');
		badge.textContent = next > 99 ? '99+' : String(next);
		badge.setAttribute(
			'aria-label',
			next === 1 ? '1 unread log entry' : next + ' unread log entries'
		);
	}

	function markLogRead(row) {
		if (!row || row.getAttribute('data-read') === '1') {
			return;
		}

		var logId = parseInt(row.getAttribute('data-log-id'), 10) || 0;
		if (!logId) {
			return;
		}

		row.setAttribute('data-read', '1');
		row.classList.remove('is-unread');
		row.classList.add('is-read');

		updateLogsUnreadBadge(-1);
		post('ld_fg_mark_log_read', { log_id: String(logId) });
	}

	function openDrawer(row) {
		if (!drawer || !overlay || !row) return;
		var raw = row.getAttribute('data-log') || '{}';
		var data;
		try {
			data = JSON.parse(raw);
		} catch (err) {
			data = {};
		}

		activeLogId = parseInt(data.id || row.getAttribute('data-log-id'), 10) || 0;

		var formEl = document.getElementById('ld-fg-drawer-form');
		var dateEl = document.getElementById('ld-fg-drawer-date-text');
		var titleEl = document.getElementById('ld-fg-drawer-title');
		var reasonEl = document.getElementById('ld-fg-drawer-reason');
		var fieldsEl = document.getElementById('ld-fg-drawer-fields');
		var integrationEl = document.querySelector('#ld-fg-drawer-integration [data-value]');
		var layerEl = document.querySelector('#ld-fg-drawer-layer [data-value]');
		var layerIcon = document.querySelector('#ld-fg-drawer-layer [data-icon]');
		var actionEl = document.getElementById('ld-fg-drawer-action');
		var providerEl = document.querySelector('#ld-fg-drawer-provider [data-value]');
		var providerBadge = document.getElementById('ld-fg-drawer-provider-badge');
		var confLabel = document.getElementById('ld-fg-drawer-confidence-label');
		var confBar = document.getElementById('ld-fg-drawer-confidence-bar');
		var badge = document.getElementById('ld-fg-drawer-verdict-badge');
		var quarantineActions = document.getElementById('ld-fg-drawer-quarantine-actions');

		setText(formEl, data.form);
		setText(dateEl, data.date);
		setText(titleEl, data.title || (i18n.submissionBlocked || 'Form Submission Blocked'));
		setText(reasonEl, data.reason);
		setText(integrationEl, data.integration);
		setText(layerEl, data.layer);
		setText(actionEl, data.action);
		setText(providerEl, data.provider);

		if (layerIcon) {
			layerIcon.textContent = data.layer_key === 'ai' ? 'psychology' : 'smart_toy';
		}

		if (providerBadge) {
			var key = String(data.provider_key || '');
			if (key) {
				providerBadge.textContent = key.charAt(0).toUpperCase();
				providerBadge.classList.remove('hidden');
			} else {
				providerBadge.textContent = '';
				providerBadge.classList.add('hidden');
			}
		}

		var conf = parseInt(data.confidence, 10) || 0;
		if (confLabel) confLabel.textContent = conf + '%';
		if (confBar) confBar.style.width = Math.max(0, Math.min(100, conf)) + '%';

		if (badge) {
			var icon = badge.querySelector('[data-icon]');
			var label = badge.querySelector('[data-label]');
			badge.classList.remove('bg-error-container', 'text-on-error-container', 'bg-tertiary-container', 'text-on-tertiary-container', 'bg-[#e8f5e9]', 'text-[#2e7d32]');
			if (data.verdict === 'quarantined') {
				badge.classList.add('bg-tertiary-container', 'text-on-tertiary-container');
				if (icon) icon.textContent = 'inventory_2';
			} else if (data.verdict === 'allowed' || data.verdict === 'clean') {
				badge.classList.add('bg-[#e8f5e9]', 'text-[#2e7d32]');
				if (icon) icon.textContent = 'check_circle';
			} else {
				badge.classList.add('bg-error-container', 'text-on-error-container');
				if (icon) icon.textContent = 'block';
			}
			if (label) label.textContent = data.verdict_label || data.verdict || 'Blocked';
		}

		if (quarantineActions) {
			quarantineActions.classList.toggle('hidden', !data.is_quarantine);
		}

		renderCapturedFields(fieldsEl, data.fields, data.snippet);

		overlay.classList.remove('hidden', 'opacity-0');
		drawer.classList.remove('hidden', 'translate-x-full');
		drawer.setAttribute('aria-hidden', 'false');
		overlay.setAttribute('aria-hidden', 'false');

		markLogRead(row);
	}

	document.querySelectorAll('.ld-fg-log-view').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var row = btn.closest('.ld-fg-log-row');
			if (row) openDrawer(row);
		});
	});

	document.querySelectorAll('.ld-fg-log-row').forEach(function (row) {
		row.addEventListener('click', function (e) {
			if (e.target.closest('button')) return;
			openDrawer(row);
		});
		row.style.cursor = 'pointer';
	});

	document.querySelectorAll('.ld-fg-drawer-close').forEach(function (btn) {
		btn.addEventListener('click', closeDrawer);
	});
	if (overlay) overlay.addEventListener('click', closeDrawer);

	document.querySelectorAll('.ld-fg-release-quarantine').forEach(function (btn) {
		btn.addEventListener('click', function () {
			if (!activeLogId) return;
			post('ld_fg_release_quarantine', { log_id: String(activeLogId) }).then(function () {
				closeDrawer();
				window.location.reload();
			});
		});
	});

	document.querySelectorAll('.ld-fg-discard-quarantine').forEach(function (btn) {
		btn.addEventListener('click', function () {
			if (!activeLogId) return;
			post('ld_fg_discard_quarantine', { log_id: String(activeLogId) }).then(function () {
				closeDrawer();
				window.location.reload();
			});
		});
	});

	// Settings form
	var settingsForm = document.getElementById('ld-fg-settings-form');
	var settingsSaveBtn = document.getElementById('ld-fg-save-btn');
	var settingsDirty = null;
	if (settingsForm && settingsSaveBtn) {
		settingsDirty = bindDirtySave(settingsForm, settingsSaveBtn);
		settingsForm.addEventListener('submit', function (e) {
			e.preventDefault();
			if (!settingsDirty.isDirty()) {
				return;
			}
			if (!settingsForm.checkValidity()) {
				settingsForm.reportValidity();
				return;
			}
			var settings = {
				enabled: document.getElementById('ld-fg-master-toggle') && document.getElementById('ld-fg-master-toggle').checked ? 1 : 0,
				retention_days: document.getElementById('ld-fg-log-retention') ? document.getElementById('ld-fg-log-retention').value : 90,
				delete_on_uninstall: document.getElementById('ld-fg-delete-on-uninstall') && document.getElementById('ld-fg-delete-on-uninstall').checked ? 1 : 0,
				privacy_ack: document.getElementById('ld-fg-privacy-ack') && document.getElementById('ld-fg-privacy-ack').checked ? 1 : 0,
				protect_core_comments: document.getElementById('ld-fg-protect-comments') && document.getElementById('ld-fg-protect-comments').checked ? 1 : 0
			};
			var status = document.getElementById('ld-fg-save-status');
			settingsSaveBtn.disabled = true;
			post('ld_fg_save_settings', { settings: JSON.stringify(settings) }).then(function (payload) {
				if (payload && payload.success) {
					settingsDirty.markClean();
					if (status) {
						status.classList.remove('opacity-0');
						status.classList.add('opacity-100');
						setTimeout(function () {
							status.classList.remove('opacity-100');
							status.classList.add('opacity-0');
						}, 3000);
					}
					return;
				}
				settingsDirty.sync();
			}).fail(function () {
				settingsDirty.sync();
			});
		});
	}

	// Protection page save
	var protectionSave = document.querySelector('.ld-fg-save-protection');
	var protectionForm = document.getElementById('ld-fg-protection-form');
	var protectionDirty = null;
	if (protectionSave && protectionForm) {
		protectionDirty = bindDirtySave(protectionForm, protectionSave);
		protectionSave.addEventListener('click', function () {
			if (!protectionDirty.isDirty() || protectionSave.disabled) {
				return;
			}
			var settings = {};
			var actionRadio = protectionForm.querySelector('input[name="default_action"]:checked');
			var failRadio = protectionForm.querySelector('input[name="ai_fail_mode"]:checked');
			settings.default_action = actionRadio ? actionRadio.value : 'silent_discard';
			settings.ai_fail_mode = failRadio ? failRadio.value : 'fail_open';
			settings.ai_enabled = protectionForm.querySelector('.ld-fg-ai-enabled') && protectionForm.querySelector('.ld-fg-ai-enabled').checked ? 1 : 0;
			settings.heuristics = {};
			protectionForm.querySelectorAll('.ld-fg-heuristic').forEach(function (cb) {
				var m = cb.name.match(/heuristics\[(\w+)\]/);
				if (m) settings.heuristics[m[1]] = cb.checked ? 1 : 0;
			});
			var range = protectionForm.querySelector('.ld-fg-confidence-block');
			if (range) settings.confidence_block = parseInt(range.value, 10) / 100;
			protectionSave.disabled = true;
			post('ld_fg_save_settings', { settings: JSON.stringify(settings) }).then(function (payload) {
				if (payload && payload.success === false) {
					protectionDirty.sync();
					return;
				}
				protectionDirty.markClean();
				protectionSave.innerHTML = '<span class="ld-fg-icon material-symbols-outlined text-[18px]">check</span> Saved';
				setTimeout(function () {
					protectionSave.innerHTML = '<span class="ld-fg-icon material-symbols-outlined text-[18px]">save</span> Save Changes';
					protectionDirty.sync();
				}, 2000);
			}).fail(function () {
				protectionDirty.sync();
			});
		});

		var thresholdRange = protectionForm.querySelector('.ld-fg-confidence-block');
		if (thresholdRange) {
			thresholdRange.addEventListener('input', function () {
				var val = document.getElementById('ld-fg-threshold-val');
				var bar = protectionForm.querySelector('.ld-fg-threshold-bar');
				if (val) val.textContent = thresholdRange.value + '%';
				if (bar) bar.style.width = thresholdRange.value + '%';
			});
		}
	}

	// Forms search/filter
	var searchInput = document.querySelector('.ld-fg-forms-search');
	var filterSelect = document.querySelector('.ld-fg-filter-integration');
	function filterForms() {
		var q = searchInput ? searchInput.value.toLowerCase() : '';
		var integration = filterSelect ? filterSelect.value : 'all';
		document.querySelectorAll('.ld-fg-form-row').forEach(function (row) {
			var matchQ = !q || (row.getAttribute('data-title') || '').indexOf(q) !== -1;
			var matchI = integration === 'all' || row.getAttribute('data-integration') === integration;
			row.style.display = matchQ && matchI ? '' : 'none';
		});
	}
	if (searchInput) searchInput.addEventListener('input', filterForms);
	if (filterSelect) filterSelect.addEventListener('change', filterForms);

	// Dashboard chart
	if (typeof window.Chart !== 'undefined' && document.getElementById('ld-fg-block-chart') && typeof ldFgDashboard !== 'undefined') {
		var chartData = ldFgDashboard.chartData || [];
		new Chart(document.getElementById('ld-fg-block-chart'), {
			type: 'bar',
			data: {
				labels: chartData.map(function (d) { return d.label; }),
				datasets: [
					{
						label: 'Heuristic',
						data: chartData.map(function (d) { return d.heuristic; }),
						backgroundColor: '#005891'
					},
					{
						label: 'AI',
						data: chartData.map(function (d) { return d.ai; }),
						backgroundColor: '#85c0fe'
					}
				]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: { legend: { display: false } },
				scales: {
					x: { stacked: true },
					y: { stacked: true, beginAtZero: true }
				}
			}
		});
	}
})(jQuery);

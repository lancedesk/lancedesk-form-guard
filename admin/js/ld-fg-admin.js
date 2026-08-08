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

	// Save provider key
	document.querySelectorAll('.ld-fg-save-provider-key').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var card = btn.closest('.ld-fg-provider-card');
			var provider = btn.getAttribute('data-provider');
			var keyInput = card ? card.querySelector('.ld-fg-provider-api-key') : null;
			var modelSelect = card ? card.querySelector('.ld-fg-provider-model') : null;
			var settings = { providers: {} };
			settings.providers[provider] = {
				enabled: 1,
				api_key: keyInput ? keyInput.value : '',
				model: modelSelect ? modelSelect.value : ''
			};
			post('ld_fg_save_settings', { settings: JSON.stringify(settings) }).then(function () {
				window.location.reload();
			});
		});
	});

	// Log drawer
	var drawer = document.getElementById('ld-fg-log-drawer');
	var overlay = document.getElementById('ld-fg-drawer-overlay');
	var activeLogId = 0;

	function closeDrawer() {
		if (!drawer || !overlay) return;
		drawer.classList.add('translate-x-full');
		overlay.classList.add('opacity-0');
		setTimeout(function () {
			drawer.classList.add('hidden');
			overlay.classList.add('hidden');
		}, 300);
	}

	function openDrawer(row) {
		if (!drawer || !overlay) return;
		activeLogId = parseInt(row.getAttribute('data-log-id'), 10) || 0;
		var cells = row.querySelectorAll('td');
		var formEl = document.getElementById('ld-fg-drawer-form');
		var dateEl = document.getElementById('ld-fg-drawer-date');
		var reasonEl = document.getElementById('ld-fg-drawer-reason');
		var snippetEl = document.getElementById('ld-fg-drawer-snippet');
		if (formEl && cells[1]) formEl.textContent = cells[1].innerText.trim();
		if (dateEl && cells[0]) dateEl.lastChild.textContent = cells[0].innerText.trim();
		if (reasonEl && cells[5]) reasonEl.textContent = cells[5].getAttribute('title') || cells[5].innerText.trim();
		if (snippetEl && cells[5]) snippetEl.textContent = cells[5].innerText.trim();
		overlay.classList.remove('hidden', 'opacity-0');
		drawer.classList.remove('hidden', 'translate-x-full');
	}

	document.querySelectorAll('.ld-fg-log-view').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var row = btn.closest('.ld-fg-log-row');
			if (row) openDrawer(row);
		});
	});

	document.querySelectorAll('.ld-fg-drawer-close').forEach(function (btn) {
		btn.addEventListener('click', closeDrawer);
	});
	if (overlay) overlay.addEventListener('click', closeDrawer);

	document.querySelectorAll('.ld-fg-release-quarantine').forEach(function (btn) {
		btn.addEventListener('click', function () {
			if (!activeLogId) return;
			post('ld_fg_release_quarantine', { log_id: String(activeLogId) }).then(closeDrawer);
		});
	});

	document.querySelectorAll('.ld-fg-discard-quarantine').forEach(function (btn) {
		btn.addEventListener('click', function () {
			if (!activeLogId) return;
			post('ld_fg_discard_quarantine', { log_id: String(activeLogId) }).then(closeDrawer);
		});
	});

	// Settings form
	var settingsForm = document.getElementById('ld-fg-settings-form');
	if (settingsForm) {
		settingsForm.addEventListener('submit', function (e) {
			e.preventDefault();
			if (!settingsForm.checkValidity()) {
				settingsForm.reportValidity();
				return;
			}
			var settings = {
				enabled: document.getElementById('ld-fg-master-toggle') && document.getElementById('ld-fg-master-toggle').checked ? 1 : 0,
				retention_days: document.getElementById('ld-fg-log-retention') ? document.getElementById('ld-fg-log-retention').value : 90,
				delete_on_uninstall: document.getElementById('ld-fg-delete-on-uninstall') && document.getElementById('ld-fg-delete-on-uninstall').checked ? 1 : 0,
				privacy_ack: document.getElementById('ld-fg-privacy-ack') && document.getElementById('ld-fg-privacy-ack').checked ? 1 : 0
			};
			var saveBtn = document.getElementById('ld-fg-save-btn');
			var status = document.getElementById('ld-fg-save-status');
			if (saveBtn) saveBtn.disabled = true;
			post('ld_fg_save_settings', { settings: JSON.stringify(settings) }).then(function (payload) {
				if (saveBtn) saveBtn.disabled = false;
				if (status && payload.success) {
					status.classList.remove('opacity-0');
					status.classList.add('opacity-100');
					setTimeout(function () {
						status.classList.remove('opacity-100');
						status.classList.add('opacity-0');
					}, 3000);
				}
			});
		});
	}

	// Protection page save
	var protectionSave = document.querySelector('.ld-fg-save-protection');
	var protectionForm = document.getElementById('ld-fg-protection-form');
	if (protectionSave && protectionForm) {
		protectionSave.addEventListener('click', function () {
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
			post('ld_fg_save_settings', { settings: JSON.stringify(settings) }).then(function () {
				protectionSave.textContent = 'Saved';
				setTimeout(function () { protectionSave.innerHTML = '<span class="ld-fg-icon material-symbols-outlined text-[18px]">save</span> Save Changes'; }, 2000);
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

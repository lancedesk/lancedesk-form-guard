/**
 * LanceDesk Form Guard — front-end traps (honeypot + render time).
 *
 * Refreshes render timestamps, injects missing fields, and appends traps
 * to Ninja/WPForms-style AJAX payloads that omit unknown inputs.
 */
(function () {
	'use strict';

	if (typeof window.ldFgFront === 'undefined') {
		return;
	}

	var cfg = window.ldFgFront;
	var now = function () {
		return Math.floor(Date.now() / 1000);
	};

	function isProtected(integration, formId) {
		var list = cfg.protected && cfg.protected[integration];
		if (!list || !list.length) {
			return false;
		}
		formId = String(formId);
		return list.indexOf('*') !== -1 || list.indexOf(formId) !== -1;
	}

	function buildTraps(integration, formId) {
		var wrap = document.createElement('div');
		wrap.className = 'ld-fg-traps';
		wrap.setAttribute('data-ld-fg-integration', integration);
		wrap.setAttribute('data-ld-fg-form', String(formId));

		if (cfg.tooFast) {
			var rt = document.createElement('input');
			rt.type = 'hidden';
			rt.name = 'ld_fg_rt';
			rt.className = 'ld-fg-rt';
			rt.value = String(now());
			rt.setAttribute('autocomplete', 'off');
			wrap.appendChild(rt);
		}

		if (cfg.honeypot) {
			var hpWrap = document.createElement('div');
			hpWrap.className = 'ld-fg-hp';
			hpWrap.setAttribute('aria-hidden', 'true');

			var id = 'ld-fg-' + integration + '-' + formId + '-hp';
			var label = document.createElement('label');
			label.htmlFor = id;
			label.textContent = 'Leave this field empty';

			var input = document.createElement('input');
			input.type = 'text';
			input.name = 'ld_fg_hp';
			input.id = id;
			input.value = '';
			input.tabIndex = -1;
			input.setAttribute('autocomplete', 'off');

			hpWrap.appendChild(label);
			hpWrap.appendChild(input);
			wrap.appendChild(hpWrap);
		}

		return wrap;
	}

	function ensureTraps(form, integration, formId) {
		if (!form || !isProtected(integration, formId)) {
			return;
		}

		var existing = form.querySelector('.ld-fg-traps');
		if (!existing) {
			form.appendChild(buildTraps(integration, formId));
			existing = form.querySelector('.ld-fg-traps');
		}

		var rt = form.querySelector('input.ld-fg-rt, input[name="ld_fg_rt"]');
		if (rt && cfg.tooFast) {
			rt.value = String(now());
		}
	}

	function scan() {
		// Contact Form 7
		document.querySelectorAll('form.wpcf7-form').forEach(function (form) {
			var unit = form.closest('.wpcf7');
			var id = '';
			if (unit) {
				id = unit.getAttribute('data-wpcf7-id') || unit.id.replace(/^wpcf7-f/, '').split('-')[0];
			}
			if (!id) {
				var hidden = form.querySelector('input[name="_wpcf7"]');
				if (hidden) {
					id = hidden.value;
				}
			}
			if (id) {
				ensureTraps(form, 'cf7', id);
			}
		});

		// WPForms
		document.querySelectorAll('form.wpforms-form').forEach(function (form) {
			var id = form.getAttribute('data-formid') || form.querySelector('input[name="wpforms[id]"]')?.value;
			if (id) {
				ensureTraps(form, 'wpforms', id);
			}
		});

		// Gravity Forms
		document.querySelectorAll('form[id^="gform_"]').forEach(function (form) {
			var id = form.getAttribute('data-formid') || (form.id || '').replace(/^gform_/, '');
			if (id) {
				ensureTraps(form, 'gravity', id);
			}
		});

		// Quform
		document.querySelectorAll('form.quform-form, form[class*="quform"]').forEach(function (form) {
			var id = form.getAttribute('data-quform-id') || form.getAttribute('data-id') || '';
			var m = (form.id || '').match(/quform[^\d]*(\d+)/i);
			if (!id && m) {
				id = m[1];
			}
			if (id) {
				ensureTraps(form, 'quform', id);
			}
		});

		// Ninja Forms containers
		document.querySelectorAll('[id^="nf-form-"][id$="-cont"]').forEach(function (cont) {
			var m = cont.id.match(/^nf-form-(\d+)-cont$/);
			if (!m) {
				return;
			}
			var form = cont.querySelector('form') || cont;
			ensureTraps(form, 'ninja', m[1]);
		});
	}

	function readTrapsNear(el) {
		var root = el && el.nodeType === 1 ? el : document;
		var hp = '';
		var rt = '';
		var hpEl = root.querySelector ? root.querySelector('input[name="ld_fg_hp"]') : null;
		var rtEl = root.querySelector ? root.querySelector('input[name="ld_fg_rt"]') : null;
		if (hpEl) {
			hp = hpEl.value || '';
		}
		if (rtEl) {
			rt = rtEl.value || '';
		}
		if (!rt && cfg.tooFast) {
			rt = String(now());
		}
		return { hp: hp, rt: rt };
	}

	function appendToParamString(data, traps) {
		if (typeof data !== 'string') {
			return data;
		}
		if (cfg.honeypot && data.indexOf('ld_fg_hp=') === -1) {
			data += '&ld_fg_hp=' + encodeURIComponent(traps.hp);
		}
		if (cfg.tooFast && data.indexOf('ld_fg_rt=') === -1) {
			data += '&ld_fg_rt=' + encodeURIComponent(traps.rt || String(now()));
		}
		return data;
	}

	function appendToFormData(fd, traps) {
		if (!fd || typeof fd.append !== 'function') {
			return fd;
		}
		try {
			if (cfg.honeypot && !fd.has('ld_fg_hp')) {
				fd.append('ld_fg_hp', traps.hp);
			}
			if (cfg.tooFast && !fd.has('ld_fg_rt')) {
				fd.append('ld_fg_rt', traps.rt || String(now()));
			}
		} catch (e) {
			/* older browsers */
		}
		return fd;
	}

	// Patch jQuery AJAX used by Ninja Forms / older WPForms.
	function patchJqueryAjax() {
		if (!window.jQuery || !window.jQuery.ajaxPrefilter) {
			return;
		}
		window.jQuery.ajaxPrefilter(function (options) {
			if (!options || !options.data) {
				return;
			}
			var dataStr = typeof options.data === 'string' ? options.data : '';
			var isNinja = dataStr.indexOf('nf_ajax_submit') !== -1 || dataStr.indexOf('ninja_forms') !== -1;
			var isWpforms = dataStr.indexOf('wpforms') !== -1 && dataStr.indexOf('wpforms[id]') !== -1;

			if (!isNinja && !isWpforms && typeof options.data === 'object') {
				return;
			}

			var traps = { hp: '', rt: String(now()) };
			if (isNinja) {
				var formMatch = dataStr.match(/formData%5Bid%5D=(\d+)|"id"\s*:\s*"?(\d+)/);
				var nid = formMatch ? (formMatch[1] || formMatch[2]) : '';
				if (nid && isProtected('ninja', nid)) {
					var cont = document.getElementById('nf-form-' + nid + '-cont');
					traps = readTrapsNear(cont || document);
					if (typeof options.data === 'string') {
						options.data = appendToParamString(options.data, traps);
					}
				}
			}
		});
	}

	// fetch() fallback for modern stacks.
	function patchFetch() {
		if (!window.fetch) {
			return;
		}
		var original = window.fetch;
		window.fetch = function (input, init) {
			try {
				init = init || {};
				var url = typeof input === 'string' ? input : (input && input.url) || '';
				var body = init.body;
				if (body && typeof body === 'string' && (body.indexOf('nf_ajax_submit') !== -1 || body.indexOf('wpcf7') !== -1)) {
					var traps = readTrapsNear(document);
					init = Object.assign({}, init, { body: appendToParamString(body, traps) });
				} else if (body && typeof FormData !== 'undefined' && body instanceof FormData) {
					init = Object.assign({}, init, { body: appendToFormData(body, readTrapsNear(document)) });
				}
				void url;
			} catch (e) {
				/* ignore */
			}
			return original.call(this, input, init);
		};
	}

	function boot() {
		scan();
		patchJqueryAjax();
		patchFetch();

		// Ninja Forms re-renders asynchronously.
		document.addEventListener('nfFormReady', function () {
			scan();
		});

		if (window.jQuery) {
			window.jQuery(document).on('nfFormReady wpformsReady', function () {
				scan();
			});
		}

		// Late content / page builders.
		window.setTimeout(scan, 500);
		window.setTimeout(scan, 1500);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();

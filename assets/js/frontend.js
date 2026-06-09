(function () {
	'use strict';

	var floating = document.getElementById('hpk-pp-floating');
	if (!floating) return;

	var toggle = floating.querySelector('.hpk-pp-toggle');
	var panel = floating.querySelector('.hpk-pp-panel');
	var closeBtn = floating.querySelector('.hpk-pp-close');
	var storageKey = 'hpk_pp_floating_closed';
	var rememberClosed = window.hpkPpFront && hpkPpFront.rememberClosed;

	function isOpen() {
		return floating.classList.contains('is-open');
	}

	function openPanel() {
		panel.removeAttribute('hidden');
		floating.classList.add('is-open');
		toggle.setAttribute('aria-expanded', 'true');
		if (rememberClosed) {
			try { localStorage.removeItem(storageKey); } catch (e) {}
		}
	}

	function closePanel() {
		floating.classList.remove('is-open');
		toggle.setAttribute('aria-expanded', 'false');
		setTimeout(function () {
			if (!isOpen()) panel.setAttribute('hidden', '');
		}, 250);
		if (rememberClosed) {
			try { localStorage.setItem(storageKey, '1'); } catch (e) {}
		}
	}

	function togglePanel() {
		if (isOpen()) closePanel();
		else openPanel();
	}

	if (rememberClosed) {
		try {
			if (localStorage.getItem(storageKey) === '1') {
				/* stay closed */
			}
		} catch (e) {}
	}

	toggle.addEventListener('click', togglePanel);
	closeBtn.addEventListener('click', function (e) {
		e.stopPropagation();
		closePanel();
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && isOpen()) closePanel();
	});
})();

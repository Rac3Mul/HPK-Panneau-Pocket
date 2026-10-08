(function () {
	'use strict';

	document.querySelectorAll('.hpk-pp-agenda-wrap').forEach(function (wrap) {
		var items = wrap.querySelectorAll('.hpk-pp-agenda__item');
		var empty = wrap.querySelector('.hpk-pp-agenda__none');
		var modal = wrap.querySelector('.hpk-pp-agenda-modal');
		var slot = modal ? modal.querySelector('.hpk-pp-agenda-modal__content') : null;

		wrap.querySelectorAll('.hpk-pp-agenda__filter').forEach(function (button) {
			button.addEventListener('click', function () {
				var date = button.getAttribute('data-date') || '';
				wrap.querySelectorAll('.hpk-pp-agenda__filter').forEach(function (other) {
					other.classList.toggle('is-active', other === button);
				});
				var visible = 0;
				items.forEach(function (item) {
					var show = !date || item.getAttribute('data-date') === date;
					item.hidden = !show;
					if (show) visible += 1;
				});
				if (empty) empty.hidden = visible > 0;
			});
		});

		function closeModal() {
			if (!modal) return;
			modal.hidden = true;
			document.body.classList.remove('hpk-pp-agenda-open');
			if (slot) slot.innerHTML = '';
		}

		function openModal(item) {
			if (!modal || !slot) return;
			var source = item.querySelector('.hpk-pp-agenda__source');
			if (!source) return;
			slot.innerHTML = '';
			slot.appendChild(source.content.cloneNode(true));
			modal.hidden = false;
			document.body.classList.add('hpk-pp-agenda-open');
			var close = modal.querySelector('.hpk-pp-agenda-modal__close');
			if (close) close.focus();
			var picture = slot.querySelector('.hpk-pp-agenda__full-image');
			if (picture) {
				picture.addEventListener('click', function () {
					picture.classList.toggle('is-zoomed');
				});
			}
		}

		items.forEach(function (item) {
			var opener = item.querySelector('.hpk-pp-agenda__open');
			if (!opener) return;
			opener.addEventListener('click', function () {
				openModal(item);
			});
		});

		if (modal) {
			modal.addEventListener('click', function (event) {
				if (event.target.closest('[data-close]')) closeModal();
			});
		}

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && modal && !modal.hidden) closeModal();
		});
	});
})();

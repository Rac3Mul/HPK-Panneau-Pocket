(function () {
	'use strict';

	document.querySelectorAll('.hpk-pp-agenda-wrap').forEach(function (wrap) {
		var items = wrap.querySelectorAll('.hpk-pp-agenda__item');
		var empty = wrap.querySelector('.hpk-pp-agenda__none');
		var modal = wrap.querySelector('.hpk-pp-agenda-modal');
		var slot = modal ? modal.querySelector('.hpk-pp-agenda-modal__content') : null;
		var slider = wrap.querySelector('.hpk-pp-slider');
		var sliderIndex = 0;
		var sliderTimer = null;

		function sliderGo(index) {
			if (!slider) return;
			var slides = slider.querySelectorAll('.hpk-pp-slider__slide');
			var track = slider.querySelector('.hpk-pp-slider__track');
			if (!track || !slides.length) return;
			sliderIndex = (index + slides.length) % slides.length;
			track.style.transform = 'translateX(' + (-sliderIndex * 100) + '%)';
			slider.querySelectorAll('.hpk-pp-slider__dot').forEach(function (dot, n) {
				var on = n === sliderIndex;
				dot.classList.toggle('is-active', on);
				dot.setAttribute('aria-selected', on ? 'true' : 'false');
			});
		}

		function sliderStop() {
			if (sliderTimer) {
				clearInterval(sliderTimer);
				sliderTimer = null;
			}
		}

		function sliderStart() {
			if (!slider || sliderTimer) return;
			var delay = parseInt(slider.getAttribute('data-autoplay') || '0', 10);
			var slides = slider.querySelectorAll('.hpk-pp-slider__slide');
			var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			if (reduce || delay < 2000 || slides.length < 2 || (modal && !modal.hidden)) return;
			sliderTimer = setInterval(function () {
				sliderGo(sliderIndex + 1);
			}, delay);
		}

		if (slider) {
			var prev = slider.querySelector('.hpk-pp-slider__nav--prev');
			var next = slider.querySelector('.hpk-pp-slider__nav--next');
			if (prev) {
				prev.addEventListener('click', function (event) {
					event.stopPropagation();
					sliderGo(sliderIndex - 1);
					sliderStart();
				});
			}
			if (next) {
				next.addEventListener('click', function (event) {
					event.stopPropagation();
					sliderGo(sliderIndex + 1);
					sliderStart();
				});
			}
			slider.querySelectorAll('.hpk-pp-slider__dot').forEach(function (dot) {
				dot.addEventListener('click', function (event) {
					event.stopPropagation();
					sliderGo(parseInt(dot.getAttribute('data-index') || '0', 10));
					sliderStart();
				});
			});
			slider.addEventListener('mouseenter', sliderStop);
			slider.addEventListener('mouseleave', sliderStart);
			slider.addEventListener('focusin', sliderStop);
			slider.addEventListener('focusout', function (event) {
				if (!slider.contains(event.relatedTarget)) sliderStart();
			});
			var touchX = 0;
			slider.addEventListener('touchstart', function (event) {
				touchX = event.changedTouches[0].clientX;
				sliderStop();
			}, { passive: true });
			slider.addEventListener('touchend', function (event) {
				var delta = event.changedTouches[0].clientX - touchX;
				if (Math.abs(delta) > 40) sliderGo(delta < 0 ? sliderIndex + 1 : sliderIndex - 1);
				sliderStart();
			}, { passive: true });
			sliderGo(0);
			sliderStart();
		}

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
			sliderStart();
		}

		function openModal(item) {
			if (!modal || !slot) return;
			var source = item.querySelector('.hpk-pp-agenda__source');
			if (!source) return;
			slot.innerHTML = '';
			slot.appendChild(source.content.cloneNode(true));
			modal.hidden = false;
			document.body.classList.add('hpk-pp-agenda-open');
			sliderStop();
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
			opener.addEventListener('keydown', function (event) {
				if (event.key === 'Enter' || event.key === ' ') {
					event.preventDefault();
					openModal(item);
				}
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

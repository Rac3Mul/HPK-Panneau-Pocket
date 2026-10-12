(function () {
	'use strict';

	function perView() {
		if (window.matchMedia('(max-width: 700px)').matches) return 1;
		if (window.matchMedia('(max-width: 1024px)').matches) return 2;
		return 3;
	}

	document.querySelectorAll('.hpk-pp-actus').forEach(function (root) {
		var track = root.querySelector('.hpk-pp-actus__track');
		var slides = root.querySelectorAll('.hpk-pp-actus__slide');
		var dots = root.querySelector('.hpk-pp-actus__dots');
		var modal = root.querySelector('.hpk-pp-agenda-modal');
		var slot = modal ? modal.querySelector('.hpk-pp-agenda-modal__content') : null;
		var index = 0;
		var timer = null;

		function maxIndex() {
			return Math.max(0, slides.length - perView());
		}

		function go(next) {
			var max = maxIndex();
			index = Math.max(0, Math.min(max, next));
			var width = slides[0] ? slides[0].getBoundingClientRect().width : 0;
			if (track) track.style.transform = 'translateX(' + (-index * width) + 'px)';
			if (dots) {
				dots.querySelectorAll('button').forEach(function (dot, n) {
					var on = n === index;
					dot.classList.toggle('is-active', on);
					dot.setAttribute('aria-selected', on ? 'true' : 'false');
				});
			}
		}

		function buildDots() {
			if (!dots) return;
			var count = maxIndex() + 1;
			dots.innerHTML = '';
			for (var i = 0; i < count; i++) {
				var dot = document.createElement('button');
				dot.type = 'button';
				dot.className = 'hpk-pp-actus__dot' + (i === index ? ' is-active' : '');
				dot.setAttribute('aria-label', 'Actualité ' + (i + 1));
				dot.setAttribute('aria-selected', i === index ? 'true' : 'false');
				dot.setAttribute('data-index', String(i));
				dot.addEventListener('click', function (event) {
					event.stopPropagation();
					go(parseInt(this.getAttribute('data-index') || '0', 10));
					start();
				});
				dots.appendChild(dot);
			}
		}

		function stop() {
			if (timer) {
				clearInterval(timer);
				timer = null;
			}
		}

		function start() {
			if (timer) return;
			var delay = parseInt(root.getAttribute('data-autoplay') || '0', 10);
			var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			if (reduce || delay < 2000 || maxIndex() < 1 || (modal && !modal.hidden)) return;
			timer = setInterval(function () {
				var max = maxIndex();
				go(index >= max ? 0 : index + 1);
			}, delay);
		}

		function closeModal() {
			if (!modal) return;
			modal.hidden = true;
			document.body.classList.remove('hpk-pp-agenda-open');
			if (slot) slot.innerHTML = '';
			start();
		}

		function openModal(slide) {
			if (!modal || !slot) return;
			var source = slide.querySelector('.hpk-pp-actus__source');
			if (!source) return;
			slot.innerHTML = '';
			slot.appendChild(source.content.cloneNode(true));
			modal.hidden = false;
			document.body.classList.add('hpk-pp-agenda-open');
			stop();
			var close = modal.querySelector('.hpk-pp-agenda-modal__close');
			if (close) close.focus();
			var picture = slot.querySelector('.hpk-pp-agenda__full-image');
			if (picture) {
				picture.addEventListener('click', function () {
					picture.classList.toggle('is-zoomed');
				});
			}
		}

		var prev = root.querySelector('.hpk-pp-actus__nav--prev');
		var next = root.querySelector('.hpk-pp-actus__nav--next');
		if (prev) {
			prev.addEventListener('click', function (event) {
				event.stopPropagation();
				go(index <= 0 ? maxIndex() : index - 1);
				start();
			});
		}
		if (next) {
			next.addEventListener('click', function (event) {
				event.stopPropagation();
				go(index >= maxIndex() ? 0 : index + 1);
				start();
			});
		}

		slides.forEach(function (slide) {
			var card = slide.querySelector('.hpk-pp-actus__card');
			if (!card) return;
			card.addEventListener('click', function () {
				openModal(slide);
			});
			card.addEventListener('keydown', function (event) {
				if (event.key === 'Enter' || event.key === ' ') {
					event.preventDefault();
					openModal(slide);
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

		var touchX = 0;
		root.addEventListener('touchstart', function (event) {
			touchX = event.changedTouches[0].clientX;
			stop();
		}, { passive: true });
		root.addEventListener('touchend', function (event) {
			var delta = event.changedTouches[0].clientX - touchX;
			if (Math.abs(delta) > 40) go(delta < 0 ? index + 1 : index - 1);
			start();
		}, { passive: true });

		root.addEventListener('mouseenter', stop);
		root.addEventListener('mouseleave', start);

		buildDots();
		go(0);
		start();
		window.addEventListener('resize', function () {
			buildDots();
			go(index);
		});
	});
})();

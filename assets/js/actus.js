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
			if (reduce || delay < 2000 || maxIndex() < 1) return;
			timer = setInterval(function () {
				var max = maxIndex();
				go(index >= max ? 0 : index + 1);
			}, delay);
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

		var viewport = root.querySelector('.hpk-pp-actus__viewport');
		var dragging = false;
		var moved = false;
		var startX = 0;
		var startTranslate = 0;
		var activePointer = null;
		var pendingHref = '';

		function slideWidth() {
			return slides[0] ? slides[0].getBoundingClientRect().width : 0;
		}

		function translateFor(nextIndex) {
			return -nextIndex * slideWidth();
		}

		if (viewport) {
			viewport.addEventListener('pointerdown', function (event) {
				if (event.pointerType === 'mouse' && event.button !== 0) return;
				if (event.target.closest('.hpk-pp-actus__dots, button')) return;
				var link = event.target.closest('a.hpk-pp-actus__card');
				pendingHref = link ? link.href : '';
				event.preventDefault();
				dragging = true;
				moved = false;
				startX = event.clientX;
				startTranslate = translateFor(index);
				activePointer = event.pointerId;
				if (track) track.style.transition = 'none';
				root.classList.add('is-dragging');
				stop();
				if (viewport.setPointerCapture) viewport.setPointerCapture(event.pointerId);
			});

			viewport.addEventListener('pointermove', function (event) {
				if (!dragging || event.pointerId !== activePointer || !track) return;
				var dx = event.clientX - startX;
				if (Math.abs(dx) > 8) moved = true;
				if (!moved) return;
				var width = slideWidth();
				var min = -maxIndex() * width;
				var next = Math.max(min, Math.min(0, startTranslate + dx));
				track.style.transform = 'translateX(' + next + 'px)';
			});

			function finishDrag(event, followLink) {
				if (!dragging || event.pointerId !== activePointer) return;
				dragging = false;
				activePointer = null;
				root.classList.remove('is-dragging');
				if (track) track.style.transition = '';
				if (moved) {
					var width = slideWidth() || 1;
					go(Math.round(-(startTranslate + (event.clientX - startX)) / width));
					pendingHref = '';
				} else if (followLink && pendingHref) {
					window.location.href = pendingHref;
					return;
				}
				start();
			}

			viewport.addEventListener('pointerup', function (event) {
				finishDrag(event, true);
			});
			viewport.addEventListener('pointercancel', function (event) {
				finishDrag(event, false);
			});

			root.addEventListener('click', function (event) {
				if (!moved) return;
				event.preventDefault();
				event.stopPropagation();
				moved = false;
			}, true);
		}

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

/* Vectra UI: hero slider and horizontal carousels. No dependencies. */
(function () {
	'use strict';
	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	/* Carousels: native scroll-snap for touch, arrow buttons for mouse. */
	document.querySelectorAll('[data-vectra-carousel]').forEach(function (root) {
		var track = root.querySelector('.vectra-rail__track');
		var btns = root.querySelectorAll('.vectra-rail__btn');
		if (!track) return;
		function update() {
			var max = track.scrollWidth - track.clientWidth - 2;
			btns.forEach(function (b) {
				var dir = +b.dataset.dir;
				b.disabled = dir < 0 ? track.scrollLeft <= 2 : track.scrollLeft >= max;
			});
			root.classList.toggle('is-scrollable', max > 0);
		}
		btns.forEach(function (b) {
			b.addEventListener('click', function () {
				track.scrollBy({ left: +b.dataset.dir * track.clientWidth * 0.85, behavior: reduce ? 'auto' : 'smooth' });
			});
		});
		track.addEventListener('scroll', function () { window.requestAnimationFrame(update); }, { passive: true });
		window.addEventListener('resize', update);
		update();
	});

	/* Hero slider: auto-advances every 6s, pauses on hover, focus or a hidden tab. */
	document.querySelectorAll('[data-vectra-hero]').forEach(function (root) {
		var slides = root.querySelectorAll('.vectra-hero__slide');
		var dots = root.querySelectorAll('.vectra-hero__dot');
		if (slides.length < 2) return;
		var i = 0, timer = null, paused = false;
		function go(n) {
			slides[i].classList.remove('is-active');
			slides[i].setAttribute('aria-hidden', 'true');
			dots[i] && dots[i].classList.remove('is-active');
			i = (n + slides.length) % slides.length;
			slides[i].classList.add('is-active');
			slides[i].removeAttribute('aria-hidden');
			dots[i] && dots[i].classList.add('is-active');
		}
		function start() {
			if (reduce || paused || timer) return;
			timer = window.setInterval(function () { go(i + 1); }, 6000);
		}
		function stop() { window.clearInterval(timer); timer = null; }
		dots.forEach(function (d) {
			d.addEventListener('click', function () { stop(); go(+d.dataset.slide); start(); });
		});
		root.addEventListener('mouseenter', function () { paused = true; stop(); });
		root.addEventListener('mouseleave', function () { paused = false; start(); });
		root.addEventListener('focusin', function () { paused = true; stop(); });
		root.addEventListener('focusout', function () { paused = false; start(); });
		document.addEventListener('visibilitychange', function () { document.hidden ? stop() : start(); });
		/* Swipe on touch screens. */
		var x0 = null;
		root.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
		root.addEventListener('touchend', function (e) {
			if (x0 === null) return;
			var dx = e.changedTouches[0].clientX - x0;
			if (Math.abs(dx) > 40) { stop(); go(i + (dx < 0 ? 1 : -1)); start(); }
			x0 = null;
		});
		start();
	});
})();

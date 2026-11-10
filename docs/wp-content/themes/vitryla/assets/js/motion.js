/*
 * «Вітрила юності» — рух і жива взаємодія.
 *
 * Принципи:
 *  - без бібліотек, лише стандартні можливості браузера;
 *  - якщо скрипт не спрацює, сторінка однаково повністю видима;
 *  - людям, які вимкнули анімацію в системі, рух не показується.
 */
(function () {
	'use strict';

	var d = document;
	var root = d.documentElement;
	var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
	var finePointer = window.matchMedia && matchMedia('(pointer: fine)').matches;

	function ready(fn) {
		if (d.readyState !== 'loading') { fn(); } else { d.addEventListener('DOMContentLoaded', fn); }
	}

	ready(function () {

		/* ── дорожня карта: одразу показуємо поточний місяць (і попередній для контексту) ── */
		Array.prototype.forEach.call(d.querySelectorAll('.vit-rm-track'), function (t) {
			var cur = t.querySelector('.vit-rm-m.is-now');
			if (!cur) { return; }
			var target = cur.previousElementSibling || cur;
			t.scrollLeft += target.getBoundingClientRect().left - t.getBoundingClientRect().left - 4;
		});

		/* ── смуга прогресу читання й кнопка «нагору» ── */
		var bar = d.createElement('div');
		bar.className = 'vit-progress';
		bar.setAttribute('aria-hidden', 'true');
		d.body.appendChild(bar);

		var top = d.createElement('button');
		top.type = 'button';
		top.className = 'vit-top vit-ico-up';
		top.setAttribute('aria-label', 'Повернутися нагору сторінки');
		top.addEventListener('click', function () {
			window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
		});
		d.body.appendChild(top);

		var ticking = false;
		function onScroll() {
			if (ticking) { return; }
			ticking = true;
			requestAnimationFrame(function () {
				var y = window.scrollY || root.scrollTop;
				var h = root.scrollHeight - window.innerHeight;
				d.body.classList.toggle('is-scrolled', y > 30);
				bar.style.transform = 'scaleX(' + (h > 0 ? Math.min(1, y / h) : 0) + ')';
				top.classList.toggle('is-on', y > 700);
				ticking = false;
			});
		}
		window.addEventListener('scroll', onScroll, { passive: true });
		onScroll();

		/* ── смуга демо-наповнення: можна закрити на сеанс ── */
		var demo = d.querySelector('.vit-demo-bar');
		if (demo) {
			var closed = false;
			try { closed = sessionStorage.getItem('vit-demo-closed') === '1'; } catch (e) {}
			if (closed) {
				demo.hidden = true;
			} else {
				var x = d.createElement('button');
				x.type = 'button';
				x.setAttribute('aria-label', 'Сховати попередження');
				x.textContent = '×';
				x.addEventListener('click', function () {
					demo.hidden = true;
					try { sessionStorage.setItem('vit-demo-closed', '1'); } catch (e) {}
				});
				demo.appendChild(x);
			}
		}

		/* ── підсвітка карток під курсором ── */
		if (finePointer) {
			d.addEventListener('pointermove', function (e) {
				var el = e.target.closest && e.target.closest('.vit-glow');
				if (!el) { return; }
				var r = el.getBoundingClientRect();
				el.style.setProperty('--mx', (e.clientX - r.left) + 'px');
				el.style.setProperty('--my', (e.clientY - r.top) + 'px');
			}, { passive: true });
		}

		/* додаємо підсвітку блокам, які її ще не мають */
		d.querySelectorAll('.vit-bento .tile, .vit-doc, .vit-today .cell, .vit-news-list li, .vit-news-grid li')
			.forEach(function (el) { el.classList.add('vit-glow'); });

		if (reduce) { return; }

		/* ── нахил емблеми за курсором ── */
		var hero = d.querySelector('.vit-hero-panel');
		if (hero && finePointer) {
			hero.addEventListener('pointermove', function (e) {
				var r = hero.getBoundingClientRect();
				var px = (e.clientX - r.left) / r.width - 0.5;
				var py = (e.clientY - r.top) / r.height - 0.5;
				hero.style.setProperty('--rx', (-py * 10).toFixed(2) + 'deg');
				hero.style.setProperty('--ry', (px * 14).toFixed(2) + 'deg');
			});
			hero.addEventListener('pointerleave', function () {
				hero.style.setProperty('--rx', '0deg');
				hero.style.setProperty('--ry', '0deg');
			});
		}

		if (!('IntersectionObserver' in window)) { return; }

		/* ── поява блоків при прокручуванні ── */
		root.classList.add('vit-motion');

		var selector = [
			'.vit-page-body .wp-block-post-content > *',
			'.vit-front > *',
			'.vit-children > *', '.vit-cards > *', '.vit-stats > *', '.vit-team > *',
			'.vit-clubs > *', '.vit-docs > *', '.vit-bento > *', '.vit-today > *',
			'.vit-contact-cards > *', '.vit-steps > li', '.vit-timeline > li', '.vit-faq > *',
			'.vit-news-list li', '.vit-news-grid li', '.vit-reg-sec', '.vit-related',
			'.vit-myths > *', '.vit-rm-stages > li', '.vit-resp > li', '.vit-shifts > *',
			'.vit-compare > .vit-cmp', '.vit-postlist > li'
		].join(',');

		var targets = Array.prototype.slice.call(d.querySelectorAll(selector));

		// Анімуємо лише найглибший рівень: якщо елемент містить інші цілі,
		// його самого не ховаємо — інакше блоки з’являються «матрьошкою»
		// й при швидкому гортанні сторінка здається порожньою.
		targets = targets.filter(function (el) {
			return !targets.some(function (o) { return o !== el && el.contains(o); });
		});

		// Перший екран показуємо одразу, без проявлення: людина має бачити
		// головне миттєво, а рух — лише для того, що зʼявляється при гортанні.
		var fold = window.innerHeight * 0.92;
		targets = targets.filter(function (el) {
			return el.getBoundingClientRect().top > fold;
		});
		var seen = new Set();

		targets.forEach(function (el) {
			if (seen.has(el)) { return; }
			seen.add(el);
			var siblings = el.parentElement ? Array.prototype.indexOf.call(el.parentElement.children, el) : 0;
			el.style.setProperty('--i', Math.min(siblings, 6));
			el.classList.add('vit-reveal');
		});

		function done(el) {
			// Після появи повертаємо елементу його власні переходи (наведення тощо).
			el.addEventListener('transitionend', function handler(ev) {
				if (ev.propertyName !== 'opacity') { return; }
				el.classList.remove('vit-reveal', 'is-in');
				el.style.removeProperty('--i');
				el.removeEventListener('transitionend', handler);
			});
		}

		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (e) {
				if (!e.isIntersecting) { return; }
				e.target.classList.add('is-in');
				done(e.target);
				io.unobserve(e.target);
			});
		}, { rootMargin: '0px 0px -4% 0px', threshold: 0.01 });

		requestAnimationFrame(function () {
			targets.forEach(function (el) { io.observe(el); });
		});

		/* ── анімовані лічильники ── */
		function countUp(el) {
			var text = el.textContent.trim();
			var m = text.match(/^(\D*?)(\d[\d\s ]*)([.,]\d+)?(.*)$/);
			if (!m) { return; }
			var n = parseInt(m[2].replace(/[\s ]/g, ''), 10);
			if (!n || (n >= 1800 && n <= 2100 && !m[3])) { return; } // роки не крутимо
			var frac = m[3] || '';
			var dur = 1500;
			var t0 = null;
			function step(t) {
				if (!t0) { t0 = t; }
				var k = Math.min(1, (t - t0) / dur);
				var eased = 1 - Math.pow(1 - k, 3);
				el.textContent = m[1] + Math.round(n * eased).toLocaleString('uk-UA') + (k === 1 ? frac : '') + m[4];
				if (k < 1) { requestAnimationFrame(step); }
			}
			requestAnimationFrame(step);
		}

		var counters = d.querySelectorAll('.vit-stat-n, .vit-numbers .tile b, .vit-cd-n, .vit-promo-cd b');
		var cio = new IntersectionObserver(function (entries) {
			entries.forEach(function (e) {
				if (!e.isIntersecting) { return; }
				cio.unobserve(e.target);
				countUp(e.target);
			});
		}, { threshold: 0.6 });
		counters.forEach(function (el) { cio.observe(el); });
	});
})();

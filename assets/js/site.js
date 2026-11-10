/*
 * Біляївський ліцей №2 — поведінка сторінок.
 * Без бібліотек. Якщо скрипт не спрацює, сторінка все одно повністю видима.
 * Рух вимикається, якщо в системі ввімкнено «зменшити рух».
 */
(function () {
  'use strict';
  var d = document;
  var root = d.documentElement;
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  root.classList.add('js');

  function store(key, val) {
    try {
      if (val === undefined) { return JSON.parse(localStorage.getItem(key) || '{}'); }
      localStorage.setItem(key, JSON.stringify(val));
    } catch (e) { return {}; }
  }

  /* меню на телефоні */
  var tgl = d.querySelector('.nav-toggle');
  var nav = d.getElementById('nav');
  if (tgl && nav) {
    tgl.addEventListener('click', function () {
      var open = tgl.getAttribute('aria-expanded') !== 'true';
      tgl.setAttribute('aria-expanded', String(open));
      nav.classList.toggle('is-open', open);
    });
    d.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && nav.classList.contains('is-open')) { tgl.click(); tgl.focus(); }
    });
  }

  /* смуга «Зразок сайту» — сховати на цей сеанс */
  var bar = d.querySelector('.demo-bar');
  try { if (bar && sessionStorage.getItem('demo-x')) { bar.hidden = true; } } catch (e) {}
  var bx = d.querySelector('.demo-x');
  if (bx) {
    bx.addEventListener('click', function () {
      bar.hidden = true;
      try { sessionStorage.setItem('demo-x', '1'); } catch (e) {}
    });
  }

  /* панель доступності */
  var at = d.querySelector('.a11y-toggle');
  var ap = d.getElementById('a11y');
  if (at && ap) {
    var state = store('a11y') || {};
    var sync = function () {
      ap.querySelectorAll('[data-a11y]').forEach(function (b) {
        b.setAttribute('aria-pressed', String((state[b.dataset.a11y] || '') === b.dataset.v));
      });
    };
    sync();
    at.addEventListener('click', function () {
      ap.hidden = !ap.hidden;
      at.setAttribute('aria-expanded', String(!ap.hidden));
    });
    ap.addEventListener('click', function (e) {
      var b = e.target.closest('[data-a11y]');
      if (!b) { return; }
      var k = b.dataset.a11y;
      state[k] = b.dataset.v;
      if (b.dataset.v) { root.dataset[k] = b.dataset.v; } else { delete root.dataset[k]; }
      store('a11y', state);
      sync();
    });
    d.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !ap.hidden) { ap.hidden = true; at.setAttribute('aria-expanded', 'false'); at.focus(); }
    });
  }

  /* кнопка «нагору» */
  var top = d.querySelector('.to-top');
  if (top) {
    var onScroll = function () { top.classList.toggle('is-on', window.scrollY > 900); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    top.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' }); });
  }

  /* дорожня карта: одразу показати поточний місяць */
  d.querySelectorAll('.rm-track').forEach(function (t) {
    var cur = t.querySelector('.rm-m.is-now');
    if (!cur) { return; }
    var target = cur.previousElementSibling || cur;
    t.scrollLeft += target.getBoundingClientRect().left - t.getBoundingClientRect().left - 2;
  });

  if (reduce || !('IntersectionObserver' in window)) { return; }

  /* поява блоків при прокручуванні: лише те, що нижче першого екрана, і лише найглибший рівень */
  var sel = '.bento > li, .news-lead, .news-list li, .news-grid > li, .cards > *, .stats > *, .tiles > li, .docs > li, .team > li, .clubs > li, .numbers > li, .faq > details, .steps > li, .timeline > li, .myths > *, .compare > .compare-col, .rm-stages > li, .resp > li, .contact-list > li, .postlist > li, .promo, .cta, .callout, .shifts > *';
  var fold = window.innerHeight * 0.9;
  var items = Array.prototype.filter.call(d.querySelectorAll(sel), function (el) {
    return el.getBoundingClientRect().top > fold;
  });
  items.forEach(function (el) {
    var i = el.parentElement ? Array.prototype.indexOf.call(el.parentElement.children, el) : 0;
    el.style.setProperty('--i', Math.min(i, 6));
    el.classList.add('reveal');
  });
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) {
      if (!en.isIntersecting) { return; }
      var el = en.target;
      el.classList.add('is-in');
      io.unobserve(el);
      el.addEventListener('transitionend', function h(ev) {
        if (ev.propertyName !== 'opacity') { return; }
        el.classList.remove('reveal', 'is-in');
        el.style.removeProperty('--i');
        el.removeEventListener('transitionend', h);
      });
    });
  }, { rootMargin: '0px 0px -5% 0px', threshold: 0.01 });
  items.forEach(function (el) { io.observe(el); });
  /* запобіжник: якщо браузер пропустив подію, показуємо все, що вже на екрані */
  var sweep = function () {
    items.forEach(function (el) {
      if (el.classList.contains('reveal') && !el.classList.contains('is-in') && el.getBoundingClientRect().top < window.innerHeight) { el.classList.add('is-in'); }
    });
  };
  var sweepT;
  window.addEventListener('scroll', function () { clearTimeout(sweepT); sweepT = setTimeout(sweep, 250); }, { passive: true });
  setTimeout(sweep, 1200);

  /* лічильники */
  var cio = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) {
      if (!en.isIntersecting) { return; }
      cio.unobserve(en.target);
      var el = en.target;
      var m = el.textContent.trim().match(/^(\d[\d\s ]*)(.*)$/);
      if (!m) { return; }
      var n = parseInt(m[1].replace(/\D/g, ''), 10);
      if (!n || (n >= 1800 && n <= 2100)) { return; }
      var t0 = null;
      var step = function (t) {
        if (!t0) { t0 = t; }
        var k = Math.min(1, (t - t0) / 1300);
        el.textContent = Math.round(n * (1 - Math.pow(1 - k, 3))) + m[2];
        if (k < 1) { requestAnimationFrame(step); }
      };
      requestAnimationFrame(step);
    });
  }, { threshold: 0.6 });
  d.querySelectorAll('[data-count]').forEach(function (el) { cio.observe(el); });
})();

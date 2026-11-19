/* Portfolio — public site interactions */
(function () {
  'use strict';
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  /* Theme toggle */
  $$('[data-theme-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var next = document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
      document.documentElement.setAttribute('data-theme', next);
      try { localStorage.setItem('theme', next); } catch (e) {}
    });
  });

  /* Mobile nav */
  var nav = $('#main-nav'), navBtn = $('[data-nav-toggle]');
  if (nav && navBtn) {
    navBtn.addEventListener('click', function () {
      var open = nav.classList.toggle('open');
      navBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function (e) {
      if (nav.classList.contains('open') && !nav.contains(e.target) && !navBtn.contains(e.target)) {
        nav.classList.remove('open'); navBtn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /* Search overlay */
  var search = $('[data-search]');
  function closeSearch() { if (search) search.hidden = true; }
  $$('[data-search-open]').forEach(function (b) {
    b.addEventListener('click', function () { search.hidden = false; var i = $('input', search); setTimeout(function () { i.focus(); }, 20); });
  });
  $$('[data-search-close]').forEach(function (b) { b.addEventListener('click', closeSearch); });
  if (search) search.addEventListener('click', function (e) { if (e.target === search) closeSearch(); });

  /* Filter chips */
  var grid = $('[data-filter-grid]');
  var chips = $$('[data-filter]');
  function applyFilter(val) {
    chips.forEach(function (c) { c.classList.toggle('active', c.getAttribute('data-filter') === val); });
    $$('.filter-item', grid).forEach(function (it) {
      var cats = (it.getAttribute('data-cats') || '').split('|');
      it.classList.toggle('is-hidden', val !== '*' && cats.indexOf(val) === -1);
    });
  }
  if (grid && chips.length) {
    chips.forEach(function (c) { c.addEventListener('click', function () { applyFilter(c.getAttribute('data-filter')); }); });
    var q = new URLSearchParams(location.search).get('category');
    if (q && chips.some(function (c) { return c.getAttribute('data-filter') === q; })) applyFilter(q);
  }

  /* Tabs (project page) */
  var tabs = $$('[data-tab]');
  function showTab(name, scroll) {
    var panel = $('[data-panel="' + name + '"]');
    if (!panel) return;
    tabs.forEach(function (t) {
      var on = t.getAttribute('data-tab') === name;
      t.classList.toggle('active', on); t.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    $$('[data-panel]').forEach(function (p) { p.classList.toggle('active', p === panel); });
    if (scroll) { var tb = $('.tabs'); if (tb) window.scrollTo({ top: tb.getBoundingClientRect().top + window.scrollY - 90, behavior: 'smooth' }); }
  }
  tabs.forEach(function (t) {
    t.addEventListener('click', function () {
      showTab(t.getAttribute('data-tab'));
      history.replaceState(null, '', '#' + t.getAttribute('data-tab'));
    });
  });
  $$('[data-tab-link]').forEach(function (a) {
    a.addEventListener('click', function (e) { e.preventDefault(); showTab(a.getAttribute('data-tab-link'), true); });
  });
  if (tabs.length && location.hash) showTab(location.hash.slice(1));

  /* Lightbox */
  var lb = $('[data-lightbox]');
  if (lb) {
    var lbImg = $('img', lb), lbCap = $('figcaption', lb), group = [], idx = 0;
    function render() { var a = group[idx]; lbImg.src = a.getAttribute('href'); lbCap.textContent = a.getAttribute('data-caption') || ''; }
    function open(a) {
      var scope = a.closest('[data-gallery]') || a.closest('.masonry') || document;
      group = $$('[data-lb]', scope).filter(function (x) { return !x.closest('.is-hidden'); });
      if (group.indexOf(a) === -1) group = [a];
      idx = group.indexOf(a); render(); lb.hidden = false; document.body.style.overflow = 'hidden';
      $('.lb-prev', lb).style.visibility = $('.lb-next', lb).style.visibility = group.length > 1 ? 'visible' : 'hidden';
    }
    function close() { lb.hidden = true; lbImg.src = ''; document.body.style.overflow = ''; }
    function step(d) { idx = (idx + d + group.length) % group.length; render(); }
    document.addEventListener('click', function (e) {
      var a = e.target.closest('[data-lb]');
      if (a) { e.preventDefault(); open(a); }
    });
    $('[data-lb-close]', lb).addEventListener('click', close);
    $('[data-lb-prev]', lb).addEventListener('click', function () { step(-1); });
    $('[data-lb-next]', lb).addEventListener('click', function () { step(1); });
    lb.addEventListener('click', function (e) { if (e.target === lb) close(); });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { closeSearch(); if (!lb.hidden) close(); }
      if (lb.hidden) return;
      if (e.key === 'ArrowLeft') step(-1);
      if (e.key === 'ArrowRight') step(1);
    });
    var sx = 0;
    lb.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; }, { passive: true });
    lb.addEventListener('touchend', function (e) { var dx = e.changedTouches[0].clientX - sx; if (Math.abs(dx) > 50) step(dx < 0 ? 1 : -1); });
  }

  /* Reveal on scroll */
  var reveals = $$('.reveal');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
    }, { threshold: 0.12 });
    reveals.forEach(function (r) { io.observe(r); });
  } else {
    reveals.forEach(function (r) { r.classList.add('in'); });
  }

  /* Count-up stats */
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  $$('[data-count]').forEach(function (el) {
    var raw = el.getAttribute('data-count'), m = raw.match(/^(\D*)(\d+)(.*)$/);
    if (!m || reduce) return;
    var target = parseInt(m[2], 10), done = false;
    var run = function () {
      if (done) return; done = true;
      var t0 = performance.now(), dur = 1200;
      (function tick(now) {
        var p = Math.min(1, (now - t0) / dur), v = Math.round(target * (1 - Math.pow(1 - p, 3)));
        el.textContent = m[1] + v + m[3];
        if (p < 1) requestAnimationFrame(tick);
      })(t0);
    };
    if ('IntersectionObserver' in window) {
      var o = new IntersectionObserver(function (en) { if (en[0].isIntersecting) { run(); o.disconnect(); } });
      o.observe(el);
    } else run();
  });
})();

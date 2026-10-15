/* =====================================================================
 *  TechScope — frontend JS (vanilla, no dependencies)
 * ===================================================================== */
(function () {
  'use strict';

  /* ---- Auto-dismiss flash messages ---- */
  document.querySelectorAll('[data-flash]').forEach(function (el) {
    setTimeout(function () {
      el.style.transition = 'opacity .4s, transform .4s';
      el.style.opacity = '0';
      el.style.transform = 'translateY(-8px)';
      setTimeout(function () { el.remove(); }, 400);
    }, 5000);
  });

  /* ---- Reading progress bar (single post) ---- */
  var bar = document.getElementById('reading-progress');
  if (bar) {
    var article = document.querySelector('article');
    var onScroll = function () {
      if (!article) return;
      var rect = article.getBoundingClientRect();
      var total = rect.height - window.innerHeight;
      var passed = Math.min(Math.max(-rect.top, 0), Math.max(total, 1));
      bar.style.width = (total > 0 ? (passed / total) * 100 : 100) + '%';
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ---- Back to top ---- */
  var topBtn = document.getElementById('back-to-top');
  if (topBtn) {
    window.addEventListener('scroll', function () {
      topBtn.classList.toggle('hidden', window.scrollY < 600);
    }, { passive: true });
    topBtn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* ---- Reveal on scroll (IntersectionObserver) ---- */
  var revealEls = document.querySelectorAll('[data-reveal]');
  if ('IntersectionObserver' in window && revealEls.length) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('in');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    revealEls.forEach(function (el) { io.observe(el); });
  } else {
    revealEls.forEach(function (el) { el.classList.add('in'); });
  }

  /* ---- Animated counters (stats strip) ---- */
  var counters = document.querySelectorAll('[data-count]');
  if (counters.length) {
    var animate = function (el) {
      var target = parseInt(el.getAttribute('data-count'), 10) || 0;
      var dur = 1400, start = null;
      var step = function (ts) {
        if (!start) start = ts;
        var p = Math.min((ts - start) / dur, 1);
        var eased = 1 - Math.pow(1 - p, 3); // easeOutCubic
        el.textContent = Math.round(target * eased).toLocaleString();
        if (p < 1) requestAnimationFrame(step);
        else el.textContent = target.toLocaleString();
      };
      requestAnimationFrame(step);
    };
    if ('IntersectionObserver' in window) {
      var cio = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            animate(entry.target);
            cio.unobserve(entry.target);
          }
        });
      }, { threshold: 0.5 });
      counters.forEach(function (el) { cio.observe(el); });
    } else {
      counters.forEach(animate);
    }
  }

  /* ---- Ticker duplicate safety (seamless loop) ---- */
  document.querySelectorAll('.ticker').forEach(function (el) {
    if (el.scrollWidth < el.parentElement.clientWidth * 2) {
      el.innerHTML += el.innerHTML; // ensure enough width to loop
    }
  });

  /* ---- Copy link on share buttons ---- */
  document.querySelectorAll('[data-copy-link]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      navigator.clipboard.writeText(window.location.href).then(function () {
        var old = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(function () { btn.textContent = old; }, 1800);
      });
    });
  });

  /* ---- Navbar live search (suggestions + keyboard nav) ---- */
  document.querySelectorAll('[data-nav-search]').forEach(function (form) {
    var input   = form.querySelector('input[name="q"]');
    var panel   = form.querySelector('[data-nav-suggest]');
    var ajaxUrl = form.getAttribute('action') || '/search';
    if (!input || !panel) return;

    var timer = null, active = -1, items = [], controller = null;

    var esc = function (s) {
      var d = document.createElement('div');
      d.textContent = s == null ? '' : String(s);
      return d.innerHTML;
    };

    var close = function () {
      panel.hidden = true;
      panel.innerHTML = '';
      input.setAttribute('aria-expanded', 'false');
      active = -1; items = [];
    };

    var mark = function (i) {
      items.forEach(function (el, idx) {
        el.classList.toggle('bg-surface', idx === i);
        el.classList.toggle('text-accent', idx === i);
      });
      active = i;
      if (items[i]) input.setAttribute('aria-activedescendant', items[i].id || '');
    };

    var move = function (step) {
      if (!items.length) return;
      var next = active + step;
      if (next < 0) next = items.length - 1;
      if (next >= items.length) next = 0;
      mark(next);
      items[next].scrollIntoView({ block: 'nearest' });
    };

    var render = function (data, q) {
      var rows = (data && data.results) || [];
      if (!rows.length) {
        panel.innerHTML =
          '<div class="px-4 py-3.5 text-sm text-muted">No matches for &ldquo;' + esc(q) + '&rdquo;</div>';
        panel.hidden = false;
        input.setAttribute('aria-expanded', 'true');
        items = []; active = -1;
        return;
      }

      var html = '<ul role="listbox" aria-label="Search suggestions">';
      rows.forEach(function (r, i) {
        html +=
          '<li id="sugg-' + i + '" role="option" aria-selected="false" data-url="' + esc(r.url) + '" class="cursor-pointer">' +
            '<a href="' + esc(r.url) + '" class="block px-4 py-2.5 transition hover:bg-surface" tabindex="-1">' +
              '<span class="block truncate text-sm font-semibold leading-snug text-ink">' + esc(r.title) + '</span>' +
              '<span class="mt-0.5 block truncate text-[0.72rem] font-semibold text-muted">' +
                (r.category ? esc(r.category) + ' · ' : '') + esc(r.date || '') +
              '</span>' +
            '</a>' +
          '</li>';
      });
      html += '</ul>';
      if (data.more_url) {
        html +=
          '<a href="' + esc(data.more_url) + '" class="flex items-center justify-between border-t border-line-soft bg-surface px-4 py-2.5 text-[0.72rem] font-extrabold uppercase tracking-[0.12em] text-ink transition hover:text-accent">' +
            'See all ' + esc(data.total) + ' result' + (Number(data.total) === 1 ? '' : 's') +
            '<span aria-hidden="true">→</span>' +
          '</a>';
      }

      panel.innerHTML = html;
      panel.hidden = false;
      input.setAttribute('aria-expanded', 'true');
      items  = Array.prototype.slice.call(panel.querySelectorAll('li[role="option"]'));
      active = -1;
    };

    input.addEventListener('input', function () {
      var q = input.value.trim();
      clearTimeout(timer);
      if (q.length < 2) { close(); return; }
      timer = setTimeout(function () {
        if (controller) controller.abort();
        controller = ('AbortController' in window) ? new AbortController() : null;
        fetch(ajaxUrl + '?ajax=1&q=' + encodeURIComponent(q), {
              signal: controller ? controller.signal : undefined
            })
          .then(function (r) { return r.json(); })
          .then(function (d) { render(d, q); })
          .catch(function () { /* aborted or offline — keep quiet */ });
      }, 200);
    });

    input.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown')      { e.preventDefault(); if (panel.hidden) input.dispatchEvent(new Event('input')); else move(1); }
      else if (e.key === 'ArrowUp')   { e.preventDefault(); move(-1); }
      else if (e.key === 'Escape')    { close(); }
      else if (e.key === 'Enter' && active > -1) {
        e.preventDefault();
        var url = items[active].getAttribute('data-url');
        if (url) window.location.href = url;
      }
    });

    input.addEventListener('blur', function () { setTimeout(close, 180); });
    document.addEventListener('click', function (e) { if (!form.contains(e.target)) close(); });
  });

  /* ---- Subcategory accordion fallback for browsers without Alpine ---- */

  document.querySelectorAll('[data-sub-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      setTimeout(function () {
        if (!window.Alpine) {
          var container = btn.closest('div');
          var subMenu = container ? container.parentElement.querySelector('[data-sub-menu]') : null;
          if (subMenu) {
            var isHidden = window.getComputedStyle(subMenu).display === 'none';
            subMenu.style.display = isHidden ? 'block' : 'none';
            btn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
            var svg = btn.querySelector('svg');
            if (svg) svg.classList.toggle('rotate-180', isHidden);
          }
        }
      }, 10);
    });
  });
})();

/*
 * City autocomplete — after Life Insurance IQ's location field (Discover).
 *
 * Any <input data-city-ac="city|query" data-src="…/openchair/cities"> gets a
 * suggestion list from Openchair::cities(): markets we already hold first, then
 * US cities from OpenStreetMap through our own proxy.
 *
 *   city  — the whole value is the city ("Tampa, FL"), e.g. New scan's City.
 *   query — the search box ("barbershop, Tam"): only the part after the first
 *           comma is completed, and the niche before it is kept.
 *
 * Typing is never blocked: suggestions are optional, every failure is silent.
 * Suggestion text comes from a third-party API, so it is assigned with
 * textContent and never interpolated into markup.
 */
(function () {
  'use strict';

  var uid = 0;

  function attach(input) {
    var mode = input.getAttribute('data-city-ac') === 'query' ? 'query' : 'city';
    var src  = input.getAttribute('data-src');
    if (!src) { return; }

    var listId = 'oc-cityac-' + (++uid);
    var list = document.createElement('ul');
    list.className = 'oc-cityac';
    list.id = listId;
    list.setAttribute('role', 'listbox');
    list.setAttribute('aria-label', 'City suggestions');
    list.hidden = true;
    document.body.appendChild(list);

    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-controls', listId);
    input.setAttribute('aria-expanded', 'false');
    input.setAttribute('autocomplete', 'off');

    var places = [], active = -1, timer = null, retry = null, seq = 0;

    // "barbershop, Tam" → { prefix: "barbershop", part: "Tam" }
    function split() {
      var v = input.value;
      if (mode === 'query') {
        var i = v.indexOf(',');
        if (i >= 0) { return { prefix: v.slice(0, i).trim(), part: v.slice(i + 1).trim() }; }
      }
      return { prefix: '', part: v.trim() };
    }

    function place() {
      var r = input.getBoundingClientRect();
      list.style.left  = Math.round(r.left) + 'px';
      list.style.top   = Math.round(r.bottom + 6) + 'px';
      list.style.width = Math.max(Math.round(r.width), 260) + 'px';
    }

    function close() {
      list.hidden = true;
      list.innerHTML = '';
      input.setAttribute('aria-expanded', 'false');
      input.removeAttribute('aria-activedescendant');
      places = []; active = -1;
    }

    function choose(i) {
      var p = places[i];
      if (!p) { return; }
      var s = split();
      input.value = s.prefix ? s.prefix + ', ' + p.short : p.short;
      close();
      input.dispatchEvent(new Event('change', { bubbles: true }));
      input.focus();
    }

    function highlight(i) {
      var items = list.querySelectorAll('li');
      for (var k = 0; k < items.length; k++) {
        var on = k === i;
        items[k].classList.toggle('is-on', on);
        items[k].setAttribute('aria-selected', on ? 'true' : 'false');
      }
      active = i;
      if (i >= 0 && items[i]) {
        input.setAttribute('aria-activedescendant', items[i].id);
        items[i].scrollIntoView({ block: 'nearest' });
      } else {
        input.removeAttribute('aria-activedescendant');
      }
    }

    function render() {
      if (!places.length) { close(); return; }
      list.innerHTML = '';
      places.forEach(function (p, i) {
        var li = document.createElement('li');
        li.id = listId + '-' + i;
        li.setAttribute('role', 'option');
        li.setAttribute('aria-selected', 'false');
        var b = document.createElement('b');
        b.textContent = p.short;
        var s = document.createElement('span');
        s.textContent = p.label;
        li.appendChild(b);
        if (p.known) {
          var tag = document.createElement('em');
          tag.textContent = 'your data';
          li.appendChild(tag);
        }
        li.appendChild(s);
        li.addEventListener('mousedown', function (e) { e.preventDefault(); choose(i); });
        li.addEventListener('mouseenter', function () { highlight(i); });
        list.appendChild(li);
      });
      place();
      list.hidden = false;
      input.setAttribute('aria-expanded', 'true');
      highlight(-1);
    }

    function lookup(q) {
      var mine = ++seq;
      clearTimeout(retry);
      fetch(src + (src.indexOf('?') >= 0 ? '&' : '?') + 'q=' + encodeURIComponent(q), {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        credentials: 'same-origin'
      }).then(function (r) { return r.json(); }).then(function (res) {
        if (mine !== seq) { return; }               // a newer keystroke already won
        places = (res && res.places) || [];
        if (document.activeElement === input) { render(); }
        // Held back by the one-a-second rule: ask once more when it clears.
        if (res && res.throttled) { retry = setTimeout(function () { if (mine === seq) { lookup(q); } }, 1100); }
      }).catch(function () { /* suggestions are optional; typing still works */ });
    }

    input.addEventListener('input', function () {
      var q = split().part;
      clearTimeout(timer);
      if (q.length < 2) { seq++; close(); return; }
      // Debounced, as in the reference: OpenStreetMap asks for ≤ 1 call a second.
      timer = setTimeout(function () { lookup(q); }, 350);
    });

    input.addEventListener('keydown', function (e) {
      if (list.hidden) { return; }
      if (e.key === 'ArrowDown') { e.preventDefault(); highlight(Math.min(active + 1, places.length - 1)); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); highlight(Math.max(active - 1, 0)); }
      else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); choose(active); }
      else if (e.key === 'Escape') { e.preventDefault(); close(); }
      else if (e.key === 'Tab' && active >= 0) { choose(active); }
    });

    input.addEventListener('blur', function () { setTimeout(close, 120); });
    window.addEventListener('resize', function () { if (!list.hidden) { place(); } });
    window.addEventListener('scroll', function () { if (!list.hidden) { place(); } }, true);
  }

  function init() {
    var els = document.querySelectorAll('input[data-city-ac]');
    for (var i = 0; i < els.length; i++) { attach(els[i]); }
  }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();

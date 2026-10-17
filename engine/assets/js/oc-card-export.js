/* OpenChair — "Download image (phone size)" for a signature-visual card.
 *
 * Renders OUR OWN card markup (#oc-card) to a PNG in the browser: the card's DOM
 * and stylesheets (fonts inlined as data: URIs) go into an SVG <foreignObject>,
 * which is drawn onto a canvas at 2x and saved. No library, no server, and it
 * never touches anyone else's page — this is an export of a card we drew, not
 * capture machinery (05-prd §4.2).
 *
 * Triggers: any [data-oc-export="png"] button, or ?export=png in the URL (the
 * business screen's download link opens the card page that way).
 */
(function () {
  'use strict';

  var SCALE = 2;
  var FONT_EXT = /\.(woff2?|ttf|otf)(\?|#|$)/i;

  function toDataUrl(url) {
    return fetch(url, { credentials: 'same-origin' })
      .then(function (r) { if (!r.ok) { throw new Error(r.status); } return r.blob(); })
      .then(function (blob) {
        return new Promise(function (res, rej) {
          var fr = new FileReader();
          fr.onload = function () { res(fr.result); };
          fr.onerror = rej;
          fr.readAsDataURL(blob);
        });
      });
  }

  /* Keep only @font-face blocks for the Latin subset (Google Fonts ships one
     block per unicode-range); the others would add megabytes for nothing. */
  function latinOnly(css) {
    return css.replace(/@font-face\s*{[^}]*}/g, function (block) {
      var m = block.match(/unicode-range:\s*([^;]+);/i);
      if (!m) { return block; }
      return /U\+0000-00FF/i.test(m[1]) ? block : '';
    });
  }

  function inlineUrls(css, base) {
    var urls = [];
    css.replace(/url\(\s*['"]?([^'")]+)['"]?\s*\)/g, function (_, u) {
      if (!/^data:/i.test(u) && FONT_EXT.test(u)) { urls.push(u); }
      return _;
    });
    var uniq = urls.filter(function (u, i) { return urls.indexOf(u) === i; });
    return Promise.all(uniq.map(function (u) {
      var abs = new URL(u, base).href;
      return toDataUrl(abs).then(function (d) { return [u, d]; }, function () { return [u, null]; });
    })).then(function (pairs) {
      pairs.forEach(function (p) { if (p[1]) { css = css.split(p[0]).join(p[1]); } });
      return css;
    });
  }

  function collectCss() {
    var links = Array.prototype.slice.call(document.querySelectorAll('link[rel="stylesheet"]'));
    return Promise.all(links.map(function (l) {
      return fetch(l.href, { credentials: 'same-origin' })
        .then(function (r) { return r.ok ? r.text() : ''; })
        .then(function (t) { return inlineUrls(latinOnly(t), l.href); })
        .catch(function () { return ''; });
    })).then(function (parts) {
      Array.prototype.forEach.call(document.querySelectorAll('style'), function (s) { parts.push(s.textContent); });
      // print rules would never apply here; drop nothing else.
      return parts.join('\n');
    });
  }

  function inlineImages(root) {
    var imgs = Array.prototype.slice.call(root.querySelectorAll('img'));
    return Promise.all(imgs.map(function (img) {
      if (/^data:/i.test(img.src)) { return null; }
      return toDataUrl(img.src).then(function (d) { img.setAttribute('src', d); },
        // A logo we cannot read (cross-origin) is dropped rather than failing the export.
        function () { img.parentNode && img.parentNode.removeChild(img); });
    }));
  }

  function render(node) {
    var rect = node.getBoundingClientRect();
    var w = Math.ceil(rect.width);
    var h = Math.ceil(Math.max(rect.height, node.scrollHeight));
    var clone = node.cloneNode(true);

    return Promise.all([collectCss(), inlineImages(clone)]).then(function (res) {
      var css = res[0];
      // Wrap in the body's classes so size-scoped rules (.occ-phone .occ …) still match.
      var wrap = document.createElement('div');
      wrap.setAttribute('xmlns', 'http://www.w3.org/1999/xhtml');
      wrap.className = document.body.className;
      wrap.setAttribute('style', 'margin:0;padding:0;min-height:0;background:transparent;width:' + w + 'px');
      var style = document.createElement('style');
      style.textContent = css;
      wrap.appendChild(style);
      clone.style.margin = '0';
      wrap.appendChild(clone);

      var xhtml = new XMLSerializer().serializeToString(wrap);
      var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + w + '" height="' + h + '">'
              + '<foreignObject x="0" y="0" width="100%" height="100%">' + xhtml + '</foreignObject></svg>';

      return new Promise(function (resolve, reject) {
        var img = new Image();
        img.onload = function () {
          var canvas = document.createElement('canvas');
          canvas.width = w * SCALE;
          canvas.height = h * SCALE;
          var ctx = canvas.getContext('2d');
          ctx.scale(SCALE, SCALE);
          ctx.drawImage(img, 0, 0, w, h);
          try {
            canvas.toBlob(function (blob) { blob ? resolve(blob) : reject(new Error('empty')); }, 'image/png');
          } catch (e) { reject(e); }
        };
        img.onerror = function () { reject(new Error('svg')); };
        img.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
      });
    });
  }

  function save(blob, name) {
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = (name || 'chair-card') + '.png';
    document.body.appendChild(a);
    a.click();
    setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 1500);
  }

  function exportCard(btn) {
    var node = document.getElementById('oc-card');
    if (!node) { return; }
    var name = (btn && btn.getAttribute('data-oc-name')) || document.title.replace(/[^\w]+/g, '-').toLowerCase();
    if (/occ-phone/.test(document.body.className)) { name += '-phone'; }
    if (btn) { btn.disabled = true; btn.setAttribute('data-label', btn.textContent); btn.textContent = 'Making image…'; }
    var ready = document.fonts && document.fonts.ready ? document.fonts.ready : Promise.resolve();
    return ready.then(function () { return render(node); })
      .then(function (blob) { save(blob, name); })
      .catch(function () { alert("Couldn't make the image here — use Download one-pager (PDF) instead."); })
      .then(function () { if (btn) { btn.disabled = false; btn.textContent = btn.getAttribute('data-label'); } });
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest && e.target.closest('[data-oc-export="png"]');
    if (btn) { e.preventDefault(); exportCard(btn); }
  });

  if (/[?&]export=png(&|$)/.test(location.search)) {
    window.addEventListener('load', function () {
      exportCard(document.querySelector('[data-oc-export="png"]'));
    });
  }
})();

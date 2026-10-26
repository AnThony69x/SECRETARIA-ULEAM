// [equipo]: desplazamiento continuo en bucle (tarjetas clonadas); se detiene con el mouse, el foco, el dedo o el botón Pausa.
(function () {
  document.querySelectorAll('.eq').forEach(function (eq) {
    var pista = eq.querySelector('.eq-pista'), prev = eq.querySelector('.carrusel-prev'), next = eq.querySelector('.carrusel-next'), btn = eq.querySelector('.eq-pausa');
    if (!pista || !pista.children.length) return;
    var reducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var originales = Array.prototype.slice.call(pista.children);
    originales.forEach(function (n) { var c = n.cloneNode(true); c.setAttribute('aria-hidden', 'true'); c.querySelectorAll('[tabindex]').forEach(function (t) { t.removeAttribute('tabindex'); }); pista.appendChild(c); });
    var vuelta = function () { return pista.children[originales.length].offsetLeft - pista.children[0].offsetLeft; };
    var normalizar = function () { var v = vuelta(); if (v > 0 && pista.scrollLeft >= v) pista.scrollLeft -= v; };
    var paso = function (dir) {
      var w = originales[0].getBoundingClientRect().width + parseFloat(getComputedStyle(pista).columnGap || 24);
      if (dir < 0 && pista.scrollLeft < w) pista.scrollLeft += vuelta();
      pista.scrollBy({ left: dir * w, behavior: reducido ? 'auto' : 'smooth' });
    };
    if (prev) prev.addEventListener('click', function () { paso(-1); });
    if (next) next.addEventListener('click', function () { paso(1); });
    var encima = false, manual = reducido, acum = 0, antes = 0;
    var pone = function (m) { manual = m; if (btn) { btn.setAttribute('aria-pressed', m); btn.setAttribute('aria-label', m ? 'Reanudar carrusel' : 'Pausar carrusel'); } };
    if (btn) btn.addEventListener('click', function () { pone(!manual); });
    if (reducido) { pone(true); if (btn) btn.hidden = true; }
    ['mouseenter', 'focusin'].forEach(function (e) { eq.addEventListener(e, function () { encima = true; }); });
    ['mouseleave', 'focusout'].forEach(function (e) { eq.addEventListener(e, function () { encima = false; }); });
    eq.addEventListener('touchstart', function () { encima = true; }, { passive: true });
    eq.addEventListener('touchend', function () { setTimeout(function () { encima = false; }, 2500); }, { passive: true });
    pista.addEventListener('scroll', normalizar, { passive: true });
    requestAnimationFrame(function marco(t) {
      if (!encima && !manual && antes) {
        acum += 45 * (t - antes) / 1000;
        var px = Math.floor(acum);
        if (px) { acum -= px; pista.scrollLeft += px; normalizar(); }
      }
      antes = t;
      requestAnimationFrame(marco);
    });
  });
})();

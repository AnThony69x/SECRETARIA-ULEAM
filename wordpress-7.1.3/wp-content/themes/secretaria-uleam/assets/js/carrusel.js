(function () {
  // Carrusel de personal: desplazamiento continuo en bucle; se detiene al instante con el mouse, el foco o el dedo.
  var carr = document.querySelector('.perfiles-carrusel > .wp-block-group__inner-container');
  if (carr) {
    var caja = document.createElement('div');
    caja.className = 'carrusel';
    carr.parentNode.insertBefore(caja, carr);
    caja.appendChild(carr);
    var originales = Array.prototype.slice.call(carr.children);
    originales.forEach(function (n) {
      var c = n.cloneNode(true);
      c.classList.add('clon');
      c.setAttribute('aria-hidden', 'true');
      carr.appendChild(c);
    });
    var vuelta = function () { return carr.children[originales.length].offsetLeft - carr.children[0].offsetLeft; };
    var normalizar = function () {
      var v = vuelta();
      if (carr.scrollLeft >= v) carr.scrollLeft -= v;
    };
    var paso = function (dir) {
      var w = originales[0].getBoundingClientRect().width + parseFloat(getComputedStyle(carr).columnGap || 24);
      if (dir < 0 && carr.scrollLeft < w) carr.scrollLeft += vuelta();
      carr.scrollBy({ left: dir * w, behavior: 'smooth' });
    };
    ['prev', 'next'].forEach(function (n, i) {
      var b = document.createElement('button');
      b.className = 'carrusel-btn carrusel-' + n;
      b.setAttribute('aria-label', i ? 'Siguiente' : 'Anterior');
      b.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="' + (i ? 'm9 18 6-6-6-6' : 'm15 18-6-6 6-6') + '"/></svg>';
      b.addEventListener('click', function () { paso(i ? 1 : -1); });
      caja.appendChild(b);
    });
    var quieto = false, acum = 0, antes = 0;
    var reducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var parar = function () { quieto = true; carr.scrollTo({ left: carr.scrollLeft }); };
    var seguir = function () { quieto = false; };
    ['mouseenter', 'focusin'].forEach(function (e) { caja.addEventListener(e, parar); });
    ['mouseleave', 'focusout'].forEach(function (e) { caja.addEventListener(e, seguir); });
    caja.addEventListener('touchstart', parar, { passive: true });
    caja.addEventListener('touchend', function () { setTimeout(seguir, 2500); }, { passive: true });
    carr.addEventListener('scroll', normalizar, { passive: true });
    var marco = function (t) {
      if (!quieto && !reducido && antes) {
        acum += 45 * (t - antes) / 1000;
        var px = Math.floor(acum);
        if (px) { acum -= px; carr.scrollLeft += px; normalizar(); }
      }
      antes = t;
      requestAnimationFrame(marco);
    };
    requestAnimationFrame(marco);
  }
})();

// Organigrama ([organigrama]): el HTML y los datos vienen de wp-admin (Personal / Áreas); aquí solo va el comportamiento.
(function () {
  var ol = document.getElementById('oc');
  if (!ol) return;

  // Si una foto no carga, se usa el avatar por defecto.
  ol.addEventListener('error', function (e) {
    var im = e.target;
    if (im.tagName !== 'IMG' || !im.classList.contains('oc-av') || im.classList.contains('oc-def')) return;
    im.classList.add('oc-def');
    im.src = ol.dataset.avatar;
  }, true);

  // Animación al entrar en pantalla: nodo, línea, panel y tarjetas en cascada.
  if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (e) {
        if (!e.isIntersecting) return;
        var li = e.target;
        io.unobserve(li);
        li.classList.add('oc-in');
        setTimeout(function () { li.classList.remove('oc-anim', 'oc-in'); }, 2200);
      });
    }, { threshold: .15 });
    ol.querySelectorAll('.oc-n').forEach(function (li) {
      li.classList.add('oc-anim');
      li.querySelectorAll('.oc-c').forEach(function (c, i) { c.style.setProperty('--i', i); });
      io.observe(li);
    });
  }

  // Reparte las filas de forma equilibrada (7 -> 4 + 3) limitando el ancho de cada grupo de tarjetas.
  function equilibrar() {
    var movil = window.matchMedia('(max-width:600px)').matches;
    ol.querySelectorAll('.oc-caj').forEach(function (c) {
      if (movil || c.classList.contains('oc-uno') || !c.children.length) { c.style.maxWidth = ''; return; }
      var W = c.children[0].offsetWidth, G = parseFloat(getComputedStyle(c).columnGap) || 24;
      var p = c.parentNode, cs = getComputedStyle(p);
      var ancho = p.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
      var cols = Math.max(1, Math.floor((ancho + G) / (W + G))), filas = Math.ceil(c.children.length / cols);
      var por = Math.ceil(c.children.length / filas);
      c.style.maxWidth = (por * W + (por - 1) * G) + 'px';
    });
  }
  equilibrar();
  window.addEventListener('resize', equilibrar);

  // Botón Subir.
  var up = document.createElement('button');
  up.className = 'oc-up';
  up.type = 'button';
  up.setAttribute('aria-label', 'Subir');
  up.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m18 15-6-6-6 6"/></svg>';
  up.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
  document.body.appendChild(up);
  window.addEventListener('scroll', function () { up.classList.toggle('ver', window.scrollY > 400); }, { passive: true });
})();

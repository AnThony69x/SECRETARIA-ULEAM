(function () {
  var boton = document.getElementById('toggle');
  var menu = document.getElementById('menu');
  if (boton && menu) {
    boton.addEventListener('click', function () {
      boton.setAttribute('aria-expanded', menu.classList.toggle('abierto'));
    });
  }

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

  var slides = document.querySelectorAll('.slide');
  var puntos = document.getElementById('puntos');
  if (!slides.length || !puntos) return;
  var actual = 0, temporizador;
  function ir(i) {
    actual = (i + slides.length) % slides.length;
    slides.forEach(function (s, k) { s.classList.toggle('on', k === actual); });
    puntos.querySelectorAll('button').forEach(function (p, k) { p.classList.toggle('on', k === actual); });
  }
  function reiniciar() {
    clearInterval(temporizador);
    if (!pausado && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      temporizador = setInterval(function () { ir(actual + 1); }, 6000);
    }
  }
  var pausado = false, pausa = document.getElementById('pausa');
  pausa.addEventListener('click', function () {
    pausado = !pausado;
    pausa.setAttribute('aria-label', pausado ? 'Reanudar carrusel' : 'Pausar carrusel');
    pausa.style.opacity = pausado ? .6 : 1;
    reiniciar();
  });
  slides.forEach(function (_, i) {
    var p = document.createElement('button');
    p.setAttribute('aria-label', 'Banner ' + (i + 1));
    p.addEventListener('click', function () { ir(i); reiniciar(); });
    puntos.appendChild(p);
  });
  document.getElementById('prev').addEventListener('click', function () { ir(actual - 1); reiniciar(); });
  document.getElementById('next').addEventListener('click', function () { ir(actual + 1); reiniciar(); });
  ir(0);
  reiniciar();
})();

// Documentos: pestañas, selector de año, búsqueda, tipo y "mostrar más".
document.querySelectorAll('.docs-todo').forEach(function (d) {
  var PAG = 30, q = '', tipo = '', tabs = [].slice.call(d.querySelectorAll('[role=tab]'));
  var secs = tabs.map(function (t) {
    var el = document.getElementById(t.getAttribute('aria-controls')), l = el.querySelector('.doc-lista');
    var rows = l ? [].slice.call(l.querySelectorAll('.doc-fila')) : [], gs = [];
    rows.forEach(function (r) { if (gs.indexOf(r.dataset.g) < 0) gs.push(r.dataset.g); });
    return { tab: t, el: el, l: l, rows: rows, gs: gs, g: gs.length > 2 ? gs[0] : '', lim: PAG, name: t.dataset.nombre };
  });
  var act = secs[0];
  function ok(r) { return (!tipo || r.dataset.t === tipo) && (!q || (r.dataset.g + ' ' + r.textContent).toLowerCase().indexOf(q) > -1); }
  function render() {
    secs.forEach(function (s) { s.n = s.rows.filter(ok).length; s.tab.querySelector('.doc-tab-n').textContent = s.n + (s.n === 1 ? ' documento' : ' documentos'); });
    if (!act.n) { var o = secs.filter(function (s) { return s.n; })[0]; if (o) act = o; }
    secs.forEach(function (s) {
      var on = s === act;
      s.tab.setAttribute('aria-selected', on); s.tab.tabIndex = on ? 0 : -1; s.el.hidden = !on;
      if (!on || !s.l) return;
      // Selector de año/periodo del apartado activo, con cantidad por opción.
      var sel = d.querySelector('.doc-anio select'), per = !/^Año/.test(s.gs[0] || '');
      sel.innerHTML = '';
      [''].concat(s.gs).forEach(function (g) {
        var c = s.rows.filter(function (r) { return ok(r) && (!g || r.dataset.g === g); }).length;
        if (g && !c) return;
        sel.add(new Option((g ? g.replace(/^Año /, '') : (per ? 'Todos los periodos' : 'Todos los años')) + ' (' + c + ')', g, false, s.g === g));
      });
      d.querySelector('.doc-anio').hidden = s.gs.length < 2;
      sel.onchange = function () { s.g = sel.value; s.lim = PAG; render(); };
      var vis = s.rows.filter(function (r) { return ok(r) && (!s.g || r.dataset.g === s.g); }), shown = 0;
      s.rows.forEach(function (r) { var v = vis.indexOf(r) > -1 && shown < s.lim; if (v) shown++; r.hidden = !v; });
      var mas = s.l.querySelector('.doc-mas'); mas.hidden = vis.length <= shown;
      mas.textContent = 'Mostrar más (' + (vis.length - shown) + ' restantes)';
      mas.onclick = function () { s.lim += PAG; render(); };
      d.querySelector('.doc-contador').textContent = 'Mostrando ' + shown + ' de ' + vis.length + ' documentos en ' + s.name + (s.g ? ' · ' + s.g : '');
      d.querySelector('.doc-vacio').hidden = vis.length > 0;
    });
    // El filtro de tipo solo tiene sentido si el apartado tiene resoluciones (Miembros no).
    d.querySelector('.doc-tipo').hidden = !act.rows.some(function (r) { return r.dataset.t === 'resolucion'; });
    if (!secs.some(function (s) { return s.n; })) { d.querySelector('.doc-vacio').hidden = false; d.querySelector('.doc-contador').textContent = '0 documentos'; }
  }
  tabs.forEach(function (t, i) {
    t.onclick = function () { act = secs[i]; render(); };
    t.onkeydown = function (e) {
      var k = { ArrowRight: 1, ArrowLeft: -1 }[e.key]; if (!k) return;
      act = secs[(i + k + secs.length) % secs.length]; render(); act.tab.focus();
    };
  });
  d.querySelector('.doc-buscar input').addEventListener('input', function (e) {
    q = e.target.value.trim().toLowerCase();
    secs.forEach(function (s) { s.lim = PAG; if (q) s.g = ''; });
    render();
  });
  d.querySelector('.doc-tipo select').addEventListener('change', function (e) { tipo = e.target.value; secs.forEach(function (s) { s.lim = PAG; }); render(); });
  var h = location.hash.slice(1); if (h === 'actas') h = 'sesiones-ordinarias';
  secs.forEach(function (s) { if (s.el.id === h) act = s; });
  render();
});

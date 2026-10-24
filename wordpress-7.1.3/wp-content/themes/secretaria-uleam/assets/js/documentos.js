// Documentos: pestañas, selector de año, búsqueda, tipo, orden, periodos con sus sesiones y "mostrar más".
// La página trae solo el año inicial de cada pestaña; los demás años y las búsquedas se piden a /wp-json/uleam/v1/documentos.
document.querySelectorAll('.docs-todo').forEach(function (d) {
  var PAG = 30, PAGS = 4, api = d.dataset.api, vq = '&vista=' + (d.dataset.vista || ''), q = '', tipo = '', orden = '', res = null, buscando = 0, timer;
  var tpl = d.querySelector('.doc-tpl').content.firstElementChild;
  var cont = d.querySelector('.doc-contador'), vacio = d.querySelector('.doc-vacio'), sel = d.querySelector('.doc-anio select');
  var tabs = [].slice.call(d.querySelectorAll('[role=tab]'));
  var secs = tabs.map(function (t) {
    var el = document.getElementById(t.getAttribute('aria-controls')), s = {
      tab: t, el: el, slug: el.id, name: t.dataset.nombre, ul: el.querySelector('.doc-filas'), mas: el.querySelector('.doc-mas'),
      grupos: JSON.parse(el.dataset.resumen || '[]'), g0: el.dataset.g0 || '', cache: {}, pide: {}, fallo: {}, abiertos: {}, pag: 1
    };
    // Con varios años la vista inicial es "todos": el año en curso abierto y los anteriores recogidos (documentos-anios.js).
    s.ini = s.g0 && /^Año/.test(s.g0) ? '' : s.g0;
    s.g = s.ini;
    s.cache[s.g0 || '*'] = JSON.parse(el.dataset.ini || '[]');
    return s;
  });
  var act = secs[0];
  function suma(c) { return tipo ? (c[tipo] || 0) : Object.keys(c).reduce(function (n, k) { return n + c[k]; }, 0); }
  function okT(r) { return !tipo || r.t === tipo; }
  // Conteo por periodo de una sección: de la búsqueda si la hay, si no del resumen del servidor.
  function conteos(s) {
    if (!res) return s.grupos.map(function (x) { return [x[0], suma(x[1])]; });
    var m = {}, orden = [];
    res.forEach(function (r) { if (r.s === s.slug && okT(r)) { if (!(r.g in m)) { m[r.g] = 0; orden.push(r.g); } m[r.g]++; } });
    return orden.map(function (g) { return [g, m[g]]; });
  }
  function filas(s) {
    if (res) return res.filter(function (r) { return r.s === s.slug && okT(r) && (!s.g || r.g === s.g); });
    var c = s.cache[s.g || '*'];
    return c ? c.filter(okT) : null;
  }
  function pedir(s, g) {
    var k = g || '*';
    if (s.pide[k]) return;
    s.pide[k] = 1;
    fetch(api + '?seccion=' + encodeURIComponent(s.slug) + (g ? '&grupo=' + encodeURIComponent(g) : '') + vq)
      .then(function (r) { if (!r.ok) throw r; return r.json(); })
      .then(function (rows) { s.cache[k] = rows; render(); })
      .catch(function () { s.pide[k] = 0; s.fallo[g] = 1; render(); cont.textContent = 'No se pudieron cargar los documentos. Intenta de nuevo.'; s.ul.removeAttribute('aria-busy'); });
  }
  var T = { sin: d.dataset.tSin, vacio: vacio.firstChild.nodeValue, actual: d.dataset.tActual, rec: d.dataset.tRec, ant: d.dataset.tAnt, cargando: 'Cargando documentos…', error: 'No se pudieron cargar los documentos.', reintentar: 'Reintentar' };
  var DS = window.ULEAM_DOC, MESES = DS.MESES, tipoDe = DS.tipoDe, agrupar = DS.agrupar, MES2 = +d.dataset.mes2 || 7;
  function fila(r, i, compacto) {
    var li = tpl.cloneNode(true), a = li.querySelectorAll('a');
    li.style.setProperty('--i', i % PAG);
    li.querySelector('.doc-t>span').textContent = r.ti; // nombre original del documento, sin abreviar
    li.querySelector('.doc-t small').textContent = compacto || act.g ? '' : r.g; // el año ya se ve en el filtro o en la sesión
    a[0].href = a[1].href = r.u;
    a[0].setAttribute('aria-label', a[0].textContent + ': ' + r.ti);
    a[1].setAttribute('aria-label', a[1].textContent + ': ' + r.ti);
    return li;
  }
  function partesDe(rows) {
    var c = { acta: 0, resolucion: 0, otro: 0 }, p = [];
    rows.forEach(function (r) { c[tipoDe(r)]++; });
    if (c.acta) p.push(c.acta + (c.acta > 1 ? ' actas' : ' acta'));
    if (c.resolucion) p.push(c.resolucion + (c.resolucion > 1 ? ' resoluciones' : ' resolución'));
    if (c.otro) p.push(c.otro + (c.otro > 1 ? ' otros' : ' otro'));
    return p.join(' · ');
  }
  // Una sesión dentro del periodo: encabezado (n.º, fecha y conteo) + sus documentos con el nombre original.
  function sesionBloque(G) {
    var div = document.createElement('div'), h = document.createElement('h4'), ul = document.createElement('ul');
    div.className = 'doc-ses-bloque'; ul.className = 'doc-sub';
    h.className = 'doc-ses-h';
    h.innerHTML = '<span></span><small></small>';
    h.firstChild.textContent = G.otros ? 'Anexos y otros documentos' : (G.n !== null ? 'Sesión N.º ' + G.n : 'Sesión') + (G.f ? ' · ' + G.f[2] + ' de ' + MESES[G.f[1] - 1] + ' de ' + G.f[0] : '');
    h.lastChild.textContent = partesDe(G.rows);
    G.rows.forEach(function (r, i) { ul.appendChild(fila(r, i, true)); });
    div.appendChild(h); div.appendChild(ul);
    return div;
  }
  // recuerda (opcional): objeto donde se guarda si el periodo quedó abierto, para respetarlo al repintar.
  function periodoLi(P, abierto, recuerda) {
    var li = document.createElement('li'), det = document.createElement('details'), sum = document.createElement('summary'), cuerpo = document.createElement('div');
    var n = P.ses.filter(function (G) { return !G.otros; }).length;
    li.className = 'doc-sesion doc-periodo'; det.open = abierto; cuerpo.className = 'doc-per';
    sum.innerHTML = '<span class="doc-ses-ic" aria-hidden="true"><svg class="ico" viewBox="0 0 24 24"><path d="M8 2v4M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg></span><span class="doc-ses-t"><b></b><small></small></span><span class="doc-ses-n"></span><svg class="ico doc-ses-chev" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
    sum.querySelector('b').textContent = P.p ? 'Periodo ' + P.anio + '-' + P.p : 'Anexos y otros documentos ' + P.anio;
    sum.querySelector('small').textContent = P.p === 1 ? 'Enero – ' + MESES[MES2 - 2] + ' ' + P.anio : P.p === 2 ? MESES[MES2 - 1].replace(/^./, function (c) { return c.toUpperCase(); }) + ' – diciembre ' + P.anio : 'Sin fecha de sesión';
    sum.querySelector('.doc-ses-n').textContent = (n ? n + (n > 1 ? ' sesiones · ' : ' sesión · ') : '') + P.docs + (P.docs > 1 ? ' documentos' : ' documento');
    (orden === 'asc' ? P.ses.slice().reverse() : P.ses).forEach(function (G) { cuerpo.appendChild(sesionBloque(G)); });
    if (recuerda) det.addEventListener('toggle', function () { recuerda['p' + P.k] = det.open; });
    det.appendChild(sum); det.appendChild(cuerpo); li.appendChild(det);
    return li;
  }
  // Al cambiar de pestaña o filtro la lista puede quedar más corta: el navegador recortaba la página y saltaba hacia arriba.
  // Se fija el alto actual y se reduce con una transición, así el desplazamiento es suave.
  var suave = !window.matchMedia('(prefers-reduced-motion: reduce)').matches, tAlto;
  function render() {
    var alto = d.classList.contains('listo') ? d.offsetHeight : 0;
    if (alto) { clearTimeout(tAlto); d.style.transition = 'none'; d.style.minHeight = alto + 'px'; }
    pintar();
    if (!alto) return;
    requestAnimationFrame(function () {
      d.style.transition = suave ? 'min-height .4s ease' : 'none';
      d.style.minHeight = '0px';
      tAlto = setTimeout(function () { d.style.minHeight = ''; d.style.transition = ''; }, suave ? 450 : 0);
    });
  }
  function pintar() {
    var por = secs.map(function (s) { return conteos(s); });
    secs.forEach(function (s, i) {
      s.n = por[i].reduce(function (n, x) { return n + x[1]; }, 0);
      s.tab.querySelector('.doc-tab-n').textContent = s.n + (s.n === 1 ? ' documento' : ' documentos');
    });
    // Si una búsqueda o un filtro deja la pestaña en 0 (o al cargar) se salta a otra con documentos; una sección vacía de verdad
    // que el usuario elige se queda, con su aviso.
    if (!act.n && (q || act.grupos.length || !d.classList.contains('listo'))) { var o = secs.filter(function (s) { return s.n; })[0]; if (o) act = o; }
    secs.forEach(function (s) {
      var on = s === act;
      s.tab.setAttribute('aria-selected', on); s.tab.tabIndex = on ? 0 : -1; s.el.hidden = !on;
    });
    var s = act, cs = por[secs.indexOf(s)], sinDocs = !q && !s.grupos.length;
    vacio.firstChild.nodeValue = sinDocs ? T.sin : T.vacio; // sección sin documentos vs. filtros sin resultados
    d.querySelector('.doc-herramientas').hidden = sinDocs; // sin documentos no hay nada que buscar ni filtrar
    var per = !/^Año/.test((s.grupos[0] || [''])[0]);
    // Selector de año/periodo con cantidad por opción (los años viejos viven en la página del archivo histórico).
    sel.innerHTML = '';
    sel.add(new Option((per ? 'Todos los periodos' : 'Todos los años') + ' (' + s.n + ')', '', false, !s.g));
    cs.forEach(function (x) { sel.add(new Option(x[0].replace(/^Año /, '') + ' (' + x[1] + ')', x[0], false, s.g === x[0])); });
    d.querySelector('.doc-anio').hidden = s.grupos.length < 2;
    d.querySelector('.doc-tipo').hidden = !s.grupos.some(function (x) { return x[1].resolucion; });
    d.querySelector('.doc-limpiar').hidden = !(q || tipo || s.g !== s.ini);
    var act_ = d.querySelector('.doc-activos'), tsel = d.querySelector('.doc-tipo select');
    act_.innerHTML = '';
    [[q, '“' + q + '”', function () { var i = d.querySelector('.doc-buscar input'); i.value = ''; i.dispatchEvent(new Event('input')); }],
     [s.g && s.g !== s.ini, s.g.replace(/^Año /, ''), function () { s.g = s.ini; s.pag = 1; render(); }],
     [tipo, tipo && tsel.options[tsel.selectedIndex].text, function () { tsel.value = ''; tsel.dispatchEvent(new Event('change')); }]
    ].forEach(function (f) {
      if (!f[0]) return;
      var b = document.createElement('button'); b.type = 'button'; b.className = 'doc-activo';
      b.setAttribute('aria-label', 'Quitar filtro ' + f[1]); b.textContent = f[1];
      b.insertAdjacentHTML('beforeend', '<svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>');
      b.onclick = f[2]; act_.appendChild(b);
    });
    if (buscando) { cont.textContent = 'Buscando…'; return; }
    if (!res && !s.g && !s.ini && s.g0) return pintarAnios(s, cs);
    var rows = filas(s);
    if (!rows) { s.ul.setAttribute('aria-busy', 'true'); s.ul.classList.add('cargando'); cont.textContent = 'Cargando documentos…'; pedir(s, s.g); return; }
    s.ul.removeAttribute('aria-busy'); s.ul.classList.remove('cargando');
    var ses = agrupar(rows), frag = document.createDocumentFragment(), total, ver, unidad;
    if (ses) {
      var pers = DS.periodos(ses, MES2);
      if (orden === 'asc') pers.reverse();
      ver = pers.slice(0, s.pag * PAGS); total = pers.length; unidad = ['periodo', 'periodos'];
      ver.forEach(function (P) { frag.appendChild(periodoLi(P, false)); }); // recogidos: con muchos documentos, el usuario elige qué periodo abrir
    } else {
      if (orden === 'asc') { // invierte el orden de los años, no el de los documentos dentro de cada año
        var por_g = {}, gs_ = [];
        rows.forEach(function (r) { if (!por_g[r.g]) { por_g[r.g] = []; gs_.push(r.g); } por_g[r.g].push(r); });
        rows = [].concat.apply([], gs_.reverse().map(function (g) { return por_g[g]; }));
      }
      ver = rows.slice(0, s.pag * PAG); total = rows.length; unidad = ['documento', 'documentos'];
      ver.forEach(function (r, i) { frag.appendChild(fila(r, i, false)); });
    }
    s.ul.classList.toggle('por-sesion', !!ses); s.ul.classList.remove('por-anio');
    s.ul.innerHTML = ''; s.ul.appendChild(frag);
    s.ul.hidden = !ver.length;
    s.mas.hidden = total <= ver.length;
    s.mas.textContent = s.mas.dataset.txt + ' (' + (total - ver.length) + ' ' + unidad[total - ver.length === 1 ? 0 : 1] + ')';
    cont.textContent = !rows.length ? '' : 'Mostrando ' + ver.length + ' de ' + total + ' ' + unidad[total === 1 ? 0 : 1] + (ses ? ' (' + rows.length + ' documentos)' : '') + ' en ' + s.name + (s.g ? ' · ' + s.g : '');
    vacio.hidden = rows.length > 0;
    var vh = vacio.querySelector('.doc-vacio-hist'); // la búsqueda sigue en el archivo histórico
    if (vh) { vh.hidden = !q; vh.href = vh.href.split('?')[0] + '?q=' + encodeURIComponent(q); }
  }
  // Contenido de un año: sus periodos con sesiones o, si no se pueden agrupar, la lista de documentos.
  function cuerpo(rows, s) {
    rows = rows.filter(okT);
    var ses = agrupar(rows);
    if (!ses) return rows.map(function (r, i) { return fila(r, i, true); });
    var pers = DS.periodos(ses, MES2);
    if (orden === 'asc') pers.reverse();
    // Los periodos empiezan recogidos y conservan lo que el usuario abra o cierre: al abrir otro año se repinta todo.
    return pers.map(function (P) { var k = 'p' + P.k; return periodoLi(P, !!s.abiertos[k], s.abiertos); });
  }
  function pintarAnios(s, cs) {
    var r = DS.anios({ s: s, cs: cs, asc: orden === 'asc', hist: d.dataset.vista === 'historico' || d.dataset.vista === 'todo', cuerpo: function (rows) { return cuerpo(rows, s); }, t: T,
      filas: function (g) { return s.cache[g] || null; }, pedir: function (g) { pedir(s, g); } });
    s.ul.removeAttribute('aria-busy'); s.ul.classList.remove('cargando');
    s.ul.classList.add('por-sesion', 'por-anio');
    // El repintado recrea los años: se devuelve el foco al año que lo tenía (teclado / lector de pantalla).
    var f = document.activeElement, gf = f && s.ul.contains(f) && f.closest('.doc-anio-it') ? f.closest('.doc-anio-it').dataset.g : null;
    s.ul.innerHTML = ''; s.ul.appendChild(r.frag);
    if (gf !== null) [].some.call(s.ul.querySelectorAll('.doc-anio-it'), function (li) { if (li.dataset.g !== gf) return false; li.querySelector('summary').focus({ preventScroll: true }); return true; });
    s.ul.hidden = !r.n; s.mas.hidden = true;
    cont.textContent = r.n ? r.n + (r.n === 1 ? ' año' : ' años') + ' · ' + s.n + ' documentos en ' + s.name : '';
    vacio.hidden = r.n > 0;
  }
  function buscar() {
    var t = q;
    if (t.length < 2) { res = null; buscando = 0; render(); return; }
    buscando = 1; render();
    fetch(api + '?q=' + encodeURIComponent(t) + vq)
      .then(function (r) { if (!r.ok) throw r; return r.json(); })
      .then(function (rows) { if (t !== q) return; res = rows; buscando = 0; render(); })
      .catch(function () { if (t !== q) return; buscando = 0; cont.textContent = 'No se pudo completar la búsqueda. Intenta de nuevo.'; });
  }
  secs.forEach(function (s) { s.mas.addEventListener('click', function () { s.pag++; render(); }); });
  sel.addEventListener('change', function () { act.g = sel.value; act.pag = 1; render(); });
  tabs.forEach(function (t, i) {
    t.onclick = function () { act = secs[i]; render(); };
    t.onkeydown = function (e) {
      var k = { ArrowRight: 1, ArrowLeft: -1 }[e.key]; if (!k) return;
      act = secs[(i + k + secs.length) % secs.length]; render(); act.tab.focus();
    };
  });
  d.querySelector('.doc-buscar input').addEventListener('input', function (e) {
    q = e.target.value.trim();
    secs.forEach(function (s) { s.pag = 1; s.g = q ? '' : s.ini; });
    clearTimeout(timer); timer = setTimeout(buscar, 300);
  });
  d.querySelector('.doc-tipo select').addEventListener('change', function (e) { tipo = e.target.value; secs.forEach(function (s) { s.pag = 1; }); render(); });
  d.querySelector('.doc-orden select').addEventListener('change', function (e) { orden = e.target.value; secs.forEach(function (s) { s.pag = 1; }); render(); });
  d.querySelector('.doc-limpiar').addEventListener('click', function () {
    q = ''; tipo = ''; orden = ''; d.querySelector('.doc-orden select').value = ''; res = null; buscando = 0; clearTimeout(timer);
    d.querySelector('.doc-buscar input').value = ''; d.querySelector('.doc-tipo select').value = '';
    secs.forEach(function (s) { s.g = s.ini; s.pag = 1; });
    render();
  });
  var q0 = new URLSearchParams(location.search).get('q'); // búsqueda que llega desde la otra página
  if (q0) { var inp = d.querySelector('.doc-buscar input'); inp.value = q0; q = q0.trim(); secs.forEach(function (s) { s.g = ''; }); buscar(); }
  var h = location.hash.slice(1); if (h === 'actas') h = 'sesiones-ordinarias';
  secs.forEach(function (s) { if (s.slug === h) act = s; });
  render();
  // Las animaciones de filas/paneles solo se activan tras el primer pintado (al recargar no se anima nada).
  requestAnimationFrame(function () { requestAnimationFrame(function () { d.classList.add('listo'); }); });
});

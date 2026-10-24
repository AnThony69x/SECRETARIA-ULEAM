// Documentos por año: el año en curso desplegado y debajo "Histórico" (texto doc_anios_ant), cada año recogido.
// Un año solo pide sus documentos a la API al abrirlo. Lo usa assets/js/documentos.js (se carga antes, ver inc/recursos.php).
(function () {
  if (!document.querySelector('.docs-todo')) return;
  var ICO = '<svg class="ico" viewBox="0 0 24 24"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>';
  var CHEV = '<svg class="ico doc-ses-chev" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';

  // o: { s: sección, cs: [[grupo, n]…], filas(g), pedir(g), cuerpo(rows) → [li…], t: { actual, rec, ant, cargando, error, reintentar }, asc, hist }
  function anio(o, g, n, actual) {
    var s = o.s, li = document.createElement('li'), det = document.createElement('details'), sum = document.createElement('summary'), ul = document.createElement('ul');
    li.className = 'doc-sesion doc-anio-it' + (actual ? ' doc-anio-actual' : '');
    li.dataset.g = g;
    ul.className = 'doc-anio-per';
    sum.innerHTML = '<span class="doc-ses-ic" aria-hidden="true">' + ICO + '</span><span class="doc-ses-t"><b></b><small></small></span><span class="doc-ses-n"></span>' + CHEV;
    sum.querySelector('b').textContent = g;
    sum.querySelector('small').textContent = !actual ? '' : g.indexOf(new Date().getFullYear()) > -1 ? o.t.actual : o.t.rec;
    sum.querySelector('.doc-ses-n').textContent = n + (n === 1 ? ' documento' : ' documentos');
    det.open = g in s.abiertos ? s.abiertos[g] : actual;
    function llenar() {
      var rows = o.filas(g);
      ul.innerHTML = '';
      if (rows) { o.cuerpo(rows).forEach(function (x) { ul.appendChild(x); }); ul.removeAttribute('aria-busy'); return; }
      var p = document.createElement('li');
      p.className = 'doc-anio-msj';
      p.textContent = s.fallo[g] ? o.t.error : o.t.cargando;
      if (s.fallo[g]) { // reintento solo a petición, para no repetir la llamada en bucle si la red falla
        var b = document.createElement('button');
        b.type = 'button'; b.className = 'btn pq'; b.textContent = o.t.reintentar;
        b.onclick = function () { delete s.fallo[g]; llenar(); };
        p.appendChild(b);
      }
      ul.appendChild(p);
      ul.setAttribute('aria-busy', s.fallo[g] ? 'false' : 'true');
      if (!s.fallo[g]) o.pedir(g);
    }
    // El contenido se arma al abrir (los años recogidos no cuestan nada) y se recuerda abierto/cerrado entre repintados.
    det.addEventListener('toggle', function () {
      s.abiertos[g] = det.open;
      if (det.open && !ul.firstChild) llenar();
    });
    if (det.open) llenar();
    det.appendChild(sum); det.appendChild(ul); li.appendChild(det);
    return li;
  }

  function fijo(o, g) {
    var li = document.createElement('li'), ul = document.createElement('ul'), rows = o.filas(g);
    li.className = 'doc-anio-fijos'; ul.className = 'doc-sub';
    if (rows) o.cuerpo(rows).forEach(function (x) { ul.appendChild(x); });
    else { // si la petición falló, se reintenta a petición (como en los años) para no repetirla en bucle
      ul.innerHTML = '<li class="doc-anio-msj"></li>'; ul.firstChild.textContent = o.s.fallo[g] ? o.t.error : o.t.cargando;
      if (!o.s.fallo[g]) o.pedir(g);
      else { var b = document.createElement('button'); b.type = 'button'; b.className = 'btn pq'; b.textContent = o.t.reintentar; b.onclick = function () { delete o.s.fallo[g]; o.pedir(g); }; ul.firstChild.appendChild(b); }
    }
    li.appendChild(ul);
    return li;
  }

  function anios(o) {
    // En el archivo histórico ningún año se destaca: todos son pasados y van recogidos por igual.
    var frag = document.createDocumentFragment(), act = o.hist ? null : o.cs.filter(function (x) { return x[0] === o.s.g0 && x[1]; })[0];
    var esAnio = function (x) { return /^Año/.test(x[0]); };
    var resto = o.cs.filter(function (x) { return x !== act && x[1] && esAnio(x); });
    // Grupos que no son años (ej. "Documentos generales"): abiertos arriba, como lista; los años quedan como histórico.
    var fi = o.cs.filter(function (x) { return x !== act && x[1] && !esAnio(x); });
    // Con fijos y años a la vez, o.modo ('actual' | 'hist') elige cuál de los dos se ve (selector Actual / Histórico).
    var ambos = fi.length && (act || resto.length);
    if (ambos && o.modo === 'hist') fi = [];
    if (ambos && o.modo === 'actual') resto = [];
    fi.forEach(function (x) { frag.appendChild(fijo(o, x[0])); });
    var hayFijos = frag.childNodes.length > 0;
    if (o.asc) resto.reverse();
    if (act) frag.appendChild(anio(o, act[0], act[1], true));
    if (resto.length && (act || o.hist || hayFijos)) { // en el histórico el título encabeza la lista
      var sep = document.createElement('li');
      sep.className = 'doc-anios-sep';
      sep.innerHTML = '<span class="kicker"></span>';
      sep.firstChild.textContent = o.t.ant;
      frag.appendChild(sep);
    }
    resto.forEach(function (x) { frag.appendChild(anio(o, x[0], x[1], false)); });
    return { frag: frag, n: (act ? 1 : 0) + resto.length, fijos: hayFijos, ambos: !!ambos }; // n = años
  }
  window.ULEAM_DOC = window.ULEAM_DOC || {};
  window.ULEAM_DOC.anios = anios;
})();

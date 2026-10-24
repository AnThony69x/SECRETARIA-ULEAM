// Documentos: deduce sesiones (n.º y fecha leídos del título) y las reparte en dos periodos por año.
// Lo usa assets/js/documentos.js (se carga antes, ver inc/recursos.php).
(function () {
  if (!document.querySelector('.docs-todo')) return;
  // --- Sesiones: se deducen del título (n.º de sesión "SO 009"/"Nro.001" y fecha "01 de diciembre de 2023"). ---
  var MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
  var RE_FECHA = /(\d{1,2})\s+(?:de\s+)?(enero|febrero|marzo|abril|mayo|junio|julio|agosto|sept?iembre|octubre|noviembre|diciembre)\s+(?:del?\s+)?((?:19|20)\d{2})/i;
  function sesion(r) {
    var t = r.ti, n = null, f = null, m;
    if ((m = t.match(/\b(?:SO|SE)\s*0*(\d{1,3})\b/i))) n = +m[1];
    else if (/^acta/i.test(t) && (m = t.match(/\b(?:Nro|No)\.?\s*0*(\d{1,3})\b/i) || t.match(/\b0*(\d{1,3})\s+(?:19|20)\d{2}\s*$/))) n = +m[1];
    if ((m = t.match(RE_FECHA))) f = [+m[3], MESES.indexOf(m[2].toLowerCase().replace('setiembre', 'septiembre')) + 1, +m[1]];
    else if ((m = t.match(/^ACTA\s*(\d{2})(\d{2})((?:19|20)\d{2})$/i))) f = [+m[3], +m[2], +m[1]];
    return { n: n, f: f };
  }
  // Títulos antiguos como "… RCU SE 004 No.003 2015" son resoluciones aunque no digan la palabra.
  function tipoDe(r) { return r.t === 'otro' && /\b(?:RCU|OCS|HCU)\b.*No\.?\s*\d/i.test(r.ti) ? 'resolucion' : r.t; }
  function agrupar(rows) {
    var gs = {}, lista = [], con = 0;
    rows.forEach(function (r) {
      var x = sesion(r), k = x.n !== null ? r.g + '|n' + x.n : x.f ? r.g + '|f' + x.f.join('-') : r.g + '|otros';
      if (x.n !== null || x.f) con++;
      if (!gs[k]) { gs[k] = { k: k, g: r.g, n: x.n, f: null, rows: [], otros: x.n === null && !x.f }; lista.push(gs[k]); }
      if (!gs[k].f && x.f) gs[k].f = x.f;
      gs[k].rows.push(r);
    });
    // Un acta con fecha pero sin número se une a la sesión numerada del mismo día.
    lista = lista.filter(function (G) {
      if (G.n !== null || !G.f) return true;
      var par = lista.filter(function (H) { return H.n !== null && H.g === G.g && H.f && H.f.join() === G.f.join(); })[0];
      if (!par) return true;
      par.rows = par.rows.concat(G.rows);
      return false;
    });
    // Solo vale la pena agrupar si casi todo pertenece a alguna sesión (Miembros, por ejemplo, queda como lista).
    if (lista.length < 2 || con < rows.length * 0.6) return null;
    var anio = function (G) { var m = G.g.match(/\d{4}/); return m ? +m[0] : 0; };
    var clave = function (G) { return G.otros ? -1 : anio(G) * 1e6 + (G.f ? G.f[1] * 1e4 + G.f[2] * 100 : 0) + (G.n || 0); };
    lista.sort(function (A, B) { return clave(B) - clave(A); });
    lista.forEach(function (G) {
      var num = function (r) { var m = r.ti.match(/No\.?\s*0*(\d{1,4})(?!.*No\.?\s*\d)/i); return m ? +m[1] : 0; };
      var peso = { acta: 0, resolucion: 1, otro: 2 };
      G.rows.sort(function (a, b) { return (peso[tipoDe(a)] - peso[tipoDe(b)]) || (num(a) - num(b)) || a.ti.localeCompare(b.ti, 'es', { numeric: true }); });
    });
    return lista;
  }

  // Periodo 1 = enero hasta el mes anterior a "mes2"; periodo 2 = desde "mes2" hasta diciembre (mes2 se edita en el Personalizador).
  // El año es el del grupo ("Año 2011"); la fecha del título solo decide el periodo. Sin fecha o anexos: bloque aparte (p = 0).
  function periodos(ses, mes2) {
    var ps = {}, lista = [];
    ses.forEach(function (G) {
      var m = G.g.match(/\d{4}/), y = m ? +m[0] : (G.f ? G.f[0] : 0), p = G.f && !G.otros ? (G.f[1] >= mes2 ? 2 : 1) : 0, k = y + '-' + p;
      if (!ps[k]) { ps[k] = { k: k, anio: y, p: p, ses: [], docs: 0 }; lista.push(ps[k]); }
      ps[k].ses.push(G); ps[k].docs += G.rows.length;
    });
    lista.sort(function (A, B) { return (B.anio - A.anio) || ((B.p || -1) - (A.p || -1)); });
    return lista;
  }
  window.ULEAM_DOC = { MESES: MESES, tipoDe: tipoDe, agrupar: agrupar, periodos: periodos };
})();

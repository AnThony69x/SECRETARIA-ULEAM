// [ficha]: valida mientras se escribe, muestra "Otros" y el contador, genera sin recargar, muestra el resumen y descarga el PDF.
(function () {
  var cont = document.querySelector('.ficha');
  if (!cont) return;
  var f = cont.querySelector('.ficha-form'), hoja = cont.querySelector('.ficha-hoja'), rev = cont.querySelector('.ficha-rev'), anio = +cont.dataset.anio;
  if (!f) return;
  var el = f.elements, err = function (k, m) { var p = f.querySelector('#e-' + k); if (p) p.textContent = m || ''; var c = el[k]; if (c && c.setAttribute) c.setAttribute('aria-invalid', m ? 'true' : 'false'); return !m; };
  var txt = /^[\p{L}\p{N}][\p{L}\p{N}\s.,()&\/'’:-]{2,119}$/u;
  // Cédula ecuatoriana (provincia + dígito verificador módulo 10) o pasaporte alfanumérico (mismas reglas que el servidor).
  var cedula = function (c) {
    if (!/^\d{10}$/.test(c)) return /^[A-Za-z0-9]{5,20}$/.test(c);
    var p = +c.slice(0, 2), s = 0;
    if (((p < 1 || p > 24) && p !== 30) || +c[2] > 5) return false;
    for (var i = 0; i < 9; i++) { var v = +c[i] * (i % 2 ? 1 : 2); s += v > 9 ? v - 9 : v; }
    return (10 - s % 10) % 10 === +c[9];
  };
  var reglas = {
    nombres: function (v) { v = v.trim(); return /^\p{L}[\p{L}\s.'’-]{1,118}\p{L}$/u.test(v) && v.split(/\s+/).length > 1 ? '' : 'Escribe tus apellidos y nombres completos, solo con letras.'; },
    facultad: function (v) { return txt.test(v.trim()) ? '' : 'Escribe un nombre válido (letras, números y signos comunes).'; },
    carrera: function (v) { return txt.test(v.trim()) ? '' : 'Escribe un nombre válido (letras, números y signos comunes).'; },
    cedula: function (v) { return cedula(v.trim()) ? '' : 'Cédula no válida: revisa los 10 dígitos, o escribe un pasaporte de 5 a 20 letras y números.'; },
    ingreso: function (v) { return /^\d{4}$/.test(v) && v >= 1950 && v <= anio ? '' : 'Escribe un año entre 1950 y ' + anio + '.'; },
    egreso: function (v) { return !/^\d{4}$/.test(v) || v < 1950 || v > anio ? 'Escribe un año entre 1950 y ' + anio + '.' : (el.ingreso.value && +v < +el.ingreso.value ? 'El año de egreso no puede ser anterior al de ingreso.' : ''); }
  };
  Object.keys(reglas).forEach(function (k) {
    el[k].addEventListener('blur', function () { err(k, reglas[k](el[k].value)); });
    el[k].addEventListener('input', function () { if (el[k].getAttribute('aria-invalid') === 'true') err(k, reglas[k](el[k].value)); });
  });
  // «Ver más documentos» despliega el resto de la lista.
  var mas = f.querySelector('.ficha-mas'), masBtn = f.querySelector('.ficha-mas-btn');
  if (mas) masBtn.addEventListener('click', function () {
    var abre = mas.hidden; mas.hidden = !abre; masBtn.setAttribute('aria-expanded', abre);
    masBtn.querySelector('span').textContent = abre ? 'Ver menos documentos' : 'Ver más documentos (' + masBtn.dataset.mas + ')';
  });
  var cuenta = f.querySelector('#n-detalle');
  el.detalle.addEventListener('input', function () { cuenta.textContent = el.detalle.value.length + ' / 1000'; });
  var docs = function () { return [].filter.call(f.querySelectorAll('input[name="docs[]"]'), function (c) { return c.checked; }); };
  var valido = function () {
    var ok = true;
    Object.keys(reglas).forEach(function (k) { if (!err(k, reglas[k](el[k].value))) ok = false; });
    if (!docs().length) { err('docs', 'Marca al menos un documento.'); ok = false; } else err('docs', '');
    if (!el.acepto.checked) { err('acepto', 'Debes aceptar el aviso para generar la solicitud.'); ok = false; } else err('acepto', '');
    if (!ok) { var primero = f.querySelector('[aria-invalid="true"]') || el.acepto; primero.focus(); }
    return ok;
  };
  // Rellena una lista de datos (comprobante o revisión) con lo escrito; solo textContent.
  var llenar = function (caja, attr, n) {
    caja.querySelectorAll('[data-' + attr + ']').forEach(function (s) {
      var k = s.dataset[attr];
      if (k === 'n') s.textContent = n;
      else if (k === 'total') { var t = docs().reduce(function (a, c) { return a + (+c.dataset.valor || 0); }, 0); s.textContent = t ? 'Valor referencial a pagar: $' + t.toFixed(2) : ''; }
      else if (k === 'docs') {
        s.textContent = '';
        docs().map(function (c) { return [c.nextElementSibling.textContent, c.dataset.valor ? '$' + (+c.dataset.valor).toFixed(2) : '']; }).forEach(function (t) {
          var li = document.createElement('li'); li.appendChild(document.createTextNode(t[0]));
          if (t[1]) { var em = document.createElement('em'); em.textContent = t[1]; li.appendChild(em); }
          s.appendChild(li);
        });
      } else s.textContent = el[k].value.trim();
    });
  };
  var mostrar = function (a, b) { a.hidden = true; b.hidden = false; b.scrollIntoView({ behavior: 'smooth', block: 'start' }); };
  var modal = cont.querySelector('.ficha-modal');
  // Al cerrar el aviso (botón, Esc o fondo) se baja a las indicaciones de pago.
  modal.addEventListener('close', function () { var p = hoja.querySelector('.ficha-pago'); p.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
  modal.querySelector('[data-ir]').addEventListener('click', function () { modal.close(); });
  modal.addEventListener('click', function (e) { if (e.target === modal) modal.close(); });
  // Descarga el PDF automáticamente y deja el botón por si hace falta repetirla.
  var bajar = function (url) {
    var a = hoja.querySelector('[data-pdf]'); a.href = url;
    var t = document.createElement('a'); t.href = url; t.download = ''; document.body.appendChild(t); t.click(); t.remove();
  };
  var enviar = function () {
    var msg = rev.querySelector('.ficha-error'), btn = rev.querySelector('[data-confirmar]');
    msg.textContent = ''; btn.disabled = true;
    var datos = new FormData(f); datos.set('action', 'uleam_ficha');
    fetch(cont.dataset.ajax, { method: 'POST', body: datos, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) { if (j.ok) { llenar(hoja, 'f', j.num); mostrar(rev, hoja); bajar(j.pdf); modal.querySelector('[data-m=numero]').textContent = j.num; if (modal.showModal) modal.showModal(); f.reset(); if (mas) { mas.hidden = true; masBtn.setAttribute('aria-expanded', 'false'); masBtn.querySelector('span').textContent = 'Ver más documentos (' + masBtn.dataset.mas + ')'; } cuenta.textContent = '0 / 1000'; } else msg.textContent = j.error || 'No pudimos generar la solicitud.'; })
      .catch(function () { msg.textContent = 'No hay conexión. Revisa tu internet e inténtalo de nuevo.'; })
      .then(function () { btn.disabled = false; });
  };
  f.addEventListener('submit', function (e) {
    f.querySelector('.ficha-error').textContent = '';
    if (!valido()) { e.preventDefault(); return; }
    if (!window.fetch || !window.FormData) return; // sin soporte: se envía de la forma normal
    e.preventDefault(); llenar(rev, 'r'); mostrar(f, rev); // primero se revisa; el envío real es al confirmar
  });
  rev.querySelector('[data-corregir]').addEventListener('click', function () { mostrar(rev, f); });
  rev.querySelector('[data-confirmar]').addEventListener('click', enviar);
  // Copia el número de cuenta y lo confirma en el propio botón.
  hoja.querySelectorAll('[data-copiar]').forEach(function (b) {
    b.addEventListener('click', function () {
      var t = b.querySelector('span'), antes = t.textContent;
      (navigator.clipboard ? navigator.clipboard.writeText(b.dataset.copiar) : Promise.reject()).then(function () { t.textContent = '¡Copiado!'; }, function () { t.textContent = 'Selecciona y copia'; });
      setTimeout(function () { t.textContent = antes; }, 1800);
    });
  });
  hoja.querySelector('[data-nueva]').addEventListener('click', function () { hoja.hidden = true; f.hidden = false; f.querySelector('input').focus(); });
})();

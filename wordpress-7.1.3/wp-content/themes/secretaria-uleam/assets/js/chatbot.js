// Asistente flotante: abre/cierra el panel, conversa con sugerencias que se renuevan y, si hay API, reenvía lo escrito al agente.
(function () {
  var cb = document.querySelector('.cb');
  if (!cb) return;
  var btn = cb.querySelector('.cb-btn'), panel = cb.querySelector('.cb-panel'), msgs = cb.querySelector('.cb-msgs');
  var form = cb.querySelector('.cb-form'), api = cb.dataset.api, datos = cb.querySelector('.cb-datos'), hist = [];
  var todas = [].map.call(datos.querySelectorAll('button'), function (b, i) { // sugerencias: enlace (data-u) o pregunta frecuente (respuesta en el div siguiente)
    return { i: i, html: b.innerHTML, t: b.textContent.trim(), u: b.dataset.u || '', ans: b.dataset.u ? '' : b.nextElementSibling.innerHTML };
  });
  var pool = todas.slice(), inicio = cb.dataset.ini || msgs.innerHTML, typing = '<span class="cb-typing" aria-label="Escribiendo"><i></i><i></i><i></i></span>';

  // La conversación vive en sessionStorage: sobrevive al cambiar de página y se borra al recargar (F5) o cerrar la pestaña.
  var CLAVE = 'cb_estado';
  function guardar() {
    try { sessionStorage.setItem(CLAVE, JSON.stringify({ h: msgs.innerHTML, p: pool.map(function (x) { return x.i; }), hist: hist, abierto: !panel.hidden })); } catch (e) {}
  }
  function abrir(v) {
    panel.hidden = !v;
    btn.setAttribute('aria-expanded', v);
    if (v) form.querySelector('input').focus();
    guardar();
  }
  function bajar() { msgs.scrollTo({ top: msgs.scrollHeight, behavior: 'smooth' }); }
  // Mensaje del asistente (con su ícono) o del usuario; devuelve la burbuja. html solo viene del servidor (ya filtrado con kses).
  function poner(cls, html, texto) {
    var m = document.createElement('div'), f = document.createElement('div');
    m.className = 'cb-m ' + cls;
    if (texto != null) m.textContent = texto; else m.innerHTML = html;
    f.className = 'cb-fila ' + cls;
    if (cls === 'bot') f.innerHTML = '<span class="cb-av2">' + panel.querySelector('.cb-av').innerHTML + '</span>';
    f.appendChild(m); msgs.appendChild(f); bajar();
    return m;
  }
  function quitar(m) { m.closest('.cb-fila').remove(); }
  function enlaces(lista) {
    var ul = document.createElement('ul'); ul.className = 'cb-docs';
    lista.forEach(function (d) {
      var li = document.createElement('li'), a = document.createElement('a');
      a.href = d.u; a.innerHTML = '<span></span>' + cb.dataset.flecha; a.firstChild.textContent = d.t; a.setAttribute('aria-label', d.t);
      if (a.host !== location.host) { a.target = '_blank'; a.rel = 'noopener'; } // los del mismo sitio se abren en la misma pestaña
      li.appendChild(a); ul.appendChild(li);
    });
    return ul;
  }
  // Bloque de sugerencias al final de la conversación: reemplaza al anterior y muestra hasta 4 que aún no se usaron.
  function sugerir() {
    var v = msgs.querySelector('.cb-ir'); if (v) v.remove();
    if (!pool.length) return;
    var d = document.createElement('div'); d.className = 'cb-ir';
    d.innerHTML = '<small>' + datos.dataset.tit + '</small>';
    pool.slice(0, 4).forEach(function (s) {
      var b = document.createElement('button'); b.type = 'button'; b.innerHTML = s.html;
      b.dataset.i = s.i; // el clic se resuelve en msgs (delegado) para que siga vivo al restaurar la conversación
      d.appendChild(b);
    });
    msgs.appendChild(d); bajar();
  }
  // El asistente escribe, responde y vuelve a ofrecer opciones.
  function responder(fn) {
    var esp = poner('bot', typing);
    setTimeout(function () { quitar(esp); fn(); setTimeout(function () { poner('bot', '', datos.dataset.mas); sugerir(); }, 500); }, 700);
  }
  function elegir(s) {
    pool = pool.filter(function (x) { return x !== s; });
    var v = msgs.querySelector('.cb-ir'); if (v) v.remove();
    poner('yo', '', s.t);
    responder(function () {
      var m = poner('bot', s.u ? '' : s.ans, s.u ? datos.dataset.resp : null);
      if (s.u) m.appendChild(enlaces([{ t: s.t, u: s.u }]));
    });
  }
  msgs.addEventListener('click', function (e) {
    var b = e.target.closest('.cb-ir button');
    if (b && todas[+b.dataset.i]) elegir(todas[+b.dataset.i]);
  });
  cb.querySelector('.cb-rec').addEventListener('click', function () { // vuelve a empezar
    msgs.innerHTML = inicio; hist = []; pool = todas.slice();
    form.querySelector('input').value = ''; form.querySelector('input').focus(); sugerir();
  });
  btn.addEventListener('click', function () { abrir(panel.hidden); });
  cb.querySelector('.cb-x').addEventListener('click', function () { abrir(false); btn.focus(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !panel.hidden) { abrir(false); btn.focus(); } });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var i = form.querySelector('input'), t = i.value.trim();
    if (!t) return;
    i.value = '';
    var v = msgs.querySelector('.cb-ir'); if (v) v.remove();
    poner('yo', '', t);
    var esp = poner('bot', typing);
    fetch(api, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ message: t, history: hist }) })
      .then(function (r) { return r.json().then(function (j) { if (!r.ok) throw j; return j; }); })
      .then(function (j) {
        esp.innerHTML = j.reply; // html del servidor, ya filtrado con kses
        hist.push({ role: 'user', content: t }, { role: 'bot', content: esp.textContent });
        var l = (j.docs || []).concat(j.links || []);
        if (l.length) esp.appendChild(enlaces(l));
      })
      .catch(function (j) { esp.textContent = (j && j.message) || cb.dataset.error; })
      .then(function () { setTimeout(function () { poner('bot', '', datos.dataset.mas); sugerir(); }, 500); });
  });
  var nav = performance.getEntriesByType && performance.getEntriesByType('navigation')[0], est = null;
  try {
    if (nav && nav.type === 'reload') sessionStorage.removeItem(CLAVE);
    else est = JSON.parse(sessionStorage.getItem(CLAVE));
  } catch (e) {}
  if (est && est.h) {
    cb.classList.add('cb-quieto'); // al volver de otra página no se repiten las animaciones de entrada
    setTimeout(function () { cb.classList.remove('cb-quieto'); }, 400);
    msgs.innerHTML = est.h;
    msgs.querySelectorAll('.cb-typing').forEach(function (t) { quitar(t.parentNode); }); // una respuesta a medias no se restaura
    pool = est.p.map(function (i) { return todas[i]; }).filter(Boolean); hist = est.hist || [];
    if (est.abierto) { panel.hidden = false; btn.setAttribute('aria-expanded', true); msgs.scrollTop = msgs.scrollHeight; }
  } else sugerir();
  new MutationObserver(guardar).observe(msgs, { childList: true, subtree: true, characterData: true });
})();

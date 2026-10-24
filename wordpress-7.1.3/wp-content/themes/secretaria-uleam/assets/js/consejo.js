// Integración del Consejo: el bloque "consejo-intro" empieza recogido; un botón lo despliega (sin JS se ve completo).
document.querySelectorAll('.consejo-intro').forEach(function (g, n) {
  var c = g.querySelector('.wp-block-group__inner-container') || g, ul = c.querySelector('ul');
  if (!ul) return;
  var t = window.ULEAM_CI || { ver: 'Ver integrantes', ocultar: 'Ocultar integrantes' };
  var cuerpo = document.createElement('div'), dentro = document.createElement('div'), b = document.createElement('button');
  cuerpo.className = 'ci-cuerpo'; cuerpo.id = 'ci-cuerpo-' + n; cuerpo.inert = true;
  while (ul.nextSibling) dentro.appendChild(ul.nextSibling);
  dentro.insertBefore(ul, dentro.firstChild);
  cuerpo.appendChild(dentro);
  b.type = 'button'; b.className = 'ci-toggle'; b.setAttribute('aria-expanded', 'false'); b.setAttribute('aria-controls', cuerpo.id);
  var cuenta = ' (' + ul.children.length + ')';
  var pinta = function (abierto) {
    b.innerHTML = '<span></span><svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
    b.firstChild.textContent = (abierto ? t.ocultar : t.ver) + (abierto ? '' : cuenta);
  };
  pinta(false);
  b.addEventListener('click', function () {
    var abierto = b.getAttribute('aria-expanded') !== 'true';
    b.setAttribute('aria-expanded', abierto); cuerpo.classList.toggle('abierto', abierto); cuerpo.inert = !abierto; pinta(abierto);
  });
  // El botón va al lado de la frase de entrada (el párrafo justo antes de la lista), si existe.
  var intro = c.querySelector(':scope > p:not(.consejo-nota)');
  if (intro) {
    var fila = document.createElement('div'); fila.className = 'ci-fila';
    intro.parentNode.insertBefore(fila, intro); fila.appendChild(intro); fila.appendChild(b);
  } else c.appendChild(b);
  c.appendChild(cuerpo);
});

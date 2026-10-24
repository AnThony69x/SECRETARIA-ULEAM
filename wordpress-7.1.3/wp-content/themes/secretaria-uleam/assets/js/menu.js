// Menú principal en móvil: el botón abre/cierra la lista.
(function () {
  var boton = document.getElementById('toggle');
  var menu = document.getElementById('menu');
  if (boton && menu) {
    var iconos = { menu: boton.innerHTML, cerrar: '<svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>' };
    boton.addEventListener('click', function () {
      var abierto = menu.classList.toggle('abierto');
      boton.setAttribute('aria-expanded', abierto);
      boton.setAttribute('aria-label', abierto ? 'Cerrar menú' : 'Abrir menú');
      boton.innerHTML = abierto ? iconos.cerrar : iconos.menu;
    });
  }
  // Los títulos de sección del menú (enlace "#" con submenú) no son páginas: sin href no añaden "#" a la URL.
  if (!menu) return;
  menu.querySelectorAll('a[href="#"]').forEach(function (a) {
    a.removeAttribute('href');
    a.setAttribute('role', 'button');
    a.tabIndex = 0;
    if (a.nextElementSibling && a.nextElementSibling.classList.contains('sub-menu')) a.setAttribute('aria-haspopup', 'true');
  });

  // Móvil: cada submenú se pliega con un botón de flecha; el de la sección actual empieza abierto.
  menu.querySelectorAll('.menu-item-has-children').forEach(function (li, i) {
    var sub = li.querySelector(':scope > .sub-menu'), a = li.querySelector(':scope > a');
    if (!sub) return;
    sub.id = sub.id || 'submenu-' + i;
    var t = document.createElement('button');
    t.type = 'button';
    t.className = 'sub-toggle';
    t.setAttribute('aria-controls', sub.id);
    t.setAttribute('aria-label', 'Mostrar ' + (a ? a.textContent.trim() : 'submenú'));
    t.innerHTML = '<svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
    var poner = function (on) { li.classList.toggle('abierto', on); t.setAttribute('aria-expanded', on); };
    poner(li.classList.contains('current-menu-ancestor'));
    t.addEventListener('click', function () { poner(!li.classList.contains('abierto')); });
    // El título de sección sin enlace también despliega su submenú en móvil.
    if (a && !a.hasAttribute('href')) a.addEventListener('click', function () { if (window.matchMedia('(max-width:860px)').matches) poner(!li.classList.contains('abierto')); });
    li.insertBefore(t, sub);
  });
})();

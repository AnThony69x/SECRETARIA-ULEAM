// Modal de sistemas: los enlaces a la página del sistema histórico abren el diálogo (inc/sistemas-modal.php) en vez de navegar.
(function () {
  var m = document.getElementById('sis-modal');
  if (!m || typeof m.showModal !== 'function') return; // sin <dialog> el enlace funciona como siempre
  var url = m.dataset.url.replace(/\/$/, ''), ult = null;
  function abrir(e) {
    var a = e.target.closest && e.target.closest('a[href]');
    if (!a || m.contains(a) || a.href.split(/[?#]/)[0].replace(/\/$/, '') !== url) return;
    if (e.ctrlKey || e.metaKey || e.shiftKey || e.button) return; // abrir en pestaña nueva sigue funcionando
    e.preventDefault();
    ult = a;
    m.showModal();
    document.documentElement.classList.add('sis-abierto');
  }
  function cerrar() { m.close(); }
  document.addEventListener('click', abrir);
  if (location.hash === '#sistemas') { m.showModal(); document.documentElement.classList.add('sis-abierto'); } // enlace directo al modal
  m.querySelector('.sis-cerrar').addEventListener('click', cerrar);
  m.addEventListener('click', function (e) { if (e.target === m) cerrar(); }); // clic en el fondo
  m.addEventListener('close', function () { document.documentElement.classList.remove('sis-abierto'); if (ult) ult.focus(); });
})();

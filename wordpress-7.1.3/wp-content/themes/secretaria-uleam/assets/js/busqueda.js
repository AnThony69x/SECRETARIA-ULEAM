// Filtro de documentos de la búsqueda: oculta al instante las filas que no coinciden (Enter busca en todo el apartado).
(function () {
  var f = document.querySelector('.bus-filtro input[type=search]');
  var filas = document.querySelectorAll('.doc-fila');
  if (!f || !filas.length) return;
  f.addEventListener('input', function () {
    var t = f.value.trim().toLowerCase();
    filas.forEach(function (r) { r.hidden = t !== '' && r.textContent.toLowerCase().indexOf(t) === -1; });
  });
})();

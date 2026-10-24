// Biblioteca de la portada: el año solo ofrece los años del tipo elegido; al enviar se omiten los campos vacíos (URL limpia: ?s=&a=&g=).
(function () {
  var f = document.querySelector('.bib');
  if (!f) return;
  var tipo = f.elements.a, anio = f.elements.g, anios = {}, todas = [].slice.call(anio.options);
  try { anios = JSON.parse(f.dataset.anios || '{}'); } catch (e) {}
  tipo.addEventListener('change', function () {
    var ok = anios[tipo.value]; // sin tipo elegido: todos los años
    todas.forEach(function (o) { o.hidden = o.value !== '' && !!ok && ok.indexOf(o.value) < 0; o.disabled = o.hidden; });
    if (anio.selectedOptions[0].disabled) anio.value = '';
  });
  f.addEventListener('submit', function () {
    // "s" siempre viaja: sin él WordPress no abre la página de resultados
    [].forEach.call(f.elements, function (el) { if (el.name && el.name !== 's' && !el.value) el.disabled = true; });
  });
  window.addEventListener('pageshow', function () { [].forEach.call(f.elements, function (el) { el.disabled = false; }); }); // al volver atrás
})();

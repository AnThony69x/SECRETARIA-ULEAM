// Slider del hero de la portada: puntos, flechas, pausa y avance automático (se respeta movimiento reducido).
(function () {
  var slides = document.querySelectorAll('.slide');
  var puntos = document.getElementById('puntos');
  if (!slides.length || !puntos) return;
  var actual = 0, temporizador, seg = +document.getElementById('inicio').dataset.seg || 6;
  function ir(i) {
    actual = (i + slides.length) % slides.length;
    slides.forEach(function (s, k) { s.classList.toggle('on', k === actual); });
    var bs = puntos.querySelectorAll('button');
    bs.forEach(function (p) { p.classList.remove('on'); });
    void puntos.offsetWidth; // fuerza reflujo para reiniciar el relleno del punto activo
    bs[actual].classList.add('on');
  }
  function reiniciar() {
    clearInterval(temporizador);
    puntos.classList.toggle('pausado', pausado);
    if (!pausado) ir(actual);
    if (!pausado && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      temporizador = setInterval(function () { ir(actual + 1); }, seg * 1000);
    }
  }
  var pausado = document.getElementById('inicio').dataset.auto === '0', pausa = document.getElementById('pausa');
  pausa.addEventListener('click', function () {
    pausado = !pausado;
    pausa.setAttribute('aria-label', pausado ? 'Reanudar carrusel' : 'Pausar carrusel');
    pausa.style.opacity = pausado ? .6 : 1;
    reiniciar();
  });
  slides.forEach(function (_, i) {
    var p = document.createElement('button');
    p.setAttribute('aria-label', 'Banner ' + (i + 1));
    p.addEventListener('click', function () { ir(i); reiniciar(); });
    puntos.appendChild(p);
  });
  document.getElementById('prev').addEventListener('click', function () { ir(actual - 1); reiniciar(); });
  document.getElementById('next').addEventListener('click', function () { ir(actual + 1); reiniciar(); });
  if (pausado) { pausa.setAttribute('aria-label', 'Reanudar carrusel'); pausa.style.opacity = .6; }
  ir(0);
  reiniciar();
})();

// Slider del hero de la portada: puntos, flechas, pausa y avance automático (se respeta movimiento reducido).
(function () {
  var slides = document.querySelectorAll('.slide');
  var puntos = document.getElementById('puntos');
  if (!slides.length || !puntos) return;
  var actual = 0, temporizador;
  function ir(i) {
    actual = (i + slides.length) % slides.length;
    slides.forEach(function (s, k) { s.classList.toggle('on', k === actual); });
    puntos.querySelectorAll('button').forEach(function (p, k) { p.classList.toggle('on', k === actual); });
  }
  function reiniciar() {
    clearInterval(temporizador);
    if (!pausado && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      temporizador = setInterval(function () { ir(actual + 1); }, 6000);
    }
  }
  var pausado = false, pausa = document.getElementById('pausa');
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
  ir(0);
  reiniciar();
})();

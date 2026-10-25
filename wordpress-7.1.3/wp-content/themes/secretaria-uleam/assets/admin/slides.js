// Administración de slides (inc/slide-admin.php): selector de imágenes y orden por arrastre. Solo se carga en las pantallas de slides.
(function ($) {
  // Selector de imagen: abre la biblioteca de medios y deja la vista previa y el ID oculto.
  $(document).on('click', '.us-card [data-elegir]', function () {
    var card = $(this).closest('.us-card'), btn = $(this);
    var m = wp.media({ library: { type: 'image' }, multiple: false });
    m.on('select', function () {
      var a = m.state().get('selection').first().toJSON();
      var img = $('<img>').attr('src', (a.sizes && a.sizes.large ? a.sizes.large.url : a.url)).attr('alt', '');
      card.find('[data-prev]').empty().append(img);
      card.find('input[type=hidden]').val(a.id);
      card.find('[data-quitar]').prop('hidden', false);
      btn.text('Cambiar imagen');
    });
    m.open();
  });
  $(document).on('click', '.us-card [data-quitar]', function () {
    var card = $(this).closest('.us-card');
    card.find('[data-prev]').html('<span class="us-vacio"><span class="dashicons dashicons-format-image"></span>Sin imagen</span>');
    card.find('input[type=hidden]').val(0);
    card.find('[data-elegir]').text('Elegir imagen');
    $(this).prop('hidden', true);
  });

  // Lista de slides: orden por arrastre o con flechas; se guarda por AJAX.
  var lista = $('#the-list');
  if (!lista.length || !window.uleamSlides) return;
  var guardar = function () {
    var ids = lista.find('tr').map(function () { return this.id.replace('post-', ''); }).get();
    lista.find('[data-num]').each(function (i) { $(this).text(i + 1); });
    $.post(uleamSlides.ajax, { action: 'uleam_slide_orden', nonce: uleamSlides.nonce, ids: ids }).fail(function () {
      window.alert('No se pudo guardar el orden. Recarga la página e inténtalo de nuevo.');
    });
  };
  lista.sortable({ handle: '.us-asa', axis: 'y', placeholder: 'us-hueco', update: guardar });
  lista.on('click', '[data-sube], [data-baja]', function () {
    var fila = $(this).closest('tr'), sube = this.hasAttribute('data-sube'), otra = sube ? fila.prev() : fila.next();
    if (!otra.length) return;
    sube ? fila.insertBefore(otra) : fila.insertAfter(otra);
    guardar();
  });
})(jQuery);

<?php
// Asistente flotante "¿Te ayudo?": preguntas frecuentes (CPT "faq") y, opcional, una API de agente externo.
// Todo se edita en wp-admin: textos y API en Personalizar → Asistente; preguntas en el menú "Preguntas frecuentes".

const CB_TEXTOS = array(
	'cb_boton'   => array( 'Texto del botón flotante', '¿Te ayudo?' ),
	'cb_titulo'  => array( 'Título del chat', 'Asistente virtual' ),
	'cb_saludo'  => array( 'Mensaje de bienvenida', '¡Hola! Soy el asistente virtual de la Secretaría General. ¿Qué necesitas? Cuéntame con tus palabras o elige una de las opciones de abajo.' ),
	'cb_error'   => array( 'Mensaje si el agente no responde', 'No pude obtener una respuesta. Intenta de nuevo o elige una pregunta frecuente.' ),
	'cb_docs'    => array( 'Mensaje antes de la lista de documentos encontrados', 'Encontré estos documentos:' ),
	'cb_nada'    => array( 'Mensaje cuando no hay respuesta ni documentos', 'No encontré una respuesta. Prueba con otras palabras o elige una de estas preguntas:' ),
	'cb_enlaces' => array( 'Mensaje antes de los enlaces directos al sitio', 'Te llevo directo aquí:' ),
	'cb_ir'      => array( 'Respuesta al elegir un acceso directo', 'Claro, lo encuentras aquí:' ),
	'cb_mas'     => array( 'Mensaje que sigue a cada respuesta, antes de nuevas sugerencias', '¿Te ayudo con algo más?' ),
	'cb_arch'    => array( 'Título de las sugerencias (secciones y ficha)', 'Sugerencias' ),
	'cb_ficha'   => array( 'Texto del acceso a la ficha de requerimiento', 'Solicitar mi ficha de requerimiento' ),
	'cb_campo'   => array( 'Texto del campo de escritura', 'Escribe tu pregunta…' ),
);

// Gestos del avatar flotante: cada uno se ve en un momento del chat. Las imágenes se cambian en Personalizar → Asistente.
const CB_GESTOS = array(
	'saludo'       => array( 'Saludo (al abrir el chat)', 'assets/img/saludo.webp' ),
	'reposo'       => array( 'Reposo (esperando tu pregunta)', 'assets/img/reposo.webp' ),
	'pensando'     => array( 'Pensando (buscando la respuesta)', 'assets/img/pensando.webp' ),
	'respondiendo' => array( 'Respondiendo', 'assets/img/respondiendo.webp' ),
	'error'        => array( 'Error', 'assets/img/error.webp' ),
);

// Cabeza que pesa junto al botón cuando el chat está cerrado; se cambia en Personalizar → Asistente.
const CB_CABEZA = 'assets/img/cabeza-pensando-v2.webp';

function cb_txt( $k ) { return get_theme_mod( $k, CB_TEXTOS[ $k ][1] ); }

add_action( 'init', function () {
	register_post_type( 'faq', array(
		'labels'       => array( 'name' => 'Preguntas frecuentes', 'singular_name' => 'Pregunta', 'add_new_item' => 'Añadir pregunta', 'edit_item' => 'Editar pregunta' ),
		'public'       => false,
		'show_ui'      => true,
		'show_in_rest' => true,
		'menu_icon'    => 'dashicons-format-chat',
		'supports'     => array( 'title', 'editor', 'page-attributes' ), // título = pregunta, contenido = respuesta, orden = posición
	) );
} );
add_filter( 'enter_title_here', fn( $t, $p ) => 'faq' === $p->post_type ? 'Escribe la pregunta' : $t, 10, 2 );

add_action( 'customize_register', function ( $c ) {
	$c->add_section( 'uleam_chat', array( 'title' => 'Asistente (chat)', 'priority' => 33, 'description' => 'Las preguntas se editan en el menú «Preguntas frecuentes».' ) );
	$c->add_setting( 'cb_activo', array( 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ) );
	$c->add_control( 'cb_activo', array( 'section' => 'uleam_chat', 'label' => 'Mostrar el botón flotante del asistente', 'type' => 'checkbox' ) );
	foreach ( CB_TEXTOS as $k => $d ) {
		$c->add_setting( $k, array( 'default' => $d[1], 'sanitize_callback' => 'sanitize_text_field' ) );
		$c->add_control( $k, array( 'section' => 'uleam_chat', 'label' => $d[0], 'type' => 'text' ) );
	}
	foreach ( CB_GESTOS as $k => $d ) {
		$c->add_setting( "cb_gesto_$k", array( 'default' => get_theme_file_uri( $d[1] ), 'sanitize_callback' => 'esc_url_raw' ) );
		$c->add_control( new WP_Customize_Image_Control( $c, "cb_gesto_$k", array( 'section' => 'uleam_chat', 'label' => 'Avatar: ' . $d[0] ) ) );
	}
	$c->add_setting( 'cb_cabeza', array( 'default' => get_theme_file_uri( CB_CABEZA ), 'sanitize_callback' => 'esc_url_raw' ) );
	$c->add_control( new WP_Customize_Image_Control( $c, 'cb_cabeza', array( 'section' => 'uleam_chat', 'label' => 'Cabeza junto al botón (chat cerrado; vacía = sin imagen)' ) ) );
	$c->add_setting( 'cb_api_url', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
	$c->add_control( 'cb_api_url', array( 'section' => 'uleam_chat', 'label' => 'API del agente (URL, vacío = solo preguntas frecuentes)', 'type' => 'url',
		'description' => 'Se envía POST JSON {"message": "...", "history": [...]} y se espera {"reply": "..."} (también vale "answer", "respuesta" o "text").' ) );
	$c->add_setting( 'cb_api_key', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
	$c->add_control( 'cb_api_key', array( 'section' => 'uleam_chat', 'label' => 'Clave de la API (se envía como Bearer; nunca llega al navegador)', 'type' => 'text' ) );
} );

// El navegador habla con WordPress y este reenvía al agente: así la clave queda en el servidor.
add_action( 'rest_api_init', function () {
	register_rest_route( 'uleam/v1', '/chat', array(
		'methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => 'cb_responder',
	) );
} );

// Palabras clave del mensaje: sin tildes, sin palabras cortas ni vacías.
function cb_palabras( $t ) {
	$vacias = array( 'donde','como','cual','cuales','cuando','quiero','necesito','puedo','puede','hola','para','pero','esta','estan','este','esto','esos','esas','tiene','tienen','sobre','desde','hasta','entre','algun','alguna','archivo','archivos','documento','documentos' );
	$p = preg_split( '/[^a-z0-9]+/', strtolower( remove_accents( $t ) ), -1, PREG_SPLIT_NO_EMPTY );
	return array_slice( array_values( array_unique( array_filter( $p, fn( $w ) => strlen( $w ) >= 4 && ! in_array( $w, $vacias, true ) ) ) ), 0, 6 );
}

// Pregunta frecuente que mejor coincide (título pesa el doble que la respuesta); null si no llega al mínimo.
function cb_faq( $kw, $faqs ) {
	$mejor = null; $max = 1;
	foreach ( $faqs as $f ) {
		$ti = remove_accents( strtolower( $f->post_title ) );
		$co = remove_accents( strtolower( wp_strip_all_tags( $f->post_content ) ) );
		$pt = 0;
		foreach ( $kw as $w ) $pt += ( false !== strpos( $ti, $w ) ? 2 : 0 ) + ( false !== strpos( $co, $w ) ? 1 : 0 );
		if ( $pt > $max ) { $max = $pt; $mejor = $f; }
	}
	return $mejor ? wp_kses_post( wpautop( $mejor->post_content ) ) : null;
}

// Documentos (PDF) que contienen las palabras; los que coinciden con más palabras van primero. Reutiliza doc_api().
function cb_docs( $kw ) {
	$hits = $fil = array();
	foreach ( array_diff( $kw, array( 'general' ) ) as $w ) { // «general» está en casi todos los títulos
		$r = doc_api( array( 'q' => $w, 'seccion' => '', 'grupo' => '' ) );
		foreach ( is_wp_error( $r ) ? array() : $r as $d ) { $hits[ $d['u'] ] = ( $hits[ $d['u'] ] ?? 0 ) + 1; $fil[ $d['u'] ] = $d['ti']; }
	}
	arsort( $hits );
	return array_map( fn( $u ) => array( 't' => $fil[ $u ], 'u' => esc_url_raw( $u ) ), array_slice( array_keys( $hits ), 0, 5 ) );
}

// Enlace a una sección de documentos (listado filtrado del sitio).
function cb_url_sec( $t ) { return add_query_arg( array( 's' => '', 'a' => $t->slug ), home_url( '/' ) ); }

// Página de la ficha de requerimiento (la que contiene el shortcode [ficha]); null si no existe.
function cb_url_ficha() {
	$p = get_posts( array( 'post_type' => 'page', 'numberposts' => 1, 's' => '[ficha', 'fields' => 'ids' ) );
	return $p ? get_permalink( $p[0] ) : null;
}

// Enlaces directos al sitio: secciones de documentos cuyo nombre (o el de su órgano) contiene una palabra, y la ficha.
function cb_enlaces( $kw ) {
	$out = array();
	if ( array_intersect( $kw, array( 'ficha', 'requerimiento', 'solicitud', 'solicitar', 'tramite', 'tramites' ) ) && ( $u = cb_url_ficha() ) ) {
		$out[] = array( 't' => cb_txt( 'cb_ficha' ), 'u' => $u );
	}
	$sec = array();
	foreach ( (array) get_terms( array( 'taxonomy' => 'seccion', 'hide_empty' => false ) ) as $t ) {
		$nm = remove_accents( strtolower( $t->name ) );
		$n  = array_sum( array_map( 'strlen', array_filter( $kw, fn( $w ) => false !== strpos( $nm, $w ) ) ) ); // pesa más la palabra más larga (más específica)
		if ( ! $n ) continue;
		$p = $t->parent ? get_term( $t->parent, 'seccion' ) : null;
		$sec[] = array( $n, array( 't' => ( $p && ! is_wp_error( $p ) ? $p->name . ' · ' : '' ) . $t->name, 'u' => cb_url_sec( $t ) ) );
	}
	usort( $sec, fn( $x, $y ) => $y[0] <=> $x[0] ); // las más específicas primero
	$out = array_merge( $out, array_column( $sec, 1 ) );
	return array_slice( $out, 0, 4 );
}

// Respuesta del agente externo (si hay API); null si no está configurado o falla, y entonces responden las preguntas frecuentes.
function cb_agente( $msg, $hist ) {
	$url = get_theme_mod( 'cb_api_url', '' );
	if ( ! $url ) return null;
	$key = get_theme_mod( 'cb_api_key', '' );
	$res = wp_remote_post( $url, array(
		'timeout' => 20,
		'headers' => array( 'Content-Type' => 'application/json' ) + ( $key ? array( 'Authorization' => 'Bearer ' . $key ) : array() ),
		'body'    => wp_json_encode( array( 'message' => $msg, 'history' => $hist ) ),
	) );
	$j = is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ? null : json_decode( wp_remote_retrieve_body( $res ), true );
	foreach ( array( 'reply', 'answer', 'respuesta', 'text' ) as $c ) {
		if ( ! empty( $j[ $c ] ) && is_string( $j[ $c ] ) ) return wp_kses_post( $j[ $c ] );
	}
	return null;
}

function cb_responder( WP_REST_Request $r ) {
	$msg = mb_substr( sanitize_textarea_field( (string) $r->get_param( 'message' ) ), 0, 500 );
	if ( '' === $msg ) return new WP_Error( 'cb_vacio', cb_txt( 'cb_error' ), array( 'status' => 400 ) );
	// Límite por IP (20 mensajes/min) para que nadie sature el buscador ni gaste la cuota del agente.
	$k = 'cb_rl_' . md5( $_SERVER['REMOTE_ADDR'] ?? '' );
	$n = (int) get_transient( $k );
	if ( $n >= 20 ) return new WP_Error( 'cb_limite', 'Demasiados mensajes, espera un momento.', array( 'status' => 429 ) );
	set_transient( $k, $n + 1, MINUTE_IN_SECONDS );
	$hist = array_slice( array_map( fn( $h ) => array( 'role' => 'bot' === ( $h['role'] ?? '' ) ? 'assistant' : 'user', 'content' => mb_substr( sanitize_text_field( $h['content'] ?? '' ), 0, 500 ) ), (array) $r->get_param( 'history' ) ), -10 );
	$faqs  = get_posts( array( 'post_type' => 'faq', 'numberposts' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'date' => 'ASC' ) ) );
	$kw    = cb_palabras( $msg );
	$resp  = cb_agente( $msg, $hist ) ?? cb_faq( $kw, $faqs );
	$docs  = cb_docs( $kw );
	$enl   = cb_enlaces( $kw );
	return array( 'reply' => $resp ?: esc_html( cb_txt( $docs ? 'cb_docs' : ( $enl ? 'cb_enlaces' : 'cb_nada' ) ) ), 'docs' => $docs, 'links' => $enl, 'docs_txt' => $resp && ( $docs || $enl ) ? esc_html( cb_txt( 'cb_docs' ) ) : '');
}

add_action( 'wp_footer', function () {
	if ( ! get_theme_mod( 'cb_activo', true ) ) return;
	$faqs = get_posts( array( 'post_type' => 'faq', 'numberposts' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'date' => 'ASC' ) ) );
	$gestos = '';
	foreach ( CB_GESTOS as $k => $d ) { // una imagen por gesto; el JS muestra la que toca según el estado del chat
		$u = get_theme_mod( "cb_gesto_$k", get_theme_file_uri( $d[1] ) );
		if ( $u ) $gestos .= '<img data-estado="' . esc_attr( $k ) . '" src="' . esc_url( $u ) . '" alt="" width="454" height="680" loading="lazy">';
	}
	$cab = get_theme_mod( 'cb_cabeza', get_theme_file_uri( CB_CABEZA ) );
	?>
<div class="cb" data-flecha="<?php echo esc_attr( uleam_icon( 'arrow-right' ) ); ?>" data-api="<?php echo esc_url( rest_url( 'uleam/v1/chat' ) ); ?>" data-error="<?php echo esc_attr( cb_txt( 'cb_error' ) ); ?>">
  <section class="cb-panel" id="cb-panel" role="dialog" aria-label="<?php echo esc_attr( cb_txt( 'cb_titulo' ) ); ?>" hidden>
    <header><span class="cb-av"><?php echo uleam_icon( 'message-circle' ); ?></span><b><?php echo esc_html( cb_txt( 'cb_titulo' ) ); ?></b><button type="button" class="cb-rec" aria-label="Reiniciar conversación" title="Reiniciar conversación"><?php echo uleam_icon( 'recargar' ); ?></button><button type="button" class="cb-x" aria-label="Cerrar chat"><?php echo uleam_icon( 'cerrar' ); ?></button></header>
    <div class="cb-msgs" aria-live="polite"><div class="cb-fila bot"><span class="cb-av2"><?php echo uleam_icon( 'message-circle' ); ?></span><div class="cb-m bot"><?php echo esc_html( cb_txt( 'cb_saludo' ) ); ?></div></div></div>
    <?php $ficha = cb_url_ficha(); $secs = get_terms( array( 'taxonomy' => 'seccion', 'parent' => 0, 'hide_empty' => false ) ); ?>
    <?php // Fuente de las sugerencias: chatbot.js las va mostrando de pocas en pocas, una vez cada una. ?>
    <div class="cb-datos" hidden data-resp="<?php echo esc_attr( cb_txt( 'cb_ir' ) ); ?>" data-mas="<?php echo esc_attr( cb_txt( 'cb_mas' ) ); ?>" data-tit="<?php echo esc_attr( cb_txt( 'cb_arch' ) ); ?>">
      <?php if ( $ficha ) : ?><button type="button" data-u="<?php echo esc_url( $ficha ); ?>"><?php echo uleam_icon( 'file-text' ); ?><?php echo esc_html( cb_txt( 'cb_ficha' ) ); ?></button><?php endif; ?>
      <?php foreach ( $faqs as $f ) : ?><button type="button"><?php echo uleam_icon( 'message-circle' ); ?><?php echo esc_html( $f->post_title ); ?></button><div><?php echo wp_kses_post( wpautop( $f->post_content ) ); ?></div><?php endforeach; ?>
      <?php foreach ( (array) $secs as $t ) : ?><button type="button" data-u="<?php echo esc_url( cb_url_sec( $t ) ); ?>"><?php echo uleam_icon( 'archive' ); ?><?php echo esc_html( $t->name ); ?></button><?php endforeach; ?>
    </div>
    <form class="cb-form"><input type="text" maxlength="500" autocomplete="off" placeholder="<?php echo esc_attr( cb_txt( 'cb_campo' ) ); ?>" aria-label="<?php echo esc_attr( cb_txt( 'cb_campo' ) ); ?>"><button type="submit" aria-label="Enviar"><?php echo uleam_icon( 'send' ); ?></button></form>
  </section>
  <div class="cb-figura" aria-hidden="true" hidden><?php echo $gestos; ?></div>
  <?php if ( $cab ) : ?><button type="button" class="cb-cabeza" aria-label="Abrir el asistente virtual" aria-controls="cb-panel"><img src="<?php echo esc_url( $cab ); ?>" alt="" width="248" height="240"></button><?php endif; ?>
  <button type="button" class="cb-btn" aria-expanded="false" aria-controls="cb-panel"><?php echo uleam_icon( 'message-circle' ); ?><span><?php echo esc_html( cb_txt( 'cb_boton' ) ); ?></span></button>
</div>
<script>
// Pinta ya la conversación guardada (antes de que cargue chatbot.js) para que no parpadee al cambiar de página.
(function () {
  try {
    var n = performance.getEntriesByType('navigation')[0], e = JSON.parse(sessionStorage.getItem('cb_estado')), c = document.querySelector('.cb');
    if (!c || !e || !e.h || !e.abierto || (n && n.type === 'reload')) return;
    c.classList.add('cb-quieto');
    c.dataset.ini = c.querySelector('.cb-msgs').innerHTML; // saludo original, para el botón de reiniciar
    c.querySelector('.cb-msgs').innerHTML = e.h;
    c.querySelector('.cb-panel').hidden = false;
    c.querySelector('.cb-msgs').scrollTop = 1e6;
  } catch (x) {}
})();
</script>
<?php } );

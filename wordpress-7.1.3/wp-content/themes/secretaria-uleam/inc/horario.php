<?php
// [horario]: tarjetas de horario, correo y ubicación. Todo se edita en Apariencia → Personalizar → Horario de atención.

const HA_DIAS = array( 5 => 'Lunes a viernes', 6 => 'Lunes a sábado', 7 => 'Todos los días' );
// Textos de las tarjetas: clave => [etiqueta en el Personalizador, valor por defecto].
const HA_TEXTOS = array(
	'ha_t_horario'   => array( 'Título de la tarjeta principal', 'Horario de atención' ),
	'ha_t_apertura'  => array( 'Etiqueta de la hora de apertura', 'Apertura' ),
	'ha_t_cierre'    => array( 'Etiqueta de la hora de cierre', 'Cierre' ),
	'ha_t_correo'    => array( 'Título de la tarjeta de correo', 'Escríbenos' ),
	'ha_t_copiar'    => array( 'Botón para copiar el correo', 'Copiar correo' ),
	'ha_t_copiado'   => array( 'Mensaje al copiar el correo', '¡Copiado!' ),
	'ha_t_ubicacion' => array( 'Título de la tarjeta de ubicación', 'Dónde estamos' ),
	'ha_t_mapa'      => array( 'Botón del mapa', 'Ver en el mapa' ),
);

add_action( 'customize_register', function ( $c ) {
	$c->add_section( 'uleam_horario', array( 'title' => 'Horario de atención', 'priority' => 31 ) );
	$campos = array(
		'ha_inicio'    => array( 'Hora de apertura', '08:00', 'time', 'sanitize_text_field' ),
		'ha_fin'       => array( 'Hora de cierre', '17:00', 'time', 'sanitize_text_field' ),
		'ha_correo'    => array( 'Correo de atención', 'secretaria.general@uleam.edu.ec', 'email', 'sanitize_email' ),
		'ha_nota'      => array( 'Texto de la tarjeta de correo', 'Atendemos consultas y solicitudes por correo electrónico.', 'text', 'sanitize_text_field' ),
		'ha_direccion' => array( 'Dirección', "Av. Circunvalación – Vía a San Mateo\nManta – Manabí – Ecuador", 'textarea', 'sanitize_textarea_field' ),
		'ha_mapa'      => array( 'Enlace del mapa', 'https://www.google.com/maps/search/?api=1&query=Universidad+Laica+Eloy+Alfaro+de+Manab%C3%AD+Manta', 'url', 'esc_url_raw' ),
	);
	foreach ( $campos as $k => $v ) {
		$c->add_setting( "uleam_$k", array( 'default' => $v[1], 'sanitize_callback' => $v[3] ) );
		$c->add_control( "uleam_$k", array( 'section' => 'uleam_horario', 'label' => $v[0], 'type' => $v[2] ) );
	}
	$c->add_setting( 'uleam_ha_dias', array( 'default' => 5, 'sanitize_callback' => 'absint' ) );
	$c->add_control( 'uleam_ha_dias', array( 'section' => 'uleam_horario', 'label' => 'Días de atención', 'type' => 'select', 'choices' => HA_DIAS ) );
	foreach ( HA_TEXTOS as $k => $v ) {
		$c->add_setting( "uleam_$k", array( 'default' => $v[1], 'sanitize_callback' => 'sanitize_text_field' ) );
		$c->add_control( "uleam_$k", array( 'section' => 'uleam_horario', 'label' => $v[0], 'type' => 'text' ) );
	}
} );

function ha_mod( $k, $def ) {
	return get_theme_mod( "uleam_$k", $def ) ?: $def;
}

// Texto editable de las tarjetas, ya escapado para HTML.
function ha_txt( $k ) {
	return esc_html( ha_mod( $k, HA_TEXTOS[ $k ][1] ) );
}

add_shortcode( 'horario', function () {
	$ini    = ha_mod( 'ha_inicio', '08:00' );
	$fin    = ha_mod( 'ha_fin', '17:00' );
	$dias   = (int) ha_mod( 'ha_dias', 5 );
	$correo = ha_mod( 'ha_correo', 'secretaria.general@uleam.edu.ec' );
	$dir    = ha_mod( 'ha_direccion', "Av. Circunvalación – Vía a San Mateo\nManta – Manabí – Ecuador" );
	$mapa   = ha_mod( 'ha_mapa', 'https://www.google.com/maps/search/?api=1&query=Universidad+Laica+Eloy+Alfaro+de+Manab%C3%AD+Manta' );
	$pills  = '';
	foreach ( array( 1 => 'L', 2 => 'M', 3 => 'M', 4 => 'J', 5 => 'V', 6 => 'S', 0 => 'D' ) as $n => $l ) {
		$on     = 0 === $n ? 7 === $dias : $n <= $dias;
		$nombre = array( 'Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado' )[ $n ];
		$pills .= '<li data-d="' . $n . '" class="' . ( $on ? 'on' : '' ) . '" title="' . $nombre . ( $on ? '' : ': cerrado' ) . '"><abbr title="' . $nombre . '">' . $l . '</abbr></li>';
	}
	ob_start(); ?>
<div class="ha" data-ini="<?php echo esc_attr( $ini ); ?>" data-fin="<?php echo esc_attr( $fin ); ?>" data-dias="<?php echo esc_attr( $dias ); ?>">
  <div class="ha-grid">
    <article class="ha-card ha-main">
      <div class="ha-top">
        <span class="ha-ic"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></span>
        <div class="ha-tit">
          <h2><?php echo ha_txt( 'ha_t_horario' ); ?></h2>
          <p class="ha-sub"><?php echo esc_html( HA_DIAS[ $dias ] ?? HA_DIAS[5] ); ?></p>
        </div>
        <p class="ha-hora"><span class="ha-h"><small><?php echo ha_txt( 'ha_t_apertura' ); ?></small><?php echo esc_html( $ini ); ?></span><span class="ha-sep">–</span><span class="ha-h"><small><?php echo ha_txt( 'ha_t_cierre' ); ?></small><?php echo esc_html( $fin ); ?></span></p>
      </div>
      <div class="ha-pie">
        <ul class="ha-dias" aria-label="Días de atención"><?php echo $pills; ?></ul>
        <p class="ha-estado" aria-live="polite"></p>
      </div>
    </article>
    <article class="ha-card">
      <span class="ha-ic"><?php echo uleam_icon( 'mail' ); ?></span>
      <h2><?php echo ha_txt( 'ha_t_correo' ); ?></h2>
      <p><?php echo esc_html( ha_mod( 'ha_nota', 'Atendemos consultas y solicitudes por correo electrónico.' ) ); ?></p>
      <a class="ha-mail" href="mailto:<?php echo esc_attr( $correo ); ?>"><?php echo esc_html( $correo ); ?></a>
      <button type="button" class="ha-btn ha-copiar" data-copiar="<?php echo esc_attr( $correo ); ?>" data-ok="<?php echo ha_txt( 'ha_t_copiado' ); ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg><span><?php echo ha_txt( 'ha_t_copiar' ); ?></span></button>
    </article>
    <article class="ha-card">
      <span class="ha-ic"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg></span>
      <h2><?php echo ha_txt( 'ha_t_ubicacion' ); ?></h2>
      <p><?php echo nl2br( esc_html( $dir ) ); ?></p>
      <a class="ha-btn" href="<?php echo esc_url( $mapa ); ?>" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 17 17 7M7 7h10v10"/></svg><span><?php echo ha_txt( 'ha_t_mapa' ); ?></span></a>
    </article>
  </div>
</div>
	<?php
	return ob_get_clean();
} );

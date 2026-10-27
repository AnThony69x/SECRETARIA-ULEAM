<?php
// [ficha]: Ficha de requerimiento de documentos en línea (formulario interactivo + comprobante imprimible).
// El envío, el guardado y la administración están en inc/ficha-envio.php. Todo el texto se edita en Apariencia → Personalizar → Ficha de requerimiento.

// Textos y datos editables: clave => [etiqueta del Personalizador, valor por defecto, tipo].
const FICHA_CAMPOS = array(
	'ficha_codigo'   => array( 'Código del formulario (sus últimos dígitos se reemplazan por el número de la solicitud)', 'PAC-01-F-001', 'text' ),
	'ficha_revision' => array( 'Revisión', '1', 'text' ),
	'ficha_inst'     => array( 'Institución (membrete del PDF)', 'Universidad Laica Eloy Alfaro de Manabí', 'text' ),
	'ficha_nombre'   => array( 'Nombre del documento', 'Ficha de requerimiento de documentos', 'text' ),
	'ficha_proc'     => array( 'Procedimiento', 'Emisión y entrega de certificado de promoción.', 'text' ),
	'ficha_titulo'   => array( 'Título sobre el formulario', 'Secretaría General', 'text' ),
	'ficha_docs'     => array( 'Documentos que se pueden solicitar (uno por línea: Nombre | valor en USD)', "Certificado de matrícula | 5.00\nCertificado de promoción | 5.00\nCertificación de indicador al mérito | 8.00\nAnalítico de notas | 12.00\nCertificación de acta de grado original / fiel copia de su original | 5.00\nCertificación de título original | 10.00\nCertificación de promedio de acta de grado | 6.00\nCertificación de promedio de año/semestre/nivel/global | 8.00\nCertificación de copia de título | 6.00\nCertificación de no tener impedimento de tercera matrícula (cambio de IES) | 10.00\nCertificación de haber agotado la tercera matrícula (cambio de IES) | 10.00\nCertificación IES de aval oficial | 10.00\nCertificación de acceso a estudios de cuarto nivel | 10.00\nCertificación de firma de autoridades académicas | 3.00\nCertificación de escala de calificaciones | 6.00\nRécord único | 10.00\nCertificación de no haber sido sancionado por la ULEAM (para otras universidades) | 5.00\nCertificación Universidad Santa María | 20.00\nCertificación récord académico traducción/inglés | 20.00\nCertificación consolidada para el exterior | 20.00\nSobre certificado para documentos al exterior | 2.00\nCertificación IES acreditada / carrera acreditada | 10.00", 'textarea' ),
	'ficha_requisitos' => array( 'Requisitos (una línea terminada en ":" es un título; el resto son requisitos en negrilla)', "Estudiantes:\nCopia de cédula\nCertificados de matrícula\nCertificados de promoción\nCertificado de no haber sido sancionado (cuando son estudiantes)\nCertificado de no tener tercera matrícula\nPara profesionales:\nTítulo\nActa de grado\nCédula\nPara documentos al exterior:\nSobre membretado para enviar documentos al exterior", 'textarea' ),
	'ficha_visibles' => array( 'Documentos visibles al inicio (el resto se despliega)', '6', 'text' ),
	'ficha_nota'     => array( 'Nota bajo la lista de documentos', 'Valores en USD según la Resolución OCS-SE-003 No. 007-2024 (Órgano Colegiado Superior). Se pagan al retirar el documento.', 'textarea' ),
	'ficha_aviso'    => array( 'Aviso de protección de datos', 'Usaremos tus datos solo para atender esta solicitud. Se guardan en el sistema de la Secretaría General y no se comparten con terceros.', 'textarea' ),
	'ficha_ok'       => array( 'Mensaje al generar la solicitud', 'Tu solicitud fue registrada y tu PDF se descargó. Págalo y envía los documentos indicados al correo de Secretaría General.', 'textarea' ),
	'ficha_pagos'    => array( 'Métodos de pago (uno por línea: Banco | Titular | N.º de cuenta)', "Banco Pichincha | Universidad Laica Eloy Alfaro de Manabí | xxxxxxx
Banco del Pacífico | Universidad Laica Eloy Alfaro de Manabí | xxxxxxx", 'textarea' ),
	'ficha_pago_nota' => array( 'Texto antes de los requisitos ({correo} se reemplaza por el correo de atención)', "Todo trámite institucional generado en línea debe enviarse al correo de Secretaría General ({correo}) con estos documentos adjuntos:", 'textarea' ),
);

add_action( 'customize_register', function ( $c ) {
	$c->add_section( 'uleam_ficha', array( 'title' => 'Ficha de requerimiento', 'priority' => 32 ) );
	foreach ( FICHA_CAMPOS as $k => $v ) {
		$san = array( 'text' => 'sanitize_text_field', 'textarea' => 'sanitize_textarea_field', 'email' => 'sanitize_email' )[ $v[2] ];
		$c->add_setting( "uleam_$k", array( 'default' => $v[1], 'sanitize_callback' => $san ) );
		$c->add_control( "uleam_$k", array( 'section' => 'uleam_ficha', 'label' => $v[0], 'type' => $v[2] ) );
	}
} );

// Mensajes de error cuando el envío no usa JavaScript.
const FICHA_ERRORES = array(
	'x'    => 'No pudimos generar la solicitud. Revisa los datos e inténtalo de nuevo.',
	'rapido' => 'Hiciste varias solicitudes seguidas. Espera un rato e inténtalo de nuevo.',
	'datos' => 'Revisa los datos: todos los campos son obligatorios, la cédula debe tener 10 dígitos y debes marcar un documento y aceptar el aviso.',
);

function ficha_mod( $k ) {
	return get_theme_mod( "uleam_$k", FICHA_CAMPOS[ $k ][1] ) ?: FICHA_CAMPOS[ $k ][1];
}

// Número consecutivo de la solicitud en el año, con tres dígitos (001, 002…; vuelve a 001 cada enero).
function ficha_num( $n ) {
	return sprintf( '%03d', $n );
}

// El código lleva el consecutivo al final: PAC-01-F-001 → PAC-01-F-007 para la solicitud 7.
function ficha_codigo_pref() {
	return preg_replace( '/\d+$/', '', ficha_mod( 'ficha_codigo' ) );
}
function ficha_codigo_num( $n ) {
	return ficha_codigo_pref() . ficha_num( $n );
}
// Código de la próxima solicitud del año (el contador guarda la última asignada).
function ficha_codigo_proximo() {
	return ficha_codigo_num( (int) get_option( 'uleam_ficha_n_' . wp_date( 'Y' ), 0 ) + 1 );
}

// Documentos solicitables: una línea = "Nombre | valor" (el valor es opcional; sin líneas vacías ni repetidas).
function ficha_lineas() {
	$out = array();
	foreach ( explode( "\n", ficha_mod( 'ficha_docs' ) ) as $l ) {
		list( $n, $v ) = array_pad( array_map( 'trim', explode( '|', $l, 2 ) ), 2, '' );
		if ( '' !== $n ) {
			$out[ $n ] = is_numeric( $v ) ? (float) $v : null;
		}
	}
	return $out; // nombre => valor|null
}
function ficha_docs() {
	return array_keys( ficha_lineas() );
}

// Requisitos: una línea terminada en ":" es título de grupo; el resto son requisitos. Devuelve [es_titulo, texto].
function ficha_requisitos() {
	$out = array();
	foreach ( explode( "\n", ficha_mod( 'ficha_requisitos' ) ) as $l ) {
		$l = trim( $l );
		if ( '' !== $l ) {
			$out[] = array( ':' === substr( $l, -1 ), $l );
		}
	}
	return $out;
}

// Métodos de pago editables: una línea = "Banco | Titular | N.º de cuenta".
function ficha_pagos() {
	$out = array();
	foreach ( explode( "\n", ficha_mod( 'ficha_pagos' ) ) as $l ) {
		$c = array_pad( array_map( 'trim', explode( '|', $l ) ), 3, '' );
		if ( '' !== $c[0] ) {
			$out[] = $c;
		}
	}
	return $out;
}

// Campo de texto con etiqueta y mensaje de error accesible.
// Identificador estable de un documento (no depende del orden de la lista).
function ficha_clave( $texto ) {
	return substr( md5( $texto ), 0, 8 );
}

// Token firmado con la hora de carga: no caduca con la caché de la página y no se puede inventar.
function ficha_token( $ts ) {
	return wp_hash( 'ficha|' . $ts );
}

function ficha_campo( $id, $etiqueta, $extra = '' ) {
	return '<div class="ficha-c"><label for="f-' . $id . '">' . esc_html( $etiqueta ) . '</label><input id="f-' . $id . '" name="' . $id . '" type="text" required aria-describedby="e-' . $id . '"' . $extra . '><p class="ficha-e" id="e-' . $id . '" role="alert"></p></div>';
}

add_shortcode( 'ficha', function () {
	$chks = '';
	$vis  = max( 1, (int) ficha_mod( 'ficha_visibles' ) );
	$mas  = '';
	$k    = 0;
	foreach ( ficha_lineas() as $d => $v ) { // el valor viaja solo para mostrarlo; el servidor no lo usa
		$esc  = '';
		$esc .= '<label class="ficha-chk"><input type="checkbox" name="docs[]" value="' . ficha_clave( $d ) . '"' . ( null !== $v ? ' data-valor="' . esc_attr( $v ) . '"' : '' ) . '><span>' . esc_html( $d ) . '</span>' . ( null !== $v ? '<em>$' . number_format( $v, 2 ) . '</em>' : '' ) . '</label>';
		if ( ++$k <= $vis ) {
			$chks .= $esc;
		} else {
			$mas .= $esc;
		}
	}
	$ok  = isset( $_GET['ficha'], $_GET['n'] ) && 'ok' === $_GET['ficha']; // respaldo sin JavaScript (solo informativo; el número real viene del guardado)
	$pdf = $ok && 0 === strpos( (string) ( $_GET['pdf'] ?? '' ), admin_url( 'admin-ajax.php' ) ) ? (string) $_GET['pdf'] : ''; // solo enlaces al propio PDF
	$err = isset( $_GET['ficha'] ) && 'err' === $_GET['ficha'] ? ( FICHA_ERRORES[ sanitize_key( $_GET['m'] ?? '' ) ] ?? FICHA_ERRORES['x'] ) : '';
	ob_start(); ?>
<div class="ficha" data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-anio="<?php echo (int) wp_date( 'Y' ); ?>" data-codigo="<?php echo esc_attr( ficha_codigo_pref() ); ?>">
  <dl class="ficha-cab">
    <div><dt>Nombre del documento</dt><dd><?php echo esc_html( mb_strtoupper( ficha_mod( 'ficha_nombre' ) ) ); ?></dd></div>
    <div><dt>Código</dt><dd data-codigo-siguiente><?php echo esc_html( ficha_codigo_proximo() ); ?></dd></div>
    <div><dt>Procedimiento</dt><dd><?php echo esc_html( ficha_mod( 'ficha_proc' ) ); ?></dd></div>
    <div><dt>Revisión</dt><dd><?php echo esc_html( ficha_mod( 'ficha_revision' ) ); ?></dd></div>
  </dl>
  <?php if ( $ok ) : ?><p class="ficha-exito" role="status"><?php echo uleam_icon( 'star' ) . esc_html( ficha_mod( 'ficha_ok' ) ) . ' No. ' . ficha_num( (int) $_GET['n'] ); ?> <?php if ( $pdf ) : ?><a href="<?php echo esc_url( $pdf ); ?>" download>Descargar PDF</a><?php endif; ?></p><?php endif; ?>
  <?php if ( $err ) : ?><p class="ficha-e ficha-error" role="alert"><?php echo esc_html( $err ); ?></p><?php endif; ?>
  <form class="ficha-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate<?php echo $ok ? ' hidden' : ''; ?>>
    <input type="hidden" name="action" value="uleam_ficha">
    <input type="hidden" name="ts" value="<?php echo time(); ?>">
    <input type="hidden" name="sig" value="<?php echo esc_attr( ficha_token( time() ) ); ?>">
    <p class="ficha-trampa" aria-hidden="true"><label>No llenar este campo <input type="text" name="web" tabindex="-1" autocomplete="off"></label></p>
    <h2 class="ficha-t"><?php echo esc_html( mb_strtoupper( ficha_mod( 'ficha_titulo' ) ) ); ?></h2>
    <fieldset class="ficha-g"><legend>Datos del solicitante</legend>
      <?php echo ficha_campo( 'nombres', 'Apellidos y nombres del/la solicitante', ' maxlength="120" autocomplete="name"' )
        . ficha_campo( 'cedula', 'Número de cédula y/o pasaporte', ' maxlength="20" autocomplete="off"' )
        . ficha_campo( 'facultad', 'Facultad', ' maxlength="120"' ) . ficha_campo( 'carrera', 'Carrera', ' maxlength="120"' )
        . ficha_campo( 'ingreso', 'Año de ingreso', ' inputmode="numeric" maxlength="4"' ) . ficha_campo( 'egreso', 'Año de egreso', ' inputmode="numeric" maxlength="4"' ); ?>
    </fieldset>
    <fieldset class="ficha-g"><legend>Documento solicitado</legend>
      <div class="ficha-docs"><?php echo $chks; ?></div>
      <?php if ( $mas ) : ?>
      <button type="button" class="ficha-mas-btn" aria-expanded="false" aria-controls="ficha-mas" data-mas="<?php echo (int) ( $k - $vis ); ?>"><?php echo uleam_icon( 'chevron-down' ); ?><span>Ver más documentos (<?php echo (int) ( $k - $vis ); ?>)</span></button>
      <div class="ficha-docs ficha-mas" id="ficha-mas" hidden><?php echo $mas; ?></div>
      <?php endif; ?>
      <p class="ficha-nota"><?php echo esc_html( ficha_mod( 'ficha_nota' ) ); ?></p>
      <p class="ficha-e" id="e-docs" role="alert"></p>
    </fieldset>
    <div class="ficha-c"><label for="f-detalle">Detalle de requerimiento <small id="n-detalle">0 / 1000</small></label><textarea id="f-detalle" name="detalle" rows="5" maxlength="1000"></textarea></div>
    <label class="ficha-chk ficha-acepto"><input type="checkbox" name="acepto" value="1" required><span><?php echo esc_html( ficha_mod( 'ficha_aviso' ) ); ?></span></label>
    <p class="ficha-e" id="e-acepto" role="alert"></p>
    <p class="ficha-e ficha-error" role="alert"></p>
    <button type="submit" class="btn azul"><?php echo uleam_icon( 'file-text' ); ?>Generar solicitud</button>
  </form>
  <section class="ficha-rev" hidden aria-labelledby="rev-t">
    <h2 class="ficha-t" id="rev-t">Revisa tus datos antes de generar tu solicitud</h2>
    <p class="ficha-rev-aviso">Comprueba que todo esté correcto. Al confirmar se registra la solicitud y se genera tu PDF con la ficha y los métodos de pago.</p>
    <ul class="ficha-p-datos">
      <?php foreach ( array( 'nombres' => 'Apellidos y nombres', 'cedula' => 'Cédula y/o pasaporte', 'facultad' => 'Facultad', 'carrera' => 'Carrera', 'ingreso' => 'Año de ingreso', 'egreso' => 'Año de egreso' ) as $k => $e ) : ?>
        <li><span><?php echo esc_html( $e ); ?>:</span><b data-r="<?php echo esc_attr( $k ); ?>"></b></li>
      <?php endforeach; ?>
    </ul>
    <p class="ficha-p-t">Documento solicitado:</p>
    <ul class="ficha-p-docs" data-r="docs"></ul>
    <p class="ficha-total" data-r="total"></p>
    <p class="ficha-p-t">Detalle de requerimiento:</p>
    <p class="ficha-p-det" data-r="detalle"></p>
    <p class="ficha-e ficha-error" role="alert"></p>
    <p class="ficha-acc"><button type="button" class="btn" data-corregir>Corregir datos</button> <button type="button" class="btn azul" data-confirmar><?php echo uleam_icon( 'file-text' ); ?>Confirmar y generar PDF</button></p>
  </section>
  <section class="ficha-hoja" hidden aria-label="Comprobante de la solicitud">
    <p class="ficha-exito" role="status"><?php echo uleam_icon( 'star' ) . esc_html( ficha_mod( 'ficha_ok' ) ); ?></p>
    <div class="ficha-papel">
      <p class="ficha-p-cab"><b data-codigo-ficha><?php echo esc_html( ficha_codigo_proximo() ); ?></b> · Revisión <?php echo esc_html( ficha_mod( 'ficha_revision' ) ); ?> · <?php echo esc_html( ficha_mod( 'ficha_proc' ) ); ?></p>
      <h2><?php echo esc_html( mb_strtoupper( ficha_mod( 'ficha_titulo' ) ) ); ?></h2>
      <h3><?php echo esc_html( mb_strtoupper( ficha_mod( 'ficha_nombre' ) ) ); ?> No. <span data-f="n"></span></h3>
      <ul class="ficha-p-datos">
        <?php foreach ( array( 'nombres' => 'Apellidos y nombres', 'cedula' => 'Cédula y/o pasaporte', 'facultad' => 'Facultad', 'carrera' => 'Carrera', 'ingreso' => 'Año de ingreso', 'egreso' => 'Año de egreso' ) as $k => $e ) : ?>
          <li><span><?php echo esc_html( $e ); ?>:</span><b data-f="<?php echo esc_attr( $k ); ?>"></b></li>
        <?php endforeach; ?>
      </ul>
      <p class="ficha-p-t">Documento solicitado:</p>
      <ul class="ficha-p-docs" data-f="docs"></ul>
      <p class="ficha-total" data-f="total"></p>
      <p class="ficha-p-t">Detalle de requerimiento:</p>
      <p class="ficha-p-det" data-f="detalle"></p>
      <table class="ficha-p-firmas"><tr><td>Nombre del/la solicitante:</td><td>Firma:</td><td>Fecha:</td></tr><tr><td>Nombre de quien recibe:</td><td>Firma:</td><td>Fecha:</td></tr></table>
    </div>
    <section class="ficha-pago" aria-labelledby="pago-t">
      <h3 id="pago-t"><?php echo uleam_icon( 'landmark' ); ?>Siguiente paso: pago y entrega</h3>
      <ul class="ficha-bancos">
        <?php foreach ( ficha_pagos() as $m ) : ?>
        <li>
          <span class="icono"><?php echo uleam_icon( 'landmark' ); ?></span>
          <span class="ficha-banco"><b><?php echo esc_html( $m[0] ); ?></b><small><?php echo esc_html( $m[1] ); ?></small></span>
          <span class="ficha-cuenta"><small>N.º de cuenta</small><code><?php echo esc_html( $m[2] ); ?></code></span>
          <button type="button" class="ficha-copiar" data-copiar="<?php echo esc_attr( $m[2] ); ?>" aria-label="Copiar número de cuenta de <?php echo esc_attr( $m[0] ); ?>"><?php echo uleam_icon( 'copy' ); ?><span>Copiar</span></button>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php $correo = ha_mod( 'ha_correo', 'secretaria.general@uleam.edu.ec' ); ?>
      <p class="ficha-pago-nota"><?php echo uleam_icon( 'mail' ); ?><span><?php echo implode( '<a href="mailto:' . esc_attr( $correo ) . '">' . esc_html( $correo ) . '</a>', array_map( 'esc_html', explode( '{correo}', ficha_mod( 'ficha_pago_nota' ) ) ) ); ?></span></p>
      <?php if ( $req = ficha_requisitos() ) : ?>
      <div class="ficha-req">
        <?php foreach ( $req as $r ) : ?>
        <p class="<?php echo $r[0] ? 'ficha-req-t' : 'ficha-req-i'; ?>"><?php echo esc_html( $r[1] ); ?></p>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </section>
    <p class="ficha-acc"><a class="btn azul" data-pdf href="#" download><?php echo uleam_icon( 'download' ); ?>Descargar PDF</a> <button type="button" class="btn" data-nueva>Nueva solicitud</button></p>
  </section>
  <dialog class="ficha-modal" aria-labelledby="m-t">
    <svg class="ficha-m-ico" viewBox="0 0 52 52" aria-hidden="true"><circle cx="26" cy="26" r="24"/><path d="M15 27l8 8 14-16"/></svg>
    <h2 id="m-t">¡Tu ficha está lista!</h2>
    <p class="ficha-m-cod">Solicitud No. <b data-m="numero"></b></p>
    <p>Tu PDF se descargó. Ahora realiza el pago y envía los documentos al correo de Secretaría General.</p>
    <button type="button" class="btn azul" data-ir><?php echo uleam_icon( 'arrow-right' ); ?>Ver pago y envío</button>
  </dialog>
</div>
	<?php
	return ob_get_clean();
} );

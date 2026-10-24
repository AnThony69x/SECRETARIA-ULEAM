<?php
/**
 * Configuración de WordPress para el SERVIDOR. Copia este archivo como wordpress-7.1.3/wp-config.php en el servidor
 * y reemplaza los valores marcados con TU_… No lo subas a git (wp-config.php ya está ignorado) y no reutilices la
 * contraseña de la base de datos de desarrollo.
 */

/* Base de datos */
define( 'DB_NAME', 'TU_BASE_DE_DATOS' );
define( 'DB_USER', 'TU_USUARIO_BD' );
define( 'DB_PASSWORD', 'TU_CONTRASENA_BD' ); // nueva y larga; solo este usuario debe poder usar esta base
define( 'DB_HOST', 'localhost' );             // o el host de la base (ej. 'localhost:3306')
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );
$table_prefix = 'wp_';                        // el mismo de la base importada

/* Claves de seguridad: genera unas NUEVAS en https://api.wordpress.org/secret-key/1.1/salt/ y pégalas aquí. */
define( 'AUTH_KEY',         'TU_CLAVE' );
define( 'SECURE_AUTH_KEY',  'TU_CLAVE' );
define( 'LOGGED_IN_KEY',    'TU_CLAVE' );
define( 'NONCE_KEY',        'TU_CLAVE' );
define( 'AUTH_SALT',        'TU_CLAVE' );
define( 'SECURE_AUTH_SALT', 'TU_CLAVE' );
define( 'LOGGED_IN_SALT',   'TU_CLAVE' );
define( 'NONCE_SALT',       'TU_CLAVE' );

/*
 * Direcciones del sitio.
 *  - WP_HOME    = la parte visual (lo que ve el público):      https://TU-DOMINIO
 *  - WP_SITEURL = dónde está WordPress; el administrador queda en WP_SITEURL/wp-admin/
 * Normalmente son la misma dirección. Deben coincidir con lo que dejó despliegue/cambiar-url.php en la base de datos.
 */
define( 'WP_HOME', 'https://TU-DOMINIO' );
define( 'WP_SITEURL', 'https://TU-DOMINIO' );

/* Detrás de un proxy o balanceador que termina el HTTPS (Cloudflare, Nginx delante de Apache…). */
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
	$_SERVER['HTTPS'] = 'on';
}

/* Seguridad y rendimiento */
define( 'FORCE_SSL_ADMIN', true );          // el inicio de sesión y el administrador solo por HTTPS
define( 'DISALLOW_FILE_EDIT', true );       // sin editor de archivos del tema/plugins dentro del administrador
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_MEMORY_LIMIT', '256M' );
define( 'WP_ENVIRONMENT_TYPE', 'production' );
define( 'AUTOMATIC_UPDATER_DISABLED', false );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );   // solo actualizaciones de seguridad automáticas

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';

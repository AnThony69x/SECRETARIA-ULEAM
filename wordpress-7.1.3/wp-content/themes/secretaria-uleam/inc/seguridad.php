<?php
// IP del visitante para los límites de uso. Detrás de Cloudflare/proxy, REMOTE_ADDR es la del proxy:
// define( 'ULEAM_PROXY_CF', true ) en wp-config.php solo si el servidor acepta tráfico únicamente de Cloudflare.
function uleam_ip() {
	$ip = ( defined( 'ULEAM_PROXY_CF' ) && ULEAM_PROXY_CF ) ? ( $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '' ) : '';
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : ( $_SERVER['REMOTE_ADDR'] ?? '' );
}

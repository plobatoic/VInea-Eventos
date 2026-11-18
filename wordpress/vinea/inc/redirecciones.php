<?php
/**
 * Redirecciones 301 de URLs antiguas (solo si la URL da 404).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'template_redirect', 'vinea_redirecciones' );
function vinea_redirecciones(): void {
	if ( ! is_404() ) {
		return;
	}
	$mapa = array(
		'/proveedor-de-freelance/'           => '/servicios/personal-eventos/',
		'/servicios/proveedor-de-freelance/' => '/servicios/personal-eventos/',
		'/servicios/congresos-2-2/'          => '/servicios/congresos/',
		'/nuestra-esencia/'                  => '/descubre-esencia-vinea-eventos/',
		'/accesibilidad-web-vinea-eventos/'  => '/accesibilidad-web-real-decreto-1112-2018/',
		'/articulo/'                         => '/blog/',
	);

	$ruta = (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH );
	// Quitar el subdirectorio de instalación, si lo hay.
	$base = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	if ( '/' !== $base && 0 === strpos( $ruta, $base ) ) {
		$ruta = '/' . substr( $ruta, strlen( $base ) );
	}
	$ruta = trailingslashit( strtolower( $ruta ) );

	if ( isset( $mapa[ $ruta ] ) ) {
		wp_safe_redirect( home_url( $mapa[ $ruta ] ), 301 );
		exit;
	}
}

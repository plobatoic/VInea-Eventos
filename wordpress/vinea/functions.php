<?php
/**
 * Vinea: punto de entrada del tema. Solo carga los archivos de inc/.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VINEA_VERSION', '1.0.0' );
define( 'VINEA_DIR', get_template_directory() );
define( 'VINEA_URL', get_template_directory_uri() );

// El orden importa solo por legibilidad: todo se engancha a hooks.
foreach ( array(
	'setup',
	'recursos',
	'ajustes',
	'seo',
	'formularios',
	'portada',
	'plantilla',
	'redirecciones',
	'afinado',
	'eventos',
	'tienda',
	'pulido',
	'admin',
	'importador',
) as $vinea_archivo ) {
	$vinea_ruta = VINEA_DIR . '/inc/' . $vinea_archivo . '.php';
	if ( is_readable( $vinea_ruta ) ) {
		require_once $vinea_ruta;
	}
}
unset( $vinea_archivo, $vinea_ruta );

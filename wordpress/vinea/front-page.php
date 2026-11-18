<?php
/**
 * Portada de Vinea: el diseño que han elegido (el del recorrido en vídeo), con su gota 3D,
 * el portal al Liceu, los servicios en horizontal, cifras, proyectos, accesibilidad, momentos,
 * opiniones y próximos eventos. El marcado está en partes/portada-video.html; las fotos en
 * assets/portada/; los estilos en assets/css/portada.css y la animación en assets/js/portada.js.
 */
get_header();

$portada = get_template_directory() . '/partes/portada-video.html';
if ( file_exists( $portada ) ) {
	echo str_replace( // phpcs:ignore WordPress.Security.EscapeOutput -- HTML propio del tema.
		array( '{{T}}', '{{H}}' ),
		array( esc_url( get_template_directory_uri() ), untrailingslashit( esc_url( home_url() ) ) ),
		file_get_contents( $portada ) // phpcs:ignore
	);
}

get_footer();

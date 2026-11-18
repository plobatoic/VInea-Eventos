<?php
/**
 * Ajustes finos para que el contenido importado se vea exactamente como en la web original.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// No cambiar comillas, guiones ni puntos suspensivos: el texto se muestra carácter a carácter como el original.
add_filter( 'run_wptexturize', '__return_false' );

/**
 * - Emojis que llegaron como imagen externa (📍…): vuelven a ser texto al inicio de su párrafo.
 * - Títulos H1 dentro del contenido: se muestran como H2 (la página ya tiene su H1 en la cabecera).
 */
add_filter(
	'the_content',
	static function ( $html ) {
		if ( is_admin() || ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		$html = preg_replace(
			'~(<p\b[^>]*>)((?:(?!</p>).)*)</p>\s*<figure>\s*<img\b[^>]*s\.w\.org/images/core/emoji[^>]*\balt="([^"]*)"[^>]*>\s*</figure>~is',
			'$1$3 $2</p>',
			$html
		);
		$html = preg_replace( '~<figure>\s*<img\b[^>]*s\.w\.org/images/core/emoji[^>]*\balt="([^"]*)"[^>]*>\s*</figure>~is', '<p>$1</p>', $html );
		$html = preg_replace( '~<h1(\b[^>]*)>(.*?)</h1>~is', '<h2$1>$2</h2>', $html );
		return $html;
	},
	20
);

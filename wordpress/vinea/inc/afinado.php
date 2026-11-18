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

/**
 * La Home no usa el editor de bloques: sus textos (los de la Home original) se editan en
 * «Contenido de la portada». Para que no se vea en blanco, la pantalla de la Home abre con el
 * editor clásico, sin la caja de contenido vacía, y con los campos de la portada a la vista.
 */
add_filter(
	'use_block_editor_for_post',
	static function ( $usar, $post ) {
		return ( $post && (int) $post->ID === (int) get_option( 'page_on_front' ) ) ? false : $usar;
	},
	PHP_INT_MAX,
	2
);
add_action(
	'load-post.php',
	static function () {
		$id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0; // phpcs:ignore WordPress.Security.NonceVerification
		if ( $id && $id === (int) get_option( 'page_on_front' ) ) {
			remove_post_type_support( 'page', 'editor' );
		}
	}
);
add_action(
	'edit_form_after_title',
	static function ( $post ) {
		if ( (int) $post->ID !== (int) get_option( 'page_on_front' ) ) {
			return;
		}
		echo '<div class="notice notice-info inline" style="margin:16px 0;padding:12px 16px"><p style="font-size:14px"><strong>Textos de la portada.</strong> Aquí abajo están todos los textos de la Home, en el mismo orden en que se ven en la web: entrada, servicios, conciencia social, la esencia de Vinea, opiniones y frase final. Cámbialos y pulsa <em>Actualizar</em>. El diseño y las animaciones los pone el tema.</p></div>';
	}
);

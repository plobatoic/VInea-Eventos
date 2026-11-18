<?php
/**
 * Contenido editable de la portada (meta box + función vinea_portada()).
 * Se guarda en el post meta `_vinea_portada` de la página de inicio.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Claves de texto simple. */
function vinea_portada_claves_texto(): array {
	return array(
		'claim', 'h1', 'boton_texto', 'boton_url', 'lema', 'intro',
		'conciencia_titulo', 'conciencia_texto', 'conciencia_proyecto', 'conciencia_imagen',
		'esencia_titulo', 'esencia_texto', 'esencia_imagen', 'esencia_boton_texto', 'esencia_boton_url',
		'opiniones_titulo', 'cierre',
	);
}

/** Claves que son listas. */
function vinea_portada_claves_lista(): array {
	return array( 'servicios', 'opiniones', 'montaje', 'momentos' );
}

/** Valores por defecto: importar/portada.json (o vacío). */
function vinea_portada_defecto(): array {
	static $def = null;
	if ( null !== $def ) {
		return $def;
	}
	$def = array();
	foreach ( vinea_portada_claves_texto() as $k ) {
		$def[ $k ] = '';
	}
	foreach ( vinea_portada_claves_lista() as $k ) {
		$def[ $k ] = array();
	}
	$ruta = VINEA_DIR . '/importar/portada.json';
	if ( is_readable( $ruta ) ) {
		$json = json_decode( (string) file_get_contents( $ruta ), true );
		if ( is_array( $json ) ) {
			foreach ( $def as $k => $v ) {
				if ( array_key_exists( $k, $json ) ) {
					$def[ $k ] = $json[ $k ];
				}
			}
		}
	}
	return $def;
}

/** ID de la página de portada. */
function vinea_portada_id(): int {
	$id = (int) get_option( 'page_on_front' );
	if ( ! $id ) {
		$home = get_page_by_path( 'home' );
		$id   = $home ? (int) $home->ID : 0;
	}
	return $id;
}

/* ---------- Conversión texto <-> lista ---------- */

function vinea_portada_partes( string $linea, int $n ): array {
	$p = array_map( 'trim', explode( '|', $linea, $n ) );
	return array_pad( $p, $n, '' );
}

function vinea_portada_lineas( string $texto ): array {
	$out = array();
	foreach ( preg_split( '/\R/u', $texto ) as $l ) {
		$l = trim( $l );
		if ( '' !== $l ) {
			$out[] = $l;
		}
	}
	return $out;
}

function vinea_portada_texto_a_lista( string $clave, string $texto ): array {
	$lista = array();
	foreach ( vinea_portada_lineas( $texto ) as $l ) {
		switch ( $clave ) {
			case 'servicios':
				[ $t, $x, $u, $i ] = vinea_portada_partes( $l, 4 );
				$lista[]           = array( 'titulo' => $t, 'texto' => $x, 'url' => $u, 'imagen' => $i );
				break;
			case 'opiniones':
				[ $a, $f, $x ] = vinea_portada_partes( $l, 3 );
				$lista[]       = array( 'autor' => $a, 'fecha' => $f, 'texto' => str_replace( '\n', "\n", $x ) );
				break;
			default: // montaje, momentos
				$lista[] = array( 'url' => $l );
		}
	}
	return $lista;
}

function vinea_portada_lista_a_texto( string $clave, $lista ): string {
	if ( is_string( $lista ) ) {
		return $lista;
	}
	$lineas = array();
	foreach ( (array) $lista as $it ) {
		switch ( $clave ) {
			case 'servicios':
				$lineas[] = implode( ' | ', array( $it['titulo'] ?? '', $it['texto'] ?? '', $it['url'] ?? '', $it['imagen'] ?? '' ) );
				break;
			case 'opiniones':
				$lineas[] = implode( ' | ', array( $it['autor'] ?? '', $it['fecha'] ?? '', str_replace( array( "\r\n", "\n" ), '\n', (string) ( $it['texto'] ?? '' ) ) ) );
				break;
			default:
				$lineas[] = is_array( $it ) ? (string) ( $it['url'] ?? '' ) : (string) $it;
		}
	}
	return implode( "\n", $lineas );
}

/**
 * Valores de la portada, con las listas ya convertidas a arrays de arrays.
 */
function vinea_portada(): array {
	$datos = vinea_portada_defecto();
	$id    = vinea_portada_id();
	$meta  = $id ? get_post_meta( $id, '_vinea_portada', true ) : array();

	if ( is_array( $meta ) ) {
		foreach ( $datos as $k => $v ) {
			if ( array_key_exists( $k, $meta ) ) {
				$datos[ $k ] = $meta[ $k ];
			}
		}
	}

	foreach ( vinea_portada_claves_lista() as $k ) {
		if ( is_string( $datos[ $k ] ) ) {
			$datos[ $k ] = vinea_portada_texto_a_lista( $k, $datos[ $k ] );
		} elseif ( in_array( $k, array( 'montaje', 'momentos' ), true ) ) {
			$datos[ $k ] = array_map(
				static fn( $x ) => is_array( $x ) ? $x : array( 'url' => (string) $x ),
				(array) $datos[ $k ]
			);
		}
	}
	foreach ( vinea_portada_claves_texto() as $k ) {
		$datos[ $k ] = (string) $datos[ $k ];
	}
	return $datos;
}

/* ---------- Meta box ---------- */

add_action( 'add_meta_boxes_page', 'vinea_portada_metabox' );
function vinea_portada_metabox( WP_Post $post ): void {
	if ( (int) $post->ID !== vinea_portada_id() ) {
		return;
	}
	add_meta_box( 'vinea_portada', 'Contenido de la portada', 'vinea_portada_metabox_html', 'page', 'normal', 'high' );
}

function vinea_portada_campos(): array {
	// clave => [etiqueta, tipo (input|textarea), filas, ayuda]
	return array(
		'claim'               => array( 'Claim (frase grande de la entrada)', 'input', 1, '' ),
		'h1'                  => array( 'Título principal (H1)', 'input', 1, '' ),
		'boton_texto'         => array( 'Botón principal: texto', 'input', 1, '' ),
		'boton_url'           => array( 'Botón principal: enlace', 'input', 1, 'Por ejemplo /contacto/' ),
		'lema'                => array( 'Lema', 'input', 1, '' ),
		'intro'               => array( 'Texto de introducción', 'textarea', 4, '' ),
		'servicios'           => array( 'Servicios', 'textarea', 8, 'Una línea por servicio con el formato: Título | Texto | URL | URL de imagen' ),
		'conciencia_titulo'   => array( 'Conciencia: título', 'input', 1, '' ),
		'conciencia_texto'    => array( 'Conciencia: texto', 'textarea', 4, '' ),
		'conciencia_proyecto' => array( 'Conciencia: proyecto', 'input', 1, '' ),
		'conciencia_imagen'   => array( 'Conciencia: imagen (URL)', 'input', 1, 'Dirección completa de la imagen; puedes copiarla desde la Biblioteca de medios.' ),
		'esencia_titulo'      => array( 'Esencia: título', 'input', 1, '' ),
		'esencia_texto'       => array( 'Esencia: texto', 'textarea', 4, '' ),
		'esencia_imagen'      => array( 'Esencia: imagen (URL)', 'input', 1, 'Dirección completa de la imagen; puedes copiarla desde la Biblioteca de medios.' ),
		'esencia_boton_texto' => array( 'Esencia: texto del botón', 'input', 1, '' ),
		'esencia_boton_url'   => array( 'Esencia: enlace del botón', 'input', 1, '' ),
		'opiniones_titulo'    => array( 'Opiniones: título', 'input', 1, '' ),
		'opiniones'           => array( 'Opiniones', 'textarea', 8, 'Una por línea con el formato: Autor | Fecha | Texto. Los saltos de línea dentro del texto se escriben como \n' ),
		'cierre'              => array( 'Frase de cierre', 'input', 1, '' ),
		'montaje'             => array( 'Montaje (fotos de la transición)', 'textarea', 6, 'Una URL de imagen por línea. Son las fotos que pasan durante la transición de entrada.' ),
		'momentos'            => array( 'Momentos (galería)', 'textarea', 6, 'Una URL de imagen por línea.' ),
	);
}

function vinea_portada_metabox_html( WP_Post $post ): void {
	wp_nonce_field( 'vinea_portada_guardar', 'vinea_portada_nonce' );
	$datos = vinea_portada();
	echo '<p class="description">Estos textos e imágenes se muestran en la portada. Si dejas un campo vacío, no se mostrará.</p>';
	foreach ( vinea_portada_campos() as $clave => $c ) {
		[ $etiqueta, $tipo, $filas, $ayuda ] = $c;
		$id    = 'vinea_portada_' . $clave;
		$valor = in_array( $clave, vinea_portada_claves_lista(), true )
			? vinea_portada_lista_a_texto( $clave, $datos[ $clave ] )
			: (string) $datos[ $clave ];

		echo '<p style="margin-bottom:2px"><label for="' . esc_attr( $id ) . '"><strong>' . esc_html( $etiqueta ) . '</strong></label></p>';
		if ( 'textarea' === $tipo ) {
			printf( '<textarea class="widefat" rows="%d" id="%s" name="vinea_portada[%s]">%s</textarea>', (int) $filas, esc_attr( $id ), esc_attr( $clave ), esc_textarea( $valor ) );
		} else {
			printf( '<input type="text" class="widefat" id="%s" name="vinea_portada[%s]" value="%s">', esc_attr( $id ), esc_attr( $clave ), esc_attr( $valor ) );
		}
		if ( $ayuda ) {
			echo '<p class="description" style="margin-top:2px">' . esc_html( $ayuda ) . '</p>';
		}
	}
}

add_action( 'save_post_page', 'vinea_portada_guardar', 10, 1 );
function vinea_portada_guardar( int $post_id ): void {
	if ( ! isset( $_POST['vinea_portada_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vinea_portada_nonce'] ) ), 'vinea_portada_guardar' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$entrada = isset( $_POST['vinea_portada'] ) && is_array( $_POST['vinea_portada'] ) ? wp_unslash( $_POST['vinea_portada'] ) : array();
	$limpio  = array();
	foreach ( vinea_portada_campos() as $clave => $c ) {
		$v = isset( $entrada[ $clave ] ) ? (string) $entrada[ $clave ] : '';
		if ( in_array( $clave, array( 'servicios', 'montaje', 'momentos' ), true ) || false !== strpos( $clave, 'url' ) || false !== strpos( $clave, 'imagen' ) ) {
			$limpio[ $clave ] = ( 'textarea' === $c[1] ) ? sanitize_textarea_field( $v ) : sanitize_text_field( $v );
		} else {
			// Texto con HTML en línea permitido (enlaces, negritas, cursivas, saltos).
			$limpio[ $clave ] = vinea_kses_linea( $v );
		}
	}
	update_post_meta( $post_id, '_vinea_portada', $limpio );
}

<?php
/**
 * Importador del contenido original (vineaeventos.com).
 *
 * Se lanza por URL, paso a paso:
 *   https://DOMINIO/?vinea_importar=TOKEN&paso=0
 * El token es el que devuelve importar/token.php (si el archivo no existe,
 * el importador está desactivado). Ver importar/LEEME.md.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VINEA_LOTE_IMAGENES = 5;
const VINEA_PRESUPUESTO_S = 14; // Segundos máximos por lote de imágenes.

add_action( 'init', 'vinea_importador_arrancar', 30 );
function vinea_importador_arrancar(): void {
	if ( ! isset( $_GET['vinea_importar'] ) || is_admin() ) {
		return;
	}
	$ruta_token = VINEA_DIR . '/importar/token.php';
	if ( ! is_readable( $ruta_token ) ) {
		return; // Importador desactivado.
	}
	$token = include $ruta_token;
	$dado  = is_string( $_GET['vinea_importar'] ) ? wp_unslash( $_GET['vinea_importar'] ) : '';
	if ( ! is_string( $token ) || '' === $token || ! hash_equals( $token, $dado ) ) {
		return;
	}

	$paso = isset( $_GET['paso'] ) ? absint( $_GET['paso'] ) : 0;
	@set_time_limit( 60 ); // phpcs:ignore
	@ini_set( 'memory_limit', '512M' ); // phpcs:ignore

	if ( get_option( 'vinea_importado' ) && empty( $_GET['forzar'] ) ) {
		wp_send_json(
			array(
				'ok'             => false,
				'paso'           => $paso,
				'siguiente'      => null,
				'total_estimado' => null,
				'mensaje'        => 'La importación ya se hizo. Añade &forzar=1 para repetirla (es idempotente).',
				'detalles'       => array(),
			),
			409
		);
	}

	// Actuar como administrador (permisos para menús, categorías, medios…).
	$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID', 'orderby' => 'ID' ) );
	if ( $admins ) {
		wp_set_current_user( (int) $admins[0] );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/post.php';
	require_once ABSPATH . 'wp-admin/includes/taxonomy.php';
	require_once ABSPATH . 'wp-admin/includes/translation-install.php';

	try {
		$r = vinea_importar_paso( $paso );
		wp_send_json( array_merge( array( 'ok' => true, 'paso' => $paso ), $r ) );
	} catch ( Throwable $e ) {
		wp_send_json(
			array(
				'ok'             => false,
				'paso'           => $paso,
				'siguiente'      => null,
				'total_estimado' => null,
				'mensaje'        => 'Error: ' . $e->getMessage(),
				'detalles'       => array( 'archivo' => basename( $e->getFile() ), 'linea' => $e->getLine() ),
			),
			500
		);
	}
}

/* ------------------------------------------------------------------
 * Datos y utilidades
 * ---------------------------------------------------------------- */

function vinea_imp_contenido(): array {
	static $datos = null;
	if ( null === $datos ) {
		$ruta = VINEA_DIR . '/importar/contenido.json';
		if ( ! is_readable( $ruta ) ) {
			throw new RuntimeException( 'No se encuentra importar/contenido.json' );
		}
		$datos = json_decode( (string) file_get_contents( $ruta ), true );
		if ( ! is_array( $datos ) ) {
			throw new RuntimeException( 'importar/contenido.json no es JSON válido' );
		}
	}
	return $datos;
}

function vinea_imp_portada_json(): array {
	$ruta = VINEA_DIR . '/importar/portada.json';
	if ( ! is_readable( $ruta ) ) {
		return array();
	}
	$j = json_decode( (string) file_get_contents( $ruta ), true );
	return is_array( $j ) ? $j : array();
}

function vinea_imp_estado(): array {
	$e = get_option( 'vinea_import_estado', array() );
	return is_array( $e ) ? $e : array();
}

function vinea_imp_guardar_estado( array $e ): void {
	update_option( 'vinea_import_estado', $e, false );
}

/** IDs importados (páginas y entradas) con meta _vinea_id_antiguo. */
function vinea_imp_ids( string $tipo ): array {
	return get_posts(
		array(
			'post_type'      => $tipo,
			'post_status'    => 'any',
			'meta_key'       => '_vinea_id_antiguo',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);
}

function vinea_imp_buscar_por_antiguo( string $tipo, int $id_antiguo ): int {
	$r = get_posts(
		array(
			'post_type'      => $tipo,
			'post_status'    => 'any',
			'meta_key'       => '_vinea_id_antiguo',
			'meta_value'     => $id_antiguo,
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	return $r ? (int) $r[0] : 0;
}

/** Extrae pares URL => alt de los <img src> de un HTML (solo imágenes de la web original). */
function vinea_imp_imagenes_de_html( string $html ): array {
	$out = array();
	if ( ! preg_match_all( '/<img\b[^>]*>/i', $html, $m ) ) {
		return $out;
	}
	foreach ( $m[0] as $tag ) {
		if ( ! preg_match( '/\bsrc\s*=\s*(["\'])(.*?)\1/is', $tag, $s ) ) {
			continue;
		}
		$url = html_entity_decode( trim( $s[2] ) );
		if ( ! vinea_imp_url_descargable( $url ) ) {
			continue;
		}
		$alt = '';
		if ( preg_match( '/\balt\s*=\s*(["\'])(.*?)\1/is', $tag, $a ) ) {
			$alt = html_entity_decode( $a[2] );
		}
		if ( ! isset( $out[ $url ] ) || ( '' === $out[ $url ] && '' !== $alt ) ) {
			$out[ $url ] = $alt;
		}
	}
	return $out;
}

/** Solo se descargan imágenes alojadas en la web original (se descartan emojis de s.w.org, etc.). */
function vinea_imp_url_descargable( string $url ): bool {
	if ( ! preg_match( '~^https?://~i', $url ) ) {
		return false;
	}
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	if ( ! in_array( $host, array( 'vineaeventos.com', 'www.vineaeventos.com' ), true ) ) {
		return false;
	}
	$ext = strtolower( pathinfo( (string) wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
	return in_array( $ext, array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif' ), true );
}

function vinea_imp_con_kses_apagado( callable $fn ) {
	kses_remove_filters();
	try {
		return $fn();
	} finally {
		kses_init_filters();
	}
}

/** Dirige el despacho de los pasos. */
function vinea_importar_paso( int $paso ): array {
	if ( 0 === $paso ) {
		return vinea_imp_paso_ajustes();
	}
	if ( 1 === $paso ) {
		return vinea_imp_paso_paginas();
	}
	if ( 2 === $paso ) {
		return vinea_imp_paso_entradas();
	}
	if ( 3 === $paso ) {
		return vinea_imp_paso_preparar_imagenes();
	}
	$estado  = vinea_imp_estado();
	$final_a = isset( $estado['final_a'] ) ? (int) $estado['final_a'] : 0;
	if ( ! $final_a || $paso < $final_a ) {
		return vinea_imp_paso_lote_imagenes( $paso );
	}
	if ( $paso === $final_a ) {
		return vinea_imp_paso_reemplazar();
	}
	if ( $paso === $final_a + 1 ) {
		return vinea_imp_paso_portada_y_menus();
	}
	throw new RuntimeException( 'Paso fuera de rango.' );
}

/* ------------------------------------------------------------------
 * Paso 0: ajustes y categorías
 * ---------------------------------------------------------------- */
function vinea_imp_paso_ajustes(): array {
	global $wp_rewrite;
	$datos    = vinea_imp_contenido();
	$detalles = array();

	update_option( 'blogname', 'Vinea Eventos y Turismo' );
	update_option( 'blogdescription', 'Agencia de eventos en Barcelona para empresas e instituciones' );
	update_option( 'timezone_string', 'Europe/Madrid' );
	update_option( 'gmt_offset', '' );
	update_option( 'date_format', 'j \d\e F \d\e Y' );
	update_option( 'time_format', 'H:i' );
	update_option( 'start_of_week', 1 );

	$wp_rewrite->set_permalink_structure( '/%postname%/' );

	// Idioma: intenta descargar el paquete es_ES.
	try {
		if ( function_exists( 'wp_can_install_language_pack' ) && wp_can_install_language_pack() ) {
			$idioma = wp_download_language_pack( 'es_ES' );
			$detalles['idioma'] = $idioma ? $idioma : 'no se pudo descargar es_ES';
		}
	} catch ( Throwable $e ) {
		$detalles['idioma'] = 'error: ' . $e->getMessage();
	}
	update_option( 'WPLANG', 'es_ES' );

	// Contenido de ejemplo de WordPress.
	$borrados = array();
	$hola     = get_post( 1 );
	if ( $hola && 'post' === $hola->post_type && in_array( $hola->post_name, array( 'hello-world', 'hola-mundo' ), true ) && ! get_post_meta( 1, '_vinea_id_antiguo', true ) ) {
		wp_delete_post( 1, true );
		$borrados[] = 'entrada de ejemplo';
	}
	$ejemplo = get_post( 2 );
	if ( $ejemplo && 'page' === $ejemplo->post_type && in_array( $ejemplo->post_name, array( 'sample-page', 'pagina-de-ejemplo' ), true ) && ! get_post_meta( 2, '_vinea_id_antiguo', true ) ) {
		wp_delete_post( 2, true );
		$borrados[] = 'página de ejemplo';
	}
	$priv = (int) get_option( 'wp_page_for_privacy_policy' );
	if ( $priv ) {
		$p = get_post( $priv );
		if ( $p && 'draft' === $p->post_status && ! get_post_meta( $priv, '_vinea_id_antiguo', true ) ) {
			wp_delete_post( $priv, true );
			update_option( 'wp_page_for_privacy_policy', 0 );
			$borrados[] = 'política de privacidad de ejemplo';
		}
	}
	$detalles['borrados'] = $borrados;

	// Categorías.
	$cats    = array();
	$defecto = (int) get_option( 'default_category' );
	foreach ( $datos['categorias'] ?? array() as $c ) {
		$slug = sanitize_title( $c['slug'] );
		$t    = get_term_by( 'slug', $slug, 'category' );
		if ( ! $t && 'uncategorized' === $slug && $defecto ) {
			// La categoría por defecto de WP en español tiene otro slug: se adopta.
			$r = wp_update_term( $defecto, 'category', array( 'name' => $c['nombre'], 'slug' => $slug ) );
			$t = is_wp_error( $r ) ? null : get_term( $defecto, 'category' );
		}
		if ( ! $t ) {
			$r = wp_insert_term( $c['nombre'], 'category', array( 'slug' => $slug ) );
			$t = is_wp_error( $r ) ? null : get_term( $r['term_id'], 'category' );
		}
		if ( $t && ! is_wp_error( $t ) ) {
			$cats[ $slug ] = (int) $t->term_id;
		}
	}
	$detalles['categorias'] = count( $cats );

	vinea_imp_guardar_estado( array( 'inicio' => time() ) );

	return array(
		'siguiente'      => 1,
		'total_estimado' => 6,
		'mensaje'        => 'Ajustes aplicados y categorías creadas.',
		'detalles'       => $detalles,
	);
}

/* ------------------------------------------------------------------
 * Paso 1: páginas
 * ---------------------------------------------------------------- */
function vinea_imp_fecha( string $iso ): array {
	$local = str_replace( 'T', ' ', $iso );
	$ts    = strtotime( $local );
	if ( ! $ts || $ts > current_time( 'timestamp' ) ) {
		$local = current_time( 'mysql' );
	}
	return array( $local, get_gmt_from_date( $local ) );
}

function vinea_imp_guardar_seo( int $id, array $seo ): void {
	$t = (string) ( $seo['title'] ?? '' );
	$d = (string) ( $seo['description'] ?? '' );
	update_post_meta( $id, '_vinea_seo_title', $t );
	update_post_meta( $id, '_vinea_seo_desc', $d );
	update_post_meta( $id, '_yoast_wpseo_title', $t );
	update_post_meta( $id, '_yoast_wpseo_metadesc', $d );
}

function vinea_imp_paso_paginas(): array {
	$datos  = vinea_imp_contenido();
	$paginas = $datos['paginas'] ?? array();
	$autor  = get_current_user_id();
	$mapa   = array(); // slug => ID
	$nuevas = 0;
	$actual = 0;

	// Padres primero, hijos después.
	usort( $paginas, static fn( $a, $b ) => ( '' !== $a['padre'] ) <=> ( '' !== $b['padre'] ) );

	vinea_imp_con_kses_apagado(
		static function () use ( $paginas, $autor, &$mapa, &$nuevas, &$actual ): void {
			foreach ( $paginas as $p ) {
				$id_padre = 0;
				if ( '' !== $p['padre'] ) {
					$id_padre = $mapa[ $p['padre'] ] ?? 0;
					if ( ! $id_padre ) {
						$pp       = get_page_by_path( $p['padre'] );
						$id_padre = $pp ? (int) $pp->ID : 0;
					}
				}
				[ $fecha, $fecha_gmt ] = vinea_imp_fecha( (string) $p['fecha'] );
				$args = array(
					'post_type'     => 'page',
					'post_status'   => 'publish',
					'post_name'     => $p['slug'],
					'post_title'    => $p['titulo'],
					'post_content'  => $p['contenido'],
					'post_excerpt'  => $p['extracto'] ?? '',
					'post_parent'   => $id_padre,
					'menu_order'    => (int) ( $p['orden'] ?? 0 ),
					'post_author'   => $autor,
					'post_date'     => $fecha,
					'post_date_gmt' => $fecha_gmt,
				);

				$id = vinea_imp_buscar_por_antiguo( 'page', (int) $p['id_antiguo'] );
				if ( ! $id ) {
					// Adoptar una página existente con la misma ruta.
					$ruta = ( '' !== $p['padre'] ? $p['padre'] . '/' : '' ) . $p['slug'];
					$ex   = get_page_by_path( $ruta );
					$id   = $ex ? (int) $ex->ID : 0;
				}
				if ( $id ) {
					$args['ID'] = $id;
					$id         = wp_update_post( wp_slash( $args ), true );
					++$actual;
				} else {
					$id = wp_insert_post( wp_slash( $args ), true );
					++$nuevas;
				}
				if ( is_wp_error( $id ) ) {
					throw new RuntimeException( 'Página ' . $p['slug'] . ': ' . $id->get_error_message() );
				}
				update_post_meta( $id, '_vinea_id_antiguo', (int) $p['id_antiguo'] );
				vinea_imp_guardar_seo( (int) $id, (array) ( $p['seo'] ?? array() ) );
				$mapa[ $p['slug'] ] = (int) $id;
			}
		}
	);

	return array(
		'siguiente'      => 2,
		'total_estimado' => 6,
		'mensaje'        => 'Páginas importadas.',
		'detalles'       => array( 'creadas' => $nuevas, 'actualizadas' => $actual, 'total' => count( $paginas ) ),
	);
}

/* ------------------------------------------------------------------
 * Paso 2: entradas
 * ---------------------------------------------------------------- */
function vinea_imp_paso_entradas(): array {
	$datos    = vinea_imp_contenido();
	$entradas = $datos['entradas'] ?? array();
	$autor    = get_current_user_id();
	$nuevas   = 0;
	$actual   = 0;

	vinea_imp_con_kses_apagado(
		static function () use ( $entradas, $autor, &$nuevas, &$actual ): void {
			foreach ( $entradas as $e ) {
				$ids_cat = array();
				foreach ( (array) ( $e['categorias'] ?? array() ) as $slug ) {
					$t = get_term_by( 'slug', sanitize_title( $slug ), 'category' );
					if ( $t ) {
						$ids_cat[] = (int) $t->term_id;
					}
				}
				[ $fecha, $fecha_gmt ] = vinea_imp_fecha( (string) $e['fecha'] );
				$args = array(
					'post_type'     => 'post',
					'post_status'   => 'publish',
					'post_name'     => $e['slug'],
					'post_title'    => $e['titulo'],
					'post_content'  => $e['contenido'],
					'post_excerpt'  => $e['extracto'] ?? '',
					'post_author'   => $autor,
					'post_date'     => $fecha,
					'post_date_gmt' => $fecha_gmt,
				);
				if ( $ids_cat ) {
					$args['post_category'] = $ids_cat;
				}
				$id = vinea_imp_buscar_por_antiguo( 'post', (int) $e['id_antiguo'] );
				if ( $id ) {
					$args['ID'] = $id;
					$id         = wp_update_post( wp_slash( $args ), true );
					++$actual;
				} else {
					$id = wp_insert_post( wp_slash( $args ), true );
					++$nuevas;
				}
				if ( is_wp_error( $id ) ) {
					throw new RuntimeException( 'Entrada ' . $e['slug'] . ': ' . $id->get_error_message() );
				}
				update_post_meta( $id, '_vinea_id_antiguo', (int) $e['id_antiguo'] );
				vinea_imp_guardar_seo( (int) $id, (array) ( $e['seo'] ?? array() ) );
			}
		}
	);

	return array(
		'siguiente'      => 3,
		'total_estimado' => 6,
		'mensaje'        => 'Entradas importadas.',
		'detalles'       => array( 'creadas' => $nuevas, 'actualizadas' => $actual, 'total' => count( $entradas ) ),
	);
}

/* ------------------------------------------------------------------
 * Paso 3: preparar la cola de imágenes
 * ---------------------------------------------------------------- */
function vinea_imp_paso_preparar_imagenes(): array {
	$urls = array(); // url => alt

	foreach ( array( 'page', 'post' ) as $tipo ) {
		foreach ( vinea_imp_ids( $tipo ) as $id ) {
			$p = get_post( $id );
			foreach ( vinea_imp_imagenes_de_html( (string) $p->post_content ) as $u => $alt ) {
				if ( ! isset( $urls[ $u ] ) || ( '' === $urls[ $u ] && '' !== $alt ) ) {
					$urls[ $u ] = $alt;
				}
			}
		}
	}

	// Imágenes de portada.json.
	$ruta = VINEA_DIR . '/importar/portada.json';
	if ( is_readable( $ruta ) ) {
		$crudo = (string) file_get_contents( $ruta );
		$crudo = str_replace( '\\/', '/', $crudo );
		if ( preg_match_all( '~https?://[^\s"\'<>\\\\]+~i', $crudo, $m ) ) {
			foreach ( $m[0] as $u ) {
				if ( vinea_imp_url_descargable( $u ) && ! isset( $urls[ $u ] ) ) {
					$urls[ $u ] = '';
				}
			}
		}
	}

	$mapa = get_option( 'vinea_mapa_imagenes', array() );
	$mapa = is_array( $mapa ) ? $mapa : array();
	$cola = array();
	foreach ( $urls as $u => $alt ) {
		if ( isset( $mapa[ $u ] ) && get_post( (int) $mapa[ $u ] ) ) {
			continue; // Ya descargada en una pasada anterior.
		}
		$cola[] = array( $u, $alt );
	}
	update_option( 'vinea_cola_imagenes', $cola, false );
	update_option( 'vinea_mapa_imagenes', $mapa, false );
	update_option( 'vinea_fallos_imagenes', array(), false );

	$estado            = vinea_imp_estado();
	$estado['total_img'] = count( $urls );
	unset( $estado['final_a'] );
	if ( ! $cola ) {
		$estado['final_a'] = 4;
	}
	vinea_imp_guardar_estado( $estado );

	$lotes = (int) ceil( count( $cola ) / VINEA_LOTE_IMAGENES );
	return array(
		'siguiente'      => 4, // Sin imágenes pendientes, el paso 4 ya es el reemplazo.
		'total_estimado' => 4 + $lotes + 2,
		'mensaje'        => sprintf( 'Cola preparada: %d imágenes (%d ya estaban descargadas).', count( $cola ), count( $urls ) - count( $cola ) ),
		'detalles'       => array( 'imagenes_total' => count( $urls ), 'pendientes' => count( $cola ), 'lotes' => $lotes ),
	);
}

/* ------------------------------------------------------------------
 * Pasos 4…: descarga de imágenes por lotes
 * ---------------------------------------------------------------- */
function vinea_imp_paso_lote_imagenes( int $paso ): array {
	$cola   = get_option( 'vinea_cola_imagenes', array() );
	$cola   = is_array( $cola ) ? $cola : array();
	$mapa   = get_option( 'vinea_mapa_imagenes', array() );
	$mapa   = is_array( $mapa ) ? $mapa : array();
	$fallos = get_option( 'vinea_fallos_imagenes', array() );
	$fallos = is_array( $fallos ) ? $fallos : array();

	$inicio = microtime( true );
	$hechas = 0;
	$ok     = 0;

	while ( $cola && $hechas < VINEA_LOTE_IMAGENES && ( microtime( true ) - $inicio ) < VINEA_PRESUPUESTO_S ) {
		[ $url, $alt ] = array_shift( $cola );
		++$hechas;
		$r = vinea_imp_descargar( $url, (string) $alt );
		if ( is_int( $r ) ) {
			$mapa[ $url ] = $r;
			++$ok;
		} else {
			$fallos[] = array( 'url' => $url, 'motivo' => $r );
		}
		// Guardar tras cada imagen para no perder progreso si se corta el paso.
		update_option( 'vinea_mapa_imagenes', $mapa, false );
		update_option( 'vinea_cola_imagenes', $cola, false );
	}
	update_option( 'vinea_fallos_imagenes', $fallos, false );

	$estado = vinea_imp_estado();
	if ( ! $cola ) {
		$estado['final_a'] = $paso + 1;
		vinea_imp_guardar_estado( $estado );
	}
	$lotes_restantes = (int) ceil( count( $cola ) / VINEA_LOTE_IMAGENES );

	return array(
		'siguiente'      => $paso + 1,
		'total_estimado' => $paso + $lotes_restantes + 2,
		'mensaje'        => sprintf( 'Imágenes: %d descargadas en este lote, %d pendientes, %d fallos acumulados.', $ok, count( $cola ), count( $fallos ) ),
		'detalles'       => array( 'descargadas' => count( $mapa ), 'pendientes' => count( $cola ), 'fallos' => count( $fallos ) ),
	);
}

/** @return int|string ID del adjunto o texto de error. */
function vinea_imp_descargar( string $url, string $alt ) {
	$tmp = download_url( $url, 15 );
	if ( is_wp_error( $tmp ) ) {
		return $tmp->get_error_message();
	}
	$nombre = sanitize_file_name( rawurldecode( basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) ) );
	$id     = media_handle_sideload(
		array( 'name' => $nombre, 'tmp_name' => $tmp ),
		0,
		null,
		array( 'post_status' => 'inherit' )
	);
	if ( is_wp_error( $id ) ) {
		if ( file_exists( $tmp ) ) {
			@unlink( $tmp ); // phpcs:ignore
		}
		return $id->get_error_message();
	}
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	update_post_meta( $id, '_vinea_url_original', esc_url_raw( $url ) );
	return (int) $id;
}

/* ------------------------------------------------------------------
 * Paso final A: reemplazar URLs, clases wp-image, imágenes destacadas
 * ---------------------------------------------------------------- */

/** Mapa url antigua => url nueva. */
function vinea_imp_mapa_urls(): array {
	$mapa = get_option( 'vinea_mapa_imagenes', array() );
	$out  = array();
	foreach ( (array) $mapa as $viejo => $id ) {
		$nuevo = wp_get_attachment_url( (int) $id );
		if ( $nuevo ) {
			$out[ $viejo ] = array( (int) $id, $nuevo );
		}
	}
	return $out;
}

function vinea_imp_reemplazar_html( string $html, array $mapa ): string {
	$html = preg_replace_callback(
		'/<img\b[^>]*>/i',
		static function ( array $m ) use ( $mapa ): string {
			$tag = $m[0];
			if ( ! preg_match( '/\bsrc\s*=\s*(["\'])(.*?)\1/is', $tag, $s ) ) {
				return $tag;
			}
			$src = html_entity_decode( trim( $s[2] ) );
			if ( ! isset( $mapa[ $src ] ) ) {
				return $tag;
			}
			[ $id, $nuevo ] = $mapa[ $src ];
			$tag = preg_replace( '/\bsrc\s*=\s*(["\']).*?\1/is', 'src="' . esc_url( $nuevo ) . '"', $tag, 1 );
			$tag = preg_replace( '/\s(?:srcset|sizes)\s*=\s*(["\']).*?\1/is', '', $tag );
			if ( preg_match( '/\bclass\s*=\s*(["\'])(.*?)\1/is', $tag, $c ) ) {
				if ( ! preg_match( '/\bwp-image-\d+\b/', $c[2] ) ) {
					$tag = preg_replace( '/\bclass\s*=\s*(["\'])(.*?)\1/is', 'class="' . trim( $c[2] . ' wp-image-' . $id ) . '"', $tag, 1 );
				}
			} else {
				$tag = preg_replace( '/<img\b/i', '<img class="wp-image-' . $id . '"', $tag, 1 );
			}
			return $tag;
		},
		$html
	);

	// Resto de apariciones (enlaces a archivos, etc.).
	$pares = array();
	foreach ( $mapa as $viejo => $d ) {
		$pares[ $viejo ] = $d[1];
	}
	return strtr( $html, $pares );
}

function vinea_imp_reemplazar_rec( $valor, array $mapa ) {
	if ( is_array( $valor ) ) {
		foreach ( $valor as $k => $v ) {
			$valor[ $k ] = vinea_imp_reemplazar_rec( $v, $mapa );
		}
		return $valor;
	}
	if ( is_string( $valor ) ) {
		$pares = array();
		foreach ( $mapa as $viejo => $d ) {
			$pares[ $viejo ] = $d[1];
		}
		return strtr( $valor, $pares );
	}
	return $valor;
}

function vinea_imp_paso_reemplazar(): array {
	$mapa = vinea_imp_mapa_urls();
	$tocados   = 0;
	$destacadas = 0;

	vinea_imp_con_kses_apagado(
		static function () use ( $mapa, &$tocados, &$destacadas ): void {
			foreach ( array( 'page', 'post' ) as $tipo ) {
				foreach ( vinea_imp_ids( $tipo ) as $id ) {
					$p     = get_post( $id );
					$nuevo = vinea_imp_reemplazar_html( (string) $p->post_content, $mapa );
					if ( $nuevo !== $p->post_content ) {
						wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $nuevo ) ) );
						++$tocados;
					}
					if ( ! has_post_thumbnail( $id ) ) {
						$img = vinea_primera_imagen( $nuevo );
						if ( is_string( $img ) && '' !== $img ) {
							$img = vinea_url_a_id( $img );
						}
						if ( $img && is_int( $img ) && get_post( $img ) ) {
							set_post_thumbnail( $id, $img );
							++$destacadas;
						}
					}
				}
			}
		}
	);

	// Portada: portada.json con las URL nuevas, guardado como meta de la página «home».
	$detalle_portada = 'sin portada.json';
	$portada         = vinea_imp_portada_json();
	if ( $portada ) {
		$portada = vinea_imp_reemplazar_rec( $portada, $mapa );
		$home    = get_page_by_path( 'home' );
		if ( $home ) {
			update_post_meta( $home->ID, '_vinea_portada', $portada );
			$detalle_portada = 'guardada en la página home (ID ' . $home->ID . ')';
		} else {
			$detalle_portada = 'no existe la página home';
		}
	}

	return array(
		'siguiente'      => vinea_imp_estado()['final_a'] + 1,
		'total_estimado' => vinea_imp_estado()['final_a'] + 1,
		'mensaje'        => 'URLs de imágenes reemplazadas.',
		'detalles'       => array(
			'contenidos_actualizados' => $tocados,
			'destacadas_asignadas'    => $destacadas,
			'portada'                 => $detalle_portada,
		),
	);
}

/* ------------------------------------------------------------------
 * Paso final B: portada, blog, menús
 * ---------------------------------------------------------------- */
function vinea_imp_pagina_id( string $slug ): int {
	$ruta = $slug;
	// Las hijas de «servicios» se identifican por slug, sin importar la ruta completa.
	$r = get_posts(
		array(
			'post_type'      => 'page',
			'name'           => $ruta,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	return $r ? (int) $r[0] : 0;
}

/**
 * Crea un menú con la estructura dada (idempotente: lo vacía y lo recrea).
 *
 * @param array $items Cada elemento: [titulo, slug_de_pagina|null, url|null, hijos[]]
 */
function vinea_imp_crear_menu( string $nombre, array $items ): int {
	$menu = wp_get_nav_menu_object( $nombre );
	if ( $menu ) {
		foreach ( (array) wp_get_nav_menu_items( $menu->term_id, array( 'post_status' => 'any' ) ) as $it ) {
			wp_delete_post( $it->ID, true );
		}
		$id_menu = (int) $menu->term_id;
	} else {
		$id_menu = wp_create_nav_menu( $nombre );
		if ( is_wp_error( $id_menu ) ) {
			throw new RuntimeException( 'No se pudo crear el menú ' . $nombre . ': ' . $id_menu->get_error_message() );
		}
	}

	$pos = 0;
	$añadir = static function ( array $lista, int $padre ) use ( &$añadir, &$pos, $id_menu ): void {
		foreach ( $lista as $it ) {
			[ $titulo, $slug, $url ] = array( $it[0], $it[1] ?? null, $it[2] ?? null );
			$args = array(
				'menu-item-title'     => $titulo,
				'menu-item-status'    => 'publish',
				'menu-item-position'  => ++$pos,
				'menu-item-parent-id' => $padre,
			);
			$pid = $slug ? vinea_imp_pagina_id( $slug ) : 0;
			if ( $pid ) {
				$args['menu-item-type']      = 'post_type';
				$args['menu-item-object']    = 'page';
				$args['menu-item-object-id'] = $pid;
			} else {
				$args['menu-item-type'] = 'custom';
				$args['menu-item-url']  = home_url( $url ?? '/' );
			}
			$id_item = wp_update_nav_menu_item( $id_menu, 0, $args );
			if ( is_wp_error( $id_item ) ) {
				continue;
			}
			if ( ! empty( $it[3] ) ) {
				$añadir( $it[3], (int) $id_item );
			}
		}
	};
	$añadir( $items, 0 );
	return $id_menu;
}

function vinea_imp_paso_portada_y_menus(): array {
	global $wp_rewrite;

	$home = vinea_imp_pagina_id( 'home' );
	$blog = vinea_imp_pagina_id( 'blog' );
	if ( $home ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home );
	}
	if ( $blog ) {
		update_option( 'page_for_posts', $blog );
	}
	$priv = vinea_imp_pagina_id( 'politica-privacidad' );
	if ( $priv ) {
		update_option( 'wp_page_for_privacy_policy', $priv );
	}

	// Si la portada se guardó antes de existir page_on_front, ya está en la página home.
	$principal = vinea_imp_crear_menu(
		'Principal',
		array(
			array( 'Home', 'home' ),
			array( 'Nuestra esencia', 'descubre-esencia-vinea-eventos' ),
			array(
				'Servicios',
				'servicios',
				null,
				array(
					array( 'Navidad en Vinea', null, '/corporativos/navidad.html' ),
					array( 'Eventos corporativos', 'eventos-corporativos' ),
					array( 'Team building', 'team-building' ),
					array( 'Conferencias y convenciones', 'conferencias-convenciones' ),
					array( 'Galas de premios', 'galas-de-premios' ),
					array( 'Ferias', 'ferias' ),
					array( 'Congresos', 'congresos' ),
					array( 'Espacios únicos', 'espacios' ),
					array( 'Proveedores de freelance', 'personal-eventos' ),
					array( 'Formaciones y consultorías', 'formaciones-consultorias' ),
					array( 'Padel Experiences', null, '/clinics/padel_experience.html' ),
				),
			),
			array( 'Entradas', 'eventos-2' ),
			array( 'Proyectos', 'blog' ),
			array( 'Contacto', 'contacto' ),
		)
	);

	$pie = vinea_imp_crear_menu(
		'Pie',
		array(
			array( 'Home', 'home' ),
			array( 'Nuestra esencia', 'descubre-esencia-vinea-eventos' ),
			array( 'Servicios', 'servicios' ),
			array( 'Eventos auténticos', null, '/servicios/' ),
			array( 'Consultorías y formaciones', 'formaciones-consultorias' ),
			array( 'Espacios únicos', 'espacios' ),
			array( 'Proveedor de freelance', 'personal-eventos' ),
			array( 'Proyectos', 'blog' ),
			array( 'Política de privacidad', 'politica-privacidad' ),
			array( 'Política de cookies', 'politica-cookies' ),
			array( 'Aviso legal', 'aviso-legal' ),
		)
	);

	$ubicaciones              = (array) get_theme_mod( 'nav_menu_locations', array() );
	$ubicaciones['principal'] = $principal;
	$ubicaciones['pie']       = $pie;
	set_theme_mod( 'nav_menu_locations', $ubicaciones );

	$wp_rewrite->set_permalink_structure( '/%postname%/' );
	flush_rewrite_rules();
	update_option( 'vinea_importado', 1 );

	$mapa   = (array) get_option( 'vinea_mapa_imagenes', array() );
	$fallos = (array) get_option( 'vinea_fallos_imagenes', array() );
	return array(
		'siguiente'      => null,
		'total_estimado' => (int) ( vinea_imp_estado()['final_a'] ?? 0 ) + 1,
		'mensaje'        => 'Importación terminada.',
		'detalles'       => array(
			'paginas'   => count( vinea_imp_ids( 'page' ) ),
			'entradas'  => count( vinea_imp_ids( 'post' ) ),
			'imagenes'  => count( $mapa ),
			'fallos'    => count( $fallos ),
			'fallos_lista' => array_slice( $fallos, 0, 30 ),
			'portada'   => $home ? 'home (ID ' . $home . ')' : 'no encontrada',
			'blog'      => $blog ? 'blog (ID ' . $blog . ')' : 'no encontrada',
		),
	);
}

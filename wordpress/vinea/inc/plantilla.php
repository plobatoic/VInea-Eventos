<?php
/**
 * Funciones comunes de las plantillas.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------
 * Utilidades de texto
 * ---------------------------------------------------------------- */

/** Permite solo HTML en línea. */
function vinea_kses_linea( string $html ): string {
	return wp_kses(
		$html,
		array(
			'a'      => array( 'href' => true, 'title' => true, 'target' => true, 'rel' => true ),
			'strong' => array(),
			'b'      => array(),
			'em'     => array(),
			'i'      => array(),
			'br'     => array(),
			'span'   => array( 'class' => true ),
			'small'  => array(),
			'sup'    => array(),
			'sub'    => array(),
			'mark'   => array(),
		)
	);
}

/** «Mes Año» en español con el mes en mayúscula inicial. */
function vinea_mes_anio( $post = null ): string {
	$t = wp_date( 'F Y', get_post_time( 'U', true, $post ) );
	return mb_strtoupper( mb_substr( $t, 0, 1 ) ) . mb_substr( $t, 1 );
}

/** Nombre de la categoría principal (ignora la categoría por defecto). */
function vinea_categoria_principal( $post = null ): string {
	$post    = get_post( $post );
	$cats    = $post ? get_the_category( $post->ID ) : array();
	$defecto = (int) get_option( 'default_category' );
	foreach ( $cats as $c ) {
		if ( (int) $c->term_id !== $defecto && 'uncategorized' !== $c->slug ) {
			return $c->name;
		}
	}
	return '';
}

/* ------------------------------------------------------------------
 * Imágenes
 * ---------------------------------------------------------------- */

/** ID de adjunto a partir de una URL de la propia web (0 si no hay). */
function vinea_url_a_id( string $url ): int {
	$host_web = wp_parse_url( home_url(), PHP_URL_HOST );
	$host_url = wp_parse_url( $url, PHP_URL_HOST );
	if ( ! $host_url || ( $host_web && $host_url !== $host_web && 'www.' . $host_url !== $host_web && $host_url !== 'www.' . $host_web ) ) {
		return 0;
	}
	return (int) attachment_url_to_postid( $url );
}

/** Compara dos imágenes (ID o URL). */
function vinea_misma_imagen( $a, $b ): bool {
	if ( is_string( $a ) && ! ctype_digit( $a ) ) {
		$a = vinea_url_a_id( $a ) ? vinea_url_a_id( $a ) : $a;
	}
	if ( is_string( $b ) && ! ctype_digit( $b ) ) {
		$b = vinea_url_a_id( $b ) ? vinea_url_a_id( $b ) : $b;
	}
	return (string) $a === (string) $b;
}

/**
 * Imagen lista para imprimir. $id_o_url: ID de adjunto o URL.
 * $attrs['prioridad'] = true marca la imagen como LCP.
 */
function vinea_img( $id_o_url, string $tam = 'large', array $attrs = array() ): string {
	$prioridad = ! empty( $attrs['prioridad'] );
	unset( $attrs['prioridad'] );

	if ( $prioridad ) {
		$attrs['loading']       = 'eager';
		$attrs['fetchpriority'] = 'high';
	} else {
		$attrs += array( 'loading' => 'lazy' );
	}
	$attrs += array( 'decoding' => 'async' );

	if ( is_string( $id_o_url ) ) {
		$id_o_url = trim( $id_o_url );
		if ( '' === $id_o_url ) {
			return '';
		}
		if ( ctype_digit( $id_o_url ) ) {
			$id_o_url = (int) $id_o_url;
		} else {
			$id = vinea_url_a_id( $id_o_url );
			if ( $id ) {
				$id_o_url = $id;
			}
		}
	}

	if ( is_int( $id_o_url ) ) {
		if ( $id_o_url <= 0 ) {
			return '';
		}
		$attrs += array( 'sizes' => '(min-width: 1024px) 1200px, 100vw' );
		return wp_get_attachment_image( $id_o_url, $tam, false, $attrs );
	}

	// URL externa o no registrada en la biblioteca.
	$alt = (string) ( $attrs['alt'] ?? '' );
	unset( $attrs['alt'] );
	$extra = '';
	foreach ( $attrs as $k => $v ) {
		if ( is_scalar( $v ) && preg_match( '/^[a-z-]+$/', (string) $k ) ) {
			$extra .= sprintf( ' %s="%s"', esc_attr( $k ), esc_attr( (string) $v ) );
		}
	}
	return sprintf( '<img src="%s" alt="%s"%s>', esc_url( (string) $id_o_url ), esc_attr( $alt ), $extra );
}

/** URL de una imagen (ID o URL). */
function vinea_url_imagen( $id_o_url, string $tam = 'medium_large' ): string {
	if ( is_int( $id_o_url ) || ( is_string( $id_o_url ) && ctype_digit( $id_o_url ) ) ) {
		return (string) wp_get_attachment_image_url( (int) $id_o_url, $tam );
	}
	return is_string( $id_o_url ) ? $id_o_url : '';
}

/** Primera imagen de un HTML: ID (si tiene clase wp-image-ID) o URL; '' si no hay. */
function vinea_primera_imagen( string $html ) {
	if ( ! preg_match_all( '/<img\b[^>]*>/i', $html, $m ) ) {
		return '';
	}
	foreach ( $m[0] as $tag ) {
		if ( preg_match( '/\bsrc\s*=\s*(["\'])(.*?)\1/is', $tag, $s ) ) {
			$src = html_entity_decode( $s[2] );
		} else {
			continue;
		}
		if ( false !== strpos( $src, 's.w.org/images/core/emoji' ) || preg_match( '~/[^/]*logo[^/]*$~i', $src ) ) {
			continue; // Emojis incrustados como imagen y logotipos: no sirven de imagen principal.
		}
		if ( preg_match( '/\bclass\s*=\s*(["\'])[^"\']*\bwp-image-(\d+)\b/i', $tag, $c ) ) {
			return (int) $c[2];
		}
		return $src;
	}
	return '';
}

/** Imagen de un post: destacada o primera del contenido. */
function vinea_imagen_post( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	$id = get_post_thumbnail_id( $post );
	if ( $id ) {
		return (int) $id;
	}
	return vinea_primera_imagen( $post->post_content );
}

/* ------------------------------------------------------------------
 * Cabecera de página
 * ---------------------------------------------------------------- */

/**
 * Si el contenido empieza por un h1 (opcionalmente precedido por una etiqueta
 * corta h2/h3/h4/p y/o una figura), lo separa. No modifica ningún texto.
 *
 * @param string     $html            Contenido sin filtrar.
 * @param bool       $con_entradilla  Extraer el primer <p> tras el h1.
 * @param int|string $mostrada        Si se indica, solo se quita la figura inicial si es esta imagen.
 * @return array{titulo:string,entradilla:string,etiqueta:string,resto:string,imagen:int|string}
 */
function vinea_separar_cabecera( string $html, bool $con_entradilla = true, $mostrada = '' ): array {
	$res = array(
		'titulo'     => get_the_title(),
		'entradilla' => '',
		'etiqueta'   => '',
		'resto'      => $html,
		'imagen'     => vinea_primera_imagen( $html ),
	);

	if ( ! preg_match( '~^(\s*<section\b[^>]*\bclass="[^"]*\bbloque\b[^"]*"[^>]*>)(.*?)(</section>)~is', $html, $m, PREG_OFFSET_CAPTURE ) ) {
		return $res;
	}
	$inner  = $m[2][0];
	$inicio = $m[2][1];
	$fin    = $m[3][1];

	$imagen = $res['imagen'];
	$quitar_figura = static function ( string $trozo ) use ( &$imagen, $mostrada ): string {
		if ( '' === $imagen ) {
			return $trozo;
		}
		if ( '' !== $mostrada && ! vinea_misma_imagen( $mostrada, $imagen ) ) {
			return $trozo;
		}
		if ( preg_match( '~<figure\b(?:(?!</figure>).)*?<img\b(?:(?!</figure>).)*</figure>\s*~is', $trozo, $f, PREG_OFFSET_CAPTURE ) ) {
			if ( vinea_misma_imagen( vinea_primera_imagen( $f[0][0] ), $imagen ) ) {
				return substr_replace( $trozo, '', $f[0][1], strlen( $f[0][0] ) );
			}
		}
		return $trozo;
	};

	$patron = '~^\s*((?:<figure\b.*?</figure>\s*)*)(?:<(h[2-4]|p)\b[^>]*>((?:(?!</?(?:p|h[1-6]|figure)\b).)*?)</\2>\s*)?((?:<figure\b.*?</figure>\s*)*)<h1\b[^>]*>(.*?)</h1>\s*~is';
	if ( ! preg_match( $patron, $inner, $h ) ) {
		// Sin h1 inicial: solo se evita repetir la imagen que ya se muestra.
		$nuevo = $quitar_figura( $inner );
		if ( $nuevo !== $inner ) {
			$res['resto'] = substr( $html, 0, $inicio ) . $nuevo . substr( $html, $fin );
		}
		return $res;
	}

	$etiqueta = trim( $h[3] );
	if ( '' !== $etiqueta && ( 'p' === strtolower( $h[2] ) && mb_strlen( wp_strip_all_tags( $etiqueta ) ) > 90 ) ) {
		return $res; // Es un párrafo largo, no una etiqueta: no tocar.
	}

	$res['titulo']   = vinea_kses_linea( trim( $h[5] ) );
	$res['etiqueta'] = vinea_kses_linea( $etiqueta );
	$tras            = substr( $inner, strlen( $h[0] ) );

	// Las figuras situadas antes del h1 se muestran en la cabecera.
	if ( '' !== trim( $h[1] . $h[4] ) ) {
		$img_fig = vinea_primera_imagen( $h[1] . $h[4] );
		if ( '' !== $img_fig ) {
			$res['imagen'] = $img_fig;
		}
		$imagen = $res['imagen'];
	}

	if ( $con_entradilla && preg_match( '~^\s*<p(?![^>]*\bclass=)[^>]*>((?:(?!</?(?:p|h[1-6]|figure|ul|ol)\b).)*?)</p>\s*~is', $tras, $p ) && false === stripos( $p[1], '<a ' ) && false === stripos( $p[1], '<br' ) && mb_strlen( wp_strip_all_tags( $p[1] ) ) <= 400 ) {
		$res['entradilla'] = vinea_kses_linea( trim( $p[1] ) );
		$tras              = substr( $tras, strlen( $p[0] ) );
	}

	$tras = $quitar_figura( $tras );

	if ( '' === trim( $tras ) ) {
		$res['resto'] = substr( $html, 0, $m[0][1] ) . ltrim( substr( $html, $fin + strlen( '</section>' ) ) );
	} else {
		$res['resto'] = substr( $html, 0, $inicio ) . $tras . substr( $html, $fin );
	}
	return $res;
}

/**
 * Imprime la cabecera de página.
 *
 * @param array $args titulo (HTML en línea), etiqueta, entradilla, imagen (ID o URL),
 *                    migas (lista de [titulo, url] intermedias), boton (bool|array texto/url).
 */
function vinea_parte_cabecera( array $args = array() ): void {
	$a = wp_parse_args(
		$args,
		array(
			'titulo'     => get_the_title(),
			'etiqueta'   => '',
			'entradilla' => '',
			'imagen'     => '',
			'migas'      => array(),
			'boton'      => false,
		)
	);

	$con_foto = '' !== $a['imagen'] && 0 !== $a['imagen'];
	$img      = $con_foto ? vinea_img( $a['imagen'], 'large', array( 'prioridad' => true ) ) : '';
	$con_foto = '' !== $img;
	?>
<section class="cab<?php echo $con_foto ? '' : ' cab--sin-foto'; ?>">
	<div class="gota-deco cab__gota" aria-hidden="true"></div>
	<div class="contenedor cab__grid">
		<div class="cab__texto">
			<nav class="migas" aria-label="Ruta de navegación"><ol><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Inicio</a></li><?php
			foreach ( (array) $a['migas'] as $miga ) {
				printf( '<li><a href="%s">%s</a></li>', esc_url( $miga[1] ), esc_html( $miga[0] ) );
			}
			?><li><span aria-current="page"><?php echo esc_html( wp_strip_all_tags( $a['titulo'] ) ); ?></span></li></ol></nav>
			<?php if ( '' !== $a['etiqueta'] ) : ?>
				<p class="etiqueta" data-reveal><?php echo vinea_kses_linea( $a['etiqueta'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
			<?php endif; ?>
			<h1 class="titulo-1 cab__titulo" data-split><?php echo vinea_kses_linea( $a['titulo'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h1>
			<?php if ( '' !== $a['entradilla'] ) : ?>
				<p class="entradilla cab__entradilla" data-reveal style="--d:350"><?php echo vinea_kses_linea( $a['entradilla'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
			<?php endif; ?>
			<?php
			if ( $a['boton'] ) {
				$b = wp_parse_args( is_array( $a['boton'] ) ? $a['boton'] : array(), array( 'texto' => 'cuéntanos tu idea', 'url' => '/contacto/' ) );
				?>
			<div class="acciones" data-reveal style="--d:500"><a class="boton" href="<?php echo esc_url( $b['url'] ); ?>"><?php echo esc_html( $b['texto'] ); ?> <span class="flecha" aria-hidden="true">→</span></a></div>
			<?php } ?>
		</div>
		<?php if ( $con_foto ) : ?>
			<div class="cab__foto"><?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		<?php endif; ?>
	</div>
</section>
	<?php
}

/* ------------------------------------------------------------------
 * Tarjetas y bandas
 * ---------------------------------------------------------------- */

function vinea_resumen( $post, int $palabras ): string {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	$texto = $post->post_excerpt ? $post->post_excerpt : strip_shortcodes( $post->post_content );
	$texto = wp_strip_all_tags( $texto );
	$texto = preg_replace( '/\s*(\[…\]|\[&hellip;\])\s*$/u', '', $texto );
	return wp_trim_words( trim( preg_replace( '/\s+/u', ' ', $texto ) ), $palabras, '…' );
}

function vinea_tarjeta_proyecto( $post, bool $grande = false ): void {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	$cat  = vinea_categoria_principal( $post );
	$meta = ( '' !== $cat ? $cat . ' · ' : '' ) . vinea_mes_anio( $post );
	$img  = vinea_img( vinea_imagen_post( $post ), 'medium_large', array( 'sizes' => '(min-width: 1024px) 33vw, (min-width: 600px) 50vw, 100vw' ) );
	?>
<a class="tarjeta-p<?php echo $grande ? ' tarjeta-p--grande' : ''; ?>" href="<?php echo esc_url( get_permalink( $post ) ); ?>" data-tilt data-cursor="Ver">
	<div class="tarjeta-p__foto"><?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
	<div class="tarjeta-p__texto">
		<p class="tarjeta-p__meta"><?php echo esc_html( $meta ); ?></p>
		<h3 class="tarjeta-p__titulo"><?php echo esc_html( get_the_title( $post ) ); ?></h3>
		<p class="tarjeta-p__resumen"><?php echo esc_html( vinea_resumen( $post, 26 ) ); ?></p>
	</div>
</a>
	<?php
}

function vinea_banda_cta( string $titulo, string $texto = '', string $url = '/contacto/', string $boton = 'cuéntanos tu idea' ): void {
	?>
<section class="banda">
	<div class="contenedor banda__in">
		<div>
			<h2 class="titulo-2" data-split><?php echo esc_html( $titulo ); ?></h2>
			<?php if ( '' !== $texto ) : ?>
				<p class="banda__texto" data-reveal><?php echo esc_html( $texto ); ?></p>
			<?php endif; ?>
		</div>
		<a class="boton banda__boton" href="<?php echo esc_url( $url ); ?>" data-reveal><?php echo esc_html( $boton ); ?> <span class="flecha" aria-hidden="true">→</span></a>
	</div>
	<div class="gota-deco banda__gota" aria-hidden="true"></div>
</section>
	<?php
}

/* ------------------------------------------------------------------
 * Proyectos: relacionados y listado
 * ---------------------------------------------------------------- */

/** @return WP_Post[] */
function vinea_proyectos_relacionados( $post, int $n = 2 ): array {
	$post   = get_post( $post );
	$result = array();
	if ( ! $post ) {
		return $result;
	}
	$cats = wp_get_post_categories( $post->ID );
	if ( $cats ) {
		$result = get_posts(
			array(
				'post_type'      => 'post',
				'posts_per_page' => $n,
				'post__not_in'   => array( $post->ID ),
				'category__in'   => $cats,
				'no_found_rows'  => true,
			)
		);
	}
	if ( count( $result ) < $n ) {
		$excluir = array_merge( array( $post->ID ), wp_list_pluck( $result, 'ID' ) );
		$result  = array_merge(
			$result,
			get_posts(
				array(
					'post_type'      => 'post',
					'posts_per_page' => $n - count( $result ),
					'post__not_in'   => $excluir,
					'no_found_rows'  => true,
				)
			)
		);
	}
	return $result;
}

/** URL de la página del blog (/blog/). */
function vinea_url_blog(): string {
	$id = (int) get_option( 'page_for_posts' );
	return $id ? get_permalink( $id ) : home_url( '/blog/' );
}

/** Listado de proyectos (home.php, archive.php, category.php, index.php). */
function vinea_listado_proyectos(): void {
	$id_blog = (int) get_option( 'page_for_posts' );
	$titulo  = $id_blog ? get_the_title( $id_blog ) : 'Proyectos';

	vinea_parte_cabecera(
		array(
			'titulo'   => $titulo,
			'etiqueta' => 'Proyectos',
		)
	);

	$defecto = (int) get_option( 'default_category' );
	$cats    = array_filter(
		get_categories( array( 'hide_empty' => true ) ),
		static fn( $c ) => (int) $c->term_id !== $defecto && 'uncategorized' !== $c->slug
	);
	$actual  = is_category() ? get_queried_object_id() : 0;
	?>
<div class="seccion proyectos">
	<div class="contenedor">
		<?php if ( $cats ) : ?>
			<nav class="filtros" aria-label="Categorías">
				<a href="<?php echo esc_url( vinea_url_blog() ); ?>"<?php echo $actual ? '' : ' aria-current="page"'; ?>>Todos</a>
				<?php foreach ( $cats as $c ) : ?>
					<a href="<?php echo esc_url( get_category_link( $c ) ); ?>"<?php echo (int) $c->term_id === $actual ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $c->name ); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>

		<?php if ( have_posts() ) : ?>
			<ul class="rejilla">
				<?php
				while ( have_posts() ) {
					the_post();
					echo '<li data-reveal>';
					vinea_tarjeta_proyecto( get_post() );
					echo '</li>';
				}
				?>
			</ul>
			<?php
			the_posts_pagination(
				array(
					'mid_size'           => 1,
					'prev_text'          => '←',
					'next_text'          => '→',
					'screen_reader_text' => 'Navegación de proyectos',
				)
			);
			?>
		<?php else : ?>
			<p>No hay proyectos todavía.</p>
		<?php endif; ?>
	</div>
</div>
	<?php
}

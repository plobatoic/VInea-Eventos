<?php
/**
 * Portada de Vinea: el diseño elegido (el del recorrido en vídeo: gota 3D, portal al Liceu,
 * servicios en horizontal, cifras, proyectos, accesibilidad, momentos y agenda) con TODOS los
 * textos de la Home original intercalados en su mismo orden: lema, servicios, conciencia social,
 * la esencia de Vinea, empresas que confían (logos y reseñas de Google) y el cierre.
 * Los textos originales se editan en Páginas → Home («Contenido de la portada»).
 */
get_header();

$p = function_exists( 'vinea_portada' ) ? vinea_portada() : array();
if ( empty( $p ) ) {
	$json = get_template_directory() . '/importar/portada.json';
	$p    = file_exists( $json ) ? json_decode( file_get_contents( $json ), true ) : array(); // phpcs:ignore
}
$v = function ( $clave, $defecto = '' ) use ( $p ) {
	return isset( $p[ $clave ] ) && '' !== $p[ $clave ] ? $p[ $clave ] : $defecto;
};
$img = function ( $url, $tam = 'large', $attrs = array() ) {
	if ( $url ) {
		echo function_exists( 'vinea_img' ) ? vinea_img( $url, $tam, $attrs ) : sprintf( '<img src="%s" alt="" loading="lazy">', esc_url( $url ) ); // phpcs:ignore
	}
};
$url_de = function ( $item ) {
	return is_array( $item ) ? ( $item['url'] ?? '' ) : $item;
};
$servicios = (array) $v( 'servicios', array() );
$opiniones = (array) $v( 'opiniones', array() );
$bloques   = array();

// ---------- LEMA ----------
ob_start(); ?>
<section class="og-lema" aria-label="Vinea">
	<div class="contenedor--estrecho">
		<svg class="og-rama" viewBox="0 0 200 60" aria-hidden="true"><path pathLength="1" d="M4 40 C 50 10, 90 56, 130 28 S 180 14, 196 22"/><path pathLength="1" d="M70 34 q 6 -18 22 -20"/><path pathLength="1" d="M128 30 q 10 12 26 10"/><circle class="og-rama__fruto" style="--k:0" cx="94" cy="13" r="6"/><circle class="og-rama__fruto" style="--k:1" cx="156" cy="40" r="5"/><circle class="og-rama__fruto" style="--k:2" cx="196" cy="22" r="7"/></svg>
		<p class="og-lema__texto" data-relleno><?php echo esc_html( $v( 'lema' ) ); ?></p>
	</div>
</section>
<?php $bloques['lema'] = ob_get_clean();

// ---------- SERVICIOS (Home original) ----------
ob_start(); ?>
<section class="og-serv" aria-labelledby="og-serv-titulo">
	<div class="contenedor">
		<h2 id="og-serv-titulo" class="og-titulo" data-split><?php echo esc_html( $v( 'intro' ) ); ?></h2>
		<ul class="og-serv__lista">
			<?php foreach ( $servicios as $i => $s ) : ?>
				<?php $externo = str_starts_with( (string) ( $s['url'] ?? '' ), 'http' ); ?>
				<li class="og-serv__item" data-reveal style="--d:<?php echo (int) ( $i * 100 ); ?>">
					<a href="<?php echo esc_url( $s['url'] ?? '#' ); ?>"<?php echo $externo ? ' target="_blank" rel="noopener"' : ''; ?>>
						<div class="og-serv__cab"><div class="og-serv__foto"><?php $img( $s['imagen'] ?? '', 'medium_large', array( 'sizes' => '(max-width: 560px) 80vw, (max-width: 1000px) 44vw, 300px', 'alt' => '' ) ); ?></div><span class="og-serv__anillo" aria-hidden="true"></span></div>
						<p class="og-serv__num"><span><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span><span class="og-serv__flecha" aria-hidden="true">→</span></p>
						<h3 class="og-serv__titulo"><?php echo esc_html( $s['titulo'] ?? '' ); ?></h3>
						<p class="og-serv__texto"><?php echo esc_html( $s['texto'] ?? '' ); ?></p>
						<?php if ( $externo ) : ?><span class="sr-only"> (se abre en otra pestaña)</span><?php endif; ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
<?php $bloques['servicios'] = ob_get_clean();

// ---------- CONCIENCIA SOCIAL Y MEDIOAMBIENTAL ----------
ob_start(); ?>
<section class="og-conc" aria-labelledby="og-conc-titulo">
	<div class="contenedor og-conc__grid">
		<div>
			<h2 id="og-conc-titulo" class="og-titulo" data-split><?php echo esc_html( $v( 'conciencia_titulo' ) ); ?></h2>
			<p class="og-conc__p" data-reveal><?php echo esc_html( $v( 'conciencia_texto' ) ); ?></p>
		</div>
		<figure class="og-conc__obra" data-reveal="foto">
			<div class="og-conc__foto"><?php $img( $v( 'conciencia_imagen' ), 'large', array( 'sizes' => '(max-width: 900px) 90vw, 46vw', 'alt' => '' ) ); ?></div>
			<span class="og-conc__hoja" aria-hidden="true"></span>
			<svg class="og-conc__sello" viewBox="0 0 200 200" aria-hidden="true"><defs><path id="og-sello" d="M100 100m-80 0a80 80 0 1 1 160 0a80 80 0 1 1-160 0"/></defs><text><textPath href="#og-sello">Accesible · Sostenible · Accesible · Sostenible ·</textPath></text><path d="M86 82h18a18 18 0 1 1-18 18V84q0-2 0-2z" fill="#ff5611"/></svg>
			<figcaption class="og-conc__pie"><?php echo esc_html( $v( 'conciencia_proyecto' ) ); ?></figcaption>
		</figure>
	</div>
</section>
<?php $bloques['conciencia'] = ob_get_clean();

// ---------- LA ESENCIA DE VINEA ----------
ob_start(); ?>
<!-- ============ 5. LA ESENCIA DE VINEA: composición editorial ============
     Foto de Lara y Mirian (de su web) a sangre hasta el borde de la pantalla, con la curva de la gota
     de Vinea, y la tarjeta de texto superpuesta. Entrada de una vez al aparecer: la foto se descubre
     como un telón y hace un acercamiento lento (solo transform). -->
<?php $esencia_dir = get_template_directory_uri() . '/assets/esencia/'; ?>
<section class="ese" data-ese aria-labelledby="esencia-titulo">
	<figure class="ese__foto">
		<div class="ese__telon">
			<img class="ese__a" src="<?php echo esc_url( $esencia_dir . 'premios-ondas-1600.webp' ); ?>" srcset="<?php echo esc_url( $esencia_dir . 'premios-ondas-1000.webp' ); ?> 1000w, <?php echo esc_url( $esencia_dir . 'premios-ondas-1600.webp' ); ?> 1600w" sizes="(max-width: 900px) 100vw, 62vw" width="1600" height="1066" alt="Lara y Mirian en los Premios Ondas 2024" loading="lazy" decoding="async">
			<!-- Una gota cae, salpica y abre una ventana con la otra foto; luego se cierra y vuelve a subir -->
			<span class="ese__sombra-imp" aria-hidden="true"></span>
			<div class="ese__ventana" aria-hidden="true">
				<img src="<?php echo esc_url( $esencia_dir . 'lara-y-mirian-1400.webp' ); ?>" srcset="<?php echo esc_url( $esencia_dir . 'lara-y-mirian-820.webp' ); ?> 820w, <?php echo esc_url( $esencia_dir . 'lara-y-mirian-1400.webp' ); ?> 1400w" sizes="(max-width: 900px) 100vw, 62vw" width="1400" height="1867" alt="" loading="lazy" decoding="async">
			</div>
			<span class="ese__borde" aria-hidden="true"></span>
			<?php foreach ( array( array( -70, 70 ), array( -25, 95 ), array( 20, 85 ), array( 65, 75 ), array( 110, 60 ), array( -115, 55 ) ) as $g ) : ?><span class="ese__gotita" style="--a:<?php echo (int) $g[0]; ?>deg;--d:<?php echo (int) $g[1]; ?>px" aria-hidden="true"></span><?php endforeach; ?>
			<span class="ese__onda" aria-hidden="true"></span>
			<span class="ese__onda ese__onda--2" aria-hidden="true"></span>
			<span class="ese__cae" aria-hidden="true"></span>
		</div>
	</figure>
	<div class="contenedor ese__capa">
		<div class="ese__tarjeta">
			<span class="ese__gota" aria-hidden="true"></span>
			<h2 id="esencia-titulo" class="ese__titulo"><?php echo esc_html( $v( 'esencia_titulo' ) ); ?></h2>
			<p class="ese__p"><?php echo esc_html( $v( 'esencia_texto' ) ); ?></p>
			<a class="boton boton--naranja" href="<?php echo esc_url( $v( 'esencia_boton_url', '/descubre-esencia-vinea-eventos/' ) ); ?>"><?php echo esc_html( $v( 'esencia_boton_texto', 'nuestra esencia' ) ); ?> <span class="flecha" aria-hidden="true">→</span></a>
		</div>
	</div>
</section>
<?php $bloques['esencia'] = ob_get_clean();

// ---------- EMPRESAS QUE CONFÍAN EN VINEA ----------
ob_start(); ?>
<!-- ============ 6. EMPRESAS QUE CONFÍAN EN VINEA: marcas (como en la web original) y reseñas de Google ============ -->
<?php
$marcas_dir = get_template_directory() . '/assets/marcas/';
$marcas     = file_exists( $marcas_dir . 'marcas.json' ) ? json_decode( file_get_contents( $marcas_dir . 'marcas.json' ), true ) : array(); // phpcs:ignore
$marca_img  = function ( $m ) {
	return sprintf( '<img src="%s" width="%d" height="%d" alt="%s" loading="lazy" decoding="async">', esc_url( get_template_directory_uri() . '/assets/marcas/' . $m['f'] ), (int) $m['w'], (int) $m['h'], esc_attr( $m['nombre'] ) );
};
$g_logo   = '<svg class="g-logo" viewBox="0 0 48 48" aria-hidden="true" focusable="false"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.1 3.6l6.8-6.8C35.8 2.4 30.3 0 24 0 14.6 0 6.6 5.4 2.6 13.3l7.9 6.1C12.4 13.7 17.7 9.5 24 9.5z"/><path fill="#4285F4" d="M46.1 24.6c0-1.6-.1-3.1-.4-4.6H24v9h12.4c-.5 2.9-2.2 5.3-4.6 6.9l7.5 5.8c4.4-4 6.8-10 6.8-17.1z"/><path fill="#FBBC05" d="M10.5 28.6c-.5-1.4-.8-3-.8-4.6s.3-3.2.8-4.6l-7.9-6.1C1 16.5 0 20.1 0 24s1 7.5 2.6 10.7l7.9-6.1z"/><path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.5-5.8c-2.1 1.4-4.8 2.3-8.4 2.3-6.3 0-11.6-4.2-13.5-10l-7.9 6.1C6.6 42.6 14.6 48 24 48z"/></svg>';
$estrellas = '<span class="estrellas" role="img" aria-label="5 de 5 estrellas">' . str_repeat( '<svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10 1.5l2.6 5.4 5.9.8-4.3 4.1 1 5.8L10 14.8l-5.2 2.8 1-5.8L1.5 7.7l5.9-.8z"/></svg>', 5 ) . '</span>';
$colores   = array( '#1a73e8', '#d93025', '#188038', '#e37400', '#8430ce', '#00796b', '#c5221f', '#3949ab', '#ad1457', '#5f6368' );
?>
<section class="inicio-opiniones" aria-labelledby="opiniones-titulo">
	<div class="contenedor">
		<h2 id="opiniones-titulo" class="og-titulo inicio-opiniones__titulo" data-split><?php echo esc_html( $v( 'opiniones_titulo', 'Empresas que confían en Vinea' ) ); ?></h2>

		<?php if ( $marcas ) : ?>
		<?php $mitad = (int) ceil( count( $marcas ) / 2 ); ?>
		<ul class="marcas" data-reveal="fade" aria-label="Algunas empresas que han confiado en Vinea">
			<?php for ( $t = 0; $t < $mitad; $t++ ) : ?>
				<li class="marcas__baldosa" style="--t:<?php echo (int) $t; ?>">
					<div class="marcas__giro">
						<span class="marcas__cara"><?php echo $marca_img( $marcas[ $t ] ); // phpcs:ignore ?></span>
						<?php if ( isset( $marcas[ $t + $mitad ] ) ) : ?><span class="marcas__cara marcas__cara--atras"><?php echo $marca_img( $marcas[ $t + $mitad ] ); // phpcs:ignore ?></span><?php endif; ?>
					</div>
				</li>
			<?php endfor; ?>
		</ul>
		<?php endif; ?>

		<?php if ( $opiniones ) : ?>
		<div class="resenas" data-resenas>
			<div class="resenas__cabeza" data-reveal>
				<?php echo $g_logo; // phpcs:ignore ?>
				<div><strong>Reseñas en Google</strong><?php echo $estrellas; // phpcs:ignore ?></div>
			</div>
			<ul class="resenas__lista">
				<?php foreach ( $opiniones as $i => $o ) : ?>
					<li class="resena" data-reveal style="--d:<?php echo (int) ( ( $i % 3 ) * 80 ); ?>">
						<article>
							<header class="resena__cabeza">
								<span class="resena__avatar" style="--c:<?php echo esc_attr( $colores[ $i % count( $colores ) ] ); ?>" aria-hidden="true"><?php echo esc_html( mb_strtoupper( mb_substr( $o['autor'] ?? '', 0, 1 ) ) ); ?></span>
								<span class="resena__quien"><strong><?php echo esc_html( $o['autor'] ?? '' ); ?></strong><?php if ( ! empty( $o['fecha'] ) ) : ?><time datetime="<?php echo esc_attr( $o['fecha'] ); ?>"><?php echo esc_html( date_i18n( 'j M Y', strtotime( $o['fecha'] ) ) ); ?></time><?php endif; ?></span>
								<?php echo str_replace( 'class="g-logo"', 'class="g-logo resena__g"', $g_logo ); // phpcs:ignore ?>
							</header>
							<?php echo $estrellas; // phpcs:ignore ?>
							<p class="resena__texto"><?php echo nl2br( esc_html( str_replace( '\n', "\n", $o['texto'] ?? '' ) ) ); ?></p>
							<button class="resena__mas" type="button" aria-expanded="false" hidden>Leer más</button>
						</article>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( count( $opiniones ) > 3 ) : ?><p class="resenas__pie"><button class="boton resenas__ver" type="button" data-resenas-ver>Ver más reseñas</button></p><?php endif; ?>
		</div>
		<?php endif; ?>
	</div>
</section>
<?php $bloques['opiniones'] = ob_get_clean();

// ---------- CIERRE ----------
ob_start(); ?>
<section class="og-cierre" aria-label="Nuestra forma de trabajar">
	<span class="mini mini--a" aria-hidden="true"></span>
	<span class="mini mini--b" aria-hidden="true"></span>
	<span class="mini mini--c" aria-hidden="true"></span>
	<div class="contenedor--estrecho">
		<h2 class="og-cierre__texto" data-relleno><?php echo esc_html( $v( 'cierre' ) ); ?></h2>
	</div>
</section>
<?php $bloques['cierre'] = ob_get_clean();

// Diseño de la portada (vídeo) + bloques originales en su orden
$portada = get_template_directory() . '/partes/portada-video.html';
$html    = file_exists( $portada ) ? (string) file_get_contents( $portada ) : ''; // phpcs:ignore
// Entradas (eventos reales, los mismos que en la página «Entradas»)
$cid     = ' data-astro-cid-j7pv25f6';
$meses   = array( 'enero' => 'ene', 'febrero' => 'feb', 'marzo' => 'mar', 'abril' => 'abr', 'mayo' => 'may', 'junio' => 'jun', 'julio' => 'jul', 'agosto' => 'ago', 'septiembre' => 'sep', 'octubre' => 'oct', 'noviembre' => 'nov', 'diciembre' => 'dic' );
$eventos = get_posts( array( 'post_type' => 'etn', 'posts_per_page' => 3, 'meta_key' => 'vinea_en_listado', 'meta_value' => '1', 'orderby' => 'menu_order', 'order' => 'ASC' ) ); // phpcs:ignore
$lista   = '';
foreach ( $eventos as $ev ) {
	$f     = (string) get_post_meta( $ev->ID, 'vinea_fecha_txt', true );
	$lugar = (string) get_post_meta( $ev->ID, 'vinea_lugar', true );
	$dia   = '';
	$mes   = '';
	if ( preg_match( '/^(\p{L}+)\s+(\d{1,2}),\s*(\d{4})$/u', trim( $f ), $mm ) ) {
		$dia = $mm[2];
		$mes = $meses[ mb_strtolower( $mm[1] ) ] ?? mb_substr( $mm[1], 0, 3 );
	}
	$lista .= '<li class="proximo" data-reveal' . $cid . '><a href="' . esc_url( get_permalink( $ev ) ) . '"' . $cid . '>'
		. '<span class="proximo__fecha"' . $cid . '><strong' . $cid . '>' . esc_html( $dia ) . '</strong><span' . $cid . '>' . esc_html( $mes ) . '</span></span>'
		. '<span class="proximo__info"' . $cid . '><span class="titulo-3"' . $cid . '>' . esc_html( get_the_title( $ev ) ) . '</span>'
		. ( '' !== $lugar ? '<span class="proximo__lugar"' . $cid . '>' . esc_html( $lugar ) . '</span>' : '' ) . '</span>'
		. '<span class="proximo__estado"' . $cid . '>Attend <span aria-hidden="true">→</span></span></a></li>';
}
$html = str_replace( '{{EVENTOS}}', $lista, $html );
$html    = str_replace( array( '{{T}}', '{{H}}' ), array( esc_url( get_template_directory_uri() ), untrailingslashit( esc_url( home_url() ) ) ), $html );
$poner   = static function ( string $html, string $antes_de, string $bloque ): string {
	$p = strpos( $html, $antes_de );
	return false === $p ? $html . $bloque : substr( $html, 0, $p ) . $bloque . substr( $html, $p );
};
$html = $poner( $html, '<section class="seccion intro"', $bloques['lema'] );
$html = $poner( $html, '<section class="servicios seccion--papel"', $bloques['servicios'] );
$html = $poner( $html, '<section class="accesible"', $bloques['conciencia'] );
$html = $poner( $html, '<section class="seccion momentos"', $bloques['esencia'] );
$html = $poner( $html, '<section class="seccion proximos"', $bloques['opiniones'] );
$html .= $bloques['cierre'];
echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- HTML propio del tema y textos escapados arriba.

get_footer();

<?php
/**
 * Portada de Vinea. Todos los textos salen de la Home original (campos de la
 * página de portada, editables en el escritorio de WordPress). El diseño y las
 * animaciones los pone el tema.
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
	if ( ! $url ) {
		return;
	}
	if ( function_exists( 'vinea_img' ) ) {
		echo vinea_img( $url, $tam, $attrs ); // phpcs:ignore -- HTML de imagen ya escapado.
	} else {
		printf( '<img src="%s" alt="%s" loading="lazy">', esc_url( $url ), esc_attr( $attrs['alt'] ?? '' ) );
	}
};
$url_de = function ( $item ) {
	return is_array( $item ) ? ( $item['url'] ?? '' ) : $item;
};

// "Creamos experiencias emocionales…": la palabra «emocionales» en cursiva.
$claim = esc_html( $v( 'claim', 'Creamos experiencias emocionales para que sean recordadas' ) );
$claim = preg_replace( '/(emocionales)/iu', '<em>$1</em>', $claim );

$servicios_hijos = array();
$pag_servicios   = get_page_by_path( 'servicios' );
if ( $pag_servicios ) {
	$servicios_hijos = get_pages( array( 'child_of' => $pag_servicios->ID, 'parent' => $pag_servicios->ID, 'sort_column' => 'menu_order' ) );
}
$montaje   = (array) $v( 'montaje', array() );
$momentos  = (array) $v( 'momentos', array() );
$servicios = (array) $v( 'servicios', array() );
$opiniones = (array) $v( 'opiniones', array() );
$tema      = get_template_directory_uri();
?>

<!-- ============ ENTRADA ============ -->
<section class="hero" data-hero aria-labelledby="hero-titulo">
	<div class="hero__aura" aria-hidden="true" data-aura></div>
	<canvas class="hero__particulas" aria-hidden="true" data-particulas></canvas>
	<canvas class="hero__gota" aria-hidden="true" data-gota></canvas>
	<div class="hero__gota-plana" aria-hidden="true" data-gota-plana></div>

	<div class="hero__escena" data-hero-centro>
		<div class="hero__logo" data-hero-logo>
			<?php echo file_get_contents( get_template_directory() . '/partes/logo-animado.svg' ); // phpcs:ignore -- SVG propio del tema. ?>
		</div>
	</div>

	<div class="hero__pie contenedor">
		<div class="hero__textos" data-hero-fade>
			<h1 id="hero-titulo" class="hero__h1"><?php echo esc_html( $v( 'h1', 'Agencia de eventos en Barcelona para empresas e instituciones' ) ); ?></h1>
			<p class="hero__claim"><?php echo $claim; // phpcs:ignore -- escapado arriba. ?></p>
		</div>
		<div class="hero__acciones" data-hero-fade>
			<a class="boton boton--naranja" href="<?php echo esc_url( $v( 'boton_url', '/contacto/' ) ); ?>"><?php echo esc_html( $v( 'boton_texto', 'cuéntanos tu idea' ) ); ?> <span class="flecha" aria-hidden="true">→</span></a>
		</div>
	</div>

	<div class="hero__bajar" aria-hidden="true" data-hero-bajar><span></span></div>

	<div class="hero__portal" data-portal>
		<?php foreach ( $montaje as $i => $foto ) : ?>
			<div class="hero__portal-foto" data-portal-foto style="z-index:<?php echo (int) $i; ?>">
				<?php $img( $url_de( $foto ), 'full', array( 'sizes' => '100vw', 'alt' => '' ) ); ?>
			</div>
		<?php endforeach; ?>
		<div class="hero__portal-texto contenedor sobre-oscuro" data-portal-texto>
			<p class="titulo-1" data-palabras><?php echo esc_html( $v( 'lema' ) ); ?></p>
		</div>
	</div>
</section>

<!-- ============ FRANJA: nombres de los servicios ============ -->
<?php if ( $servicios_hijos ) : ?>
<section class="franja" aria-hidden="true" data-skew>
	<p class="franja__fila" data-franja="-1">
		<?php for ( $r = 0; $r < 2; $r++ ) : foreach ( $servicios_hijos as $s ) : ?><span><?php echo esc_html( get_the_title( $s ) ); ?><i class="franja__gota" aria-hidden="true"></i></span><?php endforeach; endfor; ?>
	</p>
</section>
<?php endif; ?>

<!-- ============ SERVICIOS ============ -->
<section class="servicios seccion--papel" id="servicios" data-servicios aria-labelledby="servicios-titulo">
	<div class="servicios__cabeza contenedor">
		<h2 id="servicios-titulo" class="titulo-2 relleno" data-relleno><?php echo esc_html( $v( 'intro' ) ); ?></h2>
	</div>
	<div class="servicios__ventana">
		<ul class="servicios__pista" data-pista>
			<?php foreach ( $servicios as $i => $s ) : ?>
				<li class="servicios__item" data-servicio>
					<a class="tarjeta-s" href="<?php echo esc_url( $s['url'] ?? '#' ); ?>" data-tilt data-cursor="Ver" <?php echo str_starts_with( (string) ( $s['url'] ?? '' ), 'http' ) ? 'target="_blank" rel="noopener"' : ''; ?>>
						<div class="tarjeta-s__foto"><?php $img( $s['imagen'] ?? '', 'medium_large', array( 'sizes' => '(max-width: 900px) 80vw, 400px', 'alt' => '' ) ); ?></div>
						<div class="tarjeta-s__texto">
							<p class="tarjeta-s__meta"><span><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span></p>
							<h3 class="tarjeta-s__titulo"><?php echo esc_html( $s['titulo'] ?? '' ); ?></h3>
							<p class="tarjeta-s__resumen"><?php echo esc_html( $s['texto'] ?? '' ); ?></p>
							<span class="tarjeta-s__vermas">ver más <span aria-hidden="true">→</span></span>
						</div>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

<!-- ============ CONCIENCIA SOCIAL Y MEDIOAMBIENTAL (con las 4 fotos del carrusel original) ============ -->
<?php $carrusel = array_merge( array( $v( 'conciencia_imagen' ) ), array_map( $url_de, array_slice( $momentos, 0, 3 ) ) ); ?>
<section class="accesible" aria-labelledby="conciencia-titulo">
	<div class="contenedor accesible__cabeza">
		<h2 id="conciencia-titulo" class="titulo-1" data-split><?php echo esc_html( $v( 'conciencia_titulo' ) ); ?></h2>
		<p class="accesible__p" data-reveal><?php echo esc_html( $v( 'conciencia_texto' ) ); ?></p>
	</div>
	<figure class="carrusel-proyecto">
		<div class="momentos__collage">
			<?php foreach ( array_filter( $carrusel ) as $i => $foto ) : ?>
				<div class="momento momento--<?php echo (int) $i; ?>" data-parallax="<?php echo esc_attr( array( 0.12, -0.18, 0.24, -0.1 )[ $i ] ?? 0 ); ?>">
					<?php $img( $foto, 'medium_large', array( 'sizes' => '(max-width: 900px) 70vw, 34vw', 'alt' => '' ) ); ?>
				</div>
			<?php endforeach; ?>
		</div>
		<figcaption class="accesible__pie contenedor"><?php echo esc_html( $v( 'conciencia_proyecto' ) ); ?></figcaption>
	</figure>
</section>

<!-- ============ LA ESENCIA DE VINEA ============ -->
<section class="seccion esencia-portada" aria-labelledby="esencia-titulo">
	<div class="contenedor esencia-portada__grid">
		<div class="esencia-portada__foto con-gota" data-reveal="foto"><?php $img( $v( 'esencia_imagen' ), 'large', array( 'sizes' => '(max-width: 900px) 80vw, 40vw', 'alt' => '' ) ); ?></div>
		<div>
			<h2 id="esencia-titulo" class="titulo-1" data-split><?php echo esc_html( $v( 'esencia_titulo' ) ); ?></h2>
			<p class="entradilla" data-reveal><?php echo esc_html( $v( 'esencia_texto' ) ); ?></p>
			<a class="boton" href="<?php echo esc_url( $v( 'esencia_boton_url', '/descubre-esencia-vinea-eventos/' ) ); ?>" data-reveal><?php echo esc_html( $v( 'esencia_boton_texto', 'nuestra esencia' ) ); ?> <span class="flecha" aria-hidden="true">→</span></a>
		</div>
	</div>
</section>

<!-- ============ EMPRESAS QUE CONFÍAN EN VINEA ============ -->
<?php if ( $opiniones ) : ?>
<section class="seccion seccion--arena confianza" aria-labelledby="opiniones-titulo">
	<div class="contenedor opiniones" data-reveal>
		<div class="opiniones__cabeza">
			<h2 id="opiniones-titulo" class="titulo-2" data-split><?php echo esc_html( $v( 'opiniones_titulo', 'Empresas que confían en Vinea' ) ); ?></h2>
			<div class="opiniones__flechas">
				<button type="button" class="opiniones__flecha" data-op="-1" aria-controls="lista-opiniones"><span aria-hidden="true">←</span><span class="sr-only">Opinión anterior</span></button>
				<button type="button" class="opiniones__flecha" data-op="1" aria-controls="lista-opiniones"><span aria-hidden="true">→</span><span class="sr-only">Opinión siguiente</span></button>
			</div>
		</div>
		<ul class="opiniones__lista" id="lista-opiniones" tabindex="0" aria-label="<?php echo esc_attr( $v( 'opiniones_titulo', 'Empresas que confían en Vinea' ) ); ?>" data-opiniones>
			<?php foreach ( $opiniones as $o ) : ?>
				<li class="opinion">
					<figure>
						<span class="opinion__comillas" aria-hidden="true">“</span>
						<blockquote><p><?php echo nl2br( esc_html( str_replace( '\n', "\n", $o['texto'] ?? '' ) ) ); ?></p></blockquote>
						<figcaption>
							<span class="opinion__inicial" aria-hidden="true"><?php echo esc_html( mb_substr( $o['autor'] ?? '', 0, 1 ) ); ?></span>
							<span><?php echo esc_html( $o['autor'] ?? '' ); ?><?php if ( ! empty( $o['fecha'] ) ) : ?><time class="opinion__fecha" datetime="<?php echo esc_attr( $o['fecha'] ); ?>"><?php echo esc_html( date_i18n( 'j M Y', strtotime( $o['fecha'] ) ) ); ?></time><?php endif; ?></span>
						</figcaption>
					</figure>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
<?php endif; ?>

<!-- ============ CIERRE (último texto de la Home original) ============ -->
<section class="seccion cierre" aria-label="Nuestra forma de trabajar">
	<div class="contenedor--estrecho">
		<h2 class="cierre__texto relleno" data-relleno><?php echo esc_html( $v( 'cierre' ) ); ?></h2>
	</div>
</section>

<?php get_footer(); ?>

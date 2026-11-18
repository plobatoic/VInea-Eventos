<?php
/**
 * Portada de Vinea: textos de la Home original, en su mismo orden, editables en
 * Páginas → Home. Diseño claro y acogedor; animaciones solo con CSS (ligeras).
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
	echo function_exists( 'vinea_img' ) ? vinea_img( $url, $tam, $attrs ) : sprintf( '<img src="%s" alt="" loading="lazy">', esc_url( $url ) ); // phpcs:ignore
};
$url_de = function ( $item ) {
	return is_array( $item ) ? ( $item['url'] ?? '' ) : $item;
};

$claim     = preg_replace( '/(emocionales)/iu', '<em>$1</em>', esc_html( $v( 'claim', 'Creamos experiencias emocionales para que sean recordadas' ) ) );
$montaje   = array_values( array_filter( array_map( $url_de, (array) $v( 'montaje', array() ) ) ) );
$momentos  = array_values( array_filter( array_map( $url_de, (array) $v( 'momentos', array() ) ) ) );
$servicios = (array) $v( 'servicios', array() );
$opiniones = (array) $v( 'opiniones', array() );
$carrusel  = array_values( array_filter( array_merge( array( $v( 'conciencia_imagen' ) ), array_slice( $momentos, 0, 3 ) ) ) );
?>

<!-- ============ 1. ENTRADA ============ -->
<section class="inicio-hero" aria-labelledby="hero-titulo">
	<div class="contenedor inicio-hero__grid">
		<div class="inicio-hero__texto">
			<h1 id="hero-titulo" class="inicio-hero__h1"><?php echo esc_html( $v( 'h1', 'Agencia de eventos en Barcelona para empresas e instituciones' ) ); ?></h1>
			<p class="inicio-hero__claim"><?php echo $claim; // phpcs:ignore -- escapado arriba. ?></p>
			<div class="inicio-hero__acciones">
				<a class="boton boton--naranja" href="<?php echo esc_url( $v( 'boton_url', '/contacto/' ) ); ?>"><?php echo esc_html( $v( 'boton_texto', 'cuéntanos tu idea' ) ); ?> <span class="flecha" aria-hidden="true">→</span></a>
			</div>
		</div>

		<div class="inicio-hero__visual" aria-hidden="true">
			<div class="inicio-hero__gota">
				<?php foreach ( array_slice( $montaje, 0, 3 ) as $i => $foto ) : ?>
					<div class="inicio-hero__foto" style="--n:<?php echo (int) $i; ?>"><?php $img( $foto, 'large', array( 'sizes' => '(max-width: 900px) 90vw, 46vw', 'alt' => '', 'prioridad' => 0 === $i ) ); ?></div>
				<?php endforeach; ?>
			</div>
			<?php if ( isset( $momentos[3] ) ) : ?><div class="inicio-hero__mini inicio-hero__mini--a"><?php $img( $momentos[3], 'medium', array( 'sizes' => '180px', 'alt' => '' ) ); ?></div><?php endif; ?>
			<?php if ( isset( $momentos[4] ) ) : ?><div class="inicio-hero__mini inicio-hero__mini--b"><?php $img( $momentos[4], 'medium', array( 'sizes' => '150px', 'alt' => '' ) ); ?></div><?php endif; ?>
			<span class="inicio-hero__punto"></span>
			<svg class="inicio-hero__sello" viewBox="0 0 200 200"><defs><path id="sello-circulo" d="M100 100m-78 0a78 78 0 1 1 156 0a78 78 0 1 1-156 0"/></defs><text><textPath href="#sello-circulo">Vinea Eventos y Turismo · Vinea Eventos y Turismo ·</textPath></text></svg>
		</div>
	</div>
</section>

<!-- ============ 2. LEMA ============ -->
<section class="inicio-lema" aria-label="Vinea">
	<div class="contenedor--estrecho">
		<p class="inicio-lema__texto" data-relleno><?php echo esc_html( $v( 'lema' ) ); ?></p>
	</div>
</section>

<!-- ============ 3. SERVICIOS ============ -->
<section class="inicio-servicios" id="servicios" aria-labelledby="servicios-titulo">
	<div class="contenedor">
		<h2 id="servicios-titulo" class="inicio-servicios__titulo" data-split><?php echo esc_html( $v( 'intro' ) ); ?></h2>
		<ul class="inicio-servicios__lista">
			<?php foreach ( $servicios as $i => $s ) : ?>
				<?php $externo = str_starts_with( (string) ( $s['url'] ?? '' ), 'http' ); ?>
				<li data-reveal style="--d:<?php echo (int) ( $i * 90 ); ?>">
					<a class="vineta" href="<?php echo esc_url( $s['url'] ?? '#' ); ?>"<?php echo $externo ? ' target="_blank" rel="noopener"' : ''; ?>>
						<div class="vineta__foto"><?php $img( $s['imagen'] ?? '', 'medium_large', array( 'sizes' => '(max-width: 600px) 80vw, (max-width: 1100px) 40vw, 300px', 'alt' => '' ) ); ?></div>
						<span class="vineta__num"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
						<h3 class="vineta__titulo"><?php echo esc_html( $s['titulo'] ?? '' ); ?></h3>
						<p class="vineta__texto"><?php echo esc_html( $s['texto'] ?? '' ); ?></p>
						<span class="vineta__mas">ver más <span aria-hidden="true">→</span><?php echo $externo ? '<span class="sr-only"> (se abre en otra pestaña)</span>' : ''; ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

<!-- ============ 4. CONCIENCIA SOCIAL Y MEDIOAMBIENTAL ============ -->
<section class="inicio-conciencia" aria-labelledby="conciencia-titulo">
	<div class="contenedor inicio-conciencia__grid">
		<div class="inicio-conciencia__texto">
			<h2 id="conciencia-titulo" class="inicio-conciencia__titulo" data-split><?php echo esc_html( $v( 'conciencia_titulo' ) ); ?></h2>
			<p class="inicio-conciencia__p" data-reveal><?php echo esc_html( $v( 'conciencia_texto' ) ); ?></p>
		</div>
		<figure class="inicio-conciencia__fotos">
			<div class="mosaico">
				<?php foreach ( array_slice( $carrusel, 0, 4 ) as $i => $foto ) : ?>
					<div class="mosaico__foto mosaico__foto--<?php echo (int) $i; ?>" data-reveal="foto" style="--d:<?php echo (int) ( $i * 80 ); ?>"><?php $img( $foto, 'medium_large', array( 'sizes' => '(max-width: 900px) 45vw, 25vw', 'alt' => '' ) ); ?></div>
				<?php endforeach; ?>
			</div>
			<figcaption class="inicio-conciencia__pie"><?php echo esc_html( $v( 'conciencia_proyecto' ) ); ?></figcaption>
		</figure>
	</div>
</section>

<!-- ============ 5. LA ESENCIA DE VINEA ============ -->
<section class="inicio-esencia" aria-labelledby="esencia-titulo">
	<div class="contenedor inicio-esencia__grid">
		<div class="inicio-esencia__foto" data-reveal="foto"><?php $img( $v( 'esencia_imagen' ), 'large', array( 'sizes' => '(max-width: 900px) 80vw, 40vw', 'alt' => '' ) ); ?></div>
		<div>
			<h2 id="esencia-titulo" class="inicio-seccion__titulo" data-split><?php echo esc_html( $v( 'esencia_titulo' ) ); ?></h2>
			<p class="inicio-esencia__texto" data-reveal><?php echo esc_html( $v( 'esencia_texto' ) ); ?></p>
			<a class="boton" href="<?php echo esc_url( $v( 'esencia_boton_url', '/descubre-esencia-vinea-eventos/' ) ); ?>" data-reveal><?php echo esc_html( $v( 'esencia_boton_texto', 'nuestra esencia' ) ); ?> <span class="flecha" aria-hidden="true">→</span></a>
		</div>
	</div>
</section>

<!-- ============ 6. EMPRESAS QUE CONFÍAN EN VINEA ============ -->
<?php if ( $opiniones ) : ?>
<section class="inicio-opiniones" aria-labelledby="opiniones-titulo">
	<div class="contenedor">
		<h2 id="opiniones-titulo" class="inicio-seccion__titulo inicio-opiniones__titulo" data-split><?php echo esc_html( $v( 'opiniones_titulo', 'Empresas que confían en Vinea' ) ); ?></h2>
		<ul class="inicio-opiniones__lista">
			<?php foreach ( $opiniones as $i => $o ) : ?>
				<li class="tarjeta-opinion" data-reveal style="--d:<?php echo (int) ( ( $i % 3 ) * 80 ); ?>">
					<figure>
						<blockquote><p><?php echo nl2br( esc_html( str_replace( '\n', "\n", $o['texto'] ?? '' ) ) ); ?></p></blockquote>
						<figcaption>
							<span class="tarjeta-opinion__inicial" aria-hidden="true"><?php echo esc_html( mb_substr( $o['autor'] ?? '', 0, 1 ) ); ?></span>
							<span><strong><?php echo esc_html( $o['autor'] ?? '' ); ?></strong><?php if ( ! empty( $o['fecha'] ) ) : ?><time datetime="<?php echo esc_attr( $o['fecha'] ); ?>"><?php echo esc_html( date_i18n( 'j M Y', strtotime( $o['fecha'] ) ) ); ?></time><?php endif; ?></span>
						</figcaption>
					</figure>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
<?php endif; ?>

<!-- ============ 7. CIERRE ============ -->
<section class="inicio-cierre" aria-label="Nuestra forma de trabajar">
	<div class="contenedor--estrecho">
		<h2 class="inicio-cierre__texto" data-relleno><?php echo esc_html( $v( 'cierre' ) ); ?></h2>
	</div>
</section>

<?php get_footer(); ?>

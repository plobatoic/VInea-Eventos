<?php
/**
 * Portada de Vinea. Textos de la Home original, en su mismo orden (editables en Páginas → Home).
 * Diseño en 3D hecho con CSS (transformaciones que mueve la tarjeta gráfica): anillo de fotos,
 * muro de fotos en perspectiva, abanico de fotos y tarjetas con profundidad.
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

// Galería de fotos de Vinea (todas las fotos de eventos de la web original, optimizadas)
$gal_dir  = get_template_directory() . '/assets/galeria/';
$gal_url  = get_template_directory_uri() . '/assets/galeria/';
$galeria  = file_exists( $gal_dir . 'galeria.json' ) ? json_decode( file_get_contents( $gal_dir . 'galeria.json' ), true ) : array( 'fotos' => array(), 'anillo' => array() ); // phpcs:ignore
$por_nom  = array();
foreach ( $galeria['fotos'] as $f ) {
	$por_nom[ $f['f'] ] = $f;
}
$foto_gal = function ( $nombre, $carga = 'lazy', $clase = '' ) use ( $por_nom, $gal_url ) {
	$f = $por_nom[ $nombre ] ?? null;
	if ( ! $f ) {
		return '';
	}
	return sprintf( '<img src="%s" width="%d" height="%d" alt="" loading="%s" decoding="async"%s>', esc_url( $gal_url . $f['f'] ), (int) $f['w'], (int) $f['h'], esc_attr( $carga ), $clase ? ' class="' . esc_attr( $clase ) . '"' : '' );
};

$claim     = preg_replace( '/(emocionales)/iu', '<em>$1</em>', esc_html( $v( 'claim', 'Creamos experiencias emocionales para que sean recordadas' ) ) );
$momentos  = array_values( array_filter( array_map( $url_de, (array) $v( 'momentos', array() ) ) ) );
$servicios = (array) $v( 'servicios', array() );
$opiniones = (array) $v( 'opiniones', array() );
$abanico   = array_values( array_filter( array_merge( array( $v( 'conciencia_imagen' ) ), array_slice( $momentos, 0, 3 ) ) ) );
$anillo    = $galeria['anillo'];
$resto     = array_values( array_diff( array_keys( $por_nom ), $anillo ) );
?>

<!-- ============ 1. ENTRADA: anillo 3D de fotos ============ -->
<section class="h3d" data-h3d aria-labelledby="hero-titulo">
	<div class="h3d__escena" aria-hidden="true">
		<div class="h3d__anillo" style="--n:<?php echo count( $anillo ); ?>">
			<?php foreach ( $anillo as $i => $nombre ) : ?>
				<figure class="h3d__foto" style="--i:<?php echo (int) $i; ?>"><?php echo $foto_gal( $nombre, $i < 6 ? 'eager' : 'lazy' ); // phpcs:ignore ?></figure>
			<?php endforeach; ?>
		</div>
	</div>
	<div class="h3d__texto contenedor">
		<h1 id="hero-titulo" class="h3d__h1"><?php echo esc_html( $v( 'h1', 'Agencia de eventos en Barcelona para empresas e instituciones' ) ); ?></h1>
		<p class="h3d__claim"><?php echo $claim; // phpcs:ignore -- escapado arriba. ?></p>
		<a class="boton boton--naranja h3d__boton" href="<?php echo esc_url( $v( 'boton_url', '/contacto/' ) ); ?>"><?php echo esc_html( $v( 'boton_texto', 'cuéntanos tu idea' ) ); ?> <span class="flecha" aria-hidden="true">→</span></a>
	</div>
	<div class="h3d__bajar" aria-hidden="true"><span></span></div>
</section>

<!-- ============ 2. LEMA ============ -->
<section class="inicio-lema" aria-label="Vinea">
	<div class="contenedor--estrecho">
		<p class="inicio-lema__texto" data-relleno><?php echo esc_html( $v( 'lema' ) ); ?></p>
	</div>
</section>

<!-- ============ 3. SERVICIOS: tarjetas con profundidad ============ -->
<section class="inicio-servicios" id="servicios" aria-labelledby="servicios-titulo">
	<div class="contenedor">
		<h2 id="servicios-titulo" class="inicio-servicios__titulo" data-split><?php echo esc_html( $v( 'intro' ) ); ?></h2>
		<ul class="inicio-servicios__lista">
			<?php foreach ( $servicios as $i => $s ) : ?>
				<?php $externo = str_starts_with( (string) ( $s['url'] ?? '' ), 'http' ); ?>
				<li data-reveal style="--d:<?php echo (int) ( $i * 90 ); ?>">
					<a class="vineta vineta--3d" href="<?php echo esc_url( $s['url'] ?? '#' ); ?>" data-tilt<?php echo $externo ? ' target="_blank" rel="noopener"' : ''; ?>>
						<span class="vineta__fondo" aria-hidden="true"></span>
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

<!-- ============ MURO 3D con las fotos de Vinea ============ -->
<?php if ( $resto ) : ?>
<section class="muro" data-muro aria-label="Fotos de eventos organizados por Vinea">
	<div class="muro__plano" aria-hidden="true">
		<?php
		$cols = 6;
		for ( $c = 0; $c < $cols; $c++ ) :
			$en_col = array_values( array_filter( $resto, function ( $k ) use ( $cols, $c ) { return $k % $cols === $c; }, ARRAY_FILTER_USE_KEY ) );
			$en_col = array_slice( $en_col, 0, 12 );
			?>
			<div class="muro__col" style="--v:<?php echo (int) ( 52 + $c * 7 ); ?>s;--sentido:<?php echo 0 === $c % 2 ? 'normal' : 'reverse'; ?>">
				<?php for ( $r = 0; $r < 2; $r++ ) : foreach ( $en_col as $nombre ) : echo $foto_gal( $nombre ); endforeach; endfor; // phpcs:ignore ?>
			</div>
		<?php endfor; ?>
	</div>
</section>
<?php endif; ?>

<!-- ============ 4. CONCIENCIA SOCIAL Y MEDIOAMBIENTAL ============ -->
<section class="inicio-conciencia" aria-labelledby="conciencia-titulo">
	<div class="contenedor inicio-conciencia__grid">
		<div class="inicio-conciencia__texto">
			<h2 id="conciencia-titulo" class="inicio-conciencia__titulo" data-split><?php echo esc_html( $v( 'conciencia_titulo' ) ); ?></h2>
			<p class="inicio-conciencia__p" data-reveal><?php echo esc_html( $v( 'conciencia_texto' ) ); ?></p>
		</div>
		<figure class="abanico" data-reveal="fade">
			<div class="abanico__cartas">
				<?php foreach ( array_slice( $abanico, 0, 4 ) as $i => $foto ) : ?>
					<div class="abanico__carta" style="--k:<?php echo (int) $i; ?>"><?php $img( $foto, 'medium_large', array( 'sizes' => '(max-width: 900px) 60vw, 26vw', 'alt' => '' ) ); ?></div>
				<?php endforeach; ?>
			</div>
			<figcaption class="inicio-conciencia__pie"><?php echo esc_html( $v( 'conciencia_proyecto' ) ); ?></figcaption>
		</figure>
	</div>
</section>

<!-- ============ 5. LA ESENCIA DE VINEA ============ -->
<section class="inicio-esencia" aria-labelledby="esencia-titulo">
	<div class="contenedor inicio-esencia__grid">
		<div class="esencia3d" data-tilt>
			<span class="esencia3d__gota" aria-hidden="true"></span>
			<div class="inicio-esencia__foto"><?php $img( $v( 'esencia_imagen' ), 'large', array( 'sizes' => '(max-width: 900px) 80vw, 40vw', 'alt' => '' ) ); ?></div>
		</div>
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
					<figure data-tilt>
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
	<span class="inicio-cierre__gota inicio-cierre__gota--a" aria-hidden="true"></span>
	<span class="inicio-cierre__gota inicio-cierre__gota--b" aria-hidden="true"></span>
	<div class="contenedor--estrecho">
		<h2 class="inicio-cierre__texto" data-relleno><?php echo esc_html( $v( 'cierre' ) ); ?></h2>
	</div>
</section>

<?php get_footer(); ?>

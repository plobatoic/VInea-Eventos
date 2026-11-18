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
$cara      = array_values( array_filter( array_merge( array_map( $url_de, (array) $v( 'montaje', array() ) ), $momentos ) ) );
$resto     = array_keys( $por_nom ); // el muro usa todas las fotos de la galería
?>

<!-- ============ 1. ENTRADA: la gota de Vinea en 3D ============ -->
<section class="p3d" data-h3d aria-labelledby="hero-titulo">
	<div class="p3d__fondo" aria-hidden="true"></div>
	<div class="contenedor p3d__grid">
		<div class="p3d__texto">
			<h1 id="hero-titulo" class="p3d__h1"><?php echo esc_html( $v( 'h1', 'Agencia de eventos en Barcelona para empresas e instituciones' ) ); ?></h1>
			<p class="p3d__claim"><?php echo $claim; // phpcs:ignore -- escapado arriba. ?></p>
			<a class="boton boton--naranja p3d__boton" href="<?php echo esc_url( $v( 'boton_url', '/contacto/' ) ); ?>"><?php echo esc_html( $v( 'boton_texto', 'cuéntanos tu idea' ) ); ?> <span class="flecha" aria-hidden="true">→</span></a>
		</div>
		<div class="p3d__escena" aria-hidden="true">
			<div class="p3d__sombra"></div>
			<div class="p3d__giro"><div class="p3d__entra"><div class="p3d__mundo">
				<span class="p3d__aro p3d__aro--a"></span>
				<span class="p3d__aro p3d__aro--b"></span>
				<div class="p3d__gota">
					<?php for ( $k = 0; $k < 14; $k++ ) : ?><span class="p3d__capa" style="--k:<?php echo (int) $k; ?>"></span><?php endfor; ?>
					<div class="p3d__cara">
						<?php foreach ( array_slice( $cara, 0, 4 ) as $i => $foto ) : ?>
							<div class="p3d__foto" style="--i:<?php echo (int) $i; ?>"><?php $img( $foto, 'large', array( 'sizes' => '(max-width: 900px) 60vw, 460px', 'alt' => '', 'prioridad' => 0 === $i ) ); ?></div>
						<?php endforeach; ?>
						<span class="p3d__brillo"></span>
					</div>
				</div>
				<?php foreach ( array( 'a', 'b', 'c' ) as $m ) : ?>
					<div class="p3d__mini p3d__mini--<?php echo esc_attr( $m ); ?>"><div class="p3d__mini-in"><?php for ( $k = 0; $k < 4; $k++ ) : ?><span style="--k:<?php echo (int) $k; ?>"></span><?php endfor; ?></div></div>
				<?php endforeach; ?>
			</div></div></div>
		</div>
	</div>
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

<!-- ============ 5. LA ESENCIA DE VINEA: vuelo de dron hasta Mirian y Lara ============
     La sala es la de su foto de los Premios Ondas: el fondo se ha reconstruido a partir de la propia
     foto (quitándolas a ellas con IA) y se monta en 3D (pared lateral, pared del fondo y suelo).
     Al llegar a la sección, la cámara vuela sola desde lejos y desde arriba hasta tenerlas delante.
     Es una animación CSS (la mueve la tarjeta gráfica), no depende del scroll. -->
<?php $esencia_dir = get_template_directory_uri() . '/assets/esencia/'; ?>
<section class="sala" data-sala aria-labelledby="esencia-titulo" style="--foto:url('<?php echo esc_url( $esencia_dir . 'sala.webp' ); ?>')">
	<div class="sala__escena" aria-hidden="true">
		<div class="sala__ajuste" data-sala-ajuste>
			<div class="sala__camara">
				<span class="sala__suelo"></span>
				<span class="sala__pared sala__pared--izq"></span>
				<span class="sala__pared sala__pared--fondo"></span>
				<span class="sala__pared sala__pared--der"></span>
				<img class="sala__jefas" src="<?php echo esc_url( $esencia_dir . 'mirian-y-lara.webp' ); ?>" width="784" height="1000" alt="" loading="eager" fetchpriority="low" decoding="async">
				<span class="sala__humo"></span>
			</div>
		</div>
	</div>
	<div class="contenedor sala__capa">
		<div class="sala__texto">
			<h2 id="esencia-titulo" class="inicio-seccion__titulo"><?php echo esc_html( $v( 'esencia_titulo' ) ); ?></h2>
			<p class="inicio-esencia__texto"><?php echo esc_html( $v( 'esencia_texto' ) ); ?></p>
			<a class="boton boton--naranja" href="<?php echo esc_url( $v( 'esencia_boton_url', '/descubre-esencia-vinea-eventos/' ) ); ?>"><?php echo esc_html( $v( 'esencia_boton_texto', 'nuestra esencia' ) ); ?> <span class="flecha" aria-hidden="true">→</span></a>
		</div>
	</div>
</section>

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
		<h2 id="opiniones-titulo" class="inicio-seccion__titulo inicio-opiniones__titulo" data-split><?php echo esc_html( $v( 'opiniones_titulo', 'Empresas que confían en Vinea' ) ); ?></h2>

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

<!-- ============ 7. CIERRE ============ -->
<section class="inicio-cierre" aria-label="Nuestra forma de trabajar">
	<span class="inicio-cierre__gota inicio-cierre__gota--a" aria-hidden="true"></span>
	<span class="inicio-cierre__gota inicio-cierre__gota--b" aria-hidden="true"></span>
	<div class="contenedor--estrecho">
		<h2 class="inicio-cierre__texto" data-relleno><?php echo esc_html( $v( 'cierre' ) ); ?></h2>
	</div>
</section>

<?php get_footer(); ?>

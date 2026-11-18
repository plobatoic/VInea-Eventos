<?php
/**
 * Evento individual (URL /etn/… como en la web original): cartel entero a un lado y texto al otro,
 * con «Solicitar plaza» y otros eventos debajo.
 */
get_header();

while ( have_posts() ) :
	the_post();
	$post  = get_post();
	$lugar = (string) get_post_meta( $post->ID, 'vinea_lugar', true );
	$fecha = (string) get_post_meta( $post->ID, 'vinea_fecha_txt', true );
	$img   = (int) get_post_thumbnail_id( $post );
	?>
<article class="ev">
	<header class="cab-proy contenedor">
		<nav class="migas" aria-label="Ruta de navegación"><ol><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Inicio</a></li><li><a href="<?php echo esc_url( home_url( '/eventos-2/' ) ); ?>">Entradas</a></li><li><span aria-current="page"><?php echo esc_html( get_the_title() ); ?></span></li></ol></nav>
		<p class="etiqueta" data-reveal><?php echo esc_html( trim( $lugar . ( '' !== $lugar && '' !== $fecha ? ' · ' : '' ) . $fecha ) ); ?></p>
		<h1 class="titulo-1 cab-proy__titulo" data-split><?php echo esc_html( get_the_title() ); ?></h1>
	</header>
	<div class="contenedor ev__grid<?php echo function_exists( 'vinea_tienda_evento' ) && vinea_tienda_evento( $post ) ? ' ev__grid--tienda' : ''; ?>">
		<div class="ev__principal">
			<?php if ( $img ) : ?>
				<figure class="ev__cartel" data-reveal="foto"><?php echo vinea_img( $img, 'large', array( 'prioridad' => true, 'sizes' => '(min-width: 1100px) 640px, 92vw', 'data-ampliar' => '' ) ); // phpcs:ignore ?></figure>
			<?php endif; ?>
			<div class="contenido ev__texto">
				<section class="bloque bloque--intro"><?php the_content(); ?></section>
			</div>
		</div>
		<?php
		if ( function_exists( 'vinea_tienda_evento' ) && vinea_tienda_evento( $post ) ) {
			vinea_caja_tickets( $post );
		} else {
			?>
			<p class="ev__accion"><a class="boton boton--naranja" href="<?php echo esc_url( home_url( '/contacto/' ) ); ?>">Attend <span class="flecha" aria-hidden="true">→</span></a></p>
			<?php
		}
		?>
	</div>
</article>
	<?php
	$otros = get_posts( array( 'post_type' => 'etn', 'posts_per_page' => 3, 'post__not_in' => array( $post->ID ), 'meta_key' => 'vinea_en_listado', 'meta_value' => '1', 'orderby' => 'menu_order', 'order' => 'ASC' ) ); // phpcs:ignore
	if ( $otros ) :
		?>
<section class="seccion mas">
	<div class="contenedor">
		<h2 class="titulo-2" data-split>Entradas</h2>
		<ul class="eventos" role="list">
			<?php foreach ( $otros as $o ) : ?>
				<li class="evento" data-reveal>
					<a class="evento__foto" href="<?php echo esc_url( get_permalink( $o ) ); ?>" tabindex="-1" aria-hidden="true"><?php echo vinea_img( (int) get_post_thumbnail_id( $o ), 'medium_large', array( 'sizes' => '(min-width: 1100px) 380px, 92vw', 'alt' => '' ) ); // phpcs:ignore ?></a>
					<div class="evento__texto">
						<h3 class="evento__titulo"><a href="<?php echo esc_url( get_permalink( $o ) ); ?>"><?php echo esc_html( get_the_title( $o ) ); ?></a></h3>
						<p class="evento__resumen"><?php echo esc_html( $o->post_excerpt ); ?></p>
						<?php $desde = function_exists( 'vinea_precio_desde' ) ? vinea_precio_desde( $o ) : null; ?>
						<p class="evento__pie">
							<?php if ( null !== $desde ) : ?><span class="evento__precio"><small>Desde</small> <?php echo esc_html( vinea_precio( $desde ) ); ?></span><?php endif; ?>
							<a class="boton boton--naranja evento__boton" href="<?php echo esc_url( get_permalink( $o ) ); ?>">Attend <span class="flecha" aria-hidden="true">→</span></a>
						</p>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
		<?php
	endif;
	vinea_banda_cta( 'cuéntanos tu idea' );
endwhile;

get_footer();

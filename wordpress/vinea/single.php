<?php
/**
 * Entrada individual (en la web, «Proyectos»).
 */
get_header();

while ( have_posts() ) :
	the_post();
	$post   = get_post();
	$cat    = vinea_categoria_principal( $post );
	$imagen = vinea_imagen_post( $post );
	$cab    = vinea_separar_cabecera( $post->post_content, false, $imagen );
	?>
<article>
	<header class="cab-proy contenedor">
		<nav class="migas" aria-label="Ruta de navegación"><ol><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Inicio</a></li><li><a href="<?php echo esc_url( vinea_url_blog() ); ?>">Proyectos</a></li><li><span aria-current="page"><?php echo esc_html( get_the_title() ); ?></span></li></ol></nav>
		<p class="etiqueta" data-reveal><?php echo '' !== $cat ? esc_html( $cat ) . ' · ' : ''; ?><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( vinea_mes_anio( $post ) ); ?></time></p>
		<h1 class="titulo-1 cab-proy__titulo" data-split><?php echo ! empty( $cab['titulo'] ) ? wp_kses( $cab['titulo'], array( 'strong' => array(), 'em' => array(), 'br' => array(), 'a' => array( 'href' => array() ) ) ) : esc_html( get_the_title() ); ?></h1>
	</header>

	<?php if ( '' !== $imagen ) : ?>
		<div class="portada contenedor"><?php echo vinea_img( $imagen, 'full', array( 'prioridad' => true, 'sizes' => '(min-width: 1200px) 1200px, 100vw' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
	<?php endif; ?>

	<div class="contenido seccion">
		<div class="contenedor--estrecho">
			<?php echo apply_filters( 'the_content', $cab['resto'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</div>
</article>

	<?php $mas = vinea_proyectos_relacionados( $post, 2 ); ?>
	<?php if ( $mas ) : ?>
<section class="seccion mas">
	<div class="contenedor">
		<h2 class="titulo-2" data-split>Proyectos</h2>
		<div class="mas__grid">
			<?php
			foreach ( $mas as $otro ) {
				vinea_tarjeta_proyecto( $otro );
			}
			?>
		</div>
	</div>
</section>
	<?php endif; ?>

	<?php
	vinea_banda_cta( 'cuéntanos tu idea' );
endwhile;

get_footer();

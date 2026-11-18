<?php
/**
 * Template Name: Landing a pantalla completa
 * Template Post Type: page
 *
 * Sin cabecera ni pie del tema: se muestra solo el contenido de la página
 * (pensado para pegar HTML en un bloque «HTML personalizado»).
 */
remove_filter( 'the_content', 'wpautop' );
remove_filter( 'the_content', 'wptexturize' );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php
wp_body_open();
while ( have_posts() ) {
	the_post();
	the_content();
}
wp_footer();
?>
</body>
</html>

<?php
/**
 * Ajustes de administración: avisos y comentarios desactivados.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Aviso si el email de formularios no es el de producción.
add_action( 'admin_notices', 'vinea_aviso_email' );
function vinea_aviso_email(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$pantalla = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $pantalla || ! in_array( $pantalla->id, array( 'dashboard' ), true ) ) {
		return;
	}
	if ( 'info@vineaeventos.com' === strtolower( vinea_ajuste( 'email_formularios' ) ) ) {
		return;
	}
	printf(
		'<div class="notice notice-warning"><p><strong>Vinea:</strong> los formularios de la web se envían ahora a <code>%s</code>. En producción deben llegar a <code>info@vineaeventos.com</code>. <a href="%s">Cambiarlo en Ajustes → Vinea</a>.</p></div>',
		esc_html( vinea_ajuste( 'email_formularios' ) ),
		esc_url( admin_url( 'options-general.php?page=vinea-ajustes' ) )
	);
}

/* ---------- Comentarios fuera ---------- */
add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open', '__return_false', 20 );
add_filter( 'comments_array', '__return_empty_array', 20 );

add_action(
	'init',
	static function (): void {
		foreach ( array( 'post', 'page' ) as $tipo ) {
			remove_post_type_support( $tipo, 'comments' );
			remove_post_type_support( $tipo, 'trackbacks' );
		}
	},
	100
);

add_action(
	'admin_menu',
	static function (): void {
		remove_menu_page( 'edit-comments.php' );
		remove_submenu_page( 'options-general.php', 'options-discussion.php' );
	}
);

add_action(
	'admin_init',
	static function (): void {
		global $pagenow;
		if ( in_array( $pagenow, array( 'edit-comments.php', 'options-discussion.php' ), true ) ) {
			wp_safe_redirect( admin_url() );
			exit;
		}
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}
);

add_action(
	'admin_bar_menu',
	static function ( WP_Admin_Bar $barra ): void {
		$barra->remove_node( 'comments' );
	},
	999
);

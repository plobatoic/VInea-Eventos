<?php
/**
 * SEO básico. Si Yoast SEO está activo, no hace nada (Yoast manda).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function vinea_yoast_activo(): bool {
	return defined( 'WPSEO_VERSION' );
}

// Metacampos propios (los rellena el importador y el cuadro de la edición).
add_action(
	'init',
	static function (): void {
		foreach ( array( 'post', 'page' ) as $tipo ) {
			foreach ( array( '_vinea_seo_title', '_vinea_seo_desc' ) as $meta ) {
				register_post_meta(
					$tipo,
					$meta,
					array(
						'type'              => 'string',
						'single'            => true,
						'sanitize_callback' => 'sanitize_text_field',
						'show_in_rest'      => false,
						'auth_callback'     => static fn() => current_user_can( 'edit_posts' ),
					)
				);
			}
		}
	}
);

function vinea_seo_descripcion(): string {
	if ( is_singular() ) {
		$id   = get_queried_object_id();
		$desc = (string) get_post_meta( $id, '_vinea_seo_desc', true );
		if ( '' === $desc ) {
			$post = get_post( $id );
			if ( $post ) {
				$desc = $post->post_excerpt ? $post->post_excerpt : wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
			}
		}
		return wp_trim_words( trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $desc ) ) ), 32, '…' );
	}
	return get_bloginfo( 'description' );
}

if ( ! vinea_yoast_activo() ) {

	add_filter(
		'pre_get_document_title',
		static function ( $titulo ) {
			if ( is_singular() ) {
				$t = (string) get_post_meta( get_queried_object_id(), '_vinea_seo_title', true );
				if ( '' !== $t ) {
					return $t;
				}
			}
			return $titulo;
		}
	);

	add_action( 'wp_head', 'vinea_seo_cabecera', 2 );
	function vinea_seo_cabecera(): void {
		$desc = vinea_seo_descripcion();
		if ( '' !== $desc ) {
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
		}

		$titulo = wp_get_document_title();
		$url    = is_singular() ? get_permalink() : home_url( add_query_arg( null, null ) );
		printf( '<meta property="og:locale" content="es_ES">' . "\n" );
		printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
		printf( '<meta property="og:type" content="%s">' . "\n", is_singular( 'post' ) ? 'article' : 'website' );
		printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $titulo ) );
		if ( '' !== $desc ) {
			printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $desc ) );
		}
		printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
		if ( is_singular() && has_post_thumbnail() ) {
			$img = wp_get_attachment_image_url( get_post_thumbnail_id(), 'large' );
			if ( $img ) {
				printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $img ) );
			}
		}
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	}

	add_action( 'wp_head', 'vinea_seo_jsonld', 3 );
	function vinea_seo_jsonld(): void {
		$redes = array_values( array_filter( array( vinea_ajuste( 'instagram' ), vinea_ajuste( 'linkedin' ) ) ) );
		$datos = array(
			'@context'  => 'https://schema.org',
			'@type'     => 'ProfessionalService',
			'name'      => 'Vinea Eventos y Turismo',
			'url'       => home_url( '/' ),
			'email'     => vinea_ajuste( 'email_publico' ),
			'telephone' => array_values( array_filter( array( vinea_ajuste( 'telefono_1' ), vinea_ajuste( 'telefono_2' ) ) ) ),
			'address'   => array(
				'@type'           => 'PostalAddress',
				'addressLocality' => 'Manresa',
				'addressRegion'   => 'Barcelona',
				'addressCountry'  => 'ES',
			),
			'sameAs'    => $redes,
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $datos, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	// Cuadro «SEO» en páginas y entradas.
	add_action( 'add_meta_boxes', 'vinea_seo_metabox' );
	function vinea_seo_metabox(): void {
		foreach ( array( 'page', 'post' ) as $tipo ) {
			add_meta_box( 'vinea_seo', 'SEO (Vinea)', 'vinea_seo_metabox_html', $tipo, 'normal', 'low' );
		}
	}

	function vinea_seo_metabox_html( WP_Post $post ): void {
		wp_nonce_field( 'vinea_seo_guardar', 'vinea_seo_nonce' );
		?>
		<p><label for="vinea_seo_title"><strong>Título SEO</strong></label><br>
			<input type="text" class="widefat" id="vinea_seo_title" name="vinea_seo_title" value="<?php echo esc_attr( get_post_meta( $post->ID, '_vinea_seo_title', true ) ); ?>"></p>
		<p><label for="vinea_seo_desc"><strong>Meta descripción</strong></label><br>
			<textarea class="widefat" rows="3" id="vinea_seo_desc" name="vinea_seo_desc"><?php echo esc_textarea( get_post_meta( $post->ID, '_vinea_seo_desc', true ) ); ?></textarea></p>
		<p class="description">Si se deja vacío, se usa el título de la página y el extracto.</p>
		<?php
	}

	add_action(
		'save_post',
		static function ( int $id ): void {
			if ( ! isset( $_POST['vinea_seo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vinea_seo_nonce'] ) ), 'vinea_seo_guardar' ) ) {
				return;
			}
			if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $id ) ) {
				return;
			}
			update_post_meta( $id, '_vinea_seo_title', sanitize_text_field( wp_unslash( $_POST['vinea_seo_title'] ?? '' ) ) );
			update_post_meta( $id, '_vinea_seo_desc', sanitize_textarea_field( wp_unslash( $_POST['vinea_seo_desc'] ?? '' ) ) );
		}
	);
}

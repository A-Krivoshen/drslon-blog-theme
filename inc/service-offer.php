<?php
/**
 * One thematic service offer after a blog post.
 *
 * The article text is not rewritten. Yandex recommendation widgets stay
 * in the existing extras block. A post can force a service or turn the block off.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/service-offer-match.php';

const KRV_SERVICE_OFFER_META = '_krv_service_offer';

/**
 * @param mixed $value Raw meta value.
 */
function krv_service_offer_sanitize( $value ): string {
	$value = is_string( $value ) ? strtolower( trim( $value ) ) : '';

	if ( 'off' === $value ) {
		return 'off';
	}

	if ( '' === $value || 'auto' === $value ) {
		return '';
	}

	$catalog = krv_service_offer_catalog();

	return isset( $catalog[ $value ] ) ? $value : '';
}

add_action( 'init', 'krv_service_offer_register_meta' );
function krv_service_offer_register_meta(): void {
	register_post_meta(
		'post',
		KRV_SERVICE_OFFER_META,
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'auth_callback'     => static function ( $allowed = null, $meta_key = null, $post_id = 0, ...$unused ): bool {
				unset( $allowed, $meta_key, $unused );
				$post_id = (int) $post_id;

				return $post_id > 0
					? current_user_can( 'edit_post', $post_id )
					: current_user_can( 'edit_posts' );
			},
			'sanitize_callback' => 'krv_service_offer_sanitize',
		)
	);
}

/**
 * @return array{0: array<int, string>, 1: array<int, string>, 2: string}
 */
function krv_service_offer_terms_for_post( int $post_id ): array {
	$categories = array();
	$tags       = array();

	foreach ( get_the_category( $post_id ) as $term ) {
		if ( $term instanceof WP_Term && '' !== $term->slug ) {
			$categories[] = $term->slug;
		}
	}

	$tag_terms = get_the_tags( $post_id );
	if ( is_array( $tag_terms ) ) {
		foreach ( $tag_terms as $term ) {
			if ( $term instanceof WP_Term && '' !== $term->slug ) {
				$tags[] = $term->slug;
			}
		}
	}

	$primary = '';
	$primary_id = (int) get_post_meta( $post_id, '_primary_term_category', true );
	if ( $primary_id > 0 ) {
		$primary_term = get_term( $primary_id, 'category' );
		if ( $primary_term instanceof WP_Term && ! is_wp_error( $primary_term ) ) {
			$primary = $primary_term->slug;
		}
	}

	return array( $categories, $tags, $primary );
}

/**
 * Service id for the current post, or an empty string.
 */
function krv_service_offer_current_id( int $post_id ): string {
	if ( $post_id <= 0 || 'post' !== get_post_type( $post_id ) ) {
		return '';
	}

	$explicit = krv_service_offer_sanitize( get_post_meta( $post_id, KRV_SERVICE_OFFER_META, true ) );
	list( $categories, $tags, $primary ) = krv_service_offer_terms_for_post( $post_id );

	return krv_service_offer_pick( $categories, $tags, $primary, $explicit );
}

function krv_service_offer_shortcode(): string {
	if ( ! is_singular( 'post' ) ) {
		return '';
	}

	$post_id = (int) get_queried_object_id();
	$service_id = krv_service_offer_current_id( $post_id );
	$catalog = krv_service_offer_catalog();

	if ( '' === $service_id || ! isset( $catalog[ $service_id ] ) ) {
		return '';
	}

	$service = $catalog[ $service_id ];
	$GLOBALS['krv_service_offer_rendered'] = true;

	$html  = '<aside class="krv-service-offer" data-krv-service="' . esc_attr( $service_id ) . '">';
	$html .= '<p class="krv-service-offer__title">' . esc_html( $service['title'] ) . '</p>';
	$html .= '<p class="krv-service-offer__text">' . esc_html( $service['text'] ) . '</p>';
	$html .= '<a class="krv-service-offer__cta" data-krv-service-offer="' . esc_attr( $service_id ) . '" href="' . esc_url( $service['url'] ) . '">' . esc_html( $service['cta'] ) . '</a>';
	$html .= '</aside>';

	return $html;
}
add_shortcode( 'krv_service_offer', 'krv_service_offer_shortcode' );

add_action( 'wp_footer', 'krv_service_offer_click_script', 40 );
function krv_service_offer_click_script(): void {
	if ( empty( $GLOBALS['krv_service_offer_rendered'] ) ) {
		return;
	}
	?>
	<script>
	(function () {
		if (window.krvServiceOfferBound) return;
		window.krvServiceOfferBound = true;
		document.addEventListener('click', function (event) {
			var link = event.target && event.target.closest ? event.target.closest('[data-krv-service-offer]') : null;
			if (!link) return;
			if (!window.krvConsent || window.krvConsent.analytics !== true) return;
			if (typeof window.ym !== 'function') return;
			var service = link.getAttribute('data-krv-service-offer') || '';
			var page = window.location && window.location.pathname ? window.location.pathname : '/';
			try {
				window.ym(51907757, 'reachGoal', 'blog_service_click', { service: service, page: page });
			} catch (error) {}
		});
	})();
	</script>
	<?php
}

add_action( 'add_meta_boxes_post', 'krv_service_offer_metabox' );
function krv_service_offer_metabox(): void {
	add_meta_box(
		'krv_service_offer',
		'Коммерческая рекомендация',
		'krv_service_offer_metabox_render',
		'post',
		'side',
		'default',
		array(
			'__block_editor_compatible_meta_box' => true,
		)
	);
}

function krv_service_offer_metabox_render( WP_Post $post ): void {
	$value = krv_service_offer_sanitize( get_post_meta( $post->ID, KRV_SERVICE_OFFER_META, true ) );
	wp_nonce_field( 'krv_service_offer_save', 'krv_service_offer_nonce' );
	echo '<p><label for="krv_service_offer_choice">После статьи</label></p>';
	echo '<select id="krv_service_offer_choice" name="krv_service_offer" style="width:100%">';
	echo '<option value="auto"' . selected( $value, '', false ) . '>Авто по рубрике и меткам</option>';
	echo '<option value="off"' . selected( $value, 'off', false ) . '>Не показывать</option>';
	foreach ( krv_service_offer_catalog() as $service_id => $service ) {
		echo '<option value="' . esc_attr( $service_id ) . '"' . selected( $value, $service_id, false ) . '>' . esc_html( $service['title'] ) . '</option>';
	}
	echo '</select>';
	echo '<p class="description">Блок не вставляется в текст и не заменяет рекомендации Яндекса. Ссылка ведёт на лендинг услуги.</p>';
}

add_action( 'save_post_post', 'krv_service_offer_save', 10, 2 );
function krv_service_offer_save( int $post_id, WP_Post $post ): void {
	unset( $post );

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! isset( $_POST['krv_service_offer_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['krv_service_offer_nonce'] ) ), 'krv_service_offer_save' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$raw = isset( $_POST['krv_service_offer'] ) ? wp_unslash( $_POST['krv_service_offer'] ) : '';
	update_post_meta( $post_id, KRV_SERVICE_OFFER_META, krv_service_offer_sanitize( $raw ) );
}

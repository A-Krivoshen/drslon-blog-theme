<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin UI: explicit sticky checkbox for posts.
 *
 * Motivation: make sticky toggle easy to find, regardless of editor UI.
 * Scope: post post_type only; only allows changing sticky for publish status.
 */

if ( ! is_admin() ) {
	return;
}

/**
 * Meta box on post edit screen.
 */
add_action( 'add_meta_boxes_post', function (): void {
	add_meta_box(
		'drslon-sticky-post',
		__( 'Закрепить запись', 'drslon-blog' ),
		function ( WP_Post $post ): void {
			$post_id   = (int) $post->ID;
			$is_public = ( 'publish' === get_post_status( $post_id ) );
			$checked   = is_sticky( $post_id );

			wp_nonce_field( 'drslon_sticky_post_save', 'drslon_sticky_post_nonce' );
			?>
			<p>
				<label>
					<input type="checkbox" name="drslon_sticky_enabled" value="1" <?php checked( $checked ); ?> <?php disabled( ! $is_public ); ?> />
					<?php esc_html_e( 'Закрепить запись', 'drslon-blog' ); ?>
				</label>
			</p>
			<?php if ( ! $is_public ) : ?>
				<p class="description">
					<?php esc_html_e( 'Закрепление доступно только для опубликованных записей.', 'drslon-blog' ); ?>
				</p>
			<?php endif; ?>
			<?php
		},
		'post',
		'side',
		'high'
	);
} );

/**
 * Persist sticky toggle.
 */
add_action( 'save_post_post', function ( int $post_id, WP_Post $post ): void {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['drslon_sticky_post_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['drslon_sticky_post_nonce'] ) ), 'drslon_sticky_post_save' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Only allow toggling sticky for published posts.
	if ( 'publish' !== get_post_status( $post_id ) ) {
		return;
	}

	$want_sticky = ! empty( $_POST['drslon_sticky_enabled'] );
	$is_sticky   = is_sticky( $post_id );

	if ( $want_sticky && ! $is_sticky ) {
		stick_post( $post_id );
		return;
	}

	if ( ! $want_sticky && $is_sticky ) {
		unstick_post( $post_id );
	}
}, 10, 2 );

/**
 * Posts list: show sticky state column.
 */
add_filter( 'manage_post_posts_columns', function ( array $columns ): array {
	$insert_after = 'title';
	$new_columns  = array();

	foreach ( $columns as $key => $label ) {
		$new_columns[ $key ] = $label;
		if ( $insert_after === $key ) {
			$new_columns['drslon_sticky'] = __( 'Sticky', 'drslon-blog' );
		}
	}

	if ( ! isset( $new_columns['drslon_sticky'] ) ) {
		$new_columns['drslon_sticky'] = __( 'Sticky', 'drslon-blog' );
	}

	return $new_columns;
} );

add_action( 'manage_post_posts_custom_column', function ( string $column, int $post_id ): void {
	if ( 'drslon_sticky' !== $column ) {
		return;
	}

	$status    = (string) get_post_status( $post_id );
	$is_sticky = is_sticky( $post_id );

	if ( 'publish' !== $status ) {
		echo '<span aria-hidden="true">—</span>';
		return;
	}

	echo $is_sticky ? esc_html__( 'Yes', 'drslon-blog' ) : esc_html__( 'No', 'drslon-blog' );
}, 10, 2 );

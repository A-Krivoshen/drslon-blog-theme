<?php
/**
 * Targeted, repeatable policy correction. Dry-run by default.
 *
 * wp --skip-plugins=wp-linkedin-auto-publish,wptelegram,share-on-mastodon,max-autopost \
 *   eval-file scripts/update-cookie-policy.php
 * Apply with the final argument "apply", KRV_POLICY_EXPECTED_SHA and
 * KRV_POLICY_BACKUP set to an unused backup file outside the web root.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( "Run with WP-CLI.\n" );
}

if ( untrailingslashit( home_url() ) !== 'https://krivoshein.site' ) {
	WP_CLI::error( 'Unexpected site; policy update refused.' );
}

$post = get_page_by_path( 'politika-v-otnoshenii-obrabotki-personalnyh-dannyh', OBJECT, 'page' );
if ( ! $post instanceof WP_Post ) {
	WP_CLI::error( 'Policy page not found.' );
}

$original = $post->post_content;
$content  = str_replace(
	array(
		'с помощью сервисов <b>Яндекс.Метрика</b> и <b>Rambler Top100</b>',
		'<b>Яндекс.Метрика</b> и <b>Rambler Top100</b>',
		'Обезличенная статистика передаётся только в сервисы аналитики (Яндекс, Rambler)',
		'https://krivoshein.site/#gdpr_cookie_modal',
	),
	array(
		'с помощью сервиса <b>Яндекс.Метрика</b>',
		'<b>Яндекс.Метрика</b>',
		'Обезличенная статистика передаётся в Яндекс.Метрику',
		'#krv-cookie-settings',
	),
	$original
);
$content = preg_replace( '~^[ \t]*<span[^>]*>- <b>Rambler Top100</b>.*?</span><br>\r?\n~m', '', $content );

if ( false === strpos( $content, '3.5 На сайте показываются рекомендации Рекламной сети Яндекса' ) ) {
	$anchor = 'настройки cookie</a>.';
	$offset = strpos( $content, $anchor );
	if ( false === $offset ) {
		WP_CLI::error( 'Expected cookie settings paragraph is missing; update refused.' );
	}
	$offset += strlen( $anchor );
	$content = substr_replace(
		$content,
		'<br>' . "\n" . '      3.5 На сайте показываются рекомендации Рекламной сети Яндекса (РСЯ). По умолчанию они включены до изменения настроек. Их можно отключить независимо от Яндекс.Метрики через настройки cookie или кнопкой «Только нужные».',
		$offset,
		0
	);
}

if ( preg_match( '/rambler|top100|рамблер/iu', $content ) ) {
	WP_CLI::error( 'An obsolete service reference remains; update refused.' );
}

$meta_before = (string) get_post_meta( $post->ID, '_genesis_description', true );
$meta_after  = str_replace( ' и Rambler Top100', '', $meta_before );
if ( preg_match( '/rambler|top100|рамблер/iu', $meta_after ) ) {
	WP_CLI::error( 'An obsolete metadata reference remains; update refused.' );
}

$sha = hash( 'sha256', $original );
WP_CLI::log( 'Policy ID: ' . $post->ID );
WP_CLI::log( 'Original content SHA256: ' . $sha );
WP_CLI::log( 'Updated content SHA256: ' . hash( 'sha256', $content ) );
WP_CLI::log( 'Content changed: ' . ( $content !== $original ? 'yes' : 'no' ) );
WP_CLI::log( 'Description changed: ' . ( $meta_after !== $meta_before ? 'yes' : 'no' ) );

if ( ! isset( $args[0] ) || 'apply' !== $args[0] ) {
	WP_CLI::success( 'Dry run only; no database writes.' );
	return;
}
if ( $content === $original && $meta_before === $meta_after ) {
	WP_CLI::success( 'Policy already updated.' );
	return;
}
if ( getenv( 'KRV_POLICY_EXPECTED_SHA' ) !== $sha ) {
	WP_CLI::error( 'Policy changed since review; update refused.' );
}

$backup = getenv( 'KRV_POLICY_BACKUP' );
if ( ! $backup || file_exists( $backup ) || ! is_writable( dirname( $backup ) ) ) {
	WP_CLI::error( 'An unused, writable backup path is required.' );
}
$backup_dir = realpath( dirname( $backup ) );
$web_root   = realpath( ABSPATH );
if ( $backup_dir === $web_root || 0 === strpos( $backup_dir, $web_root . DIRECTORY_SEPARATOR ) ) {
	WP_CLI::error( 'Backup must be outside the public web root.' );
}
$saved = file_put_contents(
	$backup,
	wp_json_encode(
		array( 'post' => $post->to_array(), 'description' => $meta_before ),
		JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	),
	LOCK_EX
);
if ( false === $saved ) {
	WP_CLI::error( 'Policy backup failed; update refused.' );
}
chmod( $backup, 0600 );

$result = wp_update_post( array( 'ID' => $post->ID, 'post_content' => wp_slash( $content ) ), true );
if ( is_wp_error( $result ) ) {
	WP_CLI::error( $result->get_error_message() );
}
update_post_meta( $post->ID, '_genesis_description', $meta_after );
clean_post_cache( $post->ID );
WP_CLI::success( 'Policy and its description updated; original content backed up.' );

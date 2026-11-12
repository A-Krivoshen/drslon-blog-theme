<?php
/** Standalone WP-CLI migration regressions, without a database. */
define( 'WP_CLI', true );
define( 'OBJECT', 'OBJECT' );
$temporary = sys_get_temp_dir() . '/krv-policy-test-' . bin2hex( random_bytes( 6 ) );
mkdir( $temporary, 0700 );
mkdir( $temporary . '/public', 0700 );
define( 'ABSPATH', $temporary . '/public/' );

class WP_Post {
	public $ID = 5288;
	public $post_content;
	public $post_title = 'Original policy title';
	public $post_date = '2025-03-29 09:00:00';
	public function to_array() { return get_object_vars( $this ); }
}
class WP_CLI {
	public static function error( $message ) { throw new RuntimeException( $message ); }
	public static function log( $message ) {}
	public static function success( $message ) {}
}
function untrailingslashit( $value ) { return rtrim( $value, '/' ); }
function home_url() { return 'https://krivoshein.site'; }
function get_page_by_path( ...$args ) { return $GLOBALS['policy_post']; }
function get_post_thumbnail_id( $id ) { return 8935; }
function get_post_meta( $id, $key, $single = true ) { return $GLOBALS['policy_meta'][ $id ][ $key ] ?? ''; }
function update_post_meta( $id, $key, $value ) { $GLOBALS['policy_meta'][ $id ][ $key ] = $value; }
function clean_post_cache( $id ) {}
function current_user_can( $capability ) { return $GLOBALS['policy_capability']; }
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
function wp_slash( $value ) { return $value; }
function is_wp_error( $value ) { return false; }
function wp_update_post( $data, $error = false ) {
	if ( ! array_key_exists( 'page_template', $data ) || '' !== $data['page_template'] ) {
		throw new RuntimeException( 'Would revalidate an unrelated legacy page template.' );
	}
	$GLOBALS['policy_updates']++;
	$GLOBALS['policy_post']->post_content = $data['post_content'];
	return $data['ID'];
}
function check_policy( $condition, $message ) {
	if ( ! $condition ) throw new RuntimeException( $message );
}

function policy_case( $scenario, $temporary ) {
	$fixture = <<<'HTML'
<!-- wp:html -->
<div>с помощью сервисов <b>Яндекс.Метрика</b> и <b>Rambler Top100</b></div>
      <span style="margin-left: 20px; display: inline-block;">- <b>Rambler Top100</b> (см. <a href="https://help.rambler.ru/legal/1142/top100">политику</a>)</span><br>
<div>Обезличенная статистика передаётся только в сервисы аналитики (Яндекс, Rambler)</div>
<a href="https://krivoshein.site/#gdpr_cookie_modal">настройки cookie</a>.
<style>.policy{color:blue}</style><script>window.policyExample=true;</script>
<!-- /wp:html -->
HTML;
	$GLOBALS['policy_post'] = new WP_Post();
	$GLOBALS['policy_post']->post_content = $fixture;
	$GLOBALS['policy_meta'] = array(
		5288 => array( '_genesis_description' => 'Данные Яндекс.Метрики и Rambler Top100.', '_wp_page_template' => 'page-templates/contact.php' ),
		8935 => array( '_wp_attachment_image_alt' => 'Данные Яндекс.Метрики и Rambler Top100.' ),
	);
	$GLOBALS['policy_updates'] = 0;
	$GLOBALS['policy_capability'] = 'no-capability' !== $scenario;
	$args = 'dry-run' === $scenario ? array() : array( 'apply' );
	$backupPath = $temporary . '/before.json';
	if ( file_exists( $backupPath ) ) unlink( $backupPath );
	if ( 'public-backup' === $scenario ) $backupPath = ABSPATH . 'before.json';
	if ( 'metadata-only' === $scenario ) {
		// Generate the corrected body via the real dry run, while leaving metadata old.
		$args = array();
		require __DIR__ . '/../scripts/update-cookie-policy.php';
		$GLOBALS['policy_post']->post_content = $content;
		$args = array( 'apply' );
	}
	putenv( 'KRV_POLICY_EXPECTED_SHA=' . ( 'changed-sha' === $scenario ? 'incorrect' : hash( 'sha256', $GLOBALS['policy_post']->post_content ) ) );
	putenv( 'KRV_POLICY_BACKUP=' . ( 'missing-backup' === $scenario ? '' : $backupPath ) );
	$expectedErrors = array(
		'no-capability' => 'unfiltered_html',
		'changed-sha' => 'changed since review',
		'public-backup' => 'outside the public web root',
		'missing-backup' => 'backup path is required',
	);
	try {
		require __DIR__ . '/../scripts/update-cookie-policy.php';
		check_policy( ! isset( $expectedErrors[ $scenario ] ), 'Expected refusal was missing.' );
	} catch ( RuntimeException $exception ) {
		if ( ! isset( $expectedErrors[ $scenario ] ) || false === strpos( $exception->getMessage(), $expectedErrors[ $scenario ] ) ) throw $exception;
		check_policy( 0 === $GLOBALS['policy_updates'], 'Rejected migration changed content.' );
		check_policy( ! file_exists( $backupPath ), 'Rejected migration created a backup unexpectedly.' );
		return;
	}
	if ( 'dry-run' === $scenario ) {
		check_policy( 0 === $GLOBALS['policy_updates'] && ! file_exists( $backupPath ), 'Dry run wrote data.' );
		return;
	}
	check_policy( false === (bool) preg_match( '/rambler|top100/i', $GLOBALS['policy_post']->post_content . get_post_meta( 5288, '_genesis_description' ) . get_post_meta( 8935, '_wp_attachment_image_alt' ) ), 'Obsolete reference remains.' );
	check_policy( 'page-templates/contact.php' === get_post_meta( 5288, '_wp_page_template' ), 'Legacy template metadata changed.' );
	check_policy( false !== strpos( $GLOBALS['policy_post']->post_content, '<script>window.policyExample=true;</script>' ), 'Policy HTML was stripped.' );
	check_policy( ( 'metadata-only' === $scenario ? 0 : 1 ) === $GLOBALS['policy_updates'], 'Unexpected body update count.' );
	$saved = json_decode( file_get_contents( $backupPath ), true );
	check_policy( 8935 === $saved['thumbnail']['ID'] && false !== strpos( $saved['thumbnail']['alt'], 'Top100' ), 'Original image alt not backed up.' );
	check_policy( 0600 === ( fileperms( $backupPath ) & 0777 ), 'Backup permissions are not private.' );
	if ( 'already-updated' === $scenario ) {
		require __DIR__ . '/../scripts/update-cookie-policy.php';
		check_policy( 1 === $GLOBALS['policy_updates'], 'Idempotent rerun updated content again.' );
	}
}

try {
	foreach ( array( 'dry-run', 'apply', 'already-updated', 'metadata-only', 'changed-sha', 'no-capability', 'public-backup', 'missing-backup' ) as $scenario ) {
		policy_case( $scenario, $temporary );
		echo $scenario, ": OK\n";
	}
} finally {
	foreach ( array( $temporary . '/before.json', ABSPATH . 'before.json' ) as $file ) if ( is_file( $file ) ) unlink( $file );
	rmdir( $temporary . '/public' );
	rmdir( $temporary );
}

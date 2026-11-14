<?php
/**
 * Matcher cases for the blog service offer. No database.
 */

define( 'ABSPATH', __DIR__ . '/' );

require dirname( __DIR__ ) . '/inc/service-offer-match.php';

function krv_offer_assert( string $label, string $actual, string $expected ): void {
	if ( $actual !== $expected ) {
		fwrite( STDERR, $label . ': expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) . PHP_EOL );
		exit( 1 );
	}
}

krv_offer_assert( 'linux category alone stays quiet', krv_service_offer_pick( array( 'linux' ), array(), 'linux' ), '' );
krv_offer_assert( 'nginx tag on news', krv_service_offer_pick( array( 'novosti' ), array( 'nginx' ), 'novosti' ), 'vps' );
krv_offer_assert( 'wordpress primary beats nginx tag', krv_service_offer_pick( array( 'wordpress' ), array( 'nginx' ), 'wordpress' ), 'wordpress' );
krv_offer_assert( 'nginx tag ties wordpress category and primary wins', krv_service_offer_pick( array( 'linux', 'wordpress' ), array( 'nginx' ), 'wordpress' ), 'wordpress' );
krv_offer_assert( 'nginx tag on a linux post', krv_service_offer_pick( array( 'linux' ), array( 'nginx' ), 'linux' ), 'vps' );
krv_offer_assert( 'news without a signal', krv_service_offer_pick( array( 'novosti' ), array(), 'novosti' ), '' );
krv_offer_assert( 'business stays quiet', krv_service_offer_pick( array( 'business' ), array( 'lifestyle' ), 'business' ), '' );
krv_offer_assert( 'weak ssl tag', krv_service_offer_pick( array( 'raznoe' ), array( 'ssl' ), 'raznoe' ), '' );
krv_offer_assert( 'ai primary ties with max tag', krv_service_offer_pick( array( 'ai' ), array( 'max' ), 'ai' ), 'ai' );
krv_offer_assert( 'telegram and max beat ai category', krv_service_offer_pick( array( 'ai' ), array( 'telegram', 'max' ), 'ai' ), 'bots' );
krv_offer_assert( 'python tag', krv_service_offer_pick( array( 'novosti' ), array( 'python' ), 'novosti' ), 'flask' );
krv_offer_assert( 'direct category', krv_service_offer_pick( array( 'kontekstnaya-reklama' ), array(), 'kontekstnaya-reklama' ), 'direct' );
krv_offer_assert( 'plugin category', krv_service_offer_pick( array( 'plaginy-wordpress' ), array(), 'plaginy-wordpress' ), 'wordpress' );
krv_offer_assert( 'explicit off', krv_service_offer_pick( array( 'linux' ), array( 'nginx' ), 'linux', 'off' ), '' );
krv_offer_assert( 'explicit flask', krv_service_offer_pick( array( 'linux' ), array(), 'linux', 'flask' ), 'flask' );
krv_offer_assert( 'unknown explicit falls through', krv_service_offer_pick( array( 'vps' ), array(), 'vps', 'nope' ), 'vps' );

if ( PHP_SAPI === 'cli' && isset( $argv[1] ) && '--count' === $argv[1] ) {
	$rows = json_decode( stream_get_contents( STDIN ), true );
	if ( ! is_array( $rows ) ) {
		fwrite( STDERR, "count input is not a JSON array\n" );
		exit( 1 );
	}
	$totals = array();
	$shown  = 0;
	foreach ( $rows as $row ) {
		$service = krv_service_offer_pick(
			$row['categories'] ?? array(),
			$row['tags'] ?? array(),
			(string) ( $row['primary'] ?? '' ),
			''
		);
		if ( '' === $service ) {
			continue;
		}
		$shown++;
		$totals[ $service ] = ( $totals[ $service ] ?? 0 ) + 1;
	}
	ksort( $totals );
	echo json_encode(
		array(
			'published_posts' => count( $rows ),
			'with_offer'      => $shown,
			'by_service'      => $totals,
		),
		JSON_UNESCAPED_UNICODE
	) . PHP_EOL;
	exit( 0 );
}

echo "service-offer-match: ok\n";

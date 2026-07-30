<?php
/**
 * Conditional theme asset loading.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array<string, string> handle => relative path
 */
function drslon_theme_stylesheet_map(): array {
	return array(
		'drslon-css-base'          => 'assets/css/components/01-base.css',
		'drslon-css-archive'       => 'assets/css/components/02-archive.css',
		'drslon-css-blog-home'     => 'assets/css/components/03-blog-home.css',
		'drslon-css-blog-sections' => 'assets/css/components/04-blog-sections.css',
		'drslon-css-single'        => 'assets/css/components/05-single.css',
		'drslon-css-shell'         => 'assets/css/components/06-shell.css',
		'drslon-css-footer'        => 'assets/css/components/08-footer.css',
		'drslon-css-header-sticky' => 'assets/css/components/07-header-sticky.css',
		'drslon-css-blog-mobile'   => 'assets/css/components/09-blog-mobile.css',
		'drslon-css-theme-dark'    => 'assets/css/components/10-theme-dark.css',
	);
}

function drslon_should_enqueue_style( string $handle ): bool {
	switch ( $handle ) {
		// Always-on shell styles are served via drslon_shell_bundle (one request).
		case 'drslon-css-base':
		case 'drslon-css-shell':
		case 'drslon-css-footer':
		case 'drslon-css-header-sticky':
		case 'drslon-css-theme-dark':
			return false;

		case 'drslon-css-archive':
			return is_archive() || is_category() || is_tag() || is_author() || is_date() || is_search() || ( is_home() && ! is_front_page() );

		case 'drslon-css-blog-mobile':
			return is_home() && ! is_front_page();

		case 'drslon-css-blog-home':
			return is_home() && ! is_front_page();

		case 'drslon-css-blog-sections':
			return ( is_home() && ! is_front_page() ) || is_singular( 'post' );

		case 'drslon-css-single':
			return is_singular( array( 'post', 'project', 'arkai-portfolio' ) );

		default:
			return true;
	}
}

/**
 * Always-on CSS sources (order = cascade order). Bundled into one file for fewer round-trips.
 *
 * @return list<string> Paths relative to theme root.
 */
function drslon_shell_bundle_sources(): array {
	return array(
		'style.css',
		'assets/css/components/01-base.css',
		'assets/css/components/06-shell.css',
		'assets/css/components/08-footer.css',
		'assets/css/components/07-header-sticky.css',
		'assets/css/components/10-theme-dark.css',
	);
}

/**
 * Build or refresh assets/css/bundle-shell.css when any source is newer.
 *
 * @return array{path:string,uri:string,ver:string}|null
 */
function drslon_ensure_shell_bundle(): ?array {
	$theme_dir = get_template_directory();
	$theme_uri = get_template_directory_uri();
	$bundle_rel = 'assets/css/bundle-shell.css';
	$bundle_path = $theme_dir . '/' . $bundle_rel;

	$sources = drslon_shell_bundle_sources();
	$mtime_max = 0;
	$parts     = array();

	foreach ( $sources as $rel ) {
		$path = $theme_dir . '/' . $rel;
		if ( ! is_readable( $path ) ) {
			return null;
		}
		$mtime_max = max( $mtime_max, (int) filemtime( $path ) );
		$parts[]   = $path;
	}

	$needs_build = ! is_readable( $bundle_path ) || (int) filemtime( $bundle_path ) < $mtime_max;

	if ( $needs_build ) {
		$out = "/* drslon shell bundle — auto-built, do not edit */\n";
		foreach ( $parts as $path ) {
			$rel  = str_replace( $theme_dir . '/', '', $path );
			$css  = (string) file_get_contents( $path );
			$out .= "\n/* >>> {$rel} */\n" . $css . "\n";
		}

		$dir = dirname( $bundle_path );
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		// Atomic-ish write; fall back to per-file enqueue if unwritable.
		$tmp = $bundle_path . '.tmp.' . getmypid();
		if ( false === file_put_contents( $tmp, $out ) ) {
			return null;
		}
		if ( ! @rename( $tmp, $bundle_path ) ) {
			@unlink( $tmp );
			if ( false === file_put_contents( $bundle_path, $out ) ) {
				return null;
			}
		}
	}

	return array(
		'path' => $bundle_path,
		'uri'  => $theme_uri . '/' . $bundle_rel,
		'ver'  => (string) filemtime( $bundle_path ),
	);
}

function drslon_enqueue_theme_styles(): void {
	$theme_dir = get_template_directory();
	$theme_uri = get_template_directory_uri();

	$bundle = drslon_ensure_shell_bundle();
	$prev   = null;

	if ( is_array( $bundle ) ) {
		wp_enqueue_style(
			'drslon-css-shell-bundle',
			$bundle['uri'],
			array(),
			$bundle['ver']
		);
		$prev = 'drslon-css-shell-bundle';
	} else {
		// Fallback: original multi-file chain if bundle cannot be written.
		$style_path = $theme_dir . '/style.css';
		$version    = file_exists( $style_path ) ? (string) filemtime( $style_path ) : wp_get_theme()->get( 'Version' );
		wp_enqueue_style( 'drslon-blog-theme-style', get_stylesheet_uri(), array(), $version );
		$prev = 'drslon-blog-theme-style';

		foreach ( array( 'drslon-css-base', 'drslon-css-shell', 'drslon-css-footer', 'drslon-css-header-sticky', 'drslon-css-theme-dark' ) as $handle ) {
			$map  = drslon_theme_stylesheet_map();
			$rel  = $map[ $handle ] ?? '';
			$path = $rel ? $theme_dir . '/' . $rel : '';
			if ( ! $rel || ! file_exists( $path ) ) {
				continue;
			}
			wp_enqueue_style( $handle, $theme_uri . '/' . $rel, array( $prev ), (string) filemtime( $path ) );
			$prev = $handle;
		}
	}

	foreach ( drslon_theme_stylesheet_map() as $handle => $relative_path ) {
		if ( ! drslon_should_enqueue_style( $handle ) ) {
			continue;
		}

		$path = $theme_dir . '/' . $relative_path;

		if ( ! file_exists( $path ) ) {
			continue;
		}

		wp_enqueue_style(
			$handle,
			$theme_uri . '/' . $relative_path,
			$prev ? array( $prev ) : array(),
			(string) filemtime( $path )
		);

		$prev = $handle;
	}
}
add_action( 'wp_enqueue_scripts', 'drslon_enqueue_theme_styles', 20 );

/**
 * Early FOUC-safe theme bootstrap (same contract as static landings).
 */
function drslon_theme_fouc_script(): void {
	if ( is_admin() ) {
		return;
	}
	?>
<script>
(function(){try{
  var t=null;
  try{
    var m=document.cookie.match(/(?:^|;\s*)krv_theme=(light|dark)/);
    if(m) t=m[1];
  }catch(e){}
  if(t!=='light'&&t!=='dark'){
    try{t=localStorage.getItem('krv_theme');}catch(e){}
  }
  if(t!=='light'&&t!=='dark'){
    var h=new Date().getHours();
    t=(h>=7&&h<20)?'light':'dark';
  }
  document.documentElement.setAttribute('data-theme',t==='dark'?'dark':'light');
}catch(e){}})();
</script>
	<?php
}
add_action( 'wp_head', 'drslon_theme_fouc_script', 0 );

function drslon_enqueue_theme_scripts(): void {
	$theme_dir = get_template_directory();
	$theme_uri = get_template_directory_uri();

	$theme_js = $theme_dir . '/assets/js/krv-theme.js';
	if ( file_exists( $theme_js ) ) {
		wp_enqueue_script(
			'drslon-krv-theme',
			$theme_uri . '/assets/js/krv-theme.js',
			array(),
			(string) filemtime( $theme_js ),
			true
		);
	}

	$sticky_path = $theme_dir . '/assets/js/sticky-header.js';

	if ( file_exists( $sticky_path ) ) {
		wp_enqueue_script(
			'drslon-sticky-header',
			$theme_uri . '/assets/js/sticky-header.js',
			array(),
			(string) filemtime( $sticky_path ),
			true
		);
	}

	if ( is_home() && ! is_front_page() ) {
		$slider_path = $theme_dir . '/assets/js/featured-slider.js';

		if ( file_exists( $slider_path ) ) {
			wp_enqueue_script(
				'drslon-featured-slider',
				$theme_uri . '/assets/js/featured-slider.js',
				array(),
				(string) filemtime( $slider_path ),
				true
			);
		}
	}

	if ( ! is_singular( array( 'post', 'project', 'arkai-portfolio' ) ) ) {
		return;
	}

	$lightbox_path = $theme_dir . '/assets/js/content-lightbox.js';

	if ( file_exists( $lightbox_path ) ) {
		wp_enqueue_script(
			'drslon-content-lightbox',
			$theme_uri . '/assets/js/content-lightbox.js',
			array(),
			(string) filemtime( $lightbox_path ),
			true
		);
	}

	// Long TOC: expand on desktop only (see auto-toc.php threshold).
	if ( is_singular( array( 'post', 'project' ) ) ) {
		$toc_js = $theme_dir . '/assets/js/auto-toc.js';
		if ( file_exists( $toc_js ) ) {
			wp_enqueue_script(
				'drslon-auto-toc',
				$theme_uri . '/assets/js/auto-toc.js',
				array(),
				(string) filemtime( $toc_js ),
				true
			);
		}
	}
}
add_action( 'wp_enqueue_scripts', 'drslon_enqueue_theme_scripts', 25 );
/**
 * Header search: icon-only loupe that opens a modal (never expands inline).
 */
function drslon_header_search_render_block( string $content, array $block ): string {
	if ( ( $block['blockName'] ?? '' ) !== 'core/search' ) {
		return $content;
	}
	$class = (string) ( $block['attrs']['className'] ?? '' );
	if ( strpos( $class, 'drslon-header-search' ) === false ) {
		return $content;
	}
	$content = preg_replace( '/\srequired(=("|\')required("|\')|=("|\')true("|\'))?/i', '', $content ) ?? $content;
	$content = str_replace(
		'class="wp-block-search__button',
		'class="wp-block-search__button drslon-header-search__loupe',
		$content
	);
	// Prefer type=button so native submit cannot expand core button-only mode.
	$content = preg_replace(
		'/(class="wp-block-search__button drslon-header-search__loupe[^"]*")(\s+type="submit")?/i',
		'$1 type="button" aria-haspopup="dialog" aria-label="Открыть поиск"',
		$content,
		1
	) ?? $content;
	return $content;
}
add_filter( 'render_block', 'drslon_header_search_render_block', 20, 2 );

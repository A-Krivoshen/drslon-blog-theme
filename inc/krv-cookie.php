<?php
/**
 * Cookie notice for krivoshein.site.
 *
 * Necessary cookies (theme, this choice) stay on. Yandex Metrika and
 * Rambler Top100 start only after an explicit analytics choice.
 * Replaces the Moove GDPR Cookie Compliance plugin.
 *
 * @package drslon-blog-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front of the site only. Feeds, embeds, and wp-admin stay quiet.
 */
function drslon_krv_cookie_is_public(): bool {
	if ( is_admin() || is_feed() || is_embed() ) {
		return false;
	}

	return ! wp_is_json_request();
}

/**
 * Hide the bar before paint when a choice is already stored.
 */
function drslon_krv_cookie_head(): void {
	if ( ! drslon_krv_cookie_is_public() ) {
		return;
	}

	echo "<script>(function(){var c=document.cookie;if(/(?:^|;\\s*)krv_consent=(?:a|n)(?:;|$)/.test(c)||/(?:^|;\\s*)moove_gdpr_popup=/.test(c)){document.documentElement.classList.add('krv-cookie-chosen');}})();</script>\n";
}
add_action( 'wp_head', 'drslon_krv_cookie_head', 0 );

/**
 * Bar and settings dialog. The footer link is #krv-cookie-settings.
 */
function drslon_krv_cookie_footer(): void {
	if ( ! drslon_krv_cookie_is_public() ) {
		return;
	}

	$policy = home_url( '/politika-v-otnoshenii-obrabotki-personalnyh-dannyh/' );
	?>
<aside class="krv-cookie" id="krv-cookie" aria-label="Уведомление о cookie">
	<div class="krv-cookie__panel">
		<p class="krv-cookie__title" id="krv-cookie-title">Файлы cookie</p>
		<p class="krv-cookie__text">Нужные cookie помнят тему оформления. Яндекс Метрика и Rambler Top100 включаются только с вашего согласия. Подробности в <a href="<?php echo esc_url( $policy ); ?>">политике ПДн и cookie</a>.</p>
		<div class="krv-cookie__actions">
			<button type="button" class="krv-cookie__btn krv-cookie__btn--primary" data-krv-consent="a">Принять</button>
			<button type="button" class="krv-cookie__btn" data-krv-consent="n">Только нужные</button>
			<button type="button" class="krv-cookie__btn krv-cookie__btn--quiet" data-krv-consent="settings">Настроить</button>
		</div>
	</div>
</aside>
<div class="krv-cookie-dialog" id="krv-cookie-settings" hidden>
	<div class="krv-cookie-dialog__backdrop" data-krv-consent="close"></div>
	<div class="krv-cookie-dialog__panel" role="dialog" aria-modal="true" aria-labelledby="krv-cookie-settings-title">
		<p class="krv-cookie__title" id="krv-cookie-settings-title">Настройка cookie</p>
		<label class="krv-cookie-choice krv-cookie-choice--locked">
			<input type="checkbox" checked disabled>
			<span>
				<strong>Необходимые</strong>
				<span>Тема оформления и сохранение этого выбора. Всегда включены.</span>
			</span>
		</label>
		<label class="krv-cookie-choice" for="krv-cookie-analytics">
			<input type="checkbox" id="krv-cookie-analytics">
			<span>
				<strong>Аналитика</strong>
				<span>Яндекс Метрика и Rambler Top100. Визиты и переходы по ссылкам, без запуска до согласия.</span>
			</span>
		</label>
		<div class="krv-cookie__actions">
			<button type="button" class="krv-cookie__btn krv-cookie__btn--primary" data-krv-consent="save">Сохранить</button>
			<button type="button" class="krv-cookie__btn" data-krv-consent="a">Принять всё</button>
			<button type="button" class="krv-cookie__btn krv-cookie__btn--quiet" data-krv-consent="close">Закрыть</button>
		</div>
	</div>
</div>
	<?php
}
add_action( 'wp_footer', 'drslon_krv_cookie_footer', 20 );

/**
 * Footer script. Analytics IDs are public and match the old plugin settings.
 */
function drslon_krv_cookie_script(): void {
	if ( ! drslon_krv_cookie_is_public() ) {
		return;
	}

	$path = get_template_directory() . '/assets/js/krv-cookie.js';
	if ( ! is_readable( $path ) ) {
		return;
	}

	wp_enqueue_script(
		'drslon-krv-cookie',
		get_template_directory_uri() . '/assets/js/krv-cookie.js',
		array(),
		(string) filemtime( $path ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'drslon_krv_cookie_script' );

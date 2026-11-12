<?php
/**
 * Cookie notice for krivoshein.site.
 *
 * Necessary cookies (theme, this choice) stay on. Yandex Metrika starts
 * only after an explicit analytics choice. Recommendations are independent.
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

	echo <<<'JS'
<script id="drslon-krv-cookie-head">
(function(){
  /* Keep decoding and legacy precedence aligned with krv-cookie.js. */
  function values(n){
    var out=[];
    try{
      var parts=document.cookie.split(";");
      for(var i=0;i<parts.length;i++){
        var c=parts[i].replace(/^\s+/,"");
        if(c.indexOf(n+"=")!==0)continue;
        try{out.push(decodeURIComponent(c.slice(n.length+1)));}catch(e){}
      }
    }catch(e){}
    return out;
  }
  function flag(v){
    if(v===1||v==="1"||v===true)return true;
    if(v===0||v==="0"||v===false)return false;
    return null;
  }
  function choice(a,d){return a?(d?"a":"m"):(d?"d":"n");}
  function restrict(list){
    var a=true,d=true;
    for(var i=0;i<list.length;i++){
      a=a&&(list[i]==="a"||list[i]==="m");
      d=d&&(list[i]!=="n"&&list[i]!=="m");
    }
    return choice(a,d);
  }
  var raw=values("krv_consent"),current=[],legacy=[];
  for(var i=0;i<raw.length;i++){
    var v=raw[i];
    if(/^[anmd]$/.test(v))current.push(v);
    else if(v==="all"||v==="necessary")legacy.push(v==="all"?"a":"n");
    else{
      try{
        var data=JSON.parse(v);
        if(!data||typeof data!=="object")continue;
        var a=flag(data.a),d=flag(data.d);
        if(a!==null&&d!==null)legacy.push(choice(a,d));
      }catch(e){}
    }
  }
  if(!current.length&&!legacy.length){
    raw=values("moove_gdpr_popup");
    for(var j=0;j<raw.length;j++){
      try{
        var data=JSON.parse(raw[j]);
        if(data&&flag(data.thirdparty)===true)legacy.push("a");
        else if(data&&(flag(data.thirdparty)===false||flag(data.strict)===true))legacy.push("n");
      }catch(e){}
    }
  }
  var v=current.length?restrict(current):(legacy.length?restrict(legacy):"");
  window.krvConsent={analytics:v==="a"||v==="m",ads:v!=="n"&&v!=="m",necessary:true};
  if(v)document.documentElement.classList.add("krv-cookie-chosen");
})();
</script>
JS;
	echo "\n";
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
		<p class="krv-cookie__text">Нужные cookie помнят тему оформления. Яндекс.Метрика включается только с вашего согласия. Рекомендации РСЯ показываются сразу, их можно отключить кнопкой «Только нужные» или в настройках. Подробности в <a href="<?php echo esc_url( $policy ); ?>">политике ПДн и cookie</a>.</p>
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
				<span>Яндекс.Метрика. Статистика посещений, без запуска до согласия.</span>
			</span>
		</label>
		<label class="krv-cookie-choice" for="krv-cookie-ads">
			<input type="checkbox" id="krv-cookie-ads">
			<span>
				<strong>Рекомендации РСЯ</strong>
				<span>Рекламная сеть Яндекса. Можно отключить независимо от аналитики.</span>
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

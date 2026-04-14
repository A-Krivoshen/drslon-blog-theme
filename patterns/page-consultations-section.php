<?php
/**
 * Title: Consultations Page Section
 * Slug: drslon-blog/page-consultations-section
 * Categories: featured
 * Inserter: yes
 * Description: Consultations section with intro, offer card, CTA and FAQ-ready structure.
 */
?>
<!-- wp:group {"style":{"spacing":{"padding":{"top":"30px","right":"24px","bottom":"30px","left":"24px"},"blockGap":"20px"},"border":{"width":"1px","color":"var:preset|color|border","radius":"12px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="border-color:var(--wp--preset--color--border);border-width:1px;border-radius:12px;padding-top:30px;padding-right:24px;padding-bottom:30px;padding-left:24px">
	<!-- wp:heading {"level":2} -->
	<h2>Консультации</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"textColor":"muted"} -->
	<p class="has-muted-color has-text-color">Практические консультации по WordPress, Linux и DevOps: аудит текущей конфигурации, разбор узких мест и пошаговый план изменений.</p>
	<!-- /wp:paragraph -->

	<!-- wp:columns {"isStackedOnMobile":true,"style":{"spacing":{"blockGap":{"left":"20px"}}}} -->
	<div class="wp-block-columns">
		<!-- wp:column {"width":"56%"} -->
		<div class="wp-block-column" style="flex-basis:56%">
			<!-- wp:group {"style":{"spacing":{"blockGap":"10px"}}} -->
			<div class="wp-block-group">
				<!-- wp:heading {"level":3,"fontSize":"sm"} -->
				<h3 class="has-sm-font-size">Что входит</h3>
				<!-- /wp:heading -->

				<!-- wp:list -->
				<ul><li>Подготовка перед звонком: сбор контекста и ключевых вопросов</li><li>Созвон 60–90 минут с разбором инфраструктуры</li><li>Письменный summary с рекомендациями и приоритетами</li></ul>
				<!-- /wp:list -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"44%"} -->
		<div class="wp-block-column" style="flex-basis:44%">
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"18px","right":"18px","bottom":"18px","left":"18px"},"blockGap":"10px"},"border":{"width":"1px","color":"var:preset|color|border","radius":"10px"},"color":{"background":"var:preset|color|surface"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group has-background" style="border-color:var(--wp--preset--color--border);border-width:1px;border-radius:10px;background-color:var(--wp--preset--color--surface);padding-top:18px;padding-right:18px;padding-bottom:18px;padding-left:18px">
				<!-- wp:paragraph {"fontSize":"xs","textColor":"muted"} -->
				<p class="has-muted-color has-text-color has-xs-font-size">Стартовая консультация</p>
				<!-- /wp:paragraph -->

				<!-- wp:heading {"level":3} -->
				<h3>от 12 000 ₽</h3>
				<!-- /wp:heading -->

				<!-- wp:buttons -->
				<div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#consultation-request">Запросить консультацию</a></div><!-- /wp:button --></div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:separator -->
	<hr class="wp-block-separator has-alpha-channel-opacity"/>
	<!-- /wp:separator -->

	<!-- wp:heading {"level":3,"fontSize":"sm"} -->
	<h3 class="has-sm-font-size">FAQ (можно заполнить при переносе)</h3>
	<!-- /wp:heading -->

	<!-- wp:list -->
	<ul><li>С какими проектами вы работаете?</li><li>Можно ли после консультации взять сопровождение?</li><li>Как быстро можно назначить слот?</li></ul>
	<!-- /wp:list -->
</div>
<!-- /wp:group -->

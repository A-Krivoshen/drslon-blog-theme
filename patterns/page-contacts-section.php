<?php
/**
 * Title: Contacts Page Section
 * Slug: drslon-blog/page-contacts-section
 * Categories: featured
 * Inserter: yes
 * Description: Two-column contacts section with direct links and a visual card close to the production contacts page.
 */
?>
<!-- wp:group {"style":{"spacing":{"padding":{"top":"32px","right":"24px","bottom":"32px","left":"24px"},"blockGap":"20px"},"border":{"width":"1px","color":"var:preset|color|border","radius":"12px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="border-color:var(--wp--preset--color--border);border-width:1px;border-radius:12px;padding-top:32px;padding-right:24px;padding-bottom:32px;padding-left:24px">
	<!-- wp:columns {"verticalAlignment":"top","isStackedOnMobile":true,"style":{"spacing":{"blockGap":{"left":"24px"}}}} -->
	<div class="wp-block-columns are-vertically-aligned-top">
		<!-- wp:column {"verticalAlignment":"top","width":"64%"} -->
		<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:64%">
			<!-- wp:heading {"level":2} -->
			<h2>Связаться со мной</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"textColor":"muted"} -->
			<p class="has-muted-color has-text-color">Если у вас есть вопрос по сайту, серверу, рекламе или проекту — напишите удобным способом.</p>
			<!-- /wp:paragraph -->

			<!-- wp:group {"style":{"spacing":{"padding":{"top":"16px","right":"16px","bottom":"16px","left":"16px"},"blockGap":"10px"},"border":{"width":"1px","color":"var:preset|color|border","radius":"10px"},"color":{"background":"var:preset|color|surface"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group has-background" style="border-color:var(--wp--preset--color--border);border-width:1px;border-radius:10px;background-color:var(--wp--preset--color--surface);padding-top:16px;padding-right:16px;padding-bottom:16px;padding-left:16px">
				<!-- wp:paragraph {"fontSize":"xs","textColor":"muted"} -->
				<p class="has-muted-color has-text-color has-xs-font-size">Контакты</p>
				<!-- /wp:paragraph -->

				<!-- wp:list -->
				<ul><li>📞 <a href="tel:+79636641615">+7 (963) 664-16-15</a></li><li>✉️ <a href="mailto:aleksey@krivoshein.site">aleksey@krivoshein.site</a></li><li>✈️ <a href="https://t.me/DrSlon">@DrSlon</a></li><li>🔷 <a href="https://krivoshein.site/max">MAX</a></li></ul>
				<!-- /wp:list -->
			</div>
			<!-- /wp:group -->

			<!-- wp:buttons -->
			<div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="mailto:aleksey@krivoshein.site">Написать на email</a></div><!-- /wp:button -->

			<!-- wp:button {"className":"is-style-outline"} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="https://t.me/DrSlon">Написать в Telegram</a></div>
			<!-- /wp:button --></div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"top","width":"36%"} -->
		<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:36%">
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"16px","right":"16px","bottom":"16px","left":"16px"},"blockGap":"12px"},"border":{"width":"1px","color":"var:preset|color|border","radius":"10px"},"color":{"background":"var:preset|color|surface"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group has-background" style="border-color:var(--wp--preset--color--border);border-width:1px;border-radius:10px;background-color:var(--wp--preset--color--surface);padding-top:16px;padding-right:16px;padding-bottom:16px;padding-left:16px">
				<!-- wp:cover {"url":"https://krivoshein.site/wp-content/uploads/2025/05/chatgpt-image-21-maya-2025-g.-11_12_07-1.png","dimRatio":40,"overlayColor":"text","isUserOverlayColor":true,"minHeight":320,"isDark":false,"style":{"border":{"radius":"8px"}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-cover is-light" style="border-radius:8px;min-height:320px"><span aria-hidden="true" class="wp-block-cover__background has-text-background-color has-background-dim-40 has-background-dim"></span><img class="wp-block-cover__image-background" alt="Слон у компьютера" src="https://krivoshein.site/wp-content/uploads/2025/05/chatgpt-image-21-maya-2025-g.-11_12_07-1.png" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:spacer {"height":"220px"} -->
				<div style="height:220px" aria-hidden="true" class="wp-block-spacer"></div>
				<!-- /wp:spacer -->

				<!-- wp:paragraph {"align":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"500"}},"textColor":"surface"} -->
				<p class="has-text-align-center has-surface-color has-text-color" style="font-style:normal;font-weight:500">Если вас нет в Интернете, то вас нет в бизнесе</p>
				<!-- /wp:paragraph --></div></div>
				<!-- /wp:cover -->

				<!-- wp:buttons -->
				<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/">На главную</a></div>
				<!-- /wp:button --></div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->

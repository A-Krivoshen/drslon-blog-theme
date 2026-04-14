<?php
/**
 * Title: Resume Page Section
 * Slug: drslon-blog/page-resume-section
 * Categories: featured
 * Inserter: yes
 * Description: Resume section with profile hero and structured blocks for experience, skills and education.
 */
?>
<!-- wp:group {"style":{"spacing":{"padding":{"top":"30px","right":"24px","bottom":"30px","left":"24px"},"blockGap":"20px"},"border":{"width":"1px","color":"var:preset|color|border","radius":"12px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="border-color:var(--wp--preset--color--border);border-width:1px;border-radius:12px;padding-top:30px;padding-right:24px;padding-bottom:30px;padding-left:24px">
	<!-- wp:group {"style":{"spacing":{"blockGap":"10px"}}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"level":2} -->
		<h2>Алексей Кривошеин — WordPress / Linux / DevOps Engineer</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"textColor":"muted"} -->
		<p class="has-muted-color has-text-color">12+ лет в поддержке и развитии веб-проектов: от CMS и CI/CD до производительности, отказоустойчивости и эксплуатационной практики.</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#experience">Опыт</a></div><!-- /wp:button -->

		<!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#skills">Навыки</a></div><!-- /wp:button -->

		<!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#education">Образование</a></div><!-- /wp:button --></div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->

	<!-- wp:columns {"isStackedOnMobile":true,"style":{"spacing":{"blockGap":{"left":"22px"}}}} -->
	<div class="wp-block-columns">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:heading {"level":3,"fontSize":"sm","anchor":"experience"} -->
			<h3 class="wp-block-heading has-sm-font-size" id="experience">Опыт</h3>
			<!-- /wp:heading -->

			<!-- wp:list -->
			<ul><li><strong>Senior WP Engineer</strong> — поддержка продуктовых и контентных платформ</li><li><strong>DevOps / SRE задачи</strong> — Nginx, Docker, CI/CD, мониторинг</li><li><strong>Техлид на аутсорсе</strong> — аудит, roadmap, стабилизация продакшна</li></ul>
			<!-- /wp:list -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:heading {"level":3,"fontSize":"sm","anchor":"skills"} -->
			<h3 class="wp-block-heading has-sm-font-size" id="skills">Навыки</h3>
			<!-- /wp:heading -->

			<!-- wp:list -->
			<ul><li>WordPress Core, block themes, производительность</li><li>Linux администрирование, Bash, Python automation</li><li>Nginx, Docker, backup/restore, безопасность</li></ul>
			<!-- /wp:list -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:columns {"isStackedOnMobile":true,"style":{"spacing":{"blockGap":{"left":"22px"}}}} -->
	<div class="wp-block-columns">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:heading {"level":3,"fontSize":"sm"} -->
			<h3 class="has-sm-font-size">Сертификаты</h3>
			<!-- /wp:heading -->

			<!-- wp:list -->
			<ul><li>LPIC / Linux administration tracks</li><li>Docker и контейнерная эксплуатация</li><li>Курсы по информационной безопасности</li></ul>
			<!-- /wp:list -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:heading {"level":3,"fontSize":"sm","anchor":"education"} -->
			<h3 class="wp-block-heading has-sm-font-size" id="education">Образование</h3>
			<!-- /wp:heading -->

			<!-- wp:list -->
			<ul><li>Техническое высшее образование</li><li>Непрерывное самообучение в DevOps и backend tooling</li><li>Практика на коммерческих проектах и long-term сопровождении</li></ul>
			<!-- /wp:list -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<?php
/**
 * Title: Contacts Page Section
 * Slug: drslon-blog/page-contacts-section
 * Categories: featured
 * Inserter: yes
 * Description: Practical contacts section for DrSlon Blog with direct links, work format and short request checklist.
 */
?>
<!-- wp:group {"className":"drslon-page-section drslon-contacts-section","style":{"spacing":{"padding":{"top":"32px","right":"24px","bottom":"32px","left":"24px"},"blockGap":"22px"},"border":{"width":"1px","color":"var:preset|color|border","radius":"14px"},"color":{"background":"var:preset|color|surface"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group drslon-page-section drslon-contacts-section has-background" style="border-color:var(--wp--preset--color--border);border-width:1px;border-radius:14px;background-color:var(--wp--preset--color--surface);padding-top:32px;padding-right:24px;padding-bottom:32px;padding-left:24px">
        <!-- wp:columns {"verticalAlignment":"top","isStackedOnMobile":true,"style":{"spacing":{"blockGap":{"left":"28px"}}}} -->
        <div class="wp-block-columns are-vertically-aligned-top">
                <!-- wp:column {"verticalAlignment":"top","width":"62%"} -->
                <div class="wp-block-column is-vertically-aligned-top" style="flex-basis:62%">
                        <!-- wp:heading {"level":2} -->
                        <h2>Контакты</h2>
                        <!-- /wp:heading -->

                        <!-- wp:paragraph {"textColor":"muted"} -->
                        <p class="has-muted-color has-text-color">Пишите, если нужна помощь с WordPress, сервером, Nginx, Docker, производительностью сайта, техническим SEO или рекламной инфраструктурой. Лучше сразу кратко описать задачу, домен и что уже пробовали.</p>
                        <!-- /wp:paragraph -->

                        <!-- wp:group {"style":{"spacing":{"padding":{"top":"18px","right":"18px","bottom":"18px","left":"18px"},"blockGap":"10px"},"border":{"width":"1px","color":"var:preset|color|border","radius":"12px"},"color":{"background":"var:preset|color|background"}},"layout":{"type":"constrained"}} -->
                        <div class="wp-block-group has-background" style="border-color:var(--wp--preset--color--border);border-width:1px;border-radius:12px;background-color:var(--wp--preset--color--background);padding-top:18px;padding-right:18px;padding-bottom:18px;padding-left:18px">
                                <!-- wp:heading {"level":3,"fontSize":"sm"} -->
                                <h3 class="has-sm-font-size">Основные способы связи</h3>
                                <!-- /wp:heading -->

                                <!-- wp:list -->
                                <ul><li>Телефон: <a href="tel:+79636641615">+7 (963) 664-16-15</a></li><li>Email: <a href="mailto:aleksey@krivoshein.site">aleksey@krivoshein.site</a></li><li>Telegram: <a href="https://t.me/DrSlon">@DrSlon</a></li><li>Сайт: <a href="https://krivoshein.site">krivoshein.site</a></li></ul>
                                <!-- /wp:list -->
                        </div>
                        <!-- /wp:group -->

                        <!-- wp:buttons {"style":{"spacing":{"blockGap":"10px"}}} -->
                        <div class="wp-block-buttons"><!-- wp:button -->
                        <div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="mailto:aleksey@krivoshein.site">Написать на email</a></div>
                        <!-- /wp:button -->

                        <!-- wp:button {"className":"is-style-outline"} -->
                        <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="https://t.me/DrSlon">Написать в Telegram</a></div>
                        <!-- /wp:button --></div>
                        <!-- /wp:buttons -->
                </div>
                <!-- /wp:column -->

                <!-- wp:column {"verticalAlignment":"top","width":"38%"} -->
                <div class="wp-block-column is-vertically-aligned-top" style="flex-basis:38%">
                        <!-- wp:group {"style":{"spacing":{"padding":{"top":"20px","right":"20px","bottom":"20px","left":"20px"},"blockGap":"12px"},"border":{"width":"1px","color":"var:preset|color|border","radius":"12px"},"color":{"background":"var:preset|color|background"}},"layout":{"type":"constrained"}} -->
                        <div class="wp-block-group has-background" style="border-color:var(--wp--preset--color--border);border-width:1px;border-radius:12px;background-color:var(--wp--preset--color--background);padding-top:20px;padding-right:20px;padding-bottom:20px;padding-left:20px">
                                <!-- wp:heading {"level":3,"fontSize":"sm"} -->
                                <h3 class="has-sm-font-size">Что прислать для быстрого старта</h3>
                                <!-- /wp:heading -->

                                <!-- wp:list -->
                                <ul><li>Ссылку на сайт или репозиторий.</li><li>Краткое описание проблемы или задачи.</li><li>Скриншоты, логи, ошибки из консоли или админки.</li><li>Что уже меняли и после чего всё сломалось.</li></ul>
                                <!-- /wp:list -->

                                <!-- wp:paragraph {"fontSize":"xs","textColor":"muted"} -->
                                <p class="has-muted-color has-text-color has-xs-font-size">Чем точнее вводные, тем меньше шаманства с бубном и тем быстрее находится причина.</p>
                                <!-- /wp:paragraph -->
                        </div>
                        <!-- /wp:group -->
                </div>
                <!-- /wp:column -->
        </div>
        <!-- /wp:columns -->
</div>
<!-- /wp:group -->

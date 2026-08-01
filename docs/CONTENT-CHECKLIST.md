# Чеклист контента (post / project / arkai-portfolio)

Чтобы новые материалы не откатывали качество pass2.

## Перед публикацией

1. **Язык** - живой русский, без канцелярита и ИИ-штампов.
2. **Тире** - запрещены em/en dash (`—` `–`). Только обычный дефис `-` или запятые.
3. **Gutenberg** - блоки `<!-- wp:... -->`, не «классический» HTML целиком.
4. **Код** - через Enlighter (`EnlighterJSRAW` / freeform / html-блок), не голый `<pre>` без подсветки, если это сниппеты.
5. **Slug и дата** - не менять у уже опубликованных; у новых - осмысленный slug латиницей.
6. **Обложка** - `_thumbnail_id` обязателен; если пусто, fallback в контент-процессе: `10519`.
7. **Meta SEO** - `_genesis_description` (The SEO Framework), до **160** символов, без `—`/`–`, по смыслу статьи.
8. **Соцплагины** - при CLI-update всегда:
   ```
   --skip-plugins=wp-linkedin-auto-publish,wptelegram,share-on-mastodon,max-autopost
   ```
   Иначе уйдёт повторный автопостинг.

## CPT

| Тип в админке | `post_type` | URL-префикс |
|---------------|-------------|-------------|
| Записи | `post` | `/slug/` |
| Проекты | `project` | `/project/slug/` |
| Портфолио | `arkai-portfolio` | `/portfolios/slug/` |

**Не** использовать `post_type=portfolio` - тип не зарегистрирован, страницы отдают 404.

## Блок «Источники и ссылки»

Только так (тема красит light/dark сама). **Без** `style=` на карточках:

```html
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Источники и ссылки</h3>
<!-- /wp:heading -->

<!-- wp:html -->
<div class="krv-source-cards">
<a class="krv-source-cards__item" href="https://example.com/" target="_blank" rel="noopener noreferrer">
<span>Заголовок источника</span>
<span>Короткое описание</span>
</a>
</div>
<!-- /wp:html -->
```

Нельзя:
- inline `background` / `border` / `display:grid` на сетке источников;
- «голый» grid без `class="krv-source-cards"`.

## CLI (эталон)

```bash
SKIP="--path=/var/www/krivoshein.site/htdocs --allow-root --skip-plugins=wp-linkedin-auto-publish,wptelegram,share-on-mastodon,max-autopost"

wp $SKIP post update ID /tmp/content.html --post_type=TYPE --post_title="..."
wp $SKIP post meta update ID _genesis_description "..."
# thumb if empty:
# wp $SKIP post meta update ID _thumbnail_id 10519
```

## Быстрая самопроверка

- [ ] нет `—` / `–` в HTML
- [ ] есть Gutenberg-комментарии
- [ ] meta ≤ 160
- [ ] thumb ≠ 0
- [ ] источники = `krv-source-cards` без inline style
- [ ] CPT = `post` | `project` | `arkai-portfolio`
- [ ] не гоняли update без `--skip-plugins=...` для соцсетей

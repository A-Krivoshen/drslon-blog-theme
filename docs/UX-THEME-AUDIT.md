# UX/UI аудит темы `drslon-blog-theme`

> Июнь 2026. Статус спринтов A–C.

## Вердикт

Тема сильна как **технический блог** (7.5/10), слабее как оболочка **коммерческого hub** (5/10 до спринтов). После A–C — **~8/10** для смешанного сайта (услуги + блог).

## Спринты (выполнено)

| Спринт | Версия | Что сделано |
|--------|--------|-------------|
| **A** | 0.2.1 | Accent `#5181fe`, CTA «Прайс», MAX, `/servisy/`, `drslon-plugin-page` |
| **B** | 0.2.2 | IA «Услуги», lean mobile header, компактный footer, `prefers-reduced-motion` |
| **C** | 0.3.0 | CSS → 7 components, blog shortcodes → плагин, эта документация |

## Остаётся (не срочно)

- Поиск в header (сейчас только sidebar)
- Спрятать переводчик на mobile
- Дальнейшая чистка дублей в `06-shell.css` (бывшие prod hotfixes)
- Полный перенос `legacy-compat.php` в плагин

## CSS после спринта C

```
assets/css/components/
├── 01-base.css          — header, sidebar base, early layout
├── 02-archive.css       — архивы, category columns
├── 03-blog-home.css     — featured slider, tiles, home editorial
├── 04-blog-sections.css — blog sections grid, animations
├── 05-single.css        — single post, nav cards, related
├── 06-shell.css         — footer, mobile hotfixes, legacy pages, lightbox
└── 07-header-sticky.css — fixed header, sprint A/B
```

`style.css` — только метаданные темы для WordPress.

## Шорткоды блога (в плагине)

Реализация: `drslon-site-core/includes/shortcodes/blog-shortcodes.php`

| Шорткод | Назначение |
|---------|------------|
| `[drslon_featured_post]` | Слайдер на `/blog/` |
| `[drslon_category_tiles]` | Плитки рубрик |
| `[drslon_blog_sections]` | Секции по категориям |
| `[drslon_post_nav_cards]` | Prev/next в single |
| `[drslon_related_posts]` | Похожие записи |
| `[drslon_post_extras]` | Обёртка `krv_render_post_extras` |
| `[drslon_reading_time]` | Время чтения |
| `[drslon_post_views]` | Просмотры |

Тема: `inc/legacy-shortcodes.php` — тонкий bridge к плагину.
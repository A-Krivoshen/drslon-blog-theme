# UX/UI аудит темы `drslon-blog-theme`

> Июнь 2026. Статус спринтов A–E.

## Вердикт

Тема сильна как **технический блог** (7.5/10), слабее как оболочка **коммерческого hub** (5/10 до спринтов). После A–E — **~9/10** для смешанного сайта (услуги + блог).

## Спринты (выполнено)

| Спринт | Версия | Что сделано |
|--------|--------|-------------|
| **A** | 0.2.1 | Accent `#5181fe`, CTA «Прайс», MAX, `/servisy/`, `drslon-plugin-page` |
| **B** | 0.2.2 | IA «Услуги», lean mobile header, компактный footer, `prefers-reduced-motion` |
| **C** | 0.3.0 | CSS → 7 components, blog shortcodes → плагин, эта документация |
| **D** | 0.3.2–0.3.3 | Footer chips, MAX/a11y parity, plugin `filemtime`, `01-base` cleanup |
| **E** | 0.4.0 | Conditional CSS, `08-footer`, contact shortcodes, plugin-bridge, header search, `featured-slider.js`, CSS dedup |

## Остаётся (не срочно)

- Дальнейшая чистка дублей в `06-shell.css` (mobile hotfixes, ~1600 строк)
- CSS bundle/minify для production (опционально)

## CSS после спринта E

```
assets/css/components/
├── 01-base.css          — header, sidebar base, early layout
├── 02-archive.css       — архивы, category columns
├── 03-blog-home.css     — featured slider, tiles, home editorial
├── 04-blog-sections.css — blog sections grid, animations
├── 05-single.css        — single post, nav cards, related
├── 06-shell.css         — mobile hotfixes, legacy pages, lightbox
├── 07-header-sticky.css — fixed header, CTA, header search
└── 08-footer.css        — footer columns, connect chips
```

`style.css` — только метаданные темы для WordPress.

## Шорткоды блога (в плагине)

Реализация: `drslon-site-core-main/includes/shortcodes/blog-shortcodes.php`

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
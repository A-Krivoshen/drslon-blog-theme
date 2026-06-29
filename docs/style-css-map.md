# Style.css Map (DrSlon Blog Theme)

> **v0.3.0:** стили разбиты на `assets/css/components/*.css`.  
> `style.css` — только заголовок темы для WordPress.  
> Ниже — исторический индекс монолита (номера строк до split).

## Файлы компонентов (актуально)

| Файл | Содержимое |
|------|------------|
| `01-base.css` | Header base, featured-post, layout grids, sidebar base |
| `02-archive.css` | Archive cards, category columns, archive polish |
| `03-blog-home.css` | Featured slider, category tiles, home editorial waves |
| `04-blog-sections.css` | Blog sections, tiles hotfixes, slider overlay, animations |
| `05-single.css` | Post nav cards, related posts |
| `06-shell.css` | Header/footer mobile, 404, legacy pages, lightbox |
| `07-header-sticky.css` | Fixed/sticky header, plugin-page shell, sprint A/B |

---

## Архив: индекс монолита (до v0.3.0)

Источник: audit-only разбор `style.css` (~6300 строк). Документ помогает быстро находить зоны ответственности и места повышенного риска.

## Индекс секций (диапазоны строк)

Формат: `Секция → строки → риск/комментарий`.

1. Theme header (метаданные темы)
   - `1–14` → низкий: только комментарий с метаданными.

2. Header (base)
   - `17–96` → средний: локальные `.drslon-*`, но есть связки с Gutenberg/WP блоками (`.wp-block-*`).

3. Blog home/list helpers + базовые карточки/листинги
   - `97–190` → средний: `.drslon-featured-post*`, `.drslon-list-*`.

4. Layout: blog/single grids
   - `191–214` → высокий: `display: grid !important`, влияет на раскладку и поведение колонок.

5. Single (base): hero/content
   - `215–231` → средний: базовые размеры/типографика `.drslon-single-*`.

6. Archive head (base)
   - `232–245` → низкий–средний: заголовок/описание архивов.

7. Sidebar (base)
   - `256–277` → низкий–средний: оформление заголовков и списков в сайдбаре.

8. Pagination (base)
   - `279–320` → низкий: локальный `.drslon-pagination`.

9. Responsive (early)
   - `321–373` → высокий: меняет поведение навигации/раскладок, есть `!important` и “sidebar-disabled” логика.

10. Sticky sidebar fixes (три слоя подряд)
   - `375–448` → очень высокий: серии `STRONG/ULTIMATE/FINAL`, `grid ↔ flex`, много `!important`, легко сломать раскладку на desktop.
   - Примечание: тут же есть правка `.wp-block-group` overflow `!important` для layout (`445–448`).

11. Archive/category/search layout (grid + cards v1)
   - `450–572` → средний: сетка архивов и базовые `.drslon-archive-card*`.
   - Mobile for this block: `558–572`.

12. Home editorial overrides (волна 1)
   - `573–903` → средний–высокий: крупные переопределения `.drslon-featured-post*`, `.drslon-category-tiles*`, `.drslon-home-post*`.

13. Archive/category columns from theme setting
   - `904–938` → средний: `.category-columns-*` с `grid-template-columns: … !important`.

14. Archive cards overrides (волна 2/3/4)
   - `939–1017` → средний: “archive cards on separate surface”, hover, изменения body/title/excerpt.
   - `1018–1099` → средний: “archive polish after card surface” + responsive.
   - `1100–1147` → средний: “final polish for archive cards”, min-heights.

15. Blog home editorial landing v2
   - `1148–1417` → средний–высокий: крупная волна переопределений для featured + tiles + feed.

16. Blog home editorial landing v3 (compact fix)
   - `1418–1599` → высокий: много `!important` и переопределений сетки/размеров.

17. Blog home editorial landing v4 (featured slider вводится, featured-post выключается)
   - `1595–2016` → высокий: `.drslon-featured-slider*` база + `display:none` для `.drslon-featured-post`.
   - В конце блока есть `@media (max-width: 900px)` начало (продолжение ниже).

18. Featured slider: controls/arrows/polish/final card design + text cleanup
   - `2018–2497` → очень высокий: многократные override одних и тех же селекторов (`__controls`, `__arrow`, `__card`, `__content`, `__media`).
   - Медиа-брейки в этом куске: `max-width: 900px`, `max-width: 640px`.

19. Sidebar behavior (final sticky column + reset sidebar inner positions)
   - `2499–2549` → очень высокий: “final sidebar behavior” с `position: sticky !important` и массовыми `position: static !important` для элементов сайдбара.
   - Влияет на `.drslon-archive-layout`, `.drslon-home-layout`, `.drslon-blog-layout`, `.drslon-single-layout`.

20. Blog sections on `/blog/` (sections grid / section cards / CTA)
   - `2551–2735` → средний: `.drslon-blog-sections*`, `.drslon-blog-section-card*`.
   - Responsive: `2714–2735`.

21. Popular tiles tweaks / multiple desktop “FINAL FIX/HOTFIX” waves
   - `2738–3200` → высокий: плотные override `.drslon-category-tiles*`, много `!important`, несколько последовательных “final/hotfix” блоков.

22. Featured slider desktop overlay variants + /blog overlay + global desktop hotfix
   - `3201–3542` → очень высокий: разные режимы для `.home`, `.blog`, `.home.blog` и общий desktop override.

23. FINAL OVERRIDE tail for blog home (featured hero + popular tiles + CTA)
   - `3543–4047` → очень высокий: “Clean final tail”, фактически задаёт конечную правду для `/blog/` обёрток.

24. Single: post nav cards + related posts
   - `4049–4250` → средний: `.drslon-post-nav-*`, `.drslon-related-post*`.

25. Header mobile polish + header socials/logo tweaks
   - `4251–4410` → средний–высокий: много `!important`, правит поведение в `@media (max-width: 781px)`.

26. Mobile featured tighten pass + mobile tidy passes (featured + tiles)
   - `4411–4537` → высокий: дополнительные хвостовые mobile override для featured/tiles.

27. Final mobile polish: archive + single + sidebar
   - `4538–4731` → высокий: большое количество `!important` для архивов/сингла/паддингов/радиусов.

28. Mobile fixes: home section cards + related cards
   - `4732–4788` → средний–высокий.

29. Featured slider arrows: mobile polish + hide controls + then re-enable (conflicting intent)
   - `4789–5026` → очень высокий: есть блок “hide featured slider arrows completely” (`4815–4824`), затем “hotfix: arrows visible” (`4918–4976`) и “restore mechanics” (`4977–5026`).
   - Это зона конфликтов по приоритету в хвосте.

30. 404 page
   - `5027–5123` → средний: локально `.drslon-404-*`, но есть hiding cookie revisit widgets через `body.error404 … !important`.

31. Single meta + extras
   - `5124–5166` → средний.

32. Sidebar polish final + correction
   - `5262–5345` → средний–высокий: два последовательных блока “final” и “correction”, второй активно использует `!important`.

33. Footer polish
   - `5346–5398` → низкий.

34. Mobile menu final polish + cookie revisit hiding during open menu
   - `5399–5517` → высокий: много `!important`, высокие `z-index`, `body:has(...)`.

35. Prod hotfixes: desktop header socials row + single nav cards override
   - `5518–5675` → средний–высокий: переопределяет ранее объявленные single nav cards и header.

36. Prod hotfix: mobile gutters and content width
   - `5676–5760` → очень высокий: влияет на `.wp-site-blocks`, `body.* main.wp-block-group`, размеры/паддинги контента, img/video/iframe max-width.

37. Prod hotfix: mobile legacy pages width + buttons
   - `5761–5856` → очень высокий: целится в `.wp-block-post-content`, `.wp-block-group`, `.wp-block-columns`, `.alignwide/.alignfull`, селекторы по инлайн-стилям `[style*="padding-…"]`.

38. Desktop lightbox
   - `5858–5974` → средний–высокий: fixed overlay, `html/body overflow`, `z-index: 999999`.

## Зоны ответственности (быстрые указатели)

1. Header
   - Base: `17–96`
   - Mobile/tablet/desktop hotfixes: `321–361`, `4251–4410`, `4825–4865`, `5167–5207`, `5520–5571`

2. Footer
   - `5346–5398`

3. Sidebar
   - Base: `256–277`, `861–874`
   - Sticky/layout fixes: `375–448`, `2499–2549`
   - Polish/corrections: `5262–5345`
   - Mobile polish: `4717–4731` (в составе `4538–4731`)

4. `/blog/` (лендинг/секции)
   - Sections/cards: `2551–2735`
   - Popular tiles heavy tail: `2738–3200` и “FINAL OVERRIDE” `3543–4047`

5. Featured slider
   - Base: `1601–2016`
   - Controls/final design/text cleanup: `2018–2497`
   - Desktop overlays (`.home`, `.blog`, wrappers): `3201–3542`, `3543–4047`
   - Mobile/tablet arrow conflicts + restore mechanics: `4789–5026`

6. Archive cards
   - Base: `473–552`
   - Override layers: `939–1147`
   - Mobile polish: `4538–4616`

7. Archive/category/search layout
   - Grid: `450–572`
   - Columns settings: `904–938`
   - Mobile gutters impacting archive/search/category: `5676–5708`

8. Single
   - Base: `203–231`
   - Nav/related: `4049–4250` (+ override `5573–5675`)
   - Mobile polish + gutters: `4538–4731`, `5676–5747`

9. Mobile/responsive (сквозные хвосты)
   - Ранние брейки: `321–373`, `558–572`, `875–903`, `927–938`
   - Основные “mobile tails”: `4411–5026`, `5399–5517`, `5676–5856`

10. Legacy pages
   - `5761–5856`

11. Lightbox
   - `5858–5974`

## Зоны повышенного риска (почему)

1. Sticky sidebar и переключение layout (grid/flex)
   - `375–448`, `2499–2549`
   - Причина: много `!important`, несколько последовательных “final” фиксов, затрагивает фундаментальную раскладку колонок.

2. Featured slider (много волн override)
   - `2018–2497`, `3201–4047`, `4789–5026`
   - Причина: один и тот же набор селекторов переопределяется в нескольких местах, разные режимы `.home`/`.blog`/обёртки, конфликты намерений по стрелкам.

3. Legacy/mobile hotfixes, затрагивающие Gutenberg глобально
   - `5676–5856`
   - Причина: селекторы уровня страницы (`body.* main.wp-block-group`, `.wp-block-post-content`, `.wp-block-columns`, `[style*="padding-"]`) и много `!important`.

4. Popular tiles (длинный хвост “FINAL FIX/HOTFIX”)
   - `2738–3200` + части `3543–4047`
   - Причина: тяжёлые переопределения и зависимость от обёрток.

## Повторяющиеся блоки (что встречается слоями)

1. `.drslon-featured-post*`
   - База: `97–167`
   - Редакционные override: `583–692`, `1156–1417`, `1418–1599`
   - Отключение: `1597–1599` (`display: none;`)

2. `.drslon-archive-card*`
   - База: `473–552`
   - Слой 2: `939–1017`
   - Слой 3: `1018–1099`
   - Слой 4: `1100–1147`
   - Мобайл: `4574–4609`

3. `.drslon-category-tiles*`
   - Несколько волн переопределений: `700–903`, `1280–1953`, `2705–3200`, `3543–4047`, `4484–4526`, `4866–4917`

4. Header tablet fix продублирован
   - `4825–4865` и `5167–5207` выглядят как одно и то же по назначению/брейкпоинтам.

## Что нельзя трогать без визуальной проверки (минимальный список)

1. Sticky sidebar/layout fixes
   - `375–448`, `2499–2549`
   - Проверять минимум: desktop `>=1100px`, страницы `home`, `blog`, `single`, `archive/category/search`.

2. Prod hotfix: mobile gutters + legacy pages
   - `5676–5856`
   - Проверять минимум: mobile `<=781px`, страницы `single`, `archive`, `category`, `search`, `page` (особенно legacy контент).

3. Featured slider хвост с конфликтами стрелок
   - `4789–5026`
   - Проверять минимум: mobile/tablet (480/640/781/800px), поведение controls (видимость/кликабельность/позиция).

4. Featured slider desktop overlay variants
   - `3201–4047`
   - Проверять минимум: `.home` и `.blog` на desktop.

5. Mobile menu block
   - `5399–5517`
   - Проверять минимум: открытие/закрытие меню, scroll, наложения (`z-index`) и `body:has(...)` поведение.

## Опасные “глобальные” селекторы (ориентиры)

Точки, где стиль трогает базовые контейнеры/блоки WP:

1. `.wp-site-blocks`
   - `5678–5680`

2. `body.* main.wp-block-group`
   - `5682–5690` (single/search/archive/category/error404)
   - `5763–5767` (page)

3. `.wp-block-post-content`
   - `5749–5759`

4. `.wp-block-group`
   - `445–448` (overflow visible внутри layout)
   - `5783–5798` (legacy container/layout)

5. `.wp-block-columns`
   - `356–373`, `896–899`, `5784–5802`

Примечание: глобальных “tag-only” селекторов `img {}`, `a {}`, `main {}`, `article {}` в текущем файле не обнаружено; они встречаются только в scoped-контексте (например `.drslon-… img`).

## Безопасные маленькие задачи на будущее (без рефакторинга)

1. Добавить оглавление-комментарий в начало `style.css` (только комментарии) с ссылкой на этот документ и ключевыми диапазонами.
2. Сверить и устранить дублирование header tablet fix (`4825–4865` vs `5167–5207`) без изменения поведения (после визуального сравнения на 600–900px).
3. Для `.drslon-archive-card` добавить по одному короткому комментарию над слоями `939+`, `1018+`, `1100+` (почему появился слой), чтобы не ломать приоритеты при точечных правках.
4. Для featured slider добавить краткую “priority note” (комментарий) рядом с блоками `4815–4824`, `4918–4976`, `4977–5026`, фиксируя намерение (hide vs show vs restore mechanics).
5. Для legacy/mobile hotfix (`5676–5856`) добавить предупреждающий комментарий “prod hotfix: validate on mobile page content widths” перед блоком.

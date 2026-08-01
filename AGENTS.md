# AGENTS.md

This repository contains a WordPress block theme named DrSlon Blog Theme.

Use block theme architecture first:
- theme.json
- templates
- parts
- patterns

Rules:
- keep code simple
- avoid unnecessary abstractions
- avoid heavy dependencies
- prefer content-first design
- use PHP only when it adds real value

Content (post / project / arkai-portfolio):
- follow `docs/CONTENT-CHECKLIST.md` (no em/en dashes, Gutenberg, krv-source-cards, genesis ≤160, skip social plugins on CLI updates)
- portfolio CPT slug is `arkai-portfolio` (URL `/portfolios/...`), not `portfolio`

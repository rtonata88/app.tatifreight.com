---
name: nexus-design
description: Use this skill to generate well-branded interfaces and assets for Nexus, the Namibian consulting-practice ERP (projects, finance, payroll, clients, vendors, people), either for production React + shadcn code or throwaway prototypes, mocks and documents. Contains the ink/brass/paper token system, type, component library and UI kits.
user-invocable: true
---

Read the `readme.md` file within this skill, and explore the other available files.

Start with `readme.md` (direction, content fundamentals, visual foundations, iconography, index),
then `styles.css` and `tokens/` for the exact values, then `components/*/\*.prompt.md` for what
each component is and when to use it, then `ui_kits/` for how real Nexus screens are composed.

If creating visual artifacts (mocks, throwaway prototypes, printed documents), copy the token
CSS out and write static HTML that links it — `class="nx-ink"` for application surfaces,
`class="nx-paper"` for anything printed or exported. If working on production code, `tokens/shadcn.css`
maps the system onto shadcn/ui variables, so unmodified shadcn primitives render in the Nexus
register; use the components here for the patterns shadcn has no opinion about (status pills,
index tables, dossier rows, form summary sidebars).

Non-negotiables: one brass accent per region; hairlines instead of shadows; sentence case;
bare inputs with uppercase tracked labels; tabular mono for money and references; no emoji;
no illustrations; never animate transform.

If the user invokes this skill without any other guidance, ask them what they want to build or
design, ask some questions, and act as an expert designer who outputs HTML artifacts *or*
production code, depending on the need.

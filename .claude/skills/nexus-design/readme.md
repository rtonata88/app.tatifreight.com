# Nexus Design System

> A ledger-grade design system for **Nexus** — the Laravel business-management platform at
> [`rtonata88/nexus`](https://github.com/rtonata88/nexus) — recast for **React + shadcn/ui**.
>
> **Direction:** the practice's own register — slate on off-white, one blue accent, Public Sans,
> soft-shadow cards. Dense enough for a 200-row table, legible enough to read all day.
> Ships three modes: **Slate** (light, the default), **Ink** (dark), **Paper** (printed documents).

---

## What Nexus is

Nexus is the operational and financial system of record for a **Namibian professional-services
practice** (consulting engineering: projects, disciplines, fee scales, site trips, timesheets).
NAD currency, +264 phone numbers, Windhoek / Walvis Bay / Oshakati offices.

Read from the repository, the modules are:

| Module | Surfaces |
|---|---|
| **Dashboards** | Analytics, Finance, Projects, Personnel |
| **Client management** | Clients, quotations, invoices, payments, feedback surveys |
| **Vendor management** | Vendor directory, purchase orders, vendor invoices, vendor payments |
| **Finance** | Bank accounts, transactions, transfers, reconciliation, expenses, other income, budgets, financial reports |
| **Projects** | Projects, stages, project personnel, timesheets, project trip requests |
| **People** | Personnel dossiers, documents, notes, leave types, leave applications and requests |
| **Payroll** | Periods, runs, payslips, reports, tax brackets, allowances, deductions, medical aid, pension |
| **Self-service** | Start menu, my projects, my timesheets, trip requests, leave applications |
| **Admin** | Users, roles and permissions, company information, system preferences, list management |

Four screen archetypes carry almost all 400+ Blade views, and the whole system is built to
serve them: **index table**, **record dossier**, **create/edit form with a summary sidebar**,
and **dashboard**. A fifth, the **printed document** (payslip, invoice, statement), is the
reason Paper mode exists.

## Sources

- **Repository:** <https://github.com/rtonata88/nexus> — branch `main`. Read, not copied: Blade
  layouts (`resources/views/layouts/*`), the sidebar IA (`layouts/sidebar.blade.php`), index and
  show views across finance / payroll / personnel / quotations, `dashboard/analytic.blade.php`,
  and the app's own `docs/design-system-prd.md`.
- **Prior in-app design system:** `docs/design-system-prd.md` describes the light Bootstrap
  `ep-*` layer (DM Sans, `#4e73df` blue, soft shadows, icon tiles). **Its component inventory is
  the inventory of this system** — every `ep-*` pattern has a React counterpart here.
- **Visual register:** the boutique ink/brass/paper direction attached to this project as the
  house style. Structure and content came from Nexus; the register did not.
- **Stack target:** React + Tailwind + shadcn/ui, replacing Blade + Alpine + Bootstrap 5 +
  Yajra DataTables.

### A note on where the palette came from

The first pass of this system was skinned in an unrelated house style (near-black + brass,
Archivo/Manrope, editorial italic) that had been attached to the project. **That has been
corrected.** Nexus is a professional ERP for an engineering practice, and its palette and type
now come from the app's own `docs/design-system-prd.md`:

| Token | Value | Source |
|---|---|---|
| Page | `#f8f9fa` | `--ep-bg-page` |
| Card | `#ffffff` | `--ep-bg-card` |
| Accent | `#4e73df` | `--ep-primary` |
| Text | `#2d3748` / `#6c757d` / `#a0aec0` | `--ep-text-*` |
| State | `#28a745` / `#b45309` / `#dc3545` / `#17a2b8` | `--ep-success/warning/danger/info` |
| Body face | DM Sans | `--ep-font-body` |
| Card lift | `0 .125rem .25rem rgba(0,0,0,.075)` | `--ep-shadow` |

What the system adds on top of those values is discipline, not a new palette: one accent per
region, tabular mono for money and references, a single status-pill form, bare hairline inputs,
the dossier row, 44px table rows, and no motion that moves anything. **Ink** (dark) is kept as a
genuine alternative for night shifts and site laptops, and **Paper** is what printed documents
render in.

---

## Content fundamentals

**Voice.** Plain, regulatory, second-person. The system addresses the user as "you" and states
facts rather than selling them. It says "Payroll period is closed. Adjustments will post to
March," not "Oops! This period is locked 🔒".

**Casing.** Sentence case everywhere — headings ("Record an expense", "Leave applications"),
buttons ("Submit for approval", "Update status", "Download PDF"), labels, tabs. Never Title
Case. Acronyms stay capitalised: PAYE, VAT, NAD, ECN, KYC, PDF, SSC.

**Buttons are verbs.** "New quotation", "Submit for approval", "Send reset link", "Reopen
period". Never "OK", never "Submit" alone.

**Copy specimens.**
- Page subtitle: *"Create and track client quotations through to project handover."*
- Empty state: *"No payslips yet. Payslips appear here once a payroll run is marked as paid."*
- Warning: *"Subsistence for NX-0231 has NAD 18,400 of NAD 200,000 remaining this stage."*
- Toast: *"Quotation QT-2026-0184 approved. Project NX-0231 created."*
- Error: *"Those details do not match our records. Five failed attempts locks the account for
  15 minutes."*

**Editorial italic is a voice, not decoration.** One-line page subtitles and dossier keys
("Submitted by", "Verification level") are set in italic display type. Nothing else is italic.

**Status taxonomy.** Active · Approved · Paid · Pending · Draft · Submitted · Completed ·
Declined · Overdue · Inactive. Rendered UPPERCASE, `0.08em` tracking, dot-led, in a hairline
pill that takes the state's colour. Counts and taxonomies never use a pill — they use a badge.

**Numbers.** `NAD 68,400.00` — currency prefix, thousands separators, two decimals, tabular
mono, right-aligned in tables. Headline figures abbreviate: `NAD 4.18m`. References are
uppercase mono and hyphenated: `QT-2026-0184`, `INV-2026-0117`, `EXP-2026-0442`, `TWY-0142`.
Phone numbers partially masked: `+264 81 ••• 4422`. Bank accounts: `Bank Windhoek ••• 8021`.
Dates: `14 Feb 2026` in prose, `09 Feb 2026` mono in cells. Empty values are an em dash — never
blank, never "N/A".

**No emoji. No exclamation marks. No marketing flourish.** Compliance is stated, not softened.

---

## Visual foundations

**The defining choice.** An off-white page (`#f8f9fa`), white cards, slate text, and a single
blue accent (`#4e73df`) — the practice's own colours. **The system has one accent.** Every other
colour is state (success, warning, danger, info) and never hierarchy or decoration.

**Surfaces:** Page `#f8f9fa` → Elevated `#f1f3f5` (table heads, section fills) → Card `#ffffff`,
with `#f1f3f5` again as the table row hover. In Ink mode the same ladder runs dark: Rail
`#0A0907` → Page `#0E0D0B` → Elevated `#15130F` → Card `#1A1814`.

**Type.** **Public Sans** — a civic grotesque, sober and slightly stiff, which is the right
register for regulated infrastructure work — carries display and UI alike: 700 at -0.015em for
page titles, 600 for card titles and buttons, 400/500 for body and cells. **Archivo Narrow**
(700) carries headline figures, where condensed tabular numerals let `NAD 4,182,940` sit in a
metric card without shrinking. **Roboto Mono** carries references, money, dates and hours. There
is no fourth face, and nothing is set in italic. 13px is the working body size; 11px is the label
floor; 20px+ is display territory.

*Chosen against IBM Plex Sans and Source Sans 3 on a live invoices screen — the candidates are
kept in `guidelines/type-a-plex.html` and `type-b-source.html` if the call is ever revisited.*

**Rules and lift.** Structure comes from 1px rules — standard 8%, strong 14%, accent 32% (used
once per screen) — and from the app's own soft card shadow, `--nx-lift-0`
(`0 .125rem .25rem rgba(0,0,0,.075)`), lifting to `--nx-lift-1` on hover. `--nx-lift-brass`
marks the single most important card. Overlays get `0 .5rem 1.5rem rgba(0,0,0,.18)`. In Ink mode
every one of those tokens resolves to a hairline ring instead, so the same component reads
correctly in both.

**Cards.** 8px radius, 1px border, white fill, 24px padding (32px on dossiers and documents),
soft shadow. Header rule only when there is a body. The emphasis variant switches to the accent
border and adds a faint wash from the top right. Tables sit in cards with the body padding
removed so rows run edge to edge.

**Inputs are bare.** No box, no fill. Label above — 11px, 600, `0.18em` tracking, muted. One
hairline beneath. Focus turns that hairline brass; errors turn it oxblood and move the message
into the hint slot. Typography does the framing, which is what keeps a 12-field expense form
from looking like a wall of boxes.

**Buttons.** Three weights and one destructive: **primary** (solid `#4e73df`, white text — one
per region), **ghost** (hairline, accent on hover — the workhorse), **text** (inline links,
"View all →"), **danger** (danger outline). 36px default, 28px in table toolbars, 44px in auth
and empty states.

**Radii.** `0 / 2 / 4 / 6 / 8 / 12 / pill`. Flat is the default; radius is a softening. Controls
6, cards and inputs 8, dialogs 12, status pills full.

**Spacing.** `4 · 8 · 12 · 16 · 20 · 24 · 32 · 40 · 56 · 80`. Card padding 24, page gutter 32,
section break 32, **table row 36** (30 dense), control height 36. The row height is deliberately
tight — this is an app where ops staff scan hundreds of records, and rows per screen beat
comfort. The consequence: table cells do not wrap. Long values are truncated or given their own
column, never allowed to reflow a row.

**Configuration is cardless.** Settings panes do not use cards: a 220px `SettingsNav` rail on the
left, and on the right a stack of `SettingsRow`s separated by hairlines — setting name and its
consequence on the left of each row, control on the right. Cards were the wrong container there;
they add a border and 24px of padding around every single field and turn one long list of
decisions into a grid of boxes with no reading order. The rail is where the fifteen managed lists
finally become findable, with their record counts alongside.

**Layout rules.** A fixed 248px rail (darkest surface) and a fixed 56px topbar; only the page
body scrolls. Body content is capped at 1440px and centred. Forms use a two-column grid with a
**sticky summary sidebar** — the pattern the Blade app already uses on expenses. Printed
documents are a 760px paper column.

**Motion.** 220ms on `cubic-bezier(0.32, 0.72, 0.24, 1)`. Only `background`, `border-color`,
`color` and `box-shadow` animate. **Never transform, never scale, never bounce.** Buttons
darken; card borders strengthen; progress bars grow at 420ms. Nothing shifts position, because
in a dense table app movement reads as breakage.

**Hover and press.** Hover = colour change only (ghost buttons take a brass border; table rows
take the `#201D18` hover surface; cards strengthen their hairline). Press = the darker brass
step (`#8A6826`), no displacement. Focus = a 1px brass ring, never a soft glow halo.

**Transparency and blur.** Alpha is used for hairlines and the four state washes at 12%, and for
the modal scrim (`rgba(6,5,4,.72)`). **No backdrop blur anywhere** — it costs legibility over
dense tables and buys nothing.

**Charts.** Six ordered series, brass first, then moss, slate, amber, oxblood, muted. No fills
under lines beyond a 12% wash, no gridline clutter, no drop shadows. Axis labels in mono.

**Imagery.** None ships, and none is needed — type, colour and space carry the design. If
imagery is added later it should be photographic, warm-graded, low-saturation, on ink with
generous margin. **No illustrations, no gradients as decoration, no mascots, no grain, no
stock-photo people.**

**Three modes, one class.** `.nx-light` (Slate) is the default and what staff use all day.
`.nx-ink` is the dark alternative — same tokens, same components, one class swap; the UI kit has
a switch in its topbar so you can compare. `.nx-paper` is the document mode: warm paper stock,
ink type, dotted rules — payslips, invoices and statements always render in it.

---

## Iconography

The Nexus repo ships **no icon set of its own** — the Blade views use Font Awesome 4 classes
(`fa fa-users`, `fa fa-money`, `fa fa-clock-o`) inherited from the Riho admin template, plus a
Feather SVG sprite from the same template. Neither is a Nexus asset, and neither survives a move
to React.

**Sanctioned set: [Lucide](https://lucide.dev) (`lucide-react`).** Stroke only, 1.6px,
rounded caps, 16px nominal (14px in table row actions, 15px in buttons, 22–32px in empty
states and upload zones). Icons inherit `currentColor` and never carry their own colour except
brass on a card header.

**How the kits load it.** `ui_kits/nexus-erp/index.html` loads Lucide's UMD build from CDN
(`unpkg.com/lucide@0.454.0`), and `Shell.jsx` exports a thin `Icon` wrapper that looks the icon
node up in `lucide.icons` and renders it — **real Lucide geometry, nothing traced by hand**. The
wrapper only maps semantic names to Lucide names (`grid` → `LayoutGrid`, `money` → `Banknote`,
`edit` → `PenLine`, …). **In production, install `lucide-react`, import the components directly,
and delete the wrapper.**

**The app's current sprite is preserved for reference.** `assets/icons/icon-sprite.svg` is the
repo's own `public/assets/svg/icon-sprite.svg` — the Feather-derived sprite that
`layouts/sidebar.blade.php` references as `icon-sprite.svg#stroke-home` / `#fill-home`. It ships
here so the port can diff old glyph against new, and because the Blade app still needs it during
a phased migration. It is a **template asset, not a Nexus one** — do not extend it; add new icons
from Lucide.

**Rules.** No emoji. No unicode characters as icons. No filled variants. No icon-only buttons
without a `label`. No decorative icons — the Blade app's coloured icon tiles in page headers and
metric cards were dropped on purpose: the label carries the meaning, and a 48px blue chip in
every header is 48px of noise repeated 400 times.

**Brand marks.** Nexus has no logo. `assets/legacy-template/` holds the three Riho template
PNGs found in the repo, kept only as evidence of what is currently rendered — **they are not a
Nexus brand and must not be used.** Everywhere a mark would go, the system sets the word
"Nexus" in Archivo 800, or an `NX` monogram in a hairline frame with a brass rule. Send a real
wordmark and both are replaced in one place.

---

## Moving from Blade to React + shadcn

`tokens/shadcn.css` is the migration seam. It maps every shadcn/ui variable
(`--background`, `--foreground`, `--card`, `--primary`, `--muted`, `--accent`, `--border`,
`--input`, `--ring`, `--radius`, `--destructive`, `--sidebar-*`, `--chart-1…5`) onto Nexus
tokens, under both `.nx-ink` and `.nx-paper`. Set `<html class="nx-ink">`, point Tailwind's
theme at the same variables, and unmodified shadcn primitives come out in the Nexus register.

Then reach for the components in this system rather than reinventing them — they encode the
decisions shadcn has no opinion about: what a status pill looks like, how money is aligned, what
an index table's action column contains, where a form's summary sidebar goes.

A rough correspondence for the port:

| Blade / Bootstrap today | Here |
|---|---|
| `ep-page-header` + icon tile | `PageHeader` (no icon tile) |
| `ep-metric-card`, `ep-quick-stat` | `MetricCard`, `StatCard` |
| Yajra DataTables markup | `DataTable` + `Pagination` |
| `ep-data-grid`, `ep-data-row` | `DataGrid`, `DossierRow` |
| `ep-badge` (status vs count) | `StatusPill` / `Badge` |
| `ep-btn`, `ep-action-btn` | `Button`, `IconButton` |
| `ep-toggle`, `ep-type-pill` | `Switch`, `RadioPills` |
| `ep-alert`, toastr flash | `Alert`, `Toast` |
| Bootstrap modal | `Modal` |
| `ep-tabs` nav-pills | `Tabs` |
| Settings cards | `SettingsNav` + `SettingsRow` (cardless) |
| `ep-empty-state` | `EmptyState` |
| `ep-leave-progress` | `ProgressMeter` |
| `layouts/sidebar` + `layouts/header` | `Sidebar`, `Topbar`, `Breadcrumb` |

---

## Index

| Path | What it is |
|---|---|
| `styles.css` | The one file consumers link. `@import`s only. |
| `tokens/fonts.css` | Google Fonts import for the four families |
| `tokens/colors.css` | Ink, brass, paper, text, hairlines, state, chart series |
| `tokens/typography.css` | Families, size scale, line heights, tracking |
| `tokens/spacing.css` | Spacing scale, card/gutter/row/control metrics, layout widths |
| `tokens/elevation.css` | Radii, hairline lift, motion, focus ring |
| `tokens/shadcn.css` | shadcn/ui variable mapping for `.nx-ink` and `.nx-paper` |
| `tokens/theme-light.css` | **Slate — the default theme.** The app's own palette, type and shadows |
| `tokens/primitives.css` | Type-role, rule, focus and print classes (`nx-h2`, `nx-money`, `nx-rule-brass`, …) |
| `guidelines/` | 19 foundation specimen cards (Colors, Type, Spacing, Brand) |
| `components/core/` | `Button`, `IconButton`, `StatusPill`, `Badge`, `Card`, `Avatar` |
| `components/forms/` | `FormField`, `Input`, `Select`, `Textarea`, `Checkbox`, `Switch`, `RadioPills` |
| `components/data/` | `MetricCard`, `StatCard`, `DataTable`, `DataGrid`, `DossierRow`, `SettingsRow`, `ProgressMeter`, `EmptyState`, `Pagination` |
| `components/navigation/` | `Sidebar`, `Topbar`, `PageHeader`, `SectionHeader`, `Tabs`, `Breadcrumb`, `SettingsNav` |
| `components/feedback/` | `Alert`, `Modal`, `Toast`, `ActivityList` |
| `ui_kits/nexus-erp/` | App kit: shell + dashboard, index, dossier, form, payslip, settings |
| `ui_kits/auth/` | Sign in, password reset, access request |
| `assets/icons/` | The repo's own icon sprite, kept for reference during the port |
| `assets/legacy-template/` | The repo's inherited template logos — reference only, not brand |
| `ds-shim.js` | Dev-only loader so the cards and kits render before the bundle is compiled |
| `github.md` | Repo association and sync record |
| `SKILL.md` | Agent-skill manifest |

Every component directory holds `<Name>.jsx`, `<Name>.d.ts` (props contract) and
`<Name>.prompt.md` (what and when, with a usage example), plus one preview card per directory.

### Intentional additions

Three components have no direct `ep-*` counterpart and were added because the React port needs
them: **`Breadcrumb`** (the Blade layout has a breadcrumb `<ol>` but no styled component),
**`Toast`** (replaces the `toastr` jQuery dependency), and **`Icon`** (a thin Lucide wrapper in
the UI kit, see Iconography). Nothing else was invented.

`Switch` and `RadioPills` are **not** additions — they are the React counterparts of
`ep-toggle` and `ep-type-pill`, both defined in `partials/design-system-forms` and used on the
expense create form ("Amount includes VAT (15%)"; "Draft · save for later" vs "Submit · for
approval").

---

## Caveats and open asks

1. **No logo.** The single biggest gap. Everything renders the word "Nexus" in Archivo. **Send a
   wordmark plus a monogram in light and dark, and I will wire them into the rail, the topbar,
   the auth panel and every printed document.**
2. **Fonts load from Google's CDN.** Public Sans, Archivo Narrow and Roboto Mono are all free and
   open-licensed. **If you would rather not depend on the CDN, say so and I will self-host the
   `.woff2` files.**
3. **~~The register is borrowed.~~ Fixed.** The palette and type now come from the app's own
   `docs/design-system-prd.md`; Slate (light) is the default, Ink is an alternative.
4. **Real names and numbers are placeholders.** Clients (Roads Authority, NamWater, City of
   Windhoek), projects (NX-0231), people and every figure are invented, though the formats are
   real. **Send a handful of real client, project and employee names and I will reskin the kits
   with them.**

   Where a field set could be grounded, it was: the Settings panes use the actual
   `company_information` columns and the actual seeded `expense_categories` rows, and the auth
   screens use the field sets in `routes/auth.php`. **Two things are still layout-only, and I have
   marked them as such in `github.md`: the Settings Blade views and the auth Blade views were not
   read, so those two screens are our layout over real data shapes, not recreations.** Say the
   word and I will read them and reconcile.
5. **Modules not yet drawn.** Banking and reconciliation, budgets, financial reports, purchase
   orders, timesheet capture, the start-menu launcher, and roles/permissions. The archetypes
   cover them, but no screen exists yet. **Tell me which three matter most.** Within Settings,
   seven panes are built (Company information, Addresses, Branding, Financial year, Invoice and
   quotation defaults, Terms and conditions, Expense categories) — enough to fix the three shapes
   a pane takes: a form, a block of document text, and a managed list. The rest say so plainly.
6. **Charts are CSS, not a library.** The kit draws bars in CSS. Production should use one
   charting library across all four dashboards — say which (Recharts is the natural fit with
   shadcn) and I will pin the series tokens to it.
7. **No 2FA screen.** The repo's auth routes have none. Say the word if it is on the roadmap.
8. **Accessibility partly audited.** Paper-mode labels were failing 4.5:1 and are now pinned to
   `--nx-pfg-2`. Still outstanding: the state colours on their 12% washes, and `--nx-fg-3` on
   card surfaces. **Both need a proper audit before launch.**

> To share this system with your organisation, set the file type to **Design System** in the
> Share menu.

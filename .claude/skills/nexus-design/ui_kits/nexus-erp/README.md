# Nexus ERP — UI kit

A click-through recreation of the Nexus application shell and its four recurring screen
archetypes, rebuilt in the design system's ink/brass/paper register. It composes the
published components; nothing is re-implemented locally except the icon set and the shell.

Open `index.html`.

## Screens

| File | Stands in for | Archetype |
|---|---|---|
| `Shell.jsx` | `layouts/master.blade.php` + `layouts/sidebar.blade.php` + `layouts/header.blade.php` | App chrome: module rail, topbar, breadcrumb, scrolling body |
| `DashboardScreen.jsx` | `dashboard/analytic.blade.php` | Metric row, cash-flow chart, performance meters, activity timeline |
| `QuotationsScreen.jsx` | `quotations/index.blade.php` (+ its status modal) | Index table: tabs, DataTable, row actions, pagination, modal |
| `EmployeeScreen.jsx` | `personnel/show.blade.php` | Record dossier: fact grid, tabbed sub-records, entitlement meters |
| `ExpenseFormScreen.jsx` | `finance/expenses/create.blade.php` | Create form with a sticky summary sidebar |
| `PayslipScreen.jsx` | `payroll/payslips/show.blade.php` | Printed document on paper stock inside the app |
| `SettingsScreen.jsx` | `settings/company-information`, `settings/list-management`, `layouts/sidebar.blade.php` | Cardless settings: sub-rail + hairline rows |

`Shell.jsx` also exports `Icon` — a small stroke-icon set standing in for Lucide, which the
production app should install instead (see readme.md > Iconography).

## What is faithful, and what is not

- **Faithful:** the rail's module structure and submenu depth, the index-table column
  vocabulary (mono references, right-aligned money, trailing action column), the sticky
  form sidebar, the payslip's earnings/deductions/net structure, the NAD number formats.
- **Simplified:** the cash-flow chart is CSS bars, not Chart.js; tables show 3-6 rows in
  place of paginated hundreds; permissions, validation and persistence are absent.
- **Redesigned, deliberately:** the visual register. The Blade app is light Bootstrap with
  a blue accent and icon tiles; this kit is the boutique ink/brass direction. Structure and
  data were kept, styling was not.

## Interactions worth clicking

- Rail navigation between all five screens, with submenu disclosure.
- Quotations: status tab filters, the empty state on an unfilled filter, the row-level
  "Update status" modal, and the toast it fires.
- Employee: the four sub-record tabs, including the empty Notes state.
- Expense form: live VAT recalculation, quick-amount chips, submit fires a toast.
- Settings: the sub-rail switches panes; three are built (Company information, Expense categories,
  Numbering and references), the rest state plainly that they are stubs.
- Topbar: the Light/Dark switch flips the whole system between Slate and Ink.

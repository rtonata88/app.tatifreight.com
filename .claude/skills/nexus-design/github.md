repo: rtonata88/nexus
branch: main

## Last sync

date: 2026-09-05T17:58:00Z

### Updated in this project

- Built the Nexus Design System from the repo: tokens, 23 components, 2 UI kits, 19 specimen cards.
- Component inventory taken from `docs/design-system-prd.md` (the app's `ep-*` layer) and the views that use it.
- Navigation IA lifted verbatim from `resources/views/layouts/sidebar.blade.php`.
- Added `tokens/shadcn.css` as the Blade to React + shadcn migration seam.
- Copied `public/assets/svg/icon-sprite.svg` into `assets/icons/`; kits now render real Lucide geometry.
- `Switch` and `RadioPills` added from `ep-toggle` / `ep-type-pill` in the forms partial.
- Re-grounded palette in the app's own `--ep-*` tokens; Slate (light) is now the default, Ink the alternative.
- Type set to Public Sans + Archivo Narrow + Roboto Mono; table rows tightened to 36px.
- Settings rebuilt cardless on a new `SettingsNav` + `SettingsRow` pair, fields taken from the real schema.
- Auth register corrected to the Breeze field set (name/email/password/confirm + email verification).

## Screen map

| Screen | Built from |
|---|---|
| `ui_kits/nexus-erp/Shell.jsx` | `resources/views/layouts/master.blade.php`, `layouts/sidebar.blade.php`, `layouts/header.blade.php` |
| `ui_kits/nexus-erp/DashboardScreen.jsx` | `resources/views/dashboard/analytic.blade.php` |
| `ui_kits/nexus-erp/QuotationsScreen.jsx` | `resources/views/quotations/index.blade.php` |
| `ui_kits/nexus-erp/EmployeeScreen.jsx` | `resources/views/personnel/show.blade.php` (read in full), `personnel/index.blade.php` |
| `ui_kits/nexus-erp/ExpenseFormScreen.jsx` | `resources/views/finance/expenses/create.blade.php` (read in full, incl. its VAT calculation JS) |
| `ui_kits/nexus-erp/PayslipScreen.jsx` | `resources/views/payroll/payslips/show.blade.php` (structure), `payroll/payslips/index.blade.php` |
| `ui_kits/nexus-erp/SettingsScreen.jsx` | `database/migrations/2025_04_29_145321_create_company_information_table.php` + `app/Models/CompanyInformation.php` (field set), `2025_04_30_153114_create_expense_categories_table.php` + `2025_01_22_100001_add_is_capital_expense_to_expense_categories.php` (columns and seed rows), `layouts/sidebar.blade.php` (nav groups). **The Blade settings views themselves are unread — layout is ours, fields are the schema's.** |
| `ui_kits/auth/AuthScreens.jsx` | `routes/auth.php` (flows and field sets — standard Laravel Breeze). **The auth Blade views are unread; the split-panel layout is ours, not a recreation.** |
| `components/*` | `docs/design-system-prd.md` (the `ep-*` component inventory) |

## Sync history

None — this is the first sync.

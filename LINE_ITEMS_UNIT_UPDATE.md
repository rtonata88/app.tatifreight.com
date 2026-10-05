# Quote & Invoice Line Items - Unit Field Update

## Overview
Replaced the `type` field with `unit` field in quote_line_items and invoice_line_items tables to better represent transport/logistics billing units.

## Changes Made

### Database Migrations
- **`2025_11_03_202454_change_type_to_unit_in_quote_line_items_table.php`**: Removed `type` enum, added `unit` enum
- **`2025_11_03_202455_change_type_to_unit_in_invoice_line_items_table.php`**: Removed `type` enum, added `unit` enum

### Available Units (Transport/Logistics Industry)
1. **trip** - Single trip/journey (default)
2. **day** - Per day rental
3. **hour** - Per hour rental
4. **km** - Per kilometer
5. **tonne** - Per tonne of cargo
6. **load** - Per load
7. **pallet** - Per pallet
8. **container** - Per container (20ft/40ft)
9. **cbm** - Cubic meter
10. **item** - Per item
11. **week** - Weekly rental
12. **month** - Monthly rental

### Models Updated
- `app/Models/QuoteLineItem.php` - Updated fillable field from `type` to `unit`
- `app/Models/InvoiceLineItem.php` - Updated fillable field from `type` to `unit`

### Livewire Components Updated
- `resources/views/livewire/quotes/create.blade.php`
  - Replaced "Type" dropdown with "Unit" dropdown
  - Made vehicle selection optional
  - **Converted to Excel-style table layout** with borders and inline editing
  - All fields now edit directly in the table (no separate cards per line item)
  - Added hover effect on rows for better UX
  - Delete icon button in each row
  
- `resources/views/livewire/quotes/edit.blade.php`
  - Replaced "Type" dropdown with "Unit" dropdown
  - Made vehicle selection optional
  - **Converted to Excel-style table layout** with borders and inline editing
  - Updated convertToBooking logic to find first line item with vehicle
  
- `resources/views/livewire/quotes/index.blade.php`
  - Updated duplicate quote logic to copy `unit` instead of `type`
  - Updated convertToBooking logic to find first line item with vehicle

### UI/UX Improvements
- **Excel-like spreadsheet interface** for line items:
  - Table with visible borders (like Excel cells)
  - Inline editing (no separate cards)
  - Row numbers in first column
  - Hover effect on rows
  - Compact layout for better overview
  - Direct input/select controls in cells
  - Delete icon button in last column

### PDF Template Updated
- `resources/views/pdf/quote.blade.php`
  - Removed "Type" column
  - Added "Unit" column showing the billing unit (Trip, Day, Hour, etc.)
  - Adjusted column widths for better layout

## Impact
- **Old behavior**: Line items were categorized as "rental", "mdc", "extra", or "discount"
- **New behavior**: Line items specify billing unit (trip, day, hour, km, tonne, etc.)
- **Benefit**: More flexible and accurate billing for transport/logistics services

## Migration Applied
✅ Migrations have been successfully applied to the database.

## Next Steps (if needed)
- Invoice line items can be similarly updated when invoicing module is actively used
- Consider adding custom unit types if needed for specific business cases

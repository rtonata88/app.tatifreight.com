# Invoice Create Page Updates

## Changes Applied

### 1. Excel-Style Table Layout
Converted the invoice line items from individual cards to an Excel-style table matching the quotes format:

**Features:**
- Table with visible borders (like Excel cells)
- Column headers: #, Vehicle, Description, Unit, Qty, Unit Price, Amount, Delete
- Inline editing in table cells
- Row numbers in first column (gray background)
- Hover effect on rows (`hover:bg-gray-50`)
- Compact layout for better overview
- Delete icon button in last column

### 2. Replaced "Type" with "Unit"
Updated from service categorization to billing units:

**Old Values:**
- service
- rental
- mdc
- extra

**New Values (12 transport/logistics units):**
- trip (default)
- day
- hour
- km
- tonne
- load
- pallet
- container
- cbm
- item
- week
- month

### 3. Currency Already Using N$
The invoice already uses N$ (Namibian Dollar) throughout:
- Unit Price column
- Amount column
- Totals section (Subtotal, VAT, Total, Amount Due)

## Files Modified
- `resources/views/livewire/invoices/create.blade.php`
  - Updated `addLineItem()` to use `unit` instead of `type`
  - Updated `loadBookingDetails()` to use appropriate units ('day' for rental, 'trip' for MDC)
  - Updated `save()` to save `unit` instead of `type`
  - Replaced card-based line items with Excel-style table
  - All fields now edit directly in the table

## Result
✅ Invoice create page now matches the quotation create page design
✅ Excel-style spreadsheet interface for line items
✅ Same unit options as quotations
✅ Consistent user experience across quotes and invoices
✅ Using N$ currency throughout


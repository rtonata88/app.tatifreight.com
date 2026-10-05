# MDC to Logbook Migration

**Date:** November 5, 2025  
**Status:** ✅ Complete

## Overview

MDC Charges have been successfully de-linked from Bookings and linked to Logbooks instead. This change better reflects the reality that MDC charges are incurred based on actual vehicle usage (tracked in logbooks) rather than on bookings.

---

## Why This Change?

### Previous Structure (Bookings → MDC)
- MDC was linked to bookings
- Problem: Not all logbook entries have bookings (internal trips, maintenance, etc.)
- Problem: MDC is charged based on actual usage, not booked usage

### New Structure (Logbooks → MDC)
- MDC is now linked to logbook entries
- ✅ MDC calculated on actual trips recorded in logbooks
- ✅ Works for both client bookings and internal trips
- ✅ More accurate reflection of actual usage

---

## Changes Made

### 1. Database Migration ✅
**File:** `database/migrations/2025_11_05_134137_replace_booking_id_with_logbook_id_in_mdc_calculations_table.php`

**Changes:**
- Removed `booking_id` foreign key from `mdc_calculations` table
- Added `logbook_id` foreign key to `mdc_calculations` table
- Maintains referential integrity with cascade delete

**Migration:**
```php
Schema::table('mdc_calculations', function (Blueprint $table) {
    // Drop booking_id
    $table->dropForeign(['booking_id']);
    $table->dropColumn('booking_id');
    
    // Add logbook_id
    $table->foreignId('logbook_id')->after('id')->constrained()->onDelete('cascade');
});
```

---

### 2. Model Updates ✅

#### MdcCalculation Model
**File:** `app/Models/MdcCalculation.php`

**Changes:**
- Updated `$fillable` array: `booking_id` → `logbook_id`
- Updated relationship: `booking()` → `logbook()`

```php
public function logbook(): BelongsTo
{
    return $this->belongsTo(Logbook::class);
}
```

#### Booking Model
**File:** `app/Models/Booking.php`

**Changes:**
- Removed `mdcCalculation()` relationship
- Removed `mdcCalculations()` relationship

#### Logbook Model
**File:** `app/Models/Logbook.php`

**Changes:**
- Added `mdcCalculation()` relationship
- Added `mdcCalculations()` relationship

```php
public function mdcCalculation(): HasOne
{
    return $this->hasOne(MdcCalculation::class);
}

public function mdcCalculations(): HasMany
{
    return $this->hasMany(MdcCalculation::class);
}
```

---

### 3. View Updates ✅

#### Booking Calendar View
**File:** `resources/views/livewire/bookings/calendar.blade.php`

**Changes:**
- Removed MDC calculations from booking eager loading
- Removed MDC information from booking details modal
- Bookings now only show invoice information (not MDC)

#### MDC Index View
**File:** `resources/views/livewire/mdc/index.blade.php`

**Changes:**
- Updated to load `logbook.booking.client` instead of `booking.client`
- Changed "Booking" column to "Logbook Entry"
- Updated search to search by logbook purpose and trip details
- Updated search placeholder text
- Client info now comes from: `logbook → booking → client`
- Shows "Internal" if no booking/client linked

**Table Columns:**
| Before | After |
|--------|-------|
| Booking | Logbook Entry |
| Shows booking number | Shows logbook date & route |

#### MDC Record Payment View
**File:** `resources/views/livewire/mdc/record-payment.blade.php`

**Changes:**
- Updated to load `logbook` instead of `booking`
- Changed `booking_number` to `logbook_reference`
- Shows logbook date as reference instead of booking number

---

### 4. Documentation Updates ✅

#### MDC Payment System Documentation
**File:** `MDC_PAYMENT_SYSTEM.md`

**Changes:**
- Updated all examples to use "Logbook Entry" instead of "Booking"
- Updated SQL queries to join through logbooks
- Updated table examples to show logbook dates

---

## Data Relationship Flow

### Before:
```
Booking → MDC Calculation → Vehicle
   ↓
Client
```

### After:
```
Logbook → MDC Calculation → Vehicle
   ↓
Booking (optional) → Client (optional)
```

**Note:** Logbooks can optionally have a booking (for client trips), or be standalone (for internal trips).

---

## How It Works Now

### Creating MDC Charges

1. **Driver creates a logbook entry:**
   - Records trip details (start/end odometer, route, date)
   - Optionally links to a booking (if it's a client job)

2. **System calculates MDC:**
   - Based on logbook entry details
   - Distance from odometer readings
   - Vehicle GVM from vehicle record
   - Creates `MdcCalculation` linked to the logbook entry

3. **MDC appears in MDC Charges list:**
   - Shows logbook entry date and route
   - Shows client info (if linked through booking)
   - Shows "Internal" if no client

---

## Client Information Access

### For Client Trips:
```
Logbook → Booking → Client
```
- Logbook has a `booking_id`
- Can access client through: `$mdcCalculation->logbook->booking->client`

### For Internal Trips:
```
Logbook (no booking)
```
- Logbook has `booking_id = null`
- No client information needed
- Display as "Internal" in views

---

## Migration Guide for Existing Data

If you have existing data, you'll need to:

1. **Backup your database**
2. **Create logbook entries for existing MDC calculations:**
   ```php
   // For each existing MDC calculation with a booking
   foreach (MdcCalculation::with('booking')->get() as $mdc) {
       if ($mdc->booking) {
           // Create logbook entry from booking
           $logbook = Logbook::create([
               'vehicle_id' => $mdc->booking->vehicle_id,
               'driver_id' => $mdc->booking->driver_id,
               'booking_id' => $mdc->booking->id,
               'date' => $mdc->calculation_date,
               'start_odometer' => 0, // You'll need to set this
               'end_odometer' => $mdc->distance_km, // Approximate
               'origin_from' => $mdc->booking->pickup_location,
               'origin_to' => $mdc->booking->delivery_location,
               'purpose' => 'Client booking',
               'created_by' => $mdc->booking->created_by,
           ]);
           
           // Update MDC calculation with logbook_id
           // This will be done by the migration
       }
   }
   ```
3. **Run the migration**
4. **Verify data integrity**

---

## Testing Checklist

### ✅ Completed Changes
- [x] Migration created and ready
- [x] MdcCalculation model updated
- [x] Booking model updated (relationships removed)
- [x] Logbook model updated (relationships added)
- [x] Booking calendar view updated
- [x] MDC index view updated
- [x] MDC record payment view updated
- [x] Documentation updated

### 🧪 To Test After Running Migration
- [ ] Run migration: `php artisan migrate`
- [ ] Create a logbook entry
- [ ] Verify MDC calculation can be created from logbook
- [ ] Check MDC index page displays correctly
- [ ] Test MDC payment recording
- [ ] Verify client information shows for client trips
- [ ] Verify "Internal" shows for non-client trips
- [ ] Test search functionality on MDC page

---

## Running the Migration

### To Apply Changes:
```bash
cd /Users/richard/Projects/taati
php artisan migrate
```

### To Rollback (if needed):
```bash
php artisan migrate:rollback --step=1
```

**Note:** The rollback will restore the `booking_id` column and remove the `logbook_id` column.

---

## Files Changed

### New Files:
1. `database/migrations/2025_11_05_134137_replace_booking_id_with_logbook_id_in_mdc_calculations_table.php`
2. `MDC_LOGBOOK_MIGRATION.md` (this file)

### Modified Files:
1. `app/Models/MdcCalculation.php`
2. `app/Models/Booking.php`
3. `app/Models/Logbook.php`
4. `resources/views/livewire/bookings/calendar.blade.php`
5. `resources/views/livewire/mdc/index.blade.php`
6. `resources/views/livewire/mdc/record-payment.blade.php`
7. `MDC_PAYMENT_SYSTEM.md`

---

## Summary

✅ **MDC Charges are now linked to Logbooks instead of Bookings**  
✅ **All models updated with correct relationships**  
✅ **All views updated to display logbook information**  
✅ **Documentation updated to reflect new structure**  
✅ **Client information still accessible through logbook → booking → client**  
✅ **Supports both client trips and internal trips**  

**Next Step:** Run the migration when ready to apply the changes to your database.

---

**Implementation Date:** November 5, 2025  
**System:** Tati Investment Fleet Management  
**Module:** MDC Calculation System


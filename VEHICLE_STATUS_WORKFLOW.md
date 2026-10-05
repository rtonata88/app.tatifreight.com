# Vehicle Status Workflow

## Overview
This document explains when and how vehicle status changes based on booking lifecycle.

## Vehicle Status Values
- `available` - Vehicle is free and can be booked
- `in_use` - Vehicle is currently being used in an active booking
- `maintenance` - Vehicle is under maintenance (manual status)
- `out_of_service` - Vehicle is temporarily unavailable (manual status)

## Booking Status Values
- `pending` - Booking created but not yet confirmed
- `confirmed` - Booking confirmed, waiting for start date
- `in_progress` - Booking is active (vehicle is being used)
- `completed` - Booking finished successfully
- `cancelled` - Booking was cancelled

---

## Date-Aware Vehicle Availability

### ✅ New Behavior (Implemented)
Vehicles are marked as "in_use" **ONLY** when a booking status is `in_progress`, NOT when a booking is created for a future date.

### Key Features:
1. **Future Bookings Don't Block Vehicles Immediately**
   - You can book a vehicle for 2 months from now
   - Vehicle remains `available` until booking starts
   - Other bookings can use the vehicle for non-overlapping dates

2. **Overlap Detection**
   - System checks for conflicting bookings with `confirmed` or `in_progress` status
   - Prevents double-booking for the same date range
   - Shows error: "This vehicle is not available for the selected date range"

3. **Status-Based Workflow**
   - Vehicle status follows booking status changes
   - Only `in_progress` bookings mark vehicles as `in_use`

---

## When Vehicle Status Changes

### 📅 Booking Creation (`bookings/create.blade.php`)

**Action:** Create new booking
- **Booking Status:** `pending`
- **Vehicle Status:** **NO CHANGE** (remains `available`)
- **Rationale:** Future bookings shouldn't block the vehicle immediately

**Validation:**
```php
// Checks for overlapping confirmed/in_progress bookings
$vehicle->isAvailable($start_date, $end_date)
```

---

### ✏️ Booking Update (`bookings/edit.blade.php`)

#### Scenario 1: Status Changes to `in_progress`
**Action:** Booking starts (e.g., vehicle pickup)
- **Before:** Booking status = `pending` or `confirmed`
- **After:** Booking status = `in_progress`
- **Vehicle Status:** `available` → `in_use`

#### Scenario 2: Status Changes to `completed`
**Action:** Booking ends successfully
- **Before:** Booking status = `in_progress`
- **After:** Booking status = `completed`
- **Vehicle Status:** `in_use` → `available`

#### Scenario 3: Status Changes to `cancelled`
**Action:** Booking is cancelled
- **Before:** Any status
- **After:** Booking status = `cancelled`
- **Vehicle Status:** → `available` (if was `in_use`)

#### Scenario 4: Vehicle Changed (Mid-Booking)
**Action:** Assign different vehicle to existing booking
- **Old Vehicle:** If booking was `in_progress` → returns to `available`
- **New Vehicle:** If booking is `in_progress` → becomes `in_use`

---

### 🔄 Quote to Booking Conversion

**Files:**
- `quotes/index.blade.php` (Convert to Booking action)
- `quotes/edit.blade.php` (Convert to Booking action)

**Action:** Convert approved quote to booking
- **Booking Status:** `pending`
- **Vehicle Status:** **NO CHANGE** (remains `available`)
- **Note:** Vehicle will only be marked `in_use` when booking status changes to `in_progress`

---

## Validation Rules

### Overlap Detection (`Vehicle::isAvailable()`)

The system checks for bookings with status `confirmed` or `in_progress` that overlap with requested dates:

```php
// Check if vehicle is free for date range
$vehicle->isAvailable($startDate, $endDate, $excludeBookingId = null)
```

**Overlap Scenarios Detected:**
1. Existing booking **starts** during requested period
2. Existing booking **ends** during requested period
3. Existing booking **encompasses** requested period (starts before and ends after)

**Example:**
```
Existing Booking: Jan 10 - Jan 20 (in_progress)
New Request:      Jan 15 - Jan 25
Result:           ❌ BLOCKED - Overlaps!

Existing Booking: Jan 10 - Jan 20 (pending)
New Request:      Jan 15 - Jan 25
Result:           ❌ BLOCKED - Even pending bookings block if confirmed!

Existing Booking: Jan 10 - Jan 20 (cancelled)
New Request:      Jan 15 - Jan 25
Result:           ✅ ALLOWED - Cancelled bookings don't block
```

---

## Summary of Changes Made

### Files Modified:

1. **`resources/views/livewire/bookings/create.blade.php`**
   - ❌ Removed: Automatic vehicle status update on booking creation
   - ✅ Added: Vehicle availability validation before creating booking

2. **`resources/views/livewire/bookings/edit.blade.php`**
   - ✅ Enhanced: Smarter vehicle status updates based on status transitions
   - ✅ Added: Vehicle availability validation (excluding current booking)
   - ✅ Fixed: Proper handling of vehicle changes mid-booking

3. **`resources/views/livewire/quotes/index.blade.php`**
   - ❌ Removed: Automatic vehicle status update on quote conversion

4. **`resources/views/livewire/quotes/edit.blade.php`**
   - ❌ Removed: Automatic vehicle status update on quote conversion

5. **`app/Models/Vehicle.php`**
   - ✅ Added: `isAvailable($startDate, $endDate, $excludeBookingId)` method
   - ✅ Implements comprehensive overlap detection logic

---

## Best Practices

### For Staff:
1. **Create Booking:** Status = `pending` (vehicle stays available)
2. **Confirm Booking:** Status = `confirmed` (vehicle stays available but is reserved)
3. **Start Job:** Status = `in_progress` (vehicle becomes "in_use")
4. **Complete Job:** Status = `completed` (vehicle returns to "available")

### For Future Enhancements:
- Consider adding a scheduled job to auto-transition bookings:
  - `confirmed` → `in_progress` when `start_date` arrives
  - `in_progress` → `completed` when `end_date` passes
- Add calendar view showing vehicle availability
- Add notifications for upcoming bookings

---

## Testing Checklist

- [ ] Create booking for future date → Vehicle stays available
- [ ] Try to double-book same vehicle for overlapping dates → Should fail
- [ ] Change booking status to `in_progress` → Vehicle becomes `in_use`
- [ ] Change booking status to `completed` → Vehicle becomes `available`
- [ ] Cancel booking with `in_progress` status → Vehicle becomes `available`
- [ ] Edit booking dates to overlap with another booking → Should fail
- [ ] Convert quote to booking → Vehicle stays available

---

## Migration Notes

**No database changes required** - All changes are in application logic only.

Existing bookings will continue to work. You may need to manually review any existing `pending` bookings that incorrectly marked vehicles as `in_use`.


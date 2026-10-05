# MDC (Mass Distance Charges) Setup Guide

## Overview
The MDC system automatically calculates Road Fund Administration (RFANAM) charges when bookings are completed.

---

## How MDC Works

### Automatic Calculation
MDC is automatically calculated when:
1. A booking status is changed to **`completed`**
2. The booking has **distance_km** filled in
3. The vehicle has either:
   - A **GVM (Gross Vehicle Mass)** set, OR
   - A manually linked **MDC Rate Card**

### Calculation Formula
```
MDC Amount = (Distance in KM ÷ 100) × Rate per 100km
```

**Example:**
- Distance: 670 km
- GVM: 34 tonnes → Charge Level 3 (N$22.16/100km)
- MDC = (670 ÷ 100) × 22.16 = **N$148.47**

---

## Setup Requirements

### 1. ✅ MDC Rate Cards (Already Seeded)
11 official RFANAM rate categories are loaded:

| Category | GVM Range | Rate/100km |
|----------|-----------|------------|
| Light Vehicle (Exempt) | 0 - 3.5t | N$0.00 |
| Charge Level 1 | 3.5t - 7t | N$10.10 |
| Charge Level 2 | 7t - 16t | N$12.22 |
| Charge Level 3 | 16t - 34t | N$22.16 |
| Charge Level 4 | 34t - 44t | N$44.47 |
| Charge Level 5 | 44t+ | N$66.64 |

**View/Edit:** Navigate to **Fleet Management → MDC Rates**

---

### 2. ⚠️ Configure Vehicle GVM

**For MDC to work, each vehicle MUST have:**
- **GVM (Gross Vehicle Mass)** in tonnes

**How to Set GVM:**
1. Go to **Fleet Management → Vehicles**
2. Click **Edit** on a vehicle
3. Scroll to **MDC (Mass Distance Charges)** section
4. Enter the vehicle's **GVM** in tonnes (e.g., 34.5)
5. The system will auto-suggest the correct MDC Rate Card
6. Save the vehicle

**Auto-Linking:**
- When you enter GVM, the system automatically suggests the correct RFANAM rate
- You can override the suggestion by manually selecting a different rate card

---

### 3. ✅ Create Bookings with Distance

When creating/editing a booking:
1. Fill in **Distance (km)** field
2. Complete the booking (status → **completed**)
3. MDC will be automatically calculated and saved

---

## Checking MDC Calculations

### View MDC Charges
Navigate to: **Fleet Management → MDC Charges**

**Features:**
- Total accumulated MDC amount
- Total paid vs outstanding
- This month's MDC
- Filter by date range, vehicle, or client
- Search by booking number
- **Record Payment** button
- Payment status indicators

### View MDC Report
Navigate to: **Reports → MDC Report**

**Features:**
- Group by Vehicle, Client, or Month
- Running totals
- Summary statistics
- PDF export for RFANAM submission

---

## Recording MDC Payments

### How to Record a Payment to RFANAM

1. Navigate to: **Fleet Management → MDC Charges**
2. Click the **"Record Payment"** button
3. Fill in payment details:
   - Payment Date
   - Amount (total payment made to RFANAM)
   - Payment Method (Bank Transfer, EFT, Cash, Cheque)
   - Bank Reference / Transaction ID
   - Upload receipt/proof of payment (optional)
   - Notes (optional)

4. Click **"Record Payment"**

### What Happens Automatically:

✅ **MDC Payment Record Created**
- Unique reference generated (e.g., MDC-000001)
- Payment details stored

✅ **Expense Auto-Created**
- Category: "MDC Payment"
- Amount: Same as MDC payment
- Status: Auto-approved
- Receipt attached (if uploaded)
- Description: "MDC Payment to RFANAM - MDC-000001"

✅ **Payment Allocated to Charges**
- Payment automatically allocated to oldest unpaid MDC charges first
- MDC calculations marked as "paid" or "partially paid"
- Outstanding balance updated

✅ **Audit Trail**
- Who recorded the payment
- When it was recorded
- Which MDC charges were covered
- Link to expense record

### Payment Allocation Example:

**Scenario:** You pay RFANAM N$500

| MDC Charge | Amount | Status Before | Payment Allocated | Status After |
|------------|--------|---------------|-------------------|--------------|
| BKG-000001 | N$148.47 | Unpaid | N$148.47 | ✅ Paid |
| BKG-000002 | N$200.00 | Unpaid | N$200.00 | ✅ Paid |
| BKG-000003 | N$180.00 | Unpaid | N$151.53 | ⚠️ Partially Paid |
| **Total** | **N$528.47** | | **N$500.00** | **N$28.47 outstanding**|

The system automatically allocates the N$500 to the three oldest charges, partially covering the third one.

### Viewing Payment History

**On MDC Charges Page:**
- Payment status badges (Paid / Partially Paid / Unpaid)
- Amount paid vs outstanding for each charge
- Color-coded indicators

**On Expenses Page:**
- Filter by category "MDC Payment"
- View all RFANAM payments
- Download receipts
- Export for accounting

---

## Troubleshooting

### ❌ MDC Not Being Created?

Check these common issues:

#### 1. Vehicle Missing GVM
```
Solution: Edit the vehicle and add GVM in tonnes
```

#### 2. Booking Missing Distance
```
Solution: Edit the booking and fill in the "Distance (km)" field
```

#### 3. Booking Not Completed
```
MDC only calculates when booking status = 'completed'
Solution: Change booking status to 'completed'
```

#### 4. Check Laravel Logs
```bash
tail -f storage/logs/laravel.log | grep MDC
```

---

## Database Schema

### `mdc_calculations` Table
- `booking_id` - Link to booking
- `vehicle_id` - Link to vehicle
- `mdc_rate_card_id` - RFANAM rate used
- `gvm_tonnes` - Vehicle GVM at time of calculation
- `distance_km` - Distance traveled
- `load_weight` - Actual load (optional)
- `tare_weight` - Vehicle tare weight (optional, legacy)
- `total_mass` - Total mass (optional, legacy)
- `rate_per_100kg_km` - Rate used
- `mdc_amount` - **Final calculated MDC**
- `calculation_date` - When calculated

### `mdc_rate_cards` Table
- `category_name` - RFANAM category name
- `min_gvm_tonnes` - Minimum GVM for this rate
- `max_gvm_tonnes` - Maximum GVM (null = unlimited)
- `rate_per_100km` - N$ per 100km
- `effective_from` - Rate valid from
- `effective_to` - Rate valid until (null = ongoing)
- `is_active` - Active/inactive

### `vehicles` Table (MDC Fields)
- `gvm_tonnes` - Gross Vehicle Mass in tonnes
- `mdc_rate_card_id` - Manually linked rate (optional)

---

## Workflow Example

### Scenario: Haul from Windhoek to Walvis Bay

1. **Create Booking:**
   - Client: ABC Mining
   - Vehicle: N6767W (GVM: 34t)
   - Start: Nov 1, 2025
   - End: Nov 3, 2025
   - Distance: 670 km
   - Status: **pending**

2. **Start Trip:**
   - Change status to **in_progress**
   - Vehicle automatically marked as "in_use"

3. **Complete Trip:**
   - Change status to **completed**
   - **MDC Automatically Calculated:**
     - GVM: 34t → Charge Level 3
     - Rate: N$22.16/100km
     - MDC: (670 ÷ 100) × 22.16 = **N$148.47**
   - Vehicle status returns to "available"

4. **View MDC:**
   - Check **MDC Charges** page
   - See accumulated total
   - Export report for RFANAM

---

## Recent Fix Applied

### Issue Resolved: Nov 3, 2025
**Problem:** MDC calculations weren't being created due to legacy required fields (`tare_weight`, `load_weight`, `total_mass`).

**Solution:** Migration applied to make legacy fields nullable:
```sql
ALTER TABLE mdc_calculations 
MODIFY tare_weight DECIMAL(10,2) NULL,
MODIFY load_weight DECIMAL(10,2) NULL,
MODIFY total_mass DECIMAL(10,2) NULL;
```

**Result:** MDC calculations now work with GVM-based RFANAM rates without requiring tare/load weights.

---

## Updating RFANAM Rates

If RFANAM updates their rates:

1. Go to **Fleet Management → MDC Rates**
2. Find the rate to update
3. Click **Edit**
4. Update the **rate_per_100km** value
5. Or deactivate old rate and create new one with different effective dates

**Note:** Historical MDC calculations are not affected - they keep the rate that was active when calculated.

---

## Next Steps

### Immediate Actions:
1. ✅ Edit all vehicles to add GVM
2. ✅ Link vehicles to rate cards (auto-suggested)
3. ✅ Complete existing bookings with distances
4. ✅ Check MDC Charges page to see accumulated totals

### Optional Enhancements:
- Set up monthly reminders to pay RFANAM
- Export MDC report before each payment
- Add email notifications when MDC exceeds threshold
- Integrate with accounting system

---

## Support

**Documentation:** https://www.rfanam.com.na/charges/
**System:** Tati Investment Fleet Management
**Last Updated:** November 3, 2025


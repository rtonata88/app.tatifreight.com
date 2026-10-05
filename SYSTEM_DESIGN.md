# Trucking Company Management System – Laravel Development Roadmap
## Project Overview

Build a **comprehensive web-based trucking management system** to streamline:
- Tipper truck rentals (construction/mining)
- 14-ton cooler truck rentals (refrigerated)
- 34-ton superlink truck rentals (heavy freight)
- Logistics & contractor hire services

---

## 1. Database Schema Design (Eloquent Models)

```bash
php artisan make:model User -mfs
php artisan make:model Client -mfs
php artisan make:model Vehicle -mfs
php artisan make:model Booking -mfs
php artisan make:model Quote -mfs
php artisan make:model Invoice -mfs
php artisan make:model Expense -mfs
php artisan make:model RateCard -mfs
php artisan make:model MdcCalculation -mfs
php artisan make:model ServiceAgreement -mfs
php artisan make:model VehicleInspection -mfs
```

### Key Relationships
- `Client` → hasMany `Booking`, `Quote`, `Invoice`, `ServiceAgreement`
- `Vehicle` → belongsTo `VehicleType`, hasMany `Booking`, `Expense`, `MdcCalculation`
- `Booking` → belongsTo `Client`, `Vehicle`, `Driver(User)`, `Quote`
- `Quote` → morphToMany `Vehicle`, hasOne `Booking` (on accept)
- `Expense` → belongsTo `Vehicle`, `Booking?`, `User` (driver claim)

---

## 2. User Roles & Permissions (Spatie Laravel Permission)

```bash
composer require spatie/laravel-permission
```

### Roles
| Role | Access |
|------|-------|
| `admin` | Full access |
| `manager` | All except system settings |
| `dispatcher` | Bookings, Quotes, Clients |
| `driver` | View assigned jobs, submit expenses |
| `accountant` | Financials + Reports |

> Use middleware: `@can('view-bookings')`, role-based gates

---

## 3. Core Modules (Phase 1)

### 3.1 Authentication & User Management
- Laravel Breeze / Jetstream (with Inertia + Vue or Livewire)
- Email verification
- Password reset
- Profile management

### 3.2 Fleet Management
**Model**: `Vehicle`
- Fields: `reg_number`, `vin`, `make`, `model`, `type` (enum: tipper, cooler, superlink, support), `load_capacity`, `tare_weight`, `insurance_expiry`, `disc_expiry`, `status`, `current_mileage`, `gps_device_id`
- File uploads: license disc, insurance, photos
- Service schedule (recurring reminders)
- Availability calendar integration

### 3.3 Bookings Management
**Model**: `Booking`
- Create with: client, vehicle(s), dates, duration, pickup/delivery, notes
- Status: `pending → confirmed → in_progress → completed → cancelled`
- Calendar view (FullCalendar.js)
- Conflict detection (vehicle double-booking)
- Driver assignment
- Recurring bookings (weekly/monthly)
- Notifications (email + in-app)

> Use Laravel Events & Listeners for status changes

### 3.4 Client Management
**Model**: `Client`
- Profile: name, email, phone, address, billing info
- Classification: `adhoc` or `contract`
- Service agreements (PDF upload or generate)
- Credit limit, payment terms
- Communication log (notes, emails)

---

## 4. Financial Modules (Phase 2)

### 4.1 Quotations System
- Template-based (by vehicle type)
- Line items: rate × duration/distance, MDC, extras
- Versioning (`quote_v1`, `v2`)
- Approval workflow (pending → approved → sent)
- Convert to Booking on acceptance
- PDF generation (DomPDF) + email
- Expiry date + auto-expire job

### 4.2 Invoicing System
- Auto-generate from completed bookings
- Manual creation
- Tax calculation (VAT?)
- Payment status: `unpaid`, `partial`, `paid`
- Overdue reminders (scheduled task)
- Export to CSV/PDF

### 4.3 Expense Management
- Categories (fuel, maintenance, tolls, driver wages, etc.)
- Receipt image upload (stored in `storage/app/receipts`)
- Associate with vehicle or booking
- Approval workflow
- Driver expense claims (mobile-friendly form)

### 4.4 Rate Card Management
**Model**: `RateCard`
- Types: hourly, daily, per km, tonnage, load-specific
- Client-specific overrides
- Seasonal rates
- MDC inclusion toggle
- Audit log of changes

---

## 5. Mass Distance Charges (MDC) Module

**Model**: `MdcCalculation`
- Auto-calculate: `(tare + load) × distance × rate_per_100kg_km`
- Store: `booking_id`, `distance_km`, `total_mass`, `rate`, `amount`
- Integrate with route from Booking
- Government rate updates (admin editable)
- Include in quotes/invoices as line item

> Use Laravel Scheduler: `php artisan mdc:recalculate-outdated`

---

## 6. Advanced Features (Phase 3)

### 6.1 Reporting & Analytics
- P&L Report
- Fleet utilization %
- Client revenue ranking
- Driver performance (jobs completed, expenses)
- Maintenance due alerts
- Export: PDF, Excel (Maatwebsite/Excel)

### 6.2 Notifications
- Email: booking confirmations, quote sent, invoice due
- In-app: Laravel Notifications + database channel
- SMS (optional via Twilio)

### 6.3 Document Management
- Store: contracts, discs, insurance, receipts
- Version control
- Expiry alerts

### 6.4 Mobile Optimization
- Responsive UI (Tailwind)
- Driver portal: view jobs, submit expenses, upload receipts

---

## 7. Integrations

| Integration | Tool |
|-----------|------|
| Email | Laravel Mail (SMTP/Mailgun) |
| PDF | DomPDF or Snappy (wkhtmltopdf) |
| GPS (Future) | API endpoint ready |
| Accounting Export | CSV/XML for QuickBooks/Xero |
| Backups | Laravel Backup (Spatie) |

# Booking Calendar

## Overview
The Booking Calendar provides a visual representation of vehicle bookings, making it easy to see when and where each vehicle is booked at a glance.

## Features

### Calendar View (Month)
- **Full Month Display**: Shows the current month in a traditional calendar grid format
- **Color-Coded Bookings**: Each booking is displayed with a color based on its status:
  - 🟡 **Yellow**: Pending bookings
  - 🔵 **Blue**: Confirmed bookings
  - 🟢 **Green**: In Progress bookings
  - ⚫ **Gray**: Completed bookings
  - 🔴 **Red**: Cancelled bookings

- **Quick Information**: Each booking card shows:
  - Vehicle registration number
  - Client name
  - Color-coded left border for status

- **Today Indicator**: The current day is highlighted in blue

### List View
- **Tabular Format**: View all bookings in a traditional table
- **Detailed Information**: Shows:
  - Booking number (clickable to edit)
  - Vehicle (registration + type)
  - Client name
  - Start and end dates
  - Duration (in days)
  - Status badge

### Navigation & Filters

#### Date Navigation
- **Previous/Next Month**: Arrow buttons to navigate between months
- **Today Button**: Jump back to the current month
- **Month Display**: Shows current month and year

#### Vehicle Filter
- **All Vehicles**: Shows bookings for all vehicles (default)
- **Single Vehicle**: Filter to show only one vehicle's bookings
- Dropdown displays vehicle registration and type

#### View Toggle
- **Month View**: Traditional calendar grid
- **List View**: Table format for detailed viewing

### Booking Details Modal
- **Quick View**: Click any booking to see a detailed modal popup
- **Comprehensive Information**: The modal displays:
  - Client information (company name, contact person, phone, email)
  - Vehicle details (registration, type, make/model, capacity)
  - Trip details (dates, duration, distance, pickup/drop-off locations)
  - Driver information (if assigned)
  - Financial details (total amount, MDC charges)
  - Notes (if any)
- **Edit Option**: Direct "Edit Booking" button for authorized users
- **Easy Close**: Close button or click outside to dismiss

### Integration
- **Click to View**: Clicking any booking opens a detailed modal popup
- **Quick Edit**: Modal includes an "Edit Booking" button for authorized users
- **New Booking Button**: Quick access to create new bookings
- **List/Calendar Toggle**: Switch between list and calendar views from either page
- **Sidebar Link**: Calendar is accessible from the Operations section

## Access & Permissions
- Accessible to users with `view-bookings` permission
- Route: `/bookings/calendar`
- Available in sidebar under **Operations > Calendar**

## Use Cases

### 1. Vehicle Availability Check
Before creating a new booking, check the calendar to see:
- Which vehicles are available on specific dates
- Which vehicles have overlapping bookings
- Gaps in vehicle schedules

### 2. Fleet Utilization
- Identify underutilized vehicles
- See busy periods at a glance
- Plan maintenance during downtime

### 3. Conflict Resolution
- Quickly identify booking conflicts
- See which bookings are still pending vs confirmed
- Track in-progress trips

### 4. Client Scheduling
- Check vehicle availability when client calls
- View upcoming bookings to plan ahead
- Verify booking dates visually

## Technical Details

### Date Range
- Calendar displays one full month at a time
- Includes leading/trailing days to complete weeks
- Bookings that span multiple days appear on all relevant dates

### Query Optimization
- Bookings are filtered by date range for performance
- Eager loads client and vehicle relationships
- Handles multi-day bookings efficiently

### Responsive Design
- Month view: Best on desktop/tablet
- List view: Optimized for all screen sizes
- Mobile users can toggle to list view for better experience

## Tips
1. Use **vehicle filter** to check availability for a specific truck
2. Switch to **list view** for a detailed overview with duration
3. Use **color indicators** to quickly identify booking status
4. Click on bookings directly from calendar to edit details
5. The **"Today" button** helps you quickly return to current date

## Related Features
- [Vehicle Status Workflow](VEHICLE_STATUS_WORKFLOW.md)
- [Booking Management](resources/views/livewire/bookings/index.blade.php)
- [Date-Aware Vehicle Availability](VEHICLE_STATUS_WORKFLOW.md#date-aware-availability)


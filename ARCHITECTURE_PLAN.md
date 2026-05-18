# Sand | سند — Architecture & Implementation Plan

> **Base:** Handyman Service Marketplace v11.17.0 (Iqonic)
> **Target:** Saudi Marketplace "Sand | سند"
> **Stack:** Laravel 11 + Vue 3 + Blade + Sanctum + MySQL

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Existing Architecture (What's Already Built)](#2-existing-architecture-whats-already-built)
3. [Phase 1: Rebranding to Sand | سند](#3-phase-1-rebranding-to-sand--سند)
4. [Phase 2: Booking Workflow Enhancement](#4-phase-2-booking-workflow-enhancement)
5. [Phase 3: Quote System Enhancement](#5-phase-3-quote-system-enhancement)
6. [Phase 4: Payment Hold / Escrow System](#6-phase-4-payment-hold--escrow-system)
7. [Phase 5: Refundable Insurance System](#7-phase-5-refundable-insurance-system)
8. [Phase 6: Investigation Mode](#8-phase-6-investigation-mode)
9. [Phase 7: Legal Acknowledgements](#9-phase-7-legal-acknowledgements)
10. [Phase 8: Dashboard Restructure](#10-phase-8-dashboard-restructure)
11. [Phase 9: Sidebar Restructure](#11-phase-9-sidebar-restructure)
12. [Phase 10: Notifications Enhancement](#12-phase-10-notifications-enhancement)
13. [Phase 11: API Compatibility](#13-phase-11-api-compatibility)
14. [Complete Migration List](#14-complete-migration-list)
15. [Step-by-Step Implementation Order](#15-step-by-step-implementation-order)

---

## 1. Executive Summary

### What Already Exists (Do NOT Rebuild)

The codebase already has a fully functional inspection → quote → approval → escrow workflow:

| Component | Status | File |
|-----------|--------|------|
| `BookingWorkflowService` | ✅ Complete | `app/Services/BookingWorkflowService.php` |
| `Quote` model + table | ✅ Complete | `app/Models/Quote.php`, `quotes` table |
| Booking status ENUM | ✅ Complete | 18 statuses in `bookings.status` |
| Payment status ENUM | ✅ Complete | 8 statuses in `bookings.payment_status` |
| Escrow hold in `savePayment` | ✅ Complete | `API/PaymentController.php:50-78` |
| Escrow release on completion | ✅ Complete | `BookingWorkflowService:263-264` |
| Dashboard metrics (Sand-specific) | ✅ Complete | `HomeController.php:61-86` |
| Quote API endpoints | ✅ Complete | `API/QuoteController.php` |
| Quote routes | ✅ Complete | `routes/api.php:268-282` |
| APP_NAME = "Sand" | ✅ Complete | `.env` |
| Sidebar "Bidding Management" | ✅ Complete | `partials/_body_sidebar.blade.php` |

### What Needs to Be Built

| Component | Priority | Effort |
|-----------|----------|--------|
| Rebranding (colors, emails, translations) | High | 2 days |
| Quote system enhancement (handyman_id, duration, notes) | Medium | 1 day |
| Payment hold tracking (escrow transactions table) | High | 2 days |
| Insurance/deposit system | High | 2 days |
| Investigation/dispute mode | High | 1.5 days |
| Legal acknowledgements | Medium | 1 day |
| Dashboard redesign (Vue widgets) | Medium | 2 days |
| Sidebar restructure | Medium | 0.5 day |
| Notification templates | Medium | 1 day |
| API compatibility pass | Ongoing | 1 day |

---

## 2. Existing Architecture (What's Already Built)

### 2.1 Current Booking State Machine

```
                    ┌──────────────────────────────────┐
                    │         Booking Created           │
                    │    status: pending_inspection     │
                    └────────────┬─────────────────────┘
                                 │
                                 ▼
                    ┌──────────────────────────────────┐
                    │      Provider Inspects Site       │
                    │    markInspected()                │
                    │    status: waiting_quote          │
                    └────────────┬─────────────────────┘
                                 │
                    ┌────────────┴────────────┐
                    ▼                         ▼
     ┌─────────────────────────┐   ┌─────────────────────┐
     │   Provider Submits      │   │  Quote Rejected     │
     │   Quote                 │   │  (can re-submit)    │
     │   submitQuote()         │   │  quote_rejected     │
     │   status: quoted        │   └─────────────────────┘
     └────────────┬────────────┘
                  │
                  ▼
     ┌─────────────────────────┐
     │   Customer Approves     │
     │   approveQuote()        │
     │   status: quote_approved│
     └────────────┬────────────┘
                  │
                  ▼
     ┌─────────────────────────┐
     │   Customer Pays         │
     │   holdInEscrow()        │
     │   payment_status: escrow│
     └────────────┬────────────┘
                  │
                  ▼
     ┌─────────────────────────┐
     │   Provider Starts Job   │
     │   startBooking()        │
     │   status: in_progress   │
     └────────────┬────────────┘
                  │
                  ▼
     ┌─────────────────────────┐
     │   Job Completed         │
     │   completeBooking()     │
     │   status: completed     │
     │   payment_status:       │
     │     released            │
     └─────────────────────────┘
```

### 2.2 Existing Bookings Table Schema

The `bookings` table already has these critical columns for the workflow:

| Column | Type | Purpose |
|--------|------|---------|
| `status` | ENUM(18 values) | Current booking state |
| `payment_status` | ENUM(8 values) | `escrow`, `released`, `refunded`, etc. |
| `quote_id` | FK→quotes.id | Latest quote reference |
| `quote_price` | decimal(10,2) | Denormalized for Flutter |
| `quote_description` | text | Denormalized for Flutter |
| `reason` | text | Rejection reason |

### 2.3 Existing Quotes Table

| Column | Type | Purpose |
|--------|------|---------|
| `id` | bigIncrements | Primary key |
| `booking_id` | FK→bookings.id | Related booking |
| `provider_id` | FK→users.id | Who quoted |
| `price` | decimal(10,2) | Quote amount |
| `notes` | text | Provider's notes |
| `status` | ENUM(pending,approved,rejected) | Quote state |
| Soft deletes + timestamps | — | — |

---

## 3. Phase 1: Rebranding to Sand | سند

### 3.1 Color System

**Primary Color:** `rgb(57, 127, 141)` — Teal/Sandstone

```scss
// Generate color palette
$sand-primary:       #397D8D;  // rgb(57, 127, 141)
$sand-primary-light: #4A9AAD;  // light shade (+15%)
$sand-primary-dark:  #2B6070;  // dark shade (-15%)
$sand-primary-hover: #316D7D;  // hover state
$sand-primary-bg:    #E8F2F5;  // background tint (rgba(57,127,141,0.09))
$sand-sidebar-bg:    #1A2E35;  // dark sidebar
$sand-sidebar-text:  #C8DDE3;  // sidebar text
$sand-accent-gold:   #C9A84C;  // accent for premium/prestige
```

### 3.2 Migration: Branding Settings

**`database/migrations/YYYY_MM_DD_000001_add_sand_branding_settings.php`**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update theme-setup with Sand colors
        DB::table('settings')->updateOrInsert(
            ['type' => 'theme-setup', 'key' => 'theme-setup'],
            [
                'value' => json_encode([
                    'primary_color'    => '#397D8D',
                    'primary_hover'    => '#316D7D',
                    'sidebar_bg'       => '#1A2E35',
                    'sidebar_text'     => '#C8DDE3',
                    'logo'             => null,
                    'favicon'          => null,
                    'footer_logo'      => null,
                    'loader'           => null,
                ]),
            ]
        );

        // Update app settings name
        DB::table('app_settings')->updateOrInsert(
            ['name' => 'app_name'],
            ['value' => json_encode('Sand | سند')]
        );
    }

    public function down(): void
    {
        // Restore original values if needed
    }
};
```

### 3.3 SCSS Variable Override File

**`public/scss/sand/_brand-variables.scss`**

```scss
// ============================================================
// Sand | سند — Brand Variables
// Override handyman-design-system defaults
// ============================================================

// ── Primary Color ───────────────────────────────────────────
$sand-primary:       #397D8D;
$sand-primary-rgb:   57, 127, 141;
$sand-primary-light: #4A9AAD;
$sand-primary-dark:  #2B6070;

// ── Override Bootstrap Primary ──────────────────────────────
$primary:       $sand-primary;

// ── Sidebar ─────────────────────────────────────────────────
$sidebar-bg:        #1A2E35;
$sidebar-text:      #C8DDE3;
$sidebar-active:    $sand-primary-light;
$sidebar-width:     260px;

// ── Buttons ─────────────────────────────────────────────────
$btn-primary-bg:            $sand-primary;
$btn-primary-hover-bg:      $sand-primary-hover;
$btn-primary-active-bg:     $sand-primary-dark;
$btn-primary-border:        $sand-primary;

// ── Cards / Stats ───────────────────────────────────────────
$card-accent:       $sand-primary;
$stat-up:           #3CAE5C;
$stat-down:         #FB2F2F;

// ── ApexCharts ──────────────────────────────────────────────
$chart-primary:     $sand-primary;
$chart-secondary:   $sand-primary-light;
$chart-grid:        #E8F2F5;
```

Then in `public/scss/app.scss` (or the main compiled file):

```scss
// Load Sand brand BEFORE Bootstrap so it overrides correctly
@import 'sand/brand-variables';
@import 'handyman-design-system/variables';
@import 'bootstrap/scss/bootstrap';
```

### 3.4 Files to Update for Branding

| File | Change |
|------|--------|
| `config/app.php` | `'name' => 'Sand | سند'` (fallback) |
| `.env` | Already `APP_NAME="Sand"` — verify |
| `resources/views/partials/_head.blade.php` | Update `<title>` to use "Sand | سند" |
| `resources/views/partials/_body_header.blade.php` | Update navbar brand text |
| `resources/views/partials/_body_sidebar.blade.php` | Update sidebar header/logo |
| `resources/views/layouts/guest.blade.php` | Add sand color CSS vars |
| `resources/views/layouts/dashboard.blade.php` | Add sand color CSS vars |
| `resources/views/auth/login.blade.php` | Update branding text |
| `resources/views/emails/approveemail.blade.php` | Change "Handyman Service" → "Sand" |
| `resources/views/emails/verification.blade.php` | Change branding |
| `resources/views/emails/statusUpdated.blade.php` | Change branding |
| `resources/views/booking/invoice_pdf.blade.php` | Update logo/text |
| `resources/views/provider/subscription-invoice-pdf.blade.php` | Update logo/text |
| `resources/lang/en/messages.php` | Update app name references |
| `resources/lang/ar/messages.php` | Update app name references |
| `public/js/chart-custom.js` | Update theme colors to #397D8D |
| `public/js/charts/01.js` | Update colors |
| `public/js/customizer.js` | Update defaults |

### 3.5 Rebranding Service Provider

**`app/Providers/SandBrandingServiceProvider.php`**

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;

class SandBrandingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Make brand config available everywhere
        $this->app->singleton('sand.brand', function () {
            return [
                'name'          => 'Sand | سند',
                'primary_color' => '#397D8D',
                'primary_rgb'   => '57, 127, 141',
                'sidebar_bg'    => '#1A2E35',
                'accent_gold'   => '#C9A84C',
                'version'       => config('app.version', '11.17.0'),
            ];
        });
    }

    public function boot(): void
    {
        // Share brand config with all Blade views
        View::share('sandBrand', app('sand.brand'));

        // @sandBrand directive for inline use
        Blade::directive('sandBrand', function () {
            return "<?php echo app('sand.brand')['name']; ?>";
        });
    }
}
```

---

## 4. Phase 2: Booking Workflow Enhancement

### 4.1 Target State Machine

```
                    ┌──────────────────────────────────────┐
                    │        inspection_requested           │
                    │  (Customer requests service visit)    │
                    └──────────────┬───────────────────────┘
                                   │
                                   ▼
                    ┌──────────────────────────────────────┐
                    │           inspected                   │
                    │  (Provider visits & inspects site)    │
                    └──────────────┬───────────────────────┘
                                   │
                    ┌──────────────┴───────────────────────┐
                    ▼                                      ▼
     ┌─────────────────────────────┐     ┌──────────────────────────────┐
     │       quote_submitted       │     │     quote_rejected           │
     │  (Provider submits price)   │◄────│  (Customer rejected,         │
     └──────────────┬──────────────┘     │   provider can re-quote)     │
                    │                    └──────────────────────────────┘
                    ▼
     ┌─────────────────────────────┐
     │       quote_approved         │
     │  (Customer accepts quote)   │
     └──────────────┬──────────────┘
                    │
                    ▼
     ┌─────────────────────────────┐
     │        payment_held          │
     │  (Amount in escrow)         │
     └──────────────┬──────────────┘
                    │
     ┌──────────────┴──────────────┐
     ▼                             ▼
 ┌──────────────┐     ┌──────────────────────┐
 │  in_progress  │     │    cancelled         │
 │  (Job active) │     │  (Any stage)         │
 └───────┬───────┘     └──────────────────────┘
         │
         ▼
 ┌──────────────────────┐
 │     completed         │
 │  (Job done)           │
 └───────┬──────────────┘
         │
         ▼
 ┌──────────────────────┐
 │      released         │
 │  (Escrow → Provider)  │
 └──────────────────────┘

 Additional paths:
   Any active state → disputed → under_investigation
   under_investigation → resolved → released or refunded
```

### 4.2 Migration: Add New Status Values

**`database/migrations/YYYY_MM_DD_000010_add_sand_booking_statuses.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The bookings.status ENUM already has all needed values from the 
        // harden migration. We need to add:
        // - 'disputed'
        // - 'under_investigation'
        // - 'resolved'
        
        // MySQL ENUM modification requires raw statement
        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM(
            'pending_inspection',
            'inspected',
            'waiting_quote',
            'quote_submitted',
            'quoted',
            'quote_approved',
            'quote_rejected',
            'payment_held',
            'in_progress',
            'completed',
            'released',
            'cancelled',
            'pending',
            'confirmed',
            'disputed',
            'under_investigation',
            'resolved',
            'on_the_way',
            'hold',
            'pending_approval',
            'rejected',
            'accept'
        ) NOT NULL DEFAULT 'pending_inspection'");

        // Add new payment_status values for investigation
        DB::statement("ALTER TABLE bookings MODIFY COLUMN payment_status ENUM(
            'pending', 'paid', 'escrow', 'pending_release',
            'released', 'refunded', 'held', 'advanced_paid',
            'frozen_under_investigation', 'partially_released'
        ) DEFAULT 'pending'");

        // Add investigation fields to bookings
        Schema::table('bookings', function ($table) {
            $table->text('dispute_reason')->nullable()->after('reason');
            $table->text('investigation_notes')->nullable()->after('dispute_reason');
            $table->timestamp('frozen_until')->nullable()->after('investigation_notes');
            $table->foreignId('investigated_by')->nullable()
                  ->constrained('users')->nullOnDelete()->after('frozen_until');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function ($table) {
            $table->dropColumn([
                'dispute_reason',
                'investigation_notes',
                'frozen_until',
                'investigated_by',
            ]);
        });
    }
};
```

### 4.3 Migration: Add Statuses to booking_statuses Seeder

**`database/migrations/YYYY_MM_DD_000011_add_sand_booking_statuses_seeder.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $newStatuses = [
            ['name' => 'disputed',              'status' => 1],
            ['name' => 'under_investigation',    'status' => 1],
            ['name' => 'resolved',              'status' => 1],
            ['name' => 'inspection_requested',  'status' => 1],
            ['name' => 'inspected',             'status' => 1],
            ['name' => 'quote_submitted',       'status' => 1],
            ['name' => 'payment_held',          'status' => 1],
            ['name' => 'released',              'status' => 1],
        ];

        foreach ($newStatuses as $status) {
            DB::table('booking_statuses')->updateOrInsert(
                ['name' => $status['name']],
                $status
            );
        }
    }

    public function down(): void
    {
        DB::table('booking_statuses')
          ->whereIn('name', ['disputed', 'under_investigation', 'resolved',
                             'inspection_requested', 'inspected', 'quote_submitted',
                             'payment_held', 'released'])
          ->delete();
    }
};
```

### 4.4 Enhanced BookingWorkflowService

Add new methods to `app/Services/BookingWorkflowService.php`:

```php
/**
 * Transition: any active status → disputed
 * Customer or admin opens a dispute.
 */
public function openDispute(Booking $booking, int $actorId, string $reason): array
{
    $disputableStatuses = ['quoted', 'quote_approved', 'payment_held', 'in_progress'];
    if (! in_array($booking->status, $disputableStatuses)) {
        return $this->error(422, "Cannot dispute booking in [{$booking->status}] status.");
    }

    return DB::transaction(function () use ($booking, $actorId, $reason) {
        $old = $booking->status;
        $booking->status = 'disputed';
        $booking->dispute_reason = $reason;
        $booking->frozen_until = now()->addDays(14); // Default investigation period
        $booking->save();

        // Log activity
        $booking->activities()->create([
            'activity_type'    => 'dispute_opened',
            'activity_message' => "Dispute opened: {$reason}",
            'created_by'       => $actorId,
        ]);

        return $this->ok('Dispute opened. Booking frozen for investigation.', [
            'booking_id' => $booking->id,
            'status'     => $booking->status,
            'old_status' => $old,
        ]);
    });
}

/**
 * Admin marks a dispute as under active investigation.
 * Freezes all financial operations.
 */
public function escalateToInvestigation(Booking $booking, int $adminId): array
{
    if ($booking->status !== 'disputed') {
        return $this->error(422, 'Can only investigate disputed bookings.');
    }

    return DB::transaction(function () use ($booking, $adminId) {
        $old = $booking->status;
        $booking->status = 'under_investigation';
        $booking->payment_status = 'frozen_under_investigation';
        $booking->investigated_by = $adminId;
        $booking->save();

        $booking->activities()->create([
            'activity_type'    => 'investigation_started',
            'activity_message' => 'Admin escalated to investigation.',
            'created_by'       => $adminId,
        ]);

        return $this->ok('Investigation started. All funds frozen.', [
            'booking_id' => $booking->id,
            'status'     => $booking->status,
        ]);
    });
}

/**
 * Admin resolves investigation.
 * Can release funds to provider or refund customer.
 */
public function resolveInvestigation(
    Booking $booking,
    int $adminId,
    string $resolution, // 'released' | 'refunded' | 'partial'
    ?float $partialAmount = null
): array {
    if ($booking->status !== 'under_investigation') {
        return $this->error(422, 'Booking is not under investigation.');
    }

    return DB::transaction(function () use ($booking, $adminId, $resolution, $partialAmount) {
        $old = $booking->status;
        $booking->status = 'resolved';

        match ($resolution) {
            'released' => $booking->payment_status = 'released',
            'refunded' => $booking->payment_status = 'refunded',
            'partial'  => $booking->payment_status = 'partially_released',
            default    => throw new \InvalidArgumentException("Invalid resolution: {$resolution}"),
        };

        $booking->investigation_notes = "Resolved by admin #{$adminId}: {$resolution}";
        $booking->save();

        $booking->activities()->create([
            'activity_type'    => 'investigation_resolved',
            'activity_message' => "Investigation resolved: {$resolution}",
            'created_by'       => $adminId,
        ]);

        return $this->ok('Investigation resolved.', [
            'booking_id'     => $booking->id,
            'status'         => $booking->status,
            'payment_status' => $booking->payment_status,
        ]);
    });
}
```

### 4.5 Booking Model: New Constants and Scopes

Add to `app/Models/Booking.php`:

```php
// Status constants for clean reference
public const STATUS_PENDING_INSPECTION  = 'pending_inspection';
public const STATUS_INSPECTED           = 'inspected';
public const STATUS_WAITING_QUOTE       = 'waiting_quote';
public const STATUS_QUOTE_SUBMITTED     = 'quote_submitted';
public const STATUS_QUOTED              = 'quoted';
public const STATUS_QUOTE_APPROVED      = 'quote_approved';
public const STATUS_QUOTE_REJECTED      = 'quote_rejected';
public const STATUS_PAYMENT_HELD        = 'payment_held';
public const STATUS_IN_PROGRESS         = 'in_progress';
public const STATUS_COMPLETED           = 'completed';
public const STATUS_RELEASED            = 'released';
public const STATUS_CANCELLED           = 'cancelled';
public const STATUS_DISPUTED            = 'disputed';
public const STATUS_UNDER_INVESTIGATION = 'under_investigation';
public const STATUS_RESOLVED            = 'resolved';

// Payment status constants
public const PAYMENT_PENDING    = 'pending';
public const PAYMENT_ESCROW     = 'escrow';
public const PAYMENT_HELD       = 'held';
public const PAYMENT_RELEASED   = 'released';
public const PAYMENT_REFUNDED   = 'refunded';
public const PAYMENT_FROZEN     = 'frozen_under_investigation';

// Scopes for dashboard/reporting
public function scopeDisputed($query)
{
    return $query->whereIn('status', ['disputed', 'under_investigation']);
}

public function scopeActiveWorkflow($query)
{
    return $query->whereIn('status', [
        'pending_inspection', 'inspected', 'waiting_quote',
        'quoted', 'quote_approved', 'payment_held', 'in_progress',
    ]);
}

public function scopeEscrowHeld($query)
{
    return $query->whereIn('payment_status', ['escrow', 'held', 'pending_release']);
}

public function scopeFrozen($query)
{
    return $query->where('payment_status', 'frozen_under_investigation');
}
```

---

## 5. Phase 3: Quote System Enhancement

### 5.1 Migration: Enhance Quotes Table

**`database/migrations/YYYY_MM_DD_000020_enhance_quotes_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            // Add new fields
            $table->foreignId('handyman_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->after('provider_id');

            $table->integer('estimated_duration')
                  ->nullable()
                  ->comment('Estimated job duration in minutes')
                  ->after('price');

            $table->text('inspection_notes')
                  ->nullable()
                  ->after('notes');

            $table->timestamp('approved_at')
                  ->nullable()
                  ->after('status');

            $table->timestamp('rejected_at')
                  ->nullable()
                  ->after('approved_at');

            $table->text('rejection_reason')
                  ->nullable()
                  ->after('rejected_at');

            // Add index for faster lookups
            $table->index('status');
            $table->index(['booking_id', 'status'], 'quotes_booking_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['handyman_id']);
            $table->dropColumn([
                'handyman_id',
                'estimated_duration',
                'inspection_notes',
                'approved_at',
                'rejected_at',
                'rejection_reason',
            ]);
            $table->dropIndex('quotes_booking_status_idx');
        });
    }
};
```

### 5.2 Enhanced Quote Model

**`app/Models/Quote.php`** (update fillable and add methods):

```php
protected $fillable = [
    'booking_id',
    'provider_id',
    'handyman_id',
    'price',
    'estimated_duration',
    'notes',
    'inspection_notes',
    'status',
    'approved_at',
    'rejected_at',
    'rejection_reason',
];

protected $casts = [
    'booking_id'        => 'integer',
    'provider_id'       => 'integer',
    'handyman_id'       => 'integer',
    'price'             => 'double',
    'estimated_duration'=> 'integer',
    'approved_at'       => 'datetime',
    'rejected_at'       => 'datetime',
];

// Relationships
public function handyman()
{
    return $this->belongsTo(User::class, 'handyman_id', 'id')->withTrashed();
}

public function bookingActivities()
{
    return $this->morphMany(BookingActivity::class, 'activitable');
}

// Scopes
public function scopeRejected($query)
{
    return $query->where('status', 'rejected');
}

public function scopeByProvider($query, int $providerId)
{
    return $query->where('provider_id', $providerId);
}

// Accessors
public function getIsExpiredAttribute(): bool
{
    return $this->status === 'pending'
        && $this->created_at->addDays(7)->isPast();
}
```

### 5.3 Quote API Controller Enhancement

**`app/Http/Controllers/API/QuoteController.php`** (key methods):

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Quote;
use App\Services\BookingWorkflowService;
use App\Http\Resources\API\QuoteResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuoteController extends Controller
{
    public function __construct(
        protected BookingWorkflowService $workflowService
    ) {}

    /**
     * Provider submits a new quote after inspection.
     * POST /api/quote/submit
     */
    public function submit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id'        => 'required|exists:bookings,id',
            'price'             => 'required|numeric|min:0',
            'estimated_duration'=> 'nullable|integer|min:1',
            'notes'             => 'nullable|string|max:2000',
            'inspection_notes'  => 'nullable|string|max:5000',
            'handyman_id'       => 'nullable|exists:users,id',
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);
        $actorId = auth()->id();

        $result = $this->workflowService->submitQuote(
            $booking,
            $actorId,
            $validated['price'],
            $validated['notes'] ?? null,
        );

        if (! $result['ok']) {
            return response()->json($result, $result['code']);
        }

        // Update the quote with new fields
        $quote = Quote::find($booking->quote_id);
        if ($quote) {
            $quote->update([
                'estimated_duration' => $validated['estimated_duration'],
                'inspection_notes'   => $validated['inspection_notes'],
                'handyman_id'        => $validated['handyman_id'],
            ]);
        }

        return response()->json([
            'status' => 'true',
            'message' => __('messages.quote_submitted'),
            'data'    => new QuoteResource($quote ?? $booking),
        ]);
    }

    /**
     * Get quote history for a booking.
     * GET /api/quote/history/{bookingId}
     */
    public function history(int $bookingId): JsonResponse
    {
        $quotes = Quote::where('booking_id', $bookingId)
            ->with(['provider:id,display_name,email', 'handyman:id,display_name'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'true',
            'data'   => QuoteResource::collection($quotes),
        ]);
    }

    /**
     * Admin: Get all quotes with filters.
     * GET /api/admin/quotes
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $quotes = Quote::with([
            'booking:id,customer_id,status',
            'booking.customer:id,display_name',
            'provider:id,display_name',
        ])
        ->when($request->status, fn($q, $s) => $q->where('status', $s))
        ->when($request->provider_id, fn($q, $id) => $q->where('provider_id', $id))
        ->orderBy('created_at', 'desc')
        ->paginate($request->per_page ?? 15);

        return response()->json([
            'status' => 'true',
            'data'   => QuoteResource::collection($quotes),
        ]);
    }
}
```

### 5.4 Quote Resource

**`app/Http/Resources/API/QuoteResource.php`**

```php
<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;

class QuoteResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'booking_id'        => $this->booking_id,
            'provider_id'       => $this->provider_id,
            'provider_name'     => optional($this->provider)->display_name,
            'handyman_id'       => $this->handyman_id,
            'handyman_name'     => optional($this->handyman)->display_name,
            'price'             => (float) $this->price,
            'estimated_duration'=> $this->estimated_duration,
            'notes'             => $this->notes,
            'inspection_notes'  => $this->inspection_notes,
            'status'            => $this->status,
            'approved_at'       => $this->approved_at,
            'rejected_at'       => $this->rejected_at,
            'rejection_reason'  => $this->rejection_reason,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
```

### 5.5 Quotes Admin Page Routes

Add to `routes/web.php`:

```php
// Quote Management (Admin Panel)
Route::group(['middleware' => ['auth', 'permission:booking list']], function () {
    Route::get('quotes', [QuoteController::class, 'adminIndex'])->name('quote.admin.index');
    Route::get('quotes-index-data', [QuoteController::class, 'adminIndexData'])->name('quote.admin.index-data');
    Route::get('quotes/{id}', [QuoteController::class, 'adminShow'])->name('quote.admin.show');
    Route::post('quotes/{id}/resolve', [QuoteController::class, 'adminResolve'])->name('quote.admin.resolve');
});
```

### 5.6 Vue Component: Quote Analytics Widget

**`resources/js/sections/QuoteStatsWidget.vue`**

```vue
<template>
  <div class="row">
    <div class="col-md-3" v-for="stat in stats" :key="stat.label">
      <div class="card stat-card" :style="{ borderLeftColor: stat.color }">
        <div class="card-body">
          <h6 class="text-muted mb-1">{{ stat.label }}</h6>
          <h3 class="mb-0">{{ stat.count }}</h3>
          <small :class="stat.trend >= 0 ? 'text-success' : 'text-danger'">
            {{ stat.trend >= 0 ? '+' : '' }}{{ stat.trend }}% vs last week
          </small>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      stats: [
        { label: 'Pending Quotes', count: 0, color: '#faa938', trend: 0 },
        { label: 'Approved Quotes', count: 0, color: '#3CAE5C', trend: 0 },
        { label: 'Inspection Requests', count: 0, color: '#397D8D', trend: 0 },
        { label: 'Avg. Quote Value', count: '0 SAR', color: '#C9A84C', trend: 0 },
      ],
    };
  },
  mounted() {
    this.fetchStats();
  },
  methods: {
    async fetchStats() {
      const { data } = await axios.get('/api/admin/quote-stats');
      if (data.status === 'true') {
        this.stats = data.data;
      }
    },
  },
};
</script>
```

---

## 6. Phase 4: Payment Hold / Escrow System

### 6.1 Migration: Escrow Transactions Table

**`database/migrations/YYYY_MM_DD_000030_create_escrow_transactions_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escrow_transactions', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic: can be linked to booking or other entities
            $table->morphs('escrowable');
            
            $table->foreignId('customer_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            
            $table->foreignId('provider_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            
            $table->foreignId('payment_id')
                  ->nullable()
                  ->constrained('payments')
                  ->nullOnDelete();
            
            // Financial
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('held_amount', 12, 2)->default(0);
            $table->decimal('released_amount', 12, 2)->default(0);
            $table->decimal('refunded_amount', 12, 2)->default(0);
            $table->decimal('penalty_deducted', 12, 2)->default(0);
            
            // Status
            $table->enum('status', [
                'held',
                'released',
                'refunded',
                'frozen_under_investigation',
                'partially_released',
            ])->default('held');
            
            // Tracking
            $table->timestamp('held_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('frozen_at')->nullable();
            $table->timestamp('scheduled_release_at')->nullable();
            
            // Audit
            $table->text('notes')->nullable();
            $table->foreignId('actioned_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('status');
            $table->index('customer_id');
            $table->index('provider_id');
            $table->index(['escrowable_type', 'escrowable_id'], 'escrowable_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escrow_transactions');
    }
};
```

### 6.2 Escrow Service

**`app/Services/EscrowService.php`**

```php
<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\EscrowTransaction;
use App\Models\WalletHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Owns all escrow/held-payment operations.
 * Every money movement is recorded in escrow_transactions for audit.
 */
class EscrowService
{
    /**
     * Hold payment in escrow after customer pays.
     * Called from PaymentController@savePayment.
     */
    public function hold(Booking $booking, Payment $payment, int $actionedBy): EscrowTransaction
    {
        return DB::transaction(function () use ($booking, $payment, $actionedBy) {
            $escrow = EscrowTransaction::create([
                'escrowable_type' => Booking::class,
                'escrowable_id'   => $booking->id,
                'customer_id'     => $booking->customer_id,
                'provider_id'     => $booking->provider_id,
                'payment_id'      => $payment->id,
                'amount'          => $payment->total_amount,
                'held_amount'     => $payment->total_amount,
                'status'          => 'held',
                'held_at'         => now(),
                'actioned_by'     => $actionedBy,
                'notes'           => 'Payment held in escrow after quote approval.',
            ]);

            // Update booking payment_status
            $booking->payment_status = 'escrow';
            $booking->save();

            // Log financial activity
            WalletHistory::create([
                'user_id'          => $booking->customer_id,
                'datetime'         => now(),
                'activity_type'    => 'payment_held',
                'activity_message' => "Payment of {$payment->total_amount} SAR held in escrow for booking #{$booking->id}.",
                'activity_data'    => json_encode([
                    'credit_debit_amount' => $payment->total_amount,
                    'transaction_type'    => 'debit',
                    'booking_id'          => $booking->id,
                    'escrow_id'           => $escrow->id,
                ]),
            ]);

            return $escrow;
        });
    }

    /**
     * Release escrow to provider after job completion.
     */
    public function release(Booking $booking, int $actionedBy): EscrowTransaction
    {
        return DB::transaction(function () use ($booking, $actionedBy) {
            /** @var EscrowTransaction $escrow */
            $escrow = EscrowTransaction::where('escrowable_id', $booking->id)
                ->where('escrowable_type', Booking::class)
                ->whereIn('status', ['held', 'frozen_under_investigation'])
                ->latest()
                ->firstOrFail();

            $escrow->update([
                'status'           => 'released',
                'released_amount'  => $escrow->held_amount,
                'held_amount'      => 0,
                'released_at'      => now(),
                'actioned_by'      => $actionedBy,
                'notes'            => 'Released after job completion.',
            ]);

            $booking->payment_status = 'released';
            $booking->save();

            // Credit provider's wallet
            $providerWallet = $booking->provider->wallet ?? 
                Wallet::create(['user_id' => $booking->provider_id, 'amount' => 0]);
            $providerWallet->increment('amount', $escrow->released_amount);

            WalletHistory::create([
                'user_id'          => $booking->provider_id,
                'datetime'         => now(),
                'activity_type'    => 'escrow_released',
                'activity_message' => "Escrow of {$escrow->amount} SAR released for booking #{$booking->id}.",
                'activity_data'    => json_encode([
                    'credit_debit_amount' => $escrow->released_amount,
                    'transaction_type'    => 'credit',
                    'booking_id'          => $booking->id,
                    'escrow_id'           => $escrow->id,
                ]),
            ]);

            return $escrow;
        });
    }

    /**
     * Refund escrow back to customer (for cancellations or disputes resolved in customer's favor).
     */
    public function refund(Booking $booking, int $actionedBy, ?string $reason = null): EscrowTransaction
    {
        return DB::transaction(function () use ($booking, $actionedBy, $reason) {
            $escrow = EscrowTransaction::where('escrowable_id', $booking->id)
                ->where('escrowable_type', Booking::class)
                ->whereIn('status', ['held', 'frozen_under_investigation'])
                ->latest()
                ->firstOrFail();

            $escrow->update([
                'status'           => 'refunded',
                'refunded_amount'  => $escrow->held_amount,
                'held_amount'      => 0,
                'refunded_at'      => now(),
                'actioned_by'      => $actionedBy,
                'notes'            => $reason ?? 'Full refund processed.',
            ]);

            $booking->payment_status = 'refunded';
            $booking->save();

            // Credit customer's wallet
            $customerWallet = $booking->customer->wallet ??
                Wallet::create(['user_id' => $booking->customer_id, 'amount' => 0]);
            $customerWallet->increment('amount', $escrow->refunded_amount);

            WalletHistory::create([
                'user_id'          => $booking->customer_id,
                'datetime'         => now(),
                'activity_type'    => 'escrow_refunded',
                'activity_message' => "Escrow of {$escrow->amount} SAR refunded for booking #{$booking->id}.",
                'activity_data'    => json_encode([
                    'credit_debit_amount' => $escrow->refunded_amount,
                    'transaction_type'    => 'credit',
                    'booking_id'          => $booking->id,
                    'escrow_id'           => $escrow->id,
                ]),
            ]);

            return $escrow;
        });
    }

    /**
     * Freeze escrow during investigation.
     */
    public function freeze(Booking $booking, int $actionedBy): EscrowTransaction
    {
        $escrow = EscrowTransaction::where('escrowable_id', $booking->id)
            ->where('escrowable_type', Booking::class)
            ->where('status', 'held')
            ->latest()
            ->firstOrFail();

        $escrow->update([
            'status'    => 'frozen_under_investigation',
            'frozen_at' => now(),
            'actioned_by' => $actionedBy,
            'notes'     => 'Frozen due to dispute investigation.',
        ]);

        return $escrow;
    }

    /**
     * Deduct penalty from escrow (admin action during investigation).
     */
    public function deductPenalty(
        Booking $booking,
        float $penaltyAmount,
        int $actionedBy,
        string $reason
    ): EscrowTransaction {
        return DB::transaction(function () use ($booking, $penaltyAmount, $actionedBy, $reason) {
            $escrow = EscrowTransaction::where('escrowable_id', $booking->id)
                ->where('escrowable_type', Booking::class)
                ->where('status', 'frozen_under_investigation')
                ->latest()
                ->firstOrFail();

            $newHeld = $escrow->held_amount - $penaltyAmount;

            $escrow->update([
                'held_amount'      => max(0, $newHeld),
                'penalty_deducted' => $escrow->penalty_deducted + $penaltyAmount,
                'notes'            => "Penalty deducted: {$penaltyAmount} SAR. Reason: {$reason}",
                'actioned_by'      => $actionedBy,
            ]);

            return $escrow->fresh();
        });
    }

    /**
     * Get summary stats for dashboard.
     */
    public function getDashboardStats(): array
    {
        return [
            'total_held'      => EscrowTransaction::where('status', 'held')->sum('held_amount'),
            'total_released'  => EscrowTransaction::where('status', 'released')->sum('released_amount'),
            'total_refunded'  => EscrowTransaction::where('status', 'refunded')->sum('refunded_amount'),
            'total_frozen'    => EscrowTransaction::where('status', 'frozen_under_investigation')->sum('held_amount'),
            'total_penalties' => EscrowTransaction::sum('penalty_deducted'),
            'count_active'    => EscrowTransaction::whereIn('status', ['held', 'frozen_under_investigation'])->count(),
        ];
    }
}
```

### 6.3 Escrow Model

**`app/Models/EscrowTransaction.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EscrowTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'escrow_transactions';

    protected $fillable = [
        'escrowable_type',
        'escrowable_id',
        'customer_id',
        'provider_id',
        'payment_id',
        'amount',
        'held_amount',
        'released_amount',
        'refunded_amount',
        'penalty_deducted',
        'status',
        'held_at',
        'released_at',
        'refunded_at',
        'frozen_at',
        'scheduled_release_at',
        'notes',
        'actioned_by',
    ];

    protected $casts = [
        'amount'               => 'decimal:2',
        'held_amount'          => 'decimal:2',
        'released_amount'      => 'decimal:2',
        'refunded_amount'      => 'decimal:2',
        'penalty_deducted'     => 'decimal:2',
        'held_at'              => 'datetime',
        'released_at'          => 'datetime',
        'refunded_at'          => 'datetime',
        'frozen_at'            => 'datetime',
        'scheduled_release_at' => 'datetime',
    ];

    // ─── Relationships ───────────────────────────────────────

    public function escrowable()
    {
        return $this->morphTo();
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function actionedBy()
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }

    // ─── Scopes ─────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['held', 'frozen_under_investigation']);
    }

    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByProvider($query, int $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    public function scopeScheduledForRelease($query)
    {
        return $query->where('status', 'held')
            ->whereNotNull('scheduled_release_at')
            ->where('scheduled_release_at', '<=', now());
    }
}
```

### 6.4 Console Command: Auto-Release Scheduler

**`app/Console/Commands/ReleaseScheduledEscrow.php`**

```php
<?php

namespace App\Console\Commands;

use App\Models\EscrowTransaction;
use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReleaseScheduledEscrow extends Command
{
    protected $signature = 'sand:release-escrow';
    protected $description = 'Auto-release escrow transactions scheduled for release.';

    public function handle(): int
    {
        $released = 0;

        EscrowTransaction::scheduledForRelease()
            ->chunk(100, function ($transactions) use (&$released) {
                foreach ($transactions as $escrow) {
                    DB::transaction(function () use ($escrow) {
                        $booking = Booking::find($escrow->escrowable_id);
                        if (! $booking) return;

                        $booking->payment_status = 'released';
                        $booking->save();

                        $escrow->update([
                            'status'          => 'released',
                            'released_amount' => $escrow->held_amount,
                            'held_amount'     => 0,
                            'released_at'     => now(),
                            'notes'           => 'Scheduled auto-release.',
                        ]);
                    });

                    $released++;
                }
            });

        $this->info("Released {$released} escrow transactions.");
        return Command::SUCCESS;
    }
}
```

Register in `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('sand:release-escrow')->hourly();
    // ... existing commands
}
```

---

## 7. Phase 5: Refundable Insurance System

### 7.1 Migration: Insurance Tables

**`database/migrations/YYYY_MM_DD_000040_create_insurance_system.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Add insurance fields to users table ──────────────
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('insurance_balance', 12, 2)
                  ->default(0)
                  ->after('loyalty_points')
                  ->comment('Current refundable insurance balance');

            $table->decimal('insurance_target', 12, 2)
                  ->default(100.00)
                  ->after('insurance_balance')
                  ->comment('Required insurance amount (default 100 SAR)');

            $table->enum('insurance_status', [
                'unpaid',
                'partial',
                'active',
                'frozen',
                'refunded',
            ])->default('unpaid')->after('insurance_target');

            $table->decimal('frozen_amount', 12, 2)
                  ->default(0)
                  ->after('insurance_status')
                  ->comment('Amount frozen during investigation');

            $table->index('insurance_status', 'users_insurance_status_idx');
        });

        // ── Insurance transactions audit table ───────────────
        Schema::create('insurance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('related'); // polymorphic: booking, payout, etc.

            $table->decimal('amount', 12, 2);
            $table->enum('type', [
                'deposit',
                'deduction',
                'refund',
                'gradual_deduction',
                'penalty',
            ]);
            $table->enum('direction', ['credit', 'debit']);

            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);

            $table->text('reason')->nullable();
            $table->foreignId('actioned_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('user_id');
            $table->index('type');
        });

        // ── Seed default insurance target setting ────────────
        DB::table('settings')->updateOrInsert(
            ['type' => 'insurance', 'key' => 'insurance-config'],
            ['value' => json_encode([
                'default_target'      => 100,
                'currency'            => 'SAR',
                'allow_gradual'       => true,
                'gradual_percentage'  => 10, // 10% from each payout
                'auto_deduct'         => true,
            ])]
        );
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'insurance_balance',
                'insurance_target',
                'insurance_status',
                'frozen_amount',
            ]);
            $table->dropIndex('users_insurance_status_idx');
        });

        Schema::dropIfExists('insurance_transactions');
        DB::table('settings')->where('type', 'insurance')->delete();
    }
};
```

### 7.2 Insurance Service

**`app/Services/InsuranceService.php`**

```php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\InsuranceTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Manages the refundable insurance deposit system for providers and handymen.
 *
 * Business Rules:
 * - Each provider/handyman needs a refundable insurance balance of X SAR (default 100)
 * - Can be paid directly or deducted gradually from earnings (10% per payout)
 * - Cannot withdraw protected balance
 * - Admin can deduct from insurance during disputes
 * - Fully refundable on account closure
 */
class InsuranceService
{
    /**
     * Get insurance config from settings.
     */
    public function getConfig(): array
    {
        $setting = \DB::table('settings')
            ->where('type', 'insurance')
            ->where('key', 'insurance-config')
            ->first();

        return $setting
            ? json_decode($setting->value, true)
            : ['default_target' => 100, 'currency' => 'SAR', 'allow_gradual' => true, 'gradual_percentage' => 10, 'auto_deduct' => true];
    }

    /**
     * Process a direct insurance deposit payment.
     */
    public function deposit(User $user, float $amount, int $actionedBy): InsuranceTransaction
    {
        return DB::transaction(function () use ($user, $amount, $actionedBy) {
            $balanceBefore = $user->insurance_balance;

            $user->increment('insurance_balance', $amount);
            $user->insurance_status = $user->insurance_balance >= $user->insurance_target
                ? 'active'
                : 'partial';
            $user->save();

            $transaction = InsuranceTransaction::create([
                'user_id'        => $user->id,
                'related_type'   => User::class,
                'related_id'     => $user->id,
                'amount'         => $amount,
                'type'           => 'deposit',
                'direction'      => 'credit',
                'balance_before' => $balanceBefore,
                'balance_after'  => $user->insurance_balance,
                'reason'         => 'Direct insurance deposit',
                'actioned_by'    => $actionedBy,
            ]);

            return $transaction;
        });
    }

    /**
     * Gradually deduct from provider payout toward insurance target.
     * Called when processing provider payouts.
     *
     * @return float Amount actually deducted
     */
    public function deductGradually(User $user, float $payoutAmount): float
    {
        if ($user->insurance_status === 'active') {
            return 0; // Already at target
        }

        $config = $this->getConfig();
        $deductionPercent = $config['gradual_percentage'] ?? 10;
        $deductionAmount = round($payoutAmount * ($deductionPercent / 100), 2);

        // Don't over-deduct beyond target
        $shortfall = $user->insurance_target - $user->insurance_balance;
        $actualDeduction = min($deductionAmount, $shortfall);

        if ($actualDeduction <= 0) {
            return 0;
        }

        DB::transaction(function () use ($user, $actualDeduction) {
            $balanceBefore = $user->insurance_balance;

            $user->increment('insurance_balance', $actualDeduction);
            $user->insurance_status = $user->insurance_balance >= $user->insurance_target
                ? 'active'
                : 'partial';
            $user->save();

            InsuranceTransaction::create([
                'user_id'        => $user->id,
                'related_type'   => User::class,
                'related_id'     => $user->id,
                'amount'         => $actualDeduction,
                'type'           => 'gradual_deduction',
                'direction'      => 'credit',
                'balance_before' => $balanceBefore,
                'balance_after'  => $user->insurance_balance,
                'reason'         => "Gradual deduction from payout ({$deductionPercent}%)",
                'actioned_by'    => 1, // System
            ]);
        });

        return $actualDeduction;
    }

    /**
     * Admin deducts from insurance as penalty during dispute.
     */
    public function deductPenalty(User $user, float $amount, string $reason, int $actionedBy): InsuranceTransaction
    {
        return DB::transaction(function () use ($user, $amount, $reason, $actionedBy) {
            $balanceBefore = $user->insurance_balance;
            $actualDeduction = min($amount, $user->insurance_balance);

            $user->decrement('insurance_balance', $actualDeduction);
            $user->insurance_status = $user->insurance_balance <= 0
                ? 'unpaid'
                : ($user->insurance_balance >= $user->insurance_target ? 'active' : 'partial');
            $user->save();

            $transaction = InsuranceTransaction::create([
                'user_id'        => $user->id,
                'related_type'   => User::class,
                'related_id'     => $user->id,
                'amount'         => $actualDeduction,
                'type'           => 'penalty',
                'direction'      => 'debit',
                'balance_before' => $balanceBefore,
                'balance_after'  => $user->insurance_balance,
                'reason'         => $reason,
                'actioned_by'    => $actionedBy,
            ]);

            return $transaction;
        });
    }

    /**
     * Freeze insurance balance during investigation.
     */
    public function freeze(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->frozen_amount = $user->insurance_balance;
            $user->insurance_status = 'frozen';
            $user->save();
        });
    }

    /**
     * Unfreeze insurance after investigation resolved.
     */
    public function unfreeze(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->frozen_amount = 0;
            $user->insurance_status = $user->insurance_balance >= $user->insurance_target
                ? 'active'
                : ($user->insurance_balance > 0 ? 'partial' : 'unpaid');
            $user->save();
        });
    }

    /**
     * Full refund on account closure.
     */
    public function refundOnClosure(User $user, int $actionedBy): float
    {
        return DB::transaction(function () use ($user, $actionedBy) {
            $refundAmount = $user->insurance_balance;
            if ($refundAmount <= 0) return 0;

            $balanceBefore = $user->insurance_balance;

            $user->insurance_balance = 0;
            $user->insurance_status = 'refunded';
            $user->save();

            InsuranceTransaction::create([
                'user_id'        => $user->id,
                'related_type'   => User::class,
                'related_id'     => $user->id,
                'amount'         => $refundAmount,
                'type'           => 'refund',
                'direction'      => 'debit',
                'balance_before' => $balanceBefore,
                'balance_after'  => 0,
                'reason'         => 'Full insurance refund on account closure',
                'actioned_by'    => $actionedBy,
            ]);

            // Credit user's wallet
            $wallet = $user->wallet ?? Wallet::create(['user_id' => $user->id, 'amount' => 0]);
            $wallet->increment('amount', $refundAmount);

            return $refundAmount;
        });
    }

    /**
     * Check if user's insurance is active (has met target).
     */
    public function isCovered(User $user): bool
    {
        return $user->insurance_status === 'active'
            && $user->insurance_balance >= $user->insurance_target;
    }

    /**
     * Get user's withdrawable balance (total - insurance - frozen).
     */
    public function getWithdrawableBalance(User $user): float
    {
        if (! $user->wallet) return 0;
        $protected = max($user->insurance_balance, $user->frozen_amount);
        return max(0, $user->wallet->amount - $protected);
    }
}
```

### 7.3 Insurance Model

**`app/Models/InsuranceTransaction.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceTransaction extends Model
{
    use HasFactory;

    protected $table = 'insurance_transactions';

    protected $fillable = [
        'user_id',
        'related_type',
        'related_id',
        'amount',
        'type',
        'direction',
        'balance_before',
        'balance_after',
        'reason',
        'actioned_by',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after'  => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function related()
    {
        return $this->morphTo();
    }

    public function actionedBy()
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }
}
```

---

## 8. Phase 6: Investigation Mode

### 8.1 Migration: Investigation Logs Table

**`database/migrations/YYYY_MM_DD_000050_create_investigation_logs_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investigation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opened_by')->constrained('users')->cascadeOnDelete();

            // Investigation details
            $table->text('dispute_reason');
            $table->enum('status', [
                'open',
                'under_investigation',
                'resolved',
                'closed',
            ])->default('open');

            // Resolution
            $table->enum('resolution', [
                'pending',
                'released_to_provider',
                'refunded_to_customer',
                'partial_refund',
                'penalty_deducted',
                'dismissed',
            ])->default('pending');

            // Financial impact
            $table->decimal('penalty_amount', 12, 2)->default(0);
            $table->decimal('refund_amount', 12, 2)->default(0);

            // Audit
            $table->text('admin_notes')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('booking_id');
        });

        // Investigation activity log entries
        Schema::create('investigation_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investigation_id')
                  ->constrained('investigation_logs')
                  ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action'); // note, evidence_uploaded, status_change, etc.
            $table->text('description')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamps();

            $table->index('investigation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investigation_activities');
        Schema::dropIfExists('investigation_logs');
    }
};
```

### 8.2 Investigation Service

**`app/Services/InvestigationService.php`**

```php
<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\InvestigationLog;
use App\Models\EscrowTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InvestigationService
{
    public function __construct(
        protected EscrowService $escrowService,
        protected InsuranceService $insuranceService,
    ) {}

    /**
     * Open a new investigation for a disputed booking.
     */
    public function open(Booking $booking, int $adminId, string $reason): InvestigationLog
    {
        return DB::transaction(function () use ($booking, $adminId, $reason) {
            // Create investigation log
            $investigation = InvestigationLog::create([
                'booking_id'     => $booking->id,
                'opened_by'      => $adminId,
                'dispute_reason' => $reason,
                'status'         => 'open',
                'resolution'     => 'pending',
            ]);

            // Update booking status
            $booking->status = 'disputed';
            $booking->dispute_reason = $reason;
            $booking->frozen_until = now()->addDays(14);
            $booking->save();

            // Freeze escrow
            $this->escrowService->freeze($booking, $adminId);

            // Freeze provider/handyman insurance
            if ($booking->provider) {
                $this->insuranceService->freeze($booking->provider);
            }

            // Log activity
            $booking->activities()->create([
                'activity_type'    => 'investigation_opened',
                'activity_message' => "Investigation opened by admin: {$reason}",
                'created_by'       => $adminId,
            ]);

            return $investigation;
        });
    }

    /**
     * Escalate to active investigation (freezes all financial operations).
     */
    public function escalate(int $investigationId, int $adminId): InvestigationLog
    {
        $investigation = InvestigationLog::findOrFail($investigationId);
        $booking = $investigation->booking;

        return DB::transaction(function () use ($investigation, $booking, $adminId) {
            $investigation->update(['status' => 'under_investigation']);
            $booking->update(['status' => 'under_investigation']);

            $booking->activities()->create([
                'activity_type'    => 'investigation_escalated',
                'activity_message' => 'Investigation escalated. All funds frozen.',
                'created_by'       => $adminId,
            ]);

            return $investigation->fresh();
        });
    }

    /**
     * Resolve investigation with a specific outcome.
     */
    public function resolve(
        int $investigationId,
        int $adminId,
        string $resolution,
        ?float $penaltyAmount = null,
        ?string $notes = null
    ): InvestigationLog {
        $investigation = InvestigationLog::findOrFail($investigationId);
        $booking = $investigation->booking;

        return DB::transaction(function () use ($investigation, $booking, $adminId, $resolution, $penaltyAmount, $notes) {
            $updateData = [
                'status'           => 'resolved',
                'resolution'       => $resolution,
                'resolved_by'      => $adminId,
                'resolved_at'      => now(),
                'resolution_notes' => $notes,
            ];

            match ($resolution) {
                'released_to_provider' => $this->resolveRelease($booking, $adminId),
                'refunded_to_customer' => $this->resolveRefund($booking, $adminId),
                'partial_refund'       => $this->resolvePartialRefund($booking, $adminId, $penaltyAmount),
                'penalty_deducted'     => $this->resolvePenalty($booking, $adminId, $penaltyAmount),
                'dismissed'            => $this->resolveDismissed($booking, $adminId),
                default                => throw new \InvalidArgumentException("Invalid resolution: {$resolution}"),
            };

            if ($resolution === 'penalty_deducted' && $penaltyAmount) {
                $updateData['penalty_amount'] = $penaltyAmount;
            }

            $investigation->update($updateData);

            // Unfreeze insurance
            if ($booking->provider) {
                $this->insuranceService->unfreeze($booking->provider);
            }

            // Update booking
            $booking->status = 'resolved';
            $booking->investigation_notes = $notes;
            $booking->save();

            $booking->activities()->create([
                'activity_type'    => 'investigation_resolved',
                'activity_message' => "Investigation resolved: {$resolution}",
                'created_by'       => $adminId,
            ]);

            return $investigation->fresh();
        });
    }

    // ── Private resolution handlers ──────────────────────────

    private function resolveRelease(Booking $booking, int $adminId): void
    {
        $this->escrowService->release($booking, $adminId);
    }

    private function resolveRefund(Booking $booking, int $adminId): void
    {
        $this->escrowService->refund($booking, $adminId);
    }

    private function resolvePartialRefund(Booking $booking, int $adminId, ?float $amount): void
    {
        $this->escrowService->deductPenalty($booking, $amount ?? 0, $adminId, 'Partial refund penalty');
        $this->escrowService->release($booking, $adminId);
    }

    private function resolvePenalty(Booking $booking, int $adminId, ?float $amount): void
    {
        // Deduct from provider insurance
        if ($booking->provider && $amount) {
            $this->insuranceService->deductPenalty(
                $booking->provider,
                $amount,
                "Penalty for dispute on booking #{$booking->id}",
                $adminId
            );
        }

        // Release remaining escrow to customer as refund
        $this->escrowService->refund($booking, $adminId);
    }

    private function resolveDismissed(Booking $booking, int $adminId): void
    {
        $this->escrowService->release($booking, $adminId);
    }

    /**
     * Get dashboard stats.
     */
    public function getDashboardStats(): array
    {
        return [
            'active_investigations' => InvestigationLog::whereIn('status', ['open', 'under_investigation'])->count(),
            'pending_disputes'      => Booking::where('status', 'disputed')->count(),
            'resolved_this_month'   => InvestigationLog::where('status', 'resolved')
                ->whereMonth('resolved_at', now()->month)->count(),
            'total_penalties'       => InvestigationLog::sum('penalty_amount'),
        ];
    }
}
```

### 8.3 Investigation Model

**`app/Models/InvestigationLog.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvestigationLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'investigation_logs';

    protected $fillable = [
        'booking_id',
        'opened_by',
        'dispute_reason',
        'status',
        'resolution',
        'penalty_amount',
        'refund_amount',
        'admin_notes',
        'resolution_notes',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'penalty_amount' => 'decimal:2',
        'refund_amount'  => 'decimal:2',
        'resolved_at'    => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function activities()
    {
        return $this->hasMany(InvestigationActivity::class, 'investigation_id');
    }
}
```

---

## 9. Phase 7: Legal Acknowledgements

### 9.1 Migration: Agreement Tables

**`database/migrations/YYYY_MM_DD_000060_create_legal_agreements_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_agreements', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // provider_agreement, customer_agreement, handyman_agreement
            $table->text('content_ar'); // Arabic text
            $table->text('content_en')->nullable(); // English translation
            $table->string('version', 20)->default('1.0');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['type', 'version']);
            $table->index('type');
            $table->index('is_active');
        });

        Schema::create('agreement_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('legal_agreement_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('accepted_at');

            $table->unique(['user_id', 'legal_agreement_id'], 'user_agreement_unique');
            $table->index('user_id');
            $table->index('legal_agreement_id');

            $table->timestamps();
        });

        // Seed default agreements
        DB::table('legal_agreements')->insert([
            [
                'type'       => 'provider_agreement',
                'content_ar' => 'أقر أنا الفني بمسؤوليتي الكاملة عن جودة العمل المقدم للعميل. أتعهد بالالتزام بمعايير الجودة والأمان المعتمدة في منصة سند. أقر بأن أي مخالفة ستؤدي إلى خصم من التأمين أو تجميد الحساب وفقاً لسياسة المنصة.',
                'content_en' => 'I, the technician, acknowledge full responsibility for the quality of work provided to the customer. I commit to adhering to the quality and safety standards approved by the Sand platform. I acknowledge that any violation will result in insurance deduction or account suspension according to platform policy.',
                'version'    => '1.0',
                'is_active'  => true,
                'created_by' => 1,
            ],
            [
                'type'       => 'customer_agreement',
                'content_ar' => 'نحن في سند نضمن لك جودة الخدمة المقدمة. في حال وجود أي مشكلة، يرجى التواصل مع فريق الدعم خلال 24 ساعة من انتهاء الخدمة. سيتم الاحتفاظ بالمبلغ في حساب ضمان حتى تأكيد رضاك عن الخدمة.',
                'content_en' => 'At Sand, we guarantee the quality of service provided. If there is any issue, please contact the support team within 24 hours of service completion. The amount will be held in escrow until your satisfaction with the service is confirmed.',
                'version'    => '1.0',
                'is_active'  => true,
                'created_by' => 1,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_acceptances');
        Schema::dropIfExists('legal_agreements');
    }
};
```

### 9.2 Agreement Service

**`app/Services/AgreementService.php`**

```php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\LegalAgreement;
use App\Models\AgreementAcceptance;
use Illuminate\Support\Facades\DB;

class AgreementService
{
    /**
     * Get the active agreement for a given type.
     */
    public function getActiveAgreement(string $type): ?LegalAgreement
    {
        return LegalAgreement::where('type', $type)
            ->where('is_active', true)
            ->latest('version')
            ->first();
    }

    /**
     * Check if user has accepted the latest version.
     */
    public function hasAcceptedLatest(User $user, string $type): bool
    {
        $agreement = $this->getActiveAgreement($type);
        if (! $agreement) return false;

        return AgreementAcceptance::where('user_id', $user->id)
            ->where('legal_agreement_id', $agreement->id)
            ->exists();
    }

    /**
     * Record user acceptance of an agreement.
     */
    public function accept(User $user, string $type, string $ipAddress, ?string $userAgent): AgreementAcceptance
    {
        $agreement = $this->getActiveAgreement($type);
        if (! $agreement) {
            throw new \RuntimeException("No active agreement found for type: {$type}");
        }

        return DB::transaction(function () use ($user, $agreement, $ipAddress, $userAgent) {
            return AgreementAcceptance::updateOrCreate(
                [
                    'user_id'            => $user->id,
                    'legal_agreement_id' => $agreement->id,
                ],
                [
                    'ip_address'   => $ipAddress,
                    'user_agent'   => $userAgent,
                    'accepted_at'  => now(),
                ]
            );
        });
    }

    /**
     * Get acceptance history for a user.
     */
    public function getUserHistory(User $user): array
    {
        return AgreementAcceptance::where('user_id', $user->id)
            ->with('agreement')
            ->orderBy('accepted_at', 'desc')
            ->get()
            ->toArray();
    }
}
```

### 9.3 Agreement Middleware

**`app/Http/Middleware/RequireAgreementAcceptance.php`**

```php
<?php

namespace App\Http\Middleware;

use App\Services\AgreementService;
use Closure;
use Illuminate\Http\Request;

class RequireAgreementAcceptance
{
    public function __construct(
        protected AgreementService $agreementService
    ) {}

    public function handle(Request $request, Closure $next, string $type = 'provider_agreement')
    {
        $user = $request->user();

        if ($user && ! $this->agreementService->hasAcceptedLatest($user, $type)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status'  => 'false',
                    'message' => 'Please accept the terms and conditions first.',
                    'data'    => [
                        'requires_agreement' => true,
                        'agreement_type'     => $type,
                        'agreement'          => $this->agreementService->getActiveAgreement($type),
                    ],
                ], 403);
            }

            return redirect()->route('agreement.show', ['type' => $type]);
        }

        return $next($request);
    }
}
```

### 9.4 Agreement API Endpoint

Add to `routes/api.php`:
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('agreement/{type}', [API\AgreementController::class, 'show']);
    Route::post('agreement/{type}/accept', [API\AgreementController::class, 'accept']);
});
```

**`app/Http/Controllers/API/AgreementController.php`**

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\AgreementService;
use Illuminate\Http\Request;

class AgreementController extends Controller
{
    public function __construct(
        protected AgreementService $agreementService
    ) {}

    public function show(string $type)
    {
        $agreement = $this->agreementService->getActiveAgreement($type);
        if (! $agreement) {
            return response()->json(['status' => 'false', 'message' => 'Agreement not found.'], 404);
        }

        return response()->json([
            'status' => 'true',
            'data'   => [
                'id'        => $agreement->id,
                'type'      => $agreement->type,
                'content'   => $agreement->content_ar,
                'version'   => $agreement->version,
                'accepted'  => $this->agreementService->hasAcceptedLatest(auth()->user(), $type),
            ],
        ]);
    }

    public function accept(string $type, Request $request)
    {
        $user = auth()->user();
        $alreadyAccepted = $this->agreementService->hasAcceptedLatest($user, $type);

        if ($alreadyAccepted) {
            return response()->json([
                'status'  => 'true',
                'message' => 'Already accepted.',
            ]);
        }

        $this->agreementService->accept(
            $user,
            $type,
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'status'  => 'true',
            'message' => 'Agreement accepted successfully.',
        ]);
    }
}
```

---

## 10. Phase 8: Dashboard Restructure

### 10.1 Dashboard Service

**`app/Services/DashboardService.php`**

```php
<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Models\Quote;
use App\Models\EscrowTransaction;
use App\Models\InvestigationLog;
use App\Models\CommissionEarning;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(
        protected EscrowService $escrowService,
        protected InsuranceService $insuranceService,
        protected InvestigationService $investigationService,
    ) {}

    /**
     * Get all admin dashboard metrics.
     */
    public function getAdminMetrics(): array
    {
        $escrowStats = $this->escrowService->getDashboardStats();
        $investigationStats = $this->investigationService->getDashboardStats();

        return [
            // ── Operations ─────────────────────────────────
            'inspection_requests' => Booking::whereIn('status', [
                'pending_inspection', 'inspection_requested'
            ])->count(),
            'pending_quotes'      => Quote::pending()->count(),
            'approved_quotes'     => Quote::approved()->count(),
            'active_jobs'         => Booking::where('status', 'in_progress')->count(),
            'disputes'            => Booking::where('status', 'disputed')->count(),

            // ── Financial ──────────────────────────────────
            'held_payments'       => $escrowStats['total_held'],
            'released_payments'   => $escrowStats['total_released'],
            'total_refunded'      => $escrowStats['total_refunded'],
            'frozen_funds'        => $escrowStats['total_frozen'],
            'total_penalties'     => $escrowStats['total_penalties'],
            'escrow_count_active' => $escrowStats['count_active'],

            // ── Users ──────────────────────────────────────
            'active_providers'    => User::where('user_type', 'provider')
                ->where('status', 1)->count(),
            'active_handymen'     => User::where('user_type', 'handyman')
                ->where('status', 1)->count(),
            'active_customers'    => User::where('user_type', 'user')
                ->where('status', 1)->count(),

            // ── Insurance ──────────────────────────────────
            'insurance_active'    => User::where('insurance_status', 'active')->count(),
            'insurance_pending'   => User::whereIn('insurance_status', ['unpaid', 'partial'])->count(),
            'insurance_frozen'    => User::where('insurance_status', 'frozen')->count(),
            'insurance_total_held' => User::sum('insurance_balance'),

            // ── Investigations ─────────────────────────────
            'active_investigations' => $investigationStats['active_investigations'],
            'resolved_investigations' => $investigationStats['resolved_this_month'],

            // ── Revenue ────────────────────────────────────
            'daily_revenue'   => CommissionEarning::whereDate('created_at', today())
                ->sum('commission_amount'),
            'monthly_revenue' => CommissionEarning::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('commission_amount'),
            'total_revenue'   => CommissionEarning::sum('commission_amount'),
        ];
    }

    /**
     * Chart data: revenue trend (12 months).
     */
    public function getRevenueTrend(): array
    {
        $monthly = CommissionEarning::select(
            DB::raw('SUM(commission_amount) as total'),
            DB::raw('MONTH(created_at) as month'),
            DB::raw('YEAR(created_at) as year')
        )
        ->where('created_at', '>=', now()->subMonths(12))
        ->groupBy('year', 'month')
        ->orderBy('year')
        ->orderBy('month')
        ->get();

        $labels = [];
        $data = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M Y');
            $found = $monthly->firstWhere(function ($item) use ($date) {
                return $item->month == $date->month && $item->year == $date->year;
            });
            $data[] = $found ? (float) $found->total : 0;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Chart data: booking lifecycle funnel.
     */
    public function getBookingFunnel(): array
    {
        return [
            ['stage' => 'Inspections',  'count' => Booking::whereIn('status', ['pending_inspection', 'inspected'])->count()],
            ['stage' => 'Quotes',       'count' => Booking::whereIn('status', ['quoted', 'quote_approved'])->count()],
            ['stage' => 'In Progress',  'count' => Booking::where('status', 'in_progress')->count()],
            ['stage' => 'Completed',    'count' => Booking::where('status', 'completed')->count()],
            ['stage' => 'Disputed',     'count' => Booking::whereIn('status', ['disputed', 'under_investigation'])->count()],
        ];
    }

    /**
     * Chart data: escrow balance over time (last 30 days).
     */
    public function getEscrowTrend(): array
    {
        $daily = EscrowTransaction::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(held_amount) as held'),
            DB::raw('SUM(released_amount) as released'),
        )
        ->where('created_at', '>=', now()->subDays(30))
        ->groupBy('date')
        ->orderBy('date')
        ->get()
        ->keyBy('date');

        $labels = [];
        $held = [];
        $released = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('d M');
            $held[] = isset($daily[$date]) ? (float) $daily[$date]->held : 0;
            $released[] = isset($daily[$date]) ? (float) $daily[$date]->released : 0;
        }

        return ['labels' => $labels, 'series' => [
            ['name' => 'Held',     'data' => $held],
            ['name' => 'Released', 'data' => $released],
        ]];
    }
}
```

### 10.2 Dashboard API Endpoint

Add to `routes/admin-api.php`:
```php
Route::get('dashboard-metrics', [API\DashboardController::class, 'metrics']);
Route::get('dashboard-charts', [API\DashboardController::class, 'charts']);
```

### 10.3 Vue Dashboard Widgets

**`resources/js/sections/SandDashboard.vue`** — Main container:

```vue
<template>
  <div class="sand-dashboard">
    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-3" v-for="card in summaryCards" :key="card.label">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <p class="text-muted mb-1 small">{{ card.label }}</p>
                <h3 class="mb-0 fw-bold">{{ card.value }}</h3>
              </div>
              <div class="rounded-3 p-2" :style="{ backgroundColor: card.bgColor }">
                <i :class="card.icon" :style="{ color: card.color }"></i>
              </div>
            </div>
            <small class="text-muted">{{ card.subtext }}</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 mb-4">
      <div class="col-md-8">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Revenue Trend</h5>
            <select class="form-select form-select-sm w-auto" v-model="revenueRange">
              <option value="12">Last 12 Months</option>
              <option value="6">Last 6 Months</option>
              <option value="3">Last Quarter</option>
            </select>
          </div>
          <div class="card-body">
            <div ref="revenueChart" style="height: 300px"></div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent border-0">
            <h5 class="mb-0">Booking Funnel</h5>
          </div>
          <div class="card-body">
            <div ref="funnelChart" style="height: 300px"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Escrow & Insurance Row -->
    <div class="row g-3">
      <div class="col-md-6">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent border-0">
            <h5 class="mb-0">Escrow Balance (30 Days)</h5>
          </div>
          <div class="card-body">
            <div ref="escrowChart" style="height: 250px"></div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent border-0">
            <h5 class="mb-0">Insurance Status</h5>
          </div>
          <div class="card-body">
            <div ref="insuranceChart" style="height: 250px"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import ApexCharts from 'apexcharts';

export default {
  data() {
    return {
      summaryCards: [],
      revenueRange: '12',
      charts: { revenue: null, funnel: null, escrow: null, insurance: null },
    };
  },
  mounted() {
    this.fetchMetrics();
    this.fetchCharts();
  },
  methods: {
    async fetchMetrics() {
      const { data } = await axios.get('/api/admin/dashboard-metrics');
      if (data.status === 'true') {
        const m = data.data;
        this.summaryCards = [
          { label: 'Inspection Requests', value: m.inspection_requests, icon: 'bi bi-clipboard-check', color: '#397D8D', bgColor: '#E8F2F5', subtext: `${m.pending_quotes} pending quotes` },
          { label: 'Held Payments', value: `${m.held_payments} SAR`, icon: 'bi bi-shield-lock', color: '#C9A84C', bgColor: '#F8F3E0', subtext: `${m.escrow_count_active} active escrows` },
          { label: 'Active Jobs', value: m.active_jobs, icon: 'bi bi-gear', color: '#3CAE5C', bgColor: '#E8F5E9', subtext: `${m.disputes} disputes` },
          { label: 'Insurance Balance', value: `${m.insurance_total_held} SAR`, icon: 'bi bi-shield-check', color: '#397D8D', bgColor: '#E8F2F5', subtext: `${m.insurance_active} active, ${m.insurance_pending} pending` },
        ];
      }
    },
    async fetchCharts() {
      const { data } = await axios.get('/api/admin/dashboard-charts');
      if (data.status === 'true') {
        this.renderRevenueChart(data.data.revenue_trend);
        this.renderFunnelChart(data.data.booking_funnel);
        this.renderEscrowChart(data.data.escrow_trend);
        this.renderInsuranceChart(data.data.insurance_status);
      }
    },
    renderRevenueChart(chartData) {
      this.charts.revenue = new ApexCharts(this.$refs.revenueChart, {
        chart: { type: 'area', height: 300, toolbar: { show: false } },
        colors: ['#397D8D'],
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0 } },
        series: [{ name: 'Revenue', data: chartData.data }],
        xaxis: { categories: chartData.labels, labels: { style: { colors: '#6c757d' } } },
        yaxis: { labels: { formatter: v => `${v} SAR` } },
        tooltip: { y: { formatter: v => `${v} SAR` } },
        grid: { borderColor: '#f1f1f1' },
      });
      this.charts.revenue.render();
    },
    renderFunnelChart(chartData) {
      this.charts.funnel = new ApexCharts(this.$refs.funnelChart, {
        chart: { type: 'bar', height: 300, toolbar: { show: false } },
        colors: ['#397D8D', '#4A9AAD', '#3CAE5C', '#C9A84C', '#FB2F2F'],
        plotOptions: { bar: { borderRadius: 4, horizontal: true, distributed: true } },
        series: [{ data: chartData.map(s => s.count) }],
        xaxis: { categories: chartData.map(s => s.stage) },
        grid: { borderColor: '#f1f1f1' },
      });
      this.charts.funnel.render();
    },
    renderEscrowChart(chartData) {
      this.charts.escrow = new ApexCharts(this.$refs.escrowChart, {
        chart: { type: 'line', height: 250, toolbar: { show: false } },
        colors: ['#397D8D', '#3CAE5C'],
        series: chartData.series,
        xaxis: { categories: chartData.labels, labels: { style: { colors: '#6c757d' } } },
        grid: { borderColor: '#f1f1f1' },
      });
      this.charts.escrow.render();
    },
    renderInsuranceChart(chartData) {
      this.charts.insurance = new ApexCharts(this.$refs.insuranceChart, {
        chart: { type: 'donut', height: 250 },
        labels: ['Active', 'Pending', 'Frozen', 'Refunded'],
        series: [chartData.active, chartData.pending, chartData.frozen, chartData.refunded],
        colors: ['#3CAE5C', '#faa938', '#FB2F2F', '#6c757d'],
        legend: { position: 'bottom' },
      });
      this.charts.insurance.render();
    },
  },
};
</script>
```

---

## 11. Phase 9: Sidebar Restructure

### 11.1 New Sidebar Structure

Update `resources/views/partials/_body_sidebar.blade.php` — reorganize menu items:

```php
// ── DASHBOARD ──────────────────────────────────────────────
Menu::make('MenuList', function ($menu) {
    $menu->add(trans('messages.dashboard'), ['route' => 'home', 'icon' => '<svg>...</svg>'])
         ->data('role', 'admin')
         ->prepend('<span>')->append('</span>');

    // ── OPERATIONS ─────────────────────────────────────────
    $menu->add(trans('messages.operations'), ['class' => 'nav-title'])
         ->data('role', 'admin');

    $menu->add(trans('messages.inspection_requests'), ['route' => 'inspection.index', 'icon' => '<svg>...</svg>'])
         ->data('permission', 'booking list')
         ->prepend('<span>')->append('</span>');

    $menu->add(trans('messages.quotes'), ['route' => 'quote.admin.index', 'icon' => '<svg>...</svg>'])
         ->data('permission', 'booking list')
         ->prepend('<span>')->append('</span>');

    $menu->add(trans('messages.active_jobs'), ['route' => 'booking.index', 'icon' => '<svg>...</svg>'])
         ->data('permission', 'booking list')
         ->prepend('<span>')->append('</span>');

    $menu->add(trans('messages.disputes'), ['route' => 'dispute.index', 'icon' => '<svg>...</svg>'])
         ->data('permission', 'booking list')
         ->prepend('<span>')->append('</span>');

    // ── FINANCIAL ──────────────────────────────────────────
    $menu->add(trans('messages.financial'), ['class' => 'nav-title'])
         ->data('role', 'admin');

    $menu->add(trans('messages.held_payments'), ['route' => 'escrow.index', 'icon' => '<svg>...</svg>'])
         ->data('permission', 'payment list')
         ->prepend('<span>')->append('</span>');

    $menu->add(trans('messages.released_payments'), ['route' => 'escrow.released', 'icon' => '<svg>...</svg>'])
         ->data('permission', 'payment list')
         ->prepend('<span>')->append('</span>');

    $menu->add(trans('messages.refunds'), ['route' => 'escrow.refunded', 'icon' => '<svg>...</svg>'])
         ->data('permission', 'payment list')
         ->prepend('<span>')->append('</span>');

    $menu->add(trans('messages.wallets'), ['route' => 'wallet.index', 'icon' => '<svg>...</svg>'])
         ->data('permission', 'wallet list')
         ->prepend('<span>')->append('</span>');

    $menu->add(trans('messages.insurance_deposits'), ['route' => 'insurance.index', 'icon' => '<svg>...</svg>'])
         ->data('permission', 'payment list')
         ->prepend('<span>')->append('</span>');

    $menu->add(trans('messages.payout_requests'), ['route' => 'providerpayout.index', 'icon' => '<svg>...</svg>'])
         ->data('permission', 'providerpayout list')
         ->prepend('<span>')->append('</span>');

    // ── USERS ──────────────────────────────────────────────
    // Existing users section stays but simplified

    // ── SERVICES ───────────────────────────────────────────
    // Existing services section stays

    // ── REPORTS ────────────────────────────────────────────
    $menu->add(trans('messages.reports'), ['class' => 'nav-title'])
         ->data('role', 'admin');

    $menu->add(trans('messages.revenue_reports'), ['route' => 'earning', 'icon' => '<svg>...</svg>'])
         ->data('permission', 'earning list')
         ->prepend('<span>')->append('</span>');

    $menu->add(trans('messages.escrow_reports'), ['route' => 'escrow.reports', 'icon' => '<svg>...</svg>'])
         ->data('permission', 'payment list')
         ->prepend('<span>')->append('</span>');

    $menu->add(trans('messages.provider_earnings'), ['route' => 'handymanEarning', 'icon' => '<svg>...</svg>'])
         ->data('permission', 'earning list')
         ->prepend('<span>')->append('</span>');

    // ── SETTINGS ───────────────────────────────────────────
    // Existing settings section stays with additions for:
    // - Agreements
    // - Branding
});
```

### 11.2 New Translation Keys

Add to `resources/lang/ar/messages.php`:
```php
'operations'          => 'العمليات',
'inspection_requests' => 'طلبات المعاينة',
'quotes'              => 'عروض الأسعار',
'active_jobs'         => 'الوظائف النشطة',
'disputes'            => 'النزاعات',
'financial'           => 'المالية',
'held_payments'       => 'المدفوعات المحتجزة',
'released_payments'   => 'المدفوعات المفرج عنها',
'refunds'             => 'المبالغ المستردة',
'insurance_deposits'  => 'التأمينات',
'payout_requests'     => 'طلبات السحب',
'reports'             => 'التقارير',
'revenue_reports'     => 'تقارير الإيرادات',
'escrow_reports'      => 'تقارير الضمان',
'provider_earnings'   => 'أرباح المزودين',
'agreements'          => 'الاتفاقيات',
'branding'            => 'العلامة التجارية',
'quote_submitted'     => 'تم تقديم عرض السعر بنجاح',
'payment_held'        => 'تم حجز الدفعة',
'investigation_opened'=> 'تم فتح التحقيق',
'quote_approved'      => 'تم الموافقة على عرض السعر',
'quote_rejected'      => 'تم رفض عرض السعر',
'dispute_opened'      => 'تم فتح النزاع',
'escrow_released'     => 'تم الإفراج عن المبلغ',
```

Add matching English keys to `resources/lang/en/messages.php`.

---

## 12. Phase 10: Notifications Enhancement

### 12.1 New Notification Templates Seeder

**`database/seeders/SandNotificationTemplatesSeeder.php`**

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SandNotificationTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name'    => 'inspection_requested',
                'label'   => 'Inspection Requested',
                'type'    => 'inspection_requested',
                'status'  => 1,
                'content' => [
                    'ar' => [
                        'push' => 'تم طلب معاينة جديدة للخدمة #{booking_id}',
                        'mail' => 'تم تقديم طلب معاينة جديد. يرجى مراجعة التفاصيل.',
                    ],
                    'en' => [
                        'push' => 'New inspection requested for booking #{booking_id}',
                        'mail' => 'A new inspection request has been submitted. Please review the details.',
                    ],
                ],
            ],
            [
                'name'    => 'quote_submitted',
                'label'   => 'Quote Submitted',
                'type'    => 'quote_submitted',
                'status'  => 1,
                'content' => [
                    'ar' => [
                        'push' => 'تم تقديم عرض سعر بقيمة {quote_price} ريال للخدمة #{booking_id}',
                        'mail' => 'قام المزود بتقديم عرض سعر. يرجى مراجعته والموافقة عليه.',
                    ],
                    'en' => [
                        'push' => 'Quote of {quote_price} SAR submitted for booking #{booking_id}',
                        'mail' => 'The provider has submitted a quote. Please review and approve.',
                    ],
                ],
            ],
            [
                'name'    => 'quote_approved',
                'label'   => 'Quote Approved',
                'type'    => 'quote_approved',
                'status'  => 1,
                'content' => [
                    'ar' => [
                        'push' => 'تم الموافقة على عرض السعر للخدمة #{booking_id}. يرجى البدء في العمل.',
                        'mail' => 'وافق العميل على عرض السعر. يمكنك الآن بدء الخدمة.',
                    ],
                    'en' => [
                        'push' => 'Quote approved for booking #{booking_id}. You can start the job.',
                        'mail' => 'The customer has approved the quote. You may now begin the service.',
                    ],
                ],
            ],
            [
                'name'    => 'quote_rejected',
                'label'   => 'Quote Rejected',
                'type'    => 'quote_rejected',
                'status'  => 1,
                'content' => [
                    'ar' => [
                        'push' => 'تم رفض عرض السعر للخدمة #{booking_id}',
                        'mail' => 'العملاء رفض عرض السعر. يمكنك تقديم عرض معدل.',
                    ],
                    'en' => [
                        'push' => 'Quote rejected for booking #{booking_id}',
                        'mail' => 'The customer rejected the quote. You may submit a revised one.',
                    ],
                ],
            ],
            [
                'name'    => 'payment_held',
                'label'   => 'Payment Held in Escrow',
                'type'    => 'payment_held',
                'status'  => 1,
                'content' => [
                    'ar' => [
                        'push' => 'تم حجز مبلغ {amount} ريال في حساب الضمان للخدمة #{booking_id}',
                        'mail' => 'تم تأمين مبلغ الخدمة في حساب الضمان. يمكنك البدء بأمان.',
                    ],
                    'en' => [
                        'push' => '{amount} SAR held in escrow for booking #{booking_id}',
                        'mail' => 'The service amount is secured in escrow. You can proceed safely.',
                    ],
                ],
            ],
            [
                'name'    => 'payment_released',
                'label'   => 'Payment Released from Escrow',
                'type'    => 'payment_released',
                'status'  => 1,
                'content' => [
                    'ar' => [
                        'push' => 'تم الإفراج عن مبلغ {amount} ريال من حساب الضمان للخدمة #{booking_id}',
                        'mail' => 'تم تحويل المبلغ إلى محفظتك. شكراً لاستخدامك سند.',
                    ],
                    'en' => [
                        'push' => '{amount} SAR released from escrow for booking #{booking_id}',
                        'mail' => 'The amount has been transferred to your wallet. Thank you for using Sand.',
                    ],
                ],
            ],
            [
                'name'    => 'investigation_opened',
                'label'   => 'Investigation Opened',
                'type'    => 'investigation_opened',
                'status'  => 1,
                'content' => [
                    'ar' => [
                        'push' => 'تم فتح تحقيق للخدمة #{booking_id}. يرجى متابعة البريد الإلكتروني.',
                        'mail' => 'تم فتح تحقيق بخصوص الخدمة #{booking_id}. سيتم التواصل معك قريباً.',
                    ],
                    'en' => [
                        'push' => 'Investigation opened for booking #{booking_id}. Check your email.',
                        'mail' => 'An investigation has been opened regarding booking #{booking_id}. We will contact you shortly.',
                    ],
                ],
            ],
            [
                'name'    => 'insurance_deducted',
                'label'   => 'Insurance Deducted',
                'type'    => 'insurance_deducted',
                'status'  => 1,
                'content' => [
                    'ar' => [
                        'push' => 'تم خصم {amount} ريال من التأمين الخاص بك.',
                        'mail' => 'تم خصم مبلغ من التأمين الخاص بك. لمزيد من التفاصيل، يرجى مراجعة لوحة التحكم.',
                    ],
                    'en' => [
                        'push' => '{amount} SAR deducted from your insurance deposit.',
                        'mail' => 'An amount has been deducted from your insurance. Please check the dashboard for details.',
                    ],
                ],
            ],
        ];

        foreach ($templates as $template) {
            $existing = DB::table('notification_templates')
                ->where('type', $template['type'])
                ->first();

            $id = $existing
                ? $existing->id
                : DB::table('notification_templates')->insertGetId([
                    'name'       => $template['name'],
                    'label'      => $template['label'],
                    'type'       => $template['type'],
                    'status'     => $template['status'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            foreach ($template['content'] as $locale => $channels) {
                foreach ($channels as $channel => $message) {
                    DB::table('notification_template_content_mappings')->updateOrInsert(
                        [
                            'notification_template_id' => $id,
                            'language'                 => $locale,
                            'channel'                  => $channel,
                        ],
                        [
                            'message'    => $message,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }
    }
}
```

### 12.2 Notification Helper Integration

In `app/Traits/NotificationTrait.php`, add send methods for the new event types:

```php
/**
 * Send inspection requested notification.
 */
public function sendInspectionRequested($booking): void
{
    $this->sendNotification([
        'activity_type' => 'inspection_requested',
        'booking_id'    => $booking->id,
        'provider_id'   => $booking->provider_id,
        'customer_id'   => $booking->customer_id,
        'customer_name' => optional($booking->customer)->display_name,
        'booking_services_name' => optional($booking->service)->name,
        'title'         => 'inspection_requested',
    ]);
}

/**
 * Send quote submitted notification.
 */
public function sendQuoteSubmitted($booking): void
{
    $this->sendNotification([
        'activity_type' => 'quote_submitted',
        'booking_id'    => $booking->id,
        'provider_id'   => $booking->provider_id,
        'customer_id'   => $booking->customer_id,
        'quote_price'   => $booking->quote_price,
        'booking_services_name' => optional($booking->service)->name,
    ]);
}

/**
 * Send payment held notification.
 */
public function sendPaymentHeld($booking): void
{
    $this->sendNotification([
        'activity_type' => 'payment_held',
        'booking_id'    => $booking->id,
        'customer_id'   => $booking->customer_id,
        'provider_id'   => $booking->provider_id,
        'amount'        => $booking->total_amount,
    ]);
}

/**
 * Send investigation opened notification.
 */
public function sendInvestigationOpened($booking, $reason): void
{
    $this->sendNotification([
        'activity_type' => 'investigation_opened',
        'booking_id'    => $booking->id,
        'customer_id'   => $booking->customer_id,
        'provider_id'   => $booking->provider_id,
        'reason'        => $reason,
    ]);
}
```

---

## 13. Phase 11: API Compatibility

### 13.1 Key Principles

1. **NEVER remove existing API response fields** — only add new ones
2. **New endpoints get new route names** — don't change existing URLs
3. **Deprecate with `deprecated_at` headers** — don't break old clients
4. **Existing status ENUM values stay** — add new ones alongside
5. **Flutter app reads `booking.quote_price` directly** — already denormalized

### 13.2 New API Routes

Add to `routes/api.php`:

```php
// ── Inspection/Quote Routes (existing — already present) ──
Route::post('mark-inspected',         [QuoteController::class, 'markInspected']);
Route::post('add-quote',              [QuoteController::class, 'submit']);
Route::post('submit-quote',           [QuoteController::class, 'submit']);
Route::post('approve-quote',          [QuoteController::class, 'approve']);
Route::post('reject-quote',           [QuoteController::class, 'reject']);

// ── New Quote Routes ───────────────────────────────────────
Route::get('quote/history/{booking_id}', [QuoteController::class, 'history']);
Route::get('quote/{id}',                 [QuoteController::class, 'show']);

// ── Escrow Routes ──────────────────────────────────────────
Route::get('escrow/status/{booking_id}',  [EscrowController::class, 'status']);
Route::get('escrow/history',              [EscrowController::class, 'myHistory']);

// ── Insurance Routes ───────────────────────────────────────
Route::get('insurance/status',            [InsuranceController::class, 'status']);
Route::post('insurance/deposit',          [InsuranceController::class, 'deposit']);
Route::get('insurance/transactions',      [InsuranceController::class, 'transactions']);

// ── Agreement Routes ───────────────────────────────────────
Route::get('agreement/{type}',            [AgreementController::class, 'show']);
Route::post('agreement/{type}/accept',    [AgreementController::class, 'accept']);

// ── Investigation Routes ───────────────────────────────────
Route::get('investigation/{booking_id}', [InvestigationController::class, 'show']);
Route::post('investigation/{id}/respond', [InvestigationController::class, 'respond']);
```

### 13.3 Booking Resource Enhancement

**`app/Http/Resources/API/BookingResource.php`** — Add new fields without removing old ones:

```php
public function toArray($request): array
{
    return [
        // ... existing fields (keep EVERYTHING) ...
        'id'                    => $this->id,
        'customer_id'           => $this->customer_id,
        'provider_id'           => $this->provider_id,
        'service_id'            => $this->service_id,
        'status'                => $this->status,
        'payment_status'        => $this->payment_status,
        'total_amount'          => $this->total_amount,
        // ... all existing fields ...

        // ── New Fields (additive only) ─────────────────────
        'quote'                    => new QuoteResource($this->whenLoaded('latestQuote')),
        'escrow'                   => $this->when($this->payment_status === 'escrow', [
            'held_amount'  => $this->escrow?->held_amount,
            'held_at'      => $this->escrow?->held_at,
            'release_by'   => $this->escrow?->scheduled_release_at,
        ]),
        'dispute'                  => $this->when($this->status === 'disputed', [
            'reason'       => $this->dispute_reason,
            'raised_at'    => $this->updated_at,
        ]),
        'can_approve_quote'        => $this->status === 'quoted',
        'can_start_booking'        => $this->status === 'quote_approved' && $this->payment_status === 'escrow',
        'insurance_required'       => !optional($this->provider)->insurance_status === 'active',
        'agreement_required'       => true, // client should always check
    ];
}
```

---

## 14. Complete Migration List

| # | Migration Name | Purpose |
|---|----------------|---------|
| 1 | `YYYY_MM_DD_000001_add_sand_branding_settings` | Seed Sand brand colors and app name |
| 2 | `YYYY_MM_DD_000010_add_sand_booking_statuses` | Add disputed/under_investigation/resolved to ENUMs, add investigation fields to bookings |
| 3 | `YYYY_MM_DD_000011_add_sand_booking_statuses_seeder` | Seed new booking_statuses table entries |
| 4 | `YYYY_MM_DD_000020_enhance_quotes_table` | Add handyman_id, estimated_duration, inspection_notes, timestamps |
| 5 | `YYYY_MM_DD_000030_create_escrow_transactions_table` | Full escrow tracking with audit trail |
| 6 | `YYYY_MM_DD_000040_create_insurance_system` | Insurance fields on users + insurance_transactions table |
| 7 | `YYYY_MM_DD_000050_create_investigation_logs_table` | Investigation logs + activities tables |
| 8 | `YYYY_MM_DD_000060_create_legal_agreements_table` | Legal agreements + acceptances tables + seed defaults |

**Total: 8 new migrations. Zero destructive changes.**

---

## 15. Step-by-Step Implementation Order

### Sprint 1: Foundation (Days 1-3)

```
Day 1:  Rebranding
        ├── Update colors (SCSS, CSS vars, ApexCharts)
        ├── Update ALL hardcoded "Handyman Service" → "Sand | سند"
        ├── Update email templates
        ├── Update translation files
        ├── Run migration #1 (branding settings)
        └── Verify: login page, dashboard header, sidebar, all mails

Day 2:  Booking Workflow Enhancement
        ├── Run migration #2 (disputed/investigation statuses)
        ├── Run migration #3 (seed booking statuses)
        ├── Add constants + scopes to Booking model
        ├── Add openDispute/escalateToInvestigation/resolveInvestigation to BookingWorkflowService
        └── Verify: status transitions work via API

Day 3:  Quote System Enhancement
        ├── Run migration #4 (enhance quotes table)
        ├── Update Quote model (fillable, relationships, scopes)
        ├── Create QuoteResource
        ├── Enhance QuoteController (submit, history, adminIndex)
        ├── Create admin quote management routes + controller
        └── Verify: quote submission includes duration/handyman/notes
```

### Sprint 2: Financial Systems (Days 4-7)

```
Day 4:  Escrow/Payment Hold
        ├── Run migration #5 (escrow_transactions)
        ├── Create EscrowTransaction model
        ├── Build EscrowService (hold/release/refund/freeze/deductPenalty)
        ├── Create EscrowController (status, history)
        ├── Create admin escrow management pages
        ├── Create escrow dashboard widget
        └── Verify: payment flow → escrow created → released on completion

Day 5:  Insurance System
        ├── Run migration #6 (insurance fields + transactions)
        ├── Create InsuranceTransaction model
        ├── Build InsuranceService (deposit/withdraw/deduct/freeze/refund)
        ├── Create InsuranceController (status, deposit, history)
        ├── Create admin insurance management pages
        └── Verify: provider can deposit, insurance shows active, gradual deduction works

Day 6:  Investigation Mode
        ├── Run migration #7 (investigation_logs)
        ├── Create InvestigationLog + InvestigationActivity models
        ├── Build InvestigationService (open/escalate/resolve)
        ├── Integrate with BookingWorkflowService (dispute → investigate → resolve)
        ├── Integrate with EscrowService (freeze/release during investigation)
        ├── Integrate with InsuranceService (freeze/deduct during investigation)
        ├── Create admin investigation pages
        └── Verify: dispute → freeze funds → resolve → release or refund

Day 7:  Integration Testing
        ├── End-to-end: booking → inspect → quote → approve → pay → escrow → complete → release
        ├── Dispute path: booking → dispute → investigate → resolve → release/refund
        ├── Insurance path: deposit → deduct penalty → refund on closure
        ├── API compatibility pass (no old fields removed)
        └── Fix any issues
```

### Sprint 3: UX & Admin (Days 8-10)

```
Day 8:  Legal Acknowledgements
        ├── Run migration #8 (agreements + acceptances)
        ├── Create LegalAgreement + AgreementAcceptance models
        ├── Build AgreementService
        ├── Create AgreementController (API)
        ├── Create RequireAgreementAcceptance middleware
        ├── Create admin agreement management page
        ├── Seed default Arabic/English agreement texts
        └── Verify: API returns 403 if agreement not accepted

Day 9:  Dashboard + Sidebar Restructure
        ├── Build DashboardService (admin metrics, charts)
        ├── Create dashboard API endpoints
        ├── Build SandDashboard Vue component with ApexCharts
        ├── Reorganize sidebar menu items
        ├── Add new translation keys
        └── Verify: dashboard shows correct metrics, charts render

Day 10: Notifications + Polish
        ├── Seed sand notification templates
        ├── Add notification helper methods to NotificationTrait
        ├── Integrate notifications into BookingWorkflowService transitions
        ├── Integrate notifications into EscrowService
        ├── Integrate notifications into InvestigationService
        ├── Final QA pass
        └── Verify: all events trigger push/email/in-app
```

---

## Appendix: Files to Create

```
NEW FILES:
├── app/Services/EscrowService.php
├── app/Services/InsuranceService.php
├── app/Services/InvestigationService.php
├── app/Services/AgreementService.php
├── app/Services/DashboardService.php
├── app/Models/EscrowTransaction.php
├── app/Models/InsuranceTransaction.php
├── app/Models/InvestigationLog.php
├── app/Models/InvestigationActivity.php
├── app/Models/LegalAgreement.php
├── app/Models/AgreementAcceptance.php
├── app/Http/Resources/API/QuoteResource.php
├── app/Http/Controllers/API/AgreementController.php
├── app/Http/Controllers/API/EscrowController.php
├── app/Http/Controllers/API/InsuranceController.php
├── app/Http/Controllers/API/InvestigationController.php
├── app/Http/Middleware/RequireAgreementAcceptance.php
├── app/Providers/SandBrandingServiceProvider.php
├── app/Console/Commands/ReleaseScheduledEscrow.php
├── database/seeders/SandNotificationTemplatesSeeder.php
├── public/scss/sand/_brand-variables.scss
├── resources/js/sections/SandDashboard.vue
├── resources/js/sections/QuoteStatsWidget.vue
├── FEATURES_AND_WORKFLOWS.md
└── ARCHITECTURE_PLAN.md  ← you are here

MIGRATIONS (8 total):
├── YYYY_MM_DD_000001_add_sand_branding_settings.php
├── YYYY_MM_DD_000010_add_sand_booking_statuses.php
├── YYYY_MM_DD_000011_add_sand_booking_statuses_seeder.php
├── YYYY_MM_DD_000020_enhance_quotes_table.php
├── YYYY_MM_DD_000030_create_escrow_transactions_table.php
├── YYYY_MM_DD_000040_create_insurance_system.php
├── YYYY_MM_DD_000050_create_investigation_logs_table.php
└── YYYY_MM_DD_000060_create_legal_agreements_table.php

FILES TO MODIFY:
├── app/Models/Booking.php               → Add constants, scopes, investigation fields
├── app/Models/Quote.php                  → Add new fillable/relationships/scopes
├── app/Models/User.php                   → Add insurance relations
├── app/Models/WalletHistory.php          → Add escrow/insurance activity types
├── app/Services/BookingWorkflowService.php → Add dispute/investigation transitions
├── app/Traits/NotificationTrait.php      → Add new notification send methods
├── app/Http/Controllers/HomeController.php → Add new dashboard metrics
├── app/Http/Controllers/BookingController.php → Admin dispute/investigation actions
├── app/Http/Resources/API/BookingResource.php → Add quote/escrow/dispute fields
├── app/Http/Resources/API/BookingDetailResource.php → Same additions
├── app/Http/Kernel.php                   → Register agreement middleware
├── resources/views/partials/_body_sidebar.blade.php → Reorganize menu
├── resources/views/layouts/dashboard.blade.php → Add sand CSS vars
├── resources/views/layouts/guest.blade.php → Update dynamic color script
├── resources/views/emails/approveemail.blade.php → Rebrand
├── resources/views/emails/verification.blade.php → Rebrand
├── resources/lang/ar/messages.php        → Add new keys
├── resources/lang/en/messages.php        → Add new keys
├── config/app.php                        → App name fallback
├── public/js/chart-custom.js             → Update theme colors
├── public/js/charts/01.js                → Update colors
├── routes/api.php                        → New routes
├── routes/web.php                        → Admin quote/escrow/investigation routes
├── routes/admin-api.php                  → Dashboard metrics routes
└── .env                                  → Verify APP_NAME
```

---

> **Key Principle:** Every SQL change is a new migration with rollback. Every endpoint preserves existing fields. Every service wraps money operations in DB transactions. Every financial movement is audited.

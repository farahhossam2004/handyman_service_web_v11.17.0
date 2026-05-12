# Handyman Service Marketplace — Features & Workflows

> **Version:** 11.17.0 | **Framework:** Laravel 11.x | **PHP:** ^8.0|^8.2

---

## Table of Contents

1. [Platform Overview](#platform-overview)
2. [User Roles](#user-roles)
3. [Complete Module List](#complete-module-list)
4. [Core Workflows](#core-workflows)
5. [Technical Architecture](#technical-architecture)
6. [API Endpoints](#api-endpoints)
7. [Database Schema Summary](#database-schema-summary)
8. [Third-Party Integrations](#third-party-integrations)

---

## Platform Overview

Multi-vendor on-demand services SaaS platform connecting **customers** with **service providers** and their **handymen**. Serves as both an admin panel (Blade + Vue 3) and a RESTful API backend (Sanctum) for mobile apps.

| Attribute | Details |
|-----------|---------|
| **Type** | Admin Panel + REST API for Handyman Service Marketplace |
| **Backend** | Laravel 11.x, PHP 8.2 |
| **Database** | MySQL (~90+ tables, 184 migrations) |
| **Admin UI** | Laravel Blade + Vue 3 + Pinia + Bootstrap 5 |
| **Customer UI** | Laravel Blade |
| **API Auth** | Laravel Sanctum (token-based) |
| **Web Auth** | Laravel Breeze (session-based) |
| **Storage** | Local or AWS S3 (Spatie MediaLibrary) |

---

## User Roles

| Role | Type | Description |
|------|------|-------------|
| **admin** | Backend | Full system control — all modules, settings, users |
| **demo_admin** | Backend | Read-only admin — cannot change settings |
| **provider** | Backend+API | Service provider — manages services, handymen, bookings, payouts |
| **handyman** | Backend+API | Field technician — assigned bookings, completion, cash collection, ratings |
| **user** | Web+API | Customer — browses, books, pays, rates, uses wallet/loyalty |

All roles stored in single `users` table with `user_type` discriminator + Spatie roles/permissions.

---

## Complete Module List

### 1. User Management
- 4 user types in single `users` table (admin, provider, handyman, customer)
- Registration (email + social login), profile editing, status toggle
- Email verification, soft-deletes, timezone/language preferences
- Referral system (referral code, referred_by, loyalty_points)
- Address management (multiple addresses per user, geocoding)
- Document verification per user type
- Live location tracking on active bookings
- Favorites (saved providers and services)

### 2. Role & Permission System
- Spatie Laravel Permission — granular CRUD permissions per module
- Roles: admin, demo_admin, provider, handyman, user
- Permission-check middleware on all admin routes
- Demo admin restrictions (blocked from changing settings)

### 3. Service Management
- **Hierarchy:** Categories → SubCategories → Services
- **Pricing:** Hourly or fixed price
- **Visit types:** On-site or online
- **Service Addons:** Extra charge options per service
- **Service Packages:** Bundle multiple services at a discount
- **Service FAQs:** Per-service Q&A
- **Service Proofs:** Upload completion proof images per booking
- **Service Zones:** Polygon-based geographic coverage (JSON coordinates)
- **SEO:** Meta title/description/keywords, canonical URL, slug per entity
- **Multi-language:** Polymorphic translations table

### 4. Booking System
- **Status workflow:** pending → confirmed → in_progress → completed → cancelled
- **Handyman assignment:** Multiple handymen per booking via pivot table
- **Coupon integration:** Discount tracking per booking
- **Extra charges:** Title, price, quantity
- **Package & addon mappings**
- **Address mapping:** With lat/lng geocoding
- **Slot booking:** Day + time slot selection
- **Advance payment:** Partial payment before service
- **Cancellation charges:** Configurable amount/percentage
- **Rating & reviews:** Per-booking customer feedback
- **Activity log:** Timeline of all booking actions
- **Inspection/Quote workflow:**
  1. Booking created in `pending` status
  2. Provider inspects and submits Quote (price + description)
  3. Customer approves or rejects
  4. On approval → escrow payment → confirmed
  5. Handyman assigned → in_progress → complete → escrow released

### 5. Provider Management
- Provider types/categories with commission configuration
- Approval workflow with document verification
- Multiple service addresses per provider
- Service zone assignments (geographic coverage)
- Time slot management (day-wise start/end, per-service or global)
- Tax mappings per provider
- Payouts (bank transfer, status tracking, paid dates)
- Earnings & commission tracking (`commission_earnings` table)
- Subscription management (plan assignment, upgrade/downgrade with proration)

### 6. Handyman Management
- Handyman types with pricing (different rates per type)
- Approval workflow & document verification
- Zone assignment, provider assignment
- Handyman-specific ratings
- Payouts (cash collection workflow)

### 7. Coupon & Discount System
- Discount types: percentage or fixed amount
- Service-specific coupon mappings
- Date-based start/end validity
- Usage count tracking

### 8. Tax Management
- Tax types: percentage or fixed amount
- Provider-level tax assignment
- Automatic tax calculation on bookings

### 9. Subscription & Plans
- Subscription plans with feature limits:
  - service_limit, booking_limit, serviceaddon_limit
  - handyman_limit, service_package_limit, post_job_limit
- Plan types: limited / unlimited
- Trial period support
- Plan upgrade/downgrade with proration
- In-app purchase identifiers (Play Store / App Store)
- Subscription transactions linked to payments

### 10. Loyalty Program
- **Earn Rules:** Points per service/category/package with min/max amounts, date ranges, stackable
- **Redeem Rules:** Full or partial redemption
- **Referral Rules:** Points for referring others
- **Point Activities:** Full audit trail (earn/redeem, source, type, related entity)

### 11. Wallet System
- Customer wallets with balance tracking
- Wallet top-up via payment gateways
- Transaction history
- Withdrawal requests with approval

### 12. Payment System
- **Gateways:** Stripe, Razorpay, PhonePe
- Booking payments (full or advance)
- Cash payment workflow: customer → handyman → provider → admin
- Escrow for inspection workflow
- Payment history & status tracking (paid, pending, failed)
- Gateway configuration (live/test mode, JSON credentials)

### 13. Payout System
- Provider payouts (bank transfer)
- Handyman payouts (cash collection)
- Bank account management (multiple banks per provider, default flag)
- Withdrawal requests with approval workflow

### 14. Commission / Earning Tracking
- Per-booking commission earnings
- Commission split: admin / provider / handyman
- Commission status: pending / paid
- Payment date tracking

### 15. Shop / Store Module
- Physical store locations per provider
- Shop hours (day-wise open/close, holiday flag)
- Shop documents (verification)
- Shop services (which services at which shop)
- Disablable via middleware

### 16. Post Job Request System
- Customers post job requests with description and budget
- Providers/handymen submit bids (amount + description)
- Status tracking: requested → in_progress → completed → cancelled
- Service mapping (links to relevant services)

### 17. Help Desk / Support Tickets
- Customer support ticket creation
- Activity log per ticket
- Priority & status management

### 18. Promotional Banners
- Provider-requested marketing banners
- Duration & charges configuration
- Payment tracking for banner placements
- Admin approval workflow

### 19. Notification System
- **Push:** OneSignal — customizable templates per user type
- **Email:** Configurable SMTP, multi-language templates with placeholders
- **SMS:** Twilio integration
- **In-app:** Database-driven notifications
- **Bulk:** Queue job for mass push notifications

### 20. Content Management (CMS)
- Blog posts with author, category, SEO fields
- Sliders (homepage carousel)
- Frontend settings (landing page configuration)
- SEO settings (meta per page)
- Static pages: About, Privacy, Terms, Refund, Data Deletion (WYSIWYG)

### 21. Geographic Management
- Countries → States → Cities hierarchy
- Service Zones (polygon coordinates in JSON)
- Provider/handyman zone assignments
- Google Maps API integration
- Geocoding (address → lat/lng)
- Distance-based provider/shop search

### 22. Export & Reporting
- Excel export (bookings, earnings, payouts) via Maatwebsite
- PDF generation (invoices, reports) via DomPDF/MPDF
- DataTables on all admin list views (search, sort, paginate, export)
- Dashboard charts: revenue, bookings, users, earnings (ApexCharts)

### 23. Media Management
- Spatie Media Library for all file uploads
- Media collections per model (avatars, service images, documents, proofs)
- AWS S3 cloud storage support
- Image optimization (console command)

### 24. Multi-Language / i18n
- Translation system via polymorphic `translations` table
- Language switcher in admin UI
- Localized notification & email templates
- Support for Arabic, English, French, German, etc.

### 25. Installer System
- Step-by-step web installer:
  1. Welcome
  2. Requirements check
  3. Directory permissions
  4. Environment configuration (.env generation)
  5. Database setup (migration + seeding)
  6. Final setup completion

### 26. Admin Dashboard
- Summary cards: Total Services, Tax, Earnings, Revenue
- Monthly revenue chart (ApexCharts, filterable by date range)
- Recent widgets: Providers, Customers, Bookings
- 4 role-specific dashboards (Admin, Provider, Handyman, Customer)

---

## Core Workflows

### Authentication Flow

| Context | Method | Middleware |
|---------|--------|-----------|
| **Admin Panel (Web)** | Session-based (Laravel Breeze) | `auth` middleware |
| **API (Mobile Apps)** | Sanctum token-based | `auth:sanctum` middleware |
| **Installer** | No auth | `CheckInstallation` middleware |

### Booking State Machine

```
Customer creates booking
         │
         ▼
    [Pending] ───→ Customer cancels ───→ [Cancelled]
         │
         │  (Inspection/Quote workflow?)
         │  YES                          NO
         │   │                           │
         │   ▼                           ▼
         │  Provider submits Quote   [Confirmed] (auto)
         │   │                           │
         │   ▼                           │
         │  Customer approves/rejects     │
         │   │                           │
         │   ├── Reject → [Cancelled]     │
         │   └── Approve → Escrow         │
         │                 │              │
         │                 ▼              │
         │          [Confirmed] ◄─────────┘
         │                 │
         │                 ▼
         │          Handyman assigned
         │                 │
         │                 ▼
         │          [In Progress]
         │                 │
         │                 ▼
         │          Service Proofs uploaded
         │                 │
         │                 ▼
         │          [Completed]
         │                 │
         │                 ▼
         │          Rating & Review
         │                 │
         │                 ▼
         │          Escrow released (if applicable)
         │
         └── Provider can cancel (with charge)
```

### Payment Flow

```
Booking Created
       │
       ▼
Payment Type?
  ├── Advance Payment (partial %) → paid upfront
  ├── Full Payment → paid at confirmation
  └── Cash → paid after completion
       │
       ▼
Payment processed via:
  ├── Stripe (credit/debit card)
  ├── Razorpay (India)
  ├── PhonePe (India)
  └── Wallet (deduct from balance)
       │
       ▼
Payment status tracked:
  Paid → Pending → Failed → Refunded
       │
       ▼
Cash Workflow (if cash):
  Customer → Handyman → Provider → Admin (commission split)
```

### Service Save API Flow (Mobile App)

```
POST /api/service-save
       │
       ▼
  ServiceRequest (FormRequest)
       │
       ├── prepareForValidation():
       │   ├── Normalize translations array
       │   ├── Fallback name from translations
       │   ├── Strip failed file uploads (count valid files only)
       │   └── Handle multilingual name population
       │
       ├── rules():
       │   ├── name, category_id, type, price, status (required)
       │   ├── price > commission validation
       │   ├── attachment_count → nullable|integer|min:1
       │   └── service_attachment_* → nullable
       │
       ├── Validation passes?
       │   ├── NO  → 422 JSON error response
       │   └── YES → Controller::store()
       │
       └── Controller::store()
           ├── Create Service record
           ├── Handle API attachments (loop by attachment_count)
           ├── Handle web attachments (hasFile)
           ├── Store media via Spatie MediaLibrary
           └── Return JSON response
```

### Provider Subscription Lifecycle

```
Admin creates Plan with limits
       │
       ▼
Provider purchases subscription
       │
       ▼
PlanValidatorService checks:
  ├── service_limit: can provider add more services?
  ├── booking_limit: can provider accept more bookings?
  ├── handyman_limit: can provider add more handymen?
  ├── serviceaddon_limit: can provider add more addons?
  ├── service_package_limit: can provider create more packages?
  └── post_job_limit: can provider bid on more jobs?
       │
       ▼
Subscription statuses:
  Pending → Active → Expired → Cancelled
       │
       ▼
Upgrade/Downgrade:
  PlanUpgradeService / PlanDowngradeService
  PlanProrationService (calculate prorated charges)
       │
       ▼
Scheduled: CheckSubscription command (cron) expires overdue subscriptions
```

### Commission & Payout Flow

```
Booking completed
       │
       ▼
CommissionEarning created per booking:
  ├── Admin commission
  ├── Provider commission
  └── Handyman commission
       │
       ▼
Commission status: pending → paid
       │
       ▼
Provider requests payout:
  ├── Select bank account
  ├── Enter amount
  └── Admin approves → paid_date recorded
       │
       ▼
Handyman payout:
  ├── Cash collection workflow
  └── Admin approves payout request
```

### Notification Flow

```
Event triggered (booking created, status change, payment, etc.)
       │
       ▼
NotificationTrait sends via configured channels:
       │
       ├── Push (OneSignal) → Mobile app notification
       │   └── Template: per user_type, multi-language
       │
       ├── Email (SMTP) → Email inbox
       │   └── Template: multi-language, placeholders
       │
       ├── SMS (Twilio) → Phone
       │
       └── In-app → notifications table → notification list in app
```

---

## Technical Architecture

### Request Lifecycle

```
Client (Browser / Mobile App / API Client)
       │
       ▼
  public/index.php              (Laravel entry point)
       │
       ▼
  bootstrap/app.php             (Framework bootstrap)
       │
       ▼
  App\Http\Kernel               (HTTP Kernel)
       │
       ├── Global Middleware:
       │   ├── TrustProxies
       │   ├── PreventRequestsDuringMaintenance
       │   ├── ValidatePostSize
       │   ├── TrimStrings
       │   └── ConvertEmptyStringsToNull
       │
       ├── Route Groups:
       │   ├── 'web' → EncryptCookies, StartSession, VerifyCsrfToken, LanguageTranslator
       │   └── 'api' → EnsureFrontendRequestsAreStateful, throttle:api, SetUserLocale
       │
       ├── Per-Route Middleware:
       │   ├── auth, guest, verified
       │   ├── permission:{slug}, role:{role}
       │   ├── CheckInstallation, prevent.demo.setting
       │   └── shop.module.enabled
       │
       ▼
   Controller Layer
       │
       ├── FormRequest Validation  (29 classes)
       ├── Service Layer           (9 classes)
       ├── Eloquent Models         (93 models)
       ├── API Resources           (55 transformers)
       │
       ▼
   Response: JSON (API) or Blade view (Web)
```

### Project Structure

```
├── app/
│   ├── Console/Commands/         # 4 Artisan commands
│   ├── Exports/                  # Excel export classes
│   ├── Helper/                   # Helper functions
│   ├── Http/
│   │   ├── Controllers/          # 59 controllers
│   │   ├── Middleware/           # 14 middleware classes
│   │   ├── Requests/            # 29 form request validators
│   │   └── Resources/           # 55 API resource transformers
│   ├── Jobs/                     # Queue jobs
│   ├── Mail/                     # 4 mail classes
│   ├── Models/                   # 93 Eloquent models
│   ├── Notifications/            # 4 notification classes
│   ├── Providers/                # 7 service providers
│   ├── Services/                 # 9 business logic classes
│   ├── Traits/                   # 4 reusable traits
│   └── View/Components/          # Blade components
├── config/                       # 28 config files
├── database/
│   ├── migrations/               # 184 migration files
│   └── seeders/                  # 68 seeder classes
├── resources/views/              # 68 Blade view directories
├── routes/
│   ├── web.php                   # Admin panel routes
│   ├── api.php                   # Public + authenticated API
│   ├── admin-api.php             # Admin-specific API
│   ├── frontend.php              # Customer-facing web
│   ├── auth.php                  # Auth routes
│   ├── channels.php              # Broadcasting
│   └── console.php               # Console commands
└── public/                       # Web root / assets
```

### Service Layer Classes

| Service | Responsibility |
|---------|---------------|
| `BookingSlotService` | Time slot calculation and availability |
| `BookingWorkflowService` | Booking state transitions |
| `PlanUpgradeService` | Plan upgrade logic |
| `PlanDowngradeService` | Plan downgrade logic |
| `PlanProrationService` | Prorated charge calculation |
| `PlanValidatorService` | Plan constraint validation |
| `ProviderSubscriptionDetailPresenter` | Subscription formatting |
| `StripePaymentService` | Stripe gateway integration |
| `TwilioNotificationService` | SMS delivery |

### Traits

| Trait | Methods |
|-------|---------|
| `EarningTrait` | Commission & earning calculations |
| `NotificationTrait` | Multi-channel notification dispatch |
| `TranslationTrait` | Polymorphic translation helpers |
| `ZoneTrait` | Geographic zone operations |

### Console Commands (Scheduled)

| Command | Schedule | Purpose |
|---------|----------|---------|
| `CheckPostJobRequest` | Cron | Expire/check post job request statuses |
| `CheckSubscription` | Cron | Expire/check provider subscriptions |
| `GeocodeProviders` | Batch | Batch geocode provider addresses |
| `OptimizeImages` | Batch | Optimize uploaded media images |

---

## API Endpoints

### Public Endpoints (No Auth)
- `category-list`, `subcategory-list`, `service-list`
- `type-list`, `blog-list`, `slider-list`
- `country-list`, `state-list`, `city-list`
- `search-list`, `nearby-providers`
- `top-rated-service`, `coupon-list`
- `configurations`, `firebase-detail`
- `register`, `login`, `social-login`
- `forgot-password`, `contact-us`
- `check-referral`, `check-field`
- `zones`, `zone-save`, `zone-detail`
- `shop-list`, `shop-detail`, `shop-hours-list`

### Authenticated Endpoints (Sanctum)
- **Dashboard:** `dashboard-detail`, `provider-dashboard`, `admin-dashboard`, `handyman-dashboard`
- **User:** `update-profile`, `change-password`, `logout`, `delete-account`, `switch-language`, `user-wallet-balance`
- **Services:** `service-detail`, `service-list`, `service-save`, `service-delete`, `service-rating-list`, `favourite` (CRUD)
- **Bookings:** `booking-list`, `booking-detail`, `booking-save`, `booking-update`, `booking-action`, `booking-status`, `available-slots`, `booking-assigned`, `save-booking-rating`
- **Payments:** `save-payment`, `payment-list`, `payment-history`, `transfer-payment`, `cash-detail`, `payment-gateways`, `create-stripe-payment`, `phonepe/*`
- **Providers:** `save-provideraddress`, `provideraddress-list/deleted`, `provider-zones`, `provider-document-*`, `provider-payout`, `provider-payout-list`
- **Handymen:** `handyman-payout-save`, `handyman-earning-list`, `handyman-payout-list`, `handyman-update-available-status`
- **Wallet:** `wallet-history`, `wallet-top-up`, `withdraw-money`
- **Subscriptions:** `plan-list`, `save-subscription`, `cancel-subscription`, `subscription-history`, `download-subscription-invoice`
- **Post Jobs:** `save-post-job`, `get-post-job`, `get-post-job-detail`, `save-bid`, `get-bid-list`
- **Help Desk:** `helpdesk-list`, `helpdesk-save`, `helpdesk-closed`, `helpdesk-detail`, `helpdesk-activity-save`
- **Banners:** `banner-payment`, `save-banner`, `delete-banner`, `promotional-banner-list`
- **Addresses:** `address-save`, `address-list`, `address/{id}`, `address-delete`
- **Loyalty:** `loyalty-history`, `get-earn-points`
- **Notifications:** `notification-list`
- **Locations:** `update-location`, `get-location`
- **Quotes/Inspection:** `mark-inspected`, `add-quote`, `submit-quote`, `booking-start`, `complete-booking`, `approve-quote`, `reject-quote`, `booking-quote`
- **Shops:** `shop-create`, `shop-update`, `shop-delete`, `shop-restore`, `shop-force-delete`, `shop-hours-store`

---

## Database Schema Summary

### Core Tables
| Table | Purpose |
|-------|---------|
| `users` | All user types (admin, provider, handyman, customer) |
| `permissions`, `roles`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` | Spatie RBAC |
| `personal_access_tokens` | Sanctum API tokens |

### Service Tables
| Table | Purpose |
|-------|---------|
| `categories` | Service categories with SEO |
| `sub_categories` | Subcategories under categories |
| `services` | Core service entity (name, price, type, provider, etc.) |
| `service_addons` | Extra charge options per service |
| `service_faqs` | Per-service Q&A |
| `service_packages`, `package_service_mappings` | Service bundles |
| `service_proofs` | Completion proof images |
| `service_zones`, `service_zone_mappings` | Geographic coverage |
| `provider_zone_mappings` | Provider→zone links |

### Booking Tables
| Table | Purpose |
|-------|---------|
| `bookings` | Central booking record with full financial breakdown |
| `booking_statuses` | Status history timeline |
| `booking_activities` | Activity/audit log |
| `booking_handyman_mappings` | Assigned handymen |
| `booking_address_mappings` | Booking locations |
| `booking_coupon_mappings` | Coupon usage |
| `booking_extra_charges` | Extra fees |
| `booking_ratings` | Customer reviews |
| `quotes` | Inspection quotes |

### Financial Tables
| Table | Purpose |
|-------|---------|
| `payments` | Booking payments |
| `payment_gateways` | Gateway config (Stripe, Razorpay, PhonePe) |
| `payment_histories` | Payment audit trail |
| `wallets` | User wallet balance |
| `wallet_histories` | Wallet transactions |
| `banks` | Provider bank accounts |
| `provider_payouts` | Provider payout records |
| `handyman_payouts` | Handyman payout records |
| `commission_earnings` | Per-booking commission split |
| `taxes`, `provider_taxes` | Tax rates and assignments |
| `coupons`, `coupon_service_mappings` | Discount codes |

### Subscription Tables
| Table | Purpose |
|-------|---------|
| `plans` | Subscription plan definitions |
| `plan_limits` | Per-plan feature limits |
| `provider_subscriptions` | Active provider subscriptions |
| `subscription_transactions` | Subscription payments |

### Provider / Handyman Tables
| Table | Purpose |
|-------|---------|
| `provider_types` | Provider categories |
| `provider_address_mappings` | Provider locations |
| `provider_documents` | Verification docs |
| `provider_slot_mappings` | Availability slots |
| `handyman_types` | Handyman categories with pricing |
| `handyman_ratings` | Handyman-specific ratings |

### Content Tables
| Table | Purpose |
|-------|---------|
| `blogs` | Blog posts |
| `sliders` | Homepage carousel |
| `promotional_banners` | Marketing banners |
| `help_desk`, `help_desk_activity_mapping` | Support tickets |
| `post_job_requests`, `post_job_bids`, `post_job_service_mappings` | Job requests |

### Loyalty Tables
| Table | Purpose |
|-------|---------|
| `loyalty_earn_rules`, `loyalty_earn_service_mappings` | Point earning rules |
| `loyalty_redeem_rules`, `loyalty_redeem_partial_rules`, `loyalty_redeem_service_mappings` | Redemption rules |
| `loyalty_referral_rules` | Referral rules |
| `loyalty_point_activities` | Point transaction audit trail |

### Shop Tables
| Table | Purpose |
|-------|---------|
| `shops` | Physical stores |
| `shop_service_mappings` | Shop→service availability |
| `shop_documents` | Shop verification |
| `shop_hours` | Operating hours |

### Other Tables
| Table | Purpose |
|-------|---------|
| `countries`, `states`, `cities` | Geographic hierarchy |
| `addresses` | Reusable addresses |
| `documents` | Document type definitions |
| `translations` | Polymorphic multi-language |
| `media` | Spatie Media Library |
| `settings`, `app_settings`, `frontend_settings`, `seo_settings` | System configuration |
| `live_locations` | Real-time GPS tracking |
| `user_favourite_services`, `user_favourite_providers` | User favorites |
| `notification_templates`, `notification_template_content_mappings` | Push templates |
| `mail_templates`, `mail_template_content_mappings` | Email templates |
| `constants`, `static_data` | Reference data |

---

## Third-Party Integrations

| Package/Service | Purpose |
|-----------------|---------|
| `stripe/stripe-php` | Stripe payment gateway |
| `razorpay/razorpay` | Razorpay payment gateway (India) |
| **PhonePe** (custom) | PhonePe UPI (India) |
| `laravel-notification-channels/onesignal` | Push notifications |
| `twilio/sdk` | SMS notifications |
| `spatie/laravel-permission` | Role-based access control |
| `spatie/laravel-medialibrary` | File uploads & media management |
| `league/flysystem-aws-s3-v3` | AWS S3 cloud storage |
| `toin0u/geocoder-laravel` | Address geocoding |
| **Google Maps API** | Map & location services |
| `google/apiclient` | Google services (social login, Firebase) |
| `barryvdh/laravel-dompdf` | PDF invoice generation |
| `carlos-meneses/laravel-mpdf` | Alternative PDF generation |
| `maatwebsite/excel` | Excel data export |
| `yajra/laravel-datatables-oracle` | Server-side DataTables |
| `laravel/sanctum` | API token authentication |
| `tightenco/ziggy` | Laravel routes in JavaScript |
| `propaganistas/laravel-phone` | Phone number validation |
| `lavary/laravel-menu` | Dynamic sidebar menus |
| `spatie/laravel-html` | Fluent HTML generation |
| `guzzlehttp/guzzle` | HTTP client |
| **Pusher** | Real-time broadcasting (configured) |
| **ApexCharts** | Dashboard charts |
| **TinyMCE** | WYSIWYG editor |
| **FullCalendar** | Calendar views |
| **Swiper** | Carousels/sliders |
| **GSAP** | Animations |

---

## Recent Changes (v11.17.0)

| Change | Details |
|--------|---------|
| **Duration field removed** | `services.duration` column dropped via migration. Removed from model, API resources, all Blade views, seeder, validation, trait |
| **Attachments made optional** | API `service-save` endpoint: `service_attachment_*` fields made nullable. `prepareForValidation` strips failed uploads before validation runs, preventing 422 errors when file uploads fail in the Flutter app |

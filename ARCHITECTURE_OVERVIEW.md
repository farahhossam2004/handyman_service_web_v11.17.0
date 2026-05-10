# Handyman Service Web v11.17.0 — Architecture & Feature Overview

## Table of Contents
1. [Project Overview](#project-overview)
2. [Tech Stack](#tech-stack)
3. [All Features / Modules](#all-features--modules)
4. [Backend Workflow](#backend-workflow)
5. [Database Schema](#database-schema)
6. [Key Relationships](#key-relationships)

---

## Project Overview

| Attribute | Value |
|-----------|-------|
| **Type** | Admin Panel + Backend API for a multi-vendor Handyman Service Marketplace |
| **Version** | 11.17.0 |
| **Backend** | Laravel 11.x (PHP ^8.0\|^8.2) |
| **Database** | MySQL (relational) |
| **Frontend** | Laravel Blade + Vue 3, built with Laravel Mix (Webpack) |
| **API Auth** | Laravel Sanctum (token-based) |
| **Roles** | admin, demo_admin, provider, handyman, user (customer) |

---

## Tech Stack

### Backend
- **Framework:** Laravel 11.x
- **PHP:** ^8.0 \| ^8.2
- **Database:** MySQL
- **Queue:** Laravel Queue (database/redis)

### Key Packages

| Category | Package |
|----------|---------|
| Roles/Permissions | `spatie/laravel-permission` |
| Media/Uploads | `spatie/laravel-medialibrary` |
| API Auth | `laravel/sanctum` |
| Payments | `stripe/stripe-php`, `razorpay/razorpay`, phonepe |
| Push Notifications | `laravel-notification-channels/onesignal` |
| SMS | `twilio/sdk` |
| Geocoding | `toin0u/geocoder-laravel` |
| Exports | `maatwebsite/excel` |
| PDFs | `barryvdh/laravel-dompdf`, `carlos-meneses/laravel-mpdf` |
| Data Tables | `yajra/laravel-datatables-oracle` |
| Cloud Storage | `league/flysystem-aws-s3-v3` |
| HTML Builder | `spatie/laravel-html` |
| Menus | `lavary/laravel-menu` |
| Phone Validation | `propaganistas/laravel-phone` |

### Frontend
- Vue 3 + Pinia + Vee-Validate
- Bootstrap 5, SCSS
- jQuery, ApexCharts, DataTables, FullCalendar, TinyMCE, Swiper, GSAP

---

## All Features / Modules

### 1. User Management
- 4 user types: **admin**, **provider**, **handyman**, **customer**
- Registration (email + social login), profile management, status (active/inactive)
- Email verification, soft-deletes, timezone/language preferences
- Referral system (referral code, referred_by, loyalty_points)
- Address management (multiple addresses per user, geocoding)
- Document verification per user type
- Live location tracking on active bookings

### 2. Role & Permission System (Spatie)
- Granular CRUD permissions per module
- Roles: admin, demo_admin, provider, handyman, user
- Permission-check middleware on all admin routes
- Demo admin restrictions (blocked from changing settings)

### 3. Service Management
- **Categories** → **SubCategories** → **Services** (hierarchical)
- Service types: hourly / fixed price
- Visit types: on-site / online
- Service Addons (extra charge options per service)
- Service Packages (bundles of services at a discount)
- Service FAQs
- Service Proofs (upload completion proof images per booking)
- Service Zones (polygon-based geographic availability via JSON coordinates)
- SEO metadata per category/subcategory/service (meta_title, meta_description, canonical_url, slug, seo_enabled)
- Multi-language translations via morphMany

### 4. Booking System
- Full CRUD with status workflow: pending → confirmed → in_progress → completed → cancelled
- Handyman assignment (multiple handymen per booking via `booking_handyman_mappings`)
- Coupon application with discount tracking
- Extra charges (title, price, qty)
- Package & addon mappings
- Address mapping with lat/lng
- Slot-based booking (day + time slot)
- Advance payment support (partial payment before booking)
- Cancellation charges (configurable amount/percentage)
- Rating & reviews per booking
- Activity log (timeline of all booking actions)
- **Inspection/Quoting workflow (2026 feature):**
  - Booking created in `pending` status
  - Provider submits a `Quote` (price + description)
  - Customer approves/rejects the quote
  - On approval → payment moves to escrow → status changes to `confirmed`
  - Handyman assigned → `in_progress` → completion → escrow released

### 5. Provider Management
- Provider types (categories of providers)
- Approval workflow with document verification
- Multiple service addresses per provider
- Service zone assignments (which zones a provider covers)
- Time slot management (day-wise start/end times, per-service or global)
- Tax mappings (which taxes apply)
- Payouts (payment_method, amount, status, paid_date, bank account)
- Earnings & commission tracking (`commission_earnings` table)
- Subscription management (plan assignment, upgrade/downgrade)

### 6. Handyman Management
- Handyman types with pricing (different rates per type)
- Approval & document verification
- Zone assignment, provider assignment
- Ratings specific to handymen
- Payouts (cash collection workflow)

### 7. Coupon & Discount System
- Discount types: percentage or fixed amount
- Service-specific coupon mappings
- Date-based start/end validity
- Usage count tracking

### 8. Tax Management
- Tax types: percentage or fixed amount
- Provider-level tax assignment
- Automatic tax calculation on bookings (final_total_tax, tax field)

### 9. Subscription & Plans
- Subscription plans with feature limits:
  - service_limit, booking_limit, serviceaddon_limit
  - handyman_limit, service_package_limit, post_job_limit
- Plan types: limited / unlimited
- Trial period support
- Plan upgrade/downgrade with proration (`PlanUpgradeService`, `PlanDowngradeService`, `PlanProrationService`)
- In-app purchase support (playstore_identifier, appstore_identifier)
- Subscription transactions linked to payments

### 10. Loyalty Program
- **Earn Rules:** points per service / category / package, with min/max amounts, date ranges, stackable option
- **Redeem Rules:** full or partial redemption rules
- **Referral Rules:** points earned for referring others
- **Point Activities:** full audit trail (earn/redeem, source, type, related entity)

### 11. Wallet System
- Customer wallets with balance tracking
- Wallet top-up via payment gateways
- Transaction history (wallet_histories)
- Withdrawal requests

### 12. Payment System
- **Gateways:** Stripe, Razorpay, PhonePe
- Booking payments (full or advance)
- Cash payment workflow: customer → handyman → provider → admin
- Escrow for inspection workflow
- Payment history & status tracking (paid, pending, failed)
- Payment gateway configuration (live/test mode, JSON credentials)

### 13. Payout System
- Provider payouts (bank transfer, payment method tracking)
- Handyman payouts (cash collection workflow)
- Bank account management (multiple banks per provider, default flag)
- Withdrawal requests with approval workflow

### 14. Commission / Earning Tracking
- Per-booking commission earnings
- Commission split: admin / provider / handyman
- Commission status: pending / paid
- Payment date tracking

### 15. Shop / Store Module
- Physical store locations per provider
- Shop hours (day-wise open/close, is_closed flag)
- Shop documents (verification)
- Shop services (which services available at which shop)
- Disablable via `shop.module.enabled` middleware

### 16. Post Job Request System
- Customers post job requests with description and price
- Providers/handymen submit bids (amount + description)
- Status tracking: requested, in_progress, completed, cancelled
- Service mapping (which services the job relates to)

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
- **Push:** OneSignal integration, customizable templates per user type
- **Email:** Configurable SMTP, multi-language templates with placeholders
- **SMS:** Twilio integration
- **In-app:** Database-driven notifications (`notifications` table)
- **Bulk:** Queue job (`BulkNotification`) for mass push

### 20. Content Management
- Blog posts with SEO metadata
- Sliders (homepage carousel)
- Frontend settings (landing page configuration)
- SEO settings (meta per page)
- Static pages (about, privacy, terms, refund, data deletion)

### 21. Geographic Management
- Countries → States → Cities hierarchy
- Service Zones (polygon coordinates in JSON)
- Provider/handyman zone assignments
- Google Maps API integration
- Geocoding for address → lat/lng conversion
- Distance-based provider/shop search

### 22. Export & Reporting
- Excel export (bookings, earnings, payouts)
- PDF generation (invoices, reports)
- DataTables integration for all admin list views
- Dashboard charts (revenue, bookings, users, earnings)

### 23. Media Management
- Spatie Media Library for all file uploads
- Media collections per model (avatars, service images, documents, proofs)
- AWS S3 cloud storage support
- Image optimization (console command: `OptimizeImages`)

### 24. Multi-Language / i18n
- Translation system via polymorphic `translations` table
- Language switcher in UI
- Localized notification & email templates

### 25. Installer System
- Step-by-step web installer: Welcome → Requirements → Permissions → Environment → Database → Final
- Environment configuration (.env) generation
- Database migration & seeding

### 26. Admin Dashboard
- Revenue charts (ApexCharts)
- Booking statistics
- User registrations
- Earning breakdown

---

## Backend Workflow

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
       ├── Global Middleware (every request):
       │   ├── TrustProxies
       │   ├── PreventRequestsDuringMaintenance
       │   ├── ValidatePostSize
       │   ├── TrimStrings
       │   └── ConvertEmptyStringsToNull
       │
       ├── Route Group Middleware:
       │
       │   ┌── 'web' Group ──────────────────────────────┐
       │   │  Routes: web.php, frontend.php, auth.php     │
       │   │  Middleware:                                  │
       │   │  ├── EncryptCookies                          │
       │   │  ├── StartSession                            │
       │   │  ├── ShareErrorsFromSession                  │
       │   │  ├── VerifyCsrfToken                         │
       │   │  ├── SubstituteBindings                      │
       │   │  └── LanguageTranslator                      │
       │   └──────────────────────────────────────────────┘
       │
       │   ┌── 'api' Group ───────────────────────────────┐
       │   │  Routes: api.php, admin-api.php               │
       │   │  Middleware:                                  │
       │   │  ├── EnsureFrontendRequestsAreStateful        │
       │   │  ├── throttle:api                             │
       │   │  ├── SubstituteBindings                      │
       │   │  ├── SetUserLocale                           │
       │   │  └── LanguageTranslator                      │
       │   └──────────────────────────────────────────────┘
       │
       ├── Per-Route Middleware (applied in route definitions):
       │   ├── auth                    → Authenticate (redirect if guest)
       │   ├── guest                   → RedirectIfAuthenticated
       │   ├── permission:{slug}       → Spatie Permission check
       │   ├── role:{role}             → Spatie Role check
       │   ├── CheckInstallation       → Redirect to installer if not installed
       │   ├── prevent.demo.setting    → Blocks demo_admin from changing settings
       │   ├── shop.module.enabled     → 404 if shop module disabled
       │   └── verified                → Email verification check
       │
       ▼
  Controller Layer
       │
       ├── FormRequest Validation   (App\Http\Requests)
       │   └── 29 validation classes (authorization + validation rules)
       │
       ├── Service Layer            (App\Services)
       │   ├── BookingSlotService           — Time slot calculation
       │   ├── BookingWorkflowService       — Booking state transitions
       │   ├── PlanUpgradeService           — Plan upgrade logic
       │   ├── PlanDowngradeService         — Plan downgrade logic
       │   ├── PlanProrationService         — Prorated charge calculation
       │   ├── PlanValidatorService         — Plan constraint validation
       │   ├── ProviderSubscriptionDetailPresenter — Subscription formatting
       │   ├── StripePaymentService         — Stripe gateway
       │   └── TwilioNotificationService    — SMS delivery
       │
       ├── Eloquent Model Operations (App\Models)
       │   └── 93 models with relationships, scopes, accessors
       │
       ├── API Resource Transformation (App\Http\Resources)
       │   └── 55 API resource classes for JSON responses
       │
       └── Response
           ├── JSON response (API routes)
           └── Blade view (Web routes)
```

### Authentication Flow

| Context | Method | Middleware |
|---------|--------|-----------|
| **Admin Panel (Web)** | Session-based (Laravel Breeze) | `auth` middleware, routes in `auth.php` |
| **API (Mobile Apps)** | Sanctum token-based | `auth:sanctum` middleware |
| **Installer** | No auth | `CheckInstallation` middleware |

### Booking State Machine

```
Customer creates booking
         │
         ▼
    [Pending] ───→ Customer cancels ───→ [Cancelled]
         │
         │  (Inspection/Quote workflow enabled?)
         │  YES                                  NO
         │   │                                    │
         │   ▼                                    ▼
         │  Provider submits Quote       [Confirmed] (auto)
         │   │                                    │
         │   ▼                                    │
         │  Customer approves/rejects              │
         │   │                                    │
         │   ├── Reject → [Cancelled]              │
         │   └── Approve → Escrow payment          │
         │                 │                       │
         │                 ▼                       │
         │          [Confirmed] ◄──────────────────┘
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
         └── Provider can also cancel (with charge)
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
  Customer → Handyman → Provider → Admin (commission)
```

### Console Commands (Scheduled Tasks)

| Command | Purpose |
|---------|---------|
| `CheckPostJobRequest` | Cron to expire/check post job request statuses |
| `CheckSubscription` | Cron to expire/check provider subscriptions |
| `GeocodeProviders` | Batch geocode provider addresses |
| `OptimizeImages` | Optimize uploaded media images |

### Queue Jobs

| Job | Purpose |
|-----|---------|
| `BulkNotification` | Send bulk push notifications via OneSignal |

---

## Database Schema

The application uses MySQL with **184 migration files** producing **~90+ database tables**. Below is the complete schema organized by domain.

### 1. Core & Auth Tables

| Table | Key Columns | Notes |
|-------|-------------|-------|
| `users` | id, username, first_name, last_name, email, password, user_type (enum: admin/demo_admin/provider/handyman/user), contact_number, country_id, state_id, city_id, provider_id, providertype_id, handymantype_id, status, is_featured, is_available, is_subscribe, is_email_verified, player_id, login_type, time_zone, language, referral_code, referred_by, loyalty_points, latitude, longitude, handyman_zone_id, service_address_id, slots_for_all_services, designation, known_languages, skills, description, why_choose_me, last_online_time, social_image, uid, remember_token, created_at, updated_at, deleted_at | Single table for all user types. Polymorphic via user_type + nullable FK columns. |
| `password_resets` | email, token, created_at | |
| `personal_access_tokens` | id, tokenable_type, tokenable_id, name, token, abilities, last_used_at, expires_at, created_at, updated_at | Sanctum API tokens |
| `failed_jobs` | id, uuid, connection, queue, payload, exception, failed_at | Queue failure log |
| `permissions` | id, name, guard_name, created_at, updated_at | Spatie |
| `roles` | id, name, guard_name, created_at, updated_at | Spatie |
| `model_has_roles` | role_id, model_type, model_id | Spatie pivot |
| `model_has_permissions` | permission_id, model_type, model_id | Spatie pivot |
| `role_has_permissions` | permission_id, role_id | Spatie pivot |

### 2. Service Management

| Table | Key Columns | Notes |
|-------|-------------|-------|
| `categories` | id, name, description, color, status, is_featured, meta_title, meta_description, meta_keywords, canonical_url, seo_enabled, slug, created_at, updated_at | Service categories |
| `sub_categories` | id, name, category_id (FK), status, is_featured, meta_title, meta_description, meta_keywords, canonical_url, seo_enabled, slug, created_at, updated_at | Subcategories under categories |
| `services` | id, name, category_id (FK), subcategory_id (FK), provider_id (FK→users), type (hourly/fixed), price, discount, duration, description, status, is_featured, is_slot, is_enable_advance_payment, advance_payment_amount, visit_type (on-site/online), service_type, added_by, service_request_status, is_service_request, meta_title, meta_description, meta_keywords, canonical_url, slug, seo_enabled, created_at, updated_at | Core service entity |
| `service_addons` | id, service_id (FK), name, price, type, status | Extra options per service |
| `service_faqs` | id, service_id (FK), question, answer, status | Per-service FAQ |
| `service_packages` | id, name, amount, discount, type, status | Bundled service packages |
| `package_service_mappings` | id, package_id (FK), service_id (FK), service_price | Package contents |
| `service_proofs` | id, booking_id (FK), handyman_id (FK), image, description, status | Completion proof images |
| `service_zones` | id, name (unique), coordinates (JSON polygon), status | Geographic coverage zones |
| `service_zone_mappings` | id, service_id (FK), zone_id (FK) | Which services in which zones |
| `provider_zone_mappings` | id, provider_id (FK→users), zone_id (FK) | Which providers cover which zones |

### 3. Booking System

| Table | Key Columns | Notes |
|-------|-------------|-------|
| `bookings` | id, customer_id (FK→users), service_id (FK), provider_id (FK→users), post_request_id (FK), type, date, start_at, end_at, quantity, amount, discount, total_amount, redeemed_points, redeemed_discount, description, reason, coupon_id (FK), status, payment_id (FK), address, duration_diff, booking_address_id, tax, booking_slot, booking_day, advance_paid_amount, final_total_service_price, final_total_tax, final_sub_total, final_discount_amount, final_coupon_discount_amount, cancellation_charge, cancellation_charge_amount, shop_id, zone_id, quote_id, payment_status, quote_price, quote_description, created_at, updated_at | Central booking record with full financial breakdown |
| `booking_statuses` | id, booking_id (FK), status, created_at | Status history timeline |
| `booking_activities` | id, booking_id (FK), activity_type, activity_message, activity_slug, created_by | Activity log |
| `booking_handyman_mappings` | id, booking_id (FK), handyman_id (FK→users), status | Assigned handymen |
| `booking_address_mappings` | id, booking_id (FK), provider_address_id, address, lat, long | Booking location |
| `booking_coupon_mappings` | id, booking_id (FK), coupon_id (FK), discount, discount_type | Coupon usage |
| `booking_extra_charges` | id, booking_id (FK), title, price, qty | Extra fees |
| `booking_package_mappings` | id, booking_id (FK), package_id (FK) | Package attached |
| `booking_service_addon_mapping` | id, booking_id (FK), service_addon_id (FK), price | Addon services |
| `booking_ratings` | id, booking_id (FK), service_id (FK), customer_id (FK→users), rating, review, status | Customer review |
| `quotes` | id, booking_id (FK), provider_id (FK→users), price, description, status, created_at, updated_at | Inspection quotes |

### 4. Provider Management

| Table | Key Columns | Notes |
|-------|-------------|-------|
| `provider_types` | id, name, status | Categorization |
| `provider_address_mappings` | id, provider_id (FK→users), address, latitude, longitude, status | Provider locations |
| `provider_service_address_mappings` | id, provider_id, service_id, provider_address_id | Service→location mapping |
| `provider_slot_mappings` | id, provider_id (FK→users), day, start_time, end_time, status, slots_for_all_services | Availability slots |
| `provider_documents` | id, provider_id (FK→users), document_id (FK), image, is_verified | Verification |
| `provider_taxes` | id, provider_id (FK→users), tax_id (FK) | Tax assignment |
| `provider_payouts` | id, provider_id (FK→users), payment_method, description, amount, status, paid_date, bank_id, handyman_amount | Payout records |
| `commission_earnings` | id, employee_id, booking_id (FK), commissions, user_type, commission_amount, commission_status, payment_date | Commission tracking |

### 5. Handyman Management

| Table | Key Columns | Notes |
|-------|-------------|-------|
| `handyman_types` | id, name, price, type, status, created_by | Categorization with pricing |
| `handyman_payouts` | id, handyman_id (FK→users), payment_method, description, amount | Payout records |
| `handyman_ratings` | id, booking_id (FK), handyman_id (FK→users), customer_id (FK→users), rating, review, status | Handyman-specific ratings |

### 6. Payment & Financial

| Table | Key Columns | Notes |
|-------|-------------|-------|
| `payments` | id, customer_id (FK→users), booking_id (FK), datetime, discount, total_amount, payment_type, txn_id, payment_status, other_transaction_detail | Booking payments |
| `payment_gateways` | id, name, status, type, credentials (JSON), is_test | Gateway config |
| `payment_histories` | id, payment_id (FK), action, type, message, datetime, amount | Audit trail |
| `wallets` | id, user_id (FK→users), title, amount, status | User wallet |
| `wallet_histories` | id, wallet_id (FK), type, message, amount, datetime, status | Wallet transactions |
| `banks` | id, provider_id (FK→users), bank_name, branch, holder_name, account_number, is_default | Bank accounts for payout |

### 7. Subscription & Plans

| Table | Key Columns | Notes |
|-------|-------------|-------|
| `plans` | id, title, identifier, playstore_identifier, appstore_identifier, type, amount, status, duration, description, trial_period, plan_type | Subscription plans |
| `plan_limits` | id, plan_id (FK), service_limit, booking_limit, serviceaddon_limit, handyman_limit, service_package_limit, post_job_limit | Per-plan limits |
| `provider_subscriptions` | id, plan_id (FK), user_id (FK→users), title, identifier, type, start_at, end_at, amount, status, payment_id, plan_limitation (JSON), active_in_app_purchase_identifier, duration, description, plan_type, other_detail (JSON) | Active provider subscriptions |
| `subscription_transactions` | id, subscription_plan_id, payment_id | Subscription payments |

### 8. Loyalty Program

| Table | Key Columns | Notes |
|-------|-------------|-------|
| `loyalty_earn_rules` | id, loyalty_type (service/category/package_service), service_id, minimum_amount, maximum_amount, start_date, end_date, points, status, is_stackable | Point earning rules |
| `loyalty_earn_service_mappings` | id, loyalty_earn_id, service_id, loyalty_type | Rule→service links |
| `loyalty_redeem_rules` | — | Redemption rules |
| `loyalty_redeem_partial_rules` | — | Partial redemption |
| `loyalty_redeem_service_mappings` | — | Redeem rule→service |
| `loyalty_referral_rules` | — | Referral points |
| `loyalty_point_activities` | id, user_id (FK→users), type (earn/redeem), points, source, earn_type, related_id, description | Point transaction log |

### 9. Post Job Request

| Table | Key Columns | Notes |
|-------|-------------|-------|
| `post_request_statuses` | id, name, status | Status options |
| `post_job_requests` | id, title, customer_id (FK→users), provider_id (FK→users), status, description, price, job_price, reason, date | Customer job posts |
| `post_job_service_mappings` | id, post_request_id (FK), service_id (FK) | Job→service links |
| `post_job_bids` | id, post_request_id (FK), provider_id (FK→users), amount, description, status | Provider bids |

### 10. Shop / Store

| Table | Key Columns | Notes |
|-------|-------------|-------|
| `shops` | id, provider_id (FK→users), shop_name, country_id, state_id, city_id, address, lat, long, registration_number, shop_start_time, shop_end_time, contact_number, email, is_active | Physical stores |
| `shop_service_mappings` | id, shop_id (FK), service_id (FK) | Shop→service availability |
| `shop_documents` | id, shop_id (FK), document_id (FK), image, is_verified | Shop verification |
| `shop_hours` | id, shop_id (FK), day, open_time, close_time, is_closed | Operating hours |

### 11. Notification & Content

| Table | Key Columns | Notes |
|-------|-------------|-------|
| `notification_templates` | id, name, label, type, status, channels | Push/notification templates |
| `notification_template_content_mappings` | id, notification_template_id, language, subject, message, user_type | Localized content |
| `mail_templates` | id, name, label, description, type, to, bcc, cc, status, channels | Email templates |
| `mail_template_content_mappings` | id, mail_template_id, language, subject, message, user_type | Localized email content |
| `notifications` | id, type, notifiable_type, notifiable_id, data, read_at | In-app notifications |
| `blogs` | id, title, description, author_id (FK→users), category_id, status, is_featured, meta_title, meta_description, slug | Blog posts |
| `sliders` | id, title, description, status | Homepage sliders |
| `promotional_banners` | id, title, description, banner_type, banner_redirect_url, is_requested_banner, status, reject_reason, duration, charges, start_date, end_date, total_amount, payment_method, payment_status, service_id, provider_id | Marketing banners |
| `help_desk` | id, user_id (FK→users), title, description, status, priority | Support tickets |
| `help_desk_activity_mapping` | id, help_desk_id (FK), message, created_by | Ticket activity |
| `coupons` | id, code, discount, discount_type, type, start_date, end_date, usage_count, status | Discount codes |
| `coupon_service_mappings` | id, coupon_id (FK), service_id (FK) | Coupon→service |

### 12. Geographic & Configuration

| Table | Key Columns | Notes |
|-------|-------------|-------|
| `countries` | id, name, code, status | Countries |
| `states` | id, name, country_id (FK), status | States |
| `cities` | id, name, state_id (FK), status | Cities |
| `addresses` | id, user_id (FK→users), type, address, lat, long, is_primary | Reusable addresses |
| `documents` | id, name, type, status | Document type definitions |
| `taxes` | id, name, type (percent/fixed), value, status | Tax rates |
| `translations` | id, translatable_type, translatable_id, attribute, locale, value | Polymorphic translations |
| `media` | id, model_type, model_id, collection_name, name, file_name, mime_type, disk, size, manipulations, custom_properties, generated_conversions, responsive_images | Spatie media library |
| `settings` | id, name, value (JSON) | System settings |
| `app_settings` | id, name, value (JSON) | App settings |
| `frontend_settings` | id, name, value (JSON) | Landing page settings |
| `seo_settings` | id, page, meta_title, meta_description, meta_keywords | SEO config |
| `live_locations` | id, booking_id (FK), user_id (FK→users), lat, long, datetime | Live GPS tracking |
| `user_favourite_services` | id, user_id (FK→users), service_id (FK) | Saved services |
| `user_favourite_providers` | id, user_id (FK→users), provider_id (FK→users) | Saved providers |
| `constants` | id, name, value | System constants |
| `static_data` | id, name, value, status | Static reference data |

---

## Key Relationships

```
User (polymorphic: admin / provider / handyman / customer)
  ├── hasMany → Service (as provider)
  ├── hasMany → Booking (as customer or as provider)
  ├── hasMany → BookingHandymanMapping (as handyman)
  ├── hasMany → ProviderPayout / HandymanPayout
  ├── hasOne → Wallet
  ├── hasMany → Bank
  ├── hasMany → ProviderAddressMapping
  ├── belongsToMany → ServiceZone (via provider_zone_mappings)
  ├── hasMany → Shop (as provider)
  ├── hasMany → Blog (as author)
  ├── hasMany → ProviderSubscription
  ├── hasMany → PostJobRequest (as customer)
  ├── hasMany → HelpDesk
  ├── hasMany → CommissionEarning
  └── hasMany → LiveLocation

Category
  ├── hasMany → SubCategory
  └── hasMany → Service

Service
  ├── belongsTo → Category
  ├── belongsTo → SubCategory
  ├── belongsTo → User (provider)
  ├── hasMany → Booking
  ├── hasMany → ServiceAddon
  ├── hasMany → ServiceFaq
  ├── hasMany → CouponServiceMapping
  ├── belongsToMany → ServiceZone (via service_zone_mappings)
  ├── belongsToMany → Shop (via shop_service_mappings)
  └── belongsToMany → LoyaltyEarnRule (via loyalty_earn_service_mappings)

Booking
  ├── belongsTo → User (customer)
  ├── belongsTo → User (provider)
  ├── belongsTo → Service
  ├── belongsTo → Coupon
  ├── belongsTo → Payment
  ├── belongsTo → Quote
  ├── hasMany → BookingHandymanMapping → User (handyman)
  ├── hasMany → BookingActivity
  ├── hasMany → BookingRating
  ├── hasMany → BookingExtraCharge
  ├── hasMany → BookingAddressMapping
  ├── hasMany → BookingStatus
  ├── hasMany → LiveLocation
  ├── hasMany → CommissionEarning
  ├── hasMany → ServiceProof
  └── hasOne → Quote (active)

Plan → PlanLimit (one-to-one)

ProviderSubscription
  ├── belongsTo → Plan
  ├── belongsTo → User
  └── hasMany → SubscriptionTransaction

Wallet → WalletHistory (one-to-many)

PostJobRequest
  ├── belongsTo → User (customer)
  ├── hasMany → PostJobServiceMapping → Service
  └── hasMany → PostJobBid → User (provider)

Shop → ShopHour, ShopDocument, ShopServiceMapping → Service (one-to-many each)

HelpDesk → HelpDeskActivityMapping (one-to-many)

LoyaltyEarnRule ↔ Service (many-to-many via loyalty_earn_service_mappings)

ProviderSubscription
  ├── plan_limitation (JSON column) — snapshot of plan limits at subscription time
  └── other_detail (JSON column) — extensible metadata

Payment
  ├── belongsTo → Booking
  ├── belongsTo → User (customer)
  └── hasMany → PaymentHistory
```

---

## Project Structure

```
handyman_service_web_v11.17.0/
├── app/
│   ├── Console/Commands/         # 4 Artisan commands
│   ├── Exceptions/               # Error handler
│   ├── Exports/                  # Excel export classes
│   ├── Helper/                   # 3900+ line helper functions
│   ├── Http/
│   │   ├── Controllers/          # 59 controllers (API, Admin, Auth, Installer)
│   │   ├── Kernel.php            # Middleware stack
│   │   ├── Middleware/           # 14 middleware classes
│   │   ├── Requests/            # 29 form request validators
│   │   └── Resources/           # 55 API resource transformers
│   ├── Jobs/                     # Queue jobs
│   ├── Mail/                     # 4 mail classes
│   ├── Models/                   # 93 Eloquent models
│   ├── Notifications/            # 4 notification classes
│   ├── Providers/                # 7 service providers
│   ├── Services/                 # 9 service classes
│   ├── Traits/                   # 4 reusable traits
│   └── View/Components/          # Blade components
├── config/                       # 28 config files
├── database/
│   ├── migrations/               # 184 migration files
│   └── seeders/                  # 68 seeder classes
├── resources/
│   ├── views/                    # 68 Blade view directories
│   └── js/ / css/ / scss/ / sass/ # Frontend assets
├── routes/
│   ├── web.php                   # Admin panel (auth-protected)
│   ├── api.php                   # Public + authenticated API
│   ├── admin-api.php             # Admin-specific API
│   ├── frontend.php              # Customer-facing web
│   ├── auth.php                  # Auth routes
│   ├── channels.php              # Broadcasting
│   └── console.php               # Console commands
├── public/                       # Web root
├── storage/                      # Logs, cache, sessions
├── tests/                        # PHPUnit tests
└── vendor/                       # Composer packages
```

# Admin Dashboard Features — Handyman Service v11.17.0

## Access

| Page | URL |
|------|-----|
| Login | `http://127.0.0.1:8000/auth/login` |
| Dashboard | `http://127.0.0.1:8000/home` |

There are **4 role-based dashboards**: Admin, Provider, Handyman, and Customer (user). Below covers the **Admin** dashboard.

---

## Dashboard Overview (`/home`)

The admin dashboard homepage displays:

### Summary Cards (Top Row)
| Card | Route Link |
|------|-----------|
| **Total Services** count | `/service` |
| **Total Tax** collected | — |
| **My Earning** (admin commission + subscription + cancellation charges) | `/earning` |
| **Total Revenue** (commission + promotional banner payments) | `/earning` |

### Monthly Revenue Chart (ApexCharts)
- Line chart showing revenue across 12 months (Jan–Dec)
- Filterable by year, month, week, and custom date range via AJAX
- Currency symbol from system config

### Side Widgets

| Widget | Content |
|--------|---------|
| **Recent Providers** | Last 5 registered providers, avatar, email, rating, links to provider detail page |
| **Recent Customers** | Last 5 registered customers, avatar, registration date |
| **Recent Bookings** | Last 5 bookings with booking ID, customer avatar, date, status badge, link to booking detail |

---

## Admin Modules (Left Sidebar Navigation)

All modules below are accessible from the admin sidebar. Each module follows a consistent CRUD pattern with DataTables, bulk actions, status toggles, and search/filter.

### 1. Role & Permission Management

| Page | Route | Description |
|------|-------|-------------|
| Roles | `/role` | Create, edit, delete roles. Assign permissions per role. DataTable with bulk actions. |
| Permissions | `/permission` | List all permissions, add new permissions grouped by type. |

### 2. Category Management

| Page | Route | Description |
|------|-------|-------------|
| Categories | `/category` | CRUD for service categories. Features: status toggle, featured toggle, image upload, trash management (soft-delete + restore), DataTable, bulk actions, SEO fields (meta_title, meta_description, meta_keywords, canonical_url, slug). |
| Subcategories | `/subcategory` | CRUD with similar features. Linked to parent category. |

### 3. Service Management

| Page | Route | Description |
|------|-------|-------------|
| Services | `/service` | Full CRUD for services. Fields: name, category, subcategory, provider, type (hourly/fixed), price, discount, duration, description, status, featured, slot/advance payment options, visit type (on-site/online), SEO. DataTable with bulk actions, trash management. |
| Service Addons | `/serviceaddon` | Extra charge options per service (name, price, type, status). |
| Service FAQs | `/servicefaq` | Per-service FAQ management (question, answer, status). |
| Service Packages | `/servicepackage` | Bundle services into packages with pricing and discounts. |
| Service Zones | `/servicezone` | Geographic zones (polygon coordinates in JSON) for service availability. |
| Provider Service Requests | `/provider-service-request` | Approve/reject services requested by providers. |

### 4. Booking Management

| Page | Route | Description |
|------|-------|-------------|
| Bookings | `/booking` | Full CRUD. Key actions: assign handyman, update status (pending→confirmed→in_progress→completed→cancelled), view booking details, invoice PDF download, DataTable with export (Excel), bulk actions, earning breakdown chart. |
| Booking Details | `/booking/details/{id}` | Complete booking view with timeline, handyman assignments, payments, extra charges, activity log. |
| Invoice PDF | `/invoice_pdf/{id}` | Download booking invoice as PDF (DomPDF/MPDF). |

### 5. Provider Management

| Page | Route | Description |
|------|-------|-------------|
| Providers | `/provider` | List all providers with pending/approved filters. Features: approve/reject, view detail, change password, time slots, subscription data, DataTable with bulk actions. |
| Provider Detail | `/provider_info/{id}` | Full provider profile with services, bookings, reviews, subscription info. |
| Provider Types | `/providertype` | Categories of providers with commission configuration. |
| Provider Addresses | `/provideraddress` | Multiple service addresses per provider with lat/lng geocoding. |
| Provider Documents | `/providerdocument` | Document uploads with verify/unverify toggle. |
| Provider Slots | `/provider-time-slot/{id}` | Day-wise availability time slots. |
| Provider Payouts | `/providerpayout` | Payout management with create, approve, history. |

### 6. Handyman Management

| Page | Route | Description |
|------|-------|-------------|
| Handymen | `/handyman` | List with pending/approved filters. Features: approve, assign to provider, change password, reviews, detail page, bulk actions. |
| Handyman Types | `/handymantype` | Types with pricing (commission percentage or fixed amount). |
| Handyman Ratings | `/handyman-rating` | Customer ratings specific to handymen. |
| Handyman Payouts | `/handymanpayout` | Payout records with create/approve flow. |

### 7. Customer (User) Management

| Page | Route | Description |
|------|-------|-------------|
| Users | `/user` | Customer list with active/inactive filter. Features: email verification toggle, change password, status toggle, bulk actions, DataTable. |

### 8. Payment Management

| Page | Route | Description |
|------|-------|-------------|
| Payments | `/payment` | Payment list with DataTable, bulk actions. |
| Cash Payments | `/cash-payment-list` | Cash payment workflow: handyman collects → provider → admin approval. |
| Payment History | `/paymenthistory-index-data/{id}` | Per-booking payment audit trail (actions, type, message, datetime, amount). |
| Payment Gateways | Settings page | Configure Stripe, Razorpay, PhonePe (live/test mode, JSON credentials). |

### 9. Subscription & Plans

| Page | Route | Description |
|------|-------|-------------|
| Plans | `/plans` | Create/edit subscription plans with limits (service_limit, booking_limit, handyman_limit, addon_limit, package_limit, post_job_limit). |
| Provider Subscriptions | `/provider/subscription-data` | View all provider subscriptions, statuses, upgrade/downgrade. |
| Subscription Detail | `/provider-subscription/{subscriptionId}` | Individual subscription details with limits, transactions. |

### 10. Coupon Management

| Page | Route | Description |
|------|-------|-------------|
| Coupons | `/coupon` | Discount codes with type (percentage/fixed), service mappings, date ranges, usage count tracking, status toggle. |

### 11. Tax Management

| Page | Route | Description |
|------|-------|-------------|
| Taxes | `/tax` | Tax rates (percentage/fixed), provider tax assignments, status toggle. |

### 12. Loyalty Program

| Page | Route | Description |
|------|-------|-------------|
| Referral & Loyalty | `/referral-loyalty` | Central loyalty management page. |
| Earn Rules | Earn Rule tab | Points per service/category/package with min/max amounts, date ranges, stackable option. |
| Redeem Rules | Redeem Rule tab | Full and partial redemption configurations. |
| Partial Rules | Partial Rule tab | Partial point redemption options. |
| Referral Rules | Referral Rule tab | Point rewards for customer referrals. |
| Point Activity History | `/loyalty-history-index` | All point transactions (earn/redeem), filterable by user. |
| Points Dashboard | `/points-dashboard/{id}` | Per-user points overview. |

### 13. Wallet Management

| Page | Route | Description |
|------|-------|-------------|
| Wallets | `/wallet` | Customer wallet list with balance, status toggle. |
| Wallet History | `/wallet-history-index-data/{id}` | Transaction history per wallet. |
| Withdrawal Requests | `/withdrawal-request` | Pending withdrawal requests with payout approval. |

### 14. Shop / Store Management

| Page | Route | Description |
|------|-------|-------------|
| Shops | `/shop` | Physical store CRUD. Features: shop name, address, lat/lng, registration number, operating hours, contact info, services mapping. |
| Shop Documents | `/shopdocument` | Shop verification documents. |
| Shop Hours | `/shop/{id}/manage-hour` | Day-wise open/close times. |

### 15. Post Job Request Management

| Page | Route | Description |
|------|-------|-------------|
| Post Job Requests | `/post-job-request` | Customer job posts with provider bids, status tracking, service mappings. |

### 16. Help Desk

| Page | Route | Description |
|------|-------|-------------|
| Help Desk Tickets | `/helpdesk` | Support tickets with priority, status, activity log. |

### 17. Promotional Banners

| Page | Route | Description |
|------|-------|-------------|
| Promotional Banners | `/promotionalbanner` | Provider-requested marketing banners with duration, charges, payment tracking, admin approval. |

### 18. Content Management

| Page | Route | Description |
|------|-------|-------------|
| Blog | `/blog` | Blog posts with author, category, SEO fields (meta_title, meta_description, slug), featured toggle. |
| Sliders | `/slider` | Homepage carousel images with title, description, status. |
| Frontend Settings | `/frontend-setting/{page?}` | Landing page configuration (header, footer, login/register page). |

### 19. CMS Pages

| Page | Route | Description |
|------|-------|-------------|
| Terms & Conditions | `/pages/term-condition` | WYSIWYG editor for terms page. |
| Privacy Policy | `/pages/privacy-policy` | WYSIWYG editor for privacy page. |
| About Us | `/pages/about-us` | WYSIWYG editor for about page. |
| Help & Support | `/pages/help-support` | WYSIWYG editor for help/support page. |
| Refund Policy | `/pages/refund-cancellation-policy` | WYSIWYG editor for refund policy page. |
| Data Deletion Request | `/pages/data-deletion-request` | Data deletion policy page. |

### 20. Earnings & Payouts

| Page | Route | Description |
|------|-------|-------------|
| Earnings | `/earning` | Admin earning reports with DataTable, filters. |
| Handyman Earnings | `/handyman-earning` | Per-handyman earning breakdown. |
| Provider Payouts | `/providerpayout` | Create/approve provider payout requests. |
| Handyman Payouts | `/handymanpayout` | Create/approve handyman payout requests. |

### 21. Ratings & Reviews

| Page | Route | Description |
|------|-------|-------------|
| Booking Ratings | `/booking-rating` | Customer ratings for completed bookings. |
| Handyman Ratings | `/handyman-rating` | Ratings specific to handyman performance. |
| Rating Reviews | `/ratingreview` | All reviews with moderation (approve/reject). |

### 22. Notifications & Templates

| Page | Route | Description |
|------|-------|-------------|
| Notification List | `/notification` | In-app notification list. |
| Notification Templates | `/notification-templates` | Push notification templates with multi-language content, per-user-type, status/channel configuration. |
| Mail Templates | `/mail-templates` | Email templates with multi-language subject/body, SMTP config, placeholders, BCC/CC. |
| Push Notification | `/push-notification` | Send manual push notifications via OneSignal. |

### 23. Settings

| Page | Route | Description |
|------|-------|-------------|
| General Settings | `/setting/general` | App name, logo, favicon, email, phone, address, currency, date/time format, decimal precision. |
| SEO Settings | `/setting/seo` | Meta tags per page. |
| Theme Setup | `/setting/theme` | Color scheme, layout configuration. |
| Site Setup | `/setting/site-setup` | Site configuration. |
| Service Config | `/setting/service-config` | Service module settings (slot durations, advance payment %). |
| Promotion Config | `/setting/promotion-config` | Banner promotion settings. |
| Social Media | `/setting/social-media` | Social media links for frontend. |
| Cookie Setup | `/setting/cookie-setup` | GDPR cookie consent configuration. |
| Payment Gateways | Settings page | Stripe, Razorpay, PhonePe configuration. |
| Earning Type | `/save-earning-setting` | Subscription-based or commission-based earning model toggle. |
| User Dashboard | `/save-userdashboard-setting` | User dashboard type configuration. |

### 24. Bank Account Management

| Page | Route | Description |
|------|-------|-------------|
| Banks | `/bank` | Provider bank accounts for payouts (holder name, account number, bank name, branch, default flag). |

### 25. Document Type Management

| Page | Route | Description |
|------|-------|-------------|
| Documents | `/document` | Document type definitions (provider documents, shop documents) with required/optional flag. |

### 26. Language / Translation

| Page | Route | Description |
|------|-------|-------------|
| Language Switcher | `/lang/{locale}` | Switch admin panel language. |
| Translation Editor | AJAX POST | Edit language files directly from admin panel. |

### 27. Geographic Data

| Page | Route | Description |
|------|-------|-------------|
| Countries | AJAX select | Supported countries list. |
| States | AJAX select | States linked to country. |
| Cities | AJAX select | Cities linked to state. |
| Service Zones | `/servicezone` | Polygon-based geographic zones. |

### 28. Database / System Management

| Page | Route | Description |
|------|-------|-------------|
| Database Backup | Via artisan | `handyman_service.sql` dump included. |
| Installer | `/install` | Step-by-step web installer (welcome → requirements → permissions → environment → database → final). |

### 29. Export Features

| Type | Description |
|------|-------------|
| Excel | Booking data export via Maatwebsite Excel. |
| PDF | Invoice download via DomPDF/MPDF. |
| DataTables | All list views support search, sort, paginate, and export. |

---

## Role-Specific Dashboards

### Provider Dashboard (`/home` as provider)
- Total bookings, services, earnings
- Monthly revenue line chart (provider payouts by month)
- Remaining payout amount
- Recent bookings

### Handyman Dashboard (`/home` as handyman)
- Pending/completed/cancelled booking counts
- Total earnings
- Remaining payout
- Recent assigned bookings

### Customer Dashboard (`/home` as user)
- Total bookings
- Wallet balance
- Loyalty points
- Recent bookings

---

## Sidebar Structure

The admin sidebar is dynamic (reorderable via `/sidebar-reorder-save`) and includes:

1. Dashboard
2. Role & Permission
3. Category → Subcategory
4. Service → Service Addon → Service FAQ → Service Package → Service Zone
5. Booking → Booking Rating
6. Provider → Provider Type → Provider Address → Provider Slot → Provider Document → Provider Payout
7. Handyman → Handyman Type → Handyman Rating → Handyman Payout
8. User (Customer)
9. Payment → Cash Payment
10. Plan (Subscription)
11. Coupon
12. Tax
13. Loyalty (Referral & Loyalty)
14. Wallet
15. Shop
16. Post Job Request
17. Help Desk
18. Promotional Banner
19. Blog
20. Slider
21. Earning
22. Notification → Notification Template → Mail Template → Push Notification
23. Document
24. Frontend Setting
25. Setting
26. Bank
27. Language

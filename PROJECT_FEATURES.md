# Handyman Service Web v11.17.0 - Admin Panel Features Documentation

## Project Overview
- **Framework**: Laravel 11.x
- **PHP Version**: ^8.0|^8.2
- **Project Type**: Admin Panel for Handyman Service Management System
- **Version**: 11.17.0

---

## Core Features

### 1. User Management System
- **User Types**: Admin, Provider, Handyman, Customer
- **User Roles & Permissions**: Implemented using `spatie/laravel-permission`
- **User Profile Management**
- **User Favorites**: Favorite providers and services
- **User Status Management**: Active/Inactive
- **Address Management**: Multiple addresses per user
- **Document Verification**: Upload and verify user documents
- **Live Location Tracking**

### 2. Service Management
- **Categories**: Create, manage service categories with images
- **SubCategories**: Hierarchical service categorization
- **Services**: Complete service management with pricing
- **Service Addons**: Additional service options with extra charges
- **Service Packages**: Bundle multiple services into packages
- **Service FAQs**: Frequently asked questions for services
- **Service Proof**: Upload proof of completed work
- **Service Zones**: Geographic zone-based service availability
- **Service Status**: Active/Inactive service control

### 3. Booking System
- **Booking Creation & Management**
- **Booking Status Tracking**: Multiple status stages
- **Booking Address Mapping**
- **Booking Coupon Integration**
- **Booking Extra Charges**
- **Booking Handyman Assignment**
- **Booking Package Mapping**
- **Booking Service Addon Mapping**
- **Booking Rating & Reviews**
- **Booking Activity Log**
- **Booking Cancellation Management**

### 4. Provider Management
- **Provider Registration & Approval**
- **Provider Types**: Different provider categories
- **Provider Documents**: Document upload and verification
- **Provider Address Mapping**
- **Provider Service Areas**: Zone-based service allocation
- **Provider Slots**: Available time slots management
- **Provider Tax Mapping**
- **Provider Payouts**: Payment collection and approval workflow
- **Provider Subscription**: Plan-based subscription system
- **Provider Earnings & Commission Tracking**

### 5. Handyman Management
- **Handyman Registration & Approval**
- **Handyman Types**: Different handyman categories with pricing
- **Handyman Rating & Reviews**
- **Handyman Payouts**: Cash collection and approval workflow
- **Handyman Document Verification**
- **Handyman Zone Assignment**

### 6. Payment System
- **Payment Gateways**:
  - Stripe Integration (`stripe/stripe-php`)
  - Razorpay Integration (`razorpay/razorpay`)
- **Payment History Tracking**
- **Payment Status Management**: Paid, Pending, Failed
- **Cash Payment Workflow**: Handyman → Provider → Admin approval chain
- **Wallet System**:
  - Customer wallet management
  - Wallet transaction history
  - Wallet recharge/withdrawal

### 7. Loyalty Program
- **Loyalty Earn Rules**: Define how customers earn points
- **Loyalty Redeem Rules**: Define how points can be redeemed
- **Loyalty Redeem Partial Rules**: Partial redemption options
- **Loyalty Referral Rules**: Referral-based point earning
- **Loyalty Point Activities**: Track all point transactions
- **Service-specific Loyalty Mapping**

### 8. Subscription & Plans
- **Subscription Plans**: Create and manage provider plans
- **Plan Limits**: Service limits, booking limits, etc.
- **Plan Types**: Limited/Unlimited plans
- **Subscription Status**: Pending, Active, Inactive, Cancelled
- **Subscription Transactions**: Payment history for subscriptions
- **Plan Upgrade/Downgrade**

### 9. Tax Management
- **Tax Configuration**: Multiple tax rates
- **Provider Tax Mapping**: Assign taxes to providers
- **Tax Calculation**: Automatic tax calculation on bookings

### 10. Coupon & Discount System
- **Coupon Creation**: Percentage or fixed amount discounts
- **Coupon Service Mapping**: Apply coupons to specific services
- **Coupon Validity**: Date-based expiration
- **Coupon Usage Tracking**

### 11. Notification System
- **Push Notifications**: OneSignal integration for mobile apps
- **Email Notifications**: Configurable email templates
- **Notification Templates**: Customizable templates with placeholders
- **Notification Template Content Mapping**: Multi-language support
- **In-app Notifications**: Database-driven notifications
- **Twilio SMS Integration**: SMS notifications

### 12. Rating & Review System
- **Booking Ratings**: Customer ratings for completed bookings
- **Handyman Ratings**: Ratings specific to handyman performance
- **Review Management**: Admin moderation of reviews
- **Rating Analytics**

### 13. Content Management
- **Blog Management**: Create and manage blog posts
- **Slider Management**: Homepage slider images
- **Promotional Banners**: Marketing banners with targeting
- **Frontend Settings**: Configure homepage and frontend elements
- **SEO Settings**: Meta tags, descriptions for SEO optimization
- **App Settings**: Mobile app configuration
- **App Download Links**: iOS/Android download links

### 14. Shop/Store Management
- **Shop Creation & Management**
- **Shop Documents**: Verification documents
- **Shop Hours**: Operating hours configuration
- **Shop Services**: Services offered by shop

### 15. Help Desk System
- **Support Tickets**: Customer support requests
- **Help Desk Activities**: Track ticket progress
- **Ticket Status Management**

### 16. Post Job Request System
- **Job Posting**: Customers can post specific job requests
- **Job Bidding**: Providers/handymen can bid on jobs
- **Job Status Tracking**: Requested, Cancelled, etc.
- **Service Mapping**: Link jobs to specific services

### 17. Financial Management
- **Earnings Reports**: Provider and admin earnings
- **Commission Management**: Configure commission rates
- **Payout Management**:
  - Provider payouts
  - Handyman payouts
  - Withdrawal requests
- **Payment History**: Complete transaction audit trail
- **Wallet Management**: Customer wallet operations

### 18. Geographic Management
- **Countries**: Manage supported countries
- **States**: State-level management
- **Cities**: City-level service availability
- **Service Zones**: Define service coverage areas
- **Geocoder Integration**: `toin0u/geocoder-laravel` for address geocoding
- **Google Maps API**: Map integration for location services

### 19. Installer System
- **Step-by-step Installation Wizard**
- **Environment Configuration**
- **Database Setup**
- **Permissions Check**
- **Requirements Validation**
- **Final Setup Completion**

### 20. Export & Reporting
- **Excel Export**: `maatwebsite/excel` for data export
- **PDF Generation**:
  - `barryvdh/laravel-dompdf`
  - `carlos-meneses/laravel-mpdf`
- **DataTables Integration**: `yajra/laravel-datatables-oracle` for advanced tables
- **Booking Reports**
- **Earning Reports**
- **Payout Reports**

### 21. Media Management
- **Spatie Media Library**: `spatie/laravel-medialibrary` for file management
- **File Uploads**: Images, documents, etc.
- **AWS S3 Support**: `league/flysystem-aws-s3-v3` for cloud storage
- **Image Optimization**: Automatic image processing

### 22. Multi-language Support
- **Translation Management**
- **Language Configuration**
- **Localization Support**

### 23. Admin Features
- **Dashboard**: Overview of key metrics
- **Role Management**: Admin, Super Admin roles
- **Permission Management**: Granular permission control
- **Settings Management**: System-wide configuration
- **Mobile App Configuration**: `mobile-config.php`
- **Broadcasting Setup**: Real-time features with Pusher

### 24. API System
- **RESTful API**: Comprehensive API for mobile apps
- **Sanctum Authentication**: `laravel/sanctum` for API tokens
- **API Routes**: Separate admin and frontend APIs
- **Ziggy Integration**: `tightenco/ziggy` for route access in JS

### 25. Additional Features
- **Phone Validation**: `propaganistas/laravel-phone` for international phone numbers
- **Menu Management**: `lavary/laravel-menu` for dynamic menus
- **HTML Builder**: `spatie/laravel-html` for fluent HTML generation
- **Visit Types**: On-site and Online service options
- **Bank Management**: Bank account details for payouts
- **Static Data Management**

---

## Technical Stack

### Backend
- **Framework**: Laravel 11.x
- **PHP**: ^8.0|^8.2
- **Database**: MySQL (configurable)

### Key Packages
| Package | Purpose |
|---------|---------|
| `spatie/laravel-permission` | Role and permission management |
| `spatie/laravel-medialibrary` | File and media management |
| `yajra/laravel-datatables-oracle` | Advanced data tables |
| `maatwebsite/excel` | Excel export/import |
| `barryvdh/laravel-dompdf` | PDF generation |
| `stripe/stripe-php` | Stripe payment gateway |
| `razorpay/razorpay` | Razorpay payment gateway |
| `laravel-notification-channels/onesignal` | Push notifications |
| `twilio/sdk` | SMS notifications |
| `toin0u/geocoder-laravel` | Geocoding services |
| `league/flysystem-aws-s3-v3` | AWS S3 cloud storage |
| `laravel/sanctum` | API authentication |
| `tightenco/ziggy` | JavaScript route access |

### Frontend
- **JavaScript**: Mix/Webpack build system
- **CSS**: Compiled via Laravel Mix
- **Vendor Libraries**:
  - TinyMCE (Rich text editor)
  - FullCalendar (Calendar views)
  - Emoji Picker

---

## Configuration Files
- `app.php` - Application configuration
- `auth.php` - Authentication settings
- `cache.php` - Cache configuration
- `database.php` - Database connections
- `datatables.php` - DataTables configuration
- `dompdf.php` - PDF generation settings
- `excel.php` - Excel export settings
- `filesystems.php` - File storage configuration
- `geocoder.php` - Geocoding settings
- `media-library.php` - Media library configuration
- `permission.php` - Permission system settings
- `services.php` - Third-party services
- `constant.php` - Application constants
- `installer.php` - Installer configuration
- `mobile-config.php` - Mobile app settings
- `notification-setting.php` - Notification configuration
- `sanctum.php` - API authentication settings

---

## Database
- **SQL File**: `handyman_service.sql` (3MB+ database dump included)
- **Models**: 90+ Eloquent models covering all features
- **Migrations**: Database schema in `database/migrations`

---

## Installation
1. Check system requirements
2. Configure environment (`.env`)
3. Run installer wizard at `/install`
4. Configure database connection
5. Set up payment gateways
6. Configure notification channels
7. Set up storage (local or S3)

---

## Project Structure
```
├── app/
│   ├── Models/ (90+ models)
│   ├── Http/Controllers/ (60+ controllers)
│   ├── Services/ (Business logic)
│   ├── Traits/ (Reusable traits)
│   ├── Notifications/ (Notification classes)
│   ├── Mail/ (Email classes)
│   ├── Jobs/ (Background jobs)
│   └── Helper/ (Helper functions)
├── config/ (28 config files)
├── routes/ (web.php, api.php, admin-api.php, frontend.php)
├── resources/ (Views, assets)
├── database/ (Migrations, seeders)
└── public/ (Assets, vendor libraries)
```

---

## Version Information
- **Version**: 11.17.0
- **Last Updated**: April 2024
- **Laravel Version**: 11.x
- **PHP Requirement**: ^8.0|^8.2

---

## Notes
- This is the **Admin Panel** for the Handyman Service ecosystem
- Mobile apps (iOS/Android) would connect via the API
- The system supports multi-provider, multi-handyman, multi-customer workflows
- Comprehensive financial tracking with commission, payout, and wallet systems
- Geolocation-based service delivery with zone management

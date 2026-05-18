<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\BookingResource;
use App\Http\Resources\API\CategoryResource;
use App\Http\Resources\API\ServiceResource;
use App\Http\Resources\API\ShopResource;
use App\Http\Resources\API\SliderResource;
use App\Http\Resources\API\UserResource;
use App\Http\Resources\PromotionalBannerResource;
use App\Models\Booking;
use App\Models\BookingRating;
use App\Models\Category;
use App\Models\LoyaltyReferralRule;
use App\Models\PromotionalBanner;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Shop;
use App\Models\Slider;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
    ) {}

    public function configurations(Request $request): JsonResponse
    {
        $settings = Setting::getAllSettings();

        $config = [];
        foreach ($settings as $setting) {
            $config[$setting->key] = $setting->value;
        }

        $defaults = [
            'site_name' => 'Handyman',
            'site_description' => '',
            'inquiry_email' => '',
            'helpline_number' => '',
            'website' => '',
            'zipcode' => '',
            'site_copyright' => '',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'time_zone' => 'UTC',
            'distance_type' => 'km',
            'radius' => '50',
            'is_user_authorized' => '0',
            'play_store_url' => '',
            'appstore_url' => '',
            'provider_appstore_url' => '',
            'provider_play_store_url' => '',
            'currency_code' => 'USD',
            'currency_position' => 'left',
            'currency_symbol' => '$',
            'decimal_point' => '2',
            'google_map_key' => '',
            'advance_payment_status' => '1',
            'slot_service_status' => '0',
            'digital_service_status' => '0',
            'service_package_status' => '1',
            'service_addon_status' => '1',
            'job_request_service_status' => '0',
            'social_login_status' => '0',
            'google_login_status' => '0',
            'apple_login_status' => '0',
            'otp_login_status' => '0',
            'online_payment_status' => '1',
            'blog_status' => '1',
            'maintenance_mode' => '0',
            'wallet_status' => '1',
            'chat_gpt_status' => '0',
            'test_chat_gpt_without_key' => '0',
            'chat_gpt_key' => '',
            'firebase_notification_status' => '1',
            'firebase_key' => '',
            'facebook_url' => '',
            'linkedin_url' => '',
            'instagram_url' => '',
            'youtube_url' => '',
            'twitter_url' => '',
            'terms_conditions' => '',
            'privacy_policy' => '',
            'help_and_support' => '',
            'refund_policy' => '',
            'earning_type' => 'subscription',
            'auto_assign_status' => '0',
            'is_in_app_purchase_enable' => '0',
            'revenue_cat_entitlement_identifier' => '',
            'revenue_cat_google_api_key' => '',
            'revenue_cat_apple_api_key' => '',
            'provider_banner_amount' => '0',
            'promotional_banner' => '0',
            'enable_chat' => '1',
            'is_nearby_provider_enable' => '0',
        ];

        $response = array_merge($defaults, $config);

        return response()->json($response);
    }

    public function metrics(): JsonResponse
    {
        $metrics = $this->dashboardService->getAdminMetrics();

        return response()->json([
            'status' => 'true',
            'data'   => $metrics,
        ]);
    }

    public function providerDashboard(): JsonResponse
    {
        $providerId = auth()->id();
        $metrics = $this->dashboardService->getProviderMetrics($providerId);

        return response()->json([
            'status' => 'true',
            'data'   => $metrics,
        ]);
    }

    public function charts(): JsonResponse
    {
        return response()->json([
            'status' => 'true',
            'data'   => [
                'revenue_trend'   => $this->dashboardService->getRevenueTrend(),
                'booking_funnel'  => $this->dashboardService->getBookingFunnel(),
                'escrow_trend'    => $this->dashboardService->getEscrowTrend(),
                'insurance_status'=> $this->dashboardService->getInsuranceStats(),
            ],
        ]);
    }

    public function dashboardDetail(Request $request): JsonResponse
    {
        $customerId = $request->customer_id ?? auth()->id();

        $slider = SliderResource::collection(
            Slider::where('status', 1)->orderBy('id', 'desc')->get()
        );

        $promotionalBanner = PromotionalBannerResource::collection(
            PromotionalBanner::where('status', 'accepted')
                ->where('payment_status', 'paid')
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now()->startOfDay())
                ->orderBy('created_at', 'desc')
                ->get()
        );

        $category = CategoryResource::collection(
            Category::where('status', 1)->orderBy('id', 'desc')->get()
        );

        $serviceQuery = Service::where('status', 1)
            ->where(function ($q) {
                $q->where('service_request_status', 'approve')
                  ->orWhereNull('service_request_status');
            })
            ->where('service_type', 'service');

        if (default_earning_type() === 'subscription') {
            $serviceQuery->whereHas('providers', function ($q) {
                $q->where('status', 1)->where('is_subscribe', 1);
            });
        }

        $service = ServiceResource::collection(
            (clone $serviceQuery)->orderBy('created_at', 'desc')->get()
        );

        $featuredService = ServiceResource::collection(
            (clone $serviceQuery)->where('is_featured', 1)->orderBy('created_at', 'desc')->get()
        );

        $provider = UserResource::collection(
            User::where('user_type', 'provider')
                ->where('status', 1)
                ->whereHas('providerService', function ($q) {
                    $q->where('status', 1);
                })
                ->orderBy('id', 'desc')
                ->get()
        );

        $customerReview = BookingRating::with(['customer'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($rating) {
                return [
                    'id'             => $rating->id,
                    'booking_id'     => $rating->booking_id,
                    'customer_id'    => $rating->customer_id,
                    'customer_name'  => optional($rating->customer)->display_name ?? optional($rating->customer)->name,
                    'profile_image'  => optional($rating->customer)->profile_image ?? getSingleMedia($rating->customer, 'profile_image', null),
                    'service_id'     => $rating->service_id,
                    'service_name'   => optional($rating->service)->name,
                    'review'         => $rating->review,
                    'rating'         => $rating->rating,
                    'attchments'     => $rating->attachments,
                    'created_at'     => $rating->created_at,
                ];
            });

        $upcomingBooking = null;
        if ($customerId) {
            $booking = Booking::where('customer_id', $customerId)
                ->whereIn('status', [
                    Booking::STATUS_PAYMENT_HELD,
                    Booking::STATUS_IN_PROGRESS,
                    Booking::STATUS_QUOTE_APPROVED,
                ])
                ->orderBy('date', 'asc')
                ->first();

            if ($booking) {
                $upcomingBooking = new BookingResource($booking);
            }
        }

        $notificationUnreadCount = 0;
        if ($customerId) {
            $customer = User::find($customerId);
            if ($customer) {
                $notificationUnreadCount = $customer->unreadNotifications()->count();
            }
        }

        $isEmailVerified = 0;
        if ($customerId) {
            $customer = User::find($customerId);
            if ($customer) {
                $isEmailVerified = $customer->is_email_verified ?? 0;
            }
        }

        $shop = ShopResource::collection(
            Shop::where('is_active', 1)->orderBy('id', 'desc')->get()
        );

        $referralRule = LoyaltyReferralRule::where('status', 1)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->exists();

        return comman_custom_response([
            'slider'                      => $slider,
            'promotional_banner'          => $promotionalBanner,
            'category'                    => $category,
            'service'                     => $service,
            'featured_service'            => $featuredService,
            'provider'                    => $provider,
            'customer_review'             => $customerReview,
            'upcomming_confirmed_booking'  => $upcomingBooking,
            'notification_unread_count'   => $notificationUnreadCount,
            'is_email_verified'           => $isEmailVerified,
            'shop'                        => $shop,
            'referral_rule'               => $referralRule,
        ]);
    }

    public function firebaseDetails(): JsonResponse
    {
        $otherSetting = Setting::where('type', 'OTHER_SETTING')->first();
        $data = $otherSetting ? json_decode($otherSetting->value) : null;

        $projectId = $data->project_id ?? null;
        $firebaseToken = getAccessToken();

        return response()->json([
            'status' => true,
            'data'   => [
                'project_id'     => $projectId,
                'firebase_token' => $firebaseToken,
            ],
            'message' => 'Firebase details retrieved successfully',
        ]);
    }
}

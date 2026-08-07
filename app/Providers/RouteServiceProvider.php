<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';
    public const FRONTEND = '/';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        // Max 1 OTP send per phone per minute (matches the 60s cooldown).
        RateLimiter::for('otp_send', function (Request $request) {
            $phone = (string) $request->input('phone', '');
            $key = 'otp_send:'.($phone !== '' ? $phone : $request->ip());

            return Limit::perMinutes(1, (int) config('services.msegat.otp_send_rate_limit', 1))->by($key);
        });

        // Cap verification attempts per phone to protect against brute forcing.
        RateLimiter::for('otp_verify', function (Request $request) {
            $id = (string) $request->input('verification_id', '');
            $key = 'otp_verify:'.($id !== '' ? $id : $request->ip());

            return Limit::perMinutes(5, (int) config('services.msegat.otp_max_attempts', 5))->by($key);
        });
    }
}

<?php

namespace App\Providers;

use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Reservation;
use App\Observers\CourseObserver;
use App\Observers\CourseOfferingObserver;
use App\Policies\ReservationPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoApiTransport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mail::extend('brevo', static function (array $config): BrevoApiTransport {
            return new BrevoApiTransport((string) $config['api_key']);
        });

        RateLimiter::for('api', static function (Request $request): Limit {
            $bearerToken = $request->bearerToken();

            if ($bearerToken !== null) {
                return Limit::perMinute(300)->by('token:'.hash('sha256', $bearerToken));
            }

            return Limit::perMinute(60)->by('ip:'.$request->ip());
        });

        Gate::policy(Reservation::class, ReservationPolicy::class);
        Course::observe(CourseObserver::class);
        CourseOffering::observe(CourseOfferingObserver::class);

        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}

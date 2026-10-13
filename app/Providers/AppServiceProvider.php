<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Sprint;
use App\Models\Task;
use App\Observers\CommentObserver;
use App\Observers\SprintObserver;
use App\Observers\TaskObserver;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();

        $this->configureRateLimiting();

        Task::observe(TaskObserver::class);
        Comment::observe(CommentObserver::class);
        Sprint::observe(SprintObserver::class);

        Vite::prefetch(concurrency: 3);
    }

    /**
     * Configure rate limiters for the authentication endpoints.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('google-oauth-redirect', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        RateLimiter::for('google-oauth-callback', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        RateLimiter::for('project-invitations', fn (Request $request) => Limit::perHour(20)->by((string) ($request->user()?->id ?? $request->ip())));

        RateLimiter::for('account-security', fn (Request $request) => Limit::perMinute(6)->by((string) ($request->user()?->id ?? $request->ip())));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}

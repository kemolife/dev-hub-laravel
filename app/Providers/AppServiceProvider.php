<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\CommentPosted;
use App\Events\PostPublished;
use App\Listeners\DispatchWebhooksForEvent;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Observers\PostObserver;
use App\Policies\CommentPolicy;
use App\Policies\PostPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->configureGates();
        $this->configureObservers();
        $this->configureSlowQueryLogging();
        $this->configureRateLimiters();
        $this->configureEventListeners();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureGates(): void
    {
        Gate::define('admin', fn (User $user): bool => $user->isAdmin());
        Gate::define('moderator', fn (User $user): bool => $user->isModerator());
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
    }

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

    protected function configureObservers(): void
    {
        Post::observe(PostObserver::class);
    }

    protected function configureRateLimiters(): void
    {
        RateLimiter::for('api-anonymous', function (Request $request): Limit {
            return Limit::perHour(60)->by($request->ip());
        });

        RateLimiter::for('api-authenticated', function (Request $request): Limit {
            if ($request->user()) {
                return Limit::perHour(1000)->by((string) $request->user()->id);
            }

            return Limit::perHour(60)->by($request->ip());
        });

        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(5)->by($request->input('email').'|'.$request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request): Limit {
            return Limit::perHour(3)->by((string) $request->input('email'));
        });
    }

    protected function configureEventListeners(): void
    {
        Event::listen([PostPublished::class, CommentPosted::class], DispatchWebhooksForEvent::class);
    }

    /**
     * Log queries slower than 100ms to the daily channel in local/testing environments.
     *
     * This surfaces N+1 and missing-index problems during development before they reach production.
     */
    protected function configureSlowQueryLogging(): void
    {
        if (! $this->app->environment('local', 'testing')) {
            return;
        }

        DB::listen(function (QueryExecuted $query): void {
            if ($query->time >= 100) {
                Log::channel('daily')->warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time_ms' => $query->time,
                    'connection' => $query->connectionName,
                ]);
            }
        });
    }
}

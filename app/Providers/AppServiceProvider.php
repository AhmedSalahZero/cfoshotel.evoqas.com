<?php

namespace App\Providers;

use App\Models\Concerns\AllowsLazyLoading;
use App\Support\StudyCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — AppServiceProvider
//  Location: app/Providers/AppServiceProvider.php
//
//  App-wide wiring, in one place:
//    · Frontend    — Vite pre-loads the next screens.
//    · Safety while building — lazy loading and silently dropped attributes
//      throw on your computer, so slow queries (N+1) and typos show up early
//      instead of on the live server.
//    · HTTPS is forced on the live server.
//    · One password rule for the whole application.
// ══════════════════════════════════════════════════════════════════

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Model::preventLazyLoading(! $this->app->isProduction());

        // The hotel models read their relations on demand by design (see AllowsLazyLoading); every other model
        // keeps the strict rule.
        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
            if ($model instanceof AllowsLazyLoading) {
                return;
            }

            throw new LazyLoadingViolationException($model, $relation);
        });
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // While building (not on the live server): how long every request took and how many database queries it ran, one line each in
        // storage/logs/laravel.log, starting with TIMING. It shows where the seconds of a slow screen go.
        if (! $this->app->isProduction() && ! $this->app->runningUnitTests() && ! $this->app->runningInConsole()) {
            $queries = 0;
            $queryMs = 0.0;
            DB::listen(function ($query) use (&$queries, &$queryMs): void {
                $queries++;
                $queryMs += $query->time;
            });
            Event::listen(RequestHandled::class, function (RequestHandled $event) use (&$queries, &$queryMs): void {
                $start = defined('LARAVEL_START') ? LARAVEL_START : ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
                $partial = $event->request->header('X-Inertia-Partial-Data');
                Log::info(sprintf('TIMING %s /%s %s status %d: %.2fs, %d queries (%.2fs)', $event->request->method(), ltrim($event->request->path(), '/'),
                    $partial ? '[later part: '.$partial.']' : '', $event->response->getStatusCode(), microtime(true) - $start, $queries, $queryMs / 1000));
            });
        }

        // Kept results of the calculation are dropped the moment study data is written (see App\Support\StudyCache).
        DB::listen(fn ($query) => StudyCache::noteQuery($query->sql));

        // At least 10 characters with upper and lower case letters and a number.
        Password::defaults(fn () => Password::min(10)->mixedCase()->numbers());
    }
}

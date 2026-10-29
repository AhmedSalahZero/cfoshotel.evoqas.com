<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — HandleInertiaRequests
//  Location: app/Http/Middleware/HandleInertiaRequests.php
//
//  What every screen receives as shared props (usePage().props in Vue):
//
//    auth.user  → the signed-in person: id, name, initials, email, language, theme, role, company
//                 (company = name, read_only, days_left, ends — drives the subscription notices)
//                 (null on the sign-in page)
//    theme      → 'light' | 'dark' for this request (account, else session, else light)
//    flash      → success / error / warning / info messages
//    study      → on every page of a study: what the second top bar needs; null everywhere else (no study module yet)
//    locale     → 'en' | 'ar' for this request
//    timezone   → the application's time zone
//    app        → name and environment
//
//  Everything is a closure, so it is only worked out when a page is
//  actually drawn, never for redirects.
// ══════════════════════════════════════════════════════════════════

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            'auth' => fn () => [
                'user' => ($user = $request->user()) ? [
                    'id'       => $user->id,
                    'name'     => $user->name,
                    'initials' => $user->initials(),
                    'email'    => $user->email,
                    'language' => $user->preferredLanguage(),
                    'theme'    => $user->preferredTheme(),
                    'role'     => $user->role,
                    'company'  => self::companyFor($user),
                ] : null,
            ],

            'theme' => fn () => self::themeFor($request),

            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'info'    => $request->session()->get('info'),
            ],

            'study'    => null,

            'locale'   => fn () => app()->getLocale(),
            'timezone' => config('app.timezone'),

            'app' => [
                'name' => config('app.name'),
                'env'  => config('app.env'),
            ],
        ];
    }

    /** The person's company for the top bar and the subscription notices (null for the super admin). */
    private static function companyFor($user): ?array
    {
        $company = $user->isSuperAdmin() ? null : $user->company;

        return $company ? [
            'name'      => $company->displayName(),
            'read_only' => $company->isReadOnly(),
            'days_left' => $company->daysLeft(),
            'ends'      => $company->subscription_ends_at?->format('Y-m-d'),
        ] : null;
    }

    /** The theme for this request: the account's choice, else what a guest picked, else light. */
    public static function themeFor(Request $request): string
    {
        if ($user = $request->user()) {
            return $user->preferredTheme();
        }

        $session = $request->hasSession() ? $request->session()->get('theme') : null;

        return $session === 'dark' ? 'dark' : 'light';
    }
}

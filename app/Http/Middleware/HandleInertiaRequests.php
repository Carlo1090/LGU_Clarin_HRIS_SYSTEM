<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Hris\HrisResourceController;
use App\Http\Controllers\Hris\PortalController;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    private const NAVIGATION_PRIORITY = [
        '/hris/attendance' => 0,
        '/hris/portal/my-attendance' => 0,
        '/hris/employees' => 1,
        '/hris/leave-applications' => 2,
        '/hris/portal/my-leave-requests' => 2,
    ];

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'isHrmoWorkspace' => fn (): bool => in_array($request->user()?->role?->name, ['HRMO Head', 'HR Officer'], true),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'hrisNavigation' => fn (): array => $request->user()
                ? collect([...PortalController::navigation($request->user()), ...HrisResourceController::navigation()])
                    ->sortBy(fn (array $item) => self::NAVIGATION_PRIORITY[$item['href']] ?? PHP_INT_MAX)
                    ->values()->all()
                : [],
        ];
    }
}

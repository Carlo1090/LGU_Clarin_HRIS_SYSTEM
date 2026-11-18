<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Hris\HrisResourceController;
use App\Http\Controllers\Hris\PortalController;
use App\Models\Notification;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
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
            'notifications' => function () use ($request): array {
                if (! $request->user()) {
                    return ['unreadCount' => 0, 'items' => []];
                }
                $query = Notification::query()->where('user_id', $request->user()->id);

                return [
                    'unreadCount' => (clone $query)->where('is_read', false)->count(),
                    'items' => $query->orderBy('is_read')->latest('id')->limit(12)->get()->map(fn (Notification $item) => [
                        'id' => $item->id, 'title' => $item->title, 'message' => $item->message,
                        'type' => $item->type, 'isRead' => (bool) $item->is_read, 'createdAt' => $item->created_at?->toIso8601String(),
                    ])->all(),
                ];
            },
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'hrisNavigation' => fn (): array => $request->user() ? [...PortalController::navigation($request->user()), ...HrisResourceController::navigation()] : [],
        ];
    }
}

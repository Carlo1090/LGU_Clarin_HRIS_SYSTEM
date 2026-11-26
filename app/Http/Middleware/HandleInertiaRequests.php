<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Hris\AttendanceCorrectionController;
use App\Http\Controllers\Hris\HrisResourceController;
use App\Http\Controllers\Hris\PortalController;
use App\Models\Announcement;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
            'canViewAttendanceCorrections' => fn (): bool => $request->user() && AttendanceCorrectionController::available($request->user()),
            'notifications' => fn (): array => $this->notificationFeed($request->user()),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'hrisNavigation' => fn (): array => $request->user()
                ? collect([...PortalController::navigation($request->user()), ...HrisResourceController::navigation()])
                    ->sortBy(fn (array $item) => self::NAVIGATION_PRIORITY[$item['href']] ?? PHP_INT_MAX)
                    ->values()->all()
                : [],
        ];
    }

    private function notificationFeed(?User $user): array
    {
        if (! $user?->is_active) {
            return ['unreadCount' => 0, 'items' => []];
        }

        $notifications = $user->notifications()->getQuery();
        $known = (clone $notifications)->where('related_model_type', Announcement::class)->whereNotNull('related_model_id')->select('related_model_id');
        $pending = Announcement::query()->visible()->whereNotIn('id', $known);

        if ((clone $pending)->exists()) {
            DB::transaction(function () use ($user, $pending): void {
                // Serialize registration for this recipient across concurrent requests.
                User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                $pending->withVisibleAt()->orderBy('visible_at')->orderBy('id')->get()->chunk(100)->each(function ($announcements) use ($user): void {
                    $user->notifications()->insert($announcements->map(fn (Announcement $announcement): array => [
                        'user_id' => $user->id,
                        'title' => $announcement->title,
                        'message' => Str::limit(Str::squish($announcement->content), 180),
                        'type' => 'announcement',
                        'related_model_type' => Announcement::class,
                        'related_model_id' => $announcement->id,
                        'created_at' => $announcement->getAttribute('visible_at') ?? now(),
                        'updated_at' => now(),
                    ])->all());
                });
            });
        }

        $query = $notifications->where(fn ($feed) => $feed
            ->whereNull('related_model_type')
            ->orWhere('related_model_type', '!=', Announcement::class)
            ->orWhereIn('related_model_id', Announcement::query()->visible()->select('id')));

        return [
            'unreadCount' => (clone $query)->where('is_read', false)->count(),
            'items' => $query->orderBy('is_read')->latest('created_at')->latest('id')->limit(12)->get()->map(fn (Notification $item) => [
                'id' => $item->id, 'title' => $item->title, 'message' => $item->message,
                'type' => $item->type, 'isRead' => $item->is_read, 'createdAt' => $item->created_at?->toIso8601String(),
            ])->all(),
        ];
    }
}

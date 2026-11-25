<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ExpireIdleHrisSession;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $trackLogin = isset(ExpireIdleHrisSession::MINUTES[$user->role?->name ?? '']);
        $lastLogin = $trackLogin ? $user->loginActivities()->where('status', 'success')->max('login_at') : null;

        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'showLastLogin' => $trackLogin,
            'lastLoginAt' => $lastLogin === null ? null : Date::parse($lastLogin)->toIso8601String(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    public function uploadPhoto(Request $request): RedirectResponse
    {
        $request->validate(['photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096']]);
        $path = $request->file('photo')->store('profile-photos', 'local');
        abort_unless($path, 500, 'The photo could not be saved.');

        try {
            $previous = DB::transaction(function () use ($request, $path) {
                $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);
                $previous = $user->avatar;
                $user->forceFill(['avatar' => '/'.$path])->save();

                return $previous;
            });
        } catch (Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }

        if ($previous && str_starts_with($previous, '/profile-photos/')) {
            Storage::disk('local')->delete(ltrim($previous, '/'));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profile photo updated.']);

        return to_route('profile.edit');
    }

    public function photo(string $filename): StreamedResponse
    {
        $disk = Storage::disk('local');
        $path = 'profile-photos/'.$filename;
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, headers: ['Cache-Control' => 'private, max-age=86400', 'X-Content-Type-Options' => 'nosniff']);
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();
        if ($user->avatar && str_starts_with($user->avatar, '/profile-photos/')) {
            Storage::disk('local')->delete(ltrim($user->avatar, '/'));
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

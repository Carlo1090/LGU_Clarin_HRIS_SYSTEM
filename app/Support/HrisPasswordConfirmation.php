<?php

namespace App\Support;

use Illuminate\Http\Request;

class HrisPasswordConfirmation
{
    public static function require(Request $request): void
    {
        $key = 'hris.password_confirmed_at.'.$request->user()->id;
        $confirmedAt = $request->session()->get($key);
        if (! $request->filled('password') && is_int($confirmedAt) && now()->timestamp - $confirmedAt < 300) {
            return;
        }

        $request->validate(['password' => ['required', 'string', 'current_password']]);
        $request->session()->put($key, now()->timestamp);
    }
}

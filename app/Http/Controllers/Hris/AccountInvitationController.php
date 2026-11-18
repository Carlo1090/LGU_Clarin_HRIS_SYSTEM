<?php

namespace App\Http\Controllers\Hris;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Notifications\HrisAccountSetup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AccountInvitationController
{
    private const INVITABLE_ROLES = [
        'Employee', 'Department Head', 'HR Officer', 'Payroll Officer', 'Accountant',
        'Treasurer', 'Mayor', 'MIS Administrator', 'DPO', 'Auditor',
    ];

    public function create(Request $request): Response
    {
        $this->authorize($request);

        return Inertia::render('hris/invite-account', [
            'roles' => self::INVITABLE_ROLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize($request);

        $input = $request->validate([
            'employee_number' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'role' => ['required', Rule::in(self::INVITABLE_ROLES)],
            'approval_reference' => ['required', 'string', 'max:100'],
        ]);
        $email = isset($input['email']) && $input['email'] !== '' ? Str::lower($input['email']) : null;
        if (! $email) {
            return back()->withErrors(['email' => 'A secure no-email account setup process is still required.']);
        }

        DB::transaction(function () use ($request, $input, $email): void {
            $employee = Employee::query()->with('department')
                ->where('employee_number', $input['employee_number'])->lockForUpdate()->first();

            if (! $employee || ! isset(Department::PLANTILLA_OFFICES[$employee->department?->code])) {
                throw ValidationException::withMessages(['employee_number' => 'Select an employee assigned to a recognized plantilla office.']);
            }

            $role = Role::query()->where('name', $input['role'])->where('guard_name', 'web')->first();
            abort_unless($role, 409, 'The approved roles must be configured before inviting users.');

            $user = User::query()->where('email', $email)->first();
            if ($employee->user_id && $user?->id === $employee->user_id
                && $user->employee_id === $employee->id && $user->role_id === $role->id
                && $user->requires_mfa && $user->is_active && ! $user->hasVerifiedEmail()) {
                return;
            }

            if ($user || $employee->user_id || User::query()->where('employee_id', $employee->id)->exists()) {
                throw ValidationException::withMessages(['email' => 'That email or employee is already linked to an account.']);
            }

            $user = new User([
                'name' => trim($employee->first_name.' '.$employee->last_name),
                'email' => $email,
                'password' => Str::random(64),
            ]);
            $user->forceFill(['role_id' => $role->id, 'employee_id' => $employee->id, 'requires_mfa' => true])->save();
            $employee->forceFill(['user_id' => $user->id])->save();

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'account.invitation_created',
                'model_type' => User::class,
                'model_id' => $user->id,
                'metadata' => json_encode(['role' => $role->name, 'approval_reference' => $input['approval_reference']], JSON_THROW_ON_ERROR),
            ]);
        });

        $status = HrisAccountSetup::sendTo($email);

        if ($status !== Password::RESET_LINK_SENT) {
            return back()->withErrors(['email' => __($status)]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Account setup email sent.']);

        return back();
    }

    private function authorize(Request $request): void
    {
        $user = $request->user();
        abort_unless($user->role?->name === 'HRMO Head' && $user->requires_mfa && $user->hasEnabledTwoFactorAuthentication(), 403);
    }
}

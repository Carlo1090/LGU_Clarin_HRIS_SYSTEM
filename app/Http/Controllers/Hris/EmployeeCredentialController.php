<?php

namespace App\Http\Controllers\Hris;

use App\Http\Responses\EmployeeActivationSlips;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeActivationCode;
use App\Models\User;
use App\Services\EmployeeAccountCreator;
use App\Services\EmployeeAccountDeactivation;
use App\Support\HrisPasswordConfirmation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EmployeeCredentialController
{
    public function deactivate(Request $request, Employee $employee, EmployeeAccountDeactivation $accounts): Response
    {
        abort_unless($request->user()->role?->name === 'HRMO Head', 403);
        $data = $request->validate([
            'password' => ['required', 'string'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $accounts->deactivate($request->user(), $employee->user()->firstOrFail(), $data['password'], $data['reason']);

        return to_route('hris.profile-review.index');
    }

    public function reactivate(Request $request, Employee $employee, EmployeeAccountDeactivation $accounts): Response
    {
        abort_unless($request->user()->role?->name === 'HRMO Head', 403);
        $data = $request->validate(['password' => ['required', 'string']]);

        return EmployeeActivationSlips::respond($request, [$accounts->reactivate($request->user(), $employee, $data['password'])], reissued: true);
    }

    public function reissue(Request $request, Employee $employee, EmployeeAccountCreator $accounts, EmployeeAccountDeactivation $sessions): Response
    {
        abort_unless(in_array($request->user()->role?->name, ['HR Officer', 'HRMO Head'], true), 403);
        $data = $request->validate([
            'revoke_now' => ['sometimes', 'boolean'],
        ]);
        $revokeNow = $data['revoke_now'] ?? false;
        abort_if($revokeNow && $request->user()->role?->name !== 'HRMO Head', 403);
        HrisPasswordConfirmation::require($request);
        $slip = DB::transaction(function () use ($accounts, $employee, $request, $revokeNow, $sessions): array {
            $slip = $accounts->reissue($employee, $request->user());
            if ($revokeNow) {
                $account = $employee->user()->firstOrFail();
                $revoked = $sessions->revokeSessions($account);
                AuditLog::query()->create([
                    'user_id' => $request->user()->id, 'action' => 'employee.sessions_revoked',
                    'model_type' => User::class, 'model_id' => $account->id,
                    'metadata' => json_encode(['sessions_revoked' => $revoked, 'reason' => 'suspected_compromise'], JSON_THROW_ON_ERROR),
                ]);
            }

            return $slip;
        }, attempts: 3);

        return EmployeeActivationSlips::respond($request, [$slip], reissued: true);
    }

    public function print(Request $request): Response
    {
        abort_unless($request->user()->can('manage-hris-resource', 'employees'), 403);
        $ids = $request->validate([
            'issuances' => ['required', 'array', 'max:5000'],
            'issuances.*' => ['required', 'integer', 'distinct'],
        ])['issuances'];
        $owned = EmployeeActivationCode::query()->where('issued_by', $request->user()->id)
            ->whereIntegerInRaw('id', $ids)->pluck('id')->all();
        abort_unless(count($owned) === count($ids), 403);

        AuditLog::query()->create([
            'user_id' => $request->user()->id, 'action' => 'employee.activation_slips_print_requested',
            'model_type' => EmployeeActivationCode::class,
            'metadata' => json_encode(['issuance_ids' => $owned], JSON_THROW_ON_ERROR),
        ]);

        return response()->noContent();
    }
}

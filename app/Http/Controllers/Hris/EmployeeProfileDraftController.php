<?php

namespace App\Http\Controllers\Hris;

use App\Models\Employee;
use App\Models\EmployeeProfileDraft;
use App\Models\EmployeeProfileRevision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EmployeeProfileDraftController
{
    public function update(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->role?->name === 'Employee' && $user->employee_id !== null, 403);
        $data = $request->validate([
            'birth_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'tin' => ['sometimes', 'nullable', 'string', 'max:64'],
            'gsis_bp_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'pwd' => ['sometimes', 'nullable', 'in:yes,no'],
            'pwd_id_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'indigenous_people' => ['sometimes', 'nullable', 'in:yes,no'],
            'indigenous_people_detail' => ['sometimes', 'nullable', 'string', 'max:255'],
            'solo_parent' => ['sometimes', 'nullable', 'in:yes,no'],
            'solo_parent_id_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'civil_service_eligibility' => ['sometimes', 'nullable', 'string', 'max:255'],
            'eligibility_other' => ['sometimes', 'nullable', 'string', 'max:255'],
            'date_regularized' => ['sometimes', 'nullable', $request->input('date_regularized') === 'not_applicable' ? 'in:not_applicable' : 'date_format:Y-m-d'],
            'date_of_original_appointment' => ['sometimes', 'nullable', $request->input('date_of_original_appointment') === 'not_applicable' ? 'in:not_applicable' : 'date_format:Y-m-d'],
            'date_of_last_promotion_appointment' => ['sometimes', 'nullable', $request->input('date_of_last_promotion_appointment') === 'not_applicable' ? 'in:not_applicable' : 'date_format:Y-m-d'],
        ]);

        DB::transaction(function () use ($user, $data): void {
            $employee = Employee::query()->lockForUpdate()->findOrFail($user->employee_id);
            $draft = EmployeeProfileDraft::query()->find($employee->id);
            $values = array_replace($draft?->values ?? [], $data);
            foreach (EmployeeProfileRevision::DETAILS as $field => $detail) {
                $on = $field === 'civil_service_eligibility' ? 'Other' : 'yes';
                if (($values[$field] ?? null) !== $on) {
                    unset($values[$detail]);
                }
            }
            EmployeeProfileDraft::query()->updateOrCreate(['employee_id' => $employee->id], ['values' => $values]);
        }, attempts: 3);

        return $request->expectsJson() ? response()->noContent() : to_route('hris.my-profile');
    }
}

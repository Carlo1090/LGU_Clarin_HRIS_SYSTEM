<?php

namespace App\Http\Controllers\Hris;

use App\Casts\EmployeeIdentifier;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeProfileDraft;
use App\Models\EmployeeProfileRevision as Revision;
use App\Models\SystemSetting;
use App\Models\User;
use App\Support\EmployeeImportTemplate;
use App\Support\HrisPasswordConfirmation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeProfileReviewController
{
    /** @return list<string> */
    public static function naFields(Employee $employee): array
    {
        $value = self::setting('employee_profile.na_fields');
        $settings = json_decode($value ?? '{}', true);
        $allowed = $settings[$employee->employment_status] ?? [];

        return is_array($allowed) ? array_values(array_intersect(Revision::ALLOW_NA, $allowed)) : [];
    }

    public function submit(Request $request): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->role?->name === 'Employee' && $actor->employee_id !== null, 403);
        DB::transaction(function () use ($actor): void {
            $employee = Employee::query()->lockForUpdate()->findOrFail($actor->employee_id);
            $draft = EmployeeProfileDraft::query()->find($employee->id);
            $values = $draft?->values ?? [];
            $existing = Revision::query()->where('employee_id', $employee->id)->get()->groupBy('field');
            $allowedNa = self::naFields($employee);
            $ready = [];

            foreach (Revision::FIELDS as $field) {
                $revisions = $existing->get($field, collect());
                if (! array_key_exists($field, $values) && $revisions->contains(fn (Revision $revision) => in_array($revision->status, ['approved', 'superseded', 'submitted'], true))) {
                    continue;
                }
                if ($revisions->contains('status', 'submitted')) {
                    throw ValidationException::withMessages([$field => 'Wait for the current review before submitting another change to this field.']);
                }
                $value = $values[$field] ?? null;
                $na = $value === 'not_applicable' && in_array($field, $allowedNa, true);
                $detailKey = Revision::DETAILS[$field] ?? null;
                $detail = $detailKey === null ? null : ($values[$detailKey] ?? null);
                if (! $na) {
                    $rules = match ($field) {
                        'birth_date' => ['date_format:Y-m-d', 'before_or_equal:today'],
                        'date_regularized', 'date_of_original_appointment', 'date_of_last_promotion_appointment' => ['date_format:Y-m-d'],
                        'pwd', 'indigenous_people', 'solo_parent' => [Rule::in(['yes', 'no'])],
                        'civil_service_eligibility' => [Rule::in(config('employee_eligibilities'))],
                        'tin' => ['string', 'max:64', Rule::notIn(['not_applicable'])],
                        default => ['string', 'max:100', Rule::notIn(['not_applicable'])],
                    };
                    Validator::make([$field => $value], [$field => ['required', ...$rules]])->validate();
                    if (($field === 'civil_service_eligibility' && $value === 'Other') || (in_array($field, ['pwd', 'solo_parent'], true) && $value === 'yes')) {
                        Validator::make([$detailKey => $detail], [$detailKey => ['required', 'string', 'max:255']])->validate();
                    }
                }
                $ready[$field] = ['value' => $value, 'detail' => $detail, 'not_applicable' => $na];
            }

            if ($ready === []) {
                throw ValidationException::withMessages(['profile' => 'There are no draft changes to submit.']);
            }
            foreach ($ready as $field => $submitted) {
                $revision = Revision::query()->create([
                    'employee_id' => $employee->id, 'field' => $field, 'status' => 'submitted',
                    'submitted' => $submitted, 'previous' => $this->current($employee, $field), 'submitted_by' => $actor->id,
                ]);
                $this->audit($actor->id, $revision, 'submitted');
            }
            $draft?->delete();
        }, attempts: 3);

        return to_route('hris.my-profile');
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()->role?->name === 'HRMO Head', 403);
        $records = Revision::query()->with('employee')->where('status', 'submitted')->oldest('id')->simplePaginate(20)
            ->through(function (Revision $revision): array {
                $current = $this->current($revision->employee, $revision->field);

                return [
                    'id' => $revision->id, 'employeeId' => $revision->employee_id, 'employeeNumber' => $revision->employee->employee_number,
                    'field' => $revision->field, 'submitted' => $this->display($revision->field, $revision->submitted),
                    'current' => $this->display($revision->field, $current), 'conflict' => $current['value'] !== null && $current !== $revision->submitted,
                    'version' => $this->version($revision, $current),
                ];
            });

        $audit = AuditLog::query()->with('user:id,name')->whereIn('action', [
            'employee.identifier_revealed', 'employee.activation_code_reissued', 'employee.sessions_revoked',
            'employee.activation_recovery_used', 'employee.account_deactivated', 'employee.account_reactivation_started',
        ])->latest('id')->simplePaginate(20, pageName: 'audit_page')->withQueryString();
        $reactivatable = User::query()->where('is_active', false)->whereHas('role', fn ($query) => $query->where('name', 'Employee'))
            ->whereKey($audit->getCollection()->where('action', 'employee.account_deactivated')->pluck('model_id')->all())
            ->pluck('employee_id', 'id');
        $audit->through(fn (AuditLog $entry): array => [
            'id' => $entry->id, 'action' => $entry->action, 'actor' => $entry->user?->name,
            'subjectId' => $entry->model_id, 'subjectType' => class_basename($entry->model_type),
            'at' => $entry->created_at?->toIso8601String(),
            'reactivateEmployeeId' => $entry->action === 'employee.account_deactivated' ? $reactivatable->get($entry->model_id) : null,
            'details' => array_intersect_key(json_decode($entry->metadata ?? '{}', true) ?: [],
                array_flip(['field', 'revision_id', 'sessions_revoked', 'reason', 'fallback'])),
        ]);

        return Inertia::render('hris/employee-profile-review', [
            'records' => $records->withQueryString(), 'audit' => $audit,
            'settings' => [
                'statuses' => $this->employmentStatuses(),
                'eligibleFields' => Revision::ALLOW_NA,
                'na_fields' => json_decode(self::setting('employee_profile.na_fields') ?? '{}', true) ?: [],
                'privacy_notice' => self::setting('employee_profile.privacy_notice') ?? '',
            ],
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->role?->name === 'HRMO Head', 403);
        $data = $request->validate([
            'na_fields' => ['present', 'array'],
            'na_fields.*' => ['array'],
            'na_fields.*.*' => [Rule::in(Revision::ALLOW_NA)],
            'privacy_notice' => ['nullable', 'string', 'max:4000'],
        ]);
        if (array_diff(array_keys($data['na_fields']), $this->employmentStatuses()) !== []) {
            throw ValidationException::withMessages(['na_fields' => 'Use an existing employment status.']);
        }
        DB::transaction(function () use ($actor, $data): void {
            foreach ([
                'employee_profile.na_fields' => json_encode($data['na_fields'], JSON_THROW_ON_ERROR),
                'employee_profile.privacy_notice' => $data['privacy_notice'] ?? null,
            ] as $key => $value) {
                SystemSetting::query()->updateOrCreate(['key' => $key], [
                    'value' => $value, 'category' => 'employee_profile', 'is_active' => true,
                ]);
            }
            AuditLog::query()->create(['user_id' => $actor->id, 'action' => 'employee.profile_settings_updated']);
        });

        return to_route('hris.profile-review.index');
    }

    private static function setting(string $key): ?string
    {
        return SystemSetting::query()->where('key', $key)->where('is_active', true)->value('value');
    }

    /** @return list<string> */
    private function employmentStatuses(): array
    {
        return collect(app(EmployeeImportTemplate::class)->statuses())
            ->merge(Employee::query()->distinct()->pluck('employment_status'))
            ->filter()->unique()->sort()->values()->all();
    }

    public function decide(Request $request, Revision $revision): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->role?->name === 'HRMO Head', 403);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['use_submitted', 'keep_existing', 'return'])],
            'version' => ['required', 'string', 'size:64'],
            'reason' => ['required_if:decision,return', 'nullable', 'string', 'max:1000'],
        ]);
        HrisPasswordConfirmation::require($request);

        DB::transaction(function () use ($actor, $revision, $data): void {
            $employee = Employee::query()->lockForUpdate()->findOrFail($revision->employee_id);
            $revision = Revision::query()->lockForUpdate()->findOrFail($revision->id);
            $current = $this->current($employee, $revision->field);
            abort_unless($revision->status === 'submitted' && hash_equals($this->version($revision, $current), $data['version']), 409, 'The employee record changed. Reload the review and try again.');
            $status = match ($data['decision']) {
                'use_submitted' => 'approved',
                'keep_existing' => 'superseded',
                'return' => 'returned',
            };
            if ($status === 'superseded' && $current['value'] === null) {
                throw ValidationException::withMessages(['decision' => 'There is no existing value to keep.']);
            }
            $submitted = $revision->submitted;
            if ($status === 'approved') {
                if ($submitted['not_applicable'] && ! in_array($revision->field, self::naFields($employee), true)) {
                    throw ValidationException::withMessages(['decision' => 'The current employment status does not allow N/A for this field.']);
                }
                $value = $submitted['not_applicable'] ? null : $submitted['value'];
                $attributes = match ($revision->field) {
                    'pwd', 'indigenous_people' => [$revision->field => $value === 'yes' ? 'Y' : null],
                    'solo_parent' => ['solo_parent_id_number' => $value === 'yes' ? $submitted['detail'] : null],
                    'civil_service_eligibility' => [$revision->field => $value === 'Other' ? $submitted['detail'] : $value],
                    default => [$revision->field => $value],
                };
                $employee->fill($attributes)->save();
            }
            $revision->update([
                'status' => $status, 'previous' => $current,
                'resolved' => match ($status) {
                    'approved' => $submitted, 'superseded' => $current, default => null
                },
                'reviewed_by' => $actor->id, 'reviewed_at' => now(),
                'reason' => $status === 'returned' ? $data['reason'] : null,
            ]);
            $this->audit($actor->id, $revision, $status);
            $approved = Revision::query()->where('employee_id', $employee->id)
                ->whereIn('status', ['approved', 'superseded'])->distinct()->pluck('field')->all();
            if (array_diff(Revision::FIELDS, $approved) === []) {
                AuditLog::query()->firstOrCreate([
                    'action' => 'employee.profile_approved', 'model_type' => Employee::class, 'model_id' => $employee->id,
                ], ['user_id' => $actor->id]);
            }
        }, attempts: 3);

        return to_route('hris.profile-review.index');
    }

    /** @return array{value: mixed, detail: mixed, not_applicable: bool} */
    private function current(Employee $employee, string $field): array
    {
        $value = match ($field) {
            'birth_date', 'date_regularized', 'date_of_original_appointment', 'date_of_last_promotion_appointment' => $employee->getAttribute($field)?->format('Y-m-d'),
            'pwd', 'indigenous_people' => $employee->getAttribute($field) === 'Y' ? 'yes' : null,
            'solo_parent' => $employee->solo_parent_id_number === null ? null : 'yes',
            default => $employee->getAttribute($field),
        };

        return ['value' => $value, 'detail' => $field === 'solo_parent' ? $employee->solo_parent_id_number : null, 'not_applicable' => false];
    }

    /** @param array{value: mixed, detail: mixed, not_applicable: bool} $value */
    private function display(string $field, array $value): ?string
    {
        if ($value['not_applicable']) {
            return 'N/A';
        }
        if (in_array($field, ['tin', 'gsis_bp_number'], true)) {
            return EmployeeIdentifier::mask($value['value']);
        }
        if ($value['detail'] !== null) {
            return $value['value'].' · '.(in_array($field, ['pwd', 'solo_parent'], true) ? EmployeeIdentifier::mask($value['detail']) : $value['detail']);
        }

        return $value['value'];
    }

    /** @param array<string, mixed> $current */
    private function version(Revision $revision, array $current): string
    {
        return hash_hmac('sha256', json_encode([$revision->id, $current], JSON_THROW_ON_ERROR), config('app.key'));
    }

    private function audit(int $actor, Revision $revision, string $status): void
    {
        AuditLog::query()->create([
            'user_id' => $actor, 'action' => 'employee.profile_field_'.$status,
            'model_type' => Revision::class, 'model_id' => $revision->id,
            'metadata' => json_encode(['employee_id' => $revision->employee_id, 'field' => $revision->field], JSON_THROW_ON_ERROR),
        ]);
    }
}

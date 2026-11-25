<?php

use App\Models\Employee;
use App\Models\EmployeeProfileRevision;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('employee submits encrypted revisions and only HRMO Head can resolve conflicts or return fields', function () {
    $this->withoutVite();
    $employee = Employee::factory()->create(['tin' => '123443219', 'employment_status' => 'active', 'birth_date' => null]);
    $owner = User::factory()->for($employee)->for(Role::factory()->state(['name' => 'Employee']))->create();
    $head = User::factory()->for(Role::factory()->state(['name' => 'HRMO Head']))->create();
    $officer = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->create();
    $settings = route('hris.profile-review.settings');
    $this->actingAs($officer)->put($settings, ['na_fields' => []])->assertForbidden();
    $this->actingAs($head)->put($settings, ['na_fields' => ['active' => ['tin']]])->assertSessionHasErrors('na_fields.active.0');
    $this->put($settings, [
        'na_fields' => ['active' => ['gsis_bp_number']], 'privacy_notice' => 'HRMO personnel notice.',
    ])->assertSessionHasNoErrors();
    $draft = [
        'birth_date' => '1990-01-02', 'tin' => '535313535', 'gsis_bp_number' => 'not_applicable',
        'pwd' => 'no', 'indigenous_people' => 'no', 'solo_parent' => 'no',
        'civil_service_eligibility' => 'Other', 'eligibility_other' => 'Reviewed eligibility',
        'date_regularized' => '2020-01-01', 'date_of_original_appointment' => '2020-01-01',
        'date_of_last_promotion_appointment' => '2024-01-01',
    ];

    $submit = route('hris.my-profile.submit');
    $save = route('hris.my-profile.draft');
    $queue = route('hris.profile-review.index');
    $this->actingAs($owner)->patchJson($save, $draft)->assertNoContent();
    $this->post($submit)->assertRedirect(route('hris.my-profile'));
    expect($employee->fresh()->birth_date)->toBeNull()
        ->and(DB::table('employee_profile_revisions')->where('field', 'tin')->value('submitted'))->not->toContain('535313535');
    $this->assertDatabaseCount('employee_profile_revisions', 10);
    $this->actingAs($officer)->get($queue)->assertForbidden();
    $this->actingAs($head);
    $readQueue = function () use ($queue): array {
        $records = [];
        $this->get($queue)->assertOk()->assertInertia(function (AssertableInertia $page) use (&$records): void {
            $records = collect($page->toArray()['props']['records']['data'])->keyBy('field')->all();
        });

        return $records;
    };
    $records = $readQueue();
    expect($records['tin']['submitted'])->toBe('••••3535')->and($records['tin']['conflict'])->toBeTrue();
    $reveal = route('hris.profile-review.reveal', $records['tin']['id']);
    $this->actingAs($officer)->postJson($reveal, ['password' => 'password'])->assertForbidden();
    $this->actingAs($head)->postJson($reveal, ['password' => 'password'])->assertOk()->assertJsonPath('value', '535313535');
    $this->get($queue)->assertInertia(fn (AssertableInertia $page) => $page
        ->where('audit.data.0.action', 'employee.identifier_revealed')
        ->where('audit.data.0.details.revision_id', $records['tin']['id'])
        ->missing('audit.data.0.details.value'));
    $decide = fn (array $record, string $decision, array $extra = []) => $this->post(route('hris.profile-review.decide', $record['id']),
        $extra + ['decision' => $decision, 'password' => 'password', 'version' => $record['version']]);

    $decide($records['tin'], 'keep_existing', ['password' => 'wrong'])->assertSessionHasErrors('password');
    $raw = $employee->fresh()->getRawOriginal('tin');
    $decide($records['tin'], 'keep_existing')->assertSessionHasNoErrors()->assertRedirect($queue);
    expect($employee->fresh()->getRawOriginal('tin'))->toBe($raw);
    $this->assertDatabaseHas('employee_profile_revisions', ['field' => 'tin', 'status' => 'superseded']);
    $decide($records['birth_date'], 'return', ['reason' => 'Check the day.'])->assertSessionHasNoErrors();
    $this->actingAs($owner)->get(route('hris.my-profile'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('reviewFields.birth_date.reason', 'Check the day.')
            ->where('privacyNotice', 'HRMO personnel notice.'));
    $this->patchJson($save, ['birth_date' => '1990-01-03'])->assertNoContent();
    $this->post($submit)->assertSessionHasNoErrors();
    $this->actingAs($head);
    foreach ($readQueue() as $record) {
        $decide($record, 'use_submitted')->assertSessionHasNoErrors()->assertRedirect($queue);
    }
    expect($employee->fresh()->birth_date->toDateString())->toBe('1990-01-03')
        ->and($employee->fresh()->civil_service_eligibility)->toBe('Reviewed eligibility')
        ->and($employee->fresh()->gsis_bp_number)->toBeNull();
    $this->assertDatabaseHas('audit_logs', ['action' => 'employee.profile_approved', 'model_id' => $employee->id]);
    expect(EmployeeProfileRevision::query()->where('field', 'gsis_bp_number')->sole()->resolved['not_applicable'])->toBeTrue();
});

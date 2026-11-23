<?php

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\Payroll;
use App\Models\PrivacyRequest;
use App\Models\User;
use Database\Seeders\DemoDashboardSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('demo dashboard seeding is local only, repeatable, and populates HR analytics', function () {
    $this->seed(DemoDashboardSeeder::class);
    expect(Employee::count())->toBe(0);

    $originalEnvironment = app()->environment();
    app()['env'] = 'local';
    try {
        $this->seed(DemoDashboardSeeder::class);
        $counts = [Employee::count(), AttendanceRecord::count(), LeaveApplication::count(), Payroll::count(), PrivacyRequest::count()];
        $this->seed(DemoDashboardSeeder::class);
        expect([Employee::count(), AttendanceRecord::count(), LeaveApplication::count(), Payroll::count(), PrivacyRequest::count()])->toBe($counts);
        expect($counts[0])->toBe(12)->and($counts[1])->toBeGreaterThan(0)->and($counts[2])->toBeGreaterThan(0)->and($counts[3])->toBeGreaterThan(0)->and($counts[4])->toBeGreaterThan(0);

        $officer = User::where('email', 'hr-officer@example.com')->firstOrFail();
        $this->withoutVite()->actingAs($officer)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('analytics.scope', 'Municipal workforce')
            ->has('analytics.records.data')
            ->where('analytics.bars.data.5.primary', fn ($value) => $value > 0));
    } finally {
        app()['env'] = $originalEnvironment;
    }
});

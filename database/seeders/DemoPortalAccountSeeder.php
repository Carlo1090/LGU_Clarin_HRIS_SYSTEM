<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoPortalAccountSeeder extends Seeder
{
    private const ACCOUNTS = [
        'department-head@example.com' => 'Department Head',
        'payroll@example.com' => 'Payroll Officer',
        'accountant@example.com' => 'Accountant',
        'treasurer@example.com' => 'Treasurer',
        'mayor@example.com' => 'Mayor',
        'mis@example.com' => 'MIS Administrator',
        'dpo@example.com' => 'DPO',
        'auditor@example.com' => 'Auditor',
    ];

    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        DB::transaction(function (): void {
            foreach (self::ACCOUNTS as $email => $name) {
                if (User::query()->where('email', $email)->exists()) {
                    continue;
                }

                $role = Role::firstOrCreate(['name' => $name], ['guard_name' => 'web']);

                User::factory()->create([
                    'name' => "Demo {$name}",
                    'email' => $email,
                    'role_id' => $role->id,
                    'password' => 'password',
                ]);
            }
        });
    }
}

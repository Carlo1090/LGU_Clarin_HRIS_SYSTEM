<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Notifications\HrisAccountSetup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BootstrapHrmoHead extends Command
{
    protected $signature = 'hris:bootstrap-head {email} {--name=} {--approval=} {--operator=} {--execute}';

    protected $description = 'Preflight or provision the first approved HRMO Head account';

    public function handle(): int
    {
        $validator = Validator::make([
            'email' => Str::lower($this->argument('email')),
            'name' => $this->option('name'),
            'approval' => $this->option('approval'),
            'operator' => $this->option('operator'),
        ], [
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'approval' => ['required', 'string', 'max:100'],
            'operator' => ['required', 'string', 'max:100'],
        ]);

        try {
            $input = $validator->validate();
            DB::transaction(function () use ($input): void {
                $role = Role::query()->where('name', 'HRMO Head')->where('guard_name', 'web')->lockForUpdate()->first();
                if (! $role || User::query()->where('role_id', $role->id)->where('requires_mfa', true)->exists()
                    || User::query()->where('email', $input['email'])->exists()) {
                    throw ValidationException::withMessages(['email' => 'Bootstrap requires a configured HRMO Head role, a new email, and no previously provisioned head.']);
                }

                if (! $this->option('execute')) {
                    return;
                }

                $user = new User([
                    'name' => $input['name'],
                    'email' => $input['email'],
                    'password' => Str::random(64),
                ]);
                $user->forceFill(['role_id' => $role->id, 'requires_mfa' => true])->save();

                AuditLog::create([
                    'action' => 'account.hrmo_head_bootstrapped',
                    'model_type' => User::class,
                    'model_id' => $user->id,
                    'metadata' => json_encode(['approval_reference' => $input['approval'], 'operator' => $input['operator']], JSON_THROW_ON_ERROR),
                ]);
            });
        } catch (ValidationException $error) {
            $this->error($error->validator->errors()->first());

            return self::FAILURE;
        }

        if (! $this->option('execute')) {
            $this->info('Preflight passed. Execution requires --execute after approval.');

            return self::SUCCESS;
        }

        $status = HrisAccountSetup::sendTo($input['email']);
        if ($status !== Password::RESET_LINK_SENT) {
            $this->error('Account recorded. The account holder can request a new setup link from Forgot password.');

            return self::FAILURE;
        }

        $this->info('Account setup email sent.');

        return self::SUCCESS;
    }
}

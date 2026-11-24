<?php

use App\Attendance\Services\PunchClassifier;

/**
 * @param array<string,int|string|bool> $override
 * @return array{am_in:string,am_out:string,pm_in:string,pm_out:string,grace_minutes:int,crosses_midnight:bool}
 */
function schedule(array $override = []): array
{
    return [
        'am_in' => (string) ($override['am_in'] ?? '08:00'),
        'am_out' => (string) ($override['am_out'] ?? '12:00'),
        'pm_in' => (string) ($override['pm_in'] ?? '13:00'),
        'pm_out' => (string) ($override['pm_out'] ?? '17:00'),
        'grace_minutes' => (int) ($override['grace_minutes'] ?? 0),
        'crosses_midnight' => (bool) ($override['crosses_midnight'] ?? false),
    ];
}

test('classifies a normal day using actual clock time', function () {
    $result = (new PunchClassifier())->classify([
        ['punched_at' => '2026-11-24 07:55:00', 'raw_status' => 3],
        ['punched_at' => '2026-11-24 12:01:00', 'raw_status' => 0],
        ['punched_at' => '2026-11-24 13:02:00', 'raw_status' => 1],
        ['punched_at' => '2026-11-24 17:05:00', 'raw_status' => 2],
    ], schedule(), '2026-11-24');

    expect($result['status'])->toBe('complete')
        ->and($result['am_in_at'])->toBe('2026-11-24 07:55:00')
        ->and($result['am_out_at'])->toBe('2026-11-24 12:01:00')
        ->and($result['pm_in_at'])->toBe('2026-11-24 13:02:00')
        ->and($result['pm_out_at'])->toBe('2026-11-24 17:05:00')
        ->and($result['late_minutes_am'])->toBe(0)
        ->and($result['late_minutes_pm'])->toBe(2);
});

test('grace removes late minutes and duplicate taps merge', function () {
    $result = (new PunchClassifier())->classify([
        ['punched_at' => '2026-11-24 08:05:00'],
        ['punched_at' => '2026-11-24 08:06:30'],
        ['punched_at' => '2026-11-24 12:00:00'],
        ['punched_at' => '2026-11-24 13:00:00'],
        ['punched_at' => '2026-11-24 17:00:00'],
    ], schedule(['grace_minutes' => 10]), '2026-11-24');

    expect($result['late_minutes_am'])->toBe(0)
        ->and($result['flags'])->toHaveKey('duplicate_merged')
        ->and(count($result['punches']))->toBe(4);
});

test('missing slots stay null and raw status never changes slot assignment', function () {
    $result = (new PunchClassifier())->classify([
        ['punched_at' => '2026-11-24 08:00:00', 'raw_status' => 3],
        ['punched_at' => '2026-11-24 13:00:00', 'raw_status' => 0],
        ['punched_at' => '2026-11-24 17:00:00', 'raw_status' => 3],
    ], schedule(), '2026-11-24');

    expect($result['status'])->toBe('incomplete')
        ->and($result['am_out_at'])->toBeNull()
        ->and($result['flags'])->toHaveKey('missing_am_out')
        ->and($result['am_in_at'])->toBe('2026-11-24 08:00:00')
        ->and($result['pm_in_at'])->toBe('2026-11-24 13:00:00');
});

test('a scheduled day with no punches is absent', function () {
    $result = (new PunchClassifier())->classify([], schedule(), '2026-11-24');

    expect($result['status'])->toBe('absent')
        ->and($result['am_in_at'])->toBeNull();
});

test('missing schedule returns no_schedule', function () {
    $result = (new PunchClassifier())->classify([
        ['punched_at' => '2026-11-24 08:00:00'],
    ], null, '2026-11-24');

    expect($result['status'])->toBe('no_schedule')
        ->and($result['flags'])->toHaveKey('no_schedule');
});

test('cross midnight shift allows pm out punches on the next day', function () {
    $schedule = schedule([
        'am_in' => '22:00',
        'am_out' => '02:00',
        'pm_in' => '03:00',
        'pm_out' => '06:00',
        'crosses_midnight' => true,
    ]);

    $result = (new PunchClassifier())->classify([
        ['punched_at' => '2026-11-24 21:58:00'],
        ['punched_at' => '2026-11-25 02:01:00'],
        ['punched_at' => '2026-11-25 03:02:00'],
        ['punched_at' => '2026-11-25 05:55:00'],
    ], $schedule, '2026-11-24');

    expect($result['status'])->toBe('complete')
        ->and($result['am_in_at'])->toBe('2026-11-24 21:58:00')
        ->and($result['pm_out_at'])->toBe('2026-11-25 05:55:00');
});

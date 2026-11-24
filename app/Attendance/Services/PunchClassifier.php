<?php

namespace App\Attendance\Services;

use Illuminate\Support\Carbon;

class PunchClassifier
{
    public function __construct(
        private int $duplicateWindowSeconds = 120,
        private int $amInHoursBefore = 4,
        private int $pmOutHoursAfter = 6,
    ) {}

    /**
     * @param list<array{punched_at:string, raw_status?:int|null}> $rawPunches
     * @param array{am_in:string, am_out:string, pm_in:string, pm_out:string, grace_minutes?:int, crosses_midnight?:bool, schedule_source?:string, schedule_id?:int}|null $schedule
     * @return array{
     *     am_in_at:string|null,
     *     am_out_at:string|null,
     *     pm_in_at:string|null,
     *     pm_out_at:string|null,
     *     late_minutes_am:int,
     *     late_minutes_pm:int,
     *     undertime_minutes:int,
     *     status:'complete'|'incomplete'|'absent'|'no_schedule',
     *     flags:array<string,mixed>,
     *     slots:array<string,string|null>,
     *     punches:list<array{punched_at:string,slot:string|null,duplicate_merged:bool}>
     * }
     */
    public function classify(array $rawPunches, ?array $schedule, string $workDate): array
    {
        if ($schedule === null) {
            return [
                'am_in_at' => null,
                'am_out_at' => null,
                'pm_in_at' => null,
                'pm_out_at' => null,
                'late_minutes_am' => 0,
                'late_minutes_pm' => 0,
                'undertime_minutes' => 0,
                'status' => 'no_schedule',
                'flags' => ['no_schedule' => true],
                'slots' => [
                    'am_in' => null,
                    'am_out' => null,
                    'pm_in' => null,
                    'pm_out' => null,
                ],
                'punches' => array_map(
                    fn (array $punch): array => [
                        'punched_at' => (string) $punch['punched_at'],
                        'slot' => null,
                        'duplicate_merged' => false,
                    ],
                    $rawPunches,
                ),
            ];
        }

        $punches = $rawPunches;
        usort($punches, fn (array $first, array $second): int =>
            strcmp((string) $first['punched_at'], (string) $second['punched_at']));

        $windowSeconds = max(0, $this->duplicateWindowSeconds);
        /** @var list<array{punched_at:string,slot:string|null,duplicate_merged:bool}> $merged */
        $merged = [];
        $flags = [];
        foreach ($punches as $punch) {
            $time = Carbon::parse($punch['punched_at']);
            /** @var array{punched_at:string,slot:string|null,duplicate_merged:bool}|null $previous */
            $previous = $merged === [] ? null : end($merged);
            if ($previous !== null) {
                $previousTime = Carbon::parse($previous['punched_at']);
                if (abs($time->getTimestamp() - $previousTime->getTimestamp()) <= $windowSeconds) {
                    $keeperIndex = count($merged) - 1;
                    $merged[$keeperIndex] = [
                        'punched_at' => (string) $merged[$keeperIndex]['punched_at'],
                        'slot' => null,
                        'duplicate_merged' => true,
                    ];
                    $flags['duplicate_merged'] = true;
                    continue;
                }
            }
            $merged[] = [
                'punched_at' => $time->format('Y-m-d H:i:s'),
                'slot' => null,
                'duplicate_merged' => false,
            ];
        }

        $boundaries = $this->boundaries($workDate, $schedule);
        $windows = [
            'am_in' => [$boundaries['am_in_window_start'], $boundaries['am_mid']],
            'am_out' => [$boundaries['am_mid'], $boundaries['pm_mid_lunch']],
            'pm_in' => [$boundaries['pm_mid_lunch'], $boundaries['pm_mid_out']],
            'pm_out' => [$boundaries['pm_mid_out'], $boundaries['pm_out_window_end']],
        ];

        /** @var array<string,string|null> $assigned */
        $assigned = [
            'am_in' => null,
            'am_out' => null,
            'pm_in' => null,
            'pm_out' => null,
        ];
        foreach ($merged as $index => $punch) {
            $punchedAt = (string) $punch['punched_at'];
            foreach ($windows as $slot => [$start, $end]) {
                $time = Carbon::parse($punchedAt);
                if ($time->gte($start) && ($slot === 'pm_out' ? $time->lte($end) : $time->lt($end))) {
                    $merged[$index] = [
                        'punched_at' => $punchedAt,
                        'slot' => $slot,
                        'duplicate_merged' => (bool) $punch['duplicate_merged'],
                    ];
                    if ($slot === 'am_in' || $slot === 'pm_in') {
                        $assigned[$slot] ??= $punchedAt;
                    } else {
                        $assigned[$slot] = $punchedAt;
                    }
                    break;
                }
            }
        }
        foreach (['am_in', 'am_out', 'pm_in', 'pm_out'] as $slot) {
            if ($assigned[$slot] === null) {
                $flags['missing_'.$slot] = true;
            }
        }

        $grace = (int) ($schedule['grace_minutes'] ?? 0);
        $lateAm = $assigned['am_in'] === null
            ? 0
            : max(0, (int) floor((Carbon::parse($assigned['am_in'])->getTimestamp() - $boundaries['am_in_scheduled']->getTimestamp()) / 60) - $grace);
        $latePm = $assigned['pm_in'] === null
            ? 0
            : max(0, (int) floor((Carbon::parse($assigned['pm_in'])->getTimestamp() - $boundaries['pm_in_scheduled']->getTimestamp()) / 60) - $grace);

        $undertime = 0;
        if ($assigned['am_out'] !== null) {
            $amOut = Carbon::parse($assigned['am_out']);
            $undertime += max(0, (int) floor(($boundaries['am_out_scheduled']->getTimestamp() - $amOut->getTimestamp()) / 60));
        }
        if ($assigned['pm_out'] !== null) {
            $pmOut = Carbon::parse($assigned['pm_out']);
            $undertime += max(0, (int) floor(($boundaries['pm_out_scheduled']->getTimestamp() - $pmOut->getTimestamp()) / 60));
        }

        $slotCount = count(array_filter($assigned));
        $status = $slotCount === 4
            ? 'complete'
            : ($slotCount === 0 ? 'absent' : 'incomplete');
        if ($rawPunches !== [] && $slotCount === 0) {
            $status = 'incomplete';
        }

        return [
            'am_in_at' => $assigned['am_in'],
            'am_out_at' => $assigned['am_out'],
            'pm_in_at' => $assigned['pm_in'],
            'pm_out_at' => $assigned['pm_out'],
            'late_minutes_am' => $lateAm,
            'late_minutes_pm' => $latePm,
            'undertime_minutes' => $undertime,
            'status' => $status,
            'flags' => $flags,
            'slots' => $assigned,
            'punches' => $merged,
        ];
    }

    /**
     * @param array{am_in:string, am_out:string, pm_in:string, pm_out:string, crosses_midnight?:bool} $schedule
     * @return array<string,Carbon>
     */
    public function boundaries(string $workDate, array $schedule): array
    {
        $base = Carbon::parse($workDate.' 00:00:00');
        $crosses = (bool) ($schedule['crosses_midnight'] ?? false);

        $amIn = $this->at($base, $schedule['am_in']);
        $amOut = $this->at($base, $schedule['am_out']);
        $pmIn = $this->at($base, $schedule['pm_in']);
        $pmOut = $this->at($base, $schedule['pm_out']);

        if ($crosses) {
            if ($amOut->lt($amIn)) {
                $amOut->addDay();
            }
            if ($pmIn->lt($amOut)) {
                $pmIn->addDay();
            }
            if ($pmOut->lt($pmIn)) {
                $pmOut->addDay();
            }
        }

        $amMid = $this->midpoint($amIn, $amOut);
        $pmLunchMid = $this->midpoint($amOut, $pmIn);
        $pmOutMid = $this->midpoint($pmIn, $pmOut);
        return [
            'am_in_window_start' => $amIn->copy()->subHours($this->amInHoursBefore),
            'am_mid' => $amMid,
            'pm_mid_lunch' => $pmLunchMid,
            'pm_mid_out' => $pmOutMid,
            'pm_out_window_end' => $pmOut->copy()->addHours($this->pmOutHoursAfter),
            'am_in_scheduled' => $amIn,
            'am_out_scheduled' => $amOut,
            'pm_in_scheduled' => $pmIn,
            'pm_out_scheduled' => $pmOut,
        ];
    }

    private function at(Carbon $base, string $time): Carbon
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));
        return $base->copy()->setTime($hour, $minute, 0);
    }

    private function midpoint(Carbon $first, Carbon $second): Carbon
    {
        return $first->copy()->addSeconds((int) round(abs($second->getTimestamp() - $first->getTimestamp()) / 2));
    }
}

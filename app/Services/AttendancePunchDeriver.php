<?php

namespace App\Services;

use App\Models\AttendancePunch;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Builder;

class AttendancePunchDeriver
{
    /**
     * @param  list<array{machine_user_id: int, punched_at: string}>  $rows
     */
    public function refreshRows(array $rows): void
    {
        $window = max(0, (int) config('attendance.duplicate_window_seconds', 120));
        $groups = [];
        foreach ($rows as $row) {
            $key = $row['machine_user_id'].'|'.substr($row['punched_at'], 0, 10);
            $groups[$key]['machine_user_id'] = $row['machine_user_id'];
            $groups[$key]['min'] = isset($groups[$key]['min']) ? min($groups[$key]['min'], $row['punched_at']) : $row['punched_at'];
            $groups[$key]['max'] = isset($groups[$key]['max']) ? max($groups[$key]['max'], $row['punched_at']) : $row['punched_at'];
        }

        foreach (array_chunk(array_values($groups), 150) as $groupChunk) {
            $punches = AttendancePunch::query()->where(function (Builder $query) use ($groupChunk, $window): void {
                foreach ($groupChunk as $group) {
                    $from = $this->adjust($group['min'], -$window);
                    $to = $this->adjust($group['max'], $window);
                    $query->orWhere(function (Builder $range) use ($group, $from, $to): void {
                        $range->where('machine_user_id', $group['machine_user_id'])
                            ->whereBetween('punched_at', [$from, $to]);
                    });
                }
            })->orderBy('punched_at')->orderBy('id')->get([
                'id', 'machine_user_id', 'punched_at', 'punch_state', 'verify_mode',
            ]);

            $byDay = $punches->groupBy(fn (AttendancePunch $punch): string => $punch->machine_user_id.'|'.substr($punch->punched_at, 0, 10));
            foreach ($byDay as $dayPunches) {
                $previousAt = null;
                $updates = [];
                foreach ($dayPunches as $punch) {
                    $isDuplicate = $previousAt !== null && $this->secondsBetween($previousAt, $punch->punched_at) <= $window;
                    $previousAt = $punch->punched_at;
                    $slot = self::slotForState($punch->punch_state);
                    $flags = $this->flagsFor($punch->punch_state, $punch->verify_mode, $punch->punched_at);
                    $key = json_encode([$slot, $isDuplicate, $flags], JSON_THROW_ON_ERROR);
                    $updates[$key] ??= [
                        'slot' => $slot,
                        'duplicate' => $isDuplicate,
                        'flags' => json_encode($flags, JSON_THROW_ON_ERROR),
                        'ids' => [],
                    ];
                    $updates[$key]['ids'][] = $punch->id;
                }

                foreach ($updates as $update) {
                    AttendancePunch::query()->whereIn('id', $update['ids'])->update([
                        'derived_slot' => $update['slot'],
                        'is_duplicate' => $update['duplicate'],
                        'flags' => $update['flags'],
                    ]);
                }
            }
        }
    }

    public function expectedSlot(string $punchedAt): ?string
    {
        $time = substr($punchedAt, 11, 5);
        $windows = config('attendance.windows', []);
        if ($time < ($windows['am_in_end'] ?? '12:00')) {
            return 'AM_IN';
        }
        if ($time < ($windows['lunch_out_end'] ?? '12:45')) {
            return 'LUNCH_OUT';
        }
        if ($time < ($windows['pm_in_end'] ?? '16:00')) {
            return 'PM_IN';
        }

        return 'PM_OUT';
    }

    public static function slotForState(?int $state): ?string
    {
        return match ($state) {
            0 => 'AM_IN',
            1 => 'PM_OUT',
            2 => 'LUNCH_OUT',
            3 => 'PM_IN',
            4 => 'OT_IN',
            5 => 'OT_OUT',
            default => null,
        };
    }

    /** @return array<string, bool|string> */
    public function flagsFor(?int $state, ?int $verifyMode, string $punchedAt): array
    {
        $flags = [];
        $slot = self::slotForState($state);
        $expected = $this->expectedSlot($punchedAt);
        if ($slot === null) {
            $flags['unknown_punch_state'] = true;
        }
        if ($expected !== null && $slot !== $expected) {
            $flags['suspected_wrong_button'] = true;
            $flags['suggested_slot'] = $expected;
        }
        if ($verifyMode === 0) {
            $flags['pin_verify'] = true;
        }

        return $flags;
    }

    private function adjust(string $timestamp, int $seconds): string
    {
        return $this->dateTime($timestamp)
            ->modify(sprintf('%+d seconds', $seconds))->format('Y-m-d H:i:s');
    }

    private function secondsBetween(string $first, string $second): int
    {
        return abs(
            $this->dateTime($second)->getTimestamp()
            - $this->dateTime($first)->getTimestamp(),
        );
    }

    private function dateTime(string $timestamp): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i:s',
            $timestamp,
            new DateTimeZone(config('attendance.timezone', 'Asia/Manila')),
        );
        if (! $date) {
            throw new \InvalidArgumentException('Cannot interpret the stored local punch time.');
        }

        return $date;
    }
}

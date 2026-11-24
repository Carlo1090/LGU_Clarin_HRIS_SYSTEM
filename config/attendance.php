<?php

return [
    'minimum_verification_punches' => (int) env('ATTENDANCE_MINIMUM_VERIFICATION_PUNCHES', 50),
    'timezone' => env('ATTENDANCE_TIMEZONE', 'Asia/Manila'),
    'duplicate_window_seconds' => (int) env('ATTENDANCE_DUPLICATE_WINDOW_SECONDS', 120),
    'classifier' => [
        'am_in_hours_before' => (int) env('ATTENDANCE_AM_IN_HOURS_BEFORE', 4),
        'pm_out_hours_after' => (int) env('ATTENDANCE_PM_OUT_HOURS_AFTER', 6),
    ],
    'windows' => [
        'am_in_end' => env('ATTENDANCE_AM_IN_END', '12:00'),
        'lunch_out_end' => env('ATTENDANCE_LUNCH_OUT_END', '12:45'),
        'pm_in_end' => env('ATTENDANCE_PM_IN_END', '16:00'),
    ],
];

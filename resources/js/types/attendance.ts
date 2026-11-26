export type EmployeeBiometricLink = {
    id: number | null;
    locked: boolean;
    status?: 'pending' | 'verified';
    note?: string | null;
};

export type ImportBatchSummary = {
    id: number;
    file_name: string;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    error_message?: string | null;
    total_lines: number;
    inserted_count: number;
    duplicate_count: number;
    recompute_summary: {
        employees_affected: number;
        unlinked_machine_ids: number[];
    } | null;
    invalid_count: number;
    invalid_lines: { line: number; reason: string }[];
};

export type AttendanceImportPreview = {
    total_rows: number;
    date_range: { from: string | null; to: string | null };
    distinct_machine_ids: number;
    machine_ids: number[];
    unlinked_ids: number[];
    would_skip: number;
    invalid_count: number;
    errors: { line: number; reason: string }[];
};

export type BiometricPunchGroup = {
    avatar?: string | null;
    biometric_id: number;
    employee_number: string | null;
    first_name: string | null;
    last_name: string | null;
    multiple_owners: boolean;
    unlinked_count: number;
    punch_count: number;
    first_punch: string;
    last_punch: string;
};

export type DailyPunch = {
    id: number;
    punched_at: string;
    punch_state: number | null;
    verify_mode: number | null;
    verify_label: string;
    device_id: number | null;
    work_code: number | null;
    derived_slot: string | null;
    is_duplicate: boolean;
    flags: Record<string, boolean | string>;
};

export type DailyPunchSlot = {
    id: number;
    time: string;
    is_duplicate: boolean;
    primary: boolean;
    slot: string;
};

export type DailyPunchRow = {
    key: string;
    machine_user_id: number;
    date: string;
    employee_id: number | null;
    employee_name: string | null;
    employee_number: string | null;
    department_name: string | null;
    slots: Record<string, DailyPunchSlot[]>;
    correction_times: Partial<Record<string, string>>;
    punches: DailyPunch[];
    flags: {
        missing: boolean;
        missing_slots: string[];
        no_schedule: boolean;
        duplicate: boolean;
        pin_verify: boolean;
        unlinked: boolean;
    };
    flag_labels: string[];
    status: 'complete' | 'incomplete' | 'absent' | 'no_schedule' | null;
    late_minutes_am: number | null;
    late_minutes_pm: number | null;
    undertime_minutes: number | null;
    schedule_source: 'department' | 'override' | null;
    schedule_id: number | null;
};

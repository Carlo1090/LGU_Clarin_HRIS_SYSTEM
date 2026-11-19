export type EmployeeBiometricLink = {
    id: number | null;
    locked: boolean;
    status?: 'pending' | 'verified';
    note?: string | null;
};

export type ImportBatchSummary = {
    file_name: string;
    total_lines: number;
    inserted_count: number;
    duplicate_count: number;
    invalid_count: number;
    clock_offset_seconds: number;
    invalid_lines: { line: number; reason: string }[];
};

export type BiometricPunchGroup = {
    biometric_id: number;
    employee_number: string | null;
    first_name: string | null;
    last_name: string | null;
    punch_count: number;
    first_punch: string;
    last_punch: string;
};

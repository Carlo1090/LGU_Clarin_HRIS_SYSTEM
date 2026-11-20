import type { ReactNode } from 'react';
import {
    BriefcaseBusiness,
    Building2,
    CalendarDays,
    CheckCheck,
    CircleOff,
    ClipboardList,
    GitBranch,
    Layers,
    UsersRound,
    type LucideIcon,
} from 'lucide-react';
import BiometricIdField from '@/components/biometric-id-field';
import {
    WorkspaceOverview,
    type WorkspaceMetric,
} from '@/components/workspace-metrics';

export type Field = {
    name: string;
    label: string;
    type: string;
    required?: boolean;
    options?: { value: string | number; label: string }[];
};

export type HrisValue = string | number | boolean | null | undefined;

export type HrisRow = {
    id: number | string;
    canManage?: boolean;
    [key: string]: HrisValue;
};

type DirectoryLayout = {
    title: string;
    icon: LucideIcon;
    nameFields: string[];
    summaryFields: string[];
};

export const directoryLayouts: Record<string, DirectoryLayout | undefined> = {
    employees: {
        title: 'Employee directory',
        icon: UsersRound,
        nameFields: ['first_name', 'middle_name', 'last_name', 'suffix'],
        summaryFields: [
            'employee_number',
            'department_id',
            'position_id',
            'employment_status',
        ],
    },
    'leave-applications': {
        title: 'Leave applications',
        icon: CalendarDays,
        nameFields: ['employee_id'],
        summaryFields: ['leave_type_id', 'start_date', 'end_date', 'status'],
    },
    departments: {
        title: 'Office directory',
        icon: Building2,
        nameFields: ['name'],
        summaryFields: ['code', 'parent_department_id', 'description'],
    },
    positions: {
        title: 'Position directory',
        icon: BriefcaseBusiness,
        nameFields: ['title'],
        summaryFields: ['code', 'department_id', 'job_grade', 'is_active'],
    },
};

export function DirectoryOverview({
    resource,
    title,
    rows,
    statusSummary,
}: {
    resource: string;
    title: string;
    rows: HrisRow[];
    statusSummary: { status: string; total: number }[];
}) {
    let metrics: WorkspaceMetric[] = [];
    if (resource === 'employees') {
        metrics = [
            {
                label: 'Employees',
                count: statusSummary.reduce(
                    (sum, item) => sum + Number(item.total),
                    0,
                ),
                icon: UsersRound,
            },
            ...statusSummary.map(({ status, total }) => ({
                label: status
                    .replaceAll('_', ' ')
                    .replace(/^./, (c) => c.toUpperCase()),
                count: total,
                icon: BriefcaseBusiness,
            })),
        ];
    } else if (resource === 'positions') {
        const active = rows.filter((row) => Boolean(row.is_active)).length;
        metrics = [
            { label: 'Positions', count: rows.length, icon: BriefcaseBusiness },
            { label: 'Active', count: active, icon: CheckCheck },
            { label: 'Inactive', count: rows.length - active, icon: CircleOff },
            {
                label: 'Departments represented',
                count: new Set(
                    rows
                        .map((row) => row.department_id)
                        .filter((id) => id != null),
                ).size,
                icon: Building2,
            },
        ];
    } else if (resource === 'departments') {
        const childOffices = rows.filter(
            (row) => row.parent_department_id != null,
        ).length;
        metrics = [
            { label: 'Offices', count: rows.length, icon: Building2 },
            {
                label: 'Top-level offices',
                count: rows.length - childOffices,
                icon: Layers,
            },
            {
                label: 'With a parent office',
                count: childOffices,
                icon: GitBranch,
            },
        ];
    } else if (resource === 'leave-applications') {
        metrics = [
            { label: 'Applications', count: rows.length, icon: ClipboardList },
            {
                label: 'In review',
                count: rows.filter((row) =>
                    ['pending', 'department_reviewed', 'hr_validated'].includes(
                        String(row.status),
                    ),
                ).length,
                icon: CalendarDays,
            },
            {
                label: 'Approved',
                count: rows.filter((row) => row.status === 'approved').length,
                icon: CheckCheck,
            },
            {
                label: 'Rejected',
                count: rows.filter((row) => row.status === 'rejected').length,
                icon: CircleOff,
            },
        ];
    }

    return (
        <WorkspaceOverview
            title={
                resource === 'employees'
                    ? 'Workforce status'
                    : title + ' overview'
            }
            description={
                resource === 'employees'
                    ? 'Employment status across your workforce.'
                    : resource === 'departments'
                      ? 'Office structure across the municipal directory.'
                      : 'Counts for the records on this page.'
            }
            metrics={metrics}
        />
    );
}

export function ResourceDirectory({
    layout,
    rows,
    fields,
    canLinkBiometricIds,
    renderValue,
    renderActions,
}: {
    layout: DirectoryLayout;
    rows: HrisRow[];
    fields: Field[];
    canLinkBiometricIds: boolean;
    renderValue: (field: Field, value: HrisValue) => HrisValue;
    renderActions: (row: HrisRow) => ReactNode;
}) {
    const summaryFields = fields.filter((field) =>
        layout.summaryFields.includes(field.name),
    );
    const detailFields = fields.filter(
        (field) =>
            !layout.nameFields.includes(field.name) &&
            !layout.summaryFields.includes(field.name),
    );
    const Icon = layout.icon;
    function renderField(field: Field, row: HrisRow) {
        return (
            <div key={field.name} className="min-w-0">
                <dt className="text-xs text-muted-foreground">{field.label}</dt>
                <dd className="mt-1 break-words">
                    {renderValue(field, row[field.name])}
                </dd>
            </div>
        );
    }
    return (
        <ul className="divide-y border-t">
            {rows.map((row) => {
                const nameParts = layout.nameFields.flatMap((name) => {
                    const field = fields.find((item) => item.name === name);
                    return field && row[name]
                        ? [renderValue(field, row[name])]
                        : [];
                });
                return (
                    <li key={row.id} className="space-y-3 px-4 py-5 sm:px-6">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div className="flex min-w-0 items-center gap-3">
                                <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-accent text-accent-foreground">
                                    <Icon
                                        aria-hidden="true"
                                        className="size-5"
                                    />
                                </span>
                                <div className="min-w-0">
                                    <h3 className="font-semibold break-words">
                                        {nameParts.join(' ')}
                                    </h3>
                                    <p className="text-xs text-muted-foreground">
                                        Record ID {row.id}
                                    </p>
                                </div>
                            </div>
                            <div className="flex flex-wrap gap-1">
                                {renderActions(row)}
                            </div>
                        </div>
                        <dl className="grid grid-cols-2 gap-3 text-sm lg:grid-cols-4">
                            {summaryFields.map((field) =>
                                renderField(field, row),
                            )}
                        </dl>
                        {(detailFields.length > 0 || canLinkBiometricIds) && (
                            <details className="rounded-xl border bg-muted/30 px-3 py-2 text-sm">
                                <summary className="cursor-pointer font-medium">
                                    {canLinkBiometricIds
                                        ? 'Record details and biometric ID'
                                        : 'Record details'}
                                </summary>
                                <dl className="my-3 grid grid-cols-2 gap-3 lg:grid-cols-4">
                                    {detailFields.map((field) =>
                                        renderField(field, row),
                                    )}
                                </dl>
                                {canLinkBiometricIds &&
                                    typeof row.id === 'number' && (
                                        <BiometricIdField
                                            key={`${row.id}-${row.biometric_id ?? ''}-${row.biometric_locked === true}`}
                                            employeeId={row.id}
                                            employeeNumber={String(
                                                row.employee_number,
                                            )}
                                            link={{
                                                id:
                                                    typeof row.biometric_id ===
                                                    'number'
                                                        ? row.biometric_id
                                                        : null,
                                                locked:
                                                    row.biometric_locked ===
                                                    true,
                                                status:
                                                    row.biometric_status ===
                                                    'verified'
                                                        ? 'verified'
                                                        : 'pending',
                                                note:
                                                    typeof row.biometric_note ===
                                                    'string'
                                                        ? row.biometric_note
                                                        : null,
                                            }}
                                        />
                                    )}
                            </details>
                        )}
                    </li>
                );
            })}
            {rows.length === 0 && (
                <li className="p-6 text-sm text-muted-foreground">
                    No records found.
                </li>
            )}
        </ul>
    );
}

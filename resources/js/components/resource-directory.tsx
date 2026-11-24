import PersonAvatar from '@/components/person-avatar';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableHeader,
    TableHead,
    TableBody,
    TableCell,
    TableRow,
} from '@/components/ui/table';
import type { ReactNode } from 'react';
import {
    BriefcaseBusiness,
    Building2,
    CalendarDays,
    CheckCheck,
    CircleOff,
    ClipboardList,
    GitBranch,
    MoreHorizontal,
    Layers,
    UsersRound,
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
    account?: boolean;
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
    nameLabel: string;
    nameFields: string[];
    summaryFields: string[];
};

export const directoryLayouts: Record<string, DirectoryLayout | undefined> = {
    employees: {
        title: 'Employee directory',
        nameLabel: 'Employee',
        nameFields: ['first_name', 'middle_name', 'last_name', 'suffix'],
        summaryFields: [
            'employee_number',
            'department_id',
            'position_id',
            'employment_status',
            'work_schedule',
        ],
    },
    'leave-applications': {
        title: 'Leave applications',
        nameLabel: 'Employee',
        nameFields: ['employee_id'],
        summaryFields: [
            'leave_type_id',
            'start_date',
            'end_date',
            'days_requested',
            'reason',
            'status',
        ],
    },
    departments: {
        title: 'Office directory',
        nameLabel: 'Department',
        nameFields: ['name'],
        summaryFields: ['code', 'parent_department_id', 'description', 'work_schedule'],
    },
    positions: {
        title: 'Position directory',
        nameLabel: 'Position',
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
            {
                label: 'Without schedule',
                count: rows.filter((row) => !row.work_schedule).length,
                icon: CalendarDays,
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
    renderActions?: (row: HrisRow) => ReactNode;
}) {
    const nameFields = layout.nameFields.flatMap((name) =>
        fields.filter((field) => field.name === name),
    );
    const summaryFields = layout.summaryFields.flatMap((name) =>
        fields.filter((field) => field.name === name),
    );
    const detailFields = fields.filter(
        (field) =>
            !layout.nameFields.includes(field.name) &&
            !layout.summaryFields.includes(field.name),
    );
    const columnCount =
        1 +
        summaryFields.length +
        Number(detailFields.length > 0) +
        Number(canLinkBiometricIds) +
        Number(Boolean(renderActions));

    return (
        <Table
            aria-labelledby="records-heading"
            className="workspace-record-table"
        >
            <TableHeader className="workspace-table-head">
                <TableRow>
                    <TableHead scope="col">{layout.nameLabel}</TableHead>
                    {summaryFields.map((field) => (
                        <TableHead key={field.name} scope="col">
                            {field.label}
                        </TableHead>
                    ))}
                    {detailFields.length > 0 && (
                        <TableHead scope="col">Record details</TableHead>
                    )}
                    {canLinkBiometricIds && (
                        <TableHead scope="col">Biometric ID</TableHead>
                    )}
                    {renderActions && (
                        <TableHead scope="col">Actions</TableHead>
                    )}
                </TableRow>
            </TableHeader>
            <TableBody>
                {rows.map((row) => {
                    const name = nameFields
                        .filter((field) => row[field.name])
                        .map((field) => renderValue(field, row[field.name]))
                        .join(' ');
                    return (
                        <TableRow key={row.id}>
                            <TableHead
                                scope="row"
                                className="min-w-40 whitespace-normal"
                            >
                                <span className="inline-flex items-center gap-2">
                                    {layout.nameLabel === 'Employee' && (
                                        <PersonAvatar
                                            name={name}
                                            src={
                                                typeof row.avatar === 'string'
                                                    ? row.avatar
                                                    : null
                                            }
                                        />
                                    )}
                                    {name}
                                </span>
                                <span className="block text-xs font-normal text-muted-foreground">
                                    Record ID {row.id}
                                </span>
                            </TableHead>
                            {summaryFields.map((field) => (
                                <TableCell
                                    key={field.name}
                                    className={
                                        field.type === 'textarea'
                                            ? 'max-w-sm min-w-48 break-words whitespace-normal'
                                            : undefined
                                    }
                                >
                                    {field.name === 'work_schedule' && !row.work_schedule ? (
                                        <Badge variant="secondary">No schedule</Badge>
                                    ) : renderValue(field, row[field.name])}
                                </TableCell>
                            ))}
                            {detailFields.length > 0 && (
                                <TableCell>
                                    <details>
                                        <summary
                                            aria-label={`View details for ${name}`}
                                            title="View details"
                                            className="inline-flex size-9 cursor-pointer list-none items-center justify-center rounded-md hover:bg-accent focus-visible:outline-2 [&::-webkit-details-marker]:hidden"
                                        >
                                            <MoreHorizontal
                                                aria-hidden="true"
                                                className="size-4"
                                            />
                                        </summary>
                                        <dl className="mt-2 space-y-2">
                                            {detailFields.map((field) => (
                                                <div key={field.name}>
                                                    <dt className="text-xs text-muted-foreground">
                                                        {field.label}
                                                    </dt>
                                                    <dd>
                                                        {renderValue(
                                                            field,
                                                            row[field.name],
                                                        )}
                                                    </dd>
                                                </div>
                                            ))}
                                        </dl>
                                    </details>
                                </TableCell>
                            )}
                            {canLinkBiometricIds && (
                                <TableCell>
                                    {typeof row.id === 'number' && (
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
                                </TableCell>
                            )}
                            {renderActions && (
                                <TableCell>{renderActions(row)}</TableCell>
                            )}
                        </TableRow>
                    );
                })}
                {rows.length === 0 && (
                    <TableRow>
                        <TableCell
                            colSpan={columnCount}
                            className="py-8 text-center text-muted-foreground"
                        >
                            No records found.
                        </TableCell>
                    </TableRow>
                )}
            </TableBody>
        </Table>
    );
}

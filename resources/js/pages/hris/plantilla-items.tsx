import { DirectorySearch } from '@/components/table-search';
import PersonAvatar from '@/components/person-avatar';
import { Head } from '@inertiajs/react';
import {
    Building2,
    ChevronRight,
    Files,
    UserCheck,
    UserRoundMinus,
} from 'lucide-react';
import { useState } from 'react';
import RecordPagination, {
    type RecordPageLink,
} from '@/components/record-pagination';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import WorkspaceHeader from '@/components/workspace-header';
import { WorkspaceOverview } from '@/components/workspace-metrics';

type Row = { id: number; [key: string]: string | number | null };
type Field = { name: string; label: string };
type Props = {
    title: string;
    fields: Field[];
    records: {
        data: Row[];
        links: RecordPageLink[];
        total: number;
        from: number | null;
        to: number | null;
    };
};

const compensationFields = new Set([
    'salary_grade',
    'step',
    'authorized_annual_salary',
    'actual_annual_salary',
]);
const employeeFields = new Set([
    'employee_number',
    'last_name',
    'first_name',
    'middle_name',
    'sex',
    'date_of_birth',
    'age',
    'tin',
    'gsis_bp_number',
    'pwd',
    'indigenous_people',
    'solo_parent_id_number',
]);
const appointmentFields = new Set([
    'appointment_status',
    'date_of_original_appointment',
    'date_of_last_promotion_appointment',
    'civil_service_eligibility',
    'comment_annotation',
]);

function groupFields(fields: Field[]) {
    const groups: Record<string, Field[]> = {
        'Position and office': [],
        Compensation: [],
        Appointment: [],
        Employee: [],
    };
    for (const field of fields) {
        if (compensationFields.has(field.name)) groups.Compensation.push(field);
        else if (appointmentFields.has(field.name))
            groups.Appointment.push(field);
        else if (employeeFields.has(field.name)) groups.Employee.push(field);
        else groups['Position and office'].push(field);
    }
    return Object.entries(groups).filter(([, items]) => items.length > 0);
}

function displayValue(name: string, value: string | number | null | undefined) {
    if (value === null || value === undefined || value === '') return '—';
    if (['authorized_annual_salary', 'actual_annual_salary'].includes(name))
        return Number(value).toFixed(2);
    if (
        [
            'date_of_birth',
            'date_of_original_appointment',
            'date_of_last_promotion_appointment',
        ].includes(name)
    )
        return String(value).slice(0, 10);
    return String(value);
}

function PlantillaRow({
    row,
    groups,
}: {
    row: Row;
    groups: ReturnType<typeof groupFields>;
}) {
    const [open, setOpen] = useState(false);
    const position = row.position_title || 'Untitled position';
    const item = row.item_number
        ? `Item ${row.item_number}`
        : `Record ${row.id}`;
    const department = row.department_name || row.department_code;
    const employee =
        [row.first_name, row.last_name].filter(Boolean).join(' ') ||
        String(row.employee_number ?? '');

    return (
        <Sheet open={open} onOpenChange={setOpen}>
            <TableRow
                className="cursor-pointer focus-within:bg-accent/40"
                onClick={() => setOpen(true)}
            >
                <TableHead
                    scope="row"
                    className="h-auto px-3 py-3 break-words whitespace-normal sm:px-5"
                >
                    <span className="block font-semibold">{position}</span>
                    <span className="mt-1 block text-xs font-normal text-muted-foreground">
                        {item}
                    </span>
                    {row.employee_number && (
                        <span className="mt-1 inline-flex items-center gap-2 text-xs font-normal">
                            <PersonAvatar
                                name={employee}
                                src={
                                    typeof row.avatar === 'string'
                                        ? row.avatar
                                        : null
                                }
                            />
                            {employee}
                        </span>
                    )}
                    <span className="mt-1 block text-xs font-normal text-muted-foreground sm:hidden">
                        {department}
                    </span>
                </TableHead>
                <TableCell className="hidden px-3 break-words whitespace-normal sm:table-cell">
                    {displayValue('department_name', department)}
                </TableCell>
                <TableCell className="hidden px-3 tabular-nums sm:table-cell">
                    {displayValue('salary_grade', row.salary_grade)}
                </TableCell>
                <TableCell className="px-2 text-right sm:px-4">
                    <SheetTrigger asChild>
                        <Button
                            variant="outline"
                            size="icon"
                            className="rounded-full"
                            aria-label={`View ${position}, ${item}`}
                        >
                            <ChevronRight aria-hidden="true" />
                        </Button>
                    </SheetTrigger>
                </TableCell>
            </TableRow>
            <SheetContent
                side="right"
                className="public-surface dashboard-popover w-full max-w-full gap-0 sm:w-[32rem] sm:max-w-[32rem]"
            >
                <SheetHeader className="shrink-0 border-b bg-accent/60 p-5 pr-12">
                    <SheetTitle className="line-clamp-2 text-xl break-words">
                        {position}
                    </SheetTitle>
                    <SheetDescription className="break-words">
                        {item}
                    </SheetDescription>
                </SheetHeader>
                <div className="min-h-0 flex-1 space-y-6 overflow-y-auto p-5">
                    {groups.map(([label, fields]) => (
                        <section key={label}>
                            <h3 className="mb-3 flex items-center gap-2 text-sm font-semibold">
                                {label === 'Employee' &&
                                    row.employee_number && (
                                        <PersonAvatar
                                            name={String(
                                                row.first_name ||
                                                    row.employee_number,
                                            )}
                                            src={
                                                typeof row.avatar === 'string'
                                                    ? row.avatar
                                                    : null
                                            }
                                        />
                                    )}
                                {label}
                            </h3>
                            <dl className="grid grid-cols-1 gap-x-5 gap-y-3 border-t pt-3 min-[420px]:grid-cols-2">
                                {fields.map((field) => (
                                    <div key={field.name} className="min-w-0">
                                        <dt className="text-xs text-muted-foreground">
                                            {field.label}
                                        </dt>
                                        <dd className="mt-1 text-sm break-words whitespace-pre-line">
                                            {displayValue(
                                                field.name,
                                                row[field.name],
                                            )}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        </section>
                    ))}
                </div>
            </SheetContent>
        </Sheet>
    );
}

export default function PlantillaItems({ title, fields, records }: Props) {
    const groups = groupFields([
        { name: 'department_name', label: 'Department' },
        { name: 'department_code', label: 'Department code' },
        ...fields,
        { name: 'employee_number', label: 'Employee number' },
    ]);
    const linked = records.data.filter(
        (row) => row.employee_number != null && row.employee_number !== '',
    ).length;
    const departments = new Set(
        records.data
            .map((row) => row.department_code || row.department_name)
            .filter(Boolean),
    );

    return (
        <main className="workspace-page max-w-7xl">
            <Head title={title} />
            <WorkspaceHeader
                title={title}
                description="Browse municipal positions and open an item to review its appointment and compensation."
            />
            <WorkspaceOverview
                title="Staffing overview"
                description="Counts for the Plantilla items on this page."
                metrics={[
                    { label: 'Items', count: records.data.length, icon: Files },
                    {
                        label: 'Departments',
                        count: departments.size,
                        icon: Building2,
                    },
                    {
                        label: 'Linked to an employee',
                        count: linked,
                        icon: UserCheck,
                    },
                    {
                        label: 'No employee link',
                        count: records.data.length - linked,
                        icon: UserRoundMinus,
                    },
                ]}
            />
            <section
                className="workspace-panel overflow-hidden"
                aria-labelledby="plantilla-records"
            >
                <div className="flex flex-wrap items-center justify-between gap-2 px-4 py-5 sm:px-5">
                    <h2
                        id="plantilla-records"
                        className="text-lg font-semibold tracking-tight"
                    >
                        Plantilla records
                    </h2>
                    <span className="text-xs text-muted-foreground">
                        {records.from && records.to
                            ? `${records.from}–${records.to} of ${records.total} items`
                            : `${records.total} items`}
                    </span>
                </div>
                <DirectorySearch
                    label="Search Plantilla records"
                    columns={[
                        { name: 'position', label: 'Position' },
                        { name: 'item', label: 'Item number' },
                        { name: 'department', label: 'Department' },
                        { name: 'grade', label: 'Salary grade' },
                        { name: 'employee', label: 'Employee' },
                    ]}
                />
                <Table
                    aria-labelledby="plantilla-records"
                    className="table-fixed"
                >
                    <TableHeader className="workspace-table-head">
                        <TableRow>
                            <TableHead scope="col" className="px-3 sm:px-5">
                                Position
                            </TableHead>
                            <TableHead
                                scope="col"
                                className="hidden w-1/3 px-3 sm:table-cell"
                            >
                                Department
                            </TableHead>
                            <TableHead
                                scope="col"
                                className="hidden w-24 px-3 whitespace-normal sm:table-cell"
                            >
                                Salary grade
                            </TableHead>
                            <TableHead scope="col" className="w-14 sm:w-20">
                                <span className="sr-only">Details</span>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {records.data.map((row) => (
                            <PlantillaRow
                                key={row.id}
                                row={row}
                                groups={groups}
                            />
                        ))}
                        {!records.data.length && (
                            <TableRow>
                                <TableCell
                                    colSpan={4}
                                    className="py-8 text-center whitespace-normal text-muted-foreground"
                                >
                                    No Plantilla records found.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
                <RecordPagination links={records.links} />
            </section>
        </main>
    );
}

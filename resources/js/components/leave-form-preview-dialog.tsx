import { useTableSearch } from '@/components/table-search';
import { Eye } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

export const leaveTypes = [
    [
        'Vacation Leave',
        'Sec. 51, Rule XVI, Omnibus Rules Implementing E.O. No. 292',
    ],
    [
        'Mandatory/Forced Leave',
        'Sec. 25, Rule XVI, Omnibus Rules Implementing E.O. No. 292',
    ],
    [
        'Sick Leave',
        'Sec. 43, Rule XVI, Omnibus Rules Implementing E.O. No. 292',
    ],
    ['Maternity Leave', 'R.A. No. 11210 / IRR issued by CSC, DOLE and SSS'],
    ['Paternity Leave', 'R.A. No. 8187 / CSC MC No. 71, s. 1998, as amended'],
    [
        'Special Privilege Leave',
        'Sec. 21, Rule XVI, Omnibus Rules Implementing E.O. No. 292',
    ],
    ['Solo Parent Leave', 'R.A. No. 8972 / CSC MC No. 8, s. 2004'],
    [
        'Study Leave',
        'Sec. 68, Rule XVI, Omnibus Rules Implementing E.O. No. 292',
    ],
    ['10-Day VAWC Leave', 'R.A. No. 9262 / CSC MC No. 15, s. 2005'],
    [
        'Rehabilitation Privilege',
        'Sec. 55, Rule XVI, Omnibus Rules Implementing E.O. No. 292',
    ],
    [
        'Special Leave Benefits for Women',
        'R.A. No. 9710 / CSC MC No. 25, s. 2010',
    ],
    ['Special Emergency (Calamity) Leave', 'CSC MC No. 2, s. 2012, as amended'],
    ['Adoption Leave', 'R.A. No. 8552'],
];

export const leaveReasons = [
    {
        title: 'In case of Vacation/Special Privilege Leave',
        options: [
            { label: 'Within the Philippines', detail: '' },
            { label: 'Abroad', detail: 'Destination' },
        ],
    },
    {
        title: 'In case of Sick Leave',
        options: [
            { label: 'In Hospital', detail: 'Illness' },
            { label: 'Out Patient', detail: 'Illness' },
        ],
    },
    {
        title: 'In case of Special Leave Benefits for Women',
        options: [{ label: 'Specify Illness', detail: 'Illness' }],
    },
    {
        title: 'In case of Study Leave',
        options: [
            { label: 'Completion of Master’s Degree', detail: '' },
            { label: 'BAR/Board Examination Review', detail: '' },
        ],
    },
    {
        title: 'Other purpose',
        options: [
            { label: 'Monetization of Leave Credits', detail: '' },
            { label: 'Terminal Leave', detail: '' },
            { label: 'Other reason', detail: 'Reason' },
        ],
    },
];

export type LeaveApplicant = {
    firstName: string;
    middleName: string | null;
    lastName: string;
    suffix?: string | null;
    department: string | null;
    position: string | null;
    salary?: string | null;
};

export type LeaveDraft = {
    applicant: LeaveApplicant;
    filingDate: string;
    leaveType: string;
    startDate: string;
    endDate: string;
    daysRequested: string;
    reason: string;
    reasonDetails: string;
    commutation: boolean;
};

function Choice({
    checked = false,
    children,
}: {
    checked?: boolean;
    children: ReactNode;
}) {
    return (
        <li className="flex items-start gap-1.5">
            <span
                aria-hidden="true"
                className="mt-0.5 inline-flex size-3 shrink-0 items-center justify-center border border-black text-[10px] leading-none"
            >
                {checked ? '✓' : ''}
            </span>
            <span>
                {checked && <span className="sr-only">Selected. </span>}
                {children}
            </span>
        </li>
    );
}

function FormLine({
    label,
    children,
}: {
    label: string;
    children?: ReactNode;
}) {
    return (
        <div className="flex items-end gap-1.5">
            <span className="shrink-0">{label}</span>
            <span className="min-h-4 flex-1 border-b border-black px-1 font-semibold">
                {children}
            </span>
        </div>
    );
}

function Signature({ children }: { children: ReactNode }) {
    return (
        <p className="mx-auto mt-6 w-3/4 border-t border-black pt-1 text-center">
            {children}
        </p>
    );
}

<<<<<<< HEAD:resources/js/components/leave-form-demo-dialog.tsx
const creditRows = [
    ['Total Earned', '18.75', '14.50'],
    ['Less this application', '3.00', '0.00'],
    ['Balance', '15.75', '14.50'],
];

function LeaveFormDemo() {
    const search = useTableSearch(
        creditRows,
        ['Credit', 'Vacation leave', 'Sick leave'],
        (row) => row,
        'Search sample leave credits',
    );
=======
function LeaveFormPreview({ draft }: { draft?: LeaveDraft }) {
    const selectedType = draft
        ? draft.leaveType.trim().toLowerCase()
        : 'vacation leave';
    const otherType =
        draft &&
        !leaveTypes.some(([name]) => name.toLowerCase() === selectedType)
            ? draft.leaveType
            : '';

>>>>>>> 2bcfedee3e691c4d958da8917ba2da6b47d6ffcd:resources/js/components/leave-form-preview-dialog.tsx
    return (
        <div className="overflow-x-auto print:overflow-visible">
            {search.toolbar}
            <article className="mx-auto max-w-3xl min-w-[34rem] border border-black bg-white p-4 font-[Arial,sans-serif] text-[11px] leading-snug text-black print:max-w-[185mm] print:min-w-0 print:border-0 print:p-0 print:text-[8pt] print:leading-tight print:[&_section]:p-1.5">
                <header className="grid grid-cols-[1fr_2.4fr_1fr] items-start gap-2">
                    <div className="italic">
                        <p>Civil Service Form No. 6</p>
                        <p>Revised 2020</p>
                        <p className="mt-1 font-bold not-italic">
                            {draft ? 'Draft preview' : 'Demo · Fictional data'}
                        </p>
                    </div>
                    <div className="flex items-center justify-center gap-2 text-center">
                        <img
                            src="/images/clarin_logo.png"
                            alt=""
                            className="size-12 shrink-0 object-contain"
                        />
                        <div>
                            <p>Republic of the Philippines</p>
                            <p className="font-bold">LGU - CLARIN</p>
                            <p>Pob. Norte, Clarin, Bohol</p>
                        </div>
                    </div>
                    <p className="min-h-11 border border-black p-1 text-center">
                        Stamp of Date of Receipt
                    </p>
                </header>
                <h3 className="my-2 text-center text-base font-bold">
                    APPLICATION FOR LEAVE
                </h3>

                <div className="border border-black">
                    <div className="grid grid-cols-[2fr_3fr] divide-x divide-black border-b border-black">
                        <div className="p-2">
                            <p>1. OFFICE/DEPARTMENT</p>
                            <p className="mt-1 font-semibold">
                                {draft
                                    ? draft.applicant.department
                                    : 'Sample Administrative Office'}
                            </p>
                        </div>
                        <div className="p-2">
                            <p>2. NAME</p>
                            <dl className="mt-1 grid grid-cols-3 gap-2 text-center">
                                {[
                                    [
                                        'Last',
                                        draft
                                            ? [
                                                  draft.applicant.lastName,
                                                  draft.applicant.suffix,
                                              ]
                                                  .filter(Boolean)
                                                  .join(' ')
                                            : 'Sample',
                                    ],
                                    [
                                        'First',
                                        draft
                                            ? draft.applicant.firstName
                                            : 'Jamie',
                                    ],
                                    [
                                        'Middle',
                                        draft
                                            ? draft.applicant.middleName
                                            : 'Demo',
                                    ],
                                ].map(([label, name]) => (
                                    <div
                                        key={label}
                                        className="flex flex-col-reverse"
                                    >
                                        <dt className="border-t border-black">
                                            ({label})
                                        </dt>
                                        <dd className="font-semibold">
                                            {name}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        </div>
                    </div>
                    <div className="grid grid-cols-[1.1fr_1.4fr_0.8fr] gap-2 p-2">
                        <FormLine label="3. DATE OF FILING">
                            {draft?.filingDate ?? 'November 16, 2026'}
                        </FormLine>
                        <FormLine label="4. POSITION">
                            {draft
                                ? draft.applicant.position
                                : 'Administrative Aide'}
                        </FormLine>
                        <FormLine label="5. SALARY">
                            {draft
                                ? draft.applicant.salary
                                    ? `₱${draft.applicant.salary}`
                                    : ''
                                : '₱20,000.00'}
                        </FormLine>
                    </div>

                    <h4 className="border-y border-black px-2 py-1 text-center font-bold">
                        6. DETAILS OF APPLICATION
                    </h4>
                    <div className="grid grid-cols-2 divide-x divide-black">
                        <section className="space-y-1.5 p-2">
                            <h5 className="font-bold">
                                6.A TYPE OF LEAVE TO BE AVAILED OF
                            </h5>
                            <ul className="space-y-1">
                                {leaveTypes.map(([name, reference]) => (
                                    <Choice
                                        key={name}
                                        checked={
                                            name.toLowerCase() === selectedType
                                        }
                                    >
                                        {name}{' '}
                                        <small className="text-[9px] leading-tight">
                                            ({reference})
                                        </small>
                                    </Choice>
                                ))}
                            </ul>
                            <FormLine label="Others">{otherType}</FormLine>
                        </section>
                        <section className="space-y-2 p-2">
                            <h5 className="font-bold">6.B DETAILS OF LEAVE</h5>
                            {leaveReasons.map(({ title, options }) => (
                                <div key={title}>
                                    <p className="italic">{title}</p>
                                    <ul className="mt-0.5 space-y-0.5">
                                        {options
                                            .filter(
                                                ({ label }) =>
                                                    label !== 'Other reason' ||
                                                    draft?.reason === label,
                                            )
                                            .map(({ label, detail }) => (
                                                <Choice
                                                    key={label}
                                                    checked={
                                                        draft
                                                            ? draft.reason ===
                                                              label
                                                            : label ===
                                                              'Within the Philippines'
                                                    }
                                                >
                                                    {label}
                                                    {detail &&
                                                        ' (Specify ' +
                                                            detail.toLowerCase() +
                                                            ')'}
                                                    {draft?.reason === label &&
                                                        draft.reasonDetails && (
                                                            <strong className="ml-1 wrap-anywhere whitespace-pre-wrap">
                                                                {
                                                                    draft.reasonDetails
                                                                }
                                                            </strong>
                                                        )}
                                                    {!draft &&
                                                        label ===
                                                            'Within the Philippines' && (
                                                            <strong>
                                                                {' '}
                                                                Cebu City
                                                            </strong>
                                                        )}
                                                </Choice>
                                            ))}
                                    </ul>
                                </div>
                            ))}
                        </section>
                    </div>
                    <div className="grid grid-cols-2 divide-x divide-black border-t border-black">
                        <section className="space-y-2 p-2">
                            <h5 className="font-bold">
                                6.C NUMBER OF WORKING DAYS APPLIED FOR
                            </h5>
                            <p className="border-b border-black px-1 font-semibold">
                                {draft ? draft.daysRequested : '3 days'}
                            </p>
                            <FormLine label="INCLUSIVE DATES">
                                {draft
                                    ? `${draft.startDate || '—'} – ${draft.endDate || '—'}`
                                    : 'November 23–25, 2026'}
                            </FormLine>
                        </section>
                        <section className="p-2">
                            <h5 className="mb-1 font-bold">6.D COMMUTATION</h5>
                            <ul className="space-y-1">
                                <Choice
                                    checked={draft ? !draft.commutation : true}
                                >
                                    Not Requested
                                </Choice>
                                <Choice checked={draft?.commutation}>
                                    Requested
                                </Choice>
                            </ul>
                            <Signature>(Signature of Applicant)</Signature>
                        </section>
                    </div>

                    <h4 className="border-y border-black px-2 py-1 text-center font-bold">
                        7. DETAILS OF ACTION ON APPLICATION
                    </h4>
                    <div className="grid grid-cols-2 divide-x divide-black">
                        <section className="space-y-1.5 p-2">
                            <h5 className="font-bold">
                                7.A CERTIFICATION OF LEAVE CREDITS
                            </h5>
                            <FormLine label="As of">
                                {!draft && 'November 16, 2026'}
                            </FormLine>
                            <table
                                aria-label="Sample leave credits"
                                className="w-full border-collapse [&_td]:border [&_td]:border-black [&_td]:p-1 [&_th]:border [&_th]:border-black [&_th]:p-1"
                            >
                                <thead>
                                    <tr>
                                        <td />
                                        <th scope="col">Vacation Leave</th>
                                        <th scope="col">Sick Leave</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {creditRows.map(
                                        ([label, vacation, sick]) => (
                                            <tr
                                                key={label}
                                                className={
                                                    search.rows.some(
                                                        (row) =>
                                                            row[0] === label,
                                                    )
                                                        ? undefined
                                                        : 'hidden print:table-row'
                                                }
                                            >
<<<<<<< HEAD:resources/js/components/leave-form-demo-dialog.tsx
                                                <th
                                                    scope="row"
                                                    className="text-left font-normal"
                                                >
                                                    {label}
                                                </th>
                                                <td className="text-center">
                                                    {vacation}
                                                </td>
                                                <td className="text-center">
                                                    {sick}
                                                </td>
                                            </tr>
                                        ),
                                    )}
=======
                                                {label}
                                            </th>
                                            <td className="text-center">
                                                {!draft && vacation}
                                            </td>
                                            <td className="text-center">
                                                {!draft && sick}
                                            </td>
                                        </tr>
                                    ))}
>>>>>>> 2bcfedee3e691c4d958da8917ba2da6b47d6ffcd:resources/js/components/leave-form-preview-dialog.tsx
                                </tbody>
                            </table>
                            <Signature>(Authorized Officer)</Signature>
                        </section>
                        <section className="flex flex-col p-2">
                            <h5 className="mb-1.5 font-bold">
                                7.B RECOMMENDATION
                            </h5>
                            <ul className="mb-auto space-y-1">
                                <Choice checked={!draft}>For approval</Choice>
                                <Choice>
                                    For disapproval due to __________________
                                </Choice>
                            </ul>
                            <Signature>(Authorized Officer)</Signature>
                        </section>
                    </div>
                    <div className="grid grid-cols-2 divide-x divide-black border-t border-black">
                        <section className="space-y-1 p-2">
                            <h5 className="font-bold">7.C APPROVED FOR</h5>
                            <p>
                                <strong className="border-b border-black px-3">
                                    {draft ? '___' : '3'}
                                </strong>{' '}
                                days with pay
                            </p>
                            <p>_______ days without pay</p>
                            <p>_______ others (Specify)</p>
                        </section>
                        <section className="space-y-2 p-2">
                            <h5 className="font-bold">
                                7.D DISAPPROVED DUE TO
                            </h5>
                            <p className="h-3 border-b border-black" />
                            <p className="h-3 border-b border-black" />
                        </section>
                    </div>
                    <div className="px-2 pb-2">
                        <Signature>(Authorized Official)</Signature>
                    </div>
                </div>
            </article>
        </div>
    );
}

export default function LeaveFormPreviewDialog({
    draft,
    disabled = false,
}: {
    draft?: LeaveDraft;
    disabled?: boolean;
}) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    type="button"
                    disabled={disabled}
                    variant="secondary"
                    className={draft ? undefined : 'ml-auto'}
                >
                    <Eye aria-hidden="true" />
                    {draft ? 'Preview request' : 'Preview Leave Form'}
                </Button>
            </DialogTrigger>
            <DialogContent
                aria-describedby={undefined}
                data-print-preview=""
                className="max-h-[90dvh] overflow-y-auto sm:max-w-4xl"
            >
                <DialogTitle className="pr-8 print:hidden">
                    {draft
                        ? 'Review your leave request'
                        : 'Leave application demo'}
                </DialogTitle>
                <div className="flex justify-end print:hidden">
                    <Button type="button" onClick={() => window.print()}>
                        Print form
                    </Button>
                </div>
                <LeaveFormPreview draft={draft} />
            </DialogContent>
        </Dialog>
    );
}

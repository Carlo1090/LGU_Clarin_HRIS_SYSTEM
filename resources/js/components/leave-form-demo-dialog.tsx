import { Eye } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

const leaveTypes = [
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

function LeaveFormDemo() {
    return (
        <div className="overflow-x-auto print:overflow-visible">
            <article className="mx-auto max-w-3xl min-w-[34rem] border border-black bg-white p-4 font-[Arial,sans-serif] text-[11px] leading-snug text-black print:max-w-[185mm] print:min-w-0 print:border-0 print:p-0 print:text-[8pt] print:leading-tight print:[&_section]:p-1.5">
                <header className="grid grid-cols-[1fr_2.4fr_1fr] items-start gap-2">
                    <div className="italic">
                        <p>Civil Service Form No. 6</p>
                        <p>Revised 2020</p>
                        <p className="mt-1 font-bold not-italic">
                            Demo · Fictional data
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
                                Municipal Treasurer’s Office
                            </p>
                        </div>
                        <div className="p-2">
                            <p>2. NAME</p>
                            <dl className="mt-1 grid grid-cols-3 gap-2 text-center">
                                {[
                                    ['Last', 'Reyes'],
                                    ['First', 'Alex'],
                                    ['Middle', 'Mendoza'],
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
                            November 18, 2026
                        </FormLine>
                        <FormLine label="4. POSITION">
                            Administrative Assistant II
                        </FormLine>
                        <FormLine label="5. SALARY">₱24,000.00</FormLine>
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
                                {leaveTypes.map(([name, reference], index) => (
                                    <Choice key={name} checked={index === 0}>
                                        {name}{' '}
                                        <small className="text-[9px] leading-tight">
                                            ({reference})
                                        </small>
                                    </Choice>
                                ))}
                            </ul>
                            <FormLine label="Others" />
                        </section>
                        <section className="space-y-2 p-2">
                            <h5 className="font-bold">6.B DETAILS OF LEAVE</h5>
                            <div>
                                <p className="italic">
                                    In case of Vacation/Special Privilege Leave
                                </p>
                                <ul className="mt-0.5 space-y-0.5">
                                    <Choice checked>
                                        Within the Philippines{' '}
                                        <strong>Bohol</strong>
                                    </Choice>
                                    <Choice>
                                        Abroad (Specify) ____________________
                                    </Choice>
                                </ul>
                            </div>
                            <div>
                                <p className="italic">In case of Sick Leave</p>
                                <ul className="mt-0.5 space-y-0.5">
                                    <Choice>
                                        In Hospital (Specify Illness)
                                        ______________
                                    </Choice>
                                    <Choice>
                                        Out Patient (Specify Illness)
                                        ______________
                                    </Choice>
                                </ul>
                            </div>
                            <div>
                                <p className="italic">
                                    In case of Special Leave Benefits for Women
                                </p>
                                <FormLine label="(Specify Illness)" />
                            </div>
                            <div>
                                <p className="italic">In case of Study Leave</p>
                                <ul className="mt-0.5 space-y-0.5">
                                    <Choice>
                                        Completion of Master’s Degree
                                    </Choice>
                                    <Choice>
                                        BAR/Board Examination Review
                                    </Choice>
                                </ul>
                            </div>
                            <div>
                                <p className="italic">Other purpose</p>
                                <ul className="mt-0.5 space-y-0.5">
                                    <Choice>
                                        Monetization of Leave Credits
                                    </Choice>
                                    <Choice>Terminal Leave</Choice>
                                </ul>
                            </div>
                        </section>
                    </div>
                    <div className="grid grid-cols-2 divide-x divide-black border-t border-black">
                        <section className="space-y-2 p-2">
                            <h5 className="font-bold">
                                6.C NUMBER OF WORKING DAYS APPLIED FOR
                            </h5>
                            <p className="border-b border-black px-1 font-semibold">
                                2 days
                            </p>
                            <FormLine label="INCLUSIVE DATES">
                                November 26–27, 2026
                            </FormLine>
                        </section>
                        <section className="p-2">
                            <h5 className="mb-1 font-bold">6.D COMMUTATION</h5>
                            <ul className="space-y-1">
                                <Choice checked>Not Requested</Choice>
                                <Choice>Requested</Choice>
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
                            <FormLine label="As of">November 18, 2026</FormLine>
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
                                    {[
                                        ['Total Earned', '12.50', '10.00'],
                                        [
                                            'Less this application',
                                            '2.00',
                                            '0.00',
                                        ],
                                        ['Balance', '10.50', '10.00'],
                                    ].map(([label, vacation, sick]) => (
                                        <tr key={label}>
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
                                    ))}
                                </tbody>
                            </table>
                            <Signature>(Authorized Officer)</Signature>
                        </section>
                        <section className="flex flex-col p-2">
                            <h5 className="mb-1.5 font-bold">
                                7.B RECOMMENDATION
                            </h5>
                            <ul className="mb-auto space-y-1">
                                <Choice checked>For approval</Choice>
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
                                    2
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

export default function LeaveFormDemoDialog() {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button type="button" variant="secondary" className="ml-auto">
                    <Eye aria-hidden="true" />
                    Preview Leave Form
                </Button>
            </DialogTrigger>
            <DialogContent
                aria-describedby={undefined}
                data-print-preview=""
                className="max-h-[90dvh] overflow-y-auto sm:max-w-4xl"
            >
                <DialogTitle className="pr-8 print:hidden">
                    Leave application demo
                </DialogTitle>
                <div className="flex justify-end print:hidden">
                    <Button type="button" onClick={() => window.print()}>
                        Print demo
                    </Button>
                </div>
                <LeaveFormDemo />
            </DialogContent>
        </Dialog>
    );
}

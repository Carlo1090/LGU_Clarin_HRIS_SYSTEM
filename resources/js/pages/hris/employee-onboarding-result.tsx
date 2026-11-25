import { downloadEmployeeImportErrors } from '@/lib/employee-import-errors';
import { Button } from '@/components/ui/button';
import WorkspaceHeader from '@/components/workspace-header';
import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

type Slip = {
    employee_number: string;
    name: string;
    department: string;
    activation_code: string;
    expires_at: string;
    issuance_id: number;
};

type RejectedRow = { row: number; errors: string[] };

export default function EmployeeOnboardingResult({
    imported,
    reissued = false,
    created,
    rejected,
    activationUrl,
}: {
    imported: boolean;
    reissued?: boolean;
    created: number;
    rejected: RejectedRow[];
    activationUrl: string;
}) {
    const slips = (usePage().flash.activationSlips ?? []) as Slip[];
    const title = imported
        ? 'Employee import complete'
        : reissued
          ? 'Activation code reissued'
          : 'Employee account created';
    const [printing, setPrinting] = useState(false);
    const [printError, setPrintError] = useState('');

    async function printSlips() {
        const csrfCookie = document.cookie
            .split('; ')
            .find((cookie) => cookie.startsWith('XSRF-TOKEN='));
        setPrinting(true);
        setPrintError('');
        try {
            const response = await fetch(
                '/hris/employees/activation-slips/print',
                {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-XSRF-TOKEN': decodeURIComponent(
                            csrfCookie?.slice('XSRF-TOKEN='.length) ?? '',
                        ),
                    },
                    body: JSON.stringify({
                        issuances: slips.map((slip) => slip.issuance_id),
                    }),
                },
            );
            if (response.status !== 204) {
                throw new Error('Print audit failed');
            }
            window.print();
        } catch {
            setPrintError(
                'The print request could not be recorded. Try again before leaving this page.',
            );
        } finally {
            setPrinting(false);
        }
    }

    return (
        <main className="workspace-page max-w-5xl">
            <Head title={title} />
            <div className="print:hidden">
                <WorkspaceHeader title={title}>
                    <Button asChild variant="outline">
                        <Link
                            href={
                                imported
                                    ? '/hris/employees/import'
                                    : '/hris/employees'
                            }
                        >
                            {imported
                                ? 'Back to bulk import'
                                : 'Back to Employees'}
                        </Link>
                    </Button>
                </WorkspaceHeader>
                {!reissued && (
                    <p className="my-4 text-sm">
                        {created} pending account(s) created. {rejected.length}{' '}
                        row(s) held back.
                    </p>
                )}
                {rejected.length > 0 && (
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => downloadEmployeeImportErrors(rejected)}
                    >
                        Download errors
                    </Button>
                )}
                {slips.length > 0 && (
                    <Button
                        type="button"
                        disabled={printing}
                        onClick={printSlips}
                    >
                        Print activation slips
                    </Button>
                )}
                {printError && (
                    <p role="alert" className="text-sm text-destructive">
                        {printError}
                    </p>
                )}
            </div>
            {slips.length > 0 && (
                <section data-activation-slip-sheet className="space-y-4">
                    <p className="text-sm print:hidden">
                        Print or distribute these codes privately before leaving
                        this page.
                    </p>
                    {slips.map((slip) => (
                        <article
                            key={slip.employee_number}
                            className="break-inside-avoid rounded-lg border p-4"
                        >
                            <h2 className="font-semibold">{slip.name}</h2>
                            <p>{slip.department}</p>
                            <p>Employee Number {slip.employee_number}</p>
                            <p className="font-mono text-lg">
                                Code {slip.activation_code}
                            </p>
                            <p>Expires {slip.expires_at}</p>
                            <p className="break-all">{activationUrl}</p>
                        </article>
                    ))}
                    <section className="mt-6 break-inside-avoid">
                        <h2 className="font-semibold">HR distribution copy</h2>
                        <table className="mt-2 w-full border-collapse text-left text-sm">
                            <thead>
                                <tr>
                                    <th>Employee Number</th>
                                    <th>Name</th>
                                    <th>Signature</th>
                                </tr>
                            </thead>
                            <tbody>
                                {slips.map((slip) => (
                                    <tr
                                        key={slip.employee_number}
                                        className="border-b"
                                    >
                                        <td className="py-2">
                                            {slip.employee_number}
                                        </td>
                                        <td>{slip.name}</td>
                                        <td>________________</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </section>
                </section>
            )}
        </main>
    );
}

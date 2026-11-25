import WorkspaceHeader from '@/components/workspace-header';
import { Head } from '@inertiajs/react';

type EmployeeProfile = Record<string, string | number | null>;

const sections = [
    {
        title: 'Personal information',
        fields: [
            ['employee_number', 'Employee Number'],
            ['first_name', 'First Name'],
            ['middle_name', 'Middle Name'],
            ['last_name', 'Last Name'],
            ['suffix', 'Suffix'],
            ['gender', 'Sex'],
            ['birth_date', 'Date of Birth'],
            ['employment_status', 'Employment Status'],
            ['date_hired', 'Date Hired'],
            ['date_regularized', 'Date Regularized'],
            ['age', 'Age'],
            ['tin', 'TIN'],
        ],
    },
    {
        title: 'Appointment and eligibility',
        fields: [
            ['date_of_original_appointment', 'Date of Original Appointment'],
            [
                'date_of_last_promotion_appointment',
                'Date of Last Promotion/Appointment',
            ],
            ['appointment_status', 'Status'],
            ['civil_service_eligibility', 'Civil Service Eligibility'],
            ['comment_annotation', 'Comment/Annotation'],
        ],
    },
    {
        title: 'Position and office',
        fields: [
            ['department', 'Department'],
            ['organizational_unit', 'Organizational Unit'],
            ['item_number', 'Item Number'],
            ['position_title', 'Position Title'],
            ['salary_grade', 'Salary Grade'],
            ['authorized_annual_salary', 'Authorized Annual Salary'],
            ['actual_annual_salary', 'Actual Annual Salary'],
            ['step', 'Step'],
            ['area_code', 'Area Code'],
            ['area_type', 'Area Type'],
            ['level', 'Level'],
            ['position_classification', 'Position Classification'],
            ['abolished', 'Abolished'],
            ['dissolved', 'Dissolved'],
        ],
    },
    {
        title: 'Additional information',
        fields: [
            ['pwd', 'Person with Disability'],
            ['indigenous_people', 'Indigenous People'],
            ['solo_parent_id_number', 'Solo Parent ID Number'],
            ['gsis_bp_number', 'GSIS BP Number'],
        ],
    },
] as const;

export default function MyProfile({
    employee,
    customFields = [],
    draft,
    identifierRevealEmployeeId,
    reviewFields = {},
    eligibilities = [],
    naFields = [],
    privacyNotice = null,
    savedPrivateDraftFields = [],
}: {
    employee: EmployeeProfile;
    customFields?: { label: string; value: string | null }[];
    draft?: Record<string, string | null> | null;
    identifierRevealEmployeeId?: number | null;
    reviewFields?: ReviewFields;
    eligibilities?: string[];
    naFields?: string[];
    privacyNotice?: string | null;
    savedPrivateDraftFields?: string[];
}) {
    return (
        <>
            <Head title="My HR Profile" />
            <main className="workspace-page">
                <WorkspaceHeader
                    title="My HR Profile"
                    description="Employee information maintained by HRMO."
                />
                {draft != null && (
                    <EmployeeProfileDraft
                        draft={draft}
                        savedPrivateFields={savedPrivateDraftFields}
                        reviewFields={reviewFields}
                        eligibilities={eligibilities}
                        naFields={naFields}
                        privacyNotice={privacyNotice}
                    />
                )}
                {sections.map((section) => (
                    <section
                        key={section.title}
                        className="workspace-panel space-y-4 p-5"
                    >
                        <h2 className="font-semibold">{section.title}</h2>
                        <dl className="grid gap-4 @min-[34rem]/workspace:grid-cols-2 @min-[56rem]/workspace:grid-cols-3">
                            {section.fields.map(([key, label]) => (
                                <div key={key} className="min-w-0">
                                    <dt className="text-xs text-muted-foreground">
                                        {label}
                                    </dt>
                                    <dd className="mt-1 text-sm break-words">
                                        {employee[key] === null ||
                                        employee[key] === ''
                                            ? '—'
                                            : String(employee[key])}
                                    </dd>
                                    {identifierRevealEmployeeId &&
                                        employee[key] &&
                                        (key === 'tin' ||
                                            key === 'gsis_bp_number') && (
                                            <EmployeeIdentifierReveal
                                                employeeId={
                                                    identifierRevealEmployeeId
                                                }
                                                field={key}
                                                label={label}
                                            />
                                        )}
                                </div>
                            ))}
                        </dl>
                    </section>
                ))}
                {customFields.length > 0 && (
                    <section className="workspace-panel space-y-4 p-5">
                        <h2 className="font-semibold">
                            Additional HR information
                        </h2>
                        <dl className="grid gap-4 @min-[34rem]/workspace:grid-cols-2 @min-[56rem]/workspace:grid-cols-3">
                            {customFields.map((field) => (
                                <div key={field.label} className="min-w-0">
                                    <dt className="text-xs text-muted-foreground">
                                        {field.label}
                                    </dt>
                                    <dd className="mt-1 text-sm break-words">
                                        {field.value || '—'}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </section>
                )}
            </main>
        </>
    );
}
import EmployeeProfileDraft, {
    type ReviewFields,
} from '@/components/employee-profile-draft';
import EmployeeIdentifierReveal from '@/components/employee-identifier-reveal';

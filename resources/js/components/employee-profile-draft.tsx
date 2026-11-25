import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';

const fields = [
    ['birth_date', 'Birth Date', 'date'],
    ['tin', 'TIN', 'text'],
    ['gsis_bp_number', 'GSIS BP Number', 'text'],
    ['pwd', 'PWD', 'yes-no'],
    ['pwd_id_number', 'PWD ID Number', 'text', 'pwd'],
    ['indigenous_people', 'Indigenous People', 'yes-no'],
    [
        'indigenous_people_detail',
        'Indigenous People detail',
        'text',
        'indigenous_people',
    ],
    ['solo_parent', 'Solo Parent', 'yes-no'],
    ['solo_parent_id_number', 'Solo Parent ID Number', 'text', 'solo_parent'],
    ['civil_service_eligibility', 'Civil Service Eligibility', 'text'],
    [
        'eligibility_other',
        'Other eligibility',
        'text',
        'civil_service_eligibility',
    ],
    ['date_regularized', 'Date Regularized', 'date'],
    ['date_of_original_appointment', 'Date of Original Appointment', 'date'],
    [
        'date_of_last_promotion_appointment',
        'Date of Last Promotion or Appointment',
        'date',
    ],
] as const;

export type ReviewFields = Record<
    string,
    { status: string; reason: string | null }
>;

export default function EmployeeProfileDraft({
    draft,
    savedPrivateFields,
    reviewFields,
    eligibilities,
    naFields,
    privacyNotice,
}: {
    draft: Record<string, string | null>;
    savedPrivateFields: string[];
    reviewFields: ReviewFields;
    eligibilities: string[];
    naFields: string[];
    privacyNotice: string | null;
}) {
    const [values, setValues] = useState(draft);
    const committed = useRef({ ...draft });
    const queue = useRef(Promise.resolve());
    const [messages, setMessages] = useState<Record<string, string>>({});
    const [changes, setChanges] = useState<Record<string, boolean>>({});
    const [failed, setFailed] = useState<Record<string, boolean>>({});
    const failures = useRef<Record<string, boolean>>({});
    const submission = useForm({});

    function save(field: string, value: string) {
        if ((committed.current[field] ?? '') === value) return;
        committed.current[field] = value;
        setMessages((current) => ({ ...current, [field]: 'Saving…' }));
        queue.current = queue.current.then(async () => {
            try {
                const cookie = document.cookie
                    .split('; ')
                    .find((part) => part.startsWith('XSRF-TOKEN='));
                const result = await fetch('/hris/my-profile/draft', {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-XSRF-TOKEN': decodeURIComponent(
                            cookie?.slice('XSRF-TOKEN='.length) ?? '',
                        ),
                    },
                    body: JSON.stringify({ [field]: value || null }),
                });
                if (result.status !== 204) throw new Error('Draft save failed');
                delete failures.current[field];
                setFailed({ ...failures.current });
                setMessages((current) => ({ ...current, [field]: 'Saved' }));
            } catch {
                failures.current[field] = true;
                setFailed({ ...failures.current });
                if (committed.current[field] === value)
                    delete committed.current[field];
                setMessages((current) => ({
                    ...current,
                    [field]: 'Could not save. Leave the field again to retry.',
                }));
            }
        });
    }

    return (
        <section className="workspace-panel space-y-4 p-5">
            <h2 className="font-semibold">Profile draft</h2>
            {privacyNotice && (
                <p className="text-sm whitespace-pre-line">{privacyNotice}</p>
            )}
            <p className="text-sm text-muted-foreground">
                Draft fields save when you leave them. HR reviews submitted
                changes.
            </p>
            <div className="grid gap-4 sm:grid-cols-2">
                {fields.map(([field, label, type, parent]) => {
                    const expected =
                        parent === 'civil_service_eligibility'
                            ? 'Other'
                            : 'yes';
                    if (parent && values[parent] !== expected) return null;
                    const review = reviewFields[parent ?? field];
                    const approved =
                        review?.status === 'approved' ||
                        review?.status === 'superseded';
                    const locked =
                        review?.status === 'submitted' ||
                        (approved && !changes[parent ?? field]);
                    const isNa = values[field] === 'not_applicable';
                    return (
                        <div key={field} className="space-y-1 text-sm">
                            <label htmlFor={`draft-${field}`}>{label}</label>
                            {review && !parent && (
                                <p className="text-xs capitalize">
                                    {review.status}
                                    {review.reason ? ` · ${review.reason}` : ''}
                                </p>
                            )}
                            {locked ? (
                                approved && !parent ? (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            setChanges((current) => ({
                                                ...current,
                                                [field]: true,
                                            }))
                                        }
                                    >
                                        Request change
                                    </Button>
                                ) : null
                            ) : type === 'yes-no' ||
                              field === 'civil_service_eligibility' ? (
                                <select
                                    className="block h-9 w-full rounded-md border bg-background px-2"
                                    id={`draft-${field}`}
                                    value={values[field] ?? ''}
                                    onChange={(event) => {
                                        const value = event.target.value;
                                        setValues((current) => ({
                                            ...current,
                                            [field]: value,
                                        }));
                                        save(field, value);
                                    }}
                                >
                                    <option value="">Select</option>
                                    {(type === 'yes-no'
                                        ? ['yes', 'no']
                                        : eligibilities
                                    ).map((choice) => (
                                        <option key={choice} value={choice}>
                                            {choice === 'yes'
                                                ? 'Yes'
                                                : choice === 'no'
                                                  ? 'No'
                                                  : choice}
                                        </option>
                                    ))}
                                </select>
                            ) : (
                                !isNa && (
                                    <Input
                                        id={`draft-${field}`}
                                        type={type}
                                        value={values[field] ?? ''}
                                        placeholder={
                                            savedPrivateFields.includes(field)
                                                ? 'Saved. Enter a new value to replace it.'
                                                : undefined
                                        }
                                        onChange={(event) =>
                                            setValues((current) => ({
                                                ...current,
                                                [field]: event.target.value,
                                            }))
                                        }
                                        onBlur={(event) =>
                                            save(field, event.target.value)
                                        }
                                    />
                                )
                            )}
                            {!locked && naFields.includes(field) && (
                                <label className="flex items-center gap-2 text-xs">
                                    <input
                                        type="checkbox"
                                        checked={isNa}
                                        onChange={(event) => {
                                            const value = event.target.checked
                                                ? 'not_applicable'
                                                : '';
                                            setValues((current) => ({
                                                ...current,
                                                [field]: value,
                                            }));
                                            save(field, value);
                                        }}
                                    />
                                    Not applicable
                                </label>
                            )}
                            <span
                                className="block text-xs text-muted-foreground"
                                role="status"
                            >
                                {messages[field]}
                            </span>
                        </div>
                    );
                })}
            </div>
            {Object.entries(submission.errors).map(([field, error]) => (
                <p className="text-sm text-destructive" key={field}>
                    {String(error)}
                </p>
            ))}
            <Button
                type="button"
                disabled={
                    submission.processing || Object.keys(failed).length > 0
                }
                onClick={async () => {
                    await queue.current;
                    if (Object.keys(failures.current).length > 0) return;
                    submission.post('/hris/my-profile/submit', {
                        preserveScroll: true,
                        onSuccess: () => {
                            setValues({});
                            committed.current = {};
                            setMessages({});
                            setChanges({});
                        },
                    });
                }}
            >
                Submit for review
            </Button>
        </section>
    );
}

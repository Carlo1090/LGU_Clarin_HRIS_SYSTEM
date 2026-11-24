import { Form, Head, Link, usePage } from '@inertiajs/react';
import { MapPin, Pencil, Upload } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/hooks/use-initials';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { Auth } from '@/types';

type Details = {
    phone: string | null;
    location: string | null;
    bio: string | null;
};
type ProfileValues = Details & { name: string; email: string | null };
type Field = keyof ProfileValues;
type Section = 'personal' | 'location' | 'bio';
const sections: { id: Section; title: string; fields: Field[] }[] = [
    {
        id: 'personal',
        title: 'Personal info',
        fields: ['name', 'email', 'phone'],
    },
    { id: 'location', title: 'Location', fields: ['location'] },
    { id: 'bio', title: 'Bio', fields: ['bio'] },
];
const labels = {
    name: 'Full name',
    email: 'Email address',
    phone: 'Phone',
    location: 'Location',
    bio: 'Bio',
};
const lengths = { name: 255, email: 255, phone: 40, location: 160, bio: 2000 };
const autocomplete = {
    name: 'name',
    email: 'email',
    phone: 'tel',
    location: 'address-level2',
};

function ProfilePhoto({
    name,
    avatar,
}: {
    name: string;
    avatar?: string | null;
}) {
    const initials = useInitials();
    const input = useRef<HTMLInputElement>(null);
    const [preview, setPreview] = useState('');
    useEffect(
        () => () => {
            if (preview) URL.revokeObjectURL(preview);
        },
        [preview],
    );
    function reset() {
        if (input.current) input.current.value = '';
        setPreview('');
    }
    return (
        <Form
            {...ProfileController.uploadPhoto.form()}
            options={{ preserveScroll: true }}
            errorBag="profilePhoto"
            onSuccess={reset}
            className="flex flex-wrap items-center gap-5 border-b pb-6"
        >
            {({ processing, errors, clearErrors }) => (
                <>
                    <Avatar
                        className="size-20 ring-1 ring-border sm:size-24"
                        aria-label={`Profile picture for ${name}`}
                    >
                        <AvatarImage
                            src={preview || avatar || undefined}
                            alt={name}
                            className="object-cover"
                        />
                        <AvatarFallback className="bg-lime-200 text-2xl font-semibold text-lime-950">
                            {initials(name)}
                        </AvatarFallback>
                    </Avatar>
                    <div className="min-w-0 flex-1 basis-44 space-y-2">
                        <input
                            type="file"
                            name="photo"
                            id="profile-photo"
                            ref={input}
                            hidden
                            accept="image/jpeg,image/png,image/webp"
                            onChange={(event) => {
                                const file = event.currentTarget.files?.[0];
                                if (file) {
                                    clearErrors();
                                    setPreview(URL.createObjectURL(file));
                                }
                            }}
                        />
                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={processing}
                                aria-describedby="photo-help"
                                onClick={() => input.current?.click()}
                            >
                                <Upload aria-hidden="true" />
                                {preview
                                    ? 'Choose another photo'
                                    : 'Upload new photo'}
                            </Button>
                            {preview && (
                                <>
                                    <Button
                                        type="submit"
                                        size="sm"
                                        className="dashboard-action"
                                        disabled={processing}
                                    >
                                        {processing ? 'Saving…' : 'Save photo'}
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="ghost"
                                        disabled={processing}
                                        onClick={() => {
                                            reset();
                                            clearErrors();
                                        }}
                                    >
                                        Cancel
                                    </Button>
                                </>
                            )}
                        </div>
                        <p
                            id="photo-help"
                            className="text-xs leading-5 text-muted-foreground"
                        >
                            JPEG, PNG or WebP · Up to 2 MB
                            <br />
                            Maximum 4096 × 4096 pixels
                        </p>
                        <InputError role="alert" message={errors.photo} />
                    </div>
                </>
            )}
        </Form>
    );
}

function ProfileCard({
    section,
    values,
}: {
    section: (typeof sections)[number];
    values: ProfileValues;
}) {
    const [editing, setEditing] = useState(false);
    const editButton = useRef<HTMLButtonElement>(null);
    const personal = section.id === 'personal';
    function close() {
        setEditing(false);
        requestAnimationFrame(() => editButton.current?.focus());
    }
    return (
        <Card
            role="region"
            aria-labelledby={`heading-${section.id}`}
            className={`gap-3 rounded-2xl p-4 shadow-none sm:p-5 ${editing ? 'bg-muted/40' : ''}`}
        >
            <div className="flex items-center justify-between gap-3">
                <h2
                    id={`heading-${section.id}`}
                    className="text-base font-semibold tracking-tight"
                >
                    {section.title}
                </h2>
                {!editing && (
                    <Button
                        ref={editButton}
                        type="button"
                        variant="outline"
                        size="sm"
                        aria-label={`Edit ${section.title}`}
                        onClick={() => setEditing(true)}
                    >
                        <Pencil aria-hidden="true" />
                        Edit
                    </Button>
                )}
            </div>
            {editing ? (
                <Form
                    {...ProfileController.update.form()}
                    options={{ preserveScroll: true }}
                    errorBag={section.id}
                    onSuccess={close}
                >
                    {({ processing, errors }) => (
                        <fieldset
                            disabled={processing}
                            className="min-w-0 space-y-3"
                        >
                            <input
                                type="hidden"
                                name="section"
                                value={section.id}
                            />
                            <div
                                className={
                                    personal
                                        ? 'grid gap-3 sm:grid-cols-2'
                                        : 'grid gap-3'
                                }
                            >
                                {section.fields.map((field, index) => {
                                    const props = {
                                        id: field,
                                        name: field,
                                        defaultValue: values[field] ?? '',
                                        maxLength: lengths[field],
                                        autoFocus: index === 0,
                                        'aria-invalid': !!errors[field],
                                        'aria-describedby': errors[field]
                                            ? `${field}-error`
                                            : undefined,
                                    };
                                    return (
                                        <div
                                            key={field}
                                            className="min-w-0 space-y-1.5"
                                        >
                                            <Label
                                                htmlFor={field}
                                                className={
                                                    personal ? '' : 'sr-only'
                                                }
                                            >
                                                {labels[field]}
                                            </Label>
                                            {field === 'bio' ? (
                                                <textarea
                                                    {...props}
                                                    rows={5}
                                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-ring"
                                                />
                                            ) : (
                                                <Input
                                                    {...props}
                                                    type={
                                                        field === 'email'
                                                            ? 'email'
                                                            : field === 'phone'
                                                              ? 'tel'
                                                              : 'text'
                                                    }
                                                    autoComplete={
                                                        autocomplete[field]
                                                    }
                                                    required={
                                                        field === 'name' ||
                                                        field === 'email'
                                                    }
                                                />
                                            )}
                                            <InputError
                                                id={`${field}-error`}
                                                role="alert"
                                                message={errors[field]}
                                            />
                                        </div>
                                    );
                                })}
                            </div>
                            <div className="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={close}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    size="sm"
                                    className="dashboard-action"
                                    data-test={
                                        personal
                                            ? 'update-profile-button'
                                            : undefined
                                    }
                                >
                                    {processing ? 'Saving…' : 'Save changes'}
                                </Button>
                            </div>
                        </fieldset>
                    )}
                </Form>
            ) : personal ? (
                <dl className="grid gap-3 sm:grid-cols-[1fr_1.4fr_1fr]">
                    {section.fields.map((field) => (
                        <div key={field} className="min-w-0">
                            <dt className="mb-1 text-xs text-muted-foreground">
                                {labels[field]}
                            </dt>
                            <dd className="text-sm font-medium [overflow-wrap:anywhere]">
                                {values[field] || 'Not provided'}
                            </dd>
                        </div>
                    ))}
                </dl>
            ) : (
                <p className="flex items-start gap-2 text-sm leading-6">
                    {section.id === 'location' && (
                        <MapPin
                            className="mt-1 size-4 shrink-0 text-muted-foreground"
                            aria-hidden="true"
                        />
                    )}
                    <span
                        className={
                            values[section.fields[0]]
                                ? '[overflow-wrap:anywhere] whitespace-pre-line'
                                : 'text-muted-foreground'
                        }
                    >
                        {values[section.fields[0]] ||
                            (section.id === 'bio'
                                ? 'No bio yet.'
                                : 'No location added.')}
                    </span>
                </p>
            )}
        </Card>
    );
}

export default function Profile({
    details,
    mustVerifyEmail,
    status,
}: {
    details: Details;
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const {
        auth: { user },
    } = usePage<{ auth: Auth }>().props;
    const values = { ...details, name: user.name, email: user.email };
    return (
        <>
            <Head title="Profile settings" />
            <div className="space-y-4">
                <h1 className="sr-only">Profile settings</h1>
                <ProfilePhoto name={user.name} avatar={user.avatar} />
                {sections.map((section) => (
                    <ProfileCard
                        key={section.id}
                        section={section}
                        values={values}
                    />
                ))}
                {mustVerifyEmail && user.email && !user.email_verified_at && (
                    <div className="text-sm">
                        <p>
                            Your email address is unverified.{' '}
                            <Link
                                href={send()}
                                as="button"
                                className="underline underline-offset-4"
                            >
                                Resend verification email
                            </Link>
                        </p>
                        {status === 'verification-link-sent' && (
                            <p role="status">
                                A new verification link has been sent to your
                                email address.
                            </p>
                        )}
                    </div>
                )}
            </div>
            <DeleteUser />
        </>
    );
}

Profile.layout = { breadcrumbs: [{ title: 'Profile settings', href: edit() }] };

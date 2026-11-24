import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Camera, UserRound } from 'lucide-react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import InputError from '@/components/input-error';
import PersonAvatar from '@/components/person-avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { Auth } from '@/types';

type PageProps = {
    auth: Auth;
};

export default function Profile({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const { auth } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Profile settings" />

            <div className="space-y-10">
                {/* Page heading */}
                <header className="flex items-center gap-4">
                    <span
                        className="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-accent text-accent-foreground"
                        aria-hidden="true"
                    >
                        <UserRound className="size-5" />
                    </span>
                    <div className="space-y-1">
                        <h1 className="text-xl font-semibold tracking-tight">
                            Profile information
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Your photo, name, and email are shown across the
                            HRIS portal.
                        </p>
                    </div>
                </header>

                {/* Photo */}
                <Form
                    {...ProfileController.uploadPhoto.form()}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                >
                    {({ processing, errors }) => (
                        <div className="space-y-3">
                            <div className="flex flex-col items-center gap-5 rounded-2xl border bg-muted/30 p-5 text-center sm:flex-row sm:items-center sm:text-left">
                                <div className="size-20 shrink-0 [&>*]:size-full!">
                                    <PersonAvatar
                                        name={auth.user.name}
                                        src={auth.user.avatar}
                                    />
                                </div>

                                <div className="min-w-0 flex-1 space-y-3">
                                    <div className="space-y-1">
                                        <p className="text-sm font-medium">
                                            Profile picture
                                        </p>
                                        <p
                                            id="photo-help"
                                            className="text-xs text-muted-foreground"
                                        >
                                            JPEG, PNG or WebP, up to 2 MB. The
                                            photo saves as soon as you pick it.
                                        </p>
                                    </div>

                                    <input
                                        id="profile-photo"
                                        name="photo"
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        required
                                        disabled={processing}
                                        className="peer sr-only"
                                        aria-describedby="photo-help"
                                        onChange={(event) => {
                                            if (
                                                event.currentTarget.files
                                                    ?.length
                                            ) {
                                                event.currentTarget.form?.requestSubmit();
                                            }
                                        }}
                                    />
                                    <Label
                                        htmlFor="profile-photo"
                                        className="inline-flex h-9 cursor-pointer items-center gap-2 rounded-full border bg-background px-4 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground peer-focus-visible:ring-2 peer-focus-visible:ring-ring peer-disabled:pointer-events-none peer-disabled:opacity-50"
                                    >
                                        {processing ? (
                                            <Spinner />
                                        ) : (
                                            <Camera
                                                className="size-4"
                                                aria-hidden="true"
                                            />
                                        )}
                                        {processing
                                            ? 'Uploading…'
                                            : 'Change photo'}
                                    </Label>
                                </div>
                            </div>
                            <InputError message={errors.photo} />
                        </div>
                    )}
                </Form>

                {/* Name and email */}
                <Form
                    {...ProfileController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-8"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-6 sm:grid-cols-2">
                                <div className="grid content-start gap-2">
                                    <Label htmlFor="name">Full name</Label>

                                    <Input
                                        id="name"
                                        className="block w-full"
                                        defaultValue={auth.user.name}
                                        name="name"
                                        required
                                        autoComplete="name"
                                        placeholder="Your full name"
                                    />

                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid content-start gap-2">
                                    <Label htmlFor="email">Email address</Label>

                                    <Input
                                        id="email"
                                        type="email"
                                        className="block w-full"
                                        defaultValue={auth.user.email}
                                        name="email"
                                        required
                                        autoComplete="username"
                                        placeholder="you@example.com"
                                    />

                                    <InputError message={errors.email} />
                                </div>
                            </div>

                            {mustVerifyEmail &&
                                auth.user.email_verified_at === null && (
                                    <div>
                                        <p className="text-sm text-muted-foreground">
                                            Your email address is unverified.{' '}
                                            <Link
                                                href={send()}
                                                as="button"
                                                className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                            >
                                                Click here to re-send the
                                                verification email.
                                            </Link>
                                        </p>

                                        {status ===
                                            'verification-link-sent' && (
                                            <div className="mt-2 text-sm font-medium text-green-600">
                                                A new verification link has been
                                                sent to your email address.
                                            </div>
                                        )}
                                    </div>
                                )}

                            <div className="flex items-center justify-end gap-4 border-t pt-6">
                                <Button
                                    disabled={processing}
                                    className="rounded-full px-6"
                                    data-test="update-profile-button"
                                >
                                    {processing && <Spinner />}
                                    Save changes
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <Separator />

                {/* Danger zone */}
                <section aria-label="Danger zone">
                    <DeleteUser />
                </section>
            </div>
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile settings',
            href: edit(),
        },
    ],
};
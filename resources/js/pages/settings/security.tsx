import { Form, Head } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import { useRef } from 'react';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { edit } from '@/routes/security';

type Props = {
    passwordRules: string;
};

export default function Security(props: Props) {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    return (
        <>
            <Head title="Security settings" />

            <div className="space-y-8">
                <div className="flex items-start gap-4">
                    <span
                        className="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-accent text-accent-foreground"
                        aria-hidden="true"
                    >
                        <KeyRound className="size-5" />
                    </span>
                    <div className="space-y-1">
                        <h1 className="text-xl font-semibold tracking-tight">
                            Change password
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Choose a strong password that you don't use on any
                            other site.
                        </p>
                    </div>
                </div>

                <Form
                    {...SecurityController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    resetOnError={[
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]}
                    resetOnSuccess
                    onError={(errors) => {
                        if (errors.password) {
                            passwordInput.current?.focus();
                        }

                        if (errors.current_password) {
                            currentPasswordInput.current?.focus();
                        }
                    }}
                    className="space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="current_password">
                                    Current password
                                </Label>

                                <PasswordInput
                                    id="current_password"
                                    ref={currentPasswordInput}
                                    name="current_password"
                                    className="block w-full"
                                    autoComplete="current-password"
                                    placeholder="Enter your current password"
                                />

                                <InputError message={errors.current_password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">New password</Label>

                                <PasswordInput
                                    id="password"
                                    ref={passwordInput}
                                    name="password"
                                    className="block w-full"
                                    autoComplete="new-password"
                                    placeholder="Enter a new password"
                                    passwordrules={props.passwordRules}
                                />

                                <p className="text-xs text-muted-foreground">
                                    Use a long password that mixes letters,
                                    numbers, and symbols.
                                </p>

                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirm new password
                                </Label>

                                <PasswordInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    className="block w-full"
                                    autoComplete="new-password"
                                    placeholder="Re-enter the new password"
                                    passwordrules={props.passwordRules}
                                />

                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <div className="flex items-center gap-4 pt-2">
                                <Button
                                    disabled={processing}
                                    className="rounded-full px-6"
                                    data-test="update-password-button"
                                >
                                    {processing && <Spinner />}
                                    Update password
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

Security.layout = {
    breadcrumbs: [
        {
            title: 'Security settings',
            href: edit(),
        },
    ],
};

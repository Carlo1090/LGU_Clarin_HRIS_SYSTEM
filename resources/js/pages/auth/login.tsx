import { Form, Head } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = { status?: string; canResetPassword: boolean };

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Log in" />
            {status && (
                <div
                    role="status"
                    className="mb-6 rounded-xl border border-input bg-accent p-3 text-sm leading-6 text-accent-foreground"
                >
                    {status}
                </div>
            )}
            <PasskeyVerify />
            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="space-y-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="space-y-5">
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    autoComplete="email"
                                    placeholder="email@example.com"
                                    aria-invalid={!!errors.email}
                                    aria-describedby={
                                        errors.email ? 'email-error' : undefined
                                    }
                                />
                                <InputError
                                    id="email-error"
                                    message={errors.email}
                                    role="alert"
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    autoComplete="current-password"
                                    placeholder="Enter your password"
                                    aria-invalid={!!errors.password}
                                    aria-describedby={
                                        errors.password
                                            ? 'password-error'
                                            : undefined
                                    }
                                />
                                <InputError
                                    id="password-error"
                                    message={errors.password}
                                    role="alert"
                                />
                            </div>
                            <div className="flex flex-wrap items-center justify-between gap-x-3 gap-y-3">
                                <div className="flex items-center gap-2">
                                    <Checkbox id="remember" name="remember" />
                                    <Label
                                        htmlFor="remember"
                                        className="cursor-pointer text-sm font-normal text-muted-foreground"
                                    >
                                        Remember me
                                    </Label>
                                </div>
                                {canResetPassword && (
                                    <TextLink
                                        href={request()}
                                        className="text-sm font-medium text-accent-foreground"
                                    >
                                        Forgot password?
                                    </TextLink>
                                )}
                            </div>
                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                                aria-busy={processing}
                                data-test="login-button"
                            >
                                {processing ? <Spinner /> : null}
                                {processing
                                    ? 'Logging in...'
                                    : 'Log in to HRIS'}
                                {!processing && (
                                    <ArrowRight
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                )}
                            </Button>
                        </div>
                        <p className="text-center text-sm leading-6 text-muted-foreground">
                            Don&apos;t have an account?{' '}
                            <TextLink
                                href={register()}
                                className="font-semibold text-accent-foreground"
                            >
                                Create an account
                            </TextLink>
                        </p>
                    </>
                )}
            </Form>
        </>
    );
}

Login.layout = {
    title: 'Welcome back',
    description: 'Enter your credentials to access your municipal workspace.',
};

import { Form, Head, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { redirect as googleRedirect } from '@/routes/google';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

function GoogleIcon() {
    return (
        <svg aria-hidden="true" viewBox="0 0 24 24" className="size-5 shrink-0">
            <path
                fill="#4285F4"
                d="M21.35 12.21c0-.7-.06-1.39-.18-2.05H12v3.89h5.25a4.48 4.48 0 0 1-1.94 2.94v2.53h3.15c1.85-1.71 2.89-4.23 2.89-7.31Z"
            />
            <path
                fill="#34A853"
                d="M12 21.7c2.64 0 4.85-.87 6.46-2.18l-3.15-2.53c-.87.58-1.99.92-3.31.92-2.55 0-4.7-1.72-5.47-4.04H3.28v2.61A9.75 9.75 0 0 0 12 21.7Z"
            />
            <path
                fill="#FBBC05"
                d="M6.53 13.87a5.85 5.85 0 0 1 0-3.74V7.52H3.28a9.7 9.7 0 0 0 0 8.96l3.25-2.61Z"
            />
            <path
                fill="#EA4335"
                d="M12 6.09c1.44 0 2.73.5 3.75 1.47l2.81-2.81A9.39 9.39 0 0 0 12 2.3a9.75 9.75 0 0 0-8.72 5.22l3.25 2.61C7.3 7.81 9.45 6.09 12 6.09Z"
            />
        </svg>
    );
}

type Props = {
    status?: string;
    canResetPassword: boolean;
    canSignInWithGoogle: boolean;
};

export default function Login({
    status,
    canResetPassword,
    canSignInWithGoogle,
}: Props) {
    const googleError = usePage().props.errors.google;

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
            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="space-y-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="space-y-5">
                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    Email or Employee Number
                                </Label>
                                <Input
                                    id="email"
                                    type="text"
                                    name="email"
                                    required
                                    autoFocus
                                    autoComplete="username"
                                    placeholder="Email or employee number"
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
                    </>
                )}
            </Form>
            <div className="mt-6 space-y-4">
                <div className="flex items-center gap-3 text-xs text-muted-foreground">
                    <Separator className="flex-1" />
                    Or continue with
                    <Separator className="flex-1" />
                </div>
                {googleError && (
                    <InputError message={googleError} role="alert" />
                )}
                {canSignInWithGoogle ? (
                    <Button
                        asChild
                        variant="outline"
                        className="h-11 w-full rounded-full border border-black bg-background text-foreground hover:bg-muted dark:border-white/70"
                    >
                        <a href={googleRedirect.url()}>
                            <GoogleIcon /> Continue with Google
                        </a>
                    </Button>
                ) : (
                    <Button
                        type="button"
                        variant="outline"
                        className="h-11 w-full rounded-full border border-black bg-background text-foreground dark:border-white/70"
                        disabled
                    >
                        <GoogleIcon /> Continue with Google
                    </Button>
                )}
                {!canSignInWithGoogle && (
                    <p className="text-center text-xs text-muted-foreground">
                        Google sign-in is currently unavailable.
                    </p>
                )}
            </div>
            <p className="mt-6 text-center text-sm leading-6 text-muted-foreground">
                Contact HRMO for an account invitation.
            </p>
        </>
    );
}

Login.layout = {
    title: 'Welcome back',
    description: 'Enter your credentials to access your municipal workspace.',
};

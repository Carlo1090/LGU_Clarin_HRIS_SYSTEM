import { router } from '@inertiajs/react';
import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useRef,
    useState,
    type ReactNode,
} from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { feedback } from '@/lib/feedback';

type Confirm = (message: string, requiredText?: string) => Promise<boolean>;
const Context = createContext<(typeof feedback & { confirm: Confirm }) | null>(
    null,
);

export function useFeedback() {
    const value = useContext(Context);
    if (!value) throw new Error('useFeedback requires FeedbackProvider');
    return value;
}

export function FeedbackProvider({ children }: { children: ReactNode }) {
    const [prompt, setPrompt] = useState<{
        message: string;
        requiredText?: string;
    } | null>(null);
    const [typed, setTyped] = useState('');
    const resolve = useRef<((answer: boolean) => void) | null>(null);
    const cancel = useRef<HTMLButtonElement>(null);
    const finish = useCallback((answer: boolean) => {
        resolve.current?.(answer);
        resolve.current = null;
        setPrompt(null);
    }, []);
    useEffect(() => {
        const off = router.on('navigate', () => finish(false));
        return () => {
            off();
            resolve.current?.(false);
        };
    }, [finish]);
    const confirm = useCallback<Confirm>(
        (message, requiredText) =>
            new Promise((done) => {
                resolve.current?.(false);
                resolve.current = done;
                setTyped('');
                setPrompt({ message, requiredText });
            }),
        [],
    );
    const value = useMemo(() => ({ ...feedback, confirm }), [confirm]);
    return (
        <Context.Provider value={value}>
            {children}
            <Dialog
                open={!!prompt}
                onOpenChange={(open) => {
                    if (!open) finish(false);
                }}
            >
                <DialogContent
                    onOpenAutoFocus={(event) => {
                        event.preventDefault();
                        cancel.current?.focus();
                    }}
                >
                    <DialogTitle>
                        {prompt?.requiredText
                            ? 'Confirm irreversible action'
                            : 'Confirm action'}
                    </DialogTitle>
                    <DialogDescription>{prompt?.message}</DialogDescription>
                    {prompt?.requiredText && (
                        <label className="grid gap-2 text-sm">
                            Type {prompt.requiredText} to confirm
                            <Input
                                value={typed}
                                autoComplete="off"
                                onChange={(e) => setTyped(e.target.value)}
                            />
                        </label>
                    )}
                    <DialogFooter>
                        <Button
                            ref={cancel}
                            variant="outline"
                            onClick={() => finish(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant={
                                prompt?.requiredText ? 'destructive' : 'default'
                            }
                            disabled={
                                !!prompt?.requiredText &&
                                typed !== prompt.requiredText
                            }
                            onClick={() => finish(true)}
                        >
                            Confirm
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </Context.Provider>
    );
}

export function CredentialsAcknowledgment({
    children,
    credentials,
    title = 'Employee account created',
}: {
    children: ReactNode;
    credentials: string;
    title?: string;
}) {
    const [open, setOpen] = useState(true);
    const [copied, setCopied] = useState(false);
    return (
        <Dialog open={open}>
            <DialogContent
                className="max-h-[90svh] overflow-auto [&>button:last-child]:hidden"
                onEscapeKeyDown={(e) => e.preventDefault()}
                onInteractOutside={(e) => e.preventDefault()}
            >
                <DialogTitle>{title}</DialogTitle>
                <DialogDescription>
                    Review the new credentials and share them privately with the
                    employee.
                </DialogDescription>
                <div className="space-y-2 break-words">{children}</div>
                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={async () => {
                            try {
                                await navigator.clipboard.writeText(
                                    credentials,
                                );
                                setCopied(true);
                            } catch {
                                feedback.error(
                                    'Copy failed. Select the credentials and copy them manually.',
                                );
                            }
                        }}
                    >
                        {copied ? 'Copied' : 'Copy Credentials'}
                    </Button>
                    <Button onClick={() => setOpen(false)}>Done</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

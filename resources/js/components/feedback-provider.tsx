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
import { Checkbox } from '@/components/ui/checkbox';
import { feedback } from '@/lib/feedback';

type Confirm = (message: string, destructiveLabel?: string) => Promise<boolean>;
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
        destructiveLabel?: string;
    } | null>(null);
    const [acknowledged, setAcknowledged] = useState(false);
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
        (message, destructiveLabel) =>
            new Promise((done) => {
                resolve.current?.(false);
                resolve.current = done;
                setAcknowledged(false);
                setPrompt({ message, destructiveLabel });
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
                        {prompt?.destructiveLabel
                            ? 'Confirm irreversible action'
                            : 'Confirm action'}
                    </DialogTitle>
                    <DialogDescription>{prompt?.message}</DialogDescription>
                    {prompt?.destructiveLabel && (
                        <label className="flex items-start gap-3 rounded-lg border bg-muted/40 p-3 text-sm">
                            <Checkbox
                                checked={acknowledged}
                                onCheckedChange={(value) =>
                                    setAcknowledged(value === true)
                                }
                            />
                            I understand the consequences and want to proceed.
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
                                prompt?.destructiveLabel
                                    ? 'destructive'
                                    : 'default'
                            }
                            disabled={
                                !!prompt?.destructiveLabel && !acknowledged
                            }
                            onClick={() => finish(true)}
                        >
                            {prompt?.destructiveLabel ?? 'Confirm'}
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

import { http } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import DailyTimeRecordPreview, {
    type DailyTimeRecordData,
} from '@/components/daily-time-record-preview';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

function PreviewContent({ url }: { url: string }) {
    const [request, setRequest] = useState({ url });
    const [result, setResult] = useState<{
        request: { url: string };
        preview?: DailyTimeRecordData;
        error?: string;
    }>();
    const busy = result?.request !== request;

    useEffect(() => {
        const controller = new AbortController();

        async function load() {
            try {
                const response = await http.getClient().request({
                    method: 'get',
                    url: request.url,
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                if (response.status !== 200) {
                    throw new Error('DTR preview request failed');
                }
                const preview = JSON.parse(
                    response.data,
                ) as DailyTimeRecordData;

                if (!controller.signal.aborted) {
                    setResult({ request, preview });
                }
            } catch {
                if (!controller.signal.aborted) {
                    setResult((previous) => ({
                        request,
                        preview: previous?.preview,
                        error: 'Could not load the DTR preview. Try again.',
                    }));
                }
            }
        }

        void load();

        return () => controller.abort();
    }, [request]);

    if (result?.preview) {
        return (
            <DailyTimeRecordPreview
                {...result.preview}
                busy={busy}
                error={busy ? undefined : result.error}
                onUpdate={(nextUrl) => setRequest({ url: nextUrl })}
            />
        );
    }

    return busy ? (
        <p role="status" className="text-sm text-muted-foreground">
            Loading DTR preview…
        </p>
    ) : (
        <div className="space-y-3">
            <p role="alert" className="text-sm text-destructive">
                {result.error}
            </p>
            <Button
                type="button"
                variant="outline"
                onClick={() => setRequest({ url })}
            >
                Try again
            </Button>
        </div>
    );
}

export default function DailyTimeRecordDialog({
    url,
    demo = false,
}: {
    url: string;
    demo?: boolean;
}) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    size={demo ? 'default' : 'sm'}
                >
                    {demo ? 'View DTR demo' : 'Preview DTR'}
                </Button>
            </DialogTrigger>
            <DialogContent
                aria-describedby={undefined}
                data-dtr-preview=""
                className="max-h-[90dvh] overflow-y-auto sm:max-w-3xl"
            >
                <DialogTitle className="pr-8 print:hidden">
                    {demo
                        ? 'Daily Time Record demo'
                        : 'Daily Time Record preview'}
                </DialogTitle>
                <PreviewContent url={url} />
            </DialogContent>
        </Dialog>
    );
}

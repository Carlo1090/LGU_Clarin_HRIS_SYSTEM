import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { feedback } from '@/lib/feedback';
import { toast } from 'sonner';
import type { FlashToast } from '@/types/ui';

export function useFlashToast(): void {
    useEffect(() => {
        const stopFlash = router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash;
            const data = flash?.toast as FlashToast | undefined;

            if (!data) {
                return;
            }

            if (data.type === 'success')
                feedback.success(
                    data.message,
                    undefined,
                    data.kind,
                    data.description,
                );
            else if (data.type === 'error') feedback.error(data.message);
            else toast[data.type](data.message);
        });

        const connectionId = 'hris-connection';
        const offline = () =>
            toast.warning('You are offline', {
                id: connectionId,
                duration: Infinity,
                description: 'Reconnect before submitting changes.',
            });
        const online = () =>
            toast.info('Network connection available', {
                id: connectionId,
                duration: 4000,
                description:
                    'Review any interrupted action before trying again.',
            });
        const stopBefore = router.on('before', ({ detail: { visit } }) => {
            if (!navigator.onLine && visit.method !== 'get') {
                offline();
                return false;
            }
        });
        const stopNetwork = router.on('networkError', () => {
            if (navigator.onLine) {
                toast.warning('Connection interrupted', {
                    id: connectionId,
                    duration: Infinity,
                    description:
                        'The server response did not arrive. Check the record before submitting again.',
                });
            } else offline();
            return false;
        });
        if (!navigator.onLine) offline();
        window.addEventListener('offline', offline);
        window.addEventListener('online', online);
        return () => {
            stopFlash();
            stopBefore();
            stopNetwork();
            window.removeEventListener('offline', offline);
            window.removeEventListener('online', online);
        };
    }, []);
}

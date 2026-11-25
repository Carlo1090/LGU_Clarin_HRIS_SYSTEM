import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { feedback } from '@/lib/feedback';
import { toast } from 'sonner';
import type { FlashToast } from '@/types/ui';

export function useFlashToast(): void {
    useEffect(() => {
        return router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash;
            const data = flash?.toast as FlashToast | undefined;

            if (!data) {
                return;
            }

            if (data.type === 'success' || data.type === 'error')
                feedback[data.type](data.message);
            else toast[data.type](data.message);
        });
    }, []);
}

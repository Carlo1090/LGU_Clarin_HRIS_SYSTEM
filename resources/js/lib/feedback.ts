import { toast } from 'sonner';

type Id = string | number;
export const feedback = {
    success(message: string, id?: Id) {
        return toast.success(message, {
            id,
            duration: 4000,
            dismissible: true,
            closeButton: true,
            action: undefined,
        });
    },
    error(message: string, retry?: () => void, id?: Id) {
        return toast.error(message, {
            id,
            duration: Infinity,
            dismissible: true,
            closeButton: true,
            action: retry ? { label: 'Retry', onClick: retry } : undefined,
        });
    },
    undo(message: string, restore: () => void) {
        return toast(message, {
            duration: 6000,
            action: { label: 'Undo', onClick: restore },
        });
    },
    progress(message: string) {
        const id = toast.loading(message, {
            duration: Infinity,
            dismissible: false,
            closeButton: false,
        });
        return {
            success: (result: string) => feedback.success(result, id),
            error: (result: string, retry?: () => void) =>
                feedback.error(result, retry, id),
            cancel: () => toast.dismiss(id),
        };
    },
};

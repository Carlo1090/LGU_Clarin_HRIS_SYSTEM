import { toast } from 'sonner';

export type SuccessKind = 'create' | 'update' | 'delete';
type Id = string | number;
export const feedback = {
    success(
        message: string,
        id?: Id,
        kind: SuccessKind = 'update',
        description?: string,
    ) {
        return toast.success(message, {
            id,
            className: `hr-toast--${kind}`,
            description,
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
        return toast.success(message, {
            className: 'hr-toast--delete',
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

import './toast.css';
import { CircleCheck, CircleX, Info, LoaderCircle, TriangleAlert } from 'lucide-react';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { useAppearance } from '@/hooks/use-appearance';
import { Toaster as Sonner, type ToasterProps } from 'sonner';

function Toaster({ ...props }: ToasterProps) {
    const { appearance } = useAppearance();

    useFlashToast();

    return (
        <Sonner
            theme={appearance}
            className="toaster group"
            position="top-right"
            closeButton
            toastOptions={{ className: 'hr-toast' }}
            icons={{success: <CircleCheck />, error: <CircleX />, warning: <TriangleAlert />, info: <Info />, loading: <LoaderCircle className="animate-spin" />}}
            {...props}
        />
    );
}

export { Toaster };

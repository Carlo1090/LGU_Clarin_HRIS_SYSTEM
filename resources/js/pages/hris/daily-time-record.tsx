import { Head, usePage } from '@inertiajs/react';
import DailyTimeRecordPreview, {
    type DailyTimeRecordData,
} from '@/components/daily-time-record-preview';

export default function DailyTimeRecord(data: DailyTimeRecordData) {
    const { errors } = usePage().props;

    return (
        <main className="mx-auto max-w-3xl p-4 sm:p-8 print:max-w-none print:p-0">
            <Head
                title={
                    data.demo
                        ? 'Daily Time Record Demo'
                        : 'Daily Time Record Preview'
                }
            />
            <DailyTimeRecordPreview
                {...data}
                error={errors.month || errors.period}
            />
        </main>
    );
}

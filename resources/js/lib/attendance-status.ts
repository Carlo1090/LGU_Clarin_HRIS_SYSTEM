export function attendanceStatusLabel(status: string) {
    switch (status) {
        case 'absent':
            return 'No punches on a scheduled workday';
        case 'incomplete':
            return 'Incomplete punches';
        case 'no_schedule':
            return 'No schedule';
        default:
            return status.replaceAll('_', ' ');
    }
}

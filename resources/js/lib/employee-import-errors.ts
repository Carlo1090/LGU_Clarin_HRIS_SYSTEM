export function downloadEmployeeImportErrors(
    rows: { row: number; errors: string[] }[],
) {
    const csv = [
        'Excel row,Errors',
        ...rows.map(
            ({ row, errors }) =>
                `${row},"${errors.join(' ').replaceAll('"', '""')}"`,
        ),
    ].join('\r\n');
    const url = URL.createObjectURL(
        new Blob([csv], { type: 'text/csv;charset=utf-8' }),
    );
    const link = document.createElement('a');
    link.href = url;
    link.download = 'employee-import-errors.csv';
    link.click();
    setTimeout(() => URL.revokeObjectURL(url), 0);
}

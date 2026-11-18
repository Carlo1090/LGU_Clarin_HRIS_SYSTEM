import { Head, Link } from "@inertiajs/react";

type Row = { id: number; department_code: string; department_name: string; [key: string]: string | number | null };
type Props = {
    title: string;
    resources: { key: string; title: string }[];
    fields: { name: string; label: string }[];
    records: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };
};

function displayValue(name: string, value: string | number | null | undefined) {
    if (value === null || value === undefined || value === "") return "—";
    if (["authorized_annual_salary", "actual_annual_salary"].includes(name)) return Number(value).toFixed(2);
    if (["date_of_birth", "date_of_original_appointment", "date_of_last_promotion_appointment"].includes(name)) return String(value).slice(0, 10);
    return String(value);
}

export default function PlantillaItems({ title, resources, fields, records }: Props) {
    const columns = [{ name: "department_name", label: "Department" }, ...fields, { name: "employee_number", label: "Employee Number" }];

    return (
        <>
            <Head title={title} />
            <main className="space-y-6 p-4 md:p-8">
                <header>
                    <h1 className="text-2xl font-semibold">{title}</h1>
                </header>
                <nav aria-label="HRIS modules" className="flex flex-wrap gap-2">
                    {resources.map((item) => <Link key={item.key} href={`/hris/${item.key}`} className={`rounded-md border px-3 py-2 text-sm ${item.key === "plantilla-items" ? "bg-primary text-primary-foreground" : "hover:bg-muted"}`}>{item.title}</Link>)}
                </nav>

                <section className="space-y-3">
                    <div>
                        <h2 className="text-lg font-semibold">Plantilla records</h2>
                    </div>
                    <div className="overflow-x-auto rounded-lg border">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted/60"><tr>
                                {columns.map((column) => <th key={column.name} className="whitespace-nowrap px-3 py-3 font-medium">{column.label}</th>)}
                            </tr></thead>
                            <tbody>
                                {records.data.map((row) => <tr key={row.id} className="border-t align-top">
                                {columns.map((column) => <td key={column.name} className="max-w-64 whitespace-nowrap px-3 py-3">{displayValue(column.name, row[column.name])}</td>)}
                                </tr>)}
                                {!records.data.length && <tr><td colSpan={columns.length} className="px-3 py-8 text-center text-muted-foreground">No Plantilla records found.</td></tr>}
                            </tbody>
                        </table>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {records.links.map((link, index) => <Link key={`${link.label}-${index}`} href={link.url ?? "#"} preserveScroll className={`rounded border px-3 py-1 text-sm ${link.active ? "bg-primary text-primary-foreground" : ""} ${!link.url ? "pointer-events-none opacity-50" : ""}`}>{link.label.replace(/&laquo;|&raquo;/g, "").trim()}</Link>)}
                    </div>
                </section>
            </main>
        </>
    );
}

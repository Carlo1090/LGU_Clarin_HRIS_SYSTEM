import { router, usePage } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Column = { name: string; label: string };
type SearchProps = {
    columns: Column[];
    label: string;
    initialQuery?: string;
    initialColumn?: string;
    onSearch: (query: string, column: string) => void;
};

function SearchForm({
    columns,
    label,
    initialQuery = '',
    initialColumn = '',
    onSearch,
}: SearchProps) {
    const [query, setQuery] = useState(initialQuery);
    const [column, setColumn] = useState(initialColumn);
    function submit(event: FormEvent) {
        event.preventDefault();
        onSearch(query.trim(), column);
    }
    return (
        <form
            aria-label={label}
            role="search"
            onSubmit={submit}
            className="flex flex-wrap items-center gap-2 border-y bg-muted/30 p-3 print:hidden"
        >
            <Input
                type="search"
                maxLength={120}
                aria-label={label}
                placeholder={label}
                value={query}
                onChange={(event) => setQuery(event.target.value)}
                className="min-w-32 flex-1 bg-card"
            />
            <select
                aria-label="Search column"
                value={column}
                onChange={(event) => setColumn(event.target.value)}
                className="h-9 max-w-full rounded-md border border-input bg-card px-2 text-sm"
            >
                <option value="">All columns</option>
                {columns.map((field) => (
                    <option key={field.name} value={field.name}>
                        {field.label}
                    </option>
                ))}
            </select>
            <Button
                type="submit"
                size="icon"
                aria-label="Apply search"
                title="Apply search"
            >
                <Search aria-hidden="true" />
            </Button>
            {(initialQuery || query || initialColumn || column) && (
                <Button
                    type="button"
                    size="icon"
                    variant="outline"
                    aria-label="Clear search"
                    title="Clear search"
                    onClick={() => {
                        setQuery('');
                        setColumn('');
                        onSearch('', '');
                    }}
                >
                    <X aria-hidden="true" />
                </Button>
            )}
        </form>
    );
}

export function DirectorySearch({
    columns,
    label,
}: {
    columns: Column[];
    label: string;
}) {
    const page = usePage();
    const url = new URL(page.url, 'http://localhost');
    function search(query: string, column: string) {
        const params = new URLSearchParams(url.search);
        params.delete('page');
        for (const [key, value] of [
            ['q', query],
            ['column', column],
        ]) {
            if (value) params.set(key, value);
            else params.delete(key);
        }
        router.get(url.pathname, Object.fromEntries(params), {
            preserveScroll: true,
            preserveState: true,
        });
    }
    return (
        <SearchForm
            key={page.url}
            initialQuery={url.searchParams.get('q') ?? ''}
            initialColumn={url.searchParams.get('column') ?? ''}
            onSearch={search}
            label={label}
            columns={columns}
        />
    );
}

export function useTableSearch<T>(
    rows: T[],
    columns: string[],
    values: (row: T) => (string | number | boolean | null | undefined)[],
    label = 'Search this page',
) {
    const [filter, setFilter] = useState({ query: '', column: '' });
    const term = filter.query.toLocaleLowerCase();
    const filtered = term
        ? rows.filter((row) => {
              const cells = values(row);
              const selected =
                  filter.column === '' ? cells : [cells[Number(filter.column)]];
              return selected.some((value) =>
                  String(value ?? '')
                      .toLocaleLowerCase()
                      .includes(term),
              );
          })
        : rows;
    const toolbar = (
        <div className="print:hidden">
            <SearchForm
                label={label}
                columns={columns.map((name, i) => ({
                    name: String(i),
                    label: name,
                }))}
                initialQuery={filter.query}
                initialColumn={filter.column}
                onSearch={(query, column) => setFilter({ query, column })}
            />
            {term && (
                <p
                    role="status"
                    className="px-3 py-1.5 text-xs text-muted-foreground"
                >
                    {filtered.length} of {rows.length} matching
                </p>
            )}
        </div>
    );
    return { rows: filtered, toolbar };
}

import { CheckIcon, ChevronDownIcon, Search } from 'lucide-react';
import { useMemo, useRef, useState } from 'react';
import type { KeyboardEvent } from 'react';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

export type SearchableOption = { value: string | number; label: string };

type Props = {
    id?: string;
    value: string | number;
    options: SearchableOption[];
    onChange: (value: string) => void;
    /** Label of the empty choice, e.g. "None" or "Select booking (optional)". */
    placeholder: string;
    searchPlaceholder?: string;
    invalid?: boolean;
};

/**
 * A select with a search box, for long lists such as bookings. The trigger looks like NativeSelect
 * (bare, one hairline beneath); typing filters by any word in the label, arrows move, Enter picks.
 */
export function SearchableSelect({ id, value, options, onChange, placeholder, searchPlaceholder = 'Search…', invalid }: Props) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [active, setActive] = useState(0);
    const listRef = useRef<HTMLDivElement>(null);

    const selected = options.find((option) => String(option.value) === String(value));

    // The empty choice comes first, then the options matching every typed word.
    const choices = useMemo(() => {
        const words = query.toLowerCase().split(/\s+/).filter(Boolean);
        const matches = options.filter((option) => words.every((word) => option.label.toLowerCase().includes(word)));
        return words.length ? matches : [{ value: '', label: placeholder }, ...matches];
    }, [options, query, placeholder]);

    const toggle = (next: boolean) => {
        setOpen(next);
        if (next) {
            setQuery('');
            setActive(Math.max(0, options.findIndex((option) => String(option.value) === String(value)) + 1));
        }
    };

    const pick = (option: SearchableOption) => {
        onChange(String(option.value));
        setOpen(false);
    };

    const move = (index: number) => {
        setActive(index);
        listRef.current?.querySelector(`[data-index="${index}"]`)?.scrollIntoView({ block: 'nearest' });
    };

    const onKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            move(Math.min(active + 1, choices.length - 1));
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            move(Math.max(active - 1, 0));
        } else if (event.key === 'Enter') {
            // Keep Enter from submitting the surrounding form.
            event.preventDefault();
            if (choices[active]) pick(choices[active]);
        }
    };

    return (
        <Popover open={open} onOpenChange={toggle}>
            <PopoverTrigger asChild>
                <button
                    type="button"
                    id={id}
                    role="combobox"
                    aria-expanded={open}
                    aria-invalid={invalid}
                    className="relative flex h-11 w-full min-w-0 items-center border-0 border-b border-input bg-transparent py-1 pr-7 pl-0 text-left text-base font-medium text-foreground transition-colors duration-200 outline-none focus-visible:border-primary aria-invalid:border-destructive md:h-9 md:text-body"
                >
                    <span className={cn('truncate', !selected && 'text-(--nx-fg-4)')}>{selected?.label ?? placeholder}</span>
                    <ChevronDownIcon className="pointer-events-none absolute top-1/2 right-0.5 size-3.5 -translate-y-1/2 text-muted-foreground opacity-60" />
                </button>
            </PopoverTrigger>
            <PopoverContent align="start" className="w-(--radix-popover-trigger-width) min-w-64 p-0">
                <div className="flex items-center gap-2 border-b px-3">
                    <Search className="size-4 shrink-0 text-muted-foreground" />
                    <input
                        autoFocus
                        value={query}
                        onChange={(e) => {
                            setQuery(e.target.value);
                            setActive(0);
                        }}
                        onKeyDown={onKeyDown}
                        placeholder={searchPlaceholder}
                        aria-label={searchPlaceholder}
                        className="h-11 w-full bg-transparent text-base outline-none placeholder:text-(--nx-fg-4) md:h-9 md:text-sm"
                    />
                </div>
                <div ref={listRef} role="listbox" className="max-h-64 overflow-y-auto p-1">
                    {choices.length === 0 ? (
                        <p className="px-2 py-6 text-center text-sm text-muted-foreground">No matches.</p>
                    ) : (
                        choices.map((option, index) => {
                            const isSelected = String(option.value) === String(value);
                            return (
                                <button
                                    key={`${option.value}-${index}`}
                                    type="button"
                                    role="option"
                                    aria-selected={isSelected}
                                    data-index={index}
                                    onMouseEnter={() => setActive(index)}
                                    onClick={() => pick(option)}
                                    className={cn(
                                        'flex w-full items-center gap-2 rounded-sm px-2 py-2.5 text-left text-sm md:py-1.5',
                                        index === active && 'bg-accent text-accent-foreground',
                                        option.value === '' && 'text-muted-foreground',
                                    )}
                                >
                                    <CheckIcon className={cn('size-4 shrink-0', isSelected ? 'opacity-100' : 'opacity-0')} />
                                    <span className="truncate">{option.label}</span>
                                </button>
                            );
                        })
                    )}
                </div>
            </PopoverContent>
        </Popover>
    );
}

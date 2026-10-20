import { Plus } from 'lucide-react';
import { useState } from 'react';
import { FormField } from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { usePermissions } from '@/hooks/use-permissions';
import { store } from '@/routes/expenses/categories';
import type { ExpenseCategory } from './expense-meta';

type Props = {
    id?: string;
    categories: ExpenseCategory[];
    value: string;
    onChange: (value: string) => void;
    invalid?: boolean;
};

/**
 * The category picker on the expense form. Users who may edit expenses get a "New category"
 * button: a short dialog adds the category without leaving the form, then lists and selects it.
 */
export function CategorySelect({ id = 'category', categories, value, onChange, invalid }: Props) {
    const { can } = usePermissions();
    const [created, setCreated] = useState<ExpenseCategory[]>([]);
    const [open, setOpen] = useState(false);
    // Server order (alphabetical, "Other" last) with new categories added before "Other".
    const options = [...categories.filter((c) => c.value !== 'other'), ...created, ...categories.filter((c) => c.value === 'other')];

    return (
        <div className="flex items-end gap-2">
            <div className="min-w-0 flex-1">
                <NativeSelect id={id} value={value} onChange={(e) => onChange(e.target.value)} aria-invalid={invalid}>
                    <option value="">Select category</option>
                    {options.map((c) => (
                        <option key={c.value} value={c.value}>
                            {c.label}
                        </option>
                    ))}
                </NativeSelect>
            </div>
            {can('edit-expenses') && (
                <>
                    <Button type="button" variant="outline" onClick={() => setOpen(true)} className="shrink-0">
                        <Plus strokeWidth={1.6} />
                        <span className="max-sm:sr-only">New category</span>
                    </Button>
                    <NewCategoryDialog
                        open={open}
                        onOpenChange={setOpen}
                        onCreated={(category) => {
                            setCreated((list) => [...list, category]);
                            onChange(category.value);
                        }}
                    />
                </>
            )}
        </div>
    );
}

const xsrfToken = () => decodeURIComponent(document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/)?.[1] ?? '');

function NewCategoryDialog({
    open,
    onOpenChange,
    onCreated,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onCreated: (category: ExpenseCategory) => void;
}) {
    const [label, setLabel] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [processing, setProcessing] = useState(false);

    const reset = (next: boolean) => {
        if (!next) {
            setLabel('');
            setError(null);
        }
        onOpenChange(next);
    };

    // A nested form would submit the outer expense form, so this one is submitted by hand.
    const submit = async () => {
        setProcessing(true);
        setError(null);
        try {
            const response = await fetch(store().url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
                body: JSON.stringify({ label }),
            });
            if (response.status === 422) {
                const body = await response.json();
                setError(body.errors?.label?.[0] ?? 'Check the category name.');
                return;
            }
            if (!response.ok) {
                setError('The category could not be saved. Try again.');
                return;
            }
            const { category } = await response.json();
            onCreated(category);
            reset(false);
        } catch {
            setError('No connection. Check your signal and try again.');
        } finally {
            setProcessing(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={reset}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>New expense category</DialogTitle>
                    <DialogDescription>
                        It is selected for this expense and offered on every expense from now on. On the profit &amp; loss report it is counted under
                        Other.
                    </DialogDescription>
                </DialogHeader>

                <FormField label="Category name" required htmlFor="new_category_label" error={error ?? undefined}>
                    <Input
                        id="new_category_label"
                        value={label}
                        onChange={(e) => {
                            setLabel(e.target.value);
                            setError(null);
                        }}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                submit();
                            }
                        }}
                        placeholder="e.g., Tyres"
                        autoFocus
                        aria-invalid={!!error}
                    />
                </FormField>

                <DialogFooter>
                    <Button type="button" variant="ghost" onClick={() => reset(false)}>
                        Cancel
                    </Button>
                    <Button type="button" onClick={submit} disabled={processing}>
                        {processing && <Spinner />}
                        Save category
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

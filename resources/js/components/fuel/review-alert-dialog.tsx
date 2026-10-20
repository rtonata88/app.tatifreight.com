import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { FormField } from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { review } from '@/routes/fuel-alerts';

/** "Mark reviewed" on a fuel alert: a short note on what was found, kept for the owner. */
export function ReviewAlertDialog({ alertId, summary }: { alertId: number; summary: string }) {
    const [open, setOpen] = useState(false);
    const form = useForm({ review_note: '' });

    const toggle = (next: boolean) => {
        if (!next) {
            form.reset();
            form.clearErrors();
        }
        setOpen(next);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(review(alertId).url, { preserveScroll: true, onSuccess: () => toggle(false) });
    };

    return (
        <Dialog open={open} onOpenChange={toggle}>
            <DialogTrigger asChild>
                <Button type="button" variant="outline" size="sm">
                    Mark reviewed
                </Button>
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit} className="grid gap-4">
                    <DialogHeader>
                        <DialogTitle>Review fuel alert</DialogTitle>
                        <DialogDescription>{summary}</DialogDescription>
                    </DialogHeader>

                    <FormField label="What did you find?" required htmlFor={`review_note_${alertId}`} error={form.errors.review_note}>
                        <Textarea
                            id={`review_note_${alertId}`}
                            rows={3}
                            value={form.data.review_note}
                            onChange={(e) => form.setData('review_note', e.target.value)}
                            placeholder="e.g., Spoke to the driver: long idle at the border post."
                            autoFocus
                            aria-invalid={!!form.errors.review_note}
                        />
                    </FormField>

                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={() => toggle(false)}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            Save review
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

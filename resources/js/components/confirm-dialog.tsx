import type { ReactNode } from 'react';
import { useState } from 'react';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';

type ConfirmDialogProps = {
    /** The element that opens the dialog, e.g. a delete button. */
    trigger: ReactNode;
    title?: string;
    description?: ReactNode;
    confirmLabel?: string;
    destructive?: boolean;
    /** Run the action. Call `done()` when the request finishes to close the dialog. */
    onConfirm: (done: () => void) => void;
};

/**
 * Replaces the old wire:confirm="Are you sure…" prompts.
 *
 * <ConfirmDialog
 *   trigger={<Button variant="destructive" size="sm">Delete</Button>}
 *   description="This vehicle will be permanently deleted."
 *   onConfirm={(done) => router.delete(destroy(vehicle.id).url, { onFinish: done })}
 * />
 */
export function ConfirmDialog({
    trigger,
    title = 'Are you sure?',
    description,
    confirmLabel = 'Delete',
    destructive = true,
    onConfirm,
}: ConfirmDialogProps) {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    return (
        <AlertDialog open={open} onOpenChange={setOpen}>
            <AlertDialogTrigger asChild>{trigger}</AlertDialogTrigger>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{title}</AlertDialogTitle>
                    {description && <AlertDialogDescription>{description}</AlertDialogDescription>}
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel disabled={processing}>Cancel</AlertDialogCancel>
                    <Button
                        variant={destructive ? 'destructive' : 'default'}
                        disabled={processing}
                        onClick={() => {
                            setProcessing(true);
                            onConfirm(() => {
                                setProcessing(false);
                                setOpen(false);
                            });
                        }}
                    >
                        {confirmLabel}
                    </Button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}

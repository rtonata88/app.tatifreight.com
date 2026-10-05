import { useEffect, useState } from 'react';
import { Input } from '@/components/ui/input';

type FileInputProps = {
    id?: string;
    accept?: string;
    file: File | null;
    onChange: (file: File | null) => void;
    /** URL of the file already stored, shown until a new one is picked. */
    currentUrl?: string | null;
    /** Show image previews (current and newly picked). */
    preview?: boolean;
    'aria-invalid'?: boolean;
};

/** File picker with an optional image preview — replaces wire:model uploads + temporaryUrl(). */
export function FileInput({ id, accept, file, onChange, currentUrl, preview = false, ...rest }: FileInputProps) {
    const [objectUrl, setObjectUrl] = useState<string | null>(null);

    useEffect(() => {
        if (!file || !preview) {
            setObjectUrl(null);
            return;
        }
        const url = URL.createObjectURL(file);
        setObjectUrl(url);
        return () => URL.revokeObjectURL(url);
    }, [file, preview]);

    return (
        <div className="space-y-2">
            {preview && currentUrl && !objectUrl && (
                <div>
                    <p className="mb-1 text-xs text-muted-foreground">Current:</p>
                    <img src={currentUrl} alt="" className="h-32 rounded border object-contain" />
                </div>
            )}
            <Input id={id} type="file" accept={accept} onChange={(e) => onChange(e.target.files?.[0] ?? null)} {...rest} />
            {objectUrl && (
                <div>
                    <p className="mb-1 text-xs text-green-600 dark:text-green-400">New upload preview:</p>
                    <img src={objectUrl} alt="" className="h-32 rounded border object-contain" />
                </div>
            )}
        </div>
    );
}

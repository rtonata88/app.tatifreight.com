import { Camera, Paperclip, X } from 'lucide-react';
import { useEffect, useId, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { compressImage } from '@/lib/compress-image';
import { cn } from '@/lib/utils';

type FileInputProps = {
    id?: string;
    accept?: string;
    file: File | null;
    onChange: (file: File | null) => void;
    /** URL of the file already stored, shown until a new one is picked. */
    currentUrl?: string | null;
    /** Show image previews (current and newly picked). */
    preview?: boolean;
    /** Offer a "Take photo" button that opens the rear camera. Defaults to on when images are accepted. */
    camera?: boolean;
    'aria-invalid'?: boolean;
};

const formatSize = (bytes: number) => (bytes < 1_000_000 ? `${Math.max(1, Math.round(bytes / 1000))} KB` : `${(bytes / 1_000_000).toFixed(1)} MB`);

/**
 * File picker built for phones: "Take photo" opens the camera, "Choose file" opens photos and
 * files. Photos are shrunk before upload so they fit the server's size limits.
 */
export function FileInput({ id, accept, file, onChange, currentUrl, preview = false, camera, ...rest }: FileInputProps) {
    const fallbackId = useId();
    const inputId = id ?? fallbackId;
    const pickRef = useRef<HTMLInputElement>(null);
    const cameraRef = useRef<HTMLInputElement>(null);
    const [objectUrl, setObjectUrl] = useState<string | null>(null);
    const [preparing, setPreparing] = useState(false);
    const acceptsImages = !accept || accept.includes('image') || /\.(jpe?g|png|webp)/i.test(accept);
    const showCamera = camera ?? acceptsImages;

    useEffect(() => {
        if (!file || !preview || !file.type.startsWith('image/')) {
            setObjectUrl(null);
            return;
        }
        const url = URL.createObjectURL(file);
        setObjectUrl(url);
        return () => URL.revokeObjectURL(url);
    }, [file, preview]);

    const pick = async (picked: File | undefined) => {
        if (!picked) return;
        setPreparing(true);
        onChange(await compressImage(picked));
        setPreparing(false);
    };

    const clear = () => {
        onChange(null);
        if (pickRef.current) pickRef.current.value = '';
        if (cameraRef.current) cameraRef.current.value = '';
    };

    return (
        <div className="space-y-3">
            {preview && currentUrl && !objectUrl && (
                <div>
                    <p className="mb-1 text-xs text-muted-foreground">Current</p>
                    <img src={currentUrl} alt="" className="h-32 rounded border object-contain" />
                </div>
            )}

            <input
                ref={pickRef}
                id={inputId}
                type="file"
                accept={accept}
                className="sr-only"
                tabIndex={-1}
                onChange={(e) => pick(e.target.files?.[0])}
                {...rest}
            />
            {showCamera && (
                <input
                    ref={cameraRef}
                    type="file"
                    accept="image/*"
                    capture="environment"
                    className="sr-only"
                    tabIndex={-1}
                    aria-hidden="true"
                    onChange={(e) => pick(e.target.files?.[0])}
                />
            )}

            <div className={cn('grid gap-2', showCamera ? 'grid-cols-2 md:flex' : 'grid-cols-1 md:flex')}>
                {showCamera && (
                    <Button type="button" variant="outline" onClick={() => cameraRef.current?.click()} disabled={preparing}>
                        <Camera strokeWidth={1.6} /> Take photo
                    </Button>
                )}
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => pickRef.current?.click()}
                    disabled={preparing}
                    aria-invalid={rest['aria-invalid']}
                    aria-controls={inputId}
                >
                    <Paperclip strokeWidth={1.6} /> {file || currentUrl ? 'Replace file' : 'Choose file'}
                </Button>
            </div>

            {(preparing || file) && (
                <div className="flex min-w-0 items-center gap-2 border-b pb-2 text-body">
                    <span className="min-w-0 flex-1 truncate font-medium">{preparing ? 'Preparing photo…' : file?.name}</span>
                    {file && !preparing && <span className="shrink-0 font-mono text-xs text-muted-foreground">{formatSize(file.size)}</span>}
                    {file && !preparing && (
                        <Button type="button" variant="ghost" size="icon-sm" onClick={clear} aria-label="Remove file">
                            <X strokeWidth={1.6} />
                        </Button>
                    )}
                </div>
            )}

            {objectUrl && <img src={objectUrl} alt="" className="h-32 rounded border object-contain" />}
        </div>
    );
}

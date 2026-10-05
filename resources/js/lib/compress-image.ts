/**
 * Shrinks a phone photo before upload so it fits the server's image limits (2MB for most uploads)
 * and costs less mobile data. Non-images (PDFs) and images already small enough pass through.
 */
export async function compressImage(file: File, { maxSide = 1600, quality = 0.8, maxBytes = 1_500_000 } = {}): Promise<File> {
    if (!file.type.startsWith('image/') || file.type === 'image/gif' || file.type === 'image/svg+xml' || file.size <= maxBytes) {
        return file;
    }

    try {
        const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
        const scale = Math.min(1, maxSide / Math.max(bitmap.width, bitmap.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bitmap.width * scale);
        canvas.height = Math.round(bitmap.height * scale);
        canvas.getContext('2d')?.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        bitmap.close();

        const blob = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
        if (!blob || blob.size >= file.size) {
            return file;
        }

        const name = file.name.replace(/\.[^.]+$/, '') + '.jpg';

        return new File([blob], name, { type: 'image/jpeg', lastModified: file.lastModified });
    } catch {
        // HEIC or another format the browser cannot decode: send the original and let the server decide.
        return file;
    }
}

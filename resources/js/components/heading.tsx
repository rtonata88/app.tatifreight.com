export default function Heading({
    title,
    description,
    variant = 'default',
}: {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
}) {
    return (
        <header className={variant === 'small' ? '' : 'mb-8 space-y-0.5'}>
            <h2
                className={
                    variant === 'small'
                        ? 'mb-0.5 text-base font-semibold'
                        : 'text-2xl leading-tight font-bold tracking-[-0.015em] sm:text-[28px]'
                }
            >
                {title}
            </h2>
            {description && (
                <p className="mt-2 text-sm text-pretty text-muted-foreground">{description}</p>
            )}
        </header>
    );
}

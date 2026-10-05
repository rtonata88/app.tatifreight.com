import { usePage } from '@inertiajs/react';
import { Wordmark } from '@/components/wordmark';
import type { AuthLayoutProps } from '@/types';

/*
 * Nexus auth frame (shared with Tweya): a two-panel split. Left, a warm, low-saturation
 * photograph on ink carrying the wordmark, one line of positioning and an accent rule. Right,
 * the form in a 380px column. Ink literals are used on the left because the Slate theme
 * re-points the ink tokens to light surfaces.
 */
const PHOTO = {
    src: '/images/auth/login.jpg',
    alt: 'A tar road crossing a dry Namibian plain towards distant mountains',
    credit: 'Mareike Kramper',
    href: 'https://unsplash.com/photos/tKUujgcer44',
};

export default function AuthSplitLayout({ children, title, description }: AuthLayoutProps) {
    const { company } = usePage().props;

    return (
        <div className="grid min-h-svh bg-background lg:grid-cols-2">
            <aside className="relative hidden overflow-hidden bg-[#1A1813] lg:block">
                <img src={PHOTO.src} alt={PHOTO.alt} className="absolute inset-0 size-full object-cover object-[50%_65%] saturate-[.7]" />
                <div className="absolute inset-0 bg-[linear-gradient(to_top,rgba(26,24,19,0.94)_0%,rgba(26,24,19,0.62)_45%,rgba(26,24,19,0.28)_100%)]" />
                <div className="relative flex h-full flex-col justify-between p-14">
                    <div className="flex">
                        <Wordmark size="panel" light />
                    </div>
                    <div>
                        <p className="max-w-[440px] text-[26px] leading-[1.35] font-medium tracking-[-0.015em] text-pretty text-[#F2EDE3]">
                            Vehicles, bookings and billing for a transport business, in one ledger.
                        </p>
                        <div className="my-6 max-w-[440px] border-t border-(--nx-rule-brass)" />
                        <p className="max-w-[440px] text-xs leading-relaxed text-[#C8BDA3]">
                            What you can see and change is set by your role.
                        </p>
                    </div>
                    <div className="flex items-end justify-between gap-6 font-mono text-[11px] text-[#8A8068]">
                        <span>{company.name}</span>
                        <a href={PHOTO.href} target="_blank" rel="noreferrer" className="transition-colors hover:text-[#C8BDA3]">
                            Photo: {PHOTO.credit}, Unsplash
                        </a>
                    </div>
                </div>
            </aside>

            <main className="flex items-center justify-center px-6 py-14 sm:px-10">
                <div className="w-full max-w-[380px]">
                    <div className="mb-10 flex lg:hidden">
                        <Wordmark size="panel" />
                    </div>
                    <h1 className="text-[28px] leading-tight font-bold tracking-[-0.015em]">{title}</h1>
                    {description && <p className="mt-2 text-sm text-pretty text-muted-foreground">{description}</p>}
                    <div className="mt-10">{children}</div>
                </div>
            </main>
        </div>
    );
}

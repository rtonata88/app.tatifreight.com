import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import type { User } from '@/types';

export function UserInfo({
    user,
    showEmail = false,
}: {
    user: User;
    showEmail?: boolean;
}) {
    const getInitials = useInitials();

    return (
        <>
            <Avatar className="size-[30px] shrink-0 overflow-hidden rounded-full">
                <AvatarImage src={user.avatar} alt={user.name} />
                <AvatarFallback className="bg-(--nx-brass-wash-2) text-micro font-semibold text-(--nx-brass-lo)">
                    {getInitials(user.name)}
                </AvatarFallback>
            </Avatar>
            <div className="grid min-w-0 flex-1 text-left leading-tight">
                <span className="truncate text-body font-semibold">{user.name}</span>
                {showEmail && (
                    <span className="truncate text-micro text-muted-foreground">
                        {user.email}
                    </span>
                )}
            </div>
        </>
    );
}

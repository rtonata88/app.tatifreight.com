import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { ucfirst } from '@/components/admin/role-badge';
import { FormActions } from '@/components/form-actions';
import { FormField } from '@/components/form-field';
import { FormSection } from '@/components/form-section';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { index, store, update } from '@/routes/users';

export type RoleOption = { name: string; permissions_count: number };

type Props = {
    roles: RoleOption[];
    /** Present when editing. */
    user?: { id: number; name: string; email: string; role: string };
};

export function UserForm({ roles, user }: Props) {
    const editing = Boolean(user);

    const { data, setData, errors, processing, post, put } = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
        password: '',
        password_confirmation: '',
        role: user?.role ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (user) {
            put(update(user.id).url, { preserveScroll: true });
        } else {
            post(store().url, { preserveScroll: true });
        }
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <FormSection title="User information">
                <FormField label="Full name" required htmlFor="name" error={errors.name}>
                    <Input id="name" autoComplete="name" value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="John Doe" aria-invalid={!!errors.name} />
                </FormField>
                <FormField label="Email address" required htmlFor="email" error={errors.email}>
                    <Input
                        id="email"
                        type="email"
                        autoComplete="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        placeholder="user@example.com"
                        aria-invalid={!!errors.email}
                    />
                </FormField>
                <FormField
                    label={editing ? 'New password' : 'Password'}
                    required={!editing}
                    htmlFor="password"
                    error={errors.password}
                    description={editing ? 'Leave blank to keep the current password.' : 'Minimum 8 characters.'}
                >
                    <Input
                        id="password"
                        type="password"
                        autoComplete="new-password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        placeholder={editing ? 'Leave blank to keep current' : '••••••••'}
                        aria-invalid={!!errors.password}
                    />
                </FormField>
                <FormField
                    label={editing ? 'Confirm new password' : 'Confirm password'}
                    required={!editing}
                    htmlFor="password_confirmation"
                    error={errors.password_confirmation}
                >
                    <Input
                        id="password_confirmation"
                        type="password"
                        autoComplete="new-password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        placeholder="••••••••"
                        aria-invalid={!!errors.password_confirmation}
                    />
                </FormField>
            </FormSection>

            <FormSection title="Role assignment" columns={1}>
                <FormField
                    label="User role"
                    required
                    htmlFor="role"
                    error={errors.role}
                    description={
                        editing ? (
                            <>
                                Current role: <strong>{ucfirst(user?.role || 'No role')}</strong>
                            </>
                        ) : (
                            'Roles determine what actions a user can perform in the system.'
                        )
                    }
                >
                    <NativeSelect id="role" value={data.role} onChange={(e) => setData('role', e.target.value)} aria-invalid={!!errors.role}>
                        <option value="">Select a role</option>
                        {roles.map((role) => (
                            <option key={role.name} value={role.name}>
                                {ucfirst(role.name)} ({role.permissions_count} permissions)
                            </option>
                        ))}
                    </NativeSelect>
                </FormField>
            </FormSection>

            <FormActions>
                <Button type="submit" disabled={processing}>
                    {processing && <Spinner />}
                    {editing ? 'Update user' : 'Create user'}
                </Button>
                <Button asChild variant="ghost">
                    <Link href={index()}>Cancel</Link>
                </Button>
            </FormActions>
        </form>
    );
}

/** Client classification labels; the stored values stay 'adhoc' and 'contract'. */
export const clientTypeLabels: Record<string, string> = {
    adhoc: 'Once-off',
    contract: 'Contract',
};

export function clientTypeLabel(value: string | null | undefined): string {
    if (!value) return '';
    return clientTypeLabels[value] ?? value.charAt(0).toUpperCase() + value.slice(1);
}

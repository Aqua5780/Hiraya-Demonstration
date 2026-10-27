export type ConfirmTone = 'primary' | 'danger';

export interface ConfirmOptions {
    title: string;
    message: string;
    confirmLabel?: string;
    cancelLabel?: string;
    tone?: ConfirmTone;
    inputLabel?: string;
    inputPlaceholder?: string;
    inputRequired?: boolean;
    inputValue?: string;
}

export interface ConfirmDetail extends ConfirmOptions {
    onConfirm: (input?: string) => void;
}

export function showConfirm(options: ConfirmOptions, onConfirm: (input?: string) => void): void {
    window.dispatchEvent(
        new CustomEvent<ConfirmDetail>('hiraya-confirm', {
            detail: { ...options, onConfirm },
        }),
    );
}

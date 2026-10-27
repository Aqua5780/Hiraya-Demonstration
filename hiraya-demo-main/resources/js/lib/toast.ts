export type ToastType = 'success' | 'error' | 'info';

export interface ToastPayload {
    id: number;
    message: string;
    type: ToastType;
}

export function showToast(message: string, type: ToastType = 'success'): void {
    window.dispatchEvent(
        new CustomEvent<ToastPayload>('hiraya-toast', {
            detail: {
                id: Date.now() + Math.floor(Math.random() * 1000),
                message,
                type,
            },
        }),
    );
}

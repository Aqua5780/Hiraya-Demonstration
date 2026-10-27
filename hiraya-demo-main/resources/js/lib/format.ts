export function formatCurrency(value: number | string | null | undefined): string {
    const amount = Number(value ?? 0);

    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    }).format(Number.isFinite(amount) ? amount : 0);
}

export function formatNumber(value: number | string | null | undefined): string {
    const amount = Number(value ?? 0);

    return new Intl.NumberFormat('en-PH').format(Number.isFinite(amount) ? amount : 0);
}

export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-PH', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(value));
}

export function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-PH', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(value));
}

export function timeAgo(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);
    const seconds = Math.floor((Date.now() - date.getTime()) / 1000);

    if (seconds < 60) {
        return 'Just now';
    }

    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) {
        return `${minutes} minute${minutes === 1 ? '' : 's'} ago`;
    }

    const hours = Math.floor(minutes / 60);
    if (hours < 24) {
        return `${hours} hour${hours === 1 ? '' : 's'} ago`;
    }

    const days = Math.floor(hours / 24);
    if (days < 7) {
        return `${days} day${days === 1 ? '' : 's'} ago`;
    }

    return formatDate(value);
}

export function titleCase(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

export function statusTone(status: string | null | undefined): 'green' | 'gold' | 'red' | 'navy' | 'gray' {
    switch (status) {
        case 'active':
        case 'enrolled':
        case 'verified':
        case 'published':
        case 'open':
        case 'submitted':
        case 'passed':
            return 'green';
        case 'pending':
        case 'draft':
        case 'upcoming':
        case 'encoded':
            return 'gold';
        case 'rejected':
        case 'failed':
        case 'locked':
        case 'closed':
        case 'inactive':
        case 'cancelled':
            return 'red';
        case 'completed':
            return 'navy';
        default:
            return 'gray';
    }
}

export function dayOrder(day: string): number {
    return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'].indexOf(day);
}

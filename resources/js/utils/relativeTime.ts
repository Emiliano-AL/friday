const rtf = new Intl.RelativeTimeFormat('es', { numeric: 'auto' });

const divisions: { amount: number; unit: Intl.RelativeTimeFormatUnit }[] = [
    { amount: 60, unit: 'second' },
    { amount: 60, unit: 'minute' },
    { amount: 24, unit: 'hour' },
    { amount: 7, unit: 'day' },
];

/**
 * Format an ISO datetime as Spanish relative time ("justo ahora",
 * "hace 20 min", "hace 1 hora", "hace 2 días"); falls back to an
 * absolute es-MX date beyond one week.
 */
export function relativeTime(iso: string): string {
    const date = new Date(iso);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    let seconds = (date.getTime() - Date.now()) / 1000;

    if (Math.abs(seconds) < 60) {
        return 'justo ahora';
    }

    for (const division of divisions) {
        if (Math.abs(seconds) < division.amount) {
            return rtf.format(Math.round(seconds), division.unit);
        }

        seconds /= division.amount;
    }

    return new Intl.DateTimeFormat('es-MX', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(date);
}

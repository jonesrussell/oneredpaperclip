type DateInput = string | Date | null | undefined;

export interface DateFormatOptions {
    locale?: string;
    fallback?: string;
    includeTime?: boolean;
    shortMonth?: boolean;
}

const DEFAULT_OPTIONS: DateFormatOptions = {
    locale: 'en-US',
    fallback: '-',
    includeTime: false,
    shortMonth: false,
};

export function useDateFormat() {
    const formatDate = (
        date: DateInput,
        options: DateFormatOptions = {},
    ): string => {
        if (!date) {
            return options.fallback ?? DEFAULT_OPTIONS.fallback!;
        }

        const merged = { ...DEFAULT_OPTIONS, ...options };
        const dateObj = typeof date === 'string' ? new Date(date) : date;

        const formatOptions: Intl.DateTimeFormatOptions = {
            year: 'numeric',
            month: merged.shortMonth ? 'short' : 'long',
            day: 'numeric',
        };

        if (merged.includeTime) {
            formatOptions.hour = '2-digit';
            formatOptions.minute = '2-digit';
        }

        return dateObj.toLocaleString(merged.locale, formatOptions);
    };

    const formatDateTime = (
        date: DateInput,
        options: Omit<DateFormatOptions, 'includeTime'> = {},
    ): string => {
        return formatDate(date, { ...options, includeTime: true });
    };

    const formatShortDate = (
        date: DateInput,
        options: Omit<DateFormatOptions, 'shortMonth'> = {},
    ): string => {
        return formatDate(date, { ...options, shortMonth: true });
    };

    return {
        formatDate,
        formatDateTime,
        formatShortDate,
    };
}

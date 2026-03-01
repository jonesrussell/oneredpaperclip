export interface FieldDefinition {
    name: string;
    type: string;
    label: string;
    required?: boolean;
    rules?: string[];
    relationship?: string;
    display_field?: string;
    placeholder?: string;
    options?: Array<{ label: string; value: string | number }>;
    [key: string]: unknown;
}

export interface ColumnDefinition {
    name: string;
    label: string;
    sortable?: boolean;
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
    per_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    first_page_url: string;
    last_page_url: string;
    path: string;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

export interface AdminFilters {
    search?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
    [key: string]: string | undefined;
}

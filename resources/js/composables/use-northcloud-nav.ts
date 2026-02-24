import { usePage } from '@inertiajs/vue3';
import { FileText, Users, type LucideIcon } from 'lucide-vue-next';
import { computed, type ComputedRef } from 'vue';
import type { NavItem } from '@/types';

interface NorthcloudNavItem {
    title: string;
    href: string;
    icon: string;
}

const iconMap: Record<string, LucideIcon> = {
    FileText,
    Users,
};

export function useNorthcloudNav(): { items: ComputedRef<NavItem[]> } {
    const page = usePage();

    const items = computed<NavItem[]>(() => {
        const northcloud = page.props.northcloud as
            | { navigation?: NorthcloudNavItem[] }
            | undefined;
        const nav = northcloud?.navigation ?? [];

        return nav.map((item): NavItem => {
            const icon = iconMap[item.icon];
            return {
                title: item.title,
                href: item.href,
                ...(icon !== undefined && { icon }),
            };
        });
    });

    return { items };
}

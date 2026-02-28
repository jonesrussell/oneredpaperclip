import { usePage } from '@inertiajs/vue3';
import { FileText, ListChecks, Users, type LucideIcon } from 'lucide-vue-next';
import { computed, type ComputedRef } from 'vue';
import { index as adminChallengesIndex } from '@/routes/admin/challenges';
import type { NavItem } from '@/types';

interface NorthcloudNavItem {
    title: string;
    href: string;
    icon: string;
}

const iconMap: Record<string, LucideIcon> = {
    FileText,
    ListChecks,
    Users,
};

export function useNorthcloudNav(): { items: ComputedRef<NavItem[]> } {
    const page = usePage();

    const items = computed<NavItem[]>(() => {
        const northcloud = page.props.northcloud as
            | { navigation?: NorthcloudNavItem[] }
            | undefined;
        const nav = northcloud?.navigation ?? [];

        if (nav.length === 0) {
            return [];
        }

        const northcloudItems = nav.map((item): NavItem => {
            const icon = iconMap[item.icon];
            return {
                title: item.title,
                href: item.href,
                ...(icon !== undefined && { icon }),
            };
        });

        const challengesItem: NavItem = {
            title: 'Challenges',
            href: adminChallengesIndex().url,
            icon: ListChecks,
        };

        return [...northcloudItems, challengesItem];
    });

    return { items };
}

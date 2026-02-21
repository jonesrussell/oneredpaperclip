<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { toUrl } from '@/lib/utils';
import { type NavItem } from '@/types';

type Props = {
    items: NavItem[];
    class?: string;
};

defineProps<Props>();

function isExternal(href: NonNullable<NavItem['href']>): boolean {
    const url = typeof href === 'string' ? href : href?.url;
    return (
        typeof url === 'string' &&
        (url.startsWith('http://') || url.startsWith('https://'))
    );
}
</script>

<template>
    <SidebarGroup
        :class="`group-data-[collapsible=icon]:p-0 ${$props.class || ''}`"
    >
        <SidebarGroupContent>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in items" :key="item.title">
                    <SidebarMenuButton
                        class="text-neutral-600 hover:text-neutral-800 dark:text-neutral-300 dark:hover:text-neutral-100"
                        as-child
                    >
                        <component
                            :is="isExternal(item.href) ? 'a' : Link"
                            :href="toUrl(item.href)"
                            :target="
                                isExternal(item.href) ? '_blank' : undefined
                            "
                            :rel="
                                isExternal(item.href)
                                    ? 'noopener noreferrer'
                                    : undefined
                            "
                        >
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </component>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>
</template>

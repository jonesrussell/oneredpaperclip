<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronsUpDown } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import UserInfo from '@/components/UserInfo.vue';
import { login, register } from '@/routes';
import UserMenuContent from './UserMenuContent.vue';

const page = usePage();
const user = page.props.auth.user;
const { isMobile, state } = useSidebar();
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <template v-if="user">
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <SidebarMenuButton
                            size="lg"
                            class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                            data-test="sidebar-menu-button"
                        >
                            <UserInfo :user="user" />
                            <ChevronsUpDown class="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        :side="
                            isMobile
                                ? 'bottom'
                                : state === 'collapsed'
                                  ? 'left'
                                  : 'bottom'
                        "
                        align="end"
                        :side-offset="4"
                    >
                        <UserMenuContent :user="user" />
                    </DropdownMenuContent>
                </DropdownMenu>
            </template>
            <template v-else>
                <SidebarMenuButton size="lg" as-child>
                    <Link
                        :href="login()"
                        class="flex w-full items-center gap-2"
                        data-test="sidebar-login-link"
                    >
                        <UserInfo :user="null" />
                        <span class="ml-auto text-muted-foreground"
                            >Sign in</span
                        >
                    </Link>
                </SidebarMenuButton>
                <div class="px-2 py-1">
                    <Link
                        :href="register()"
                        class="text-xs text-muted-foreground underline hover:text-foreground"
                        >Create account</Link
                    >
                </div>
            </template>
        </SidebarMenuItem>
    </SidebarMenu>
</template>

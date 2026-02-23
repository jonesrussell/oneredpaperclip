<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, Compass, Home, PlusCircle, User } from 'lucide-vue-next';
import { computed } from 'vue';
import { dashboard, home, login, register } from '@/routes';
import campaigns from '@/routes/campaigns';

const page = usePage();
const currentUrl = computed(() => page.url);
const user = computed(() => page.props.auth?.user);

function isActive(path: string): boolean {
    return currentUrl.value.startsWith(path);
}
</script>

<template>
    <nav
        class="fixed inset-x-0 bottom-0 z-50 flex h-16 w-full max-w-[100vw] items-center justify-around gap-0 border-t border-[var(--border)] bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-md lg:hidden"
        aria-label="Mobile navigation"
    >
        <Link
            :href="user ? dashboard().url : home().url"
            class="flex min-w-0 flex-1 flex-col items-center gap-0.5 px-1 py-1 text-xs font-medium transition-colors sm:px-3"
            :class="
                isActive('/dashboard') || currentUrl === '/'
                    ? 'text-[var(--brand-red)]'
                    : 'text-[var(--ink-muted)]'
            "
        >
            <Home class="size-5" />
            <span>Home</span>
        </Link>

        <Link
            :href="campaigns.index().url"
            class="flex min-w-0 flex-1 flex-col items-center gap-0.5 px-1 py-1 text-xs font-medium transition-colors sm:px-3"
            :class="
                isActive('/campaigns') && !currentUrl.includes('/create')
                    ? 'text-[var(--brand-red)]'
                    : 'text-[var(--ink-muted)]'
            "
        >
            <Compass class="size-5" />
            <span>Explore</span>
        </Link>

        <Link
            :href="user ? campaigns.create().url : register().url"
            class="-mt-5 flex min-w-0 flex-1 flex-col items-center gap-0.5"
        >
            <div
                class="flex size-12 items-center justify-center rounded-full bg-[var(--brand-red)] text-white shadow-lg transition-transform hover:scale-105 active:scale-95"
            >
                <PlusCircle class="size-6" />
            </div>
            <span class="text-xs font-semibold text-[var(--brand-red)]">
                Create
            </span>
        </Link>

        <Link
            :href="user ? dashboard().url : login().url"
            class="flex min-w-0 flex-1 flex-col items-center gap-0.5 px-1 py-1 text-xs font-medium text-[var(--ink-muted)] transition-colors sm:px-3"
        >
            <Bell class="size-5" />
            <span>Activity</span>
        </Link>

        <Link
            :href="user ? dashboard().url : login().url"
            class="flex min-w-0 flex-1 flex-col items-center gap-0.5 px-1 py-1 text-xs font-medium text-[var(--ink-muted)] transition-colors sm:px-3"
        >
            <User class="size-5" />
            <span>Profile</span>
        </Link>
    </nav>
</template>

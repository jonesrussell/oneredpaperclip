<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import BottomTabBar from '@/components/BottomTabBar.vue';
import FlashMessage from '@/components/FlashMessage.vue';
import { about, dashboard, home, login, register } from '@/routes';
import challenges from '@/routes/challenges';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const canRegister = computed(
    () => (page.props as Record<string, unknown>).canRegister ?? true,
);
</script>

<template>
    <div
        class="min-h-screen w-full overflow-x-hidden bg-[var(--paper)] text-[var(--ink)]"
    >
        <!-- Decorative background blobs -->
        <div
            class="pointer-events-none fixed inset-0 z-0 overflow-hidden"
            aria-hidden="true"
        >
            <div
                class="animate-blob-pulse absolute -top-32 -right-32 h-[28rem] w-[28rem] rounded-full bg-[var(--hot-coral)]/35 blur-3xl"
            />
            <div
                class="animate-blob-pulse absolute -bottom-24 -left-24 h-80 w-80 rounded-full bg-[var(--sunny-yellow)]/30 blur-3xl"
                style="animation-delay: -2s"
            />
            <div
                class="animate-blob-pulse absolute top-1/2 right-1/3 h-72 w-72 rounded-full bg-[var(--electric-mint)]/25 blur-3xl"
                style="animation-delay: -4s"
            />
            <div
                class="animate-blob-pulse absolute top-1/3 -left-20 h-64 w-64 rounded-full bg-[var(--soft-lavender)]/20 blur-3xl"
                style="animation-delay: -1s"
            />
        </div>

        <!-- Grain overlay -->
        <div
            class="welcome-grain pointer-events-none fixed inset-0 z-[1] opacity-[0.03]"
            aria-hidden="true"
        />

        <FlashMessage />

        <!-- Sticky top header -->
        <header
            class="sticky top-0 z-40 w-full overflow-x-hidden border-b border-[var(--border)] bg-[var(--paper)]/90 backdrop-blur-md dark:bg-[var(--paper)]/90"
        >
            <div
                class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-2 px-4 sm:gap-4 sm:px-6"
            >
                <Link
                    :href="home().url"
                    class="flex min-w-0 shrink items-center gap-2 font-display text-lg font-bold tracking-tight transition-transform hover:scale-[1.02]"
                >
                    <span class="truncate text-[var(--brand-red)]">
                        One Red Paperclip
                    </span>
                </Link>

                <nav
                    class="hidden items-center gap-2 lg:flex"
                    aria-label="Main navigation"
                >
                    <a
                        href="#how-it-works"
                        class="rounded-lg px-3 py-2 text-sm font-medium text-[var(--ink-muted)] transition-colors hover:bg-[var(--accent)] hover:text-[var(--ink)]"
                    >
                        How it works
                    </a>
                    <Link
                        :href="about().url"
                        class="rounded-lg px-3 py-2 text-sm font-medium text-[var(--ink-muted)] transition-colors hover:bg-[var(--accent)] hover:text-[var(--ink)]"
                    >
                        About
                    </Link>
                    <Link
                        :href="challenges.index().url"
                        class="rounded-lg px-3 py-2 text-sm font-medium text-[var(--ink-muted)] transition-colors hover:bg-[var(--accent)] hover:text-[var(--ink)]"
                    >
                        Explore challenges
                    </Link>

                    <AppearanceToggle class="ml-2" />

                    <template v-if="user">
                        <Link
                            :href="dashboard().url"
                            class="rounded-xl bg-[var(--brand-red)] px-4 py-2 text-sm font-semibold text-white shadow-md transition-all hover:-translate-y-0.5 hover:bg-[var(--brand-red-hover)] hover:shadow-lg"
                        >
                            Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="login().url"
                            class="rounded-lg px-3 py-2 text-sm font-medium text-[var(--ink-muted)] transition-colors hover:bg-[var(--accent)] hover:text-[var(--ink)]"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register().url"
                            class="rounded-xl bg-[var(--brand-red)] px-4 py-2 text-sm font-semibold text-white shadow-md transition-all hover:-translate-y-0.5 hover:bg-[var(--brand-red-hover)] hover:shadow-lg"
                        >
                            Get started
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Main content -->
        <main
            class="relative z-10 pb-[calc(5rem+env(safe-area-inset-bottom,0px))] lg:pb-0"
        >
            <slot />
        </main>

        <!-- Footer -->
        <footer
            class="relative z-10 border-t border-[var(--border)] bg-[var(--paper)]/80 py-8 pb-[calc(6rem+env(safe-area-inset-bottom,0px))] lg:pb-8 dark:bg-[var(--paper)]/80"
        >
            <div
                class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 sm:px-6"
            >
                <Link
                    :href="home().url"
                    class="font-display text-lg font-bold text-[var(--ink)] transition-opacity hover:opacity-80"
                >
                    One Red Paperclip
                </Link>
                <nav
                    class="flex flex-wrap items-center gap-6 text-sm font-medium text-[var(--ink-muted)]"
                    aria-label="Footer navigation"
                >
                    <a href="#how-it-works" class="hover:text-[var(--ink)]">
                        How it works
                    </a>
                    <Link :href="about().url" class="hover:text-[var(--ink)]">
                        About
                    </Link>
                    <Link
                        :href="challenges.index().url"
                        class="hover:text-[var(--ink)]"
                    >
                        Challenges
                    </Link>
                    <Link
                        v-if="!user"
                        :href="login().url"
                        class="hover:text-[var(--ink)]"
                    >
                        Log in
                    </Link>
                </nav>
            </div>
        </footer>

        <!-- Mobile bottom tab bar -->
        <BottomTabBar />
    </div>
</template>

<style scoped>
.welcome-grain {
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
}
</style>

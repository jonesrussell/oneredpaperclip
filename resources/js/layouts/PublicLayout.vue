<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import BottomTabBar from '@/components/BottomTabBar.vue';
import FlashMessage from '@/components/FlashMessage.vue';
import { buttonVariants } from '@/components/ui/button';
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
        <FlashMessage />

        <!-- Sticky top header -->
        <header
            class="sticky top-0 z-40 w-full overflow-x-hidden border-b border-[var(--border)] bg-[var(--paper)]/90 backdrop-blur-md"
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
                        :href="`${home().url}#how-it-works`"
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
                            :class="buttonVariants({ variant: 'brand' })"
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
                            :class="buttonVariants({ variant: 'brand' })"
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
            class="relative z-10 border-t border-[var(--border)] bg-[var(--paper)]/80 py-8 pb-[calc(6rem+env(safe-area-inset-bottom,0px))] lg:pb-8"
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
                    <a :href="`${home().url}#how-it-works`" class="hover:text-[var(--ink)]">
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

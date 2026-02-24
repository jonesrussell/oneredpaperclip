<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import PublicLayout from '@/layouts/PublicLayout.vue';
import { register } from '@/routes';
import campaigns from '@/routes/campaigns';
</script>

<template>
    <Head title="About — One Red Paperclip" />
    <PublicLayout>
        <!-- Hero -->
        <section class="mx-auto max-w-4xl px-4 py-16 sm:px-6 sm:py-24">
            <div class="text-center">
                <span
                    class="inline-block rounded-full bg-[var(--brand-red)]/10 px-4 py-1.5 text-sm font-semibold text-[var(--brand-red)] dark:bg-[var(--brand-red)]/20 dark:text-[var(--brand-red-muted)]"
                >
                    The origin story
                </span>
                <h1
                    class="mt-6 font-display text-4xl leading-tight font-semibold tracking-tight text-[var(--ink)] sm:text-5xl"
                >
                    From a red paperclip<br />
                    <span class="text-[var(--brand-red)]">to a house.</span>
                </h1>
                <p
                    class="mx-auto mt-5 max-w-2xl text-lg leading-relaxed text-[var(--ink-muted)]"
                >
                    In 2005, one man proved that a series of small trades could
                    lead to something extraordinary. His experiment inspired a
                    movement—and this platform.
                </p>
            </div>
        </section>

        <!-- Kyle's Story -->
        <section
            class="border-t border-[var(--ink)]/10 bg-white/50 py-16 sm:py-24 dark:bg-[var(--paper)]"
        >
            <div class="mx-auto max-w-4xl px-4 sm:px-6">
                <div class="grid gap-12 md:grid-cols-[1fr,auto] md:items-start">
                    <div>
                        <h2
                            class="font-display text-2xl font-semibold tracking-tight text-[var(--ink)] sm:text-3xl"
                        >
                            Kyle MacDonald's experiment
                        </h2>
                        <div
                            class="mt-6 space-y-5 text-base leading-relaxed text-[var(--ink-muted)]"
                        >
                            <p>
                                On July 14, 2005, Canadian blogger
                                <strong class="font-semibold text-[var(--ink)]"
                                    >Kyle MacDonald</strong
                                >
                                posted a photo of a single red paperclip on his
                                website with a simple proposition: he wanted to
                                trade it for something bigger or better. Then he
                                would trade that item, and the next, and keep
                                going until he reached his ultimate goal—a
                                house.
                            </p>
                            <p>
                                What followed was a chain of
                                <strong class="font-semibold text-[var(--ink)]"
                                    >14 trades over one year</strong
                                >. The paperclip became a fish-shaped pen, which
                                became a hand-sculpted doorknob, which became a
                                camping stove. The stove led to a generator,
                                then a "party kit" with an empty beer keg and
                                neon sign, then a snowmobile.
                            </p>
                            <p>
                                The trades got bigger: a trip to Yahk, British
                                Columbia. A box truck. A recording contract. A
                                year's rent in Phoenix. An afternoon with Alice
                                Cooper. A KISS snow globe. A movie role offered
                                by director Corbin Bernsen.
                            </p>
                            <p>
                                On July 12, 2006—almost exactly one year after
                                that first post—the town of Kipling,
                                Saskatchewan, traded Kyle a
                                <strong class="font-semibold text-[var(--ink)]"
                                    >two-story farmhouse</strong
                                >
                                in exchange for the movie role. One red
                                paperclip had become a home.
                            </p>
                        </div>
                        <a
                            href="https://en.wikipedia.org/wiki/One_red_paperclip"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-6 inline-flex items-center gap-1 text-sm font-medium text-[var(--brand-red)] transition-colors hover:underline"
                        >
                            Read the full story on Wikipedia
                            <svg
                                class="size-3.5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M7 17L17 7M17 7H7M17 7V17" />
                            </svg>
                        </a>
                    </div>

                    <!-- Trade journey visual -->
                    <div
                        class="hidden w-56 shrink-0 md:block"
                        aria-hidden="true"
                    >
                        <div
                            class="rounded-2xl border border-[var(--ink)]/10 bg-[var(--paper)] p-5 shadow-sm"
                        >
                            <p
                                class="mb-3 text-center font-display text-xs font-semibold tracking-wide text-[var(--ink-muted)] uppercase"
                            >
                                14 trades in 1 year
                            </p>
                            <ol
                                class="space-y-2.5 text-xs text-[var(--ink-muted)]"
                            >
                                <li class="flex items-baseline gap-2">
                                    <span
                                        class="font-mono text-[var(--brand-red)]"
                                        >01</span
                                    >
                                    Red paperclip
                                </li>
                                <li class="flex items-baseline gap-2">
                                    <span
                                        class="font-mono text-[var(--brand-red)]"
                                        >02</span
                                    >
                                    Fish-shaped pen
                                </li>
                                <li class="flex items-baseline gap-2">
                                    <span
                                        class="font-mono text-[var(--brand-red)]"
                                        >03</span
                                    >
                                    Doorknob
                                </li>
                                <li class="flex items-baseline gap-2">
                                    <span
                                        class="font-mono text-[var(--brand-red)]"
                                        >04</span
                                    >
                                    Camping stove
                                </li>
                                <li class="flex items-baseline gap-2">
                                    <span
                                        class="font-mono text-[var(--brand-red)]"
                                        >05</span
                                    >
                                    Generator
                                </li>
                                <li class="flex items-baseline gap-2">
                                    <span
                                        class="font-mono text-[var(--brand-red)]"
                                        >06</span
                                    >
                                    "Instant party" kit
                                </li>
                                <li class="flex items-baseline gap-2">
                                    <span
                                        class="font-mono text-[var(--brand-red)]"
                                        >07</span
                                    >
                                    Snowmobile
                                </li>
                                <li class="flex items-baseline gap-2">
                                    <span
                                        class="font-mono text-[var(--brand-red)]"
                                        >08</span
                                    >
                                    Trip to Yahk, BC
                                </li>
                                <li class="flex items-baseline gap-2">
                                    <span
                                        class="font-mono text-[var(--brand-red)]"
                                        >09</span
                                    >
                                    Box truck
                                </li>
                                <li class="flex items-baseline gap-2">
                                    <span
                                        class="font-mono text-[var(--brand-red)]"
                                        >10</span
                                    >
                                    Recording contract
                                </li>
                                <li class="flex items-baseline gap-2">
                                    <span
                                        class="font-mono text-[var(--brand-red)]"
                                        >11</span
                                    >
                                    Year's rent in Phoenix
                                </li>
                                <li class="flex items-baseline gap-2">
                                    <span
                                        class="font-mono text-[var(--brand-red)]"
                                        >12</span
                                    >
                                    Afternoon w/ Alice Cooper
                                </li>
                                <li class="flex items-baseline gap-2">
                                    <span
                                        class="font-mono text-[var(--brand-red)]"
                                        >13</span
                                    >
                                    KISS snow globe
                                </li>
                                <li class="flex items-baseline gap-2">
                                    <span
                                        class="font-mono font-semibold text-[var(--brand-red)]"
                                        >14</span
                                    >
                                    <span
                                        class="font-semibold text-[var(--ink)]"
                                        >A house</span
                                    >
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Our Mission -->
        <section
            class="border-t border-[var(--ink)]/10 bg-[var(--ink)]/[0.02] py-16 sm:py-24"
        >
            <div class="mx-auto max-w-4xl px-4 sm:px-6">
                <h2
                    class="font-display text-2xl font-semibold tracking-tight text-[var(--ink)] sm:text-3xl"
                >
                    What we're building
                </h2>
                <p class="mt-2 max-w-xl text-[var(--ink-muted)]">
                    Same spirit, new tools.
                </p>

                <div class="mt-10 grid gap-6 sm:grid-cols-2">
                    <div
                        class="rounded-xl border border-[var(--ink)]/10 bg-[var(--paper)] p-6 shadow-sm"
                    >
                        <div
                            class="mb-3 inline-flex size-10 items-center justify-center rounded-lg bg-[var(--brand-red)]/10"
                        >
                            <svg
                                class="size-5 text-[var(--brand-red)]"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path
                                    d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                                />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                        </div>
                        <h3
                            class="font-display text-lg font-semibold text-[var(--ink)]"
                        >
                            Open to everyone
                        </h3>
                        <p
                            class="mt-2 text-sm leading-relaxed text-[var(--ink-muted)]"
                        >
                            Kyle did it solo. Here, anyone can create a challenge
                            and start trading up from whatever they have.
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-[var(--ink)]/10 bg-[var(--paper)] p-6 shadow-sm"
                    >
                        <div
                            class="mb-3 inline-flex size-10 items-center justify-center rounded-lg bg-[var(--electric-mint)]/10"
                        >
                            <svg
                                class="size-5 text-emerald-600"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path
                                    d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"
                                />
                                <path d="m9 12 2 2 4-4" />
                            </svg>
                        </div>
                        <h3
                            class="font-display text-lg font-semibold text-[var(--ink)]"
                        >
                            Dual confirmation
                        </h3>
                        <p
                            class="mt-2 text-sm leading-relaxed text-[var(--ink-muted)]"
                        >
                            Both parties confirm every trade before it goes
                            through. No surprises, no disputes.
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-[var(--ink)]/10 bg-[var(--paper)] p-6 shadow-sm"
                    >
                        <div
                            class="mb-3 inline-flex size-10 items-center justify-center rounded-lg bg-[var(--sky-blue)]/10"
                        >
                            <svg
                                class="size-5 text-sky-600"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path
                                    d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"
                                />
                            </svg>
                        </div>
                        <h3
                            class="font-display text-lg font-semibold text-[var(--ink)]"
                        >
                            Challenge-based
                        </h3>
                        <p
                            class="mt-2 text-sm leading-relaxed text-[var(--ink-muted)]"
                        >
                            Every trade-up journey is a challenge with a clear
                            start item and goal. Track progress along the way.
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-[var(--ink)]/10 bg-[var(--paper)] p-6 shadow-sm"
                    >
                        <div
                            class="mb-3 inline-flex size-10 items-center justify-center rounded-lg bg-[var(--sunny-yellow)]/10"
                        >
                            <svg
                                class="size-5 text-amber-600"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path
                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.77 5.82 21 7 14.14l-5-4.87 6.91-1.01L12 2"
                                />
                            </svg>
                        </div>
                        <h3
                            class="font-display text-lg font-semibold text-[var(--ink)]"
                        >
                            Community-driven
                        </h3>
                        <p
                            class="mt-2 text-sm leading-relaxed text-[var(--ink-muted)]"
                        >
                            Follow challenges, make offers, and cheer people on.
                            Trading up is more fun together.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Disclaimer + CTA -->
        <section class="border-t border-[var(--ink)]/10 py-16 sm:py-20">
            <div class="mx-auto max-w-2xl px-4 text-center sm:px-6">
                <p class="text-sm leading-relaxed text-[var(--ink-muted)]">
                    One Red Paperclip is an independent project inspired by Kyle
                    MacDonald's experiment. We are not affiliated with, endorsed
                    by, or connected to Kyle MacDonald in any way.
                </p>
            </div>
        </section>

        <!-- CTA strip -->
        <section
            class="border-t border-white/10 bg-[hsl(24_10%_12%)] py-16 text-center sm:py-20"
        >
            <div class="mx-auto max-w-2xl px-4 sm:px-6">
                <h2
                    class="font-display text-2xl font-semibold tracking-tight text-white sm:text-3xl"
                >
                    Ready to start your own trade-up?
                </h2>
                <p class="mt-3 text-white/80">
                    Create a challenge, list your start item, and see how far you
                    can go.
                </p>
                <div
                    class="mt-8 flex flex-wrap items-center justify-center gap-4"
                >
                    <Link
                        v-if="!$page.props.auth.user"
                        :href="register().url"
                        class="inline-flex rounded-md bg-white px-5 py-2.5 font-medium text-[var(--ink)] transition-opacity hover:opacity-90"
                    >
                        Create an account
                    </Link>
                    <Link
                        :href="campaigns.index().url"
                        :class="
                            !$page.props.auth.user
                                ? 'inline-flex rounded-md border border-white/40 px-5 py-2.5 font-medium text-white transition-colors hover:border-white/70 hover:bg-white/10'
                                : 'inline-flex rounded-md bg-white px-5 py-2.5 font-medium text-[var(--ink)] transition-opacity hover:opacity-90'
                        "
                    >
                        Browse challenges
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

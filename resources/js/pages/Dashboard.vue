<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Compass, PlusCircle } from 'lucide-vue-next';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import campaigns from '@/routes/campaigns';
import type { BreadcrumbItem } from '@/types';

const page = usePage();
const user = page.props.auth.user;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const howItWorksSteps = [
    {
        step: '1',
        title: 'Start',
        body: 'Create a campaign with something you have and something you want.',
    },
    {
        step: '2',
        title: 'Offer',
        body: 'Others browse and submit offers to trade for your current item.',
    },
    {
        step: '3',
        title: 'Trade',
        body: 'Accept an offer. Both parties confirm to complete the trade.',
    },
    {
        step: '4',
        title: 'Goal',
        body: 'Your item updates. Keep trading until you reach your goal.',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-5xl flex-col gap-8 p-4 sm:p-6">
            <!-- Greeting -->
            <div>
                <h1
                    class="font-display text-2xl font-bold tracking-tight text-[var(--ink)] sm:text-3xl"
                >
                    Hey {{ user?.name?.split(' ')[0] ?? 'there' }}!
                </h1>
                <p class="mt-1 text-sm text-[var(--ink-muted)]">
                    Your trading desk
                </p>
            </div>

            <!-- Quick stats -->
            <div class="grid grid-cols-3 gap-3">
                <div
                    class="rounded-2xl border border-[var(--border)] bg-white p-4 text-center"
                >
                    <p
                        class="font-mono text-2xl font-bold text-[var(--hot-coral)]"
                    >
                        0
                    </p>
                    <p class="mt-1 text-xs font-medium text-[var(--ink-muted)]">
                        Active Campaigns
                    </p>
                </div>
                <div
                    class="rounded-2xl border border-[var(--border)] bg-white p-4 text-center"
                >
                    <p
                        class="font-mono text-2xl font-bold text-[var(--sunny-yellow)]"
                    >
                        0
                    </p>
                    <p class="mt-1 text-xs font-medium text-[var(--ink-muted)]">
                        Pending Offers
                    </p>
                </div>
                <div
                    class="rounded-2xl border border-[var(--border)] bg-white p-4 text-center"
                >
                    <p
                        class="font-mono text-2xl font-bold text-[var(--electric-mint)]"
                    >
                        0
                    </p>
                    <p class="mt-1 text-xs font-medium text-[var(--ink-muted)]">
                        Completed Trades
                    </p>
                </div>
            </div>

            <!-- Shortcut cards -->
            <div class="grid gap-4 sm:grid-cols-2">
                <Link :href="campaigns.create().url" class="block">
                    <Card class="h-full">
                        <CardHeader>
                            <div
                                class="flex size-10 items-center justify-center rounded-xl bg-[var(--hot-coral)]/10 text-[var(--hot-coral)]"
                            >
                                <PlusCircle class="size-5" />
                            </div>
                            <CardTitle class="mt-2">
                                Start a campaign
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-[var(--ink-muted)]">
                                Pick a start item and a goal. See how far you
                                can trade up.
                            </p>
                        </CardContent>
                    </Card>
                </Link>

                <Link :href="campaigns.index().url" class="block">
                    <Card class="h-full">
                        <CardHeader>
                            <div
                                class="flex size-10 items-center justify-center rounded-xl bg-[var(--electric-mint)]/10 text-[var(--electric-mint)]"
                            >
                                <Compass class="size-5" />
                            </div>
                            <CardTitle class="mt-2">
                                Browse campaigns
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-[var(--ink-muted)]">
                                Explore active campaigns and make an offer on
                                something you like.
                            </p>
                        </CardContent>
                    </Card>
                </Link>
            </div>

            <!-- How it works -->
            <Card>
                <CardHeader>
                    <CardTitle
                        class="font-display text-lg font-semibold text-[var(--ink)]"
                    >
                        How it works
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <ol class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <li
                            v-for="step in howItWorksSteps"
                            :key="step.step"
                            class="relative rounded-xl border border-[var(--border)] p-4"
                        >
                            <span
                                class="absolute -top-2 -left-2 flex size-6 items-center justify-center rounded-full bg-[var(--brand-red)] text-xs font-bold text-white"
                            >
                                {{ step.step }}
                            </span>
                            <h4
                                class="font-display font-semibold text-[var(--ink)]"
                            >
                                {{ step.title }}
                            </h4>
                            <p
                                class="mt-1 text-sm leading-relaxed text-[var(--ink-muted)]"
                            >
                                {{ step.body }}
                            </p>
                        </li>
                    </ol>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import campaigns from '@/routes/campaigns';
import { type BreadcrumbItem } from '@/types';
import { Megaphone, Plus, Sparkles } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6"
        >
            <div>
                <h1
                    class="font-display text-2xl font-semibold tracking-tight text-[var(--ink)] sm:text-3xl"
                >
                    Your trading desk
                </h1>
                <p class="mt-1 text-[var(--ink-muted)]">
                    Start a campaign or discover what others are trading.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <Link :href="campaigns.create()" class="block transition-opacity hover:opacity-90">
                    <Card
                        class="h-full border-[var(--ink)]/10 transition-shadow hover:shadow-md"
                    >
                        <CardHeader class="pb-2">
                            <div
                                class="mb-2 flex size-10 items-center justify-center rounded-lg bg-[var(--brand-red)]/10 text-[var(--brand-red)]"
                            >
                                <Plus class="size-5" />
                            </div>
                            <CardTitle class="font-display text-lg">
                                Start a campaign
                            </CardTitle>
                            <CardDescription class="text-[var(--ink-muted)]">
                                Set your start item and goal. Others will make
                                offers—trade up step by step.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="pt-0">
                            <Button variant="brand" size="sm">
                                Create campaign
                            </Button>
                        </CardContent>
                    </Card>
                </Link>
                <Link
                    :href="campaigns.index()"
                    class="block transition-opacity hover:opacity-90"
                >
                    <Card
                        class="h-full border-[var(--ink)]/10 transition-shadow hover:shadow-md"
                    >
                        <CardHeader class="pb-2">
                            <div
                                class="mb-2 flex size-10 items-center justify-center rounded-lg bg-[var(--ink)]/10 text-[var(--ink)]"
                            >
                                <Megaphone class="size-5" />
                            </div>
                            <CardTitle class="font-display text-lg">
                                Browse campaigns
                            </CardTitle>
                            <CardDescription class="text-[var(--ink-muted)]">
                                See active trade-up campaigns. Make an offer and
                                help someone get closer to their goal.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="pt-0">
                            <Button variant="outline" size="sm">
                                View all campaigns
                            </Button>
                        </CardContent>
                    </Card>
                </Link>
            </div>

            <Card class="border-[var(--ink)]/10">
                <CardHeader class="pb-2">
                    <div
                        class="mb-2 flex size-10 items-center justify-center rounded-lg bg-[var(--brand-red)]/10 text-[var(--brand-red)]"
                    >
                        <Sparkles class="size-5" />
                    </div>
                    <CardTitle class="font-display text-lg">
                        How it works
                    </CardTitle>
                    <CardDescription class="text-[var(--ink-muted)]">
                        Create a campaign with something you have and something
                        you want. Others offer trades for your current item.
                        Accept, confirm together, and your current item updates.
                        Keep trading until you reach your goal—or beyond.
                    </CardDescription>
                </CardHeader>
            </Card>
        </div>
    </AppLayout>
</template>

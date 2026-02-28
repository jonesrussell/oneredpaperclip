<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Package, Target, Trash2, XCircle } from 'lucide-vue-next';

import ChallengeStatusBadge from '@/components/admin/ChallengeStatusBadge.vue';
import ChallengeVisibilityBadge from '@/components/admin/ChallengeVisibilityBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';

interface ItemMedia {
    url: string;
}

interface Item {
    id: number;
    title: string;
    media?: ItemMedia[];
}

interface Props {
    challenge: {
        id: number;
        title: string;
        description?: string;
        status: 'draft' | 'active' | 'completed' | 'paused';
        visibility: 'public' | 'unlisted';
        created_at: string;
        user?: { id: number; name: string } | null;
        category?: { id: number; name: string } | null;
        currentItem?: Item | null;
        goalItem?: Item | null;
        offers_count?: number;
        trades_count?: number;
    };
}

const props = defineProps<Props>();

const routePrefix = '/dashboard/admin/challenges';
const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Challenges (Admin)', href: routePrefix },
    { title: props.challenge.title, href: '#' },
];

const formatDateTime = (date: string) => {
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getItemImage = (item: Item | null | undefined): string | null => {
    if (!item?.media?.length) {
        return null;
    }
    return item.media[0].url;
};

const handleUnpublish = () => {
    router.post(`${routePrefix}/${props.challenge.id}/unpublish`);
};

const handleDelete = () => {
    if (confirm('Are you sure you want to delete this challenge?')) {
        router.delete(`${routePrefix}/${props.challenge.id}`);
    }
};
</script>

<template>
    <Head :title="`${challenge.title} - Admin`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6"
        >
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <Button
                        variant="ghost"
                        size="sm"
                        as="a"
                        :href="routePrefix"
                        class="mb-2"
                    >
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Challenges
                    </Button>
                    <h1 class="text-3xl font-bold tracking-tight">
                        {{ challenge.title }}
                    </h1>
                </div>
                <div class="flex gap-2">
                    <Button
                        v-if="challenge.status !== 'draft'"
                        variant="outline"
                        @click="handleUnpublish"
                    >
                        <XCircle class="mr-2 h-4 w-4" />
                        Unpublish
                    </Button>
                    <Button variant="destructive" @click="handleDelete">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete
                    </Button>
                </div>
            </div>

            <!-- Challenge Details -->
            <Card>
                <CardHeader>
                    <CardTitle>Challenge Details</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
                        <div>
                            <p class="text-muted-foreground">Status</p>
                            <div class="mt-1">
                                <ChallengeStatusBadge
                                    :status="challenge.status"
                                />
                            </div>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Visibility</p>
                            <div class="mt-1">
                                <ChallengeVisibilityBadge
                                    :visibility="challenge.visibility"
                                />
                            </div>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Owner</p>
                            <p class="font-medium">
                                {{ challenge.user?.name ?? 'Unknown' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Category</p>
                            <p class="font-medium">
                                {{
                                    challenge.category?.name ?? 'Uncategorized'
                                }}
                            </p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Created</p>
                            <p class="font-medium">
                                {{ formatDateTime(challenge.created_at) }}
                            </p>
                        </div>
                    </div>

                    <div v-if="challenge.description" class="mt-6">
                        <p class="mb-2 text-sm text-muted-foreground">
                            Description
                        </p>
                        <p class="text-sm">{{ challenge.description }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Stats -->
            <Card>
                <CardHeader>
                    <CardTitle>Statistics</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
                        <div>
                            <p class="text-muted-foreground">Offers</p>
                            <p class="text-2xl font-bold">
                                {{ challenge.offers_count ?? 0 }}
                            </p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Trades</p>
                            <p class="text-2xl font-bold">
                                {{ challenge.trades_count ?? 0 }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Items -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Current Item -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Package class="h-5 w-5" />
                            Current Item
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="challenge.currentItem"
                            class="flex flex-col gap-4"
                        >
                            <img
                                v-if="getItemImage(challenge.currentItem)"
                                :src="getItemImage(challenge.currentItem)!"
                                :alt="challenge.currentItem.title"
                                class="aspect-square w-full rounded-lg object-cover"
                            />
                            <div
                                v-else
                                class="flex aspect-square w-full items-center justify-center rounded-lg bg-muted"
                            >
                                <Package
                                    class="h-12 w-12 text-muted-foreground"
                                />
                            </div>
                            <p class="font-medium">
                                {{ challenge.currentItem.title }}
                            </p>
                        </div>
                        <p v-else class="text-muted-foreground italic">
                            No current item
                        </p>
                    </CardContent>
                </Card>

                <!-- Goal Item -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Target class="h-5 w-5" />
                            Goal Item
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="challenge.goalItem"
                            class="flex flex-col gap-4"
                        >
                            <img
                                v-if="getItemImage(challenge.goalItem)"
                                :src="getItemImage(challenge.goalItem)!"
                                :alt="challenge.goalItem.title"
                                class="aspect-square w-full rounded-lg object-cover"
                            />
                            <div
                                v-else
                                class="flex aspect-square w-full items-center justify-center rounded-lg bg-muted"
                            >
                                <Target
                                    class="h-12 w-12 text-muted-foreground"
                                />
                            </div>
                            <p class="font-medium">
                                {{ challenge.goalItem.title }}
                            </p>
                        </div>
                        <p v-else class="text-muted-foreground italic">
                            No goal item
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

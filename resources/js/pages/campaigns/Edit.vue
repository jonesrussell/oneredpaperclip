<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import InputError from '@/components/InputError.vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/AppLayout.vue';
import campaigns from '@/routes/campaigns';
import type { BreadcrumbItem } from '@/types';

type ItemEdit = { title: string; description?: string | null } | null;

type CampaignEdit = {
    id: number;
    title?: string | null;
    story?: string | null;
    category_id?: number | null;
    status: string;
    visibility: string;
    start_item?: ItemEdit;
    startItem?: ItemEdit;
    goal_item?: ItemEdit;
    goalItem?: ItemEdit;
};

const props = defineProps<{
    campaign: CampaignEdit;
    categories: { id: number; name: string; slug: string }[];
}>();

const startItem = () =>
    props.campaign.start_item ?? props.campaign.startItem ?? null;
const goalItem = () =>
    props.campaign.goal_item ?? props.campaign.goalItem ?? null;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Campaigns', href: '/campaigns' },
    {
        title: props.campaign.title ?? 'Campaign',
        href: `/campaigns/${props.campaign.id}`,
    },
    { title: 'Edit', href: `/campaigns/${props.campaign.id}/edit` },
];

const startTitle = ref(startItem()?.title ?? '');
const startDescription = ref(startItem()?.description ?? '');
const goalTitle = ref(goalItem()?.title ?? '');
const goalDescription = ref(goalItem()?.description ?? '');
const campaignTitle = ref(props.campaign.title ?? '');
const campaignStory = ref(props.campaign.story ?? '');
const categoryId = ref(
    props.campaign.category_id != null
        ? String(props.campaign.category_id)
        : '',
);
const visibility = ref(props.campaign.visibility ?? 'public');
const status = ref(props.campaign.status ?? 'active');

function cancel(): void {
    router.visit(campaigns.show.url({ campaign: props.campaign.id }));
}
</script>

<template>
    <Head
        :title="campaign.title ? `Edit: ${campaign.title}` : 'Edit campaign'"
    />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-2xl px-4 py-8">
            <h1
                class="mb-6 font-display text-2xl font-semibold text-[var(--ink)]"
            >
                Edit campaign
            </h1>

            <Form
                v-bind="campaigns.update.form({ campaign: campaign.id })"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-6"
            >
                <Card class="border-[var(--border)]">
                    <CardHeader>
                        <CardTitle class="font-display text-lg"
                            >Start item</CardTitle
                        >
                        <CardDescription>
                            The item you're starting your trade journey with.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="start_item_title">Title</Label>
                            <Input
                                id="start_item_title"
                                v-model="startTitle"
                                name="start_item[title]"
                                type="text"
                                required
                                placeholder="e.g. Red paperclip"
                            />
                            <InputError :message="errors['start_item.title']" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="start_item_description"
                                >Description</Label
                            >
                            <RichTextEditor
                                id="start_item_description"
                                v-model="startDescription"
                                name="start_item[description]"
                                placeholder="Describe your item..."
                                min-height="min-h-[4.5rem]"
                            />
                            <InputError
                                :message="errors['start_item.description']"
                            />
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-[var(--border)]">
                    <CardHeader>
                        <CardTitle class="font-display text-lg"
                            >Goal item</CardTitle
                        >
                        <CardDescription>
                            The item you're hoping to trade up to.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="goal_item_title">Title</Label>
                            <Input
                                id="goal_item_title"
                                v-model="goalTitle"
                                name="goal_item[title]"
                                type="text"
                                required
                                placeholder="e.g. A house"
                            />
                            <InputError :message="errors['goal_item.title']" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="goal_item_description"
                                >Description</Label
                            >
                            <RichTextEditor
                                id="goal_item_description"
                                v-model="goalDescription"
                                name="goal_item[description]"
                                placeholder="Describe your dream item..."
                                min-height="min-h-[4.5rem]"
                            />
                            <InputError
                                :message="errors['goal_item.description']"
                            />
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-[var(--border)]">
                    <CardHeader>
                        <CardTitle class="font-display text-lg"
                            >Campaign details</CardTitle
                        >
                        <CardDescription>
                            Title, story, category, and visibility.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="title">Campaign title</Label>
                            <Input
                                id="title"
                                v-model="campaignTitle"
                                name="title"
                                type="text"
                                placeholder="e.g. From paperclip to house"
                            />
                            <InputError :message="errors.title" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="story">Your story</Label>
                            <RichTextEditor
                                id="story"
                                v-model="campaignStory"
                                name="story"
                                placeholder="Why are you starting this campaign?"
                                min-height="min-h-[6rem]"
                            />
                            <InputError :message="errors.story" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="category_id">Category</Label>
                            <select
                                id="category_id"
                                v-model="categoryId"
                                name="category_id"
                                class="h-9 w-full rounded-md border border-input bg-[var(--popover)] px-3 py-1 text-base text-[var(--popover-foreground)] shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                            >
                                <option value="">Select a category</option>
                                <option
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.name }}
                                </option>
                            </select>
                            <InputError :message="errors.category_id" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="visibility">Visibility</Label>
                                <select
                                    id="visibility"
                                    v-model="visibility"
                                    name="visibility"
                                    class="h-9 w-full rounded-md border border-input bg-[var(--popover)] px-3 py-1 text-base text-[var(--popover-foreground)] shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                                >
                                    <option value="public">Public</option>
                                    <option value="unlisted">Unlisted</option>
                                </select>
                                <InputError :message="errors.visibility" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="status">Status</Label>
                                <select
                                    id="status"
                                    v-model="status"
                                    name="status"
                                    class="h-9 w-full rounded-md border border-input bg-[var(--popover)] px-3 py-1 text-base text-[var(--popover-foreground)] shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                                >
                                    <option value="draft">Draft</option>
                                    <option value="active">Active</option>
                                </select>
                                <InputError :message="errors.status" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex justify-end gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="processing"
                        @click="cancel"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        variant="brand"
                        :disabled="processing"
                    >
                        <Spinner v-if="processing" />
                        Save changes
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>

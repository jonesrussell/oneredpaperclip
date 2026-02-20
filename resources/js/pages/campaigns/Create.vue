<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
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

const props = defineProps<{
    categories: { id: number; name: string; slug: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Campaigns', href: campaigns.index().url },
    { title: 'Create', href: campaigns.create().url },
];

const inputClasses =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] border-[var(--ink)]/20';
const textareaClasses =
    'min-h-[100px] w-full rounded-md border border-[var(--ink)]/20 bg-transparent px-3 py-2 text-base shadow-xs outline-none md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] resize-y';
</script>

<template>
    <Head title="Create Campaign" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6"
        >
            <div>
                <h1
                    class="font-display text-2xl font-semibold tracking-tight text-[var(--ink)] sm:text-3xl"
                >
                    Create a campaign
                </h1>
                <p class="mt-1 text-[var(--ink-muted)]">
                    Set your start item and goal. Others will make offers to
                    help you trade up.
                </p>
            </div>

            <Form
                v-bind="campaigns.store.form()"
                v-slot="{ errors, processing }"
                class="max-w-2xl space-y-8"
            >
                <Card class="border-[var(--ink)]/10">
                    <CardHeader>
                        <CardTitle class="font-display text-lg">
                            Campaign details
                        </CardTitle>
                        <CardDescription class="text-[var(--ink-muted)]">
                            Optional title and story for your trade-up journey.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="title">Campaign title</Label>
                            <Input
                                id="title"
                                name="title"
                                type="text"
                                maxlength="255"
                                placeholder="e.g. From a red paperclip to a house"
                                :class="inputClasses"
                            />
                            <InputError :message="errors.title" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="story">Story (optional)</Label>
                            <textarea
                                id="story"
                                name="story"
                                rows="4"
                                maxlength="2000"
                                placeholder="Why are you doing this? What’s your goal?"
                                :class="textareaClasses"
                            />
                            <InputError :message="errors.story" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="category_id">Category</Label>
                            <select
                                id="category_id"
                                name="category_id"
                                :class="inputClasses"
                            >
                                <option value="">None</option>
                                <option
                                    v-for="cat in categories"
                                    :key="cat.id"
                                    :value="cat.id"
                                >
                                    {{ cat.name }}
                                </option>
                            </select>
                            <InputError :message="errors.category_id" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Visibility</Label>
                            <div class="flex flex-wrap gap-4">
                                <label
                                    class="flex cursor-pointer items-center gap-2 text-sm text-[var(--ink)]"
                                >
                                    <input
                                        type="radio"
                                        name="visibility"
                                        value="public"
                                        checked
                                        class="border-[var(--ink)]/30 text-[var(--brand-red)] focus:ring-[var(--brand-red)]"
                                    />
                                    Public — anyone can find and offer
                                </label>
                                <label
                                    class="flex cursor-pointer items-center gap-2 text-sm text-[var(--ink)]"
                                >
                                    <input
                                        type="radio"
                                        name="visibility"
                                        value="unlisted"
                                        class="border-[var(--ink)]/30 text-[var(--brand-red)] focus:ring-[var(--brand-red)]"
                                    />
                                    Unlisted — only via link
                                </label>
                            </div>
                            <InputError :message="errors.visibility" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Status</Label>
                            <div class="flex flex-wrap gap-4">
                                <label
                                    class="flex cursor-pointer items-center gap-2 text-sm text-[var(--ink)]"
                                >
                                    <input
                                        type="radio"
                                        name="status"
                                        value="active"
                                        checked
                                        class="border-[var(--ink)]/30 text-[var(--brand-red)] focus:ring-[var(--brand-red)]"
                                    />
                                    Active — accept offers now
                                </label>
                                <label
                                    class="flex cursor-pointer items-center gap-2 text-sm text-[var(--ink)]"
                                >
                                    <input
                                        type="radio"
                                        name="status"
                                        value="draft"
                                        class="border-[var(--ink)]/30 text-[var(--brand-red)] focus:ring-[var(--brand-red)]"
                                    />
                                    Draft — finish later
                                </label>
                            </div>
                            <InputError :message="errors.status" />
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-[var(--ink)]/10">
                    <CardHeader>
                        <CardTitle class="font-display text-lg">
                            Start item
                        </CardTitle>
                        <CardDescription class="text-[var(--ink-muted)]">
                            What you have right now. This is where your journey
                            begins.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="start_item.title">Item title *</Label>
                            <Input
                                id="start_item.title"
                                name="start_item[title]"
                                type="text"
                                required
                                maxlength="255"
                                placeholder="e.g. One red paperclip"
                                :class="inputClasses"
                            />
                            <InputError :message="errors['start_item.title']" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="start_item.description"
                                >Description (optional)</Label
                            >
                            <textarea
                                id="start_item.description"
                                name="start_item[description]"
                                rows="2"
                                maxlength="2000"
                                placeholder="Condition, details…"
                                :class="textareaClasses"
                            />
                            <InputError
                                :message="errors['start_item.description']"
                            />
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-[var(--ink)]/10">
                    <CardHeader>
                        <CardTitle class="font-display text-lg">
                            Goal item
                        </CardTitle>
                        <CardDescription class="text-[var(--ink-muted)]">
                            What you’re trading toward. Dream big.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="goal_item.title">Goal title *</Label>
                            <Input
                                id="goal_item.title"
                                name="goal_item[title]"
                                type="text"
                                required
                                maxlength="255"
                                placeholder="e.g. A house"
                                :class="inputClasses"
                            />
                            <InputError :message="errors['goal_item.title']" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="goal_item.description"
                                >Description (optional)</Label
                            >
                            <textarea
                                id="goal_item.description"
                                name="goal_item[description]"
                                rows="2"
                                maxlength="2000"
                                placeholder="Any specifics?"
                                :class="textareaClasses"
                            />
                            <InputError
                                :message="errors['goal_item.description']"
                            />
                        </div>
                    </CardContent>
                </Card>

                <div class="flex flex-wrap gap-4">
                    <Button
                        type="submit"
                        variant="brand"
                        :disabled="processing"
                    >
                        <Spinner v-if="processing" />
                        Create campaign
                    </Button>
                    <Button variant="ghost" as-child>
                        <Link :href="campaigns.index()">Cancel</Link>
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>

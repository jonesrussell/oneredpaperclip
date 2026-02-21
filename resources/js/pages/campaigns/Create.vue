<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';

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

defineProps<{
    categories: { id: number; name: string; slug: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Campaigns', href: '/campaigns' },
    { title: 'Create', href: '/campaigns/create' },
];

const currentStep = ref(1);
const totalSteps = 4;

const startTitle = ref('');
const startDescription = ref('');
const goalTitle = ref('');
const goalDescription = ref('');
const campaignTitle = ref('');
const campaignStory = ref('');
const categoryId = ref('');
const visibility = ref('public');
const status = ref('draft');

const stepLabels = [
    'What do you have?',
    "What's your dream item?",
    'Tell your story',
    'Review & launch',
];

function nextStep(): void {
    if (currentStep.value < totalSteps) {
        currentStep.value++;
    }
}

function prevStep(): void {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
}

function goToStep(step: number): void {
    if (step >= 1 && step <= totalSteps) {
        currentStep.value = step;
    }
}
</script>

<template>
    <Head title="Create Campaign" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-2xl px-4 py-8">
            <!-- Step progress bar -->
            <div class="mb-8 flex items-center justify-center gap-2">
                <template v-for="step in totalSteps" :key="step">
                    <button
                        type="button"
                        class="flex size-9 items-center justify-center rounded-full text-sm font-bold transition-all"
                        :class="
                            step <= currentStep
                                ? 'bg-[var(--brand-red)] text-white shadow-md'
                                : step === currentStep + 1
                                  ? 'border-2 border-[var(--border)] bg-white text-[var(--ink-muted)]'
                                  : 'bg-[var(--muted)] text-[var(--ink-muted)]'
                        "
                        :aria-label="`Step ${step}: ${stepLabels[step - 1]}`"
                        :aria-current="
                            step === currentStep ? 'step' : undefined
                        "
                        @click="goToStep(step)"
                    >
                        {{ step }}
                    </button>
                    <div
                        v-if="step < totalSteps"
                        class="h-0.5 w-8 rounded-full transition-colors"
                        :class="
                            step < currentStep
                                ? 'bg-[var(--brand-red)]'
                                : 'bg-[var(--border)]'
                        "
                    />
                </template>
            </div>

            <!-- Step label -->
            <h1
                class="mb-6 text-center font-display text-2xl font-semibold text-[var(--ink)]"
            >
                {{ stepLabels[currentStep - 1] }}
            </h1>

            <!-- Form -->
            <Form
                v-bind="campaigns.store.form()"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-6"
            >
                <!-- Step 1: Start item -->
                <div v-show="currentStep === 1">
                    <Card class="border-[var(--border)]">
                        <CardHeader>
                            <CardTitle class="font-display text-lg"
                                >Your start item</CardTitle
                            >
                            <CardDescription>
                                What do you have to trade? This is where your
                                journey begins.
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
                                    autofocus
                                    placeholder="e.g. Red paperclip"
                                />
                                <InputError
                                    :message="errors['start_item.title']"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="start_item_description"
                                    >Description</Label
                                >
                                <textarea
                                    id="start_item_description"
                                    v-model="startDescription"
                                    name="start_item[description]"
                                    rows="3"
                                    placeholder="Describe your item..."
                                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                                />
                                <InputError
                                    :message="errors['start_item.description']"
                                />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Step 2: Goal item -->
                <div v-show="currentStep === 2">
                    <Card class="border-[var(--border)]">
                        <CardHeader>
                            <CardTitle class="font-display text-lg"
                                >Your dream item</CardTitle
                            >
                            <CardDescription>
                                What are you hoping to trade up to? Dream big.
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
                                <InputError
                                    :message="errors['goal_item.title']"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="goal_item_description"
                                    >Description</Label
                                >
                                <textarea
                                    id="goal_item_description"
                                    v-model="goalDescription"
                                    name="goal_item[description]"
                                    rows="3"
                                    placeholder="Describe your dream item..."
                                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                                />
                                <InputError
                                    :message="errors['goal_item.description']"
                                />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Step 3: Campaign details -->
                <div v-show="currentStep === 3">
                    <Card class="border-[var(--border)]">
                        <CardHeader>
                            <CardTitle class="font-display text-lg"
                                >Campaign details</CardTitle
                            >
                            <CardDescription>
                                Give your campaign a name and tell people why
                                you're trading.
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
                                <textarea
                                    id="story"
                                    v-model="campaignStory"
                                    name="story"
                                    rows="4"
                                    placeholder="Why are you starting this campaign?"
                                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                                />
                                <InputError :message="errors.story" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="category_id">Category</Label>
                                <select
                                    id="category_id"
                                    v-model="categoryId"
                                    name="category_id"
                                    class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
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
                                        class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                                    >
                                        <option value="public">Public</option>
                                        <option value="unlisted">
                                            Unlisted
                                        </option>
                                    </select>
                                    <InputError :message="errors.visibility" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="status">Status</Label>
                                    <select
                                        id="status"
                                        v-model="status"
                                        name="status"
                                        class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                                    >
                                        <option value="draft">Draft</option>
                                        <option value="active">Active</option>
                                    </select>
                                    <InputError :message="errors.status" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Step 4: Review & launch -->
                <div v-show="currentStep === 4">
                    <Card class="border-[var(--border)]">
                        <CardHeader>
                            <CardTitle class="font-display text-lg"
                                >Review your campaign</CardTitle
                            >
                            <CardDescription>
                                Make sure everything looks good before
                                launching.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="rounded-xl bg-[var(--paper)] p-4">
                                <p
                                    class="text-xs font-medium tracking-wider text-[var(--ink-muted)] uppercase"
                                >
                                    Start item
                                </p>
                                <p
                                    class="font-display font-semibold text-[var(--ink)]"
                                >
                                    {{ startTitle || 'Not set' }}
                                </p>
                                <p
                                    v-if="startDescription"
                                    class="mt-1 text-sm text-[var(--ink-muted)]"
                                >
                                    {{ startDescription }}
                                </p>
                            </div>
                            <div class="flex items-center justify-center">
                                <span class="text-lg text-[var(--brand-red)]"
                                    >&darr;</span
                                >
                            </div>
                            <div class="rounded-xl bg-[var(--paper)] p-4">
                                <p
                                    class="text-xs font-medium tracking-wider text-[var(--ink-muted)] uppercase"
                                >
                                    Goal item
                                </p>
                                <p
                                    class="font-display font-semibold text-[var(--ink)]"
                                >
                                    {{ goalTitle || 'Not set' }}
                                </p>
                                <p
                                    v-if="goalDescription"
                                    class="mt-1 text-sm text-[var(--ink-muted)]"
                                >
                                    {{ goalDescription }}
                                </p>
                            </div>
                            <div
                                v-if="campaignTitle"
                                class="rounded-xl bg-[var(--paper)] p-4"
                            >
                                <p
                                    class="text-xs font-medium tracking-wider text-[var(--ink-muted)] uppercase"
                                >
                                    Campaign
                                </p>
                                <p
                                    class="font-display font-semibold text-[var(--ink)]"
                                >
                                    {{ campaignTitle }}
                                </p>
                                <p
                                    v-if="campaignStory"
                                    class="mt-1 text-sm text-[var(--ink-muted)]"
                                >
                                    {{ campaignStory }}
                                </p>
                            </div>
                            <div class="flex gap-3">
                                <div
                                    v-if="visibility"
                                    class="rounded-xl bg-[var(--paper)] px-3 py-2"
                                >
                                    <p
                                        class="text-xs font-medium tracking-wider text-[var(--ink-muted)] uppercase"
                                    >
                                        Visibility
                                    </p>
                                    <p
                                        class="text-sm font-medium text-[var(--ink)] capitalize"
                                    >
                                        {{ visibility }}
                                    </p>
                                </div>
                                <div
                                    v-if="status"
                                    class="rounded-xl bg-[var(--paper)] px-3 py-2"
                                >
                                    <p
                                        class="text-xs font-medium tracking-wider text-[var(--ink-muted)] uppercase"
                                    >
                                        Status
                                    </p>
                                    <p
                                        class="text-sm font-medium text-[var(--ink)] capitalize"
                                    >
                                        {{ status }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Navigation buttons -->
                <div class="flex justify-between gap-3">
                    <Button
                        v-if="currentStep > 1"
                        type="button"
                        variant="outline"
                        @click="prevStep"
                    >
                        Back
                    </Button>
                    <span v-else />
                    <Button
                        v-if="currentStep < totalSteps"
                        type="button"
                        variant="brand"
                        @click="nextStep"
                    >
                        Next
                    </Button>
                    <Button
                        v-if="currentStep === totalSteps"
                        type="submit"
                        variant="brand"
                        :disabled="processing"
                    >
                        <Spinner v-if="processing" />
                        Launch campaign
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>

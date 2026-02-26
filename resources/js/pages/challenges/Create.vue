<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import DOMPurify from 'dompurify';
import { Sparkles } from 'lucide-vue-next';
import { ref } from 'vue';
import { aiSuggest } from '@/actions/App/Http/Controllers/CampaignController';

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

defineProps<{
    categories: { id: number; name: string; slug: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Challenges', href: '/campaigns' },
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

const aiSuggestLoading = ref<'start_item' | 'goal_item' | 'story' | null>(null);
const aiSuggestError = ref<string | null>(null);

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

/** Sanitize HTML for safe display in review step. */
function sanitizeForReview(html: string): string {
    if (!html) return '';
    return DOMPurify.sanitize(html, {
        ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'a'],
        ALLOWED_ATTR: ['href', 'target', 'rel'],
    });
}

function getCsrfToken(): string | null {
    if (typeof document === 'undefined') return null;
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    if (!match) return null;
    try {
        return decodeURIComponent(match[1]);
    } catch {
        return match[1];
    }
}

function htmlToPlainText(html: string): string {
    if (!html) return '';
    return html.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
}

async function requestAiSuggest(
    context: 'start_item' | 'goal_item' | 'story',
    currentHtml: string,
    titleHint: string,
): Promise<void> {
    aiSuggestError.value = null;
    aiSuggestLoading.value = context;
    try {
        const headers: Record<string, string> = {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        };
        const csrf = getCsrfToken();
        if (csrf) headers['X-XSRF-TOKEN'] = csrf;
        const res = await fetch(aiSuggest.url(), {
            method: 'POST',
            credentials: 'include',
            headers,
            body: JSON.stringify({
                context,
                current_text: htmlToPlainText(currentHtml),
                title_hint: titleHint || undefined,
            }),
        });
        const data = (await res.json()) as { suggestion?: string };
        if (!res.ok) {
            aiSuggestError.value =
                (data as { message?: string }).message ?? 'Request failed';
            return;
        }
        const suggestion = data.suggestion ?? '';
        const wrapped = suggestion ? `<p>${suggestion}</p>` : '';
        if (context === 'start_item') startDescription.value = wrapped;
        else if (context === 'goal_item') goalDescription.value = wrapped;
        else campaignStory.value = wrapped;
    } catch {
        aiSuggestError.value = 'Something went wrong. Try again.';
    } finally {
        aiSuggestLoading.value = null;
    }
}
</script>

<template>
    <Head title="Create challenge" />

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
                                <div
                                    class="flex flex-wrap items-center justify-between gap-2"
                                >
                                    <Label for="start_item_description"
                                        >Description</Label
                                    >
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="gap-1.5 text-xs"
                                        :disabled="
                                            aiSuggestLoading !== null ||
                                            !startTitle.trim()
                                        "
                                        @click="
                                            requestAiSuggest(
                                                'start_item',
                                                startDescription,
                                                startTitle,
                                            )
                                        "
                                    >
                                        <Spinner
                                            v-if="
                                                aiSuggestLoading ===
                                                'start_item'
                                            "
                                            class="size-3.5"
                                        />
                                        <Sparkles
                                            v-else
                                            class="size-3.5"
                                            aria-hidden
                                        />
                                        Improve with AI
                                    </Button>
                                </div>
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
                                <p
                                    v-if="
                                        aiSuggestError &&
                                        aiSuggestLoading === null
                                    "
                                    class="text-xs text-destructive"
                                >
                                    {{ aiSuggestError }}
                                </p>
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
                                <div
                                    class="flex flex-wrap items-center justify-between gap-2"
                                >
                                    <Label for="goal_item_description"
                                        >Description</Label
                                    >
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="gap-1.5 text-xs"
                                        :disabled="
                                            aiSuggestLoading !== null ||
                                            !goalTitle.trim()
                                        "
                                        @click="
                                            requestAiSuggest(
                                                'goal_item',
                                                goalDescription,
                                                goalTitle,
                                            )
                                        "
                                    >
                                        <Spinner
                                            v-if="
                                                aiSuggestLoading ===
                                                'goal_item'
                                            "
                                            class="size-3.5"
                                        />
                                        <Sparkles
                                            v-else
                                            class="size-3.5"
                                            aria-hidden
                                        />
                                        Improve with AI
                                    </Button>
                                </div>
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
                                <p
                                    v-if="
                                        aiSuggestError &&
                                        aiSuggestLoading === null
                                    "
                                    class="text-xs text-destructive"
                                >
                                    {{ aiSuggestError }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Step 3: Challenge details -->
                <div v-show="currentStep === 3">
                    <Card class="border-[var(--border)]">
                        <CardHeader>
                            <CardTitle class="font-display text-lg"
                                >Challenge details</CardTitle
                            >
                            <CardDescription>
                                Give your challenge a name and tell people why
                                you're trading.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid gap-2">
                                <Label for="title">Challenge title</Label>
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
                                <div
                                    class="flex flex-wrap items-center justify-between gap-2"
                                >
                                    <Label for="story">Your story</Label>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="gap-1.5 text-xs"
                                        :disabled="
                                            aiSuggestLoading !== null ||
                                            !campaignTitle.trim()
                                        "
                                        @click="
                                            requestAiSuggest(
                                                'story',
                                                campaignStory,
                                                campaignTitle,
                                            )
                                        "
                                    >
                                        <Spinner
                                            v-if="
                                                aiSuggestLoading === 'story'
                                            "
                                            class="size-3.5"
                                        />
                                        <Sparkles
                                            v-else
                                            class="size-3.5"
                                            aria-hidden
                                        />
                                        Improve with AI
                                    </Button>
                                </div>
                                <RichTextEditor
                                    id="story"
                                    v-model="campaignStory"
                                    name="story"
                                    placeholder="Why are you starting this challenge?"
                                    min-height="min-h-[6rem]"
                                />
                                <InputError :message="errors.story" />
                                <p
                                    v-if="
                                        aiSuggestError &&
                                        aiSuggestLoading === null
                                    "
                                    class="text-xs text-destructive"
                                >
                                    {{ aiSuggestError }}
                                </p>
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
                </div>

                <!-- Step 4: Review & launch -->
                <div v-show="currentStep === 4">
                    <Card class="border-[var(--border)]">
                        <CardHeader>
                            <CardTitle class="font-display text-lg"
                                >Review your challenge</CardTitle
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
                                <div
                                    v-if="startDescription"
                                    class="mt-1 text-sm text-[var(--ink-muted)] prose prose-sm max-w-none"
                                    v-html="sanitizeForReview(startDescription)"
                                />
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
                                <div
                                    v-if="goalDescription"
                                    class="mt-1 text-sm text-[var(--ink-muted)] prose prose-sm max-w-none"
                                    v-html="sanitizeForReview(goalDescription)"
                                />
                            </div>
                            <div
                                v-if="campaignTitle"
                                class="rounded-xl bg-[var(--paper)] p-4"
                            >
                                <p
                                    class="text-xs font-medium tracking-wider text-[var(--ink-muted)] uppercase"
                                >
                                    Challenge
                                </p>
                                <p
                                    class="font-display font-semibold text-[var(--ink)]"
                                >
                                    {{ campaignTitle }}
                                </p>
                                <div
                                    v-if="campaignStory"
                                    class="mt-1 text-sm text-[var(--ink-muted)] prose prose-sm max-w-none"
                                    v-html="sanitizeForReview(campaignStory)"
                                />
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
                        Launch challenge
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
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

type ItemEdit = {
    id: number;
    title: string;
    description?: string | null;
    image_url?: string | null;
} | null;

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
    { title: 'Challenges', href: '/campaigns' },
    {
        title: props.campaign.title ?? 'Challenge',
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

const aiSuggestLoading = ref<'start_item' | 'goal_item' | 'story' | null>(null);
const aiSuggestError = ref<string | null>(null);
const uploadLoading = ref<'start' | 'goal' | null>(null);
const uploadError = ref<string | null>(null);

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

function cancel(): void {
    router.visit(campaigns.show.url({ campaign: props.campaign.id }));
}

async function uploadItemPhoto(itemId: number, file: File, which: 'start' | 'goal'): Promise<void> {
    uploadError.value = null;
    uploadLoading.value = which;
    try {
        const formData = new FormData();
        formData.append('image', file);
        const csrf = getCsrfToken();
        const headers: HeadersInit = { Accept: 'application/json' };
        if (csrf) headers['X-XSRF-TOKEN'] = csrf;
        const res = await fetch(`/items/${itemId}/media`, {
            method: 'POST',
            credentials: 'include',
            headers,
            body: formData,
        });
        if (!res.ok) {
            const data = (await res.json()) as { message?: string; errors?: Record<string, string[]> };
            const msg = data.message ?? data.errors?.image?.join(' ') ?? 'Upload failed';
            uploadError.value = msg;
            return;
        }
        router.reload();
    } catch {
        uploadError.value = 'Upload failed. Try again.';
    } finally {
        uploadLoading.value = null;
    }
}

function onItemPhotoChange(itemId: number, which: 'start' | 'goal', event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;
    uploadItemPhoto(itemId, file, which);
    input.value = '';
}
</script>

<template>
    <Head
        :title="campaign.title ? `Edit: ${campaign.title}` : 'Edit challenge'"
    />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-2xl px-4 py-8">
            <h1
                class="mb-6 font-display text-2xl font-semibold text-[var(--ink)]"
            >
                Edit challenge
            </h1>

            <p
                v-if="uploadError"
                class="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive"
            >
                {{ uploadError }}
            </p>
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
                                    :disabled="aiSuggestLoading !== null || !startTitle.trim()"
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
                                            aiSuggestLoading === 'start_item'
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
                                    aiSuggestError && aiSuggestLoading === null
                                "
                                class="text-xs text-destructive"
                            >
                                {{ aiSuggestError }}
                            </p>
                        </div>
                        <div v-if="startItem()" class="grid gap-2 border-t border-[var(--border)] pt-4">
                            <Label>Photo</Label>
                            <div class="flex flex-wrap items-center gap-3">
                                <div
                                    class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-[var(--border)] bg-[var(--muted)]"
                                >
                                    <img
                                        v-if="startItem()?.image_url"
                                        :src="startItem()!.image_url!"
                                        alt="Start item"
                                        class="size-full object-cover"
                                    />
                                    <span
                                        v-else
                                        class="text-xs text-[var(--ink-muted)]"
                                    >
                                        No photo
                                    </span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <input
                                        type="file"
                                        accept="image/*"
                                        class="text-sm file:mr-2 file:rounded-md file:border-0 file:bg-[var(--brand-red)] file:px-3 file:py-1.5 file:text-white file:text-sm"
                                        :disabled="uploadLoading === 'start'"
                                        @change="onItemPhotoChange(startItem()!.id, 'start', $event)"
                                    />
                                    <Spinner v-if="uploadLoading === 'start'" class="size-4" />
                                </div>
                            </div>
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
                                    :disabled="aiSuggestLoading !== null || !goalTitle.trim()"
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
                                            aiSuggestLoading === 'goal_item'
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
                                    aiSuggestError && aiSuggestLoading === null
                                "
                                class="text-xs text-destructive"
                            >
                                {{ aiSuggestError }}
                            </p>
                        </div>
                        <div v-if="goalItem()" class="grid gap-2 border-t border-[var(--border)] pt-4">
                            <Label>Photo</Label>
                            <div class="flex flex-wrap items-center gap-3">
                                <div
                                    class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-[var(--border)] bg-[var(--muted)]"
                                >
                                    <img
                                        v-if="goalItem()?.image_url"
                                        :src="goalItem()!.image_url!"
                                        alt="Goal item"
                                        class="size-full object-cover"
                                    />
                                    <span
                                        v-else
                                        class="text-xs text-[var(--ink-muted)]"
                                    >
                                        No photo
                                    </span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <input
                                        type="file"
                                        accept="image/*"
                                        class="text-sm file:mr-2 file:rounded-md file:border-0 file:bg-[var(--brand-red)] file:px-3 file:py-1.5 file:text-white file:text-sm"
                                        :disabled="uploadLoading === 'goal'"
                                        @change="onItemPhotoChange(goalItem()!.id, 'goal', $event)"
                                    />
                                    <Spinner v-if="uploadLoading === 'goal'" class="size-4" />
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-[var(--border)]">
                    <CardHeader>
                        <CardTitle class="font-display text-lg"
                            >Challenge details</CardTitle
                        >
                        <CardDescription>
                            Title, story, category, and visibility.
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
                                    :disabled="aiSuggestLoading !== null || !campaignTitle.trim()"
                                    @click="
                                        requestAiSuggest(
                                            'story',
                                            campaignStory,
                                            campaignTitle,
                                        )
                                    "
                                >
                                    <Spinner
                                        v-if="aiSuggestLoading === 'story'"
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
                                    aiSuggestError && aiSuggestLoading === null
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

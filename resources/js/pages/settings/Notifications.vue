<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

import NotificationPreferencesController from '@/actions/App/Http/Controllers/Settings/NotificationPreferencesController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/notifications';
import { type BreadcrumbItem } from '@/types';

type NotificationPreference = {
    database: boolean;
    email: boolean;
};

type NotificationType = {
    label: string;
    description: string;
};

type Props = {
    preferences: Record<string, NotificationPreference>;
    availableTypes: Record<string, NotificationType>;
    status?: string;
};

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Notification settings',
        href: edit().url,
    },
];

const form = useForm({
    preferences: { ...props.preferences },
});

function submit() {
    form.patch(NotificationPreferencesController.update.url());
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Notification settings" />

        <h1 class="sr-only">Notification Settings</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Notification preferences"
                    description="Choose how you want to be notified about activity on your challenges and offers"
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-4">
                        <Card
                            v-for="(type, key) in availableTypes"
                            :key="key"
                            class="overflow-hidden"
                        >
                            <CardHeader class="pb-2">
                                <CardTitle class="text-base">{{ type.label }}</CardTitle>
                                <CardDescription>{{ type.description }}</CardDescription>
                            </CardHeader>
                            <CardContent class="pt-0">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex items-center gap-6">
                                        <div class="flex items-center gap-2">
                                            <Switch
                                                :id="`${key}-database`"
                                                v-model:checked="form.preferences[key].database"
                                            />
                                            <Label :for="`${key}-database`" class="cursor-pointer text-sm">
                                                In-app
                                            </Label>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <Switch
                                                :id="`${key}-email`"
                                                v-model:checked="form.preferences[key].email"
                                            />
                                            <Label :for="`${key}-email`" class="cursor-pointer text-sm">
                                                Email
                                            </Label>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">
                            Save preferences
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="form.recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                Saved.
                            </p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

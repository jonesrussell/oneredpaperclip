<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { formatDistanceToNow } from 'date-fns';
import { Bell, Check, CheckCheck, Gift, HandshakeIcon, Trophy, XCircle } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import NotificationController from '@/actions/App/Http/Controllers/NotificationController';

type Notification = {
    id: string;
    type: string;
    data: Record<string, unknown>;
    read_at: string | null;
    created_at: string;
};

const page = usePage();
const notifications = ref<Notification[]>([]);
const unreadCount = ref(0);
const loading = ref(false);

const isAuthenticated = computed(() => !!page.props.auth?.user);

async function fetchNotifications() {
    if (!isAuthenticated.value) return;

    loading.value = true;
    try {
        const response = await fetch(NotificationController.index.url());
        const data = await response.json();
        notifications.value = data.notifications;
        unreadCount.value = data.unread_count;
    } catch (error) {
        console.error('Failed to fetch notifications:', error);
    } finally {
        loading.value = false;
    }
}

async function markAsRead(id: string) {
    try {
        await fetch(NotificationController.markAsRead.url({ id }), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN':
                    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });
        const notification = notifications.value.find((n) => n.id === id);
        if (notification) {
            notification.read_at = new Date().toISOString();
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        }
    } catch (error) {
        console.error('Failed to mark notification as read:', error);
    }
}

async function markAllAsRead() {
    try {
        await fetch(NotificationController.markAllAsRead.url(), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN':
                    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });
        notifications.value.forEach((n) => {
            if (!n.read_at) {
                n.read_at = new Date().toISOString();
            }
        });
        unreadCount.value = 0;
    } catch (error) {
        console.error('Failed to mark all notifications as read:', error);
    }
}

function getNotificationIcon(type: string) {
    switch (type) {
        case 'offer_received':
            return Gift;
        case 'offer_accepted':
            return Check;
        case 'offer_declined':
            return XCircle;
        case 'trade_pending_confirmation':
            return HandshakeIcon;
        case 'trade_completed':
            return CheckCheck;
        case 'challenge_completed':
            return Trophy;
        default:
            return Bell;
    }
}

function getNotificationTitle(notification: Notification): string {
    const data = notification.data as Record<string, string>;
    switch (notification.type) {
        case 'offer_received':
            return `${data.from_user_name || 'Someone'} made an offer`;
        case 'offer_accepted':
            return 'Your offer was accepted!';
        case 'offer_declined':
            return 'Your offer was declined';
        case 'trade_pending_confirmation':
            return `${data.confirmed_by_user_name || 'Someone'} confirmed the trade`;
        case 'trade_completed':
            return 'Trade completed!';
        case 'challenge_completed':
            return `Challenge "${data.challenge_title || ''}" completed!`;
        default:
            return 'New notification';
    }
}

function getNotificationDescription(notification: Notification): string {
    const data = notification.data as Record<string, string>;
    switch (notification.type) {
        case 'offer_received':
            return `On "${data.challenge_title || 'your challenge'}"`;
        case 'offer_accepted':
            return `For "${data.challenge_title || 'a challenge'}"`;
        case 'offer_declined':
            return `For "${data.challenge_title || 'a challenge'}"`;
        case 'trade_pending_confirmation':
            return 'Waiting for your confirmation';
        case 'trade_completed':
            return `New item: ${data.new_item_title || 'Unknown'}`;
        case 'challenge_completed':
            return `Goal reached: ${data.goal_item_title || 'Unknown'}`;
        default:
            return '';
    }
}

function formatTime(dateString: string): string {
    return formatDistanceToNow(new Date(dateString), { addSuffix: true });
}

onMounted(() => {
    fetchNotifications();
});
</script>

<template>
    <DropdownMenu v-if="isAuthenticated">
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="relative">
                <Bell class="size-5" />
                <span
                    v-if="unreadCount > 0"
                    class="bg-brand-red absolute -right-0.5 -top-0.5 flex size-4 items-center justify-center rounded-full text-[10px] font-medium text-white"
                >
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
                <span class="sr-only">Notifications</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-80">
            <DropdownMenuLabel class="flex items-center justify-between">
                <span>Notifications</span>
                <Button
                    v-if="unreadCount > 0"
                    variant="ghost"
                    size="sm"
                    class="h-auto p-0 text-xs text-muted-foreground hover:text-foreground"
                    @click.prevent.stop="markAllAsRead"
                >
                    Mark all as read
                </Button>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />

            <div v-if="loading" class="p-4 text-center text-sm text-muted-foreground">
                Loading...
            </div>

            <div
                v-else-if="notifications.length === 0"
                class="p-4 text-center text-sm text-muted-foreground"
            >
                No notifications yet
            </div>

            <div v-else class="max-h-80 overflow-y-auto">
                <DropdownMenuItem
                    v-for="notification in notifications"
                    :key="notification.id"
                    class="flex cursor-pointer items-start gap-3 p-3"
                    :class="{ 'bg-muted/50': !notification.read_at }"
                    @click="markAsRead(notification.id)"
                >
                    <div
                        class="flex size-8 shrink-0 items-center justify-center rounded-full"
                        :class="{
                            'bg-green-100 text-green-600 dark:bg-green-900/30':
                                notification.type === 'offer_accepted' ||
                                notification.type === 'trade_completed' ||
                                notification.type === 'challenge_completed',
                            'bg-blue-100 text-blue-600 dark:bg-blue-900/30':
                                notification.type === 'offer_received' ||
                                notification.type === 'trade_pending_confirmation',
                            'bg-gray-100 text-gray-600 dark:bg-gray-800':
                                notification.type === 'offer_declined',
                        }"
                    >
                        <component
                            :is="getNotificationIcon(notification.type)"
                            class="size-4"
                        />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium leading-tight">
                            {{ getNotificationTitle(notification) }}
                        </p>
                        <p
                            v-if="getNotificationDescription(notification)"
                            class="truncate text-xs text-muted-foreground"
                        >
                            {{ getNotificationDescription(notification) }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ formatTime(notification.created_at) }}
                        </p>
                    </div>
                    <div
                        v-if="!notification.read_at"
                        class="mt-1 size-2 shrink-0 rounded-full bg-brand-red"
                    />
                </DropdownMenuItem>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

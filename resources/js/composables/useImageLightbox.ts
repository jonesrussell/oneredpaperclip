import type { Ref } from 'vue';
import { ref } from 'vue';

export function useImageLightbox(): {
    open: Ref<boolean>;
    imageUrl: Ref<string | null>;
    title: Ref<string>;
    openLightbox: (url: string | null, title: string) => void;
    onOpenChange: (value: boolean) => void;
} {
    const open = ref(false);
    const imageUrl = ref<string | null>(null);
    const title = ref('');

    function openLightbox(url: string | null, caption: string): void {
        if (!url) return;
        imageUrl.value = url;
        title.value = caption;
        open.value = true;
    }

    function onOpenChange(value: boolean): void {
        open.value = value;
        if (!value) imageUrl.value = null;
    }

    return {
        open,
        imageUrl,
        title,
        openLightbox,
        onOpenChange,
    };
}

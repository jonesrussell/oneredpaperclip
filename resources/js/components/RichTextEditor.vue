<script setup lang="ts">

import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { onBeforeUnmount, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        id?: string;
        modelValue?: string;
        name?: string;
        placeholder?: string;
        minHeight?: string;
    }>(),
    {
        modelValue: '',
        placeholder: '',
        minHeight: 'min-h-[4.5rem]',
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const editor = useEditor({
    content: props.modelValue || '',
    extensions: [
        StarterKit,
        Link.configure({ openOnClick: false }),
        Placeholder.configure({ placeholder: props.placeholder }),
    ],
    editorProps: {
        attributes: {
            class:
                'prose prose-sm max-w-none focus:outline-none ' +
                'prose-p:my-1 prose-ul:my-1 prose-ol:my-1',
        },
    },
    onUpdate: ({ editor: ed }) => {
        emit('update:modelValue', ed.getHTML());
    },
});

watch(
    () => props.modelValue,
    (newVal) => {
        const ed = editor.value;
        if (!ed) return;
        const current = ed.getHTML();
        if (newVal !== current) {
            ed.commands.setContent(newVal ?? '', false);
        }
    },
);

onBeforeUnmount(() => {
    editor.value?.destroy();
});
</script>

<template>
    <div
        class="w-full rounded-md border border-input bg-transparent shadow-xs transition-[color,box-shadow] outline-none focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50"
        :class="[minHeight]"
    >
        <EditorContent
            :editor="editor"
            :id="id"
            class="rich-text-editor-content px-3 py-2 text-base md:text-sm"
        />
    </div>
    <input
        v-if="name"
        type="hidden"
        :name="name"
        :value="modelValue"
    />
</template>

<style>
.rich-text-editor-content .ProseMirror p.is-editor-empty:first-child::before {
    color: var(--muted-foreground);
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
}
</style>

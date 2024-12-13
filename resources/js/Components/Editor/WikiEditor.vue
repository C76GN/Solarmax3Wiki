<template>
    <div class="wiki-editor">
        <textarea :id="id" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" ref="editor">
        </textarea>
    </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount } from 'vue';
import 'tinymce/tinymce';

const props = defineProps({
    id: {
        type: String,
        default: 'content'
    },
    modelValue: {
        type: String,
        default: ''
    },
    init: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['update:modelValue']);

onMounted(() => {
    const editor = tinymce.init({
        ...props.init,
        selector: `textarea#${props.id}`,
        setup: (editor) => {
            editor.on('input change undo redo', () => {
                emit('update:modelValue', editor.getContent());
            });
        },
    });
});

onBeforeUnmount(() => {
    tinymce.get(props.id)?.destroy();
});
</script>

<style>
.wiki-editor {
    margin-bottom: 1rem;
}
</style>
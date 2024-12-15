<template>
    <div class="wiki-editor relative">
        <textarea :id="id" :value="modelValue" @input="handleInput" ref="editor"></textarea>
        <WikiLinkAutocomplete v-if="showAutocomplete" :show="showAutocomplete" :query="autocompleteQuery"
            :editor="editor" @select="insertWikiLink" @hide="hideAutocomplete" :style="autocompletePosition" />
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import WikiLinkAutocomplete from '@/Components/Wiki/WikiLinkAutocomplete.vue';

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
const editor = ref(null);
const showAutocomplete = ref(false);
const autocompleteQuery = ref('');
const autocompletePosition = ref({});

// 计算编辑器配置
const editorConfig = computed(() => ({
    ...props.init,
    selector: `textarea#${props.id}`,
    skin: 'oxide',
    content_css: 'default',
    height: 500,
    menubar: false,
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'wikilink'
    ],
    toolbar: 'undo redo | formatselect | ' +
        'bold italic backcolor | alignleft aligncenter ' +
        'alignright alignjustify | bullist numlist outdent indent | ' +
        'removeformat | wikilink | help',
    setup: (ed) => {
        editor.value = ed;

        // 监听内容变化
        ed.on('input change undo redo', () => {
            emit('update:modelValue', ed.getContent());
        });

        // 监听 Wiki 链接输入
        ed.on('keyup', (e) => {
            const content = ed.getContent();
            const pos = ed.selection.getStart();
            const lastOpenBracket = content.lastIndexOf('[[', pos);

            if (lastOpenBracket !== -1 && content.indexOf(']]', lastOpenBracket) === -1) {
                const query = content.substring(lastOpenBracket + 2, pos);
                if (query.length > 0) {
                    showAutocomplete.value = true;
                    autocompleteQuery.value = query;

                    // 计算自动完成框的位置
                    const rect = ed.selection.getBoundingClientRect();
                    autocompletePosition.value = {
                        position: 'absolute',
                        top: `${rect.bottom + window.scrollY}px`,
                        left: `${rect.left}px`,
                        zIndex: 1000
                    };
                }
            } else {
                hideAutocomplete();
            }
        });
    }
}));

const handleInput = (e) => {
    emit('update:modelValue', e.target.value);
};

const insertWikiLink = (page) => {
    if (editor.value) {
        editor.value.insertContent(`[[${page.title}]]`);
    }
    hideAutocomplete();
};

const hideAutocomplete = () => {
    showAutocomplete.value = false;
    autocompleteQuery.value = '';
};

onMounted(() => {
    tinymce.init(editorConfig.value);
});

onBeforeUnmount(() => {
    if (editor.value) {
        editor.value.destroy();
    }
});
</script>

<style>
.wiki-editor {
    position: relative;
}

.tox-tinymce {
    border-radius: 0.375rem;
    border-color: #e5e7eb !important;
}

.tox-tinymce:focus-within {
    border-color: #3b82f6 !important;
    outline: 2px solid #93c5fd;
    outline-offset: 2px;
}
</style>
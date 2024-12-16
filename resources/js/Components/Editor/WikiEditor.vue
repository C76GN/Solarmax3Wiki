// FileName: /var/www/Solarmax3Wiki/resources/js/Components/Editor/WikiEditor.vue
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
import { useEditor } from '@/plugins/tinymce';

const props = defineProps({
    id: {
        type: String,
        default: 'content'
    },
    modelValue: {
        type: String,
        default: ''
    },
});

const emit = defineEmits(['update:modelValue']);
const editor = ref(null);
const showAutocomplete = ref(false);
const autocompleteQuery = ref('');
const autocompletePosition = ref({});

// 获取基础编辑器配置
const { init: baseInit } = useEditor();

// 扩展编辑器配置
const editorConfig = computed(() => ({
    ...baseInit,
    selector: `textarea#${props.id}`,
    plugins: [
        ...baseInit.plugins,
        'wikilink'
    ],
    toolbar: baseInit.toolbar + ' | wikilink',
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
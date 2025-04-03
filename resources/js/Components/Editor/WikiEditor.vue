<template>
    <div class="wiki-editor relative">
        <WikiPreview :content="modelValue" initialMode="edit" @mode-change="handleModeChange">
            <textarea :id="id" :value="modelValue" @input="handleInput" ref="editor"></textarea>
        </WikiPreview>

        <WikiLinkAutocomplete v-if="showAutocomplete" :show="showAutocomplete" :query="autocompleteQuery"
            :editor="editor" @select="insertWikiLink" @hide="hideAutocomplete" :style="autocompletePosition" />

        <div v-if="autoSaveStatus" class="mt-2 text-sm text-gray-500 auto-save-status"
            :class="{ 'auto-save-flash': autoSaveFlash }">
            {{ autoSaveStatus }}
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue';
import { debounce } from 'lodash';
import WikiLinkAutocomplete from '@/Components/Wiki/WikiLinkAutocomplete.vue';
import WikiPreview from '@/Components/Wiki/WikiPreview.vue';
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
    autoSaveKey: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue', 'auto-save']);
const editor = ref(null);
const showAutocomplete = ref(false);
const autocompleteQuery = ref('');
const autocompletePosition = ref({});
const autoSaveStatus = ref('');
const autoSaveFlash = ref(false);
const editorMode = ref('edit');

// 获取基础编辑器配置
const { init: baseInit } = useEditor();

// 自动保存功能
const autoSave = debounce(() => {
    if (!props.autoSaveKey) return;
    const content = editor.value ? editor.value.getContent() : '';
    const saveData = {
        content,
        timestamp: new Date().toISOString()
    };
    localStorage.setItem(props.autoSaveKey, JSON.stringify(saveData));
    autoSaveStatus.value = `草稿已自动保存 (${new Date().toLocaleTimeString()})`;
    // 添加闪烁效果
    autoSaveFlash.value = true;
    setTimeout(() => {
        autoSaveFlash.value = false;
    }, 1000);
    emit('auto-save', saveData);
}, 2000);

// 加载自动保存的内容
const loadSavedContent = () => {
    if (!props.autoSaveKey) return false;

    try {
        const savedData = localStorage.getItem(props.autoSaveKey);
        if (!savedData) return false;

        const parsed = JSON.parse(savedData);
        if (parsed.content && editor.value) {
            editor.value.setContent(parsed.content);
            emit('update:modelValue', parsed.content);

            const savedTime = new Date(parsed.timestamp);
            autoSaveStatus.value = `草稿已加载 (${savedTime.toLocaleString()})`;
            return true;
        }
    } catch (e) {
        console.error('加载草稿失败:', e);
    }

    return false;
};

// 扩展编辑器配置
const editorConfig = computed(() => {
  return useEditor({
    selector: `textarea#${props.id}`,
    plugins: [...baseInit.plugins, 'wikilink', 'autosave', 'pagebreak'],
    toolbar: baseInit.toolbar + ' | previewToggle',
    setup: (ed) => {
      editor.value = ed;
      
      ed.on('input change undo redo', () => {
        emit('update:modelValue', ed.getContent());
        autoSave();
      });
      
      ed.on('keyup', handleKeyup);
      
      // 添加大纲按钮
      ed.ui.registry.addButton('outline', {
        icon: 'unordered-list',
        tooltip: '文章大纲',
        onAction: () => {
          const headings = [];
          const content = ed.getContent();
          const tempEl = document.createElement('div');
          tempEl.innerHTML = content;
          
          tempEl.querySelectorAll('h1, h2, h3, h4, h5, h6').forEach(el => {
            headings.push({
              level: parseInt(el.tagName.replace('H', '')),
              text: el.textContent,
              id: el.id || ''
            });
          });
          
          ed.windowManager.open({
            title: '文章大纲',
            body: {
              type: 'panel',
              items: [{
                type: 'htmlpanel',
                html: generateOutlineHtml(headings)
              }]
            },
            buttons: [{
              type: 'cancel',
              text: '关闭'
            }],
            width: 400,
            height: 300
          });
        }
      });
    },
    autosave_ask_before_unload: true,
    autosave_interval: '30s',
    autosave_prefix: `wiki_${props.id}_`
  }).init;
});

// 生成大纲HTML
const generateOutlineHtml = (headings) => {
    let html = '<div style="max-height: 250px; overflow-y: auto;">';
    html += '<ul style="list-style-type: none; padding: 0; margin: 0;">';

    headings.forEach(heading => {
        const padding = (heading.level - 1) * 15;
        html += `<li style="padding: 5px 0 5px ${padding}px; cursor: pointer;" 
             data-id="${heading.id}" 
             onclick="parent.postMessage({mceAction: 'scrollToHeading', id: '${heading.id}'}, '*')">
             ${heading.text}</li>`;
    });

    html += '</ul></div>';
    return html;
};

const handleInput = (e) => {
    emit('update:modelValue', e.target.value);
    autoSave();
};

const handleKeyup = (e) => {
    const ed = editor.value;
    if (!ed) return;

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
};

const insertWikiLink = (page) => {
    if (editor.value) {
        editor.value.insertContent(`<span class="mce-wikilink" data-wikilink="${page.title}">${page.title}</span>`);
    }
    hideAutocomplete();
};

const hideAutocomplete = () => {
    showAutocomplete.value = false;
    autocompleteQuery.value = '';
};

const handleModeChange = (mode) => {
    editorMode.value = mode;

    // 如果切换到预览模式，确保编辑器内容是最新的
    if (mode === 'preview' && editor.value) {
        emit('update:modelValue', editor.value.getContent());
    }
};

// 监听窗口消息，用于大纲点击滚动
const handleWindowMessage = (event) => {
    if (event.data && event.data.mceAction === 'scrollToHeading' && editor.value) {
        const id = event.data.id;
        if (!id) return;

        editor.value.focus();
        const element = editor.value.dom.get(id);
        if (element) {
            editor.value.selection.scrollIntoView(element);
        }
    }
};

onMounted(() => {
    tinymce.init(editorConfig.value);
    window.addEventListener('message', handleWindowMessage);
});

onBeforeUnmount(() => {
    if (editor.value) {
        editor.value.destroy();
        editor.value = null;
    }
    window.removeEventListener('message', handleWindowMessage);
});

// 页面加载时尝试恢复自动保存的内容
watch(() => editor.value, (newEditor) => {
    if (newEditor) {
        setTimeout(() => {
            if (!loadSavedContent() && props.modelValue) {
                newEditor.setContent(props.modelValue);
            }
        }, 100);
    }
}, { immediate: true });
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

.auto-save-status {
    transition: background-color 0.5s ease;
}

.auto-save-flash {
    background-color: #e9ffd9;
    border-radius: 3px;
    padding: 2px 5px;
}

.mce-wikilink {
    color: #0645ad;
    text-decoration: none;
    background-color: #eaf3ff;
    padding: 0 2px;
    border-radius: 2px;
}

.mce-wikilink:hover {
    text-decoration: underline;
}
</style>
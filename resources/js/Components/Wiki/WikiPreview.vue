<template>
  <div class="wiki-preview">
    <div class="wiki-preview-toolbar">
      <button class="wiki-preview-button" :class="{ active: !previewMode }" @click="setMode('edit')">
        <span class="icon">✏️</span>
        编辑
      </button>
      <button class="wiki-preview-button" :class="{ active: previewMode }" @click="setMode('preview')">
        <span class="icon">👁️</span>
        预览
      </button>
    </div>

    <div class="wiki-preview-content">
      <div v-if="previewMode" class="preview-container prose max-w-none p-6 bg-white rounded-lg"
        v-html="sanitizedContent"></div>
      <slot v-else></slot>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import DOMPurify from 'dompurify';

const props = defineProps({
  content: {
    type: String,
    default: ''
  },
  initialMode: {
    type: String,
    default: 'edit'
  }
});

const emit = defineEmits(['mode-change']);
const previewMode = ref(props.initialMode === 'preview');

// 确保内容安全，防止XSS攻击
const sanitizedContent = computed(() => {
  return DOMPurify.sanitize(props.content, {
    ALLOWED_TAGS: [
      'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'br', 'hr',
      'ul', 'ol', 'li', 'dl', 'dt', 'dd',
      'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
      'a', 'span', 'div', 'blockquote', 'code', 'pre',
      'em', 'strong', 'del', 'ins', 'sub', 'sup', 'u', 'strike',
      'img', 'figure', 'figcaption'
    ],
    ALLOWED_ATTR: [
      'id', 'class', 'href', 'target', 'title', 'rel',
      'src', 'alt', 'width', 'height', 'style',
      'data-title'
    ]
  });
});

const setMode = (mode) => {
  try {
    previewMode.value = mode === 'preview';
    emit('mode-change', mode);
  } catch (error) {
    console.error('模式切换错误:', error);
    previewMode.value = !previewMode.value;
  }
};

// 监听初始模式变化
watch(() => props.initialMode, (newMode) => {
  previewMode.value = newMode === 'preview';
});
</script>

<style scoped>
.wiki-preview {
  display: flex;
  flex-direction: column;
  width: 100%;
}

.wiki-preview-toolbar {
  display: flex;
  border-bottom: 1px solid #e5e7eb;
  background-color: #f9fafb;
  border-top-left-radius: 0.375rem;
  border-top-right-radius: 0.375rem;
}

.wiki-preview-button {
  display: flex;
  align-items: center;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #4b5563;
  border: none;
  background: transparent;
  cursor: pointer;
  transition: all 0.2s;
}

.wiki-preview-button .icon {
  margin-right: 0.25rem;
}

.wiki-preview-button:hover {
  background-color: #f3f4f6;
}

.wiki-preview-button.active {
  color: #1d4ed8;
  border-bottom: 2px solid #1d4ed8;
  background-color: #eff6ff;
}

.wiki-preview-content {
  flex: 1;
  overflow: auto;
}

.preview-container {
  color: #374151;
}
</style>
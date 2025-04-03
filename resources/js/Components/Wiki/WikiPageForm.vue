<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div>
            <label class="block text-sm font-medium text-gray-700">页面标题</label>
            <input type="text" v-model="form.title"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                :class="{ 'border-red-500': form.errors.title }">
            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">页面分类</label>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="category in categories" :key="category.id" class="relative flex items-start">
                    <div class="flex h-5 items-center">
                        <input type="checkbox" :value="category.id" v-model="form.categories"
                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    </div>
                    <div class="ml-3 text-sm">
                        <label class="font-medium text-gray-700">{{ category.name }}</label>
                        <p class="text-gray-500">{{ category.description }}</p>
                    </div>
                </div>
            </div>
            <p v-if="form.errors.categories" class="mt-1 text-sm text-red-600">{{ form.errors.categories }}</p>
        </div>

        <div>
            <div class="flex justify-between items-center mb-2">
                <label class="block text-sm font-medium text-gray-700">页面内容</label>
                <div v-if="editorLoaded" class="flex items-center">
                    <span class="text-sm text-gray-500 mr-2">编辑模式：</span>
                    <select v-model="selectedEditor"
                        class="text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="rich">富文本编辑器</option>
                        <option value="split">分屏预览</option>
                    </select>
                </div>
            </div>

            <div ref="editorContainer">
                <!-- 富文本编辑器 -->
                <div v-if="selectedEditor === 'rich'">
                    <WikiEditor id="wiki-content" v-model="form.content" :autoSaveKey="autoSaveKey"
                        @auto-save="handleAutoSave" @content-changed="updateContent" />
                </div>

                <!-- 分屏编辑器 -->
                <div v-else class="border border-gray-300 rounded-md overflow-hidden">
                    <div class="flex border-b border-gray-300">
                        <button class="flex-1 py-2 px-4 text-center text-sm font-medium"
                            :class="splitView === 'edit' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700'"
                            @click="splitView = 'edit'">
                            编辑
                        </button>
                        <button class="flex-1 py-2 px-4 text-center text-sm font-medium"
                            :class="splitView === 'split' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700'"
                            @click="splitView = 'split'">
                            分屏
                        </button>
                        <button class="flex-1 py-2 px-4 text-center text-sm font-medium"
                            :class="splitView === 'preview' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700'"
                            @click="splitView = 'preview'">
                            预览
                        </button>
                    </div>

                    <div class="split-editor-container">
                        <!-- 只编辑 -->
                        <div v-if="splitView === 'edit'" class="h-[500px]">
                            <textarea ref="codeEditor" v-model="form.content"
                                class="w-full h-full p-4 font-mono text-sm border-none focus:ring-0 focus:outline-none"
                                @input="autoSaveDebounced"></textarea>
                        </div>

                        <!-- 分屏编辑 -->
                        <div v-else-if="splitView === 'split'" class="grid grid-cols-2 h-[500px]">
                            <textarea ref="codeEditor" v-model="form.content"
                                class="w-full h-full p-4 font-mono text-sm border-none focus:ring-0 focus:outline-none border-r border-gray-300"
                                @input="autoSaveDebounced"></textarea>
                            <div class="prose prose-sm max-w-none overflow-auto p-4" v-html="sanitizedContent"></div>
                        </div>

                        <!-- 只预览 -->
                        <div v-else class="h-[500px] overflow-auto">
                            <div class="prose prose-sm max-w-none p-4" v-html="sanitizedContent"></div>
                        </div>
                    </div>
                </div>
            </div>

            <p v-if="form.errors.content" class="mt-1 text-sm text-red-600">{{ form.errors.content }}</p>

            <!-- 版本控制和修改说明 -->
            <div v-if="isEditing" class="mt-4">
                <label class="block text-sm font-medium text-gray-700">修改说明</label>
                <input type="text" v-model="form.comment"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="简要描述你的修改（可选）">
            </div>

            <!-- 自动保存状态 -->
            <div v-if="autoSaveStatus" id="autoSaveStatus" class="mt-2 text-sm text-gray-500"
                :class="{ 'highlight-save': autoSaveHighlight }">
                {{ autoSaveStatus }}
            </div>
        </div>

        <!-- 编辑冲突警告 -->
        <div v-if="editConflictWarning" class="bg-amber-50 border-l-4 border-amber-500 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <ExclamationTriangleIcon class="h-5 w-5 text-amber-500" />
                </div>
                <div class="ml-3">
                    <p class="text-sm text-amber-700">
                        <strong>警告：</strong> {{ editConflictWarning }}
                    </p>
                </div>
            </div>
        </div>

        <!-- 当前正在编辑的用户列表 -->
        <div v-if="currentEditors.length > 0" class="bg-blue-50 border-l-4 border-blue-500 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <InformationCircleIcon class="h-5 w-5 text-blue-500" />
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        <strong>提示：</strong> {{ currentEditors.join('、') }} 也在编辑此页面。
                    </p>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-4">
            <Link :href="route('wiki.index')"
                class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-all duration-300">
            取消
            </Link>
            <Button type="submit" variant="primary" :disabled="form.processing || submitting">
                {{ isEditing ? '更新页面' : '创建页面' }}
            </Button>
        </div>
    </form>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import DOMPurify from 'dompurify';
import { ExclamationTriangleIcon, InformationCircleIcon } from '@heroicons/vue/24/outline';
import WikiEditor from '@/Components/Editor/WikiEditor.vue';
import { useEditConflict } from '@/Composables/useEditConflict';
import Button from '@/Components/Buttons/Button.vue';

const props = defineProps({
    page: {
        type: Object,
        default: null
    },
    categories: {
        type: Array,
        required: true
    }
});

const emit = defineEmits(['content-changed']);

// 编辑器配置
const editorContainer = ref(null);
const codeEditor = ref(null);
const selectedEditor = ref('rich');
const splitView = ref('edit');
const editorLoaded = ref(false);
const autoSaveStatus = ref('');
const autoSaveHighlight = ref(false);
const submitting = ref(false);
const editConflictWarning = ref('');

// 检查页面是否为编辑模式
const isEditing = computed(() => !!props.page);

// 安全处理HTML内容
const sanitizedContent = computed(() => {
    return DOMPurify.sanitize(form.content, {
        ALLOWED_TAGS: [
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'br', 'hr',
            'ul', 'ol', 'li', 'dl', 'dt', 'dd',
            'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
            'a', 'span', 'div', 'blockquote', 'code', 'pre',
            'em', 'strong', 'del', 'ins', 'sub', 'sup',
            'img', 'figure', 'figcaption'
        ],
        ALLOWED_ATTR: [
            'id', 'class', 'href', 'target', 'title', 'rel',
            'src', 'alt', 'width', 'height', 'style',
            'data-wikilink'
        ]
    });
});

// 自动保存键
const autoSaveKey = computed(() =>
    isEditing.value
        ? `wiki_edit_${props.page.id}`
        : 'wiki_new_draft'
);

// 表单
const form = useForm({
    title: props.page?.title || '',
    content: props.page?.content || '',
    categories: props.page?.categories || [],
    last_check: props.page?.updated_at || '',
    comment: '',
    force_update: false
});

// 使用编辑冲突检测
const {
    showConflictWarning,
    currentEditors,
    modifiedContent,
    updateContent,
    viewDiff,
    forceSubmit
} = useEditConflict(
    props.page?.id || 0,
    props.page?.content || ''
);

// 监听冲突警告
watch(showConflictWarning, (newVal) => {
    if (newVal) {
        editConflictWarning.value = '在您编辑期间，另一用户已更新了此页面内容。继续提交将覆盖他们的更改。';
    } else {
        editConflictWarning.value = '';
    }
});

// 自动保存功能
const autoSaveDebounced = debounce(() => {
    const saveData = {
        title: form.title,
        content: form.content,
        categories: form.categories,
        timestamp: new Date().toISOString()
    };

    localStorage.setItem(autoSaveKey.value, JSON.stringify(saveData));
    autoSaveStatus.value = `草稿已自动保存 (${new Date().toLocaleTimeString()})`;

    // 显示高亮效果
    autoSaveHighlight.value = true;
    setTimeout(() => {
        autoSaveHighlight.value = false;
    }, 1000);

    // 更新冲突检测中的内容
    updateContent(form.content);

    // 向父组件通知内容变化
    emit('content-changed', form.content);
}, 2000);

// 处理自动保存回调
const handleAutoSave = (data) => {
    autoSaveStatus.value = `草稿已自动保存 (${new Date().toLocaleTimeString()})`;
    autoSaveHighlight.value = true;
    setTimeout(() => {
        autoSaveHighlight.value = false;
    }, 1000);
};

// 从本地存储加载草稿
const loadDraft = () => {
    if (!autoSaveKey.value) return false;

    try {
        const saved = localStorage.getItem(autoSaveKey.value);
        if (!saved) return false;

        const draft = JSON.parse(saved);
        if (!draft) return false;

        if (draft.title) form.title = draft.title;
        if (draft.content) form.content = draft.content;
        if (draft.categories) form.categories = draft.categories;

        const savedTime = new Date(draft.timestamp);
        autoSaveStatus.value = `草稿已加载 (${savedTime.toLocaleString()})`;
        return true;
    } catch (e) {
        console.error('加载草稿失败:', e);
        return false;
    }
};

// 清除草稿
const clearDraft = () => {
    localStorage.removeItem(autoSaveKey.value);
    autoSaveStatus.value = '';
};

// 表单提交
const submit = () => {
    // 检查是否有冲突警告
    if (showConflictWarning.value) {
        if (!confirm('检测到页面内容已被其他用户修改。是否仍要提交您的更改？')) {
            return;
        }
        form.force_update = true;
    }

    submitting.value = true;
    form.last_check = props.page?.updated_at || '';

    if (isEditing.value) {
        form.put(route('wiki.update', props.page.id), {
            onSuccess: () => {
                clearDraft();
                submitting.value = false;
            },
            onError: (errors) => {
                submitting.value = false;
                if (errors.conflict) {
                    console.error("内容冲突:", errors.message);
                    // 显示冲突比较界面
                    viewDiff(form.content);
                }
            }
        });
    } else {
        form.post(route('wiki.store'), {
            onSuccess: () => {
                clearDraft();
                submitting.value = false;
            },
            onError: () => {
                submitting.value = false;
            }
        });
    }
};

// 加载本地存储配置
const loadPreferences = () => {
    // 加载编辑器类型偏好
    const savedEditor = localStorage.getItem('wiki-editor-type');
    if (savedEditor) {
        selectedEditor.value = savedEditor;
    }

    // 加载分屏模式偏好
    const savedSplitView = localStorage.getItem('wiki-split-view');
    if (savedSplitView) {
        splitView.value = savedSplitView;
    }
};

// 保存偏好设置
watch(selectedEditor, (newVal) => {
    localStorage.setItem('wiki-editor-type', newVal);
});

watch(splitView, (newVal) => {
    localStorage.setItem('wiki-split-view', newVal);
});

// 生命周期钩子
onMounted(() => {
    loadPreferences();
    loadDraft(); // 尝试加载草稿
    editorLoaded.value = true;

    // 键盘快捷键
    const handleKeyDown = (e) => {
        // Ctrl+S 保存
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            submit();
        }

        // Ctrl+Shift+P 切换预览
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'p') {
            e.preventDefault();
            if (selectedEditor.value === 'split') {
                if (splitView.value === 'edit') splitView.value = 'preview';
                else if (splitView.value === 'preview') splitView.value = 'split';
                else splitView.value = 'edit';
            } else {
                selectedEditor.value = 'split';
                splitView.value = 'preview';
            }
        }
    };

    window.addEventListener('keydown', handleKeyDown);

    onBeforeUnmount(() => {
        window.removeEventListener('keydown', handleKeyDown);
    });
});
</script>

<style scoped>
.highlight-save {
    background-color: #e6ffe6;
    transition: background-color 1s;
    padding: 2px 4px;
    border-radius: 4px;
}

.split-editor-container {
    border-top: 1px solid #e5e7eb;
}
</style>
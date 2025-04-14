<script setup>
// 保持之前的 imports 不变
import { ref, onMounted, onUnmounted, computed } from 'vue'; // 引入 computed
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Editor from '@/Components/Wiki/Editor.vue';
import InputError from '@/Components/Other/InputError.vue';
import WikiPreviewPane from '@/Components/Wiki/WikiPreviewPane.vue';
import { mainNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = mainNavigationLinks;

const openPreviewInNewTab = () => {
    const url = route('wiki.preview'); // 后端路由
    const csrfToken = pageProps.csrf; // 从 Inertia props 获取 CSRF token

    // 创建一个临时的 form 元素
    const tempForm = document.createElement('form');
    tempForm.method = 'POST';
    tempForm.action = url;
    tempForm.target = '_blank'; // 关键：在新标签页打开
    tempForm.style.display = 'none'; // 隐藏表单

    // 添加 CSRF token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;
    tempForm.appendChild(csrfInput);

    // 添加表单数据
    const fields = {
        title: form.title,
        content: tiptapEditorRef.value?.editor?.getHTML() || form.content, // 获取最新编辑器内容
        category_ids: form.category_ids,
        tag_ids: form.tag_ids,
    };

    for (const key in fields) {
        if (Object.prototype.hasOwnProperty.call(fields, key)) {
            const value = fields[key];
            // 处理数组，例如 category_ids 和 tag_ids
            if (Array.isArray(value)) {
                value.forEach((item, index) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `${key}[${index}]`; // PHP 需要这种格式来接收数组
                    input.value = item;
                    tempForm.appendChild(input);
                });
            } else {
                // 处理普通字段
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                tempForm.appendChild(input);
            }
        }
    }

    // 将表单添加到 body 并提交，然后移除
    document.body.appendChild(tempForm);
    tempForm.submit();
    document.body.removeChild(tempForm);
};

const props = defineProps({
    categories: { type: Array, required: true },
    tags: { type: Array, required: true },
    errors: Object
});

const form = useForm({
    title: '',
    content: '<p></p>',
    category_ids: [],
    tag_ids: [],
});

const tiptapEditorRef = ref(null);
const editorIsEditable = ref(true);
const showPreviewPane = ref(true); // 现在是响应式变量

// --- 新增：切换预览窗格的函数 ---
const togglePreviewPane = () => {
    showPreviewPane.value = !showPreviewPane.value;
};

// --- 新增：计算编辑区和预览区的动态 Class ---
const editorPaneClass = computed(() => {
    return showPreviewPane.value ? 'w-full md:w-1/2 h-full' : 'w-full h-full';
});

const previewPaneClass = computed(() => {
    return showPreviewPane.value ? 'w-full md:w-1/2 h-full' : 'hidden'; // 使用hidden来完全移除
});


const createPage = () => {
    if (tiptapEditorRef.value && tiptapEditorRef.value.editor) {
        form.content = tiptapEditorRef.value.editor.getHTML();
    }
    if (form.content === '<p></p>') {
        form.setError('content', '内容不能为空。');
        return;
    }
    form.post(route('wiki.store'), {
        onError: (pageErrors) => {
            console.error("创建页面失败:", pageErrors);
            if (!pageErrors.title && !pageErrors.content && !pageErrors.category_ids && !pageErrors.tag_ids) {
                form.setError('general', '创建页面时发生未知错误。');
            }
        }
    });
};

// isMobile 逻辑保持不变，用于移动端按钮显示，但布局现在主要靠 showPreviewPane 控制
const isMobile = ref(false);
const updateMobileStatus = () => {
    isMobile.value = window.innerWidth < 768;
    // 不再根据 isMobile 自动隐藏预览
    // if (!isMobile.value) {
    //     showPreviewPane.value = true;
    // }
};
onMounted(() => {
    updateMobileStatus();
    window.addEventListener('resize', updateMobileStatus);
});
onUnmounted(() => {
    window.removeEventListener('resize', updateMobileStatus);
});
</script>

<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head title="创建 Wiki 页面" />
        <!-- 移除外部容器的固定高度和 overflow-hidden，让页面自然滚动 -->
        <div class="container mx-auto py-6 px-4 flex flex-col">
            <div
                class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6 flex flex-col flex-grow">
                <!-- Header Section -->
                <div class="flex justify-between items-start mb-4 pb-4 border-b dark:border-gray-700 flex-shrink-0">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">创建新 Wiki 页面</h1>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <!-- 新增：切换预览按钮 (桌面端也显示) -->
                        <button @click="togglePreviewPane" class="btn-secondary text-xs px-2 py-1">
                            <font-awesome-icon :icon="['fas', showPreviewPane ? 'eye-slash' : 'eye']" class="mr-1" />
                            {{ showPreviewPane ? '隐藏' : '显示' }}预览
                        </button>
                        <button @click="openPreviewInNewTab" type="button" class="btn-secondary text-sm"
                            title="在新标签页中预览页面">
                            <font-awesome-icon :icon="['fas', 'external-link-alt']" class="mr-1" /> 在新标签页预览
                        </button>
                        <Link :href="route('wiki.index')" class="btn-secondary text-sm">
                        取消
                        </Link>
                        <button @click="createPage" class="btn-primary text-sm" :disabled="form.processing">
                            <font-awesome-icon v-if="form.processing" :icon="['fas', 'spinner']" spin class="mr-1" />
                            {{ form.processing ? '正在创建...' : '创建页面' }}
                        </button>
                    </div>
                </div>

                <!-- Main Content Area (Editor + Preview) -->
                <!-- 移除这里的固定高度和 overflow-hidden -->
                <div class="flex-grow flex flex-col md:flex-row gap-6">
                    <!-- Editing Pane: 使用动态 Class -->
                    <div :class="editorPaneClass" class="flex flex-col overflow-y-auto pr-2 editor-pane">
                        <!-- Form content moved inside the scrollable div -->
                        <div class="space-y-5 flex-grow flex flex-col">
                            <!-- Title -->
                            <div>
                                <label for="title"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">标题 <span
                                        class="text-red-500">*</span></label>
                                <input id="title" v-model="form.title" type="text" class="input-field" required />
                                <InputError class="mt-1" :message="form.errors.title" />
                            </div>

                            <!-- Editor -->
                            <div class="flex-grow flex flex-col">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">内容 <span
                                        class="text-red-500">*</span></label>
                                <Editor v-model="form.content" :editable="true" :autosave="false" :pageId="null"
                                    ref="tiptapEditorRef" placeholder="开始编辑页面内容..." class="flex-grow" />
                                <InputError class="mt-1" :message="form.errors.content" />
                            </div>

                            <!-- Categories -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">分类 <span
                                        class="text-red-500">*</span></label>
                                <div class="checkbox-group">
                                    <div v-for="category in categories" :key="category.id" class="flex items-center">
                                        <input type="checkbox" :id="`category-${category.id}`" :value="category.id"
                                            v-model="form.category_ids" class="checkbox" />
                                        <label :for="`category-${category.id}`"
                                            class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ category.name
                                            }}</label>
                                    </div>
                                </div>
                                <InputError class="mt-1" :message="form.errors.category_ids" />
                            </div>

                            <!-- Tags -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">标签
                                    (可选)</label>
                                <div class="checkbox-group">
                                    <div v-for="tag in tags" :key="tag.id" class="flex items-center">
                                        <input type="checkbox" :id="`tag-${tag.id}`" :value="tag.id"
                                            v-model="form.tag_ids" class="checkbox" />
                                        <label :for="`tag-${tag.id}`"
                                            class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ tag.name }}</label>
                                    </div>
                                </div>
                                <InputError class="mt-1" :message="form.errors.tag_ids" />
                            </div>

                            <!-- Error Message -->
                            <div v-if="form.errors.general"
                                class="mt-1 text-sm text-red-600 dark:text-red-400 text-right font-medium">
                                <font-awesome-icon :icon="['fas', 'exclamation-circle']" class="mr-1" /> {{
                                form.errors.general }}
                            </div>
                        </div>
                    </div>

                    <!-- Preview Pane: 使用动态 Class -->
                    <div :class="previewPaneClass" class="flex flex-col">
                        <!-- 添加 h-full 来尝试让它填充 flex item 的高度 -->
                        <WikiPreviewPane class="h-full" :form="form" :categories="categories" :tags="tags" :page="null"
                            :currentVersion="null" />
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<style scoped>
/* 移除 editor-pane 的固定高度或最大高度（如果之前有） */
.editor-pane {
    scrollbar-width: thin;
    scrollbar-color: #a0aec0 #e2e8f0;
    /* 确保内部元素可以使其增长 */
    flex-grow: 1;
    min-height: 400px;
    /* 或根据需要调整 */
}

.dark .editor-pane {
    scrollbar-color: #4a5568 #2d3748;
}

.editor-pane::-webkit-scrollbar {
    width: 6px;
}

.editor-pane::-webkit-scrollbar-track {
    background: #e2e8f0;
    border-radius: 3px;
}

.dark .editor-pane::-webkit-scrollbar-track {
    background: #2d3748;
}

.editor-pane::-webkit-scrollbar-thumb {
    background-color: #a0aec0;
    border-radius: 3px;
}

.dark .editor-pane::-webkit-scrollbar-thumb {
    background-color: #4a5568;
}

/* Ensure Tiptap editor takes up available space */
:deep(.tiptap-editor) {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    /* 可能需要移除或调整 min-height */
    min-height: 250px;
}

:deep(.editor-content) {
    flex-grow: 1;
    max-height: none;
    /* Override any max-height set in the component */
    height: auto;
    /* Allow it to grow naturally */
}

.input-field,
.textarea-field,
select {
    @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400 text-sm;
}

.checkbox-group {
    @apply grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 max-h-48 overflow-y-auto p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-800/50;
}

.checkbox {
    @apply h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800;
}

.btn-primary {
    @apply px-4 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
    @apply px-4 py-1.5 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 text-sm font-medium;
}

/* 保持响应式按钮的隐藏逻辑（如果还需要的话） */
.md\\:hidden {
    display: none;
}

@media (max-width: 767px) {
    .md\\:hidden {
        display: block;
        /* 或者 flex, inline-flex 等 */
    }
}
</style>
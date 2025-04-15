<script setup>
// 从 'vue' 和 '@inertiajs/vue3' 导入必要的函数和组件
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'; // 保持原有的 usePage 导入

// 导入布局和组件
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Editor from '@/Components/Wiki/Editor.vue';
import InputError from '@/Components/Other/InputError.vue';
import WikiPreviewPane from '@/Components/Wiki/WikiPreviewPane.vue';
import { mainNavigationLinks } from '@/config/navigationConfig'; // 导入导航链接配置

// --- 修正点：在这里调用 usePage() 获取 pageProps ---
const pageProps = usePage().props;
// ----------------------------------------------------

// 定义导航链接
const navigationLinks = mainNavigationLinks;

// 定义 Props
const props = defineProps({
    categories: { type: Array, required: true }, // Wiki 分类列表
    tags: { type: Array, required: true }, // Wiki 标签列表
    errors: Object // 后端传递的验证错误
});

// 初始化表单数据
const form = useForm({
    title: '', // 页面标题
    content: '<p></p>', // 页面内容，默认为一个空的段落
    category_ids: [], // 选中的分类 ID 数组
    tag_ids: [], // 选中的标签 ID 数组
});

// 引用 Tiptap 编辑器实例
const tiptapEditorRef = ref(null);
// 控制编辑器是否可编辑的状态 (在创建页面时始终为 true)
const editorIsEditable = ref(true);
// 控制预览窗格是否显示的状态
const showPreviewPane = ref(true);

// --- 方法 ---

// 切换预览窗格的显示状态
const togglePreviewPane = () => {
    showPreviewPane.value = !showPreviewPane.value;
};

// 在新标签页中打开预览
const openPreviewInNewTab = () => {
    const currentContent = tiptapEditorRef.value?.editor?.getHTML() || form.content;

    // 1. 检查标题是否为空
    if (!form.title.trim()) {
        alert('请先输入页面标题再进行预览！'); // 使用 alert 简单提示，也可以替换为更复杂的通知组件
        return; // 阻止后续操作
    }

    // 2. 检查内容是否为空 (考虑 Tiptap 的默认 <p></p>)
    if (!currentContent || currentContent === '<p></p>' || currentContent.trim() === '') {
        alert('请先输入页面内容再进行预览！');
        return; // 阻止后续操作
    }

    // --- 如果检查通过，继续执行原有的逻辑 ---
    const url = route('wiki.preview');
    const csrfToken = pageProps.csrf;
    const tempForm = document.createElement('form');

    tempForm.method = 'POST';
    tempForm.action = url;
    tempForm.target = '_blank';
    tempForm.style.display = 'none';

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;
    tempForm.appendChild(csrfInput);

    const fields = {
        title: form.title,
        content: currentContent, // 使用获取到的当前内容
        category_ids: form.category_ids,
        tag_ids: form.tag_ids,
    };

    for (const key in fields) {
        if (Object.prototype.hasOwnProperty.call(fields, key)) {
            const value = fields[key];
            if (Array.isArray(value)) {
                value.forEach((item, index) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `${key}[${index}]`;
                    input.value = item;
                    tempForm.appendChild(input);
                });
            } else {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                tempForm.appendChild(input);
            }
        }
    }

    document.body.appendChild(tempForm);
    tempForm.submit();
    document.body.removeChild(tempForm);
};


// 计算编辑区和预览区的动态 Class
const editorPaneClass = computed(() => {
    return showPreviewPane.value ? 'w-full md:w-1/2 h-full' : 'w-full h-full';
});
const previewPaneClass = computed(() => {
    return showPreviewPane.value ? 'w-full md:w-1/2 h-full' : 'hidden';
});

// 创建 Wiki 页面
const createPage = () => {
    // 获取编辑器的最新 HTML 内容
    if (tiptapEditorRef.value && tiptapEditorRef.value.editor) {
        form.content = tiptapEditorRef.value.editor.getHTML();
    }
    // 检查内容是否为空（Tiptap 默认空内容是 <p></p>）
    if (form.content === '<p></p>') {
        form.setError('content', '内容不能为空。');
        return; // 阻止提交
    }
    // 发起 POST 请求到后端存储页面
    form.post(route('wiki.store'), {
        // 请求失败时的回调
        onError: (pageErrors) => {
            console.error("创建页面失败:", pageErrors);
            // 如果没有具体的字段错误，显示一个通用错误消息
            if (!pageErrors.title && !pageErrors.content && !pageErrors.category_ids && !pageErrors.tag_ids) {
                form.setError('general', '创建页面时发生未知错误。');
            }
        }
    });
};

// 判断是否为移动设备（用于响应式布局）
const isMobile = ref(false);
const updateMobileStatus = () => {
    isMobile.value = window.innerWidth < 768; // 阈值可以根据需要调整
};

// --- 生命周期钩子 ---
onMounted(() => {
    updateMobileStatus(); // 组件挂载时检查设备类型
    window.addEventListener('resize', updateMobileStatus); // 监听窗口大小变化
});

onUnmounted(() => {
    window.removeEventListener('resize', updateMobileStatus); // 组件卸载时移除监听器
});
</script>

<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head title="创建 Wiki 页面" />

        <div class="container mx-auto py-6 px-4 flex flex-col">
            <!-- 主内容区域卡片 -->
            <div
                class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6 flex flex-col flex-grow">
                <!-- 顶部操作栏 -->
                <div class="flex justify-between items-start mb-4 pb-4 border-b dark:border-gray-700 flex-shrink-0">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">创建新 Wiki 页面</h1>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <!-- 切换预览按钮 -->
                        <button @click="togglePreviewPane" class="btn-secondary text-xs px-2 py-1">
                            <font-awesome-icon :icon="['fas', showPreviewPane ? 'eye-slash' : 'eye']" class="mr-1" />
                            {{ showPreviewPane ? '隐藏' : '显示' }}预览
                        </button>
                        <!-- 新标签页预览按钮 -->
                        <button @click="openPreviewInNewTab" type="button" class="btn-secondary text-sm"
                            title="在新标签页中预览页面">
                            <font-awesome-icon :icon="['fas', 'external-link-alt']" class="mr-1" /> 在新标签页预览
                        </button>
                        <!-- 取消按钮 -->
                        <Link :href="route('wiki.index')" class="btn-secondary text-sm">
                        取消
                        </Link>
                        <!-- 创建页面按钮 -->
                        <button @click="createPage" class="btn-primary text-sm" :disabled="form.processing">
                            <font-awesome-icon v-if="form.processing" :icon="['fas', 'spinner']" spin class="mr-1" />
                            {{ form.processing ? '正在创建...' : '创建页面' }}
                        </button>
                    </div>
                </div>

                <!-- 编辑区和预览区布局 -->
                <div class="flex-grow flex flex-col md:flex-row gap-6">
                    <!-- 编辑区 -->
                    <div :class="editorPaneClass" class="flex flex-col overflow-y-auto pr-2 editor-pane">
                        <div class="space-y-5 flex-grow flex flex-col">
                            <!-- 标题输入 -->
                            <div>
                                <label for="title"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">标题 <span
                                        class="text-red-500">*</span></label>
                                <input id="title" v-model="form.title" type="text" class="input-field" required />
                                <InputError class="mt-1" :message="form.errors.title" />
                            </div>
                            <!-- 内容编辑器 -->
                            <div class="flex-grow flex flex-col">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">内容 <span
                                        class="text-red-500">*</span></label>
                                <Editor v-model="form.content" :editable="true" :autosave="false" :pageId="null"
                                    ref="tiptapEditorRef" placeholder="开始编辑页面内容..." class="flex-grow" />
                                <InputError class="mt-1" :message="form.errors.content" />
                            </div>
                            <!-- 分类选择 -->
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
                            <!-- 标签选择 -->
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
                            <!-- 通用错误显示 -->
                            <div v-if="form.errors.general"
                                class="mt-1 text-sm text-red-600 dark:text-red-400 text-right font-medium">
                                <font-awesome-icon :icon="['fas', 'exclamation-circle']" class="mr-1" /> {{
                                    form.errors.general }}
                            </div>
                        </div>
                    </div>

                    <!-- 预览区 -->
                    <div :class="previewPaneClass" class="flex flex-col">
                        <!-- 使用 WikiPreviewPane 组件显示预览 -->
                        <WikiPreviewPane class="h-full" :form="form" :categories="categories" :tags="tags" :page="null"
                            :currentVersion="null" />
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<style scoped>
/* 编辑区滚动条样式 */
.editor-pane {
    scrollbar-width: thin;
    /* Firefox */
    scrollbar-color: #a0aec0 #e2e8f0;
    /* Firefox: thumb track */
    flex-grow: 1;
    min-height: 400px;
    /* 保证编辑器有最小高度 */
}

/* Webkit 浏览器滚动条样式 (Chrome, Safari) */
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

/* 确保 Tiptap 编辑器能够填充可用空间 */
:deep(.tiptap-editor) {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    min-height: 250px;
    /* Tiptap 编辑器本身也需要最小高度 */
}

:deep(.editor-content) {
    flex-grow: 1;
    max-height: none;
    /* 移除可能的最大高度限制 */
    height: auto;
    /* 高度自适应 */
}

/* 输入框、文本域和下拉选择框的基础样式 */
.input-field,
.textarea-field,
select {
    @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400 text-sm;
}

/* 复选框组样式 */
.checkbox-group {
    @apply grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 max-h-48 overflow-y-auto p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-800/50;
}

/* 复选框样式 */
.checkbox {
    @apply h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800;
}

/* 按钮样式 */
.btn-primary {
    @apply px-4 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
    @apply px-4 py-1.5 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 text-sm font-medium;
}

/* 响应式隐藏 (针对预览窗格) */
.md\:hidden {
    display: none;
}

@media (max-width: 767px) {

    /* Tailwind 的 md断点是 768px */
    .md\:hidden {
        display: block;
    }

    .flex-col.md\:flex-row {
        flex-direction: column;
        /* 强制在小屏幕下垂直排列 */
    }

    .md\:w-1\/2 {
        width: 100%;
        /* 在小屏幕下占满宽度 */
    }

    .editor-pane {
        padding-right: 0;
        /* 移除编辑区的右边距 */
    }
}
</style>
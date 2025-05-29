<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Editor from '@/Components/Wiki/Editor.vue';
import InputError from '@/Components/Other/InputError.vue';
import WikiPreviewPane from '@/Components/Wiki/WikiPreviewPane.vue';
import { mainNavigationLinks } from '@/config/navigationConfig';

// 获取当前页面的 Inertia 属性
const pageProps = usePage().props;

// 定义主导航链接
const navigationLinks = mainNavigationLinks;

// 定义组件接收的 props
const props = defineProps({
    categories: { type: Array, required: true }, // Wiki 分类列表
    tags: { type: Array, required: true },       // Wiki 标签列表
    errors: Object                               // 后端传递的验证错误对象
});

// 初始化表单数据，用于创建 Wiki 页面
const form = useForm({
    title: '',         // 页面标题
    content: '<p></p>', // 页面内容，默认为一个空的 HTML 段落
    category_ids: [],  // 选中的分类 ID 数组
    tag_ids: [],       // 选中的标签 ID 数组
});

// 引用 Tiptap 编辑器实例，以便直接操作编辑器
const tiptapEditorRef = ref(null);
// 控制编辑器是否可编辑的状态，创建页面时默认为 true
const editorIsEditable = ref(true);
// 控制预览窗格的显示/隐藏状态
const showPreviewPane = ref(true);

// 切换预览窗格的显示状态
const togglePreviewPane = () => {
    showPreviewPane.value = !showPreviewPane.value;
};

// 在新标签页中打开页面预览
const openPreviewInNewTab = () => {
    // 获取编辑器当前的 HTML 内容，如果编辑器未加载则使用表单内容
    const currentContent = tiptapEditorRef.value?.editor?.getHTML() || form.content;

    // 标题验证
    if (!form.title.trim()) {
        alert('请先输入页面标题再进行预览！');
        return;
    }

    // 内容验证，检查是否为空或只包含默认的空段落标签
    if (!currentContent || currentContent === '<p></p>' || currentContent.trim() === '') {
        alert('请先输入页面内容再进行预览！');
        return;
    }

    // 构建一个临时表单并提交到预览路由
    const url = route('wiki.preview');
    const csrfToken = pageProps.csrf; // 获取 CSRF Token

    const tempForm = document.createElement('form');
    tempForm.method = 'POST';
    tempForm.action = url;
    tempForm.target = '_blank'; // 在新标签页打开
    tempForm.style.display = 'none'; // 隐藏表单

    // 添加 CSRF Token 到表单
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;
    tempForm.appendChild(csrfInput);

    // 准备要提交的字段数据
    const fields = {
        title: form.title,
        content: currentContent,
        category_ids: form.category_ids,
        tag_ids: form.tag_ids,
    };

    // 将字段数据添加到临时表单
    for (const key in fields) {
        if (Object.prototype.hasOwnProperty.call(fields, key)) {
            const value = fields[key];
            if (Array.isArray(value)) {
                // 处理数组类型的字段（如 category_ids, tag_ids）
                value.forEach((item, index) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `${key}[${index}]`; // 生成数组字段名，如 category_ids[0]
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

    // 将临时表单添加到 DOM 并提交，然后移除
    document.body.appendChild(tempForm);
    tempForm.submit();
    document.body.removeChild(tempForm);
};

// 根据 `showPreviewPane` 和 `isMobile` 计算编辑区和预览区的 CSS 类
const editorPaneClass = computed(() => {
    return showPreviewPane.value ? 'w-full md:w-1/2 h-full' : 'w-full h-full';
});
const previewPaneClass = computed(() => {
    return showPreviewPane.value ? 'w-full md:w-1/2 h-full' : 'hidden';
});

// 提交表单创建 Wiki 页面
const createPage = () => {
    // 确保获取编辑器最新内容
    if (tiptapEditorRef.value && tiptapEditorRef.value.editor) {
        form.content = tiptapEditorRef.value.editor.getHTML();
    }

    // 再次检查内容是否为空，Tiptap 的空内容是 '<p></p>'
    if (form.content === '<p></p>' || !form.content.trim()) {
        form.setError('content', '内容不能为空。');
        return;
    }

    // 发送 POST 请求到后端存储页面
    form.post(route('wiki.store'), {
        // 请求失败时的回调函数
        onError: (pageErrors) => {
            console.error("创建页面失败:", pageErrors);
            // 如果后端返回的错误不包含特定字段的错误（如标题、内容、分类、标签），则显示一个通用错误消息
            if (!pageErrors.title && !pageErrors.content && !pageErrors.category_ids && !pageErrors.tag_ids) {
                form.setError('general', '创建页面时发生未知错误。');
            }
        }
    });
};

// 用于判断当前设备是否为移动端
const isMobile = ref(false);
const updateMobileStatus = () => {
    // 根据窗口宽度判断是否为移动端（这里以 768px 为阈值）
    isMobile.value = window.innerWidth < 768;
};

// 组件挂载时执行
onMounted(() => {
    updateMobileStatus(); // 初始化时更新移动端状态
    window.addEventListener('resize', updateMobileStatus); // 监听窗口大小变化
});

// 组件卸载时执行
onUnmounted(() => {
    window.removeEventListener('resize', updateMobileStatus); // 移除窗口大小变化监听器
});
</script>

<template>
    <!-- 主布局容器，传入导航链接 -->
    <MainLayout :navigationLinks="navigationLinks">

        <!-- 设置页面标题 -->

        <Head title="创建 Wiki 页面" />

        <div class="container mx-auto py-6 px-4 flex flex-col">
            <!-- 主内容区域的背景卡片 -->
            <div
                class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6 flex flex-col flex-grow">
                <!-- 顶部操作栏，包含页面标题和各种操作按钮 -->
                <div class="flex justify-between items-start mb-4 pb-4 border-b dark:border-gray-700 flex-shrink-0">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">创建新 Wiki 页面</h1>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <!-- 切换预览窗格显示/隐藏的按钮 -->
                        <button @click="togglePreviewPane" class="btn-secondary text-xs px-2 py-1">
                            <font-awesome-icon :icon="['fas', showPreviewPane ? 'eye-slash' : 'eye']" class="mr-1" />
                            {{ showPreviewPane ? '隐藏' : '显示' }}预览
                        </button>
                        <!-- 在新标签页打开预览的按钮 -->
                        <button @click="openPreviewInNewTab" type="button" class="btn-secondary text-sm"
                            title="在新标签页中预览页面">
                            <font-awesome-icon :icon="['fas', 'external-link-alt']" class="mr-1" /> 在新标签页预览
                        </button>
                        <!-- 取消创建并返回 Wiki 列表的按钮 -->
                        <Link :href="route('wiki.index')" class="btn-secondary text-sm">
                        取消
                        </Link>
                        <!-- 提交表单创建页面的按钮 -->
                        <button @click="createPage" class="btn-primary text-sm" :disabled="form.processing">
                            <font-awesome-icon v-if="form.processing" :icon="['fas', 'spinner']" spin class="mr-1" />
                            {{ form.processing ? '正在创建...' : '创建页面' }}
                        </button>
                    </div>
                </div>

                <!-- 编辑区和预览区的布局容器，支持响应式布局 -->
                <div class="flex-grow flex flex-col md:flex-row gap-6">
                    <!-- 编辑区，包含标题、内容编辑器、分类和标签选择 -->
                    <div :class="editorPaneClass" class="flex flex-col overflow-y-auto pr-2 editor-pane">
                        <div class="space-y-5 flex-grow flex flex-col">
                            <!-- 页面标题输入框 -->
                            <div>
                                <label for="title"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">标题 <span
                                        class="text-red-500">*</span></label>
                                <input id="title" v-model="form.title" type="text" class="input-field" required />
                                <InputError class="mt-1" :message="form.errors.title" />
                            </div>
                            <!-- Wiki 内容编辑器组件 -->
                            <div class="flex-grow flex flex-col">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">内容 <span
                                        class="text-red-500">*</span></label>
                                <Editor v-model="form.content" :editable="true" :autosave="false" :pageId="null"
                                    ref="tiptapEditorRef" placeholder="开始编辑页面内容..." class="flex-grow" />
                                <InputError class="mt-1" :message="form.errors.content" />
                            </div>
                            <!-- Wiki 分类选择区域 -->
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
                            <!-- Wiki 标签选择区域（可选） -->
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
                            <!-- 通用错误信息显示区域 -->
                            <div v-if="form.errors.general"
                                class="mt-1 text-sm text-red-600 dark:text-red-400 text-right font-medium">
                                <font-awesome-icon :icon="['fas', 'exclamation-circle']" class="mr-1" /> {{
                                    form.errors.general }}
                            </div>
                        </div>
                    </div>

                    <!-- 预览区，使用 WikiPreviewPane 组件显示内容预览 -->
                    <div :class="previewPaneClass" class="flex flex-col">
                        <WikiPreviewPane class="h-full" :form="form" :categories="categories" :tags="tags" :page="null"
                            :currentVersion="null" />
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<style scoped>
/* 编辑区滚动条样式，用于 Firefox 浏览器 */
.editor-pane {
    scrollbar-width: thin;
    scrollbar-color: #a0aec0 #e2e8f0;
    flex-grow: 1;
    min-height: 400px;
}

/* 编辑区滚动条样式，用于深色模式下的 Firefox 浏览器 */
.dark .editor-pane {
    scrollbar-color: #4a5568 #2d3748;
}

/* Webkit 浏览器 (如 Chrome, Safari) 的滚动条整体样式 */
.editor-pane::-webkit-scrollbar {
    width: 6px;
}

/* Webkit 浏览器滚动条轨道样式 */
.editor-pane::-webkit-scrollbar-track {
    background: #e2e8f0;
    border-radius: 3px;
}

/* 深色模式下 Webkit 浏览器滚动条轨道样式 */
.dark .editor-pane::-webkit-scrollbar-track {
    background: #2d3748;
}

/* Webkit 浏览器滚动条滑块样式 */
.editor-pane::-webkit-scrollbar-thumb {
    background-color: #a0aec0;
    border-radius: 3px;
}

/* 深色模式下 Webkit 浏览器滚动条滑块样式 */
.dark .editor-pane::-webkit-scrollbar-thumb {
    background-color: #4a5568;
}

/* 强制 Tiptap 编辑器在父容器中垂直填充 */
:deep(.tiptap-editor) {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    min-height: 250px;
}

/* Tiptap 编辑器内容区域样式 */
:deep(.editor-content) {
    flex-grow: 1;
    max-height: none;
    height: auto;
}

/* 输入框、文本域和下拉选择框的通用基础样式 */
.input-field,
.textarea-field,
select {
    @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400 text-sm;
}

/* 复选框组的布局和样式 */
.checkbox-group {
    @apply grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 max-h-48 overflow-y-auto p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-800/50;
}

/* 单个复选框的样式 */
.checkbox {
    @apply h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800;
}

/* 主要操作按钮的样式 */
.btn-primary {
    @apply px-4 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed;
}

/* 次要操作按钮的样式 */
.btn-secondary {
    @apply px-4 py-1.5 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 text-sm font-medium;
}

/* 媒体查询：针对中等屏幕尺寸以下（md 断点 768px）的响应式调整 */
@media (max-width: 767px) {
    .md\:hidden {
        display: block;
        /* 在小屏幕上显示 */
    }

    .flex-col.md\:flex-row {
        flex-direction: column;
        /* 强制垂直堆叠布局 */
    }

    .md\:w-1\/2 {
        width: 100%;
        /* 占满整个宽度 */
    }

    .editor-pane {
        padding-right: 0;
        /* 移除右侧内边距 */
    }
}
</style>
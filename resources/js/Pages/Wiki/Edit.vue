<template>
    <!-- 主要布局容器，引入导航链接 -->
    <MainLayout :navigationLinks="navigationLinks">

        <!-- 设置页面标题，显示当前编辑的页面标题 -->

        <Head :title="`编辑: ${page.title}`" />
        <!-- 主内容区域容器 -->
        <div class="container mx-auto py-6 px-4 flex flex-col h-[calc(100vh-theme(space.24))]">
            <!-- 页面内容主体卡片 -->
            <div
                class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-4 md:p-6 flex flex-col flex-grow overflow-hidden">
                <!-- 页面头部：标题和操作按钮 -->
                <div class="flex flex-col md:flex-row justify-between md:items-start mb-3 gap-y-2 flex-shrink-0 z-10">
                    <!-- 左侧：页面标题和版本信息 -->
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">编辑: {{ form.title || page.title
                            }}</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1"
                            :title="`服务器最新版本ID: ${props.initialVersionId ?? 'N/A'}\n最后成功保存时基于的版本ID: ${lastSuccessfulSaveVersionId ?? 'N/A'}`">
                            当前编辑基于版本: v{{ currentBaseVersionNumber || '?' }}
                        </p>
                    </div>
                    <!-- 右侧：操作按钮组 -->
                    <div class="flex items-center gap-2 flex-wrap flex-shrink-0">
                        <!-- 移动端显示/隐藏预览按钮 -->
                        <button @click="togglePreviewPane" class="btn-secondary text-xs px-2 py-1 md:hidden">
                            <font-awesome-icon :icon="['fas', showPreviewPane ? 'eye-slash' : 'eye']"
                                class="mr-1 h-3 w-3" /> {{ showPreviewPane ? '隐藏' : '显示' }}预览
                        </button>
                        <!-- 刷新页面数据按钮 -->
                        <button @click="refreshPage" class="btn-secondary text-sm" title="刷新页面数据（将丢失未保存的更改！）">
                            <font-awesome-icon :icon="['fas', 'sync-alt']" :spin="false" class="h-3 w-3" /> 刷新
                        </button>
                        <!-- 在新标签页中预览按钮 -->
                        <button @click="openPreviewInNewTab" type="button" class="btn-secondary text-sm"
                            title="在新标签页中预览">
                            <font-awesome-icon :icon="['fas', 'external-link-alt']" class="mr-1 h-3 w-3" /> 外部预览
                        </button>
                        <!-- 取消编辑并返回页面详情页 -->
                        <Link :href="route('wiki.show', page.slug)" class="btn-secondary text-sm"> 取消 </Link>
                        <!-- 保存页面按钮 -->
                        <button @click="savePage" class="btn-primary text-sm"
                            :disabled="!computedEditorIsEditable || form.processing || isSaving || showStaleVersionModal || isProcessingChoice"
                            :title="computedEditorIsEditable ? '保存更改' : '当前状态不可保存'">
                            <font-awesome-icon v-if="form.processing || isSaving" :icon="['fas', 'spinner']" spin
                                class="mr-1 h-3 w-3" /> {{ isSaving ? '正在保存...' : '保存页面' }}
                        </button>
                    </div>
                </div>

                <!-- 状态栏和编辑者列表 -->
                <div class="mb-3 flex-shrink-0 space-y-2">
                    <!-- 页面冲突警告信息，用户无权限解决时显示 -->
                    <div v-if="isConflict && !canResolveConflict && !showStaleVersionModal && !userAcknowledgedConflict"
                        class="alert alert-error">
                        页面冲突，您无权解决，无法编辑。
                    </div>
                    <!-- 页面冲突警告信息，用户有权限解决时显示 -->
                    <div v-else-if="isConflict && canResolveConflict && !showStaleVersionModal && !userAcknowledgedConflict"
                        class="alert alert-warning">
                        页面冲突，请提交编辑以解决。
                    </div>
                    <!-- 用户选择继续编辑旧版本后的警告及操作按钮 -->
                    <div v-if="userAcknowledgedConflict && !showStaleVersionModal"
                        class="alert alert-warning text-center">
                        你选择了继续编辑旧版本。
                        <button @click="refreshPage" class="btn-secondary text-xs px-2 py-0.5 ml-2">刷新以获取最新</button>
                        <button @click="handleForceSave" class="btn-danger text-xs px-2 py-0.5 ml-2">强制保存(当前版本)</button>
                        <button @click="handleDiscardAndExit"
                            class="btn-secondary text-xs px-2 py-0.5 ml-2">放弃编辑并退出</button>
                    </div>

                    <!-- 协作编辑者列表组件 -->
                    <EditorsList :pageId="page.id" />
                </div>

                <!-- 编辑器和预览面板布局 -->
                <div class="flex-grow flex flex-col md:flex-row gap-6 min-h-0">
                    <!-- 编辑器面板 -->
                    <div :class="[editorPaneClass, 'flex flex-col']">
                        <!-- 表单提交区域 -->
                        <form @submit.prevent="savePage"
                            class="space-y-5 flex-grow flex flex-col overflow-y-auto pr-2 editor-pane-scrollbar">
                            <!-- 标题输入框 -->
                            <div>
                                <label for="title"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">标题 <span
                                        class="text-red-500">*</span></label>
                                <input id="title" v-model="form.title" type="text" class="input-field"
                                    :disabled="!computedEditorIsEditable" required />
                                <InputError class="mt-1" :message="form.errors.title" />
                            </div>
                            <!-- Tiptap 编辑器组件 -->
                            <div class="flex-grow flex flex-col min-h-[300px]">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">内容 <span
                                        class="text-red-500">*</span></label>
                                <Editor v-model="form.content" :editable="computedEditorIsEditable"
                                    :autosave="computedEditorIsEditable" :pageId="page.id" @saved="onDraftSaved"
                                    @statusUpdate="handleEditorStatusUpdate" placeholder="开始编辑页面内容..."
                                    ref="tiptapEditorRef"
                                    class="flex-grow !min-h-[250px] border-gray-300 dark:border-gray-600 rounded-b-lg" />
                                <InputError class="mt-1" :message="form.errors.content || form.errors.version_id" />
                            </div>
                            <!-- 分类和标签选择区 -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- 分类选择 -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">分类
                                        <span class="text-red-500">*</span></label>
                                    <div class="checkbox-group"
                                        :class="{ 'disabled-group': !computedEditorIsEditable }">
                                        <div v-for="category in categories" :key="category.id"
                                            class="flex items-center">
                                            <input type="checkbox" :id="`category-${category.id}`" :value="category.id"
                                                v-model="form.category_ids" :disabled="!computedEditorIsEditable"
                                                class="checkbox" />
                                            <label :for="`category-${category.id}`"
                                                class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ category.name
                                                }}</label>
                                        </div>
                                    </div>
                                    <InputError class="mt-1" :message="form.errors.category_ids" />
                                </div>
                                <!-- 标签选择 -->
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">标签</label>
                                    <div class="checkbox-group"
                                        :class="{ 'disabled-group': !computedEditorIsEditable }">
                                        <div v-for="tag in tags" :key="tag.id" class="flex items-center">
                                            <input type="checkbox" :id="`tag-${tag.id}`" :value="tag.id"
                                                v-model="form.tag_ids" :disabled="!computedEditorIsEditable"
                                                class="checkbox" />
                                            <label :for="`tag-${tag.id}`"
                                                class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ tag.name
                                                }}</label>
                                        </div>
                                    </div>
                                    <InputError class="mt-1" :message="form.errors.tag_ids" />
                                </div>
                            </div>
                            <!-- 提交说明输入框 -->
                            <div>
                                <label for="comment"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">提交说明 <span
                                        class="text-xs text-gray-500 dark:text-gray-400">(可选)</span></label>
                                <textarea id="comment" v-model="form.comment" rows="2"
                                    :disabled="!computedEditorIsEditable" class="textarea-field"
                                    placeholder="例如：修正了XX数据..."></textarea>
                                <InputError class="mt-1" :message="form.errors.comment" />
                            </div>
                            <!-- 编辑器状态栏 -->
                            <div
                                class="editor-status-bar flex justify-end items-center mt-auto text-right pr-1 flex-shrink-0 sticky bottom-0 bg-white/80 dark:bg-gray-900/80 pt-2">
                                <span v-if="editorStatusBar.message" :class="editorStatusBar.class"
                                    class="flex items-center justify-end text-xs">
                                    <font-awesome-icon :icon="editorStatusBar.icon" :spin="editorStatusBar.spin"
                                        class="mr-1 h-3 w-3" />
                                    {{ editorStatusBar.message }}
                                </span>
                            </div>
                        </form>
                    </div>
                    <!-- 预览面板 (桌面端显示，移动端隐藏) -->
                    <div :class="[previewPaneClass, 'hidden md:flex md:flex-col min-h-0']">
                        <WikiPreviewPane
                            class="h-full border-gray-300 dark:border-gray-600 border rounded-lg overflow-hidden"
                            :form="form" :categories="categories" :tags="tags" :page="page"
                            :currentVersion="page.current_version" />
                    </div>
                </div>
            </div>
        </div>

        <!-- 版本冲突模态框 -->
        <Modal :show="showStaleVersionModal" @close="handleContinueEditing" maxWidth="4xl" :closeable="true">
            <!-- 模态框内容区 -->
            <div class="p-6 bg-gray-800 text-gray-200 rounded-lg shadow-xl">
                <!-- 模态框标题 -->
                <h2 class="text-xl font-bold mb-4 text-yellow-400 flex items-center">
                    <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="mr-2" /> 页面版本冲突
                </h2>
                <!-- 冲突说明信息 -->
                <p class="mb-4 text-sm">
                    在你编辑期间 (从 v{{ staleVersionData.base_version_number ?? currentBaseVersionNumber }} 开始)，页面已被 <strong
                        class="text-white">{{ staleVersionData.current_version_creator || '其他用户' }}</strong> 更新至
                    <strong>v{{ staleVersionData.current_version_number || '新' }}</strong> 版本。
                    <span v-if="staleVersionData.current_version_updated_at"> (更新于 {{ formatDateTime(new
                        Date(staleVersionData.current_version_updated_at)) }})</span>
                </p>
                <p class="mb-6 text-sm">请仔细检查以下内容差异，并选择如何处理您的编辑：</p>

                <!-- 差异对比视图区域 -->
                <div
                    class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 max-h-[50vh] overflow-y-auto p-3 bg-gray-900/70 rounded-md border border-gray-700">
                    <!-- 您的编辑与最新版本的差异 -->
                    <div
                        class="md:border-r border-gray-700 pr-0 md:pr-4 pb-4 md:pb-0 mb-4 md:mb-0 border-b md:border-b-0">
                        <h3 class="text-xs font-semibold mb-2 text-gray-400 uppercase tracking-wider">
                            <span class="text-blue-400">您的编辑</span>
                            <span class="text-white mx-1"> vs </span>
                            <span class="text-green-400">最新版本 v{{ staleVersionData.current_version_number }}</span>
                        </h3>
                        <div class="diff-modal"
                            v-html="staleVersionData.diff_user_vs_current || '<p class=\'p-2 text-gray-500 italic\'>无法加载您的编辑与最新版本的差异。</p>'">
                        </div>
                    </div>
                    <!-- 您基于的版本与最新版本的差异 -->
                    <div>
                        <h3 class="text-xs font-semibold mb-2 text-gray-400 uppercase tracking-wider">
                            <span class="text-red-400">您基于的版本 v{{ staleVersionData.base_version_number }}</span>
                            <span class="text-white mx-1"> vs </span>
                            <span class="text-green-400">最新版本 v{{ staleVersionData.current_version_number }}</span>
                        </h3>
                        <div class="diff-modal"
                            v-html="staleVersionData.diff_base_vs_current || '<p class=\'p-2 text-gray-500 italic\'>无法加载基础版本与最新版本的差异。</p>'">
                        </div>
                    </div>
                </div>

                <!-- 模态框底部操作按钮 -->
                <div
                    class="flex flex-col sm:flex-row justify-end items-center gap-3 mt-6 pt-4 border-t border-gray-700">
                    <!-- 继续编辑（忽略冲突）按钮 -->
                    <button @click="handleContinueEditing" class="btn-secondary w-full sm:w-auto text-sm"
                        :disabled="isProcessingChoice" title="关闭此弹窗，继续在当前（旧版本）内容上编辑。下次保存时将自动触发页面冲突状态。">
                        继续编辑 (忽略)
                    </button>
                    <!-- 强制保存（我的版本）按钮 -->
                    <button @click="handleForceSave" class="btn-warning w-full sm:w-auto text-sm"
                        :disabled="isProcessingChoice" title="强行保存当前编辑的内容，这将会覆盖其他用户的更改并将页面标记为冲突，需手动解决。">
                        <font-awesome-icon v-if="isProcessingChoice && form.force_conflict" :icon="['fas', 'spinner']"
                            spin class="mr-1" />
                        强制保存 (我的版本)
                    </button>
                    <!-- 放弃编辑，加载最新版本按钮 -->
                    <button @click="handleDiscardAndLoadLatest" class="btn-secondary w-full sm:w-auto text-sm"
                        :disabled="isProcessingChoice" title="丢弃当前编辑和草稿，加载页面最新版本的内容，然后您可以继续编辑。">
                        <font-awesome-icon
                            v-if="isProcessingChoice && !form.force_conflict && !userAcknowledgedConflict"
                            :icon="['fas', 'spinner']" spin class="mr-1" />
                        放弃编辑，加载最新
                    </button>
                    <!-- 放弃编辑并退出按钮 -->
                    <button @click="handleDiscardAndExit" class="btn-danger w-full sm:w-auto text-sm"
                        :disabled="isProcessingChoice" title="丢弃当前编辑和草稿，并退出编辑页面。">
                        <font-awesome-icon
                            v-if="isProcessingChoice && !form.force_conflict && !userAcknowledgedConflict"
                            :icon="['fas', 'spinner']" spin class="mr-1" />
                        放弃编辑并退出
                    </button>
                </div>
            </div>
        </Modal>
        <!-- 闪烁消息组件 -->
        <FlashMessage ref="flashMessageRef" />
    </MainLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch, nextTick, onBeforeUnmount } from 'vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Editor from '@/Components/Wiki/Editor.vue';
import InputError from '@/Components/Other/InputError.vue';
import EditorsList from '@/Components/Wiki/EditorsList.vue';
import WikiPreviewPane from '@/Components/Wiki/WikiPreviewPane.vue';
import Modal from '@/Components/Modal/Modal.vue';
import FlashMessage from '@/Components/Other/FlashMessage.vue';
import { formatDateTime } from '@/utils/formatters';
import { mainNavigationLinks } from '@/config/navigationConfig';
import axios from 'axios';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

// 导入主导航链接配置
const navigationLinks = mainNavigationLinks;
// 获取当前页面的 Inertia 属性
const pageProps = usePage().props;

// 定义组件接收的属性
const props = defineProps({
    page: { type: Object, required: true }, // 当前Wiki页面对象
    content: { type: String, required: true }, // 页面内容（草稿或最新版本）
    categories: { type: Array, required: true }, // 可用的分类列表
    tags: { type: Array, required: true }, // 可用的标签列表
    hasDraft: { type: Boolean, default: false }, // 是否存在当前用户的草稿
    lastSaved: { type: String, default: null }, // 上次草稿保存时间
    canResolveConflict: { type: Boolean, default: false }, // 当前用户是否可以解决页面冲突
    isConflict: { type: Boolean, default: false },     // 页面是否处于冲突状态
    editorIsEditable: { type: Boolean, default: true }, // 编辑器是否可编辑
    errors: Object, // Inertia传递的错误信息
    initialVersionId: { type: Number, default: null }, // 页面加载时的当前版本ID
    initialVersionNumber: { type: [Number, String], default: 0 }, // 页面加载时的当前版本号
});

// 对Tiptap编辑器实例的引用
const tiptapEditorRef = ref(null);
// 对FlashMessage组件的引用
const flashMessageRef = ref(null);
// 控制预览面板的显示/隐藏
const showPreviewPane = ref(true);

// 与版本追踪和冲突处理相关的状态
const currentBaseVersionId = ref(props.initialVersionId); // 当前编辑所基于的版本ID
const currentBaseVersionNumber = ref(props.initialVersionNumber); // 当前编辑所基于的版本号
const lastSuccessfulSaveVersionId = ref(props.initialVersionId); // 用户上次成功保存时所基于的版本ID
const isSaving = ref(false); // 通用的保存状态
const isProcessingChoice = ref(false); // 模态框按钮的加载状态

// 过时版本/冲突处理相关状态
const showStaleVersionModal = ref(false); // 是否显示版本冲突模态框
const userAcknowledgedConflict = ref(false); // 标记用户是否已选择“继续编辑”（忽略冲突）
const staleVersionData = ref({ // 存储从服务器409响应中获取的冲突数据
    current_version_id: null,
    current_version_number: null,
    diff_base_vs_current: '',
    diff_user_vs_current: '',
    current_content: '',
    current_version_creator: '',
    current_version_updated_at: '',
    base_version_number: null, // 用户开始编辑时的版本号
});

// 自动保存状态
const autosaveStatus = ref(null); // 自动保存的状态信息
const localLastSaved = ref(props.lastSaved ? new Date(props.lastSaved) : null); // 本地最后保存草稿的时间

// 移动端视图状态
const isMobile = ref(false);
// 更新移动端状态，并在移动端时默认隐藏预览面板
const updateMobileStatus = () => {
    isMobile.value = window.innerWidth < 768;
    if (isMobile.value && showPreviewPane.value) {
        showPreviewPane.value = false; // 移动端默认隐藏预览
    }
};

// 表单数据
const form = useForm({
    title: props.page.title,
    content: props.content, // 初始化为草稿或初始内容
    category_ids: props.page.category_ids || [],
    tag_ids: props.page.tag_ids || [],
    comment: '', // 提交说明
    version_id: currentBaseVersionId.value, // 关键：追踪保存所基于的版本ID
    force_conflict: false, // 强制冲突标志，用于模态框操作
});

// 计算属性：判断编辑器是否可编辑
const computedEditorIsEditable = computed(() => {
    // 如果没有编辑权限，或者正在保存/处理选择，则不可编辑
    if (!props.editorIsEditable || isSaving.value || isProcessingChoice.value) {
        return false;
    }
    // 如果冲突模态框显示，则主编辑器不可编辑
    if (showStaleVersionModal.value) {
        return false;
    }
    // 如果用户已确认冲突并选择继续编辑，则可编辑
    if (userAcknowledgedConflict.value) {
        return true;
    }
    // 否则，依赖于props中传入的编辑器可编辑状态（考虑了锁定等因素）
    return props.editorIsEditable;
});

// 计算属性：编辑器底部状态栏显示的信息
const editorStatusBar = computed(() => {
    if (isSaving.value) {
        return { class: 'text-blue-600 dark:text-blue-400', icon: ['fas', 'spinner'], spin: true, message: '正在保存...' };
    }
    if (isProcessingChoice.value) {
        return { class: 'text-blue-600 dark:text-blue-400', icon: ['fas', 'spinner'], spin: true, message: '正在处理您的选择...' };
    }
    if (showStaleVersionModal.value) {
        return { class: 'text-yellow-500 dark:text-yellow-400 font-semibold', icon: ['fas', 'exclamation-triangle'], spin: false, message: '检测到版本冲突，请在弹窗中选择操作。' };
    }
    if (userAcknowledgedConflict.value) {
        return { class: 'text-red-500 dark:text-red-400 font-semibold', icon: ['fas', 'exclamation-triangle'], spin: false, message: '注意：正在编辑旧版本，保存将触发冲突！' };
    }
    // 优先显示不与过时版本相关的通用错误
    if (form.errors.general) {
        return { class: 'text-red-600 dark:text-red-400 font-semibold', icon: ['fas', 'exclamation-circle'], spin: false, message: form.errors.general };
    }
    if (autosaveStatus.value) {
        switch (autosaveStatus.value.type) {
            case 'success': return { class: 'text-green-600 dark:text-green-400', icon: ['fas', 'check-circle'], spin: false, message: autosaveStatus.value.message };
            case 'error': return { class: 'text-red-600 dark:text-red-400', icon: ['fas', 'exclamation-circle'], spin: false, message: autosaveStatus.value.message };
            case 'pending': return { class: 'text-blue-600 dark:text-blue-400', icon: ['fas', 'spinner'], spin: true, message: autosaveStatus.value.message };
            default: return { class: 'text-gray-500 dark:text-gray-400', icon: ['fas', 'info-circle'], spin: false, message: autosaveStatus.value.message };
        }
    }
    if (hasUnsavedChanges.value) {
        return { class: 'text-gray-400 dark:text-gray-500 italic', icon: ['fas', 'circle-info'], spin: false, message: '有未保存的更改' };
    }
    if (localLastSaved.value) {
        return { class: 'text-gray-400 dark:text-gray-500 italic', icon: ['fas', 'save'], spin: false, message: `草稿已于 ${formatDateTime(localLastSaved.value)} 自动保存` };
    }
    return { class: 'text-gray-400 dark:text-gray-500 italic', icon: ['fas', 'circle-info'], spin: false, message: '编辑 v' + (currentBaseVersionNumber.value || '?') };
});

// 计算属性：判断表单是否有未保存的更改
const hasUnsavedChanges = computed(() => form.isDirty);

// 计算属性：编辑器面板的CSS类
const editorPaneClass = computed(() => {
    return showPreviewPane.value && !isMobile.value ? 'w-full md:w-1/2' : 'w-full';
});

// 计算属性：预览面板的CSS类
const previewPaneClass = computed(() => {
    return showPreviewPane.value && !isMobile.value ? 'hidden md:flex md:w-1/2' : 'hidden';
});

// 方法：切换预览面板显示状态
const togglePreviewPane = () => {
    showPreviewPane.value = !showPreviewPane.value;
};

// 方法：Tiptap编辑器自动保存完成时的回调
const onDraftSaved = (status) => {
    // 如果冲突模态框显示或冲突已确认，则不更新状态
    if (showStaleVersionModal.value || userAcknowledgedConflict.value) return;

    if (status && status.saved_at) {
        localLastSaved.value = new Date(status.saved_at);
    }
    // 如果保存成功或等待中（非错误），清除通用错误
    if (form.errors.general && (status?.type === 'success' || status?.type === 'pending')) {
        form.clearErrors('general');
    }
    autosaveStatus.value = status;

    // 非等待状态的自动保存信息在一定时间后消失
    if (status?.type !== 'pending') {
        setTimeout(() => {
            if (autosaveStatus.value === status) {
                autosaveStatus.value = null;
            }
        }, status?.type === 'error' ? 5000 : 3000);
    }
};

// 方法：处理编辑器状态更新事件
const handleEditorStatusUpdate = (status) => {
    // 如果冲突模态框显示或冲突已确认，则不更新状态
    if (showStaleVersionModal.value || userAcknowledgedConflict.value) return;
    autosaveStatus.value = status;
};

// 方法：保存页面
const savePage = async () => {
    if (isSaving.value) return; // 如果正在保存，则直接返回
    isSaving.value = true; // 设置保存状态为true
    autosaveStatus.value = { type: 'pending', message: '正在提交保存...' }; // 更新状态栏信息
    form.clearErrors(); // 清除之前的错误

    // 检查Tiptap编辑器实例是否存在
    if (!tiptapEditorRef.value?.editor) {
        form.setError('general', '编辑器实例丢失，请刷新页面。');
        autosaveStatus.value = { type: 'error', message: '编辑器错误' };
        flashMessageRef.value?.addMessage('error', '编辑器错误，请刷新页面。');
        isSaving.value = false;
        return;
    }

    const currentContent = tiptapEditorRef.value.editor.getHTML();
    // 检查内容是否为空
    if (currentContent === '<p></p>' || !currentContent.trim()) {
        form.setError('content', '内容不能为空。');
        autosaveStatus.value = { type: 'error', message: '内容不能为空' };
        flashMessageRef.value?.addMessage('error', '内容不能为空。');
        isSaving.value = false;
        return;
    }

    // 更新表单内容和基础版本ID
    form.content = currentContent;
    form.version_id = currentBaseVersionId.value;

    console.log(`Submitting save request for page ${props.page.id} based on version ID: ${form.version_id}. Force conflict: ${form.force_conflict}. User Acknowledged Conflict: ${userAcknowledgedConflict.value}`);

    // 发送保存请求
    try {
        const response = await axios.put(route('wiki.update', props.page.slug), form.data());
        console.log("Save/Update attempt response:", response.data);
        handleSaveResponse(response.data); // 处理保存响应
    } catch (error) {
        handleSaveError(error); // 处理保存错误
    } finally {
        isSaving.value = false; // 结束保存状态
        form.force_conflict = false; // 重置强制冲突标志
        // 如果状态栏显示为等待中且模态框未显示，则清除状态栏信息
        if (autosaveStatus.value?.type === 'pending' && !showStaleVersionModal.value) {
            autosaveStatus.value = null;
        }
        // 如果模态框未显示，重置用户已确认冲突的标志
        if (!showStaleVersionModal.value) {
            userAcknowledgedConflict.value = false;
        }
    }
};

// 方法：处理保存响应
const handleSaveResponse = (responseData) => {
    if (responseData.status === 'success' || responseData.status === 'conflict_resolved') {
        handleSaveSuccess(responseData); // 保存成功或冲突已解决
    } else if (responseData.status === 'no_changes') {
        handleNoChanges(responseData); // 无更改
    } else if (responseData.status === 'conflict_forced') {
        handleConflictForced(responseData); // 强制冲突
    } else {
        console.warn("Received unexpected success status:", responseData.status);
        autosaveStatus.value = { type: 'info', message: responseData.message || '操作完成，但状态未知。' };
        flashMessageRef.value?.addMessage('info', responseData.message || '操作完成，但状态未知。');
        userAcknowledgedConflict.value = false;
        if (responseData.redirect_url) {
            router.visit(responseData.redirect_url);
        }
    }
}

// 方法：处理保存成功或冲突已解决的情况
const handleSaveSuccess = (responseData) => {
    const newVersionId = responseData.new_version_id;
    const newVersionNumber = responseData.new_version_number;
    console.log('Save successful / Conflict resolved:', responseData.message);

    if (newVersionId != null && newVersionNumber != null) {
        currentBaseVersionId.value = newVersionId;
        currentBaseVersionNumber.value = newVersionNumber;
        lastSuccessfulSaveVersionId.value = newVersionId; // 更新最后成功保存的参考版本
        form.version_id = currentBaseVersionId.value; // 同步表单中的版本ID
        userAcknowledgedConflict.value = false; // 成功保存后重置冲突标志
        console.log(`Save success! New base version set to ID: ${currentBaseVersionId.value}, Number: ${currentBaseVersionNumber.value}`);
    } else {
        console.warn("Success response missing new version details. Base version not updated.");
    }

    form.comment = ''; // 清空评论字段
    form.defaults(); // 将当前表单状态设置为默认值（标记为干净）
    form.reset();    // 根据默认值重置表单的“脏”状态
    form.clearErrors(); // 清除所有表单错误
    localLastSaved.value = null; // 清除本地草稿时间指示器
    autosaveStatus.value = { type: 'success', message: responseData.message || '页面保存成功！' };
    flashMessageRef.value?.addMessage('success', responseData.message || '页面保存成功！');

    if (responseData.redirect_url) {
        router.visit(responseData.redirect_url); // 如果有重定向URL，则进行重定向
    } else {
        // 可选：停留在当前页面，在一定时间后清除状态栏信息
        setTimeout(() => { if (autosaveStatus.value?.type === 'success') autosaveStatus.value = null; }, 3000);
    }
};

// 方法：处理服务器未检测到更改的情况
const handleNoChanges = (responseData) => {
    console.log("No changes detected by server.");
    autosaveStatus.value = { type: 'info', message: responseData.message || '未检测到更改。' };
    flashMessageRef.value?.addMessage('info', responseData.message || '未检测到更改，页面未更新。');
    form.defaults(); // 将当前表单状态标记为干净
    form.reset();
    localLastSaved.value = null; // 草稿可能已不相关
    userAcknowledgedConflict.value = false; // 重置冲突标志
    setTimeout(() => { if (autosaveStatus.value?.type === 'info') autosaveStatus.value = null; }, 3000);
};

// 方法：处理强制冲突保存的响应
const handleConflictForced = (responseData) => {
    console.log("Conflict forced response received:", responseData);
    autosaveStatus.value = { type: 'warning', message: responseData.message || '更改已保存但标记为冲突。' };
    flashMessageRef.value?.addMessage('warning', responseData.message || '您的更改已保存，但由于版本冲突，页面已被标记并锁定。');
    form.defaults(); // 将强制保存后的状态视为“干净”
    form.reset();
    // 由于页面现在可能被锁定，重定向用户
    if (responseData.redirect_url) {
        router.visit(responseData.redirect_url);
    } else {
        router.visit(route('wiki.show', props.page.slug));
    }
};

// 方法：处理保存错误
const handleSaveError = (error) => {
    console.error("Save error handler triggered:", error);
    userAcknowledgedConflict.value = false; // 保存失败时重置“继续编辑”状态

    if (error.response) {
        // 如果是409版本冲突错误
        if (error.response.status === 409 && error.response.data?.status === 'stale_version') {
            console.log("Stale version detected via 409 response:", error.response.data);
            staleVersionData.value = { // 存储冲突数据
                current_version_id: error.response.data.current_version_id ?? null,
                current_version_number: error.response.data.current_version_number ?? null,
                base_version_number: error.response.data.base_version_number ?? currentBaseVersionNumber.value,
                diff_base_vs_current: error.response.data.diff_base_vs_current || '<p class="p-2 text-red-400">无法加载基础版本与最新版本的差异。</p>',
                diff_user_vs_current: error.response.data.diff_user_vs_current || '<p class="p-2 text-red-400">无法加载您的编辑与最新版本的差异。</p>',
                current_content: error.response.data.current_content ?? '无法加载最新版本的内容。',
                current_version_creator: error.response.data.current_version_creator ?? '未知用户',
                current_version_updated_at: error.response.data.current_version_updated_at ?? '',
            };
            showStaleVersionModal.value = true; // 显示冲突模态框
            autosaveStatus.value = { type: 'error', message: '版本冲突，请选择操作' };
        }
        // 如果是422验证错误
        else if (error.response.status === 422) {
            form.errors = error.response.data.errors; // 将验证错误分配给表单
            const firstError = Object.values(error.response.data.errors).flat()[0];
            autosaveStatus.value = { type: 'error', message: firstError || '提交的内容有误' };
            flashMessageRef.value?.addMessage('error', firstError || '提交的内容有误，请检查表单。');
        }
        // 其他服务器错误
        else {
            const errorMsg = error.response.data?.message || error.message || '保存时发生未知服务器错误。';
            form.setError('general', errorMsg);
            autosaveStatus.value = { type: 'error', message: errorMsg };
            flashMessageRef.value?.addMessage('error', errorMsg);
            setTimeout(() => { if (autosaveStatus.value?.type === 'error') autosaveStatus.value = null; }, 5000);
        }
    }
    // 网络错误或请求无法发送
    else {
        const netErrorMsg = '网络连接错误或请求无法发送。';
        form.setError('general', netErrorMsg);
        autosaveStatus.value = { type: 'error', message: '网络错误' };
        flashMessageRef.value?.addMessage('error', netErrorMsg);
        setTimeout(() => { if (autosaveStatus.value?.type === 'error') autosaveStatus.value = null; }, 5000);
    }
};

// 冲突模态框按钮处理：继续编辑（忽略冲突）
const handleContinueEditing = () => {
    if (isProcessingChoice.value) return; // 如果正在处理，则返回
    console.log("User chose to continue editing the outdated version.");
    showStaleVersionModal.value = false; // 关闭模态框
    userAcknowledgedConflict.value = true; // 设置用户已确认冲突的标志
    autosaveStatus.value = { type: 'warning', message: '注意：正在编辑旧版本，保存将触发冲突！' }; // 更新状态栏信息
    // 重新启用编辑器并聚焦
    nextTick(() => {
        tiptapEditorRef.value?.editor?.commands?.focus();
    });
    // 状态消息在一定时间后消失
    setTimeout(() => { if (autosaveStatus.value?.type === 'warning') autosaveStatus.value = null; }, 4000);
};

// 冲突模态框按钮处理：放弃编辑并加载最新版本
const handleDiscardAndLoadLatest = async () => {
    if (isProcessingChoice.value) return;
    isProcessingChoice.value = true;
    autosaveStatus.value = { type: 'pending', message: '正在放弃更改并加载最新...' };
    showStaleVersionModal.value = false; // 立即关闭模态框

    try {
        // 尝试删除草稿
        try {
            await axios.delete(route('wiki.draft.delete', { page: props.page.slug }));
            console.log(`User draft deleted for page ${props.page.id}`);
            localLastSaved.value = null;
        } catch (draftError) {
            console.warn("Failed to delete draft while discarding, proceeding anyway.", draftError);
        }

        // 从冲突数据中获取最新内容和版本详情
        const latestContent = staleVersionData.value.current_content || '<p></p>';
        const latestVersionId = staleVersionData.value.current_version_id;
        const latestVersionNumber = staleVersionData.value.current_version_number;

        // 更新表单内容
        form.content = latestContent;
        form.comment = ''; // 清空评论

        // 更新Tiptap编辑器内容，但不触发'update'事件
        if (tiptapEditorRef.value?.editor) {
            tiptapEditorRef.value.editor.commands.setContent(latestContent, false);
        } else {
            console.error("Editor instance not found when discarding.");
            flashMessageRef.value?.addMessage('error', '编辑器实例出错，请尝试刷新。');
            isProcessingChoice.value = false;
            autosaveStatus.value = { type: 'error', message: '编辑器错误' };
            return;
        }

        // 更新基础版本追踪状态
        currentBaseVersionId.value = latestVersionId;
        currentBaseVersionNumber.value = latestVersionNumber;
        lastSuccessfulSaveVersionId.value = latestVersionId;
        form.version_id = currentBaseVersionId.value;

        userAcknowledgedConflict.value = false; // 重置冲突标志

        // 重置表单状态，基于加载的最新内容设置默认值
        form.defaults({
            title: form.title, // 保留可能的标题修改
            content: latestContent,
            category_ids: form.category_ids, // 保留可能的分类修改
            tag_ids: form.tag_ids,           // 保留可能的标签修改
            comment: '',
            version_id: latestVersionId
        });
        form.reset(); // 重置字段并清除脏状态/错误

        // 更新状态并通知用户
        const successMsg = `已加载最新版本 v${latestVersionNumber || '?'}. 之前的更改已放弃。`;
        autosaveStatus.value = { type: 'success', message: successMsg };
        flashMessageRef.value?.addMessage('success', successMsg);

        // 重新启用编辑器并聚焦
        nextTick(() => {
            tiptapEditorRef.value?.editor?.setEditable(true);
            tiptapEditorRef.value?.editor?.commands.focus();
        });

        setTimeout(() => { if (autosaveStatus.value?.type === 'success') autosaveStatus.value = null; }, 4000);

    } catch (error) {
        console.error("Error during 'Discard & Edit Latest':", error);
        const errorMsg = '加载最新版本时出错，请尝试手动刷新页面。';
        autosaveStatus.value = { type: 'error', message: errorMsg };
        flashMessageRef.value?.addMessage('error', errorMsg);
        nextTick(() => {
            if (tiptapEditorRef.value?.editor) {
                tiptapEditorRef.value.editor.setEditable(props.editorIsEditable);
            }
        });
    } finally {
        isProcessingChoice.value = false;
        if (autosaveStatus.value?.type === 'pending') autosaveStatus.value = null;
    }
};

// 冲突模态框按钮处理：放弃编辑并退出
const handleDiscardAndExit = async () => {
    if (isProcessingChoice.value) return;
    isProcessingChoice.value = true;
    autosaveStatus.value = { type: 'pending', message: '正在放弃编辑并退出...' };
    showStaleVersionModal.value = false;

    try {
        // 尝试删除草稿
        try {
            await axios.delete(route('wiki.draft.delete', { page: props.page.slug }));
            console.log(`User draft deleted for page ${props.page.id} before exiting.`);
        } catch (draftError) {
            console.warn("Failed to delete draft before exiting.", draftError);
        }

        // 重定向回页面详情页
        router.visit(route('wiki.show', props.page.slug), {
            onFinish: () => {
                isProcessingChoice.value = false;
                autosaveStatus.value = null;
            }
        });

    } catch (error) {
        console.error("Error during 'Discard & Exit':", error);
        const errorMsg = '退出时出错，请手动返回页面。';
        autosaveStatus.value = { type: 'error', message: errorMsg };
        flashMessageRef.value?.addMessage('error', errorMsg);
        isProcessingChoice.value = false;
        if (autosaveStatus.value?.type === 'pending') autosaveStatus.value = null;
    }
};

// 冲突模态框按钮处理：强制保存（我的版本）
const handleForceSave = () => {
    if (isProcessingChoice.value || isSaving.value) return;
    isProcessingChoice.value = true;
    showStaleVersionModal.value = false;

    // 设置强制冲突标志，并触发常规保存流程
    form.force_conflict = true;
    savePage()
        .finally(() => {
            form.force_conflict = false;
            isProcessingChoice.value = false;
        });
};


// 方法：刷新页面数据
const refreshPage = () => {
    if (isSaving.value || isProcessingChoice.value) {
        flashMessageRef.value?.addMessage('warning', "请等待当前操作完成后再刷新。");
        return;
    }
    // 如果有未保存的更改或已确认冲突，则提示用户
    if (form.isDirty || userAcknowledgedConflict.value) {
        if (!confirm("刷新页面将丢失未保存的更改或编辑状态，确定要刷新吗？")) {
            return;
        }
    }

    console.log("Refreshing page data via router.reload...");
    autosaveStatus.value = { type: 'pending', message: '正在刷新页面数据...' };

    router.reload({
        // 仅重新加载编辑页面所需的相关属性
        only: [
            'page', 'content', 'categories', 'tags', 'hasDraft',
            'lastSaved', 'isConflict', 'editorIsEditable',
            'initialVersionId', 'initialVersionNumber', 'errors'
        ],
        preserveState: false, // 不保留旧状态，重新获取新数据
        preserveScroll: true, // 保持滚动位置
        onSuccess: (pageResponse) => {
            console.log("Page reload via router.reload successful.");
            const newProps = pageResponse.props;

            // 重置表单默认值
            form.defaults({
                title: newProps.page.title,
                content: newProps.content,
                category_ids: newProps.page.category_ids || [],
                tag_ids: newProps.page.tag_ids || [],
                comment: '',
                version_id: newProps.initialVersionId, // 重置基础版本ID
                force_conflict: false,
            });
            form.reset(); // 重置表单字段并清除脏状态/错误

            // 重置版本追踪状态
            currentBaseVersionId.value = newProps.initialVersionId;
            currentBaseVersionNumber.value = newProps.initialVersionNumber;
            lastSuccessfulSaveVersionId.value = newProps.initialVersionId;
            userAcknowledgedConflict.value = false; // 重置标志
            showStaleVersionModal.value = false; // 确保模态框关闭

            // 更新编辑器内容和状态
            if (tiptapEditorRef.value?.editor) {
                tiptapEditorRef.value.editor.commands.setContent(newProps.content, false);
                tiptapEditorRef.value.editor.setEditable(newProps.editorIsEditable); // 反映当前可编辑状态
            } else {
                console.warn("Editor ref not available after reload for content update.");
            }

            // 更新其他本地状态
            localLastSaved.value = newProps.lastSaved ? new Date(newProps.lastSaved) : null;
            // 检查刷新后是否存在持久性错误
            if (newProps.errors && Object.keys(newProps.errors).length > 0) {
                form.errors = newProps.errors;
                if (newProps.errors.general) {
                    flashMessageRef.value?.addMessage('error', newProps.errors.general);
                }
            } else {
                form.clearErrors();
            }

            autosaveStatus.value = { type: 'success', message: '页面数据已刷新！' };
            flashMessageRef.value?.addMessage('success', '页面数据已刷新！');
            setTimeout(() => { if (autosaveStatus.value?.type === 'success') autosaveStatus.value = null; }, 2000);
        },
        onError: (errors) => {
            console.error("Page reload failed:", errors);
            form.errors = errors;
            autosaveStatus.value = { type: 'error', message: '刷新失败，请稍后重试或手动刷新' };
            flashMessageRef.value?.addMessage('error', '刷新页面数据失败，请尝试手动刷新浏览器。');
            setTimeout(() => { if (autosaveStatus.value?.type === 'error') autosaveStatus.value = null; }, 5000);
        }
    });
};


// 实时处理（假设后端已正确广播）
let echoChannel = null;

// 方法：处理实时版本更新事件
const handleRealtimeVersionUpdate = (newVersionId) => {
    // 忽略来自用户自身保存的更新
    if (newVersionId != null && newVersionId === lastSuccessfulSaveVersionId.value) {
        console.log(`Ignoring Echo update for own saved version ${newVersionId}.`);
        return;
    }
    // 如果模态框已打开，则忽略Echo更新，让模态框处理状态
    if (showStaleVersionModal.value) {
        console.log("Conflict modal already showing, ignoring Echo update for now.");
        return;
    }

    // 如果接收到的版本比当前基础版本新
    if (newVersionId != null && newVersionId !== currentBaseVersionId.value) {
        console.warn(`Page outdated detected via Echo! Current base: ${currentBaseVersionId.value}, Newest DB: ${newVersionId}. Setting userAcknowledgedConflict=false.`);
        userAcknowledgedConflict.value = false; // 标记为过时，需要用户通过模态框触发操作
        autosaveStatus.value = { type: 'error', message: '注意：页面已被其他用户更新！点击保存处理。' };
        flashMessageRef.value?.addMessage('error', '页面已被其他用户更新！如果您编辑了内容，点击保存将显示处理选项。', 10000);
        // 不直接从Echo显示模态框，用户在下次保存尝试时触发
    } else {
        console.log(`Echo update received for version ${newVersionId}, which matches current base or is invalid. No action.`);
    }
};

// 方法：设置Echo监听器
const setupEchoListener = () => {
    if (!window.Echo) { console.warn("Echo is not initialized!"); return; }
    const channelName = `wiki.page.${props.page.id}`;
    try {
        echoChannel = window.Echo.channel(channelName);
        echoChannel.listen('.page.version.updated', (data) => {
            console.log('Received page.version.updated event via Echo:', data);
            handleRealtimeVersionUpdate(data.newVersionId);
        });
        echoChannel.error((error) => { console.error(`Echo channel error on ${channelName}:`, error); });
        console.log(`Listening on Echo channel: ${channelName}`);
    } catch (error) {
        console.error(`Error setting up Echo listener on channel ${channelName}:`, error);
    }
};

// 方法：清理Echo监听器
const cleanupEchoListener = () => {
    if (echoChannel) {
        try {
            echoChannel.stopListening('.page.version.updated');
            window.Echo.leave(`wiki.page.${props.page.id}`);
            console.log(`Stopped listening and left Echo channel: wiki.page.${props.page.id}`);
        } catch (e) { console.error("Error stopping Echo listener or leaving channel:", e); }
        echoChannel = null;
    }
};


// 方法：在新标签页中预览
const openPreviewInNewTab = () => {
    const currentContent = tiptapEditorRef.value?.editor?.getHTML() || form.content;
    if (!form.title.trim()) { alert('请先输入页面标题再进行预览！'); return; }
    if (!currentContent || currentContent === '<p></p>' || currentContent.trim() === '') { alert('请先输入页面内容再进行预览！'); return; }
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
        content: currentContent,
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

// 生命周期钩子：组件挂载后
onMounted(() => {
    console.log("Edit.vue mounted. Initial Base:", currentBaseVersionId.value, `(v${currentBaseVersionNumber.value})`, "Editable:", props.editorIsEditable);
    setupEchoListener(); // 设置Echo监听器

    // 监听Inertia错误（例如，表单验证或重定向错误）
    watch(() => pageProps.errors, (newErrors) => {
        if (newErrors && Object.keys(newErrors).length > 0) {
            form.errors = newErrors; // 将验证错误分配给表单
            if (newErrors.general && newErrors.general !== autosaveStatus.value?.message) {
                flashMessageRef.value?.addMessage('error', newErrors.general);
                autosaveStatus.value = { type: 'error', message: newErrors.general };
                setTimeout(() => { if (autosaveStatus.value?.message === newErrors.general) autosaveStatus.value = null; }, 5000);
            }
        } else if (Object.keys(form.errors).length > 0) {
            form.clearErrors(); // 如果Inertia错误已清除，则清除表单错误
        }
    }, { deep: true, immediate: true });


    updateMobileStatus(); // 更新移动端状态
    window.addEventListener('resize', updateMobileStatus); // 监听窗口大小变化

    // 根据加载的草稿初始设置自动保存状态
    if (props.hasDraft && props.lastSaved) {
        autosaveStatus.value = { type: 'info', message: `已加载 ${formatDateTime(localLastSaved.value)} 的草稿` };
        setTimeout(() => { if (autosaveStatus.value?.type === 'info') autosaveStatus.value = null; }, 4000);
    }

    // 在页面卸载前提示（如果有未保存的更改且不在冲突模态框中）
    window.onbeforeunload = (event) => {
        if (form.isDirty && !isSaving.value && !isProcessingChoice.value && !showStaleVersionModal.value) {
            event.preventDefault();
            event.returnValue = '您有未保存的更改，确定要离开吗？草稿可能无法完全保存。';
            return event.returnValue;
        }
    };
});

// 生命周期钩子：组件卸载前
onBeforeUnmount(() => {
    console.log("Edit.vue unmounting...");
    cleanupEchoListener(); // 清理Echo监听器
    window.removeEventListener('resize', updateMobileStatus); // 移除窗口大小监听器
    window.onbeforeunload = null; // 移除onbeforeunload监听器

    // 尝试在卸载时保存草稿，仅当安全且逻辑上可行时
    const canAutosaveOnUnload = computedEditorIsEditable.value // 检查当前可编辑状态
        && form.isDirty                     // 是否有更改
        && !isSaving.value                  // 是否正在保存
        && !isProcessingChoice.value       // 是否正在处理模态框选择
        && !showStaleVersionModal.value    // 模态框是否打开
        && !userAcknowledgedConflict.value; // 用户是否故意编辑过时版本

    if (canAutosaveOnUnload && tiptapEditorRef.value?.editor) {
        if (navigator.sendBeacon) {
            console.log("Attempting to save draft on unmount via sendBeacon (safe state check passed).");
            const url = route('wiki.save-draft', props.page.id);
            const payload = JSON.stringify({
                content: tiptapEditorRef.value.editor.getHTML(),
                _token: pageProps.csrf
            });
            const blob = new Blob([payload], { type: 'application/json' });

            try {
                const success = navigator.sendBeacon(url, blob);
                console.log("sendBeacon call returned:", success ? "Queued" : "Failed to queue");
            } catch (e) {
                console.error("sendBeacon call threw an error:", e);
            }
        } else {
            console.warn("sendBeacon not supported, cannot reliably save draft on unmount/unload.");
        }
    } else {
        console.log("Skipping draft save on unmount. State:", {
            computedEditable: computedEditorIsEditable.value,
            isDirty: form.isDirty,
            isSaving: isSaving.value,
            isProcessingChoice: isProcessingChoice.value,
            isModalOpen: showStaleVersionModal.value,
            acknowledgedConflict: userAcknowledgedConflict.value,
            editorExists: !!tiptapEditorRef.value?.editor
        });
    }
});
</script>

<style scoped>
/* 计算属性高度，确保内容适应视口高度 */
.h-\[calc\(100vh-theme\(space\.24\)\)\] {
    height: calc(100vh - 6rem);
}

/* 容器全高 */
.container {
    height: 100%;
}

/* 背景模糊效果 */
.flex-grow.bg-white\/80 {
    display: flex;
    flex-direction: column;
}

/* 布局：grow 1，flex，column方向，md以上row方向 */
.flex-grow.flex.flex-col.md\:flex-row {
    flex-grow: 1;
    min-height: 0;
}

/* 编辑器面板滚动条样式 */
.editor-pane-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: #a0aec0 #e2e8f0;
}

/* 暗色模式下编辑器面板滚动条样式 */
.dark .editor-pane-scrollbar {
    scrollbar-color: #4b5563 #2d3748;
}

/* Webkit浏览器下编辑器面板滚动条宽度 */
.editor-pane-scrollbar::-webkit-scrollbar {
    width: 6px;
}

/* Webkit浏览器下编辑器面板滚动条轨道 */
.editor-pane-scrollbar::-webkit-scrollbar-track {
    background: #e2e8f0;
    border-radius: 3px;
}

/* Webkit浏览器暗色模式下编辑器面板滚动条轨道 */
.dark .editor-pane-scrollbar::-webkit-scrollbar-track {
    background: #2d3748;
}

/* Webkit浏览器下编辑器面板滚动条拇指 */
.editor-pane-scrollbar::-webkit-scrollbar-thumb {
    background-color: #a0aec0;
    border-radius: 3px;
}

/* Webkit浏览器暗色模式下编辑器面板滚动条拇指 */
.dark .editor-pane-scrollbar::-webkit-scrollbar-thumb {
    background-color: #4b5563;
}

/* Tiptap编辑器根元素样式深度选择器 */
:deep(.tiptap-editor) {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

/* Tiptap编辑器内容区域样式深度选择器 */
:deep(.editor-content) {
    flex-grow: 1;
    overflow-y: auto;
    min-height: 250px;
}

/* 输入框、文本域、选择框通用样式 */
.input-field,
.textarea-field,
select {
    @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400 text-sm disabled:bg-gray-100 dark:disabled:bg-gray-800/60 disabled:cursor-not-allowed dark:disabled:text-gray-500;
}

/* 文本域最小高度 */
.textarea-field {
    min-height: 5rem;
}

/* 复选框组样式 */
.checkbox-group {
    @apply grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 max-h-48 overflow-y-auto p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-800/50;
}

/* 禁用状态下的复选框组样式 */
.checkbox-group.disabled-group {
    @apply bg-gray-100 dark:bg-gray-800/30 opacity-60 cursor-not-allowed;
}

/* 复选框样式 */
.checkbox {
    @apply h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 disabled:cursor-not-allowed;
}

/* 禁用状态下复选框标签样式 */
.checkbox:disabled+label {
    @apply cursor-not-allowed opacity-60;
}

/* 按钮通用样式 */
.btn-primary,
.btn-secondary,
.btn-warning,
.btn-danger {
    @apply px-4 py-1.5 rounded-md transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center justify-center;
}

/* 主要按钮样式 */
.btn-primary {
    @apply bg-blue-600 text-white hover:bg-blue-700;
}

/* 次要按钮样式 */
.btn-secondary {
    @apply bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500;
}

/* 警告按钮样式 */
.btn-warning {
    @apply bg-yellow-500 text-white hover:bg-yellow-600;
}

/* 危险按钮样式 */
.btn-danger {
    @apply bg-red-600 text-white hover:bg-red-700;
}

/* 警告框通用样式 */
.alert {
    @apply p-3 rounded-md border-l-4 mb-3 text-sm;
}

/* 错误警告框样式 */
.alert-error {
    @apply bg-red-50 border-red-400 text-red-700 dark:bg-red-900/30 dark:text-red-300 dark:border-red-600;
}

/* 警告警告框样式 */
.alert-warning {
    @apply bg-yellow-50 border-yellow-400 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300 dark:border-yellow-600;
}

/* 信息警告框样式 */
.alert-info {
    @apply bg-blue-50 border-blue-400 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-600;
}

/* 编辑器状态栏样式 */
.editor-status-bar {
    @apply text-xs min-h-[16px];
}

/* 差异对比模态框样式 */
.diff-modal {
    max-height: 30vh;
    overflow-y: auto;
    background-color: #1f2937;
    border: 1px solid #4b5563;
    border-radius: 0.25rem;
    scrollbar-width: thin;
    scrollbar-color: #4b5563 #1f2937;
}

/* Webkit浏览器下差异对比模态框滚动条宽度 */
.diff-modal::-webkit-scrollbar {
    width: 5px;
}

/* Webkit浏览器下差异对比模态框滚动条轨道 */
.diff-modal::-webkit-scrollbar-track {
    background: #1f2937;
}

/* Webkit浏览器下差异对比模态框滚动条拇指 */
.diff-modal::-webkit-scrollbar-thumb {
    background-color: #4b5563;
    border-radius: 2.5px;
}

/* 差异对比表格样式深度选择器 */
.diff-modal :deep(table.diff) {
    font-family: monospace;
    font-size: 0.7rem;
    line-height: 1.3;
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

/* 差异对比表格单元格样式深度选择器 */
.diff-modal :deep(td),
.diff-modal :deep(th) {
    padding: 0.1rem 0.3rem !important;
    border: 1px solid #374151 !important;
    vertical-align: top;
    word-break: break-word;
    white-space: pre-wrap;
    color: #d1d5db;
}

/* 差异对比表格头部样式深度选择器 */
.diff-modal :deep(th) {
    background-color: #2d3748 !important;
    border-color: #4b5563 !important;
    text-align: center;
    color: #9ca3af;
}

/* 差异对比表格行号列样式深度选择器 */
.diff-modal :deep(td.lines-no) {
    background-color: #2d3748 !important;
    border-right: 1px solid #4b5563 !important;
    color: #6b7280;
    text-align: right;
    padding-right: 0.5rem;
    width: 35px !important;
    min-width: 35px !important;
    user-select: none;
}

/* 删除行左侧样式深度选择器 */
.diff-modal :deep(.ChangeDelete .Left) {
    background-color: rgba(153, 27, 27, 0.25) !important;
}

/* 删除文本样式深度选择器 */
.diff-modal :deep(.ChangeDelete .Left del),
.diff-modal :deep(td.Left .ChangeReplace del) {
    background-color: rgba(220, 38, 38, 0.35) !important;
    color: #fecaca !important;
    text-decoration: line-through;
    border-radius: 0.125rem;
    padding: 0 0.1rem;
}

/* 插入行右侧样式深度选择器 */
.diff-modal :deep(.ChangeInsert .Right) {
    background-color: rgba(4, 100, 65, 0.25) !important;
}

/* 插入文本样式深度选择器 */
.diff-modal :deep(.ChangeInsert .Right ins),
.diff-modal :deep(td.Right .ChangeReplace ins) {
    background-color: rgba(5, 150, 105, 0.35) !important;
    color: #a7f3d0 !important;
    text-decoration: none;
    border-radius: 0.125rem;
    padding: 0 0.1rem;
}

/* 替换行左侧样式深度选择器 */
.diff-modal :deep(.ChangeReplace .Left) {
    background-color: rgba(153, 27, 27, 0.25) !important;
}

/* 替换行右侧样式深度选择器 */
.diff-modal :deep(.ChangeReplace .Right) {
    background-color: rgba(4, 100, 65, 0.25) !important;
}

/* 媒体查询：小屏幕设备适配 */
@media (max-width: 767px) {
    .md\:flex-row {
        flex-direction: column;
    }

    .md\:w-1\/2 {
        width: 100%;
    }

    .editor-pane-scrollbar {
        padding-right: 0;
    }

    .flex-grow.flex.flex-col.md\:flex-row {
        gap: 1rem;
    }

    .diff-modal :deep(table.diff) {
        font-size: 0.65rem;
    }

    .diff-modal :deep(td.lines-no) {
        width: 30px !important;
        min-width: 30px !important;
        padding-right: 0.3rem;
    }

    .diff-modal :deep(td),
    .diff-modal :deep(th) {
        padding: 0.05rem 0.2rem !important;
    }
}
</style>
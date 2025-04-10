<template>
    <MainLayout :navigationLinks="navigationLinks">
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <h1 class="text-2xl font-bold mb-6">编辑 Wiki 页面: {{ page.title }}</h1>

                <!-- 冲突状态提示 -->
                <div v-if="isConflict && !canResolveConflict"
                    class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md">
                    <div class="flex items-center">
                        <font-awesome-icon :icon="['fas', 'exclamation-circle']" class="mr-2" />
                        <p>该页面当前存在编辑冲突，您无法编辑。请等待有权限的人员解决冲突。</p>
                        <Link :href="route('wiki.show', page.slug)"
                            class="ml-auto px-3 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600 whitespace-nowrap">
                        查看页面
                        </Link>
                    </div>
                </div>
                <div v-else-if="isConflict && canResolveConflict"
                    class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded-md">
                    <div class="flex items-center">
                        <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="mr-2" />
                        <p>该页面处于冲突状态。您可以编辑下方内容以解决冲突，或
                            <Link :href="route('wiki.show-conflicts', page.slug)"
                                class="font-bold underline hover:text-yellow-800">前往冲突解决页面</Link> 进行对比和合并。
                        </p>
                    </div>
                </div>

                <!-- 编辑者列表 -->
                <EditorsList :pageId="page.id" class="mb-4" />

                <!-- 草稿提示 -->
                <div v-if="hasDraft && localLastSaved"
                    class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded-md">
                    <div class="flex items-center">
                        <font-awesome-icon :icon="['fas', 'info-circle']" class="mr-2" />
                        <p>
                            检测到本地草稿，已自动加载。最后自动保存于 {{ formatDateTime(localLastSaved) }}。
                        </p>
                    </div>
                </div>

                <!-- 表单 -->
                <form @submit.prevent="savePage">
                    <div class="space-y-6">
                        <!-- 标题 -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                                标题 <span class="text-red-500">*</span>
                            </label>
                            <input id="title" v-model="form.title" type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
                                :disabled="!isEditable" required />
                            <InputError class="mt-1" :message="form.errors.title" />
                        </div>

                        <!-- 编辑器 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                内容 <span class="text-red-500">*</span>
                            </label>
                            <Editor v-model="form.content" :editable="isEditable" :autosave="isEditable"
                                :pageId="page.id" @saved="onDraftSaved" @statusUpdate="handleEditorStatusUpdate"
                                placeholder="开始编辑页面内容..." ref="tiptapEditorRef" />
                            <InputError class="mt-1" :message="form.errors.content" />
                            <!-- 自动保存状态 -->
                            <div v-if="isEditable"
                                class="mt-1 text-xs text-gray-500 flex justify-end items-center min-h-[20px]">
                                <span v-if="autosaveStatus" :class="autosaveStatusClass" class="ml-2 flex items-center">
                                    <font-awesome-icon :icon="autosaveStatusIcon"
                                        :spin="autosaveStatus.type === 'pending'" class="mr-1" />
                                    {{ autosaveStatus.message }}
                                </span>
                                <span v-else class="ml-2 text-gray-400 italic"></span>
                            </div>
                        </div>

                        <!-- 分类 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                分类 <span class="text-red-500">*</span> (至少选择一个)
                            </label>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-4 gap-y-2 max-h-60 overflow-y-auto p-2 border rounded"
                                :class="{ 'bg-gray-100 opacity-70': !isEditable }">
                                <div v-for="category in categories" :key="category.id" class="flex items-center">
                                    <input type="checkbox" :id="`category-${category.id}`" :value="category.id"
                                        v-model="form.category_ids" :disabled="!isEditable"
                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 disabled:cursor-not-allowed" />
                                    <label :for="`category-${category.id}`" class="ml-2 text-sm text-gray-700">
                                        {{ category.name }}
                                    </label>
                                </div>
                            </div>
                            <InputError class="mt-1" :message="form.errors.category_ids" />
                        </div>

                        <!-- 标签 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                标签 (可选)
                            </label>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-4 gap-y-2 max-h-60 overflow-y-auto p-2 border rounded"
                                :class="{ 'bg-gray-100 opacity-70': !isEditable }">
                                <div v-for="tag in tags" :key="tag.id" class="flex items-center">
                                    <input type="checkbox" :id="`tag-${tag.id}`" :value="tag.id" v-model="form.tag_ids"
                                        :disabled="!isEditable"
                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 disabled:cursor-not-allowed" />
                                    <label :for="`tag-${tag.id}`" class="ml-2 text-sm text-gray-700">
                                        {{ tag.name }}
                                    </label>
                                </div>
                            </div>
                            <InputError class="mt-1" :message="form.errors.tag_ids" />
                        </div>

                        <!-- 提交说明 -->
                        <div>
                            <label for="comment" class="block text-sm font-medium text-gray-700 mb-1">
                                提交说明 <span class="text-xs text-gray-500">(本次修改的简要描述)</span>
                            </label>
                            <textarea id="comment" v-model="form.comment" rows="2" :disabled="!isEditable"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
                                placeholder="例如：修正了XX数据，添加了YY说明..."></textarea>
                            <InputError class="mt-1" :message="form.errors.comment" />
                        </div>

                        <!-- 操作按钮 -->
                        <div class="flex justify-end space-x-3 pt-4 border-t">
                            <Link :href="route('wiki.show', page.slug)"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                            取消
                            </Link>
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="!isEditable || form.processing">
                                <font-awesome-icon v-if="form.processing" :icon="['fas', 'spinner']" spin
                                    class="mr-1" />
                                {{ form.processing ? '正在保存...' : '保存页面' }}
                            </button>
                        </div>
                        <!-- 通用错误 -->
                        <div v-if="form.errors.general" class="mt-1 text-sm text-red-600 text-right font-medium">
                            <font-awesome-icon :icon="['fas', 'exclamation-circle']" class="mr-1" />
                            {{ form.errors.general }}
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue';
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Editor from '@/Components/Wiki/Editor.vue';
import InputError from '@/Components/Other/InputError.vue';
import axios from 'axios';
import { formatDateTime } from '@/utils/formatters';
import EditorsList from '@/Components/Wiki/EditorsList.vue';
import { mainNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = mainNavigationLinks;
const pageProps = usePage().props;

const props = defineProps({
    page: { type: Object, required: true },
    content: { type: String, required: true },
    categories: { type: Array, required: true },
    tags: { type: Array, required: true },
    hasDraft: { type: Boolean, default: false },
    lastSaved: { type: String, default: null }, // ISO string
    canResolveConflict: { type: Boolean, default: false },
    isConflict: { type: Boolean, default: false }, // Explicitly pass conflict status
    errors: Object
});

let lockRefreshTimer = null;
const tiptapEditorRef = ref(null);

const form = useForm({
    title: props.page.title,
    content: props.content,
    category_ids: props.page.category_ids || [],
    tag_ids: props.page.tag_ids || [],
    comment: '',
    version_id: props.page.current_version_id,
});

const lastSaved = ref(props.hasDraft ? new Date() : null); // Track last saved time from draft
const autosaveStatus = ref(null);
const localLastSaved = ref(props.lastSaved ? new Date(props.lastSaved) : null);

// --- Computed Properties ---
const isEditable = computed(() => {
    // 可编辑条件：页面不是冲突状态，或者页面冲突但当前用户有权解决
    return !props.isConflict || props.canResolveConflict;
});

const canForceUnlock = computed(() => {
    // 检查是否锁定、锁定者不是当前用户、且当前用户有强制解锁权限
    return props.lockInfo.isLocked
        && props.lockInfo.lockedBy?.id !== pageProps.auth.user?.id
        && pageProps.auth.user?.permissions?.includes('wiki.resolve_conflict'); // 假设权限名称为 'wiki.force_unlock'
});

const autosaveStatusClass = computed(() => {
    if (!autosaveStatus.value) return 'text-gray-400 italic';
    switch (autosaveStatus.value.type) {
        case 'success': return 'text-green-600';
        case 'error': return 'text-red-600';
        case 'pending': return 'text-blue-600';
        default: return 'text-gray-500';
    }
});

const autosaveStatusIcon = computed(() => {
    if (!autosaveStatus.value) return ['fas', 'circle-info']; // Example default icon
    switch (autosaveStatus.value.type) {
        case 'success': return ['fas', 'check-circle'];
        case 'error': return ['fas', 'exclamation-circle'];
        case 'pending': return ['fas', 'spinner']; // Assuming you have a spinner icon
        default: return ['fas', 'info-circle'];
    }
});

// --- Methods ---
const onDraftSaved = (status) => {
    // Update lastSaved ref if draft was saved successfully
    if (status && status.saved_at) {
        lastSaved.value = new Date(status.saved_at);
    }
    autosaveStatus.value = status;
    // Optionally clear general error if draft save succeeds
    if (status.type === 'success') {
        form.clearErrors('general');
    }
};

const handleEditorStatusUpdate = (status) => {
    autosaveStatus.value = status;
};

const savePage = () => {
    // Ensure content is updated from editor if using manual triggers
    // If using v-model, form.content should be up-to-date
    if (tiptapEditorRef.value && tiptapEditorRef.value.editor) {
        form.content = tiptapEditorRef.value.editor.getHTML();
    }
    form.transform(data => ({
        ...data,
        version_id: props.page.current_version_id // Ensure the correct base version ID is sent
    })).put(route('wiki.update', props.page.slug), {
        preserveScroll: true,
        onError: (pageErrors) => {
            console.error("Page save failed:", pageErrors);
            if (pageErrors.general) {
                // Keep general errors from backend
            } else {
                form.setError('general', '保存页面时出错，请检查下方具体错误信息。');
            }
            // Keep form.errors reactive updates from Inertia
        },
        onSuccess: () => {
            localLastSaved.value = null; // Clear local draft time on successful save
            autosaveStatus.value = { type: 'success', message: '页面已成功保存！' };
            setTimeout(() => { // 一段时间后清除成功提示
                if (autosaveStatus.value?.type === 'success') {
                    autosaveStatus.value = null;
                }
            }, 3000);
        },
    });
};


// --- Lifecycle Hooks ---
onMounted(() => {
    // Start lock refresh only if locked by the current user
    if (props.lockInfo.isLocked && props.lockInfo.lockedBy?.id === pageProps.auth.user?.id) {
        lockRefreshTimer = setInterval(refreshLock, 1 * 60 * 1000); // Refresh lock every minute
    }
    // Add listener for page unload
    window.addEventListener('beforeunload', unlockPageIfNeeded);
    console.log('Edit page mounted. Editable:', isEditable.value);

    // 监听来自 Inertia 的错误 (保持不变)
    watch(() => props.errors, (newErrors) => {
        if (newErrors) {
            form.errors = newErrors;
        }
    }, { deep: true });
});

onBeforeUnmount(() => {
    // 组件卸载时通知后端用户离开
    const user = pageProps.auth.user;
    if (user) {
        // 使用 sendBeacon 尝试发送，如果不支持或失败也无妨
        try {
            if (navigator.sendBeacon) {
                const url = route('wiki.editors.unregister', { page: props.page.id });
                const formData = new FormData();
                // 不需要发送额外数据，后端通过 session 获取用户
                navigator.sendBeacon(url, formData);
                console.log('Sent unregister request via sendBeacon');
            } else {
                // 不使用 axios fallback，避免阻塞页面关闭
                console.log('sendBeacon not supported, skipping unregister on unload.');
            }
        } catch (e) {
            console.warn('Error sending unregister beacon:', e);
        }
    }
});


</script>

<style>
.disabled\:bg-gray-100:disabled {
    background-color: #f3f4f6;
}

.disabled\:cursor-not-allowed:disabled {
    cursor: not-allowed;
}

.min-h-\[20px\] {
    min-height: 20px;
}
/* Add any specific styles for the edit page if needed */
</style>
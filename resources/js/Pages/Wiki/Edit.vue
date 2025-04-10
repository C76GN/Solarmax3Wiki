<template>
    <MainLayout :navigationLinks="navigationLinks">
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <h1 class="text-2xl font-bold mb-6">编辑 Wiki 页面: {{ page.title }}</h1>

                <!-- 冲突警告 -->
                <div v-if="isConflict && !canResolveConflict" class="alert-error mb-6">
                    <div class="flex items-center">
                        <font-awesome-icon :icon="['fas', 'exclamation-circle']" class="mr-2" />
                        <p>该页面当前存在编辑冲突，您无法编辑。请等待有权限的人员解决冲突。</p>
                        <Link :href="route('wiki.show', page.slug)"
                            class="ml-auto px-3 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600 whitespace-nowrap">
                        查看页面
                        </Link>
                    </div>
                </div>
                <div v-else-if="isConflict && canResolveConflict" class="alert-warning mb-6">
                    <div class="flex items-center">
                        <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="mr-2" />
                        <p>该页面处于冲突状态。您可以编辑下方内容以解决冲突，或
                            <Link :href="route('wiki.show-conflicts', page.slug)"
                                class="font-bold underline hover:text-yellow-800">前往冲突解决页面</Link> 进行对比和合并。
                        </p>
                    </div>
                </div>
                <!-- 页面已更新提示 -->
                <div v-if="isOutdated" class="alert-info mb-6">
                    <div class="flex items-center">
                        <font-awesome-icon :icon="['fas', 'info-circle']" class="mr-2" />
                        <p>在您编辑期间，此页面已被其他用户更新。为避免覆盖他人工作，请先 <button @click="refreshPage"
                                class="underline font-medium">刷新页面</button> 查看最新内容，然后再进行提交。</p>
                    </div>
                </div>

                <EditorsList :pageId="page.id" class="mb-4" />

                <div v-if="hasDraft && localLastSaved" class="alert-info mb-6">
                    <div class="flex items-center">
                        <font-awesome-icon :icon="['fas', 'info-circle']" class="mr-2" />
                        <p>
                            检测到本地草稿，已自动加载。最后自动保存于 {{ formatDateTime(localLastSaved) }}。
                        </p>
                    </div>
                </div>

                <form @submit.prevent="savePage">
                    <div class="space-y-6">
                        <!-- 标题 -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                                标题 <span class="text-red-500">*</span>
                            </label>
                            <input id="title" v-model="form.title" type="text" class="input-field"
                                :disabled="!isEditable || isOutdated" required />
                            <InputError class="mt-1" :message="form.errors.title" />
                        </div>
                        <!-- 内容编辑器 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                内容 <span class="text-red-500">*</span>
                            </label>
                            <!-- 编辑器状态现在由 isEditable 和 isOutdated 控制 -->
                            <Editor v-model="form.content" :editable="isEditable && !isOutdated"
                                :autosave="isEditable && !isOutdated" :pageId="page.id" @saved="onDraftSaved"
                                @statusUpdate="handleEditorStatusUpdate" placeholder="开始编辑页面内容..."
                                ref="tiptapEditorRef" />
                            <InputError class="mt-1" :message="form.errors.content" />
                            <div v-if="isEditable"
                                class="mt-1 text-xs text-gray-500 flex justify-end items-center min-h-[20px]">
                                <span class="mr-auto text-gray-400">基于版本: v{{ initialVersionNumber }}</span>
                                <span v-if="autosaveStatus" :class="autosaveStatusClass" class="ml-2 flex items-center">
                                    <font-awesome-icon :icon="autosaveStatusIcon"
                                        :spin="autosaveStatus.type === 'pending'" class="mr-1" />
                                    {{ autosaveStatus.message }}
                                </span>
                            </div>
                        </div>
                        <!-- 分类和标签 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                分类 <span class="text-red-500">*</span> (至少选择一个)
                            </label>
                            <div class="checkbox-group" :class="{ 'disabled-group': !isEditable || isOutdated }">
                                <div v-for="category in categories" :key="category.id" class="flex items-center">
                                    <input type="checkbox" :id="`category-${category.id}`" :value="category.id"
                                        v-model="form.category_ids" :disabled="!isEditable || isOutdated"
                                        class="checkbox" />
                                    <label :for="`category-${category.id}`" class="ml-2 text-sm text-gray-700">
                                        {{ category.name }}
                                    </label>
                                </div>
                            </div>
                            <InputError class="mt-1" :message="form.errors.category_ids" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                标签 (可选)
                            </label>
                            <div class="checkbox-group" :class="{ 'disabled-group': !isEditable || isOutdated }">
                                <div v-for="tag in tags" :key="tag.id" class="flex items-center">
                                    <input type="checkbox" :id="`tag-${tag.id}`" :value="tag.id" v-model="form.tag_ids"
                                        :disabled="!isEditable || isOutdated" class="checkbox" />
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
                            <textarea id="comment" v-model="form.comment" rows="2" :disabled="!isEditable || isOutdated"
                                class="textarea-field" placeholder="例如：修正了XX数据，添加了YY说明..."></textarea>
                            <InputError class="mt-1" :message="form.errors.comment" />
                        </div>
                        <!-- 操作按钮 -->
                        <div class="flex justify-end space-x-3 pt-4 border-t">
                            <Link :href="route('wiki.show', page.slug)" class="btn-secondary">
                            取消
                            </Link>
                            <button type="submit" class="btn-primary"
                                :disabled="!isEditable || form.processing || isOutdated"
                                :title="isOutdated ? '页面已被更新，请刷新后提交' : ''">
                                <font-awesome-icon v-if="form.processing" :icon="['fas', 'spinner']" spin
                                    class="mr-1" />
                                {{ form.processing ? '正在保存...' : '保存页面' }}
                            </button>
                        </div>
                        <!-- 全局错误提示 -->
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
    content: { type: String, required: true }, // 初始加载的内容 (可能是草稿或最新版本)
    categories: { type: Array, required: true },
    tags: { type: Array, required: true },
    hasDraft: { type: Boolean, default: false },
    lastSaved: { type: String, default: null }, // 草稿最后保存时间
    canResolveConflict: { type: Boolean, default: false },
    isConflict: { type: Boolean, default: false }, // 页面是否处于冲突状态
    errors: Object
});

const tiptapEditorRef = ref(null);
// 初始化 version_id 时使用 props.page.current_version?.id，如果 version 不存在则使用 page.current_version_id
const initialVersionId = ref(props.page.current_version_id || props.page.current_version?.id);
const initialVersionNumber = ref(props.page.current_version?.version_number || '未知');

const form = useForm({
    title: props.page.title,
    content: props.content, // 使用传入的 content 初始化
    category_ids: props.page.category_ids || [],
    tag_ids: props.page.tag_ids || [],
    comment: '',
    version_id: initialVersionId.value // 基于加载时的版本ID
});

const isOutdated = ref(false); // 新增：页面是否过时状态
let echoChannel = null; // 用于 WebSocket

const autosaveStatus = ref(null);
const localLastSaved = ref(props.lastSaved ? new Date(props.lastSaved) : null);


// --- Computed Properties ---
const isEditable = computed(() => {
    return !props.isConflict || props.canResolveConflict;
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
    if (!autosaveStatus.value) return ['fas', 'circle-info'];
    switch (autosaveStatus.value.type) {
        case 'success': return ['fas', 'check-circle'];
        case 'error': return ['fas', 'exclamation-circle'];
        case 'pending': return ['fas', 'spinner'];
        default: return ['fas', 'info-circle'];
    }
});

// --- Methods ---
const onDraftSaved = (status) => {
    if (status && status.saved_at) {
        localLastSaved.value = new Date(status.saved_at); // 更新本地草稿保存时间显示
    }
    autosaveStatus.value = status;
    if (status.type === 'success') {
        form.clearErrors('general');
    }
};

const handleEditorStatusUpdate = (status) => {
    autosaveStatus.value = status;
};

const savePage = () => {
    if (isOutdated.value) {
        alert("页面已被更新，请先刷新页面！");
        return;
    }
    if (tiptapEditorRef.value && tiptapEditorRef.value.editor) {
        form.content = tiptapEditorRef.value.editor.getHTML();
    }
    // 确保提交时 version_id 是加载页面时的版本 ID
    form.transform(data => ({
        ...data,
        version_id: initialVersionId.value // 强制使用初始版本ID进行冲突检测
    })).put(route('wiki.update', props.page.slug), {
        preserveScroll: true,
        onError: (pageErrors) => {
            console.error("Page save failed:", pageErrors);
            if (pageErrors.general && pageErrors.general.includes('版本ID无效')) {
                isOutdated.value = true; // 特殊处理版本错误
                form.setError('general', '页面已被更新，请刷新。');
            } else if (pageErrors.general) {
                // 其他一般性错误
            } else {
                form.setError('general', '保存页面时出错，请检查下方具体错误信息。');
            }
        },
        onSuccess: () => {
            localLastSaved.value = null; // 清除本地草稿时间显示
            autosaveStatus.value = { type: 'success', message: '页面已成功保存！' };
            initialVersionId.value = pageProps.page?.current_version_id; // 更新基准版本 ID 为最新的
            initialVersionNumber.value = pageProps.page?.current_version?.version_number || '最新';
            isOutdated.value = false; // 保存成功后重置过时状态
            setTimeout(() => {
                if (autosaveStatus.value?.type === 'success') {
                    autosaveStatus.value = null;
                }
            }, 3000);
        },
    });
};

const refreshPage = () => {
    router.reload({ only: ['page', 'content', 'errors'] }); // 只重新加载关键数据
}

// --- WebSocket Listener Setup ---
const setupVersionUpdateListener = () => {
    const channelName = `wiki.page.${props.page.id}`;
    if (!window.Echo) {
        console.warn("Echo is not initialized! Cannot listen for version updates.");
        // 可以在这里考虑启动轮询作为备用方案
        // startPollingVersion();
        return;
    }
    try {
        echoChannel = window.Echo.channel(channelName);
        console.log(`Listening on channel: ${channelName} for version updates`);

        echoChannel.listen('.page.version.updated', (data) => {
            console.log('Received page.version.updated event:', data);
            // 检查收到的新版本ID是否与当前编辑器基于的版本ID不同
            if (data.newVersionId && data.newVersionId !== initialVersionId.value) {
                console.log(`Page outdated! Current base: ${initialVersionId.value}, Newest: ${data.newVersionId}`);
                isOutdated.value = true;
                // 可以选择在这里显示一个更即时的提示，而不是等用户提交时才发现
                if (flashMessage.value) {
                    flashMessage.value.addMessage('warning', '注意：此页面已被更新，请刷新后再提交。');
                } else {
                    // Fallback alert if FlashMessage ref isn't ready or available
                    // alert('注意：此页面已被其他用户更新，请刷新页面后再提交，以避免覆盖更改。');
                    console.warn('FlashMessage component not available for outdated warning.');
                }
            }
        });

    } catch (error) {
        console.error(`Error setting up Echo listener for version updates on channel ${channelName}:`, error);
    }
};

// --- Lifecycle Hooks ---
onMounted(() => {
    // 初始化时检查是否有错误传递过来，特别是版本错误
    if (props.errors && props.errors.general && props.errors.general.includes('版本ID无效')) {
        isOutdated.value = true;
    }

    setupVersionUpdateListener(); // 启动 WebSocket 监听

    // 全局事件监听器（作为备用，如果 EditList 中的事件触发不稳定）
    window.addEventListener('page-version-updated', handleGlobalVersionUpdate);

    watch(() => props.errors, (newErrors) => {
        if (newErrors) {
            form.errors = newErrors;
            if (newErrors.general && newErrors.general.includes('版本ID无效')) {
                isOutdated.value = true;
            }
        }
    }, { deep: true });
});

onBeforeUnmount(() => {
    // 清理 WebSocket 监听
    if (echoChannel) {
        try {
            echoChannel.stopListening('.page.version.updated');
            // 不在这里离开频道，因为 EditorList 可能还需要它
            // window.Echo.leave(`wiki.page.${props.pageId}`);
            console.log(`Stopped listening on channel: wiki.page.${props.pageId} for version updates`);
        } catch (e) {
            console.error("Error stopping Echo listener:", e);
        }
        echoChannel = null; // 清理引用
    }
    // 移除全局事件监听器
    window.removeEventListener('page-version-updated', handleGlobalVersionUpdate);
});

// 全局事件处理函数
const handleGlobalVersionUpdate = (event) => {
    if (event.detail.pageId === props.page.id && event.detail.newVersionId !== initialVersionId.value) {
        console.log(`Page outdated detected via global event! Current base: ${initialVersionId.value}, Newest: ${event.detail.newVersionId}`);
        isOutdated.value = true;
        // 可以在这里也显示提示
        if (flashMessage.value) {
            flashMessage.value.addMessage('warning', '注意：此页面已被更新，请刷新后再提交。');
        } else {
            console.warn('FlashMessage component not available for outdated warning (global handler).');
        }
    }
};

</script>

<style scoped>
/* 添加一些基本样式 */
.input-field {
    @apply w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed;
}

.textarea-field {
    @apply w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed;
}

.checkbox-group {
    @apply grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-4 gap-y-2 max-h-60 overflow-y-auto p-2 border rounded;
}

.checkbox-group.disabled-group {
    @apply bg-gray-100 opacity-70 cursor-not-allowed;
}

.checkbox {
    @apply h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 disabled:cursor-not-allowed;
}

.btn-primary {
    @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
    @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition;
}

.alert-error {
    @apply bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md;
}

.alert-warning {
    @apply bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-md;
}

.alert-info {
    @apply bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 rounded-md;
}

.min-h-\[20px\] {
    min-height: 20px;
    /* Ensure the status bar has height even when empty */
}
</style>
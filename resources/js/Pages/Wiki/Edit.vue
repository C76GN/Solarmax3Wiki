<template>
    <MainLayout :navigationLinks="navigationLinks">
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <h1 class="text-2xl font-bold mb-6">编辑 Wiki 页面: {{ page.title }}</h1>

                <!-- Conflict/Outdated Alerts -->
                <div v-if="isConflict && !canResolveConflict" class="alert-error mb-6">
                    <div class="flex items-center">
                        <font-awesome-icon :icon="['fas', 'exclamation-circle']" class="mr-2" />
                        <p>该页面当前存在编辑冲突，您无法编辑。请等待有权限的人员解决冲突。</p>
                        <Link :href="route('wiki.show', page.slug)"
                            class="ml-auto px-3 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600 whitespace-nowrap">
                        查看页面</Link>
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
                <div v-if="isOutdated" class="alert-info mb-6">
                    <div class="flex items-center">
                        <font-awesome-icon :icon="['fas', 'info-circle']" class="mr-2" />
                        <p>在您编辑期间，此页面已被其他用户更新。为避免覆盖他人工作，请先 <button @click="refreshPage"
                                class="underline font-medium">刷新页面</button> 查看最新内容，然后再进行提交。</p>
                    </div>
                </div>

                <EditorsList :pageId="page.id" class="mb-4" />

                <!-- Draft Alert -->
                <div v-if="hasDraft && localLastSaved" class="alert-info mb-6">
                    <div class="flex items-center">
                        <font-awesome-icon :icon="['fas', 'save']" class="mr-2 flex-shrink-0" />
                        <p class="text-sm">
                            检测到本地草稿，已自动加载。最后自动保存于 {{ formatDateTime(localLastSaved) }}。
                        </p>
                    </div>
                </div>

                <form @submit.prevent="savePage">
                    <div class="space-y-6">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                                标题 <span class="text-red-500">*</span>
                            </label>
                            <!-- 绑定 :disabled -->
                            <input id="title" v-model="form.title" type="text" class="input-field"
                                :disabled="!editorIsEditable || isOutdated" required />
                            <InputError class="mt-1" :message="form.errors.title" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                内容 <span class="text-red-500">*</span>
                            </label>
                            <!-- 传入 editorIsEditable 作为 editable prop -->
                            <!-- 监听 @toggle-edit 事件 -->
                            <Editor v-model="form.content" :editable="editorIsEditable && !isOutdated"
                                :autosave="editorIsEditable && !isOutdated" :pageId="page.id" @saved="onDraftSaved"
                                @statusUpdate="handleEditorStatusUpdate" @toggle-edit="toggleEditorEditable"
                                placeholder="开始编辑页面内容..." ref="tiptapEditorRef" />
                            <InputError class="mt-1" :message="form.errors.content" />
                            <!-- Autosave Status Display -->
                            <div v-if="editorIsEditable"
                                class="mt-1 text-xs text-gray-500 flex justify-end items-center min-h-[20px]">
                                <span class="mr-auto text-gray-400">基于版本: v{{ initialVersionNumber }}</span>
                                <span v-if="autosaveStatus" :class="autosaveStatusClass" class="ml-2 flex items-center">
                                    <font-awesome-icon :icon="autosaveStatusIcon"
                                        :spin="autosaveStatus.type === 'pending'" class="mr-1" />
                                    {{ autosaveStatus.message }}
                                </span>
                            </div>
                        </div>

                        <!-- Categories -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                分类 <span class="text-red-500">*</span> (至少选择一个)
                            </label>
                            <!-- 绑定 :disabled -->
                            <div class="checkbox-group" :class="{ 'disabled-group': !editorIsEditable || isOutdated }">
                                <div v-for="category in categories" :key="category.id" class="flex items-center">
                                    <input type="checkbox" :id="`category-${category.id}`" :value="category.id"
                                        v-model="form.category_ids" :disabled="!editorIsEditable || isOutdated"
                                        class="checkbox" />
                                    <label :for="`category-${category.id}`" class="ml-2 text-sm text-gray-700">{{
                                        category.name }}</label>
                                </div>
                            </div>
                            <InputError class="mt-1" :message="form.errors.category_ids" />
                        </div>

                        <!-- Tags -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                标签 (可选)
                            </label>
                            <!-- 绑定 :disabled -->
                            <div class="checkbox-group" :class="{ 'disabled-group': !editorIsEditable || isOutdated }">
                                <div v-for="tag in tags" :key="tag.id" class="flex items-center">
                                    <input type="checkbox" :id="`tag-${tag.id}`" :value="tag.id" v-model="form.tag_ids"
                                        :disabled="!editorIsEditable || isOutdated" class="checkbox" />
                                    <label :for="`tag-${tag.id}`" class="ml-2 text-sm text-gray-700">{{ tag.name
                                        }}</label>
                                </div>
                            </div>
                            <InputError class="mt-1" :message="form.errors.tag_ids" />
                        </div>

                        <!-- Comment -->
                        <div>
                            <label for="comment" class="block text-sm font-medium text-gray-700 mb-1">
                                提交说明 <span class="text-xs text-gray-500">(本次修改的简要描述)</span>
                            </label>
                            <!-- 绑定 :disabled -->
                            <textarea id="comment" v-model="form.comment" rows="2"
                                :disabled="!editorIsEditable || isOutdated" class="textarea-field"
                                placeholder="例如：修正了XX数据，添加了YY说明..."></textarea>
                            <InputError class="mt-1" :message="form.errors.comment" />
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end space-x-3 pt-4 border-t">
                            <Link :href="route('wiki.show', page.slug)" class="btn-secondary">取消</Link>
                            <button type="submit" class="btn-primary"
                                :disabled="!editorIsEditable || form.processing || isOutdated"
                                :title="isOutdated ? '页面已被更新，请刷新后提交' : (!editorIsEditable ? '当前为预览模式' : '')">
                                <font-awesome-icon v-if="form.processing" :icon="['fas', 'spinner']" spin
                                    class="mr-1" />
                                {{ form.processing ? '正在保存...' : '保存页面' }}
                            </button>
                        </div>

                        <!-- General Error -->
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
import EditorsList from '@/Components/Wiki/EditorsList.vue'; // 假设您有这个组件
import { mainNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = mainNavigationLinks;
const pageProps = usePage().props;

const props = defineProps({
    page: { type: Object, required: true },
    content: { type: String, required: true },
    categories: { type: Array, required: true },
    tags: { type: Array, required: true },
    hasDraft: { type: Boolean, default: false },
    lastSaved: { type: String, default: null },
    canResolveConflict: { type: Boolean, default: false },
    isConflict: { type: Boolean, default: false },
    errors: Object // 从 Inertia 接收 errors prop
});

const tiptapEditorRef = ref(null);

// --- 状态管理 ---
const editorIsEditable = ref(!props.isLocked && (!props.isConflict || props.canResolveConflict)); // 初始编辑状态
const initialVersionId = ref(props.page.current_version_id || props.page.current_version?.id);
const initialVersionNumber = ref(props.page.current_version?.version_number || '未知');
const isOutdated = ref(false); // 页面是否过时状态
const autosaveStatus = ref(null);
const localLastSaved = ref(props.lastSaved ? new Date(props.lastSaved) : null);
let echoChannel = null; // 用于 WebSocket

const form = useForm({
    title: props.page.title,
    content: props.content, // 初始内容
    category_ids: props.page.category_ids || [],
    tag_ids: props.page.tag_ids || [],
    comment: '',
    version_id: initialVersionId.value // 基于加载时的版本ID
});

// --- 计算属性 ---
// 使用 editorIsEditable 作为 isEditable 的计算源
const isEditable = computed(() => editorIsEditable.value);

const autosaveStatusClass = computed(() => { /* ... 保持不变 ... */
    if (!autosaveStatus.value) return 'text-gray-400 italic';
    switch (autosaveStatus.value.type) {
        case 'success': return 'text-green-600';
        case 'error': return 'text-red-600';
        case 'pending': return 'text-blue-600';
        default: return 'text-gray-500';
    }
});
const autosaveStatusIcon = computed(() => { /* ... 保持不变 ... */
    if (!autosaveStatus.value) return ['fas', 'circle-info'];
    switch (autosaveStatus.value.type) {
        case 'success': return ['fas', 'check-circle'];
        case 'error': return ['fas', 'exclamation-circle'];
        case 'pending': return ['fas', 'spinner'];
        default: return ['fas', 'info-circle'];
    }
});

// --- 方法 ---
// 切换编辑状态
const toggleEditorEditable = () => {
    editorIsEditable.value = !editorIsEditable.value;
    console.log("Editor editable state toggled to:", editorIsEditable.value);
    // 可以在这里添加其他切换状态时需要执行的逻辑
};

// 监听草稿保存事件
const onDraftSaved = (status) => {
    if (status && status.saved_at) {
        localLastSaved.value = new Date(status.saved_at);
    }
    autosaveStatus.value = status;
    if (status.type === 'success') {
        form.clearErrors('general'); // 清除通用错误
    }
};

// 处理编辑器状态更新
const handleEditorStatusUpdate = (status) => {
    autosaveStatus.value = status;
};

// 保存页面
const savePage = () => {
    if (isOutdated.value) {
        alert("页面已被更新，请先刷新页面！");
        return;
    }
    // 从 Editor 组件获取最新内容
    if (tiptapEditorRef.value && tiptapEditorRef.value.editor) {
        form.content = tiptapEditorRef.value.editor.getHTML();
    } else {
        console.error("无法获取编辑器实例来保存内容。");
        form.setError('general', '无法获取编辑器内容，请重试。');
        return;
    }

    // 提交表单
    form.transform(data => ({
        ...data,
        version_id: initialVersionId.value // 使用加载时的版本 ID
    })).put(route('wiki.update', props.page.slug), {
        preserveScroll: true,
        onError: (pageErrors) => {
            console.error("页面保存失败:", pageErrors);
            if (pageErrors.general && pageErrors.general.includes('版本ID无效')) {
                isOutdated.value = true;
                form.setError('general', '页面已被更新，请刷新。');
            } else if (pageErrors.general && pageErrors.general.includes('编辑冲突')) {
                form.setError('general', pageErrors.general);
                // 考虑导航到冲突页面或显示特定UI
                router.visit(route('wiki.show-conflicts', props.page.slug));
            } else {
                form.setError('general', '保存页面时出错，请检查下方字段错误或稍后重试。');
            }
        },
        onSuccess: (pageResponse) => { // 接收 Inertia 返回的页面数据
            // 获取响应中的最新页面数据（如果后端返回了）
            const updatedPageProps = pageResponse.props?.page;
            const updatedCurrentVersion = pageResponse.props?.page?.current_version;

            localLastSaved.value = null; // 成功保存后清除本地草稿时间戳
            autosaveStatus.value = { type: 'success', message: '页面已成功保存！' };

            // 基于 *响应* 更新初始版本信息，以防并发更新
            initialVersionId.value = updatedPageProps?.current_version_id || initialVersionId.value;
            initialVersionNumber.value = updatedCurrentVersion?.version_number || initialVersionNumber.value;

            isOutdated.value = false; // 重置过时状态
            form.comment = ''; // 清空提交说明
            form.clearErrors(); // 清除所有错误

            console.log(`Page saved successfully. New base version ID: ${initialVersionId.value}, Number: ${initialVersionNumber.value}`);

            // 保持在编辑模式（或根据需求切换）
            editorIsEditable.value = false;

            // 3秒后清除成功消息
            setTimeout(() => {
                if (autosaveStatus.value?.type === 'success') {
                    autosaveStatus.value = null;
                }
            }, 3000);
        },
    });
};

// 刷新页面
const refreshPage = () => {
    console.log("Reloading page data...");
    // 仅重新加载可能变化的数据，保持滚动位置
    router.reload({ only: ['page', 'content', 'currentVersion', 'isLocked', 'lockedBy', 'draft', 'lastSaved', 'isConflict', 'errors'] });
};

// WebSocket 监听器设置 (保持不变)
const setupVersionUpdateListener = () => { /* ... */
    const channelName = `wiki.page.${props.page.id}`;
    if (!window.Echo) {
        console.warn("Echo is not initialized! Cannot listen for version updates.");
        return;
    }
    try {
        echoChannel = window.Echo.channel(channelName);
        console.log(`Listening on channel: ${channelName} for version updates`);
        echoChannel.listen('.page.version.updated', (data) => {
            console.log('Received page.version.updated event:', data);
            if (data.newVersionId && data.newVersionId !== initialVersionId.value) {
                console.log(`Page outdated! Current base: ${initialVersionId.value}, Newest: ${data.newVersionId}`);
                isOutdated.value = true;
            }
        });
        echoChannel.error((error) => {
            console.error(`Echo channel error on ${channelName}:`, error);
        });
    } catch (error) {
        console.error(`Error setting up Echo listener for version updates on channel ${channelName}:`, error);
    }
};
const handleGlobalVersionUpdate = (event) => { /* ... */
    if (event.detail.pageId === props.page.id && event.detail.newVersionId !== initialVersionId.value) {
        console.log(`Page outdated detected via global event! Current base: ${initialVersionId.value}, Newest: ${event.detail.newVersionId}`);
        isOutdated.value = true;
    }
};

// 生命周期钩子
onMounted(() => {
    // 检查初始 props.errors
    if (props.errors?.general?.includes('版本ID无效')) {
        isOutdated.value = true;
    }
    setupVersionUpdateListener();
    window.addEventListener('page-version-updated', handleGlobalVersionUpdate);

    // 监听 Inertia props 中的 errors
    watch(() => pageProps.errors, (newErrors) => {
        if (newErrors) {
            form.errors = newErrors; // 将 Inertia 错误同步到 useForm
            if (newErrors.general?.includes('版本ID无效')) {
                isOutdated.value = true;
            }
        }
    }, { deep: true, immediate: true }); // immediate 确保挂载时检查

});

onBeforeUnmount(() => { /* ... 保持不变 ... */
    if (echoChannel) {
        try {
            echoChannel.stopListening('.page.version.updated');
            console.log(`Stopped listening on channel: wiki.page.${props.page.id} for version updates`);
        } catch (e) {
            console.error("Error stopping Echo listener:", e);
        }
        echoChannel = null;
    }
    window.removeEventListener('page-version-updated', handleGlobalVersionUpdate);
});
</script>

<style scoped>
/* 保持之前的样式不变 */
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
}
</style>
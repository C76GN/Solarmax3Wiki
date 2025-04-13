<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head :title="`编辑: ${page.title}`" />
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <h1 class="text-2xl font-bold mb-6 text-gray-900 dark:text-gray-100">编辑 Wiki 页面: {{ page.title }}</h1>

                <!-- 冲突/过时/草稿提示 -->
                <div v-if="isConflict && !canResolveConflict" class="alert alert-error mb-6">
                    <div class="flex items-center">
                        <font-awesome-icon :icon="['fas', 'exclamation-circle']" class="mr-2 flex-shrink-0" />
                        <p>该页面当前存在编辑冲突，您无法编辑。请等待有权限的人员解决冲突。</p>
                        <Link :href="route('wiki.show', page.slug)"
                            class="ml-auto px-3 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600 whitespace-nowrap">
                        查看页面</Link>
                    </div>
                </div>
                <div v-else-if="isConflict && canResolveConflict" class="alert alert-warning mb-6">
                    <div class="flex items-center">
                        <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="mr-2 flex-shrink-0" />
                        <p>该页面处于冲突状态。您可以编辑下方内容以解决冲突，或
                            <Link :href="route('wiki.show-conflicts', page.slug)"
                                class="font-bold underline hover:text-yellow-800 dark:hover:text-yellow-200">前往冲突解决页面
                            </Link> 进行对比和合并。
                        </p>
                    </div>
                </div>
                <div v-if="isOutdated" class="alert alert-info mb-6">
                    <div class="flex items-center">
                        <font-awesome-icon :icon="['fas', 'info-circle']" class="mr-2 flex-shrink-0" />
                        <p>在您编辑期间，此页面已被其他用户更新。为避免覆盖他人工作，请先 <button @click="refreshPage"
                                class="underline font-medium">刷新页面</button> 查看最新内容，然后再进行提交。</p>
                    </div>
                </div>
                <div v-if="hasDraft && localLastSaved" class="alert alert-info mb-6">
                    <div class="flex items-center">
                        <font-awesome-icon :icon="['fas', 'save']" class="mr-2 flex-shrink-0" />
                        <p class="text-sm">检测到本地草稿，已自动加载。最后自动保存于 {{ formatDateTime(localLastSaved) }}。</p>
                    </div>
                </div>

                <!-- 编辑者列表 -->
                <EditorsList :pageId="page.id" class="mb-4" />

                <form @submit.prevent="savePage">
                    <div class="space-y-6">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                标题 <span class="text-red-500">*</span>
                            </label>
                            <input id="title" v-model="form.title" type="text" class="input-field"
                                :disabled="!editorIsEditable || isOutdated" required />
                            <InputError class="mt-1" :message="form.errors.title" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                内容 <span class="text-red-500">*</span>
                            </label>
                            <Editor v-model="form.content" :editable="editorIsEditable && !isOutdated"
                                :autosave="editorIsEditable && !isOutdated" :pageId="page.id" @saved="onDraftSaved"
                                @statusUpdate="handleEditorStatusUpdate" @toggle-edit="toggleEditorEditable"
                                placeholder="开始编辑页面内容..." ref="tiptapEditorRef" />
                            <InputError class="mt-1" :message="form.errors.content" />
                            <div v-if="editorIsEditable" class="editor-status-bar">
                                <span class="mr-auto text-gray-500 dark:text-gray-400 text-xs">基于版本: v{{
                                    initialVersionNumber }}</span>
                                <span v-if="autosaveStatus" :class="autosaveStatusClass"
                                    class="flex items-center text-xs">
                                    <font-awesome-icon :icon="autosaveStatusIcon"
                                        :spin="autosaveStatus.type === 'pending'" class="mr-1 h-3 w-3" />
                                    {{ autosaveStatus.message }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                分类 <span class="text-red-500">*</span> (至少选择一个)
                            </label>
                            <div class="checkbox-group" :class="{ 'disabled-group': !editorIsEditable || isOutdated }">
                                <div v-for="category in categories" :key="category.id" class="flex items-center">
                                    <input type="checkbox" :id="`category-${category.id}`" :value="category.id"
                                        v-model="form.category_ids" :disabled="!editorIsEditable || isOutdated"
                                        class="checkbox" />
                                    <label :for="`category-${category.id}`"
                                        class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ category.name
                                        }}</label>
                                </div>
                            </div>
                            <InputError class="mt-1" :message="form.errors.category_ids" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                标签 (可选)
                            </label>
                            <div class="checkbox-group" :class="{ 'disabled-group': !editorIsEditable || isOutdated }">
                                <div v-for="tag in tags" :key="tag.id" class="flex items-center">
                                    <input type="checkbox" :id="`tag-${tag.id}`" :value="tag.id" v-model="form.tag_ids"
                                        :disabled="!editorIsEditable || isOutdated" class="checkbox" />
                                    <label :for="`tag-${tag.id}`"
                                        class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ tag.name }}</label>
                                </div>
                            </div>
                            <InputError class="mt-1" :message="form.errors.tag_ids" />
                        </div>

                        <div>
                            <label for="comment"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                提交说明 <span class="text-xs text-gray-500 dark:text-gray-400">(本次修改的简要描述)</span>
                            </label>
                            <textarea id="comment" v-model="form.comment" rows="2"
                                :disabled="!editorIsEditable || isOutdated" class="textarea-field"
                                placeholder="例如：修正了XX数据，添加了YY说明..."></textarea>
                            <InputError class="mt-1" :message="form.errors.comment" />
                        </div>

                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <Link :href="route('wiki.show', page.slug)" class="btn-secondary">取消</Link>
                            <button type="submit" class="btn-primary"
                                :disabled="!editorIsEditable || form.processing || isOutdated"
                                :title="isOutdated ? '页面已被更新，请刷新后提交' : (!editorIsEditable ? '当前为预览模式' : '')">
                                <font-awesome-icon v-if="form.processing" :icon="['fas', 'spinner']" spin
                                    class="mr-1" />
                                {{ form.processing ? '正在保存...' : '保存页面' }}
                            </button>
                        </div>
                        <div v-if="form.errors.general"
                            class="mt-1 text-sm text-red-600 dark:text-red-400 text-right font-medium">
                            <font-awesome-icon :icon="['fas', 'exclamation-circle']" class="mr-1" /> {{
                            form.errors.general }}
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Editor from '@/Components/Wiki/Editor.vue';
import InputError from '@/Components/Other/InputError.vue';
import EditorsList from '@/Components/Wiki/EditorsList.vue';
import { formatDateTime } from '@/utils/formatters';
import { mainNavigationLinks } from '@/config/navigationConfig';
import axios from 'axios'; // 确保引入 axios

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
    errors: Object
});

const tiptapEditorRef = ref(null);
const editorIsEditable = ref(!props.page.is_locked && (!props.isConflict || props.canResolveConflict));
const initialVersionId = ref(props.page.current_version_id || props.page.current_version?.id);
const initialVersionNumber = ref(props.page.current_version?.version_number || '未知');
const isOutdated = ref(false); // 页面是否过时
const autosaveStatus = ref(null);
const localLastSaved = ref(props.lastSaved ? new Date(props.lastSaved) : null);
let echoChannel = null;

const form = useForm({
    title: props.page.title,
    content: props.content,
    category_ids: props.page.category_ids || [],
    tag_ids: props.page.tag_ids || [],
    comment: '',
    version_id: initialVersionId.value // 必须基于这个版本ID提交
});

// 计算属性保持不变
const autosaveStatusClass = computed(() => { /* ... */
    if (!autosaveStatus.value) return 'text-gray-400 dark:text-gray-500 italic';
    switch (autosaveStatus.value.type) {
        case 'success': return 'text-green-600 dark:text-green-400';
        case 'error': return 'text-red-600 dark:text-red-400';
        case 'pending': return 'text-blue-600 dark:text-blue-400';
        default: return 'text-gray-500 dark:text-gray-400';
    }
});
const autosaveStatusIcon = computed(() => { /* ... */
    if (!autosaveStatus.value) return ['fas', 'info-circle']; // Fallback icon
    switch (autosaveStatus.value.type) {
        case 'success': return ['fas', 'check-circle'];
        case 'error': return ['fas', 'exclamation-circle'];
        case 'pending': return ['fas', 'spinner'];
        default: return ['fas', 'info-circle'];
    }
});

// --- Methods ---
const toggleEditorEditable = () => {
    editorIsEditable.value = !editorIsEditable.value;
};

const onDraftSaved = (status) => {
    if (status && status.saved_at) {
        localLastSaved.value = new Date(status.saved_at);
    }
    // 清除之前的general错误，避免干扰新状态
    if (form.errors.general) form.clearErrors('general');
    autosaveStatus.value = status;
    if (status?.type === 'error') {
        form.setError('general', status.message);
    } else {
        // 只有在非错误情况下清除 general 错误
        if (form.errors.general) form.clearErrors('general');
    }
    // 成功保存后，清除版本过时状态
    if (status?.type === 'success') {
        isOutdated.value = false;
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
    } else {
        console.error("无法获取编辑器实例来保存内容。");
        form.setError('general', '无法获取编辑器内容，请重试。');
        return;
    }

    // 确保基于正确的版本ID提交
    form.version_id = initialVersionId.value;

    form.put(route('wiki.update', props.page.slug), {
        preserveScroll: true,
        onError: (pageErrors) => {
            console.error("页面保存失败:", pageErrors);
            if (pageErrors.general && pageErrors.general.includes('版本ID无效')) {
                isOutdated.value = true;
                form.setError('general', '页面已被更新，请刷新。');
            } else if (pageErrors.general && pageErrors.general.includes('编辑冲突')) {
                form.setError('general', pageErrors.general);
                router.visit(route('wiki.show-conflicts', props.page.slug));
            } else if (pageErrors.content && pageErrors.content === "The content field is required.") {
                form.setError('content', '内容不能为空');
            }
            else {
                form.setError('general', '保存页面时出错，请检查下方字段错误或稍后重试。');
            }
        },
        onSuccess: (pageResponse) => {
            const newVersionId = pageResponse.props?.page?.current_version_id;
            const newVersionNumber = pageResponse.props?.page?.current_version?.version_number;

            if (newVersionId && newVersionNumber) {
                initialVersionId.value = newVersionId;
                initialVersionNumber.value = newVersionNumber;
                isOutdated.value = false; // 重置过时状态
                console.log(`Page saved successfully. New base version ID: ${initialVersionId.value}, Number: ${initialVersionNumber.value}`);
            } else {
                console.warn("Save success response did not contain updated version information.");
            }

            localLastSaved.value = null; // 清除本地草稿时间
            autosaveStatus.value = { type: 'success', message: '页面已成功保存！' };
            form.comment = '';
            form.clearErrors();

            // 切换回预览模式
            editorIsEditable.value = false;
            if (tiptapEditorRef.value && tiptapEditorRef.value.editor) {
                tiptapEditorRef.value.editor.setEditable(false);
            }

            // 定时清除成功消息
            setTimeout(() => {
                if (autosaveStatus.value?.type === 'success') {
                    autosaveStatus.value = null;
                }
            }, 3000);
        },
    });
};

const refreshPage = () => {
    // 增加 only 数组内容，确保关键状态被刷新
    router.reload({ only: ['page', 'content', 'currentVersion', 'isLocked', 'lockedBy', 'draft', 'lastSaved', 'isConflict', 'errors', 'canResolveConflict'] });
};

const handleGlobalVersionUpdate = (event) => {
    if (event.detail.pageId === props.page.id && event.detail.newVersionId !== initialVersionId.value) {
        console.log(`Page outdated detected via global event! Current base: ${initialVersionId.value}, Newest: ${event.detail.newVersionId}`);
        isOutdated.value = true;
        // 可能还需要更新 initialVersionId 和 number，但 reload 更可靠
    }
};

// Realtime Listener Setup
const setupVersionUpdateListener = () => {
    const channelName = `wiki.page.${props.page.id}`;
    if (!window.Echo) {
        console.warn("Echo is not initialized! Cannot listen for version updates.");
        return;
    }
    try {
        echoChannel = window.Echo.channel(channelName);
        echoChannel.listen('.page.version.updated', (data) => {
            console.log('Received page.version.updated event:', data);
            if (data.newVersionId && data.newVersionId !== initialVersionId.value) {
                console.log(`Page outdated! Current base: ${initialVersionId.value}, Newest: ${data.newVersionId}`);
                isOutdated.value = true;
            }
        });
        echoChannel.error((error) => { console.error(`Echo channel error on ${channelName}:`, error); });
        console.log(`Listening on channel: ${channelName} for version updates`);
    } catch (error) {
        console.error(`Error setting up Echo listener for version updates on channel ${channelName}:`, error);
    }
};


// Lifecycle Hooks
onMounted(() => {
    console.log("Edit.vue mounted. Initial editable:", editorIsEditable.value);
    if (pageProps.errors?.general?.includes('版本ID无效')) {
        isOutdated.value = true;
    }
    setupVersionUpdateListener();
    window.addEventListener('page-version-updated', handleGlobalVersionUpdate);

    // 确保编辑器在挂载后基于 props.editable 设置状态
    if (tiptapEditorRef.value?.editor) {
        tiptapEditorRef.value.editor.setEditable(editorIsEditable.value);
    }

    // 监听 Inertia 错误
    watch(() => pageProps.errors, (newErrors) => {
        if (newErrors) {
            form.errors = newErrors; // 更新表单错误
            if (newErrors.general?.includes('版本ID无效')) {
                isOutdated.value = true;
            }
            if (newErrors.general) {
                autosaveStatus.value = { type: 'error', message: newErrors.general };
            }
        }
    }, { deep: true });

    // 监听 page prop 的变化 (用于 router.reload)
    watch(() => props.page, (newPage) => {
        if (newPage) {
            form.title = newPage.title;
            form.category_ids = newPage.category_ids || [];
            form.tag_ids = newPage.tag_ids || [];
            initialVersionId.value = newPage.current_version_id;
            initialVersionNumber.value = newPage.current_version?.version_number || '未知';
            isOutdated.value = false; // Reload 后重置过时状态
            editorIsEditable.value = !newPage.is_locked && (!props.isConflict || props.canResolveConflict); // 更新编辑器可编辑状态
            // 可能还需要更新 editor content，如果 reload 包含 content
            if (tiptapEditorRef.value?.editor && props.content !== tiptapEditorRef.value.editor.getHTML()) {
                tiptapEditorRef.value.editor.commands.setContent(props.content, false);
            }
            form.clearErrors(); // 清除之前的错误
            autosaveStatus.value = { type: 'info', message: '页面已刷新' };
            setTimeout(() => { autosaveStatus.value = null; }, 3000);
        }
    }, { deep: true });
});

onBeforeUnmount(() => {
    console.log("Edit.vue unmounting");
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
/* 保持 Create.vue 中的统一样式类 */
.input-field,
.textarea-field,
select {
    @apply w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-500 disabled:bg-gray-100 dark:disabled:bg-gray-700 disabled:cursor-not-allowed dark:disabled:text-gray-400;
}

.checkbox-group {
    @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-60 overflow-y-auto p-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800/50;
}

.checkbox-group.disabled-group {
    @apply bg-gray-100 dark:bg-gray-700/50 opacity-70 cursor-not-allowed;
}

.checkbox {
    @apply h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-blue-600 dark:ring-offset-gray-800 disabled:cursor-not-allowed;
}

.checkbox:disabled+label {
    @apply cursor-not-allowed opacity-70;
}

.btn-primary {
    @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
    @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500;
}

/* Alert 样式 */
.alert {
    @apply p-4 rounded-md border-l-4 mb-6;
}

.alert-error {
    @apply bg-red-50 border-red-400 text-red-700 dark:bg-red-900/30 dark:text-red-300 dark:border-red-600;
}

.alert-warning {
    @apply bg-yellow-50 border-yellow-400 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300 dark:border-yellow-600;
}

.alert-info {
    @apply bg-blue-50 border-blue-400 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-600;
}

.editor-status-bar {
    @apply mt-1 text-xs flex justify-end items-center min-h-[20px];
}
</style>
<script setup>
// 保持之前的 imports 不变
import { ref, onMounted, onUnmounted, onBeforeUnmount, computed, watch } from 'vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Editor from '@/Components/Wiki/Editor.vue';
import InputError from '@/Components/Other/InputError.vue';
import EditorsList from '@/Components/Wiki/EditorsList.vue';
import WikiPreviewPane from '@/Components/Wiki/WikiPreviewPane.vue';
import { formatDateTime } from '@/utils/formatters';
import { mainNavigationLinks } from '@/config/navigationConfig';
import axios from 'axios';

const navigationLinks = mainNavigationLinks;
const pageProps = usePage().props;

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
    page: { type: Object, required: true },
    content: { type: String, required: true },
    categories: { type: Array, required: true },
    tags: { type: Array, required: true },
    hasDraft: { type: Boolean, default: false },
    lastSaved: { type: String, default: null },
    canResolveConflict: { type: Boolean, default: false },
    isConflict: { type: Boolean, default: false },
    editorIsEditable: { type: Boolean, default: true },
    errors: Object
});

const tiptapEditorRef = ref(null);
const showPreviewPane = ref(true); // 现在是响应式变量
const initialVersionId = ref(props.page.current_version_id || props.page.current_version?.id);
const initialVersionNumber = ref(props.page.current_version?.version_number || '未知');
const isOutdated = ref(false);
const autosaveStatus = ref(null);
const localLastSaved = ref(props.lastSaved ? new Date(props.lastSaved) : null);
let echoChannel = null;

const form = useForm({
    title: props.page.title,
    content: props.content,
    category_ids: props.page.category_ids || [],
    tag_ids: props.page.tag_ids || [],
    comment: '',
    version_id: initialVersionId.value
});

// --- Computed Properties ---
const computedEditorIsEditable = computed(() => props.editorIsEditable);
const autosaveStatusClass = computed(() => {
    if (!autosaveStatus.value) return 'text-gray-400 dark:text-gray-500 italic';
    switch (autosaveStatus.value.type) {
        case 'success': return 'text-green-600 dark:text-green-400';
        case 'error': return 'text-red-600 dark:text-red-400';
        case 'pending': return 'text-blue-600 dark:text-blue-400';
        case 'info': return 'text-blue-600 dark:text-blue-400';
        default: return 'text-gray-500 dark:text-gray-400';
    }
});

const autosaveStatusIcon = computed(() => {
    if (!autosaveStatus.value) return ['fas', 'circle-info'];
    switch (autosaveStatus.value.type) {
        case 'success': return ['fas', 'check-circle'];
        case 'error': return ['fas', 'exclamation-circle'];
        case 'pending': return ['fas', 'spinner'];
        case 'info': return ['fas', 'info-circle'];
        default: return ['fas', 'info-circle'];
    }
});

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

// 其他 methods (toggleEditorEditable, onDraftSaved, etc.) 保持不变
const toggleEditorEditable = () => {
    console.warn("toggleEditorEditable called, but button might be removed in split view");
};
const onDraftSaved = (status) => {
    if (status && status.saved_at) {
        localLastSaved.value = new Date(status.saved_at);
    }
    if (form.errors.general) form.clearErrors('general');
    autosaveStatus.value = status;
    if (status?.type === 'error') {
        form.setError('general', status.message);
        setTimeout(() => {
            if (autosaveStatus.value?.type === 'error') {
                autosaveStatus.value = null;
            }
        }, 5000);
    } else {
        if (form.errors.general && !form.errors.general.includes('已被更新')) {
            form.clearErrors('general');
        }
        setTimeout(() => {
            if (autosaveStatus.value && autosaveStatus.value.type !== 'error') {
                autosaveStatus.value = null;
            }
        }, 3000);
    }
    if (status?.type === 'success') {
        isOutdated.value = false;
        if (status.draft_id && status.message.includes('已于')) {
            console.log("Draft saved, but base version remains v" + initialVersionNumber.value);
        }
    }
};
const handleEditorStatusUpdate = (status) => {
    autosaveStatus.value = status;
};
const savePage = () => {
    if (isOutdated.value) {
        alert("页面已被其他用户更新，请先刷新页面查看最新内容，然后再提交您的修改！");
        autosaveStatus.value = { type: 'error', message: '页面已过时，请刷新！' };
        return;
    }
    if (tiptapEditorRef.value && tiptapEditorRef.value.editor) {
        form.content = tiptapEditorRef.value.editor.getHTML();
    } else {
        console.error("无法获取编辑器实例来保存内容。");
        form.setError('general', '无法获取编辑器内容，请重试。');
        autosaveStatus.value = { type: 'error', message: '编辑器错误，无法保存' };
        return;
    }
    if (form.content === '<p></p>') {
        form.setError('content', '内容不能为空。');
        autosaveStatus.value = { type: 'error', message: '内容不能为空' };
        return;
    }
    form.version_id = initialVersionId.value;
    form.put(route('wiki.update', props.page.slug), {
        preserveScroll: true,
        onError: (pageErrors) => {
            console.error("页面保存失败:", pageErrors);
            if (pageErrors.version_id && pageErrors.version_id.includes('无效')) {
                isOutdated.value = true;
                form.setError('general', '页面已被更新，请刷新后再试。');
                autosaveStatus.value = { type: 'error', message: '页面已过时，请刷新！' };
            } else if (pageErrors.general && pageErrors.general.includes('编辑冲突')) {
                form.setError('general', pageErrors.general);
                autosaveStatus.value = { type: 'error', message: '编辑冲突，请解决' };
            } else if (pageErrors.content) {
                form.setError('content', pageErrors.content);
                autosaveStatus.value = { type: 'error', message: '内容无效或过长' };
            } else if (pageErrors.title) {
                form.setError('title', pageErrors.title);
                autosaveStatus.value = { type: 'error', message: '标题无效或重复' };
            } else if (pageErrors.category_ids) {
                form.setError('category_ids', pageErrors.category_ids);
                autosaveStatus.value = { type: 'error', message: '分类选择无效' };
            } else {
                form.setError('general', '保存页面时出错，请检查字段错误或稍后重试。');
                autosaveStatus.value = { type: 'error', message: '保存失败，请重试' };
            }
            if (!isOutdated.value) {
                setTimeout(() => {
                    if (autosaveStatus.value?.type === 'error') {
                        autosaveStatus.value = null;
                    }
                }, 5000);
            }
        },
        onSuccess: (pageResponse) => {
            const successProps = pageResponse.props;
            const newPageData = successProps?.page;
            const newVersionId = newPageData?.current_version_id;
            const newVersionNumber = newPageData?.current_version?.version_number;
            if (newVersionId && newVersionNumber != null) {
                initialVersionId.value = newVersionId;
                initialVersionNumber.value = newVersionNumber;
                isOutdated.value = false;
                console.log(`Page saved successfully. New base version ID: ${initialVersionId.value}, Number: ${initialVersionNumber.value}`);
            } else {
                console.warn("Save success response did not contain fully updated version information. Keeping current base.", newPageData);
            }
            localLastSaved.value = null;
            autosaveStatus.value = { type: 'success', message: '页面已成功保存！' };
            form.comment = ''; // Clear comment field
            form.clearErrors(); // Clear any previous errors
            setTimeout(() => {
                if (autosaveStatus.value?.type === 'success') {
                    autosaveStatus.value = null;
                }
            }, 3000);
        },
        onFinish: () => {
            console.log("Save attempt finished.");
        }
    });
};
const refreshPage = () => {
    console.log("Attempting to refresh page data...");
    autosaveStatus.value = { type: 'pending', message: '正在刷新页面...' };
    router.reload({
        only: [
            'page',
            'content',
            'isLocked',
            'lockedBy',
            'draft',
            'lastSaved',
            'isConflict',
            'canResolveConflict',
            'editorIsEditable',
            'errors'
        ],
        preserveState: false,
        preserveScroll: true,
        onSuccess: () => {
            console.log("Page reloaded successfully.");
            autosaveStatus.value = { type: 'success', message: '页面数据已刷新！' };
            setTimeout(() => {
                if (autosaveStatus.value?.type === 'success') autosaveStatus.value = null;
            }, 2000);
        },
        onError: (errors) => {
            console.error("Page reload failed:", errors);
            flashMessage.value?.addMessage('error', '刷新页面失败，请稍后重试。');
            autosaveStatus.value = { type: 'error', message: '刷新失败' };
            setTimeout(() => {
                if (autosaveStatus.value?.type === 'error') autosaveStatus.value = null;
            }, 3000);
        }
    });
};
const handleGlobalVersionUpdate = (event) => {
    if (event.detail.pageId === props.page.id && event.detail.newVersionId !== initialVersionId.value) {
        console.log(`Page outdated detected via global event! Current base: ${initialVersionId.value}, Newest: ${event.detail.newVersionId}`);
        isOutdated.value = true;
        autosaveStatus.value = { type: 'error', message: '页面已被更新，请刷新！' };
    }
};
const setupVersionUpdateListener = () => {
    const channelName = `wiki.page.${props.page.id}`;
    if (!window.Echo) {
        console.warn("Echo is not initialized! Cannot listen for version updates.");
        return;
    }
    try {
        echoChannel = window.Echo.channel(channelName);
        echoChannel.listen('.page.version.updated', (data) => {
            console.log('Received page.version.updated event via Echo:', data);
            if (data.newVersionId && data.newVersionId !== initialVersionId.value) {
                console.log(`Page outdated detected via Echo! Current base: ${initialVersionId.value}, Newest: ${data.newVersionId}`);
                isOutdated.value = true;
                autosaveStatus.value = { type: 'error', message: '页面已被更新，请刷新！' };
            }
        });
        echoChannel.error((error) => { console.error(`Echo channel error on ${channelName}:`, error); });
        console.log(`Listening on channel: ${channelName} for version updates`);
    } catch (error) {
        console.error(`Error setting up Echo listener for version updates on channel ${channelName}:`, error);
    }
};
// Lifecycle hooks and watchers remain largely the same
onMounted(() => {
    console.log("Edit.vue mounted. Initial editable state from prop:", props.editorIsEditable);
    setupVersionUpdateListener();
    watch(() => pageProps.errors, (newErrors) => {
        if (newErrors) {
            form.errors = newErrors;
            if (newErrors.general?.includes('版本ID无效')) {
                isOutdated.value = true;
                autosaveStatus.value = { type: 'error', message: '页面已过时，请刷新！' };
            } else if (newErrors.general && !form.errors.general?.includes('已被更新')) {
                autosaveStatus.value = { type: 'error', message: newErrors.general };
                setTimeout(() => {
                    if (autosaveStatus.value?.type === 'error' && !isOutdated.value) {
                        autosaveStatus.value = null;
                    }
                }, 5000);
            }
        }
    }, { deep: true, immediate: true });
    watch(() => props.page, (newPage, oldPage) => {
        if (newPage && newPage !== oldPage && newPage.id === props.page.id) {
            console.log('Page props updated, resetting form and states.');
            form.title = newPage.title;
            if (props.content !== form.content) {
                form.content = props.content;
                if (tiptapEditorRef.value?.editor) {
                    tiptapEditorRef.value.editor.commands.setContent(props.content, false);
                }
            }
            form.category_ids = newPage.category_ids || [];
            form.tag_ids = newPage.tag_ids || [];
            initialVersionId.value = newPage.current_version_id;
            initialVersionNumber.value = newPage.current_version?.version_number || '未知';
            isOutdated.value = false;
            const newEditableState = !newPage.is_locked && (!newPage.is_conflict || props.canResolveConflict);
            if (computedEditorIsEditable.value !== newEditableState) {
                if (tiptapEditorRef.value?.editor) {
                    tiptapEditorRef.value.editor.setEditable(newEditableState);
                }
            }
            if (tiptapEditorRef.value?.editor) {
                tiptapEditorRef.value.editor.setEditable(props.editorIsEditable);
            }
            form.clearErrors();
            if (autosaveStatus.value?.type !== 'success') {
                autosaveStatus.value = { type: 'info', message: '页面已刷新至最新版本' };
                setTimeout(() => {
                    if (autosaveStatus.value?.type === 'info') autosaveStatus.value = null;
                }, 3000);
            }
        }
    }, { deep: true });

    // isMobile check on mount
    updateMobileStatus();
    window.addEventListener('resize', updateMobileStatus);
});
onBeforeUnmount(() => {
    console.log("Edit.vue unmounting");
    if (echoChannel) {
        try {
            echoChannel.stopListening('.page.version.updated');
            window.Echo.leave(`wiki.page.${props.page.id}`);
            console.log(`Stopped listening and left channel: wiki.page.${props.page.id}`);
        } catch (e) {
            console.error("Error stopping Echo listener or leaving channel:", e);
        }
        echoChannel = null;
    }
    window.removeEventListener('page-version-updated', handleGlobalVersionUpdate);
    window.removeEventListener('resize', updateMobileStatus); // Cleanup resize listener
});

const isMobile = ref(false);
const updateMobileStatus = () => {
    isMobile.value = window.innerWidth < 768;
    // Removed auto-show logic for preview on desktop resize
};
</script>

<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head :title="`编辑: ${page.title}`" />
        <!-- Removed fixed height container -->
        <div class="container mx-auto py-6 px-4 flex flex-col">
            <div
                class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6 flex flex-col flex-grow">
                <!-- Header Section -->
                <div class="flex justify-between items-start mb-4 pb-4 border-b dark:border-gray-700 flex-shrink-0">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">编辑: {{ page.title }}</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            基于版本: v{{ initialVersionNumber }} (ID: {{ initialVersionId || 'N/A' }})
                        </p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <!-- Preview Toggle Button -->
                        <button @click="togglePreviewPane" class="btn-secondary text-xs px-2 py-1">
                            <font-awesome-icon :icon="['fas', showPreviewPane ? 'eye-slash' : 'eye']" class="mr-1" />
                            {{ showPreviewPane ? '隐藏' : '显示' }}预览
                        </button>
                        <button @click="refreshPage" class="btn-secondary text-sm" title="刷新页面数据">
                            <font-awesome-icon :icon="['fas', 'sync-alt']" :spin="form.processing && !isOutdated" /> 刷新
                        </button>
                        <button @click="openPreviewInNewTab" type="button" class="btn-secondary text-sm"
                            title="在新标签页中预览页面">
                            <font-awesome-icon :icon="['fas', 'external-link-alt']" class="mr-1" /> 在新标签页预览
                        </button>
                        <Link :href="route('wiki.show', page.slug)" class="btn-secondary text-sm">
                        取消
                        </Link>
                        <button @click="savePage" class="btn-primary text-sm"
                            :disabled="!computedEditorIsEditable || form.processing || isOutdated"
                            :title="isOutdated ? '页面已被更新，请刷新后提交' : (!computedEditorIsEditable ? '当前无法编辑（锁定或冲突）' : '保存更改')">
                            <font-awesome-icon v-if="form.processing" :icon="['fas', 'spinner']" spin class="mr-1" />
                            {{ form.processing ? '正在保存...' : '保存页面' }}
                        </button>
                    </div>
                </div>

                <!-- Alerts and Editors List (no height change) -->
                <div v-if="isConflict && !canResolveConflict" class="alert alert-error mb-4 flex-shrink-0">...</div>
                <div v-else-if="isConflict && canResolveConflict" class="alert alert-warning mb-4 flex-shrink-0">...
                </div>
                <div v-if="isOutdated" class="alert alert-info mb-4 flex-shrink-0">...</div>
                <div v-if="hasDraft && localLastSaved && !isOutdated" class="alert alert-info mb-4 flex-shrink-0">...
                </div>
                <EditorsList :pageId="page.id" class="mb-4 flex-shrink-0" />

                <!-- Main Content Area (Editor + Preview) - Removed fixed height -->
                <div class="flex-grow flex flex-col md:flex-row gap-6">
                    <!-- Editing Pane: Using dynamic class -->
                    <div :class="editorPaneClass" class="flex flex-col overflow-y-auto pr-2 editor-pane">
                        <!-- Form content moved inside the scrollable div -->
                        <div class="space-y-5 flex-grow flex flex-col">
                            <!-- Title, Editor, Categories, Tags, Comment (remain same structure) -->
                            <div>
                                <label for="title"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">标题 <span
                                        class="text-red-500">*</span></label>
                                <input id="title" v-model="form.title" type="text" class="input-field"
                                    :disabled="!computedEditorIsEditable || isOutdated" required />
                                <InputError class="mt-1" :message="form.errors.title" />
                            </div>
                            <div class="flex-grow flex flex-col">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">内容 <span
                                        class="text-red-500">*</span></label>
                                <Editor v-model="form.content" :editable="computedEditorIsEditable && !isOutdated"
                                    :autosave="computedEditorIsEditable && !isOutdated" :pageId="page.id"
                                    @saved="onDraftSaved" @statusUpdate="handleEditorStatusUpdate"
                                    @toggle-edit="toggleEditorEditable" placeholder="开始编辑页面内容..." ref="tiptapEditorRef"
                                    class="flex-grow" />
                                <InputError class="mt-1" :message="form.errors.content" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">分类 <span
                                        class="text-red-500">*</span></label>
                                <div class="checkbox-group"
                                    :class="{ 'disabled-group': !computedEditorIsEditable || isOutdated }">
                                    <div v-for="category in categories" :key="category.id" class="flex items-center">
                                        <input type="checkbox" :id="`category-${category.id}`" :value="category.id"
                                            v-model="form.category_ids"
                                            :disabled="!computedEditorIsEditable || isOutdated" class="checkbox" />
                                        <label :for="`category-${category.id}`"
                                            class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ category.name
                                            }}</label>
                                    </div>
                                </div>
                                <InputError class="mt-1" :message="form.errors.category_ids" />
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">标签</label>
                                <div class="checkbox-group"
                                    :class="{ 'disabled-group': !computedEditorIsEditable || isOutdated }">
                                    <div v-for="tag in tags" :key="tag.id" class="flex items-center">
                                        <input type="checkbox" :id="`tag-${tag.id}`" :value="tag.id"
                                            v-model="form.tag_ids" :disabled="!computedEditorIsEditable || isOutdated"
                                            class="checkbox" />
                                        <label :for="`tag-${tag.id}`"
                                            class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ tag.name }}</label>
                                    </div>
                                </div>
                                <InputError class="mt-1" :message="form.errors.tag_ids" />
                            </div>
                            <div>
                                <label for="comment"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">提交说明</label>
                                <textarea id="comment" v-model="form.comment" rows="2"
                                    :disabled="!computedEditorIsEditable || isOutdated" class="textarea-field"
                                    placeholder="例如：修正了XX数据..."></textarea>
                                <InputError class="mt-1" :message="form.errors.comment" />
                            </div>
                            <div v-if="form.errors.general && (!autosaveStatus || autosaveStatus.type !== 'error')"
                                class="mt-1 text-sm text-red-600 dark:text-red-400 text-right font-medium">
                                <font-awesome-icon :icon="['fas', 'exclamation-circle']" class="mr-1" /> {{
                                form.errors.general }}
                            </div>
                            <div class="editor-status-bar flex-shrink-0 mt-auto text-right pr-1">
                                <span v-if="autosaveStatus" :class="autosaveStatusClass"
                                    class="flex items-center justify-end text-xs">
                                    <font-awesome-icon :icon="autosaveStatusIcon"
                                        :spin="autosaveStatus.type === 'pending'" class="mr-1 h-3 w-3" />
                                    {{ autosaveStatus.message }}
                                </span>
                                <span v-else class="text-xs text-gray-400 dark:text-gray-500 italic">未自动保存或无更改</span>
                            </div>
                        </div>
                    </div>

                    <!-- Preview Pane: Using dynamic class -->
                    <div :class="previewPaneClass" class="flex flex-col">
                        <!-- Added h-full to make it fill available flex height -->
                        <WikiPreviewPane class="h-full" :form="form" :categories="categories" :tags="tags" :page="page"
                            :currentVersion="page.current_version" />
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<style scoped>
/* Keeping existing styles, but adjusting height constraints */
.editor-pane {
    scrollbar-width: thin;
    scrollbar-color: #a0aec0 #e2e8f0;
    /* Allow flex item to grow based on content */
    flex-grow: 1;
    min-height: 400px;
    /* Optional: Set a minimum height if needed */
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
    /* Ensure it takes height */
    height: 100%;
}

:deep(.editor-content) {
    flex-grow: 1;
    max-height: none;
    /* Remove potential max-height constraints */
    height: auto;
    /* Allow it to grow naturally */
    /* Make sure content itself scrolls if needed */
    overflow-y: auto;
}

.input-field,
.textarea-field,
select {
    @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400 text-sm disabled:bg-gray-100 dark:disabled:bg-gray-800/60 disabled:cursor-not-allowed dark:disabled:text-gray-500;
}

.checkbox-group {
    @apply grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 max-h-48 overflow-y-auto p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-800/50;
}

.checkbox-group.disabled-group {
    @apply bg-gray-100 dark:bg-gray-800/30 opacity-60 cursor-not-allowed;
}

.checkbox {
    @apply h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 disabled:cursor-not-allowed;
}

.checkbox:disabled+label {
    @apply cursor-not-allowed opacity-60;
}

.btn-primary {
    @apply px-4 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
    @apply px-4 py-1.5 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 text-sm font-medium;
}

.alert {
    @apply p-3 rounded-md border-l-4 mb-4 text-sm;
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
    @apply mt-1 text-xs min-h-[16px];
}

.md\\:hidden {
    display: none;
}

@media (max-width: 767px) {
    .md\\:hidden {
        display: block;
    }
}
</style>
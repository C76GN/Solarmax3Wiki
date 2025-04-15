<script setup>
import { ref, onMounted, onUnmounted, onBeforeUnmount, computed, watch } from 'vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Editor from '@/Components/Wiki/Editor.vue';
import InputError from '@/Components/Other/InputError.vue';
import EditorsList from '@/Components/Wiki/EditorsList.vue';
import WikiPreviewPane from '@/Components/Wiki/WikiPreviewPane.vue';
import FlashMessage from '@/Components/Other/FlashMessage.vue'; // 引入FlashMessage
import { formatDateTime } from '@/utils/formatters';
import { mainNavigationLinks } from '@/config/navigationConfig';
import axios from 'axios';

const navigationLinks = mainNavigationLinks;
const pageProps = usePage().props;
const flashMessage = ref(null); // 添加FlashMessage的ref

// --- Props ---
const props = defineProps({
    page: { type: Object, required: true },
    content: { type: String, required: true }, // 这是从后端加载的初始内容
    categories: { type: Array, required: true },
    tags: { type: Array, required: true },
    hasDraft: { type: Boolean, default: false },
    lastSaved: { type: String, default: null },
    canResolveConflict: { type: Boolean, default: false },
    isConflict: { type: Boolean, default: false },
    editorIsEditable: { type: Boolean, default: true },
    errors: Object
});

// --- Refs ---
const tiptapEditorRef = ref(null);
const showPreviewPane = ref(true);
const initialVersionId = ref(props.page.current_version_id || props.page.current_version?.id);
const initialVersionNumber = ref(props.page.current_version?.version_number || '未知');
const isOutdated = ref(false); // 标记页面是否已过时
const autosaveStatus = ref(null); // 用于显示自动保存或提交状态
const localLastSaved = ref(props.lastSaved ? new Date(props.lastSaved) : null); // 本地草稿保存时间

// --- **新增：用于存储初始状态的 Refs** ---
const initialTitle = ref('');
const initialContent = ref('');
const initialCategoryIds = ref([]);
const initialTagIds = ref([]);

let echoChannel = null; // Echo channel

// --- Form ---
const form = useForm({
    title: props.page.title,
    content: props.content, // 使用传入的 content 初始化
    category_ids: [...(props.page.category_ids || [])].map(id => Number(id)), // 确保是数字数组
    tag_ids: [...(props.page.tag_ids || [])].map(id => Number(id)), // 确保是数字数组
    comment: '',
    version_id: initialVersionId.value
});

// --- Computed Properties ---
const computedEditorIsEditable = computed(() => props.editorIsEditable && !props.isConflict); // 编辑器是否可编辑

const autosaveStatusClass = computed(() => {
    if (!autosaveStatus.value) return 'text-gray-400 dark:text-gray-500 italic';
    switch (autosaveStatus.value.type) {
        case 'success': return 'text-green-600 dark:text-green-400';
        case 'error': return 'text-red-600 dark:text-red-400';
        case 'pending': return 'text-blue-600 dark:text-blue-400';
        case 'info': return 'text-blue-600 dark:text-blue-400'; // 用于“无更改”提示
        default: return 'text-gray-500 dark:text-gray-400';
    }
});

const autosaveStatusIcon = computed(() => {
    if (!autosaveStatus.value) return ['fas', 'circle-info']; // 默认图标
    switch (autosaveStatus.value.type) {
        case 'success': return ['fas', 'check-circle'];
        case 'error': return ['fas', 'exclamation-circle'];
        case 'pending': return ['fas', 'spinner'];
        case 'info': return ['fas', 'info-circle'];
        default: return ['fas', 'info-circle'];
    }
});

// --- Methods ---
const togglePreviewPane = () => {
    showPreviewPane.value = !showPreviewPane.value;
};

const editorPaneClass = computed(() => {
    return showPreviewPane.value ? 'w-full md:w-1/2 h-full' : 'w-full h-full';
});

const previewPaneClass = computed(() => {
    return showPreviewPane.value ? 'w-full md:w-1/2 h-full' : 'hidden';
});

// 这个函数现在只是改变本地状态，由Editor组件内部处理editable
const toggleEditorEditable = () => {
    console.warn("toggleEditorEditable called, but editor's editable state is primarily controlled by props.");
    // 如果需要，可以触发父组件更新 editorIsEditable prop，但这通常不是最佳实践
};

// 草稿保存成功后的回调
const onDraftSaved = (status) => {
    if (status && status.saved_at) {
        localLastSaved.value = new Date(status.saved_at);
    }
    // 如果保存成功，清除通用错误（但不清除特定字段错误）
    if (status?.type === 'success' && form.errors.general) {
        // 保留与版本过时相关的错误
        if (!form.errors.general.includes('已被更新')) {
            form.clearErrors('general');
        }
    }
    autosaveStatus.value = status;

    // 设置错误消息，如果不是版本过时错误，则短时间显示
    if (status?.type === 'error') {
        form.setError('general', status.message);
        if (!isOutdated.value) { // 如果不是因为过时导致的错误，则过会清除
            setTimeout(() => {
                if (autosaveStatus.value?.type === 'error') {
                    autosaveStatus.value = null;
                    // 清除因草稿保存失败引起的通用错误
                    if (form.errors.general === status.message) {
                        form.clearErrors('general');
                    }
                }
            }, 5000);
        }
    } else {
        // 如果状态不是错误，清除可能存在的非版本过时通用错误
        if (form.errors.general && !form.errors.general.includes('已被更新')) {
            form.clearErrors('general');
        }
        // 短暂显示成功或信息提示
        setTimeout(() => {
            if (autosaveStatus.value && autosaveStatus.value.type !== 'error') {
                autosaveStatus.value = null;
            }
        }, 3000);
    }

    // 如果草稿保存成功，并且之前有过时的状态，取消过时状态
    // 注意：草稿保存成功不代表页面不过时，需要页面保存成功才行
    // if (status?.type === 'success') {
    //    isOutdated.value = false;
    // }
};

// 处理编辑器内部状态更新（例如自动保存状态）
const handleEditorStatusUpdate = (status) => {
    autosaveStatus.value = status;
};

// 提交页面保存
const savePage = () => {
    if (isOutdated.value) {
        // alert("页面已被其他用户更新，请先刷新页面查看最新内容，然后再提交您的修改！");
        flashMessage.value?.addMessage('error', '页面已被更新，请刷新后再提交！');
        autosaveStatus.value = { type: 'error', message: '页面已过时，请刷新！' };
        return;
    }

    const currentContent = tiptapEditorRef.value?.editor?.getHTML() || form.content;
    if (!currentContent || currentContent === '<p></p>') {
        form.setError('content', '内容不能为空。');
        autosaveStatus.value = { type: 'error', message: '内容不能为空' };
        return;
    }
    form.content = currentContent; // 确保form中的content是最新编辑器的

    // --- **新增：比较更改** ---
    const currentCategoryIds = [...form.category_ids].sort();
    const currentTagIds = [...form.tag_ids].sort();
    const storedInitialCategoryIds = [...initialCategoryIds.value].sort(); // 获取已排序的初始值
    const storedInitialTagIds = [...initialTagIds.value].sort(); // 获取已排序的初始值

    const titleChanged = form.title !== initialTitle.value;
    const contentChanged = form.content !== initialContent.value;
    const categoriesChanged = JSON.stringify(currentCategoryIds) !== JSON.stringify(storedInitialCategoryIds);
    const tagsChanged = JSON.stringify(currentTagIds) !== JSON.stringify(storedInitialTagIds);

    // 如果没有任何更改
    if (!titleChanged && !contentChanged && !categoriesChanged && !tagsChanged) {
        autosaveStatus.value = { type: 'info', message: '未检测到任何更改，无需保存。' };
        setTimeout(() => {
            if (autosaveStatus.value?.type === 'info') autosaveStatus.value = null;
        }, 3000);
        return; // 阻止提交
    }
    // --- **比较结束** ---

    form.version_id = initialVersionId.value; // 确保基于正确的版本提交
    form.put(route('wiki.update', props.page.slug), {
        preserveScroll: true,
        onError: (pageErrors) => {
            console.error("页面保存失败:", pageErrors);
            // 检查版本ID错误
            if (pageErrors.version_id || pageErrors.general?.includes('版本ID无效')) {
                isOutdated.value = true;
                form.setError('general', '页面已被更新，请刷新后再试。');
                autosaveStatus.value = { type: 'error', message: '页面已过时，请刷新！' };
                // 检查冲突错误
            } else if (pageErrors.general?.includes('编辑冲突')) {
                form.setError('general', pageErrors.general);
                autosaveStatus.value = { type: 'error', message: '编辑冲突，请解决' };
                // 处理其他字段错误
            } else {
                if (pageErrors.content) form.setError('content', pageErrors.content);
                if (pageErrors.title) form.setError('title', pageErrors.title);
                if (pageErrors.category_ids) form.setError('category_ids', pageErrors.category_ids);
                if (pageErrors.tag_ids) form.setError('tag_ids', pageErrors.tag_ids);
                if (!form.hasErrors) { // 如果没有特定字段错误，显示通用错误
                    form.setError('general', pageErrors.general || '保存页面时出错，请稍后重试。');
                }
                autosaveStatus.value = { type: 'error', message: '保存失败，请检查错误信息' };
            }

            // 如果不是因为过时导致的错误，过一段时间清除错误提示
            if (!isOutdated.value) {
                setTimeout(() => {
                    if (autosaveStatus.value?.type === 'error' && !isOutdated.value) {
                        autosaveStatus.value = null;
                        // 仅清除因本次保存失败引起的通用错误
                        if (form.errors.general && form.errors.general !== '页面已被更新，请刷新后再试。' && form.errors.general !== '编辑冲突，请解决') {
                            form.clearErrors('general');
                        }
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
                isOutdated.value = false; // 页面保存成功，不再过时
                // --- **新增：更新初始状态** ---
                initialTitle.value = form.title;
                initialContent.value = form.content;
                initialCategoryIds.value = [...form.category_ids].sort();
                initialTagIds.value = [...form.tag_ids].sort();
                // --- **更新结束** ---
                console.log(`Page saved successfully. New base version ID: ${initialVersionId.value}, Number: ${initialVersionNumber.value}`);
            } else {
                console.warn("Save success response did not contain fully updated version information. Keeping current base.", newPageData);
                // 理论上后端应该总返回最新信息，如果没返回，可能需要提示用户手动刷新
                flashMessage.value?.addMessage('warning', '页面已保存，但未能获取最新版本信息，建议刷新页面。');
            }

            localLastSaved.value = null; // 清除本地草稿信息
            autosaveStatus.value = { type: 'success', message: '页面已成功保存！' };
            form.comment = ''; // 清空提交说明
            form.clearErrors(); // 清除所有错误

            // 短暂显示成功消息
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

// 刷新页面数据
const refreshPage = () => {
    console.log("Attempting to refresh page data...");
    autosaveStatus.value = { type: 'pending', message: '正在刷新页面...' };
    router.reload({
        only: [
            'page',
            'content', // 确保 content 也被重新加载
            'isLocked',
            'lockedBy',
            'draft',
            'lastSaved',
            'isConflict',
            'canResolveConflict',
            'editorIsEditable',
            'errors'
        ],
        preserveState: false, // 设置为false以强制重新渲染并更新props
        preserveScroll: true,
        onSuccess: () => {
            console.log("Page reloaded successfully.");
            autosaveStatus.value = { type: 'success', message: '页面数据已刷新！' };
            // **更新初始状态以反映刷新后的数据**
            initialTitle.value = pageProps.page.title;
            initialContent.value = pageProps.content;
            initialCategoryIds.value = [...(pageProps.page.category_ids || [])].map(id => Number(id)).sort();
            initialTagIds.value = [...(pageProps.page.tag_ids || [])].map(id => Number(id)).sort();
            // 重置过时状态
            isOutdated.value = false;
            form.clearErrors(); // 清除可能存在的过时错误提示

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

// 处理版本更新事件 (来自Echo或全局事件)
const handleVersionUpdate = (newVersionId) => {
    if (newVersionId && newVersionId !== initialVersionId.value) {
        console.log(`Page outdated detected via Echo/Event! Current base: ${initialVersionId.value}, Newest: ${newVersionId}`);
        isOutdated.value = true;
        autosaveStatus.value = { type: 'error', message: '页面已被更新，请刷新！' };
        form.setError('general', '页面已被其他用户更新，请刷新后再提交。'); // 设置通用错误提示
    }
}

// 设置 Echo 监听器
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
            handleVersionUpdate(data.newVersionId);
        });
        echoChannel.error((error) => { console.error(`Echo channel error on ${channelName}:`, error); });
        console.log(`Listening on channel: ${channelName} for version updates`);
    } catch (error) {
        console.error(`Error setting up Echo listener for version updates on channel ${channelName}:`, error);
    }
};

// --- **新增：设置初始状态的函数** ---
const setInitialState = () => {
    initialTitle.value = props.page.title;
    initialContent.value = props.content; // 使用props.content作为初始内容
    initialCategoryIds.value = [...(props.page.category_ids || [])].map(id => Number(id)).sort();
    initialTagIds.value = [...(props.page.tag_ids || [])].map(id => Number(id)).sort();
    console.log("Initial state set:", {
        title: initialTitle.value,
        // content: initialContent.value, // 内容可能很长，不打印
        categories: initialCategoryIds.value,
        tags: initialTagIds.value
    });
};

// --- Lifecycle Hooks ---
onMounted(() => {
    console.log("Edit.vue mounted. Initial editable state from prop:", props.editorIsEditable);
    setInitialState(); // **在挂载时设置初始状态**
    setupVersionUpdateListener();

    // 监听props.errors的变化，特别是通用错误，以捕获版本ID无效等情况
    watch(() => pageProps.errors, (newErrors) => {
        if (newErrors) {
            form.errors = newErrors; // 将页面的错误同步到表单
            if (newErrors.general?.includes('版本ID无效') || newErrors.general?.includes('已被更新')) {
                isOutdated.value = true;
                autosaveStatus.value = { type: 'error', message: '页面已过时，请刷新！' };
            }
        }
    }, { deep: true, immediate: true }); // 立即执行一次以处理初始错误

    // 监听 page 和 content props 的变化，以便在页面刷新后更新表单和初始状态
    watch([() => props.page, () => props.content], ([newPage, newContent], [oldPage, oldContent]) => {
        // 只有当是同一个页面ID且数据实际发生变化时才更新
        if (newPage && newPage.id === oldPage?.id && (newPage !== oldPage || newContent !== oldContent)) {
            console.log('Page/Content props updated, resetting form and states.');

            // 更新表单数据
            form.title = newPage.title;
            form.category_ids = [...(newPage.category_ids || [])].map(id => Number(id));
            form.tag_ids = [...(newPage.tag_ids || [])].map(id => Number(id));
            // 只有当编辑器外部的内容与编辑器当前内容不同时才更新编辑器
            const editorCurrentContent = tiptapEditorRef.value?.editor?.getHTML();
            if (newContent !== editorCurrentContent) {
                form.content = newContent; // 更新 form 的 content
                if (tiptapEditorRef.value?.editor) {
                    tiptapEditorRef.value.editor.commands.setContent(newContent, false); // 更新编辑器内容，不触发 onUpdate
                }
            }

            // 更新版本信息
            initialVersionId.value = newPage.current_version_id;
            initialVersionNumber.value = newPage.current_version?.version_number || '未知';
            isOutdated.value = false; // 重置过时状态

            // 更新初始状态用于后续比较
            setInitialState();

            // 更新编辑器可编辑状态
            if (tiptapEditorRef.value?.editor) {
                tiptapEditorRef.value.editor.setEditable(props.editorIsEditable && !props.isConflict);
            }

            form.clearErrors(); // 清除旧错误
            // 提供刷新成功的提示
            if (autosaveStatus.value?.type === 'pending' && autosaveStatus.value.message === '正在刷新页面...') {
                autosaveStatus.value = { type: 'success', message: '页面数据已刷新！' };
                setTimeout(() => {
                    if (autosaveStatus.value?.type === 'success') autosaveStatus.value = null;
                }, 2000);
            } else if (!autosaveStatus.value || autosaveStatus.value?.type !== 'error') { // 如果没有错误，或者不是错误状态，给个刷新提示
                autosaveStatus.value = { type: 'info', message: '页面已刷新至最新版本' };
                setTimeout(() => {
                    if (autosaveStatus.value?.type === 'info') autosaveStatus.value = null;
                }, 3000);
            }
        }
    }, { deep: true });

    updateMobileStatus();
    window.addEventListener('resize', updateMobileStatus);
});

onBeforeUnmount(() => {
    console.log("Edit.vue unmounting");
    // 清理 Echo 监听器
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
    window.removeEventListener('resize', updateMobileStatus);
});

// 移动端检测
const isMobile = ref(false);
const updateMobileStatus = () => {
    isMobile.value = window.innerWidth < 768;
};

// --- 在新标签页预览 ---
const openPreviewInNewTab = () => {
    const currentContent = tiptapEditorRef.value?.editor?.getHTML() || form.content;
    // 1. 检查标题
    if (!form.title.trim()) {
        flashMessage.value?.addMessage('warning', '请先输入页面标题再预览！');
        return;
    }
    // 2. 检查内容
    if (!currentContent || currentContent === '<p></p>' || currentContent.trim() === '') {
        flashMessage.value?.addMessage('warning', '请先输入页面内容再预览！');
        return;
    }

    const url = route('wiki.preview');
    const csrfToken = pageProps.csrf;

    // 创建一个临时的表单
    const tempForm = document.createElement('form');
    tempForm.method = 'POST';
    tempForm.action = url;
    tempForm.target = '_blank'; // 在新标签页打开
    tempForm.style.display = 'none'; // 隐藏表单

    // 添加 CSRF token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;
    tempForm.appendChild(csrfInput);

    // 定义要发送的数据
    const fields = {
        title: form.title,
        content: currentContent, // 使用编辑器当前内容
        category_ids: form.category_ids,
        tag_ids: form.tag_ids,
    };

    // 将数据添加到表单中
    for (const key in fields) {
        if (Object.prototype.hasOwnProperty.call(fields, key)) {
            const value = fields[key];
            if (Array.isArray(value)) {
                // 处理数组类型的数据（如 category_ids, tag_ids）
                value.forEach((item, index) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `${key}[${index}]`; // Laravel能解析这种格式
                    input.value = item;
                    tempForm.appendChild(input);
                });
            } else {
                // 处理非数组类型的数据
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                tempForm.appendChild(input);
            }
        }
    }

    // 将表单添加到页面并提交，然后移除
    document.body.appendChild(tempForm);
    tempForm.submit();
    document.body.removeChild(tempForm);
};

</script>

<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head :title="`编辑: ${page.title}`" />
        <div class="container mx-auto py-6 px-4 flex flex-col h-[calc(100vh-6rem)]">
            <!-- 让主内容区占满剩余高度 -->
            <div
                class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-4 md:p-6 flex flex-col flex-grow overflow-hidden">
                <!-- Header -->
                <div
                    class="flex flex-col md:flex-row justify-between md:items-start mb-4 pb-4 border-b dark:border-gray-700 flex-shrink-0 gap-2">
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-gray-100 leading-tight">编辑: {{
                            page.title }}</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            基于版本: v{{ initialVersionNumber }} (ID: {{ initialVersionId || 'N/A' }})
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 flex-shrink-0 self-start md:self-center">
                        <button @click="togglePreviewPane" class="btn-secondary text-xs px-2 py-1" title="切换预览面板">
                            <font-awesome-icon :icon="['fas', showPreviewPane ? 'eye-slash' : 'eye']" class="mr-1" />
                            {{ showPreviewPane ? '隐藏' : '显示' }}预览
                        </button>
                        <button @click="refreshPage" class="btn-secondary text-sm" title="刷新页面数据"
                            :disabled="form.processing">
                            <font-awesome-icon :icon="['fas', 'sync-alt']"
                                :spin="autosaveStatus?.type === 'pending' && autosaveStatus?.message.includes('刷新')" />
                            刷新
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

                <!-- Alerts -->
                <div v-if="page.is_conflict && !canResolveConflict" class="alert alert-error mb-4 flex-shrink-0">
                    <font-awesome-icon :icon="['fas', 'exclamation-circle']" class="mr-2" /> 页面存在冲突，且您无权解决。请联系管理员。
                </div>
                <div v-else-if="page.is_conflict && canResolveConflict"
                    class="alert alert-warning mb-4 flex-shrink-0 flex items-center justify-between">
                    <span>
                        <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="mr-2" /> 此页面存在编辑冲突，请解决。
                    </span>
                    <Link :href="route('wiki.show-conflicts', page.slug)"
                        class="font-medium underline hover:text-yellow-800 dark:hover:text-yellow-200 ml-4">前往解决</Link>
                </div>
                <div v-if="isOutdated" class="alert alert-error mb-4 flex-shrink-0 flex items-center justify-between">
                    <span>
                        <font-awesome-icon :icon="['fas', 'exclamation-circle']" class="mr-2" /> 页面内容已过时，您的编辑基于旧版本。
                    </span>
                    <button @click="refreshPage"
                        class="font-medium underline hover:text-red-800 dark:hover:text-red-200 ml-4"
                        :disabled="form.processing">立即刷新</button>
                </div>
                <div v-if="hasDraft && localLastSaved && !isOutdated" class="alert alert-info mb-4 flex-shrink-0">
                    <font-awesome-icon :icon="['fas', 'save']" class="mr-2" /> 您有本地保存的草稿（{{
                        formatDateTime(localLastSaved) }}）。当前内容已加载该草稿。
                </div>

                <!-- Editors List -->
                <EditorsList :pageId="page.id" class="mb-4 flex-shrink-0" />

                <!-- Editor and Preview Panes -->
                <div class="flex-grow flex flex-col md:flex-row gap-6 overflow-hidden">
                    <!-- Editor Pane -->
                    <div :class="editorPaneClass" class="flex flex-col h-full">
                        <div class="space-y-5 flex-grow flex flex-col overflow-y-auto pr-2 editor-pane">
                            <!-- Title -->
                            <div>
                                <label for="title"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">标题 <span
                                        class="text-red-500">*</span></label>
                                <input id="title" v-model="form.title" type="text" class="input-field"
                                    :disabled="!computedEditorIsEditable || isOutdated" required />
                                <InputError class="mt-1" :message="form.errors.title" />
                            </div>
                            <!-- Content Editor -->
                            <div class="flex-grow flex flex-col">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">内容 <span
                                        class="text-red-500">*</span></label>
                                <Editor v-model="form.content" :editable="computedEditorIsEditable && !isOutdated"
                                    :autosave="computedEditorIsEditable && !isOutdated" :pageId="page.id"
                                    @saved="onDraftSaved" @statusUpdate="handleEditorStatusUpdate"
                                    @toggle-edit="toggleEditorEditable" placeholder="开始编辑页面内容..." ref="tiptapEditorRef"
                                    class="flex-grow h-full" />
                                <InputError class="mt-1" :message="form.errors.content" />
                            </div>
                            <!-- Categories -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">分类 <span
                                        class="text-red-500">*</span></label>
                                <div class="checkbox-group"
                                    :class="{ 'disabled-group': !computedEditorIsEditable || isOutdated }">
                                    <div v-for="category in categories" :key="category.id" class="flex items-center">
                                        <input type="checkbox" :id="`category-${category.id}`"
                                            :value="Number(category.id)" v-model="form.category_ids"
                                            :disabled="!computedEditorIsEditable || isOutdated" class="checkbox" />
                                        <label :for="`category-${category.id}`"
                                            class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ category.name
                                            }}</label>
                                    </div>
                                </div>
                                <InputError class="mt-1" :message="form.errors.category_ids" />
                            </div>
                            <!-- Tags -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">标签</label>
                                <div class="checkbox-group"
                                    :class="{ 'disabled-group': !computedEditorIsEditable || isOutdated }">
                                    <div v-for="tag in tags" :key="tag.id" class="flex items-center">
                                        <input type="checkbox" :id="`tag-${tag.id}`" :value="Number(tag.id)"
                                            v-model="form.tag_ids" :disabled="!computedEditorIsEditable || isOutdated"
                                            class="checkbox" />
                                        <label :for="`tag-${tag.id}`"
                                            class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ tag.name }}</label>
                                    </div>
                                </div>
                                <InputError class="mt-1" :message="form.errors.tag_ids" />
                            </div>
                            <!-- Save Comment -->
                            <div>
                                <label for="comment"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    提交说明 <span class="text-xs text-gray-500">(可选)</span>
                                </label>
                                <textarea id="comment" v-model="form.comment" rows="2"
                                    :disabled="!computedEditorIsEditable || isOutdated" class="textarea-field"
                                    placeholder="例如：修正了XX数据..."></textarea>
                                <InputError class="mt-1" :message="form.errors.comment" />
                            </div>
                            <!-- General Error / Autosave Status -->
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

                    <!-- Preview Pane -->
                    <div :class="previewPaneClass" class="flex flex-col h-full overflow-hidden">
                        <!-- 将 height-full 应用到预览组件上 -->
                        <WikiPreviewPane class="h-full" :form="form" :categories="categories" :tags="tags" :page="page"
                            :currentVersion="page.current_version" />
                    </div>
                </div>
            </div>
        </div>
        <FlashMessage ref="flashMessage" /> <!-- Flash message component -->
    </MainLayout>
</template>

<style scoped>
/* Editor pane scrollbar styling */
.editor-pane {
    scrollbar-width: thin;
    scrollbar-color: #a0aec0 #e2e8f0;
    /* thumb color track color for light mode */
    /* flex-grow: 1;
    min-height: 400px; */
    /* 确保编辑器有最小高度 */
}

.dark .editor-pane {
    scrollbar-color: #4a5568 #2d3748;
    /* thumb color track color for dark mode */
}

.editor-pane::-webkit-scrollbar {
    width: 6px;
}

.editor-pane::-webkit-scrollbar-track {
    background: #e2e8f0;
    /* Light mode track */
    border-radius: 3px;
}

.dark .editor-pane::-webkit-scrollbar-track {
    background: #2d3748;
    /* Dark mode track */
}

.editor-pane::-webkit-scrollbar-thumb {
    background-color: #a0aec0;
    /* Light mode thumb */
    border-radius: 3px;
}

.dark .editor-pane::-webkit-scrollbar-thumb {
    background-color: #4a5568;
    /* Dark mode thumb */
}

/* Tiptap Editor styling adjustments */
:deep(.tiptap-editor) {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    /* Allow editor to grow */
    /* height: 100%; Removed fixed height */
    min-height: 300px;
    /* Set a minimum height instead */
}

:deep(.editor-content) {
    flex-grow: 1;
    /* Allow content area to grow */
    max-height: none;
    /* Remove max-height */
    height: auto;
    /* Let height be determined by content */
    overflow-y: auto;
    /* Add scroll if needed */
}

/* Input field styling */
.input-field,
.textarea-field,
select {
    @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400 text-sm disabled:bg-gray-100 dark:disabled:bg-gray-800/60 disabled:cursor-not-allowed dark:disabled:text-gray-500;
}

/* Checkbox group styling */
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


/* Button styling */
.btn-primary {
    @apply px-4 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
    @apply px-4 py-1.5 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 text-sm font-medium;
}

/* Alert styling */
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

/* Editor status bar */
.editor-status-bar {
    @apply mt-1 text-xs min-h-[16px];
    /* Ensure it has some height */
}

/* Responsive adjustments */
.md\\:hidden {
    display: none;
}

@media (max-width: 767px) {
    .md\\:hidden {
        display: block;
        /* Show the preview toggle button on smaller screens */
    }

    /* Stack editor and preview vertically on small screens */
    .flex-col.md\\:flex-row {
        flex-direction: column;
    }

    .md\\:w-1\\/2 {
        width: 100%;
        /* Full width on small screens */
    }

    .editor-pane {
        padding-right: 0;
        /* Remove padding on small screens */
    }

    .flex-grow.flex.flex-col.md\\:flex-row {
        height: auto;
        /* Adjust height for vertical stacking */
        overflow: visible;
        /* Allow content to determine height */
    }

    .editor-pane,
    .previewPaneClass {
        /* Ensure both panes can scroll if needed */
        max-height: 50vh;
        /* Example max height for mobile view */
        overflow-y: auto;
    }
}
</style>
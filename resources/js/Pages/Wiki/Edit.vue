<template>
    <MainLayout :navigationLinks="navigationLinks">
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <h1 class="text-2xl font-bold mb-6">编辑 Wiki 页面: {{ page.title }}</h1>

                <!-- 锁定提示 -->
                <div v-if="lockInfo && lockInfo.isLocked && lockInfo.lockedBy?.id !== pageProps.auth.user?.id"
                    class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded-md">
                    <div class="flex items-center">
                        <font-awesome-icon :icon="['fas', 'lock']" class="mr-2" />
                        <p>
                            此页面当前正由 <strong>{{ lockInfo.lockedBy?.name || '其他用户' }}</strong> 编辑中，暂时无法编辑。
                            <span v-if="lockInfo.lockedUntil">锁将于 {{ formatDateTime(lockInfo.lockedUntil) }}
                                自动解除。</span>
                        </p>
                        <button v-if="canForceUnlock" @click="forceUnlockPage"
                            class="ml-auto px-2 py-1 text-xs bg-yellow-500 text-white rounded hover:bg-yellow-600">强制解锁
                        </button>
                    </div>
                </div>

                <!-- 编辑者列表 -->
                <EditorsList :pageId="page.id" v-if="isEditable" />

                <!-- 草稿提示 -->
                <div v-if="hasDraft && lastSaved"
                    class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded-md">
                    <div class="flex items-center">
                        <font-awesome-icon :icon="['fas', 'info-circle']" class="mr-2" />
                        <p>
                            检测到本地草稿，已自动加载。最后自动保存于 {{ formatDateTime(lastSaved) }}。
                        </p>
                    </div>
                </div>

                <form @submit.prevent="savePage">
                    <div class="space-y-6">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                                标题 <span class="text-red-500">*</span>
                            </label>
                            <input id="title" v-model="form.title" type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                :disabled="!isEditable" required />
                            <InputError class="mt-1" :message="form.errors.title" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                内容 <span class="text-red-500">*</span>
                            </label>
                            <Editor v-model="form.content" :editable="isEditable" :autosave="true" :pageId="page.id"
                                @saved="onDraftSaved" @statusUpdate="handleEditorStatusUpdate" placeholder="开始编辑页面内容..."
                                ref="tiptapEditorRef" />
                            <InputError class="mt-1" :message="form.errors.content" />
                            <div class="mt-1 text-xs text-gray-500 flex justify-end items-center">
                                <span v-if="autosaveStatus" :class="autosaveStatusClass" class="ml-2 flex items-center">
                                    <font-awesome-icon :icon="autosaveStatusIcon"
                                        :spin="autosaveStatus.type === 'pending'" class="mr-1" />
                                    {{ autosaveStatus.message }}
                                </span>
                                <span v-else class="ml-2 text-gray-400 italic">草稿未更改</span>
                            </div>
                        </div>

                        <!-- 移除父页面选择 -->
                        <!-- <div>
                            <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-1">
                                父页面
                            </label>
                            <select id="parent_id" v-model="form.parent_id"
                                    :disabled="!isEditable"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option :value="null">无 (顶级页面)</option>
                                <option v-for="parentOption in pages" :key="parentOption.id" :value="parentOption.id">
                                    {{ parentOption.title }}
                                </option>
                            </select>
                            <InputError class="mt-1" :message="form.errors.parent_id"/>
                        </div> -->

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                分类 <span class="text-red-500">*</span>
                            </label>
                            <div
                                class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-4 gap-y-2 max-h-60 overflow-y-auto p-2 border rounded">
                                <div v-for="category in categories" :key="category.id" class="flex items-center">
                                    <input type="checkbox" :id="`category-${category.id}`" :value="category.id"
                                        v-model="form.category_ids" :disabled="!isEditable"
                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                                    <label :for="`category-${category.id}`" class="ml-2 text-sm text-gray-700">
                                        {{ category.name }}
                                    </label>
                                </div>
                            </div>
                            <InputError class="mt-1" :message="form.errors.category_ids" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                标签
                            </label>
                            <div
                                class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-4 gap-y-2 max-h-60 overflow-y-auto p-2 border rounded">
                                <div v-for="tag in tags" :key="tag.id" class="flex items-center">
                                    <input type="checkbox" :id="`tag-${tag.id}`" :value="tag.id" v-model="form.tag_ids"
                                        :disabled="!isEditable"
                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                                    <label :for="`tag-${tag.id}`" class="ml-2 text-sm text-gray-700">
                                        {{ tag.name }}
                                    </label>
                                </div>
                            </div>
                            <InputError class="mt-1" :message="form.errors.tag_ids" />
                        </div>

                        <div>
                            <label for="comment" class="block text-sm font-medium text-gray-700 mb-1">
                                提交说明 <span class="text-xs text-gray-500">(本次修改的简要描述)</span>
                            </label>
                            <textarea id="comment" v-model="form.comment" rows="2" :disabled="!isEditable"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="例如：修正了XX数据，添加了YY说明..."></textarea>
                            <InputError class="mt-1" :message="form.errors.comment" />
                        </div>

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
                        <InputError class="mt-1 text-right text-red-600 font-medium" :message="form.errors.general" />
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
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
    // pages: { type: Array, required: true }, // 移除父页面列表
    hasDraft: { type: Boolean, default: false },
    lockInfo: { type: Object, default: () => ({ isLocked: false, lockedBy: null, lockedUntil: null }) },
    errors: Object
});

let lockRefreshTimer = null;
const tiptapEditorRef = ref(null);

const form = useForm({
    title: props.page.title,
    content: props.content, // 使用传入的 content 初始化
    category_ids: props.page.categories?.map(c => c.id) || [],
    tag_ids: props.page.tags?.map(t => t.id) || [],
    comment: '',
    version_id: props.page.current_version_id, // 传递基础版本 ID
    // parent_id: props.page.parent_id || null, // 移除
});

const lastSaved = ref(props.hasDraft ? new Date() : null); // Track last saved time from draft
const autosaveStatus = ref(null);

// --- Computed Properties ---
const isEditable = computed(() => {
    const lockedByOther = props.lockInfo.isLocked && props.lockInfo.lockedBy?.id !== pageProps.auth.user?.id;
    const inConflict = props.page.status === 'conflict' && !pageProps.auth.user?.permissions?.includes('wiki.resolve_conflict');
    return !lockedByOther && !inConflict;
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
            // Keep form.errors reactive updates from Inertia
        },
        onSuccess: () => {
            // Optionally clear local draft status if save succeeds
            lastSaved.value = null;
            autosaveStatus.value = null;
        }
    });
};

const unlockPageIfNeeded = async () => {
    // Only unlock if locked by the current user
    if (props.lockInfo.isLocked && props.lockInfo.lockedBy?.id === pageProps.auth.user?.id) {
        try {
            // Prefer sendBeacon for reliability on page exit
            if (navigator.sendBeacon) {
                const url = route('wiki.unlock');
                // sendBeacon needs specific data format, typically FormData or Blob
                const data = new URLSearchParams();
                data.append('page_id', props.page.id);
                data.append('_token', pageProps.csrf); // Assuming csrf token is available globally
                navigator.sendBeacon(url, data);
                console.log('Sent unlock request via sendBeacon');
            } else {
                // Fallback to axios for browsers without sendBeacon support
                await axios.post(route('wiki.unlock'), { page_id: props.page.id });
                console.log('Sent unlock request via axios');
            }
        } catch (error) {
            // Log but don't block user experience, especially on page exit
            console.warn('Failed to unlock page on finish/unmount:', error.response?.data || error.message);
        }
    }
};

const forceUnlockPage = async () => {
    if (!canForceUnlock.value) return;

    if (confirm(`确定要强制解锁由 ${props.lockInfo.lockedBy?.name || '其他用户'} 锁定的页面吗？这可能会导致对方未保存的更改丢失。`)) {
        try {
            await axios.post(route('wiki.unlock'), { page_id: props.page.id });
            // Reload the page to reflect the unlocked state
            router.reload({ preserveState: false }); // false to force fresh data
            alert('页面已强制解锁。');
        } catch (error) {
            console.error('强制解锁页面失败:', error.response?.data || error.message);
            alert(`强制解锁失败: ${error.response?.data?.message || '未知错误'}`);
        }
    }
};

// --- Lifecycle Hooks ---
onMounted(() => {
    // Start lock refresh only if locked by the current user
    if (props.lockInfo.isLocked && props.lockInfo.lockedBy?.id === pageProps.auth.user?.id) {
        lockRefreshTimer = setInterval(refreshLock, 1 * 60 * 1000); // Refresh lock every minute
    }
    // Add listener for page unload
    window.addEventListener('beforeunload', unlockPageIfNeeded);
});

onBeforeUnmount(() => {
    if (lockRefreshTimer) {
        clearInterval(lockRefreshTimer);
        lockRefreshTimer = null;
    }
    // Attempt to unlock on component unmount (e.g., navigating away SPA-style)
    unlockPageIfNeeded();
    // Remove listener
    window.removeEventListener('beforeunload', unlockPageIfNeeded);
});

const refreshLock = async () => {
    // Check if still locked by the current user before refreshing
    if (props.lockInfo.isLocked && props.lockInfo.lockedBy?.id === pageProps.auth.user?.id) {
        try {
            await axios.post(route('wiki.refresh-lock'), { page_id: props.page.id });
        } catch (error) {
            console.error('刷新页面锁失败:', error.response?.data || error.message);
            // Stop refreshing if it fails, maybe the lock expired or server issue
            if (lockRefreshTimer) {
                clearInterval(lockRefreshTimer);
                lockRefreshTimer = null;
                console.warn("Lock refresh failed, stopping timer. Lock might have expired.");
                // Notify user about the potential lock loss
                alert("您的页面锁定已过期或刷新失败，请保存您的更改并刷新页面。");
            }
        }
    } else {
        // If not locked by current user anymore, stop the timer
        if (lockRefreshTimer) {
            clearInterval(lockRefreshTimer);
            lockRefreshTimer = null;
        }
    }
};

</script>

<style>
/* Add any specific styles for the edit page if needed */
</style>
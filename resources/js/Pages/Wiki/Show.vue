<template>
    <MainLayout :navigationLinks="navigationLinks">
        <div class="container mx-auto py-8 px-4 md:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row lg:space-x-8">
                <!-- Main Content Area -->
                <div class="w-full lg:w-3/4">
                    <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-6 md:p-8">
                        <!-- Page Header -->
                        <div class="mb-8 pb-4 border-b border-gray-200">
                            <!-- Title, Author, Dates -->
                            <div class="flex flex-col md:flex-row justify-between md:items-start mb-4 gap-4">
                                <div>
                                    <h1 class="text-3xl md:text-4xl font-bold mb-2 leading-tight">{{ page.title }}</h1>
                                    <div class="text-sm text-gray-500 flex flex-wrap items-center gap-x-4 gap-y-1">
                                        <span v-if="page.creator" class="whitespace-nowrap">
                                            <font-awesome-icon :icon="['fas', 'user']" class="mr-1" /> 由 {{
                                                page.creator.name }} 创建于 {{ formatDateShort(page.created_at) }}
                                        </span>
                                        <span v-if="currentVersion" class="whitespace-nowrap">
                                            <font-awesome-icon :icon="['fas', 'edit']" class="mr-1" /> 最新 v{{
                                                currentVersion.version_number }}
                                            <span v-if="currentVersion.creator">由 {{ currentVersion.creator.name }} 编辑于
                                                {{ formatDateTime(currentVersion.created_at) }}</span>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2 flex-shrink-0">
                                    <Link :href="route('wiki.history', page.slug)" class="btn-icon-secondary">
                                    <font-awesome-icon :icon="['fas', 'history']" /> <span>历史</span>
                                    </Link>
                                    <Link v-if="canEditPage" :href="route('wiki.edit', page.slug)"
                                        class="btn-icon-primary">
                                    <font-awesome-icon :icon="['fas', 'edit']" /> <span>编辑</span>
                                    </Link>
                                    <button v-if="canResolveConflict && page.status === 'conflict'"
                                        @click="openResolveConflictModal" class="btn-icon-warning">
                                        <font-awesome-icon :icon="['fas', 'exclamation-triangle']" /> <span>解决冲突</span>
                                    </button>
                                </div>
                            </div>
                            <!-- Categories and Tags -->
                            <div class="flex flex-wrap gap-2 mt-3">
                                <span class="text-sm text-gray-500 mr-2">分类:</span>
                                <Link v-for="category in page.categories" :key="category.id"
                                    :href="route('wiki.index', { category: category.slug })" class="tag-category">
                                {{ category.name }}
                                </Link>
                                <span class="text-sm text-gray-500 mr-2 ml-4"
                                    v-if="page.tags && page.tags.length">标签:</span>
                                <Link v-for="tag in page.tags" :key="tag.id"
                                    :href="route('wiki.index', { tag: tag.slug })" class="tag-tag">
                                {{ tag.name }}
                                </Link>
                            </div>
                        </div>

                        <!-- Status Alerts -->
                        <div v-if="isLocked || page.status === 'conflict'" class="mb-6 space-y-4">
                            <!-- Lock Alert -->
                            <div v-if="isLocked" class="alert-warning">
                                <div class="flex items-center">
                                    <font-awesome-icon :icon="['fas', 'lock']" class="mr-2 flex-shrink-0" />
                                    <p v-if="lockedBy" class="text-sm">该页面当前被 <strong>{{ lockedBy.name }}</strong>
                                        锁定编辑中<span v-if="page.locked_until"> (预计 {{ formatDateTime(page.locked_until) }}
                                            解锁)</span>。</p>
                                    <p v-else class="text-sm">页面已被锁定，无法获取锁定者信息。</p>
                                </div>
                            </div>
                            <!-- Conflict Alert -->
                            <div v-if="page.status === 'conflict'" class="alert-error">
                                <div class="flex items-center">
                                    <font-awesome-icon :icon="['fas', 'exclamation-circle']"
                                        class="mr-2 flex-shrink-0" />
                                    <p class="text-sm">该页面存在编辑冲突，需要管理员解决。
                                        <Link v-if="canResolveConflict" :href="route('wiki.show-conflicts', page.slug)"
                                            class="underline ml-2 font-medium">前往解决</Link>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <!-- Draft Alert -->
                        <div v-if="draft" class="alert-info mb-6">
                            <div class="flex items-center">
                                <font-awesome-icon :icon="['fas', 'save']" class="mr-2 flex-shrink-0" />
                                <p class="text-sm">您有一份未保存的草稿 <span v-if="draft.last_saved_at"> ({{
                                        formatDateTime(draft.last_saved_at) }})</span>
                                    <Link :href="route('wiki.edit', page.slug)" class="ml-2 underline font-medium">继续编辑
                                    </Link>
                                </p>
                            </div>
                        </div>

                        <!-- Page Content -->
                        <div class="prose max-w-none prose-indigo lg:prose-lg xl:prose-xl">
                            <div v-if="currentVersion && currentVersion.content" v-html="currentVersion.content"></div>
                            <div v-else class="text-gray-500 italic py-8 text-center border rounded-lg bg-gray-50 mt-6">
                                <p>{{ error || '该页面还没有内容。' }}</p>
                                <Link v-if="canEditPage" :href="route('wiki.edit', page.slug)"
                                    class="text-blue-600 underline mt-2 inline-block">开始编辑</Link>
                            </div>
                        </div>

                        <!-- Comments Section -->
                        <div class="mt-12 pt-8 border-t border-gray-300">
                            <h3 class="text-2xl font-bold mb-6">评论 ({{ commentsCount }})</h3>
                            <!-- Comment Form -->
                            <div v-if="$page.props.auth.user && $page.props.auth.user.permissions.includes('wiki.comment')"
                                class="mb-8 p-4 bg-gray-50 rounded-lg border">
                                <form @submit.prevent="submitComment">
                                    <textarea v-model="commentForm.content" rows="3"
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                        placeholder="添加你的评论..."></textarea>
                                    <InputError class="mt-1" :message="commentForm.errors.content" />
                                    <div class="flex justify-end mt-2">
                                        <button type="submit" class="btn-primary text-sm"
                                            :disabled="!commentForm.content.trim() || commentForm.processing">
                                            <font-awesome-icon v-if="commentForm.processing" :icon="['fas', 'spinner']"
                                                spin class="mr-1" />
                                            {{ commentForm.processing ? '发布中...' : '发布评论' }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div v-else class="mb-8 p-4 bg-gray-100 rounded-lg text-center text-sm text-gray-600">
                                <Link :href="route('login')" class="text-blue-600 underline">登录</Link>后即可发表评论。
                            </div>

                            <!-- Comments List -->
                            <div v-if="page.comments && page.comments.length" class="space-y-6">
                                <div v-for="comment in page.comments" :key="comment.id" class="comment-item">
                                    <!-- ... (单个评论和回复的结构保持不变) ... -->
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0 rounded-full w-8 h-8 flex items-center justify-center text-sm font-semibold"
                                            :class="getAvatarBgClass(comment.user?.id || 0)">{{
                                            getInitials(comment.user?.name || '访客') }}</div>
                                        <div class="flex-1">
                                            <div class="flex justify-between items-center mb-1">
                                                <div>
                                                    <span class="font-semibold text-sm mr-2">{{ comment.user?.name ||
                                                        '匿名用户' }}</span>
                                                    <span class="text-xs text-gray-500">{{
                                                        formatDateTime(comment.created_at) }}</span>
                                                </div>
                                                <div class="flex space-x-2 comment-actions"
                                                    v-if="$page.props.auth.user">
                                                    <button v-if="canManageComment(comment)"
                                                        @click="editComment(comment)"
                                                        class="btn-comment-action text-blue-600"><font-awesome-icon
                                                            :icon="['fas', 'edit']" class="mr-1" /> 编辑</button>
                                                    <button v-if="canManageComment(comment)"
                                                        @click="deleteComment(comment)"
                                                        class="btn-comment-action text-red-600"><font-awesome-icon
                                                            :icon="['fas', 'trash']" class="mr-1" /> 删除</button>
                                                    <button @click="replyToComment(comment)"
                                                        class="btn-comment-action text-gray-600"><font-awesome-icon
                                                            :icon="['fas', 'reply']" class="mr-1" /> 回复</button>
                                                </div>
                                            </div>
                                            <!-- Edit Comment Form -->
                                            <div v-if="editingCommentId === comment.id" class="mt-2">
                                                <textarea v-model="editCommentForm.content" rows="3"
                                                    class="w-full p-2 border border-gray-300 rounded-lg text-sm"></textarea>
                                                <InputError class="mt-1" :message="editCommentForm.errors.content" />
                                                <div class="flex justify-end mt-2 space-x-2">
                                                    <button @click="cancelEditComment"
                                                        class="btn-secondary text-xs">取消</button>
                                                    <button @click="updateComment(comment)" class="btn-primary text-xs"
                                                        :disabled="!editCommentForm.content.trim() || editCommentForm.processing">
                                                        <font-awesome-icon v-if="editCommentForm.processing"
                                                            :icon="['fas', 'spinner']" spin class="mr-1" />
                                                        {{ editCommentForm.processing ? '更新中...' : '更新' }}
                                                    </button>
                                                </div>
                                            </div>
                                            <p v-else class="text-gray-800 text-sm leading-relaxed">{{ comment.content
                                                }}</p>
                                        </div>
                                    </div>

                                    <!-- Reply Form -->
                                    <div v-if="replyingToCommentId === comment.id"
                                        class="mt-3 ml-11 pl-4 border-l-2 border-gray-200">
                                        <form @submit.prevent="submitReply(comment)">
                                            <textarea v-model="replyForm.content" rows="2"
                                                class="w-full p-2 border rounded-lg text-sm"
                                                placeholder="添加回复..."></textarea>
                                            <InputError class="mt-1" :message="replyForm.errors.content" />
                                            <div class="flex justify-end mt-2 space-x-2">
                                                <button type="button" @click="cancelReply"
                                                    class="btn-secondary text-xs">取消</button>
                                                <button type="submit" class="btn-primary text-xs"
                                                    :disabled="!replyForm.content.trim() || replyForm.processing">
                                                    <font-awesome-icon v-if="replyForm.processing"
                                                        :icon="['fas', 'spinner']" spin class="mr-1" />
                                                    {{ replyForm.processing ? '回复中...' : '回复' }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Replies List -->
                                    <div v-if="comment.replies && comment.replies.length"
                                        class="mt-4 ml-11 pl-4 border-l-2 border-gray-200 space-y-4">
                                        <div v-for="reply in comment.replies" :key="reply.id"
                                            class="flex items-start space-x-3 reply-item">
                                            <!-- ... (回复结构) ... -->
                                            <div class="flex-shrink-0 rounded-full w-6 h-6 flex items-center justify-center text-xs font-semibold"
                                                :class="getAvatarBgClass(reply.user?.id || 0)">{{
                                                getInitials(reply.user?.name || '访客') }}</div>
                                            <div class="flex-1">
                                                <div class="flex justify-between items-center mb-1">
                                                    <div>
                                                        <span class="font-semibold text-xs mr-2">{{ reply.user?.name ||
                                                            '匿名用户' }}</span>
                                                        <span class="text-xs text-gray-500">{{
                                                            formatDateTime(reply.created_at) }}</span>
                                                    </div>
                                                    <div class="flex space-x-2 comment-actions"
                                                        v-if="$page.props.auth.user">
                                                        <button v-if="canManageComment(reply)"
                                                            @click="editComment(reply)"
                                                            class="btn-comment-action text-blue-600"><font-awesome-icon
                                                                :icon="['fas', 'edit']" /> 编辑</button>
                                                        <button v-if="canManageComment(reply)"
                                                            @click="deleteComment(reply)"
                                                            class="btn-comment-action text-red-600"><font-awesome-icon
                                                                :icon="['fas', 'trash']" /> 删除</button>
                                                    </div>
                                                </div>
                                                <!-- Edit Reply Form -->
                                                <div v-if="editingCommentId === reply.id" class="mt-2">
                                                    <textarea v-model="editCommentForm.content" rows="2"
                                                        class="w-full p-2 border rounded-lg text-sm"></textarea>
                                                    <InputError class="mt-1"
                                                        :message="editCommentForm.errors.content" />
                                                    <div class="flex justify-end mt-2 space-x-2">
                                                        <button @click="cancelEditComment"
                                                            class="btn-secondary text-xs">取消</button>
                                                        <button @click="updateComment(reply)"
                                                            class="btn-primary text-xs"
                                                            :disabled="!editCommentForm.content.trim() || editCommentForm.processing">
                                                            <font-awesome-icon v-if="editCommentForm.processing"
                                                                :icon="['fas', 'spinner']" spin class="mr-1" />
                                                            {{ editCommentForm.processing ? '更新中...' : '更新' }}
                                                        </button>
                                                    </div>
                                                </div>
                                                <p v-else class="text-gray-700 text-sm leading-relaxed">{{ reply.content
                                                    }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-gray-500 italic text-center py-8">
                                暂无评论，成为第一个评论者吧！
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="w-full lg:w-1/4 mt-8 lg:mt-0">
                    <div class="bg-white/70 backdrop-blur-sm rounded-lg shadow-lg p-4 sticky top-8">
                        <h3 class="text-lg font-semibold mb-3">页面信息</h3>
                        <p class="text-sm text-gray-600">此处可以放置目录、相关链接等。</p>
                        <div id="toc-container" class="mt-4 text-sm"></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modals -->
        <Modal :show="showResolveConflictModal" @close="closeResolveConflictModal" maxWidth="4xl">
            <!-- Resolve Conflict Modal Content -->
            <div class="p-6">
                <h2 class="text-xl font-semibold mb-4">解决冲突</h2>
                <p class="text-gray-600 mb-4">该页面存在编辑冲突，请选择一个版本或手动合并。</p>
                <Link :href="route('wiki.show-conflicts', page.slug)" class="btn-primary">前往解决冲突页面</Link>
                <button @click="closeResolveConflictModal" class="btn-secondary ml-2">关闭</button>
            </div>
        </Modal>
        <FlashMessage ref="flashMessage" />
    </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'; // 确保 onMounted 被导入
import { Link, router, usePage, useForm } from '@inertiajs/vue3'; // 引入 useForm
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Modal from '@/Components/Modal/Modal.vue';
import FlashMessage from '@/Components/Other/FlashMessage.vue';
import InputError from '@/Components/Other/InputError.vue';
import { formatDate, formatDateShort, formatDateTime } from '@/utils/formatters';
import { mainNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = mainNavigationLinks;
const pageProps = usePage().props;

const props = defineProps({
    page: { type: Object, required: true },
    currentVersion: { type: Object, default: null },
    isLocked: { type: Boolean, default: false },
    lockedBy: { type: Object, default: null },
    draft: { type: Object, default: null },
    canEditPage: { type: Boolean, default: false },
    canResolveConflict: { type: Boolean, default: false },
    error: { type: String, default: '' },
});

const flashMessage = ref(null);
// 移除了 newComment, replyContent, editedCommentContent 的 ref，因为它们现在由 useForm 管理

const replyingToCommentId = ref(null);
const editingCommentId = ref(null);
const showResolveConflictModal = ref(false); // Modal 状态保持 ref

// --- 使用 useForm ---
const commentForm = useForm({
    content: '', // 初始为空
    parent_id: null
});

const replyForm = useForm({
    content: '', // 初始为空
    parent_id: replyingToCommentId // 直接绑定 ref
});

const editCommentForm = useForm({
    content: '', // 初始为空
});
// --- 结束 useForm ---

// --- 头像和名称处理 ---
const userColors = {};
const bgColors = [ /* ... */
    'bg-blue-100 text-blue-700', 'bg-green-100 text-green-700',
    'bg-yellow-100 text-yellow-700', 'bg-purple-100 text-purple-700',
    'bg-pink-100 text-pink-700', 'bg-indigo-100 text-indigo-700',
    'bg-red-100 text-red-700', 'bg-teal-100 text-teal-700'
];
let colorIndex = 0;
const getAvatarBgClass = (userId) => { /* ... */
    if (!userId) return bgColors[0];
    if (!userColors[userId]) {
        userColors[userId] = bgColors[colorIndex % bgColors.length];
        colorIndex++;
    }
    return userColors[userId];
};
const getInitials = (name) => { /* ... */
    if (!name) return '?';
    const nameTrimmed = name.trim();
    if (!nameTrimmed) return '?';
    const chineseMatch = nameTrimmed.match(/[\u4E00-\u9FA5]/);
    if (chineseMatch) return chineseMatch[0];
    const parts = nameTrimmed.split(/\s+/);
    if (parts.length > 0 && parts[0]) return parts[0].charAt(0).toUpperCase();
    return nameTrimmed.charAt(0).toUpperCase() || '?';
};

// --- 计算属性 ---
const commentsCount = computed(() => { /* ... */
    let count = 0;
    if (props.page.comments) {
        props.page.comments.forEach(comment => {
            count++;
            if (comment.replies) count += comment.replies.length;
        });
    }
    return count;
});
const canManageComment = (comment) => { /* ... */
    const user = pageProps.auth.user;
    if (!user) return false;
    return comment.user_id === user.id || user.permissions?.includes('wiki.moderate_comments');
};

// --- 方法 ---
const submitComment = () => {
    commentForm.parent_id = null; // 确保是顶级评论
    commentForm.post(route('wiki.comments.store', props.page.slug), {
        preserveScroll: true,
        onSuccess: () => {
            commentForm.reset('content'); // 只重置内容字段
            flashMessage.value?.addMessage('success', '评论发布成功！');
        },
        onError: (errors) => {
            // 验证错误由 InputError 显示
            // 处理其他错误
            const otherErrors = Object.keys(errors).filter(key => key !== 'content');
            if (otherErrors.length > 0) {
                const errorMsg = Object.values(errors).flat().join(' ') || '评论发布失败';
                flashMessage.value?.addMessage('error', errorMsg);
            }
        }
    });
};

const replyToComment = (comment) => {
    if (replyingToCommentId.value === comment.id) {
        cancelReply();
    } else {
        replyingToCommentId.value = comment.id;
        replyForm.content = ''; // 清空回复内容
        cancelEditComment(); // 关闭编辑状态
    }
};
const cancelReply = () => {
    replyingToCommentId.value = null;
    replyForm.reset('content'); // 重置表单内容
    replyForm.clearErrors(); // 清除错误
};
const submitReply = (parentComment) => {
    replyForm.parent_id = parentComment.id;
    replyForm.post(route('wiki.comments.store', props.page.slug), {
        preserveScroll: true,
        onSuccess: () => {
            cancelReply();
            flashMessage.value?.addMessage('success', '回复成功！');
        },
        onError: (errors) => {
            const otherErrors = Object.keys(errors).filter(key => key !== 'content');
            if (otherErrors.length > 0) {
                const errorMsg = Object.values(errors).flat().join(' ') || '回复失败';
                flashMessage.value?.addMessage('error', errorMsg);
            }
        }
    });
};

const editComment = (comment) => {
    if (editingCommentId.value === comment.id) {
        cancelEditComment();
    } else {
        editingCommentId.value = comment.id;
        editCommentForm.content = comment.content; // 设置表单内容
        cancelReply(); // 关闭回复状态
    }
};
const cancelEditComment = () => {
    editingCommentId.value = null;
    editCommentForm.reset('content');
    editCommentForm.clearErrors();
};
const updateComment = (comment) => {
    editCommentForm.put(route('wiki.comments.update', comment.id), {
        preserveScroll: true,
        onSuccess: () => {
            cancelEditComment();
            flashMessage.value?.addMessage('success', '评论更新成功！');
        },
        onError: (errors) => {
            const otherErrors = Object.keys(errors).filter(key => key !== 'content');
            if (otherErrors.length > 0) {
                const errorMsg = Object.values(errors).flat().join(' ') || '评论更新失败';
                flashMessage.value?.addMessage('error', errorMsg);
            }
        }
    });
};

const deleteComment = (comment) => {
    const typeText = comment.parent_id ? '回复' : '评论';
    if (confirm(`确定要隐藏这条${typeText}吗？`)) { // 修改提示
        router.delete(route('wiki.comments.destroy', comment.id), {
            preserveScroll: true,
            onSuccess: () => {
                flashMessage.value?.addMessage('success', `${typeText}已隐藏！`); // 修改提示
                if (editingCommentId.value === comment.id) cancelEditComment();
                if (replyingToCommentId.value === comment.id || (comment.parent_id && replyingToCommentId.value === comment.parent_id)) cancelReply(); // 修复: 也应取消对父评论的回复状态
            },
            onError: (errors) => {
                // console.error("Delete comment error:", errors); // 调试用
                const errorMsg = errors?.message || `${typeText}删除失败，请重试`;
                flashMessage.value?.addMessage('error', errorMsg);
            }
        });
    }
};

const openResolveConflictModal = () => {
    showResolveConflictModal.value = true;
};
const closeResolveConflictModal = () => {
    showResolveConflictModal.value = false;
};

// 可以在 onMounted 中处理初始错误（如果需要）
onMounted(() => {
    if (props.error) {
        flashMessage.value?.addMessage('error', props.error);
    }
    // 处理 $page.props.errors 可能包含的来自 Controller 的 'general' 错误
    const pageLevelErrors = usePage().props.errors;
    if (pageLevelErrors && pageLevelErrors.general) {
        flashMessage.value?.addMessage('error', pageLevelErrors.general);
    }
});

</script>

<style>
/* 样式保持不变 */
.prose {
    @apply text-gray-700;
}

.prose h1,
.prose h2,
.prose h3,
.prose h4,
.prose h5,
.prose h6 {
    @apply text-gray-900 font-semibold mb-4 mt-6 first:mt-0;
}

.prose p {
    @apply leading-relaxed mb-4;
}

.prose ul,
.prose ol {
    @apply pl-6 mb-4;
}

.prose li>p {
    @apply mb-1;
}

.prose blockquote {
    @apply border-l-4 border-gray-300 pl-4 italic text-gray-600 my-6;
}

.prose pre {
    @apply bg-gray-100 p-4 rounded-md overflow-x-auto my-6 text-sm;
}

.prose code:not(pre code) {
    @apply bg-gray-100 text-red-600 px-1 py-0.5 rounded text-sm;
}

.prose a {
    @apply text-blue-600 hover:underline;
}

.prose img {
    @apply max-w-full h-auto my-6 rounded;
}

.prose table {
    @apply w-full my-6 border-collapse border border-gray-300;
}

.prose th,
.prose td {
    @apply border border-gray-300 px-4 py-2;
}

.prose th {
    @apply bg-gray-100 font-semibold;
}

.tag-category {
    @apply px-3 py-1 bg-gray-200 text-gray-700 text-xs rounded-full hover:bg-gray-300 transition;
}

.tag-tag {
    @apply px-3 py-1 bg-blue-100 text-blue-700 text-xs rounded-full hover:bg-blue-200 transition;
}

.btn-icon-primary {
    @apply inline-flex items-center px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition;
}

.btn-icon-secondary {
    @apply inline-flex items-center px-3 py-1 bg-gray-200 text-gray-700 text-xs rounded hover:bg-gray-300 transition;
}

.btn-icon-warning {
    @apply inline-flex items-center px-3 py-1 bg-yellow-600 text-white text-xs rounded hover:bg-yellow-700 transition;
}

.btn-icon-primary svg,
.btn-icon-secondary svg,
.btn-icon-warning svg {
    @apply mr-1 h-3 w-3;
}

.comment-item {
    @apply transition-colors duration-300;
}

.comment-item:hover .comment-actions {
    opacity: 1;
}

.comment-actions {
    opacity: 0;
    transition: opacity 0.2s ease-in-out;
}

.reply-item {
    padding-left: 0.75rem;
    /* 12px */
}

.btn-comment-action {
    @apply text-xs flex items-center transition-colors duration-150;
}

.btn-comment-action:hover {
    opacity: 0.8;
}

.btn-comment-action svg {
    @apply h-3 w-3;
}

.alert-error {
    @apply bg-red-50 border-l-4 border-red-400 text-red-700 p-4 rounded-md;
}

.alert-warning {
    @apply bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 p-4 rounded-md;
}

.alert-info {
    @apply bg-blue-50 border-l-4 border-blue-400 text-blue-700 p-4 rounded-md;
}
</style>
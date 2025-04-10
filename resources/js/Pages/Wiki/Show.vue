<template>
    <MainLayout :navigationLinks="navigationLinks">
        <div class="container mx-auto py-6 px-4">
            <div class="flex flex-col md:flex-row md:space-x-8">
                <div class="w-full">
                    <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                        <!-- 页面头部信息 (基本不变, 使用 props.page) -->
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h1 class="text-3xl font-bold mb-2">{{ page.title }}</h1>
                                <div class="text-sm text-gray-600 mb-4">
                                    <span v-if="page.creator">由 {{ page.creator.name }} 创建于 {{
                                        formatDate(page.created_at) }}</span>
                                    <span v-else>创建信息不可用</span>
                                    <!-- 使用 props.currentVersion 显示版本信息 -->
                                    <span v-if="currentVersion" class="ml-2">
                                        当前版本: v{{ currentVersion.version_number }}
                                        <span v-if="currentVersion.creator">
                                            由 {{ currentVersion.creator.name }} 编辑于 {{
                                            formatDateTime(currentVersion.created_at) }}
                                        </span>
                                    </span>
                                </div>
                                <!-- 分类和标签 (使用 props.page) -->
                                <div class="flex flex-wrap gap-2">
                                    <Link v-for="category in page.categories" :key="category.id"
                                        :href="route('wiki.index', { category: category.slug })"
                                        class="px-3 py-1 bg-gray-200 text-gray-700 text-sm rounded hover:bg-gray-300">
                                    {{ category.name }}
                                    </Link>
                                    <Link v-for="tag in page.tags" :key="tag.id"
                                        :href="route('wiki.index', { tag: tag.slug })"
                                        class="px-3 py-1 bg-blue-100 text-blue-700 text-sm rounded hover:bg-blue-200">
                                    {{ tag.name }}
                                    </Link>
                                </div>
                            </div>
                            <!-- 操作按钮 (使用 props.canEdit 等) -->
                            <div class="flex items-center space-x-3 flex-shrink-0">
                                <Link :href="route('wiki.history', page.slug)"
                                    class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 flex items-center text-sm">
                                <font-awesome-icon :icon="['fas', 'history']" class="mr-1 h-3 w-3" /> 历史版本
                                </Link>
                                <Link v-if="canEdit && !isLocked" :href="route('wiki.edit', page.slug)"
                                    class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 flex items-center text-sm">
                                <font-awesome-icon :icon="['fas', 'edit']" class="mr-1 h-3 w-3" /> 编辑
                                </Link>
                                <button v-if="canResolveConflict && page.status === 'conflict'"
                                    @click="openResolveConflictModal"
                                    class="px-3 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700 flex items-center text-sm">
                                    <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="mr-1 h-3 w-3" />
                                    解决冲突
                                </button>
                            </div>
                        </div>

                        <!-- 锁定和冲突提示 (使用 props.isLocked, props.page.status 等) -->
                        <div v-if="isLocked || page.status === 'conflict'" class="mb-6 space-y-4">
                            <div v-if="isLocked"
                                class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-md">
                                <div class="flex items-center">
                                    <font-awesome-icon :icon="['fas', 'lock']" class="mr-2" />
                                    <p v-if="lockedBy">
                                        该页面正在被 <strong>{{ lockedBy.name }}</strong> 编辑
                                        <span v-if="page.locked_until">，预计 {{ formatDateTime(page.locked_until) }}
                                            解锁</span>。
                                    </p>
                                    <p v-else>页面已被锁定，但无法获取锁定者信息。</p>
                                </div>
                            </div>
                            <div v-if="page.status === 'conflict'"
                                class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md">
                                <div class="flex items-center">
                                    <font-awesome-icon :icon="['fas', 'exclamation-circle']" class="mr-2" />
                                    <p>
                                        该页面存在编辑冲突，需要管理员解决。
                                        <Link v-if="canResolveConflict" :href="route('wiki.show-conflicts', page.slug)"
                                            class="underline ml-2 font-medium">
                                        前往解决
                                        </Link>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- 草稿提示 (使用 props.draft) -->
                        <div v-if="draft"
                            class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded-md">
                            <div class="flex items-center">
                                <font-awesome-icon :icon="['fas', 'save']" class="mr-2" />
                                <p>
                                    您有一份未保存的草稿 <span v-if="draft.last_saved_at">（{{ formatDateTime(draft.last_saved_at)
                                        }}）</span>
                                    <Link :href="route('wiki.edit', page.slug)" class="ml-2 underline font-medium">继续编辑
                                    </Link>
                                </p>
                            </div>
                        </div>

                        <!-- 核心内容显示区域 -->
                        <div class="prose max-w-none">
                            <!-- 直接检查 props.currentVersion 和其 content -->
                            <div v-if="currentVersion && currentVersion.content" v-html="currentVersion.content"></div>
                            <div v-else class="text-gray-500 italic py-8 text-center border rounded-lg bg-gray-50">
                                <!-- 使用 props.error 显示后端错误，否则显示默认文字 -->
                                <p>{{ error || '该页面还没有内容。' }}</p>
                                <Link v-if="canEdit" :href="route('wiki.edit', page.slug)"
                                    class="text-blue-600 underline mt-2 inline-block">开始编辑</Link>
                            </div>
                        </div>

                        <!-- 评论区 (使用 props.page.comments) -->
                        <div class="mt-10 pt-6 border-t border-gray-200">
                            <h3 class="text-xl font-bold mb-4">评论 ({{ commentsCount }})</h3>
                            <!-- 评论表单 -->
                            <div v-if="$page.props.auth.user && $page.props.auth.user.permissions.includes('wiki.comment')"
                                class="mb-6">
                                <form @submit.prevent="submitComment">
                                    <textarea v-model="newComment" rows="3"
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="添加评论..."></textarea>
                                    <div class="flex justify-end mt-2">
                                        <button type="submit"
                                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
                                            :disabled="!newComment.trim()">发布评论</button>
                                    </div>
                                </form>
                            </div>
                            <!-- 评论列表 -->
                            <div v-if="page.comments && page.comments.length" class="space-y-6">
                                <div v-for="comment in page.comments" :key="comment.id"
                                    class="bg-gray-50 p-4 rounded-lg border">
                                    <!-- 评论内容和操作 -->
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-center mb-2">
                                            <span class="font-medium mr-2">{{ comment.user.name }}</span>
                                            <span class="text-xs text-gray-500">{{ formatDateTime(comment.created_at)
                                                }}</span>
                                        </div>
                                        <!-- 评论操作按钮 -->
                                        <div class="flex space-x-2" v-if="$page.props.auth.user">
                                            <button v-if="canManageComment(comment)" @click="editComment(comment)"
                                                class="text-blue-600 hover:text-blue-800 text-xs">
                                                <font-awesome-icon :icon="['fas', 'edit']" /> 编辑
                                            </button>
                                            <button v-if="canManageComment(comment)" @click="deleteComment(comment)"
                                                class="text-red-600 hover:text-red-800 text-xs">
                                                <font-awesome-icon :icon="['fas', 'trash']" /> 删除
                                            </button>
                                            <button @click="replyToComment(comment)"
                                                class="text-gray-600 hover:text-gray-800 text-xs">
                                                <font-awesome-icon :icon="['fas', 'reply']" /> 回复
                                            </button>
                                        </div>
                                    </div>
                                    <!-- 编辑评论 -->
                                    <div v-if="editingCommentId === comment.id" class="mt-2">
                                        <textarea v-model="editedCommentContent" rows="3"
                                            class="w-full p-2 border border-gray-300 rounded-lg text-sm"></textarea>
                                        <div class="flex justify-end mt-2 space-x-2">
                                            <button @click="cancelEditComment"
                                                class="px-3 py-1 text-xs bg-gray-200 rounded">取消</button>
                                            <button @click="updateComment(comment)"
                                                class="px-3 py-1 text-xs bg-blue-600 text-white rounded"
                                                :disabled="!editedCommentContent.trim()">更新</button>
                                        </div>
                                    </div>
                                    <!-- 显示评论内容 -->
                                    <p v-else class="text-gray-800 text-sm">{{ comment.content }}</p>

                                    <!-- 回复输入框 -->
                                    <div v-if="replyingToCommentId === comment.id" class="mt-3 ml-4 pl-4 border-l-2">
                                        <form @submit.prevent="submitReply(comment)">
                                            <textarea v-model="replyContent" rows="2"
                                                class="w-full p-2 border rounded-lg text-sm"
                                                placeholder="添加回复..."></textarea>
                                            <div class="flex justify-end mt-2 space-x-2">
                                                <button type="button" @click="cancelReply"
                                                    class="px-2 py-1 text-xs bg-gray-200 rounded">取消</button>
                                                <button type="submit"
                                                    class="px-2 py-1 text-xs bg-blue-600 text-white rounded"
                                                    :disabled="!replyContent.trim()">回复</button>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- 显示回复列表 -->
                                    <div v-if="comment.replies && comment.replies.length"
                                        class="mt-4 ml-6 pl-6 border-l-2 border-gray-200 space-y-4">
                                        <div v-for="reply in comment.replies" :key="reply.id"
                                            class="bg-gray-100 p-3 rounded">
                                            <!-- 回复内容和操作 -->
                                            <div class="flex justify-between items-start">
                                                <div class="flex items-center mb-1">
                                                    <span class="font-medium mr-2 text-sm">{{ reply.user.name }}</span>
                                                    <span class="text-xs text-gray-500">{{
                                                        formatDateTime(reply.created_at) }}</span>
                                                </div>
                                                <div class="flex space-x-2" v-if="$page.props.auth.user">
                                                    <button v-if="canManageComment(reply)" @click="editComment(reply)"
                                                        class="text-blue-600 hover:text-blue-800 text-xs"><font-awesome-icon
                                                            :icon="['fas', 'edit']" /> 编辑</button>
                                                    <button v-if="canManageComment(reply)" @click="deleteComment(reply)"
                                                        class="text-red-600 hover:text-red-800 text-xs"><font-awesome-icon
                                                            :icon="['fas', 'trash']" /> 删除</button>
                                                </div>
                                            </div>
                                            <!-- 编辑回复 -->
                                            <div v-if="editingCommentId === reply.id" class="mt-2">
                                                <textarea v-model="editedCommentContent" rows="2"
                                                    class="w-full p-2 border rounded-lg text-sm"></textarea>
                                                <div class="flex justify-end mt-2 space-x-2">
                                                    <button @click="cancelEditComment"
                                                        class="px-2 py-1 text-xs bg-gray-200 rounded">取消</button>
                                                    <button @click="updateComment(reply)"
                                                        class="px-2 py-1 text-xs bg-blue-600 text-white rounded"
                                                        :disabled="!editedCommentContent.trim()">更新</button>
                                                </div>
                                            </div>
                                            <!-- 显示回复内容 -->
                                            <p v-else class="text-gray-700 text-sm">{{ reply.content }}</p>
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
            </div>
        </div>

        <!-- 解决冲突模态框 -->
        <Modal :show="showResolveConflictModal" @close="closeResolveConflictModal" maxWidth="4xl">
            <div class="p-6">
                <h2 class="text-xl font-bold mb-4">解决编辑冲突</h2>
                <p class="mb-4 text-gray-600">请直接前往
                    <Link :href="route('wiki.show-conflicts', page.slug)" class="text-blue-600 underline">冲突解决页面</Link>
                    查看差异并合并内容。
                </p>
                <div class="flex justify-end">
                    <button @click="closeResolveConflictModal"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">关闭</button>
                </div>
            </div>
        </Modal>

        <FlashMessage ref="flashMessage" />
    </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Modal from '@/Components/Modal/Modal.vue';
import FlashMessage from '@/Components/Other/FlashMessage.vue';
import { formatDate, formatDateTime } from '@/utils/formatters';
import { mainNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = mainNavigationLinks;
const pageProps = usePage().props;

const props = defineProps({
    page: { type: Object, required: true },
    currentVersion: { type: Object, default: null }, // 接收明确传递的 currentVersion
    isLocked: { type: Boolean, default: false },
    lockedBy: { type: Object, default: null },
    draft: { type: Object, default: null },
    canEdit: { type: Boolean, default: false },
    canResolveConflict: { type: Boolean, default: false },
    error: { type: String, default: '' }, // 接收错误信息
});

const flashMessage = ref(null);
const newComment = ref('');
const replyingToCommentId = ref(null);
const replyContent = ref('');
const editingCommentId = ref(null);
const editedCommentContent = ref('');
const showResolveConflictModal = ref(false);

// 计算评论总数（现在基于 props.page.comments）
const commentsCount = computed(() => {
    let count = 0;
    if (props.page.comments) {
        props.page.comments.forEach(comment => {
            count++; // Top-level comment
            if (comment.replies) {
                count += comment.replies.length; // Replies
            }
        });
    }
    return count;
});

// 判断用户是否有权管理评论（编辑/删除）
const canManageComment = (comment) => {
    const user = pageProps.auth.user;
    if (!user) return false;
    return comment.user_id === user.id || user.permissions?.includes('wiki.moderate_comments');
};


// 提交顶级评论
const submitComment = () => {
    router.post(route('wiki.comments.store', props.page.slug), {
        content: newComment.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            newComment.value = '';
            flashMessage.value?.addMessage('success', '评论发布成功！');
        },
        onError: (errors) => {
            flashMessage.value?.addMessage('error', errors.content || '评论发布失败');
        }
    });
};

// 点击回复按钮
const replyToComment = (comment) => {
    replyingToCommentId.value = comment.id;
    replyContent.value = ''; // 清空回复内容
};

// 取消回复
const cancelReply = () => {
    replyingToCommentId.value = null;
    replyContent.value = '';
};

// 提交回复
const submitReply = (parentComment) => {
    router.post(route('wiki.comments.store', props.page.slug), {
        content: replyContent.value,
        parent_id: parentComment.id
    }, {
        preserveScroll: true,
        onSuccess: () => {
            replyingToCommentId.value = null;
            replyContent.value = '';
            flashMessage.value?.addMessage('success', '回复成功！');
        },
        onError: (errors) => {
            flashMessage.value?.addMessage('error', errors.content || '回复失败');
        }
    });
};

// 点击编辑按钮
const editComment = (comment) => {
    editingCommentId.value = comment.id;
    editedCommentContent.value = comment.content;
    replyingToCommentId.value = null; // 取消回复状态
};

// 取消编辑
const cancelEditComment = () => {
    editingCommentId.value = null;
    editedCommentContent.value = '';
};

// 更新评论/回复
const updateComment = (comment) => {
    router.put(route('wiki.comments.update', comment.id), {
        content: editedCommentContent.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            editingCommentId.value = null;
            editedCommentContent.value = '';
            flashMessage.value?.addMessage('success', '评论更新成功！');
        },
        onError: (errors) => {
            flashMessage.value?.addMessage('error', errors.content || '评论更新失败');
        }
    });
};


// 删除评论/回复
const deleteComment = (comment) => {
    if (confirm(`确定要删除这条${comment.parent_id ? '回复' : '评论'}吗？此操作不可恢复。`)) {
        router.delete(route('wiki.comments.destroy', comment.id), {
            preserveScroll: true,
            onSuccess: () => {
                flashMessage.value?.addMessage('success', '评论已删除！');
                // 如果删除的是正在编辑的评论，则取消编辑状态
                if (editingCommentId.value === comment.id) {
                    cancelEditComment();
                }
                // 如果删除的是正在回复的评论的回复，或者顶级评论本身，可能需要取消回复状态
                // （更复杂的逻辑可能需要判断父级等，暂时简化处理）
                if (replyingToCommentId.value === comment.parent_id || replyingToCommentId.value === comment.id) {
                    cancelReply();
                }

            },
            onError: () => {
                flashMessage.value?.addMessage('error', '删除评论失败');
            }
        });
    }
};


// 打开解决冲突模态框
const openResolveConflictModal = () => {
    showResolveConflictModal.value = true;
};

// 关闭解决冲突模态框
const closeResolveConflictModal = () => {
    showResolveConflictModal.value = false;
};

</script>

<style>
/* 保留之前的样式 */
.prose img {
    max-width: 100%;
    height: auto;
}

.prose table {
    width: 100%;
}

.prose td,
.prose th {
    border: 1px solid #e2e8f0;
    padding: 8px 12px;
}

.prose th {
    background-color: #f1f5f9;
}
</style>
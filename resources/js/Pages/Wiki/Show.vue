<template>
    <MainLayout :navigationLinks="navigationLinks">
        <div class="container mx-auto py-6 px-4">
            <div class="flex flex-col md:flex-row md:space-x-8">
                <!-- 添加左侧侧边栏，包含页面树 -->
                <div class="w-full md:w-1/4 mb-6 md:mb-0">
                    <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-4 mb-6">
                        <PageTree :current-page-id="page.id" />
                    </div>
                    <!-- 其他侧边栏内容可以放在这里 -->
                </div>

                <!-- 右侧主要内容区域 -->
                <div class="w-full md:w-3/4">
                    <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                        <!-- 页面标题与操作 -->
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h1 class="text-3xl font-bold mb-2">{{ page.title }}</h1>
                                <div class="text-sm text-gray-600 mb-4">
                                    <span>由 {{ page.creator.name }} 创建于 {{ formatDate(page.created_at) }}</span>
                                    <span v-if="page.currentVersion">当前版本: {{ page.currentVersion.version_number
                                        }}</span>
                                </div>

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

                            <div class="flex items-center space-x-3">
                                <Link :href="route('wiki.history', page.slug)"
                                    class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 flex items-center">
                                <font-awesome-icon :icon="['fas', 'history']" class="mr-2" />
                                历史版本
                                </Link>

                                <Link v-if="canEdit && !isLocked" :href="route('wiki.edit', page.slug)"
                                    class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 flex items-center">
                                <font-awesome-icon :icon="['fas', 'edit']" class="mr-2" />
                                编辑
                                </Link>

                                <button v-if="canResolveConflict && page.status === 'conflict'"
                                    @click="showResolveConflictModal = true"
                                    class="px-3 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700 flex items-center">
                                    <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="mr-2" />
                                    解决冲突
                                </button>
                            </div>
                        </div>
                        <div v-if="page.template_id && page.meta" class="bg-gray-50 p-4 rounded-lg mb-6 border">
                            <h3 class="text-lg font-medium mb-3">{{ templateName }}</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="(field, index) in templateFields" :key="index" class="border-b pb-2">
                                    <div class="text-sm font-medium text-gray-700">{{ field.label }}</div>
                                    <div v-if="field.type === 'select'" class="mt-1">
                                        {{ getOptionLabel(field, page.meta[field.name]) }}
                                    </div>
                                    <div v-else-if="field.type === 'textarea'" class="mt-1 whitespace-pre-wrap">
                                        {{ page.meta[field.name] || '-' }}
                                    </div>
                                    <div v-else class="mt-1">
                                        {{ page.meta[field.name] || '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- 锁定或冲突警告 -->
                        <div v-if="isLocked || page.status === 'conflict'" class="mb-6">
                            <div v-if="isLocked" class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4">
                                <div class="flex items-center">
                                    <font-awesome-icon :icon="['fas', 'lock']" class="mr-2" />
                                    <p>
                                        该页面正在被 <strong>{{ lockedBy.name }}</strong> 编辑，
                                        将于 {{ formatDateTime(page.locked_until) }} 解锁
                                    </p>
                                </div>
                            </div>

                            <div v-if="page.status === 'conflict'"
                                class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4">
                                <div class="flex items-center">
                                    <font-awesome-icon :icon="['fas', 'exclamation-circle']" class="mr-2" />
                                    <p>
                                        该页面存在编辑冲突，需要管理员解决
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- 草稿提示 -->
                        <div v-if="draft" class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6">
                            <div class="flex items-center">
                                <font-awesome-icon :icon="['fas', 'save']" class="mr-2" />
                                <p>
                                    您有一份未保存的草稿（{{ formatDateTime(draft.last_saved_at) }}）
                                    <Link :href="route('wiki.edit', page.slug)" class="ml-2 underline">
                                    继续编辑
                                    </Link>
                                </p>
                            </div>
                        </div>

                        <!-- 页面内容 -->
                        <div v-if="page.currentVersion" class="prose max-w-none">
                            <div v-html="page.currentVersion.content"></div>
                        </div>
                        <div v-else class="text-gray-500 italic">
                            该页面还没有内容
                        </div>

                        <!-- 评论区 -->
                        <div class="mt-10 pt-6 border-t border-gray-200">
                            <h3 class="text-xl font-bold mb-4">评论 ({{ page.comments ? page.comments.length : 0 }})</h3>

                            <!-- 添加新评论 -->
                            <div v-if="$page.props.auth.user && $page.props.auth.user.permissions.includes('wiki.comment')"
                                class="mb-6">
                                <form @submit.prevent="submitComment">
                                    <textarea v-model="newComment" rows="3"
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="添加评论..."></textarea>
                                    <div class="flex justify-end mt-2">
                                        <button type="submit"
                                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
                                            :disabled="!newComment.trim()">
                                            发布评论
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- 评论列表 -->
                            <div v-if="page.comments && page.comments.length" class="space-y-6">
                                <div v-for="comment in page.comments" :key="comment.id"
                                    class="bg-gray-50 p-4 rounded-lg">
                                    <div class="flex justify-between">
                                        <div class="flex items-center mb-2">
                                            <div class="font-medium">{{ comment.user.name }}</div>
                                            <span class="mx-2 text-gray-500">•</span>
                                            <div class="text-sm text-gray-500">{{ formatDateTime(comment.created_at) }}
                                            </div>
                                        </div>

                                        <div class="flex space-x-2">
                                            <button
                                                v-if="$page.props.auth.user && (comment.user_id === $page.props.auth.user.id || $page.props.auth.user.permissions.includes('wiki.moderate_comments'))"
                                                @click="editComment(comment)" class="text-blue-600 hover:text-blue-800">
                                                <font-awesome-icon :icon="['fas', 'edit']" />
                                            </button>

                                            <button
                                                v-if="$page.props.auth.user && (comment.user_id === $page.props.auth.user.id || $page.props.auth.user.permissions.includes('wiki.moderate_comments'))"
                                                @click="deleteComment(comment)" class="text-red-600 hover:text-red-800">
                                                <font-awesome-icon :icon="['fas', 'trash']" />
                                            </button>
                                        </div>
                                    </div>

                                    <!-- 评论内容 -->
                                    <div v-if="comment.id !== editingCommentId" class="mt-2">
                                        {{ comment.content }}
                                    </div>

                                    <!-- 编辑评论 -->
                                    <div v-else class="mt-2">
                                        <textarea v-model="editedCommentContent" rows="3"
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                                        <div class="flex justify-end mt-2 space-x-2">
                                            <button @click="cancelEditComment"
                                                class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                                取消
                                            </button>
                                            <button @click="updateComment(comment)"
                                                class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
                                                :disabled="!editedCommentContent.trim()">
                                                更新
                                            </button>
                                        </div>
                                    </div>

                                    <!-- 回复列表 -->
                                    <div v-if="comment.replies && comment.replies.length"
                                        class="mt-4 pl-6 border-l-2 border-gray-200 space-y-4">
                                        <div v-for="reply in comment.replies" :key="reply.id"
                                            class="bg-gray-100 p-3 rounded">
                                            <div class="flex justify-between">
                                                <div class="flex items-center mb-2">
                                                    <div class="font-medium">{{ reply.user.name }}</div>
                                                    <span class="mx-2 text-gray-500">•</span>
                                                    <div class="text-sm text-gray-500">{{
                                                        formatDateTime(reply.created_at) }}
                                                    </div>
                                                </div>

                                                <div class="flex space-x-2">
                                                    <button
                                                        v-if="$page.props.auth.user && (reply.user_id === $page.props.auth.user.id || $page.props.auth.user.permissions.includes('wiki.moderate_comments'))"
                                                        @click="editComment(reply)"
                                                        class="text-blue-600 hover:text-blue-800">
                                                        <font-awesome-icon :icon="['fas', 'edit']" />
                                                    </button>

                                                    <button
                                                        v-if="$page.props.auth.user && (reply.user_id === $page.props.auth.user.id || $page.props.auth.user.permissions.includes('wiki.moderate_comments'))"
                                                        @click="deleteComment(reply)"
                                                        class="text-red-600 hover:text-red-800">
                                                        <font-awesome-icon :icon="['fas', 'trash']" />
                                                    </button>
                                                </div>
                                            </div>

                                            <div v-if="reply.id !== editingCommentId" class="mt-2">
                                                {{ reply.content }}
                                            </div>

                                            <div v-else class="mt-2">
                                                <textarea v-model="editedCommentContent" rows="2"
                                                    class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                                                <div class="flex justify-end mt-2 space-x-2">
                                                    <button @click="cancelEditComment"
                                                        class="px-2 py-1 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                                        取消
                                                    </button>
                                                    <button @click="updateComment(reply)"
                                                        class="px-2 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
                                                        :disabled="!editedCommentContent.trim()">
                                                        更新
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 添加回复 -->
                                    <div v-if="$page.props.auth.user && $page.props.auth.user.permissions.includes('wiki.comment')"
                                        class="mt-3">
                                        <button v-if="replyingToCommentId !== comment.id"
                                            @click="replyToComment(comment)"
                                            class="text-sm text-blue-600 hover:text-blue-800">
                                            回复
                                        </button>

                                        <div v-else class="mt-2">
                                            <textarea v-model="replyContent" rows="2"
                                                class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                placeholder="添加回复..."></textarea>
                                            <div class="flex justify-end mt-2 space-x-2">
                                                <button @click="cancelReply"
                                                    class="px-2 py-1 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                                    取消
                                                </button>
                                                <button @click="submitReply(comment)"
                                                    class="px-2 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
                                                    :disabled="!replyContent.trim()">
                                                    回复
                                                </button>
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
            </div>
        </div>

        <!-- 解决冲突模态框 -->
        <Modal :show="showResolveConflictModal" @close="showResolveConflictModal = false" maxWidth="4xl">
            <div class="p-6">
                <h2 class="text-xl font-bold mb-4">解决编辑冲突</h2>
                <p class="mb-4 text-gray-600">请查看冲突版本，手动合并内容后提交解决方案。</p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            合并后的内容
                        </label>
                        <Editor v-model="resolvedContent" placeholder="请输入合并后的内容..." />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            解决说明
                        </label>
                        <textarea v-model="resolutionComment" rows="2"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="请描述如何解决此冲突..."></textarea>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button @click="showResolveConflictModal = false"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                            取消
                        </button>
                        <button @click="resolveConflict"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                            :disabled="!resolvedContent.trim()">
                            解决冲突
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
    </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Modal from '@/Components/Modal/Modal.vue';
import Editor from '@/Components/Wiki/Editor.vue';
import { formatDate, formatDateTime } from '@/utils/formatters';
import axios from 'axios';
import PageTree from '@/Components/Wiki/PageTree.vue';
import { mainNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = mainNavigationLinks;

const templateName = computed(() => {
    if (page.template_id) {
        const template = props.templates?.find(t => t.id === page.template_id);
        return template ? template.name : '使用模板';
    }
    return '';
});

const templateFields = computed(() => {
    if (page.template_id && props.templates) {
        const template = props.templates.find(t => t.id === page.template_id);
        return template ? template.structure : [];
    }
    return [];
});

const getOptionLabel = (field, value) => {
    if (!field.options || !value) return '-';

    const option = field.options.find(opt => opt.value === value);
    return option ? option.label : value;
};

const props = defineProps({
    page: {
        type: Object,
        required: true
    },
    isLocked: {
        type: Boolean,
        default: false
    },
    lockedBy: {
        type: Object,
        default: null
    },
    draft: {
        type: Object,
        default: null
    },
    canEdit: {
        type: Boolean,
        default: false
    },
    canResolveConflict: {
        type: Boolean,
        default: false
    }
});

// 评论功能
const newComment = ref('');
const replyingToCommentId = ref(null);
const replyContent = ref('');
const editingCommentId = ref(null);
const editedCommentContent = ref('');

// 解决冲突
const showResolveConflictModal = ref(false);
const resolvedContent = ref(props.page.currentVersion?.content || '');
const resolutionComment = ref('');

// 提交评论
const submitComment = () => {
    router.post(route('wiki.comments.store', props.page.slug), {
        content: newComment.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            newComment.value = '';
        }
    });
};

// 开始回复
const replyToComment = (comment) => {
    replyingToCommentId.value = comment.id;
    replyContent.value = '';
};

// 取消回复
const cancelReply = () => {
    replyingToCommentId.value = null;
    replyContent.value = '';
};

// 提交回复
const submitReply = (comment) => {
    router.post(route('wiki.comments.store', props.page.slug), {
        content: replyContent.value,
        parent_id: comment.id
    }, {
        preserveScroll: true,
        onSuccess: () => {
            replyingToCommentId.value = null;
            replyContent.value = '';
        }
    });
};

// 编辑评论
const editComment = (comment) => {
    editingCommentId.value = comment.id;
    editedCommentContent.value = comment.content;
};

// 取消编辑评论
const cancelEditComment = () => {
    editingCommentId.value = null;
    editedCommentContent.value = '';
};

// 更新评论
const updateComment = (comment) => {
    router.put(route('wiki.comments.update', comment.id), {
        content: editedCommentContent.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            editingCommentId.value = null;
            editedCommentContent.value = '';
        }
    });
};

// 删除评论
const deleteComment = (comment) => {
    if (confirm('确定要删除这条评论吗？')) {
        router.delete(route('wiki.comments.destroy', comment.id), {
            preserveScroll: true
        });
    }
};

// 解决冲突
const resolveConflict = () => {
    router.post(route('wiki.resolve-conflict', props.page.slug), {
        content: resolvedContent.value,
        resolution_comment: resolutionComment.value
    }, {
        onSuccess: () => {
            showResolveConflictModal.value = false;
        }
    });
};
</script>
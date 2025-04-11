<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head :title="page.title" />
        <div class="container mx-auto py-8 px-4 md:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row lg:space-x-8">
                <!-- 主要内容区域 -->
                <div class="w-full lg:w-3/4">
                    <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6 md:p-8">
                        <!-- 页面标题和元信息 -->
                        <div class="mb-8 pb-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex flex-col md:flex-row justify-between md:items-start mb-4 gap-4">
                                <div>
                                    <h1
                                        class="text-3xl md:text-4xl font-bold mb-2 leading-tight text-gray-900 dark:text-gray-100">
                                        {{ page.title }}</h1>
                                    <div
                                        class="text-sm text-gray-500 dark:text-gray-400 flex flex-wrap items-center gap-x-4 gap-y-1">
                                        <span v-if="page.creator" class="whitespace-nowrap flex items-center">
                                            <font-awesome-icon :icon="['fas', 'user']" class="mr-1.5 w-3 h-3" /> 由 {{
                                            page.creator.name }} 创建于 {{ formatDateShort(page.created_at) }}
                                        </span>
                                        <span v-if="currentVersion" class="whitespace-nowrap flex items-center">
                                            <font-awesome-icon :icon="['fas', 'edit']" class="mr-1.5 w-3 h-3" /> 最新 v{{
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
                            <div class="flex flex-wrap gap-2 mt-3 items-center">
                                <span class="text-sm text-gray-500 dark:text-gray-400 mr-2">分类:</span>
                                <Link v-for="category in page.categories" :key="category.id"
                                    :href="route('wiki.index', { category: category.slug })" class="tag-category">
                                {{ category.name }}
                                </Link>
                                <span class="text-sm text-gray-500 dark:text-gray-400 mr-2 ml-4"
                                    v-if="page.tags && page.tags.length > 0">标签:</span>
                                <Link v-for="tag in page.tags" :key="tag.id"
                                    :href="route('wiki.index', { tag: tag.slug })" class="tag-tag">
                                {{ tag.name }}
                                </Link>
                            </div>
                        </div>

                        <!-- 锁定/冲突/草稿 提示信息 -->
                        <div v-if="isLocked || page.status === 'conflict'" class="mb-6 space-y-4">
                            <div v-if="isLocked" class="alert-warning">
                                <div class="flex items-center">
                                    <font-awesome-icon :icon="['fas', 'lock']" class="mr-2 flex-shrink-0" />
                                    <p v-if="lockedBy" class="text-sm">该页面当前被 <strong>{{ lockedBy.name }}</strong>
                                        锁定编辑中<span v-if="page.locked_until"> (预计 {{ formatDateTime(page.locked_until) }}
                                            解锁)</span>。</p>
                                    <p v-else class="text-sm">页面已被锁定，无法获取锁定者信息。</p>
                                </div>
                            </div>
                            <div v-if="page.status === 'conflict'" class="alert-error">
                                <div class="flex items-center">
                                    <font-awesome-icon :icon="['fas', 'exclamation-circle']"
                                        class="mr-2 flex-shrink-0" />
                                    <p class="text-sm">该页面存在编辑冲突，需要管理员解决。
                                        <Link v-if="canResolveConflict" :href="route('wiki.show-conflicts', page.slug)"
                                            class="underline ml-2 font-medium hover:text-red-800 dark:hover:text-red-300">
                                        前往解决
                                        </Link>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div v-if="draft" class="alert-info mb-6">
                            <div class="flex items-center">
                                <font-awesome-icon :icon="['fas', 'save']" class="mr-2 flex-shrink-0" />
                                <p class="text-sm">您有一份未保存的草稿 <span v-if="draft.last_saved_at"> ({{
                                        formatDateTime(draft.last_saved_at) }})</span>
                                    <Link :href="route('wiki.edit', page.slug)"
                                        class="ml-2 underline font-medium hover:text-blue-800 dark:hover:text-blue-300">
                                    继续编辑
                                    </Link>
                                </p>
                            </div>
                        </div>

                        <!-- Wiki 内容渲染区域 -->
                        <div
                            class="prose max-w-none prose-indigo lg:prose-lg xl:prose-xl wiki-content-display dark:prose-invert">
                            <!-- 使用 ref 绑定这个容器 -->
                            <div ref="wikiContentContainerRef" v-if="currentVersion && currentVersion.content"
                                v-html="currentVersion.content"></div>
                            <div v-else
                                class="text-gray-500 dark:text-gray-400 italic py-8 text-center border rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-gray-700 mt-6">
                                <p>{{ error || '该页面还没有内容。' }}</p>
                                <Link v-if="canEditPage" :href="route('wiki.edit', page.slug)"
                                    class="text-blue-600 dark:text-blue-400 underline mt-2 inline-block hover:text-blue-800 dark:hover:text-blue-300">
                                开始编辑</Link>
                            </div>
                        </div>

                        <!-- 评论区 -->
                        <div class="mt-12 pt-8 border-t border-gray-300 dark:border-gray-700">
                            <h3 class="text-2xl font-bold mb-6 text-gray-800 dark:text-gray-200">评论 ({{ commentsCount
                                }})</h3>

                            <!-- 发表评论表单 -->
                            <div v-if="$page.props.auth.user && $page.props.auth.user.permissions.includes('wiki.comment')"
                                class="mb-8 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg border dark:border-gray-700">
                                <form @submit.prevent="submitComment">
                                    <textarea v-model="commentForm.content" rows="3"
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400"
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
                            <div v-else
                                class="mb-8 p-4 bg-gray-100 dark:bg-gray-700/50 rounded-lg text-center text-sm text-gray-600 dark:text-gray-400">
                                <Link :href="route('login')"
                                    class="text-blue-600 dark:text-blue-400 underline hover:text-blue-800 dark:hover:text-blue-300">
                                登录</Link>后即可发表评论。
                            </div>

                            <!-- 评论列表 -->
                            <div v-if="page.comments && page.comments.length > 0" class="space-y-6">
                                <div v-for="comment in page.comments" :key="comment.id"
                                    class="comment-item pb-4 border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                                    <div class="flex items-start space-x-3">
                                        <!-- 头像 -->
                                        <div class="flex-shrink-0 rounded-full w-8 h-8 flex items-center justify-center text-sm font-semibold"
                                            :class="getAvatarBgClass(comment.user?.id || 0)">{{
                                            getInitials(comment.user?.name || '访客') }}</div>
                                        <!-- 评论内容 -->
                                        <div class="flex-1">
                                            <div class="flex justify-between items-center mb-1">
                                                <div>
                                                    <span
                                                        class="font-semibold text-sm mr-2 text-gray-800 dark:text-gray-200">{{
                                                        comment.user?.name || '匿名用户' }}</span>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{
                                                        formatDateTime(comment.created_at) }}</span>
                                                </div>
                                                <div class="flex space-x-2 comment-actions"
                                                    v-if="$page.props.auth.user">
                                                    <button v-if="canManageComment(comment)"
                                                        @click="editComment(comment)"
                                                        class="btn-comment-action text-blue-600 dark:text-blue-400"><font-awesome-icon
                                                            :icon="['fas', 'edit']" class="mr-1" /> 编辑</button>
                                                    <button v-if="canManageComment(comment)"
                                                        @click="deleteComment(comment)"
                                                        class="btn-comment-action text-red-600 dark:text-red-400"><font-awesome-icon
                                                            :icon="['fas', 'trash']" class="mr-1" /> 删除</button>
                                                    <button @click="replyToComment(comment)"
                                                        class="btn-comment-action text-gray-600 dark:text-gray-400"><font-awesome-icon
                                                            :icon="['fas', 'reply']" class="mr-1" /> 回复</button>
                                                </div>
                                            </div>
                                            <!-- 编辑评论表单 -->
                                            <div v-if="editingCommentId === comment.id" class="mt-2">
                                                <textarea v-model="editCommentForm.content" rows="3"
                                                    class="w-full p-2 border border-gray-300 rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400"></textarea>
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
                                            <!-- 显示评论内容 -->
                                            <p v-else class="text-gray-800 dark:text-gray-300 text-sm leading-relaxed">
                                                {{ comment.content }}</p>
                                        </div>
                                    </div>

                                    <!-- 回复评论表单 -->
                                    <div v-if="replyingToCommentId === comment.id"
                                        class="mt-3 ml-11 pl-4 border-l-2 border-gray-200 dark:border-gray-600">
                                        <form @submit.prevent="submitReply(comment)">
                                            <textarea v-model="replyForm.content" rows="2"
                                                class="w-full p-2 border rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400"
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

                                    <!-- 回复列表 -->
                                    <div v-if="comment.replies && comment.replies.length"
                                        class="mt-4 ml-11 pl-4 border-l-2 border-gray-200 dark:border-gray-600 space-y-4">
                                        <div v-for="reply in comment.replies" :key="reply.id"
                                            class="flex items-start space-x-3 reply-item">
                                            <!-- 回复者头像 -->
                                            <div class="flex-shrink-0 rounded-full w-6 h-6 flex items-center justify-center text-xs font-semibold"
                                                :class="getAvatarBgClass(reply.user?.id || 0)">{{
                                                getInitials(reply.user?.name || '访客') }}</div>
                                            <!-- 回复内容 -->
                                            <div class="flex-1">
                                                <div class="flex justify-between items-center mb-1">
                                                    <div>
                                                        <span
                                                            class="font-semibold text-xs mr-2 text-gray-800 dark:text-gray-300">{{
                                                            reply.user?.name || '匿名用户' }}</span>
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{
                                                            formatDateTime(reply.created_at) }}</span>
                                                    </div>
                                                    <div class="flex space-x-2 comment-actions"
                                                        v-if="$page.props.auth.user">
                                                        <button v-if="canManageComment(reply)"
                                                            @click="editComment(reply)"
                                                            class="btn-comment-action text-blue-600 dark:text-blue-400"><font-awesome-icon
                                                                :icon="['fas', 'edit']" /> 编辑</button>
                                                        <button v-if="canManageComment(reply)"
                                                            @click="deleteComment(reply)"
                                                            class="btn-comment-action text-red-600 dark:text-red-400"><font-awesome-icon
                                                                :icon="['fas', 'trash']" /> 删除</button>
                                                    </div>
                                                </div>
                                                <!-- 编辑回复表单 -->
                                                <div v-if="editingCommentId === reply.id" class="mt-2">
                                                    <textarea v-model="editCommentForm.content" rows="2"
                                                        class="w-full p-2 border rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"></textarea>
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
                                                <!-- 显示回复内容 -->
                                                <p v-else
                                                    class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed">{{
                                                    reply.content }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-gray-500 dark:text-gray-400 italic text-center py-8">
                                暂无评论，成为第一个评论者吧！
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 目录侧边栏 -->
                <div class="w-full lg:w-1/4 mt-8 lg:mt-0">
                    <div class="bg-white/70 dark:bg-gray-900/70 backdrop-blur-sm rounded-lg shadow-lg p-4 sticky top-8">
                        <h3
                            class="text-lg font-semibold mb-4 border-b pb-2 dark:border-gray-700 text-gray-800 dark:text-gray-200">
                            目录</h3>
                        <!-- 目录容器 -->
                        <div id="toc-container" class="mt-4 text-sm max-h-[70vh] overflow-y-auto">
                            <p class="text-gray-500 dark:text-gray-400 italic">正在加载目录...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 解决冲突模态框 -->
        <Modal :show="showResolveConflictModal" @close="closeResolveConflictModal" maxWidth="lg">
            <div class="p-6 bg-white dark:bg-gray-800 rounded-lg">
                <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-gray-100">
                    <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="text-yellow-500 mr-2" />
                    解决冲突
                </h2>
                <p class="text-gray-600 dark:text-gray-300 mb-6">该页面存在编辑冲突，请前往冲突解决页面进行处理。</p>
                <div class="flex justify-end space-x-3">
                    <button @click="closeResolveConflictModal" class="btn-secondary">关闭</button>
                    <Link :href="route('wiki.show-conflicts', page.slug)"
                        class="btn-primary bg-yellow-600 hover:bg-yellow-700 dark:bg-yellow-600 dark:hover:bg-yellow-700">
                    前往解决</Link>
                </div>
            </div>
        </Modal>

        <FlashMessage ref="flashMessage" />
    </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
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
    comments: { type: Array, default: () => [] } // 确保从 props 接收 comments
});

const wikiContentContainerRef = ref(null);
const flashMessage = ref(null);
const replyingToCommentId = ref(null);
const editingCommentId = ref(null);
const showResolveConflictModal = ref(false);

const commentForm = useForm({
    content: '',
    parent_id: null
});

const replyForm = useForm({
    content: '',
    parent_id: replyingToCommentId // This will be updated dynamically
});

const editCommentForm = useForm({
    content: '',
});

const generateTableOfContents = () => {
    nextTick(() => {
        const contentElement = wikiContentContainerRef.value;
        const tocContainer = document.getElementById('toc-container');

        // --- 新增：健壮性检查 ---
        if (!tocContainer) {
            console.warn('无法找到 TOC 容器 (#toc-container)');
            return; // 如果没有目录容器，直接退出
        }
        if (!contentElement) {
            console.warn('无法找到内容元素 (ref="wikiContentContainerRef")');
            // 清空或设置错误消息
            tocContainer.innerHTML = '<p class="text-gray-500 dark:text-gray-400 italic">无法生成目录：内容未加载。</p>';
            return; // 如果没有内容容器，也退出
        }
        // --- 检查结束 ---

        // 清空现有目录（或加载中提示）
        tocContainer.innerHTML = ''; // 确保清空

        const headings = contentElement.querySelectorAll('h1, h2, h3, h4, h5, h6');
        console.log("Headings found:", headings.length); // 调试信息

        if (headings.length === 0) {
            tocContainer.innerHTML = '<p class="text-gray-500 dark:text-gray-400 italic">此页面没有目录。</p>';
            return;
        }

        const tocList = document.createElement('ul');
        tocList.classList.add('space-y-2', 'list-none', 'pl-0');

        headings.forEach((heading, index) => {
            const level = parseInt(heading.tagName.substring(1));
            const text = heading.textContent?.trim() || `无标题 ${index + 1}`;

            // 确保 Heading 有 ID
            if (!heading.id) {
                const slug = text.toLowerCase()
                    .replace(/[^a-z0-9\u4e00-\u9fa5\s-]/g, '') // 允许中文
                    .trim()
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');
                const finalId = `toc-${slug}-${index}`.replace(/^[^a-zA-Z]+/, 'section-'); // 确保ID以字母开头
                heading.id = finalId || `toc-heading-${index}`; // 提供备用ID
            }

            const listItem = document.createElement('li');
            listItem.style.marginLeft = `${Math.max(0, level - 1) * 1}rem`; // 使用 rem 或 em 更灵活

            const link = document.createElement('a');
            link.href = `#${heading.id}`;
            link.textContent = text;
            link.classList.add(
                'text-blue-600',
                'dark:text-blue-400',
                'hover:text-blue-800',
                'dark:hover:text-blue-300',
                'hover:underline',
                'transition-colors',
                'duration-150',
                'text-sm', // 统一字体大小
                'block', // 确保链接占满整行以便点击
                'truncate' // 防止长标题破坏布局
            );
            if (level === 1) link.classList.add('font-semibold'); // H1 加粗
            if (level >= 3) link.classList.add('text-xs'); // H3 及以下更小

            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetElement = document.getElementById(heading.id);
                if (targetElement) {
                    const offset = 80; // 顶部导航栏的高度或其他偏移量
                    const bodyRect = document.body.getBoundingClientRect().top;
                    const elementRect = targetElement.getBoundingClientRect().top;
                    const elementPosition = elementRect - bodyRect;
                    const offsetPosition = elementPosition - offset;

                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                } else {
                    console.warn(`Target element not found for ID: ${heading.id}`);
                }
            });

            listItem.appendChild(link);
            tocList.appendChild(listItem);
        });

        tocContainer.appendChild(tocList);
        console.log("TOC generated successfully."); // 确认生成成功
    });
};

const userColors = {};
const bgColors = [
    'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300', 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300', 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
    'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-300', 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300', 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300'
];
let colorIndex = 0;

const getAvatarBgClass = (userId) => {
    if (!userId) return bgColors[0]; // Default color for anonymous or system
    if (!userColors[userId]) {
        userColors[userId] = bgColors[colorIndex % bgColors.length];
        colorIndex++;
    }
    return userColors[userId];
};

const getInitials = (name) => {
    if (!name) return '?';
    const nameTrimmed = name.trim();
    if (!nameTrimmed) return '?';
    const chineseMatch = nameTrimmed.match(/[\u4E00-\u9FA5]/); // 匹配中文字符
    if (chineseMatch) {
        return chineseMatch[0]; // 返回第一个中文字
    }
    const parts = nameTrimmed.split(/\s+/);
    if (parts.length > 0 && parts[0]) {
        return parts[0].charAt(0).toUpperCase();
    }
    return nameTrimmed.charAt(0).toUpperCase() || '?';
};

const commentsCount = computed(() => {
    let count = 0;
    // 使用 props.page.comments 而不是 props.comments
    if (props.page.comments) {
        props.page.comments.forEach(comment => {
            count++; // 计算主评论
            if (comment.replies) {
                count += comment.replies.length; // 计算回复
            }
        });
    }
    return count;
});

const canManageComment = (comment) => {
    const user = pageProps.auth.user;
    if (!user) return false;
    // 检查用户是否有管理权限，或者评论/回复是否由当前用户创建
    return comment.user_id === user.id || user.permissions?.includes('wiki.moderate_comments');
};

const submitComment = () => {
    commentForm.parent_id = null; // 主评论没有 parent_id
    commentForm.post(route('wiki.comments.store', props.page.slug), {
        preserveScroll: true,
        onSuccess: () => {
            commentForm.reset('content');
            flashMessage.value?.addMessage('success', '评论发布成功！');
            router.reload({ only: ['page'] }); // 只重新加载 page 数据
        },
        onError: (errors) => {
            // 显示具体的错误信息
            const firstError = Object.values(errors).flat()[0];
            flashMessage.value?.addMessage('error', firstError || '评论发布失败');
        }
    });
};

const replyToComment = (comment) => {
    if (replyingToCommentId.value === comment.id) {
        cancelReply(); // 如果点击的是同一个评论的回复按钮，则取消回复
    } else {
        replyingToCommentId.value = comment.id;
        replyForm.content = ''; // 清空回复内容
        cancelEditComment(); // 取消可能正在进行的编辑操作
        // 可以在这里聚焦回复框
        // nextTick(() => { /* focus logic */ });
    }
};

const cancelReply = () => {
    replyingToCommentId.value = null;
    replyForm.reset('content');
    replyForm.clearErrors();
};

const submitReply = (parentComment) => {
    replyForm.parent_id = parentComment.id; // 设置父评论ID
    replyForm.post(route('wiki.comments.store', props.page.slug), {
        preserveScroll: true,
        onSuccess: () => {
            cancelReply();
            flashMessage.value?.addMessage('success', '回复成功！');
            router.reload({ only: ['page'] });
        },
        onError: (errors) => {
            const firstError = Object.values(errors).flat()[0];
            flashMessage.value?.addMessage('error', firstError || '回复失败');
        }
    });
};

const editComment = (comment) => {
    if (editingCommentId.value === comment.id) {
        cancelEditComment(); // 如果点击的是同一个评论的编辑按钮，则取消编辑
    } else {
        editingCommentId.value = comment.id;
        editCommentForm.content = comment.content;
        cancelReply(); // 取消可能正在进行的回复操作
        // 聚焦编辑框
        // nextTick(() => { /* focus logic */ });
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
            router.reload({ only: ['page'] });
        },
        onError: (errors) => {
            const firstError = Object.values(errors).flat()[0];
            flashMessage.value?.addMessage('error', firstError || '评论更新失败');
        }
    });
};

const deleteComment = (comment) => {
    const typeText = comment.parent_id ? '回复' : '评论';
    if (confirm(`确定要隐藏这条${typeText}吗？此操作将对其他用户隐藏该内容。`)) {
        router.delete(route('wiki.comments.destroy', comment.id), {
            preserveScroll: true,
            onSuccess: () => {
                flashMessage.value?.addMessage('success', `${typeText}已隐藏！`);
                if (editingCommentId.value === comment.id) cancelEditComment();
                // 如果正在回复的评论被删除，或者正在回复的评论的回复被删除，则取消回复
                if (replyingToCommentId.value === comment.id || (comment.parent_id && replyingToCommentId.value === comment.parent_id)) cancelReply();
                router.reload({ only: ['page'] });
            },
            onError: (errors) => {
                const errorMsg = Object.values(errors).flat()[0] || `${typeText}删除失败，请重试`;
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

onMounted(() => {
    generateTableOfContents();
    // 显示来自后端的错误
    if (props.error) {
        flashMessage.value?.addMessage('error', props.error);
    }
    // 检查页面级别的错误
    const pageLevelErrors = usePage().props.errors;
    if (pageLevelErrors && pageLevelErrors.general) {
        flashMessage.value?.addMessage('error', pageLevelErrors.general);
    }
});

// 监听内容变化以重新生成目录
watch(() => props.currentVersion?.content, (newContent, oldContent) => {
    if (newContent !== oldContent) {
        console.log("Wiki content changed, regenerating TOC.");
        generateTableOfContents();
    }
}, { immediate: false }); // 首次加载时 onMounted 会处理

</script>

<style>
/* --- 核心 Wiki 内容显示样式 --- */
.wiki-content-display {
    @apply text-base text-gray-700 dark:text-gray-300;
    /* 基础文字样式 */
}

.wiki-content-display :deep(p) {
    @apply leading-relaxed mb-5;
    /* 段落行距和下边距 */
    line-height: 1.8;
    /* 更宽松的行高 */
}

/* 标题样式，添加滚动边距 */
.wiki-content-display :deep(h1),
.wiki-content-display :deep(h2),
.wiki-content-display :deep(h3),
.wiki-content-display :deep(h4),
.wiki-content-display :deep(h5),
.wiki-content-display :deep(h6) {
    @apply font-semibold text-gray-800 dark:text-gray-200 mt-10 mb-4;
    scroll-margin-top: 80px;
    /* 增加锚点滚动偏移，避免被顶部导航遮挡 */
    line-height: 1.3;
    /* 标题行高 */
}

.wiki-content-display :deep(h1) {
    @apply text-3xl border-b border-gray-300 dark:border-gray-700 pb-2 mb-6;
    /* H1 样式，带下边框 */
}

.wiki-content-display :deep(h2) {
    @apply text-2xl border-b border-gray-300 dark:border-gray-700 pb-2 mb-5;
    /* H2 样式，带下边框 */
}

.wiki-content-display :deep(h3) {
    @apply text-xl;
}

.wiki-content-display :deep(h4) {
    @apply text-lg font-medium;
}

/* 列表样式 */
.wiki-content-display :deep(ul),
.wiki-content-display :deep(ol) {
    @apply pl-6 mb-5 list-outside;
}

.wiki-content-display :deep(ul) {
    @apply list-disc;
}

.wiki-content-display :deep(ol) {
    @apply list-decimal;
}

.wiki-content-display :deep(li) {
    @apply mb-2;
}

.wiki-content-display :deep(li > p) {
    @apply mb-1;
    /* 列表项内段落的边距稍小 */
}

/* 引用块样式 */
.wiki-content-display :deep(blockquote) {
    @apply border-l-4 border-blue-300 dark:border-blue-700 pl-5 italic text-gray-600 dark:text-gray-400 my-6 py-2 bg-blue-50/50 dark:bg-blue-900/20 rounded-r;
}

/* 代码块样式 */
.wiki-content-display :deep(pre) {
    @apply bg-gray-800 text-gray-100 p-4 rounded-lg overflow-x-auto my-6 text-sm shadow-md leading-relaxed;
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
    /* 使用等宽字体 */
}

.wiki-content-display :deep(code:not(pre code)) {
    @apply bg-red-100 text-red-800 px-1.5 py-0.5 rounded text-[0.9em] font-mono dark:bg-red-900/40 dark:text-red-300;
    /* 行内代码样式 */
}

.wiki-content-display :deep(pre code) {
    background: none;
    color: inherit;
    padding: 0;
    font-size: inherit;
    line-height: inherit;
}

/* 代码高亮基础样式 (示例，实际高亮由JS库处理) */
.wiki-content-display :deep(pre code .hljs-comment) {
    @apply text-gray-400 italic;
}

.wiki-content-display :deep(pre code .hljs-keyword) {
    @apply text-blue-400;
}

.wiki-content-display :deep(pre code .hljs-string) {
    @apply text-green-400;
}

.wiki-content-display :deep(pre code .hljs-number) {
    @apply text-yellow-400;
}

/* 链接样式 */
.wiki-content-display :deep(a) {
    @apply text-cyan-600 dark:text-cyan-400 hover:text-cyan-800 dark:hover:text-cyan-300 underline decoration-cyan-600/50 hover:decoration-cyan-800 transition-colors duration-150;
}

/* 图片样式 */
.wiki-content-display :deep(img) {
    @apply max-w-full h-auto my-8 rounded-lg shadow-lg mx-auto block;
    border: 1px solid #eee;
    /* 给图片加一个细边框 */
    dark: border-gray-700;
}

/* --- 表格增强样式 --- */
.wiki-content-display :deep(table) {
    @apply w-full my-8 border-collapse border border-gray-400 dark:border-gray-600 shadow-md;
    /* 加强边框颜色和阴影 */
    table-layout: auto;
}

.wiki-content-display :deep(th),
.wiki-content-display :deep(td) {
    @apply border border-gray-300 dark:border-gray-600 px-5 py-3 text-left text-sm;
    /* 增加水平内边距 */
}

.wiki-content-display :deep(th) {
    @apply bg-gray-200 dark:bg-gray-700 font-semibold text-gray-800 dark:text-gray-100;
    /* 更明显的表头背景色和文字颜色 */
}

.wiki-content-display :deep(tr:nth-child(even)) {
    @apply bg-gray-100 dark:bg-gray-800;
    /* 更明显的斑马纹 */
}

.wiki-content-display :deep(tr:hover) {
    @apply bg-gray-200/50 dark:bg-gray-700/50;
    /* 行悬浮效果 */
}

/* --- 表格样式结束 --- */

/* --- 目录样式 --- */
#toc-container ul {
    list-style: none;
    padding-left: 0;
}

#toc-container li a {
    @apply text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline transition-colors duration-150 text-sm block truncate;
}

#toc-container li[style*="margin-left: 1rem"] a {
    /* H2 Indentation */
    @apply pl-4;
}

#toc-container li[style*="margin-left: 2rem"] a {
    /* H3 Indentation */
    @apply pl-8 text-xs;
}

#toc-container li[style*="margin-left: 3rem"] a {
    /* H4 Indentation */
    @apply pl-12 text-xs;
}

/* Add more levels if needed */
#toc-container li a.font-semibold {
    /* H1 Style */
    /* Removed @apply font-semibold; as it's handled by the class */
}

/* --- 评论区样式 --- */
/* 评论项和回复项的基本样式 */
.comment-item,
.reply-item {
    @apply flex items-start space-x-3;
}

/* 评论操作按钮 */
.comment-item:hover .comment-actions,
.reply-item:hover .comment-actions {
    opacity: 1;
}

.comment-actions {
    opacity: 1;
    /* 或者保持 0，如果确实需要悬停显示 */
    transition: opacity 0.2s ease-in-out;
    @apply flex;
    /* 保持 flex 布局 */
}

.btn-comment-action {
    @apply text-xs flex items-center transition-colors duration-150 cursor-pointer p-1 rounded;
}

.btn-comment-action:hover {
    @apply bg-gray-100 dark:bg-gray-700;
    /* 添加悬浮背景色 */
}

.btn-comment-action svg {
    @apply h-3 w-3;
}

/* --- 通用按钮和提示样式 --- */
.btn-primary {
    @apply inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition text-sm font-medium disabled:opacity-50 dark:focus:ring-offset-gray-800;
}

.btn-secondary {
    @apply inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition text-sm font-medium dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 dark:focus:ring-offset-gray-800;
}

/* 图标按钮 */
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

/* 提示框 */
.alert-error {
    @apply bg-red-50 border-l-4 border-red-400 text-red-700 p-4 rounded-md dark:bg-red-900/30 dark:text-red-300 dark:border-red-600;
}

.alert-warning {
    @apply bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 p-4 rounded-md dark:bg-yellow-900/30 dark:text-yellow-300 dark:border-yellow-600;
}

.alert-info {
    @apply bg-blue-50 border-l-4 border-blue-400 text-blue-700 p-4 rounded-md dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-600;
}

/* 分类和标签 Tag 样式 */
.tag-category {
    @apply px-2.5 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full hover:bg-gray-300 transition dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600;
}

.tag-tag {
    @apply px-2.5 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full hover:bg-blue-200 transition dark:bg-blue-900/40 dark:text-blue-300 dark:hover:bg-blue-900/60;
}

/* 暗色模式 Prose 适配 */
.dark .dark\:prose-invert {
    --tw-prose-body: theme(colors.gray.300);
    --tw-prose-headings: theme(colors.gray.100);
    --tw-prose-links: theme(colors.cyan.400);
    --tw-prose-bold: theme(colors.white);
    --tw-prose-bullets: theme(colors.gray.600);
    --tw-prose-hr: theme(colors.gray.700);
    --tw-prose-quotes: theme(colors.gray.300);
    --tw-prose-quote-borders: theme(colors.blue.700);
    --tw-prose-code: theme(colors.red.300);
    --tw-prose-pre-code: theme(colors.gray.300);
    --tw-prose-pre-bg: theme(colors.gray.800);
    --tw-prose-th-borders: theme(colors.gray.600);
    --tw-prose-td-borders: theme(colors.gray.700);
    /* 暗色模式下表格背景 */
    --tw-prose-tbody: theme(colors.gray.900);
    /* 修复暗色模式下 tbody 背景色 */
    --tw-prose-thead: theme(colors.gray.700);
    /* 确保表头背景适配 */
}
</style>
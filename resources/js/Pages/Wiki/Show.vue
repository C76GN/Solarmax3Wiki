<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head :title="page.title + (isPreview ? ' (预览)' : '')" />
        <div class="container mx-auto py-8 px-4 md:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row lg:space-x-8">
                <div class="w-full lg:w-3/4">
                    <!-- 预览提示 -->
                    <div v-if="isPreview"
                        class="mb-4 p-3 bg-blue-100 dark:bg-blue-900/50 border-l-4 border-blue-500 text-blue-700 dark:text-blue-300 rounded-md text-sm italic text-center">
                        <font-awesome-icon :icon="['fas', 'eye']" class="mr-2" /> 内容预览模式（未保存）
                    </div>
                    <!-- 主内容区 -->
                    <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6 md:p-8">
                        <!-- 标题和元信息区 -->
                        <div class="mb-8 pb-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex flex-col md:flex-row justify-between md:items-start mb-4 gap-4">
                                <div>
                                    <h1
                                        class="text-3xl md:text-4xl font-bold mb-2 leading-tight text-gray-900 dark:text-gray-100 break-words">
                                        {{ page.title }}
                                        <span
                                            v-if="isPreview || (currentVersion && currentVersion.version_number != null)"
                                            class="text-xl font-normal text-gray-500 dark:text-gray-400">
                                            (版本 {{ currentVersion?.version_number ?? '预览' }})
                                        </span>
                                    </h1>
                                    <div
                                        class="text-sm text-gray-500 dark:text-gray-400 flex flex-wrap items-center gap-x-4 gap-y-1">
                                        <span v-if="page.creator" class="whitespace-nowrap flex items-center">
                                            <font-awesome-icon :icon="['fas', 'user']" class="mr-1.5 w-3 h-3" /> 由 {{
                                                page.creator.name }} 创建于 {{ formatDateShort(page.created_at) }}
                                        </span>
                                        <span v-if="currentVersion && !isPreview"
                                            class="whitespace-nowrap flex items-center">
                                            <font-awesome-icon :icon="['fas', 'edit']" class="mr-1.5 w-3 h-3" />
                                            最新 v{{ currentVersion.version_number }}
                                            <span v-if="currentVersion.creator" class="ml-1">由 {{
                                                currentVersion.creator.name }} 编辑于 {{
                                                    formatDateTime(currentVersion.created_at) }}</span>
                                        </span>
                                        <span v-if="isPreview" class="whitespace-nowrap flex items-center">
                                            <font-awesome-icon :icon="['fas', 'clock']" class="mr-1.5 w-3 h-3" />
                                            预览生成于: {{ formatDateTime(new Date()) }}
                                        </span>
                                    </div>
                                </div>
                                <div v-if="!isPreview" class="flex items-center space-x-2 flex-shrink-0 mt-2 md:mt-0">
                                    <Link :href="route('wiki.history', page.slug)" class="btn-icon-secondary">
                                    <font-awesome-icon :icon="['fas', 'history']" /> <span>历史</span>
                                    </Link>
                                    <Link v-if="canEditPage" :href="route('wiki.edit', page.slug)"
                                        class="btn-icon-primary">
                                    <font-awesome-icon :icon="['fas', 'edit']" /> <span>编辑</span>
                                    </Link>
                                    <Link v-if="!isPreview && isConflictPage && canResolveConflict"
                                        :href="route('wiki.edit', page.slug)" class="btn-icon-warning animate-pulse">
                                    <font-awesome-icon :icon="['fas', 'exclamation-triangle']" /> <span>解决冲突</span>
                                    </Link>
                                </div>
                            </div>
                            <!-- 分类和标签 -->
                            <div class="flex flex-wrap gap-2 mt-3 items-center">
                                <span class="text-sm text-gray-500 dark:text-gray-400 mr-2">分类:</span>
                                <span v-for="category in page.categories" :key="category.id" class="tag-category">
                                    {{ category.name }}
                                </span>
                                <span class="text-sm text-gray-500 dark:text-gray-400 mr-2 ml-4"
                                    v-if="page.tags && page.tags.length > 0">标签:</span>
                                <span v-for="tag in page.tags" :key="tag.id" class="tag-tag">
                                    {{ tag.name }}
                                </span>
                            </div>
                        </div>
                        <!-- 警告和草稿提示 -->
                        <div v-if="!isPreview && (isLocked || isConflictPage)" class="mb-6 space-y-4">
                            <!-- 锁定提示 -->
                            <div v-if="isLocked && !isConflictPage" class="alert-warning">
                                <div class="flex items-center">
                                    <font-awesome-icon :icon="['fas', 'lock']" class="mr-2 flex-shrink-0" />
                                    <p v-if="lockedBy" class="text-sm">该页面当前被 <strong>{{ lockedBy.name }}</strong>
                                        锁定编辑中<span v-if="page.locked_until"> (预计 {{ formatDateTime(page.locked_until) }}
                                            解锁)</span>。</p>
                                    <p v-else class="text-sm">页面已被锁定，无法获取锁定者信息。</p>
                                </div>
                            </div>
                            <!-- 冲突提示 -->
                            <div v-if="isConflictPage" class="alert-error">
                                <div class="flex items-center">
                                    <font-awesome-icon :icon="['fas', 'exclamation-circle']"
                                        class="mr-2 flex-shrink-0" />
                                    <p class="text-sm">
                                        此页面存在编辑冲突！
                                        <template v-if="canResolveConflict">
                                            点击上方的
                                            <Link :href="route('wiki.edit', page.slug)"
                                                class="font-semibold underline hover:text-red-800 dark:hover:text-red-300 mx-1">
                                            “解决冲突”</Link>按钮进行处理。
                                        </template>
                                        <template v-else>
                                            需要拥有相应权限的用户才能解决。
                                        </template>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <!-- 草稿提示 -->
                        <div v-if="!isPreview && draft && !isConflictPage && !isLocked" class="alert-info mb-6">
                            <div class="flex items-center">
                                <font-awesome-icon :icon="['fas', 'save']" class="mr-2 flex-shrink-0" />
                                <p class="text-sm">您有一份未保存的草稿 <span v-if="draft.last_saved_at"> ({{
                                    formatDateTime(draft.last_saved_at) }})</span>
                                    <Link :href="route('wiki.edit', page.slug)"
                                        class="ml-2 underline font-medium hover:text-blue-800 dark:hover:text-blue-300">
                                    继续编辑</Link>
                                </p>
                            </div>
                        </div>

                        <!-- 页面内容 -->
                        <div ref="wikiContentContainerRef"
                            class="prose max-w-none prose-indigo lg:prose-lg xl:prose-xl wiki-content-display dark:prose-invert">
                            <div v-if="currentVersion && currentVersion.content" v-html="currentVersion.content"></div>
                            <div v-else class="error-content-placeholder">
                                <p>{{ error || (isConflictPage ? '页面冲突中，最新内容可能不可见。请解决冲突以查看或编辑最新内容。' : '该页面还没有内容。') }}
                                </p>
                                <!-- 链接到编辑或解决冲突 -->
                                <Link v-if="!isPreview && isConflictPage && canResolveConflict"
                                    :href="route('wiki.edit', page.slug)" class="link-style mt-2">
                                解决冲突
                                </Link>
                                <Link v-else-if="!isPreview && !isConflictPage && canEditPage"
                                    :href="route('wiki.edit', page.slug)" class="link-style mt-2">
                                开始编辑
                                </Link>
                            </div>
                        </div>
                        <!-- 评论区 -->
                        <div v-if="!isPreview" class="mt-12 pt-8 border-t border-gray-300 dark:border-gray-700">
                            <h3 class="text-2xl font-bold mb-6 text-gray-800 dark:text-gray-200">评论 ({{ commentsCount
                            }})</h3>
                            <!-- 评论表单 -->
                            <div v-if="$page.props.auth.user && $page.props.auth.user.permissions.includes('wiki.comment')"
                                class="mb-8 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg border dark:border-gray-700">
                                <form @submit.prevent="submitComment">
                                    <textarea v-model="commentForm.content" rows="3"
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400"
                                        placeholder="添加你的评论..." :disabled="commentForm.processing"></textarea>
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
                            <!-- 登录提示 -->
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
                                    <!-- ... 评论内容和操作 ... -->
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0 rounded-full w-8 h-8 flex items-center justify-center text-sm font-semibold"
                                            :class="getAvatarBgClass(comment.user?.id || 0)">
                                            {{ getInitials(comment.user?.name || '访客') }}
                                        </div>
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
                                                    <button @click="toggleReply(comment)"
                                                        class="btn-comment-action text-gray-600 dark:text-gray-400"><font-awesome-icon
                                                            :icon="['fas', 'reply']" class="mr-1" /> {{
                                                                replyingToCommentId === comment.id ? '取消回复' : '回复' }}</button>
                                                </div>
                                            </div>
                                            <div v-if="editingCommentId === comment.id" class="mt-2">
                                                <form @submit.prevent="updateComment(comment)">
                                                    <textarea v-model="editCommentForm.content" rows="3"
                                                        class="w-full p-2 border border-gray-300 rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400"
                                                        :disabled="editCommentForm.processing"></textarea>
                                                    <InputError class="mt-1"
                                                        :message="editCommentForm.errors.content" />
                                                    <div class="flex justify-end mt-2 space-x-2">
                                                        <button type="button" @click="cancelEditComment"
                                                            class="btn-secondary text-xs">取消</button>
                                                        <button type="submit" class="btn-primary text-xs"
                                                            :disabled="!editCommentForm.content.trim() || editCommentForm.processing">
                                                            <font-awesome-icon v-if="editCommentForm.processing"
                                                                :icon="['fas', 'spinner']" spin class="mr-1" />
                                                            {{ editCommentForm.processing ? '更新中...' : '更新评论' }}
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                            <p v-else
                                                class="text-gray-800 dark:text-gray-300 text-sm leading-relaxed break-words">
                                                {{ comment.content }}</p>
                                        </div>
                                    </div>
                                    <!-- 回复输入框 -->
                                    <div v-if="replyingToCommentId === comment.id"
                                        class="mt-3 ml-11 pl-4 border-l-2 border-gray-200 dark:border-gray-600">
                                        <form @submit.prevent="submitReply(comment)">
                                            <textarea v-model="replyForm.content" rows="2"
                                                class="w-full p-2 border border-gray-300 rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400"
                                                placeholder="添加回复..." :disabled="replyForm.processing"></textarea>
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
                                        <div v-for="reply in comment.replies" :key="reply.id" class="reply-item">
                                            <div class="flex items-start space-x-3">
                                                <div class="flex-shrink-0 rounded-full w-6 h-6 flex items-center justify-center text-xs font-semibold"
                                                    :class="getAvatarBgClass(reply.user?.id || 0)">
                                                    {{ getInitials(reply.user?.name || '访客') }}
                                                </div>
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
                                                    <div v-if="editingCommentId === reply.id" class="mt-2">
                                                        <form @submit.prevent="updateComment(reply)">
                                                            <textarea v-model="editCommentForm.content" rows="2"
                                                                class="w-full p-2 border border-gray-300 rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                                                :disabled="editCommentForm.processing"></textarea>
                                                            <InputError class="mt-1"
                                                                :message="editCommentForm.errors.content" />
                                                            <div class="flex justify-end mt-2 space-x-2">
                                                                <button type="button" @click="cancelEditComment"
                                                                    class="btn-secondary text-xs">取消</button>
                                                                <button type="submit" class="btn-primary text-xs"
                                                                    :disabled="!editCommentForm.content.trim() || editCommentForm.processing">
                                                                    <font-awesome-icon v-if="editCommentForm.processing"
                                                                        :icon="['fas', 'spinner']" spin class="mr-1" />
                                                                    {{ editCommentForm.processing ? '更新中...' : '更新回复' }}
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                    <p v-else
                                                        class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed break-words">
                                                        {{ reply.content }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- 无评论提示 -->
                            <div v-else class="text-gray-500 dark:text-gray-400 italic text-center py-8">
                                暂无评论，成为第一个评论者吧！
                            </div>
                        </div>
                        <!-- 预览模式下的评论区占位 -->
                        <div v-if="isPreview" class="mt-12 pt-8 border-t border-gray-300 dark:border-gray-700">
                            <h3 class="text-xl font-semibold mb-4 text-gray-700 dark:text-gray-300">评论区</h3>
                            <div
                                class="p-4 bg-gray-100 dark:bg-gray-700/30 rounded-lg text-center text-sm text-gray-500 dark:text-gray-400 italic">
                                （预览模式下不显示评论）
                            </div>
                        </div>
                    </div>
                </div>
                <!-- 目录 (保持不变) -->
                <div class="w-full lg:w-1/4 mt-8 lg:mt-0 lg:pl-4">
                    <div class="bg-white/70 dark:bg-gray-900/70 backdrop-blur-sm rounded-lg shadow-lg p-4 sticky top-8">
                        <h3
                            class="text-lg font-semibold mb-4 border-b pb-2 dark:border-gray-700 text-gray-800 dark:text-gray-200">
                            <font-awesome-icon :icon="['fas', 'list-ul']" class="mr-2" />目录
                        </h3>
                        <div id="toc-container" ref="tocContainerRef"
                            class="mt-4 text-sm max-h-[calc(100vh-8rem)] overflow-y-auto toc-links">
                            <p class="text-gray-500 dark:text-gray-400 italic">正在生成目录...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modals (保持不变, Resolve Conflict Modal 不再需要) -->
        <!-- FlashMessage (保持不变) -->
        <FlashMessage ref="flashMessage" />
    </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Modal from '@/Components/Modal/Modal.vue';
import FlashMessage from '@/Components/Other/FlashMessage.vue';
import InputError from '@/Components/Other/InputError.vue';
import { formatDate, formatDateShort, formatDateTime } from '@/utils/formatters';
import { mainNavigationLinks } from '@/config/navigationConfig';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'; // Ensure this is imported if used directly in template
import { faExclamationTriangle, faExclamationCircle } from '@fortawesome/free-solid-svg-icons'; // 引入图标

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
    isConflictPage: { type: Boolean, default: false }, // <-- 从 Controller 传递过来
    error: { type: String, default: '' },
    comments: { type: Array, default: () => [] },
    isPreview: { type: Boolean, default: false } // 新增的 Prop
});

const wikiContentContainerRef = ref(null);
const tocContainerRef = ref(null);
const flashMessage = ref(null);
const replyingToCommentId = ref(null);
const editingCommentId = ref(null);
const showResolveConflictModal = ref(false);
const activeTocId = ref(null);
let observer = null; // 滚动监听器

// --- Forms ---
const commentForm = useForm({ content: '', parent_id: null });
const replyForm = useForm({ content: '', parent_id: null });
const editCommentForm = useForm({ content: '' });

// --- Computed Properties ---
const commentsCount = computed(() => {
    let count = 0;
    if (props.comments) { // Use props.comments directly as it's guaranteed by default []
        props.comments.forEach(comment => {
            count++;
            if (comment.replies) count += comment.replies.length;
        });
    }
    return count;
});

// --- Methods ---
// Table of Contents Generation
const generateSlug = (text) => {
    if (!text) return '';
    return text.toLowerCase()
        .replace(/[\s\/\\]+/g, '-') // Replace spaces, slashes with hyphen
        .replace(/[^\w\u4E00-\u9FA5-]+/g, '') // Remove non-word, non-Chinese characters except hyphens
        .replace(/-+/g, '-') // Replace multiple hyphens with single hyphen
        .replace(/^-+|-+$/g, ''); // Trim hyphens from start/end
};

const generateUniqueId = (baseId, existingIds) => {
    let id = baseId;
    let counter = 1;
    while (existingIds.has(id)) {
        id = `${baseId}-${counter}`;
        counter++;
    }
    existingIds.add(id);
    return id;
};

const generateTableOfContents = () => {
    nextTick(() => { // Ensure DOM is updated before querySelectorAll
        const contentElement = wikiContentContainerRef.value;
        const tocContainer = tocContainerRef.value;
        const existingIds = new Set();

        if (!tocContainer) {
            console.warn('无法找到 TOC 容器 (ref="tocContainerRef")');
            return;
        }
        // Clear previous TOC content first
        tocContainer.innerHTML = '';

        if (!contentElement) {
            console.warn('无法找到内容元素 (ref="wikiContentContainerRef")');
            tocContainer.innerHTML = '<p class="text-gray-500 dark:text-gray-400 italic text-xs">无法生成目录：内容未加载。</p>';
            return;
        }

        const headings = contentElement.querySelectorAll('h1, h2, h3, h4, h5, h6');

        if (headings.length === 0) {
            tocContainer.innerHTML = '<p class="text-gray-500 dark:text-gray-400 italic text-xs">此页面没有目录。</p>';
            return;
        }

        const tocList = document.createElement('ul');
        tocList.classList.add('space-y-1.5', 'list-none', 'pl-0'); // Tailwind classes

        headings.forEach((heading, index) => {
            const level = parseInt(heading.tagName.substring(1));
            const text = heading.textContent?.trim() || `无标题 ${index + 1}`;
            let id = heading.id; // Use existing ID if available

            // Ensure ID is unique and generate if needed
            if (!id || existingIds.has(id)) {
                const baseSlug = generateSlug(text);
                id = generateUniqueId(`toc-${baseSlug || 'heading'}-${index}`, existingIds);
                heading.id = id; // Assign the generated ID back to the heading
            } else {
                existingIds.add(id); // Add the valid existing ID to the set
            }

            const listItem = document.createElement('li');
            // Apply indentation based on heading level
            listItem.style.paddingLeft = `${Math.max(0, level - 1) * 1}rem`; // 1rem per level indent

            const link = document.createElement('a');
            link.href = `#${id}`;
            link.textContent = text;
            link.dataset.tocId = id; // Store ID for scroll spying
            link.classList.add(
                'toc-link', // Custom class for easier selection
                'block',
                'truncate', // Prevent long text overflow
                'transition-colors',
                'duration-150',
                'text-xs', // Smaller font size for TOC
                'py-0.5', // Vertical padding
                'text-gray-600', 'dark:text-gray-400', // Base color
                'hover:text-blue-600', 'dark:hover:text-blue-400' // Hover color
            );

            // Click handler for smooth scrolling
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetElement = document.getElementById(id);
                if (targetElement) {
                    const offset = 80; // Adjust this value based on your sticky header height
                    const bodyRect = document.body.getBoundingClientRect().top;
                    const elementRect = targetElement.getBoundingClientRect().top;
                    const elementPosition = elementRect - bodyRect;
                    const offsetPosition = elementPosition - offset;

                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });

                    // Optional: Add temporary highlight to target heading
                    targetElement.classList.add('highlight-scroll');
                    setTimeout(() => {
                        targetElement.classList.remove('highlight-scroll');
                    }, 1000); // Highlight duration

                } else {
                    console.warn(`Target element not found for ID: ${id}`);
                }
            });

            listItem.appendChild(link);
            tocList.appendChild(listItem);
        });

        tocContainer.appendChild(tocList);
        // Setup scroll spying after TOC is generated
        if (!props.isPreview) { // Only activate spy on the real page, not preview
            setupTocScrollSpy();
        }
    });
};

// Intersection Observer for Scroll Spying
const setupTocScrollSpy = () => {
    if (observer) {
        observer.disconnect(); // Disconnect previous observer if any
    }
    const tocLinks = tocContainerRef.value?.querySelectorAll('.toc-link');
    if (!tocLinks || tocLinks.length === 0) {
        // console.log("No TOC links found, skipping scroll spy setup.");
        return; // No links to observe
    }

    const headingElements = Array.from(tocLinks).map(link => {
        const targetId = link.getAttribute('href')?.substring(1);
        return targetId ? document.getElementById(targetId) : null;
    }).filter(el => el !== null); // Ensure elements exist

    if (headingElements.length === 0) {
        // console.log("No heading elements found for TOC links, skipping scroll spy setup.");
        return; // No valid targets
    }

    // Observer options: Trigger when heading enters the top part of the viewport
    const options = {
        // root: null, // observing intersections with the viewport
        rootMargin: '-80px 0px -60% 0px', // Top offset for sticky nav, bottom negative margin to activate sooner
        threshold: 0 // Trigger as soon as any part enters the margin
    };

    observer = new IntersectionObserver(entries => {
        let latestActiveId = null;

        // Iterate entries in reverse to prioritize the one lowest on the screen that's intersecting
        entries.reverse().forEach(entry => {
            if (entry.isIntersecting) {
                latestActiveId = entry.target.id;
                // We found the lowest intersecting element, no need to check further up
                // Use break or simply don't overwrite `latestActiveId` again if needed
                // For simplicity, we'll just take the last one in the reversed loop
            }
        });

        // Fallback: If nothing is intersecting in the primary zone,
        // find the highest element that is *above* the top threshold
        if (!latestActiveId) {
            const elementsAboveThreshold = entries
                .filter(entry => entry.boundingClientRect.top < 80) // Check if top edge is above threshold (e.g., 80px from viewport top)
                .map(entry => entry.target); // Get the DOM elements
            if (elementsAboveThreshold.length > 0) {
                // Get the *last* element among those above (closest to the top threshold)
                latestActiveId = elementsAboveThreshold[elementsAboveThreshold.length - 1].id;
            }
        }

        activeTocId.value = latestActiveId;

        // Update TOC link styles
        tocLinks.forEach(link => {
            link.classList.remove('is-active'); // Remove active class from all
            if (link.dataset.tocId === activeTocId.value) {
                link.classList.add('is-active'); // Add to the currently active one
            }
        });
    }, options);

    // Observe all heading elements
    headingElements.forEach(el => observer.observe(el));
};


// User Avatar helpers
const userColors = {};
const bgColors = [
    'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300', 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300', 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
    'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-300', 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300', 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300'
];
let colorIndex = 0;
const getAvatarBgClass = (userId) => {
    if (!userId) return bgColors[0]; // Default color for null userId
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
    // Check if it's a Chinese character first
    const chineseMatch = nameTrimmed.match(/[\u4E00-\u9FA5]/);
    if (chineseMatch) {
        return chineseMatch[0]; // Return the first Chinese character
    }
    // Fallback for English names
    const parts = nameTrimmed.split(/\s+/);
    if (parts.length > 0 && parts[0]) {
        return parts[0].charAt(0).toUpperCase();
    }
    // Fallback if splitting fails or name is weird
    return nameTrimmed.charAt(0).toUpperCase() || '?';
};


// Comment management methods
const canManageComment = (comment) => {
    const user = pageProps.auth.user;
    if (!user) return false;
    // User can manage their own comments OR if they have the specific permission
    return comment.user_id === user.id || user.permissions?.includes('wiki.moderate_comments');
};

const submitComment = () => {
    commentForm.parent_id = null; // Ensure parent_id is null for top-level comments
    commentForm.post(route('wiki.comments.store', props.page.slug), {
        preserveScroll: true,
        onSuccess: () => {
            commentForm.reset('content');
            flashMessage.value?.addMessage('success', '评论发布成功！');
            router.reload({ only: ['comments'] }); // Only reload comments data
        },
        onError: (errors) => {
            const firstError = Object.values(errors).flat()[0];
            flashMessage.value?.addMessage('error', firstError || '评论发布失败');
        }
    });
};

const toggleReply = (comment) => {
    if (replyingToCommentId.value === comment.id) {
        cancelReply(); // Close if already replying to this one
    } else {
        replyingToCommentId.value = comment.id; // Set the target for reply
        replyForm.reset('content'); // Clear previous reply input
        cancelEditComment(); // Close any open edit form
    }
};

const cancelReply = () => {
    replyingToCommentId.value = null;
    replyForm.reset('content');
    replyForm.clearErrors();
};

const submitReply = (parentComment) => {
    replyForm.parent_id = parentComment.id; // Set the parent comment ID
    replyForm.post(route('wiki.comments.store', props.page.slug), {
        preserveScroll: true,
        onSuccess: () => {
            cancelReply(); // Close reply form
            flashMessage.value?.addMessage('success', '回复成功！');
            router.reload({ only: ['comments'] }); // Reload comments
        },
        onError: (errors) => {
            const firstError = Object.values(errors).flat()[0];
            flashMessage.value?.addMessage('error', firstError || '回复失败');
        }
    });
};

const editComment = (comment) => {
    if (editingCommentId.value === comment.id) {
        cancelEditComment(); // Close if already editing this one
    } else {
        editingCommentId.value = comment.id; // Set the target for edit
        editCommentForm.content = comment.content; // Load current content
        cancelReply(); // Close any open reply form
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
            cancelEditComment(); // Close edit form
            flashMessage.value?.addMessage('success', '评论更新成功！');
            router.reload({ only: ['comments'] }); // Reload comments
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
                // Close any forms related to this comment
                if (editingCommentId.value === comment.id) cancelEditComment();
                if (replyingToCommentId.value === comment.id || (comment.parent_id && replyingToCommentId.value === comment.parent_id)) cancelReply();
                router.reload({ only: ['comments'] }); // Reload comments
            },
            onError: (errors) => {
                const errorMsg = Object.values(errors).flat()[0] || `${typeText}删除失败，请重试`;
                flashMessage.value?.addMessage('error', errorMsg);
            }
        });
    }
};

// Resolve Conflict Modal (不再需要)
const openResolveConflictModal = () => { /* Kept for potential future use but likely unused */ };
const closeResolveConflictModal = () => { /* Kept for potential future use */ };


// --- Lifecycle Hooks ---
onMounted(() => {
    generateTableOfContents(); // Generate TOC on mount
    // Show initial flash messages if provided
    if (props.error && !props.isPreview) {
        flashMessage.value?.addMessage('error', props.error);
    }
    const pageLevelErrors = usePage().props.errors;
    if (pageLevelErrors && pageLevelErrors.general && !props.isPreview) {
        flashMessage.value?.addMessage('error', pageLevelErrors.general);
    }
    // Show conflict warning messages on initial load if applicable
    if (props.isConflictPage && !props.canResolveConflict && !props.isPreview) {
        flashMessage.value?.addMessage('warning', '此页面当前存在编辑冲突，您没有权限解决。');
    } else if (props.isConflictPage && props.canResolveConflict && !props.isPreview) {
        flashMessage.value?.addMessage('info', '此页面存在编辑冲突，您可以前往编辑页解决。');
    }
    // Set up scroll spy only if not in preview mode
    if (!props.isPreview) {
        // Add a small delay to ensure content is rendered for accurate observer setup
        setTimeout(setupTocScrollSpy, 100);
        // Optional: Re-run setup after a larger delay if dynamic content loading affects headings
        // setTimeout(setupTocScrollSpy, 1000);
    }
});

onUnmounted(() => {
    if (observer) {
        observer.disconnect(); // Clean up the observer
    }
});

// Watch for content changes to regenerate TOC
watch(() => props.currentVersion?.content, (newContent, oldContent) => {
    if (newContent !== oldContent) {
        console.log("Content prop changed, regenerating TOC...");
        generateTableOfContents();
    }
}, { immediate: false }); // Run on mount might cause issues if content isn't fully rendered

// Watch for the comments prop specifically if you expect it to reload often
watch(() => props.comments, () => {
    // Reset any reply/edit states if comments reload entirely? Optional.
    // cancelReply();
    // cancelEditComment();
    console.log("Comments prop possibly updated.");
}, { deep: true });

</script>

<style scoped>
/* 高亮滚动目标 */
.highlight-scroll {
    animation: highlight 1s ease-out;
}

@keyframes highlight {
    0% {
        background-color: rgba(59, 130, 246, 0.4);
    }

    100% {
        background-color: transparent;
    }
}

/* 目录链接 */
.toc-link {
    @apply block truncate transition-colors duration-150 text-xs py-0.5 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400;
    padding-left: inherit;
    /* 继承 li 的 padding-left */
}

/* 激活目录项的样式 */
.toc-link.is-active {
    @apply text-blue-600 dark:text-blue-400 font-semibold;
    transform: translateX(4px);
    /* 轻微向右移动 */
    border-left: 2px solid;
    /* 左侧高亮线 */
    @apply border-blue-600 dark:border-blue-400;
    padding-left: calc(inherit - 2px + 1rem);
    /* 调整内边距，留出高亮线的空间 */
}

/* 为不同级别的 TOC 链接应用缩进（如果使用内联样式则无需这里，但可以备用）*/
#toc-container ul ul .toc-link {
    padding-left: 1rem;
}

#toc-container ul ul ul .toc-link {
    padding-left: 2rem;
}

#toc-container ul ul ul ul .toc-link {
    padding-left: 3rem;
}

/* 主要内容显示区域的基础样式 */
.wiki-content-display {
    @apply text-base text-gray-700 dark:text-gray-300;
}

.wiki-content-display :deep(p) {
    @apply leading-relaxed mb-5;
    line-height: 1.8;
    /* 行高调整 */
}

.wiki-content-display :deep(h1),
.wiki-content-display :deep(h2),
.wiki-content-display :deep(h3),
.wiki-content-display :deep(h4),
.wiki-content-display :deep(h5),
.wiki-content-display :deep(h6) {
    @apply font-semibold text-gray-800 dark:text-gray-200 mt-10 mb-4;
    /* 调整标题间距 */
    scroll-margin-top: 80px;
    /* 滚动定位偏移 */
    line-height: 1.4;
    /* 标题行高 */
}

.wiki-content-display :deep(h1) {
    @apply text-3xl border-b border-gray-300 dark:border-gray-700 pb-2 mb-6;
}

.wiki-content-display :deep(h2) {
    @apply text-2xl border-b border-gray-300 dark:border-gray-700 pb-2 mb-5;
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
    @apply mb-1 inline;
}

/* 防止列表项内 p 增加过多间距 */

/* 引用块 */
.wiki-content-display :deep(blockquote) {
    @apply border-l-4 border-blue-300 dark:border-blue-700 pl-5 italic text-gray-600 dark:text-gray-400 my-6 py-2 bg-blue-50/50 dark:bg-blue-900/20 rounded-r;
}

/* 代码块 */
.wiki-content-display :deep(pre) {
    @apply bg-gray-800 text-gray-100 p-4 rounded-lg overflow-x-auto my-6 text-sm shadow-md leading-relaxed dark:bg-black/50 dark:text-gray-200;
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
    /* 代码字体 */
}

.wiki-content-display :deep(code:not(pre code)) {
    @apply bg-red-100 text-red-800 px-1.5 py-0.5 rounded text-[0.9em] font-mono dark:bg-red-900/40 dark:text-red-300;
}

.wiki-content-display :deep(pre code) {
    background: none;
    color: inherit;
    padding: 0;
    font-size: inherit;
    line-height: inherit;
}

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

/* 链接 */
.wiki-content-display :deep(a) {
    @apply text-cyan-600 dark:text-cyan-400 hover:text-cyan-800 dark:hover:text-cyan-300 underline decoration-cyan-600/50 hover:decoration-cyan-800 transition-colors duration-150;
}

/* 图片 */
.wiki-content-display :deep(img) {
    @apply max-w-full h-auto my-8 rounded-lg shadow-lg mx-auto block border border-gray-300 dark:border-gray-700;
}

/* 表格 */
.wiki-content-display :deep(table) {
    @apply w-full my-8 border-collapse border border-gray-400 dark:border-gray-600 shadow-md;
    table-layout: auto;
    /* 允许表格根据内容调整列宽 */
}

.wiki-content-display :deep(th),
.wiki-content-display :deep(td) {
    @apply border border-gray-300 dark:border-gray-600 px-5 py-3 text-left text-sm;
    /* 增加 padding */
    vertical-align: top;
}

.wiki-content-display :deep(th) {
    @apply bg-gray-200 dark:bg-gray-700 font-semibold text-gray-800 dark:text-gray-100;
}

.wiki-content-display :deep(tr:nth-child(even)) {
    @apply bg-gray-100 dark:bg-gray-800/50;
}

.wiki-content-display :deep(tr:hover) {
    @apply bg-gray-200/50 dark:bg-gray-700/50;
}

/* 分类和标签样式 */
.tag-category {
    @apply inline-block px-2.5 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full hover:bg-gray-300 transition dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600;
}

.tag-tag {
    @apply inline-block px-2.5 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full hover:bg-blue-200 transition dark:bg-blue-900/40 dark:text-blue-300 dark:hover:bg-blue-900/60;
}

/* 操作按钮 */
.btn-icon-primary {
    @apply inline-flex items-center px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition;
}

.btn-icon-secondary {
    @apply inline-flex items-center px-3 py-1 bg-gray-200 text-gray-700 text-xs rounded hover:bg-gray-300 transition dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500;
}

.btn-icon-warning {
    @apply inline-flex items-center px-3 py-1 bg-yellow-500 text-white text-xs rounded hover:bg-yellow-600 transition;
}

.btn-icon-primary svg,
.btn-icon-secondary svg,
.btn-icon-warning svg {
    @apply mr-1.5 h-3 w-3;
}

/* 警告/提示框 */
.alert-error {
    @apply bg-red-100 border-l-4 border-red-500 text-red-800 p-4 rounded-md dark:bg-red-900/40 dark:border-red-600 dark:text-red-300;
}

.alert-warning {
    @apply bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-md dark:bg-yellow-900/40 dark:border-yellow-600 dark:text-yellow-300;
}

.alert-info {
    @apply bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 rounded-md dark:bg-blue-900/40 dark:border-blue-600 dark:text-blue-300;
}

/* 评论区样式 */
.comment-item:hover .comment-actions,
.reply-item:hover .comment-actions {
    opacity: 1;
}

.comment-actions {
    opacity: 0;
    transition: opacity 0.2s ease-in-out;
    @apply flex;
}

.btn-comment-action {
    @apply text-xs flex items-center transition-colors duration-150 cursor-pointer p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700;
}

.btn-comment-action svg {
    @apply h-3 w-3;
}

/* 通用按钮样式 */
.btn-primary {
    @apply inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition text-sm font-medium disabled:opacity-50 dark:focus:ring-offset-gray-800;
}

.btn-secondary {
    @apply inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition text-sm font-medium dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 dark:focus:ring-offset-gray-800;
}

/* 深色模式下的Prose适配 */
.dark .dark\:prose-invert {
    --tw-prose-body: theme(colors.gray.300);
    --tw-prose-headings: theme(colors.gray.100);
    --tw-prose-lead: theme(colors.gray.400);
    --tw-prose-links: theme(colors.cyan.400);
    --tw-prose-bold: theme(colors.white);
    --tw-prose-counters: theme(colors.gray.400);
    --tw-prose-bullets: theme(colors.gray.500);
    --tw-prose-hr: theme(colors.gray.700);
    --tw-prose-quotes: theme(colors.gray.200);
    --tw-prose-quote-borders: theme(colors.blue.700);
    --tw-prose-captions: theme(colors.gray.400);
    --tw-prose-code: theme(colors.red.300);
    --tw-prose-pre-code: theme(colors.gray.300);
    --tw-prose-pre-bg: theme(colors.gray.900);
    --tw-prose-th-borders: theme(colors.gray.600);
    --tw-prose-td-borders: theme(colors.gray.700);
    --tw-prose-thead: theme(colors.gray.700);
    --tw-prose-tbody: theme(colors.transparent);
    /* 更自然的背景 */
}

/* 保证内容换行 */
.break-words {
    overflow-wrap: break-word;
    word-break: break-word;
    -webkit-hyphens: auto;
    -moz-hyphens: auto;
    hyphens: auto;
}

.prose :deep(code) {
    word-break: break-all;
}

/* 修正：确保code内部也换行 */

/* 内容错误提示占位符 */
.error-content-placeholder {
    @apply text-gray-500 dark:text-gray-400 italic py-8 text-center border rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-gray-700 mt-6;
}

.link-style {
    @apply text-blue-600 dark:text-blue-400 underline mt-2 inline-block hover:text-blue-800 dark:hover:text-blue-300;
}
</style>
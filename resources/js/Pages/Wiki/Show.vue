<template>
    <!-- 主布局容器，用于整合导航链接 -->
    <MainLayout :navigationLinks="navigationLinks">

        <!-- 设置页面标题，根据是否为预览模式动态显示 -->

        <Head :title="page.title + (isPreview ? ' (预览)' : '')" />
        <div class="container mx-auto py-8 px-4 md:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row lg:space-x-8">
                <!-- 左侧主内容区域，占据大部分宽度 -->
                <div class="w-full lg:w-3/4">
                    <!-- 预览模式提示，只有在isPreview为true时显示 -->
                    <div v-if="isPreview"
                        class="mb-4 p-3 bg-blue-100 dark:bg-blue-900/50 border-l-4 border-blue-500 text-blue-700 dark:text-blue-300 rounded-md text-sm italic text-center">
                        <font-awesome-icon :icon="['fas', 'eye']" class="mr-2" /> 内容预览模式（未保存）
                    </div>
                    <!-- 页面主要内容卡片 -->
                    <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6 md:p-8">
                        <!-- 页面标题和元信息区 -->
                        <div class="mb-8 pb-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex flex-col md:flex-row justify-between md:items-start mb-4 gap-4">
                                <div>
                                    <h1
                                        class="text-3xl md:text-4xl font-bold mb-2 leading-tight text-gray-900 dark:text-gray-100 break-words">
                                        {{ page.title }}
                                        <!-- 版本号显示，预览模式或有当前版本时显示 -->
                                        <span
                                            v-if="isPreview || (currentVersion && currentVersion.version_number != null)"
                                            class="text-xl font-normal text-gray-500 dark:text-gray-400">
                                            (版本 {{ currentVersion?.version_number ?? '预览' }})
                                        </span>
                                    </h1>
                                    <!-- 页面创建和更新信息 -->
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
                                <!-- 操作按钮组（历史、编辑、解决冲突），预览模式下不显示 -->
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
                            <!-- 分类和标签显示区域 -->
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
                        <!-- 页面状态警告和草稿提示 -->
                        <div v-if="!isPreview && (isLocked || isConflictPage)" class="mb-6 space-y-4">
                            <!-- 页面锁定提示 -->
                            <div v-if="isLocked && !isConflictPage" class="alert-warning">
                                <div class="flex items-center">
                                    <font-awesome-icon :icon="['fas', 'lock']" class="mr-2 flex-shrink-0" />
                                    <p v-if="lockedBy" class="text-sm">该页面当前被 <strong>{{ lockedBy.name }}</strong>
                                        锁定编辑中<span v-if="page.locked_until"> (预计 {{ formatDateTime(page.locked_until) }}
                                            解锁)</span>。</p>
                                    <p v-else class="text-sm">页面已被锁定，无法获取锁定者信息。</p>
                                </div>
                            </div>
                            <!-- 页面冲突提示 -->
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
                        <!-- 草稿存在提示，只有在非预览、非冲突、非锁定状态下显示 -->
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

                        <!-- 页面内容展示区域 -->
                        <div ref="wikiContentContainerRef"
                            class="prose max-w-none prose-indigo lg:prose-lg xl:prose-xl wiki-content-display dark:prose-invert">
                            <div v-if="currentVersion && currentVersion.content" v-html="currentVersion.content"></div>
                            <div v-else class="error-content-placeholder">
                                <p>{{ error || (isConflictPage ? '页面冲突中，最新内容可能不可见。请解决冲突以查看或编辑最新内容。' : '该页面还没有内容。') }}
                                </p>
                                <!-- 链接到编辑或解决冲突页面，根据权限和状态显示 -->
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
                        <!-- 评论区，预览模式下不显示 -->
                        <div v-if="!isPreview" class="mt-12 pt-8 border-t border-gray-300 dark:border-gray-700">
                            <h3 class="text-2xl font-bold mb-6 text-gray-800 dark:text-gray-200">评论 ({{ commentsCount
                                }})</h3>
                            <!-- 评论表单，只有登录且有评论权限的用户才能看到 -->
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
                            <!-- 未登录用户的评论提示 -->
                            <div v-else
                                class="mb-8 p-4 bg-gray-100 dark:bg-gray-700/50 rounded-lg text-center text-sm text-gray-600 dark:text-gray-400">
                                <Link :href="route('login')"
                                    class="text-blue-600 dark:text-blue-400 underline hover:text-blue-800 dark:hover:text-blue-300">
                                登录</Link>后即可发表评论。
                            </div>
                            <!-- 评论列表，如果存在评论则遍历显示 -->
                            <div v-if="page.comments && page.comments.length > 0" class="space-y-6">
                                <div v-for="comment in page.comments" :key="comment.id"
                                    class="comment-item pb-4 border-b border-gray-200 dark:border-gray-700 last:border-b-0">
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
                                            <!-- 评论编辑表单 -->
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
                        <!-- 预览模式下的评论区占位，实际评论功能不在此显示 -->
                        <div v-if="isPreview" class="mt-12 pt-8 border-t border-gray-300 dark:border-gray-700">
                            <h3 class="text-xl font-semibold mb-4 text-gray-700 dark:text-gray-300">评论区</h3>
                            <div
                                class="p-4 bg-gray-100 dark:bg-gray-700/30 rounded-lg text-center text-sm text-gray-500 dark:text-gray-400 italic">
                                （预览模式下不显示评论）
                            </div>
                        </div>
                    </div>
                </div>
                <!-- 右侧目录区域 -->
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
        <!-- 消息闪现组件，用于显示操作成功或失败的提示 -->
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
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faExclamationTriangle, faExclamationCircle } from '@fortawesome/free-solid-svg-icons'; // 引入图标

// 主导航链接配置
const navigationLinks = mainNavigationLinks;
// 获取当前页面属性，如认证用户等
const pageProps = usePage().props;

// 定义组件接收的属性
const props = defineProps({
    page: { type: Object, required: true }, // Wiki页面数据
    currentVersion: { type: Object, default: null }, // 当前版本数据
    isLocked: { type: Boolean, default: false }, // 页面是否被锁定
    lockedBy: { type: Object, default: null }, // 锁定页面的用户
    draft: { type: Object, default: null }, // 用户草稿数据
    canEditPage: { type: Boolean, default: false }, // 当前用户是否可以编辑页面
    canResolveConflict: { type: Boolean, default: false }, // 当前用户是否可以解决冲突
    isConflictPage: { type: Boolean, default: false }, // 页面是否处于冲突状态
    error: { type: String, default: '' }, // 页面级错误信息
    comments: { type: Array, default: () => [] }, // 页面评论列表
    isPreview: { type: Boolean, default: false } // 是否为预览模式
});

// DOM元素引用
const wikiContentContainerRef = ref(null); // Wiki内容容器的引用
const tocContainerRef = ref(null); // 目录容器的引用
const flashMessage = ref(null); // FlashMessage 组件的引用

// 评论相关状态
const replyingToCommentId = ref(null); // 正在回复的评论ID
const editingCommentId = ref(null); // 正在编辑的评论ID

// 冲突解决模态框状态，此页面不再使用，保留以防万一
const showResolveConflictModal = ref(false);

// 目录滚动监听器相关状态
const activeTocId = ref(null); // 当前可视区域内激活的目录项ID
let observer = null; // Intersection Observer 实例

// --- 表单状态 ---
const commentForm = useForm({ content: '', parent_id: null }); // 发表评论表单
const replyForm = useForm({ content: '', parent_id: null }); // 回复评论表单
const editCommentForm = useForm({ content: '' }); // 编辑评论表单

// --- 计算属性 ---
// 计算评论总数，包括回复
const commentsCount = computed(() => {
    let count = 0;
    if (props.comments) {
        props.comments.forEach(comment => {
            count++; // 计算主评论
            if (comment.replies) count += comment.replies.length; // 计算回复
        });
    }
    return count;
});

// --- 方法 ---
// 生成用于目录ID和URL的 slug
const generateSlug = (text) => {
    if (!text) return '';
    return text.toLowerCase()
        .replace(/[\s\/\\]+/g, '-') // 将空格、斜杠、反斜杠替换为短横线
        .replace(/[^\w\u4E00-\u9FA5-]+/g, '') // 移除除字母、数字、下划线、中文和短横线外的字符
        .replace(/-+/g, '-') // 多个短横线合并为一个
        .replace(/^-+|-+$/g, ''); // 移除开头和结尾的短横线
};

// 生成唯一的DOM元素ID
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

// 生成页面目录
const generateTableOfContents = () => {
    nextTick(() => { // 确保在DOM更新后执行，以获取最新的内容结构
        const contentElement = wikiContentContainerRef.value;
        const tocContainer = tocContainerRef.value;
        const existingIds = new Set(); // 用于追踪已分配的ID，确保唯一性

        if (!tocContainer) {
            console.warn('无法找到 TOC 容器 (ref="tocContainerRef")');
            return;
        }
        // 清空旧目录内容
        tocContainer.innerHTML = '';

        if (!contentElement) {
            console.warn('无法找到内容元素 (ref="wikiContentContainerRef")');
            tocContainer.innerHTML = '<p class="text-gray-500 dark:text-gray-400 italic text-xs">无法生成目录：内容未加载。</p>';
            return;
        }

        // 获取所有标题元素
        const headings = contentElement.querySelectorAll('h1, h2, h3, h4, h5, h6');

        if (headings.length === 0) {
            tocContainer.innerHTML = '<p class="text-gray-500 dark:text-gray-400 italic text-xs">此页面没有目录。</p>';
            return;
        }

        const tocList = document.createElement('ul');
        tocList.classList.add('space-y-1.5', 'list-none', 'pl-0');

        headings.forEach((heading, index) => {
            const level = parseInt(heading.tagName.substring(1)); // 获取标题级别 (例如，H1 -> 1)
            const text = heading.textContent?.trim() || `无标题 ${index + 1}`; // 获取标题文本
            let id = heading.id; // 尝试使用现有ID

            // 确保ID唯一，如果不存在或已重复则生成新ID
            if (!id || existingIds.has(id)) {
                const baseSlug = generateSlug(text);
                id = generateUniqueId(`toc-${baseSlug || 'heading'}-${index}`, existingIds);
                heading.id = id; // 将生成的ID分配给标题元素
            } else {
                existingIds.add(id); // 将现有ID添加到集合中
            }

            const listItem = document.createElement('li');
            // 根据标题级别应用缩进
            listItem.style.paddingLeft = `${Math.max(0, level - 1) * 1}rem`;

            const link = document.createElement('a');
            link.href = `#${id}`;
            link.textContent = text;
            link.dataset.tocId = id; // 存储ID用于滚动监听
            link.classList.add(
                'toc-link',
                'block',
                'truncate',
                'transition-colors',
                'duration-150',
                'text-xs',
                'py-0.5',
                'text-gray-600', 'dark:text-gray-400',
                'hover:text-blue-600', 'dark:hover:text-blue-400'
            );

            // 目录链接点击事件，实现平滑滚动
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetElement = document.getElementById(id);
                if (targetElement) {
                    const offset = 80; // 偏移量，用于避免标题被固定导航栏遮挡
                    // 计算目标元素的滚动位置
                    const bodyRect = document.body.getBoundingClientRect().top;
                    const elementRect = targetElement.getBoundingClientRect().top;
                    const elementPosition = elementRect - bodyRect;
                    const offsetPosition = elementPosition - offset;

                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth' // 平滑滚动
                    });

                    // 可选：添加临时高亮效果
                    targetElement.classList.add('highlight-scroll');
                    setTimeout(() => {
                        targetElement.classList.remove('highlight-scroll');
                    }, 1000); // 高亮持续时间
                } else {
                    console.warn(`未找到目标元素，ID: ${id}`);
                }
            });

            listItem.appendChild(link);
            tocList.appendChild(listItem);
        });

        tocContainer.appendChild(tocList);
        // 如果不是预览模式，设置滚动监听器
        if (!props.isPreview) {
            setupTocScrollSpy();
        }
    });
};

// 设置 Intersection Observer 实现目录的滚动高亮
const setupTocScrollSpy = () => {
    if (observer) {
        observer.disconnect(); // 断开旧的观察器
    }
    const tocLinks = tocContainerRef.value?.querySelectorAll('.toc-link');
    if (!tocLinks || tocLinks.length === 0) {
        return;
    }

    // 获取所有标题元素对应的 DOM 节点
    const headingElements = Array.from(tocLinks).map(link => {
        const targetId = link.getAttribute('href')?.substring(1);
        return targetId ? document.getElementById(targetId) : null;
    }).filter(el => el !== null); // 过滤掉null值，确保元素存在

    if (headingElements.length === 0) {
        return;
    }

    // Intersection Observer 配置项
    const options = {
        rootMargin: '-80px 0px -60% 0px', // 顶部偏移量（固定导航栏高度），底部负边距使元素提前进入可视区
        threshold: 0 // 元素进入可视区（或rootMargin定义的区域）的任意部分时触发
    };

    observer = new IntersectionObserver(entries => {
        let latestActiveId = null;

        // 反向遍历，以最靠近屏幕顶部且正在交叉的元素为准
        entries.reverse().forEach(entry => {
            if (entry.isIntersecting) {
                latestActiveId = entry.target.id;
            }
        });

        // 备用逻辑：如果主区域没有交叉元素，则查找顶部阈值以上最近的元素
        if (!latestActiveId) {
            const elementsAboveThreshold = entries
                .filter(entry => entry.boundingClientRect.top < 80) // 检查元素顶部是否在80px阈值以上
                .map(entry => entry.target);
            if (elementsAboveThreshold.length > 0) {
                // 取这些元素中最后一个（即最靠近阈值的那个）
                latestActiveId = elementsAboveThreshold[elementsAboveThreshold.length - 1].id;
            }
        }

        activeTocId.value = latestActiveId; // 更新激活的目录ID

        // 更新目录链接的样式
        tocLinks.forEach(link => {
            link.classList.remove('is-active');
            if (link.dataset.tocId === activeTocId.value) {
                link.classList.add('is-active');
            }
        });
    }, options);

    // 观察所有标题元素
    headingElements.forEach(el => observer.observe(el));
};


// 用户头像背景颜色和首字母生成
const userColors = {}; // 存储用户ID到颜色类的映射
const bgColors = [ // 预定义的背景颜色类
    'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300', 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300', 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
    'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-300', 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300', 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300'
];
let colorIndex = 0; // 颜色索引，循环使用
const getAvatarBgClass = (userId) => {
    if (!userId) return bgColors[0]; // 对匿名用户使用默认颜色
    if (!userColors[userId]) {
        userColors[userId] = bgColors[colorIndex % bgColors.length];
        colorIndex++;
    }
    return userColors[userId];
};

// 获取用户姓名的首字母或首个汉字
const getInitials = (name) => {
    if (!name) return '?';
    const nameTrimmed = name.trim();
    if (!nameTrimmed) return '?';
    // 优先匹配汉字
    const chineseMatch = nameTrimmed.match(/[\u4E00-\u9FA5]/);
    if (chineseMatch) {
        return chineseMatch[0];
    }
    // 非汉字则取首字母
    const parts = nameTrimmed.split(/\s+/);
    if (parts.length > 0 && parts[0]) {
        return parts[0].charAt(0).toUpperCase();
    }
    return nameTrimmed.charAt(0).toUpperCase() || '?';
};


// 评论管理方法
// 检查用户是否可以管理某条评论（自己的评论或有特定权限）
const canManageComment = (comment) => {
    const user = pageProps.auth.user;
    if (!user) return false;
    return comment.user_id === user.id || user.permissions?.includes('wiki.moderate_comments');
};

// 提交新评论
const submitComment = () => {
    commentForm.parent_id = null; // 顶级评论没有父ID
    commentForm.post(route('wiki.comments.store', props.page.slug), {
        preserveScroll: true, // 保持滚动位置
        onSuccess: () => {
            commentForm.reset('content'); // 清空评论内容
            flashMessage.value?.addMessage('success', '评论发布成功！');
            router.reload({ only: ['comments'] }); // 仅重新加载评论数据
        },
        onError: (errors) => {
            const firstError = Object.values(errors).flat()[0];
            flashMessage.value?.addMessage('error', firstError || '评论发布失败');
        }
    });
};

// 切换回复表单的显示/隐藏状态
const toggleReply = (comment) => {
    if (replyingToCommentId.value === comment.id) {
        cancelReply(); // 如果已经在回复当前评论，则取消回复
    } else {
        replyingToCommentId.value = comment.id; // 设置要回复的评论ID
        replyForm.reset('content'); // 清空回复内容
        cancelEditComment(); // 关闭任何正在进行的评论编辑
    }
};

// 取消回复
const cancelReply = () => {
    replyingToCommentId.value = null;
    replyForm.reset('content');
    replyForm.clearErrors(); // 清除表单错误
};

// 提交回复
const submitReply = (parentComment) => {
    replyForm.parent_id = parentComment.id; // 设置父评论ID
    replyForm.post(route('wiki.comments.store', props.page.slug), {
        preserveScroll: true,
        onSuccess: () => {
            cancelReply(); // 关闭回复表单
            flashMessage.value?.addMessage('success', '回复成功！');
            router.reload({ only: ['comments'] }); // 重新加载评论数据
        },
        onError: (errors) => {
            const firstError = Object.values(errors).flat()[0];
            flashMessage.value?.addMessage('error', firstError || '回复失败');
        }
    });
};

// 切换评论编辑表单的显示/隐藏状态
const editComment = (comment) => {
    if (editingCommentId.value === comment.id) {
        cancelEditComment(); // 如果已经在编辑当前评论，则取消编辑
    } else {
        editingCommentId.value = comment.id; // 设置要编辑的评论ID
        editCommentForm.content = comment.content; // 加载当前评论内容到表单
        cancelReply(); // 关闭任何正在进行的回复
    }
};

// 取消编辑评论
const cancelEditComment = () => {
    editingCommentId.value = null;
    editCommentForm.reset('content');
    editCommentForm.clearErrors(); // 清除表单错误
};

// 更新评论
const updateComment = (comment) => {
    editCommentForm.put(route('wiki.comments.update', comment.id), {
        preserveScroll: true,
        onSuccess: () => {
            cancelEditComment(); // 关闭编辑表单
            flashMessage.value?.addMessage('success', '评论更新成功！');
            router.reload({ only: ['comments'] }); // 重新加载评论数据
        },
        onError: (errors) => {
            const firstError = Object.values(errors).flat()[0];
            flashMessage.value?.addMessage('error', firstError || '评论更新失败');
        }
    });
};

// 删除（隐藏）评论
const deleteComment = (comment) => {
    const typeText = comment.parent_id ? '回复' : '评论'; // 判断是回复还是评论
    if (confirm(`确定要隐藏这条${typeText}吗？此操作将对其他用户隐藏该内容。`)) {
        router.delete(route('wiki.comments.destroy', comment.id), {
            preserveScroll: true,
            onSuccess: () => {
                flashMessage.value?.addMessage('success', `${typeText}已隐藏！`);
                // 如果当前评论正在编辑或回复，则取消相关操作
                if (editingCommentId.value === comment.id) cancelEditComment();
                if (replyingToCommentId.value === comment.id || (comment.parent_id && replyingToCommentId.value === comment.parent_id)) cancelReply();
                router.reload({ only: ['comments'] }); // 重新加载评论数据
            },
            onError: (errors) => {
                const errorMsg = Object.values(errors).flat()[0] || `${typeText}删除失败，请重试`;
                flashMessage.value?.addMessage('error', errorMsg);
            }
        });
    }
};

// 生命周期钩子
onMounted(() => {
    generateTableOfContents(); // 组件挂载时生成目录
    // 处理页面初始加载时的错误和警告信息
    if (props.error && !props.isPreview) {
        flashMessage.value?.addMessage('error', props.error);
    }
    const pageLevelErrors = usePage().props.errors;
    if (pageLevelErrors && pageLevelErrors.general && !props.isPreview) {
        flashMessage.value?.addMessage('error', pageLevelErrors.general);
    }
    if (props.isConflictPage && !props.canResolveConflict && !props.isPreview) {
        flashMessage.value?.addMessage('warning', '此页面当前存在编辑冲突，您没有权限解决。');
    } else if (props.isConflictPage && props.canResolveConflict && !props.isPreview) {
        flashMessage.value?.addMessage('info', '此页面存在编辑冲突，您可以前往编辑页解决。');
    }
    // 如果不是预览模式，设置目录滚动监听器
    if (!props.isPreview) {
        setTimeout(setupTocScrollSpy, 100); // 延迟设置，确保DOM渲染完成
    }
});

onUnmounted(() => {
    if (observer) {
        observer.disconnect(); // 组件卸载时断开观察器，防止内存泄漏
    }
});

// 监听 currentVersion.content 属性的变化，如果内容更新则重新生成目录
watch(() => props.currentVersion?.content, (newContent, oldContent) => {
    if (newContent !== oldContent) {
        console.log("内容属性改变，重新生成目录...");
        generateTableOfContents();
    }
}, { immediate: false });

// 监听 comments 属性的变化 (可选，如果评论不是通过router.reload更新的)
watch(() => props.comments, () => {
    console.log("评论属性可能已更新。");
}, { deep: true });

</script>

<style scoped>
/* 滚动到目标时的高亮动画 */
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

/* 目录链接样式 */
.toc-link {
    @apply block truncate transition-colors duration-150 text-xs py-0.5 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400;
    padding-left: inherit;
}

/* 激活的目录链接样式 */
.toc-link.is-active {
    @apply text-blue-600 dark:text-blue-400 font-semibold;
    transform: translateX(4px);
    border-left: 2px solid;
    @apply border-blue-600 dark:border-blue-400;
    padding-left: calc(inherit - 2px + 1rem);
}

/* 目录子项的额外缩进 */
#toc-container ul ul .toc-link {
    padding-left: 1rem;
}

#toc-container ul ul ul .toc-link {
    padding-left: 2rem;
}

#toc-container ul ul ul ul .toc-link {
    padding-left: 3rem;
}

/* Wiki内容显示区域的基础排版样式 */
.wiki-content-display {
    @apply text-base text-gray-700 dark:text-gray-300;
}

.wiki-content-display :deep(p) {
    @apply leading-relaxed mb-5;
    line-height: 1.8;
}

/* Wiki内容显示区域的标题样式 */
.wiki-content-display :deep(h1),
.wiki-content-display :deep(h2),
.wiki-content-display :deep(h3),
.wiki-content-display :deep(h4),
.wiki-content-display :deep(h5),
.wiki-content-display :deep(h6) {
    @apply font-semibold text-gray-800 dark:text-gray-200 mt-10 mb-4;
    scroll-margin-top: 80px;
    line-height: 1.4;
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

/* 引用块样式 */
.wiki-content-display :deep(blockquote) {
    @apply border-l-4 border-blue-300 dark:border-blue-700 pl-5 italic text-gray-600 dark:text-gray-400 my-6 py-2 bg-blue-50/50 dark:bg-blue-900/20 rounded-r;
}

/* 代码块样式 */
.wiki-content-display :deep(pre) {
    @apply bg-gray-800 text-gray-100 p-4 rounded-lg overflow-x-auto my-6 text-sm shadow-md leading-relaxed dark:bg-black/50 dark:text-gray-200;
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
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

/* 代码高亮颜色 */
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
    @apply max-w-full h-auto my-8 rounded-lg shadow-lg mx-auto block border border-gray-300 dark:border-gray-700;
}

/* 表格样式 */
.wiki-content-display :deep(table) {
    @apply w-full my-8 border-collapse border border-gray-400 dark:border-gray-600 shadow-md;
    table-layout: auto;
}

.wiki-content-display :deep(th),
.wiki-content-display :deep(td) {
    @apply border border-gray-300 dark:border-gray-600 px-5 py-3 text-left text-sm;
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

/* 标签分类样式 */
.tag-category {
    @apply inline-block px-2.5 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full hover:bg-gray-300 transition dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600;
}

.tag-tag {
    @apply inline-block px-2.5 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full hover:bg-blue-200 transition dark:bg-blue-900/40 dark:text-blue-300 dark:hover:bg-blue-900/60;
}

/* 图标按钮样式 */
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

/* 警告/错误/信息提示框样式 */
.alert-error {
    @apply bg-red-100 border-l-4 border-red-500 text-red-800 p-4 rounded-md dark:bg-red-900/40 dark:border-red-600 dark:text-red-300;
}

.alert-warning {
    @apply bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-md dark:bg-yellow-900/40 dark:border-yellow-600 dark:text-yellow-300;
}

.alert-info {
    @apply bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 rounded-md dark:bg-blue-900/40 dark:border-blue-600 dark:text-blue-300;
}

/* 评论项交互样式 */
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

/* 深色模式下 Prose 排版组件的颜色变量覆盖 */
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
}

/* 强制文字换行，用于处理长单词或URL */
.break-words {
    overflow-wrap: break-word;
    word-break: break-word;
    -webkit-hyphens: auto;
    -moz-hyphens: auto;
    hyphens: auto;
}

/* 确保行内代码也能正确换行 */
.prose :deep(code) {
    word-break: break-all;
}

/* 内容区域的错误/无内容占位符样式 */
.error-content-placeholder {
    @apply text-gray-500 dark:text-gray-400 italic py-8 text-center border rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-gray-700 mt-6;
}

.link-style {
    @apply text-blue-600 dark:text-blue-400 underline mt-2 inline-block hover:text-blue-800 dark:hover:text-blue-300;
}
</style>
<template>
    <MainLayout :navigationLinks="navigationLinks">
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <h1 class="text-2xl font-bold mb-6">编辑 Wiki 页面</h1>
                <!-- 锁定警告 -->
                <div v-if="lockInfo && lockInfo.isLocked"
                    class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6">
                    <div class="flex items-center">
                        <font-awesome-icon :icon="['fas', 'lock']" class="mr-2" />
                        <p>
                            页面已被锁定，将于 {{ formatDateTime(lockInfo.lockedUntil) }} 自动解锁。
                            <span v-if="lockInfo.lockedBy && lockInfo.lockedBy.id === $page.props.auth.user.id">
                                (由您锁定)
                            </span>
                            <span v-else-if="lockInfo.lockedBy">
                                (由 {{ lockInfo.lockedBy.name }} 锁定)
                            </span>
                        </p>
                    </div>
                </div>

                <EditorsList :pageId="page.id" />
                <Discussion :pageId="page.id" />
                <!-- 草稿提示 -->
                <div v-if="hasDraft" class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6">
                    <div class="flex items-center">
                        <font-awesome-icon :icon="['fas', 'info-circle']" class="mr-2" />
                        <p>
                            正在编辑草稿版本，最后一次自动保存于 {{ formatDateTime(lastSaved) }}
                        </p>
                    </div>
                </div>

                <form @submit.prevent="savePage">
                    <div class="space-y-6">
                        <!-- 标题 -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                                标题
                            </label>
                            <input id="title" v-model="form.title" type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required />
                            <div v-if="form.errors.title" class="mt-1 text-sm text-red-600">
                                {{ form.errors.title }}
                            </div>
                        </div>

                        <!-- 内容编辑器 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                内容
                            </label>
                            <Editor v-model="form.content" :autosave="true" :pageId="page.id" @saved="onDraftSaved" />
                            <div v-if="form.errors.content" class="mt-1 text-sm text-red-600">
                                {{ form.errors.content }}
                            </div>
                        </div>

                        <!-- 分类 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                分类 <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="category in categories" :key="category.id" class="flex items-center">
                                    <input type="checkbox" :id="`category-${category.id}`" :value="category.id"
                                        v-model="form.category_ids"
                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                                    <label :for="`category-${category.id}`" class="ml-2 text-sm text-gray-700">
                                        {{ category.name }}
                                    </label>
                                </div>
                            </div>
                            <div v-if="form.errors.category_ids" class="mt-1 text-sm text-red-600">
                                {{ form.errors.category_ids }}
                            </div>
                        </div>

                        <!-- 标签 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                标签
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div v-for="tag in tags" :key="tag.id" class="flex items-center">
                                    <input type="checkbox" :id="`tag-${tag.id}`" :value="tag.id" v-model="form.tag_ids"
                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                                    <label :for="`tag-${tag.id}`" class="ml-2 text-sm text-gray-700">
                                        {{ tag.name }}
                                    </label>
                                </div>
                            </div>
                            <div v-if="form.errors.tag_ids" class="mt-1 text-sm text-red-600">
                                {{ form.errors.tag_ids }}
                            </div>
                        </div>

                        <!-- 提交说明 -->
                        <div>
                            <label for="comment" class="block text-sm font-medium text-gray-700 mb-1">
                                提交说明
                            </label>
                            <textarea id="comment" v-model="form.comment" rows="2"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="简要描述此次修改内容..."></textarea>
                        </div>

                        <!-- 提交按钮 -->
                        <div class="flex justify-end space-x-3">
                            <Link :href="route('wiki.show', page.slug)"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                            取消
                            </Link>

                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                                :disabled="form.processing">
                                保存
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Editor from '@/Components/Wiki/Editor.vue';
import axios from 'axios';
import { formatDateTime } from '@/utils/formatters';
import EditorsList from '@/Components/Wiki/EditorsList.vue';
import Discussion from '@/Components/Wiki/Discussion.vue';

import { mainNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = mainNavigationLinks;

const props = defineProps({
    page: {
        type: Object,
        required: true
    },
    content: {
        type: String,
        required: true
    },
    categories: {
        type: Array,
        required: true
    },
    tags: {
        type: Array,
        required: true
    },
    hasDraft: {
        type: Boolean,
        default: false
    },
    lockInfo: {
        type: Object,
        default: () => ({
            isLocked: false,
            lockedBy: null,
            lockedUntil: null
        })
    }
});

// 定时任务：定期刷新锁，防止锁过期
let lockRefreshTimer = null;

const form = useForm({
    title: props.page.title,
    content: props.content,
    category_ids: props.page.categories.map(c => c.id),
    tag_ids: props.page.tags.map(t => t.id),
    comment: '',
    version_id: props.page.current_version_id
});

const lastSaved = ref(new Date());

// 草稿保存回调
const onDraftSaved = (data) => {
    lastSaved.value = new Date(data.saved_at);
};

// 保存页面
const savePage = () => {
    form.put(route('wiki.update', props.page.slug), {
        // 自动解锁页面
        onFinish: () => {
            unlockPage();
        }
    });
};

// 解锁页面
const unlockPage = async () => {
    try {
        await axios.post(route('wiki.unlock'), {
            page_id: props.page.id
        });
    } catch (error) {
        console.error('解锁页面失败:', error);
    }
};

// 页面离开前解锁
const handleBeforeUnload = (event) => {
    // 用户关闭页面时尝试解锁
    navigator.sendBeacon(
        route('wiki.unlock'),
        JSON.stringify({
            page_id: props.page.id
        })
    );
};
onMounted(() => {
    if (props.lockInfo.isLocked && props.lockInfo.lockedBy?.id === $page.props.auth.user.id) {
        lockRefreshTimer = setInterval(refreshLock, 5 * 60 * 1000);
    }
    window.addEventListener('beforeunload', handleBeforeUnload);
});

onBeforeUnmount(() => {
    if (lockRefreshTimer) {
        clearInterval(lockRefreshTimer);
    }
    window.removeEventListener('beforeunload', handleBeforeUnload);
    unlockPage();
});

const refreshLock = async () => {
    try {
        await axios.post(route('wiki.refresh-lock'), {
            page_id: props.page.id
        });
    } catch (error) {
        console.error('刷新页面锁失败:', error);
    }
};

</script>
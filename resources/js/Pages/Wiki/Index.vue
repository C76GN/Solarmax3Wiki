// 3. 更新文章列表页面以整合分类导航
// resources/js/Pages/Wiki/Index.vue

<template>
    <MainLayout
        :navigationLinks="[{ href: '/wiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- 分类导航 -->
            <CategoryNav :categories="categories" :current-category="filters.category" />

            <!-- 文章列表卡片 -->
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden">
                <div class="p-6">
                    <!-- 标题和操作按钮 -->
                    <div class="mb-6 flex justify-between items-center">
                        <h2 class="text-2xl font-semibold text-gray-900">Wiki 文章</h2>
                        <Link v-if="can.create_article" :href="route('wiki.create')"
                            class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out">
                        创建新文章
                        </Link>
                    </div>

                    <!-- 搜索和筛选 -->
                    <div class="mb-6 flex gap-4 bg-gray-50 p-4 rounded-lg">
                        <div class="flex-1">
                            <input type="text" v-model="form.search" @input="search"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder="搜索文章...">
                        </div>
                        <div>
                            <select v-model="form.status" @change="search"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">所有状态</option>
                                <option value="draft">草稿</option>
                                <option value="published">已发布</option>
                            </select>
                        </div>
                    </div>

                    <!-- 文章列表 -->
                    <div class="space-y-6">
                        <div v-for="article in articles.data" :key="article.id"
                            class="border-b border-gray-200 pb-6 last:border-0 last:pb-0">
                            <div class="flex justify-between items-start">
                                <div>
                                    <Link :href="route('wiki.show', article.id)"
                                        class="text-xl font-medium text-blue-600 hover:text-blue-800">
                                    {{ article.title }}
                                    </Link>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <span v-for="category in article.categories" :key="category.id"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ category.name }}
                                        </span>
                                    </div>
                                    <div class="mt-2 text-sm text-gray-500">
                                        <span>{{ formatDate(article.created_at) }}</span>
                                        <span class="mx-2">•</span>
                                        <span>作者: {{ article.creator?.name || '未知' }}</span>
                                        <span class="mx-2">•</span>
                                        <span>浏览: {{ article.view_count }}</span>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <Link v-if="can.edit_article" :href="route('wiki.edit', article.id)"
                                        class="text-blue-600 hover:text-blue-900">编辑</Link>
                                    <button v-if="can.delete_article" @click="confirmDelete(article)"
                                        class="text-red-600 hover:text-red-900">删除</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 分页 -->
                    <div class="mt-6">
                        <Pagination :links="articles.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- 删除确认对话框 -->
        <ConfirmationModal :show="confirmingDeletion" title="确认删除文章" :message="'确定要删除文章吗？此操作不可恢复。'"
            @close="closeDeleteModal" @confirm="deleteArticle" />
    </MainLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import CategoryNav from '@/Components/Wiki/CategoryNav.vue';
import Pagination from '@/Components/Other/Pagination.vue';
import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';

const props = defineProps({
    articles: Object,
    categories: Array,
    filters: Object,
    can: Object
});

const form = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    category: props.filters.category || ''
});

const confirmingDeletion = ref(false);
const articleToDelete = ref(null);

const search = () => {
    router.get(route('wiki.index'), form, {
        preserveState: true,
        preserveScroll: true
    });
};

const confirmDelete = (article) => {
    articleToDelete.value = article;
    confirmingDeletion.value = true;
};

const closeDeleteModal = () => {
    confirmingDeletion.value = false;
    articleToDelete.value = null;
};

const deleteArticle = () => {
    if (articleToDelete.value) {
        router.delete(route('wiki.destroy', articleToDelete.value.id), {
            onSuccess: () => {
                closeDeleteModal();
            }
        });
    }
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleString('zh-CN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

</script>
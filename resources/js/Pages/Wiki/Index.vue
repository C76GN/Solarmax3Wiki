<template>
    <MainLayout :navigationLinks="navigationLinks">
        <div class="container mx-auto py-6 px-4">
            <div class="flex flex-col md:flex-row md:space-x-8">
                <!-- Sidebar -->
                <div class="w-full md:w-1/4 mb-6 md:mb-0 flex-shrink-0">
                    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-lg shadow-lg p-4 mb-6">
                        <h2 class="text-xl font-bold mb-4 text-gray-800 dark:text-gray-200">分类</h2>
                        <ul v-if="categories.length > 0" class="space-y-2">
                            <li v-for="category in categories" :key="category.id">
                                <Link :href="route('wiki.index', { category: category.slug })"
                                    class="flex items-center justify-between text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-150"
                                    :class="{ 'font-bold text-blue-800 dark:text-blue-300': filters.category === category.slug }">
                                <span>{{ category.name }}</span>
                                <span
                                    class="text-xs bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full px-2 py-0.5">{{
                                    category.pages_count }}</span>
                                </Link>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-gray-500 dark:text-gray-400 italic">暂无分类</p>
                    </div>
                    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-lg shadow-lg p-4">
                        <h2 class="text-xl font-bold mb-4 text-gray-800 dark:text-gray-200">标签</h2>
                        <div v-if="tags.length > 0" class="flex flex-wrap gap-2">
                            <Link v-for="tag in tags" :key="tag.id" :href="route('wiki.index', { tag: tag.slug })"
                                class="px-3 py-1 rounded-full text-sm transition-colors duration-150"
                                :class="filters.tag === tag.slug
                                    ? 'bg-blue-600 text-white hover:bg-blue-700'
                                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'">
                            {{ tag.name }} ({{ tag.pages_count }})
                            </Link>
                        </div>
                        <p v-else class="text-sm text-gray-500 dark:text-gray-400 italic">暂无标签</p>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="w-full md:w-3/4">
                    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-lg shadow-lg p-4 md:p-6 mb-6">
                        <!-- Header & Actions -->
                        <div
                            class="flex flex-col sm:flex-row justify-between items-center mb-6 pb-4 border-b dark:border-gray-700">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4 sm:mb-0">Wiki 页面</h1>
                            <div class="flex items-center gap-4 w-full sm:w-auto">
                                <div class="relative flex-grow sm:flex-grow-0">
                                    <input type="text" v-model="search" @keyup.enter="performSearch"
                                        placeholder="搜索页面标题或Slug..."
                                        class="py-2 pl-10 pr-4 w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" />
                                    <div
                                        class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500">
                                        <font-awesome-icon :icon="['fas', 'search']" />
                                    </div>
                                </div>
                                <Link
                                    v-if="$page.props.auth.user && $page.props.auth.user.permissions.includes('wiki.create')"
                                    :href="route('wiki.create')"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm whitespace-nowrap flex items-center">
                                <font-awesome-icon :icon="['fas', 'plus']" class="mr-2 h-4 w-4" /> 创建页面
                                </Link>
                            </div>
                        </div>

                        <!-- Filters Display -->
                        <div v-if="hasFilters"
                            class="flex items-center flex-wrap gap-2 mb-4 text-sm border-b dark:border-gray-700 pb-4">
                            <span class="mr-2 text-gray-600 dark:text-gray-400 font-medium">筛选条件:</span>
                            <div v-if="filters.category"
                                class="filter-tag bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                <span>分类: {{ getCategoryName(filters.category) }}</span>
                                <button @click="removeFilter('category')" class="filter-remove-btn">
                                    <font-awesome-icon :icon="['fas', 'times']" />
                                </button>
                            </div>
                            <div v-if="filters.tag"
                                class="filter-tag bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300">
                                <span>标签: {{ getTagName(filters.tag) }}</span>
                                <button @click="removeFilter('tag')" class="filter-remove-btn">
                                    <font-awesome-icon :icon="['fas', 'times']" />
                                </button>
                            </div>
                            <div v-if="filters.search"
                                class="filter-tag bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300">
                                <span>搜索: "{{ filters.search }}"</span>
                                <button @click="removeFilter('search')" class="filter-remove-btn">
                                    <font-awesome-icon :icon="['fas', 'times']" />
                                </button>
                            </div>
                            <button @click="clearFilters"
                                class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 underline ml-auto text-xs">
                                清除所有筛选
                            </button>
                        </div>

                        <!-- Pages List -->
                        <div v-if="pages.data.length > 0">
                            <div class="divide-y dark:divide-gray-700">
                                <div v-for="page in pages.data" :key="page.id"
                                    class="py-5 group transition duration-150 ease-in-out hover:bg-gray-50 dark:hover:bg-gray-700/50 px-2 -mx-2 rounded-md">
                                    <div class="flex flex-col sm:flex-row justify-between sm:items-start">
                                        <div class="flex-grow mb-3 sm:mb-0">
                                            <Link :href="route('wiki.show', page.slug)"
                                                class="text-xl font-bold text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 mb-1 block break-words">
                                            {{ page.title }}
                                            </Link>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                                <span>由 {{ page.creator?.name || '未知用户' }} 创建于 {{
                                                    formatDateShort(page.created_at) }}</span>
                                                <span class="mx-1">|</span>
                                                <span>最后更新于 {{ formatDate(page.updated_at) }}</span>
                                            </div>
                                            <!-- Simplified tags display -->
                                            <div class="flex flex-wrap gap-1.5 mt-2">
                                                <Link v-for="category in page.categories" :key="`cat-${category.id}`"
                                                    :href="route('wiki.index', { category: category.slug })"
                                                    class="tag-category">
                                                {{ category.name }}
                                                </Link>
                                                <Link v-for="tag in page.tags" :key="`tag-${tag.id}`"
                                                    :href="route('wiki.index', { tag: tag.slug })" class="tag-tag">
                                                {{ tag.name }}
                                                </Link>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-3 flex-shrink-0 ml-0 sm:ml-4">
                                            <Link
                                                v-if="$page.props.auth.user && $page.props.auth.user.permissions.includes('wiki.edit')"
                                                :href="route('wiki.edit', page.slug)"
                                                class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-150"
                                                title="编辑">
                                            <font-awesome-icon :icon="['fas', 'edit']" class="h-4 w-4" />
                                            </Link>
                                            <Link :href="route('wiki.history', page.slug)"
                                                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors duration-150"
                                                title="查看历史">
                                            <font-awesome-icon :icon="['fas', 'history']" class="h-4 w-4" />
                                            </Link>
                                            <!-- Potential Delete Button -->
                                            <!-- <button v-if="$page.props.auth.user && $page.props.auth.user.permissions.includes('wiki.delete')" @click="confirmDelete(page)" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-150" title="删除">
                                                <font-awesome-icon :icon="['fas', 'trash']" class="h-4 w-4"/>
                                            </button> -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <Pagination :links="pages.links" class="mt-6" />
                        </div>
                        <!-- Empty State -->
                        <div v-else class="py-12 text-center text-gray-500 dark:text-gray-400">
                            <font-awesome-icon :icon="['far', 'file-alt']"
                                class="text-5xl mb-4 text-gray-400 dark:text-gray-600" />
                            <p class="font-semibold">未找到符合条件的 Wiki 页面</p>
                            <p v-if="hasFilters" class="text-sm mt-2">尝试调整或清除筛选条件。</p>
                            <Link
                                v-else-if="$page.props.auth.user && $page.props.auth.user.permissions.includes('wiki.create')"
                                :href="route('wiki.create')"
                                class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
                            <font-awesome-icon :icon="['fas', 'plus']" class="mr-2" /> 创建第一个页面
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Pagination from '@/Components/Other/Pagination.vue';
import { formatDate, formatDateShort } from '@/utils/formatters'; // 确保引入了 formatDateShort
import { mainNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = mainNavigationLinks;

const props = defineProps({
    pages: { type: Object, required: true },
    categories: { type: Array, required: true },
    tags: { type: Array, required: true },
    filters: { type: Object, default: () => ({}) }
});

const search = ref(props.filters.search || '');

const hasFilters = computed(() => {
    // 检查是否有任何非空的过滤条件
    return Object.values(props.filters).some(value => value !== null && value !== '' && value !== undefined);
});

const performSearch = () => {
    // 仅在搜索词非空时添加到过滤器，或当搜索词为空但之前有搜索词时进行清除搜索
    const currentFilters = { ...props.filters };
    if (search.value.trim()) {
        currentFilters.search = search.value.trim();
    } else {
        delete currentFilters.search; // 如果搜索框为空，则移除搜索过滤器
    }

    router.get(route('wiki.index'), currentFilters, {
        preserveState: true, // 保留组件状态，例如滚动位置
        preserveScroll: true, // 保留滚动位置
        replace: true // 避免浏览器历史记录堆叠过多相同的过滤 URL
    });
};

const removeFilter = (filterKey) => {
    const newFilters = { ...props.filters };
    delete newFilters[filterKey];
    if (filterKey === 'search') {
        search.value = ''; // 清空搜索框
    }
    router.get(route('wiki.index'), newFilters, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

const clearFilters = () => {
    search.value = ''; // 清空搜索框
    router.get(route('wiki.index'), {}, { // 传递空对象以清除所有过滤器
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

const getCategoryName = (slug) => {
    const category = props.categories.find(c => c.slug === slug);
    return category ? category.name : slug;
};

const getTagName = (slug) => {
    const tag = props.tags.find(t => t.slug === slug);
    return tag ? tag.name : slug;
};
</script>

<style scoped>
/* Filter Tag Styles */
.filter-tag {
    @apply flex items-center rounded-full px-3 py-1 text-xs font-medium;
}

.filter-remove-btn {
    @apply ml-1.5 -mr-0.5 p-0.5 rounded-full inline-flex items-center justify-center hover:bg-opacity-20 hover:bg-current focus:outline-none;
}

.filter-remove-btn svg {
    @apply h-2 w-2;
}

/* Tag styles from Show.vue for consistency */
.tag-category {
    @apply inline-block px-2 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full hover:bg-gray-300 transition dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600;
}

.tag-tag {
    @apply inline-block px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full hover:bg-blue-200 transition dark:bg-blue-900/40 dark:text-blue-300 dark:hover:bg-blue-900/60;
}
</style>
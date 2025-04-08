<template>
    <MainLayout :navigationLinks="navigationLinks">
        <div class="container mx-auto py-6 px-4">
            <div class="flex flex-col md:flex-row md:space-x-8">
                <!-- 侧边栏：分类和标签 -->
                <div class="w-full md:w-1/4 mb-6 md:mb-0">
                    <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-4 mb-6">
                        <h2 class="text-xl font-bold mb-4">分类</h2>
                        <ul class="space-y-2">
                            <li v-for="category in categories" :key="category.id">
                                <Link :href="route('wiki.index', { category: category.slug })"
                                    class="text-blue-600 hover:text-blue-800 flex items-center justify-between"
                                    :class="{ 'font-bold': filters.category === category.slug }">
                                <span>{{ category.name }}</span>
                                <span class="text-gray-600 text-sm">({{ category.pages_count }})</span>
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-4">
                        <h2 class="text-xl font-bold mb-4">标签</h2>
                        <div class="flex flex-wrap gap-2">
                            <Link v-for="tag in tags" :key="tag.id" :href="route('wiki.index', { tag: tag.slug })"
                                class="px-3 py-1 rounded-full text-sm"
                                :class="filters.tag === tag.slug ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'">
                            {{ tag.name }} ({{ tag.pages_count }})
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- 主内容区：Wiki页面列表 -->
                <div class="w-full md:w-3/4">
                    <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-4 mb-6">
                        <div class="flex justify-between items-center mb-6">
                            <h1 class="text-2xl font-bold">Wiki 页面</h1>

                            <div class="flex items-center gap-4">
                                <!-- 搜索框 -->
                                <div class="relative">
                                    <input type="text" v-model="search" @keyup.enter="performSearch"
                                        placeholder="搜索页面..."
                                        class="py-2 pl-10 pr-4 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                                    <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                                        <font-awesome-icon :icon="['fas', 'search']" />
                                    </div>
                                </div>

                                <!-- 创建按钮 -->
                                <Link
                                    v-if="$page.props.auth.user && $page.props.auth.user.permissions.includes('wiki.create')"
                                    :href="route('wiki.create')"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                <font-awesome-icon :icon="['fas', 'plus']" class="mr-2" />
                                创建页面
                                </Link>
                            </div>
                        </div>

                        <!-- 筛选标签 -->
                        <div v-if="hasFilters" class="flex items-center mb-4 text-sm">
                            <span class="mr-2 text-gray-600">筛选条件:</span>

                            <div v-if="filters.category"
                                class="flex items-center bg-blue-100 text-blue-700 rounded-full px-3 py-1 mr-2">
                                <span>分类: {{ getCategoryName(filters.category) }}</span>
                                <button @click="removeFilter('category')"
                                    class="ml-2 text-blue-600 hover:text-blue-800">
                                    <font-awesome-icon :icon="['fas', 'times']" />
                                </button>
                            </div>

                            <div v-if="filters.tag"
                                class="flex items-center bg-blue-100 text-blue-700 rounded-full px-3 py-1 mr-2">
                                <span>标签: {{ getTagName(filters.tag) }}</span>
                                <button @click="removeFilter('tag')" class="ml-2 text-blue-600 hover:text-blue-800">
                                    <font-awesome-icon :icon="['fas', 'times']" />
                                </button>
                            </div>

                            <div v-if="filters.search"
                                class="flex items-center bg-blue-100 text-blue-700 rounded-full px-3 py-1 mr-2">
                                <span>搜索: {{ filters.search }}</span>
                                <button @click="removeFilter('search')" class="ml-2 text-blue-600 hover:text-blue-800">
                                    <font-awesome-icon :icon="['fas', 'times']" />
                                </button>
                            </div>

                            <button @click="clearFilters" class="text-gray-600 hover:text-gray-800 underline ml-2">
                                清除所有筛选
                            </button>
                        </div>

                        <!-- 页面列表 -->
                        <div v-if="pages.data.length > 0">
                            <div class="divide-y">
                                <div v-for="page in pages.data" :key="page.id" class="py-4">
                                    <div class="flex justify-between">
                                        <div>
                                            <Link :href="route('wiki.show', page.slug)"
                                                class="text-xl font-bold text-blue-600 hover:text-blue-800 mb-1 block">
                                            {{ page.title }}
                                            </Link>

                                            <div class="text-sm text-gray-600 mb-2">
                                                <span>由 {{ page.creator.name }} 创建于 {{ formatDate(page.created_at)
                                                }}</span>
                                            </div>

                                            <div class="flex flex-wrap gap-2 mt-2">
                                                <Link v-for="category in page.categories" :key="category.id"
                                                    :href="route('wiki.index', { category: category.slug })"
                                                    class="px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded hover:bg-gray-300">
                                                {{ category.name }}
                                                </Link>

                                                <Link v-for="tag in page.tags" :key="tag.id"
                                                    :href="route('wiki.index', { tag: tag.slug })"
                                                    class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded hover:bg-blue-200">
                                                {{ tag.name }}
                                                </Link>
                                            </div>
                                        </div>

                                        <div class="flex items-start space-x-2">
                                            <Link
                                                v-if="$page.props.auth.user && $page.props.auth.user.permissions.includes('wiki.edit')"
                                                :href="route('wiki.edit', page.slug)"
                                                class="text-blue-600 hover:text-blue-800">
                                            <font-awesome-icon :icon="['fas', 'edit']" />
                                            </Link>

                                            <Link :href="route('wiki.history', page.slug)"
                                                class="text-gray-600 hover:text-gray-800">
                                            <font-awesome-icon :icon="['fas', 'history']" />
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 分页 -->
                            <Pagination :links="pages.links" class="mt-6" />
                        </div>
                        <div v-else class="py-8 text-center text-gray-600">
                            <font-awesome-icon :icon="['far', 'file-alt']" class="text-4xl mb-4" />
                            <p>未找到符合条件的页面</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Pagination from '@/Components/Other/Pagination.vue';
import { formatDate } from '@/utils/formatters';
import { mainNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = mainNavigationLinks;

const props = defineProps({
    pages: {
        type: Object,
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
    filters: {
        type: Object,
        default: () => ({})
    }
});

const search = ref(props.filters.search || '');

const hasFilters = computed(() => {
    return Object.keys(props.filters).some(key => props.filters[key]);
});

const performSearch = () => {
    router.get(route('wiki.index'), {
        ...props.filters,
        search: search.value
    });
};

const removeFilter = (filter) => {
    const newFilters = { ...props.filters };
    delete newFilters[filter];
    router.get(route('wiki.index'), newFilters);
};

const clearFilters = () => {
    router.get(route('wiki.index'));
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
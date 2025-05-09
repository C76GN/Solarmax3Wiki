<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head title="Wiki 页面列表" />
        <div class="container mx-auto py-6 px-4">
            <div class="flex flex-col md:flex-row md:space-x-8">
                <!-- Sidebar (Categories & Tags) -->
                <div class="w-full md:w-1/4 mb-6 md:mb-0 flex-shrink-0">
                    <!-- Categories Card -->
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
                    <!-- Tags Card -->
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

                <!-- Main Content (Page List & Filters) -->
                <div class="w-full md:w-3/4">
                    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-lg shadow-lg p-4 md:p-6 mb-6">
                        <!-- Header & Filters -->
                        <div
                            class="flex flex-col sm:flex-row justify-between items-center mb-6 pb-4 border-b dark:border-gray-700">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4 sm:mb-0">Wiki 页面</h1>
                            <div class="flex items-center gap-4 w-full sm:w-auto flex-wrap">
                                <!-- Status Filter Dropdown -->
                                <div class="relative">
                                    <select v-model="statusFilter" @change="performStatusFilter"
                                        class="py-2 px-4 pr-8 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm appearance-none">
                                        <!-- Option Texts Should NOT contain the extra icon -->
                                        <option value="">所有状态</option>
                                        <option value="published">已发布</option>
                                        <option value="conflict">有冲突</option>
                                    </select>
                                    <!-- Custom Chevron Icon -->
                                    <div
                                        class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-gray-400 dark:text-gray-500">
                                        <font-awesome-icon :icon="['fas', 'chevron-down']" class="h-3 w-3" />
                                    </div>
                                </div>
                                <!-- Search Input -->
                                <div class="relative flex-grow sm:flex-grow-0">
                                    <input type="text" v-model="search" @keyup.enter="performSearch"
                                        placeholder="搜索页面标题或Slug..."
                                        class="py-2 pl-10 pr-4 w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" />
                                    <div
                                        class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500">
                                        <font-awesome-icon :icon="['fas', 'search']" />
                                    </div>
                                </div>
                                <!-- Create Page Button -->
                                <Link
                                    v-if="$page.props.auth.user && $page.props.auth.user.permissions.includes('wiki.create')"
                                    :href="route('wiki.create')"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm whitespace-nowrap flex items-center">
                                <font-awesome-icon :icon="['fas', 'plus']" class="mr-2 h-4 w-4" /> 创建页面
                                </Link>
                            </div>
                        </div>

                        <!-- Active Filters Display -->
                        <div v-if="hasFilters"
                            class="flex items-center flex-wrap gap-2 mb-4 text-sm border-b dark:border-gray-700 pb-4">
                            <span class="mr-2 text-gray-600 dark:text-gray-400 font-medium">筛选条件:</span>
                            <!-- Category Filter Tag -->
                            <div v-if="filters.category"
                                class="filter-tag bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                <span>分类: {{ getCategoryName(filters.category) }}</span>
                                <button @click="removeFilter('category')" class="filter-remove-btn">
                                    <font-awesome-icon :icon="['fas', 'times']" />
                                </button>
                            </div>
                            <!-- Tag Filter Tag -->
                            <div v-if="filters.tag"
                                class="filter-tag bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300">
                                <span>标签: {{ getTagName(filters.tag) }}</span>
                                <button @click="removeFilter('tag')" class="filter-remove-btn">
                                    <font-awesome-icon :icon="['fas', 'times']" />
                                </button>
                            </div>
                            <!-- Search Filter Tag -->
                            <div v-if="filters.search"
                                class="filter-tag bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300">
                                <span>搜索: "{{ filters.search }}"</span>
                                <button @click="removeFilter('search')" class="filter-remove-btn">
                                    <font-awesome-icon :icon="['fas', 'times']" />
                                </button>
                            </div>
                            <!-- Status Filter Tag -->
                            <div v-if="filters.status"
                                class="filter-tag bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300">
                                <span>状态: {{ filters.status === 'conflict' ? '有冲突' : '已发布' }}</span>
                                <button @click="removeFilter('status')" class="filter-remove-btn">
                                    <font-awesome-icon :icon="['fas', 'times']" />
                                </button>
                            </div>
                            <!-- Clear Filters Button -->
                            <button @click="clearFilters"
                                class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 underline ml-auto text-xs">
                                清除所有筛选
                            </button>
                        </div>

                        <!-- Page List -->
                        <div v-if="pages.data.length > 0">
                            <div class="divide-y dark:divide-gray-700">
                                <div v-for="page in pages.data" :key="page.id"
                                    class="py-5 group transition duration-150 ease-in-out hover:bg-gray-50 dark:hover:bg-gray-700/50 px-2 -mx-2 rounded-md">
                                    <div class="flex flex-col sm:flex-row justify-between sm:items-start">
                                        <div class="flex-grow mb-3 sm:mb-0 mr-4">
                                            <!-- Title and Status Badge -->
                                            <Link :href="route('wiki.show', page.slug)"
                                                class="text-xl font-bold text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 mb-1 block break-words">
                                            {{ page.title }}
                                            <span v-if="page.status === 'conflict'"
                                                class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300 animate-pulse"
                                                title="此页面存在编辑冲突">
                                                <font-awesome-icon :icon="['fas', 'exclamation-triangle']"
                                                    class="mr-1" /> 冲突
                                            </span>
                                            </Link>
                                            <!-- Meta Info -->
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                                <span>由 {{ page.creator?.name || '未知用户' }} 创建于 {{
                                                    formatDateShort(page.created_at) }}</span>
                                                <span class="mx-1">|</span>
                                                <span>最后更新于 {{ formatDate(page.updated_at) }}</span>
                                            </div>
                                            <!-- Categories and Tags -->
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
                                        <!-- Action Buttons -->
                                        <div class="flex items-center space-x-3 flex-shrink-0 ml-0 sm:ml-4">
                                            <Link
                                                v-if="$page.props.auth.user && $page.props.auth.user.permissions.includes('wiki.edit')"
                                                :href="route('wiki.edit', page.slug)"
                                                class="action-btn text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                                title="编辑">
                                            <font-awesome-icon :icon="['fas', 'edit']" />
                                            </Link>
                                            <Link :href="route('wiki.history', page.slug)"
                                                class="action-btn text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                                title="查看历史">
                                            <font-awesome-icon :icon="['fas', 'history']" />
                                            </Link>
                                            <button
                                                v-if="$page.props.auth.user && $page.props.auth.user.permissions.includes('wiki.delete')"
                                                @click="confirmDelete(page)"
                                                class="action-btn text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                                title="删除">
                                                <font-awesome-icon :icon="['fas', 'trash']" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Pagination -->
                            <Pagination :links="pages.links" class="mt-6" />
                        </div>
                        <!-- No Pages Found Message -->
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

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteConfirm" @close="cancelDelete" @confirm="deletePage" :showFooter="true" dangerAction
            confirmText="移至回收站" cancelText="取消" maxWidth="md">
            <template #default>
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                        <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="text-yellow-500 mr-2" />
                        确认删除页面
                    </h3>
                    <p class="mb-4 text-gray-600 dark:text-gray-300">
                        确定要将页面 “<strong class="font-semibold text-gray-800 dark:text-gray-200">{{ pageToDelete?.title
                        }}</strong>”
                        移至回收站吗？
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        页面可以在回收站中恢复或永久删除。
                    </p>
                </div>
            </template>
        </Modal>
        <!-- Flash Message Component -->
        <FlashMessage ref="flashMessage" />
    </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Pagination from '@/Components/Other/Pagination.vue';
import Modal from '@/Components/Modal/Modal.vue';
import FlashMessage from '@/Components/Other/FlashMessage.vue';
import { formatDate, formatDateShort } from '@/utils/formatters';
import { mainNavigationLinks } from '@/config/navigationConfig';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
// Ensure chevron-down is imported if not already globally available
import { faChevronDown } from '@fortawesome/free-solid-svg-icons'; // Correct import path


const navigationLinks = mainNavigationLinks;

// Props definition
const props = defineProps({
    pages: { type: Object, required: true },
    categories: { type: Array, required: true },
    tags: { type: Array, required: true },
    filters: { type: Object, default: () => ({}) } // Applied filters from backend
});

// Reactive refs
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || ''); // State for status filter
const flashMessage = ref(null);
const showDeleteConfirm = ref(false);
const pageToDelete = ref(null);

// Computed property to check if any filters are active
const hasFilters = computed(() => {
    // Check props.filters which reflects the actual applied filters from the controller
    return Object.entries(props.filters).some(([key, value]) => value !== null && value !== '' && value !== undefined);
});


// Apply search and filters
const applyFilters = () => {
    const currentFilters = { ...props.filters }; // Start with existing server-side filters (like category/tag)

    // Add or remove search filter based on input
    if (search.value.trim()) {
        currentFilters.search = search.value.trim();
    } else {
        delete currentFilters.search; // Remove if empty
    }

    // Add or remove status filter based on selection
    if (statusFilter.value) {
        currentFilters.status = statusFilter.value;
    } else {
        delete currentFilters.status; // Remove if empty
    }

    // Make the Inertia request
    router.get(route('wiki.index'), currentFilters, {
        preserveState: true, // Keep component state like input values
        preserveScroll: true, // Keep scroll position
        replace: true,        // Replace history entry instead of pushing
    });
};

// Event handlers for filters
const performSearch = () => {
    applyFilters();
};

const performStatusFilter = () => {
    applyFilters();
};


// Remove a specific filter and update
const removeFilter = (filterKey) => {
    const newFilters = { ...props.filters };
    delete newFilters[filterKey]; // Remove the specific key

    // Reset the local state if it corresponds to the removed key
    if (filterKey === 'search') {
        search.value = '';
    }
    if (filterKey === 'status') {
        statusFilter.value = '';
    }
    // For category/tag, they are already removed from newFilters derived from props

    // Fetch data with updated filters
    router.get(route('wiki.index'), newFilters, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

// Clear all filters and update
const clearFilters = () => {
    search.value = '';        // Clear local search state
    statusFilter.value = '';  // Clear local status state

    // Send request with empty filters object
    router.get(route('wiki.index'), {}, { // Use an empty object for filters
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};


// Helper functions to get names from slugs (useful for filter tags display)
const getCategoryName = (slug) => {
    const category = props.categories.find(c => c.slug === slug);
    return category ? category.name : slug; // Fallback to slug if name not found
};

const getTagName = (slug) => {
    const tag = props.tags.find(t => t.slug === slug);
    return tag ? tag.name : slug; // Fallback to slug if name not found
};

// Delete Page Confirmation Logic
const confirmDelete = (page) => {
    pageToDelete.value = page;
    showDeleteConfirm.value = true;
};

const cancelDelete = () => {
    showDeleteConfirm.value = false;
    pageToDelete.value = null;
};

const deletePage = () => {
    if (!pageToDelete.value) return;
    router.delete(route('wiki.destroy', pageToDelete.value.slug), {
        preserveScroll: true,
        onSuccess: () => {
            flashMessage.value?.addMessage('success', `页面 "${pageToDelete.value.title}" 已移至回收站。`);
            // The page list will update automatically via Inertia
        },
        onError: (errors) => {
            // Display error from backend, or a default message
            const errorMsg = Object.values(errors).flat()[0] || '删除页面失败，请重试。';
            flashMessage.value?.addMessage('error', errorMsg);
        },
        onFinish: () => {
            cancelDelete(); // Close the modal regardless of success/error
        }
    });
};

</script>

<style scoped>
/* Filter Tag Styles */
.filter-tag {
    @apply flex items-center rounded-full px-3 py-1 text-xs font-medium;
}

.filter-remove-btn {
    /* Style for the 'x' button within filter tags */
    @apply ml-1.5 -mr-0.5 p-0.5 rounded-full inline-flex items-center justify-center hover:bg-opacity-20 hover:bg-current focus:outline-none;
}

.filter-remove-btn svg {
    @apply h-2 w-2;
    /* Adjust size as needed */
}

/* Category and Tag pill styles */
.tag-category {
    @apply inline-block px-2 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full hover:bg-gray-300 transition dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 whitespace-nowrap;
}

.tag-tag {
    @apply inline-block px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full hover:bg-blue-200 transition dark:bg-blue-900/40 dark:text-blue-300 dark:hover:bg-blue-900/60 whitespace-nowrap;
}

/* Action button styles (Edit, History, Delete) */
.action-btn {
    @apply p-1 rounded transition-colors duration-150 ease-in-out;
    /* Basic styling for action buttons */
}

.action-btn svg {
    @apply h-4 w-4;
    /* Icon size */
}

/* Specific Modal content styling if needed */
.modal-content p {
    @apply text-gray-600 dark:text-gray-300;
}

.modal-content strong {
    @apply font-semibold text-gray-800 dark:text-gray-200;
}
</style>
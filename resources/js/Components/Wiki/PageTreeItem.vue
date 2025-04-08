<!-- 新增文件: resources/js/Components/Wiki/PageTreeItem.vue -->

<template>
    <li class="tree-item">
        <div class="flex items-center py-1" :class="{ 'bg-blue-50 -mx-2 px-2 rounded': isCurrentPage }">
            <button v-if="hasChildren" @click="toggleExpanded" class="w-5 h-5 mr-1 flex items-center justify-center">
                <font-awesome-icon :icon="['fas', expanded ? 'chevron-down' : 'chevron-right']" size="xs" />
            </button>
            <span v-else class="w-5 h-5 mr-1"></span>

            <Link :href="route('wiki.show', page.slug)" class="flex-grow truncate hover:text-blue-600"
                :class="{ 'font-bold text-blue-600': isCurrentPage }">
            {{ page.title }}
            </Link>

            <div class="flex-shrink-0 ml-2">
                <Link v-if="$page.props.auth.user && $page.props.auth.user.permissions.includes('wiki.edit')"
                    :href="route('wiki.edit', page.slug)" class="text-gray-500 hover:text-blue-600">
                <font-awesome-icon :icon="['fas', 'edit']" size="xs" />
                </Link>
            </div>
        </div>

        <ul v-if="hasChildren && expanded" class="ml-4 pl-2 border-l border-gray-200">
            <PageTreeItem v-for="childPage in childPages" :key="childPage.id" :page="childPage" :all-pages="allPages"
                :current-page-id="currentPageId" />
        </ul>
    </li>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    page: {
        type: Object,
        required: true
    },
    allPages: {
        type: Array,
        required: true
    },
    currentPageId: {
        type: Number,
        default: null
    }
});

const expanded = ref(true);

// 是否为当前页面
const isCurrentPage = computed(() => {
    return props.page.id === props.currentPageId;
});

// 子页面列表
const childPages = computed(() => {
    return props.allPages.filter(p => p.parent_id === props.page.id)
        .sort((a, b) => a.order - b.order);
});

// 是否有子页面
const hasChildren = computed(() => {
    return childPages.value.length > 0;
});

// 切换展开/折叠状态
const toggleExpanded = () => {
    expanded.value = !expanded.value;
};
</script>
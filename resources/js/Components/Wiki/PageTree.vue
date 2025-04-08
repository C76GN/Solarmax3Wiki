<!-- 新增文件: resources/js/Components/Wiki/PageTree.vue -->

<template>
    <div class="page-tree">
        <h2 class="text-xl font-bold mb-4">页面结构</h2>
        <div v-if="loading" class="text-center py-4">
            <font-awesome-icon :icon="['fas', 'spinner']" spin class="text-gray-500" />
            <p class="text-sm text-gray-500 mt-2">加载中...</p>
        </div>
        <div v-else-if="pages.length === 0" class="text-center py-4 text-gray-500">
            暂无页面结构
        </div>
        <ul v-else class="tree-root">
            <PageTreeItem v-for="page in rootPages" :key="page.id" :page="page" :all-pages="pages"
                :current-page-id="currentPageId" />
        </ul>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import PageTreeItem from './PageTreeItem.vue';
import axios from 'axios';

const props = defineProps({
    currentPageId: {
        type: Number,
        default: null
    }
});

const pages = ref([]);
const loading = ref(true);

// 根页面（没有父页面的页面）
const rootPages = computed(() => {
    return pages.value.filter(page => !page.parent_id);
});

onMounted(async () => {
    await loadPages();
});

const loadPages = async () => {
    try {
        const response = await axios.get(route('wiki.page-tree'));
        pages.value = response.data.pages;
        loading.value = false;
    } catch (error) {
        console.error('加载页面树失败:', error);
        loading.value = false;
    }
};
</script>

<style scoped>
.tree-root {
    margin-left: 0;
    padding-left: 0;
}
</style>
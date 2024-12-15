<template>
    <div class="wiki-link-autocomplete" v-if="show">
        <div class="autocomplete-panel">
            <div v-for="page in filteredPages" :key="page.id" class="autocomplete-item"
                :class="{ active: selectedIndex === page.id }" @click="selectPage(page)"
                @mouseenter="selectedIndex = page.id">
                <span class="title">{{ page.title }}</span>
                <span class="category" v-if="page.categories.length">
                    {{ page.categories[0].name }}
                </span>
            </div>
            <div v-if="filteredPages.length === 0" class="no-results">
                无匹配结果
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
    show: Boolean,
    query: String,
    editor: Object
});

const emit = defineEmits(['select', 'hide']);

const pages = ref([]);
const selectedIndex = ref(null);
const loading = ref(false);

// 过滤页面列表
const filteredPages = computed(() => {
    if (!props.query) return [];
    const query = props.query.toLowerCase();
    return pages.value.filter(page =>
        page.title.toLowerCase().includes(query)
    ).slice(0, 10); // 限制显示数量
});

// 搜索页面
const searchPages = debounce(async (query) => {
    if (!query) return;
    loading.value = true;
    try {
        const response = await fetch(`/api/wiki/search?q=${encodeURIComponent(query)}`);
        const data = await response.json();
        pages.value = data;
    } catch (error) {
        console.error('Failed to search pages:', error);
    } finally {
        loading.value = false;
    }
}, 300);

// 监听查询变化
watch(() => props.query, (newQuery) => {
    if (newQuery) {
        searchPages(newQuery);
    }
});

// 选择页面
const selectPage = (page) => {
    emit('select', page);
    emit('hide');
};

// 键盘导航
const handleKeyDown = (e) => {
    if (!props.show) return;

    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            if (selectedIndex.value === null) {
                selectedIndex.value = filteredPages.value[0]?.id;
            } else {
                const currentIndex = filteredPages.value.findIndex(p => p.id === selectedIndex.value);
                if (currentIndex < filteredPages.value.length - 1) {
                    selectedIndex.value = filteredPages.value[currentIndex + 1].id;
                }
            }
            break;
        case 'ArrowUp':
            e.preventDefault();
            if (selectedIndex.value !== null) {
                const currentIndex = filteredPages.value.findIndex(p => p.id === selectedIndex.value);
                if (currentIndex > 0) {
                    selectedIndex.value = filteredPages.value[currentIndex - 1].id;
                } else {
                    selectedIndex.value = null;
                }
            }
            break;
        case 'Enter':
            e.preventDefault();
            if (selectedIndex.value !== null) {
                const selectedPage = filteredPages.value.find(p => p.id === selectedIndex.value);
                if (selectedPage) {
                    selectPage(selectedPage);
                }
            }
            break;
        case 'Escape':
            e.preventDefault();
            emit('hide');
            break;
    }
};

// 设置全局键盘事件监听
onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
});
</script>

<style scoped>
.wiki-link-autocomplete {
    position: absolute;
    z-index: 1000;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    max-width: 24rem;
    width: 100%;
}

.autocomplete-panel {
    max-height: 16rem;
    overflow-y: auto;
}

.autocomplete-item {
    padding: 0.5rem 1rem;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.autocomplete-item:hover,
.autocomplete-item.active {
    background-color: #f3f4f6;
}

.autocomplete-item .title {
    font-weight: 500;
    color: #111827;
}

.autocomplete-item .category {
    font-size: 0.875rem;
    color: #6b7280;
}

.no-results {
    padding: 0.5rem 1rem;
    color: #6b7280;
    text-align: center;
}
</style>
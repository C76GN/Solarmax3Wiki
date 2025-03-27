// 修改文件: resources/js/Components/Wiki/WikiLinkAutocomplete.vue

<template>
    <div class="wiki-link-autocomplete" v-if="show">
        <div class="autocomplete-header">
            <span class="autocomplete-title">Wiki页面搜索</span>
            <button class="autocomplete-close" @click="$emit('hide')">&times;</button>
        </div>

        <div class="autocomplete-panel">
            <div v-if="loading" class="autocomplete-loading">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <span>搜索中...</span>
            </div>

            <div v-else-if="filteredPages.length" class="autocomplete-results">
                <div v-for="page in filteredPages" :key="page.id" class="autocomplete-item"
                    :class="{ active: selectedIndex === page.id }" @click="selectPage(page)"
                    @mouseenter="selectedIndex = page.id">
                    <div class="item-content">
                        <span class="title">{{ page.title }}</span>
                        <div class="categories" v-if="page.categories && page.categories.length">
                            <span v-for="category in page.categories" :key="category.id" class="category">
                                {{ category.name }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="no-results">
                <p>找不到匹配 "{{ query }}" 的结果</p>
                <div class="create-new" @click="createNewPage">
                    <span class="create-icon">+</span>
                    <span>创建新页面"{{ query }}"</span>
                </div>
            </div>
        </div>

        <div class="autocomplete-footer">
            <div class="autocomplete-tips">
                <span class="tip"><kbd>↑</kbd><kbd>↓</kbd> 导航</span>
                <span class="tip"><kbd>Enter</kbd> 选择</span>
                <span class="tip"><kbd>Esc</kbd> 关闭</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
    show: Boolean,
    query: String,
    editor: Object
});

const emit = defineEmits(['select', 'hide', 'create-page']);

const pages = ref([]);
const selectedIndex = ref(null);
const loading = ref(false);

const filteredPages = computed(() => {
    if (!props.query) return [];

    const query = props.query.toLowerCase();
    return pages.value
        .filter(page => page.title.toLowerCase().includes(query))
        .slice(0, 10);
});

const searchPages = debounce(async (query) => {
    if (!query || query.length < 2) return;

    loading.value = true;
    try {
        const response = await fetch(`/api/wiki/search?q=${encodeURIComponent(query)}`);
        if (!response.ok) {
            throw new Error('搜索请求失败');
        }
        pages.value = await response.json();

        // 如果有结果并且没有选中项，则选择第一项
        if (pages.value.length > 0 && selectedIndex.value === null) {
            selectedIndex.value = pages.value[0].id;
        }
    } catch (error) {
        console.error('搜索Wiki页面失败:', error);
    } finally {
        loading.value = false;
    }
}, 300);

const selectPage = (page) => {
    emit('select', page);
};

const createNewPage = () => {
    // 发出创建新页面的事件
    emit('create-page', props.query);
    emit('hide');
};

const handleKeyDown = (e) => {
    if (!props.show) return;

    const pagesList = filteredPages.value;
    const currentIndex = pagesList.findIndex(p => p.id === selectedIndex.value);

    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            if (pagesList.length === 0) return;

            if (currentIndex === -1 || currentIndex === pagesList.length - 1) {
                selectedIndex.value = pagesList[0].id;
            } else {
                selectedIndex.value = pagesList[currentIndex + 1].id;
            }
            break;

        case 'ArrowUp':
            e.preventDefault();
            if (pagesList.length === 0) return;

            if (currentIndex === -1 || currentIndex === 0) {
                selectedIndex.value = pagesList[pagesList.length - 1].id;
            } else {
                selectedIndex.value = pagesList[currentIndex - 1].id;
            }
            break;

        case 'Enter':
            e.preventDefault();
            if (selectedIndex.value !== null) {
                const selectedPage = pagesList.find(p => p.id === selectedIndex.value);
                if (selectedPage) {
                    selectPage(selectedPage);
                }
            } else if (pagesList.length === 0 && props.query) {
                createNewPage();
            }
            break;

        case 'Escape':
            e.preventDefault();
            emit('hide');
            break;
    }
};

watch(() => props.query, (newQuery) => {
    if (newQuery) {
        searchPages(newQuery);
    }
});

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
    width: 350px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    display: flex;
    flex-direction: column;
    max-height: 350px;
}

.autocomplete-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 1rem;
    border-bottom: 1px solid #e5e7eb;
    background-color: #f9fafb;
    border-top-left-radius: 0.375rem;
    border-top-right-radius: 0.375rem;
}

.autocomplete-title {
    font-weight: 500;
    font-size: 0.875rem;
    color: #374151;
}

.autocomplete-close {
    background: none;
    border: none;
    font-size: 1.25rem;
    color: #6b7280;
    cursor: pointer;
}

.autocomplete-close:hover {
    color: #374151;
}

.autocomplete-panel {
    flex: 1;
    overflow-y: auto;
}

.autocomplete-loading,
.no-results {
    padding: 1rem;
    text-align: center;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
}

.autocomplete-loading {
    gap: 0.5rem;
}

.autocomplete-results {
    padding: 0.5rem 0;
}

.autocomplete-item {
    padding: 0.5rem 1rem;
    cursor: pointer;
    transition: background-color 0.2s;
}

.autocomplete-item:hover,
.autocomplete-item.active {
    background-color: #f3f4f6;
}

.item-content {
    display: flex;
    flex-direction: column;
}

.title {
    font-weight: 500;
    color: #111827;
}

.categories {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    margin-top: 0.25rem;
}

.category {
    font-size: 0.75rem;
    padding: 0.125rem 0.375rem;
    background-color: #e5e7eb;
    color: #4b5563;
    border-radius: 0.25rem;
    white-space: nowrap;
}

.create-new {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    margin-top: 0.5rem;
    border: 1px dashed #e5e7eb;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: background-color 0.2s;
}

.create-new:hover {
    background-color: #f3f4f6;
}

.create-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.5rem;
    width: 1.25rem;
    height: 1.25rem;
    background-color: #4f46e5;
    color: white;
    font-weight: bold;
    border-radius: 50%;
}

.autocomplete-footer {
    padding: 0.5rem 1rem;
    border-top: 1px solid #e5e7eb;
    background-color: #f9fafb;
    border-bottom-left-radius: 0.375rem;
    border-bottom-right-radius: 0.375rem;
}

.autocomplete-tips {
    display: flex;
    justify-content: center;
    gap: 1rem;
    font-size: 0.75rem;
    color: #6b7280;
}

.tip {
    display: flex;
    align-items: center;
}

kbd {
    display: inline-block;
    padding: 0.125rem 0.25rem;
    margin: 0 0.125rem;
    font-family: monospace;
    font-size: 0.75rem;
    line-height: 1;
    color: #374151;
    background-color: #e5e7eb;
    border-radius: 0.25rem;
    box-shadow: 0 1px 0 rgba(0, 0, 0, 0.1);
}
</style>
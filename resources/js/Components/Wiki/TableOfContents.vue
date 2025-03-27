// 修改文件: resources/js/Components/Wiki/TableOfContents.vue

<template>
    <div v-if="headers.length > 0" ref="tocElement" class="toc bg-white/80 backdrop-blur-sm rounded-lg shadow p-4 mb-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-medium text-gray-900">目录</h3>
            <button @click="toggleCollapse" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                <ChevronUpIcon v-if="!collapsed" class="w-5 h-5" />
                <ChevronDownIcon v-else class="w-5 h-5" />
            </button>
        </div>

        <TransitionRoot as="div" :show="!collapsed" enter="transition-all duration-300 ease-out"
            enter-from="opacity-0 max-h-0" enter-to="opacity-100 max-h-[500px]"
            leave="transition-all duration-200 ease-in" leave-from="opacity-100 max-h-[500px]"
            leave-to="opacity-0 max-h-0" class="overflow-hidden">
            <div class="toc-items pr-1 space-y-1" :class="{ 'max-h-[300px] overflow-y-auto': headers.length > 10 }">
                <div v-for="(header, index) in headers" :key="index" :class="[
                    'transition hover:bg-gray-50 rounded',
                    getIndentClass(header.level),
                ]">
                    <a :href="`#${header.id}`" :class="[
                        'block py-1 text-gray-800 hover:text-blue-600 transition',
                        { 'font-medium': isSectionActive(header.id) }
                    ]" @click.prevent="scrollToHeader(header.id)">
                        {{ header.text }}
                    </a>
                </div>
            </div>
        </TransitionRoot>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import { ChevronUpIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';
import { TransitionRoot } from '@headlessui/vue';

const props = defineProps({
    headers: {
        type: Array,
        default: () => []
    },
    offset: {
        type: Number,
        default: 100 // 滚动偏移量
    }
});

const tocElement = ref(null);
const collapsed = ref(false);
const activeSection = ref('');
const headerElements = ref([]);

// 根据标题级别返回缩进类
const getIndentClass = (level) => {
    const classes = {
        1: 'pl-0',
        2: 'pl-0',
        3: 'pl-4',
        4: 'pl-8',
        5: 'pl-12',
        6: 'pl-16'
    };
    return classes[level] || 'pl-0';
};

// 切换目录折叠状态
const toggleCollapse = () => {
    collapsed.value = !collapsed.value;
    // 保存折叠状态到本地存储
    localStorage.setItem('wiki-toc-collapsed', collapsed.value.toString());
};

// 滚动到指定标题
const scrollToHeader = (id) => {
    const element = document.getElementById(id);
    if (element) {
        const headerRect = element.getBoundingClientRect();
        const offset = props.offset;

        window.scrollTo({
            top: window.scrollY + headerRect.top - offset,
            behavior: 'smooth'
        });

        // 添加高亮动画
        element.classList.add('bg-yellow-100');
        setTimeout(() => {
            element.classList.remove('bg-yellow-100');
        }, 1500);
    }
};

// 判断当前滚动位置是否在该段落
const isSectionActive = (id) => {
    return activeSection.value === id;
};

// 更新活跃段落
const updateActiveSection = () => {
    if (headerElements.value.length === 0) return;

    const scrollPosition = window.scrollY;
    const offset = props.offset + 20;

    // 查找当前可见的段落
    for (let i = 0; i < headerElements.value.length; i++) {
        const element = headerElements.value[i];
        const rect = element.getBoundingClientRect();
        if (rect.top <= offset) {
            activeSection.value = element.id;
        } else {
            break;
        }
    }
};

// 监听滚动事件
const handleScroll = () => {
    requestAnimationFrame(updateActiveSection);
};

// 初始化目录状态
const initToc = () => {
    // 从本地存储中加载折叠状态
    const savedCollapsed = localStorage.getItem('wiki-toc-collapsed');
    if (savedCollapsed !== null) {
        collapsed.value = savedCollapsed === 'true';
    }

    // 收集页面中的标题元素
    headerElements.value = [];
    props.headers.forEach(header => {
        const element = document.getElementById(header.id);
        if (element) {
            headerElements.value.push(element);
        }
    });

    // 首次更新活跃段落
    updateActiveSection();
};

// 在标题变更时重新初始化
watch(() => props.headers, () => {
    setTimeout(initToc, 100);
}, { deep: true });

// 生命周期钩子
onMounted(() => {
    window.addEventListener('scroll', handleScroll);
    initToc();
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<style scoped>
.toc-items::-webkit-scrollbar {
    width: 4px;
}

.toc-items::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.toc-items::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.toc-items::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
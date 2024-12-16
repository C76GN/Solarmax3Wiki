// FileName: /var/www/Solarmax3Wiki/resources/js/Components/Wiki/TableOfContents.vue
<template>
    <div class="wiki-toc">
        <h3 class="text-lg font-medium text-gray-900 mb-4">目录</h3>
        <nav class="space-y-1">
            <template v-for="(header, index) in headers" :key="index">
                <a :href="`#${header.id}`" class="block py-2 transition-colors duration-200 relative pl-4" :class="[
                    header.level === 2 ? 'pl-4' : header.level === 3 ? 'pl-8' : 'pl-12',
                    currentSection === header.id
                        ? 'text-blue-600 bg-blue-50 border-l-4 border-blue-500'
                        : 'text-gray-600 hover:text-blue-600 hover:bg-gray-50'
                ]" @click.prevent="scrollToHeader(header.id)">
                    {{ header.text }}
                </a>
            </template>
        </nav>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    headers: {
        type: Array,
        required: true
    }
});

const currentSection = ref('');

// 滚动到指定标题
const scrollToHeader = (id) => {
    const element = document.getElementById(id);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
};

// 监听滚动更新当前位置
const updateCurrentSection = () => {
    const headerElements = props.headers.map(header => document.getElementById(header.id));

    // 找到当前视窗中最上方的标题
    let currentHeader = null;
    let minDistance = Infinity;

    headerElements.forEach(element => {
        if (!element) return;

        const rect = element.getBoundingClientRect();
        const distanceFromTop = Math.abs(rect.top);

        if (distanceFromTop < minDistance) {
            minDistance = distanceFromTop;
            currentHeader = element;
        }
    });

    if (currentHeader) {
        currentSection.value = currentHeader.id;
    }
};

// 使用 Intersection Observer 监听标题元素的可见性
const setupIntersectionObserver = () => {
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    currentSection.value = entry.target.id;
                }
            });
        },
        {
            rootMargin: '-20% 0px -80% 0px'
        }
    );

    props.headers.forEach(header => {
        const element = document.getElementById(header.id);
        if (element) {
            observer.observe(element);
        }
    });

    return observer;
};

let observer = null;

onMounted(() => {
    observer = setupIntersectionObserver();
    window.addEventListener('scroll', updateCurrentSection);
});

onUnmounted(() => {
    if (observer) {
        observer.disconnect();
    }
    window.removeEventListener('scroll', updateCurrentSection);
});
</script>

<style scoped>
.wiki-toc {
    position: sticky;
    top: 2rem;
    max-height: calc(100vh - 4rem);
    overflow-y: auto;
    scrollbar-width: thin;
}

.wiki-toc::-webkit-scrollbar {
    width: 4px;
}

.wiki-toc::-webkit-scrollbar-track {
    background: transparent;
}

.wiki-toc::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.5);
    border-radius: 2px;
}
</style>
<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { formatDate, formatDateShort, formatDateTime } from '@/utils/formatters';
import { debounce } from 'lodash';

// 定义组件接收的属性
const props = defineProps({
    form: { type: Object, required: true }, // 包含页面标题、内容、分类ID、标签ID等表单数据
    categories: { type: Array, required: true }, // 所有可用的分类列表
    tags: { type: Array, required: true }, // 所有可用的标签列表
    page: { type: Object, default: null }, // 仅在编辑页面时传递，包含页面原始信息
    currentVersion: { type: Object, default: null }, // 仅在编辑页面时传递，包含当前内容的版本信息
});

const pageProps = usePage().props; // 获取 Inertia 页面属性，例如认证用户信息
const previewContentRef = ref(null); // 引用预览内容容器
const tocContainerRef = ref(null); // 引用目录容器
const activeTocId = ref(null); // 当前在视口中激活的目录项ID
let observer = null; // Intersection Observer 实例，用于目录滚动高亮

// --- 用于显示信息的计算属性 ---
const displayTitle = computed(() => props.form.title || '无标题'); // 显示的页面标题

const displayCreator = computed(() => {
    // 如果是编辑页面，显示原始创建者；否则显示当前用户
    if (props.page) {
        return props.page.creator?.name || '未知用户';
    }
    return pageProps.auth?.user?.name || '当前用户';
});

const displayCreatedAt = computed(() => {
    // 如果是编辑页面，显示原始创建时间；否则显示“刚刚”
    if (props.page) {
        return formatDateShort(props.page.created_at);
    }
    return '刚刚';
});

const displayUpdater = computed(() => {
    // 如果有当前版本信息的创建者，显示该创建者；否则显示页面创建者；最后回退到当前用户
    if (props.currentVersion?.creator) {
        return props.currentVersion.creator.name;
    } else if (props.page) {
        return props.page.creator?.name || '未知用户';
    }
    return pageProps.auth?.user?.name || '当前用户';
});

const displayUpdatedAt = computed(() => {
    // 如果有当前版本信息，显示其创建时间；否则显示页面更新时间；最后回退到“现在”
    if (props.currentVersion) {
        return formatDateTime(props.currentVersion.created_at);
    } else if (props.page) {
        return formatDateTime(props.page.updated_at);
    }
    return '现在';
});

const displayCategories = computed(() => {
    // 根据表单中选中的分类ID过滤出对应的分类对象
    return props.categories.filter(cat => props.form.category_ids.includes(cat.id));
});

const displayTags = computed(() => {
    // 根据表单中选中的标签ID过滤出对应的标签对象
    return props.tags.filter(tag => props.form.tag_ids.includes(tag.id));
});

// --- 目录生成逻辑 ---
// 生成URL友好的slug（用于ID）
const generateSlug = (text) => {
    if (!text) return '';
    return text.toLowerCase()
        .replace(/[\s\\/]+/g, '-') // 将空格、斜杠替换为短横线
        .replace(/[^\w\u4e00-\u9fa5-]+/g, '') // 移除除字母、数字、下划线、中文和短横线外的字符
        .replace(/-+/g, '-') // 多个短横线合并为一个
        .replace(/^-+|-+$/g, ''); // 移除开头和结尾的短横线
};

// 生成唯一的ID
const generateUniqueId = (baseId, existingIds) => {
    let id = baseId;
    let counter = 1;
    while (existingIds.has(id)) {
        id = `${baseId}-${counter}`;
        counter++;
    }
    existingIds.add(id);
    return id;
};

// 防抖处理的目录生成函数
const generateTableOfContents = debounce(() => {
    const contentElement = previewContentRef.value;
    const tocContainer = tocContainerRef.value;
    const existingIds = new Set(); // 用于确保生成的ID唯一

    if (!tocContainer) {
        console.warn('目录容器未找到'); return;
    }
    tocContainer.innerHTML = ''; // 清空旧目录

    if (!contentElement) {
        tocContainer.innerHTML = '<p class="text-gray-500 dark:text-gray-400 italic text-xs">无法生成目录：内容未加载。</p>'; return;
    }

    // 使用临时div解析HTML字符串，避免直接操作DOM影响性能
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = props.form.content || '<p></p>'; // 使用表单内容
    const headings = tempDiv.querySelectorAll('h1, h2, h3, h4, h5, h6'); // 获取所有标题元素

    if (headings.length === 0) {
        tocContainer.innerHTML = '<p class="text-gray-500 dark:text-gray-400 italic text-xs">此页面没有目录。</p>'; return;
    }

    const tocList = document.createElement('ul');
    tocList.classList.add('space-y-1', 'list-none', 'pl-0', 'text-xs');

    headings.forEach((heading, index) => {
        const level = parseInt(heading.tagName.substring(1)); // 获取标题级别 (h1, h2...)
        const text = heading.textContent?.trim() || `无标题 ${index + 1}`; // 获取标题文本
        const baseSlug = generateSlug(text);
        const id = generateUniqueId(`preview-toc-${baseSlug || 'heading'}-${index}`, existingIds); // 生成预览专用的唯一ID
        heading.id = id; // 将ID添加到临时div中的标题元素上

        const listItem = document.createElement('li');
        listItem.style.paddingLeft = `${Math.max(0, level - 1) * 0.8}rem`; // 根据标题级别设置缩进

        const link = document.createElement('a');
        link.href = `#${id}`; // 链接指向生成的ID
        link.textContent = text;
        link.dataset.tocId = id; // 存储ID以便后续高亮
        link.classList.add('toc-link-preview', 'block', 'truncate', 'transition-colors', 'duration-150');

        // 点击目录项时平滑滚动到对应标题
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const targetElement = contentElement?.querySelector(`#${id}`); // 在实际渲染的预览内容中查找目标元素
            if (targetElement && contentElement) {
                const offset = 10; // 滚动偏移量
                const containerRect = contentElement.getBoundingClientRect();
                const targetRect = targetElement.getBoundingClientRect();
                const scrollTop = contentElement.scrollTop;
                const targetPosition = targetRect.top - containerRect.top + scrollTop - offset;

                contentElement.scrollTo({ top: targetPosition, behavior: 'smooth' });
            } else {
                console.warn(`预览目标元素未找到，ID: ${id}`);
            }
        });

        listItem.appendChild(link);
        tocList.appendChild(listItem);
    });

    tocContainer.appendChild(tocList);

    // 在Vue更新DOM后，重新给实际渲染的标题元素添加ID并设置滚动监听
    nextTick(() => {
        reapplyIdsToPreviewContent();
        setupTocScrollSpy();
    });

}, 300); // 300ms 防抖

// 将ID重新应用到预览内容中的标题元素，因为v-html会重新渲染
const reapplyIdsToPreviewContent = () => {
    const contentElement = previewContentRef.value;
    if (!contentElement) return;

    const existingIds = new Set(); // 重新收集ID
    const headingsInPreview = contentElement.querySelectorAll('h1, h2, h3, h4, h5, h6');
    headingsInPreview.forEach((heading, index) => {
        const text = heading.textContent?.trim() || `无标题 ${index + 1}`;
        const baseSlug = generateSlug(text);
        const id = generateUniqueId(`preview-toc-${baseSlug || 'heading'}-${index}`, existingIds);
        heading.id = id; // 将ID设置到实际DOM元素上
    });
};

// 设置目录滚动高亮
const setupTocScrollSpy = () => {
    if (observer) observer.disconnect(); // 如果已有观察器，先断开

    const contentElement = previewContentRef.value; // 滚动容器
    const tocContainer = tocContainerRef.value;

    if (!tocContainer || !contentElement) return;

    const tocLinks = tocContainer.querySelectorAll('.toc-link-preview'); // 获取所有目录链接
    if (tocLinks.length === 0) return;

    // 获取所有对应的标题元素
    const headingElements = Array.from(tocLinks).map(link => {
        const targetId = link.getAttribute('href')?.substring(1);
        return targetId ? contentElement.querySelector(`#${targetId}`) : null;
    }).filter(el => el !== null);

    if (headingElements.length === 0) return;

    const options = {
        root: contentElement, // 监听器在预览窗格内生效
        rootMargin: '0px 0px -80% 0px', // 当元素顶部距离根元素顶部80%时触发
        threshold: 0 // 只要有一点可见就触发
    };

    observer = new IntersectionObserver(entries => {
        let latestActiveId = null;
        // 反向遍历，确保最靠近顶部的标题被选中
        entries.reverse().forEach(entry => {
            if (entry.isIntersecting) {
                latestActiveId = entry.target.id;
            }
        });

        // 如果没有元素在指定rootMargin内交叉，但有元素在上方，则高亮最上面的那个
        if (!latestActiveId) {
            const visibleAbove = entries.filter(entry => entry.boundingClientRect.bottom < contentElement.offsetTop + 10);
            if (visibleAbove.length > 0) {
                latestActiveId = visibleAbove[visibleAbove.length - 1].target.id;
            }
        }
        // 如果滚动到页面底部，且没有活跃标题，高亮最后一个标题
        if (!latestActiveId && contentElement.scrollTop + contentElement.clientHeight >= contentElement.scrollHeight - 20) {
            if (headingElements.length > 0) {
                latestActiveId = headingElements[headingElements.length - 1].id;
            }
        }

        activeTocId.value = latestActiveId;

        // 更新目录链接的激活状态
        tocLinks.forEach(link => {
            link.classList.remove('is-active');
            if (link.dataset.tocId === activeTocId.value) {
                link.classList.add('is-active');
            }
        });
    }, options);

    // 观察所有标题元素
    headingElements.forEach(el => observer.observe(el));
};

// 监听表单内容的改变，重新生成目录
watch(() => props.form.content, (newContent, oldContent) => {
    if (newContent !== oldContent) {
        generateTableOfContents();
    }
}, { immediate: true }); // 组件初始化时立即执行一次

// --- 生命周期钩子 ---
onMounted(() => {
    generateTableOfContents(); // 组件挂载后生成目录
});

onUnmounted(() => {
    if (observer) {
        observer.disconnect(); // 组件卸载时断开观察器
    }
});
</script>

<template>
    <div
        class="h-full flex flex-col overflow-hidden border border-gray-300 dark:border-gray-700 rounded-lg shadow-inner">
        <!-- 预览头部/元信息区域 -->
        <div class="p-4 border-b border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex-shrink-0">
            <!-- 页面标题 -->
            <h1 class="text-xl md:text-2xl font-bold mb-2 text-gray-900 dark:text-gray-100 break-words">
                {{ displayTitle }}
            </h1>
            <!-- 作者和更新信息 -->
            <div class="text-xs text-gray-500 dark:text-gray-400 flex flex-wrap items-center gap-x-3 gap-y-1">
                <span class="whitespace-nowrap flex items-center">
                    <font-awesome-icon :icon="['fas', 'user']" class="mr-1 w-3 h-3" /> {{ displayCreator }} (创建于 {{
                        displayCreatedAt }})
                </span>
                <span class="whitespace-nowrap flex items-center">
                    <font-awesome-icon :icon="['fas', 'edit']" class="mr-1 w-3 h-3" /> {{ displayUpdater }} (更新于 {{
                        displayUpdatedAt }})
                </span>
            </div>
            <!-- 分类和标签信息 -->
            <div class="flex flex-wrap gap-1.5 mt-2 items-center">
                <span v-if="displayCategories.length" class="text-xs text-gray-500 dark:text-gray-400 mr-1">分类:</span>
                <span v-for="category in displayCategories" :key="category.id" class="tag-category-preview">
                    {{ category.name }}
                </span>
                <span v-if="displayTags.length" class="text-xs text-gray-500 dark:text-gray-400 mr-1 ml-2">标签:</span>
                <span v-for="tag in displayTags" :key="tag.id" class="tag-tag-preview">
                    {{ tag.name }}
                </span>
            </div>
        </div>

        <!-- 主要内容区域（可滚动） -->
        <div class="flex-grow flex overflow-hidden">
            <!-- 页面内容预览 -->
            <div ref="previewContentRef"
                class="flex-grow overflow-y-auto p-4 wiki-content-preview prose dark:prose-invert max-w-none">
                <!-- 如果有内容则渲染HTML，否则显示提示 -->
                <div v-if="form.content && form.content !== '<p></p>'" v-html="form.content"></div>
                <div v-else class="text-gray-400 dark:text-gray-500 italic py-6 text-center">开始编辑以查看预览...</div>

                <!-- 评论区占位符 -->
                <div class="mt-8 pt-6 border-t border-gray-300 dark:border-gray-700">
                    <h3 class="text-lg font-semibold mb-4 text-gray-700 dark:text-gray-300">评论区</h3>
                    <div
                        class="p-4 bg-gray-100 dark:bg-gray-700/30 rounded-lg text-center text-sm text-gray-500 dark:text-gray-400 italic">
                        评论将在页面保存后显示在这里。
                    </div>
                </div>
            </div>

            <!-- 目录侧边栏 -->
            <div
                class="hidden md:block w-48 lg:w-56 flex-shrink-0 border-l border-gray-300 dark:border-gray-700 overflow-y-auto p-3 bg-gray-50 dark:bg-gray-800/30 toc-sidebar">
                <h3
                    class="text-sm font-semibold mb-3 pb-1 border-b border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300">
                    目录
                </h3>
                <div ref="tocContainerRef" id="preview-toc-container" class="toc-links-preview">
                    <!-- 目录内容将在此处动态生成 -->
                    <p class="text-gray-500 dark:text-gray-400 italic">正在生成目录...</p> <!-- 初始加载提示 -->
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* 预览面板特定的样式 - 镜像 Show.vue 但可能有所调整 */
.wiki-content-preview {
    scroll-behavior: smooth;
    @apply text-base text-gray-700 dark:text-gray-300;
}

.wiki-content-preview :deep(p) {
    @apply leading-relaxed mb-4 line-clamp-none;
    line-height: 1.7;
}

.wiki-content-preview :deep(h1),
.wiki-content-preview :deep(h2),
.wiki-content-preview :deep(h3),
.wiki-content-preview :deep(h4),
.wiki-content-preview :deep(h5),
.wiki-content-preview :deep(h6) {
    @apply font-semibold text-gray-800 dark:text-gray-200 mt-6 mb-3;
    scroll-margin-top: 20px;
    /* 减少滚动偏移量以适应预览面板 */
    line-height: 1.3;
}

.wiki-content-preview :deep(h1) {
    @apply text-2xl border-b border-gray-300 dark:border-gray-700 pb-1 mb-4;
}

.wiki-content-preview :deep(h2) {
    @apply text-xl border-b border-gray-300 dark:border-gray-700 pb-1 mb-4;
}

.wiki-content-preview :deep(h3) {
    @apply text-lg;
}

.wiki-content-preview :deep(h4) {
    @apply text-base font-medium;
}

.wiki-content-preview :deep(ul),
.wiki-content-preview :deep(ol) {
    @apply pl-5 mb-4 list-outside;
}

.wiki-content-preview :deep(ul) {
    @apply list-disc;
}

.wiki-content-preview :deep(ol) {
    @apply list-decimal;
}

.wiki-content-preview :deep(li) {
    @apply mb-1.5;
}

.wiki-content-preview :deep(blockquote) {
    @apply border-l-4 border-blue-300 dark:border-blue-700 pl-4 italic text-gray-600 dark:text-gray-400 my-4 py-1 bg-blue-50/50 dark:bg-blue-900/20 rounded-r;
}

.wiki-content-preview :deep(pre) {
    @apply bg-gray-800 text-gray-100 p-3 rounded-md overflow-x-auto my-4 text-xs shadow dark:bg-black/50 dark:text-gray-200;
    font-family: 'JetBrains Mono', monospace;
}

.wiki-content-preview :deep(code:not(pre code)) {
    @apply bg-red-100 text-red-800 px-1 py-0.5 rounded text-[0.85em] font-mono dark:bg-red-900/40 dark:text-red-300;
}

.wiki-content-preview :deep(pre code) {
    background: none;
    color: inherit;
    padding: 0;
    font-size: inherit;
    line-height: inherit;
}

.wiki-content-preview :deep(a) {
    @apply text-cyan-600 dark:text-cyan-400 hover:text-cyan-800 dark:hover:text-cyan-300 underline decoration-cyan-600/50 hover:decoration-cyan-800/80 transition-colors duration-150;
}

.wiki-content-preview :deep(img) {
    @apply max-w-full h-auto my-6 rounded-md shadow mx-auto block border border-gray-200 dark:border-gray-700;
}

.wiki-content-preview :deep(table) {
    @apply w-full my-6 border-collapse border border-gray-300 dark:border-gray-600 shadow-sm;
}

.wiki-content-preview :deep(th),
.wiki-content-preview :deep(td) {
    @apply border border-gray-300 dark:border-gray-600 px-3 py-2 text-left text-xs;
    vertical-align: top;
}

.wiki-content-preview :deep(th) {
    @apply bg-gray-100 dark:bg-gray-700 font-medium text-gray-700 dark:text-gray-200;
}

.wiki-content-preview :deep(tr:nth-child(even)) {
    @apply bg-gray-50 dark:bg-gray-800/40;
}

/* 预览面板特有的目录样式 */
.toc-sidebar {
    scrollbar-width: thin;
    /* 适用于 Firefox */
    scrollbar-color: #a0aec0 #e2e8f0;
    /* 滚动条颜色 */
}

.dark .toc-sidebar {
    scrollbar-color: #4a5568 #2d3748;
}

.toc-sidebar::-webkit-scrollbar {
    width: 6px;
}

.toc-sidebar::-webkit-scrollbar-track {
    background: #e2e8f0;
    border-radius: 3px;
}

.dark .toc-sidebar::-webkit-scrollbar-track {
    background: #2d3748;
}

.toc-sidebar::-webkit-scrollbar-thumb {
    background-color: #a0aec0;
    border-radius: 3px;
}

.dark .toc-sidebar::-webkit-scrollbar-thumb {
    background-color: #4a5568;
}

.toc-links-preview {
    scroll-behavior: smooth;
}

.toc-link-preview {
    @apply block truncate transition-colors duration-150 text-xs py-0.5;
    @apply text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400;
}

.toc-link-preview.is-active {
    @apply text-blue-600 dark:text-blue-400 font-semibold;
    transform: translateX(2px);
}

/* 调整不同层级目录的缩进 */
#preview-toc-container li[style*="padding-left: 0.8rem"] a {
    @apply pl-3;
}

#preview-toc-container li[style*="padding-left: 1.6rem"] a {
    @apply pl-6 text-[0.7rem];
}

#preview-toc-container li[style*="padding-left: 2.4rem"] a {
    @apply pl-9 text-[0.7rem];
}


/* 预览头部中的分类和标签样式 */
.tag-category-preview {
    @apply inline-block px-2 py-0.5 bg-gray-200 text-gray-600 text-[0.65rem] rounded-full dark:bg-gray-700 dark:text-gray-300 whitespace-nowrap;
}

.tag-tag-preview {
    @apply inline-block px-2 py-0.5 bg-blue-100 text-blue-600 text-[0.65rem] rounded-full dark:bg-blue-900/40 dark:text-blue-300 whitespace-nowrap;
}
</style>
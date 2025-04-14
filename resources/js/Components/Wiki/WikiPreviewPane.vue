<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { formatDate, formatDateShort, formatDateTime } from '@/utils/formatters';
import { debounce } from 'lodash';

const props = defineProps({
    form: { type: Object, required: true }, // 包含 title, content, category_ids, tag_ids
    categories: { type: Array, required: true },
    tags: { type: Array, required: true },
    page: { type: Object, default: null }, // 仅在编辑时传递
    currentVersion: { type: Object, default: null }, // 仅在编辑时传递
});

const pageProps = usePage().props;
const previewContentRef = ref(null);
const tocContainerRef = ref(null);
const activeTocId = ref(null);
let observer = null;

// --- Computed properties for display ---
const displayTitle = computed(() => props.form.title || '无标题');

const displayCreator = computed(() => {
    if (props.page) {
        return props.page.creator?.name || '未知用户';
    }
    return pageProps.auth?.user?.name || '当前用户';
});

const displayCreatedAt = computed(() => {
    if (props.page) {
        return formatDateShort(props.page.created_at);
    }
    return '刚刚';
});

const displayUpdater = computed(() => {
    if (props.currentVersion?.creator) {
        return props.currentVersion.creator.name;
    } else if (props.page) {
        // Fallback if currentVersion has no creator but page exists
        return props.page.creator?.name || '未知用户';
    }
    return pageProps.auth?.user?.name || '当前用户';
});

const displayUpdatedAt = computed(() => {
    if (props.currentVersion) {
        return formatDateTime(props.currentVersion.created_at);
    } else if (props.page) {
        // Fallback to page update time if no currentVersion
        return formatDateTime(props.page.updated_at);
    }
    return '现在';
});

const displayCategories = computed(() => {
    return props.categories.filter(cat => props.form.category_ids.includes(cat.id));
});

const displayTags = computed(() => {
    return props.tags.filter(tag => props.form.tag_ids.includes(tag.id));
});

// --- Table of Contents Logic ---
const generateSlug = (text) => {
    if (!text) return '';
    return text.toLowerCase()
        .replace(/[\s\\/]+/g, '-')
        .replace(/[^\w\u4e00-\u9fa5-]+/g, '')
        .replace(/-+/g, '-')
        .replace(/^-+|-+$/g, '');
};

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

const generateTableOfContents = debounce(() => {
    // console.log('Generating TOC for preview...');
    const contentElement = previewContentRef.value;
    const tocContainer = tocContainerRef.value;
    const existingIds = new Set();

    if (!tocContainer) {
        console.warn('TOC container not found'); return;
    }
    tocContainer.innerHTML = ''; // Clear previous TOC

    if (!contentElement) {
        tocContainer.innerHTML = '<p class="text-gray-500 dark:text-gray-400 italic text-xs">无法生成目录：内容未加载。</p>'; return;
    }

    // Use a temporary div to parse the HTML string without affecting the actual DOM yet
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = props.form.content || '<p></p>'; // Use form content
    const headings = tempDiv.querySelectorAll('h1, h2, h3, h4, h5, h6');

    if (headings.length === 0) {
        tocContainer.innerHTML = '<p class="text-gray-500 dark:text-gray-400 italic text-xs">此页面没有目录。</p>'; return;
    }

    const tocList = document.createElement('ul');
    tocList.classList.add('space-y-1', 'list-none', 'pl-0', 'text-xs'); // Smaller text size for TOC

    headings.forEach((heading, index) => {
        const level = parseInt(heading.tagName.substring(1));
        const text = heading.textContent?.trim() || `无标题 ${index + 1}`;
        const baseSlug = generateSlug(text);
        // Generate ID for preview, might need prefix to avoid conflict if main page has TOC
        const id = generateUniqueId(`preview-toc-${baseSlug || 'heading'}-${index}`, existingIds);
        heading.id = id; // Add ID to heading *in the temporary div*

        const listItem = document.createElement('li');
        listItem.style.paddingLeft = `${Math.max(0, level - 1) * 0.8}rem`; // Slightly less indent

        const link = document.createElement('a');
        link.href = `#${id}`;
        link.textContent = text;
        link.dataset.tocId = id;
        link.classList.add('toc-link-preview', 'block', 'truncate', 'transition-colors', 'duration-150');

        link.addEventListener('click', (e) => {
            e.preventDefault();
            const targetElement = contentElement?.querySelector(`#${id}`); // Find target in the *rendered preview*
            if (targetElement && contentElement) {
                const offset = 10; // Offset within the preview pane
                const containerRect = contentElement.getBoundingClientRect();
                const targetRect = targetElement.getBoundingClientRect();
                const scrollTop = contentElement.scrollTop;
                const targetPosition = targetRect.top - containerRect.top + scrollTop - offset;

                contentElement.scrollTo({ top: targetPosition, behavior: 'smooth' });
            } else {
                console.warn(`Preview target element not found for ID: ${id}`);
            }
        });

        listItem.appendChild(link);
        tocList.appendChild(listItem);
    });

    tocContainer.appendChild(tocList);

    // After updating the preview v-html (which happens reactivity), re-add IDs to the *rendered* headings
    nextTick(() => {
        reapplyIdsToPreviewContent();
        setupTocScrollSpy(); // Setup scroll spy *after* content is rendered and IDs are applied
    });

}, 300); // Debounce TOC generation

// Function to re-apply IDs to headings in the actual preview pane
const reapplyIdsToPreviewContent = () => {
    const contentElement = previewContentRef.value;
    if (!contentElement) return;

    const existingIds = new Set(); // Need a fresh set each time
    const headingsInPreview = contentElement.querySelectorAll('h1, h2, h3, h4, h5, h6');
    headingsInPreview.forEach((heading, index) => {
        const text = heading.textContent?.trim() || `无标题 ${index + 1}`;
        const baseSlug = generateSlug(text);
        const id = generateUniqueId(`preview-toc-${baseSlug || 'heading'}-${index}`, existingIds);
        heading.id = id; // Set ID on the *rendered* element
    });
};

const setupTocScrollSpy = () => {
    if (observer) observer.disconnect();
    const contentElement = previewContentRef.value; // Scroll container
    const tocContainer = tocContainerRef.value;
    if (!tocContainer || !contentElement) return;

    const tocLinks = tocContainer.querySelectorAll('.toc-link-preview');
    if (tocLinks.length === 0) return;

    const headingElements = Array.from(tocLinks).map(link => {
        const targetId = link.getAttribute('href')?.substring(1);
        return targetId ? contentElement.querySelector(`#${targetId}`) : null; // Find inside the preview container
    }).filter(el => el !== null);

    if (headingElements.length === 0) return;

    const options = {
        root: contentElement, // Observe within the preview pane
        rootMargin: '0px 0px -80% 0px', // Adjust margins relative to the pane
        threshold: 0
    };

    observer = new IntersectionObserver(entries => {
        let latestActiveId = null;
        // Find the latest intersecting element's ID based on entry order (reversed)
        entries.reverse().forEach(entry => {
            if (entry.isIntersecting) {
                latestActiveId = entry.target.id;
            }
        });

        // Fallback if nothing is intersecting but some headings are above the viewport threshold
        if (!latestActiveId) {
            const visibleAbove = entries.filter(entry => entry.boundingClientRect.bottom < contentElement.offsetTop + 10); // Adjust threshold
            if (visibleAbove.length > 0) {
                latestActiveId = visibleAbove[visibleAbove.length - 1].target.id;
            }
        }
        // If still no active ID found (e.g., scrolled past the last heading), try to highlight the last one
        if (!latestActiveId && contentElement.scrollTop + contentElement.clientHeight >= contentElement.scrollHeight - 20) { // Check if scrolled near bottom
            if (headingElements.length > 0) {
                latestActiveId = headingElements[headingElements.length - 1].id;
            }
        }

        activeTocId.value = latestActiveId;

        tocLinks.forEach(link => {
            link.classList.remove('is-active');
            if (link.dataset.tocId === activeTocId.value) {
                link.classList.add('is-active');
            }
        });
    }, options);

    headingElements.forEach(el => observer.observe(el));
};

// Watch for content changes to regenerate TOC
watch(() => props.form.content, (newContent, oldContent) => {
    if (newContent !== oldContent) {
        generateTableOfContents();
    }
}, { immediate: true }); // Generate TOC on initial mount

// --- Lifecycle ---
onMounted(() => {
    generateTableOfContents();
});

onUnmounted(() => {
    if (observer) {
        observer.disconnect();
    }
});
</script>

<template>
    <div
        class="h-full flex flex-col overflow-hidden border border-gray-300 dark:border-gray-700 rounded-lg shadow-inner">
        <!-- Header/Meta Info -->
        <div class="p-4 border-b border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex-shrink-0">
            <h1 class="text-xl md:text-2xl font-bold mb-2 text-gray-900 dark:text-gray-100 break-words">
                {{ displayTitle }}
            </h1>
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

        <!-- Main Content Area (Scrollable) -->
        <div class="flex-grow flex overflow-hidden">
            <!-- Content -->
            <div ref="previewContentRef"
                class="flex-grow overflow-y-auto p-4 wiki-content-preview prose dark:prose-invert max-w-none">
                <div v-if="form.content && form.content !== '<p></p>'" v-html="form.content"></div>
                <div v-else class="text-gray-400 dark:text-gray-500 italic py-6 text-center">开始编辑以查看预览...</div>

                <!-- Comment Placeholder -->
                <div class="mt-8 pt-6 border-t border-gray-300 dark:border-gray-700">
                    <h3 class="text-lg font-semibold mb-4 text-gray-700 dark:text-gray-300">评论区</h3>
                    <div
                        class="p-4 bg-gray-100 dark:bg-gray-700/30 rounded-lg text-center text-sm text-gray-500 dark:text-gray-400 italic">
                        评论将在页面保存后显示在这里。
                    </div>
                </div>
            </div>

            <!-- Table of Contents -->
            <div
                class="hidden md:block w-48 lg:w-56 flex-shrink-0 border-l border-gray-300 dark:border-gray-700 overflow-y-auto p-3 bg-gray-50 dark:bg-gray-800/30 toc-sidebar">
                <h3
                    class="text-sm font-semibold mb-3 pb-1 border-b border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300">
                    目录
                </h3>
                <div ref="tocContainerRef" id="preview-toc-container" class="toc-links-preview">
                    <!-- TOC will be generated here -->
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Preview specific styles - mirror Show.vue but potentially adjusted */
.wiki-content-preview {
    scroll-behavior: smooth;
    /* Mimic prose styles from app.css but target this component */
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
    /* Reduced offset for pane scrolling */
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

/* Preview Pane specific styles */
.toc-sidebar {
    scrollbar-width: thin;
    /* For Firefox */
    scrollbar-color: #a0aec0 #e2e8f0;
    /* thumb track */
}

.dark .toc-sidebar {
    scrollbar-color: #4a5568 #2d3748;
    /* dark thumb dark track */
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

#preview-toc-container li[style*="padding-left: 0.8rem"] a {
    @apply pl-3;
}

#preview-toc-container li[style*="padding-left: 1.6rem"] a {
    @apply pl-6 text-[0.7rem];
}

#preview-toc-container li[style*="padding-left: 2.4rem"] a {
    @apply pl-9 text-[0.7rem];
}


/* Tags in preview header */
.tag-category-preview {
    @apply inline-block px-2 py-0.5 bg-gray-200 text-gray-600 text-[0.65rem] rounded-full dark:bg-gray-700 dark:text-gray-300 whitespace-nowrap;
}

.tag-tag-preview {
    @apply inline-block px-2 py-0.5 bg-blue-100 text-blue-600 text-[0.65rem] rounded-full dark:bg-blue-900/40 dark:text-blue-300 whitespace-nowrap;
}
</style>
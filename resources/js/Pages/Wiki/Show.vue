<template>
    <MainLayout
        :navigationLinks="[{ href: '/wiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- 文章头部信息 -->
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden mb-6">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h1 class="text-3xl font-bold text-gray-900">{{ article.title }}</h1>
                            <div class="mt-4 flex flex-wrap gap-4 text-sm text-gray-500">
                                <div>
                                    作者: {{ article.creator?.name || '未知' }}
                                </div>
                                <div>
                                    最后编辑: {{ formatDate(article.updated_at) }} by {{ article.lastEditor?.name || '未知' }}
                                </div>
                                <div>
                                    浏览量: {{ article.view_count }}
                                </div>
                            </div>
                            <!-- 分类标签 -->
                            <div class="mt-4 flex flex-wrap gap-2">
                                <Link v-for="category in article.categories" :key="category.id"
                                    :href="route('wiki.index', { category: category.id })"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 transition-colors duration-150">
                                {{ category.name }}
                                </Link>
                            </div>
                        </div>

                        <!-- 操作按钮 -->
                        <div class="flex gap-4" v-if="$page.props.auth.user">
                            <Link v-if="can.edit_article" :href="route('wiki.edit', article.id)"
                                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition duration-150 ease-in-out">
                            编辑
                            </Link>
                            <button v-if="can.publish_article && article.status === 'draft'" @click="publishArticle"
                                class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition duration-150 ease-in-out">
                                发布
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 文章内容 -->
                <div class="p-6 prose prose-lg max-w-none" v-html="article.content"></div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';

const props = defineProps({
    article: {
        type: Object,
        required: true
    },
    can: {
        type: Object,
        default: () => ({})
    }
});

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleString('zh-CN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const publishArticle = () => {
    router.post(route('wiki.publish', props.article.id));
};
</script>

<style>
.prose {
    color: #374151;
    max-width: 65ch;
    margin: 0 auto;
}

.prose img {
    margin: 2em auto;
    border-radius: 0.375rem;
}

.prose h2 {
    color: #111827;
    font-weight: 700;
    font-size: 1.5em;
    margin-top: 2em;
    margin-bottom: 1em;
    line-height: 1.3333333;
}

.prose p {
    margin-top: 1.25em;
    margin-bottom: 1.25em;
    line-height: 1.75;
}

.prose a {
    color: #2563eb;
    text-decoration: underline;
    font-weight: 500;
}

.prose strong {
    color: #111827;
    font-weight: 600;
}

.prose ul,
.prose ol {
    margin-top: 1.25em;
    margin-bottom: 1.25em;
    padding-left: 1.625em;
}

.prose ul {
    list-style-type: disc;
}

.prose ol {
    list-style-type: decimal;
}

.prose blockquote {
    font-weight: 500;
    font-style: italic;
    color: #111827;
    border-left-width: 0.25rem;
    border-left-color: #e5e7eb;
    quotes: "\201C" "\201D" "\2018" "\2019";
    margin: 1.6em 0;
    padding-left: 1em;
}

.prose code {
    color: #111827;
    font-weight: 600;
    font-size: 0.875em;
}

.prose pre {
    color: #e5e7eb;
    background-color: #1f2937;
    overflow-x: auto;
    font-size: 0.875em;
    line-height: 1.7142857;
    margin: 1.7142857em 0;
    border-radius: 0.375rem;
    padding: 0.8571429em 1.1428571em;
}
</style>
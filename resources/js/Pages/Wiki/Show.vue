<template>
    <MainLayout
        :navigationLinks="[{ href: '/wiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex gap-6">
                <aside class="hidden lg:block w-64 flex-shrink-0">
                    <div class="sticky top-6">
                        <TableOfContents :headers="pageHeaders" />
                        <nav class="space-y-1 bg-white/80 backdrop-blur-sm rounded-lg shadow p-4 mt-4"
                            aria-label="页面操作">
                            <div class="flex items-center space-x-2 py-2 px-3 text-gray-700 hover:bg-gray-50 rounded">
                                <EyeIcon class="w-5 h-5" />
                                <span>浏览量: {{ page.view_count }}</span>
                            </div>

                            <Link v-if="page.recent_revisions && page.recent_revisions.length"
                                :href="route('wiki.revisions', page.id)"
                                class="flex items-center space-x-2 py-2 px-3 text-gray-700 hover:bg-gray-50 rounded">
                            <ClockIcon class="w-5 h-5" />
                            <span>查看历史版本</span>
                            </Link>

                            <button v-if="$page.props.auth.user" @click="toggleFollow"
                                class="w-full flex items-center space-x-2 py-2 px-3 text-left rounded" :class="page.is_following ?
                                    'bg-blue-50 text-blue-700 hover:bg-blue-100' :
                                    'text-gray-700 hover:bg-gray-50'">
                                <StarIcon class="w-5 h-5" />
                                <span>{{ page.is_following ? '已关注' : '关注页面' }}</span>
                            </button>

                            <!-- 添加打印按钮 -->
                            <button @click="printPage"
                                class="w-full flex items-center space-x-2 py-2 px-3 text-gray-700 hover:bg-gray-50 rounded">
                                <PrinterIcon class="w-5 h-5" />
                                <span>打印页面</span>
                            </button>

                            <!-- 分享按钮 -->
                            <div class="relative">
                                <button @click="showShareMenu = !showShareMenu"
                                    class="w-full flex items-center justify-between py-2 px-3 text-gray-700 hover:bg-gray-50 rounded">
                                    <div class="flex items-center space-x-2">
                                        <ShareIcon class="w-5 h-5" />
                                        <span>分享页面</span>
                                    </div>
                                    <ChevronDownIcon class="w-4 h-4"
                                        :class="{ 'transform rotate-180': showShareMenu }" />
                                </button>

                                <div v-if="showShareMenu"
                                    class="absolute left-0 right-0 mt-1 bg-white rounded-md shadow-lg z-10 py-1">
                                    <button v-for="option in shareOptions" :key="option.id"
                                        @click="shareContent(option.id)"
                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center">
                                        <component :is="option.icon" class="w-4 h-4 mr-2" />
                                        {{ option.label }}
                                    </button>
                                </div>
                            </div>
                        </nav>
                    </div>
                </aside>

                <div class="flex-1 min-w-0">
                    <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg mb-6">
                        <div class="p-6">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h1 class="text-3xl font-bold text-gray-900">{{ page.title }}</h1>
                                    <div class="mt-2 flex flex-wrap gap-4 text-sm text-gray-500">
                                        <div class="flex items-center">
                                            <UserIcon class="w-4 h-4 mr-1" />
                                            <span>创建者: {{ page.creator?.name || '未知' }}</span>
                                        </div>
                                        <div class="flex items-center">
                                            <ClockIcon class="w-4 h-4 mr-1" />
                                            <span>最后编辑: {{ formatDate(page.updated_at) }}</span>
                                        </div>
                                        <div class="flex items-center">
                                            <EyeIcon class="w-4 h-4 mr-1" />
                                            <span>浏览量: {{ page.view_count }}</span>
                                        </div>
                                        <div v-if="page.published_at" class="flex items-center">
                                            <CalendarIcon class="w-4 h-4 mr-1" />
                                            <span>发布于: {{ formatDate(page.published_at) }}</span>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <Link v-for="category in page.categories" :key="category.id"
                                            :href="route('wiki.index', { category: category.id })"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200">
                                        <FolderIcon class="w-3 h-3 mr-1" />
                                        {{ category.name }}
                                        </Link>
                                    </div>
                                </div>
                                <div class="flex gap-4" v-if="$page.props.auth.user">
                                    <button @click="reportIssue"
                                        class="inline-flex items-center gap-1 text-gray-500 hover:text-gray-700">
                                        <ExclamationTriangleIcon class="w-5 h-5" />
                                        <span class="hidden sm:inline">报告问题</span>
                                    </button>
                                    <Link v-if="page.can && page.can.edit_page" :href="route('wiki.edit', page.id)"
                                        class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800">
                                    <PencilIcon class="w-5 h-5" />
                                    <span class="hidden sm:inline">编辑</span>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden mb-6">
                        <div class="wiki-content prose prose-lg max-w-none p-6" id="wiki-article-content"
                            v-html="page.content"></div>

                        <!-- 文章底部信息 -->
                        <div
                            class="border-t border-gray-200 px-6 py-4 flex flex-wrap justify-between items-center text-sm text-gray-500">
                            <div class="flex items-center space-x-4">
                                <span class="flex items-center">
                                    <DocumentTextIcon class="w-4 h-4 mr-1" />
                                    版本: {{ page.current_version }}
                                </span>
                                <Link :href="route('wiki.revisions', page.id)" class="text-blue-600 hover:underline">
                                查看历史版本
                                </Link>
                            </div>

                            <div class="flex space-x-2 items-center">
                                <span>最后编辑于 {{ formatDate(page.updated_at) }}</span>
                                <Link v-if="page.lastEditor" :href="'#'" class="text-blue-600 hover:underline">
                                {{ page.lastEditor.name }}
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- 相关页面 -->
                    <div v-if="page.related_pages && page.related_pages.length"
                        class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden mb-6">
                        <div class="p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                <LinkIcon class="w-5 h-5 mr-2" />
                                相关页面
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <Link v-for="relatedPage in page.related_pages" :key="relatedPage.id"
                                    :href="route('wiki.show', relatedPage.id)"
                                    class="p-3 border border-gray-200 rounded hover:bg-gray-50 transition">
                                <div class="font-medium text-blue-600">{{ relatedPage.title }}</div>
                                <div class="text-sm text-gray-500 mt-1 flex items-center space-x-3">
                                    <span class="flex items-center">
                                        <EyeIcon class="w-3.5 h-3.5 mr-1" />
                                        {{ relatedPage.view_count }}
                                    </span>
                                    <span class="flex items-center">
                                        <LinkIcon class="w-3.5 h-3.5 mr-1" />
                                        {{ relatedPage.incoming_references_count || 0 }}
                                    </span>
                                </div>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- 引用此页面的页面 -->
                    <div v-if="page.referencedByPages && page.referencedByPages.length"
                        class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden mb-6">
                        <div class="p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                <LinkIcon class="w-5 h-5 mr-2 transform rotate-180" />
                                引用此页面的内容
                            </h2>
                            <ul class="divide-y divide-gray-200">
                                <li v-for="refPage in page.referencedByPages" :key="refPage.id" class="py-3">
                                    <Link :href="route('wiki.show', refPage.id)"
                                        class="flex justify-between items-start">
                                    <div>
                                        <div class="font-medium text-blue-600">{{ refPage.title }}</div>
                                        <div v-if="refPage.pivot && refPage.pivot.context"
                                            class="text-sm text-gray-500 mt-1">
                                            "...{{ refPage.pivot.context }}..."
                                        </div>
                                    </div>
                                    <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- 页面问题区域 -->
                    <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden mb-6">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-xl font-bold text-gray-900 flex items-center">
                                    <ExclamationTriangleIcon class="w-5 h-5 mr-2" />
                                    页面问题
                                </h2>
                                <div class="flex items-center">
                                    <input type="checkbox" id="no-handle" v-model="issueFilter" @change="applyFilter"
                                        class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <label for="no-handle" class="text-sm">只看未解决</label>
                                </div>
                            </div>

                            <div v-if="page.issue && page.issue.length > 0">
                                <div v-for="v in page.issue" :key="v.id"
                                    class="mb-4 border-b border-gray-200 pb-4 last:border-0"
                                    v-show="!issueFilter || v.status === 'to_be_solved'">
                                    <div class="flex justify-between items-start">
                                        <div class="text-sm text-gray-500 flex items-center">
                                            <UserIcon class="w-3.5 h-3.5 mr-1" />
                                            {{ v.reported_by_name || '匿名用户' }}
                                            <span class="mx-2">•</span>
                                            {{ formatDate(v.created_at) }}
                                        </div>
                                        <div>
                                            <span v-if="v.status === 'to_be_solved'"
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                未解决
                                            </span>
                                            <span v-else
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                已解决
                                            </span>
                                        </div>
                                    </div>

                                    <div class="p-3 mt-2 bg-gray-50 rounded">
                                        <p class="font-medium mb-1 text-sm">反馈内容：</p>
                                        <div class="prose prose-sm max-w-none" v-html="v.content"></div>
                                    </div>

                                    <div v-if="v.status === 'to_be_solved' && page.can && page.can.issue"
                                        class="mt-2 text-right">
                                        <button @click="handle_issue(v.id)"
                                            class="px-3 py-1 text-xs bg-blue-100 text-blue-800 rounded-full hover:bg-blue-200 transition">
                                            标记为已解决
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-gray-500 text-center py-6 bg-gray-50 rounded">
                                暂无页面问题反馈
                            </div>

                            <div v-if="$page.props.auth.user && page.can && page.can.issue" class="mt-4 text-center">
                                <button @click="reportIssue"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <ExclamationTriangleIcon class="w-5 h-5 mr-2" />
                                    报告新问题
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button v-show="showBackToTop" @click="scrollToTop"
            class="fixed bottom-8 right-8 bg-gray-800/50 hover:bg-gray-800/75 text-white rounded-full p-3 transition-all duration-300">
            <ArrowUpIcon class="w-6 h-6" />
        </button>

        <Modal :show="showReportModal" @close="showReportModal = false">
            <div class="p-6">
                <h3 class="text-lg font-medium text-white mb-4">报告页面问题</h3>
                <div ref="editorContainer">
                    <textarea id="textarea" v-model="issueContent"></textarea>
                </div>
                <div class="mt-4 flex justify-end gap-4">
                    <button type="button" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                        @click="showReportModal = false">
                        取消
                    </button>
                    <button type="button" @click="submitIssue"
                        class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                        提交
                    </button>
                </div>
            </div>
        </Modal>

        <!-- 分享模态框 -->
        <Modal :show="showShareModal" @close="showShareModal = false" maxWidth="md">
            <div class="p-6">
                <h3 class="text-lg font-medium text-white mb-4">分享页面</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-white mb-1">页面链接</label>
                        <div class="flex">
                            <input type="text" readonly :value="shareUrl"
                                class="bg-gray-800 text-white flex-1 p-2 rounded-l-md border border-gray-700 focus:outline-none" />
                            <button @click="copyToClipboard(shareUrl)"
                                class="bg-blue-600 text-white px-4 py-2 rounded-r-md hover:bg-blue-700">
                                复制
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-white mb-2">分享到</label>
                        <div class="grid grid-cols-4 gap-3">
                            <button v-for="option in shareOptions" :key="option.id" @click="shareContent(option.id)"
                                class="flex flex-col items-center justify-center p-3 bg-gray-800 rounded-md hover:bg-gray-700 transition">
                                <component :is="option.icon" class="w-6 h-6 mb-1" />
                                <span class="text-xs text-white">{{ option.label }}</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="shareMode === 'embed'">
                        <label class="block text-sm font-medium text-white mb-1">嵌入代码</label>
                        <textarea readonly rows="3"
                            class="w-full bg-gray-800 text-white p-2 rounded-md border border-gray-700"><iframe src="{{ shareUrl }}" width="100%" height="500" frameborder="0"></iframe></textarea>
                    </div>
                </div>
            </div>
        </Modal>
    </MainLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import { onMounted, onUnmounted, ref, watch, computed } from 'vue';
import Modal from "@/Components/Modal/Modal.vue";
import { useEditor } from '@/plugins/tinymce';
import TableOfContents from '@/Components/Wiki/TableOfContents.vue';
import {
    EyeIcon,
    ClockIcon,
    StarIcon,
    PencilIcon,
    ArrowUpIcon,
    ExclamationTriangleIcon,
    UserIcon,
    PrinterIcon,
    ShareIcon,
    ChevronDownIcon,
    ChevronRightIcon,
    DocumentTextIcon,
    LinkIcon,
    FolderIcon,
    CalendarIcon
} from '@heroicons/vue/24/outline';

const editor = ref(null);
const editorContainer = ref(null);
const issueContent = ref('');
const showReportModal = ref(false);
const issueFilter = ref(route().params.filter === 'unresolved');
const showBackToTop = ref(false);
const pageHeaders = ref([]);
const showShareMenu = ref(false);
const showShareModal = ref(false);
const shareMode = ref('link');
const copySuccess = ref(false);

// 分享选项
const shareOptions = [
    { id: 'link', icon: LinkIcon, label: '复制链接' },
    { id: 'twitter', icon: 'div', label: 'Twitter' },
    { id: 'facebook', icon: 'div', label: 'Facebook' },
    { id: 'weixin', icon: 'div', label: '微信' },
    { id: 'embed', icon: 'div', label: '嵌入代码' },
    { id: 'email', icon: 'div', label: '电子邮件' },
    { id: 'reddit', icon: 'div', label: 'Reddit' },
    { id: 'linkedin', icon: 'div', label: 'LinkedIn' }
];

const { init: editorConfig } = useEditor();

const cleanup = () => {
    if (editor.value) {
        editor.value.destroy();
        editor.value = null;
    }
}

const parseHeaders = (content) => {
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = content;
    const headers = Array.from(tempDiv.querySelectorAll('h2, h3, h4'))
        .map(header => {
            const id = header.id || header.textContent.toLowerCase().replace(/\s+/g, '-');
            return {
                id,
                text: header.textContent,
                level: parseInt(header.tagName.substring(1))
            };
        });
    return headers;
};

const processContent = (content) => {
    const headers = parseHeaders(content);
    pageHeaders.value = headers;
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = content;
    tempDiv.querySelectorAll('h2, h3, h4').forEach((header, index) => {
        if (headers[index]) {
            header.id = headers[index].id;
        }
    });
    return tempDiv.innerHTML;
};

// 获取分享URL
const shareUrl = computed(() => {
    return window.location.origin + route('wiki.show', props.page.id);
});

// 复制到剪贴板
const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text).then(() => {
        copySuccess.value = true;
        setTimeout(() => {
            copySuccess.value = false;
        }, 2000);
    });
};

// 分享内容
const shareContent = (mode) => {
    shareMode.value = mode;

    switch (mode) {
        case 'link':
            copyToClipboard(shareUrl.value);
            showShareMenu.value = false;
            break;
        case 'twitter':
            window.open(`https://twitter.com/intent/tweet?url=${encodeURIComponent(shareUrl.value)}&text=${encodeURIComponent(props.page.title)}`, '_blank');
            showShareMenu.value = false;
            break;
        case 'facebook':
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl.value)}`, '_blank');
            showShareMenu.value = false;
            break;
        case 'email':
            window.location.href = `mailto:?subject=${encodeURIComponent(props.page.title)}&body=${encodeURIComponent(shareUrl.value)}`;
            showShareMenu.value = false;
            break;
        case 'embed':
        case 'weixin':
        case 'reddit':
        case 'linkedin':
            showShareModal.value = true;
            showShareMenu.value = false;
            break;
    }
};

// 打印页面
const printPage = () => {
    const content = document.getElementById('wiki-article-content');
    const title = props.page.title;

    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
    <html>
      <head>
        <title>${title} - 打印版</title>
        <style>
          body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; }
          h1 { text-align: center; margin-bottom: 20px; }
          .header { text-align: center; margin-bottom: 30px; color: #666; }
          .content { max-width: 800px; margin: 0 auto; }
          .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
          @media print {
            a { text-decoration: none; color: #000; }
          }
        </style>
      </head>
      <body>
        <h1>${title}</h1>
        <div class="header">
          打印于: ${new Date().toLocaleString()}<br>
          来源: ${window.location.href}
        </div>
        <div class="content">
          ${content.innerHTML}
        </div>
        <div class="footer">
          © ${new Date().getFullYear()} Wiki站点 - 页面ID: ${props.page.id}
        </div>
        <script>
          window.onload = function() { window.print(); };
        <\/script>
      <\/body>
    <\/html>
  `);
    printWindow.document.close();
};

const props = defineProps({
    page: {
        type: Object,
        required: true
    }
});

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
    if (props.page.content) {
        props.page.content = processContent(props.page.content);
    }

    // 点击文档时关闭分享菜单
    document.addEventListener('click', (e) => {
        if (showShareMenu.value) {
            showShareMenu.value = false;
        }
    });

    // 阻止事件冒泡
    const stopPropagation = (e) => {
        e.stopPropagation();
    };

    // 获取分享菜单元素并添加事件监听器
    setTimeout(() => {
        const shareMenuBtn = document.querySelector('.share-menu-btn');
        if (shareMenuBtn) {
            shareMenuBtn.addEventListener('click', stopPropagation);
        }
    }, 100);
});

onUnmounted(() => {
    cleanup();
    window.removeEventListener('scroll', handleScroll);

    // 移除事件监听器
    const shareMenuBtn = document.querySelector('.share-menu-btn');
    if (shareMenuBtn) {
        shareMenuBtn.removeEventListener('click', stopPropagation);
    }
});

const handleScroll = () => {
    showBackToTop.value = window.scrollY > 500;
};

const scrollToTop = () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
};

const applyFilter = () => {
    let params = { page: props.page.id };
    if (issueFilter.value) {
        params.filter = 'unresolved';
    }
    router.get(route('wiki.show', params), {}, { preserveState: true });
};

const toggleFollow = () => {
    router.post(route('wiki.follow', props.page.id), {}, {
        preserveState: true,
        onSuccess: (response) => {
            const message = response.props.flash?.message;
            if (message) {
            }
        }
    });
};

const handle_issue = (id) => {
    router.post(route('wiki.issue_handle'), { id }, {
        onSuccess: () => {
            router.reload({ only: ['page.issue'] });
        },
        onError: (error) => {
            alert("处理失败: " + error.message);
        }
    });
};

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

const reportIssue = () => {
    showReportModal.value = true;
    setTimeout(() => {
        const config = {
            ...editorConfig,
            selector: '#textarea',
            init_instance_callback: (ed) => {
                editor.value = ed;
            },
            setup: (ed) => {
                ed.on('input change', () => {
                    issueContent.value = ed.getContent();
                });
            }
        };
        window.tinymce?.init(config);
    }, 300);
};

const submitIssue = () => {
    if (!issueContent.value.trim()) {
        alert('请输入问题内容');
        return;
    }
    router.post(route('wiki.issue'), {
        content: issueContent.value,
        page_id: props.page.id
    }, {
        onSuccess: () => {
            showReportModal.value = false;
            issueContent.value = '';
            cleanup(); // 清理编辑器
        },
        onError: (errors) => {
            alert('提交失败：' + Object.values(errors).join('\n'));
        }
    });
};
</script>

<style>
.wiki-content {
    color: #374151;
}

.wiki-content img {
    margin: 2em auto;
    border-radius: 0.375rem;
    display: block;
    max-width: 100%;
    height: auto;
}

.wiki-content h2 {
    color: #111827;
    font-weight: 700;
    font-size: 1.5em;
    margin-top: 2em;
    margin-bottom: 1em;
    line-height: 1.3333333;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 0.5em;
}

.wiki-content h3 {
    color: #111827;
    font-weight: 600;
    font-size: 1.25em;
    margin-top: 1.6em;
    margin-bottom: 0.8em;
    line-height: 1.4;
}

.wiki-content h4 {
    color: #111827;
    font-weight: 600;
    font-size: 1.1em;
    margin-top: 1.5em;
    margin-bottom: 0.8em;
}

.wiki-content p {
    margin-top: 1.25em;
    margin-bottom: 1.25em;
    line-height: 1.75;
}

.wiki-content a {
    color: #2563eb;
    text-decoration: underline;
    font-weight: 500;
}

.wiki-content strong {
    color: #111827;
    font-weight: 600;
}

.wiki-content ul,
.wiki-content ol {
    margin-top: 1.25em;
    margin-bottom: 1.25em;
    padding-left: 1.625em;
}

.wiki-content ul {
    list-style-type: disc;
}

.wiki-content ol {
    list-style-type: decimal;
}

.wiki-content blockquote {
    font-weight: 500;
    font-style: italic;
    color: #111827;
    border-left-width: 0.25rem;
    border-left-color: #e5e7eb;
    quotes: "\201C" "\201D" "\2018" "\2019";
    margin: 1.6em 0;
    padding-left: 1em;
}

.wiki-content code {
    color: #111827;
    font-weight: 600;
    font-size: 0.875em;
    background-color: #f3f4f6;
    padding: 0.2em 0.4em;
    border-radius: 0.375rem;
}

.wiki-content pre {
    color: #e5e7eb;
    background-color: #1f2937;
    overflow-x: auto;
    font-size: 0.875em;
    line-height: 1.7142857;
    margin: 1.7142857em 0;
    border-radius: 0.375rem;
    padding: 0.8571429em 1.1428571em;
}

.wiki-content pre code {
    background-color: transparent;
    padding: 0;
    font-weight: normal;
    color: inherit;
}

.wiki-content table {
    width: 100%;
    table-layout: auto;
    text-align: left;
    margin-top: 2em;
    margin-bottom: 2em;
    font-size: 0.875em;
    line-height: 1.7142857;
    border-collapse: collapse;
}

.wiki-content table thead {
    color: #111827;
    font-weight: 600;
    border-bottom: 1px solid #d1d5db;
}

.wiki-content table th {
    vertical-align: bottom;
    padding: 0.5714286em 0.5714286em 0.5714286em 0;
    text-align: left;
}

.wiki-content table td {
    padding: 0.5714286em 0.5714286em 0.5714286em 0;
    vertical-align: top;
    border-top: 1px solid #e5e7eb;
}

/* Wiki链接样式 */
.wiki-content .mce-wikilink {
    color: #0645ad;
    text-decoration: none;
    background-color: #eaf3ff;
    padding: 0 2px;
    border-radius: 2px;
}

.wiki-content .mce-wikilink:hover {
    text-decoration: underline;
}

/* 动画效果 */
@keyframes highlight {
    0% {
        background-color: transparent;
    }

    20% {
        background-color: #fef3c7;
    }

    80% {
        background-color: #fef3c7;
    }

    100% {
        background-color: transparent;
    }
}

.wiki-content h2:target,
.wiki-content h3:target,
.wiki-content h4:target {
    animation: highlight 2s ease-in-out;
}
</style>
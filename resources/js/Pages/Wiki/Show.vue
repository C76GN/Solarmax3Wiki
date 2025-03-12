// FileName: /var/www/Solarmax3Wiki/resources/js/Pages/Wiki/Show.vue
<template>

    <MainLayout
        :navigationLinks="[{ href: '/wiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex gap-6">
                <!-- 左侧电梯导航 -->
                <aside class="hidden lg:block w-64 flex-shrink-0">
                    <div class="sticky top-6">
                        <nav class="space-y-1" aria-label="页面导航">
                            <TableOfContents :headers="pageHeaders" />
                        </nav>
                    </div>
                </aside>

                <!-- 主要内容区域 -->
                <div class="flex-1 min-w-0">
                    <!-- 页面头部 -->
                    <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg mb-6">
                        <div class="p-6">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h1 class="text-3xl font-bold text-gray-900">{{ page.title }}</h1>

                                    <!-- 页面元信息 -->
                                    <div class="mt-2 flex flex-wrap gap-4 text-sm text-gray-500">
                                        <div>
                                            创建者: {{ page.creator?.name || '未知' }}
                                        </div>
                                        <div>
                                            最后编辑: {{ formatDate(page.updated_at) }}
                                        </div>
                                        <div>
                                            浏览量: {{ page.view_count }}
                                        </div>
                                        <!--                                        <div>-->
                                        <!--                                            引用数: {{ page.references_count || 0 }}-->
                                        <!--                                        </div>-->
                                    </div>

                                    <!-- 分类标签 -->
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <Link v-for="category in page.categories" :key="category.id"
                                            :href="route('wiki.index', { category: category.id })"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200">
                                        {{ category.name }}
                                        </Link>
                                    </div>
                                </div>

                                <!-- 功能按钮区 -->
                                <div class="flex gap-4" v-if="$page.props.auth.user">
                                    <!-- 历史版本按钮 -->
                                    <!--                                    <Dropdown align="right" width="48">-->
                                    <!--                                        <template #trigger>-->
                                    <!--                                            <button-->
                                    <!--                                                class="inline-flex items-center gap-1 text-gray-500 hover:text-gray-700">-->
                                    <!--                                                <ClockIcon class="w-5 h-5"/>-->
                                    <!--                                                历史版本-->
                                    <!--                                            </button>-->
                                    <!--                                        </template>-->
                                    <!--                                        <template #content>-->
                                    <!--                                            <RevisionList :revisions="page.recent_revisions" :pageId="page.id"-->
                                    <!--                                                          :currentVersion="page.current_version"-->
                                    <!--                                                          :canEdit="can.edit_page"-->
                                    <!--                                                          @revert="revertToVersion"/>-->
                                    <!--                                        </template>-->
                                    <!--                                    </Dropdown>-->

                                    <!-- 关注按钮 -->
                                    <!--                                    <button @click="toggleFollow" class="inline-flex items-center gap-1"-->
                                    <!--                                        :class="isFollowing ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700'">-->
                                    <!--                                        <StarIcon class="w-5 h-5" />-->
                                    <!--                                        {{ isFollowing ? '已关注' : '关注' }}-->
                                    <!--                                    </button>-->

                                    <!-- 问题报告按钮 -->
                                    <button @click="reportIssue"
                                        class="inline-flex items-center gap-1 text-gray-500 hover:text-gray-700">
                                        <ExclamationTriangleIcon class="w-5 h-5" />
                                        报告问题
                                    </button>

                                    <!-- 编辑按钮 -->
                                    <Link v-if="can.edit_page" :href="route('wiki.edit', page.id)"
                                        class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800">
                                    <PencilIcon class="w-5 h-5" />
                                    编辑
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 页面内容 -->
                    <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden mb-6">
                        <div class="prose prose-lg max-w-none p-6" v-html="page.content"></div>
                    </div>
                    <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden">
                        <div class="p-4">
                            <input type="checkbox" id="no-handle" v-model="issueFilter" @change="applyFilter">
                            <label for="no-handle">只看未解决</label>
                        </div>
                        <div v-for="v in page.issue" :key="v.id">
                            <div class="p-4 mb-3" v-if="!issueFilter || v.status === 'to_be_solved'">
                                <div>{{ formatDate(v.created_at) }}</div>
                                <div class="p-2">
                                    <p>留言：</p>
                                    <div v-html="v.content"></div>
                                </div>
                                <div>
                                    <p v-if="v.status === 'to_be_solved'">
                                        <span style="color:red;">未解决</span>
                                        <button @click="handle_issue(v.id)" v-if="page.can.issue"
                                            class="inline-flex items-center ml-2 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200">标志已处理</button>
                                    </p>
                                    <p v-else style="color:green;">已解决</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 底部区域：相关页面 -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- 引用该页面的页面 -->
                        <!--                        <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg p-6">-->
                        <!--                            <h3 class="text-lg font-medium text-gray-900 mb-4">引用该页面</h3>-->
                        <!--                            <div class="space-y-2">-->
                        <!--                                <div v-for="ref in page.referenced_by" :key="ref.id" class="text-sm">-->
                        <!--                                    <Link :href="route('wiki.show', ref.id)" class="text-blue-600 hover:text-blue-800">-->
                        <!--                                        {{ ref.title }}-->
                        <!--                                    </Link>-->
                        <!--                                </div>-->
                        <!--                            </div>-->
                        <!--                        </div>-->

                        <!-- 被该页面引用的页面 -->
                        <!--                        <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg p-6">-->
                        <!--                            <h3 class="text-lg font-medium text-gray-900 mb-4">被引用页面</h3>-->
                        <!--                            <div class="space-y-2">-->
                        <!--                                <div v-for="ref in page.references" :key="ref.id" class="text-sm">-->
                        <!--                                    <Link :href="route('wiki.show', ref.id)" class="text-blue-600 hover:text-blue-800">-->
                        <!--                                    {{ ref.title }}-->
                        <!--                                    </Link>-->
                        <!--                                </div>-->
                        <!--                            </div>-->
                        <!--                        </div>-->
                    </div>
                </div>
            </div>
        </div>

        <!-- 返回顶部按钮 -->
        <!--        <button v-show="showBackToTop" @click="scrollToTop"-->
        <!--                class="fixed bottom-8 right-8 bg-gray-800/50 hover:bg-gray-800/75 text-white rounded-full p-3 transition-all duration-300">-->
        <!--            <ArrowUpIcon class="w-6 h-6"/>-->
        <!--        </button>-->

        <!-- 问题报告对话框 -->
        <Modal :show="showReportModal" @close="showReportModal = false">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4 " style="color: #fff">报告问题</h3>
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
    </MainLayout>
</template>

<script setup>
import {Link, router} from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import {onMounted, onUnmounted, ref, watch} from 'vue';
import Modal from "@/Components/Modal/Modal.vue";
import {useEditor} from '@/plugins/tinymce';

const pageHeaders = ref([]);
let showReportModal = ref(false)
const editor = ref(null);
const editorContainer = ref(null);
const issueContent = ref('');
const {init: editorConfig} = useEditor();

let show_no_handle = ref(false)




const cleanup = () => {
    if (editor.value) {
        editor.value.destroy();
        editor.value = null;
    }
}
onUnmounted(() => {
    cleanup();
});


const parseHeaders = (content) => {
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = content;

    const headers = Array.from(tempDiv.querySelectorAll('h2, h3, h4'))
        .map(header => {
            // 为标题添加 id
            const id = header.textContent.toLowerCase().replace(/\s+/g, '-');
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
        header.id = headers[index].id;
    });

    return tempDiv.innerHTML;
};

onMounted(() => {
    if (props.page.content) {
        props.page.content = processContent(props.page.content);
    }

});

const props = defineProps({
    page: {
        type: Object,
        required: true
    },
    can: {
        type: Object,
        default: () => ({})
    }
});

const applyFilter = () => {
    let params = { page: props.page.id };
    if (issueFilter.value) {
        params.filter = 'unresolved';
    }
    router.get(route('wiki.show', params), {}, { preserveState: true });
};


const issueFilter = ref(route().params.filter === 'unresolved' ? true : false);


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
    showReportModal.value = true
    setTimeout(()=>{
        const config = {
            ...editorConfig,
            selector: '#textarea',
            init_instance_callback: (ed) => {

            },
            setup: (ed) => {
                ed.on('input change', () => {
                    issueContent.value = ed.getContent();
                });
            }
        };
        window.tinymce?.init(config);
    },300)

};

const submitIssue = () => {
    router.post(route('wiki.issue'), {
        content: issueContent.value,
        page_id: props.page.id
    });
    showReportModal.value = false
};

const handle_issue = (id) => {
    router.post(route('wiki.issue_handle'), { id }, {
        onSuccess: () => {
            issueFilter.value = true;  // 处理后默认切换到“只看未解决”
            applyFilter();
        },
        onError: (error) => {
            alert("处理失败: " + error.message);
        }
    });
};


const publishPage = () => {
    router.post(route('wiki.publish', props.page.id));
};

watch(show_no_handle,(n,o)=>{
    if (n){

    }
})


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

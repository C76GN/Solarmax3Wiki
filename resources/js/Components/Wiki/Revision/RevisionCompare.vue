<template>
    <div class="revision-compare">
        <div class="bg-gray-100 p-4 rounded-lg mb-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <h3 class="text-sm font-medium text-gray-700">旧版本 ({{ fromVersion }})</h3>
                    <p class="text-sm text-gray-500">{{ formatDate(oldRevision.created_at) }}</p>
                    <p class="text-sm text-gray-500">由 {{ oldRevision.creator?.name || '未知' }} 编辑</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-700">新版本 ({{ toVersion }})</h3>
                    <p class="text-sm text-gray-500">{{ formatDate(newRevision.created_at) }}</p>
                    <p class="text-sm text-gray-500">由 {{ newRevision.creator?.name || '未知' }} 编辑</p>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <div class="flex justify-between items-center mb-2">
                <h4 class="text-lg font-medium">版本对比</h4>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-red-100 rounded mr-2"></div>
                        <span class="text-sm text-gray-600">已删除内容</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-green-100 rounded mr-2"></div>
                        <span class="text-sm text-gray-600">新增内容</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-yellow-100 rounded mr-2"></div>
                        <span class="text-sm text-gray-600">修改内容</span>
                    </div>
                </div>
            </div>

            <div class="border border-gray-200 rounded-lg">
                <div class="flex items-center p-2 border-b border-gray-200 bg-gray-50">
                    <button @click="compareMode = 'unified'" class="px-3 py-1 rounded text-sm"
                        :class="compareMode === 'unified' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700'">
                        统一视图
                    </button>
                    <button @click="compareMode = 'split'" class="px-3 py-1 rounded text-sm ml-2"
                        :class="compareMode === 'split' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700'">
                        分栏视图
                    </button>
                </div>

                <!-- 统一视图 -->
                <div v-if="compareMode === 'unified'" class="space-y-2 p-4">
                    <div v-for="(diff, index) in diffs" :key="index" :class="[
                        'p-3 rounded font-mono text-sm whitespace-pre-wrap',
                        {
                            'bg-red-100': diff.removed,
                            'bg-green-100': diff.added,
                            'bg-white': !diff.added && !diff.removed
                        }
                    ]">
                        {{ diff.value }}
                    </div>
                </div>

                <!-- 分栏视图 -->
                <div v-else class="grid grid-cols-2 gap-0 border-t border-gray-200">
                    <div class="border-r border-gray-200 p-4">
                        <h5 class="text-sm font-medium text-gray-700 mb-2">旧版本</h5>
                        <div class="prose prose-sm max-w-none" v-html="highlightDiffs(oldRevision.content, 'old')">
                        </div>
                    </div>
                    <div class="p-4">
                        <h5 class="text-sm font-medium text-gray-700 mb-2">新版本</h5>
                        <div class="prose prose-sm max-w-none" v-html="highlightDiffs(newRevision.content, 'new')">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 新增：版本注释信息 -->
        <div class="bg-gray-50 p-4 rounded-lg mt-6">
            <h4 class="text-md font-medium mb-2">修改说明</h4>
            <p v-if="newRevision.comment" class="text-sm text-gray-700">{{ newRevision.comment }}</p>
            <p v-else class="text-sm text-gray-500">没有提供修改说明</p>

            <div class="mt-4" v-if="newRevision.changes">
                <h5 class="text-sm font-medium mb-2">变更统计</h5>
                <div class="flex space-x-4 text-sm">
                    <div v-if="newRevision.changes.added?.length" class="text-green-600">
                        新增了 {{ newRevision.changes.added.length }} 行
                    </div>
                    <div v-if="newRevision.changes.removed?.length" class="text-red-600">
                        删除了 {{ newRevision.changes.removed.length }} 行
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { diffLines, diffWords } from 'diff';

const props = defineProps({
    oldRevision: {
        type: Object,
        required: true
    },
    newRevision: {
        type: Object,
        required: true
    },
    fromVersion: {
        type: Number,
        required: true
    },
    toVersion: {
        type: Number,
        required: true
    }
});

// 对比模式：unified（统一视图）或 split（分栏视图）
const compareMode = ref('unified');

// 按行对比
const diffs = computed(() => {
    return diffLines(
        props.oldRevision.content || '',
        props.newRevision.content || ''
    );
});

// 格式化日期
const formatDate = (date) => {
    return new Date(date).toLocaleString('zh-CN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// 高亮差异（用于分栏视图）
const highlightDiffs = (content, version) => {
    if (!content) return '';

    // 创建临时DOM元素解析HTML内容
    const tempEl = document.createElement('div');
    tempEl.innerHTML = content;

    // 获取纯文本内容
    const text = tempEl.textContent;

    // 对比纯文本内容
    const wordDiffs = diffWords(
        props.oldRevision.content ? document.createElement('div').textContent = props.oldRevision.content : '',
        props.newRevision.content ? document.createElement('div').textContent = props.newRevision.content : ''
    );

    // 根据对比结果高亮差异
    let highlightedText = '';
    let position = 0;

    wordDiffs.forEach(part => {
        if (
            (part.added && version === 'new') ||
            (part.removed && version === 'old')
        ) {
            // 高亮部分
            highlightedText += `<mark class="${part.added ? 'bg-green-200' : 'bg-red-200'}">${part.value}</mark>`;
        } else if (
            (part.added && version === 'old') ||
            (part.removed && version === 'new')
        ) {
            // 跳过另一版本专有的部分
        } else {
            // 未改变部分
            highlightedText += part.value;
        }
    });

    return highlightedText;
};
</script>

<style scoped>
/* 可以添加样式来优化显示效果 */
</style>
// FileName: /var/www/Solarmax3Wiki/resources/js/Components/Wiki/Revision/RevisionCompare.vue
<template>
    <div class="revision-compare">
        <!-- 版本信息头部 -->
        <div class="bg-gray-100 p-4 rounded-lg mb-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <h3 class="text-sm font-medium text-gray-700">旧版本 ({{ fromVersion }})</h3>
                    <p class="text-sm text-gray-500">{{ formatDate(oldRevision.created_at) }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-700">新版本 ({{ toVersion }})</h3>
                    <p class="text-sm text-gray-500">{{ formatDate(newRevision.created_at) }}</p>
                </div>
            </div>
        </div>

        <!-- 差异对比图例 -->
        <div class="flex gap-4 mb-4 p-4 bg-gray-50 rounded-lg">
            <div class="flex items-center">
                <div class="w-4 h-4 bg-red-100 rounded mr-2"></div>
                <span class="text-sm text-gray-600">已删除内容</span>
            </div>
            <div class="flex items-center">
                <div class="w-4 h-4 bg-green-100 rounded mr-2"></div>
                <span class="text-sm text-gray-600">新增内容</span>
            </div>
        </div>

        <!-- 差异内容显示 -->
        <div class="space-y-2">
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
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { diffLines } from 'diff';

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

// 计算文本差异
const diffs = computed(() => {
    return diffLines(
        props.oldRevision.content || '',
        props.newRevision.content || ''
    );
});

const formatDate = (date) => {
    return new Date(date).toLocaleString('zh-CN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>
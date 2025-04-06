<template>
    <MainLayout :navigationLinks="navigationLinks">
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold">{{ page.title }} - 版本比较</h1>

                    <div class="flex items-center space-x-3">
                        <Link :href="route('wiki.history', page.slug)" class="text-blue-600 hover:text-blue-800">
                        返回历史版本
                        </Link>
                        <Link :href="route('wiki.show', page.slug)" class="text-blue-600 hover:text-blue-800">
                        返回页面
                        </Link>
                    </div>
                </div>

                <!-- 版本信息 -->
                <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 bg-red-50 rounded-lg">
                        <h3 class="font-medium mb-2">旧版本 (v{{ fromVersion.version_number }})</h3>
                        <div class="text-sm text-gray-600">
                            <p>编辑者: {{ fromCreator.name }}</p>
                            <p>时间: {{ formatDateTime(fromVersion.created_at) }}</p>
                            <p>说明: {{ fromVersion.comment || '无说明' }}</p>
                        </div>
                    </div>

                    <div class="p-4 bg-green-50 rounded-lg">
                        <h3 class="font-medium mb-2">新版本 (v{{ toVersion.version_number }})</h3>
                        <div class="text-sm text-gray-600">
                            <p>编辑者: {{ toCreator.name }}</p>
                            <p>时间: {{ formatDateTime(toVersion.created_at) }}</p>
                            <p>说明: {{ toVersion.comment || '无说明' }}</p>
                        </div>
                    </div>
                </div>

                <!-- 差异比较 -->
                <div class="mb-6">
                    <h2 class="text-xl font-bold mb-4">内容差异</h2>

                    <div class="border rounded-lg overflow-hidden">
                        <div id="diff-viewer" class="p-4">
                            <!-- 差异内容将通过JS动态渲染 -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import { formatDateTime } from '@/utils/formatters';
import * as DiffMatchPatch from 'diff-match-patch';

const navigationLinks = [
    { href: '/wiki', label: 'Wiki' },
    { href: '#', label: '游戏历史&名人墙' },
    { href: '#', label: '自制专区' },
    { href: '#', label: '攻略专区' },
    { href: '#', label: '论坛' }
];

const props = defineProps({
    page: {
        type: Object,
        required: true
    },
    fromVersion: {
        type: Object,
        required: true
    },
    toVersion: {
        type: Object,
        required: true
    },
    fromCreator: {
        type: Object,
        required: true
    },
    toCreator: {
        type: Object,
        required: true
    }
});

// 在挂载后计算并渲染差异
onMounted(() => {
    renderDiff();
});

const renderDiff = () => {
    const dmp = new DiffMatchPatch();

    // 获取两个版本的内容
    const oldText = props.fromVersion.content;
    const newText = props.toVersion.content;

    // 计算差异
    const diffs = dmp.diff_main(oldText, newText);

    // 美化差异（合并相近的块）
    dmp.diff_cleanupSemantic(diffs);

    // 将差异渲染为HTML
    const diffHtml = diffs.map(([op, text]) => {
        switch (op) {
            case -1: // 删除的内容
                return `<del class="bg-red-200 px-1">${escapeHtml(text)}</del>`;
            case 1: // 添加的内容
                return `<ins class="bg-green-200 px-1">${escapeHtml(text)}</ins>`;
            case 0: // 未改变的内容
                return `<span>${escapeHtml(text)}</span>`;
        }
    }).join('');

    // 将HTML插入到页面中
    document.getElementById('diff-viewer').innerHTML = diffHtml;
};

// 辅助函数：转义 HTML 字符
const escapeHtml = (text) => {
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
        .replace(/\n/g, '<br>');
};
</script>

<style>
#diff-viewer {
    white-space: pre-wrap;
    font-family: monospace;
    line-height: 1.5;
}

#diff-viewer del {
    text-decoration: none;
}

#diff-viewer ins {
    text-decoration: none;
}
</style>
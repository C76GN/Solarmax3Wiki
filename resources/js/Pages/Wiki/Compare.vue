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

                <!-- 差异对比视图选项 -->
                <div class="mb-4">
                    <div class="flex space-x-4">
                        <button @click="viewMode = 'unified'" :class="[
                                'px-3 py-1 rounded-md',
    viewMode === 'unified'
        ? 'bg-blue-600 text-white'
        : 'bg-gray-200 text-gray-700'
]">
                            统一视图
                        </button>
                        <button @click="viewMode = 'side-by-side'" :class="[
                            'px-3 py-1 rounded-md',
                            viewMode === 'side-by-side'
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-200 text-gray-700'
                        ]">
                            并排视图
                        </button>
                    </div>
                </div>

                <div class="mb-6">
                    <h2 class="text-xl font-bold mb-4">内容差异</h2>

                    <!-- 统一视图 -->
                    <div v-if="viewMode === 'unified'" class="border rounded-lg overflow-hidden">
                        <div id="diff-viewer" class="p-4 diff-content">
                        </div>
                    </div>

                    <!-- 并排视图 -->
                    <div v-else class="border rounded-lg overflow-hidden">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-0 border-b">
                            <div class="p-2 bg-gray-100 font-medium border-r">旧版本</div>
                            <div class="p-2 bg-gray-100 font-medium">新版本</div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-0">
                            <div class="p-4 border-r old-content"></div>
                            <div class="p-4 new-content"></div>
                        </div>
                    </div>

                    <!-- 快速恢复按钮 -->
                    <div v-if="canRevert" class="mt-4 text-right">
                        <button @click="confirmRevert"
                            class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700">
                            恢复到此版本 (v{{ toVersion.version_number }})
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 确认恢复对话框 -->
        <Modal :show="showRevertModal" @close="closeRevertModal" maxWidth="md">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    确认恢复版本
                </h3>
                <p class="mb-4 text-gray-600">
                    您确定要将页面恢复到版本 v{{ toVersion.version_number }} 吗？
                    <br>此操作将创建一个新版本，保留历史记录。
                </p>
                <div class="flex justify-end">
                    <button @click="closeRevertModal" class="px-4 py-2 bg-gray-200 text-gray-700 rounded mr-2">
                        取消
                    </button>
                    <button @click="revertToVersion" class="px-4 py-2 bg-blue-600 text-white rounded">
                        确认恢复
                    </button>
                </div>
            </div>
        </Modal>
    </MainLayout>
</template>

<script setup>
import { onMounted, ref, computed, watch, nextTick } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Modal from '@/Components/Modal/Modal.vue';
import { formatDateTime } from '@/utils/formatters';
import * as DiffMatchPatch from 'diff-match-patch';
import { mainNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = mainNavigationLinks;

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

const viewMode = ref('unified');
const showRevertModal = ref(false);

const canRevert = computed(() => {
    // 检查当前用户是否有编辑权限
    return $page?.props?.auth?.user?.permissions?.includes('wiki.edit') || false;
});

onMounted(() => {
    renderDiff();
});

// 监听视图模式变化重新渲染差异
watch(viewMode, () => {
    nextTick(() => {
        renderDiff();
    });
});

const renderDiff = () => {
    if (viewMode.value === 'unified') {
        renderUnifiedDiff();
    } else {
        renderSideBySideDiff();
    }
};

const renderUnifiedDiff = () => {
    const dmp = new DiffMatchPatch();
    const oldText = props.fromVersion.content;
    const newText = props.toVersion.content;

    // 先清空差异容器
    document.getElementById('diff-viewer').innerHTML = '';

    // 计算差异并美化输出
    const diffs = dmp.diff_main(oldText, newText);
    dmp.diff_cleanupSemantic(diffs);

    // 分段处理差异，提高可读性
    const segments = splitDiffIntoSegments(diffs);

    // 生成HTML并添加到容器
    const container = document.getElementById('diff-viewer');

    segments.forEach(segment => {
        const segmentDiv = document.createElement('div');
        segmentDiv.className = 'diff-segment mb-4 pb-4 border-b border-gray-200';

        // 添加段落标题
        if (segment.type !== 'unchanged') {
            const heading = document.createElement('h4');
            heading.className = segment.type === 'added'
                ? 'text-green-600 mb-2 font-medium'
                : segment.type === 'removed'
                    ? 'text-red-600 mb-2 font-medium'
                    : 'text-orange-600 mb-2 font-medium';

            heading.textContent = segment.type === 'added'
                ? '新增内容'
                : segment.type === 'removed'
                    ? '删除内容'
                    : '修改内容';

            segmentDiv.appendChild(heading);
        }

        // 添加差异内容
        const content = document.createElement('div');
        content.innerHTML = segment.html;
        segmentDiv.appendChild(content);

        container.appendChild(segmentDiv);
    });
};

const splitDiffIntoSegments = (diffs) => {
    // 将差异分组为有意义的段落
    const segments = [];
    let currentSegment = { type: 'unchanged', html: '', diffs: [] };

    diffs.forEach(([op, text]) => {
        // 检测如果是段落变更，开始新段落
        if (text.includes('\n\n') || text.length > 200) {
            if (currentSegment.html.length > 0) {
                segments.push(currentSegment);
                currentSegment = { type: 'unchanged', html: '', diffs: [] };
            }
        }

        // 根据操作类型设置片段类型
        if (op !== 0 && currentSegment.type === 'unchanged') {
            currentSegment.type = op === 1 ? 'added' : op === -1 ? 'removed' : 'modified';
        } else if (op !== 0 && currentSegment.type !== 'modified') {
            if ((op === 1 && currentSegment.type === 'removed') ||
                (op === -1 && currentSegment.type === 'added')) {
                currentSegment.type = 'modified';
            }
        }

        // 添加HTML
        if (op === -1) {
            currentSegment.html += `<del class="bg-red-200 px-1">${escapeHtml(text)}</del>`;
        } else if (op === 1) {
            currentSegment.html += `<ins class="bg-green-200 px-1">${escapeHtml(text)}</ins>`;
        } else {
            currentSegment.html += `<span>${escapeHtml(text)}</span>`;
        }

        currentSegment.diffs.push([op, text]);
    });

    // 添加最后一个段落
    if (currentSegment.html.length > 0) {
        segments.push(currentSegment);
    }

    return segments;
};

const renderSideBySideDiff = () => {
    const oldContent = document.querySelector('.old-content');
    const newContent = document.querySelector('.new-content');

    // 清空容器
    oldContent.innerHTML = '';
    newContent.innerHTML = '';

    // 为了简化，我们用简单的方式处理
    const oldHtml = `<div class="prose max-w-none">${props.fromVersion.content}</div>`;
    const newHtml = `<div class="prose max-w-none">${props.toVersion.content}</div>`;

    oldContent.innerHTML = oldHtml;
    newContent.innerHTML = newHtml;

    // 在真实项目中，这里可以使用更复杂的差异对比算法来高亮显示差异
};

const escapeHtml = (text) => {
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
        .replace(/\n/g, '<br>');
};

const confirmRevert = () => {
    showRevertModal.value = true;
};

const closeRevertModal = () => {
    showRevertModal.value = false;
};

const revertToVersion = () => {
    router.post(route('wiki.revert-version', {
        page: props.page.slug,
        version: props.toVersion.version_number
    }), {}, {
        onSuccess: () => {
            closeRevertModal();
        }
    });
};
</script>

<style>
.diff-content {
    white-space: pre-wrap;
    font-family: monospace;
    line-height: 1.5;
}

.diff-content del {
    text-decoration: none;
    display: inline-block;
}

.diff-content ins {
    text-decoration: none;
    display: inline-block;
}

.diff-segment {
    position: relative;
}

.old-content,
.new-content {
    min-height: 400px;
    max-height: 600px;
    overflow-y: auto;
}
</style>
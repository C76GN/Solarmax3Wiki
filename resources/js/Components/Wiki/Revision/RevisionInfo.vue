// FileName: /var/www/Solarmax3Wiki/resources/js/Components/Wiki/Revision/RevisionInfo.vue
<template>
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <!-- 版本基本信息 -->
        <div class="p-6">
            <div class="flex justify-between items-start">
                <h2 class="text-xl font-semibold text-gray-900">
                    版本 {{ revision.version }}
                </h2>
                <div class="flex space-x-2">
                    <!-- 比较版本按钮 -->
                    <button v-if="showCompareButton" @click="$emit('compare', revision.version)"
                        class="inline-flex items-center px-3 py-1 border border-gray-300 text-sm text-gray-700 bg-white rounded-md hover:bg-gray-50">
                        比较版本
                    </button>
                    <!-- 恢复版本按钮 -->
                    <button v-if="canRevert && revision.version !== currentVersion"
                        @click="$emit('revert', revision.version)"
                        class="inline-flex items-center px-3 py-1 border border-blue-500 text-sm text-blue-700 bg-blue-50 rounded-md hover:bg-blue-100">
                        恢复此版本
                    </button>
                </div>
            </div>

            <!-- 版本元信息 -->
            <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">创建者</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ revision.creator.name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">创建时间</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ formatDate(revision.created_at) }}</dd>
                </div>
                <div v-if="revision.comment" class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">修改说明</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ revision.comment }}</dd>
                </div>
            </dl>

            <!-- 变更统计 -->
            <div v-if="revision.changes" class="mt-6">
                <h3 class="text-sm font-medium text-gray-500 mb-2">变更统计</h3>
                <div class="space-y-2">
                    <div v-if="revision.changes.added?.length" class="text-sm text-green-600 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        新增了 {{ revision.changes.added.length }} 行
                    </div>
                    <div v-if="revision.changes.removed?.length" class="text-sm text-red-600 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                        </svg>
                        删除了 {{ revision.changes.removed.length }} 行
                    </div>
                </div>
            </div>
        </div>

        <!-- 版本内容 -->
        <div class="border-t border-gray-200">
            <div class="px-6 py-4">
                <h3 class="text-sm font-medium text-gray-500 mb-4">版本内容</h3>
                <div class="prose prose-sm max-w-none" v-html="revision.content"></div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { formatDate } from '@/utils/formatters';

const props = defineProps({
    revision: {
        type: Object,
        required: true
    },
    currentVersion: {
        type: Number,
        required: true
    },
    canRevert: {
        type: Boolean,
        default: false
    },
    showCompareButton: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['revert', 'compare']);
</script>
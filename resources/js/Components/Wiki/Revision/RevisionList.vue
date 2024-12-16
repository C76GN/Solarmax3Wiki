// FileName: /var/www/Solarmax3Wiki/resources/js/Components/Wiki/Revision/RevisionList.vue
<template>
    <div class="space-y-4">
        <div v-for="revision in revisions" :key="revision.version" class="border-b border-gray-200 pb-4 last:border-0">
            <div class="flex justify-between items-start">
                <div>
                    <div class="font-medium text-gray-900">
                        版本 {{ revision.version }}
                        <span v-if="revision.comment" class="text-gray-500 text-sm">
                            - {{ revision.comment }}
                        </span>
                    </div>
                    <div class="text-sm text-gray-500">
                        由 {{ revision.creator.name }} 于 {{ formatDate(revision.created_at) }} 修改
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link :href="route('wiki.show-revision', [pageId, revision.version])"
                        class="text-blue-600 hover:text-blue-900 text-sm">
                    查看此版本
                    </Link>
                    <button v-if="canEdit && revision.version !== currentVersion"
                        @click="$emit('revert', revision.version)" class="text-green-600 hover:text-green-900 text-sm">
                        恢复此版本
                    </button>
                </div>
            </div>
            <div v-if="revision.changes" class="mt-2">
                <div v-if="revision.changes.added?.length" class="text-green-600 text-sm">
                    + 添加了 {{ revision.changes.added.length }} 行
                </div>
                <div v-if="revision.changes.removed?.length" class="text-red-600 text-sm">
                    - 删除了 {{ revision.changes.removed.length }} 行
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    revisions: {
        type: Array,
        required: true
    },
    pageId: {
        type: Number,
        required: true
    },
    currentVersion: {
        type: Number,
        required: true
    },
    canEdit: {
        type: Boolean,
        default: false
    }
});

const formatDate = (date) => {
    return new Date(date).toLocaleString('zh-CN');
};

defineEmits(['revert']);
</script>
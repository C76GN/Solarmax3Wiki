<template>
    <MainLayout :navigationLinks="navigationLinks">
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold">{{ page.title }} - 冲突解决</h1>

                    <div class="flex items-center space-x-3">
                        <Link :href="route('wiki.history', page.slug)" class="text-blue-600 hover:text-blue-800">
                        查看历史版本
                        </Link>
                        <Link :href="route('wiki.show', page.slug)" class="text-blue-600 hover:text-blue-800">
                        返回页面
                        </Link>
                    </div>
                </div>

                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6">
                    <div class="flex">
                        <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="mr-2 mt-1 flex-shrink-0" />
                        <div>
                            <p class="font-bold">冲突说明</p>
                            <p>该页面存在编辑冲突，需要您手动合并内容。请查看下方的冲突内容，选择要保留的部分，或手动编辑后提交解决方案。</p>
                        </div>
                    </div>
                </div>

                <!-- 冲突解决表单 -->
                <form @submit.prevent="resolveConflict">
                    <div class="space-y-6">
                        <!-- 合并后的内容 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                合并后的内容
                            </label>
                            <Editor v-model="form.content" placeholder="请合并冲突内容..." />
                            <div v-if="form.errors.content" class="mt-1 text-sm text-red-600">
                                {{ form.errors.content }}
                            </div>
                        </div>

                        <!-- 解决说明 -->
                        <div>
                            <label for="resolution_comment" class="block text-sm font-medium text-gray-700 mb-1">
                                解决说明
                            </label>
                            <textarea id="resolution_comment" v-model="form.resolution_comment" rows="2"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="请简要描述如何解决此冲突..."></textarea>
                        </div>

                        <!-- 提交按钮 -->
                        <div class="flex justify-end space-x-3">
                            <Link :href="route('wiki.show', page.slug)"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                            取消
                            </Link>

                            <button type="submit"
                                class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition"
                                :disabled="form.processing">
                                解决冲突
                            </button>
                        </div>
                    </div>
                </form>

                <!-- 冲突版本 -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h2 class="text-xl font-bold mb-4">冲突版本</h2>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- 当前版本 -->
                        <div class="border rounded-lg overflow-hidden">
                            <div class="bg-gray-100 px-4 py-2 font-medium">
                                当前版本 (v{{ conflictVersions.current.version_number }})
                                <div class="text-sm text-gray-600">
                                    由 {{ conflictVersions.current.creator.name }} 创建于 {{
                                        formatDateTime(conflictVersions.current.created_at) }}
                                </div>
                            </div>
                            <div class="p-4">
                                <button @click="selectVersion('current')"
                                    class="px-3 py-1 mb-2 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 text-sm">
                                    使用此版本
                                </button>
                                <div class="prose max-w-none">
                                    <div v-html="conflictVersions.current.content"></div>
                                </div>
                            </div>
                        </div>

                        <!-- 冲突版本 -->
                        <div class="border rounded-lg overflow-hidden">
                            <div class="bg-gray-100 px-4 py-2 font-medium">
                                冲突版本 (v{{ conflictVersions.conflict.version_number }})
                                <div class="text-sm text-gray-600">
                                    由 {{ conflictVersions.conflict.creator.name }} 创建于 {{
                                        formatDateTime(conflictVersions.conflict.created_at) }}
                                </div>
                            </div>
                            <div class="p-4">
                                <button @click="selectVersion('conflict')"
                                    class="px-3 py-1 mb-2 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 text-sm">
                                    使用此版本
                                </button>
                                <div class="prose max-w-none">
                                    <div v-html="conflictVersions.conflict.content"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Editor from '@/Components/Wiki/Editor.vue';
import { formatDateTime } from '@/utils/formatters';

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
    conflictVersions: {
        type: Object,
        required: true
    }
});

const form = useForm({
    content: props.page.currentVersion?.content || '',
    resolution_comment: '解决页面冲突'
});

// 选择特定版本的内容
const selectVersion = (version) => {
    if (version === 'current') {
        form.content = props.conflictVersions.current.content;
    } else if (version === 'conflict') {
        form.content = props.conflictVersions.conflict.content;
    }
};

// 解决冲突
const resolveConflict = () => {
    form.post(route('wiki.resolve-conflict', props.page.slug));
};
</script>
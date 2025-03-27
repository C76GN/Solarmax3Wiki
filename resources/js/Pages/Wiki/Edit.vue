<template>
    <MainLayout
        :navigationLinks="[{ href: '/wiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden">
                <div class="p-6">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-6">编辑页面</h2>

                    <!-- 添加当前编辑者信息显示 -->
                    <div v-if="currentEditors.length > 0"
                        class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                        <p class="text-yellow-700">
                            <span class="font-medium">注意：</span>
                            {{ currentEditors.join('、') }} 也在编辑此页面。请注意协调修改内容，避免冲突。
                        </p>
                    </div>

                    <WikiPageForm :page="page" :categories="categories" @content-changed="updateContent" />

                    <!-- 冲突警告对话框 -->
                    <Modal :show="showConflictWarning" @close="acknowledgeWarning">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-red-600 mb-4">内容冲突警告</h3>
                            <p class="mb-4">
                                在您编辑期间，另一用户已更新了此页面内容。继续提交将覆盖他们的更改。
                            </p>
                            <div class="mt-5 flex justify-end gap-4">
                                <button @click="viewDiff"
                                    class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                                    查看差异
                                </button>
                                <button @click="handleForceSubmit"
                                    class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600">
                                    仍然提交
                                </button>
                                <button @click="acknowledgeWarning"
                                    class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                                    返回编辑
                                </button>
                            </div>
                        </div>
                    </Modal>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import WikiPageForm from '@/Components/Wiki/WikiPageForm.vue';
import Modal from '@/Components/Modal/Modal.vue';
import { ref } from "vue";
import { useEditConflict } from '@/Composables/useEditConflict';

const props = defineProps({
    page: {
        type: Object,
        required: true
    },
    categories: {
        type: Array,
        required: true
    }
});

// 使用编辑冲突处理 Composable
const {
    showConflictWarning,
    currentEditors,
    modifiedContent,
    updateContent,
    viewDiff,
    forceSubmit
} = useEditConflict(props.page.id, props.page.content);

// 用于存储表单数据的引用
const formData = ref({
    title: props.page.title,
    content: props.page.content,
    categories: props.page.categories
});

// 确认警告对话框
const acknowledgeWarning = () => {
    showConflictWarning.value = false;
};

// 处理强制提交
const handleForceSubmit = () => {
    forceSubmit({
        title: formData.value.title,
        content: modifiedContent.value,
        categories: formData.value.categories,
        last_check: props.page.updated_at
    });
    showConflictWarning.value = false;
};
</script>
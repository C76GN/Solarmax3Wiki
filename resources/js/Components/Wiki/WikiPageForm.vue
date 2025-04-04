<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div>
            <label class="block text-sm font-medium text-gray-700">页面标题</label>
            <input type="text" v-model="form.title"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': form.errors.title }">
            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">页面分类</label>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="category in categories" :key="category.id" class="relative flex items-start">
                    <div class="flex h-5 items-center">
                        <input type="checkbox" :value="category.id" v-model="form.categories"
                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    </div>
                    <div class="ml-3 text-sm">
                        <label class="font-medium text-gray-700">{{ category.name }}</label>
                        <p class="text-gray-500">{{ category.description }}</p>
                    </div>
                </div>
            </div>
            <p v-if="form.errors.categories" class="mt-1 text-sm text-red-600">{{ form.errors.categories }}</p>
        </div>

        <div>
            <div class="flex justify-between items-center mb-2">
                <label class="block text-sm font-medium text-gray-700">页面内容</label>
            </div>

            <div ref="editorContainer">
                <WikiEditor v-model="form.content" :autoSaveKey="autoSaveKey" @auto-save="handleAutoSave"
                    @content-changed="updateContent" />
            </div>

            <p v-if="form.errors.content" class="mt-1 text-sm text-red-600">{{ form.errors.content }}</p>

            <div v-if="isEditing" class="mt-4">
                <label class="block text-sm font-medium text-gray-700">修改说明</label>
                <input type="text" v-model="form.comment"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    placeholder="简要描述你的修改（可选）">
            </div>
        </div>

        <div v-if="editConflictWarning" class="bg-amber-50 border-l-4 border-amber-500 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <ExclamationTriangleIcon class="h-5 w-5 text-amber-500" />
                </div>
                <div class="ml-3">
                    <p class="text-sm text-amber-700">
                        <strong>警告：</strong> {{ editConflictWarning }}
                    </p>
                </div>
            </div>
        </div>

        <div v-if="currentEditors.length > 0" class="bg-blue-50 border-l-4 border-blue-500 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <InformationCircleIcon class="h-5 w-5 text-blue-500" />
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        <strong>提示：</strong> {{ currentEditors.join('、') }} 也在编辑此页面。
                    </p>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-4">
            <Link :href="route('wiki.index')"
                class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-all duration-300">
            取消
            </Link>
            <Button type="submit" variant="primary" :disabled="form.processing || submitting">
                {{ isEditing ? '更新页面' : '创建页面' }}
            </Button>
        </div>
    </form>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { ExclamationTriangleIcon, InformationCircleIcon } from '@heroicons/vue/24/outline';
import WikiEditor from '@/Components/Editor/WikiEditor.vue';
import { useEditConflict } from '@/Composables/useEditConflict';
import Button from '@/Components/Buttons/Button.vue';

const props = defineProps({
    page: {
        type: Object,
        default: null
    },
    categories: {
        type: Array,
        required: true
    }
});

const emit = defineEmits(['content-changed']);

const editorContainer = ref(null);
const autoSaveStatus = ref('');
const submitting = ref(false);
const editConflictWarning = ref('');

// 检查页面是否为编辑模式
const isEditing = computed(() => !!props.page);

// 自动保存键值
const autoSaveKey = computed(() =>
    isEditing.value
        ? `wiki_edit_${props.page.id}`
        : 'wiki_new_draft'
);

// 表单
const form = useForm({
    title: props.page?.title || '',
    content: props.page?.content || '',
    categories: props.page?.categories || [],
    last_check: props.page?.updated_at || '',
    comment: '',
    force_update: false
});

// 使用编辑冲突检测
const {
    showConflictWarning,
    currentEditors,
    modifiedContent,
    updateContent,
    viewDiff,
    forceSubmit
} = useEditConflict(
    props.page?.id || 0,
    props.page?.content || ''
);

// 监听冲突警告
watch(showConflictWarning, (newVal) => {
    if (newVal) {
        editConflictWarning.value = '在您编辑期间，另一用户已更新了此页面内容。继续提交将覆盖他们的更改。';
    } else {
        editConflictWarning.value = '';
    }
});

// 自动保存处理
const handleAutoSave = (data) => {
    autoSaveStatus.value = `草稿已自动保存 (${new Date().toLocaleTimeString()})`;

    // 更新冲突检测中的内容
    updateContent(form.content);

    // 向父组件通知内容变化
    emit('content-changed', form.content);
};

// 表单提交
const submit = () => {
    // 检查是否有冲突警告
    if (showConflictWarning.value) {
        if (!confirm('检测到页面内容已被其他用户修改。是否仍要提交您的更改？')) {
            return;
        }
        form.force_update = true;
    }

    submitting.value = true;
    form.last_check = props.page?.updated_at || '';

    if (isEditing.value) {
        form.put(route('wiki.update', props.page.id), {
            onSuccess: () => {
                // 清除草稿并重置表单
                localStorage.removeItem(autoSaveKey.value);
                submitting.value = false;
            },
            onError: (errors) => {
                submitting.value = false;
                if (errors.conflict) {
                    console.error("内容冲突:", errors.message);
                    viewDiff(form.content);
                }
            }
        });
    } else {
        form.post(route('wiki.store'), {
            onSuccess: () => {
                // 清除草稿并重置表单
                localStorage.removeItem(autoSaveKey.value);
                submitting.value = false;
            },
            onError: () => {
                submitting.value = false;
            }
        });
    }
};

// 生命周期钩子
onMounted(() => {
    // 为Ctrl+S快捷键添加事件监听
    const handleKeyDown = (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            submit();
        }
    };

    window.addEventListener('keydown', handleKeyDown);

    onBeforeUnmount(() => {
        window.removeEventListener('keydown', handleKeyDown);
    });
});
</script>

<style scoped>
/* 任何额外的样式可以在这里添加 */
</style>
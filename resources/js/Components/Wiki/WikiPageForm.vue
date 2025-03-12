<template>
    <form @submit.prevent="submit" class="space-y-6">
        <!-- 标题输入 -->
        <div>
            <label class="block text-sm font-medium text-gray-700">页面标题</label>
            <input type="text" v-model="form.title"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                :class="{ 'border-red-500': form.errors.title }">
            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
        </div>

        <!-- 分类选择 -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">页面分类</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
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

        <!-- 内容编辑器 -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">页面内容</label>
            <div ref="editorContainer">
                <textarea ref="editor"></textarea>
            </div>
            <p v-if="form.errors.content" class="mt-1 text-sm text-red-600">{{ form.errors.content }}</p>
        </div>

        <!-- 表单操作 -->
        <div class="flex justify-end gap-4">
            <Link :href="route('wiki.index')"
                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition duration-150 ease-in-out">
            取消
            </Link>
            <button type="submit" :disabled="form.processing"
                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition duration-150 ease-in-out disabled:opacity-50">
                {{ isEditing ? '更新页面' : '创建页面' }}
            </button>
        </div>
    </form>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, computed, watch } from 'vue';
import { useEditor } from '@/plugins/tinymce';

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

const isEditing = computed(() => !!props.page);

const form = useForm({
    title: props.page?.title || '',
    content: props.page?.content || '',
    categories: props.page?.categories || [],
    last_check: props.page?.updated_at || ''
});

const editor = ref(null);
const editorContainer = ref(null);

const { init: editorConfig } = useEditor();

// 初始化编辑器
onMounted(() => {
    if (editorContainer.value) {
        const config = {
            ...editorConfig,
            selector: 'textarea',
            init_instance_callback: (ed) => {
                editor.value = ed;
                if (props.page?.content) {
                    editor.value.setContent(props.page.content);
                }
            },
            setup: (ed) => {
                ed.on('input change', () => {
                    form.content = ed.getContent();
                    emit('content-changed', ed.getContent());
                });
            }
        };

        window.tinymce?.init(config);
    }
});

// 清理函数
const cleanup = () => {
    if (editor.value) {
        editor.value.destroy();
        editor.value = null;
    }
};

// 组件卸载时清理
onUnmounted(() => {
    cleanup();
});

// 提交表单处理
const submit = () => {
    // 添加最后检查时间戳
    form.last_check = props.page?.updated_at || '';

    if (isEditing.value) {
        form.put(route('wiki.update', props.page.id), {
            onError: (errors) => {
                // 检查是否为冲突错误
                if (errors.conflict) {
                    // 可以在这里处理冲突
                    console.error("内容冲突:", errors.message);
                }
            }
        });
    } else {
        form.post(route('wiki.store'));
    }
};
</script>
<template>
    <!-- Tiptap 编辑器主容器，应用暗色主题样式 -->
    <div class="tiptap-editor dark">
        <!-- 菜单栏组件，只有当编辑器实例存在时才渲染，并传递编辑器实例和可编辑状态 -->
        <menu-bar v-if="editor" :editor="editor" :is-editable="editable" @toggle-edit="$emit('toggle-edit')" />

        <!-- 编辑器内容区域，当编辑器实例存在时才渲染，应用 Prose 样式并根据可编辑状态添加禁用样式 -->
        <editor-content v-if="editor" :editor="editor" class="editor-content prose max-w-none dark:prose-invert"
            :class="{ 'editor-disabled': !editable }" />

        <!-- 自动保存状态栏，仅当自动保存功能启用时显示 -->
        <div v-if="autosaveEnabled"
            class="autosave-status-bar p-2 text-xs text-gray-500 border-t border-gray-700 text-right">
            <!-- 显示自动保存状态信息（如“正在保存...”或“已保存”） -->
            <span v-if="autosaveStatus" :class="autosaveStatusClass" class="ml-2 flex items-center justify-end">
                <!-- 自动保存状态图标，根据类型判断是否旋转 -->
                <font-awesome-icon :icon="autosaveStatusIcon" :spin="autosaveStatus.type === 'pending'" class="mr-1" />
                {{ autosaveStatus.message }}
            </span>
            <!-- 当没有自动保存状态信息时，显示默认的“草稿未更改”提示 -->
            <span v-else class="ml-2 text-gray-500 italic">草稿未更改</span>
        </div>
    </div>
</template>

<script setup>
// 引入 Vue 核心功能和组合式 API
import { ref, watch, onMounted, onBeforeUnmount, computed, defineEmits, defineProps, nextTick } from 'vue';
// 引入 Tiptap Vue 集成的主函数和内容组件
import { useEditor, EditorContent } from '@tiptap/vue-3';
// 引入 Tiptap 各种功能扩展
import StarterKit from '@tiptap/starter-kit'; // 基础工具包
import Image from '@tiptap/extension-image'; // 图片处理
import Link from '@tiptap/extension-link'; // 链接处理
import Placeholder from '@tiptap/extension-placeholder'; // 占位符
import Typography from '@tiptap/extension-typography'; // 排版优化
import CharacterCount from '@tiptap/extension-character-count'; // 字符计数
import Table from '@tiptap/extension-table'; // 表格
import TableRow from '@tiptap/extension-table-row'; // 表格行
import TableCell from '@tiptap/extension-table-cell'; // 表格单元格
import TableHeader from '@tiptap/extension-table-header'; // 表格头
import Underline from '@tiptap/extension-underline'; // 下划线
import TextAlign from '@tiptap/extension-text-align'; // 文本对齐
import Highlight from '@tiptap/extension-highlight'; // 高亮
import CodeBlockLowlight from '@tiptap/extension-code-block-lowlight'; // 代码块高亮
import { lowlight } from 'lowlight/lib/core'; // 代码高亮核心库
// 引入自定义的编辑器菜单栏组件
import MenuBar from './EditorMenuBar.vue';
// 引入 HTTP 客户端
import axios from 'axios';
// 引入 Lodash 的防抖函数
import { debounce } from 'lodash';
// 引入自定义的日期时间格式化工具
import { formatDateTime } from '@/utils/formatters';
// 引入 highlight.js 支持的编程语言模块
import javascript from 'highlight.js/lib/languages/javascript';
import css from 'highlight.js/lib/languages/css';
import php from 'highlight.js/lib/languages/php';
import html from 'highlight.js/lib/languages/xml'; // XML 通常包含 HTML
import python from 'highlight.js/lib/languages/python';
import json from 'highlight.js/lib/languages/json';

// 向 lowlight 注册需要支持的代码语言，以便代码块能够正确高亮
lowlight.registerLanguage('javascript', javascript);
lowlight.registerLanguage('js', javascript); // JavaScript 的别名
lowlight.registerLanguage('css', css);
lowlight.registerLanguage('php', php);
lowlight.registerLanguage('html', html);
lowlight.registerLanguage('python', python);
lowlight.registerLanguage('json', json);

// 定义组件接收的属性 (props)
const props = defineProps({
    // 双向绑定编辑器内容的 v-model，默认为空字符串
    modelValue: { type: String, default: '' },
    // 编辑器为空时的占位符文本
    placeholder: { type: String, default: '开始编辑内容...' },
    // 控制编辑器是否可编辑
    editable: { type: Boolean, default: true },
    // 是否启用自动保存功能
    autosave: { type: Boolean, default: false },
    // 关联的页面 ID，用于自动保存的 API 请求，默认为空
    pageId: { type: Number, default: null },
    // 自动保存的时间间隔（毫秒），默认为 30 秒
    autosaveInterval: { type: Number, default: 30000 }
});

// 定义组件可以触发的事件 (emits)
const emit = defineEmits(['update:modelValue', 'saved', 'error', 'statusUpdate', 'toggle-edit']);

// 存储上一次成功保存到服务器的内容，用于判断是否有未保存更改
const lastSavedContent = ref(props.modelValue);
// 标记是否存在未保存的更改
const hasUnsavedChanges = ref(false);
// 存储自动保存的状态信息（例如：“正在保存...”，“保存成功！”）
const autosaveStatus = ref(null);

// 用于防抖处理的定时器 ID
let debounceTimer = null;
// 用于定时自动保存的定时器 ID
let autosaveTimer = null;

// 初始化 Tiptap 编辑器实例
const editor = useEditor({
    // 设置编辑器初始内容
    content: props.modelValue,
    // 配置 Tiptap 扩展
    extensions: [
        // 基础工具包：配置标题级别，并禁用其自带的代码块（因为我们使用 lowlight）
        StarterKit.configure({ heading: { levels: [1, 2, 3, 4, 5, 6] }, codeBlock: false }),
        // 图片扩展：允许 base64 编码图片，设置为内联，并应用 Tailwind CSS 样式
        Image.configure({ allowBase64: true, inline: true, HTMLAttributes: { class: 'max-w-full h-auto rounded' } }),
        // 链接扩展：配置点击不立即打开，并应用 Tailwind CSS 样式
        Link.configure({ openOnClick: false, HTMLAttributes: { class: 'text-blue-400 underline hover:text-blue-300' } }),
        // 占位符扩展：显示 `placeholder` 文本
        Placeholder.configure({ placeholder: props.placeholder }),
        // 表格相关扩展：支持表格功能和列宽调整
        Table.configure({ resizable: true }), TableRow, TableCell, TableHeader,
        // 排版扩展：提供更好的默认排版样式
        Typography,
        // 下划线扩展
        Underline,
        // 文本对齐扩展：支持对标题和段落进行文本对齐
        TextAlign.configure({ types: ['heading', 'paragraph'] }),
        // 高亮扩展：配置高亮文本的 Tailwind CSS 样式
        Highlight.configure({ HTMLAttributes: { class: 'bg-yellow-300/30 dark:bg-yellow-700/40 px-1 rounded' } }),
        // 代码块高亮扩展：集成 lowlight 进行代码高亮
        CodeBlockLowlight.configure({ lowlight }),
        // 字符计数扩展：限制内容字符数
        CharacterCount.configure({ limit: 50000 }),
    ],
    // 设置编辑器是否可编辑，直接由 `editable` prop 控制
    editable: props.editable,
    // 设置编辑器初始聚焦位置：如果可编辑，则聚焦到内容末尾
    autofocus: props.editable ? 'end' : false,
    // 当编辑器内容更新时触发的回调函数
    onUpdate: ({ editor }) => {
        const newHtml = editor.getHTML(); // 获取当前编辑器内容的 HTML 字符串
        emit('update:modelValue', newHtml); // 通过 v-model 更新父组件数据
        if (lastSavedContent.value !== newHtml) {
            hasUnsavedChanges.value = true; // 如果内容发生变化，标记为有未保存更改
        }
        debouncedSaveDraft(newHtml); // 调用防抖后的保存草稿函数，避免频繁保存
    },
    // 当编辑器实例创建完成时触发的回调函数
    onCreate: ({ editor }) => {
        editor.setEditable(props.editable); // 确保编辑器初始的可编辑状态正确设置
        lastSavedContent.value = editor.getHTML(); // 初始化 `lastSavedContent` 为当前内容
    }
});

// 侦听 `modelValue` prop 的变化，实现外部内容到编辑器的同步
watch(() => props.modelValue, (newValue) => {
    // 只有当编辑器当前内容与新的 `modelValue` 不同时才更新，防止不必要的渲染和循环
    if (editor.value && editor.value.getHTML() !== newValue) {
        editor.value.commands.setContent(newValue, false); // 更新编辑器内容，`false` 表示不触发 `onUpdate` 事件
        hasUnsavedChanges.value = false; // 外部内容同步后，清除未保存更改标志
        lastSavedContent.value = newValue; // 更新 `lastSavedContent`
    }
});

// 侦听 `editable` prop 的变化，控制编辑器可编辑状态和自动保存定时器
watch(() => props.editable, (newValue) => {
    if (editor.value) {
        editor.value.setEditable(newValue); // 设置编辑器为可编辑或只读状态
        if (newValue && autosaveEnabled.value) {
            setupAutosaveTimer(); // 如果变为可编辑且自动保存启用，则启动自动保存定时器
        } else {
            clearAutosaveTimer(); // 如果变为不可编辑，则停止所有自动保存定时器
            // 如果变为不可编辑且存在未保存更改，则尝试立即保存一次草稿
            if (!newValue && autosaveEnabled.value && hasUnsavedChanges.value) {
                if (debounceTimer) clearTimeout(debounceTimer); // 取消任何待执行的防抖保存
                saveDraft(editor.value.getHTML() || props.modelValue); // 立即保存当前草稿
            }
        }
    }
}, { immediate: true }); // 立即执行一次，确保组件初始化时状态正确

// 计算属性：判断自动保存功能是否真正启用
// 只有当 `autosave` prop 为 true，`pageId` 有值，并且编辑器当前处于可编辑状态时才启用
const autosaveEnabled = computed(() => props.autosave && props.pageId !== null && props.editable);

// 组件挂载 (mounted) 生命周期钩子
onMounted(() => {
    // 如果 `autosaveEnabled` 为真，设置自动保存定时器
    if (autosaveEnabled.value) {
        setupAutosaveTimer();
    }
    // 添加网络状态监听，当从离线恢复到在线时，尝试保存草稿
    window.addEventListener('online', handleOnline);
    // 如果初始 `modelValue` 为空，确保编辑器内容也清空
    if (editor.value && !props.modelValue) {
        editor.value.commands.setContent('', false);
    }
});

// 组件卸载 (beforeUnmount) 生命周期钩子
onBeforeUnmount(() => {
    clearAutosaveTimer(); // 清除所有自动保存相关的定时器

    // 在组件卸载前，如果自动保存启用且有未保存更改，尝试通过 `navigator.sendBeacon` 发送草稿
    // `sendBeacon` 旨在在页面卸载时可靠地发送少量数据，不会阻塞页面关闭
    if (autosaveEnabled.value && hasUnsavedChanges.value && editor.value) {
        if (debounceTimer) clearTimeout(debounceTimer); // 取消任何待执行的防抖保存

        if (navigator.sendBeacon) { // 检查浏览器是否支持 `sendBeacon`
            const url = route('wiki.save-draft', props.pageId); // 获取保存草稿的路由
            const formData = new FormData(); // 创建 FormData 对象
            formData.append('content', editor.value.getHTML()); // 添加编辑器内容
            // 获取 CSRF token 并添加到 FormData，确保请求安全
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrfToken) {
                formData.append('_token', csrfToken);
            }
            try {
                navigator.sendBeacon(url, formData); // 发送请求
            } catch (e) {
                console.error("在卸载时 sendBeacon 失败:", e);
            }
        } else {
            console.warn("浏览器不支持 navigator.sendBeacon。草稿可能无法在卸载时保存。");
        }
    }
    // 销毁 Tiptap 编辑器实例，释放资源
    if (editor.value) {
        editor.value.destroy();
    }
    // 移除网络状态监听器
    window.removeEventListener('online', handleOnline);
});

// 防抖处理的保存草稿函数，在用户停止输入后 2 秒才执行 `saveDraft`
const debouncedSaveDraft = debounce(async (content) => {
    await saveDraft(content);
}, 2000);

// 实际的保存草稿逻辑
const saveDraft = async (content) => {
    // 如果自动保存未启用，或者编辑器实例不存在，或者内容与上次保存的相同，则不执行保存
    if (!autosaveEnabled.value || !editor.value || content === lastSavedContent.value) {
        return;
    }

    autosaveStatus.value = { type: 'pending', message: '正在自动保存草稿...' }; // 更新状态为“进行中”
    emit('statusUpdate', { ...autosaveStatus.value }); // 触发状态更新事件

    try {
        // 向后端发送保存草稿的 POST 请求
        const response = await axios.post(route('wiki.save-draft', props.pageId), { content });
        lastSavedContent.value = content; // 更新 `lastSavedContent` 为当前已保存的内容
        hasUnsavedChanges.value = false; // 清除未保存更改标志

        // 格式化保存时间，并更新状态信息
        const savedAtDate = response.data.saved_at ? new Date(response.data.saved_at) : new Date();
        autosaveStatus.value = {
            type: 'success',
            message: `草稿已于 ${formatDateTime(savedAtDate)} 保存`,
            saved_at: response.data.saved_at
        };
        emit('saved', { ...autosaveStatus.value }); // 触发保存成功事件
        emit('statusUpdate', { ...autosaveStatus.value }); // 再次触发状态更新事件

        // 成功状态信息显示 5 秒后自动消失
        setTimeout(() => {
            if (autosaveStatus.value?.type === 'success') {
                autosaveStatus.value = null; // 清除状态信息
                emit('statusUpdate', null); // 通知父组件状态已清除
            }
        }, 5000);

    } catch (error) {
        // 处理保存草稿失败的情况
        const errorMessage = error.response?.data?.message || error.message || '网络错误，无法保存';
        autosaveStatus.value = {
            type: 'error',
            message: `草稿保存失败: ${errorMessage}`
        };
        emit('statusUpdate', { ...autosaveStatus.value }); // 触发状态更新事件
        emit('error', error); // 触发错误事件
    }
};

// 设置定时自动保存的定时器
const setupAutosaveTimer = () => {
    clearAutosaveTimer(); // 先清除任何现有的定时器，防止重复设置
    if (autosaveEnabled.value) {
        autosaveTimer = setInterval(() => {
            // 每隔 `autosaveInterval` 时间检查是否有未保存更改，如果有则执行保存
            if (hasUnsavedChanges.value && editor.value) {
                if (debounceTimer) clearTimeout(debounceTimer); // 如果定时器触发，取消防抖，立即保存
                saveDraft(editor.value.getHTML() || props.modelValue);
            }
        }, props.autosaveInterval);
    }
};

// 清除所有自动保存相关的定时器和防抖器
const clearAutosaveTimer = () => {
    if (autosaveTimer) {
        clearInterval(autosaveTimer);
        autosaveTimer = null;
    }
    if (debounceTimer) {
        clearTimeout(debounceTimer);
        debounceTimer = null;
    }
};

// 处理浏览器从离线状态恢复到在线状态的事件
const handleOnline = () => {
    // 如果自动保存启用且有未保存更改，则立即尝试保存草稿
    if (autosaveEnabled.value && hasUnsavedChanges.value && editor.value) {
        if (debounceTimer) clearTimeout(debounceTimer); // 取消防抖，确保立即执行
        saveDraft(editor.value.getHTML() || props.modelValue);
    }
};

// 计算属性：根据 `autosaveStatus` 的类型返回相应的 CSS 类名，用于样式化状态文本
const autosaveStatusClass = computed(() => {
    if (!autosaveStatus.value) return 'text-gray-500 italic'; // 默认样式
    switch (autosaveStatus.value.type) {
        case 'success': return 'text-green-400';
        case 'error': return 'text-red-400';
        case 'pending': return 'text-blue-400';
        default: return 'text-gray-500';
    }
});

// 计算属性：根据 `autosaveStatus` 的类型返回相应的 Font Awesome 图标
const autosaveStatusIcon = computed(() => {
    if (!autosaveStatus.value) return ['fas', 'circle-info']; // 默认图标
    switch (autosaveStatus.value.type) {
        case 'success': return ['fas', 'check-circle'];
        case 'error': return ['fas', 'exclamation-circle'];
        case 'pending': return ['fas', 'spinner'];
        default: return ['fas', 'info-circle'];
    }
});

// 暴露编辑器实例给父组件，允许父组件直接访问编辑器的方法和属性
defineExpose({ editor });
</script>

<style scoped>
/* Tiptap 编辑器主容器样式 */
.tiptap-editor {
    border: 1px solid #4b5563;
    /* 边框样式 */
    border-radius: 0.5rem;
    /* 圆角 */
    overflow: hidden;
    /* 隐藏溢出内容 */
    display: flex;
    flex-direction: column;
    /* 垂直布局 */
    background-color: #1f2937;
    /* 背景色 */
    color: #d1d5db;
    /* 默认文本颜色 */
}

/* 编辑器内容区域样式 */
.editor-content {
    padding: 1rem;
    min-height: 300px;
    /* 最小高度 */
    overflow-y: auto;
    /* 垂直滚动条 */
    max-height: 70vh;
    /* 最大高度 */
    flex-grow: 1;
    /* 允许内容区域填充剩余空间 */
    /* 应用 Tailwind Typography 插件的样式，并反转为暗色模式 */
    @apply prose max-w-none prose-invert;
}

/* ProseMirror 核心编辑器样式 */
.ProseMirror {
    background: transparent;
    /* 背景透明，以显示父容器的背景色 */
    outline: none;
    /* 移除聚焦时的默认轮廓 */
    color: inherit;
    /* 继承父元素的文本颜色 */
}

/* 编辑器禁用状态下的样式 */
.editor-disabled .ProseMirror {
    background-color: #374151;
    /* 禁用时背景色变深 */
    cursor: not-allowed;
    /* 鼠标显示禁用 */
    opacity: 0.7;
    /* 降低透明度 */
}

/* 禁用状态下菜单栏按钮的样式 */
.editor-disabled .editor-menu-bar button {
    opacity: 0.5;
    /* 降低透明度 */
    cursor: not-allowed;
    /* 鼠标显示禁用 */
}

/* 自动保存状态栏样式 */
.autosave-status-bar {
    border-top: 1px solid #4b5563;
    /* 顶部边框 */
    background-color: #111827;
    /* 背景色 */
    color: #9ca3af;
    /* 基础文本颜色 */
}

/* 自动保存状态文本的颜色过渡效果 */
.autosave-status-bar span {
    transition: color 0.3s ease;
}

/* 自动保存状态栏中默认灰色文本的颜色调整 */
.autosave-status-bar .text-gray-500 {
    color: #6b7280;
}

/* 编辑器容器聚焦时的样式 */
.tiptap-editor:focus-within {
    border-color: #3b82f6;
    /* 边框颜色变为蓝色 */
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
    /* 添加蓝色阴影 */
}

/* 针对 ProseMirror 内部特定元素的样式覆盖，确保一致性 */
.ProseMirror p {
    margin-bottom: 0.75em;
}

.ProseMirror h1,
.ProseMirror h2,
.ProseMirror h3,
.ProseMirror h4,
.ProseMirror h5,
.ProseMirror h6 {
    margin-top: 1.5em;
    margin-bottom: 0.5em;
}

.ProseMirror table {
    border-collapse: collapse;
    width: 100%;
    margin: 1em 0;
}

.ProseMirror th,
.ProseMirror td {
    border: 1px solid #4b5563;
    padding: 0.5em;
}

.ProseMirror th {
    background-color: #374151;
}

.ProseMirror tr:nth-child(even) {
    background-color: rgba(55, 65, 81, 0.5);
}
</style>
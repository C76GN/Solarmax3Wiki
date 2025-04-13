<template>
    <!-- 修改主容器背景和边框颜色 -->
    <div
        class="editor-menu-bar bg-gray-800 p-2 flex flex-wrap gap-1 rounded-t-lg border-b border-gray-700 items-center">
        <div v-if="isEditable" class="menu-container flex-grow flex flex-wrap items-center gap-1">
            <!-- 移动端分类按钮样式调整 -->
            <div v-if="isMobile"
                class="mobile-menu-categories w-full flex justify-center mb-2 border-b border-gray-700 pb-2">
                <button v-for="(category, index) in menuCategories" :key="index" @click="activeCategoryIndex = index"
                    :class="['px-2 py-1 text-xs rounded-md mx-1 transition-colors',
                        activeCategoryIndex === index
                            ? 'bg-blue-600 text-white' // 激活状态深色背景
                            : 'bg-gray-600 text-gray-300 hover:bg-gray-500' // 非激活状态深色背景
                    ]">
                    {{ category }}
                </button>
            </div>

            <!-- 工具栏按钮组样式调整 (重复应用于所有 v-if 的 div.menu-group) -->
            <div v-if="!isMobile || activeCategoryIndex === 0" class="menu-group">
                <button type="button" @click="editor.chain().focus().toggleBold().run()"
                    :disabled="!editor.can().chain().focus().toggleBold().run()"
                    :class="{ 'is-active': editor.isActive('bold') }" class="menu-button" title="粗体 (Ctrl+B)">
                    <font-awesome-icon :icon="['fas', 'bold']" /> </button>
                <button type="button" @click="editor.chain().focus().toggleItalic().run()"
                    :disabled="!editor.can().chain().focus().toggleItalic().run()"
                    :class="{ 'is-active': editor.isActive('italic') }" class="menu-button" title="斜体 (Ctrl+I)">
                    <font-awesome-icon :icon="['fas', 'italic']" /> </button>
                <button type="button" @click="editor.chain().focus().toggleUnderline().run()"
                    :disabled="!editor.can().chain().focus().toggleUnderline().run()"
                    :class="{ 'is-active': editor.isActive('underline') }" class="menu-button" title="下划线 (Ctrl+U)">
                    <font-awesome-icon :icon="['fas', 'underline']" /> </button>
                <button type="button" @click="editor.chain().focus().toggleStrike().run()"
                    :disabled="!editor.can().chain().focus().toggleStrike().run()"
                    :class="{ 'is-active': editor.isActive('strike') }" class="menu-button" title="删除线">
                    <font-awesome-icon :icon="['fas', 'strikethrough']" /> </button>
                <button type="button" @click="editor.chain().focus().toggleHighlight().run()"
                    :disabled="!editor.can().chain().focus().toggleHighlight().run()"
                    :class="{ 'is-active': editor.isActive('highlight') }" class="menu-button" title="高亮">
                    <font-awesome-icon :icon="['fas', 'highlighter']" /> </button>
                <button type="button" @click="editor.chain().focus().toggleCode().run()"
                    :disabled="!editor.can().chain().focus().toggleCode().run()"
                    :class="{ 'is-active': editor.isActive('code') }" class="menu-button" title="内联代码 (Ctrl+E)">
                    <font-awesome-icon :icon="['fas', 'code']" /> </button>
            </div>

            <!-- 调整标题和对齐按钮组 -->
            <div v-if="!isMobile || activeCategoryIndex === 1" class="menu-group">
                <button type="button" @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
                    :disabled="!editor.can().chain().focus().toggleHeading({ level: 1 }).run()"
                    :class="{ 'is-active': editor.isActive('heading', { level: 1 }) }"
                    class="menu-button menu-button-heading" title="标题 1">H1</button>
                <button type="button" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                    :disabled="!editor.can().chain().focus().toggleHeading({ level: 2 }).run()"
                    :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }"
                    class="menu-button menu-button-heading" title="标题 2">H2</button>
                <button type="button" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                    :disabled="!editor.can().chain().focus().toggleHeading({ level: 3 }).run()"
                    :class="{ 'is-active': editor.isActive('heading', { level: 3 }) }"
                    class="menu-button menu-button-heading" title="标题 3">H3</button>
                <!-- 对齐按钮 -->
                <button type="button" @click="editor.chain().focus().setTextAlign('left').run()"
                    :disabled="!editor.can().chain().focus().setTextAlign('left').run()"
                    :class="{ 'is-active': editor.isActive({ textAlign: 'left' }) }" class="menu-button"
                    title="左对齐"><font-awesome-icon :icon="['fas', 'align-left']" /> </button>
                <button type="button" @click="editor.chain().focus().setTextAlign('center').run()"
                    :disabled="!editor.can().chain().focus().setTextAlign('center').run()"
                    :class="{ 'is-active': editor.isActive({ textAlign: 'center' }) }" class="menu-button"
                    title="居中对齐"><font-awesome-icon :icon="['fas', 'align-center']" /> </button>
                <button type="button" @click="editor.chain().focus().setTextAlign('right').run()"
                    :disabled="!editor.can().chain().focus().setTextAlign('right').run()"
                    :class="{ 'is-active': editor.isActive({ textAlign: 'right' }) }" class="menu-button"
                    title="右对齐"><font-awesome-icon :icon="['fas', 'align-right']" /> </button>
                <button type="button" @click="editor.chain().focus().setTextAlign('justify').run()"
                    :disabled="!editor.can().chain().focus().setTextAlign('justify').run()"
                    :class="{ 'is-active': editor.isActive({ textAlign: 'justify' }) }" class="menu-button"
                    title="两端对齐"> <font-awesome-icon :icon="['fas', 'align-justify']" /> </button>
            </div>

            <!-- 调整列表、引用和表格按钮组 -->
            <div v-if="!isMobile || activeCategoryIndex === 2" class="menu-group">
                <button type="button" @click="editor.chain().focus().toggleBulletList().run()"
                    :disabled="!editor.can().chain().focus().toggleBulletList().run()"
                    :class="{ 'is-active': editor.isActive('bulletList') }" class="menu-button" title="无序列表">
                    <font-awesome-icon :icon="['fas', 'list-ul']" /> </button>
                <button type="button" @click="editor.chain().focus().toggleOrderedList().run()"
                    :disabled="!editor.can().chain().focus().toggleOrderedList().run()"
                    :class="{ 'is-active': editor.isActive('orderedList') }" class="menu-button" title="有序列表">
                    <font-awesome-icon :icon="['fas', 'list-ol']" /> </button>
                <button type="button" @click="editor.chain().focus().toggleBlockquote().run()"
                    :disabled="!editor.can().chain().focus().toggleBlockquote().run()"
                    :class="{ 'is-active': editor.isActive('blockquote') }" class="menu-button" title="引用">
                    <font-awesome-icon :icon="['fas', 'quote-left']" /> </button>
                <button type="button" @click="openCodeLangModal" :class="{ 'is-active': editor.isActive('codeBlock') }"
                    class="menu-button" title="代码块"> <font-awesome-icon :icon="['fas', 'file-code']" /> </button>
                <button type="button" @click="insertTable" class="menu-button" title="插入表格"> <font-awesome-icon
                        :icon="['fas', 'table']" /> </button>
                <!-- 表格操作图标 -->
                <template v-if="editor.isActive('table')">
                    <button type="button" @click="editor.chain().focus().addColumnBefore().run()"
                        :disabled="!editor.can().addColumnBefore()" class="menu-button" title="左侧插入列"><font-awesome-icon
                            :icon="['fas', 'table-columns']" /><font-awesome-icon :icon="['fas', 'arrow-left']"
                            class="ml-1 text-xs" /></button>
                    <button type="button" @click="editor.chain().focus().addColumnAfter().run()"
                        :disabled="!editor.can().addColumnAfter()" class="menu-button" title="右侧插入列"><font-awesome-icon
                            :icon="['fas', 'table-columns']" /><font-awesome-icon :icon="['fas', 'arrow-right']"
                            class="ml-1 text-xs" /></button>
                    <button type="button" @click="editor.chain().focus().deleteColumn().run()"
                        :disabled="!editor.can().deleteColumn()" class="menu-button" title="删除列"><font-awesome-icon
                            :icon="['fas', 'eraser']" /> <font-awesome-icon :icon="['fas', 'table-columns']"
                            class="ml-1 text-xs" /></button>
                    <!-- 使用 faGripLines 替换 faTableRows -->
                    <button type="button" @click="editor.chain().focus().addRowBefore().run()"
                        :disabled="!editor.can().addRowBefore()" class="menu-button" title="上方插入行"><font-awesome-icon
                            :icon="['fas', 'grip-lines']" /><font-awesome-icon :icon="['fas', 'arrow-up']"
                            class="ml-1 text-xs" /></button>
                    <button type="button" @click="editor.chain().focus().addRowAfter().run()"
                        :disabled="!editor.can().addRowAfter()" class="menu-button" title="下方插入行"><font-awesome-icon
                            :icon="['fas', 'grip-lines']" /><font-awesome-icon :icon="['fas', 'arrow-down']"
                            class="ml-1 text-xs" /></button>
                    <button type="button" @click="editor.chain().focus().deleteRow().run()"
                        :disabled="!editor.can().deleteRow()" class="menu-button" title="删除行"><font-awesome-icon
                            :icon="['fas', 'eraser']" /><font-awesome-icon :icon="['fas', 'grip-lines']"
                            class="ml-1 text-xs" /></button>
                    <!-- 结束替换 -->
                    <button type="button" @click="editor.chain().focus().deleteTable().run()"
                        :disabled="!editor.can().deleteTable()" class="menu-button" title="删除表格"><font-awesome-icon
                            :icon="['fas', 'trash-alt']" /></button>
                </template>
            </div>

            <!-- 调整链接、图片、撤销重做按钮组 -->
            <div v-if="!isMobile || activeCategoryIndex === 3" class="menu-group">
                <button type="button" @click="setLink" :disabled="editor.isActive('codeBlock')"
                    :class="{ 'is-active': editor.isActive('link') }" class="menu-button" title="链接"> <font-awesome-icon
                        :icon="['fas', 'link']" /> </button>
                <button type="button" @click="openImageUpload" class="menu-button" title="图片"> <font-awesome-icon
                        :icon="['fas', 'image']" /> </button>
                <button type="button" @click="editor.chain().focus().setHorizontalRule().run()"
                    :disabled="!editor.can().setHorizontalRule()" class="menu-button" title="分隔线"> <font-awesome-icon
                        :icon="['fas', 'minus']" /> </button>
                <button type="button" @click="editor.chain().focus().undo().run()" :disabled="!editor.can().undo()"
                    class="menu-button" title="撤销 (Ctrl+Z)"> <font-awesome-icon :icon="['fas', 'undo']" /> </button>
                <button type="button" @click="editor.chain().focus().redo().run()" :disabled="!editor.can().redo()"
                    class="menu-button" title="重做 (Ctrl+Y)"> <font-awesome-icon :icon="['fas', 'redo']" /> </button>
            </div>
        </div>

        <!-- 调整预览/编辑切换按钮样式 -->
        <div class="ml-auto pl-2 border-l border-gray-600 flex-shrink-0">
            <button type="button" @click="$emit('toggle-edit')" class="menu-button"
                :title="isEditable ? '切换到预览模式' : '切换到编辑模式'">
                <font-awesome-icon :icon="['fas', isEditable ? 'eye' : 'pen']" class="mr-1" />
                <span>{{ isEditable ? '预览' : '编辑' }}</span>
            </button>
        </div>

        <!-- 图片上传模态框样式调整 (背景和文字) -->
        <div v-if="showImageUpload"
            class="image-upload-modal fixed inset-0 bg-black bg-opacity-70 flex justify-center items-center z-50">
            <div class="image-upload-modal-content bg-gray-800 rounded-lg p-6 w-11/12 max-w-lg shadow-xl">
                <h3 class="text-lg font-medium mb-4 text-gray-100">插入图片</h3>
                <div class="mb-4">
                    <div class="flex items-center mb-2">
                        <input id="url-radio" type="radio" v-model="imageSource" value="url"
                            class="mr-2 h-4 w-4 text-blue-500 focus:ring-blue-500 border-gray-600 bg-gray-700">
                        <label for="url-radio" class="text-sm text-gray-300">图片URL</label>
                    </div>
                    <input v-if="imageSource === 'url'" v-model="imageUrl" type="text" placeholder="输入图片URL"
                        class="w-full p-2 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm bg-gray-700 text-gray-200 placeholder-gray-500">
                </div>
                <div class="mb-4">
                    <div class="flex items-center mb-2">
                        <input id="upload-radio" type="radio" v-model="imageSource" value="upload"
                            class="mr-2 h-4 w-4 text-blue-500 focus:ring-blue-500 border-gray-600 bg-gray-700">
                        <label for="upload-radio" class="text-sm text-gray-300">上传图片</label>
                    </div>
                    <input v-if="imageSource === 'upload'" type="file" @change="handleFileUpload" accept="image/*"
                        class="w-full p-2 border border-gray-600 rounded-md text-sm file:mr-4 file:py-1 file:px-2 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-800 file:text-blue-200 hover:file:bg-blue-700 bg-gray-700 text-gray-400">
                    <!-- 进度条样式调整 -->
                    <div v-if="uploadProgress > 0 && uploadProgress < 100" class="mt-2">
                        <div class="bg-gray-600 rounded-full h-2.5">
                            <div class="bg-blue-500 h-2.5 rounded-full" :style="{ width: uploadProgress + '%' }"></div>
                        </div>
                    </div>
                    <p v-if="uploadError" class="text-red-400 text-sm mt-1">{{ uploadError }}</p>
                    <div v-if="uploadedImageUrl" class="mt-2">
                        <p class="text-sm text-green-400 mb-1">图片上传成功!</p>
                        <img :src="uploadedImageUrl"
                            class="max-h-32 max-w-full rounded border border-gray-600 bg-gray-700" />
                    </div>
                </div>
                <!-- 图片设置样式调整 -->
                <div class="mb-4">
                    <div class="flex items-center mb-2">
                        <label class="text-sm font-medium text-gray-300">图片设置</label>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs text-gray-400 block mb-1">替代文本</label>
                            <input v-model="imageAlt" type="text" placeholder="图片描述（可选）"
                                class="w-full p-2 text-sm border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-700 text-gray-200 placeholder-gray-500">
                        </div>
                        <div>
                            <label class="text-xs text-gray-400 block mb-1">标题</label>
                            <input v-model="imageTitle" type="text" placeholder="图片标题（可选）"
                                class="w-full p-2 text-sm border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-700 text-gray-200 placeholder-gray-500">
                        </div>
                    </div>
                </div>
                <!-- 按钮样式调整 -->
                <div class="flex justify-end space-x-2">
                    <button @click="cancelImageUpload"
                        class="px-4 py-2 bg-gray-600 text-gray-200 rounded-md hover:bg-gray-500 text-sm transition">取消</button>
                    <button @click="insertImage"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-500 text-sm transition disabled:opacity-50"
                        :disabled="!(imageUrl || uploadedImageUrl)">插入</button>
                </div>
            </div>
        </div>

        <!-- 代码块语言选择模态框样式调整 -->
        <div v-if="showCodeLangModal"
            class="image-upload-modal fixed inset-0 bg-black bg-opacity-70 flex justify-center items-center z-50">
            <div class="image-upload-modal-content bg-gray-800 rounded-lg p-6 w-11/12 max-w-md shadow-xl">
                <h3 class="text-lg font-medium mb-4 text-gray-100">选择代码语言</h3>
                <div class="mb-4">
                    <select v-model="selectedCodeLang"
                        class="w-full p-2 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm bg-gray-700 text-gray-200">
                        <option value="">纯文本 (Plain Text)</option>
                        <option value="javascript">JavaScript</option>
                        <option value="html">HTML</option>
                        <option value="css">CSS</option>
                        <option value="php">PHP</option>
                        <option value="python">Python</option>
                        <option value="json">JSON</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-2">
                    <button @click="cancelCodeLangSelection"
                        class="px-4 py-2 bg-gray-600 text-gray-200 rounded-md hover:bg-gray-500 text-sm transition">取消</button>
                    <button @click="insertCodeBlock"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-500 text-sm transition">插入</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
// Script部分保持不变
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    editor: { type: Object, required: true },
    isEditable: { type: Boolean, default: true }
});
const emit = defineEmits(['toggle-edit']);

const isMobile = ref(false);
const activeCategoryIndex = ref(0);
const menuCategories = ['格式', '段落', '插入', '工具'];

// 图片上传状态
const showImageUpload = ref(false);
const imageSource = ref('url'); // 'url' or 'upload'
const imageUrl = ref('');
const imageAlt = ref('');
const imageTitle = ref('');
const uploadedImageUrl = ref('');
const uploadProgress = ref(0);
const uploadError = ref('');

// 代码块语言选择状态
const showCodeLangModal = ref(false);
const selectedCodeLang = ref('');

// --- Methods ---

// 检测设备类型
const checkDeviceType = () => { isMobile.value = window.innerWidth < 768; };

onMounted(() => {
    checkDeviceType();
    window.addEventListener('resize', checkDeviceType);
});

onUnmounted(() => {
    window.removeEventListener('resize', checkDeviceType);
});

// 设置链接
const setLink = () => {
    if (!props.isEditable) return;
    const previousUrl = props.editor.getAttributes('link').href;
    const url = window.prompt('输入链接URL', previousUrl);

    if (url === null) return; // 用户取消

    if (url === '') {
        props.editor.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }
    // 简单的 URL 校验和补全
    let finalUrl = url;
    if (!url.includes('://') && !url.startsWith('/') && !url.startsWith('#') && !url.startsWith('mailto:') && !url.startsWith('tel:')) {
        finalUrl = 'https://' + url;
    }

    props.editor.chain().focus().extendMarkRange('link').setLink({ href: finalUrl }).run();
};

// 打开图片上传模态框
const openImageUpload = () => {
    if (!props.isEditable) return;
    showImageUpload.value = true;
    // 重置状态
    imageSource.value = 'url';
    imageUrl.value = '';
    imageAlt.value = '';
    imageTitle.value = '';
    uploadedImageUrl.value = '';
    uploadProgress.value = 0;
    uploadError.value = '';
};

// 取消图片上传
const cancelImageUpload = () => {
    showImageUpload.value = false;
};

// 处理文件上传
const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
        uploadError.value = '请选择图片文件';
        return;
    }

    const formData = new FormData();
    formData.append('image', file);
    uploadProgress.value = 0;
    uploadError.value = '';

    // 获取 CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    axios.post('/api/upload-image', formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
            'X-CSRF-TOKEN': csrfToken // 添加 CSRF token
        },
        onUploadProgress: (progressEvent) => {
            const total = progressEvent.total;
            if (total) {
                uploadProgress.value = Math.round((progressEvent.loaded * 100) / total);
            } else {
                // 不确定总大小时给一个中间状态
                uploadProgress.value = 50;
            }
        }
    }).then(response => {
        if (response.data && response.data.url) {
            uploadedImageUrl.value = response.data.url;
            uploadProgress.value = 100; // 标记完成
        } else {
            uploadError.value = '上传成功，但未收到有效的图片URL';
            uploadProgress.value = 0;
        }

    }).catch(error => {
        console.error("Image upload error:", error.response?.data || error.message);
        uploadError.value = '上传失败：' + (error.response?.data?.message || error.response?.data?.error || '网络或服务器错误');
        uploadProgress.value = 0;
    });
};


// 插入图片
const insertImage = () => {
    const url = imageSource.value === 'url' ? imageUrl.value : uploadedImageUrl.value;
    if (url && props.isEditable) {
        const attrs = { src: url };
        if (imageAlt.value) attrs.alt = imageAlt.value;
        if (imageTitle.value) attrs.title = imageTitle.value;
        props.editor.chain().focus().setImage(attrs).run();
        showImageUpload.value = false;
    }
};

// 插入表格
const insertTable = () => {
    if (props.isEditable) {
        props.editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
    }
};

// 打开代码块语言选择模态框
const openCodeLangModal = () => {
    if (!props.isEditable) return;
    showCodeLangModal.value = true;
    // 获取当前代码块的语言，如果光标不在代码块内，则默认为空
    selectedCodeLang.value = props.editor.getAttributes('codeBlock').language || '';
};

// 取消代码块语言选择
const cancelCodeLangSelection = () => {
    showCodeLangModal.value = false;
};

// 插入代码块
const insertCodeBlock = () => {
    if (props.isEditable) {
        // 使用选择的语言或默认（纯文本）创建或切换代码块
        props.editor.chain().focus().setCodeBlock({ language: selectedCodeLang.value }).run();
        showCodeLangModal.value = false;
    }
};

</script>

<style scoped>
/* 更新按钮样式 */
.menu-button {
    padding: 0.4rem 0.6rem;
    border-radius: 0.375rem;
    /* rounded-md */
    background: transparent;
    border: none;
    color: #d1d5db;
    /* text-gray-300 */
    cursor: pointer;
    font-size: 0.875rem;
    /* text-sm */
    line-height: 1;
    display: inline-flex;
    align-items: center;
    transition: all 0.2s ease;
    margin: 0 1px;
}

.menu-button:hover:not(:disabled) {
    background-color: #374151;
    /* bg-gray-700 */
    color: #f9fafb;
    /* text-gray-100 */
}

.menu-button.is-active {
    background-color: #3b82f6;
    /* bg-blue-600 */
    color: #ffffff;
    /* text-white */
}

.menu-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* 保持标题按钮样式 */
.menu-button-heading {
    font-weight: bold;
    min-width: 30px;
    justify-content: center;
}

/* 分组边框调整 */
.menu-group {
    display: flex;
    gap: 0.25rem;
    padding: 0 0.5rem;
    align-items: center;
    border-right: 1px solid #4b5563;
    /* border-gray-600 */
}

.menu-group:last-of-type {
    border-right: none;
}

/* 移动端样式调整 */
@media (max-width: 768px) {
    .menu-container {
        flex-direction: column;
        align-items: stretch;
        gap: 0.5rem;
        width: 100%;
    }

    .menu-group {
        border-right: none;
        padding: 0;
        justify-content: center;
        flex-wrap: wrap;
    }

    .editor-menu-bar {
        flex-direction: column;
    }

    .ml-auto {
        /* 预览/编辑按钮 */
        margin-left: 0;
        margin-top: 0.5rem;
    }
}

/* 模态框内容文字颜色调整 */
.image-upload-modal-content {
    color: #d1d5db;
    /* text-gray-300 */
}

.image-upload-modal-content h3 {
    color: #f9fafb;
    /* text-gray-100 */
}

.image-upload-modal-content label {
    color: #9ca3af;
    /* text-gray-400 */
}

/* 其他输入框在模板内已通过Tailwind类调整 */
</style>
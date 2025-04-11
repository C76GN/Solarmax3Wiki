<template>
    <div
        class="editor-menu-bar bg-gray-100 p-2 flex flex-wrap gap-1 rounded-t-lg border-b border-gray-300 items-center">
        <!-- 编辑功能区 -->
        <div v-if="isEditable" class="menu-container flex-grow flex flex-wrap items-center gap-1">
            <!-- 移动端分类 -->
            <div v-if="isMobile" class="mobile-menu-categories w-full flex justify-center mb-2 border-b pb-2">
                <button v-for="(category, index) in menuCategories" :key="index" @click="activeCategoryIndex = index"
                    :class="['px-2 py-1 text-xs rounded-md mx-1 transition-colors', activeCategoryIndex === index ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300']">
                    {{ category }}
                </button>
            </div>
            <!-- 文本格式组 -->
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
            <!-- 段落格式组 -->
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
            <!-- 插入/块级元素组 -->
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
                <!-- 修改：使用 openCodeLangModal 打开弹窗 -->
                <button type="button" @click="openCodeLangModal" :class="{ 'is-active': editor.isActive('codeBlock') }"
                    class="menu-button" title="代码块"> <font-awesome-icon :icon="['fas', 'file-code']" /> </button>

                <!-- 表格操作 -->
                <button type="button" @click="insertTable" class="menu-button" title="插入表格"> <font-awesome-icon
                        :icon="['fas', 'table']" /> </button>
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
                    <button type="button" @click="editor.chain().focus().addRowBefore().run()"
                        :disabled="!editor.can().addRowBefore()" class="menu-button" title="上方插入行"><font-awesome-icon
                            :icon="['fas', 'table-rows']" /><font-awesome-icon :icon="['fas', 'arrow-up']"
                            class="ml-1 text-xs" /></button>
                    <button type="button" @click="editor.chain().focus().addRowAfter().run()"
                        :disabled="!editor.can().addRowAfter()" class="menu-button" title="下方插入行"><font-awesome-icon
                            :icon="['fas', 'table-rows']" /><font-awesome-icon :icon="['fas', 'arrow-down']"
                            class="ml-1 text-xs" /></button>
                    <button type="button" @click="editor.chain().focus().deleteRow().run()"
                        :disabled="!editor.can().deleteRow()" class="menu-button" title="删除行"><font-awesome-icon
                            :icon="['fas', 'eraser']" /><font-awesome-icon :icon="['fas', 'table-rows']"
                            class="ml-1 text-xs" /></button>
                    <button type="button" @click="editor.chain().focus().deleteTable().run()"
                        :disabled="!editor.can().deleteTable()" class="menu-button" title="删除表格"><font-awesome-icon
                            :icon="['fas', 'trash-alt']" /></button>
                </template>
            </div>
            <!-- 工具组 -->
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

        <!-- 编辑/预览切换按钮 (在最右侧) -->
        <div class="ml-auto pl-2 border-l border-gray-300 flex-shrink-0">
            <button type="button" @click="$emit('toggle-edit')" class="menu-button"
                :title="isEditable ? '切换到预览模式' : '切换到编辑模式'">
                <font-awesome-icon :icon="['fas', isEditable ? 'eye' : 'pen']" class="mr-1" />
                <span>{{ isEditable ? '预览' : '编辑' }}</span>
            </button>
        </div>

        <!-- 图片上传 Modal -->
        <div v-if="showImageUpload"
            class="image-upload-modal fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div class="image-upload-modal-content bg-white rounded-lg p-6 w-11/12 max-w-lg">
                <h3 class="text-lg font-medium mb-4 text-gray-900">插入图片</h3>
                <div class="mb-4">
                    <div class="flex items-center mb-2">
                        <input id="url-radio" type="radio" v-model="imageSource" value="url"
                            class="mr-2 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                        <label for="url-radio" class="text-sm text-gray-700">图片URL</label>
                    </div>
                    <input v-if="imageSource === 'url'" v-model="imageUrl" type="text" placeholder="输入图片URL"
                        class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                <div class="mb-4">
                    <div class="flex items-center mb-2">
                        <input id="upload-radio" type="radio" v-model="imageSource" value="upload"
                            class="mr-2 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                        <label for="upload-radio" class="text-sm text-gray-700">上传图片</label>
                    </div>
                    <input v-if="imageSource === 'upload'" type="file" @change="handleFileUpload" accept="image/*"
                        class="w-full p-2 border border-gray-300 rounded-md text-sm file:mr-4 file:py-1 file:px-2 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <div v-if="uploadProgress > 0 && uploadProgress < 100" class="mt-2">
                        <div class="bg-gray-200 rounded-full h-2.5">
                            <div class="bg-blue-600 h-2.5 rounded-full" :style="{ width: uploadProgress + '%' }"></div>
                        </div>
                    </div>
                    <p v-if="uploadError" class="text-red-500 text-sm mt-1">{{ uploadError }}</p>
                    <div v-if="uploadedImageUrl" class="mt-2">
                        <p class="text-sm text-green-600 mb-1">图片上传成功!</p>
                        <img :src="uploadedImageUrl" class="max-h-32 max-w-full rounded border border-gray-200" />
                    </div>
                </div>
                <div class="mb-4">
                    <div class="flex items-center mb-2">
                        <label class="text-sm font-medium text-gray-700">图片设置</label>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs text-gray-600 block mb-1">替代文本</label>
                            <input v-model="imageAlt" type="text" placeholder="图片描述（可选）"
                                class="w-full p-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600 block mb-1">标题</label>
                            <input v-model="imageTitle" type="text" placeholder="图片标题（可选）"
                                class="w-full p-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-2">
                    <button @click="cancelImageUpload"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">取消</button>
                    <button @click="insertImage"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm"
                        :disabled="!(imageUrl || uploadedImageUrl)">插入</button>
                </div>
            </div>
        </div>

        <!-- 代码语言选择 Modal -->
        <div v-if="showCodeLangModal"
            class="image-upload-modal fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div class="image-upload-modal-content bg-white rounded-lg p-6 w-11/12 max-w-md">
                <h3 class="text-lg font-medium mb-4 text-gray-900">选择代码语言</h3>
                <div class="mb-4">
                    <select v-model="selectedCodeLang"
                        class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">纯文本 (Plain Text)</option>
                        <option value="javascript">JavaScript</option>
                        <option value="html">HTML</option>
                        <option value="css">CSS</option>
                        <option value="php">PHP</option>
                        <option value="python">Python</option>
                        <!-- <option value="java">Java</option>
                        <option value="csharp">C#</option>
                        <option value="cpp">C++</option>
                        <option value="ruby">Ruby</option>
                        <option value="go">Go</option>
                        <option value="rust">Rust</option>
                        <option value="sql">SQL</option> -->
                        <option value="json">JSON</option>
                        <!-- <option value="yaml">YAML</option>
                        <option value="xml">XML</option>
                        <option value="markdown">Markdown</option>
                        <option value="bash">Bash</option> -->
                    </select>
                </div>
                <div class="flex justify-end space-x-2">
                    <button @click="cancelCodeLangSelection"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">取消</button>
                    <button @click="insertCodeBlock"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">插入</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios'; // 确保 axios 已导入

// 从 props 接收 isEditable 状态，不再内部管理
const props = defineProps({
    editor: { type: Object, required: true },
    isEditable: { type: Boolean, default: true } // 由父组件传入
});

// 只发出 toggle-edit 事件
const emit = defineEmits(['toggle-edit']);

// 状态管理
const isMobile = ref(false);
const activeCategoryIndex = ref(0);
const menuCategories = ['格式', '段落', '插入', '工具']; // 保持不变

// 图片上传状态
const showImageUpload = ref(false);
const imageSource = ref('url');
const imageUrl = ref('');
const imageAlt = ref('');
const imageTitle = ref('');
const uploadedImageUrl = ref('');
const uploadProgress = ref(0);
const uploadError = ref('');

// 代码块语言选择状态
const showCodeLangModal = ref(false);
const selectedCodeLang = ref('');

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
    if (!props.isEditable) return; // 只有可编辑时才执行
    const previousUrl = props.editor.getAttributes('link').href;
    const url = window.prompt('输入链接URL', previousUrl);
    if (url === null) return; // Cancelled
    if (url === '') {
        props.editor.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }
    let finalUrl = url;
    if (!url.includes('://') && !url.startsWith('/') && !url.startsWith('#') && !url.startsWith('mailto:') && !url.startsWith('tel:')) {
        finalUrl = 'https://' + url;
    }
    props.editor.chain().focus().extendMarkRange('link').setLink({ href: finalUrl }).run();
};

// 打开图片上传弹窗
const openImageUpload = () => {
    if (!props.isEditable) return;
    showImageUpload.value = true;
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
                uploadProgress.value = 50; // Indeterminate
            }
        }
    }).then(response => {
        if (response.data && response.data.url) {
            uploadedImageUrl.value = response.data.url;
            uploadProgress.value = 100;
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

// 打开代码块语言选择弹窗
const openCodeLangModal = () => {
    if (!props.isEditable) return;
    showCodeLangModal.value = true;
    selectedCodeLang.value = props.editor.getAttributes('codeBlock').language || '';
};

// 取消代码块语言选择
const cancelCodeLangSelection = () => {
    showCodeLangModal.value = false;
};

// 插入代码块
const insertCodeBlock = () => {
    if (props.isEditable) {
        props.editor.chain().focus().setCodeBlock({ language: selectedCodeLang.value }).run();
        showCodeLangModal.value = false;
    }
};
</script>

<style scoped>
/* 保持之前的样式不变 */
.menu-button {
    padding: 0.4rem 0.6rem;
    border-radius: 0.375rem;
    /* 6px */
    background: transparent;
    border: none;
    color: #4b5563;
    /* gray-600 */
    cursor: pointer;
    font-size: 0.875rem;
    /* 14px */
    line-height: 1;
    display: inline-flex;
    align-items: center;
    transition: all 0.2s ease;
    margin: 0 1px;
    /* 调整按钮间距 */
}

.menu-button:hover:not(:disabled) {
    background-color: #e5e7eb;
    /* gray-200 */
    color: #1f2937;
    /* gray-800 */
}

.menu-button.is-active {
    background-color: #dbeafe;
    /* blue-100 */
    color: #2563eb;
    /* blue-600 */
}

.menu-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.menu-button-heading {
    font-weight: bold;
    min-width: 30px;
    /* 给 H1-H6 按钮固定宽度 */
    justify-content: center;
}

.menu-group {
    display: flex;
    gap: 0.25rem;
    /* 4px */
    padding: 0 0.5rem;
    /* 8px */
    align-items: center;
    /* 在小屏幕以下不显示右边框，在大屏幕上显示 */
    border-right: 1px solid #d1d5db;
    /* gray-300 */
}

/* 最后一个组不需要右边框 */
.menu-group:last-of-type {
    border-right: none;
}


/* 移动端菜单分类 */
.mobile-menu-categories button {
    flex-grow: 1;
    text-align: center;
}

/* 响应式调整 */
@media (max-width: 768px) {
    .menu-container {
        flex-direction: column;
        align-items: stretch;
        /* 让分类按钮充满宽度 */
        gap: 0.5rem;
        /* 8px */
        width: 100%;
    }

    .menu-group {
        border-right: none;
        /* 移动端移除组间分割线 */
        padding: 0;
        justify-content: center;
        /* 居中按钮 */
        flex-wrap: wrap;
        /* 允许按钮换行 */
    }

    .editor-menu-bar {
        flex-direction: column;
        /* 整个菜单栏垂直排列 */
    }

    .ml-auto {
        /* 重置编辑按钮的位置 */
        margin-left: 0;
        margin-top: 0.5rem;
        /* 8px */
    }
}

/* 图片上传 Modal 样式 */
.image-upload-modal-content {
    /* 基础样式 */
    background-color: white;
    border-radius: 0.5rem;
    /* 8px */
    padding: 1.5rem;
    /* 24px */
    width: 91.666667%;
    /* w-11/12 */
    max-width: 32rem;
    /* max-w-lg */
    color: #374151;
    /* Default text color for modal */
}

.image-upload-modal-content h3 {
    color: #1f2937;
    /* Darker heading */
}

.image-upload-modal-content label {
    color: #4b5563;
    /* Slightly lighter label color */
}

.image-upload-modal-content input[type=text],
.image-upload-modal-content select {
    color: #1f2937;
    /* Ensure text input color is visible */
}

.image-upload-modal-content input[type=file] {
    color: #4b5563;
}
</style>
<template>
    <!-- 增加 padding 和背景色，圆角 -->
    <div
        class="editor-menu-bar bg-gray-100 p-2 flex flex-wrap gap-1 rounded-t-lg border-b border-gray-300 items-center">
        <!-- 编辑功能区 -->
        <div v-if="isEditable" class="menu-container flex-grow">
            <!-- 移动端分类按钮 -->
            <div v-if="isMobile" class="mobile-menu-categories w-full flex justify-center mb-2 border-b pb-2">
                <button v-for="(category, index) in menuCategories" :key="index" @click="activeCategoryIndex = index"
                    :class="['px-2 py-1 text-xs rounded-md mx-1 transition-colors', activeCategoryIndex === index ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300']">
                    {{ category }}
                </button>
            </div>
            <!-- 各个菜单组 -->
            <!-- 样式调整：增加按钮间距，可能统一按钮大小 -->
            <div v-if="!isMobile || activeCategoryIndex === 0" class="menu-group">
                <button type="button" @click="editor.chain().focus().toggleBold().run()"
                    :class="{ 'is-active': editor.isActive('bold') }" class="menu-button" title="粗体 (Ctrl+B)">
                    <font-awesome-icon :icon="['fas', 'bold']" /> </button>
                <button type="button" @click="editor.chain().focus().toggleItalic().run()"
                    :class="{ 'is-active': editor.isActive('italic') }" class="menu-button" title="斜体 (Ctrl+I)">
                    <font-awesome-icon :icon="['fas', 'italic']" /> </button>
                <button type="button" @click="editor.chain().focus().toggleUnderline().run()"
                    :class="{ 'is-active': editor.isActive('underline') }" class="menu-button" title="下划线 (Ctrl+U)">
                    <font-awesome-icon :icon="['fas', 'underline']" /> </button>
                <button type="button" @click="editor.chain().focus().toggleStrike().run()"
                    :class="{ 'is-active': editor.isActive('strike') }" class="menu-button" title="删除线">
                    <font-awesome-icon :icon="['fas', 'strikethrough']" /> </button>
                <button type="button" @click="editor.chain().focus().toggleHighlight().run()"
                    :class="{ 'is-active': editor.isActive('highlight') }" class="menu-button" title="高亮">
                    <font-awesome-icon :icon="['fas', 'highlighter']" /> </button>
                <button type="button" @click="editor.chain().focus().toggleCode().run()"
                    :class="{ 'is-active': editor.isActive('code') }" class="menu-button" title="内联代码">
                    <font-awesome-icon :icon="['fas', 'code']" /> </button>
            </div>
            <div v-if="!isMobile || activeCategoryIndex === 1" class="menu-group">
                <button type="button" @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
                    :class="{ 'is-active': editor.isActive('heading', { level: 1 }) }"
                    class="menu-button menu-button-heading" title="标题 1">H1</button>
                <button type="button" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                    :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }"
                    class="menu-button menu-button-heading" title="标题 2">H2</button>
                <button type="button" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                    :class="{ 'is-active': editor.isActive('heading', { level: 3 }) }"
                    class="menu-button menu-button-heading" title="标题 3">H3</button>
                <button type="button" @click="editor.chain().focus().setTextAlign('left').run()"
                    :class="{ 'is-active': editor.isActive({ textAlign: 'left' }) }" class="menu-button" title="左对齐">
                    <font-awesome-icon :icon="['fas', 'align-left']" /> </button>
                <button type="button" @click="editor.chain().focus().setTextAlign('center').run()"
                    :class="{ 'is-active': editor.isActive({ textAlign: 'center' }) }" class="menu-button" title="居中对齐">
                    <font-awesome-icon :icon="['fas', 'align-center']" /> </button>
                <button type="button" @click="editor.chain().focus().setTextAlign('right').run()"
                    :class="{ 'is-active': editor.isActive({ textAlign: 'right' }) }" class="menu-button" title="右对齐">
                    <font-awesome-icon :icon="['fas', 'align-right']" /> </button>
                <button type="button" @click="editor.chain().focus().setTextAlign('justify').run()"
                    :class="{ 'is-active': editor.isActive({ textAlign: 'justify' }) }" class="menu-button"
                    title="两端对齐"> <font-awesome-icon :icon="['fas', 'align-justify']" /> </button>
            </div>
            <div v-if="!isMobile || activeCategoryIndex === 2" class="menu-group">
                <button type="button" @click="editor.chain().focus().toggleBulletList().run()"
                    :class="{ 'is-active': editor.isActive('bulletList') }" class="menu-button" title="无序列表">
                    <font-awesome-icon :icon="['fas', 'list-ul']" /> </button>
                <button type="button" @click="editor.chain().focus().toggleOrderedList().run()"
                    :class="{ 'is-active': editor.isActive('orderedList') }" class="menu-button" title="有序列表">
                    <font-awesome-icon :icon="['fas', 'list-ol']" /> </button>
                <button type="button" @click="editor.chain().focus().toggleBlockquote().run()"
                    :class="{ 'is-active': editor.isActive('blockquote') }" class="menu-button" title="引用">
                    <font-awesome-icon :icon="['fas', 'quote-left']" /> </button>
                <button type="button" @click="editor.chain().focus().toggleCodeBlock().run()"
                    :class="{ 'is-active': editor.isActive('codeBlock') }" class="menu-button" title="代码块">
                    <font-awesome-icon :icon="['fas', 'file-code']" /> </button>
                <button type="button" @click="insertTable" class="menu-button" title="插入表格"> <font-awesome-icon
                        :icon="['fas', 'table']" /> </button>
                <button type="button" @click="editor.chain().focus().addColumnBefore().run()" class="menu-button"
                    title="左侧插入列" :disabled="!editor.can().addColumnBefore()"> <font-awesome-icon
                        :icon="['fas', 'plus']" /> <font-awesome-icon :icon="['fas', 'columns']" class="ml-1 text-xs" />
                </button>
                <button type="button" @click="editor.chain().focus().addColumnAfter().run()" class="menu-button"
                    title="右侧插入列" :disabled="!editor.can().addColumnAfter()"> <font-awesome-icon
                        :icon="['fas', 'columns']" /> <font-awesome-icon :icon="['fas', 'plus']" class="ml-1 text-xs" />
                </button>
                <button type="button" @click="editor.chain().focus().deleteColumn().run()" class="menu-button"
                    title="删除列" :disabled="!editor.can().deleteColumn()"> <font-awesome-icon :icon="['fas', 'minus']" />
                    <font-awesome-icon :icon="['fas', 'columns']" class="ml-1 text-xs" /> </button>
                <button type="button" @click="editor.chain().focus().addRowBefore().run()" class="menu-button"
                    title="上方插入行" :disabled="!editor.can().addRowBefore()"> <font-awesome-icon
                        :icon="['fas', 'plus']" /> <font-awesome-icon :icon="['fas', 'grip-lines']"
                        class="ml-1 text-xs" /> </button>
                <button type="button" @click="editor.chain().focus().addRowAfter().run()" class="menu-button"
                    title="下方插入行" :disabled="!editor.can().addRowAfter()"> <font-awesome-icon
                        :icon="['fas', 'grip-lines']" /> <font-awesome-icon :icon="['fas', 'plus']"
                        class="ml-1 text-xs" /> </button>
                <button type="button" @click="editor.chain().focus().deleteRow().run()" class="menu-button" title="删除行"
                    :disabled="!editor.can().deleteRow()"> <font-awesome-icon :icon="['fas', 'minus']" />
                    <font-awesome-icon :icon="['fas', 'grip-lines']" class="ml-1 text-xs" /> </button>
                <button type="button" @click="editor.chain().focus().deleteTable().run()" class="menu-button"
                    title="删除表格" :disabled="!editor.can().deleteTable()"> <font-awesome-icon
                        :icon="['fas', 'trash-alt']" /> </button>
            </div>
            <div v-if="!isMobile || activeCategoryIndex === 3" class="menu-group">
                <button type="button" @click="setLink" :class="{ 'is-active': editor.isActive('link') }"
                    class="menu-button" title="链接"> <font-awesome-icon :icon="['fas', 'link']" /> </button>
                <button type="button" @click="openImageUpload" class="menu-button" title="图片"> <font-awesome-icon
                        :icon="['fas', 'image']" /> </button>
                <button type="button" @click="editor.chain().focus().setHorizontalRule().run()" class="menu-button"
                    title="分隔线"> <font-awesome-icon :icon="['fas', 'minus']" /> </button>
                <button type="button" @click="editor.chain().focus().undo().run()" :disabled="!editor.can().undo()"
                    class="menu-button" title="撤销"> <font-awesome-icon :icon="['fas', 'undo']" /> </button>
                <button type="button" @click="editor.chain().focus().redo().run()" :disabled="!editor.can().redo()"
                    class="menu-button" title="重做"> <font-awesome-icon :icon="['fas', 'redo']" /> </button>
            </div>
        </div>

        <!-- 预览/编辑切换按钮，移到最右侧 -->
        <div class="ml-auto pl-2 border-l border-gray-300">
            <button type="button" @click="$emit('toggle-edit')" class="menu-button"
                :title="isEditable ? '预览模式' : '编辑模式'">
                <font-awesome-icon :icon="['fas', isEditable ? 'eye' : 'pen']" class="mr-1" />
                <span>{{ isEditable ? '预览' : '编辑' }}</span>
            </button>
        </div>

        <!-- 图片上传弹窗 -->
        <div v-if="showImageUpload"
            class="image-upload-modal fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div class="image-upload-modal-content bg-white rounded-lg p-6 w-11/12 max-w-lg">
                <h3 class="text-lg font-medium mb-4">插入图片</h3>
                <div class="mb-4">
                    <div class="flex items-center mb-2">
                        <input id="url-radio" type="radio" v-model="imageSource" value="url" class="mr-2">
                        <label for="url-radio">图片URL</label>
                    </div>
                    <input v-if="imageSource === 'url'" v-model="imageUrl" type="text" placeholder="输入图片URL"
                        class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <div class="flex items-center mb-2">
                        <input id="upload-radio" type="radio" v-model="imageSource" value="upload" class="mr-2">
                        <label for="upload-radio">上传图片</label>
                    </div>
                    <input v-if="imageSource === 'upload'" type="file" @change="handleFileUpload" accept="image/*"
                        class="w-full p-2 border border-gray-300 rounded-md">
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
                        <label class="text-sm font-medium">图片设置</label>
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
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">取消</button>
                    <button @click="insertImage" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                        :disabled="!(imageUrl || uploadedImageUrl)">插入</button>
                </div>
            </div>
        </div>

        <!-- 代码语言选择弹窗 -->
        <div v-if="showCodeLangModal"
            class="image-upload-modal fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div class="image-upload-modal-content bg-white rounded-lg p-6 w-11/12 max-w-md">
                <h3 class="text-lg font-medium mb-4">选择代码语言</h3>
                <div class="mb-4">
                    <select v-model="selectedCodeLang"
                        class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">纯文本</option>
                        <option value="javascript">JavaScript</option>
                        <option value="html">HTML</option>
                        <option value="css">CSS</option>
                        <option value="php">PHP</option>
                        <option value="python">Python</option>
                        <option value="java">Java</option>
                        <option value="csharp">C#</option>
                        <option value="cpp">C++</option>
                        <option value="ruby">Ruby</option>
                        <option value="go">Go</option>
                        <option value="rust">Rust</option>
                        <option value="sql">SQL</option>
                        <option value="json">JSON</option>
                        <option value="yaml">YAML</option>
                        <option value="xml">XML</option>
                        <option value="markdown">Markdown</option>
                        <option value="bash">Bash</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-2">
                    <button @click="cancelCodeLangSelection"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">取消</button>
                    <button @click="insertCodeBlock"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">插入</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios'; // 假设用于上传图片

const props = defineProps({
    editor: { type: Object, required: true },
    isEditable: { type: Boolean, default: true }
});
const emit = defineEmits(['toggle-edit']);

const isMobile = ref(false);
const activeCategoryIndex = ref(0);
const menuCategories = ['格式', '段落', '插入', '工具']; // 调整分类以便移动端显示

const showImageUpload = ref(false);
const imageSource = ref('url');
const imageUrl = ref('');
const imageAlt = ref('');
const imageTitle = ref('');
const uploadedImageUrl = ref('');
const uploadProgress = ref(0);
const uploadError = ref('');

const showCodeLangModal = ref(false);
const selectedCodeLang = ref('');

const checkDeviceType = () => { isMobile.value = window.innerWidth < 768; };

onMounted(() => { checkDeviceType(); window.addEventListener('resize', checkDeviceType); });
onUnmounted(() => { window.removeEventListener('resize', checkDeviceType); });

const setLink = () => {
    const previousUrl = props.editor.getAttributes('link').href;
    const url = window.prompt('输入链接URL', previousUrl);
    if (url === null) return;
    if (url === '') {
        props.editor.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }
    let finalUrl = url;
    if (!/^https?:\/\//i.test(url)) finalUrl = 'https://' + url;
    props.editor.chain().focus().extendMarkRange('link').setLink({ href: finalUrl }).run();
};

const openImageUpload = () => {
    showImageUpload.value = true;
    imageUrl.value = '';
    imageAlt.value = '';
    imageTitle.value = '';
    uploadedImageUrl.value = '';
    uploadProgress.value = 0;
    uploadError.value = '';
};

const cancelImageUpload = () => {
    showImageUpload.value = false;
};

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
    axios.post('/api/upload-image', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (progressEvent) => {
            uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
        }
    }).then(response => {
        uploadedImageUrl.value = response.data.url;
    }).catch(error => {
        uploadError.value = '上传失败：' + (error.response?.data?.error || '网络错误');
    });
};

const insertImage = () => {
    const url = imageSource.value === 'url' ? imageUrl.value : uploadedImageUrl.value;
    if (url) {
        const attrs = { src: url };
        if (imageAlt.value) attrs.alt = imageAlt.value;
        if (imageTitle.value) attrs.title = imageTitle.value;
        props.editor.chain().focus().setImage(attrs).run();
        showImageUpload.value = false;
    }
};

const insertTable = () => {
    props.editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
};

const openCodeLangModal = () => {
    showCodeLangModal.value = true;
    selectedCodeLang.value = '';
};

const cancelCodeLangSelection = () => {
    showCodeLangModal.value = false;
};

const insertCodeBlock = () => {
    props.editor.chain().focus().setCodeBlock({ language: selectedCodeLang.value }).run();
    showCodeLangModal.value = false;
};
</script>

<style scoped>
/* 基础按钮样式 */
.menu-button {
    padding: 0.4rem 0.6rem;
    /* 统一内边距 */
    border-radius: 0.375rem;
    /* 圆角 */
    background: transparent;
    border: none;
    color: #4b5563;
    /* 灰色 */
    cursor: pointer;
    font-size: 0.875rem;
    line-height: 1;
    /* 确保图标和文字对齐 */
    display: inline-flex;
    align-items: center;
    transition: all 0.2s ease;
    margin: 0 1px;
    /* 增加按钮间水平间距 */
}

.menu-button:hover:not(:disabled) {
    background-color: #e5e7eb;
    /* 悬停背景色 */
    color: #1f2937;
}

.menu-button.is-active {
    background-color: #dbeafe;
    /* 激活背景色 (浅蓝) */
    color: #2563eb;
    /* 激活文字色 (蓝色) */
}

.menu-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* 标题按钮特殊样式 */
.menu-button-heading {
    font-weight: bold;
    min-width: 30px;
    /* 固定宽度 */
    justify-content: center;
}

/* 菜单组样式 */
.menu-group {
    display: flex;
    gap: 0.25rem;
    padding: 0 0.5rem;
    /* 组内边距 */
    align-items: center;
    /* 分隔线样式 */
    border-right: 1px solid #d1d5db;
}

/* 最后一个菜单组没有右边框 */
.menu-group:last-of-type {
    border-right: none;
}

/* 移动端分类按钮样式 */
.mobile-menu-categories button {
    flex-grow: 1;
    text-align: center;
}

/* 响应式调整：小屏幕时菜单组垂直排列或允许滚动 */
@media (max-width: 768px) {
    .menu-container {
        flex-direction: column;
        align-items: stretch;
        /* 让组充满宽度 */
        gap: 0.5rem;
        /* 增加组间垂直间距 */
        width: 100%;
    }

    .menu-group {
        border-right: none;
        /* 移除垂直分隔线 */
        padding: 0;
        /* 移除组内边距 */
        justify-content: center;
        /* 按钮居中 */
        flex-wrap: wrap;
        /* 允许按钮换行 */
    }

    .editor-menu-bar {
        flex-direction: column;
        /* 整体垂直排列 */
    }

    .ml-auto {
        margin-left: 0;
        margin-top: 0.5rem;
    }

    /* 调整预览按钮位置 */
}
</style>
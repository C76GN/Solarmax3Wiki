<template>
    <!-- 编辑器菜单栏主容器，包含各种工具按钮 -->
    <div
        class="editor-menu-bar bg-gray-800 p-2 flex flex-wrap gap-1 rounded-t-lg border-b border-gray-700 items-center">
        <!-- 如果编辑器可编辑，显示工具栏 -->
        <div v-if="isEditable" class="menu-container flex-grow flex flex-wrap items-center gap-1">
            <!-- 移动端菜单分类按钮，根据屏幕宽度显示 -->
            <div v-if="isMobile"
                class="mobile-menu-categories w-full flex justify-center mb-2 border-b border-gray-700 pb-2">
                <button v-for="(category, index) in menuCategories" :key="index" @click="activeCategoryIndex = index"
                    :class="['px-2 py-1 text-xs rounded-md mx-1 transition-colors',
                        activeCategoryIndex === index
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-600 text-gray-300 hover:bg-gray-500'
                    ]">
                    {{ category }}
                </button>
            </div>

            <!-- 格式工具按钮组：粗体、斜体、下划线、删除线、高亮、内联代码 -->
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

            <!-- 段落工具按钮组：标题、文本对齐 -->
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

            <!-- 插入工具按钮组：列表、引用、代码块、表格及表格操作 -->
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
                <!-- 如果当前光标在表格内，显示表格操作按钮 -->
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
                    <button type="button" @click="editor.chain().focus().deleteTable().run()"
                        :disabled="!editor.can().deleteTable()" class="menu-button" title="删除表格"><font-awesome-icon
                            :icon="['fas', 'trash-alt']" /></button>
                </template>
            </div>

            <!-- 其他工具按钮组：链接、图片、分隔线、撤销、重做 -->
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

        <!-- 预览/编辑切换按钮 -->
        <div class="ml-auto pl-2 border-l border-gray-600 flex-shrink-0">
            <button type="button" @click="$emit('toggle-edit')" class="menu-button"
                :title="isEditable ? '切换到预览模式' : '切换到编辑模式'">
                <font-awesome-icon :icon="['fas', isEditable ? 'eye' : 'pen']" class="mr-1" />
                <span>{{ isEditable ? '预览' : '编辑' }}</span>
            </button>
        </div>

        <!-- 图片上传模态框 -->
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
                    <!-- 图片URL输入框 -->
                    <input v-if="imageSource === 'url'" v-model="imageUrl" type="text" placeholder="输入图片URL"
                        class="w-full p-2 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm bg-gray-700 text-gray-200 placeholder-gray-500">
                </div>
                <div class="mb-4">
                    <div class="flex items-center mb-2">
                        <input id="upload-radio" type="radio" v-model="imageSource" value="upload"
                            class="mr-2 h-4 w-4 text-blue-500 focus:ring-blue-500 border-gray-600 bg-gray-700">
                        <label for="upload-radio" class="text-sm text-gray-300">上传图片</label>
                    </div>
                    <!-- 文件上传输入框和进度条 -->
                    <input v-if="imageSource === 'upload'" type="file" @change="handleFileUpload" accept="image/*"
                        class="w-full p-2 border border-gray-600 rounded-md text-sm file:mr-4 file:py-1 file:px-2 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-800 file:text-blue-200 hover:file:bg-blue-700 bg-gray-700 text-gray-400">
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
                <!-- 图片替代文本和标题设置 -->
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
                <!-- 图片上传模态框操作按钮 -->
                <div class="flex justify-end space-x-2">
                    <button @click="cancelImageUpload"
                        class="px-4 py-2 bg-gray-600 text-gray-200 rounded-md hover:bg-gray-500 text-sm transition">取消</button>
                    <button @click="insertImage"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-500 text-sm transition disabled:opacity-50"
                        :disabled="!(imageUrl || uploadedImageUrl)">插入</button>
                </div>
            </div>
        </div>

        <!-- 代码块语言选择模态框 -->
        <div v-if="showCodeLangModal"
            class="image-upload-modal fixed inset-0 bg-black bg-opacity-70 flex justify-center items-center z-50">
            <div class="image-upload-modal-content bg-gray-800 rounded-lg p-6 w-11/12 max-w-md shadow-xl">
                <h3 class="text-lg font-medium mb-4 text-gray-100">选择代码语言</h3>
                <div class="mb-4">
                    <!-- 代码语言下拉选择框 -->
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
                <!-- 代码语言选择模态框操作按钮 -->
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
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

// 定义组件接收的 props
const props = defineProps({
    editor: { type: Object, required: true }, // Tiptap 编辑器实例
    isEditable: { type: Boolean, default: true } // 编辑器是否可编辑
});

// 定义组件触发的事件
const emit = defineEmits(['toggle-edit']); // 切换编辑/预览模式事件

// 响应式变量
const isMobile = ref(false); // 是否为移动端
const activeCategoryIndex = ref(0); // 移动端当前激活的菜单分类索引
const menuCategories = ['格式', '段落', '插入', '工具']; // 移动端菜单分类名称

// 图片上传相关状态
const showImageUpload = ref(false); // 是否显示图片上传模态框
const imageSource = ref('url'); // 图片来源：'url' 或 'upload'
const imageUrl = ref(''); // 图片URL
const imageAlt = ref(''); // 图片alt文本
const imageTitle = ref(''); // 图片title文本
const uploadedImageUrl = ref(''); // 上传成功后的图片URL
const uploadProgress = ref(0); // 图片上传进度
const uploadError = ref(''); // 图片上传错误信息

// 代码块语言选择相关状态
const showCodeLangModal = ref(false); // 是否显示代码语言选择模态框
const selectedCodeLang = ref(''); // 选中的代码语言

// --- 方法定义 ---

/**
 * 检测并更新设备类型（移动端/非移动端）
 */
const checkDeviceType = () => {
    isMobile.value = window.innerWidth < 768;
};

// 组件挂载时执行
onMounted(() => {
    checkDeviceType(); // 初始化设备类型
    window.addEventListener('resize', checkDeviceType); // 监听窗口大小变化
});

// 组件卸载时执行
onUnmounted(() => {
    window.removeEventListener('resize', checkDeviceType); // 移除窗口大小变化监听
});

/**
 * 设置或更新链接
 */
const setLink = () => {
    if (!props.isEditable) return; // 如果不可编辑，则退出
    const previousUrl = props.editor.getAttributes('link').href; // 获取当前链接属性
    const url = window.prompt('输入链接URL', previousUrl); // 弹出提示框输入URL

    if (url === null) return; // 如果用户取消输入，则退出

    if (url === '') {
        // 如果输入为空，则移除链接
        props.editor.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }
    // 简单的 URL 格式校验和补全
    let finalUrl = url;
    if (!url.includes('://') && !url.startsWith('/') && !url.startsWith('#') && !url.startsWith('mailto:') && !url.startsWith('tel:')) {
        finalUrl = 'https://' + url;
    }

    // 设置链接
    props.editor.chain().focus().extendMarkRange('link').setLink({ href: finalUrl }).run();
};

/**
 * 打开图片上传模态框并重置状态
 */
const openImageUpload = () => {
    if (!props.isEditable) return; // 如果不可编辑，则退出
    showImageUpload.value = true; // 显示模态框
    // 重置所有图片上传相关状态
    imageSource.value = 'url';
    imageUrl.value = '';
    imageAlt.value = '';
    imageTitle.value = '';
    uploadedImageUrl.value = '';
    uploadProgress.value = 0;
    uploadError.value = '';
};

/**
 * 取消图片上传并关闭模态框
 */
const cancelImageUpload = () => {
    showImageUpload.value = false;
};

/**
 * 处理文件上传
 * @param {Event} event - 文件输入框的 change 事件
 */
const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return; // 如果没有选择文件，则退出

    if (!file.type.startsWith('image/')) {
        uploadError.value = '请选择图片文件'; // 非图片文件提示错误
        return;
    }

    const formData = new FormData();
    formData.append('image', file); // 添加图片文件到 FormData
    uploadProgress.value = 0; // 重置上传进度
    uploadError.value = ''; // 清除错误信息

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'); // 获取 CSRF token

    // 发送 POST 请求上传图片
    axios.post('/api/upload-image', formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
            'X-CSRF-TOKEN': csrfToken // 设置 CSRF token
        },
        onUploadProgress: (progressEvent) => {
            const total = progressEvent.total;
            if (total) {
                uploadProgress.value = Math.round((progressEvent.loaded * 100) / total); // 计算上传进度
            } else {
                uploadProgress.value = 50; // 如果无法获取总大小，给一个中间值
            }
        }
    }).then(response => {
        if (response.data && response.data.url) {
            uploadedImageUrl.value = response.data.url; // 保存上传后的图片URL
            uploadProgress.value = 100; // 标记上传完成
        } else {
            uploadError.value = '上传成功，但未收到有效的图片URL';
            uploadProgress.value = 0;
        }

    }).catch(error => {
        console.error("图片上传错误:", error.response?.data || error.message);
        uploadError.value = '上传失败：' + (error.response?.data?.message || error.response?.data?.error || '网络或服务器错误');
        uploadProgress.value = 0;
    });
};

/**
 * 插入图片到编辑器
 */
const insertImage = () => {
    const url = imageSource.value === 'url' ? imageUrl.value : uploadedImageUrl.value; // 根据来源获取图片URL
    if (url && props.isEditable) {
        const attrs = { src: url }; // 图片属性
        if (imageAlt.value) attrs.alt = imageAlt.value; // 添加alt属性
        if (imageTitle.value) attrs.title = imageTitle.value; // 添加title属性
        props.editor.chain().focus().setImage(attrs).run(); // 在编辑器中插入图片
        showImageUpload.value = false; // 关闭模态框
    }
};

/**
 * 插入表格到编辑器
 */
const insertTable = () => {
    if (props.isEditable) {
        props.editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(); // 插入一个3x3带表头的表格
    }
};

/**
 * 打开代码块语言选择模态框
 */
const openCodeLangModal = () => {
    if (!props.isEditable) return; // 如果不可编辑，则退出
    showCodeLangModal.value = true; // 显示模态框
    selectedCodeLang.value = props.editor.getAttributes('codeBlock').language || ''; // 获取当前代码块语言并设置到选择框
};

/**
 * 取消代码块语言选择并关闭模态框
 */
const cancelCodeLangSelection = () => {
    showCodeLangModal.value = false;
};

/**
 * 插入或更新代码块语言
 */
const insertCodeBlock = () => {
    if (props.isEditable) {
        props.editor.chain().focus().setCodeBlock({ language: selectedCodeLang.value }).run(); // 设置代码块语言
        showCodeLangModal.value = false; // 关闭模态框
    }
};
</script>

<style scoped>
/* 菜单按钮通用样式 */
.menu-button {
    padding: 0.4rem 0.6rem;
    border-radius: 0.375rem;
    background: transparent;
    border: none;
    color: #d1d5db;
    cursor: pointer;
    font-size: 0.875rem;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    transition: all 0.2s ease;
    margin: 0 1px;
}

/* 菜单按钮悬停和激活状态样式 */
.menu-button:hover:not(:disabled) {
    background-color: #374151;
    color: #f9fafb;
}

.menu-button.is-active {
    background-color: #3b82f6;
    color: #ffffff;
}

/* 禁用状态的菜单按钮 */
.menu-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* 标题按钮的特定样式 */
.menu-button-heading {
    font-weight: bold;
    min-width: 30px;
    justify-content: center;
}

/* 菜单分组的样式，用于视觉上区分不同的工具集 */
.menu-group {
    display: flex;
    gap: 0.25rem;
    padding: 0 0.5rem;
    align-items: center;
    border-right: 1px solid #4b5563;
}

/* 最后一个菜单分组没有右边框 */
.menu-group:last-of-type {
    border-right: none;
}

/* 移动端响应式样式调整 */
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

    /* 移动端预览/编辑切换按钮的边距调整 */
    .ml-auto {
        margin-left: 0;
        margin-top: 0.5rem;
    }
}

/* 图片上传模态框内容区域的文字颜色 */
.image-upload-modal-content {
    color: #d1d5db;
}

.image-upload-modal-content h3 {
    color: #f9fafb;
}

.image-upload-modal-content label {
    color: #9ca3af;
}
</style>
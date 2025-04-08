<template>
    <div class="editor-menu-bar bg-gray-100 p-2 flex flex-wrap gap-1 rounded-t-lg border-b">
        <div v-if="isEditable" class="menu-container">
            <div v-if="!isMobile || (isMobile && activeCategoryIndex === 0)" class="menu-group">
                <button @click="editor.chain().focus().toggleBold().run()"
                    :class="{ 'is-active': editor.isActive('bold') }" class="menu-button" title="粗体">
                    <font-awesome-icon :icon="['fas', 'bold']" />
                </button>
                <button @click="editor.chain().focus().toggleItalic().run()"
                    :class="{ 'is-active': editor.isActive('italic') }" class="menu-button" title="斜体">
                    <font-awesome-icon :icon="['fas', 'italic']" />
                </button>
                <button @click="editor.chain().focus().toggleStrike().run()"
                    :class="{ 'is-active': editor.isActive('strike') }" class="menu-button" title="删除线">
                    <font-awesome-icon :icon="['fas', 'strikethrough']" />
                </button>
                <button @click="editor.chain().focus().toggleCode().run()"
                    :class="{ 'is-active': editor.isActive('code') }" class="menu-button" title="内联代码">
                    <font-awesome-icon :icon="['fas', 'code']" />
                </button>
            </div>

            <div v-if="!isMobile || (isMobile && activeCategoryIndex === 1)" class="menu-group">
                <button @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
                    :class="{ 'is-active': editor.isActive('heading', { level: 1 }) }" class="menu-button" title="标题 1">
                    <font-awesome-icon :icon="['fas', 'heading']" /><sup>1</sup>
                </button>
                <button @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                    :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }" class="menu-button" title="标题 2">
                    <font-awesome-icon :icon="['fas', 'heading']" /><sup>2</sup>
                </button>
                <button @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                    :class="{ 'is-active': editor.isActive('heading', { level: 3 }) }" class="menu-button" title="标题 3">
                    <font-awesome-icon :icon="['fas', 'heading']" /><sup>3</sup>
                </button>
            </div>

            <div v-if="!isMobile || (isMobile && activeCategoryIndex === 2)" class="menu-group">
                <button @click="editor.chain().focus().toggleBulletList().run()"
                    :class="{ 'is-active': editor.isActive('bulletList') }" class="menu-button" title="无序列表">
                    <font-awesome-icon :icon="['fas', 'list-ul']" />
                </button>
                <button @click="editor.chain().focus().toggleOrderedList().run()"
                    :class="{ 'is-active': editor.isActive('orderedList') }" class="menu-button" title="有序列表">
                    <font-awesome-icon :icon="['fas', 'list-ol']" />
                </button>
                <button @click="editor.chain().focus().toggleBlockquote().run()"
                    :class="{ 'is-active': editor.isActive('blockquote') }" class="menu-button" title="引用">
                    <font-awesome-icon :icon="['fas', 'quote-left']" />
                </button>
                <button @click="editor.chain().focus().toggleCodeBlock().run()"
                    :class="{ 'is-active': editor.isActive('codeBlock') }" class="menu-button" title="代码块">
                    <font-awesome-icon :icon="['fas', 'file-code']" />
                </button>
            </div>

            <div v-if="!isMobile || (isMobile && activeCategoryIndex === 3)" class="menu-group">
                <button @click="setLink" :class="{ 'is-active': editor.isActive('link') }" class="menu-button"
                    title="链接">
                    <font-awesome-icon :icon="['fas', 'link']" />
                </button>
                <button @click="openImageUpload" class="menu-button" title="图片">
                    <font-awesome-icon :icon="['fas', 'image']" />
                </button>
                <button @click="editor.chain().focus().undo().run()" :disabled="!editor.can().undo()"
                    class="menu-button" title="撤销">
                    <font-awesome-icon :icon="['fas', 'undo']" />
                </button>
                <button @click="editor.chain().focus().redo().run()" :disabled="!editor.can().redo()"
                    class="menu-button" title="重做">
                    <font-awesome-icon :icon="['fas', 'redo']" />
                </button>
            </div>
        </div>

        <!-- 移动设备菜单分类切换 -->
        <div v-if="isMobile && isEditable" class="mobile-menu-categories w-full flex justify-center mb-2">
            <button v-for="(category, index) in menuCategories" :key="index" @click="activeCategoryIndex = index"
                :class="[
                    'px-2 py-1 text-xs rounded-md mx-1',
                    activeCategoryIndex === index
                        ? 'bg-blue-500 text-white'
                        : 'bg-gray-200 text-gray-700'
                ]">
                {{ category }}
            </button>
        </div>

        <div class="ml-auto">
            <button @click="$emit('toggle-edit')" class="menu-button" :title="isEditable ? '预览模式' : '编辑模式'">
                <font-awesome-icon :icon="['fas', isEditable ? 'eye' : 'pen']" />
                {{ isEditable ? '预览' : '编辑' }}
            </button>
        </div>

        <!-- 图片上传对话框 -->
        <div v-if="showImageUpload" class="image-upload-modal">
            <div class="image-upload-modal-content">
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
                </div>
                <div class="flex justify-end space-x-2">
                    <button @click="cancelImageUpload" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md">
                        取消
                    </button>
                    <button @click="insertImage" class="px-4 py-2 bg-blue-600 text-white rounded-md"
                        :disabled="!(imageUrl || uploadedImageUrl)">
                        插入
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

// 定义props
const props = defineProps({
    editor: {
        type: Object,
        required: true
    },
    isEditable: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['toggle-edit']);

// 响应式变量
const isMobile = ref(false);
const activeCategoryIndex = ref(0);
const menuCategories = ['格式', '标题', '列表', '工具'];

// 图片上传相关
const showImageUpload = ref(false);
const imageSource = ref('url');
const imageUrl = ref('');
const uploadedImageUrl = ref('');
const uploadProgress = ref(0);
const uploadError = ref('');

// 检测设备类型函数
const checkDeviceType = () => {
    isMobile.value = window.innerWidth < 768;
};

// 监听窗口大小变化
onMounted(() => {
    checkDeviceType();
    window.addEventListener('resize', checkDeviceType);
});

onUnmounted(() => {
    window.removeEventListener('resize', checkDeviceType);
});

// 链接处理
const setLink = () => {
    const previousUrl = props.editor.getAttributes('link').href;
    const url = window.prompt('输入链接URL', previousUrl);

    if (url === null) {
        return;
    }

    if (url === '') {
        props.editor.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }

    props.editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
};

// 图片处理函数
const openImageUpload = () => {
    showImageUpload.value = true;
    imageUrl.value = '';
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
        headers: {
            'Content-Type': 'multipart/form-data'
        },
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
        props.editor.chain().focus().setImage({ src: url }).run();
        showImageUpload.value = false;
    }
};
</script>

<style scoped>
.editor-menu-bar {
    display: flex;
    flex-wrap: wrap;
    overflow-x: auto;
}

.menu-container {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
}

.menu-group {
    display: flex;
    gap: 0.25rem;
    margin-right: 0.5rem;
}

.menu-button {
    padding: 0.5rem;
    border-radius: 0.25rem;
    background: transparent;
    border: none;
    color: #4b5563;
    cursor: pointer;
    font-size: 0.875rem;
    display: inline-flex;
    align-items: center;
}

.menu-button:hover {
    background-color: #e5e7eb;
}

.menu-button.is-active {
    background-color: #dbeafe;
    color: #2563eb;
}

.menu-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.divider {
    width: 1px;
    align-self: stretch;
    background-color: #d1d5db;
    margin: 0 0.25rem;
}

/* 图片上传模态框样式 */
.image-upload-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 50;
}

.image-upload-modal-content {
    background-color: white;
    border-radius: 0.5rem;
    padding: 1.5rem;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* 移动设备适配 */
@media (max-width: 768px) {
    .mobile-menu-categories {
        display: flex;
        margin-bottom: 0.5rem;
    }

    .menu-group {
        flex-wrap: nowrap;
        overflow-x: auto;
        width: 100%;
        justify-content: center;
    }
}
</style>
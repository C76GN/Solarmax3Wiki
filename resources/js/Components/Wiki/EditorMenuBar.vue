<template>
    <div class="editor-menu-bar bg-gray-100 p-2 flex flex-wrap gap-1 rounded-t-lg border-b">
        <template v-if="isEditable">
            <button @click="editor.chain().focus().toggleBold().run()" :class="{ 'is-active': editor.isActive('bold') }"
                class="menu-button" title="粗体">
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
            <button @click="editor.chain().focus().toggleCode().run()" :class="{ 'is-active': editor.isActive('code') }"
                class="menu-button" title="内联代码">
                <font-awesome-icon :icon="['fas', 'code']" />
            </button>

            <span class="divider"></span>

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

            <span class="divider"></span>

            <button @click="editor.chain().focus().toggleBulletList().run()"
                :class="{ 'is-active': editor.isActive('bulletList') }" class="menu-button" title="无序列表">
                <font-awesome-icon :icon="['fas', 'list-ul']" />
            </button>
            <button @click="editor.chain().focus().toggleOrderedList().run()"
                :class="{ 'is-active': editor.isActive('orderedList') }" class="menu-button" title="有序列表">
                <font-awesome-icon :icon="['fas', 'list-ol']" />
            </button>

            <span class="divider"></span>

            <button @click="editor.chain().focus().toggleBlockquote().run()"
                :class="{ 'is-active': editor.isActive('blockquote') }" class="menu-button" title="引用">
                <font-awesome-icon :icon="['fas', 'quote-left']" />
            </button>
            <button @click="editor.chain().focus().toggleCodeBlock().run()"
                :class="{ 'is-active': editor.isActive('codeBlock') }" class="menu-button" title="代码块">
                <font-awesome-icon :icon="['fas', 'file-code']" />
            </button>

            <span class="divider"></span>

            <button @click="setLink" :class="{ 'is-active': editor.isActive('link') }" class="menu-button" title="链接">
                <font-awesome-icon :icon="['fas', 'link']" />
            </button>
            <button @click="addImage" class="menu-button" title="图片">
                <font-awesome-icon :icon="['fas', 'image']" />
            </button>

            <span class="divider"></span>

            <button @click="editor.chain().focus().undo().run()" :disabled="!editor.can().undo()" class="menu-button"
                title="撤销">
                <font-awesome-icon :icon="['fas', 'undo']" />
            </button>
            <button @click="editor.chain().focus().redo().run()" :disabled="!editor.can().redo()" class="menu-button"
                title="重做">
                <font-awesome-icon :icon="['fas', 'redo']" />
            </button>
        </template>

        <div class="ml-auto">
            <button @click="$emit('toggle-edit')" class="menu-button" :title="isEditable ? '预览模式' : '编辑模式'">
                <font-awesome-icon :icon="['fas', isEditable ? 'eye' : 'pen']" />
                {{ isEditable ? '预览' : '编辑' }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

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

// 添加链接
const setLink = () => {
    const previousUrl = props.editor.getAttributes('link').href;
    const url = window.prompt('输入链接URL', previousUrl);

    // 取消操作
    if (url === null) {
        return;
    }

    // 清除链接
    if (url === '') {
        props.editor.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }

    // 设置链接
    props.editor
        .chain()
        .focus()
        .extendMarkRange('link')
        .setLink({ href: url })
        .run();
};

// 添加图片
const addImage = () => {
    const url = window.prompt('输入图片URL');

    if (url) {
        props.editor.chain().focus().setImage({ src: url }).run();
    }
};
</script>

<style scoped>
.editor-menu-bar {
    display: flex;
    flex-wrap: wrap;
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
</style>
<template>
    <div class="wiki-editor relative">
        <!-- 编辑冲突警告 -->
        <div v-if="conflictWarning" class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 001.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">
                        <strong>警告：</strong> {{ conflictWarning }}
                    </p>
                    <div class="mt-2 flex">
                        <button @click="viewDiff"
                            class="mr-2 text-sm font-medium text-red-700 hover:text-red-900 underline">
                            查看差异
                        </button>
                        <button @click="forceUpdate"
                            class="text-sm font-medium text-red-700 hover:text-red-900 underline">
                            强制更新
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 区域锁定信息 -->
        <WikiSectionLocks :pageId="pageId" :sections="getPageSections()" @section-locked="onSectionLocked"
            @section-unlocked="onSectionUnlocked" v-if="pageId && showLockControls" />
        <div v-if="editorMode === 'edit'" class="editor-wrapper">
            <div class="editor-toolbar bg-gray-100 p-2 rounded-t-lg flex items-center space-x-2 flex-wrap">
                <button @click="editor.chain().focus().toggleBold().run()"
                    :class="{ 'is-active': editor.isActive('bold') }" class="p-1 rounded hover:bg-gray-200">
                    <span class="i-bold">B</span>
                </button>
                <button @click="editor.chain().focus().toggleItalic().run()"
                    :class="{ 'is-active': editor.isActive('italic') }" class="p-1 rounded hover:bg-gray-200">
                    <span class="i-italic">I</span>
                </button>
                <button @click="editor.chain().focus().toggleUnderline().run()"
                    :class="{ 'is-active': editor.isActive('underline') }" class="p-1 rounded hover:bg-gray-200">
                    <span class="i-underline">U</span>
                </button>
                <button @click="editor.chain().focus().toggleStrike().run()"
                    :class="{ 'is-active': editor.isActive('strike') }" class="p-1 rounded hover:bg-gray-200">
                    <span class="i-strike">S</span>
                </button>
                <span class="border-l h-6 mx-2"></span>
                <button @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
                    :class="{ 'is-active': editor.isActive('heading', { level: 1 }) }"
                    class="p-1 rounded hover:bg-gray-200">
                    H1
                </button>
                <button @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                    :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }"
                    class="p-1 rounded hover:bg-gray-200">
                    H2
                </button>
                <button @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                    :class="{ 'is-active': editor.isActive('heading', { level: 3 }) }"
                    class="p-1 rounded hover:bg-gray-200">
                    H3
                </button>
                <span class="border-l h-6 mx-2"></span>
                <button @click="editor.chain().focus().toggleBulletList().run()"
                    :class="{ 'is-active': editor.isActive('bulletList') }" class="p-1 rounded hover:bg-gray-200">
                    <span class="i-list">•</span>
                </button>
                <button @click="editor.chain().focus().toggleOrderedList().run()"
                    :class="{ 'is-active': editor.isActive('orderedList') }" class="p-1 rounded hover:bg-gray-200">
                    <span class="i-list-ol">1.</span>
                </button>
                <button @click="editor.chain().focus().toggleCodeBlock().run()"
                    :class="{ 'is-active': editor.isActive('codeBlock') }" class="p-1 rounded hover:bg-gray-200">
                    <span class="i-code">{ }</span>
                </button>
                <button @click="editor.chain().focus().toggleBlockquote().run()"
                    :class="{ 'is-active': editor.isActive('blockquote') }" class="p-1 rounded hover:bg-gray-200">
                    <span class="i-quote">"</span>
                </button>
                <span class="border-l h-6 mx-2"></span>
                <button @click="setLink" class="p-1 rounded hover:bg-gray-200">
                    <span class="i-link">🔗</span>
                </button>
                <button @click="addImage" class="p-1 rounded hover:bg-gray-200">
                    <span class="i-image">🖼️</span>
                </button>
                <button @click="addWikiLink" class="p-1 rounded hover:bg-gray-200">
                    <span class="i-wikilink">[[W]]</span>
                </button>
                <span class="border-l h-6 mx-2"></span>
                <button @click="editor.chain().focus().undo().run()" class="p-1 rounded hover:bg-gray-200">
                    <span class="i-undo">↩️</span>
                </button>
                <button @click="editor.chain().focus().redo().run()" class="p-1 rounded hover:bg-gray-200">
                    <span class="i-redo">↪️</span>
                </button>
                <span class="border-l h-6 mx-2"></span>
                <button @click="toggleDiscussionPanel" class="p-1 rounded hover:bg-gray-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
                <button @click="toggleSectionLockControls" class="p-1 rounded hover:bg-gray-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
                <div class="ml-auto">
                    <button @click="toggleMode" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">
                        预览
                    </button>
                </div>
            </div>

            <div class="editor-content p-4 border border-gray-300 rounded-b-lg min-h-[300px]">
                <editor-content :editor="editor" class="prose max-w-none" />
            </div>

            <div v-if="autoSaveStatus" class="mt-2 text-sm text-gray-500 auto-save-status"
                :class="{ 'auto-save-flash': autoSaveFlash }">
                {{ autoSaveStatus }}
            </div>
            <div v-if="currentEditors.length > 0" class="mt-2 text-sm text-blue-500">
                <span class="font-medium">当前编辑者：</span>{{ currentEditors.join('、') }}
            </div>
        </div>

        <div v-else-if="editorMode === 'preview'" class="preview-mode">
            <div class="editor-toolbar bg-gray-100 p-2 rounded-t-lg flex justify-between">
                <h3 class="font-medium">预览模式</h3>
                <button @click="toggleMode" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">
                    编辑
                </button>
            </div>
            <div class="preview-content p-4 border border-gray-300 rounded-b-lg min-h-[300px] prose max-w-none"
                v-html="previewHtml"></div>
        </div>

        <WikiLinkAutocomplete v-if="showAutocomplete" :show="showAutocomplete" :query="autocompleteQuery"
            :editor="editor" @select="insertWikiLink" @hide="hideAutocomplete" :style="autocompletePosition" />

        <div v-if="showLinkMenu" class="fixed bg-white p-3 shadow-lg rounded border border-gray-200 z-50"
            :style="linkMenuPosition">
            <div class="mb-2">
                <input ref="linkInput" v-model="linkUrl" type="text" placeholder="输入链接URL..."
                    class="w-full p-2 border border-gray-300 rounded" @keydown.enter="confirmLink" />
            </div>
            <div class="flex justify-end space-x-2">
                <button @click="hideLinkMenu" class="px-3 py-1 text-gray-600 hover:bg-gray-100 rounded">
                    取消
                </button>
                <button @click="confirmLink" class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">
                    确认
                </button>
            </div>
        </div>

        <div v-if="showImageMenu" class="fixed bg-white p-3 shadow-lg rounded border border-gray-200 z-50"
            :style="imageMenuPosition">
            <div class="mb-2">
                <input ref="imageUrlInput" v-model="imageUrl" type="text" placeholder="输入图片URL..."
                    class="w-full p-2 border border-gray-300 rounded mb-2" />
                <input v-model="imageAlt" type="text" placeholder="输入替代文本..."
                    class="w-full p-2 border border-gray-300 rounded" />
            </div>
            <div class="flex justify-end space-x-2">
                <button @click="hideImageMenu" class="px-3 py-1 text-gray-600 hover:bg-gray-100 rounded">
                    取消
                </button>
                <button @click="confirmImage" class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">
                    确认
                </button>
            </div>
        </div>
        <!-- 讨论面板 -->
        <div v-if="showDiscussionPanel" class="fixed bottom-0 right-4 w-80 h-96 z-40">
            <WikiDiscussionPanel :pageId="pageId" :editingSection="currentEditingSection"
                @close="showDiscussionPanel = false" />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import WikiLinkAutocomplete from '@/Components/Wiki/WikiLinkAutocomplete.vue';
import WikiDiscussionPanel from '@/Components/Wiki/WikiDiscussionPanel.vue';
import WikiSectionLocks from '@/Components/Wiki/WikiSectionLocks.vue';
import { getExtensions } from '@/extensions';
import { debounce } from 'lodash';
import axios from 'axios';


const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    autoSaveKey: {
        type: String,
        default: ''
    },
    pageId: {
        type: [Number, String],
        default: null
    }
});

const emit = defineEmits(['update:modelValue', 'auto-save', 'content-changed', 'conflict-detected']);

// 基本状态
const editorMode = ref('edit');
const previewHtml = ref('');
const autoSaveStatus = ref('');
const autoSaveFlash = ref(false);
const currentEditors = ref([]);
const conflictWarning = ref('');
const showDiscussionPanel = ref(false);
const showLockControls = ref(false);
const currentEditingSection = ref(null);
const lockedSections = ref([]);

// Wiki链接自动完成
const showAutocomplete = ref(false);
const autocompleteQuery = ref('');
const autocompletePosition = ref({});

// 链接菜单状态
const showLinkMenu = ref(false);
const linkMenuPosition = ref({});
const linkUrl = ref('');
const linkInput = ref(null);

// 图片菜单状态
const showImageMenu = ref(false);
const imageMenuPosition = ref({});
const imageUrl = ref('');
const imageAlt = ref('');
const imageUrlInput = ref(null);

// 初始化编辑器
const editor = useEditor({
    content: props.modelValue || '',  // 确保提供默认值
    extensions: getExtensions({
        placeholder: '开始编辑内容...'
    }),
    editable: true,  // 确保编辑器可编辑
    autofocus: false,  // 关闭自动聚焦，避免初始化问题
    onUpdate: ({ editor }) => {
        const html = editor.getHTML();
        emit('update:modelValue', html);
        autoSave();
        emit('content-changed', html);

        // 检测当前编辑的章节
        detectCurrentSection();
    },
    onSelectionUpdate: ({ editor }) => {
        const { from, to } = editor.state.selection;
        const text = editor.state.doc.textBetween(from - 2, to);
        if (text.startsWith('[[') && !text.includes(']]')) {
            const query = text.slice(2);
            if (query.length > 0) {
                showAutocomplete.value = true;
                autocompleteQuery.value = query;
                const coords = editor.view.coordsAtPos(from);
                autocompletePosition.value = {
                    position: 'absolute',
                    top: `${coords.bottom + window.scrollY}px`,
                    left: `${coords.left}px`,
                    zIndex: 1000
                };
            } else {
                hideAutocomplete();
            }
        } else {
            hideAutocomplete();
        }
    }
});

// 监听内容变化
watch(() => props.modelValue, (newValue) => {
    const isSame = newValue === editor.value?.getHTML();
    if (editor.value && !isSame) {
        editor.value.commands.setContent(newValue, false);
    }
});

// 切换编辑/预览模式
const toggleMode = () => {
    if (editorMode.value === 'edit') {
        previewHtml.value = editor.value?.getHTML() || '';
        editorMode.value = 'preview';
    } else {
        editorMode.value = 'edit';
    }
};

// 链接处理
const setLink = () => {
    const { from, to } = editor.value.state.selection;
    const selectedText = editor.value.state.doc.textBetween(from, to);
    const currentLink = editor.value.getAttributes('link');
    linkUrl.value = currentLink.href || '';

    // 计算链接菜单位置
    const coords = editor.value.view.coordsAtPos(from);
    linkMenuPosition.value = {
        top: `${coords.bottom + window.scrollY + 10}px`,
        left: `${coords.left}px`
    };
    showLinkMenu.value = true;

    // 自动聚焦到输入框
    nextTick(() => {
        linkInput.value?.focus();
    });
};

const hideLinkMenu = () => {
    showLinkMenu.value = false;
    linkUrl.value = '';
};

const confirmLink = () => {
    if (linkUrl.value) {
        editor.value.chain().focus().extendMarkRange('link').setLink({ href: linkUrl.value }).run();
    } else {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
    }
    hideLinkMenu();
};

// 图片处理
const addImage = () => {
    const { from } = editor.value.state.selection;
    const coords = editor.value.view.coordsAtPos(from);
    imageMenuPosition.value = {
        top: `${coords.bottom + window.scrollY + 10}px`,
        left: `${coords.left}px`
    };
    imageUrl.value = '';
    imageAlt.value = '';
    showImageMenu.value = true;

    // 自动聚焦到输入框
    nextTick(() => {
        imageUrlInput.value?.focus();
    });
};

const hideImageMenu = () => {
    showImageMenu.value = false;
    imageUrl.value = '';
    imageAlt.value = '';
};

const confirmImage = () => {
    if (imageUrl.value) {
        editor.value.chain().focus().setImage({
            src: imageUrl.value,
            alt: imageAlt.value
        }).run();
    }
    hideImageMenu();
};

// Wiki链接处理
const addWikiLink = () => {
    // 获取选中的文本作为Wiki链接的标题
    const { from, to } = editor.value.state.selection;
    const selectedText = editor.value.state.doc.textBetween(from, to);
    if (selectedText) {
        editor.value.chain().setWikiLink(selectedText).run();
    } else {
        // 弹出自动完成窗口
        showAutocomplete.value = true;
        autocompleteQuery.value = '';
        // 计算自动完成菜单位置
        const coords = editor.value.view.coordsAtPos(from);
        autocompletePosition.value = {
            position: 'absolute',
            top: `${coords.bottom + window.scrollY}px`,
            left: `${coords.left}px`,
            zIndex: 1000
        };
    }
};

const insertWikiLink = (page) => {
    if (editor.value) {
        editor.value.chain().focus().setWikiLink(page.title).run();
    }
    hideAutocomplete();
};

const hideAutocomplete = () => {
    showAutocomplete.value = false;
    autocompleteQuery.value = '';
};

// 自动保存
// 自动保存
const autoSave = debounce(() => {
    // 本地保存
    if (props.autoSaveKey) {
        const content = editor.value?.getHTML() || '';
        const editorState = editor.value?.getJSON() || {};
        const saveData = {
            content,
            editorState,
            timestamp: new Date().toISOString()
        };
        localStorage.setItem(props.autoSaveKey, JSON.stringify(saveData));
    }

    // 如果有pageId，则进行服务器端保存
    if (props.pageId) {
        saveServerDraft();
    }

    autoSaveStatus.value = `草稿已自动保存 (${new Date().toLocaleTimeString()})`;
    autoSaveFlash.value = true;
    setTimeout(() => {
        autoSaveFlash.value = false;
    }, 1000);

    emit('auto-save', { content: editor.value?.getHTML() });
}, 2000);

// 服务器端保存草稿
const saveServerDraft = async () => {
    if (!props.pageId) return;

    try {
        // 获取当前编辑器内容和其他必要字段
        const content = editor.value?.getHTML() || '';

        // 从父组件获取标题和分类信息
        const draftData = {
            page_id: props.pageId,
            content: content,
            // 这些数据需要从父组件传递
            title: emit('get-title') || document.querySelector('input[name="title"]')?.value || '',
            categories: emit('get-categories') || []
        };

        const response = await axios.post('/wiki/drafts', draftData);

        if (response.data.success) {
            console.log('服务器端草稿保存成功');
        }
    } catch (error) {
        console.error('服务器端草稿保存失败:', error);
    }
};

// 加载保存的内容
const loadSavedContent = () => {
    if (!props.autoSaveKey) return false;
    try {
        const savedData = localStorage.getItem(props.autoSaveKey);
        if (!savedData) return false;
        const parsed = JSON.parse(savedData);
        if (parsed.content && editor.value) {
            editor.value.commands.setContent(parsed.content);
            emit('update:modelValue', parsed.content);
            const savedTime = new Date(parsed.timestamp);
            autoSaveStatus.value = `草稿已加载 (${savedTime.toLocaleString()})`;
            return true;
        }
    } catch (e) {
        console.error('加载草稿失败:', e);
    }
    return false;
};

// 检测当前编辑区域/章节
const detectCurrentSection = () => {
    if (!editor.value) return;

    const { from } = editor.value.state.selection;
    const pos = editor.value.view.domAtPos(from);
    let currentNode = pos.node;

    // 向上查找最近的标题元素
    while (currentNode && currentNode.nodeType === Node.TEXT_NODE ||
        (currentNode.nodeType === Node.ELEMENT_NODE &&
            !['H1', 'H2', 'H3', 'H4', 'H5', 'H6'].includes(currentNode.nodeName))) {
        currentNode = currentNode.parentNode;
    }

    // 如果找到标题元素
    if (currentNode && ['H1', 'H2', 'H3', 'H4', 'H5', 'H6'].includes(currentNode.nodeName)) {
        const headingId = currentNode.id || '';
        const headingText = currentNode.textContent || '';
        currentEditingSection.value = headingText;

        // 检查该区域是否被锁定
        checkSectionLock(headingId);
    } else {
        currentEditingSection.value = '正文内容';
    }
};

// 检查区域锁是否存在
const checkSectionLock = (sectionId) => {
    if (!sectionId) return;

    const isLocked = lockedSections.value.some(lock => lock.section_id === sectionId);
    if (isLocked) {
        // 如果区域被锁定，可以显示警告或禁止编辑
        console.warn(`区域 ${sectionId} 已被锁定`);
    }
};

// 获取页面所有章节
const getPageSections = () => {
    if (!editor.value) return [];

    const sections = [];
    const content = editor.value.getHTML();

    // 创建临时DOM元素解析HTML
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = content;

    // 查找所有标题元素
    const headings = tempDiv.querySelectorAll('h1, h2, h3, h4, h5, h6');
    headings.forEach(heading => {
        sections.push({
            id: heading.id || `heading-${sections.length + 1}`,
            title: heading.textContent,
            level: parseInt(heading.tagName.substring(1))
        });
    });

    return sections;
};

// 处理区域锁定
const onSectionLocked = (sectionId) => {
    // 刷新区域锁定状态
    fetchSectionLocks();
};

const onSectionUnlocked = (sectionId) => {
    // 刷新区域锁定状态
    fetchSectionLocks();
};

// 获取区域锁定状态
const fetchSectionLocks = async () => {
    if (!props.pageId) return;

    try {
        const response = await axios.get(`/wiki/${props.pageId}/section-locks`);
        lockedSections.value = response.data.locks;
    } catch (error) {
        console.error('获取区域锁定状态失败:', error);
    }
};

// 切换讨论面板
const toggleDiscussionPanel = () => {
    showDiscussionPanel.value = !showDiscussionPanel.value;
};

// 切换区域锁定控制
const toggleSectionLockControls = () => {
    showLockControls.value = !showLockControls.value;
};

// 处理冲突警告
const setConflictWarning = (message) => {
    conflictWarning.value = message;
    emit('conflict-detected', true);
};

// 查看差异
const viewDiff = () => {
    // 调用父组件方法查看差异
    emit('view-diff', editor.value?.getHTML());
};

// 强制更新
const forceUpdate = () => {
    // 通知父组件强制更新
    emit('force-update');
    conflictWarning.value = '';
};

// 上传图片
const uploadImage = (file) => {
    const formData = new FormData();
    formData.append('image', file);
    return axios.post('/api/upload-image', formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    }).then(response => {
        const url = response.data.url;
        editor.value.chain().focus().setImage({ src: url }).run();
        return url;
    }).catch(error => {
        console.error('图片上传失败:', error);
        return null;
    });
};

// 检查编辑状态
const checkEditingStatus = async () => {
    if (!props.pageId) return;

    try {
        const response = await axios.get(`/api/wiki/${props.pageId}/status`);
        if (response.data.currentEditors) {
            currentEditors.value = response.data.currentEditors;
        }

        if (response.data.hasBeenModified) {
            setConflictWarning('页面已被他人修改，继续编辑可能会导致冲突');
        }
    } catch (error) {
        console.error('检查编辑状态失败:', error);
    }
};

// 定期通知编辑状态
const notifyEditing = async () => {
    if (!props.pageId) return;

    try {
        await axios.post(`/api/wiki/${props.pageId}/editing`);
    } catch (error) {
        console.error('通知编辑状态失败:', error);
    }
};

// 通知停止编辑
const notifyStoppedEditing = async () => {
    if (!props.pageId) return;

    try {
        await axios.post(`/api/wiki/${props.pageId}/stopped-editing`);
    } catch (error) {
        console.error('通知停止编辑失败:', error);
    }
};

onMounted(() => {
    setTimeout(() => {
        if (editor.value) {
            if (!loadSavedContent() && props.modelValue) {
                editor.value.commands.setContent(props.modelValue);
            }
        }
    }, 100);

    // 设置拖放图片上传
    const editorElement = document.querySelector('.ProseMirror');
    if (editorElement) {
        editorElement.addEventListener('dragover', (e) => {
            e.preventDefault();
        });
        editorElement.addEventListener('drop', (e) => {
            e.preventDefault();
            const files = e.dataTransfer?.files;
            if (files && files.length > 0) {
                const file = files[0];
                if (file.type.startsWith('image/')) {
                    uploadImage(file);
                }
            }
        });
    }

    // 如果有pageId则启动编辑状态检查和区域锁定检查
    if (props.pageId) {
        checkEditingStatus();
        fetchSectionLocks();

        // 设置定期检查
        const intervalId = setInterval(() => {
            notifyEditing();
            checkEditingStatus();
            fetchSectionLocks();
        }, 30000); // 每30秒检查一次

        // 组件卸载时清除定时器
        onBeforeUnmount(() => {
            clearInterval(intervalId);
            notifyStoppedEditing();
        });
    }
});

onBeforeUnmount(() => {
    editor.value?.destroy();
});
</script>

<style>
.wiki-editor {
    position: relative;
}

.editor-content {
    overflow-y: auto;
    background-color: white;
}

.preview-content {
    overflow-y: auto;
    background-color: white;
}

.editor-toolbar button.is-active {
    background-color: #e5e7eb;
    color: #4b5563;
}

.auto-save-status {
    transition: background-color 0.5s ease;
}

.auto-save-flash {
    background-color: #e9ffd9;
    border-radius: 3px;
    padding: 2px 5px;
}

.ProseMirror {
    outline: none;
    min-height: 250px;
}

.ProseMirror p.is-editor-empty:first-child::before {
    color: #adb5bd;
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
}

/* Wiki链接样式 */
.wiki-link {
    color: #0645ad;
    background-color: #eaf3ff;
    padding: 0 2px;
    border-radius: 2px;
    cursor: pointer;
}

.wiki-link:hover {
    text-decoration: underline;
}
</style>
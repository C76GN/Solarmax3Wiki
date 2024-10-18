<template>
    <div class="search relative flex items-center flex-grow h-full mx-4">
        <!-- 输入框 -->
        <input v-model="searchText"
            class="input p-2 h-full border-2 border-camp-black rounded-lg bg-gray-200 transition-all ease-in-out duration-300"
            :class="inputClasses" :placeholder="isExpanded ? '搜索内容' : ''" />
        <!-- 搜索按钮，始终可见 -->
        <button class="btn text-white px-2 py-1 rounded-lg h-full" @click="toggleSearch" :class="buttonClasses">
            <font-awesome-icon :icon="['fas', 'search']" class="iconcolor" />
        </button>
    </div>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const searchText = ref('');
const isExpanded = ref(false);

const toggleSearch = () => {
    isExpanded.value = !isExpanded.value;
    if (isExpanded.value) {
        nextTick(() => {
            document.querySelector('.input').focus();
        });
    } else {
        searchText.value = ''; // 关闭时清空输入框
    }
};

// 计算样式类
const inputClasses = computed(() => ({
    'w-full opacity-100 pointer-enabled': isExpanded.value,
    'w-0 opacity-0 pointer-disabled': !isExpanded.value,
}));

const buttonClasses = computed(() => ({
    'left-0': !isExpanded.value,
    'left-full': isExpanded.value,
}));
</script>

<style scoped>
.search {
    position: relative;
    height: 100%;
    /* 调整搜索栏高度 */
    display: flex;
    align-items: center;
}

.input {
    background-color: #fff;
    border: 0;
    font-size: 14px;
    /* 调整文字大小 */
    padding: 10px;
    /* 调整内边距 */
    height: 100%;
    /* 高度与搜索栏一致 */
    width: 0;
    /* 初始时宽度为 0，隐藏搜索框 */
    opacity: 0;
    /* 初始时透明 */
    transition: width 0.3s ease, opacity 0.3s ease;
    /* 增加透明度和宽度的过渡效果 */
}

.input.w-full {
    width: 100%;
    opacity: 1;
    /* 展开时显示搜索框并完全不透明 */
}

.pointer-disabled {
    pointer-events: none;
    /* 禁用鼠标事件，防止未展开时的交互 */
}

.pointer-enabled {
    pointer-events: auto;
    /* 启用鼠标事件，展开时可以输入 */
}

.btn {
    background-color: transparent;
    border: 0;
    cursor: pointer;
    font-size: 18px;
    position: absolute;
    top: 0;
    height: 100%;
    /* 与搜索栏一致 */
    width: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: left 0.3s ease, transform 0.3s ease;
}

.btn.left-0 {
    left: 0;
    /* 按钮在未展开时在左侧 */
}

.btn.left-full {
    left: calc(100% - 40px);
    /* 展开时按钮移动到右侧末端 */
}

.btn:focus,
.input:focus {
    outline: none;
}

.iconcolor {
    color: #005ba5;
    /* 使用 CSS 变量设置图标颜色 */
}
</style>

<!-- ParentComponent.vue -->
<template>
    <div>
        <h2>示例：自动聚焦输入框</h2>
        <!-- 使用 AutoFocusInput 组件，并绑定 ref -->
        <AutoFocusInput v-model="inputValue" ref="autoFocusInputRef" autofocus />

        <!-- 一个按钮，点击时手动聚焦输入框 -->
        <button @click="focusInput" class="mt-4 p-2 bg-blue-500 text-white rounded">
            聚焦输入框
        </button>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import AutoFocusInput from '@/Components/TextInput.vue';

const inputValue = ref(''); // 用于绑定输入框的值
const autoFocusInputRef = ref(null); // 用于存储 AutoFocusInput 组件的引用

// 手动聚焦输入框
const focusInput = () => {
    if (autoFocusInputRef.value) {
        autoFocusInputRef.value.focus(); // 调用组件暴露的 focus 方法
    }
};
</script>

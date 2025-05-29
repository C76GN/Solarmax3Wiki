<template>
    <!-- 输入框组件 -->
    <input
        class="rounded-md border-gray-300 shadow-sm focus:border-cyan-300 focus:ring-cyan-900 transition-all duration-300 ease-in-out"
        v-model="model" ref="input" @focus="onFocus" @blur="onBlur" />
</template>

<script setup>
import { onMounted, ref } from 'vue';

// 定义组件的双向绑定模型
const model = defineModel({
    type: String,
    required: true,
});

// 创建一个响应式引用，用于访问DOM中的input元素
const input = ref(null);

// 处理输入框获得焦点时的样式变化，添加高亮边框
const onFocus = () => {
    input.value.classList.add('ring-2', 'ring-cyan-500');
};

// 处理输入框失去焦点时的样式变化，移除高亮边框
const onBlur = () => {
    input.value.classList.remove('ring-2', 'ring-cyan-500');
};

// 组件挂载后执行的生命周期钩子
onMounted(() => {
    // 如果input元素有autofocus属性，则自动聚焦
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

// 暴露组件内部的方法，允许父组件通过ref访问
defineExpose({
    focus: () => input.value.focus() // 暴露一个focus方法，用于外部调用使input聚焦
});
</script>

<style scoped>
/* 为输入框添加过渡效果，使样式变化更平滑 */
input {
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}
</style>
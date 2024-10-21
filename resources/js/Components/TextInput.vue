<template>
    <input
        class="rounded-md border-gray-300 shadow-sm focus:border-cyan-300 focus:ring-cyan-900 transition-all duration-300 ease-in-out"
        v-model="model" ref="input" @focus="onFocus" @blur="onBlur" />
</template>

<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

// 处理输入框获得焦点时的样式变化
const onFocus = () => {
    input.value.classList.add('ring-2', 'ring-cyan-500');
};

// 处理输入框失去焦点时的样式变化
const onBlur = () => {
    input.value.classList.remove('ring-2', 'ring-cyan-500');
};

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<style scoped>
/* 添加过渡效果 */
input {
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}
</style>

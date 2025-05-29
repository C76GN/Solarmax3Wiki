<script setup>
import { defineEmits, defineProps } from 'vue';

// 定义事件，用于向父组件传递选中状态
const emit = defineEmits(['update:checked']);

// 定义组件的属性
const props = defineProps({
    // checked：用于指定复选框的当前选中状态，可以是布尔值或数组
    checked: {
        type: [Array, Boolean],
        default: false, // 默认值为 false，适用于布尔类型
    },
    // value：复选框的值（用于复选框组中的识别），可以是字符串、数字或布尔值
    value: {
        type: [String, Number, Boolean],
        default: null,
    },
});

// 定义方法 updateChecked，用于在状态变化时向父组件发送新的 checked 值
const updateChecked = (event) => emit('update:checked', event.target.checked);
</script>

<template>
    <!-- 复选框输入框 -->
    <!-- :value="value" 设置复选框的值 -->
    <!-- :checked="checked" 根据 checked 属性设置当前状态 -->
    <!-- @change="updateChecked"状态变化时触发 updateChecked 方法 -->
    <input type="checkbox" :value="value" :checked="checked" @change="updateChecked" class="rounded border-gray-300 text-sky-400 shadow-sm focus:border-cyan-300 hover:ring-2 hover:ring-cyan-500
    transition duration-300 ease-in-out" />
</template>

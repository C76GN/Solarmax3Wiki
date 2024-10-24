<!-- 复选框组件 -->
<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        type: [Array, Boolean],
        required: true,
    },
    value: {
        type: [String, Number, Boolean], // 明确类型
        default: null,
    },
});

// 使用 computed 直接返回 props.checked，简化 getter 和 setter
const proxyChecked = computed({
    get: () => props.checked,
    set: (val) => emit('update:checked', val),
});
</script>

<template>
    <input type="checkbox" :value="value" v-model="proxyChecked" class="rounded border-gray-300 text-sky-400 shadow-sm focus:border-cyan-300
               hover:ring-2 hover:ring-cyan-500 transition duration-300 ease-in-out" />
</template>

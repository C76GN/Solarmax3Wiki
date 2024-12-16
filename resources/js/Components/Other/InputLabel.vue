// FileName: /var/www/Solarmax3Wiki/resources/js/Components/Other/InputLabel.vue
<!-- 动态显示组件 -->
<script setup>
defineProps({
    value: {
        type: String,
    },
});
</script>

<template>
    <label class="block text-sm font-medium text-gray-700">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>

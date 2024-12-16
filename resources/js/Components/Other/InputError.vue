// FileName: /var/www/Solarmax3Wiki/resources/js/Components/Other/InputError.vue
<!-- 错误消息组件 -->
<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>

<template>
    <div v-show="message">
        <p class="text-base text-red-400">
            {{ message }}
        </p>
    </div>
</template>

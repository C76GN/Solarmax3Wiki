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

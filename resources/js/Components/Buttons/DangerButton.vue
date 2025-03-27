<template>
    <BaseButton variant="danger" v-bind="$attrs">
        <slot />
    </BaseButton>
</template>

<script setup>
import BaseButton from './BaseButton.vue';
</script>
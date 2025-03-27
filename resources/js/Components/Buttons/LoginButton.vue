<template>
    <BaseButton variant="login" fullWidth type="submit" v-bind="$attrs">
        <slot />
    </BaseButton>
</template>

<script setup>
import BaseButton from './BaseButton.vue';
</script>
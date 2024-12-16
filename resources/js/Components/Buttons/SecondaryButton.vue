// FileName: /var/www/Solarmax3Wiki/resources/js/Components/Buttons/SecondaryButton.vue
<!-- 还是按钮 -->
<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="relative mt-4 inline-flex items-center overflow-hidden rounded-md border border-transparent bg-black justify-center px-4 py-2 text-sm font-medium uppercase tracking-widest text-white transition duration-300 ease-in-out hover:bg-gray-900 hover:ring-2 hover:ring-black focus:outline-none active:bg-gray-950 active:scale-95"
    >
        <slot />
    </button>
</template>

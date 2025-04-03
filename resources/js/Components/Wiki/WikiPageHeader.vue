<template>
    <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg mb-6">
        <div class="px-6 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ title }}
                        <span v-if="subtitle" class="text-sm text-gray-500 ml-2">{{ subtitle }}</span>
                    </h1>
                    <p v-if="description" class="mt-1 text-sm text-gray-500">
                        {{ description }}
                    </p>
                </div>
                <div class="flex gap-4">
                    <slot name="actions"></slot>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        required: true
    },
    subtitle: {
        type: String,
        default: ''
    },
    description: {
        type: String,
        default: ''
    }
});
</script>
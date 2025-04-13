<template>
    <!-- Changed position to top-right -->
    <div class="fixed top-6 right-6 z-[100] w-full max-w-sm space-y-4 pointer-events-none">
        <TransitionGroup enter-active-class="transition duration-300 ease-out"
            enter-from-class="transform translate-x-full opacity-0" enter-to-class="transform translate-x-0 opacity-100"
            leave-active-class="transition duration-200 ease-in" leave-from-class="transform translate-x-0 opacity-100"
            leave-to-class="transform translate-x-full opacity-0">
            <div v-for="message in messages" :key="message.id"
                class="flex items-center p-4 rounded-lg shadow-lg backdrop-blur-sm pointer-events-auto w-full" :class="[
                    message.type === 'success' && 'bg-green-500/90 text-white',
                    message.type === 'error' && 'bg-red-500/90 text-white',
                    message.type === 'warning' && 'bg-yellow-500/90 text-black', /* Yellow background with black text for better contrast */
                    message.type === 'info' && 'bg-blue-500/90 text-white'
                ]">
                <!-- Icon Section -->
                <div class="flex-shrink-0 mr-3">
                    <font-awesome-icon v-if="message.type === 'success'" :icon="['fas', 'check-circle']"
                        class="w-5 h-5" />
                    <font-awesome-icon v-else-if="message.type === 'error'" :icon="['fas', 'exclamation-circle']"
                        class="w-5 h-5" />
                    <font-awesome-icon v-else-if="message.type === 'warning'" :icon="['fas', 'exclamation-triangle']"
                        class="w-5 h-5" />
                    <font-awesome-icon v-else :icon="['fas', 'info-circle']" class="w-5 h-5" />
                </div>
                <!-- Message Text Section -->
                <div class="flex-1 text-sm font-medium mr-2">{{ message.text }}</div>
                <!-- Close Button Section -->
                <button @click="removeMessage(message.id)" class="flex-shrink-0 ml-auto p-1 rounded-full -mr-1 -my-1"
                    :class="[
                        message.type === 'warning' ? 'text-black/70 hover:text-black hover:bg-black/10' : 'text-white/80 hover:text-white hover:bg-white/20'
                    ]" aria-label="Close">
                    <font-awesome-icon :icon="['fas', 'times']" class="w-4 h-4" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { TransitionGroup } from 'vue'; // Ensure TransitionGroup is imported if not already
import { usePage, router } from '@inertiajs/vue3';

const messages = ref([]);
let nextId = 0;

const addMessage = (type, text, duration = 5000) => { // Default duration 5 seconds
    const id = nextId++;
    messages.value.push({ id, type, text });
    setTimeout(() => {
        removeMessage(id);
    }, duration);
};

const removeMessage = (id) => {
    const index = messages.value.findIndex(m => m.id === id);
    if (index > -1) {
        messages.value.splice(index, 1);
    }
};

onMounted(() => {
    const page = usePage();
    watch(() => page.props.flash, (flash) => {
        if (flash && flash.message) {
            const { type = 'info', text } = flash.message;
            if (text) {
                addMessage(type, text);
            }
            // Clear the flash message from session after showing it
            // This requires backend cooperation or manual clearing in Inertia event listeners
            if (router && router.page && router.page.props.flash) {
                router.page.props.flash = {}; // Attempt to clear, might need backend adjustment
            }
        }
    }, { immediate: true, deep: true });

    // Watch for general page errors as well
    watch(() => page.props.errors, (errors) => {
        if (errors && errors.general) {
            addMessage('error', errors.general);
            // Clear the general error after showing
            if (router && router.page && router.page.props.errors) {
                router.page.props.errors = { ...router.page.props.errors, general: undefined };
            }
        }
    }, { deep: true });
});

// Expose addMessage to be callable from parent components if needed
defineExpose({
    addMessage
});
</script>
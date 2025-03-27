<template>
    <div>
        <label v-if="label" :class="labelClass" :for="id">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>

        <div class="mt-1">
            <!-- 文本输入 -->
            <input v-if="type === 'text' || type === 'email' || type === 'password' || type === 'number'" :id="id"
                :type="type" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)"
                :class="inputClass" :placeholder="placeholder" :required="required" :disabled="disabled"
                :autocomplete="autocomplete" ref="input" />

            <!-- 文本区域 -->
            <textarea v-else-if="type === 'textarea'" :id="id" :value="modelValue"
                @input="$emit('update:modelValue', $event.target.value)" :class="inputClass" :placeholder="placeholder"
                :required="required" :disabled="disabled" :rows="rows" ref="input"></textarea>

            <!-- 选择框 -->
            <select v-else-if="type === 'select'" :id="id" :value="modelValue"
                @change="$emit('update:modelValue', $event.target.value)" :class="inputClass" :required="required"
                :disabled="disabled" ref="input">
                <option v-if="emptyOption" value="">{{ emptyOption }}</option>
                <slot></slot>
            </select>

            <!-- 复选框 -->
            <div v-else-if="type === 'checkbox'" class="flex items-center">
                <input :id="id" type="checkbox" :checked="modelValue"
                    @change="$emit('update:modelValue', $event.target.checked)"
                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" :disabled="disabled"
                    ref="input" />
                <label v-if="label" :for="id" class="ml-2 text-sm text-gray-700">
                    {{ label }}
                </label>
            </div>
        </div>

        <!-- 错误消息 -->
        <div v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</div>

        <!-- 帮助文本 -->
        <div v-if="helpText" class="mt-1 text-sm text-gray-500">{{ helpText }}</div>
    </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';

const props = defineProps({
    label: String,
    type: {
        type: String,
        default: 'text'
    },
    modelValue: {
        type: [String, Number, Boolean],
        default: ''
    },
    id: String,
    placeholder: String,
    required: Boolean,
    disabled: Boolean,
    error: String,
    helpText: String,
    emptyOption: String,
    rows: {
        type: Number,
        default: 3
    },
    autocomplete: String,
    autofocus: Boolean
});

defineEmits(['update:modelValue']);

const input = ref(null);

const labelClass = computed(() => {
    return 'block text-sm font-medium text-gray-700';
});

const inputClass = computed(() => {
    const baseClass = 'block w-full rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm';
    const errorClass = props.error ? 'border-red-300' : 'border-gray-300';
    return `${baseClass} ${errorClass}`;
});

onMounted(() => {
    if (props.autofocus && input.value) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });
</script>
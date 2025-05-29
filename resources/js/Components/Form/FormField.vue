<template>
    <div>
        <!-- 标签，可选显示 -->
        <label v-if="label" :class="labelClass" :for="id">
            {{ label }}
            <!-- 必填星号 -->
            <span v-if="required" class="text-red-500">*</span>
        </label>

        <div class="mt-1">
            <!-- 文本、邮箱、密码、数字输入框 -->
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
                <!-- 可选的空选项 -->
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

        <!-- 错误消息，可选显示 -->
        <div v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</div>

        <!-- 帮助文本，可选显示 -->
        <div v-if="helpText" class="mt-1 text-sm text-gray-500">{{ helpText }}</div>
    </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';

// 定义组件属性
const props = defineProps({
    label: String, // 标签文本
    type: { // 输入框类型
        type: String,
        default: 'text'
    },
    modelValue: { // 绑定值
        type: [String, Number, Boolean],
        default: ''
    },
    id: String, // 输入框ID
    placeholder: String, // 占位符
    required: Boolean, // 是否必填
    disabled: Boolean, // 是否禁用
    error: String, // 错误消息
    helpText: String, // 帮助文本
    emptyOption: String, // 选择框的空选项文本
    rows: { // 文本区域行数
        type: Number,
        default: 3
    },
    autocomplete: String, // 自动填充
    autofocus: Boolean // 自动聚焦
});

// 定义组件事件
defineEmits(['update:modelValue']);

// 模板引用，用于直接访问DOM元素
const input = ref(null);

// 计算属性：标签的CSS类
const labelClass = computed(() => {
    return 'block text-sm font-medium text-gray-700';
});

// 计算属性：输入框的CSS类
const inputClass = computed(() => {
    const baseClass = 'block w-full rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm';
    const errorClass = props.error ? 'border-red-300' : 'border-gray-300'; // 根据是否有错误添加边框颜色
    return `${baseClass} ${errorClass}`;
});

// 组件挂载后执行
onMounted(() => {
    // 如果设置了自动聚焦，则聚焦输入框
    if (props.autofocus && input.value) {
        input.value.focus();
    }
});

// 暴露组件方法给父组件调用
defineExpose({ focus: () => input.value?.focus() });
</script>
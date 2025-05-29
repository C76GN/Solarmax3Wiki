<template>
    <div class="form-control">
        <!-- 输入框元素，双向绑定 modelValue -->
        <input :type="type" :value="modelValue" :required="required"
            @input="$emit('update:modelValue', $event.target.value)" ref="input" />
        <label>
            <!-- 标签字符动态显示动画 -->
            <span v-for="(char, index) in label.split('')" :key="index" :style="{ transitionDelay: `${index * 50}ms` }">
                {{ char }}
            </span>
        </label>
    </div>
</template>

<script setup>
import { ref, defineExpose } from 'vue';

// 定义组件接收的属性
const props = defineProps({
    label: {
        type: String,
        required: true,
    },
    type: {
        type: String,
        default: 'text',
    },
    modelValue: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    }
})

// 暴露 input 元素，以便父组件可以调用其 focus 方法
const input = ref(null);
defineExpose({
    focus: () => {
        input.value.focus();
    }
});
</script>

<style scoped>
.form-control {
    position: relative;
    margin: 20px 0 40px;
    width: 100%;
}

.form-control input {
    background-color: transparent;
    border: 0;
    border-bottom: 2px solid #fff;
    display: block;
    width: 100%;
    padding: 15px 0;
    font-size: 18px;
    color: #fff;
}

.form-control input:focus,
.form-control input:valid {
    outline: none;
    box-shadow: none;
    border-bottom-color: lightblue;
}

.form-control label {
    position: absolute;
    top: 15px;
    left: 0;
    pointer-events: none;
    color: white;
}

.form-control label span {
    display: inline-block;
    font-size: 18px;
    transition: 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.form-control input:focus+label span,
.form-control input:valid+label span {
    color: lightblue;
    transform: translateY(-30px);
}

input:-webkit-autofill {
    background-color: transparent !important;
    -webkit-box-shadow: 0 0 0px 1000px transparent inset;
    transition: background-color 5000s ease-in-out 0s;
    -webkit-text-fill-color: white !important;
}
</style>
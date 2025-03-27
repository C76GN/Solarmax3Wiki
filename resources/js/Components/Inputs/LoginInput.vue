<template>
    <div class="form-control">
        <!-- 使用 :value 绑定 modelValue 并监听 input 事件发射 update:modelValue -->
        <input :type="type" :value="modelValue" :required="required"
            @input="$emit('update:modelValue', $event.target.value)" ref="input" />
        <label>
            <!-- 动态显示 label 的波浪效果 -->
            <span v-for="(char, index) in label.split('')" :key="index" :style="{ transitionDelay: `${index * 50}ms` }">
                {{ char }}
            </span>
        </label>
    </div>
</template>

<script setup>
import { ref, defineExpose } from 'vue';

const props = defineProps({
    label: {
        type: String,
        required: true,  // 输入框的标签，比如 "Email" 或 "Password"
    },
    type: {
        type: String,
        default: 'text',  // 输入框的类型，比如 "text" 或 "password"
    },
    modelValue: {
        type: String,
        default: '',  // 双向绑定值
    },
    required: {
        type: Boolean,
        default: false,  // 是否为必填项
    }
})

// 暴露 input 元素
const input = ref(null);
defineExpose({
    focus: () => {
        input.value.focus();
    }
});
</script>

<style scoped>
/* 样式保持不变 */
</style>


<style scoped>
/* 保持原来的样式 */
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
    /* 去掉蓝色边框 */
    box-shadow: none;
    border-bottom-color: lightblue;
    /* 只保留底部的下划线变化 */
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
    /* 保持字体颜色为白色 */
}
</style>

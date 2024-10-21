<template>
    <h1 class="text-center text-white text-2xl mb-8">Please Login</h1>
    <form @submit.prevent="submitForm">
        <!-- Email 输入框 -->
        <InputField label="Email" type="text" v-model="email" required />

        <!-- Password 输入框 -->
        <InputField label="Password" type="password" v-model="password" required />

        <!-- Login 按钮 -->
        <PrimaryButton type="submit">
            Login
        </PrimaryButton>

        <p class="text-center mt-4 text-white">
            Don't have an account? <a href="#" class="text-cyan-300">Register</a>
        </p>
    </form>
</template>

<script setup>
import { ref } from 'vue'
import InputField from '@/Components/Solarmax3Wiki/LoginInput.vue' // 引入输入框组件
import Button from '@/Components/Solarmax3Wiki/Buttons/LoginButtontext.vue' // 引入按钮组件
import PrimaryButton from '@/Components/Solarmax3Wiki/Buttons/LoginButton.vue';
// 双向绑定 Email 和 Password
const email = ref('')
const password = ref('')

// 表单提交函数
const submitForm = () => {
    console.log('Email:', email.value)
    console.log('Password:', password.value)
    // 你可以在这里处理实际的登录逻辑
}
</script>

<style scoped></style>

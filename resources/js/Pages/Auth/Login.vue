<template>
    <GuestLayout>
        <Head title="Log in" />
        <h1 class="text-center text-white text-2xl mb-8">Login</h1>
        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>
        <!-- 登录表单 -->
        <form @submit.prevent="submit">
            <div>
                <!-- 邮箱输入框 -->
                <LoginInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required
                    ref="emailInput" autocomplete="username" label="Email" />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>
            <div class="mt-4">
                <!-- 密码输入框 -->
                <LoginInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" required
                    autocomplete="current-password" label="Password" />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>
            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-cyan-300">Remember me</span>
                </label>
            </div>
            <div class="mt-6 flex flex-col items-center justify-center">
                <Button variant="login" fullWidth type="submit" :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing">
                    Log in
                </Button>
                <!-- 忘记密码和注册链接 -->
                <Link v-if="canResetPassword" :href="route('password.request')"
                    class="mt-4 text-white text-center text-sm underline hover:text-cyan-300 focus:outline-none">
                Forgot your password?
                </Link>
                <Link :href="route('register')"
                    class="mt-2 text-white text-center text-sm underline hover:text-cyan-300 focus:outline-none">
                Don't have an account? Sign up here
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import Checkbox from '@/Components/Other/Checkbox.vue';
import GuestLayout from '@/Layouts/UserLayouts/GuestLayout.vue';
import InputError from '@/Components/Other/InputError.vue';
import Button from '@/Components/Buttons/Button.vue';
import LoginInput from '@/Components/Inputs/LoginInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
// 定义组件的 props，控制是否显示重置密码链接和状态信息
defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
// 用于引用邮箱输入框，以便在页面加载时聚焦
const emailInput = ref(null);
// 创建表单对象，管理邮箱、密码和记住我字段
const form = useForm({
    email: '',
    password: '',
    remember: false,
});
// 提交登录表单到后端
const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
// 在组件挂载后，自动聚焦到邮箱输入框
onMounted(() => {
    if (emailInput.value) {
        emailInput.value.focus();
    }
});
</script>
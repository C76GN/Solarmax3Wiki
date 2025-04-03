<template>
    <GuestLayout>

        <Head title="Log in" />
        <h1 class="text-center text-white text-2xl mb-8">Login</h1>
        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <!-- 使用自定义的 LoginInput 组件，并传递 label 属性，绑定 ref -->
                <LoginInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required
                    ref="emailInput" autocomplete="username" label="Email" />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
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

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const emailInput = ref(null);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

// 页面加载时自动聚焦到第一个输入框
onMounted(() => {
    if (emailInput.value) {
        emailInput.value.focus(); // 手动聚焦到邮箱输入框
    }
});
</script>

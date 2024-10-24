<template>
    <GuestLayout>

        <Head title="Forgot Password" />
        <h1 class="text-center text-white text-2xl mb-8">Forgot Password</h1>

        <div class="mb-4 text-sm text-white text-center">
            Forgot your password? No problem. Just let us know your email
            address and we will email you a password reset link that will allow
            you to choose a new one.
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600 text-center">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <!-- 使用自定义的 LoginInput 组件，并传递 label 属性 -->
                <LoginInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required
                    ref="emailInput" autocomplete="username" label="Email" />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-6 flex flex-col items-center justify-center">
                <LoginButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Email Password Reset Link
                </LoginButton>
            </div>
        </form>
    </GuestLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import LoginButton from '@/Components/Solarmax3Wiki/Buttons/LoginButton.vue';
import LoginInput from '@/Components/Solarmax3Wiki/LoginInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const emailInput = ref(null);

const submit = () => {
    form.post(route('password.email'));
};

// 页面加载时自动聚焦到第一个输入框
onMounted(() => {
    if (emailInput.value) {
        emailInput.value.focus(); // 手动聚焦到邮箱输入框
    }
});
</script>

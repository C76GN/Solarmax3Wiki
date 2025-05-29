<template>
    <GuestLayout>

        <Head title="Reset Password" />
        <h1 class="text-center text-white text-2xl mb-8">Reset Password</h1>

        <form @submit.prevent="submit">
            <div>
                <!-- 使用自定义的 LoginInput 组件，并传递 label 属性 -->
                <LoginInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required
                    ref="emailInput" autocomplete="username" label="Email" />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <LoginInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" required
                    autocomplete="new-password" label="Password" />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <LoginInput id="password_confirmation" type="password" class="mt-1 block w-full"
                    v-model="form.password_confirmation" required autocomplete="new-password"
                    label="Confirm Password" />

                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="mt-6 flex flex-col items-center justify-center">
                <Button variant="login" fullWidth type="submit" :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing">
                    Reset Password
                </Button>
            </div>
        </form>
    </GuestLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import GuestLayout from '@/Layouts/UserLayouts/GuestLayout.vue';
import InputError from '@/Components/Other/InputError.vue';
import Button from '@/Components/Buttons/Button.vue';
import LoginInput from '@/Components/Inputs/LoginInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const emailInput = ref(null);

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

// 页面加载时自动聚焦到第一个输入框
onMounted(() => {
    if (emailInput.value) {
        emailInput.value.focus(); // 手动聚焦到邮箱输入框
    }
});
</script>

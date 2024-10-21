<template>
    <GuestLayout>

        <Head title="Register" />
        <h1 class="text-center text-white text-2xl mb-8">Register</h1>

        <form @submit.prevent="submit">
            <div>
                <!-- 使用自定义的 LoginInput 组件，并传递 label 属性，绑定 ref -->
                <LoginInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus
                    ref="nameInput" autocomplete="name" label="Name" />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <LoginInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required
                    autocomplete="username" label="Email" />

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

            <!-- 修改此处，增加 margin-top 和 flex 布局 -->
            <div class="mt-6 flex flex-col items-center justify-center">
                <LoginButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Register
                </LoginButton>

                <!-- 确保文字在父容器中居中 -->
                <Link :href="route('login')"
                    class="mt-4 text-white text-center text-sm underline hover:text-cyan-300 focus:outline-none">
                Already registered?
                </Link>
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
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const nameInput = ref(null);

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

// 页面加载时自动聚焦到第一个输入框
onMounted(() => {
    nameInput.value.focus();
});
</script>

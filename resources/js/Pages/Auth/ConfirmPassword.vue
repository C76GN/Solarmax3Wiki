<template>
    <GuestLayout>

        <Head title="Confirm Password" />
        <h1 class="text-center text-white text-2xl mb-8">Confirm Password</h1>

        <div class="mb-4 text-sm text-white text-center">
            This is a secure area of the application. Please confirm your
            password before continuing.
        </div>

        <form @submit.prevent="submit">
            <div>
                <!-- 使用自定义的 LoginInput 组件，并传递 label 属性 -->
                <LoginInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" required
                    autocomplete="current-password" label="Password" autofocus />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-6 flex flex-col items-center justify-center">
                <LoginButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Confirm
                </LoginButton>
            </div>
        </form>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import LoginButton from '@/Components/Solarmax3Wiki/Buttons/LoginButton.vue';
import LoginInput from '@/Components/Solarmax3Wiki/LoginInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

// FileName: /var/www/Solarmax3Wiki/resources/js/Pages/Auth/ConfirmPassword.vue
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
                <Button variant="login" fullWidth type="submit" :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing">
                    Confirm
                </Button>
            </div>
        </form>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/UserLayouts/GuestLayout.vue';
import InputError from '@/Components/Other/InputError.vue';
import Button from '@/Components/Buttons/Button.vue';
import LoginInput from '@/Components/Inputs/LoginInput.vue';
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

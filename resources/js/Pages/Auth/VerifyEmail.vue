<template>
    <GuestLayout>

        <Head title="Email Verification" />
        <h1 class="text-center text-white text-2xl mb-8">Email Verification</h1>

        <div class="mb-4 text-sm text-white text-center">
            Thanks for signing up! Before getting started, could you verify your
            email address by clicking on the link we just emailed to you? If you
            didn't receive the email, we will gladly send you another.
        </div>

        <div class="mb-4 text-sm font-medium text-green-600 text-center" v-if="verificationLinkSent">
            A new verification link has been sent to the email address you
            provided during registration.
        </div>

        <form @submit.prevent="submit">
            <div class="mt-6 flex flex-col items-center justify-center">
                <LoginButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Resend Verification Email
                </LoginButton>

                <Link :href="route('logout')" method="post" as="button"
                    class="mt-4 text-white text-center text-sm underline hover:text-cyan-300 focus:outline-none">
                Log Out
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>

<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import LoginButton from '@/Components/Solarmax3Wiki/Buttons/LoginButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

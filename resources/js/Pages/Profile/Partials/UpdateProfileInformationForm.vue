<!-- 修改信息 -->
<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue'; // 导入 ref 和 onMounted

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});

// 创建 ref 来引用输入框
const nameInput = ref(null);
const emailInput = ref(null);

// 页面加载时手动聚焦到姓名输入框
onMounted(() => {
    if (nameInput.value) {
        nameInput.value.focus(); // 手动聚焦到姓名输入框
    }
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-white">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-white">
                Update your account's profile information and email address.
            </p>
        </header>

        <form @submit.prevent="form.patch(route('profile.update'))" class="mt-6 space-y-6">
            <div>
                <InputLabel class="text-white" for="name" value="Name" />

                <TextInput id="name" type="text" class="bg-cyan-950 text-gray-50 mt-1 block w-full" v-model="form.name"
                    required autocomplete="name" ref="nameInput" /> <!-- 绑定 ref -->

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel class="text-white" for="email" value="Email" />

                <TextInput id="email" type="email" class="bg-cyan-950 text-gray-50 mt-1 block w-full"
                    v-model="form.email" required autocomplete="username" ref="emailInput" /> <!-- 绑定 ref -->

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-white">
                    Your email address is unverified.
                    <Link :href="route('verification.send')" method="post" as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Click here to re-send the verification email.
                    </Link>
                </p>

                <div v-show="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="text-sm text-white">
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>

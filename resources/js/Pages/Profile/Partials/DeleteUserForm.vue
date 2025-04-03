// FileName: /var/www/Solarmax3Wiki/resources/js/Pages/Profile/Partials/DeleteUserForm.vue
<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-white">Delete Account</h2>
            <p class="mt-1 text-sm text-white">
                Once your account is deleted, all of its resources and data will be permanently deleted.
                Before deleting your account, please download any data or information that you wish to retain.
            </p>
        </header>

        <!-- 触发删除账户的按钮 -->
        <Button variant="danger" @click="confirmUserDeletion">Delete Account</Button>

        <!-- 使用新的 Modal 组件 -->
        <Modal :show="confirmingUserDeletion" @close="closeModal" maxWidth="lg">
            <div class="p-6">
                <h2 class="text-lg font-medium text-white">Are you sure you want to delete your account?</h2>
                <p class="mt-1 text-sm text-white">
                    Once your account is deleted, all of its resources and data will be permanently deleted.
                    Please enter your password to confirm you would like to permanently delete your account.
                </p>

                <div class="mt-6">
                    <InputLabel for="password" value="Password" class="sr-only" />
                    <TextInput id="password" ref="passwordInput" v-model="form.password" type="password"
                        class="mt-1 block w-3/4 bg-cyan-950 text-gray-50" placeholder="Password"
                        @keyup.enter="deleteUser" />
                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <Button variant="secondary" @click="closeModal">Cancel</Button>
                    <Button variant="danger" class="ms-3" :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing" @click="deleteUser">
                        Delete Account
                    </Button>
                </div>
            </div>
        </Modal>
    </section>
</template>

<script setup>
import Button from '@/Components/Buttons/Button.vue';
import InputError from '@/Components/Other/InputError.vue';
import InputLabel from '@/Components/Other/InputLabel.vue';
import Modal from '@/Components/Modal/Modal.vue';
import TextInput from '@/Components/Inputs/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

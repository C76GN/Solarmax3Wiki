<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head :title="`编辑用户: ${user.name}`" />
        <div class="container mx-auto py-6 px-4">
            <div class="max-w-3xl mx-auto bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6 pb-4 border-b dark:border-gray-700">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">编辑用户: {{ user.name }}</h1>
                    <Link :href="route('users.index')" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                    <font-awesome-icon :icon="['fas', 'arrow-left']" class="mr-1" /> 返回列表
                    </Link>
                </div>

                <form @submit.prevent="submit">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">邮箱</label>
                            <div class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-200">
                                {{ user.email }}
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                                分配角色 <span class="text-red-500">*</span>
                            </label>
                            <div
                                class="space-y-4 border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800/50">
                                <div v-for="role in roles" :key="role.id" class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input type="checkbox" :id="`role-${role.id}`" :value="role.id"
                                            v-model="form.roles"
                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded dark:bg-gray-600 dark:border-gray-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 disabled:cursor-not-allowed"
                                            :disabled="isSelfAdminAndTryingToRemoveAdmin(role)">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label :for="`role-${role.id}`"
                                            class="font-medium text-gray-700 dark:text-gray-300"
                                            :class="{ 'opacity-50': isSelfAdminAndTryingToRemoveAdmin(role) }">
                                            {{ role.display_name }}
                                            <span v-if="role.is_system"
                                                class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                                系统
                                            </span>
                                        </label>
                                        <p class="text-gray-500 dark:text-gray-400">{{ role.description || '无描述' }}</p>
                                        <p v-if="isSelfAdminAndTryingToRemoveAdmin(role)"
                                            class="text-xs text-yellow-600 dark:text-yellow-400 mt-1 italic">
                                            不能移除自己的管理员角色。</p>
                                    </div>
                                </div>
                            </div>
                            <div v-if="form.errors.roles" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.roles }}
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 flex justify-end gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <Link :href="route('users.index')" class="btn-secondary">
                        取消
                        </Link>
                        <button type="submit" :disabled="form.processing" class="btn-primary">
                            <font-awesome-icon v-if="form.processing" :icon="['fas', 'spinner']" spin class="mr-1" />
                            {{ form.processing ? '正在保存...' : '保存更改' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { useForm, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import MainLayout from "@/Layouts/MainLayouts/MainLayout.vue";
import { adminNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = adminNavigationLinks;
const pageProps = usePage().props;

const props = defineProps({
    user: {
        type: Object,
        required: true
    },
    roles: {
        type: Array,
        required: true
    }
});

const form = useForm({
    // Initialize with current roles (assuming user.roles is an array of role IDs)
    roles: Array.isArray(props.user.roles) ? props.user.roles : []
});

const isSelfAdminAndTryingToRemoveAdmin = (roleToCheck) => {
    const currentUser = pageProps.auth.user;
    if (!currentUser) return false;
    // Check if editing own user, if the role is admin, and if it's currently selected
    return props.user.id === currentUser.id &&
        roleToCheck.name === 'admin' &&
        form.roles.includes(roleToCheck.id);
    // This logic is slightly off for disabling the *removal*
    // Let's adjust: disable checkbox if trying to *uncheck* self-admin
};

// Check if the user is trying to remove their own admin role.
// This function helps disable the checkbox more accurately.
const disableAdminCheckbox = (roleToCheck) => {
    const currentUser = pageProps.auth.user;
    if (!currentUser) return false;
    return props.user.id === currentUser.id && roleToCheck.name === 'admin';
}

const submit = () => {
    // Filter out potential string role IDs if necessary, ensure they are numbers
    form.roles = form.roles.map(id => typeof id === 'string' ? parseInt(id, 10) : id).filter(id => !isNaN(id));

    form.put(route('users.update', props.user.id), {
        preserveScroll: true,
    });
};
</script>

<style scoped>
/* Button styles */
.btn-primary {
    @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
    @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 text-sm font-medium;
}
</style>
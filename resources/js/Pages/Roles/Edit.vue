<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head :title="`编辑角色: ${role.display_name}`" />
        <div class="container mx-auto py-6 px-4">
            <div class="max-w-3xl mx-auto bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6 pb-4 border-b dark:border-gray-700">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">编辑角色: {{ role.display_name }}</h1>
                    <Link :href="route('roles.index')" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                    <font-awesome-icon :icon="['fas', 'arrow-left']" class="mr-1" /> 返回列表
                    </Link>
                </div>
                <RoleForm :role="role" :permissions="permissions" submit-button-text="更新角色" />
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import RoleForm from '@/Components/Form/RoleForm.vue'
import MainLayout from "@/Layouts/MainLayouts/MainLayout.vue";
import { adminNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = adminNavigationLinks;

defineProps({
    role: {
        type: Object,
        required: true
    },
    permissions: {
        type: Object,
        required: true
    }
})
</script>
<template>
    <MainLayout :navigationLinks="navigationLinks">

    <div class="container mx-auto py-6">
        <div class="max-w-3xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">创建角色</h1>
            <RoleForm :permissions="permissions" submit-button-text="创建角色" />
        </div>
    </div>
    </MainLayout>
</template>

<script setup>
import RoleForm from '@/Components/Form/RoleForm.vue'
import MainLayout from "@/Layouts/MainLayouts/MainLayout.vue";

import { adminNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = adminNavigationLinks;

defineProps({
    permissions: {
        type: Object,
        required: true
    }
})
</script>

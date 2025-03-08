// FileName: /var/www/Solarmax3Wiki/resources/js/Pages/Roles/Create.vue
<template>
    <MainLayout :navigationLinks="[{ href: '/users', label: '用户管理' }, { href: '/roles', label: '角色管理' }, { href: '/wiki/categories/index', label: '页面分类' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]">

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

defineProps({
    permissions: {
        type: Object,
        required: true
    }
})
</script>

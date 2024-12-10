<template>
    <div class="container mx-auto py-6">
        <div class="max-w-3xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">创建角色</h1>
            <RoleForm :permissions="permissions" submit-button-text="创建角色" />
        </div>
    </div>
</template>

<script setup>
import RoleForm from '@/Components/Form/RoleForm.vue'

defineProps({
    permissions: {
        type: Object,
        required: true
    }
})
</script>
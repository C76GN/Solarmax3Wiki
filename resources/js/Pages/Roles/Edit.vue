<template>
    <div class="container mx-auto py-6">
        <div class="max-w-3xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">编辑角色</h1>
            <RoleForm :role="role" :permissions="permissions" submit-button-text="更新角色" />
        </div>
    </div>
</template>

<script setup>
import RoleForm from '@/Components/Form/RoleForm.vue'

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
// FileName: /var/www/Solarmax3Wiki/resources/js/Pages/Roles/Index.vue
<template>
    <MainLayout :navigationLinks="[{ href: '/users', label: '用户管理' }, { href: '/roles', label: '角色管理' }, { href: '/wiki/categories/index', label: '页面分类' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]">
    <div class="container mx-auto py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">角色管理</h1>
            <Link v-if="$page.props.auth.user.permissions.includes('role.create')" :href="route('roles.create')"
                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition duration-150 ease-in-out">
            创建角色
            </Link>
        </div>

        <!-- 角色列表 -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            角色名称
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            描述
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            权限数量
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            创建时间
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">操作</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="role in roles.data" :key="role.id">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ role.display_name }}
                                </div>
                                <span v-if="role.is_system"
                                    class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    系统
                                </span>
                            </div>
                            <div class="text-sm text-gray-500">{{ role.name }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">{{ role.description || '-' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ role.permissions.length }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ new Date(role.created_at).toLocaleDateString() }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                <Link v-if="!role.is_system && $page.props.auth.user.permissions.includes('role.edit')"
                                    :href="route('roles.edit', role.id)" class="text-blue-600 hover:text-blue-900">
                                编辑
                                </Link>
                                <button
                                    v-if="!role.is_system && $page.props.auth.user.permissions.includes('role.delete')"
                                    @click="confirmDelete(role)" class="text-red-600 hover:text-red-900">
                                    删除
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 分页 -->
        <Pagination :links="roles.links" class="mt-6" />
    </div>
        <ConfirmationModal :show="confirmingDeletion" title="确认删除角色" message="确定要删除该角色吗？此操作将同时移除该角色下所有用户的相关权限，且不可恢复。"
                           @close="confirmingDeletion = false" @confirm="deleteRole" />
    </MainLayout>
    <!-- 删除确认对话框 -->

</template>

<script setup>
import { ref } from 'vue'
import {Link, router} from '@inertiajs/vue3'
import Pagination from '@/Components/Other/Pagination.vue'
import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue'
import MainLayout from "@/Layouts/MainLayouts/MainLayout.vue";

defineProps({
    roles: {
        type: Object,
        required: true
    }
})

const confirmingDeletion = ref(false)
const roleToDelete = ref(null)

const confirmDelete = (role) => {
    router.delete(route('roles.destroy', role.id), {
        onSuccess: () => {
            alert("删除成功")
        }
    })
    // roleToDelete.value = role
    // confirmingDeletion.value = true
}

const deleteRole = () => {
    if (roleToDelete.value) {
        router.delete(route('roles.destroy', roleToDelete.value.id), {
            onSuccess: () => {
                confirmingDeletion.value = false
                roleToDelete.value = null
            }
        })
    }
}
</script>

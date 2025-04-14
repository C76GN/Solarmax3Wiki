<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head title="用户管理" />
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6 pb-4 border-b dark:border-gray-700">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">用户管理</h1>
                    <!-- Add Create User button if applicable -->
                </div>

                <div class="overflow-x-auto">
                    <!-- Removed table-fixed to allow more flexible column sizing -->
                    <table class="w-full text-left">
                        <thead class="bg-gray-100 dark:bg-gray-700/50">
                            <tr>
                                <!-- Adjusted column widths: Give more relative space to User Info and Roles -->
                                <th class="th-cell w-2/5">用户信息</th>
                                <th class="th-cell w-2/5">角色</th>
                                <th class="th-cell w-auto">注册时间</th> <!-- Use w-auto for dates -->
                                <th class="th-cell w-auto text-right pr-6">操作</th> <!-- Use w-auto for actions -->
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-if="users.data.length === 0">
                                <td colspan="4" class="py-6 text-center text-gray-500 dark:text-gray-400 italic">
                                    没有找到任何用户。</td>
                            </tr>
                            <tr v-for="user in users.data" :key="user.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="td-cell align-top"> <!-- Added align-top for consistency -->
                                    <div class="flex items-center">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ user.name }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 break-all">{{
                                                user.email }}</div> <!-- Allow email to break if needed -->
                                        </div>
                                    </div>
                                </td>
                                <td class="td-cell align-top"> <!-- Added align-top -->
                                    <div class="flex flex-wrap gap-1">
                                        <span v-if="user.roles.length === 0"
                                            class="text-gray-500 dark:text-gray-400 italic text-xs">无角色</span>
                                        <span v-for="role in user.roles" :key="role.id"
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap"
                                            :class="role.is_system
                                                ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300'
                                                : 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300'">
                                            {{ role.display_name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="td-cell align-top text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                    <!-- Added align-top and nowrap -->
                                    {{ formatDate(user.created_at) }}
                                </td>
                                <td class="td-cell align-top text-right pr-6 whitespace-nowrap">
                                    <!-- Added align-top and nowrap -->
                                    <Link v-if="$page.props.auth.user.permissions.includes('user.edit')"
                                        :href="route('users.edit', user.id)" class="btn-link text-xs">
                                    <font-awesome-icon :icon="['fas', 'edit']" class="mr-1" /> 编辑角色
                                    </Link>
                                    <span v-else class="text-xs text-gray-400 italic">无操作权限</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :links="users.links" class="mt-6" />
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MainLayout from "@/Layouts/MainLayouts/MainLayout.vue";
import Pagination from '@/Components/Other/Pagination.vue';
import { adminNavigationLinks } from '@/config/navigationConfig';
import { formatDate } from '@/utils/formatters';

const navigationLinks = adminNavigationLinks;

defineProps({
    users: {
        type: Object,
        required: true
    }
})
</script>

<style scoped>
/* Shared styles for table cells */
.th-cell {
    @apply px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap;
    /* Added nowrap to headers */
}

.td-cell {
    @apply px-4 py-4 text-sm;
}

/* Button styles */
.btn-link {
    @apply text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline transition whitespace-nowrap;
    /* Added nowrap to buttons */
}
</style>
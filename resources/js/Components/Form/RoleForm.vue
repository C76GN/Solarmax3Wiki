<template>
    <form @submit.prevent="submit">
        <div class="space-y-6">
            <!-- 角色标识 (仅创建时) -->
            <div v-if="!role">
                <label for="role-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    角色标识 <span class="text-red-500">*</span>
                </label>
                <input id="role-name" type="text" v-model="form.name"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400"
                    :class="{ 'border-red-500 dark:border-red-400': form.errors.name }" :disabled="!!role">
                <div v-if="form.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">
                    {{ form.errors.name }}
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    角色标识只能包含字母、数字和下划线，创建后不可修改。
                </p>
            </div>

            <!-- 显示名称 -->
            <div>
                <label for="display-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    显示名称 <span class="text-red-500">*</span>
                </label>
                <input id="display-name" type="text" v-model="form.display_name"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400"
                    :class="{ 'border-red-500 dark:border-red-400': form.errors.display_name }">
                <div v-if="form.errors.display_name" class="mt-1 text-sm text-red-600 dark:text-red-400">
                    {{ form.errors.display_name }}
                </div>
            </div>

            <!-- 描述 -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    描述
                </label>
                <textarea id="description" v-model="form.description" rows="3"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400"
                    :class="{ 'border-red-500 dark:border-red-400': form.errors.description }"></textarea>
                <div v-if="form.errors.description" class="mt-1 text-sm text-red-600 dark:text-red-400">
                    {{ form.errors.description }}
                </div>
            </div>

            <!-- 权限设置 -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                    权限设置 <span class="text-red-500">*</span>
                </label>
                <div class="space-y-6">
                    <div v-for="(perms, group) in permissions" :key="group"
                        class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800/50">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ getGroupName(group) }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="permission in perms" :key="permission.id" class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" :id="`perm-${permission.id}`" :value="permission.id"
                                        v-model="form.permissions"
                                        class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded dark:bg-gray-600 dark:border-gray-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800"
                                        :disabled="role?.is_system"> <!-- Admin role cannot change perms -->
                                </div>
                                <div class="ml-3 text-sm">
                                    <label :for="`perm-${permission.id}`"
                                        class="font-medium text-gray-700 dark:text-gray-300">{{ permission.display_name
                                        }}</label>
                                    <p class="text-gray-500 dark:text-gray-400">{{ permission.description || '无描述' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="form.errors.permissions" class="mt-1 text-sm text-red-600 dark:text-red-400">
                    {{ form.errors.permissions }}
                </div>
            </div>
        </div>

        <!-- 操作按钮 -->
        <div class="mt-8 flex justify-end gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <Link :href="route('roles.index')"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition duration-150 ease-in-out text-sm font-medium dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">
            取消
            </Link>
            <button type="submit" :disabled="form.processing || role?.is_system"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium">
                <font-awesome-icon v-if="form.processing" :icon="['fas', 'spinner']" spin class="mr-1" />
                {{ submitButtonText }}
            </button>
        </div>
        <p v-if="role?.is_system" class="text-right mt-2 text-xs text-yellow-600 dark:text-yellow-400 italic">
            系统管理员角色不可编辑权限。</p>
    </form>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
    role: {
        type: Object,
        default: null // null when creating
    },
    permissions: { // Expecting grouped permissions: { group: [perms] }
        type: Object,
        required: true
    },
    submitButtonText: {
        type: String,
        default: '保存'
    }
})

const form = useForm({
    name: props.role?.name || '', // Role name (identifier), only for create
    display_name: props.role?.display_name || '',
    description: props.role?.description || '',
    permissions: props.role?.permissions || [], // Array of permission IDs
})

// 用于显示权限分组的中文名称
const getGroupName = (groupKey) => {
    const groupNames = {
        'wiki': 'Wiki 管理',
        'wiki_trash': 'Wiki 回收站',
        'role': '角色管理',
        'user': '用户管理',
        'log': '日志查看',
        'other': '其他权限' // Fallback group name
    };
    return groupNames[groupKey] || groupKey.charAt(0).toUpperCase() + groupKey.slice(1); // Capitalize if no mapping found
}

// 提交表单
const submit = () => {
    if (props.role) {
        // Editing an existing role
        form.put(route('roles.update', props.role.id), {
            preserveScroll: true,
        })
    } else {
        // Creating a new role
        form.post(route('roles.store'), {
            preserveScroll: true,
        })
    }
}
</script>

<style scoped>
/* 可以添加一些额外的scoped样式，如果需要的话 */
</style>
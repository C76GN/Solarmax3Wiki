<template>
    <form @submit.prevent="submit">
        <div class="space-y-6">
            <!-- 角色名称 -->
            <div v-if="!role">
                <label class="block text-sm font-medium text-gray-700">
                    角色标识
                </label>
                <input type="text" v-model="form.name"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    :class="{ 'border-red-500': form.errors.name }">
                <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                    {{ form.errors.name }}
                </div>
                <p class="mt-1 text-sm text-gray-500">
                    角色标识只能包含字母、数字和下划线，创建后不可修改
                </p>
            </div>

            <!-- 显示名称 -->
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    显示名称
                </label>
                <input type="text" v-model="form.display_name"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    :class="{ 'border-red-500': form.errors.display_name }">
                <div v-if="form.errors.display_name" class="mt-1 text-sm text-red-600">
                    {{ form.errors.display_name }}
                </div>
            </div>

            <!-- 描述 -->
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    描述
                </label>
                <textarea v-model="form.description" rows="3"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    :class="{ 'border-red-500': form.errors.description }"></textarea>
                <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                    {{ form.errors.description }}
                </div>
            </div>

            <!-- 权限设置 -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-4">
                    权限设置
                </label>
                <div class="space-y-6">
                    <div v-for="(perms, group) in permissions" :key="group"
                        class="border border-gray-200 rounded-lg p-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ getGroupName(group) }}</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div v-for="permission in perms" :key="permission.id" class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" :value="permission.id" v-model="form.permissions"
                                        class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label class="font-medium text-gray-700">{{ permission.display_name }}</label>
                                    <p class="text-gray-500">{{ permission.description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="form.errors.permissions" class="mt-1 text-sm text-red-600">
                    {{ form.errors.permissions }}
                </div>
            </div>
        </div>

        <!-- 表单操作 -->
        <div class="mt-6 flex justify-end gap-4">
            <Link :href="route('roles.index')"
                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition duration-150 ease-in-out">
            取消
            </Link>
            <button type="submit" :disabled="form.processing"
                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition duration-150 ease-in-out disabled:opacity-50">
                {{ submitButtonText }}
            </button>
        </div>
    </form>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
    role: {
        type: Object,
        default: null
    },
    permissions: {
        type: Object,
        required: true
    },
    submitButtonText: {
        type: String,
        default: '保存'
    }
})

const form = useForm({
    name: props.role?.name || '',
    display_name: props.role?.display_name || '',
    description: props.role?.description || '',
    permissions: props.role?.permissions || []
})

const getGroupName = (group) => {
    const groupNames = {
        'template': '模板管理',
        'page': '页面管理',
        'role': '角色管理',
        'user': '用户管理'
    }
    return groupNames[group] || group
}

const submit = () => {
    if (props.role) {
        form.put(route('roles.update', props.role.id))
    } else {
        form.post(route('roles.store'))
    }
}
</script>
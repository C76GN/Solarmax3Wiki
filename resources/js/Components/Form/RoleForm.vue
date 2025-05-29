<template>
    <form @submit.prevent="submit">
        <div class="space-y-6">
            <!-- 角色基本信息 -->
            <div v-if="!role">
                <label for="role-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    角色标识 <span class="text-red-500">*</span>
                </label>
                <!-- 角色标识符，创建后不可修改 -->
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

            <!-- 角色显示名称 -->
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

            <!-- 角色描述 -->
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

            <!-- 权限配置 -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                    权限设置 <span class="text-red-500">*</span>
                </label>
                <div class="space-y-6">
                    <!-- 遍历权限分组 -->
                    <div v-for="(perms, group) in permissions" :key="group"
                        class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800/50">
                        <!-- 权限分组标题 -->
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ getGroupName(group) }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- 遍历每个权限 -->
                            <div v-for="permission in perms" :key="permission.id" class="flex items-start">
                                <div class="flex items-center h-5">
                                    <!-- 权限选择框 -->
                                    <input type="checkbox" :id="`perm-${permission.id}`" :value="permission.id"
                                        v-model="form.permissions"
                                        class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded dark:bg-gray-600 dark:border-gray-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800"
                                        :disabled="role?.is_system">
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

        <!-- 表单操作按钮 -->
        <div class="mt-8 flex justify-end gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <!-- 返回角色列表 -->
            <Link :href="route('roles.index')"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition duration-150 ease-in-out text-sm font-medium dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">
            取消
            </Link>
            <!-- 提交按钮 -->
            <button type="submit" :disabled="form.processing || role?.is_system"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium">
                <font-awesome-icon v-if="form.processing" :icon="['fas', 'spinner']" spin class="mr-1" />
                {{ submitButtonText }}
            </button>
        </div>
        <!-- 系统角色不可编辑权限提示 -->
        <p v-if="role?.is_system" class="text-right mt-2 text-xs text-yellow-600 dark:text-yellow-400 italic">
            系统管理员角色不可编辑权限。</p>
    </form>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3'

// 定义组件接收的属性
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

// 定义表单状态和数据
const form = useForm({
    name: props.role?.name || '',
    display_name: props.role?.display_name || '',
    description: props.role?.description || '',
    permissions: props.role?.permissions || [],
})

// 根据权限分组键获取中文名称
const getGroupName = (groupKey) => {
    const groupNames = {
        'wiki': 'Wiki 管理',
        'wiki_trash': 'Wiki 回收站',
        'role': '角色管理',
        'user': '用户管理',
        'log': '日志查看',
        'other': '其他权限'
    };
    return groupNames[groupKey] || groupKey.charAt(0).toUpperCase() + groupKey.slice(1);
}

// 处理表单提交
const submit = () => {
    if (props.role) {
        form.put(route('roles.update', props.role.id), {
            preserveScroll: true,
        })
    } else {
        form.post(route('roles.store'), {
            presel: true,
        })
    }
}
</script>

<style scoped></style>
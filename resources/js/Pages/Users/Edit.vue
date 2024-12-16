// FileName: /var/www/Solarmax3Wiki/resources/js/Pages/Users/Edit.vue
<template>
    <div class="container mx-auto py-6">
        <div class="max-w-3xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">
                编辑用户角色：{{ user.name }}
            </h1>

            <form @submit.prevent="submit">
                <div class="bg-white rounded-lg shadow overflow-hidden p-6">
                    <div class="space-y-6">
                        <!-- 用户信息 -->
                        <div>
                            <div class="text-sm text-gray-500">邮箱</div>
                            <div class="mt-1 text-sm font-medium text-gray-900">
                                {{ user.email }}
                            </div>
                        </div>

                        <!-- 角色设置 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-4">
                                分配角色
                            </label>
                            <div class="space-y-4">
                                <div v-for="role in roles" :key="role.id" class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input type="checkbox" :value="role.id" v-model="form.roles"
                                            :disabled="role.is_system && user.id === $page.props.auth.user.id"
                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label class="font-medium text-gray-700">
                                            {{ role.display_name }}
                                            <span v-if="role.is_system"
                                                class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                系统角色
                                            </span>
                                        </label>
                                        <p class="text-gray-500">{{ role.description }}</p>
                                    </div>
                                </div>
                            </div>
                            <div v-if="form.errors.roles" class="mt-1 text-sm text-red-600">
                                {{ form.errors.roles }}
                            </div>
                        </div>
                    </div>

                    <!-- 表单操作 -->
                    <div class="mt-6 flex justify-end gap-4">
                        <Link :href="route('users.index')"
                            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition duration-150 ease-in-out">
                        取消
                        </Link>
                        <button type="submit" :disabled="form.processing"
                            class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition duration-150 ease-in-out disabled:opacity-50">
                            保存
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
    user: {
        type: Object,
        required: true
    },
    roles: {
        type: Array,
        required: true
    }
})

const form = useForm({
    roles: props.user.roles
})

const submit = () => {
    form.put(route('users.update', props.user.id))
}
</script>
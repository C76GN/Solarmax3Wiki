<template>
    <MainLayout
        :navigationLinks="[{ href: '/wiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]"
        :show-dropdown="true">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden">
                <div class="p-6">
                    <div class="text-center">
                        <h2 class="text-2xl font-semibold text-gray-800 mb-4">
                            访问被拒绝
                        </h2>
                        <div class="text-gray-600 mb-6">
                            {{ message }}
                        </div>
                        <Link :href="route('home')"
                            class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition duration-150 ease-in-out">
                        返回首页
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue'

defineProps({
    message: {
        type: String,
        required: true
    }
})
</script>
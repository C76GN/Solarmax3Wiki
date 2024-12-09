<template>
    <MainLayout
        :navigationLinks="[{ href: '/gamewiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]"
        :show-dropdown="true">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden">
                <div class="p-6">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-6">
                        创建新页面
                    </h2>

                    <PageForm :templates="templates" submit-button-text="创建页面" />
                </div>
            </div>
        </div>
        <FlashMessage ref="flash" />
    </MainLayout>
</template>

<script setup>
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue'
import PageForm from '@/Components/Form/PageForm.vue'
import FlashMessage from '@/Components/Other/FlashMessage.vue'

const props = defineProps({
    templates: {
        type: Array,
        required: true
    }
})
</script>
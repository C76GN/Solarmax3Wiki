<template>
    <MainLayout
        :navigationLinks="[{ href: '/gamewiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]"
        :show-dropdown="true">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden">
                <div class="p-6">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-6">
                        编辑模板：{{ template.name }}
                    </h2>

                    <TemplateForm :template="template" submit-button-text="更新模板" />
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue'
import TemplateForm from '@/Components/Form/TemplateForm.vue'

const props = defineProps({
    template: {
        type: Object,
        required: true
    }
})

</script>
// FileName: /var/www/Solarmax3Wiki/resources/js/Pages/Wiki/Edit.vue
<template>
    <MainLayout
        :navigationLinks="[{ href: '/wiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden">
                <div class="p-6">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-6">编辑页面</h2>
                    <WikiPageForm :page="page" :categories="categories" />
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import WikiPageForm from '@/Components/Wiki/WikiPageForm.vue';
import {createInertiaApp, router} from "@inertiajs/vue3";
import {onBeforeUnmount} from "vue";

let prop = defineProps({
    page: {
        type: Object,
        required: true
    },
    categories: {
        type: Array,
        required: true
    }
});


let timer = setInterval(() => {
    router.post(route('wiki.lock'),{page_id:prop.page.id});
},5000)

onBeforeUnmount(()=>{
    clearInterval(timer)
})

</script>

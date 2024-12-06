<template>

    <Head title="Home" />
    <MainLayout
        :navigationLinks="[{ href: '/gamewiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]"
        :show-dropdown="true">

        <div class="py-12">
            <div class="mx-auto max-w-screen-2xl space-y-8 sm:px-6 lg:px-8">

                <div
                    class="text-center bg-gradient-to-r from-indigo-600 to-purple-500 text-white rounded-lg p-8 shadow-lg">
                    <h1 class="text-4xl font-extrabold mb-4">欢迎来到太阳系争夺战3非官方维基百科</h1>
                    <p class="text-lg font-medium">发现最新的游戏资讯、指南与更新</p>
                </div>

                <div class="main-content-layout">
                    <div class="masonry-grid">
                        <SectionCard title="关于游戏" icon="🎮" description="《太阳系争夺战3》是一款星空背景，不断开辟新星球的游戏..." />
                        <SectionCard title="关于网站" icon="🌐"
                            description="本网站由游戏爱好者搭建，旨在为玩家提供关于《太阳系争夺战3》的详细攻略、资源等信息..." />
                        <SectionCard title="最新公告" icon="📢" description="查看最新的游戏公告与资讯。" />
                        <SectionCard title="网站更新" icon="🔄" description="显示网站最近的更新内容。" />
                        <SectionCard title="游戏更新" icon="🆕" description="查看最新的游戏版本。" />
                    </div>

                    <div>
                        <Carousel :images="imageList" />
                        <SectionCard title="网站指南" icon="📖" description="此处应有使用该网站的教程..." />
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import { Head } from '@inertiajs/vue3';
import SectionCard from '@/Components/Other/SectionCard.vue';
import Carousel from '@/Components/Other/Carousel.vue';

const imageList = [
    { src: '/images/BG1.jpg', alt: 'Image 1' },
    { src: '/images/BG2.jpg', alt: 'Image 2' },
    { src: '/images/BG1.jpg', alt: 'Image 3' },
];
</script>

<style scoped>
.main-content-layout {
    display: flex;
    gap: 24px;
    flex-direction: column;
}

.masonry-grid {
    flex-grow: 2;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
}
</style>

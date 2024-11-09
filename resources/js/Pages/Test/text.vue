<!-- Home.vue -->
<template>

    <Head title="Home" />
    <MainLayout
        :navigationLinks="[{ href: '/gamewiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]"
        :show-dropdown="true">

        <!-- 欢迎信息 -->
        <div class="py-12">
            <div class="mx-auto max-w-6xl space-y-8 sm:px-6 lg:px-8">

                <!-- 欢迎信息 -->
                <div
                    class="text-center bg-gradient-to-r from-indigo-600 to-purple-500 text-white rounded-lg p-8 shadow-lg">
                    <h1 class="text-4xl font-extrabold mb-4">欢迎来到太阳系争夺战3非官方维基百科</h1>
                    <p class="text-lg font-medium">发现最新的游戏资讯、指南与更新</p>
                </div>

                <!-- 瀑布流布局 -->
                <div class="masonry-grid">
                    <SectionCard title="关于游戏" icon="🎮" description="《太阳系争夺战3》是一款星空背景，不断开辟新星球的游戏..." />
                    <SectionCard title="关于网站" icon="🌐" description="本网站由游戏爱好者搭建，旨在为玩家提供关于《太阳系争夺战3》的详细攻略、资源等信息..." />
                    <SectionCard title="最新公告" icon="📢" description="查看最新的游戏公告与资讯。" />
                    <SectionCard title="网站更新" icon="🔄" description="显示网站最近的更新内容。" />
                    <SectionCard title="游戏更新" icon="🆕" description="查看最新的游戏版本。" />
                    <SectionCard title="网站指南" icon="📖" description="提供游戏相关的详细指南与攻略信息，帮助玩家更好地参与游戏。" />
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import { Head } from '@inertiajs/vue3';
import SectionCard from '@/Components/Other/SectionCard.vue'; // 引入 SectionCard 组件
</script>

<style scoped>
/* Masonry Grid 样式 */
.masonry-grid {
    column-count: 4;
    /* 默认 4 列，自动适应内容高度 */
    column-gap: 16px;
}

.masonry-item {
    break-inside: avoid;
    margin-bottom: 16px;
}

/* 响应式布局，调整列数 */
@media (max-width: 1200px) {
    .masonry-grid {
        column-count: 3;
        /* 中等屏幕 3 列 */
    }
}

@media (max-width: 768px) {
    .masonry-grid {
        column-count: 2;
        /* 小屏幕 2 列 */
    }
}

@media (max-width: 480px) {
    .masonry-grid {
        column-count: 1;
        /* 超小屏幕 1 列 */
    }
}
</style>

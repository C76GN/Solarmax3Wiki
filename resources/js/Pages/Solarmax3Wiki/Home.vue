<template>

  <Head title="Home" />
  <MainLayout
    :navigationLinks="[{ href: '/gamewiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]"
    :show-dropdown="true">

    <!-- 欢迎信息 -->
    <div class="py-12">
      <div class="mx-auto custom-max-w space-y-8 sm:px-6 lg:px-8">

        <!-- 欢迎信息 -->
        <div class="text-center bg-gradient-to-r from-indigo-600 to-purple-500 text-white rounded-lg p-8 shadow-lg">
          <h1 class="text-4xl font-extrabold mb-4">欢迎来到太阳系争夺战3非官方维基百科</h1>
          <p class="text-lg font-medium">发现最新的游戏资讯、指南与更新</p>
        </div>

        <!-- 瀑布流布局 -->
        <ContentLayout>
          <template #left>
            <InfoCard title="关于游戏" icon="🎮" description="《太阳系争夺战3》是一款星空背景，不断开辟新星球的游戏..." />
            <InfoCard title="关于网站" icon="🌐" description="本网站由游戏爱好者搭建，旨在为玩家提供关于《太阳系争夺战3》的详细攻略、资源等信息..." />
            <InfoCard title="最新公告" icon="📢" description="查看最新的游戏公告与资讯。" />
            <InfoCard title="网站更新" icon="🔄" description="显示网站最近的更新内容。" />
            <InfoCard title="游戏更新" icon="🆕" description="查看最新的游戏版本。" />
            <InfoCard title="网站指南" icon="📖" description="提供游戏相关的详细指南与攻略信息，帮助玩家更好地参与游戏。" />
          </template>

          <template #right>
            <Carousel>
              <div v-for="(slide, index) in slides" :key="index" class="carousel-slide">
                <img :src="slide.image" alt="轮播图内容" class="w-full rounded-lg" />
                <p class="text-white mt-4">{{ slide.description }}</p>
              </div>
            </Carousel>
          </template>
        </ContentLayout>

      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import { Head } from '@inertiajs/vue3';
import ContentLayout from '@/Layouts/MainLayouts/ContentLayout.vue';
import InfoCard from '@/Components/Other/InfoCard.vue';
import Carousel from '@/Components/Other/Carousel.vue';

// 示例轮播内容
const slides = [
  { image: '/images/BG1.jpg', description: '轮播图内容1' },
  { image: '/images/BG2.jpg', description: '轮播图内容2' },
  { image: '/images/BG1.jpg', description: '轮播图内容3' },
];
</script>


<style scoped>
.custom-max-w {
  max-width: 120rem;
  /* 自定义宽度 */
}
</style>
<template>

  <HomeLayout title="Home">
    <!-- 背景图片，整个页面充满 -->


    <!-- 大标题，中央展示，带背景图片 -->
    <div
      class="w-full flex justify-center items-center h-48 border-4 border-camp-black bg-opacity-75 p-4 rounded-lg shadow-lg"
      style="background-image: url('/images/BG1.jpg'); background-size: cover; background-position: center;">
      <h1 class="text-4xl font-bold text-camp-green">欢迎来到太阳系争夺战3非官方维基百科</h1>
    </div>

    <!-- 内容部分，左边为关于游戏和关于网站，右边为公告栏 -->
    <div class="w-full flex mt-6 space-x-6">
      <!-- 左边部分 -->
      <div class="flex flex-col w-1/3 space-y-6">
        <!-- 使用 ContentBox 组件 -->
        <ContentBox title="关于游戏"
          content="《太阳系争夺战3》是一款老少皆宜，不肝不氪的策略游戏，作为指挥官的玩家将探索各个星系，率领无敌的蔚蓝军团参与精彩的战斗。这里是游戏的基本信息和玩法介绍。" />
        <ContentBox title="关于网站" content="本网站由游戏爱好者搭建，非游戏官方，旨在为玩家提供《太阳系争夺战3》的详细攻略、资源分享和游戏更新的最新信息。" />
        <ContentBox title="网站指南" content="本网站由游戏爱好者搭建，非游戏官方，旨在为玩家提供《太阳系争夺战3》的详细攻略、资源分享和游戏更新的最新信息。" />
      </div>

      <!-- 右边部分（公告栏部分） -->
      <div class="w-2/3 grid grid-cols-3 gap-4">
        <ContentBox title="最新公告" content="显示最新的公告内容。" />
        <ContentBox title="网站更新" content="显示网站最近的更新内容。" />
        <ContentBox title="游戏更新" content="显示最近新的游戏版本。" />
      </div>
    </div>

    <!-- Wiki 导航部分，位于公告栏下面 -->
    <div class="w-full mt-8 p-6 bg-gray-800 bg-opacity-75 rounded-lg shadow-lg">
      <h3 class="text-2xl font-bold text-camp-orange mb-4">Wiki 导航</h3>
      <ul class="grid grid-cols-4 gap-4 text-gray-300">
        <li>导航项1</li>
        <li>导航项2</li>
        <li>导航项3</li>
        <li>导航项4</li>
      </ul>
    </div>

  </HomeLayout>
</template>

<script setup>
import HomeLayout from '@/Layouts/Solarmax3Wiki/HomeLayout.vue';
import ContentBox from '@/Components/Solarmax3Wiki/ContentBox.vue';  // 引入 ContentBox 组件

</script>

<style scoped>
/* 你可以在这里添加额外的自定义样式 */
</style>

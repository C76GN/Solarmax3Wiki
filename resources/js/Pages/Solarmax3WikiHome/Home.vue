<template>

  <Head title="Home" />
  <NavBar />

  <!-- 背景图片，整个页面充满 -->
  <div class="bg-sky-200 min-h-screen flex flex-col items-center p-6 text-white relative bg-cover bg-center"
    style="background-image: url('/images/BG2.jpg');">

    <!-- 半透明容器，包含所有内容，并居中显示 -->
    <div class="w-full max-w-screen-xl bg-headcolor bg-opacity-40 p-8 rounded-lg shadow-lg mt-4 mb-16">

      <!-- 大标题，中央展示，带背景图片 -->
      <div
        class="w-full flex justify-center items-center h-48 border-4 border-camp-black bg-opacity-75 p-4 rounded-lg shadow-lg"
        style="background-image: url('/images/BG1.jpg'); background-size: cover; background-position: center;">
        <h1 class="text-4xl font-bold text-camp-green">
          欢迎来到太阳系争夺战3非官方维基百科
        </h1>
      </div>

      <!-- 搜索栏，位于关于游戏上方，宽度与关于游戏保持一致 -->
      <div class="w-1/3 mt-6">
        <div class="flex items-center space-x-2">
          <input type="text" class="p-2 border-2 border-camp-black rounded-lg w-full" placeholder="搜索内容">
          <button class="bg-camp-black text-white px-4 py-2 rounded-lg whitespace-nowrap">筛选</button>
          <!-- 添加了 whitespace-nowrap -->
          <button class="bg-camp-black text-white px-4 py-2 rounded-lg whitespace-nowrap">搜索</button>
          <!-- 添加了 whitespace-nowrap -->
        </div>
      </div>

      <!-- 内容部分，左边为关于游戏和关于网站，右边为公告栏 -->
      <div class="w-full flex mt-6 space-x-6">
        <!-- 左边部分 -->
        <div class="flex flex-col w-1/3 space-y-6">
          <!-- 关于游戏部分 -->
          <div class="bg-gray-800 bg-opacity-75 p-6 rounded-lg shadow-lg">
            <h2 class="text-2xl font-bold text-camp-orange mb-4">关于游戏</h2>
            <p class="text-gray-300">
              《太阳系争夺战3》是一款老少皆宜，不肝不氪的策略游戏，作为指挥官的玩家将探索各个星系，率领无敌的蔚蓝军团参与精彩的战斗。这里是游戏的基本信息和玩法介绍。
            </p>
          </div>

          <!-- 关于网站部分 -->
          <div class="bg-gray-800 bg-opacity-75 p-6 rounded-lg shadow-lg">
            <h2 class="text-2xl font-bold text-camp-orange mb-4">关于网站</h2>
            <p class="text-gray-300">
              本网站由游戏爱好者搭建，非游戏官方，旨在为玩家提供《太阳系争夺战3》的详细攻略、资源分享和游戏更新的最新信息。
            </p>
          </div>
        </div>

        <!-- 右边部分（公告栏部分） -->
        <div class="w-2/3 flex flex-col justify-between">
          <div class="grid grid-cols-3 gap-4 bg-gray-800 bg-opacity-75 p-6 rounded-lg shadow-lg">
            <!-- 最新公告 -->
            <div class="bg-gray-700 p-4 rounded-lg shadow-md">
              <h3 class="text-xl font-bold text-camp-orange mb-2">最新公告</h3>
              <p class="text-gray-300">显示最新的公告内容。</p>
            </div>

            <!-- 网站更新 -->
            <div class="bg-gray-700 p-4 rounded-lg shadow-md">
              <h3 class="text-xl font-bold text-camp-orange mb-2">网站更新</h3>
              <p class="text-gray-300">显示网站最近的更新内容。</p>
            </div>

            <!-- 游戏更新 -->
            <div class="bg-gray-700 p-4 rounded-lg shadow-md">
              <h3 class="text-xl font-bold text-camp-orange mb-2">游戏更新</h3>
              <p class="text-gray-300">显示最近新的游戏版本。</p>
            </div>
          </div>
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
    </div>
  </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import NavBar from '@/Components/Home/NavBar.vue';
</script>

<style scoped>
/* 你可以在这里添加额外的自定义样式 */
</style>

<template>

  <Head title="Home" /><!-- 设置副标题 -->
  <div><!-- 使用一个 div 容器来包裹页面的主要内容，包含一个标题-->
    <NavBar /><!-- 包含一个 NavBar 组件，表示页面的导航栏-->
    <div class="container mx-auto mt-6"><!-- 创建一个居中的容器，顶部有额外的间距 -->
      <header>
        <h1 class="text-3xl font-bold">欢迎来到太阳系争夺战3非官方维基百科</h1><!-- 主标题，使用大号加粗字体 -->
      </header>
      <!-- 其他页面内容将逐步添加 -->
    </div>
  </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'; //引入Head组件（副标题）
import NavBar from '@/Components/Home/NavBar.vue';  //引入NavBar组件（顶部导航栏组件）
</script>
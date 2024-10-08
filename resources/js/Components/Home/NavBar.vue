<template>
  <nav class="bg-gray-800 p-4">
    <div class="flex justify-between items-center w-full">
      <!-- 左侧导航 -->
      <div class="flex items-center space-x-4">
        <a href="#" class="text-white font-bold text-lg">Solarmax3Wiki Unofficial</a>
        <a href="#" class="text-gray-300 hover:text-white">游戏版本</a>
        <a href="#" class="text-gray-300 hover:text-white">游戏内容</a>
        <a href="#" class="text-gray-300 hover:text-white">自制制作</a>
        <a href="#" class="text-gray-300 hover:text-white">攻略专区</a>
        <a href="#" class="text-gray-300 hover:text-white">论坛</a>
      </div>

      <!-- 右侧导航：登录和注册 -->
      <div class="flex items-center space-x-4">
        <!-- 判断用户是否已经登录 -->
        <template v-if="$page.props.auth.user">
          <!-- 如果用户已经登录，显示 Dashboard 链接 -->
          <Link href="/dashboard" class="text-gray-300 hover:text-white">Dashboard</Link>
        </template>
        <template v-else>
          <!-- 如果用户未登录，显示登录和注册链接 -->
          <Link href="/login" class="text-gray-300 hover:text-white">登录</Link>
          <Link v-if="canRegister" href="/register" class="text-gray-300 hover:text-white">注册</Link>
        </template>
        <a href="#" class="text-gray-300 hover:text-white">中/英</a>
        <button @click="showDownloadModal = true" class="text-gray-300 hover:text-white">游戏下载</button>
        <button @click="showCommunityModal = true" class="text-gray-300 hover:text-white">社群</button>
      </div>
    </div>

    <!-- 游戏下载弹出窗口 -->
    <div v-if="showDownloadModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
      <div class="bg-white p-6 rounded-lg shadow-lg">
        <h2 class="text-xl font-bold mb-4">游戏下载</h2>
        <ul>
          <li><a href="#" class="text-blue-500 hover:underline">推荐的版本！</a></li>
          <li><a href="#" class="text-blue-500 hover:underline">全部版本</a></li>
        </ul>
        <button @click="showDownloadModal = false" class="mt-4 bg-gray-800 text-white px-4 py-2 rounded">关闭</button>
      </div>
    </div>

    <!-- 社群弹出窗口 -->
    <div v-if="showCommunityModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
      <div class="bg-white p-6 rounded-lg shadow-lg">
        <h2 class="text-xl font-bold mb-4">社群</h2>
        <ul>
          <li><a href="#" class="text-blue-500 hover:underline">社群链接 1</a></li>
          <li><a href="#" class="text-blue-500 hover:underline">社群链接 2</a></li>
        </ul>
        <button @click="showCommunityModal = false" class="mt-4 bg-gray-800 text-white px-4 py-2 rounded">关闭</button>
      </div>
    </div>
  </nav>
</template>

<script setup>
// 控制弹窗的显示状态
import { ref } from 'vue';
const showDownloadModal = ref(false);
const showCommunityModal = ref(false);

// 使用 defineProps 获取 props
defineProps({
    canRegister: {
        type: Boolean,
        default: true,  // 默认显示注册按钮
    },
});
</script>

<script>
import { Link } from '@inertiajs/inertia-vue3';  // 引入 Inertia.js 的 Link 组件
export default {
  name: 'NavBar',
  components: {
    Link,  // 注册 Link 组件
  },
};
</script>

<style scoped>
/* 适用于弹出窗口背景的样式 */
.fixed {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
}
</style>

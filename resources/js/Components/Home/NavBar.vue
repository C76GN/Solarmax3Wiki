<template>
    <nav class="bg-gray-800 p-4">
      <div class="container mx-auto flex justify-between items-center">
        <!-- 左侧导航 -->
        <div class="flex items-center space-x-4">
          <a href="#" class="text-white font-bold text-lg">Solarmax3Wiki Unofficial</a>
          <a href="#" class="text-gray-300 hover:text-white">游戏版本</a>
          <a href="#" class="text-gray-300 hover:text-white">游戏内容</a>
          <a href="#" class="text-gray-300 hover:text-white">自制制作</a>
          <a href="#" class="text-gray-300 hover:text-white">攻略专区</a>
          <a href="#" class="text-gray-300 hover:text-white">论坛</a>
        </div>
  
        <!-- 右侧导航 -->
        <div class="flex items-center space-x-4">
          <a href="#" class="text-gray-300 hover:text-white">注册</a>
          <a href="#" class="text-gray-300 hover:text-white">登录</a>
          <a href="#" class="text-gray-300 hover:text-white">中/英</a>
          
          <!-- 游戏下载菜单 -->
          <div class="relative group">
            <a href="#" class="text-gray-300 hover:text-white">游戏下载</a>
            <div class="absolute hidden group-hover:block bg-gray-700 rounded mt-2 py-2">
              <a href="#" class="block px-4 py-2 text-gray-300 hover:bg-gray-600">推荐的版本！</a>
              <a href="#" class="block px-4 py-2 text-gray-300 hover:bg-gray-600">全部版本</a>
            </div>
          </div>
  
          <!-- 社群菜单 -->
          <div class="relative group">
            <a href="#" class="text-gray-300 hover:text-white">社群</a>
            <div class="absolute hidden group-hover:block bg-gray-700 rounded mt-2 py-2">
              <!-- 这里可以添加多个社群链接 -->
              <a href="#" class="block px-4 py-2 text-gray-300 hover:bg-gray-600">社群链接 1</a>
              <a href="#" class="block px-4 py-2 text-gray-300 hover:bg-gray-600">社群链接 2</a>
            </div>
          </div>
        </div>
      </div>
    </nav>
  </template>
  
  <script>
  export default {
    name: 'NavBar',
  };
  </script>
  
  <style scoped>
  /* 可选：自定义样式 */
  </style>
  
<template>
    <div>
      <NavBar />  <!-- 我们将在下一步创建 NavBar 组件 -->
      <div class="container mx-auto mt-6">
        <h1 class="text-3xl font-bold">欢迎来到太阳系争夺战3非官方维基百科</h1>
        <!-- 其他页面内容将逐步添加 -->
      </div>
    </div>
  </template>
  
  <script>
  import NavBar from '@/Components/Home/NavBar.vue';  // 导入我们即将创建的导航栏组件
  
  export default {
    components: {
      NavBar,
    },
  };
  </script>
  
<template>
  <!-- 设置背景和内边距 -->
  <nav ref="navbar" class="bg-camp-blue p-4 fixed top-0 w-full z-50">
    <!-- Flexbox 容器，子元素均匀分布并垂直居中，宽度为100% -->
    <div class="flex justify-between items-center w-full">

      <!-- 左侧导航 -->
      <!-- Flexbox 容器，子元素垂直居中，水平间距为4个单位，防止文字换行 -->
      <NavLinks :navLinks="navLinks" :linkClass="linkClass" />

      <!-- 搜索栏，添加到导航栏的中央 -->
      <SearchBar />

      <!-- 右侧导航 -->
      <!-- Flexbox 容器，子元素垂直居中，水平间距为4个单位 -->
      <RightNav :linkClass="linkClass" @openModal="handleOpenModal" />
    </div>

    <!-- 游戏下载弹出窗口 -->
    <!-- 如果 showDownloadModal 为 true，显示 PopUp 组件 -->
    <PopUp v-if="isModalOpen" @close="closeModal" :title="modalTitle">
      <component :is="currentModalContent"></component>
    </PopUp>

  </nav>


</template>

<script setup>
import { ref } from 'vue';  // 导入 ref，用于创建响应式变量
import PopUp from '@/Components/Solarmax3Wiki/PopUp.vue';  // 导入 PopUp 组件，用于显示弹出窗口
import NavLinks from '@/Components/Solarmax3Wiki/NavLinks.vue';
import SearchBar from '@/Components/Solarmax3Wiki/SearchBar.vue';
import RightNav from '@/Components/Solarmax3Wiki/RightNav.vue';
import DownloadContent from '@/Components/Modal/ModalContent/DownloadContent.vue';
import CommunityContent from '@/Components/Modal/ModalContent/CommunityContent.vue';



const navLinks = [
  { name: 'Solarmax3Wiki', url: '/' },// 第一个链接，指向首页
  { name: '游戏维基', url: '/gamewiki' }, // 第二个链接，指向游戏维基页面
  { name: '游戏历史&名人墙', url: '#' }, // 第三个链接，指向游戏历史页面
  { name: '自制专区', url: '#' }, // 第四个链接，指向自制专区页面
  { name: '攻略专区', url: '#' }, // 第五个链接，指向攻略专区
  { name: '论坛', url: '#' }  // 第六个链接，指向论坛页面
];

// 定义链接样式类
const linkClass = 'text-camp-black hover:text-white'; // 使用 Tailwind CSS 样式，设置默认颜色和悬停时的颜色

// 弹窗管理状态
const isModalOpen = ref(false);
const modalTitle = ref('');
const currentModalContent = ref(null);

// 处理打开弹窗
const handleOpenModal = ({ title, contentKey }) => {
  modalTitle.value = title;
  if (contentKey === 'downloadContent') {
    currentModalContent.value = DownloadContent;
  } else if (contentKey === 'communityContent') {
    currentModalContent.value = CommunityContent;
  }
  isModalOpen.value = true;
};

// 关闭弹窗
const closeModal = () => {
  isModalOpen.value = false;
  currentModalContent.value = null;
};


</script>

<style scoped>
/* 可选：自定义样式 */
</style>

<template>

  <Head title="Home" />
  <MainLayout :navigationLinks="[
    { href: '/gamewiki', label: '游戏维基' },
    { href: '#', label: '游戏历史&名人墙' },
    { href: '#', label: '自制专区' },
    { href: '#', label: '攻略专区' },
    { href: '#', label: '论坛' },
  ]" :show-dropdown="true">
    <div class="py-12">
      <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
        <div class="bg-black bg-opacity-40 rounded-lg p-4 shadow sm:rounded-lg sm:p-8">
        </div>
      </div>
    </div>
  </MainLayout>
</template>


<script setup>
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import { Head } from '@inertiajs/vue3';



</script>

<style scoped>
/* 你可以在这里添加额外的自定义样式 */
</style>

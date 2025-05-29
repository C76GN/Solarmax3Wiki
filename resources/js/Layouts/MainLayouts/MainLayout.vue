<template>
    <div class="min-h-screen w-full relative overflow-hidden bg-cover bg-center"
        style="background-image: url('/images/BG2.webp')">
        <!-- 星空背景组件，绝对定位覆盖整个屏幕，不响应鼠标事件 -->
        <StarrySky class="absolute inset-0 pointer-events-none" />
        <!-- 导航栏组件，接收导航链接数据 -->
        <Navbar :navigationLinks="navigationLinks" />
        <!-- 主内容区域，顶部留白，底部留有空间避免内容被浮动菜单遮挡 -->
        <main class="mt-8 transition-all duration-300 pb-16">
            <!-- 渲染父组件传入的内容 -->
            <slot />
        </main>
        <!-- 浮动菜单组件 -->
        <FloatingMenu />
        <!-- 闪现消息组件，用于显示全局通知，通过ref暴露给父组件（尽管这里没有直接调用其方法，但可以用于模板引用） -->
        <FlashMessage ref="flashMessage" />
    </div>
</template>

<script setup>
// 导入Navbar组件，用于页面顶部导航
import Navbar from '@/Components/Nav/NavBar.vue';
// 导入FloatingMenu组件，用于页面右下角的浮动快捷菜单
import FloatingMenu from '@/Components/Other/FloatingMenu.vue';
// 导入StarrySky组件，用于生成动态星空背景效果
import StarrySky from '@/Components/Other/StarrySky.vue';
// 导入FlashMessage组件，用于显示短暂的通知消息（成功、错误等）
import FlashMessage from '@/Components/Other/FlashMessage.vue';
// 导入ref，用于创建响应式引用，这里用来获取FlashMessage组件的实例
import { ref } from 'vue';

// 定义组件的props，用于接收父组件传递的数据
const props = defineProps({
    // 导航链接数组，包含{ href: string, label: string }对象
    navigationLinks: {
        type: Array,
        default: () => [], // 默认值为空数组
    },
});

// 创建一个名为flashMessage的ref，初始值为null
// 它将用于获取FlashMessage组件的模板引用，以便在需要时调用其内部方法
const flashMessage = ref(null);
</script>

<style scoped>
/* 这里可以添加MainLayout组件特有的样式 */
</style>
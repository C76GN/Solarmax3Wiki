<template>
    <!-- 设置全屏高度和背景图的主容器 -->
    <div class="min-h-screen w-full relative overflow-hidden bg-cover bg-center"
        style="background-image: url('/images/BG2.webp')">

        <!-- 星空背景组件 -->
        <StarrySky class="absolute inset-0 pointer-events-none" />

        <!-- 导航栏 -->
        <div class="relative p-4 w-full bg-sky-400 shadow-lg z-50">
            <div class="flex justify-between items-center">

                <!-- 左侧：包含Logo和导航链接 -->
                <div class="flex items-center space-x-2">

                    <!-- Home页Logo -->
                    <Link :href="'/'">
                    <HomeLogo class="block h-9 w-auto fill-current text-gray-800" />
                    </Link>

                    <!-- 导航链接组件，传入导航数据 -->
                    <NavLinks :navigationLinks="navigationLinks" />
                </div>

                <!-- 右侧：用户下拉菜单（登录状态）或登录/注册链接（未登录状态） -->

                <!-- 登录状态下显示用户信息下拉菜单 -->
                <div v-if="$page.props.auth.user" class="hidden md:flex items-center space-x-4">
                    <UserDropdown :user="$page.props.auth.user" />
                </div>

                <!-- 未登录状态下显示登录和注册链接 -->
                <div v-else class="hidden md:flex">
                    <AuthLinks />
                </div>

                <!-- 手机端汉堡菜单按钮，点击后显示/隐藏下拉菜单 -->
                <div class="-me-2 flex items-center md:hidden">
                    <button @click="toggleDropdown" class="p-2 hover:text-black focus:outline-none">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <!-- 三条线图标表示菜单未展开，点击时切换 -->
                            <path :class="{ hidden: isDropdownVisible, 'inline-flex': !isDropdownVisible }"
                                stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                            <!-- X图标表示菜单已展开，点击时切换 -->
                            <path :class="{ hidden: !isDropdownVisible, 'inline-flex': isDropdownVisible }"
                                stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- 手机端下拉菜单，用于显示导航链接和用户信息 -->
        <transition name="slide-fade" mode="out-in">
            <ResponsiveNav v-if="isDropdownVisible" :navigationLinks="navigationLinks" :user="$page.props.auth.user" />
        </transition>

        <!-- 主内容区域，包含页面的主体内容 -->
        <main class="mt-16 transition-all duration-300">
            <slot />
        </main>
    </div>
</template>

<script setup>
// 导入必要的Vue和自定义组件
import { ref, onMounted, onBeforeUnmount } from 'vue';
import HomeLogo from '@/Components/Svg/HomeLogo.vue';
import AuthLinks from '@/Components/Nav/AuthLinks.vue';
import NavLinks from '@/Components/Nav/NavLinks.vue';
import UserDropdown from '@/Components/Nav/UserDropdown.vue';
import ResponsiveNav from '@/Components/Nav/ResponsiveNav.vue';
import { Link } from '@inertiajs/vue3';
import StarrySky from '@/Components/Other/StarrySky.vue';

// 定义props，接收导航链接数组
const props = defineProps({
    navigationLinks: {
        type: Array,
        default: () => [], // 默认导航链接为空数组
    },
});

// 定义状态变量：控制下拉菜单的显示
const isDropdownVisible = ref(false);

// 切换下拉菜单显示状态的方法
const toggleDropdown = () => {
    isDropdownVisible.value = !isDropdownVisible.value;
};

// 监听窗口大小变化，如果宽度超过768px（即进入桌面端），自动隐藏下拉菜单
const handleResize = () => {
    if (window.innerWidth >= 768) {
        isDropdownVisible.value = false;
    }
};

// 组件挂载时，添加窗口大小变化事件监听
onMounted(() => {
    window.addEventListener('resize', handleResize);
});

// 组件卸载前，移除事件监听，避免内存泄漏
onBeforeUnmount(() => {
    window.removeEventListener('resize', handleResize);
});
</script>

<style scoped>
/* 定义下拉菜单过渡动画效果 */
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>

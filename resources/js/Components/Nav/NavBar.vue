// FileName: /var/www/Solarmax3Wiki/resources/js/Components/Nav/NavBar.vue
<!-- Components/Nav/Navbar.vue -->
<template>
    <!-- 导航栏的主容器，设置背景颜色和透明度 -->
    <div class="relative p-4 w-full bg-sky-400 bg-opacity-75 shadow-lg z-50">
        <div class="flex justify-between items-center">
            <!-- 左侧 Logo 和导航链接 -->
            <div class="flex items-center space-x-2">
                <!-- 首页 Logo 链接 -->
                <Link :href="'/'">
                <HomeLogo class="block h-9 w-auto fill-current text-gray-800" />
                </Link>
                <!-- 导航链接组件 -->
                <NavLinks :navigationLinks="navigationLinks" />
            </div>

            <!-- 用户下拉菜单（登录状态下显示） -->
            <div v-if="$page.props.auth.user" class="hidden md:flex items-center space-x-4">
                <UserDropdown :user="$page.props.auth.user" />
            </div>

            <!-- 登录和注册链接（未登录状态下显示） -->
            <div v-else class="hidden md:flex">
                <AuthLinks />
            </div>

            <!-- 移动端汉堡菜单按钮，点击时显示/隐藏下拉菜单 -->
            <div class="-me-2 flex items-center md:hidden">
                <button @click="toggleDropdown" class="p-2 hover:text-black focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <!-- 菜单图标，基于 isDropdownVisible 显示不同的图标 -->
                        <path :class="{ hidden: isDropdownVisible, 'inline-flex': !isDropdownVisible }"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ hidden: !isDropdownVisible, 'inline-flex': isDropdownVisible }"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- 移动端的下拉导航菜单，带有淡入淡出的过渡效果 -->
        <transition name="slide-fade" mode="out-in">
            <ResponsiveNav v-if="isDropdownVisible" :navigationLinks="navigationLinks" :user="$page.props.auth.user" />
        </transition>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import HomeLogo from '@/Components/Svg/HomeLogo.vue'; // 导入 Logo 组件
import AuthLinks from '@/Components/Nav/AuthLinks.vue'; // 导入登录/注册链接组件
import NavLinks from '@/Components/Nav/NavLinks.vue'; // 导入导航链接组件
import UserDropdown from '@/Components/Nav/UserDropdown.vue'; // 导入用户下拉菜单组件
import ResponsiveNav from '@/Components/Nav/ResponsiveNav.vue'; // 导入响应式导航组件
import { Link } from '@inertiajs/vue3'; // 引入 Inertia.js 的 Link 组件

// 接收父组件传入的导航链接数据
const props = defineProps({
    navigationLinks: {
        type: Array,
        default: () => [],
    },
});

// 控制移动端下拉菜单的显示状态
const isDropdownVisible = ref(false);
const toggleDropdown = () => {
    isDropdownVisible.value = !isDropdownVisible.value;
};

// 监听窗口大小变化，当宽度大于等于768px时隐藏下拉菜单
const handleResize = () => {
    if (window.innerWidth >= 768) {
        isDropdownVisible.value = false;
    }
};

// 挂载时添加窗口大小变化事件监听器
onMounted(() => {
    window.addEventListener('resize', handleResize);
});

// 卸载时移除事件监听器
onBeforeUnmount(() => {
    window.removeEventListener('resize', handleResize);
});
</script>

<style scoped>
/* 下拉菜单的进入和离开动画效果 */
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    opacity: 0;
    /* 从透明状态开始 */
    transform: translateY(-10px);
    /* 从上方滑入 */
}
</style>

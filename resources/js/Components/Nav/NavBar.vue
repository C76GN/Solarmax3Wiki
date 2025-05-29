<template>
    <!-- 导航栏主容器，设置背景、透明度、阴影和层级 -->
    <div class="relative p-4 w-full bg-sky-400 bg-opacity-75 shadow-lg z-50">
        <div class="flex justify-between items-center">
            <!-- 左侧 Logo 和主导航链接 -->
            <div class="flex items-center space-x-2">
                <!-- 返回首页的 Logo 链接 -->
                <Link :href="'/'">
                <HomeLogo class="block h-9 w-auto fill-current text-gray-800" />
                </Link>
                <!-- 桌面端导航链接组件 -->
                <NavLinks :navigationLinks="navigationLinks" />
            </div>

            <!-- 桌面端用户下拉菜单 (用户已登录时显示) -->
            <div v-if="$page.props.auth.user" class="hidden md:flex items-center space-x-4">
                <UserDropdown :user="$page.props.auth.user" />
            </div>

            <!-- 桌面端登录/注册链接 (用户未登录时显示) -->
            <div v-else class="hidden md:flex">
                <AuthLinks />
            </div>

            <!-- 移动端菜单切换按钮 -->
            <div class="-me-2 flex items-center md:hidden">
                <button @click="toggleDropdown" class="p-2 hover:text-black focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <!-- 根据下拉菜单显示状态切换图标 -->
                        <path :class="{ hidden: isDropdownVisible, 'inline-flex': !isDropdownVisible }"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ hidden: !isDropdownVisible, 'inline-flex': isDropdownVisible }"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- 移动端响应式导航菜单，带有过渡效果 -->
        <transition name="slide-fade" mode="out-in">
            <ResponsiveNav v-if="isDropdownVisible" :navigationLinks="navigationLinks" :user="$page.props.auth.user" />
        </transition>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import HomeLogo from '@/Components/Svg/HomeLogo.vue';
import AuthLinks from '@/Components/Nav/AuthLinks.vue';
import NavLinks from '@/Components/Nav/NavLinks.vue';
import UserDropdown from '@/Components/Nav/UserDropdown.vue';
import ResponsiveNav from '@/Components/Nav/ResponsiveNav.vue';
import { Link } from '@inertiajs/vue3';

// 定义组件接收的导航链接属性
const props = defineProps({
    navigationLinks: {
        type: Array,
        default: () => [],
    },
});

// 控制移动端下拉菜单的显示/隐藏状态
const isDropdownVisible = ref(false);

// 切换下拉菜单的显示状态
const toggleDropdown = () => {
    isDropdownVisible.value = !isDropdownVisible.value;
};

// 监听窗口大小变化，当窗口宽度大于或等于768px时，自动隐藏移动端下拉菜单
const handleResize = () => {
    if (window.innerWidth >= 768) {
        isDropdownVisible.value = false;
    }
};

// 组件挂载时添加窗口大小变化监听器
onMounted(() => {
    window.addEventListener('resize', handleResize);
});

// 组件卸载时移除窗口大小变化监听器，防止内存泄漏
onBeforeUnmount(() => {
    window.removeEventListener('resize', handleResize);
});
</script>

<style scoped>
/* 移动端菜单的进入/离开过渡动画 */
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
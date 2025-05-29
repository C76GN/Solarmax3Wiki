<template>
    <!-- 主容器，设置最小高度、相对定位和背景样式 -->
    <div class="min-h-screen relative"
        style="background-image: url('/images/BG2.webp'); background-attachment: fixed; background-size: cover; background-position: center">
        <!-- 星空背景组件，绝对定位覆盖整个屏幕，不响应鼠标事件 -->
        <StarrySky class="absolute inset-0 pointer-events-none" />

        <!-- 悬浮顶部导航栏，设置背景、阴影和Z轴层级 -->
        <div class="relative p-4 w-full bg-sky-400 shadow-lg z-50">
            <div class="flex justify-between">
                <!-- 左侧部分：包含Logo和主要导航链接 -->
                <div class="flex items-start">
                    <!-- 网站Logo，点击返回首页 -->
                    <Link :href="'/'">
                    <HomeLogo class="block h-9 w-auto fill-current text-gray-800" />
                    </Link>

                    <!-- 桌面端导航链接，在小屏幕上隐藏 -->
                    <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                        <!-- Dashboard链接，根据当前路由判断是否激活 -->
                        <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            仪表盘
                        </NavLink>
                        <!-- Profile链接，根据当前路由判断是否激活 -->
                        <NavLink :href="route('profile.edit')" :active="route().current('profile.edit')">
                            个人资料
                        </NavLink>
                    </div>
                </div>

                <!-- 右侧部分：桌面端的用户设置下拉菜单，在小屏幕上隐藏 -->
                <div class="hidden sm:ms-6 sm:flex sm:items-center">
                    <!-- 用户设置下拉菜单组件 -->
                    <Dropdown align="right" width="48">
                        <!-- 触发下拉菜单的按钮，显示当前用户名称 -->
                        <template #trigger>
                            <span class="inline-flex rounded-md">
                                <button type="button"
                                    class="inline-flex items-center rounded-md border border-transparent bg-transparent px-3 py-2 text-lg font-medium leading-4 text-slate-800 transition duration-150 ease-in-out hover:text-black focus:outline-none">
                                    {{ $page.props.auth.user.name }}

                                    <!-- 下拉箭头图标 -->
                                    <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </span>
                        </template>

                        <!-- 下拉菜单内容：包含登出链接 -->
                        <template #content>
                            <DropdownLink :href="route('logout')" method="post" as="button">登出</DropdownLink>
                        </template>
                    </Dropdown>
                </div>

                <!-- 移动端汉堡菜单按钮，在桌面端隐藏 -->
                <div class="-me-2 flex items-center sm:hidden">
                    <button @click="showingNavigationDropdown = !showingNavigationDropdown"
                        class="bg-transparent inline-flex items-center justify-center rounded-md p-2 text-black transition duration-150 ease-in-out hover:bg-transparent hover:text-black focus:bg-transparent focus:text-black focus:outline-none">
                        <!-- 汉堡图标，根据菜单显示状态切换 -->
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path
                                :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                            <path
                                :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- 响应式导航菜单，通过过渡效果平滑显示和隐藏 -->
        <transition name="slide-fade" mode="out-in">
            <div v-if="showingNavigationDropdown" class="relative w-full bg-sky-400 shadow-lg z-40 overflow-hidden">
                <!-- 响应式主要导航链接 -->
                <div class="space-y-1 pb-3 pt-2">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                        仪表盘
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('profile.edit')" :active="route().current('profile.edit')">
                        个人资料
                    </ResponsiveNavLink>
                </div>

                <!-- 响应式用户设置选项 -->
                <div class="border-t-4 border-black pb-1 pt-4">
                    <div class="px-4">
                        <!-- 显示当前用户名称和邮箱 -->
                        <div class="text-base font-medium text-gray-800">{{ $page.props.auth.user.name }}</div>
                        <div class="text-sm font-medium text-gray-500">{{ $page.props.auth.user.email }}</div>
                    </div>

                    <!-- 响应式登出链接 -->
                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">登出</ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </transition>

        <!-- 页面主要内容区域，顶部留白 -->
        <main class="mt-16 ">
            <!-- 渲染父组件传入的内容 -->
            <slot />
        </main>
    </div>
</template>

<script setup>
// 导入Vue的响应式引用功能和生命周期钩子
import { ref, onMounted, onBeforeUnmount } from 'vue';
// 导入自定义的HomeLogo组件
import HomeLogo from '@/Components/Svg/HomeLogo.vue';
// 导入自定义的Dropdown组件，用于创建下拉菜单
import Dropdown from '@/Components/Other/Dropdown.vue';
// 导入自定义的DropdownLink组件，用于下拉菜单中的链接样式
import DropdownLink from '@/Components/Other/DropdownLink.vue';
// 导入自定义的NavLink组件，用于桌面端导航链接样式
import NavLink from '@/Components/Nav/NavLink.vue';
// 导入自定义的ResponsiveNavLink组件，用于移动端响应式导航链接样式
import ResponsiveNavLink from '@/Components/Nav/ResponsiveNavLink.vue';
// 导入Inertia.js的Link组件，用于客户端路由导航
import { Link } from '@inertiajs/vue3';
// 导入自定义的StarrySky组件，用于背景效果
import StarrySky from '@/Components/Other/StarrySky.vue';

// 定义一个响应式变量，控制移动端导航下拉菜单的显示/隐藏状态
const showingNavigationDropdown = ref(false);

/**
 * 处理窗口尺寸变化的函数。
 * 当窗口宽度达到或超过640px（通常是桌面端尺寸）时，
 * 隐藏移动端导航下拉菜单，以避免在桌面视图下出现不必要的菜单。
 */
function handleResize() {
    if (window.innerWidth >= 640) {
        showingNavigationDropdown.value = false;
    }
}

// 组件挂载后执行的生命周期钩子
onMounted(() => {
    // 为窗口的resize事件添加事件监听器
    window.addEventListener('resize', handleResize);
});

// 组件即将卸载前执行的生命周期钩子
onBeforeUnmount(() => {
    // 移除窗口resize事件的监听器，防止内存泄漏
    window.removeEventListener('resize', handleResize);
});
</script>

<style scoped>
/* 定义菜单展开/折叠的过渡效果 */
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.3s ease;
    /* 所有属性在0.3秒内平滑过渡 */
}

/* 菜单进入时的初始状态和离开时的结束状态 */
.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateY(-100%);
    /* 从上方移出 */
    opacity: 0;
    /* 完全透明 */
}

/* 菜单进入时的结束状态和离开时的初始状态 */
.slide-fade-enter-to,
.slide-fade-leave-from {
    transform: translateY(0);
    /* 恢复到原位 */
    opacity: 1;
    /* 完全不透明 */
}
</style>
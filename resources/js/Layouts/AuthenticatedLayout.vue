<template>
    <div class="min-h-screen relative"
        style="background-image: url('/images/BG2.webp'); background-attachment: fixed; background-size: cover; background-position: center">
        <StarrySky class="absolute inset-0 pointer-events-none" />

        <!-- 悬浮顶部导航栏 -->
        <div class="relative p-4 w-full bg-sky-400 shadow-lg z-50">
            <div class="flex justify-between">
                <!-- 左侧部分 -->
                <div class="flex items-start">
                    <!-- Logo -->
                    <Link :href="route('dashboard')">
                    <ApplicationLogo class="block h-9 w-auto fill-current text-gray-800" />
                    </Link>

                    <!-- Navigation Links -->
                    <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                        <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            Dashboard
                        </NavLink>
                        <NavLink :href="route('profile.edit')" :active="route().current('profile.edit')">
                            Profile
                        </NavLink>
                    </div>
                </div>

                <!-- 右侧部分 -->
                <div class="hidden sm:ms-6 sm:flex sm:items-center">
                    <!-- Settings Dropdown -->
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <span class="inline-flex rounded-md">
                                <button type="button"
                                    class="inline-flex items-center rounded-md border border-transparent bg-transparent px-3 py-2 text-lg font-medium leading-4 text-slate-800 transition duration-150 ease-in-out hover:text-black focus:outline-none">
                                    {{ $page.props.auth.user.name }}

                                    <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </span>
                        </template>

                        <template #content>
                            <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                        </template>
                    </Dropdown>
                </div>

                <!-- Hamburger for mobile -->
                <div class="-me-2 flex items-center sm:hidden">
                    <button @click="showingNavigationDropdown = !showingNavigationDropdown"
                        class="bg-transparent inline-flex items-center justify-center rounded-md p-2 text-black transition duration-150 ease-in-out hover:bg-transparent hover:text-black focus:bg-transparent focus:text-black focus:outline-none">
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

        <!-- Responsive Navigation Menu -->
        <transition name="slide-fade" mode="out-in">
            <div v-if="showingNavigationDropdown" class="relative w-full bg-sky-400 shadow-lg z-40 overflow-hidden">
                <div class="space-y-1 pb-3 pt-2">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                        Dashboard
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('profile.edit')" :active="route().current('profile.edit')">
                        Profile
                    </ResponsiveNavLink>
                </div>

                <!-- Responsive Settings Options -->
                <div class="border-t-4 border-black pb-1 pt-4">
                    <div class="px-4">
                        <div class="text-base font-medium text-gray-800">{{ $page.props.auth.user.name }}</div>
                        <div class="text-sm font-medium text-gray-500">{{ $page.props.auth.user.email }}</div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">Log Out</ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Page Content -->
        <main class="mt-16 ">
            <slot />
        </main>
    </div>
</template>

<script setup>
import { ref ,onMounted, onBeforeUnmount } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';
import StarrySky from '@/Components/Solarmax3Wiki/StarrySky.vue';

const showingNavigationDropdown = ref(false);

// 添加监听窗口尺寸变化的函数
function handleResize() {
    // 如果窗口宽度大于或等于640px（桌面端），关闭下拉菜单
    if (window.innerWidth >= 640) {
        showingNavigationDropdown.value = false;
    }
}

onMounted(() => {
    // 挂载时添加监听器
    window.addEventListener('resize', handleResize);
});

onBeforeUnmount(() => {
    // 组件卸载时移除监听器
    window.removeEventListener('resize', handleResize);
});
</script>


<style scoped>
/* 使用过渡效果来平滑展开和折叠菜单 */
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.3s ease;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateY(-100%);
    opacity: 0;
}

.slide-fade-enter-to,
.slide-fade-leave-from {
    transform: translateY(0);
    opacity: 1;
}
</style>

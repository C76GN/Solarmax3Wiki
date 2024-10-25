<template>
    <div class="min-h-screen relative"
        style="background-image: url('/images/BG2.webp'); background-attachment: fixed; background-size: cover; background-position: center">
        <StarrySky class="absolute inset-0 pointer-events-none" />

        <!-- 导航栏 -->
        <div class="relative p-4 w-full bg-sky-400 shadow-lg z-50">
            <div class="flex justify-between">
                <!-- 左侧部分 -->
                <div class="flex items-start">
                    <!-- Logo -->
                    <Link :href="logoLink">
                    <ApplicationLogo class="block h-9 w-auto fill-current text-gray-800" />
                    </Link>

                    <!-- Navigation Links -->
                    <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                        <NavLink v-for="(link, index) in navigationLinks" :key="index" :href="link.href"
                            :active="isActive(link.href)">
                            {{ link.label }}
                        </NavLink>
                    </div>
                </div>

                <!-- 右侧部分 -->
                <div class="hidden sm:ms-6 sm:flex sm:items-center">
                    <!-- 如果用户已登录，显示用户名；否则显示登录/注册按钮 -->
                    <template v-if="$page.props.auth.user">
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
                                <DropdownLink :href="logoutLink" method="post" as="button">Log Out</DropdownLink>
                            </template>
                        </Dropdown>
                    </template>
                    <template v-else>
                        <!-- 未登录时显示登录/注册按钮 -->
                        <div class="flex space-x-4">
                            <Link href="/login"
                                class="text-lg font-medium leading-4 text-slate-800 transition duration-150 ease-in-out hover:text-black">
                            登录
                            </Link>
                            <Link href="/register"
                                class="text-lg font-medium leading-4 text-slate-800 transition duration-150 ease-in-out hover:text-black">
                            注册
                            </Link>
                        </div>
                    </template>
                </div>

                <!-- Hamburger for mobile -->
                <div class="-me-2 flex items-center sm:hidden">
                    <button @click="toggleDropdown"
                        class="bg-transparent inline-flex items-center justify-center rounded-md p-2 text-black transition duration-150 ease-in-out hover:bg-transparent hover:text-black focus:bg-transparent focus:text-black focus:outline-none">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{ hidden: showingDropdown, 'inline-flex': !showingDropdown }"
                                stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{ hidden: !showingDropdown, 'inline-flex': showingDropdown }"
                                stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Responsive Navigation Menu -->
        <transition name="slide-fade" mode="out-in">
            <div v-if="showingDropdown" class="relative w-full bg-sky-400 shadow-lg z-40 overflow-hidden">
                <div class="space-y-1 pb-3 pt-2">
                    <ResponsiveNavLink v-for="(link, index) in navigationLinks" :key="index" :href="link.href"
                        :active="isActive(link.href)">
                        {{ link.label }}
                    </ResponsiveNavLink>
                </div>

                <!-- Responsive Settings Options -->
                <div v-if="showDropdown && $page.props.auth.user" class="border-t-4 border-black pb-1 pt-4">
                    <div class="px-4">
                        <div class="text-base font-medium text-gray-800">{{ $page.props.auth.user.name }}</div>
                        <div class="text-sm font-medium text-gray-500">{{ $page.props.auth.user.email }}</div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="logoutLink" method="post" as="button">Log Out</ResponsiveNavLink>
                    </div>
                </div>
                <div v-else class="border-t-4 border-black pb-1 pt-4">
                    <div class="mt-3 space-y-1">
                        <!-- 使用 ResponsiveNavLink 组件 -->
                        <ResponsiveNavLink href="/login" :active="page.url === '/login'">
                            登录
                        </ResponsiveNavLink>
                        <ResponsiveNavLink href="/register" :active="page.url === '/register'">
                            注册
                        </ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </transition>

        <!-- 页面内容 -->
        <main class="mt-16 transition-all duration-300">
            <slot />
        </main>
    </div>
</template>


<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';
import StarrySky from '@/Components/Solarmax3Wiki/StarrySky.vue';

// Props for customization
const props = defineProps({
    logoLink: {
        type: String,
        default: '/', // 默认指向首页
    },
    navigationLinks: {
        type: Array,
        default: () => [], // 默认导航链接为空数组
    },
    logoutLink: {
        type: String,
        default: '/logout',
    },
    showDropdown: {
        type: Boolean,
        default: false,
    },
});

// 获取当前页面路由
const page = usePage();

// 计算当前链接是否为活动状态
function isActive(link) {
    return page.url === link;
}

// State for dropdown visibility
const showingDropdown = ref(false);

function toggleDropdown() {
    showingDropdown.value = !showingDropdown.value;
}

// Window resize handler to manage dropdown visibility
function handleResize() {
    if (window.innerWidth >= 640) {
        showingDropdown.value = false;
    }
}

onMounted(() => {
    window.addEventListener('resize', handleResize);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', handleResize);
});
</script>

<style scoped>
/* 定义过渡动画 */
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

// FileName: /var/www/Solarmax3Wiki/resources/js/Components/Nav/UserDropdown.vue
<template>
    <!-- 下拉菜单组件，显示用户信息的下拉选项 -->
    <Dropdown align="right" width="48">
        <!-- 触发下拉菜单的按钮部分 -->
        <template #trigger>
            <!-- 按钮样式设置 -->
            <button type="button"
                class="inline-flex items-center px-3 py-2 text-lg font-medium text-slate-800 hover:text-black focus:outline-none rounded-md">
                <!-- 显示用户姓名 -->
                {{ user.name }}
                <!-- 图标，表示下拉箭头 -->
                <svg class="ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </button>
        </template>

        <!-- 下拉内容部分 -->
        <template #content>
            <!-- 跳转到用户个人资料编辑页面 -->
            <DropdownLink :href="route('profile.edit')" method="get">Profile</DropdownLink>
            <!-- 注销链接，点击后将注销用户 -->
            <DropdownLink :href="'/logout'" method="post" as="button">Log Out</DropdownLink>
        </template>
    </Dropdown>
</template>

<script setup>
import Dropdown from '@/Components/Other/Dropdown.vue';       // 导入下拉菜单组件
import DropdownLink from '@/Components/Other/DropdownLink.vue'; // 导入下拉菜单链接组件

// 定义接收的 props 参数
const props = defineProps({
    user: {
        type: Object,      // 用户对象，包含用户信息
        required: true,    // 必须传入该参数
    },
});
</script>

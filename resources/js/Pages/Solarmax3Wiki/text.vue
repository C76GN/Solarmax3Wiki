<template>
    <TopNavBar>
        <!-- 将 LeftTopNavBar 插入到顶部导航栏的左侧 -->
        <LeftTopNavBar :links="navLinks" @nav-click="handleNavClick" />
    </TopNavBar>

    <!-- 页面内容 -->
    <div class="content mt-20 p-4">
        <p>这里是页面内容</p>
        <!-- 更多内容模拟页面滚动 -->
        <div style="height: 2000px"></div>
    </div>
</template>

<script setup>
import TopNavBar from "@/Components/Solarmax3Wiki/Nav/TopNavBar.vue";
import LeftTopNavBar from "@/Components/Solarmax3Wiki/Nav/LeftTopNavBar.vue";
import { useNavLinks } from "@/Composables/useNavLinks";

const { navLinks, handleNavClick } = useNavLinks();  // 使用抽象的状态管理逻辑

</script>

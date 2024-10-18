<template>

    <HomeLayout title="GameWiki">

        <!-- 页面主体，包含使用指南和导航板块 -->
        <div class="bg-gray-200 min-h-screen p-8">
            <!-- 使用指南板块 -->
            <section class="bg-white p-6 rounded-lg shadow-lg mb-8">
                <h1 class="text-3xl font-bold mb-4">游戏维基使用指南</h1>
                <p class="text-gray-700">
                    欢迎来到《太阳系争夺战3》非官方维基！在这里，你可以找到关于游戏的详细信息、攻略、以及社区制作的内容。我们鼓励大家在探索游戏时使用此指南，并帮助完善维基内容。
                </p>
            </section>

            <!-- 导航板块 -->
            <section>
                <h2 class="text-2xl font-bold mb-4">导航</h2>

                <!-- 导航板块布局 -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- 游戏版本 -->
                    <div class="bg-blue-200 p-4 rounded-lg shadow-lg cursor-pointer" @click="goToVersionsPage">
                        <h3 class="text-xl font-bold mb-2">游戏版本</h3>
                        <p>了解不同版本的游戏内容和更新信息。</p>
                    </div>

                    <!-- 天体 -->
                    <div class="bg-green-200 p-4 rounded-lg shadow-lg">
                        <h3 class="text-xl font-bold mb-2">天体</h3>
                        <p>探索游戏中不同的星系和天体。</p>
                    </div>

                    <!-- 势力 -->
                    <div class="bg-yellow-200 p-4 rounded-lg shadow-lg">
                        <h3 class="text-xl font-bold mb-2">势力</h3>
                        <p>查看游戏中的势力与其相关的信息。</p>
                    </div>

                    <!-- 游戏机制 -->
                    <div class="bg-red-200 p-4 rounded-lg shadow-lg">
                        <h3 class="text-xl font-bold mb-2">游戏机制</h3>
                        <p>深入了解游戏的机制与玩法规则。</p>
                    </div>

                    <!-- 游戏功能 -->
                    <div class="bg-purple-200 p-4 rounded-lg shadow-lg">
                        <h3 class="text-xl font-bold mb-2">游戏功能</h3>
                        <p>查看游戏中的功能和特殊系统。</p>
                    </div>

                    <!-- AI -->
                    <div class="bg-indigo-200 p-4 rounded-lg shadow-lg">
                        <h3 class="text-xl font-bold mb-2">AI</h3>
                        <p>分析游戏中的AI行为和决策系统。</p>
                    </div>

                    <!-- 术语 -->
                    <div class="bg-pink-200 p-4 rounded-lg shadow-lg">
                        <h3 class="text-xl font-bold mb-2">术语</h3>
                        <p>游戏内术语的解释和定义。</p>
                    </div>

                    <!-- 其他 -->
                    <div class="bg-gray-200 p-4 rounded-lg shadow-lg">
                        <h3 class="text-xl font-bold mb-2">其他</h3>
                        <p>其他相关的游戏内容。</p>
                    </div>
                </div>
            </section>
        </div>
    </HomeLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';  // 使用 Inertia.js 路由对象
import NavBar from '@/Components/Solarmax3Wiki/NavBar.vue';
import HomeLayout from '@/Layouts/Solarmax3Wiki/HomeLayout.vue';

// 导航到游戏版本页面
const goToVersionsPage = () => {
    router.visit('/gamewiki/versions');  // 使用 Inertia.js 路由对象跳转
};
</script>

<style scoped>
/* 根据需要自定义样式 */
.cursor-pointer {
    cursor: pointer;
}
</style>

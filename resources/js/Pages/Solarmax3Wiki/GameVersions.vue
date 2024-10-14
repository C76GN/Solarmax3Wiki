<template>
    <div class="bg-gray-100 min-h-screen p-6">
        <!-- 页面标题 -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-camp-black">游戏版本</h1>
        </div>

        <!-- 筛选栏 -->
        <div class="mb-4 flex justify-center">
            <input v-model="search" type="text" placeholder="搜索版本..."
                class="p-2 border border-camp-black rounded-lg w-1/2" />
            <button class="ml-2 bg-camp-black text-white px-4 py-2 rounded-lg" @click="filterVersions">
                筛选
            </button>
        </div>

        <!-- 游戏版本列表 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="version in filteredVersions" :key="version.id"
                class="p-6 bg-white border border-gray-200 rounded-lg shadow-lg">
                <h2 class="text-xl font-bold mb-2">{{ version.name }}</h2>
                <p class="text-gray-700">作者: {{ version.author }}</p>
                <p class="text-gray-700">难度: {{ version.difficulty }}</p>
                <p class="text-gray-700">关卡数: {{ version.levels }}</p>
                <p class="text-gray-700">更新日志: {{ version.latest_update }}</p>
                <!-- 更多详情按钮 -->
                <button class="mt-4 bg-camp-black text-white px-4 py-2 rounded-lg" @click="showVersionDetails(version)">
                    查看详情
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const search = ref(''); // 用于存储搜索框中的值
const versions = ref([
    { id: 1, name: '版本1', author: '作者A', difficulty: '中等', levels: 10, latest_update: '增加了新关卡' },
    { id: 2, name: '版本2', author: '作者B', difficulty: '困难', levels: 20, latest_update: '修复了BUG' },
    // 更多测试数据可以在此添加
]);

// 计算筛选后的版本列表
const filteredVersions = computed(() => {
    return versions.value.filter((version) =>
        version.name.toLowerCase().includes(search.value.toLowerCase()) ||
        version.author.toLowerCase().includes(search.value.toLowerCase())
    );
});

// 点击筛选按钮时触发
const filterVersions = () => {
    // 这里可以添加更多筛选逻辑
};

// 点击"查看详情"时展示详细信息
const showVersionDetails = (version) => {
    alert(`游戏版本: ${version.name}, 作者: ${version.author}`);
};
</script>

<style scoped>
/* 你可以在这里添加额外的自定义样式 */
</style>

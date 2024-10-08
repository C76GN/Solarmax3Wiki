<template>
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center" @click="handleBackgroundClick">
        <div class="bg-white p-6 rounded-lg shadow-lg" @click.stop>
            <h2 class="text-xl font-bold mb-4">{{ title }}</h2>
            <slot></slot> <!-- 用于插入内容 -->
            <button @click="$emit('close')" class="mt-4 bg-gray-800 text-white px-4 py-2 rounded">关闭</button>
            <h6 class="font-serif opacity-75 text-xs ">或点击窗口外和使用Esc键关闭</h6>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        title: {
            type: String,
            required: true
        }
    },
    mounted() {
        // 添加键盘事件监听
        window.addEventListener('keydown', this.handleKeyDown);
    },
    beforeDestroy() {
        // 移除键盘事件监听
        window.removeEventListener('keydown', this.handleKeyDown);
    },
    methods: {
        handleKeyDown(event) {
            if (event.key === 'Escape') {
                this.$emit('close'); // 触发关闭事件
            }
        },
        handleBackgroundClick() {
            this.$emit('close'); // 点击背景时触发关闭事件
        }
    }
}
</script>

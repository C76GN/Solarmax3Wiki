<template>
    <!-- 弹窗外层容器，全屏显示，半透明黑色背景，用 Flexbox 内容居中显示，其他元素之上显示，点击背景区域时，触发 closeOnBackgroundClick 函数，关闭弹窗 -->
    <div class="fixed inset-0 bg-camp-black bg-opacity-50 flex items-center justify-center z-50"
        @click="closeOnBackgroundClick">
        <!-- 弹窗内层容器，白色背景，圆角，阴影效果，点击不会触发关闭 -->
        <div class="bg-cua4 p-6 rounded-lg shadow-lg" @click.stop>
            <!-- 弹窗标题，传入 title 属性，设置字体，设置下边距 -->
            <h2 class="text-xl font-bold mb-4">{{ title }}</h2>
            <!-- 默认插槽，用于传入自定义内容 -->
            <slot></slot>
            <!-- 关闭按钮，点击触发close事件，设置上边距，深灰色背景，白色字体，设置左右边距，圆角 -->
            <button @click="$emit('close')" class="mt-4 bg-gray-800 text-white px-4 py-2 rounded">
                关闭
            </button>
            <!-- 衬线字体，设置不透明度，设置字号-->
            <h6 class="font-serif opacity-75 text-xs">或点击窗口外和使用Esc键关闭</h6>
        </div>
    </div>
</template>

<script setup>
//导入 onMounted和onBeforeUnmount来处理生命周期事件，defineProps 用于定义组件的属性，defineEmits 用于定义组件可以触发的事件。
import { onMounted, onBeforeUnmount, defineProps, defineEmits } from "vue";

// 定义 props
// 使用 defineProps 函数定义组件接收的属性
const props = defineProps({
    title: {
        type: String, // 指定 title 必须是字符串类型
        required: true, // 指定 title 是必传的属性
    },
});

// 定义 emits
// 使用 defineEmits 函数定义组件可以发出的事件
const emit = defineEmits(["close"]);

// 键盘事件处理
// 定义处理键盘事件的函数
const handleKeyDown = (event) => {
    if (event.key === "Escape") {
        emit("close"); // 如果按下的是 Esc 键，触发 close 事件
    }
};
// 点击背景处理
// 定义处理点击背景关闭弹窗的函数
const closeOnBackgroundClick = () => {
    emit("close"); // 点击背景时触发 close 事件
};

// 添加和移除事件监听
// 在组件挂载时添加键盘事件监听器
onMounted(() => {
    window.addEventListener("keydown", handleKeyDown);
});

// 在组件卸载前移除键盘事件监听器
onBeforeUnmount(() => {
    window.removeEventListener("keydown", handleKeyDown);
});
</script>

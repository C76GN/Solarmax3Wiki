<!-- 一个“危险”按钮
默认状态：深红色按钮 (bg-red-600)，白色文字。
悬停效果：背景颜色变为浅红色 (hover:bg-red-500)。
聚焦效果：出现 2px 的红色聚焦环 (focus:ring-red-500)。
点击时：背景颜色变为更深的红色 (active:bg-red-700)。
-->

<template>
    <button
        class="relative mt-4 inline-flex items-center overflow-hidden rounded-md border border-transparent bg-red-600 justify-center px-4 py-2 text-sm font-medium uppercase tracking-widest text-white transition duration-300 ease-in-out hover:bg-red-500 hover:ring-2 hover:ring-rose-600 focus:outline-none active:bg-red-700 active:scale-95"
        @click="createRipple">
        <slot /> <!-- 插槽可用于自定义按钮文本 -->
    </button>
</template>



<script setup>
// 涟漪效果创建函数
const createRipple = (event) => {
    const button = event.currentTarget;

    // 计算点击位置
    const rect = button.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;

    // 创建涟漪元素
    const circle = document.createElement('span');
    circle.classList.add('circle');
    circle.style.top = `${y}px`;
    circle.style.left = `${x}px`;

    // 优化：仅添加动画后自动删除的类，无需手动移除
    button.appendChild(circle);

    // 自动删除涟漪元素
    circle.addEventListener('animationend', () => {
        circle.remove();
    });
};
</script>

<style scoped>
:deep(.circle) {
    position: absolute;
    background-color: rgba(255, 255, 255, 0.5);
    /* 涟漪的颜色 */
    width: 100px;
    height: 100px;
    border-radius: 50%;
    transform: translate(-50%, -50%) scale(0);
    animation: ripple-scale 0.5s ease-out;
    pointer-events: none;
    /* 禁止点击事件影响涟漪 */
}

@keyframes ripple-scale {
    to {
        transform: translate(-50%, -50%) scale(3);
        opacity: 0;
    }
}
</style>
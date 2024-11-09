<template>
    <div class="relative">
        <!-- 下拉菜单触发区域：点击切换菜单的显示状态 -->
        <div @click="open = !open">
            <slot name="trigger" />
        </div>

        <!-- 全屏下拉菜单覆盖层：在菜单打开时覆盖屏幕，点击关闭菜单 -->
        <div v-show="open" class="fixed inset-0 z-40" @click="open = false"></div>

        <!-- 下拉菜单内容区域：带有显示/隐藏动画 -->
        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100" leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <div v-show="open" class="absolute z-50 mt-2 w-48 rounded-md shadow-lg" :class="alignmentClasses"
                style="display: none">
                <!-- 菜单内容：使用传入的样式类和插槽内容 -->
                <div class="rounded-md ring-1 ring-black ring-opacity-5" :class="contentClasses">
                    <slot name="content" />
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { computed, ref, watchEffect } from 'vue';

// 定义组件属性
const props = defineProps({
    align: { type: String, default: 'right' },      // 定义菜单对齐方向，可选值：'left'，'right'，'center'
    contentClasses: { type: String, default: 'py-1 bg-sky-400' }, // 菜单内容区域的额外样式类
});

// 控制下拉菜单显示状态，默认状态为关闭
const open = ref(false);

// 定义键盘事件处理函数，用于检测 Escape 键关闭菜单
const closeOnEscape = (e) => {
    if (open.value && e.key === 'Escape') open.value = false;
};

// 监听键盘事件，当按下 Escape 键时关闭菜单
watchEffect((onCleanup) => {
    document.addEventListener('keydown', closeOnEscape);     // 添加事件监听
    onCleanup(() => document.removeEventListener('keydown', closeOnEscape)); // 组件销毁时移除监听
});

// 根据 align 属性计算菜单对齐的样式类
const alignmentClasses = computed(() => ({
    left: 'ltr:origin-top-left rtl:origin-top-right start-0',  // 左对齐样式
    right: 'ltr:origin-top-right rtl:origin-top-left end-0',   // 右对齐样式
    center: 'origin-top',                                      // 居中样式
}[props.align] || 'origin-top'));                              // 默认居中对齐

</script>

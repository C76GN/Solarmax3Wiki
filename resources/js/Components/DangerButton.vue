<!-- 一个“危险”按钮
默认状态：深红色按钮 (bg-red-600)，白色文字。
悬停效果：背景颜色变为浅红色 (hover:bg-red-500)。
聚焦效果：出现 2px 的红色聚焦环 (focus:ring-red-500)。
点击时：背景颜色变为更深的红色 (active:bg-red-700)。
-->
<template>
    <button
        class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 active:bg-red-700"
    >
        <slot />
    </button>
</template>

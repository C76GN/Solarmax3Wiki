// FileName: /var/www/Solarmax3Wiki/resources/js/Components/Other/FloatingMenu.vue
<template>
    <div :class="['menu', { active: menuActive }]" @click.stop="toggleMenu">
        <!-- 中心的触发按钮 -->
        <div class="toggle" @click.stop="toggleMenu"
            :class="{ rotating: isRotating, 'rotating--collapse': !menuActive }">
            <div class="toggle-inner" :class="{ flipped: isFlipped }">
                <!-- 正面图标 -->
                <div class="toggle-front">
                    <font-awesome-icon :icon="['fas', 'bars-staggered']" :beat-fade="!menuActive" />
                </div>
                <!-- 背面图标 -->
                <div class="toggle-back">
                    <font-awesome-icon :icon="['fas', 'xmark']" size="lg" />
                </div>
            </div>
        </div>

        <!-- 展开的子按钮 -->
        <li class="menu-item" v-for="(icon, index) in icons" :key="index" :style="{ '--i': index }">
            <button @click.stop="openModal(index)">
                <font-awesome-icon :icon="icon" v-bind="iconProps(index)" />
            </button>
        </li>

        <!-- 弹窗内容 -->
        <Modal :show="modalStates.community" @close="modalStates.community = false" maxWidth="sm" position="center"
            :closeable="true">
            <CommunityContent />
        </Modal>
        <Modal :show="modalStates.github" @close="modalStates.github = false" maxWidth="xl" position="center"
            :closeable="true">
            <GitHubContent />
        </Modal>
        <Modal :show="modalStates.download" @close="modalStates.download = false" maxWidth="xl" position="center"
            :closeable="true">
            <DownloadContent />
        </Modal>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import Modal from '@/Components/Modal/Modal.vue';
import CommunityContent from '@/Components/Modal/ModalContent/CommunityContent.vue';
import GitHubContent from '@/Components/Modal/ModalContent/GitHubContent.vue';
import DownloadContent from '@/Components/Modal/ModalContent/DownloadContent.vue';

// 定义菜单状态，控制菜单是否展开
const menuActive = ref(false);
// 控制翻转状态，用于图标翻转效果
const isFlipped = ref(false);
// 控制旋转状态，用于按钮旋转效果
const isRotating = ref(false);

// 定义图标数组，包含 Font Awesome 图标的名称
const icons = [
    ['fas', 'user-group'],
    ['fab', 'github'],
    ['fas', 'download'],
    ['fas', 'link-slash'],
    ['fas', 'link-slash'],
    ['fas', 'link-slash'],
    ['fas', 'link-slash'],
    ['fas', 'link-slash'],
];

// 根据图标索引返回相应的属性
const iconProps = (index) => ({
    beatFade: index === 0, // 第一个图标添加脉动效果
    flip: index === 1,     // 第二个图标添加翻转效果
    bounce: index === 2,   // 第三个图标添加跳动效果
});

// 定义弹窗状态，控制各个弹窗的显示与隐藏
const modalStates = ref({
    community: false,
    github: false,
    download: false,
});

// 切换菜单状态和按钮的翻转、旋转动画
const toggleMenu = () => {
    menuActive.value = !menuActive.value; // 切换菜单展开状态
    isRotating.value = true; // 开启旋转效果

    // 设置超时，旋转效果完成后翻转图标
    setTimeout(() => {
        isRotating.value = false; // 关闭旋转效果
        isFlipped.value = menuActive.value; // 根据菜单状态翻转图标
    }, 500);
};

// 根据索引打开相应的弹窗
const openModal = (index) => {
    if (index === 0) modalStates.value.community = true; // 打开社区弹窗
    if (index === 1) modalStates.value.github = true; // 打开 GitHub 弹窗
    if (index === 2) modalStates.value.download = true; // 打开下载弹窗
};
</script>

<style scoped>
.menu {
    position: fixed;
    /* 固定在页面右下角 */
    bottom: 1rem;
    /* 距离底部 1rem */
    right: 1rem;
    /* 距离右侧 1rem */
    width: 50px;
    /* 初始宽度 */
    height: 50px;
    /* 初始高度 */
    display: flex;
    /* 使用 flex 布局 */
    align-items: center;
    /* 垂直居中对齐 */
    justify-content: center;
    /* 水平居中对齐 */
    transition: all 0.5s ease;
    /* 平滑过渡效果 */
}

.menu.active {
    bottom: 3rem;
    /* 展开状态时，距离底部 3rem */
    right: 3rem;
    /* 展开状态时，距离右侧 3rem */
    width: 100px;
    /* 展开状态时的宽度 */
    height: 100px;
    /* 展开状态时的高度 */
}

.menu .toggle {
    position: relative;
    /* 设为相对定位 */
    height: 40px;
    /* 按钮高度 */
    width: 40px;
    /* 按钮宽度 */
    background: linear-gradient(135deg, #3dc8ff, #3b82f6);
    /* 渐变背景 */
    opacity: 0.5;
    /* 初始透明度 */
    border-radius: 50%;
    /* 圆形按钮 */
    display: flex;
    /* 使用 flex 布局 */
    align-items: center;
    /* 垂直居中对齐 */
    justify-content: center;
    /* 水平居中对齐 */
    cursor: pointer;
    /* 鼠标指针为手型 */
    transition: transform 0.5s ease, opacity 0.5s ease, background-color 0.5s ease, box-shadow 0.5s ease;
    /* 平滑过渡效果 */
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    /* 阴影效果 */
    z-index: 5;
    /* 确保在其他元素上方 */
    perspective: 1000px;
    /* 设置 3D 效果的透视 */
}

.menu .toggle:hover {
    opacity: 1;
    /* 鼠标悬停时不透明度为 1 */
    transform: scale(1.1);
    /* 鼠标悬停时缩放效果 */
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
    /* 鼠标悬停时阴影效果 */
}

.menu.active .toggle {
    transform: scale(1.5);
    /* 展开状态时的缩放效果 */
    opacity: 1;
    /* 不透明度为 1 */
    box-shadow: 0 0 0 2px rgba(0, 76, 150, 0.5);
    /* 展开状态时的阴影效果 */
}

.menu.active .toggle:hover {
    transform: scale(1.7);
    /* 鼠标悬停时的缩放效果 */
    box-shadow: 0 0 0 4px rgba(0, 76, 150, 0.5);
    /* 鼠标悬停时的阴影效果 */
}

.menu .toggle.rotating {
    animation: rotate360 0.5s forwards;
    /* 旋转动画 */
}

.menu .toggle.rotating--collapse {
    animation: rotate-360 0.5s forwards;
    /* 收缩时的旋转动画 */
}

@keyframes rotate360 {
    from {
        transform: scale(1) rotate(0deg);
        /* 从初始状态开始 */
    }

    to {
        transform: scale(1.5) rotate(360deg);
        /* 结束状态，放大并旋转 */
    }
}

@keyframes rotate-360 {
    from {
        transform: scale(1.5) rotate(0deg);
        /* 从放大状态开始 */
    }

    to {
        transform: scale(1) rotate(-360deg);
        /* 结束状态，恢复大小并旋转 */
    }
}

.toggle-inner {
    position: relative;
    /* 设为相对定位 */
    width: 100%;
    /* 占满父容器宽度 */
    height: 100%;
    /* 占满父容器高度 */
    transform-style: preserve-3d;
    /* 保持 3D 效果 */
    transition: transform 0.5s ease;
    /* 平滑过渡效果 */
}

.toggle-inner.flipped {
    transform: rotateY(180deg);
    /* 翻转效果 */
}

.toggle-front,
.toggle-back {
    position: absolute;
    /* 设为绝对定位 */
    width: 100%;
    /* 占满父容器宽度 */
    height: 100%;
    /* 占满父容器高度 */
    backface-visibility: hidden;
    /* 背面不可见 */
    display: flex;
    /* 使用 flex 布局 */
    align-items: center;
    /* 垂直居中对齐 */
    justify-content: center;
    /* 水平居中对齐 */
}

.toggle-back {
    transform: rotateY(180deg);
    /* 背面图标翻转 */
}

.menu-item {
    position: absolute;
    /* 设为绝对定位 */
    list-style: none;
    /* 去掉列表样式 */
    opacity: 0;
    /* 初始不透明度为 0 */
    transform: scale(0);
    /* 初始缩放为 0 */
    transition: transform 0.3s ease, opacity 0.3s ease;
    /* 平滑过渡效果 */
}

.menu.active .menu-item {
    opacity: 1;
    /* 展开状态时不透明度为 1 */
    transform: scale(1) rotate(calc(var(--i) * 45deg)) translate(70px) rotate(calc(var(--i) * -45deg));
    /* 展开状态时进行缩放和旋转 */
    transition-delay: calc(0.1s * var(--i));
    /* 根据索引设置延迟 */
}

.menu-item button {
    background: #74e3ff;
    /* 按钮背景色 */
    border-radius: 50%;
    /* 圆形按钮 */
    height: 48px;
    /* 按钮高度 */
    width: 48px;
    /* 按钮宽度 */
    color: #000000;
    /* 按钮文字颜色 */
    display: flex;
    /* 使用 flex 布局 */
    align-items: center;
    /* 垂直居中对齐 */
    justify-content: center;
    /* 水平居中对齐 */
    font-size: 1rem;
    /* 字体大小 */
    transition: background 0.3s, transform 0.3s;
    /* 背景和缩放的过渡效果 */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    /* 阴影效果 */
}

.menu-item button:hover {
    background: #38a2d3;
    /* 鼠标悬停时的背景色 */
    transform: scale(1.1);
    /* 鼠标悬停时的缩放效果 */
}

.menu.active .menu-item button:active,
.menu.active .menu-item button:focus {
    background: #267092;
    /* 按钮被点击或获得焦点时的背景色 */
    transform: scale(1.2);
    /* 按钮被点击或获得焦点时的缩放效果 */
}
</style>

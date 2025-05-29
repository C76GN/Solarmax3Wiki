<template>
    <!-- 画布：用于显示星空效果 -->
    <canvas></canvas>
</template>

<script setup>
import { onMounted } from 'vue';

onMounted(() => {
    // 星星颜色
    const STAR_COLOR = '#5FB6FF';
    // 星星尺寸
    const STAR_SIZE = 3;
    // 星星最小缩放比例，模拟星星距离远近
    const STAR_MIN_SCALE = 0.2;
    // 星星在画布外允许的最大偏移距离
    const OVERFLOW_THRESHOLD = 50;
    // 星星数量，根据窗口大小调整
    const STAR_COUNT = (window.innerWidth + window.innerHeight) / 8;

    // 获取画布元素和2D绘图上下文
    const canvas = document.querySelector('canvas');
    const context = canvas.getContext('2d');

    // 如果获取失败则中止
    if (!context) return;

    // 初始化画布和星星参数
    let scale = 1;
    let width, height;
    let stars = [];
    let pointerX, pointerY;
    let velocity = { x: 0, y: 0, tx: 0, ty: 0, z: 0.002 };
    let touchInput = false;

    generateStars();
    resize();
    step();

    // 监听窗口变化和用户输入
    window.onresize = resize;
    document.addEventListener('mousemove', onMouseMove);
    document.addEventListener('touchmove', onTouchMove, { passive: false });
    document.addEventListener('mouseleave', onMouseLeave);

    function onTouchMove(event) {
        if (shouldPreventDefault(event)) event.preventDefault();
        touchInput = true;
        movePointer(event.touches[0].clientX, event.touches[0].clientY, true);
    }

    function shouldPreventDefault(event) {
        // 如果事件目标是画布，阻止默认行为
        return event.target.tagName.toLowerCase() === 'canvas';
    }

    // 生成星星并设置随机缩放比例
    function generateStars() {
        for (let i = 0; i < STAR_COUNT; i++) {
            stars.push({
                x: 0,
                y: 0,
                z: STAR_MIN_SCALE + Math.random() * (1 - STAR_MIN_SCALE),
            });
        }
    }

    // 随机设置星星在画布中的位置
    function placeStar(star) {
        star.x = Math.random() * width;
        star.y = Math.random() * height;
    }

    // 当星星超出边界时重新设置位置
    function recycleStar(star) {
        let direction = 'z';
        let vx = Math.abs(velocity.x);
        let vy = Math.abs(velocity.y);

        // 确定星星从哪个方向进入画布
        if (vx > 1 || vy > 1) {
            let axis = vx > vy ? (Math.random() < vx / (vx + vy) ? 'h' : 'v') : (Math.random() < vy / (vx + vy) ? 'v' : 'h');
            direction = axis === 'h' ? (velocity.x > 0 ? 'l' : 'r') : (velocity.y > 0 ? 't' : 'b');
        }

        star.z = STAR_MIN_SCALE + Math.random() * (1 - STAR_MIN_SCALE);

        // 根据方向设置星星位置
        if (direction === 'z') {
            star.z = 0.1;
            star.x = Math.random() * width;
            star.y = Math.random() * height;
        } else if (direction === 'l') {
            star.x = -OVERFLOW_THRESHOLD;
            star.y = height * Math.random();
        } else if (direction === 'r') {
            star.x = width + OVERFLOW_THRESHOLD;
            star.y = height * Math.random();
        } else if (direction === 't') {
            star.x = width * Math.random();
            star.y = -OVERFLOW_THRESHOLD;
        } else if (direction === 'b') {
            star.x = width * Math.random();
            star.y = height + OVERFLOW_THRESHOLD;
        }
    }

    // 调整画布大小以适应窗口
    function resize() {
        scale = 1;
        width = window.innerWidth;
        height = window.innerHeight;
        canvas.style.width = `${width}px`;
        canvas.style.height = `${height}px`;
        canvas.width = width * scale;
        canvas.height = height * scale;
        context.scale(scale, scale);
        stars.forEach(placeStar);
    }

    // 动画的每一帧
    function step() {
        context.clearRect(0, 0, width, height);
        update();
        render();
        requestAnimationFrame(step);
    }

    // 更新星星位置和速度
    function update() {
        velocity.tx *= 0.96;
        velocity.ty *= 0.96;
        velocity.x += (velocity.tx - velocity.x) * 0.8;
        velocity.y += (velocity.ty - velocity.y) * 0.8;

        stars.forEach((star) => {
            star.x += velocity.x * star.z;
            star.y += velocity.y * star.z;
            star.x += (star.x - width / 2) * velocity.z * star.z;
            star.y += (star.y - height / 2) * velocity.z * star.z;
            star.z += velocity.z;

            if (star.x < -OVERFLOW_THRESHOLD || star.x > width + OVERFLOW_THRESHOLD || star.y < -OVERFLOW_THRESHOLD || star.y > height + OVERFLOW_THRESHOLD) {
                recycleStar(star);
            }
        });
    }

    // 绘制星星
    function render() {
        stars.forEach((star) => {
            context.beginPath();
            context.lineCap = 'round';
            context.lineWidth = STAR_SIZE * star.z * scale;
            context.globalAlpha = 0.5 + 0.5 * Math.random();
            context.strokeStyle = STAR_COLOR;
            context.moveTo(star.x, star.y);

            let tailX = velocity.x * 2;
            let tailY = velocity.y * 2;

            if (Math.abs(tailX) < 0.1) tailX = 0.5;
            if (Math.abs(tailY) < 0.1) tailY = 0.5;

            context.lineTo(star.x + tailX, star.y + tailY);
            context.stroke();
        });
    }

    // 更新星星的目标方向和速度
    function movePointer(x, y) {
        if (typeof pointerX === 'number' && typeof pointerY === 'number') {
            let ox = x - pointerX;
            let oy = y - pointerY;
            velocity.tx += (ox / 8) * scale * (touchInput ? 1 : -1);
            velocity.ty += (oy / 8) * scale * (touchInput ? 1 : -1);
        }
        pointerX = x;
        pointerY = y;
    }

    // 鼠标移动事件
    function onMouseMove(event) {
        touchInput = false;
        movePointer(event.clientX, event.clientY);
    }

    // 鼠标离开事件
    function onMouseLeave() {
        pointerX = null;
        pointerY = null;
    }
});
</script>

<style scoped>
canvas {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 0;
    pointer-events: none;
}
</style>

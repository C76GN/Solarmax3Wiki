<template>
    <!-- 画布，用于绘制星空 -->
    <canvas></canvas>
</template>

<script setup>
import { onMounted } from 'vue';

onMounted(() => {
    // 定义星星的颜色
    const STAR_COLOR = '#5FB6FF';
    // 定义星星的大小
    const STAR_SIZE = 3;
    // 定义星星的最小缩放比例，控制星星的远近效果
    const STAR_MIN_SCALE = 0.2;
    // 定义星星在屏幕外溢出时的阈值，超出此范围的星星会被重新生成
    const OVERFLOW_THRESHOLD = 50;
    // 根据窗口尺寸计算星星的数量，星星数量与窗口的宽度和高度成正比
    const STAR_COUNT = (window.innerWidth + window.innerHeight) / 8;

    // 获取画布元素
    const canvas = document.querySelector('canvas');
    // 获取2D上下文，用于在画布上绘制
    const context = canvas.getContext('2d');

    // 如果无法获取2D上下文，直接返回，避免执行后续操作
    if (!context) {
        return;
    }

    // 定义缩放比例，适配不同设备的像素密度
    let scale = 1;
    // 定义画布的宽度和高度
    let width, height;
    // 定义星星数组，保存所有星星的信息
    let stars = [];
    // 定义鼠标指针位置
    let pointerX, pointerY;
    // 定义星星的速度和缩放相关的属性
    let velocity = { x: 0, y: 0, tx: 0, ty: 0, z: 0.002 };
    // 标识是否为触摸输入
    let touchInput = false;

    // 调用函数生成星星
    generateStars();
    // 调整画布大小，使其适应窗口
    resize();
    // 启动动画帧循环
    step();

    // 监听窗口大小变化时的事件，重新调整画布大小
    window.onresize = resize;
    // 监听鼠标移动事件，更新星星运动方向
    document.addEventListener('mousemove', onMouseMove);
    // 监听触摸移动事件，并防止默认的滚动行为
    document.addEventListener('touchmove', onTouchMove, { passive: false });
    // 监听鼠标离开事件，停止对鼠标位置的追踪
    document.addEventListener('mouseleave', onMouseLeave);

    function onTouchMove(event) {
        if (shouldPreventDefault(event)) {
            event.preventDefault();
        }
        touchInput = true;
        movePointer(event.touches[0].clientX, event.touches[0].clientY, true);
    }

    function shouldPreventDefault(event) {
        // 例如：只在触摸特定元素时阻止默认行为
        return event.target.tagName.toLowerCase() === 'canvas';
    }

    /**
     * 生成初始的星星，随机设置它们的缩放比例
     */
    function generateStars() {
        for (let i = 0; i < STAR_COUNT; i++) {
            stars.push({
                x: 0,
                y: 0,
                z: STAR_MIN_SCALE + Math.random() * (1 - STAR_MIN_SCALE), // 随机生成星星的远近缩放
            });
        }
    }

    /**
     * 将星星放置在画布上的随机位置
     * @param {Object} star - 星星对象
     */
    function placeStar(star) {
        star.x = Math.random() * width;  // 在宽度范围内随机生成X坐标
        star.y = Math.random() * height; // 在高度范围内随机生成Y坐标
    }

    /**
     * 当星星超出视野时，重新生成它的位置
     * @param {Object} star - 星星对象
     */
    function recycleStar(star) {
        let direction = 'z'; // 默认的方向是z轴，表示星星在深度上移动
        let vx = Math.abs(velocity.x); // 获取水平速度的绝对值
        let vy = Math.abs(velocity.y); // 获取垂直速度的绝对值

        // 根据速度确定星星从哪个方向进入画布
        if (vx > 1 || vy > 1) {
            let axis = vx > vy ? (Math.random() < vx / (vx + vy) ? 'h' : 'v') : (Math.random() < vy / (vx + vy) ? 'v' : 'h');
            if (axis === 'h') direction = velocity.x > 0 ? 'l' : 'r'; // 水平方向：从左或右进入
            else direction = velocity.y > 0 ? 't' : 'b'; // 垂直方向：从上或下进入
        }

        // 随机设置星星的缩放比例
        star.z = STAR_MIN_SCALE + Math.random() * (1 - STAR_MIN_SCALE);

        // 根据不同的方向放置星星
        if (direction === 'z') {
            star.z = 0.1; // 生成一个非常近的星星
            star.x = Math.random() * width;
            star.y = Math.random() * height;
        } else if (direction === 'l') {
            star.x = -OVERFLOW_THRESHOLD; // 从左边进入
            star.y = height * Math.random();
        } else if (direction === 'r') {
            star.x = width + OVERFLOW_THRESHOLD; // 从右边进入
            star.y = height * Math.random();
        } else if (direction === 't') {
            star.x = width * Math.random(); // 从上面进入
            star.y = -OVERFLOW_THRESHOLD;
        } else if (direction === 'b') {
            star.x = width * Math.random(); // 从下面进入
            star.y = height + OVERFLOW_THRESHOLD;
        }
    }

    /**
     * 调整画布大小，适应窗口的变化
     */
    function resize() {
        scale = 1; // 取消设备像素比的缩放
        width = window.innerWidth;  // 设置画布的宽度为窗口宽度
        height = window.innerHeight; // 设置画布的高度为窗口高度
        canvas.style.width = `${width}px`;  // 设置画布样式宽度
        canvas.style.height = `${height}px`; // 设置画布样式高度
        canvas.width = width * scale;  // 实际绘制的宽度
        canvas.height = height * scale; // 实际绘制的高度
        context.scale(scale, scale);  // 设置上下文缩放比例

        // 重新放置星星在画布中的位置
        stars.forEach(placeStar);
    }

    /**
     * 动画循环的每一帧
     */
    function step() {
        // 清空画布
        context.clearRect(0, 0, width, height);
        // 更新星星的位置
        update();
        // 渲染星星
        render();
        // 请求下一帧
        requestAnimationFrame(step);
    }

    /**
     * 更新星星的位置和速度
     */
    function update() {
        // 缓动速度
        velocity.tx *= 0.96;
        velocity.ty *= 0.96;
        // 更新速度
        velocity.x += (velocity.tx - velocity.x) * 0.8;
        velocity.y += (velocity.ty - velocity.y) * 0.8;

        // 更新每颗星星的位置和缩放
        stars.forEach((star) => {
            star.x += velocity.x * star.z;
            star.y += velocity.y * star.z;
            star.x += (star.x - width / 2) * velocity.z * star.z;
            star.y += (star.y - height / 2) * velocity.z * star.z;
            star.z += velocity.z;

            // 当星星超出画布边界时重新生成位置
            if (star.x < -OVERFLOW_THRESHOLD || star.x > width + OVERFLOW_THRESHOLD || star.y < -OVERFLOW_THRESHOLD || star.y > height + OVERFLOW_THRESHOLD) {
                recycleStar(star);
            }
        });
    }

    /**
     * 渲染每颗星星
     */
    function render() {
        stars.forEach((star) => {
            context.beginPath();
            context.lineCap = 'round';
            context.lineWidth = STAR_SIZE * star.z * scale;
            context.globalAlpha = 0.5 + 0.5 * Math.random(); // 使星星闪烁
            context.strokeStyle = STAR_COLOR;
            context.moveTo(star.x, star.y);

            // 绘制星星的尾迹效果
            let tailX = velocity.x * 2;
            let tailY = velocity.y * 2;

            // 控制尾迹的长度
            if (Math.abs(tailX) < 0.1) tailX = 0.5;
            if (Math.abs(tailY) < 0.1) tailY = 0.5;

            context.lineTo(star.x + tailX, star.y + tailY);
            context.stroke();
        });
    }

    /**
     * 移动鼠标指针时，更新星星的运动方向
     * @param {Number} x - 鼠标的X坐标
     * @param {Number} y - 鼠标的Y坐标
     */
    function movePointer(x, y) {
        if (typeof pointerX === 'number' && typeof pointerY === 'number') {
            let ox = x - pointerX;
            let oy = y - pointerY;
            // 更新目标速度，鼠标移动会影响星星的运动方向
            velocity.tx = velocity.tx + (ox / 8) * scale * (touchInput ? 1 : -1);
            velocity.ty = velocity.ty + (oy / 8) * scale * (touchInput ? 1 : -1);
        }
        pointerX = x;
        pointerY = y;
    }

    /**
     * 鼠标移动时的事件处理函数
     */
    function onMouseMove(event) {
        touchInput = false;
        movePointer(event.clientX, event.clientY);
    }

    /**
     * 鼠标离开画布时的事件处理函数
     */
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

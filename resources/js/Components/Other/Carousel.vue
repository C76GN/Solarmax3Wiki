<template>
    <div class="carousel-container relative rounded-lg" role="region" aria-roledescription="carousel"
        @wheel="handleWheel" @mouseenter="pauseAutoPlay" @mouseleave="resumeAutoPlay" @keydown="handleKeyDown"
        tabindex="0">
        <!-- 轮播图轨道，用于滑动显示不同的图片 -->
        <div class="carousel-track transition-transform duration-300" aria-live="polite"
            :style="{ transform: `translateX(-${currentIndex * 100}%)` }">
            <div v-for="(image, index) in images" :key="index" class="carousel-slide"
                :aria-hidden="currentIndex !== index">
                <a :href="image.link" class="carousel-link" :tabindex="currentIndex === index ? 0 : -1">
                    <img :src="image.src" :alt="image.alt" class="carousel-image"
                        :loading="shouldLazyLoad(index) ? 'lazy' : 'eager'" />
                </a>
            </div>
        </div>

        <!-- 左侧导航按钮，用于切换到上一张图片 -->
        <button v-if="showNavButtons && images.length > 1" @click="previousSlide"
            class="absolute left-4 top-1/2 -translate-y-1/2 bg-black/30 hover:bg-black/50 text-white p-2 rounded-full transition-colors"
            aria-label="Previous slide">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>

        <!-- 右侧导航按钮，用于切换到下一张图片 -->
        <button v-if="showNavButtons && images.length > 1" @click="nextSlide"
            class="absolute right-4 top-1/2 -translate-y-1/2 bg-black/30 hover:bg-black/50 text-white p-2 rounded-full transition-colors"
            aria-label="Next slide">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>

        <!-- 底部导航点，用于快速跳转到指定的图片 -->
        <div v-if="showDots && images.length > 1"
            class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex items-center space-x-2">
            <button v-for="(_, index) in images" :key="index" @click="goToSlide(index)"
                class="h-2 transition-all duration-300 rounded-full bg-white/50 hover:bg-white/80"
                :class="[currentIndex === index ? 'w-8 bg-white' : 'w-2']" :aria-label="`Go to slide ${index + 1}`"
                :aria-current="currentIndex === index">
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';

// 定义组件接收的属性
const props = defineProps({
    images: {
        type: Array,
        required: true,
        validator: (value) => {
            return (
                value.length > 0 &&
                value.every(
                    (image) =>
                        typeof image.src === 'string' &&
                        typeof image.alt === 'string' &&
                        typeof image.link === 'string'
                )
            );
        },
    },
    autoPlayInterval: {
        type: Number,
        default: 5000,
        validator: (value) => value >= 2000,
    },
    showNavButtons: {
        type: Boolean,
        default: true,
    },
    showDots: {
        type: Boolean,
        default: true,
    },
});

// 当前显示的幻灯片索引
const currentIndex = ref(0);
// 自动播放定时器
const autoPlayTimer = ref(null);
// 触摸事件的起始和结束位置
const touchStartX = ref(0);
const touchEndX = ref(0);

// 计算总幻灯片数量
const totalSlides = computed(() => props.images.length);

// 判断某张图片是否需要懒加载
const shouldLazyLoad = (index) => {
    const current = currentIndex.value;
    const isAdjacent =
        Math.abs(index - current) <= 1 ||
        (current === 0 && index === totalSlides.value - 1) ||
        (current === totalSlides.value - 1 && index === 0);
    return !isAdjacent;
};

// 处理键盘按键事件，实现左右箭头切换幻灯片
const handleKeyDown = (e) => {
    if (e.key === 'ArrowLeft') {
        previousSlide();
    } else if (e.key === 'ArrowRight') {
        nextSlide();
    }
};

// 记录触摸开始的位置
const handleTouchStart = (e) => {
    touchStartX.value = e.touches[0].clientX;
};

// 记录触摸结束的位置并判断滑动方向
const handleTouchEnd = (e) => {
    touchEndX.value = e.changedTouches[0].clientX;
    const diff = touchStartX.value - touchEndX.value;

    // 判断滑动距离是否超过最小阈值
    if (Math.abs(diff) > 50) {
        if (diff > 0) {
            nextSlide();
        } else {
            previousSlide();
        }
    }
};

// 处理鼠标滚轮事件，实现上下滚动切换幻灯片
const handleWheel = (e) => {
    e.preventDefault();
    if (e.deltaY > 0) {
        nextSlide();
    } else {
        previousSlide();
    }
    resetAutoPlay();
};

// 切换到下一张幻灯片
const nextSlide = () => {
    if (totalSlides.value <= 1) return;
    currentIndex.value = (currentIndex.value + 1) % totalSlides.value;
    resetAutoPlay();
};

// 切换到上一张幻灯片
const previousSlide = () => {
    if (totalSlides.value <= 1) return;
    currentIndex.value =
        currentIndex.value === 0
            ? totalSlides.value - 1
            : currentIndex.value - 1;
    resetAutoPlay();
};

// 跳转到指定索引的幻灯片
const goToSlide = (index) => {
    if (index >= 0 && index < totalSlides.value) {
        currentIndex.value = index;
        resetAutoPlay();
    }
};

// 启动自动播放功能
const startAutoPlay = () => {
    if (totalSlides.value <= 1) return;
    autoPlayTimer.value = setInterval(() => {
        nextSlide();
    }, props.autoPlayInterval);
};

// 暂停自动播放
const pauseAutoPlay = () => {
    if (autoPlayTimer.value) {
        clearInterval(autoPlayTimer.value);
        autoPlayTimer.value = null;
    }
};

// 恢复自动播放
const resumeAutoPlay = () => {
    resetAutoPlay();
};

// 重置自动播放定时器
const resetAutoPlay = () => {
    pauseAutoPlay();
    startAutoPlay();
};

// 生命周期挂载时启动自动播放并添加触摸事件监听
onMounted(() => {
    startAutoPlay();
    window.addEventListener('touchstart', handleTouchStart);
    window.addEventListener('touchend', handleTouchEnd);
});

// 生命周期卸载时清除自动播放并移除触摸事件监听
onUnmounted(() => {
    pauseAutoPlay();
    window.removeEventListener('touchstart', handleTouchStart);
    window.removeEventListener('touchend', handleTouchEnd);
});
</script>

<style scoped>
.carousel-container {
    width: 100%;
    aspect-ratio: 16 / 9;
    max-height: 320px;
    overflow: hidden;
    background-color: transparent;
    position: relative;
    content-visibility: auto;
}

.carousel-track {
    display: flex;
    height: 100%;
    will-change: transform;
}

.carousel-slide {
    flex: 0 0 100%;
    height: 100%;
    position: relative;
}

.carousel-link {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.carousel-image {
    max-width: 100%;
    max-height: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
}

@media (hover: none) {
    .carousel-container:hover .navigation-button {
        opacity: 0;
    }
}
</style>

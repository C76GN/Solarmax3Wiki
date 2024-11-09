<template>
    <div class="carousel-container relative overflow-hidden rounded-lg shadow-lg">
        <div class="carousel-track flex transition-transform duration-500 ease-in-out"
            :style="{ transform: `translateX(-${currentSlide * 100}%)` }">
            <slot />
        </div>
        <!-- 控制按钮 -->
        <button @click="prevSlide" class="carousel-button left-0">‹</button>
        <button @click="nextSlide" class="carousel-button right-0">›</button>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const currentSlide = ref(0);

// 示例轮播图长度
const slidesCount = 3;

const nextSlide = () => {
    currentSlide.value = (currentSlide.value + 1) % slidesCount;
};

const prevSlide = () => {
    currentSlide.value = (currentSlide.value - 1 + slidesCount) % slidesCount;
};
</script>

<style scoped>
.carousel-container {
    position: relative;
    width: 100%;
    height: 300px;
}

.carousel-track {
    display: flex;
}

.carousel-slide {
    min-width: 100%;
}

.carousel-button {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0, 0, 0, 0.5);
    color: white;
    padding: 0.5rem;
    border-radius: 50%;
    cursor: pointer;
}
</style>

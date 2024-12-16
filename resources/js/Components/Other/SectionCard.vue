// FileName: /var/www/Solarmax3Wiki/resources/js/Components/Other/SectionCard.vue
<template>
    <div class="masonry-item">
        <div class="section-card">
            <h2 class="title">
                <span class="icon">{{ icon }}</span>
                {{ title }}
            </h2>
            <p class="description">{{ description }}</p>
        </div>
    </div>
</template>

<script setup>
import { defineProps } from 'vue';

const props = defineProps({
    title: String,
    icon: String,
    description: String,
});
</script>

<style scoped>
.section-card {
    background-color: rgba(31, 41, 55, 0.95);
    /* 使用更深色的灰色背景 */
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.section-card:hover {
    transform: translateY(-8px);
    box-shadow: 0px 8px 12px rgba(0, 0, 0, 0.2);
}

.title {
    font-size: 1.25rem;
    font-weight: bold;
    color: #fbbf24;
    /* 使用黄色提升可视度 */
    display: flex;
    align-items: center;
}

.icon {
    font-size: 1.5rem;
    margin-right: 0.5rem;
}

.description {
    color: #d1d5db;
    /* 使用浅灰色字体 */
    margin-top: 0.5rem;
    line-height: 1.5;
}
</style>

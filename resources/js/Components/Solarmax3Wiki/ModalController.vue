<template>
    <PopUp v-if="isOpen" @close="closeModal" :title="modalTitle">
        <div v-html="modalContent"></div> <!-- 动态插入内容 -->
    </PopUp>
</template>

<script setup>
import { ref } from 'vue';
import PopUp from './PopUp.vue';

const isOpen = ref(false);
const modalTitle = ref('');
const modalContent = ref('');  // 新增 modalContent 用于存储弹窗内容

const openModal = (title, content) => {
    modalTitle.value = title;
    modalContent.value = content; // 传递内容
    isOpen.value = true;
};

const closeModal = () => {
    isOpen.value = false;
};

defineExpose({
    openModal,
});
</script>

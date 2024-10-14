<template>
    <div class="flex items-center space-x-4 whitespace-nowrap">
        <a href="#" :class="linkClass">中/英</a>
        <button @click="openModal('游戏下载', 'downloadContent')" :class="linkClass">游戏下载</button>
        <button @click="openModal('社群', 'communityContent')" :class="linkClass">社群</button>
        <template v-if="$page.props.auth.user">
            <Link href="/dashboard" :class="linkClass">{{ $page.props.auth.user.name }}</Link>
        </template>
        <template v-else>
            <Link href="/login" :class="linkClass">登录</Link>
            <Link v-if="canRegister" href="/register" :class="linkClass">注册</Link>
        </template>
    </div>
</template>

<script setup>
import { defineEmits } from 'vue';
import { Link } from '@inertiajs/inertia-vue3';

const props = defineProps({
    linkClass: String,
    canRegister: Boolean,
});

const emit = defineEmits(['openModal']);

// 统一管理弹窗的内容
const openModal = (title, contentKey) => {
    emit('openModal', { title, contentKey });
};
</script>

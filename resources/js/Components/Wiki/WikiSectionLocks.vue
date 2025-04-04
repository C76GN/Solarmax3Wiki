<template>
    <div class="section-locks">
        <!-- 锁定状态显示 -->
        <div v-if="activeLocks.length > 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
            <h3 class="text-lg font-medium text-yellow-800 mb-2">区域锁定状态</h3>
            <ul class="space-y-2">
                <li v-for="lock in activeLocks" :key="lock.section_id" class="flex justify-between items-center">
                    <div>
                        <span class="font-medium">{{ lock.section_title || lock.section_id }}</span>
                        <span class="ml-2 text-sm text-gray-600">
                            由 {{ lock.locked_by.name }} 锁定
                            <span class="text-xs">（{{ formatExpiryTime(lock.expires_at) }}）</span>
                        </span>
                    </div>
                    <button v-if="lock.can_unlock" @click="unlockSection(lock.section_id)"
                        class="text-sm text-blue-600 hover:text-blue-800">
                        解锁
                    </button>
                </li>
            </ul>
        </div>

        <!-- 锁定管理面板 -->
        <div v-if="showLockPanel" class="bg-white rounded-lg shadow-lg p-4 mb-4 border border-gray-200">
            <h3 class="text-lg font-medium text-gray-800 mb-4">区域锁定管理</h3>

            <div v-if="availableSections.length === 0" class="text-gray-500 text-center py-4">
                无可锁定的区域
            </div>

            <div v-else>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">选择要锁定的区域</label>
                    <select v-model="selectedSection"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">请选择区域</option>
                        <option v-for="section in availableSections" :key="section.id" :value="section">
                            {{ section.title }}
                        </option>
                    </select>
                </div>

                <div v-if="selectedSection" class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">锁定时长（分钟）</label>
                    <input type="number" v-model.number="lockDuration" min="5" max="120"
                        class="w-32 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" />
                </div>

                <div class="flex justify-end">
                    <button @click="showLockPanel = false"
                        class="mr-2 px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        取消
                    </button>
                    <button @click="lockSection" :disabled="!selectedSection || isLocking"
                        class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50">
                        <span v-if="isLocking">锁定中...</span>
                        <span v-else>锁定区域</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 操作按钮 -->
        <div class="flex mb-4">
            <button @click="showLockPanel = !showLockPanel"
                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 mr-2">
                {{ showLockPanel ? '隐藏锁定面板' : '管理区域锁定' }}
            </button>
            <button @click="refreshLocks"
                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                刷新锁定状态
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    pageId: {
        type: [Number, String],
        required: true
    },
    sections: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['section-locked', 'section-unlocked']);

const locks = ref([]);
const showLockPanel = ref(false);
const selectedSection = ref(null);
const lockDuration = ref(30); // 默认30分钟
const isLocking = ref(false);
const refreshInterval = ref(null);

// 有效的锁
const activeLocks = computed(() => {
    return locks.value.filter(lock => {
        return new Date(lock.expires_at) > new Date();
    });
});

// 可以锁定的区域（未被锁定的区域）
const availableSections = computed(() => {
    return props.sections.filter(section => {
        return !activeLocks.value.some(lock => lock.section_id === section.id);
    });
});

// 格式化过期时间
const formatExpiryTime = (isoTime) => {
    const expiryDate = new Date(isoTime);
    const now = new Date();
    const diffMinutes = Math.round((expiryDate - now) / (1000 * 60));

    if (diffMinutes < 1) {
        return '即将过期';
    } else if (diffMinutes < 60) {
        return `${diffMinutes}分钟后过期`;
    } else {
        const hours = Math.floor(diffMinutes / 60);
        const mins = diffMinutes % 60;
        return `${hours}小时${mins > 0 ? mins + '分钟' : ''}后过期`;
    }
};

// 加载锁定状态
const loadLocks = async () => {
    try {
        const response = await axios.get(`/wiki/${props.pageId}/section-locks`);
        locks.value = response.data.locks;
    } catch (error) {
        console.error('加载区域锁定状态失败:', error);
    }
};

// 刷新锁定状态
const refreshLocks = () => {
    loadLocks();
};

// 锁定区域
const lockSection = async () => {
    if (!selectedSection.value) return;

    isLocking.value = true;

    try {
        const response = await axios.post(`/wiki/${props.pageId}/section-locks`, {
            section_id: selectedSection.value.id,
            section_title: selectedSection.value.title,
            duration: lockDuration.value
        });

        if (response.data.success) {
            await loadLocks();
            emit('section-locked', selectedSection.value.id);
            selectedSection.value = null;
            showLockPanel.value = false;
        }
    } catch (error) {
        console.error('锁定区域失败:', error);
        if (error.response && error.response.status === 409) {
            alert(error.response.data.message);
        } else {
            alert('锁定区域失败，请重试');
        }
    } finally {
        isLocking.value = false;
    }
};

// 解锁区域
const unlockSection = async (sectionId) => {
    try {
        const response = await axios.delete(`/wiki/${props.pageId}/section-locks`, {
            data: { section_id: sectionId }
        });

        if (response.data.success) {
            await loadLocks();
            emit('section-unlocked', sectionId);
        }
    } catch (error) {
        console.error('解锁区域失败:', error);
        alert('解锁区域失败，请重试');
    }
};

// 定时刷新锁定状态
const startAutoRefresh = () => {
    refreshInterval.value = setInterval(() => {
        loadLocks();
    }, 30000); // 每30秒刷新一次
};

onMounted(() => {
    loadLocks();
    startAutoRefresh();
});

onUnmounted(() => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
    }
});
</script>
// FileName: /var/www/Solarmax3Wiki/resources/js/Pages/Wiki/Index.vue
<template>
    <MainLayout
        :navigationLinks="[{ href: '/wiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- 分类导航 -->
            <CategoryNav :categories="categories" :current-category="filters.category" />

            <!-- 页面列表卡片 -->
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden">
                <div class="p-6">
                    <!-- 标题和操作按钮 -->
                    <div class="mb-6 flex justify-between items-center">
                        <h2 class="text-2xl font-semibold text-gray-900">Wiki 页面</h2>
                        <div class="flex gap-2">
                            <Link v-if="$page.props.auth.user?.permissions.includes('wiki.manage_trash')"
                                  :href="route('wiki.trash')"
                                  class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition duration-150 ease-in-out">
                                回收站
                            </Link>
                            <Link v-if="can.create_page" :href="route('wiki.create')"
                                  class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out">
                                创建新页面
                            </Link>
                        </div>
                    </div>

                    <!-- 搜索和筛选 -->
                    <div class="mb-6 flex gap-4 bg-gray-50 p-4 rounded-lg">
                        <div class="flex-1">
                            <input type="text" v-model="form.search" @input="search"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="搜索页面...">
                        </div>
                        <div v-if="can.show_status">
                            <select  v-model="form.status" @change="search"
                                     class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">所有状态</option>
                                <option value="draft">草稿</option>
                                <option value="published">已发布</option>
                            </select>
                        </div>
                    </div>

                    <!-- 页面列表 -->
                    <div class="space-y-6">
                        <div v-for="page in pages.data" :key="page.id"
                             class="border-b border-gray-200 pb-6 last:border-0 last:pb-0">
                            <div class="flex justify-between items-start">
                                <div>
                                    <Link :href="route('wiki.show', page.id)"
                                          class="text-xl font-medium text-blue-600 hover:text-blue-800">
                                        {{ page.title }}
                                    </Link>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <span v-for="category in page.categories" :key="category.id"
                                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ category.name }}
                                        </span>
                                    </div>
                                    <div class="mt-2 text-sm text-gray-500">
                                        <span>{{ formatDate(page.created_at) }}</span>
                                        <span class="mx-2">•</span>
                                        <span>作者: {{ page.creator?.name || '未知' }}</span>
                                        <span class="mx-2">•</span>
                                        <span>浏览: {{ page.view_count }}</span>
                                        <template v-if="page.status === 'audit_failure'">
                                            <span class="mx-2">•</span>
                                            <span style="color: red">审核失败，原因: {{ page.status_message }}</span>
                                        </template>

                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <Link v-if="can.edit_page" :href="route('wiki.edit', page.id)"
                                          class="text-blue-600 hover:text-blue-900">编辑</Link>
                                    <button v-if="page.status === 'draft' && props.uid === +page.created_by" @click="confirmAudit(page)"
                                            class="text-red-600 hover:text-red-900">提交审核</button>


                                    <button v-if="page.status === 'audit_failure' && props.uid === +page.created_by" @click="confirmAudit(page)"
                                            class="text-red-600 hover:text-red-900">重新提交</button>
                                    <button v-if="page.status === 'pending' && props.can.audit_page" @click="audit(page)"
                                            class="text-red-600 hover:text-red-900">页面审核</button>
                                    <button v-if="can.delete_page" @click="confirmDelete(page)"
                                            class="text-red-600 hover:text-red-900">删除</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 分页 -->
                    <div class="mt-6">
                        <Pagination :links="pages.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- 删除确认对话框 -->
        <Modal :show="showDeleteConfirmation" @close="cancelDelete">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    确认删除
                </h3>
                <p class="text-sm text-gray-500 mb-4">
                    确定要删除"{{ pageToDelete?.title }}"吗？此操作无法撤销。
                </p>
                <div class="mt-5 flex justify-end gap-4">
                    <button type="button" @click="cancelDelete"
                            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                        取消
                    </button>
                    <button type="button" @click="deleteConfirmed"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        确认删除
                    </button>
                </div>
            </div>
        </Modal>

        <Modal :show="showAuditModel" @close="cancelShowAuditModel">
            <div class="p-6">
                <h3 class="text-lg font-medium  mb-4" style="color: #fff">
                    页面审核
                </h3>
                <div class="mt-5 flex justify-end gap-4">
                    <button type="button" @click="auditSuccess"
                            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                        通过
                    </button>
                    <button type="button" @click="auditError"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        不通过
                    </button>
                </div>
            </div>
        </Modal>

        <Modal :show="showAuditErrorModel" @close="cancelShowAuditErrorModel">
            <div class="p-6">
                <h3 class="text-lg font-medium  mb-4" style="color: #fff">
                    页面审核失败原因
                </h3>
                <div class="mt-5 flex justify-end gap-4">
                    <textarea class="bg-cyan-950 text-gray-50 mt-1 block w-full" v-model="auditErrorMessage"></textarea>
                </div>
                <div class="mt-5 flex justify-end gap-4">
                    <button type="button" @click="auditErrorConfirm"
                            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                        确认
                    </button>
                    <button type="button" @click="cancelShowAuditErrorModel"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        取消
                    </button>
                </div>
            </div>
        </Modal>

    </MainLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import CategoryNav from '@/Components/Wiki/CategoryNav.vue';
import Pagination from '@/Components/Other/Pagination.vue';
import Modal from '@/Components/Modal/Modal.vue';

const props = defineProps({
    pages: Object,
    categories: Array,
    uid: Number,
    filters: Object,
    can: Object
});

// delete router.query.prev_page_id

const form = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    category: props.filters.category || '',
});



const showDeleteConfirmation = ref(false);
const pageToDelete = ref(null);
const showAuditModel = ref(false);
const showAuditErrorModel = ref(false);
let auditPage = ref(null)
let auditErrorMessage = ref('')

const confirmAudit = (page)=>{
    router.post(route('wiki.audit'), {
        id:page.id,
        status:'pending',
    });
}




const cancelShowAuditModel = ()=>{
    showAuditModel.value = false;
    auditPage.value = null
}

const cancelShowAuditErrorModel = ()=>{
    showAuditErrorModel.value = false;
    cancelShowAuditModel();
}

const auditErrorConfirm = ()=>{
    router.post(route('wiki.audit'), {
        id:auditPage.value.id,
        status:'audit_failure',
        status_message:auditErrorMessage.value,
    });
    cancelShowAuditErrorModel()
}

const auditSuccess = ()=>{
    if (!auditPage.value){
        alert("请选择审核页面");
        return;
    }

    router.post(route('wiki.audit'), {
        id:auditPage.value.id,
        status:'published',
    });

    cancelShowAuditModel();
}

const auditError = ()=>{
    showAuditErrorModel.value = true;
}

const search = () => {
    router.get(route('wiki.index'), form, {
        preserveState: true,
        preserveScroll: true
    });
};
search()
// 显示删除确认对话框
const confirmDelete = (page) => {
    pageToDelete.value = page;
    showDeleteConfirmation.value = true;
};

const audit = (page) => {
    showAuditModel.value = true;
    auditPage.value = page
};

// 取消删除
const cancelDelete = () => {
    showDeleteConfirmation.value = false;
    pageToDelete.value = null;
};

// 确认删除
const deleteConfirmed = () => {
    if (pageToDelete.value) {
        router.delete(route('wiki.destroy', pageToDelete.value.id), {
            onSuccess: () => {
                cancelDelete();
            },
        });
    }
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleString('zh-CN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>

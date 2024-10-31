<!-- 登陆后页面 -->
<script setup>
import AuthenticatedLayout from '@/Layouts/UserLayouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>

    <Head title="Dashboard" />

    <AuthenticatedLayout>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-black bg-opacity-40 shadow-sm sm:rounded-lg">
                    <div class="p-6 text-white">
                        You're logged in!
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

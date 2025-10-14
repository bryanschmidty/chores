<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Super Admin Dashboard" />

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Super Admin Dashboard
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="space-y-6">
                    <!-- System Overview -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">System Overview</h3>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <div class="text-2xl font-bold text-blue-600">{{ stats.total_families }}</div>
                                    <div class="text-sm text-blue-800">Total Families</div>
                                </div>
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <div class="text-2xl font-bold text-green-600">{{ stats.total_users }}</div>
                                    <div class="text-sm text-green-800">Total Users</div>
                                </div>
                                <div class="bg-yellow-50 p-4 rounded-lg">
                                    <div class="text-2xl font-bold text-yellow-600">{{ stats.total_chores }}</div>
                                    <div class="text-sm text-yellow-800">Total Chores</div>
                                </div>
                                <div class="bg-purple-50 p-4 rounded-lg">
                                    <div class="text-2xl font-bold text-purple-600">{{ stats.total_completions }}</div>
                                    <div class="text-sm text-purple-800">Total Completions</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <Link
                                    :href="route('super-admin.families.index')"
                                    class="p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors block"
                                >
                                    <div class="text-blue-600 font-medium">View All Families</div>
                                    <div class="text-sm text-blue-800">Manage family accounts</div>
                                </Link>
                                <button class="p-4 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                                    <div class="text-green-600 font-medium">System Settings</div>
                                    <div class="text-sm text-green-800">Configure global settings</div>
                                </button>
                                <button class="p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors">
                                    <div class="text-purple-600 font-medium">User Management</div>
                                    <div class="text-sm text-purple-800">Manage all users</div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    recentCompletions: Array,
    pendingVerifications: Array,
    pendingRedemptions: Array,
    todaysChores: Array,
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Admin Dashboard" />

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Family Admin Dashboard
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="space-y-6">
                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="text-2xl font-bold text-gray-900">{{ stats.total_members }}</div>
                                <div class="text-sm text-gray-600">Family Members</div>
                            </div>
                        </div>
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="text-2xl font-bold text-yellow-600">{{ stats.pending_verifications }}</div>
                                <div class="text-sm text-gray-600">Pending Verifications</div>
                            </div>
                        </div>
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="text-2xl font-bold text-blue-600">{{ stats.active_chores }}</div>
                                <div class="text-sm text-gray-600">Active Chores</div>
                            </div>
                        </div>
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="text-2xl font-bold text-red-600">{{ stats.overdue_chores }}</div>
                                <div class="text-sm text-gray-600">Overdue Chores</div>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Chores -->
                    <div v-if="todaysChores && todaysChores.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Today's Chores</h3>
                            <div class="space-y-2">
                                <div v-for="chore in todaysChores" :key="chore.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <div class="font-medium">{{ chore.name }}</div>
                                        <div class="text-sm text-gray-600">Assigned to: {{ chore.assigned_to.name }}</div>
                                    </div>
                                    <div class="text-sm font-medium text-blue-600">{{ chore.points }} pts</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Verifications -->
                    <div v-if="pendingVerifications && pendingVerifications.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-yellow-600 mb-4">Pending Verifications</h3>
                            <div class="space-y-2">
                                <div v-for="completion in pendingVerifications" :key="completion.id" class="flex items-center justify-between p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <div>
                                        <div class="font-medium text-yellow-800">{{ completion.chore.name }}</div>
                                        <div class="text-sm text-yellow-600">Completed by: {{ completion.user.name }}</div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button class="px-3 py-1 bg-green-500 text-white rounded text-sm hover:bg-green-600">
                                            Approve
                                        </button>
                                        <button class="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600">
                                            Reject
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Redemptions -->
                    <div v-if="pendingRedemptions && pendingRedemptions.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-purple-600 mb-4">Pending Redemptions</h3>
                            <div class="space-y-2">
                                <div v-for="redemption in pendingRedemptions" :key="redemption.id" class="flex items-center justify-between p-3 bg-purple-50 border border-purple-200 rounded-lg">
                                    <div>
                                        <div class="font-medium text-purple-800">{{ redemption.shop_item.name }}</div>
                                        <div class="text-sm text-purple-600">Requested by: {{ redemption.user.name }}</div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button class="px-3 py-1 bg-green-500 text-white rounded text-sm hover:bg-green-600">
                                            Approve
                                        </button>
                                        <button class="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600">
                                            Reject
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <Link
                                    :href="route('admin.chores.create')"
                                    class="p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors block"
                                >
                                    <div class="text-blue-600 font-medium">Create Chore</div>
                                    <div class="text-sm text-blue-800">Assign new chores</div>
                                </Link>
                                <Link
                                    :href="route('admin.chores.index')"
                                    class="p-4 bg-green-50 hover:bg-green-100 rounded-lg transition-colors block"
                                >
                                    <div class="text-green-600 font-medium">Manage Chores</div>
                                    <div class="text-sm text-green-800">View and edit chores</div>
                                </Link>
                                <Link
                                    :href="route('admin.templates.index')"
                                    class="p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors block"
                                >
                                    <div class="text-purple-600 font-medium">Manage Templates</div>
                                    <div class="text-sm text-purple-800">Edit chore templates</div>
                                </Link>
                                <Link
                                    :href="route('admin.family.index')"
                                    class="p-4 bg-yellow-50 hover:bg-yellow-100 rounded-lg transition-colors block"
                                >
                                    <div class="text-yellow-600 font-medium">Manage Family</div>
                                    <div class="text-sm text-yellow-800">Configure family and members</div>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

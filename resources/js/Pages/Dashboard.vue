<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: Object,
    recentCompletions: Array,
    pendingVerifications: Array,
    pendingRedemptions: Array,
    todaysChores: Array,
    pointsBalance: Number,
    myChores: Object,
    recentTransactions: Array,
    shopItems: Array,
    familyGoals: Array,
});

const user = computed(() => props.stats ? 'super-admin' : props.pendingVerifications ? 'admin' : 'member');
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Super Admin Dashboard -->
                <div v-if="user === 'super-admin'" class="space-y-6">
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
                </div>

                <!-- Admin Dashboard -->
                <div v-else-if="user === 'admin'" class="space-y-6">
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
                </div>

                <!-- Member Dashboard -->
                <div v-else class="space-y-6">
                    <!-- Points Balance -->
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-3xl font-bold">{{ pointsBalance || 0 }}</div>
                                    <div class="text-blue-100">Points Balance</div>
                                </div>
                                <div class="text-4xl opacity-20">
                                    💰
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- My Chores -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Overdue Chores -->
                        <div v-if="myChores?.overdue?.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-red-600 mb-4">Overdue Chores</h3>
                                <div class="space-y-2">
                                    <div v-for="chore in myChores.overdue" :key="chore.id" class="p-3 bg-red-50 border border-red-200 rounded-lg">
                                        <div class="font-medium text-red-800">{{ chore.name }}</div>
                                        <div class="text-sm text-red-600">{{ chore.points }} points</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Today's Chores -->
                        <div v-if="myChores?.today?.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-yellow-600 mb-4">Today's Chores</h3>
                                <div class="space-y-2">
                                    <div v-for="chore in myChores.today" :key="chore.id" class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                        <div class="font-medium text-yellow-800">{{ chore.name }}</div>
                                        <div class="text-sm text-yellow-600">{{ chore.points }} points</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Family Goals -->
                    <div v-if="familyGoals && familyGoals.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Family Goals</h3>
                            <div class="space-y-4">
                                <div v-for="goal in familyGoals" :key="goal.id" class="p-4 bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="font-medium text-gray-900">{{ goal.name }}</div>
                                        <div class="text-sm text-gray-600">{{ goal.current_points }}/{{ goal.target_points }} pts</div>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-gradient-to-r from-purple-500 to-pink-500 h-2 rounded-full" :style="{ width: (goal.current_points / goal.target_points * 100) + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

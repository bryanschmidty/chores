<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    pointsBalance: Number,
    myChores: Object,
    recentCompletions: Array,
    recentTransactions: Array,
    shopItems: Array,
    familyGoals: Array,
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Member Dashboard" />

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                My Dashboard
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="space-y-6">
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
                                        <button class="mt-2 px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600">
                                            Complete Now
                                        </button>
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
                                        <button class="mt-2 px-3 py-1 bg-yellow-500 text-white rounded text-sm hover:bg-yellow-600">
                                            Complete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Chores -->
                    <div v-if="myChores?.upcoming?.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-blue-600 mb-4">Upcoming Chores</h3>
                            <div class="space-y-2">
                                <div v-for="chore in myChores.upcoming" :key="chore.id" class="flex items-center justify-between p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                    <div>
                                        <div class="font-medium text-blue-800">{{ chore.name }}</div>
                                        <div class="text-sm text-blue-600">Due: {{ chore.next_due_date }}</div>
                                    </div>
                                    <div class="text-sm font-medium text-blue-600">{{ chore.points }} pts</div>
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
                                        <div class="bg-gradient-to-r from-purple-500 to-pink-500 h-2 rounded-full" :style="{ width: Math.min(100, (goal.current_points / goal.target_points * 100)) + '%' }"></div>
                                    </div>
                                    <button class="mt-2 px-3 py-1 bg-purple-500 text-white rounded text-sm hover:bg-purple-600">
                                        Contribute Points
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shop Items -->
                    <div v-if="shopItems && shopItems.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Rewards Shop</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div v-for="item in shopItems" :key="item.id" class="p-4 bg-gray-50 rounded-lg">
                                    <div class="font-medium text-gray-900">{{ item.name }}</div>
                                    <div class="text-sm text-gray-600 mb-2">{{ item.description }}</div>
                                    <div class="flex items-center justify-between">
                                        <div class="text-lg font-bold text-green-600">{{ item.cost }} pts</div>
                                        <button class="px-3 py-1 bg-green-500 text-white rounded text-sm hover:bg-green-600">
                                            Redeem
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Activity</h3>
                            <div class="space-y-2">
                                <div v-if="recentCompletions && recentCompletions.length > 0" class="mb-4">
                                    <h4 class="font-medium text-gray-700 mb-2">Recent Completions</h4>
                                    <div class="space-y-1">
                                        <div v-for="completion in recentCompletions" :key="completion.id" class="text-sm text-gray-600">
                                            ✅ {{ completion.chore.name }} - {{ completion.completed_at }}
                                        </div>
                                    </div>
                                </div>
                                <div v-if="recentTransactions && recentTransactions.length > 0" class="mb-4">
                                    <h4 class="font-medium text-gray-700 mb-2">Point Transactions</h4>
                                    <div class="space-y-1">
                                        <div v-for="transaction in recentTransactions" :key="transaction.id" class="text-sm text-gray-600">
                                            {{ transaction.amount > 0 ? '💰' : '💸' }} {{ transaction.description }} - {{ transaction.amount }} pts
                                        </div>
                                    </div>
                                </div>
                                <div v-if="(!recentCompletions || recentCompletions.length === 0) && (!recentTransactions || recentTransactions.length === 0)" class="text-gray-500 text-sm">
                                    No recent activity
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

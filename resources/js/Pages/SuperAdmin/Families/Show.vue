<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    family: Object,
    familyStats: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const formatDateTime = (date) => {
    return new Date(date).toLocaleString();
};

const getRoleColor = (role) => {
    const colors = {
        'super-admin': 'bg-red-100 text-red-800',
        'admin': 'bg-blue-100 text-blue-800',
        'member': 'bg-gray-100 text-gray-800',
    };
    return colors[role] || 'bg-gray-100 text-gray-800';
};

const getRoleText = (role) => {
    const texts = {
        'super-admin': 'Super Admin',
        'admin': 'Admin',
        'member': 'Member',
    };
    return texts[role] || 'Unknown';
};

const getStatusColor = (status) => {
    const colors = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'completed': 'bg-green-100 text-green-800',
        'overdue': 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="`Family: ${family.name}`" />

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ family.name }}
                </h2>
                <div class="flex space-x-2">
                    <Link
                        :href="route('super-admin.families.edit', family.id)"
                        class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded"
                    >
                        Edit Family
                    </Link>
                    <Link
                        :href="route('super-admin.families.index')"
                        class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded"
                    >
                        Back to Families
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="space-y-6">
                    <!-- Family Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="text-2xl font-bold text-blue-600">{{ familyStats.total_members }}</div>
                                <div class="text-sm text-gray-600">Total Members</div>
                            </div>
                        </div>
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="text-2xl font-bold text-purple-600">{{ familyStats.total_admins }}</div>
                                <div class="text-sm text-gray-600">Admins</div>
                            </div>
                        </div>
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="text-2xl font-bold text-green-600">{{ familyStats.total_chores }}</div>
                                <div class="text-sm text-gray-600">Total Chores</div>
                            </div>
                        </div>
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="text-2xl font-bold text-yellow-600">{{ familyStats.active_shop_items }}</div>
                                <div class="text-sm text-gray-600">Shop Items</div>
                            </div>
                        </div>
                    </div>

                    <!-- Family Details -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Family Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <dl class="space-y-2">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Family Name</dt>
                                            <dd class="text-sm text-gray-900">{{ family.name }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Created</dt>
                                            <dd class="text-sm text-gray-900">{{ formatDateTime(family.created_at) }}</dd>
                                        </div>
                                    </dl>
                                </div>
                                <div>
                                    <dl class="space-y-2">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Total Members</dt>
                                            <dd class="text-sm text-gray-900">{{ familyStats.total_members }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Active Goals</dt>
                                            <dd class="text-sm text-gray-900">{{ familyStats.active_goals }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Family Members -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Family Members</h3>
                                <Link
                                    :href="route('super-admin.families.members', family.id)"
                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                >
                                    Manage Members →
                                </Link>
                            </div>
                            
                            <div class="space-y-3">
                                <div v-for="user in family.users" :key="user.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900">{{ user.name }}</div>
                                            <div class="text-sm text-gray-600">{{ user.email }}</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span :class="[
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                            getRoleColor(user.role)
                                        ]">
                                            {{ getRoleText(user.role) }}
                                        </span>
                                        <div class="text-sm text-gray-500">
                                            Joined {{ formatDate(user.created_at) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Chores -->
                    <div v-if="family.chores && family.chores.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Chores</h3>
                            
                            <div class="space-y-3">
                                <div v-for="chore in family.chores.slice(0, 5)" :key="chore.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900">{{ chore.name }}</div>
                                        <div class="text-sm text-gray-600">
                                            Assigned to {{ chore.assigned_to.name }} • Due {{ formatDate(chore.next_due_date) }}
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-blue-600">{{ chore.points }} pts</span>
                                        <span :class="[
                                            'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                                            getStatusColor(chore.status)
                                        ]">
                                            {{ chore.status }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shop Items -->
                    <div v-if="family.shop_items && family.shop_items.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Shop Items</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div v-for="item in family.shop_items.slice(0, 6)" :key="item.id" class="p-4 bg-gray-50 rounded-lg">
                                    <div class="font-medium text-gray-900">{{ item.name }}</div>
                                    <div class="text-sm text-gray-600 mb-2">{{ item.description }}</div>
                                    <div class="text-lg font-bold text-green-600">{{ item.cost }} pts</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Family Goals -->
                    <div v-if="family.family_goals && family.family_goals.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Family Goals</h3>
                            
                            <div class="space-y-4">
                                <div v-for="goal in family.family_goals.slice(0, 3)" :key="goal.id" class="p-4 bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="font-medium text-gray-900">{{ goal.name }}</div>
                                        <div class="text-sm text-gray-600">{{ goal.current_points }}/{{ goal.target_points }} pts</div>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-gradient-to-r from-purple-500 to-pink-500 h-2 rounded-full" :style="{ width: Math.min(100, (goal.current_points / goal.target_points * 100)) + '%' }"></div>
                                    </div>
                                    <div class="mt-2 text-sm text-gray-600">{{ goal.description }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Invites -->
                    <div v-if="family.family_invites && family.family_invites.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Pending Invites</h3>
                            
                            <div class="space-y-2">
                                <div v-for="invite in family.family_invites.filter(i => i.status === 'pending')" :key="invite.id" class="flex items-center justify-between p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <div>
                                        <div class="font-medium text-yellow-800">Invite Code: {{ invite.invite_code }}</div>
                                        <div class="text-sm text-yellow-600">
                                            Role: {{ invite.role }} • Expires: {{ formatDate(invite.expires_at) }}
                                        </div>
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

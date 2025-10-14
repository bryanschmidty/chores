<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    family: Object,
    members: Array,
});

const form = useForm({
    user_id: null,
});

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

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const promoteMember = (userId) => {
    if (confirm('Are you sure you want to promote this member to admin?')) {
        form.user_id = userId;
        form.patch(route('admin.family.promote-member'));
    }
};

const demoteMember = (userId) => {
    if (confirm('Are you sure you want to demote this admin to member?')) {
        form.user_id = userId;
        form.patch(route('admin.family.demote-member'));
    }
};

const removeMember = (userId) => {
    if (confirm('Are you sure you want to remove this member from the family? This action cannot be undone.')) {
        form.user_id = userId;
        form.delete(route('admin.family.remove-member'));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Manage Family Members" />

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Manage Family Members
                </h2>
                <Link
                    :href="route('admin.family.index')"
                    class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded"
                >
                    Back to Family
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="space-y-6">
                    <!-- Family Members List -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Family Members</h3>
                                <p class="text-sm text-gray-600">Manage member roles and permissions</p>
                            </div>

                            <div class="space-y-4">
                                <div v-for="member in members" :key="member.id" class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-3 mb-2">
                                                    <h4 class="text-lg font-medium text-gray-900">{{ member.name }}</h4>
                                                    <span :class="[
                                                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                        getRoleColor(member.role)
                                                    ]">
                                                        {{ getRoleText(member.role) }}
                                                    </span>
                                                </div>
                                                <div class="text-sm text-gray-600 mb-2">{{ member.email }}</div>
                                                <div class="grid grid-cols-2 gap-4 text-sm">
                                                    <div>
                                                        <span class="font-medium text-gray-500">Points Balance:</span>
                                                        <span class="ml-1 text-blue-600 font-medium">{{ member.points_balance }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="font-medium text-gray-500">Joined:</span>
                                                        <span class="ml-1">{{ formatDate(member.created_at) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex space-x-2">
                                            <!-- Promote to Admin (for members) -->
                                            <button
                                                v-if="member.role === 'member'"
                                                @click="promoteMember(member.id)"
                                                class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded text-sm transition-colors"
                                            >
                                                Promote to Admin
                                            </button>

                                            <!-- Demote to Member (for admins, but not super admins) -->
                                            <button
                                                v-if="member.role === 'admin'"
                                                @click="demoteMember(member.id)"
                                                class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded text-sm transition-colors"
                                            >
                                                Demote to Member
                                            </button>

                                            <!-- Remove Member (not for super admins) -->
                                            <DangerButton
                                                v-if="member.role !== 'super-admin'"
                                                @click="removeMember(member.id)"
                                                class="text-sm"
                                            >
                                                Remove
                                            </DangerButton>
                                        </div>
                                    </div>

                                    <!-- Recent Transactions -->
                                    <div v-if="member.recent_transactions && member.recent_transactions.length > 0" class="mt-4 pt-4 border-t border-gray-200">
                                        <h5 class="text-sm font-medium text-gray-700 mb-2">Recent Point Activity</h5>
                                        <div class="space-y-1">
                                            <div v-for="transaction in member.recent_transactions.slice(0, 3)" :key="transaction.id" class="text-sm text-gray-600">
                                                <span :class="transaction.amount > 0 ? 'text-green-600' : 'text-red-600'">
                                                    {{ transaction.amount > 0 ? '+' : '' }}{{ transaction.amount }}
                                                </span>
                                                {{ transaction.description }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Invite New Members -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Invite New Members</h3>
                                <Link
                                    :href="route('family.invites.index')"
                                    class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded"
                                >
                                    Manage Invites
                                </Link>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">
                                Create invite codes to allow new members to join your family. You can set their role and expiration date.
                            </p>
                            <Link
                                :href="route('family.invites.create')"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Create New Invite
                            </Link>
                        </div>
                    </div>

                    <!-- Role Information -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Role Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="p-4 bg-blue-50 rounded-lg">
                                    <h4 class="font-medium text-blue-900 mb-2">Admin</h4>
                                    <ul class="text-sm text-blue-800 space-y-1">
                                        <li>• Create and manage chores</li>
                                        <li>• Manage family settings</li>
                                        <li>• Verify chore completions</li>
                                        <li>• Manage shop items</li>
                                        <li>• Promote/demote members</li>
                                    </ul>
                                </div>
                                <div class="p-4 bg-gray-50 rounded-lg">
                                    <h4 class="font-medium text-gray-900 mb-2">Member</h4>
                                    <ul class="text-sm text-gray-700 space-y-1">
                                        <li>• Complete assigned chores</li>
                                        <li>• View personal points</li>
                                        <li>• Redeem shop items</li>
                                        <li>• Contribute to family goals</li>
                                        <li>• View family activity</li>
                                    </ul>
                                </div>
                                <div class="p-4 bg-red-50 rounded-lg">
                                    <h4 class="font-medium text-red-900 mb-2">Super Admin</h4>
                                    <ul class="text-sm text-red-800 space-y-1">
                                        <li>• All admin permissions</li>
                                        <li>• System-wide access</li>
                                        <li>• Cannot be demoted</li>
                                        <li>• Cannot be removed</li>
                                        <li>• Manage all families</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

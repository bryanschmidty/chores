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
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Family Management" />

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Family Management
            </h2>
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
                                <div class="text-2xl font-bold text-yellow-600">{{ familyStats.pending_invites }}</div>
                                <div class="text-sm text-gray-600">Pending Invites</div>
                            </div>
                        </div>
                    </div>

                    <!-- Family Information -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Family Information</h3>
                                <Link
                                    :href="route('admin.family.edit')"
                                    class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded"
                                >
                                    Edit Settings
                                </Link>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <dl class="space-y-2">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Family Name</dt>
                                            <dd class="text-sm text-gray-900">{{ family.name }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Created</dt>
                                            <dd class="text-sm text-gray-900">{{ formatDate(family.created_at) }}</dd>
                                        </div>
                                    </dl>
                                </div>
                                <div>
                                    <dl class="space-y-2">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Global Verification</dt>
                                            <dd class="text-sm text-gray-900">
                                                <span :class="family.settings?.global_verification ? 'text-orange-600' : 'text-green-600'">
                                                    {{ family.settings?.global_verification ? 'Required' : 'Optional' }}
                                                </span>
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Active Shop Items</dt>
                                            <dd class="text-sm text-gray-900">{{ familyStats.active_shop_items }}</dd>
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
                                    :href="route('admin.family.members')"
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


                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

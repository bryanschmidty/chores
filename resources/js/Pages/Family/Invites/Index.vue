<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    invites: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const formatDateTime = (date) => {
    return new Date(date).toLocaleString();
};

const getStatusColor = (status) => {
    const colors = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'accepted': 'bg-green-100 text-green-800',
        'expired': 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getStatusText = (status) => {
    const texts = {
        'pending': 'Pending',
        'accepted': 'Accepted',
        'expired': 'Expired',
    };
    return texts[status] || 'Unknown';
};

const isExpired = (expiresAt) => {
    return new Date(expiresAt) < new Date();
};

const getRoleText = (role) => {
    const texts = {
        'admin': 'Admin',
        'member': 'Member',
    };
    return texts[role] || 'Unknown';
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Family Invites" />

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Family Invites
                </h2>
                <Link
                    :href="route('family.invites.create')"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                    Create Invite
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="text-2xl font-bold text-blue-600">{{ invites.total }}</div>
                            <div class="text-sm text-gray-600">Total Invites</div>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="text-2xl font-bold text-yellow-600">
                                {{ invites.data.filter(invite => invite.status === 'pending').length }}
                            </div>
                            <div class="text-sm text-gray-600">Pending</div>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="text-2xl font-bold text-green-600">
                                {{ invites.data.filter(invite => invite.status === 'accepted').length }}
                            </div>
                            <div class="text-sm text-gray-600">Accepted</div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="invites.data.length === 0" class="text-center py-12">
                    <div class="mx-auto h-12 w-12 text-gray-400">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 48 48" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M34 40h10v-4a6 6 0 00-10.712-3.714M34 40H14m20 0v-4a9.971 9.971 0 00-.712-3.714M14 40H4v-4a6 6 0 0110.713-3.714M14 40v-4c0-1.313.253-2.566.713-3.714m0 0A9.971 9.971 0 0120 24c2.759 0 5.208.896 7.287 2.286" />
                        </svg>
                    </div>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No invites yet</h3>
                    <p class="mt-1 text-sm text-gray-500">Create your first invite to allow new members to join your family.</p>
                    <div class="mt-6">
                        <Link
                            :href="route('family.invites.create')"
                            class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                        >
                            Create First Invite
                        </Link>
                    </div>
                </div>

                <!-- Invites List -->
                <div v-else class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="space-y-4">
                            <div v-for="invite in invites.data" :key="invite.id" class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-2">
                                            <h3 class="text-lg font-medium text-gray-900">Invite Code: {{ invite.invite_code }}</h3>
                                            <span :class="[
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                getStatusColor(invite.status)
                                            ]">
                                                {{ getStatusText(invite.status) }}
                                            </span>
                                            <span v-if="invite.status === 'pending' && isExpired(invite.expires_at)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Expired
                                            </span>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">
                                            <div>
                                                <span class="font-medium">Role:</span>
                                                {{ getRoleText(invite.role) }}
                                            </div>
                                            <div>
                                                <span class="font-medium">Expires:</span>
                                                {{ formatDate(invite.expires_at) }}
                                            </div>
                                            <div>
                                                <span class="font-medium">Created:</span>
                                                {{ formatDate(invite.created_at) }}
                                            </div>
                                        </div>
                                        
                                        <div v-if="invite.email" class="mt-2 text-sm text-gray-600">
                                            <span class="font-medium">Email:</span> {{ invite.email }}
                                        </div>
                                        
                                        <div v-if="invite.accepted_by" class="mt-2 text-sm text-gray-600">
                                            <span class="font-medium">Accepted by:</span> {{ invite.accepted_by.name }} on {{ formatDate(invite.accepted_by.created_at) }}
                                        </div>
                                    </div>
                                    
                                    <div class="flex space-x-2 ml-4">
                                        <Link
                                            v-if="invite.status === 'pending'"
                                            :href="route('family.invites.show', invite.id)"
                                            class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded text-sm transition-colors"
                                        >
                                            View Details
                                        </Link>
                                        <DangerButton
                                            v-if="invite.status === 'pending'"
                                            class="text-sm"
                                            @click="deleteInvite(invite)"
                                        >
                                            Cancel
                                        </DangerButton>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="invites.links" class="mt-6">
                            <nav class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <p class="text-sm text-gray-700">
                                        Showing {{ invites.from }} to {{ invites.to }} of {{ invites.total }} results
                                    </p>
                                </div>
                                <div class="flex space-x-1">
                                    <template v-for="link in invites.links" :key="link.label">
                                        <Link
                                            v-if="link.url"
                                            :href="link.url"
                                            v-html="link.label"
                                            :class="[
                                                'px-3 py-2 text-sm border rounded-md',
                                                link.active 
                                                    ? 'bg-blue-500 text-white border-blue-500' 
                                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                            ]"
                                        />
                                        <span
                                            v-else
                                            v-html="link.label"
                                            class="px-3 py-2 text-sm text-gray-500 border border-gray-300 rounded-md bg-gray-100"
                                        />
                                    </template>
                                </div>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

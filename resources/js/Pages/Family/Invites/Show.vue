<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    invite: Object,
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

const getRoleText = (role) => {
    const texts = {
        'admin': 'Admin',
        'member': 'Member',
    };
    return texts[role] || 'Unknown';
};

const isExpired = (expiresAt) => {
    return new Date(expiresAt) < new Date();
};

const copyInviteCode = () => {
    navigator.clipboard.writeText(props.invite.invite_code);
    // You could add a toast notification here
    alert('Invite code copied to clipboard!');
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Invite Details" />

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Invite Details
                </h2>
                <div class="flex space-x-2">
                    <Link
                        :href="route('family.invites.index')"
                        class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded"
                    >
                        Back to Invites
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="space-y-6">
                    <!-- Invite Information -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Invite Information</h3>
                                <div class="flex items-center space-x-2">
                                    <span :class="[
                                        'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium',
                                        getStatusColor(invite.status)
                                    ]">
                                        {{ getStatusText(invite.status) }}
                                    </span>
                                    <span v-if="invite.status === 'pending' && isExpired(invite.expires_at)" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                        Expired
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <dl class="space-y-2">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Invite Code</dt>
                                            <dd class="text-sm text-gray-900 font-mono bg-gray-100 p-2 rounded flex items-center justify-between">
                                                <span>{{ invite.invite_code }}</span>
                                                <button
                                                    v-if="invite.status === 'pending'"
                                                    @click="copyInviteCode"
                                                    class="text-blue-600 hover:text-blue-800 text-xs"
                                                >
                                                    Copy
                                                </button>
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Role</dt>
                                            <dd class="text-sm text-gray-900">{{ getRoleText(invite.role) }}</dd>
                                        </div>
                                        <div v-if="invite.email">
                                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                                            <dd class="text-sm text-gray-900">{{ invite.email }}</dd>
                                        </div>
                                    </dl>
                                </div>

                                <div>
                                    <dl class="space-y-2">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Created</dt>
                                            <dd class="text-sm text-gray-900">{{ formatDateTime(invite.created_at) }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Expires</dt>
                                            <dd class="text-sm text-gray-900">{{ formatDateTime(invite.expires_at) }}</dd>
                                        </div>
                                        <div v-if="invite.accepted_by">
                                            <dt class="text-sm font-medium text-gray-500">Accepted by</dt>
                                            <dd class="text-sm text-gray-900">{{ invite.accepted_by.name }} on {{ formatDateTime(invite.accepted_by.created_at) }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- How to Use -->
                    <div v-if="invite.status === 'pending' && !isExpired(invite.expires_at)" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">How to Share This Invite</h3>
                            
                            <div class="space-y-4">
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h4 class="text-sm font-medium text-blue-800">
                                                For New Users (Registration)
                                            </h4>
                                            <div class="mt-2 text-sm text-blue-700">
                                                <p>Share the invite code with someone who doesn't have an account yet. They can use it during registration:</p>
                                                <ol class="mt-2 list-decimal list-inside space-y-1">
                                                    <li>They visit the registration page</li>
                                                    <li>They enter the invite code: <code class="bg-blue-100 px-1 rounded">{{ invite.invite_code }}</code></li>
                                                    <li>They complete registration and automatically join your family</li>
                                                </ol>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h4 class="text-sm font-medium text-green-800">
                                                For Existing Users
                                            </h4>
                                            <div class="mt-2 text-sm text-green-700">
                                                <p>If they already have an account, they can use the invite code in their account settings to join your family.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Actions</h3>
                            
                            <div class="flex flex-wrap gap-4">
                                <button
                                    v-if="invite.status === 'pending'"
                                    @click="copyInviteCode"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Copy Invite Code
                                </button>
                                
                                <DangerButton
                                    v-if="invite.status === 'pending'"
                                    @click="deleteInvite(invite)"
                                    class="text-sm"
                                >
                                    Cancel Invite
                                </DangerButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

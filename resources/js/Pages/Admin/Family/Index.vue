<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { ref } from 'vue';
import VueGoodTable from 'vue-good-table-next';
import 'vue-good-table-next/dist/vue-good-table-next.css';

const props = defineProps({
    family: Object,
    familyStats: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const formatLastLogin = (lastLoggedIn) => {
    if (!lastLoggedIn) {
        return 'Never';
    }
    
    const now = new Date();
    const loginDate = new Date(lastLoggedIn);
    const diffInSeconds = Math.floor((now - loginDate) / 1000);
    
    if (diffInSeconds < 60) {
        return 'Just now';
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `${minutes} minute${minutes !== 1 ? 's' : ''} ago`;
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `${hours} hour${hours !== 1 ? 's' : ''} ago`;
    } else if (diffInSeconds < 2592000) {
        const days = Math.floor(diffInSeconds / 86400);
        return `${days} day${days !== 1 ? 's' : ''} ago`;
    } else {
        return loginDate.toLocaleDateString();
    }
};

const getLastLoginClass = (lastLoggedIn) => {
    if (!lastLoggedIn) {
        return 'text-gray-400 italic';
    }
    
    const now = new Date();
    const loginDate = new Date(lastLoggedIn);
    const diffInHours = Math.floor((now - loginDate) / (1000 * 60 * 60));
    
    if (diffInHours < 24) {
        return 'text-green-600 font-medium';
    } else if (diffInHours < 168) { // 7 days
        return 'text-yellow-600';
    } else {
        return 'text-red-600';
    }
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

const copyInviteLink = async (encryptedId) => {
    const inviteLink = route('invite.user', { encryptedUserId: encryptedId });
    
    // Try modern clipboard API first
    if (navigator.clipboard && window.isSecureContext) {
        try {
            await navigator.clipboard.writeText(inviteLink);
            alert('Invite link copied to clipboard!');
            return;
        } catch (err) {
            console.warn('Clipboard API failed, falling back to legacy method:', err);
        }
    }
    
    // Fallback method for older browsers or non-HTTPS contexts
    try {
        const textArea = document.createElement('textarea');
        textArea.value = inviteLink;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        const successful = document.execCommand('copy');
        document.body.removeChild(textArea);
        
        if (successful) {
            alert('Invite link copied to clipboard!');
        } else {
            // Last resort: show the link in a prompt
            prompt('Copy this invite link:', inviteLink);
        }
    } catch (err) {
        console.error('All copy methods failed:', err);
        // Last resort: show the link in a prompt
        prompt('Copy this invite link:', inviteLink);
    }
};

const columns = [
    {
        label: 'Name',
        field: 'name',
        sortable: true,
    },
    {
        label: 'Email',
        field: 'email',
        sortable: true,
    },
    {
        label: 'Role',
        field: 'role',
        sortable: true,
        formatFn: (value) => {
            const colors = {
                'super-admin': 'bg-red-100 text-red-800',
                'admin': 'bg-blue-100 text-blue-800',
                'member': 'bg-gray-100 text-gray-800',
            };
            const texts = {
                'super-admin': 'Super Admin',
                'admin': 'Admin',
                'member': 'Member',
            };
            return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colors[value] || 'bg-gray-100 text-gray-800'}">${texts[value] || 'Unknown'}</span>`;
        }
    },
    {
        label: 'Points Balance',
        field: 'points_balance',
        sortable: true,
        formatFn: (value) => `<span class="text-blue-600 font-medium">${value}</span>`
    },
    {
        label: 'Joined',
        field: 'created_at',
        sortable: true,
        formatFn: (value) => new Date(value).toLocaleDateString()
    },
    {
        label: 'Actions',
        field: 'actions',
        sortable: false,
        formatFn: (value, row) => `
            <button onclick="window.copyInviteLink('${row.encrypted_id}')" 
                    class="inline-flex items-center px-2 py-1 text-sm text-gray-600 hover:text-blue-600 transition-colors" 
                    title="Copy invite link">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
            </button>
            <a href="/admin/users/${row.id}/edit" 
               class="inline-flex items-center px-2 py-1 text-sm text-gray-600 hover:text-blue-600 transition-colors ml-2" 
               title="Edit user">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
            </a>
        `
    }
];

</script>

<template>
    <AuthenticatedLayout>
        <Head title="Family Management" />

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Family Management
                </h2>
                <Link
                    :href="route('admin.family.edit')"
                    class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded"
                >
                    Edit Family
                </Link>
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
                                <div class="text-2xl font-bold text-yellow-600">{{ familyStats.pending_invites }}</div>
                                <div class="text-sm text-gray-600">Pending Invites</div>
                            </div>
                        </div>
                    </div>

                    <!-- Family Members -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Family Members</h3>
                                <p class="text-sm text-gray-600">Manage family members and their roles</p>
                            </div>
                            
                            <!-- Debug: Show family.users data -->
                            <div v-if="!family.users || family.users.length === 0" class="text-center py-8 text-gray-500">
                                No family members found.
                            </div>
                            
                            <!-- Fallback simple table for debugging -->
                            <div v-if="family.users && family.users.length > 0" class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Points</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="user in family.users" :key="user.id">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ user.name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ user.email }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span :class="[
                                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                    getRoleColor(user.role)
                                                ]">
                                                    {{ getRoleText(user.role) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 font-medium">{{ user.points_balance || 0 }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <span :class="getLastLoginClass(user.last_logged_in)">
                                                    {{ formatLastLogin(user.last_logged_in) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <!-- Only show copy link icon for users who haven't logged in -->
                                                <button
                                                    v-if="!user.last_logged_in"
                                                    @click="copyInviteLink(user.encrypted_id)"
                                                    class="inline-flex items-center px-2 py-1 text-sm text-gray-600 hover:text-blue-600 transition-colors"
                                                    title="Copy invite link"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                    </svg>
                                                </button>
                                                <Link
                                                    :href="route('admin.users.edit', user.id)"
                                                    :class="!user.last_logged_in ? 'inline-flex items-center px-2 py-1 text-sm text-gray-600 hover:text-blue-600 transition-colors ml-2' : 'inline-flex items-center px-2 py-1 text-sm text-gray-600 hover:text-blue-600 transition-colors'"
                                                    title="Edit user"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </Link>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

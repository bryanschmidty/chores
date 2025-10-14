<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    families: Object,
    stats: Object,
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
                <!-- System Stats -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="text-2xl font-bold text-blue-600">{{ stats.total_families }}</div>
                            <div class="text-sm text-gray-600">Total Families</div>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="text-2xl font-bold text-green-600">{{ stats.total_users }}</div>
                            <div class="text-sm text-gray-600">Total Users</div>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="text-2xl font-bold text-purple-600">{{ stats.total_admins }}</div>
                            <div class="text-sm text-gray-600">Admins</div>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="text-2xl font-bold text-yellow-600">{{ stats.total_members }}</div>
                            <div class="text-sm text-gray-600">Members</div>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="text-2xl font-bold text-indigo-600">{{ stats.active_families }}</div>
                            <div class="text-sm text-gray-600">Active Families</div>
                        </div>
                    </div>
                </div>

                <!-- Families List -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-4">
                            <h3 class="text-lg font-medium text-gray-900">All Families</h3>
                        </div>

                        <!-- Empty State -->
                        <div v-if="families.data.length === 0" class="text-center py-12">
                            <div class="mx-auto h-12 w-12 text-gray-400">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 48 48" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M34 40h10v-4a6 6 0 00-10.712-3.714M34 40H14m20 0v-4a9.971 9.971 0 00-.712-3.714M14 40H4v-4a6 6 0 0110.713-3.714M14 40v-4c0-1.313.253-2.566.713-3.714m0 0A9.971 9.971 0 0120 24c2.759 0 5.208.896 7.287 2.286" />
                                </svg>
                            </div>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No families found</h3>
                            <p class="mt-1 text-sm text-gray-500">No families have been created yet.</p>
                        </div>

                        <!-- Families Grid -->
                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="family in families.data" :key="family.id" class="border border-gray-200 rounded-lg p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="text-lg font-medium text-gray-900">{{ family.name }}</h4>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ family.users_count }} members
                                    </span>
                                </div>

                                <!-- Family Stats -->
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-blue-600">{{ family.chores_count }}</div>
                                        <div class="text-xs text-gray-600">Chores</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-purple-600">{{ family.shop_items_count }}</div>
                                        <div class="text-xs text-gray-600">Shop Items</div>
                                    </div>
                                </div>

                                <!-- Family Members Preview -->
                                <div class="mb-4">
                                    <h5 class="text-sm font-medium text-gray-700 mb-2">Members</h5>
                                    <div class="space-y-1">
                                        <div v-for="user in family.users.slice(0, 3)" :key="user.id" class="flex items-center justify-between text-sm">
                                            <span class="text-gray-900">{{ user.name }}</span>
                                            <span :class="[
                                                'inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium',
                                                getRoleColor(user.role)
                                            ]">
                                                {{ getRoleText(user.role) }}
                                            </span>
                                        </div>
                                        <div v-if="family.users.length > 3" class="text-xs text-gray-500">
                                            +{{ family.users.length - 3 }} more
                                        </div>
                                    </div>
                                </div>

                                <!-- Family Info -->
                                <div class="text-xs text-gray-500 mb-4">
                                    Created {{ formatDate(family.created_at) }}
                                </div>

                                <!-- Actions -->
                                <div class="flex space-x-2">
                                    <Link
                                        :href="route('super-admin.families.show', family.id)"
                                        class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded text-center text-sm transition-colors"
                                    >
                                        View
                                    </Link>
                                    <Link
                                        :href="route('super-admin.families.edit', family.id)"
                                        class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded text-center text-sm transition-colors"
                                    >
                                        Edit
                                    </Link>
                                    <DangerButton
                                        class="flex-1 text-sm"
                                        @click="deleteFamily(family)"
                                    >
                                        Delete
                                    </DangerButton>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="families.links" class="mt-6">
                            <nav class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <p class="text-sm text-gray-700">
                                        Showing {{ families.from }} to {{ families.to }} of {{ families.total }} results
                                    </p>
                                </div>
                                <div class="flex space-x-1">
                                    <template v-for="link in families.links" :key="link.label">
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

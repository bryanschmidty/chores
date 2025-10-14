<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { computed } from 'vue';

const props = defineProps({
    chores: Object,
    templates: Array,
    familyMembers: Array,
});

const getStatusColor = (status) => {
    const colors = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'completed': 'bg-green-100 text-green-800',
        'overdue': 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getStatusText = (status) => {
    const texts = {
        'pending': 'Pending',
        'completed': 'Completed',
        'overdue': 'Overdue',
    };
    return texts[status] || 'Unknown';
};

const isOverdue = (dueDate) => {
    return new Date(dueDate) < new Date() && new Date(dueDate).toDateString() !== new Date().toDateString();
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const getRecurrenceText = (type, interval) => {
    if (type === 'none') return 'One-time';
    if (type === 'custom') return `Every ${interval} days`;
    return type.charAt(0).toUpperCase() + type.slice(1);
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Manage Chores" />

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Manage Chores
                </h2>
                <Link
                    :href="route('admin.chores.create')"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                    Create Chore
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="text-2xl font-bold text-blue-600">{{ chores.total }}</div>
                            <div class="text-sm text-gray-600">Total Chores</div>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="text-2xl font-bold text-yellow-600">
                                {{ chores.data.filter(chore => chore.status === 'pending').length }}
                            </div>
                            <div class="text-sm text-gray-600">Pending</div>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="text-2xl font-bold text-green-600">
                            {{ chores.data.filter(chore => chore.status === 'completed').length }}
                        </div>
                        <div class="text-sm text-gray-600">Completed</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="text-2xl font-bold text-red-600">
                                {{ chores.data.filter(chore => isOverdue(chore.next_due_date)).length }}
                            </div>
                            <div class="text-sm text-gray-600">Overdue</div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="chores.data.length === 0" class="text-center py-12">
                    <div class="mx-auto h-12 w-12 text-gray-400">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 48 48" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No chores yet</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating your first chore.</p>
                    <div class="mt-6">
                        <Link
                            :href="route('admin.chores.create')"
                            class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                        >
                            Create First Chore
                        </Link>
                    </div>
                </div>

                <!-- Chores List -->
                <div v-else class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="space-y-4">
                            <div v-for="chore in chores.data" :key="chore.id" class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-2">
                                            <h3 class="text-lg font-medium text-gray-900">{{ chore.name }}</h3>
                                            <span :class="[
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                getStatusColor(chore.status)
                                            ]">
                                                {{ getStatusText(chore.status) }}
                                            </span>
                                            <span v-if="isOverdue(chore.next_due_date)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Overdue
                                            </span>
                                        </div>
                                        
                                        <p v-if="chore.description" class="text-gray-600 mb-2">{{ chore.description }}</p>
                                        
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-600">
                                            <div>
                                                <span class="font-medium">Assigned to:</span>
                                                {{ chore.assigned_to.name }}
                                            </div>
                                            <div>
                                                <span class="font-medium">Due:</span>
                                                {{ formatDate(chore.next_due_date) }}
                                            </div>
                                            <div>
                                                <span class="font-medium">Points:</span>
                                                {{ chore.points }} pts
                                            </div>
                                            <div>
                                                <span class="font-medium">Recurrence:</span>
                                                {{ getRecurrenceText(chore.recurrence_type, chore.recurrence_interval) }}
                                            </div>
                                        </div>
                                        
                                        <div v-if="chore.template" class="mt-2 text-sm text-gray-500">
                                            From template: {{ chore.template.name }}
                                        </div>
                                    </div>
                                    
                                    <div class="flex space-x-2 ml-4">
                                        <Link
                                            :href="route('admin.chores.show', chore.id)"
                                            class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded text-sm transition-colors"
                                        >
                                            View
                                        </Link>
                                        <Link
                                            :href="route('admin.chores.edit', chore.id)"
                                            class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded text-sm transition-colors"
                                        >
                                            Edit
                                        </Link>
                                        <DangerButton
                                            class="text-sm"
                                            @click="deleteChore(chore)"
                                        >
                                            Delete
                                        </DangerButton>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="chores.links" class="mt-6">
                            <nav class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <p class="text-sm text-gray-700">
                                        Showing {{ chores.from }} to {{ chores.to }} of {{ chores.total }} results
                                    </p>
                                </div>
                                <div class="flex space-x-1">
                                    <template v-for="link in chores.links" :key="link.label">
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

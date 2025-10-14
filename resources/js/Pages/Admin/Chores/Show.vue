<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { computed } from 'vue';

const props = defineProps({
    chore: Object,
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

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const formatDateTime = (date) => {
    return new Date(date).toLocaleString();
};

const getRecurrenceText = (type, interval) => {
    if (type === 'none') return 'One-time';
    if (type === 'custom') return `Every ${interval} days`;
    return type.charAt(0).toUpperCase() + type.slice(1);
};

const isOverdue = computed(() => {
    return new Date(props.chore.next_due_date) < new Date() && props.chore.status === 'pending';
});

const photoRequirementsText = computed(() => {
    if (props.chore.template) {
        const requirements = props.chore.template.photo_requirements;
        switch (requirements) {
            case 'none': return 'No photos required';
            case 'after': return 'After photo required';
            case 'both': return 'Before and after photos required';
            default: return 'No photos required';
        }
    }
    return 'No photos required';
});
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="`Chore: ${chore.name}`" />

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ chore.name }}
                </h2>
                <div class="flex space-x-2">
                    <Link
                        :href="route('admin.chores.edit', chore.id)"
                        class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded"
                    >
                        Edit
                    </Link>
                    <Link
                        :href="route('admin.chores.index')"
                        class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded"
                    >
                        Back to Chores
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="space-y-6">
                    <!-- Chore Details Card -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Chore Details</h3>
                                <div class="flex items-center space-x-2">
                                    <span :class="[
                                        'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium',
                                        getStatusColor(chore.status)
                                    ]">
                                        {{ getStatusText(chore.status) }}
                                    </span>
                                    <span v-if="isOverdue" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                        Overdue
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h4 class="font-medium text-gray-900 mb-2">Basic Information</h4>
                                    <dl class="space-y-2">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Name</dt>
                                            <dd class="text-sm text-gray-900">{{ chore.name }}</dd>
                                        </div>
                                        <div v-if="chore.description">
                                            <dt class="text-sm font-medium text-gray-500">Description</dt>
                                            <dd class="text-sm text-gray-900">{{ chore.description }}</dd>
                                        </div>
                                        <div v-if="chore.template">
                                            <dt class="text-sm font-medium text-gray-500">From Template</dt>
                                            <dd class="text-sm text-gray-900">{{ chore.template.name }}</dd>
                                        </div>
                                    </dl>
                                </div>

                                <div>
                                    <h4 class="font-medium text-gray-900 mb-2">Assignment & Points</h4>
                                    <dl class="space-y-2">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Assigned To</dt>
                                            <dd class="text-sm text-gray-900">{{ chore.assigned_to.name }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Points</dt>
                                            <dd class="text-sm text-gray-900">{{ chore.points }} pts</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Created By</dt>
                                            <dd class="text-sm text-gray-900">{{ chore.created_by.name }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule & Requirements Card -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Schedule & Requirements</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h4 class="font-medium text-gray-900 mb-2">Schedule</h4>
                                    <dl class="space-y-2">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Due Date</dt>
                                            <dd class="text-sm text-gray-900">{{ formatDate(chore.next_due_date) }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Recurrence</dt>
                                            <dd class="text-sm text-gray-900">{{ getRecurrenceText(chore.recurrence_type, chore.recurrence_interval) }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Created</dt>
                                            <dd class="text-sm text-gray-900">{{ formatDateTime(chore.created_at) }}</dd>
                                        </div>
                                    </dl>
                                </div>

                                <div>
                                    <h4 class="font-medium text-gray-900 mb-2">Requirements</h4>
                                    <dl class="space-y-2">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Photo Requirements</dt>
                                            <dd class="text-sm text-gray-900">{{ photoRequirementsText }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Verification</dt>
                                            <dd class="text-sm text-gray-900">
                                                <span :class="chore.requires_verification ? 'text-orange-600' : 'text-green-600'">
                                                    {{ chore.requires_verification ? 'Admin verification required' : 'Auto-approved' }}
                                                </span>
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Completion History Card -->
                    <div v-if="chore.completions && chore.completions.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Completion History</h3>
                            
                            <div class="space-y-4">
                                <div v-for="completion in chore.completions" :key="completion.id" class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm font-medium text-gray-900">{{ completion.user.name }}</span>
                                            <span :class="[
                                                'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                                                completion.verification_status === 'approved' ? 'bg-green-100 text-green-800' :
                                                completion.verification_status === 'rejected' ? 'bg-red-100 text-red-800' :
                                                'bg-yellow-100 text-yellow-800'
                                            ]">
                                                {{ completion.verification_status === 'approved' ? 'Approved' :
                                                  completion.verification_status === 'rejected' ? 'Rejected' : 'Pending' }}
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-500">{{ formatDateTime(completion.completed_at) }}</div>
                                    </div>
                                    
                                    <div v-if="completion.verification_status === 'approved'" class="text-sm text-gray-600 mb-2">
                                        Completed {{ completion.completion_percentage }}% - Awarded {{ Math.round((chore.points * completion.completion_percentage) / 100) }} points
                                    </div>
                                    
                                    <div v-if="completion.notes" class="text-sm text-gray-600 mb-2">
                                        Notes: {{ completion.notes }}
                                    </div>
                                    
                                    <div v-if="completion.verifier" class="text-sm text-gray-500">
                                        Verified by: {{ completion.verifier.name }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No Completions Message -->
                    <div v-else class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-center">
                            <div class="mx-auto h-12 w-12 text-gray-400 mb-4">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 48 48" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-medium text-gray-900">No completions yet</h3>
                            <p class="mt-1 text-sm text-gray-500">This chore hasn't been completed yet.</p>
                        </div>
                    </div>

                    <!-- Actions Card -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Actions</h3>
                            
                            <div class="flex flex-wrap gap-4">
                                <Link
                                    :href="route('admin.chores.edit', chore.id)"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Edit Chore
                                </Link>
                                
                                <DangerButton
                                    class="text-sm"
                                    @click="deleteChore(chore)"
                                >
                                    Delete Chore
                                </DangerButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

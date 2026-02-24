<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Modal from '@/Components/Modal.vue';
import { ref } from 'vue';

const props = defineProps({
    availableChores: Array,
    assignedChores: Object,
    isAdmin: Boolean,
    familyMembers: Array,
});

const showView = ref('available'); // 'available' or 'assigned'
const showAssignModal = ref(false);
const selectedChore = ref(null);

const assignChore = (availableChoreId, assignedToId = null) => {
    const data = {
        available_chore_id: availableChoreId,
    };
    
    if (props.isAdmin && assignedToId) {
        data.assigned_to = assignedToId;
    }
    
    router.post(route('chores.assign'), data, {
        preserveScroll: true,
        onSuccess: () => {
            showAssignModal.value = false;
            selectedChore.value = null;
        },
    });
};

const openAssignModal = (availableChore) => {
    selectedChore.value = availableChore;
    showAssignModal.value = true;
};

const closeAssignModal = () => {
    showAssignModal.value = false;
    selectedChore.value = null;
};

const assignToMember = (memberId) => {
    if (selectedChore.value) {
        assignChore(selectedChore.value.id, memberId);
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="My Chores" />

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ isAdmin ? 'Family Chores' : 'My Chores' }}
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Toggle between Available and Assigned -->
                <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4">
                        <div class="flex space-x-4">
                            <button
                                @click="showView = 'available'"
                                :class="[
                                    'px-4 py-2 rounded-lg font-medium transition-colors',
                                    showView === 'available'
                                        ? 'bg-green-500 text-white'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                ]"
                            >
                                Available Chores
                                <span v-if="availableChores && availableChores.length > 0" class="ml-2">
                                    ({{ availableChores.length }})
                                </span>
                            </button>
                            <button
                                @click="showView = 'assigned'"
                                :class="[
                                    'px-4 py-2 rounded-lg font-medium transition-colors',
                                    showView === 'assigned'
                                        ? 'bg-blue-500 text-white'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                ]"
                            >
                                Assigned Chores
                                <span v-if="assignedChores && (assignedChores.overdue?.length + assignedChores.today?.length + assignedChores.upcoming?.length) > 0" class="ml-2">
                                    ({{ (assignedChores.overdue?.length || 0) + (assignedChores.today?.length || 0) + (assignedChores.upcoming?.length || 0) }})
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <!-- Available Chores View -->
                    <div v-show="showView === 'available'">
                        <div v-if="availableChores && availableChores.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-green-600 mb-4">Available Chores</h3>
                                <p class="text-sm text-gray-600 mb-4">
                                    {{ isAdmin ? 'Assign these chores to family members' : 'Pick up these chores to earn points!' }}
                                </p>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div
                                    v-for="availableChore in availableChores"
                                    :key="availableChore.id"
                                    class="p-4 bg-green-50 border border-green-200 rounded-lg"
                                >
                                    <div class="font-medium text-green-800 mb-1">{{ availableChore.chore.name }}</div>
                                    <div v-if="availableChore.chore.description" class="text-sm text-green-700 mb-2">
                                        {{ availableChore.chore.description }}
                                    </div>
                                    <div class="flex items-center justify-between mt-3">
                                        <div class="text-lg font-bold text-green-600">
                                            {{ availableChore.chore.points }} pts
                                        </div>
                                        <PrimaryButton
                                            v-if="!isAdmin"
                                            @click="assignChore(availableChore.id)"
                                            class="bg-green-500 hover:bg-green-600"
                                        >
                                            Pick Up
                                        </PrimaryButton>
                                        <PrimaryButton
                                            v-else
                                            @click="openAssignModal(availableChore)"
                                            class="bg-green-500 hover:bg-green-600"
                                        >
                                            Assign
                                        </PrimaryButton>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>

                        <div v-else class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 text-center text-gray-500">
                                <p>No available chores at the moment. Check back later!</p>
                            </div>
                        </div>
                    </div>

                    <!-- Assigned Chores View -->
                    <div v-show="showView === 'assigned'">
                        <!-- Overdue Chores -->
                    <div v-if="assignedChores?.overdue?.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-red-600 mb-4">Overdue Chores</h3>
                            <div class="space-y-2">
                                <div
                                    v-for="assignedChore in assignedChores.overdue"
                                    :key="assignedChore.id"
                                    class="p-3 bg-red-50 border border-red-200 rounded-lg"
                                >
                                    <div class="font-medium text-red-800">{{ assignedChore.chore?.name }}</div>
                                    <div class="text-sm text-red-600">
                                        Due: {{ assignedChore.due_date }} • {{ assignedChore.chore?.points }} points
                                        <span v-if="isAdmin && assignedChore.assigned_to" class="ml-2">
                                            • Assigned to: {{ assignedChore.assigned_to?.name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Chores -->
                    <div v-if="assignedChores?.today?.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-yellow-600 mb-4">Today's Chores</h3>
                            <div class="space-y-2">
                                <div
                                    v-for="assignedChore in assignedChores.today"
                                    :key="assignedChore.id"
                                    class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg"
                                >
                                    <div class="font-medium text-yellow-800">{{ assignedChore.chore?.name }}</div>
                                    <div class="text-sm text-yellow-600">
                                        {{ assignedChore.chore?.points }} points
                                        <span v-if="isAdmin && assignedChore.assigned_to" class="ml-2">
                                            • Assigned to: {{ assignedChore.assigned_to?.name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Chores -->
                    <div v-if="assignedChores?.upcoming?.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-blue-600 mb-4">Upcoming Chores</h3>
                            <div class="space-y-2">
                                <div
                                    v-for="assignedChore in assignedChores.upcoming"
                                    :key="assignedChore.id"
                                    class="flex items-center justify-between p-3 bg-blue-50 border border-blue-200 rounded-lg"
                                >
                                    <div>
                                        <div class="font-medium text-blue-800">{{ assignedChore.chore?.name }}</div>
                                        <div class="text-sm text-blue-600">
                                            Due: {{ assignedChore.due_date }}
                                            <span v-if="isAdmin && assignedChore.assigned_to" class="ml-2">
                                                • Assigned to: {{ assignedChore.assigned_to?.name }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-sm font-medium text-blue-600">
                                        {{ assignedChore.chore?.points }} pts
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Completed Chores (Recent) -->
                    <div v-if="assignedChores?.completed?.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-600 mb-4">Recently Completed</h3>
                            <div class="space-y-2">
                                <div
                                    v-for="assignedChore in assignedChores.completed"
                                    :key="assignedChore.id"
                                    class="flex items-center justify-between p-3 bg-gray-50 border border-gray-200 rounded-lg"
                                >
                                    <div>
                                        <div class="font-medium text-gray-800">{{ assignedChore.chore?.name }}</div>
                                        <div class="text-sm text-gray-600">
                                            Completed: {{ assignedChore.completed_at }}
                                        </div>
                                    </div>
                                    <div class="text-sm font-medium text-gray-600">
                                        {{ assignedChore.chore?.points }} pts
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                        <!-- Empty State for Assigned -->
                        <div
                            v-if="(!assignedChores?.overdue?.length) &&
                                   (!assignedChores?.today?.length) &&
                                   (!assignedChores?.upcoming?.length) &&
                                   (!assignedChores?.completed?.length)"
                            class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                        >
                            <div class="p-6 text-center text-gray-500">
                                <p>No assigned chores yet.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assign Modal for Admins -->
        <Modal :show="showAssignModal" @close="closeAssignModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Assign Chore: {{ selectedChore?.chore?.name }}
                </h2>
                <p class="text-sm text-gray-600 mb-4">
                    Select a family member to assign this chore to:
                </p>
                
                <div v-if="familyMembers && familyMembers.length > 0" class="space-y-2">
                    <button
                        v-for="member in familyMembers"
                        :key="member.id"
                        @click="assignToMember(member.id)"
                        class="w-full text-left px-4 py-3 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg transition-colors"
                    >
                        <div class="font-medium text-gray-900">{{ member.name }}</div>
                    </button>
                </div>
                <div v-else class="text-sm text-gray-500">
                    No family members available.
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        @click="closeAssignModal"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>


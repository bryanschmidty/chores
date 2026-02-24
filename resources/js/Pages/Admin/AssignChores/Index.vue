<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import TextArea from '@/Components/TextArea.vue';
import SelectInput from '@/Components/SelectInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    familyMembers: Array,
    availableChores: Object,
    assignments: Object,
    weekStartDate: String,
    weekEndDate: String,
});

const showAdhocModal = ref(false);
const selectedMember = ref(null);
const searchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);

const adhocForm = useForm({
    name: '',
    description: '',
    points: 10,
    assigned_to: '',
    review_required: false,
    photos_required: 'none',
    due_date: '',
});

const photosRequiredOptions = [
    { value: 'none', label: 'No photos required' },
    { value: 'only_after', label: 'After photo required' },
    { value: 'before_and_after', label: 'Before and after photos required' },
];

const expandedMembers = ref(new Set());

const toggleMemberExpansion = (memberId) => {
    if (expandedMembers.value.has(memberId)) {
        expandedMembers.value.delete(memberId);
    } else {
        expandedMembers.value.add(memberId);
    }
};

const isMemberExpanded = (memberId) => {
    return expandedMembers.value.has(memberId);
};

const getMemberAssignments = (memberId) => {
    return props.assignments[memberId] || [];
};

const getMemberStats = (memberId) => {
    const assignments = getMemberAssignments(memberId);
    const totalChores = assignments.length;
    const totalPoints = assignments.reduce((sum, assignment) => {
        return sum + assignment.chore.points;
    }, 0);
    
    return { totalChores, totalPoints };
};

const draggedChore = ref(null);

const handleDragStart = (event, chore) => {
    draggedChore.value = chore;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', JSON.stringify(chore));
};

const handleDragOver = (event) => {
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
};

const handleDragEnter = (event) => {
    event.preventDefault();
    event.target.classList.add('bg-blue-50', 'border-blue-300');
};

const handleDragLeave = (event) => {
    event.target.classList.remove('bg-blue-50', 'border-blue-300');
};

const handleDrop = async (event, memberId) => {
    event.preventDefault();
    event.target.classList.remove('bg-blue-50', 'border-blue-300');
    
    if (!draggedChore.value) return;
    
    // Create assignment via API
    const form = useForm({
        chore_id: draggedChore.value.id,
        assigned_to: memberId,
        week_start_date: props.weekStartDate,
    });
    
    form.post(route('admin.assign-chores.assign'), {
        onSuccess: () => {
            // Reload the page to get fresh data
            window.location.reload();
        },
        onError: (errors) => {
            console.error('Assignment failed:', errors);
        }
    });
};

const handleDragEnd = () => {
    draggedChore.value = null;
};

const openAdhocModal = (memberId) => {
    selectedMember.value = memberId;
    adhocForm.assigned_to = memberId;
    adhocForm.due_date = new Date().toISOString().split('T')[0]; // Today's date
    showAdhocModal.value = true;
};

const closeAdhocModal = () => {
    showAdhocModal.value = false;
    selectedMember.value = null;
    adhocForm.reset();
    searchQuery.value = '';
    searchResults.value = [];
};

const submitAdhocForm = () => {
    adhocForm.post(route('admin.assign-chores.adhoc'), {
        onSuccess: () => {
            closeAdhocModal();
            // Reload the page to get fresh data
            window.location.reload();
        },
    });
};

const searchAdhocChores = async () => {
    if (!searchQuery.value.trim()) {
        searchResults.value = [];
        return;
    }
    
    isSearching.value = true;
    
    try {
        const response = await fetch(route('admin.assign-chores.adhoc.search', { q: searchQuery.value }));
        const results = await response.json();
        searchResults.value = results;
    } catch (error) {
        console.error('Search error:', error);
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
};

const selectPreviousChore = (chore) => {
    adhocForm.name = chore.name;
    adhocForm.description = chore.description;
    adhocForm.points = chore.points;
    adhocForm.review_required = chore.review_required;
    adhocForm.photos_required = chore.photos_required;
    searchResults.value = [];
    searchQuery.value = '';
};

const removeAssignment = (assignmentId) => {
    if (confirm('Are you sure you want to remove this assignment?')) {
        useForm().delete(route('admin.assign-chores.unassign', assignmentId), {
            onSuccess: () => {
                // Reload the page to get fresh data
                window.location.reload();
            },
            onError: (errors) => {
                console.error('Removal failed:', errors);
            }
        });
    }
};

const navigateWeek = (direction) => {
    const currentWeek = new Date(props.weekStartDate);
    const newWeek = new Date(currentWeek);
    newWeek.setDate(currentWeek.getDate() + (direction === 'next' ? 7 : -7));
    
    const newWeekStart = newWeek.toISOString().split('T')[0];
    window.location.href = route('admin.assign-chores.index', { week: newWeekStart });
};

const formatDate = (dateString) => {
    // Now we know dateString is always in YYYY-MM-DD format
    const [year, month, day] = dateString.split('-');
    const date = new Date(year, month - 1, day);
    return date.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric'
    });
};

// Debounce search
let searchTimeout;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(searchAdhocChores, 300);
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Assign Chores" />

        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <button
                        @click="navigateWeek('prev')"
                        class="p-2 text-gray-400 hover:text-gray-600"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Week of {{ formatDate(weekStartDate) }}
                    </h2>
                    <button
                        @click="navigateWeek('next')"
                        class="p-2 text-gray-400 hover:text-gray-600"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
                <PrimaryButton @click="openAdhocModal()">
                    Create Ad Hoc Chore
                </PrimaryButton>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Family Members -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">Family Members</h3>
                        
                        <div v-if="familyMembers.length === 0" class="text-center py-8 text-gray-500">
                            No family members found. Add members to assign chores.
                        </div>
                        
                        <div v-else class="space-y-3">
                            <div
                                v-for="member in familyMembers"
                                :key="member.id"
                                class="bg-white border border-gray-200 rounded-lg p-4 transition-colors"
                                @dragover="handleDragOver"
                                @dragenter="handleDragEnter"
                                @dragleave="handleDragLeave"
                                @drop="handleDrop($event, member.id)"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-medium">
                                            {{ member.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-gray-900">{{ member.name }}</h4>
                                            <p class="text-sm text-gray-500">
                                                {{ getMemberStats(member.id).totalChores }} chores • 
                                                {{ getMemberStats(member.id).totalPoints }} points
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-2">
                                        <button
                                            @click="openAdhocModal(member.id)"
                                            class="text-blue-600 hover:text-blue-800 text-sm"
                                        >
                                            + Ad Hoc
                                        </button>
                                        <button
                                            @click="toggleMemberExpansion(member.id)"
                                            class="p-1 text-gray-400 hover:text-gray-600"
                                        >
                                            <svg
                                                class="w-4 h-4 transition-transform"
                                                :class="{ 'rotate-180': isMemberExpanded(member.id) }"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Assigned Chores (Expandable) -->
                                <div v-if="isMemberExpanded(member.id)" class="mt-4 space-y-2">
                                    <div v-if="getMemberAssignments(member.id).length === 0" class="text-sm text-gray-500 italic">
                                        No chores assigned this week
                                    </div>
                                    <div v-else class="space-y-2">
                                        <div
                                            v-for="assignment in getMemberAssignments(member.id)"
                                            :key="assignment.id"
                                            class="flex items-center justify-between bg-gray-50 p-2 rounded"
                                        >
                                            <div>
                                                <span class="font-medium">{{ assignment.chore.name }}</span>
                                                <span class="text-sm text-gray-500 ml-2">
                                                    {{ assignment.chore.points }} pts • {{ formatDate(assignment.due_date) }}
                                                </span>
                                            </div>
                                            <button
                                                @click="removeAssignment(assignment.id)"
                                                class="text-red-600 hover:text-red-800 text-sm"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Available Chores -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">Available Chores</h3>
                        
                        <div v-if="Object.keys(availableChores).length === 0" class="text-center py-8 text-gray-500">
                            No chores available this week. Create chores first.
                        </div>
                        
                        <div v-else class="space-y-4">
                            <div
                                v-for="(chores, frequency) in availableChores"
                                :key="frequency"
                                class="bg-white border border-gray-200 rounded-lg p-4"
                            >
                                <h4 class="font-medium text-gray-900 mb-3">{{ frequency }}</h4>
                                <div class="space-y-2">
                                    <div
                                        v-for="chore in chores"
                                        :key="chore.id"
                                        class="flex items-center justify-between bg-gray-50 p-3 rounded cursor-move hover:bg-gray-100 transition-colors"
                                        draggable="true"
                                        @dragstart="handleDragStart($event, chore)"
                                        @dragend="handleDragEnd"
                                    >
                                        <div>
                                            <span class="font-medium">{{ chore.name }}</span>
                                            <span class="text-sm text-gray-500 ml-2">{{ chore.points }} pts</span>
                                        </div>
                                        <div class="text-xs text-gray-400">
                                            Drag to assign
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ad Hoc Chore Modal -->
        <Modal :show="showAdhocModal" @close="closeAdhocModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Create Ad Hoc Chore</h2>
                
                <!-- Search Previous Ad Hoc Chores -->
                <div class="mb-6">
                    <InputLabel for="search" value="Search Previous Ad Hoc Chores" />
                    <TextInput
                        id="search"
                        v-model="searchQuery"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="Type to search..."
                        @input="debouncedSearch"
                    />
                    
                    <div v-if="searchResults.length > 0" class="mt-2 space-y-1">
                        <div
                            v-for="chore in searchResults"
                            :key="chore.id"
                            @click="selectPreviousChore(chore)"
                            class="p-2 bg-gray-50 rounded cursor-pointer hover:bg-gray-100"
                        >
                            <div class="font-medium">{{ chore.name }}</div>
                            <div class="text-sm text-gray-500">{{ chore.points }} pts</div>
                        </div>
                    </div>
                </div>
                
                <form @submit.prevent="submitAdhocForm">
                    <div class="space-y-4">
                        <div>
                            <InputLabel for="name" value="Chore Name" />
                            <TextInput
                                id="name"
                                v-model="adhocForm.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                autofocus
                            />
                            <InputError class="mt-2" :message="adhocForm.errors.name" />
                        </div>

                        <div>
                            <InputLabel for="description" value="Description" />
                            <TextArea
                                id="description"
                                v-model="adhocForm.description"
                                class="mt-1 block w-full"
                                rows="3"
                            />
                            <InputError class="mt-2" :message="adhocForm.errors.description" />
                        </div>

                        <div>
                            <InputLabel for="points" value="Points" />
                            <TextInput
                                id="points"
                                v-model="adhocForm.points"
                                type="number"
                                min="1"
                                max="1000"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="adhocForm.errors.points" />
                        </div>

                        <div>
                            <InputLabel for="due_date" value="Due Date" />
                            <TextInput
                                id="due_date"
                                v-model="adhocForm.due_date"
                                type="date"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="adhocForm.errors.due_date" />
                        </div>

                        <div>
                            <InputLabel for="photos_required" value="Photo Requirements" />
                            <SelectInput
                                id="photos_required"
                                v-model="adhocForm.photos_required"
                                class="mt-1 block w-full"
                                required
                            >
                                <option v-for="option in photosRequiredOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </SelectInput>
                            <InputError class="mt-2" :message="adhocForm.errors.photos_required" />
                        </div>

                        <div class="flex items-center">
                            <Checkbox
                                id="review_required"
                                v-model:checked="adhocForm.review_required"
                            />
                            <InputLabel for="review_required" value="Requires Review" class="ml-2" />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="closeAdhocModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Cancel
                        </button>
                        <PrimaryButton :disabled="adhocForm.processing">
                            Create & Assign
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

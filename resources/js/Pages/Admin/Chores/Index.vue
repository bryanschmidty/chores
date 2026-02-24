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
import { ref } from 'vue';

const props = defineProps({
    chores: Array,
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingChore = ref(null);

const createForm = useForm({
    name: '',
    description: '',
    points: 10,
    frequency: 'weekly',
    review_required: false,
    photos_required: 'none',
});

const editForm = useForm({
    name: '',
    description: '',
    points: 10,
    frequency: 'weekly',
    review_required: false,
    photos_required: 'none',
});

const frequencyOptions = [
    { value: 'daily', label: 'Daily' },
    { value: 'twice_weekly', label: 'Twice Weekly' },
    { value: 'weekly', label: 'Weekly' },
    { value: 'twice_monthly', label: 'Twice Monthly' },
    { value: 'monthly', label: 'Monthly' },
    { value: 'adhoc', label: 'Ad Hoc' },
];

const photosRequiredOptions = [
    { value: 'none', label: 'No photos required' },
    { value: 'only_after', label: 'After photo required' },
    { value: 'before_and_after', label: 'Before and after photos required' },
];

const openCreateModal = () => {
    createForm.reset();
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
};

const submitCreateForm = () => {
    createForm.post(route('admin.chores.store'), {
        onSuccess: () => {
            closeCreateModal();
        },
    });
};

const openEditModal = (chore) => {
    editingChore.value = chore;
    editForm.name = chore.name;
    editForm.description = chore.description;
    editForm.points = chore.points;
    editForm.frequency = chore.frequency;
    editForm.review_required = chore.review_required;
    editForm.photos_required = chore.photos_required;
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingChore.value = null;
    editForm.reset();
};

const submitEditForm = () => {
    editForm.put(route('admin.chores.update', editingChore.value.id), {
        onSuccess: () => {
            closeEditModal();
        },
    });
};

const deleteChore = (chore) => {
    if (confirm('Are you sure you want to delete this chore? This action cannot be undone.')) {
        useForm().delete(route('admin.chores.destroy', chore.id));
    }
};

const getFrequencyText = (frequency) => {
    const option = frequencyOptions.find(opt => opt.value === frequency);
    return option ? option.label : frequency;
};

const getPhotosRequiredText = (photosRequired) => {
    const option = photosRequiredOptions.find(opt => opt.value === photosRequired);
    return option ? option.label : photosRequired;
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
                <PrimaryButton @click="openCreateModal">
                    Create Chore
                </PrimaryButton>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="text-2xl font-bold text-blue-600">{{ chores.length }}</div>
                            <div class="text-sm text-gray-600">Total Chores</div>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="text-2xl font-bold text-green-600">
                                {{ chores.filter(chore => chore.frequency !== 'adhoc').length }}
                            </div>
                            <div class="text-sm text-gray-600">Recurring Chores</div>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="text-2xl font-bold text-purple-600">
                                {{ chores.filter(chore => chore.frequency === 'adhoc').length }}
                            </div>
                            <div class="text-sm text-gray-600">Ad Hoc Chores</div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="chores.length === 0" class="text-center py-12">
                    <div class="mx-auto h-12 w-12 text-gray-400">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 48 48" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No chores yet</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating your first chore.</p>
                    <div class="mt-6">
                        <PrimaryButton @click="openCreateModal">
                            Create First Chore
                        </PrimaryButton>
                    </div>
                </div>

                <!-- Chores List -->
                <div v-else class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="space-y-4">
                            <div v-for="chore in chores" :key="chore.id" class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-2">
                                            <h3 class="text-lg font-medium text-gray-900">{{ chore.name }}</h3>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ getFrequencyText(chore.frequency) }}
                                            </span>
                                            <span v-if="chore.review_required" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                Review Required
                                            </span>
                                        </div>
                                        
                                        <p v-if="chore.description" class="text-gray-600 mb-2">{{ chore.description }}</p>
                                        
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-600">
                                            <div>
                                                <span class="font-medium">Points:</span>
                                                {{ chore.points }} pts
                                            </div>
                                            <div>
                                                <span class="font-medium">Frequency:</span>
                                                {{ getFrequencyText(chore.frequency) }}
                                            </div>
                                            <div>
                                                <span class="font-medium">Photos:</span>
                                                {{ getPhotosRequiredText(chore.photos_required) }}
                                            </div>
                                            <div>
                                                <span class="font-medium">Assignments:</span>
                                                {{ chore.assigned_chores ? chore.assigned_chores.length : 0 }}
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex space-x-2 ml-4">
                                        <PrimaryButton
                                            @click="openEditModal(chore)"
                                            class="text-sm"
                                        >
                                            Edit
                                        </PrimaryButton>
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
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Modal :show="showCreateModal" @close="closeCreateModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Create New Chore</h2>
                
                <form @submit.prevent="submitCreateForm">
                    <div class="space-y-4">
                        <div>
                            <InputLabel for="name" value="Chore Name" />
                            <TextInput
                                id="name"
                                v-model="createForm.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                autofocus
                            />
                            <InputError class="mt-2" :message="createForm.errors.name" />
                        </div>

                        <div>
                            <InputLabel for="description" value="Description" />
                            <TextArea
                                id="description"
                                v-model="createForm.description"
                                class="mt-1 block w-full"
                                rows="3"
                            />
                            <InputError class="mt-2" :message="createForm.errors.description" />
                        </div>

                        <div>
                            <InputLabel for="points" value="Points" />
                            <TextInput
                                id="points"
                                v-model="createForm.points"
                                type="number"
                                min="1"
                                max="1000"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="createForm.errors.points" />
                        </div>

                        <div>
                            <InputLabel for="frequency" value="Frequency" />
                            <SelectInput
                                id="frequency"
                                v-model="createForm.frequency"
                                class="mt-1 block w-full"
                                required
                            >
                                <option v-for="option in frequencyOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </SelectInput>
                            <InputError class="mt-2" :message="createForm.errors.frequency" />
                        </div>

                        <div>
                            <InputLabel for="photos_required" value="Photo Requirements" />
                            <SelectInput
                                id="photos_required"
                                v-model="createForm.photos_required"
                                class="mt-1 block w-full"
                                required
                            >
                                <option v-for="option in photosRequiredOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </SelectInput>
                            <InputError class="mt-2" :message="createForm.errors.photos_required" />
                        </div>

                        <div class="flex items-center">
                            <Checkbox
                                id="review_required"
                                v-model:checked="createForm.review_required"
                            />
                            <InputLabel for="review_required" value="Requires Review" class="ml-2" />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="closeCreateModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Cancel
                        </button>
                        <PrimaryButton :disabled="createForm.processing">
                            Create Chore
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Edit Modal -->
        <Modal :show="showEditModal" @close="closeEditModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Edit Chore</h2>
                
                <form @submit.prevent="submitEditForm">
                    <div class="space-y-4">
                        <div>
                            <InputLabel for="edit_name" value="Chore Name" />
                            <TextInput
                                id="edit_name"
                                v-model="editForm.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                autofocus
                            />
                            <InputError class="mt-2" :message="editForm.errors.name" />
                        </div>

                        <div>
                            <InputLabel for="edit_description" value="Description" />
                            <TextArea
                                id="edit_description"
                                v-model="editForm.description"
                                class="mt-1 block w-full"
                                rows="3"
                            />
                            <InputError class="mt-2" :message="editForm.errors.description" />
                        </div>

                        <div>
                            <InputLabel for="edit_points" value="Points" />
                            <TextInput
                                id="edit_points"
                                v-model="editForm.points"
                                type="number"
                                min="1"
                                max="1000"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="editForm.errors.points" />
                        </div>

                        <div>
                            <InputLabel for="edit_frequency" value="Frequency" />
                            <SelectInput
                                id="edit_frequency"
                                v-model="editForm.frequency"
                                class="mt-1 block w-full"
                                required
                            >
                                <option v-for="option in frequencyOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </SelectInput>
                            <InputError class="mt-2" :message="editForm.errors.frequency" />
                        </div>

                        <div>
                            <InputLabel for="edit_photos_required" value="Photo Requirements" />
                            <SelectInput
                                id="edit_photos_required"
                                v-model="editForm.photos_required"
                                class="mt-1 block w-full"
                                required
                            >
                                <option v-for="option in photosRequiredOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </SelectInput>
                            <InputError class="mt-2" :message="editForm.errors.photos_required" />
                        </div>

                        <div class="flex items-center">
                            <Checkbox
                                id="edit_review_required"
                                v-model:checked="editForm.review_required"
                            />
                            <InputLabel for="edit_review_required" value="Requires Review" class="ml-2" />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="closeEditModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Cancel
                        </button>
                        <PrimaryButton :disabled="editForm.processing">
                            Update Chore
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>